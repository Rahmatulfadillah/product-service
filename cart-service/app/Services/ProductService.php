<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductService
{
    public function getProduct(int $productId): ?array
    {
        try {
            $response = Http::timeout(5)
                ->get(
                    rtrim(config('services.product.url'), '/')
                    . "/products/{$productId}"
                );

            if (
                $response->successful() &&
                is_array($response->json('data'))
            ) {
                return $response->json('data');
            }

            return null;
        } catch (Throwable $e) {
            Log::error('Gagal mengambil data produk.', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}