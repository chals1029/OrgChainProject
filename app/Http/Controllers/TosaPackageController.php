<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Services\TosaRequirements;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

final class TosaPackageController extends Controller
{
    public function __invoke(TosaApplicant $applicant): BinaryFileResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active && $office->office_role === 'oso', 403);
        abort_unless($applicant->status === 'submitted', 404);
        $directory = storage_path('app/private/exports/tosa');
        File::ensureDirectoryExists($directory, 0700, true);
        $path = tempnam($directory, 'tosa-');
        if ($path === false) {
            throw new \RuntimeException('The document package could not be created.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            File::delete($path);
            throw new \RuntimeException('The document package could not be created.');
        }
        $closed = false;
        try {
            DB::connection('mysql')->transaction(function () use ($applicant, $office, $zip, &$closed): void {
                $locked = TosaApplicant::query()->whereKey($applicant->id)->lockForUpdate()->firstOrFail();
                $actor = OfficeUser::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->status === 'submitted', 404);
                abort_unless($actor->is_active && $actor->office_role === 'oso'
                    && (int) $actor->auth_version === (int) $office->auth_version, 403);
                $missing = [];
                $included = 0;
                foreach (TosaRequirements::SUBMISSIONS as $key => $requirement) {
                    $file = $locked->requirementFile($key);
                    if ($file === null) {
                        $missing[] = $requirement['code'].'. '.$requirement['title'];
                        continue;
                    }
                    $name = $requirement['code'].'-'.Str::slug($requirement['title']).'.'.strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (! $zip->addFile($file, $name)) {
                        throw new \RuntimeException('A document could not be added to the package.');
                    }
                    $included++;
                }
                if ($included === 0) {
                    throw ValidationException::withMessages(['documents' => 'No submitted originals are currently available to download.']);
                }
                if ($missing !== [] && ! $zip->addFromString('unavailable-documents.txt', "These submitted originals are unavailable:\n".implode("\n", $missing)."\n")) {
                    throw new \RuntimeException('The document package could not be created.');
                }
                if (! $zip->close()) {
                    throw new \RuntimeException('The document package could not be completed.');
                }
                $closed = true;
            });
        } catch (\Throwable $error) {
            if (! $closed) {
                $zip->close();
            }
            File::delete($path);
            throw $error;
        }

        return response()->download($path, 'TOSA-documents-'.$applicant->id.'.zip', [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }
}
