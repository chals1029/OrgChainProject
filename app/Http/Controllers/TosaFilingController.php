<?php

namespace App\Http\Controllers;

use App\Models\OfficeAnnouncement;
use App\Models\OfficeUser;
use App\Models\TosaHistoryEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class TosaFilingController extends Controller
{
    public function end(OfficeAnnouncement $announcement): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active && $office->office_role === 'oso', 403);

        DB::connection('mysql')->transaction(function () use ($announcement, $office): void {
            $call = OfficeAnnouncement::query()->whereKey($announcement->id)->lockForUpdate()->firstOrFail();
            $actor = OfficeUser::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->is_active && $actor->office_role === 'oso'
                && (int) $actor->auth_version === (int) $office->auth_version, 403);
            abort_unless(in_array($call->type, ['TOSA', 'Call for TOSA'], true), 404);
            if ($call->ended_at === null) {
                $call->ended_at = now();
                $call->save();
                TosaHistoryEvent::record('call_ended', 'office', (int) $actor->id, $actor->name, null, ['announcement_id' => (int) $call->id]);
            }
        }, 3);

        return redirect()->route('office.tosa', ['tab' => 'documents'])->with('success', 'TOSA announcement ended.');
    }
}
