<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Services\TosaRequirements;
use App\Services\TosaScoringService;
use App\Services\TosaWorkbookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TosaWorkbookController extends Controller
{
    public function __construct(private readonly TosaWorkbookService $workbooks) {}

    public function reference(Request $request): Response
    {
        abort_unless(Auth::guard('student')->check(), 401);
        return $this->referenceResponse(false);
    }

    public function officeReference(Request $request): Response
    {
        $this->authorizeOffice();

        return $this->referenceResponse(true);
    }

    private function referenceResponse(bool $office): Response
    {
        $template = TosaRequirements::resolveTemplate('computation');
        abort_unless($template, 404, 'The original TOSA computation reference is unavailable.');
        try {
            $preview = $this->workbooks->referencePreview($template['path']);
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }
        $id = $template['source'] === 'uploaded' ? 'db-'.$template['id'] : $template['id'];
        $downloadUrl = $office
            ? route('office.updates.templates.document', ['id' => $id])
            : route('portal.tosa.templates.show', ['key' => 'computation']);

        return $this->view($preview, $downloadUrl, $office ? route('office.tosa') : route('portal.home').'#tosa');
    }

    public function student(Request $request): Response
    {
        return $this->view($this->workbooks->personalPreview($this->studentApplicant()),
            route('portal.tosa.workbook.download'), route('portal.home').'#tosa');
    }

    public function studentDownload(Request $request): StreamedResponse
    {
        return $this->download($this->studentApplicant());
    }

    public function office(Request $request, TosaApplicant $applicant): Response
    {
        $this->authorizeOffice();
        $this->requireEvaluation($applicant);

        return $this->view($this->workbooks->personalPreview($applicant),
            route('office.tosa.workbook.download', $applicant), route('office.tosa'));
    }

    public function officeDownload(Request $request, TosaApplicant $applicant): StreamedResponse
    {
        $this->authorizeOffice();
        $this->requireEvaluation($applicant);

        return $this->download($applicant);
    }

    private function studentApplicant(): TosaApplicant
    {
        $student = Auth::guard('student')->user();
        abort_unless($student, 401);
        $srCode = trim((string) ($student->sr_code ?? ''));
        // Match StudentPortalController::myTosaApplication(): first own SR-code row.
        // Request applicant IDs and query parameters are deliberately never consulted.
        $applicant = $srCode !== '' ? TosaApplicant::query()->where('sr_code', $srCode)->first() : null;
        abort_unless($applicant, 404);
        $this->requireEvaluation($applicant);

        return $applicant;
    }

    private function authorizeOffice(): void
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active
            && in_array($office->office_role, ['oso', 'ovcaa'], true)
            && $office->tosaClearanceLevel() >= 1, 403);
    }

    private function requireEvaluation(TosaApplicant $applicant): void
    {
        abort_unless(app(TosaScoringService::class)->evaluation($applicant) !== null, 404,
            'No saved evaluation workbook is available yet.');
    }

    private function view(array $preview, string $downloadUrl, string $backUrl): Response
    {
        $nonce = bin2hex(random_bytes(16));
        $policy = "default-src 'none'; script-src 'nonce-{$nonce}'; style-src 'nonce-{$nonce}'; base-uri 'none'; object-src 'none'; form-action 'none'";

        return response()->view('tosa.workbook', compact('preview', 'downloadUrl', 'backUrl', 'nonce', 'policy'))
            ->withHeaders(['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => $policy."; frame-ancestors 'self'"]);
    }

    private function download(TosaApplicant $applicant): StreamedResponse
    {
        return response()->streamDownload(function () use ($applicant): void {
            $path = tempnam(sys_get_temp_dir(), 'tosa-score-');
            if ($path === false) {
                throw new RuntimeException('Unable to create a private workbook export.');
            }
            try {
                $this->workbooks->writePersonal($applicant, $path);
                if (readfile($path) === false) {
                    throw new RuntimeException('Unable to stream the workbook export.');
                }
            } finally {
                unlink($path);
            }
        }, 'TOSA-system-computed.xlsx', ['Content-Type' => TosaWorkbookService::MIME_TYPE,
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
