<?php

namespace App\Services\Clients;

use App\Exceptions\CartServiceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

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
        } catch (Throwable $e) {
            throw new CartServiceUnavailableException(
                'Cart Service tidak dapat dihubungi.',
                0,
                $e
            );
        }

        if (!$response->successful()) {
            throw new CartServiceUnavailableException(
                'Cart Service mengembalikan respons gagal.'
            );
        }

        $body = $response->json();

        if (
            !is_array($body) ||
            !isset($body['data']) ||
            !is_array($body['data']) ||
            !isset($body['data']['items']) ||
            !is_array($body['data']['items'])
        ) {
            throw new CartServiceUnavailableException(
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
        } catch (Throwable $e) {
            // Checkout sudah berhasil, jadi kegagalan
            // mengosongkan keranjang tidak membatalkan order.
            return false;
        }
    }
}