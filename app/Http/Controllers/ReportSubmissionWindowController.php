<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Services\ReportSubmissionWindowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReportSubmissionWindowController extends Controller
{
    public function __construct(private readonly ReportSubmissionWindowService $windows) {}

    public function update(Request $request, string $reportType): RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'oso', 403);
        abort_unless(in_array($reportType, ['ar', 'fr'], true), 404);

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', Rule::in(['1st Semester', '2nd Semester', 'Midyear'])],
            'is_locked' => ['required', 'boolean'],
            'organization' => ['nullable', 'string', 'max:255'],
            'tab' => ['nullable', Rule::in(['ar', 'fr'])],
        ]);
        $locked = $request->boolean('is_locked');
        $this->windows->setLocked($reportType, $validated['semester'], $validated['academic_year'], $locked, $office);

        return redirect()->route('office.reports.index', [
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
            'organization' => trim((string) ($validated['organization'] ?? '')),
            'tab' => $validated['tab'] ?? $reportType,
        ])->with('success', strtoupper($reportType).' submissions '.($locked ? 'locked' : 'reopened').' for AY '.$validated['academic_year'].' · '.$validated['semester'].' for all organizations.');
    }
}
