<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScanBarcodeController extends Controller
{
    /**
     * Look up a product by barcode/SKU for barcode scanner input.
     * Mirrors the Livewire sale-form scanner but returns JSON so the
     * mobile app (and other clients) can scan a barcode and get product details.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('create', Sale::class);

        $request->validate([
            'barcode' => ['required', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $code = trim($request->input('barcode'));

        if ($code === '') {
            return response()->json(['error' => 'Barcode is required.'], 400);
        }

        $product = Product::query()
            ->where('business_id', $user->business_id)
            ->where('is_active', true)
            ->where(function ($query) use ($code) {
                $query->where('barcode', $code)->orWhere('sku', $code);
            })
            ->first();

        if (! $product) {
            return response()->json([
                'error' => 'No product found for barcode/SKU: '.$code,
            ], 404);
        }

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'selling_price' => (float) $product->selling_price,
            ],
        ]);
    }
}
