<?php

namespace App\Http\Controllers;

use App\Services\Health\SystemHealthSnapshot;
use Illuminate\Http\JsonResponse;

class SystemHealthController extends Controller
{
    public function index(SystemHealthSnapshot $snapshot): JsonResponse
    {
        return response()->json(['services' => $snapshot->read()])->withHeaders($this->headers());
    }

    public function show(string $service, SystemHealthSnapshot $snapshot): JsonResponse
    {
        abort_unless(in_array($service, SystemHealthSnapshot::SERVICES, true), 404);
        $result = $snapshot->read()[$service];
        $ready = in_array($result['status'], ['operational', 'degraded'], true);

        return response()->json($result, $ready ? 200 : 503)->withHeaders($this->headers());
    }

    private function headers(): array
    {
        return ['Cache-Control' => 'no-store, max-age=0', 'X-Robots-Tag' => 'noindex', 'X-Content-Type-Options' => 'nosniff'];
    }
}
