<?php

namespace App\Services\Clients;

use App\Exceptions\ProductServiceUnavailableException;
use Illuminate\Support\Facades\Http;
use Throwable;

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
        } catch (Throwable $e) {
            throw new ProductServiceUnavailableException(
                'Product Service tidak dapat dihubungi.',
                0,
                $e
            );
        }

        if ($response->status() === 404) {
            return null;
        }

        if (!$response->successful()) {
            throw new ProductServiceUnavailableException(
                'Product Service mengembalikan respons gagal.'
            );
        }

        $product = $response->json('data');

        if (
            !is_array($product) ||
            !isset(
                $product['id'],
                $product['name'],
                $product['price'],
                $product['stock']
            )
        ) {
            throw new ProductServiceUnavailableException(
                'Format data produk dari Product Service tidak valid.'
            );
        }

        return $product;
    }
}