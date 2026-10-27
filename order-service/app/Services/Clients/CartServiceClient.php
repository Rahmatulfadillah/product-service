<?php

namespace App\Services\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CartServiceClient
{
    public function getCart(int $userId): array
    {
        $baseUrl = rtrim(
            config('services.cart_service.url'),
            '/'
        );

        try {
            $response = Http::timeout(5)
                ->get($baseUrl . '/carts/' . $userId);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Cart Service tidak dapat dihubungi.',
                0,
                $e
            );
        }

        if (!$response->successful()) {
            throw new RuntimeException(
                'Cart Service mengembalikan respons gagal.'
            );
        }

        $body = $response->json();

        if (
            !is_array($body) ||
            !array_key_exists('data', $body) ||
            !is_array($body['data'])
        ) {
            throw new RuntimeException(
                'Format respons Cart Service tidak valid.'
            );
        }

        return $body['data'];
    }

    public function clearCart(int $userId): bool
    {
        $baseUrl = rtrim(
            config('services.cart_service.url'),
            '/'
        );

        try {
            $response = Http::timeout(5)
                ->delete($baseUrl . '/carts/' . $userId);

            return $response->successful();
        } catch (ConnectionException $e) {
            return false;
        }
    }
}