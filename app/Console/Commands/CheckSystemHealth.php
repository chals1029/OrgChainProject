<?php

namespace App\Console\Commands;

use App\Services\Health\CoreReadinessProbe;
use App\Services\Health\DocumentReadinessProbe;
use App\Services\Health\SystemHealthSnapshot;
use Illuminate\Console\Command;
use Throwable;

class CheckSystemHealth extends Command
{
    protected $signature = 'system:check-health';

    protected $description = 'Privately measure service readiness and publish sanitized observations';

    public function handle(CoreReadinessProbe $core, DocumentReadinessProbe $documents, SystemHealthSnapshot $snapshot): int
    {
        $lock = null;
        try {
            $directory = $snapshot->prepareDirectory();
            $lockPath = $directory.'/health.lock';
            if (is_link($lockPath)) {
                throw new \RuntimeException('Health lock is unavailable.');
            }
            $lock = fopen($lockPath, 'c');
            if ($lock === false) {
                throw new \RuntimeException('Health lock is unavailable.');
            }
            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                $this->info('A health check is already running.');

                return self::SUCCESS;
            }
            if (PHP_OS_FAMILY !== 'Windows') {
                chmod($lockPath, 0600);
            }
            $previous = $snapshot->read();
            $services = [];
            foreach (SystemHealthSnapshot::SERVICES as $id) {
                try {
                    $state = in_array($id, ['private-storage', 'document-processing'], true)
                        ? $documents->check($id)
                        : $core->check($id);
                } catch (Throwable) {
                    $state = 'unavailable';
                }
                if (! in_array($state, SystemHealthSnapshot::STATES, true)) {
                    $state = 'unknown';
                }
                $checked = gmdate('Y-m-d\TH:i:s\Z');
                $services[$id] = [
                    'status' => $state,
                    'checkedAt' => $checked,
                    'startedAt' => $previous[$id]['startedAt'] ?? ($state === 'unknown' ? null : $checked),
                ];
            }
            $snapshot->publish($services);
            $this->info('Private health observations updated for six services.');

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('Health observations could not be updated. Existing observations will expire.');

            return self::FAILURE;
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }
}
