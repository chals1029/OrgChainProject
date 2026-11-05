<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Services\TosaRequirements;
use App\Services\TosaWorkbookPreview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

final class TosaWorkbookPreviewController extends Controller
{
    public function office(Request $request, TosaWorkbookPreview $preview): JsonResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active && $office->tosaClearanceLevel() >= 1, 403);
        return $this->response($request, $preview);
    }

    public function student(Request $request, TosaWorkbookPreview $preview): JsonResponse
    {
        abort_unless(Auth::guard('student')->check(), 401);
        return $this->response($request, $preview);
    }

    private function response(Request $request, TosaWorkbookPreview $preview): JsonResponse
    {
        $parameters = $request->validate([
            'sheet' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'row' => ['sometimes', 'integer', 'min:0', 'max:10485'],
            'column' => ['sometimes', 'integer', 'min:0', 'max:10000'],
        ]);
        $template = TosaRequirements::resolveTemplate('computation');
        abort_unless($template, 404, 'This official TOSA workbook is unavailable.');
        try {
            $data = $preview->read($template['path'], (int) ($parameters['sheet'] ?? 0),
                (int) ($parameters['row'] ?? 0), (int) ($parameters['column'] ?? 0));
            if ($request->routeIs('office.tosa.workbook.preview')) {
                $id = $template['source'] === 'uploaded' ? 'db-'.$template['id'] : $template['id'];
                $data['download_url'] = route('office.updates.templates.document', ['id' => $id, 'download' => 1]);
            } else {
                $data['download_url'] = route('portal.tosa.templates.show', ['key' => 'computation']);
            }
            return response()->json($data)->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
        } catch (InvalidArgumentException $error) {
            return response()->json(['message' => $error->getMessage()], 422)
                ->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
        }
    }
}
