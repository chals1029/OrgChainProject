<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OfficePortalController extends Controller
{
    public function home(): View
    {
        $office = Auth::guard('office')->user();

        $workflow = [
            'so' => [
                'step' => 1,
                'next' => 'OSO',
                'duty' => 'Submit proposals, activity plans, and supporting documents for review.',
            ],
            'oso' => [
                'step' => 2,
                'next' => 'SDO',
                'duty' => 'Review and endorse organization requests with a clear audit trail.',
            ],
            'sdo' => [
                'step' => 3,
                'next' => 'OVCAA',
                'duty' => 'Align initiatives with campus sustainability goals and acknowledge progress.',
            ],
            'ovcaa' => [
                'step' => 4,
                'next' => 'Final',
                'duty' => 'Confirm academic alignment and close the approval chain.',
            ],
        ];

        return view('office.home', [
            'office' => $office,
            'meta' => $workflow[$office->office_role] ?? $workflow['so'],
        ]);
    }
}
