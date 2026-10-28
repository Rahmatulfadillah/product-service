<?php

namespace App\Services;

use App\Exceptions\OrderBusinessException;
use App\Models\Order;
use App\Services\Clients\CartServiceClient;
use App\Services\Clients\ProductServiceClient;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private ProductServiceClient $productClient,
        private CartServiceClient $cartClient,
    ) {
    }

    public function checkout(int $userId): Order
    {
        $cart = $this->cartClient->getCart($userId);
        $items = $cart['items'];

        if (count($items) === 0) {
            throw new OrderBusinessException(
                'Keranjang belanja kosong.'
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
                        'Data item keranjang tidak valid.'
                    );
                }

                $product = $this->productClient->getProduct($productId);

                if ($product === null) {
                    throw new OrderBusinessException(
                        "Produk dengan ID {$productId} tidak ditemukan."
                    );
                }

                if ($quantity > (int) $product['stock']) {
                    throw new OrderBusinessException(
                        "Stok produk {$product['name']} tidak mencukupi."
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

        $this->cartClient->clearCart($userId);

        return $order->load('items');
    }

    public function getById(int $orderId): ?Order
    {
        return Order::with('items')->find($orderId);
    }

    public function getByUser(int $userId)
    {
        return Order::with('items')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }
}
