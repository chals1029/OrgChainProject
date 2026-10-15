<?php

namespace App\Console\Commands;

use App\Models\ArchiveDocument;
use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OrgActivityAccomplishment;
use App\Models\OrgRenewalDocument;
use App\Models\OrgReportDocument;
use App\Models\TosaApplicant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PrivatizeDocuments extends Command
{
    protected $signature = 'documents:privatize
        {--dry-run : Inventory and verify without changing uploads or records}
        {--allow-missing : Migrate available verified files while retaining missing-file metadata and an incomplete exit status}
        {--diagnostics : Write failure paths and categories to a fixed manifest on the private local disk}';

    protected $description = 'Copy and verify confidential public uploads into local storage before retiring public copies';

    private const PREFIXES = ['in-campus-activities', 'off-campus-activities', 'renewal-documents', 'semester-reports', 'financial-smoke', 'tosa', 'receipts', 'expense-receipts', 'archive', 'archives', 'accomplishment-evidence'];

    private const MISSING = 1001;
    private const COLLISION = 1002;
    private const UNSAFE = 1003;
    private const DIAGNOSTICS_PATH = 'migration-diagnostics/confidential-documents.json';

    public function handle(): int
    {
        $paths = [];
        $records = [];
        $failures = 0;
        $counts = ['missing' => 0, 'collision' => 0, 'unsafe' => 0, 'io' => 0];
        $diagnostics = [];
        try {
            $publicRoot = realpath(Storage::disk('public')->path(''));
            $privateRoot = realpath(Storage::disk('local')->path(''));
            if (! $publicRoot || ! $privateRoot) {
                throw new RuntimeException('Storage roots unavailable.');
            }
            $publicRoot = str_replace('\\', '/', $publicRoot);
            $privateRoot = str_replace('\\', '/', $privateRoot);
            if (PHP_OS_FAMILY === 'Windows') {
                $publicRoot = strtolower($publicRoot);
                $privateRoot = strtolower($privateRoot);
            }
            if ($publicRoot === $privateRoot || str_starts_with($privateRoot, $publicRoot.'/')
                || str_starts_with($publicRoot, $privateRoot.'/')) {
                throw new RuntimeException('Confidential storage roots overlap.', self::UNSAFE);
            }
            foreach ([InCampusActivitySubmission::class => ['attachments'], OrgRenewalDocument::class => ['file_path'],
                OrgReportDocument::class => ['file_path', 'accomplishment_summary'], TosaApplicant::class => ['requirements'],
                OrgActivityAccomplishment::class => ['evidence', 'financial_snapshot'],
                ArchiveDocument::class => ['file_path'], ExpenseReceiptReview::class => ['receipt_path', 'receipt_attachments']] as $model => $fields) {
                foreach ($model::query()->cursor() as $record) {
                    foreach ($fields as $field) {
                        $this->collectPaths($record->getAttribute($field), $paths);
                    }
                    if ($record instanceof ArchiveDocument) {
                        $this->assertDisk($record->file_disk);
                        $records[] = $record;
                    }
                    if ($record instanceof ExpenseReceiptReview) {
                        $this->assertDisk($record->receipt_disk);
                        foreach ($record->receipt_attachments ?? [] as $attachment) {
                            if (is_array($attachment)) {
                                $this->assertDisk($attachment['disk'] ?? null);
                            }
                        }
                        $records[] = $record;
                    }
                }
            }
            foreach (self::PREFIXES as $prefix) {
                $this->collectPublicPrefix($prefix, $paths);
            }
        } catch (Throwable $exception) {
            ++$counts[$this->failureCategory($exception)];
            $this->reportFailures($counts);
            $this->writeDiagnostics($counts, [['category' => $this->failureCategory($exception), 'path' => null]]);
            $this->error('Inventory failed: unsafe metadata or unavailable database/storage. No public files were removed.');
            return self::FAILURE;
        }

        $verified = [];
        foreach (array_keys($paths) as $path) {
            try {
                $verified[$path] = $this->verifyCopy($path, (bool) $this->option('dry-run'));
            } catch (Throwable $exception) {
                ++$failures;
                $category = $this->failureCategory($exception);
                ++$counts[$category];
                $diagnostics[] = ['category' => $category, 'path' => $path];
            }
        }
        $this->info('Confidential inventory: '.count($paths).' files; verified: '.count($verified).'; unsafe/missing/colliding: '.$failures.'.');
        $this->reportFailures($counts);
        if (! $this->writeDiagnostics($counts, $diagnostics)) {
            return self::FAILURE;
        }
        if ($failures > 0 && (! $this->option('allow-missing') || $failures !== $counts['missing'])) {
            $this->error('Migration incomplete. No metadata was changed and no public sources were removed. Resolve unsafe files privately and rerun.');
            return self::FAILURE;
        }
        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No uploads or records changed; private diagnostics are written only when requested.');
            if ($counts['missing'] > 0) {
                $this->error('Migration remains incomplete: missing files and their metadata must be resolved privately.');
                return self::FAILURE;
            }
            return self::SUCCESS;
        }

        // Metadata is switched only for files with verified private copies.
        // A failed save leaves all public sources in place for a safe retry.
        try {
            foreach ($records as $record) {
                if ($record instanceof ArchiveDocument) {
                    if (isset($verified[$record->file_path])) {
                        $record->file_disk = 'local';
                    }
                } else {
                    if (isset($verified[$record->receipt_path])) {
                        $record->receipt_disk = 'local';
                    }
                    $attachments = $record->receipt_attachments;
                    if (is_array($attachments)) {
                        foreach ($attachments as &$attachment) {
                            if (is_array($attachment) && isset($verified[$attachment['path'] ?? ''])) {
                                $attachment['disk'] = 'local';
                            }
                        }
                        unset($attachment);
                        $record->receipt_attachments = $attachments;
                    }
                }
                if ($record->isDirty()) {
                    $record->save();
                }
            }
        } catch (Throwable) {
            ++$counts['io'];
            $diagnostics[] = ['category' => 'io', 'path' => null, 'phase' => 'metadata'];
            $this->reportFailures($counts);
            $this->writeDiagnostics($counts, $diagnostics);
            $this->error('Metadata update failed. Verified private copies and public sources are retained; rerun after resolving the database failure.');
            return self::FAILURE;
        }

        $removed = 0;
        foreach ($verified as $path => $hash) {
            try {
                $source = $this->safeAbsolute('public', $path);
                if (! is_file($source)) {
                    continue;
                }
                $target = $this->safeAbsolute('local', $path);
                if (! hash_equals($hash, $this->fingerprint($source)) || ! hash_equals($hash, $this->fingerprint($target))
                    || ! Storage::disk('public')->delete($path)) {
                    throw new RuntimeException('Source changed or deletion failed.');
                }
                ++$removed;
            } catch (Throwable $exception) {
                ++$failures;
                $category = $this->failureCategory($exception);
                ++$counts[$category];
                $diagnostics[] = ['category' => $category, 'path' => $path, 'phase' => 'retire-public'];
            }
        }
        $this->info('Public sources retired: '.$removed.'; unresolved: '.$failures.'.');
        $this->reportFailures($counts);
        if (! $this->writeDiagnostics($counts, $diagnostics)) {
            return self::FAILURE;
        }
        if ($failures > 0) {
            $this->error('Migration incomplete. Missing-file metadata and any unverified public sources were retained; resolve privately and rerun.');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }

    private function failureCategory(Throwable $exception): string
    {
        if ($exception::class !== RuntimeException::class) {
            return 'io';
        }
        return match ($exception->getCode()) {
            self::MISSING => 'missing',
            self::COLLISION => 'collision',
            self::UNSAFE => 'unsafe',
            default => 'io',
        };
    }

    private function reportFailures(array $counts): void
    {
        $this->info('Failures: missing='.$counts['missing'].'; collision='.$counts['collision'].'; unsafe='.$counts['unsafe'].'; io='.$counts['io'].'.');
    }

    private function writeDiagnostics(array $counts, array $failures): bool
    {
        if (! $this->option('diagnostics')) {
            return true;
        }
        try {
            $json = json_encode(['recorded_at' => now()->toIso8601String(), 'counts' => $counts, 'failures' => $failures], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            if (! Storage::disk('local')->put(self::DIAGNOSTICS_PATH, $json, ['visibility' => 'private'])) {
                throw new RuntimeException('Private diagnostics could not be stored.');
            }
        } catch (Throwable) {
            $this->error('Private diagnostic manifest could not be written. Migration remains incomplete.');
            return false;
        }
        $this->info('Private diagnostics: local disk, '.self::DIAGNOSTICS_PATH.'.');
        return true;
    }

    private function collectPublicPrefix(string $prefix, array &$paths): void
    {
        $root = rtrim(Storage::disk('public')->path(''), '/\\').DIRECTORY_SEPARATOR;
        $directory = $root.$prefix;
        if (is_link($directory) || is_file($directory)) {
            $paths[$prefix] = true;
            return;
        }
        if (! is_dir($directory)) {
            return;
        }
        // Unlike the disk's allFiles(), include links in the inventory so unsafe
        // orphaned links fail visibly rather than silently disappearing from it.
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $entry) {
            if ($entry->isLink() || ! $entry->isDir()) {
                $path = str_replace('\\', '/', substr($entry->getPathname(), strlen($root)));
                $paths[$path] = true;
            }
        }
    }

    private function collectPaths(mixed $value, array &$paths): void
    {
        if (is_string($value)) {
            if ($value !== '') {
                $paths[$value] = true;
            }
            return;
        }
        if (! is_array($value)) {
            return;
        }
        foreach ($value as $key => $child) {
            if ($key === 'path' && is_string($child) && $child !== '') {
                $paths[$child] = true;
            } elseif (is_array($child)) {
                $this->collectPaths($child, $paths);
            }
        }
    }

    private function assertDisk(?string $disk): void
    {
        if (! in_array($disk, [null, '', 'public', 'local'], true)) {
            throw new RuntimeException('Unsupported confidential disk.', self::UNSAFE);
        }
    }

    private function safeAbsolute(string $disk, string $path): string
    {
        $segments = explode('/', $path);
        if ($path === '' || str_contains($path, '\\') || str_contains($path, ':') || str_contains($path, "\0")
            || in_array('..', $segments, true) || in_array('.', $segments, true) || in_array('', $segments, true)
            || in_array($segments[0], ['templates', 'renewal-templates', 'tosa-templates', 'office-settings', 'community', 'avatars', 'announcements'], true)) {
            throw new RuntimeException('Unsafe confidential path.', self::UNSAFE);
        }
        $root = Storage::disk($disk)->path('');
        $current = rtrim($root, '/\\');
        if (is_link($current)) {
            throw new RuntimeException('Linked storage root.', self::UNSAFE);
        }
        foreach ($segments as $segment) {
            $current .= DIRECTORY_SEPARATOR.$segment;
            if (is_link($current)) {
                throw new RuntimeException('Linked confidential path.', self::UNSAFE);
            }
        }
        if (file_exists($current) && ! is_file($current)) {
            throw new RuntimeException('Not a regular file.', self::UNSAFE);
        }
        return $current;
    }

    private function fingerprint(string $path): string
    {
        $hash = @hash_file('sha256', $path);
        if ($hash === false) {
            throw new RuntimeException('Unreadable confidential file.');
        }
        return $hash;
    }

    private function verifyCopy(string $path, bool $dryRun): string
    {
        $source = $this->safeAbsolute('public', $path);
        $target = $this->safeAbsolute('local', $path);
        if (! is_file($source)) {
            if (! is_file($target)) {
                throw new RuntimeException('Missing confidential file.', self::MISSING);
            }
            return $this->fingerprint($target);
        }
        $hash = $this->fingerprint($source);
        if (is_file($target)) {
            if (! hash_equals($hash, $this->fingerprint($target))) {
                throw new RuntimeException('Conflicting private bytes.', self::COLLISION);
            }
            return $hash;
        }
        if ($dryRun) {
            return $hash;
        }
        if (! Storage::disk('local')->makeDirectory(dirname($path))) {
            throw new RuntimeException('Cannot create private directory.');
        }
        // Exclusive creation never replaces an existing private file, including a racing upload.
        $input = @fopen($source, 'rb');
        $output = @fopen($target, 'xb');
        try {
            if ($input === false || $output === false || @stream_copy_to_stream($input, $output) === false || ! @fflush($output)) {
                throw new RuntimeException('Cannot copy confidential file.');
            }
        } finally {
            if (is_resource($input)) fclose($input);
            if (is_resource($output)) fclose($output);
        }
        if (! hash_equals($hash, $this->fingerprint($target)) || ! hash_equals($hash, $this->fingerprint($source))) {
            throw new RuntimeException('Copy verification failed.', self::COLLISION);
        }
        return $hash;
    }
}
