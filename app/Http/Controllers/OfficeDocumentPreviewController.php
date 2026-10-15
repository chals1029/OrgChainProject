<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Services\OfficeDocumentConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class OfficeDocumentPreviewController extends Controller
{
    public function convert(Request $request, OfficeDocumentConverter $converter)
    {
        $actor = Auth::guard('office')->user();
        abort_unless($actor instanceof OfficeUser && $actor->is_active && in_array($actor->office_role, ['so', 'oso', 'sdo', 'ovcaa', 'oc'], true), 403);
        $request->validate(['file' => ['required', 'file', 'max:20480']]);
        $file = $request->file('file');
        $mime = strtolower((string) $file->getMimeType());
        $allowedMimes = ['application/msword', 'application/vnd.ms-word', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/cdfv2', 'application/x-cdf', 'application/octet-stream'];
        if (strtolower($file->getClientOriginalExtension()) !== 'doc' || ! in_array($mime, $allowedMimes, true)
            || ! $this->isWordBinary($file->getRealPath())) {
            return response()->json(['message' => 'Choose a genuine, non-empty Word 97–2003 DOC file no larger than 20 MB. Renaming another file to .doc is not supported.'], 422)
                ->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
        }
        $result = null;
        try {
            // Uploaded temporary paths have no extension. Copy the actual bytes
            // to an owned .doc input; never persist a submission or document row.
            $directory = storage_path('app/private/office-document-preview/'.bin2hex(random_bytes(20)));
            if (! is_dir(dirname($directory)) && ! mkdir(dirname($directory), 0700, true) && ! is_dir(dirname($directory))) {
                throw new RuntimeException('The document preview could not prepare its private workspace.');
            }
            if (! mkdir($directory, 0700)) {
                throw new RuntimeException('The document preview could not prepare its private workspace.');
            }
            try {
                $source = $directory.'/document.doc';
                if (! copy($file->getRealPath(), $source)) {
                    throw new RuntimeException('The document preview could not read the uploaded bytes.');
                }
                if (PHP_OS_FAMILY !== 'Windows') chmod($source, 0600);
                $result = $converter->toPdf($source);
            } finally {
                if (is_file($directory.'/document.doc')) unlink($directory.'/document.doc');
                rmdir($directory);
            }
        } catch (Throwable $error) {
            if (is_array($result)) $converter->release($result);
            $message = $error instanceof RuntimeException ? $error->getMessage()
                : 'Document conversion failed. Ask the administrator to check the isolated converter and private workspace, or save a DOCX/PDF and try again.';
            return response()->json(['message' => $message], 503)
                ->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
        }
        return response()->stream(function () use ($result, $converter): void {
            $stream = null;
            try {
                // Cleanup must run even when the client disconnects mid-stream.
                $previous = ignore_user_abort(true);
                $stream = fopen($result['path'], 'rb');
                if ($stream !== false) fpassthru($stream);
            } finally {
                if (is_resource($stream)) fclose($stream);
                $converter->release($result);
                if (isset($previous)) ignore_user_abort((bool) $previous);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="document-preview.pdf"',
            'Content-Length' => (string) filesize($result['path']),
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Verify CFB structure and the real WordDocument stream's FIB signature. */
    private function isWordBinary(string $path): bool
    {
        $bytes = file_get_contents($path);
        if ($bytes === false || strlen($bytes) < 512 || substr($bytes, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") return false;
        $u16 = static fn (string $data, int $offset): int => unpack('v', substr($data, $offset, 2))[1];
        $u32 = static fn (string $data, int $offset): int => unpack('V', substr($data, $offset, 4))[1];
        $shift = $u16($bytes, 30);
        if ($u16($bytes, 28) !== 0xfffe || ! in_array($shift, [9, 12], true) || $u16($bytes, 32) !== 6) return false;
        $size = 1 << $shift;
        $sectors = intdiv(strlen($bytes), $size) - 1;
        $sector = static function (int $id) use ($bytes, $size, $sectors): ?string {
            return $id >= 0 && $id < $sectors ? substr($bytes, ($id + 1) * $size, $size) : null;
        };
        $fatIds = [];
        for ($i = 0; $i < 109; $i++) {
            $id = $u32($bytes, 76 + $i * 4);
            if ($id < $sectors) $fatIds[] = $id;
        }
        $difat = $u32($bytes, 68);
        $seen = [];
        for ($i = 0; $i < $u32($bytes, 72); $i++) {
            if (isset($seen[$difat]) || ($part = $sector($difat)) === null) return false;
            $seen[$difat] = true;
            for ($j = 0; $j < $size - 4; $j += 4) {
                $id = $u32($part, $j);
                if ($id < $sectors) $fatIds[] = $id;
            }
            $difat = $u32($part, $size - 4);
        }
        if (count($fatIds) !== $u32($bytes, 44) || count($fatIds) !== count(array_unique($fatIds))) return false;
        // Resolve only traversed FAT entries; do not expand an untrusted FAT into millions of PHP integers.
        $entriesPerSector = intdiv($size, 4);
        $nextSector = static function (int $id) use ($fatIds, $entriesPerSector, $bytes, $size, $u32): ?int {
            $fatId = $fatIds[intdiv($id, $entriesPerSector)] ?? null;
            return $fatId === null ? null : $u32($bytes, ($fatId + 1) * $size + ($id % $entriesPerSector) * 4);
        };
        $chain = static function (int $id, int $limit) use ($sector, $nextSector, $size): ?string {
            $result = '';
            $visited = [];
            while ($id !== 0xfffffffe) {
                if (isset($visited[$id]) || ($next = $nextSector($id)) === null || strlen($result) + $size > $limit || ($part = $sector($id)) === null) return null;
                $visited[$id] = true;
                $result .= $part;
                $id = $next;
            }
            return $result;
        };
        $directory = $chain($u32($bytes, 48), 1024 * 1024);
        if ($directory === null) return false;
        $word = null;
        $root = null;
        for ($offset = 0; $offset + 128 <= strlen($directory); $offset += 128) {
            $entry = substr($directory, $offset, 128);
            $length = $u16($entry, 64);
            if ($length < 2 || $length > 64) continue;
            if (ord($entry[66]) === 5) $root = $entry;
            if (ord($entry[66]) === 2 && substr($entry, 0, $length - 2) === "W\0o\0r\0d\0D\0o\0c\0u\0m\0e\0n\0t\0") $word = $entry;
        }
        if ($word === null) return false;
        // Directory entries store start sector at 116 and 64-bit size at 120.
        $start = $u32($word, 116);
        $length = $u32($word, 120);
        if ($u32($word, 124) !== 0 || $length < 32 || $length > strlen($bytes)) return false;
        if ($length >= $u32($bytes, 56)) {
            $prefix = $sector($start);
        } else {
            if ($root === null) return false;
            $miniStream = $chain($u32($root, 116), strlen($bytes));
            $prefix = $miniStream === null ? null : substr($miniStream, $start * 64, 64);
        }
        return $prefix !== null && strlen($prefix) >= 4 && $u16($prefix, 0) === 0xa5ec
            && $u16($prefix, 2) >= 0x0065 && $u16($prefix, 2) <= 0x0112;
    }
}
