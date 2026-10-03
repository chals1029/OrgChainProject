<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Services\ReceiptDocumentValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptDocumentValidationController extends Controller
{
    public function __invoke(Request $request, ReceiptDocumentValidator $validator)
    {
        $actor = Auth::guard('office')->user();
        abort_unless($actor instanceof OfficeUser && $actor->office_role === 'so', 403);
        $request->validate(['receipt' => ['required', 'file', 'max:10240']]);
        $file = $request->file('receipt');
        abort_unless(strtolower($file->getClientOriginalExtension()) === 'docx', 422, 'Choose a DOCX file for document validation.');
        $result = $validator->validate($file);
        return response()->json($result, $result['valid'] ? 200 : 422)->header('Cache-Control', 'no-store');
    }
}
