<?php

namespace App\Services;

use App\Exceptions\OrderBusinessException;
use App\Models\Order;
use App\Services\Clients\CartServiceClient;
use App\Services\Clients\ProductServiceClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private ProductServiceClient $productClient,
        private CartServiceClient $cartClient,
    ) {
    }

    /**
     * Mengubah keranjang belanja menjadi pesanan.
     */
    public function checkout(int $userId): Order
    {
        $cart = $this->cartClient->getCart($userId);
        $items = $cart['items'];

        if (count($items) === 0) {
            throw new OrderBusinessException(
                'Keranjang belanja kosong.',
                409
            );
        }

        $order = DB::transaction(function () use ($userId, $items) {
            $order = Order::create([
                'user_id' => $userId,
                'status' => 'PENDING',
                'total_amount' => 0,
            ]);

            $total = 0;

            foreach ($items as $item) {
                $productId = (int) (
                    is_array($item)
                        ? $item['product_id']
                        : $item->product_id
                );

                $quantity = (int) (
                    is_array($item)
                        ? $item['quantity']
                        : $item->quantity
                );

                if ($productId <= 0 || $quantity <= 0) {
                    throw new OrderBusinessException(
                        'Data item keranjang tidak valid.',
                        422
                    );
                }

                $product = $this->productClient->getProduct($productId);

                if ($product === null) {
                    throw new OrderBusinessException(
                        "Produk dengan ID {$productId} tidak ditemukan.",
                        409
                    );
                }

                if ($quantity > (int) $product['stock']) {
                    throw new OrderBusinessException(
                        "Stok produk {$product['name']} tidak mencukupi.",
                        409
                    );
                }

                $price = (float) $product['price'];
                $subtotal = $price * $quantity;
                $total += $subtotal;

                $order->items()->create([
                    'product_id' => $productId,
                    'product_name' => $product['name'],
                    'unit_price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update([
                'total_amount' => $total,
            ]);

            return $order;
        });

        // Pesanan sudah tersimpan.
        // Kegagalan mengosongkan cart tidak membatalkan pesanan.
        $cartCleared = $this->cartClient->clearCart($userId);

        if (!$cartCleared) {
            Log::warning('Checkout berhasil, tetapi cart gagal dikosongkan.', [
                'order_id' => $order->id,
                'user_id' => $userId,
            ]);
        }

        return $order->load('items');
    }

    /**
     * Mengambil detail pesanan.
     */
    public function getById(int $orderId): ?Order
    {
        return Order::with('items')->find($orderId);
    }

    /**
     * Mengambil daftar pesanan berdasarkan pengguna dan status.
     */
    public function getByUser(int $userId, ?string $status = null)
    {
        $query = Order::with('items')
            ->where('user_id', $userId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->latest()->get();
    }

    /**
     * Mengubah status pesanan PENDING menjadi PAID.
     */
    public function payOrder(int $orderId): ?Order
    {
        return DB::transaction(function () use ($orderId) {
            $order = Order::where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                return null;
            }

            if ($order->status !== 'PENDING') {
                throw new OrderBusinessException(
                    'Pesanan tidak dapat dibayar karena statusnya bukan PENDING.',
                    409
                );
            }

            $order->update([
                'status' => 'PAID',
            ]);

            return $order->load('items');
        });
    }
}