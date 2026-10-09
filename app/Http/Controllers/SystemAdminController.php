<?php

namespace App\Http\Controllers;

use App\Models\ExpenseReceiptReview;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\SystemAdminAuditLog;
use App\Models\TosaApplicant;
use App\Services\BesuChainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SystemAdminController extends Controller
{
    public function dashboard(): View
    {
        $officeUsers = OfficeUser::query()
            ->orderBy('name')
            ->get()
            ->sortBy(function (OfficeUser $user): array {
                return [
                    array_search($user->office_role, ['oso', 'sdo', 'ovcaa', 'oc', 'so'], true),
                    $user->name,
                ];
            })
            ->values();

        $pendingStatuses = ['college_review', 'oso_review', 'sdo_review', 'ovcaa_review', 'oc_review'];
        $stats = [
            'active_office_users' => (clone $officeUsers)->where('is_active', true)->count(),
            'total_office_users' => $officeUsers->count(),
            'pending_workflows' => OrgActivity::query()->whereIn('workflow_status', $pendingStatuses)->count(),
            'receipt_records' => ExpenseReceiptReview::query()->count(),
            'tosa_applicants' => TosaApplicant::query()->count(),
        ];

        return view('system-admin.dashboard', [
            'admin' => Auth::guard('system_admin')->user(),
            'stats' => $stats,
            'healthChecks' => $this->healthChecks(),
            'officeUsers' => $officeUsers,
            'auditLogs' => SystemAdminAuditLog::query()
                ->with('admin')
                ->latest()
                ->limit(12)
                ->get(),
        ]);
    }

    public function updateOfficeUserStatus(Request $request, OfficeUser $user): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user = DB::transaction(function () use ($user, $validated): OfficeUser {
            $current = OfficeUser::query()->lockForUpdate()->findOrFail($user->id);
            $current->update(['is_active' => (bool) $validated['is_active']]);
            SystemAdminAuditLog::record(
                'office_account_status_changed',
                'office_user:'.$current->id,
                [
                    'email' => $current->email,
                    'role' => $current->office_role,
                    'is_active' => (bool) $current->is_active,
                ],
            );

            return $current;
        }, 3);

        return back()->with('success', $user->is_active
            ? "{$user->name}'s office account is active."
            : "{$user->name}'s office account has been disabled.");
    }

    public function clearCaches(): RedirectResponse
    {
        try {
            Artisan::call('optimize:clear');
            SystemAdminAuditLog::record('application_cache_cleared');

            return back()->with('success', 'Application caches were cleared successfully.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'The application cache could not be cleared. Check the system log.');
        }
    }

    /**
     * Keep this dashboard read-only except for explicit maintenance actions.
     * No secrets, private keys, or environment values are exposed here.
     *
     * @return list<array{label: string, status: string, detail: string, icon: string}>
     */
    private function healthChecks(): array
    {
        $checks = [];

        try {
            $database = DB::connection();
            $database->getPdo();
            $checks[] = [
                'label' => 'Application database',
                'status' => 'ok',
                'detail' => 'Connected to '.($database->getDatabaseName() ?: 'the configured database').'.',
                'icon' => 'bi-database-check',
            ];
        } catch (\Throwable $exception) {
            report($exception);
            $checks[] = [
                'label' => 'Application database',
                'status' => 'error',
                'detail' => 'The configured database connection failed.',
                'icon' => 'bi-database-x',
            ];
        }

        $storageReady = is_dir(storage_path())
            && is_writable(storage_path())
            && is_dir(storage_path('app'))
            && is_writable(storage_path('app'));
        $checks[] = [
            'label' => 'Storage and logs',
            'status' => $storageReady ? 'ok' : 'error',
            'detail' => $storageReady ? 'Application storage is writable.' : 'Storage is missing or not writable.',
            'icon' => $storageReady ? 'bi-folder-check' : 'bi-folder-x',
        ];

        $besuEnabled = (bool) config('besu.enabled', false);
        if (! $besuEnabled) {
            $checks[] = [
                'label' => 'Besu blockchain',
                'status' => 'warn',
                'detail' => 'The file ledger driver is active; Besu is not enabled in the environment.',
                'icon' => 'bi-diagram-3',
            ];
        } else {
            try {
                $chain = app(BesuChainService::class)->voteChainStatus(1);
                $online = ($chain['status'] ?? '') === 'Operational';
                $peers = (int) data_get($chain, 'nodes_health.peers', 0);
                $checks[] = [
                    'label' => 'Besu blockchain',
                    'status' => $online ? 'ok' : 'error',
                    'detail' => $online
                        ? "RPC online · {$peers} peers · block ".(int) data_get($chain, 'nodes_health.block_number', 0).'.'
                        : 'Besu is enabled but the RPC health check failed.',
                    'icon' => $online ? 'bi-diagram-3-fill' : 'bi-diagram-3',
                ];
            } catch (\Throwable $exception) {
                report($exception);
                $checks[] = [
                    'label' => 'Besu blockchain',
                    'status' => 'error',
                    'detail' => 'Besu is enabled but its health check could not complete.',
                    'icon' => 'bi-diagram-3',
                ];
            }
        }

        $checks[] = [
            'label' => 'Queue configuration',
            'status' => 'info',
            'detail' => 'Driver: '.(string) config('queue.default', 'sync').'.',
            'icon' => 'bi-stack',
        ];

        return $checks;
    }
}
