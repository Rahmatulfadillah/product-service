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

        // Produk tidak ditemukan.
        if ($response->status() === 404) {
            return null;
        }

        // Respons gagal selain 404.
        if (!$response->successful()) {
            throw new RuntimeException(
                'Product Service mengembalikan respons gagal.'
            );
        }

        // Produk berada di dalam properti "data".
        $product = $response->json('data');

        if (
            !is_array($product) ||
            !isset($product['id'], $product['name'], $product['price'], $product['stock'])
        ) {
            throw new RuntimeException(
                'Format data produk dari Product Service tidak valid.'
            );
        }

        return $product;
    }
}