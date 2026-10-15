<?php

namespace App\Services\Health;

use App\Services\OfficeDocumentConverter;
use RuntimeException;
use Throwable;

class DocumentReadinessProbe
{
    public function __construct(private readonly OfficeDocumentConverter $converter) {}

    public function check(string $service): string
    {
        if (! in_array($service, ['private-storage', 'document-processing'], true)) {
            return 'unknown';
        }

        $workspace = null;
        $source = null;
        $converted = null;
        $handle = null;
        $healthy = false;
        $clean = true;

        try {
            // Never leave root-owned probe directories for the web/timer user.
            if (PHP_OS_FAMILY !== 'Windows' && (! function_exists('posix_geteuid') || posix_geteuid() === 0)) {
                throw new RuntimeException();
            }

            $this->assertDirectoryPath(storage_path());
            $this->ensureDirectory(storage_path('app'));
            $this->ensureDirectory(storage_path('app/private'));
            $this->ensureDirectory(storage_path('app/private/system-health'), true);
            $root = storage_path('app/private/system-health/probes');
            $this->ensureDirectory($root, true);

            $directory = $root.'/'.bin2hex(random_bytes(24));
            if (! @mkdir($directory, 0700)) {
                throw new RuntimeException();
            }
            $workspace = $this->identity($directory);
            $this->assertPrivateDirectory($directory);

            $document = $service === 'document-processing';
            $marker = 'OrgChainHealth'.bin2hex(random_bytes(20));
            $content = $document
                ? '{\\rtf1\\ansi\\deff0{\\fonttbl{\\f0 Arial;}}\\f0\\fs20 '.$marker.'\\par}'
                : random_bytes(128);
            $path = $directory.'/'.bin2hex(random_bytes(20)).($document ? '.rtf' : '.bin');
            $handle = @fopen($path, 'x+b');
            if ($handle === false) {
                $handle = null;
                throw new RuntimeException();
            }
            $stat = @fstat($handle);
            if ($stat === false) {
                throw new RuntimeException();
            }
            $source = ['path' => $path, 'stat' => $stat];
            if ((PHP_OS_FAMILY !== 'Windows' && ! @chmod($path, 0600))
                || @fwrite($handle, $content) !== strlen($content)
                || ! @fflush($handle)) {
                throw new RuntimeException();
            }
            if (! @fclose($handle)) {
                $handle = null;
                throw new RuntimeException();
            }
            $handle = null;
            $this->assertOwnedFile($source);
            if (@file_get_contents($path) !== $content) {
                throw new RuntimeException();
            }

            if ($document) {
                // The existing converter retains its own no-network/resource limits.
                $this->ensureDirectory(storage_path('app/private/office-document-converter'), true);
                $converted = @$this->converter->toPdf($path);
                $text = @$this->converter->pdfText($converted['path']);
                $healthy = str_contains($text, $marker);
            } else {
                $healthy = true;
            }
        } catch (Throwable) {
            // Public callers receive only the allowlisted state, never diagnostics.
            $healthy = false;
        } finally {
            if (is_resource($handle) && ! @fclose($handle)) {
                $clean = false;
            }
            if ($converted !== null) {
                try {
                    @$this->converter->release($converted);
                    clearstatcache(true, $converted['directory']);
                    if (@lstat($converted['directory']) !== false) {
                        $clean = false;
                    }
                } catch (Throwable) {
                    $clean = false;
                }
            }
            // No recursive deletion or directory enumeration: only our recorded inode.
            if ($source !== null && ! $this->removeOwned($source, false)) {
                $clean = false;
            }
            if ($workspace !== null && ! $this->removeOwned($workspace, true)) {
                $clean = false;
            }
        }

        return $healthy && $clean ? 'operational' : 'unavailable';
    }

    private function ensureDirectory(string $path, bool $private = false): void
    {
        clearstatcache(true, $path);
        if (@lstat($path) === false && ! @mkdir($path, 0700)) {
            throw new RuntimeException();
        }
        $this->assertDirectoryPath($path);
        if ($private) {
            $this->assertPrivateDirectory($path);
        }
    }

    private function assertDirectoryPath(string $path): void
    {
        // Inspect every ancestor, not just is_dir(), which follows symlinks.
        do {
            clearstatcache(true, $path);
            $stat = @lstat($path);
            if ($stat === false || ($stat['mode'] & 0170000) !== 0040000 || is_link($path)) {
                throw new RuntimeException();
            }
            $parent = dirname($path);
            if ($parent === $path) {
                break;
            }
            $path = $parent;
        } while (true);
    }

    private function assertPrivateDirectory(string $path): void
    {
        $this->assertDirectoryPath($path);
        $stat = @lstat($path);
        if ($stat === false || (PHP_OS_FAMILY !== 'Windows'
            && ($stat['uid'] !== posix_geteuid() || ($stat['mode'] & 0022) !== 0))) {
            throw new RuntimeException();
        }
    }

    /** @return array{path: string, stat: array} */
    private function identity(string $path): array
    {
        clearstatcache(true, $path);
        $stat = @lstat($path);
        if ($stat === false) {
            throw new RuntimeException();
        }

        return ['path' => $path, 'stat' => $stat];
    }

    private function assertOwnedFile(array $file): void
    {
        $this->assertDirectoryPath(dirname($file['path']));
        $current = $this->identity($file['path']);
        if (! $this->sameIdentity($file, $current)
            || ($current['stat']['mode'] & 0170000) !== 0100000
            || (PHP_OS_FAMILY !== 'Windows'
                && ($current['stat']['uid'] !== posix_geteuid() || ($current['stat']['mode'] & 0077) !== 0))) {
            throw new RuntimeException();
        }
    }

    private function sameIdentity(array $expected, array $current): bool
    {
        return $expected['stat']['dev'] === $current['stat']['dev']
            && $expected['stat']['ino'] === $current['stat']['ino']
            && ($expected['stat']['mode'] & 0170000) === ($current['stat']['mode'] & 0170000);
    }

    private function removeOwned(array $artifact, bool $directory): bool
    {
        try {
            $this->assertDirectoryPath(dirname($artifact['path']));
            $current = $this->identity($artifact['path']);
            if (! $this->sameIdentity($artifact, $current)) {
                return false;
            }

            return $directory ? @rmdir($artifact['path']) : @unlink($artifact['path']);
        } catch (Throwable) {
            return false;
        }
    }
}
