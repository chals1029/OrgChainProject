<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Services\ReceiptScanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptScanController extends Controller
{
    public function __invoke(Request $request, ReceiptScanner $scanner)
    {
        $actor = Auth::guard('office')->user();
        abort_unless($actor instanceof OfficeUser && $actor->office_role === 'so', 403);
        $request->validate(['receipt' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240']]);
        $scan = $scanner->scan($request->file('receipt'), $actor);
        return response()->json(['scan_id' => $scan->id, 'confidence' => $scan->confidence, 'expires_at' => $scan->expires_at->toIso8601String()] + $scan->extracted)->header('Cache-Control', 'no-store');
    }
}
