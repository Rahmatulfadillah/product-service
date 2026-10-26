<?php

namespace App\Services\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ProductServiceClient
{
    public function getProduct(int $productId): ?array
    {
        $baseUrl = rtrim(
            config('services.product_service.url'),
            '/'
        );

        try {
            $response = Http::timeout(5)
                ->get($baseUrl . '/products/' . $productId);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Product Service tidak dapat dihubungi.',
                0,
                $e
            );
        }

        if ($response->status() === 404) {
            return null;
        }

        if (!$response->successful()) {
            throw new RuntimeException(
                'Product Service mengembalikan respons yang tidak berhasil.'
            );
        }

        $product = $response->json();

        if (!is_array($product)) {
            throw new RuntimeException(
                'Format respons Product Service tidak valid.'
            );
        }

        return $product;
    }
}