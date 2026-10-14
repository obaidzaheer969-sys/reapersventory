<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofDownloadController extends Controller
{
    public function __invoke(Request $request, PaymentProof $paymentProof): StreamedResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $proof = PaymentProof::withoutGlobalScopes()->findOrFail($paymentProof->id);

        abort_unless(Storage::disk($proof->disk)->exists($proof->path), 404);

        return Storage::disk($proof->disk)->download($proof->path, $proof->original_name);
    }
}
