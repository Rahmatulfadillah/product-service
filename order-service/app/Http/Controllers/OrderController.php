<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderBusinessException;
use App\Exceptions\CartServiceUnavailableException;
use App\Exceptions\ProductServiceUnavailableException;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class OrderController extends Controller
{
    /**
     * Checkout keranjang menjadi pesanan.
     */
    public function checkout(
        StoreOrderRequest $request,
        OrderService $orderService
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $order = $orderService->checkout(
                (int) $validated['user_id']
            );

            return response()->json([
                'message' => 'Checkout berhasil.',
                'data' => $order,
            ], 201);

        } catch (OrderBusinessException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (
            CartServiceUnavailableException |
            ProductServiceUnavailableException $e
        ) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 503);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan pada Order Service.',
            ], 500);
        }
    }

    /**
     * Menampilkan daftar pesanan milik pengguna.
     */
    public function index(
        Request $request,
        OrderService $orderService
    ): JsonResponse {
        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
        ]);

        try {
            $orders = $orderService->getByUser(
                (int) $validated['user_id']
            );

            return response()->json([
                'message' => 'Daftar pesanan berhasil diambil.',
                'data' => $orders,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan pada Order Service.',
            ], 500);
        }
    }

    /**
     * Menampilkan detail pesanan berdasarkan ID.
     */
    public function show(
        int $id,
        OrderService $orderService
    ): JsonResponse {
        try {
            $order = $orderService->getById($id);

            if ($order === null) {
                return response()->json([
                    'message' => 'Pesanan tidak ditemukan.',
                ], 404);
            }

            return response()->json([
                'message' => 'Detail pesanan berhasil diambil.',
                'data' => $order,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan pada Order Service.',
            ], 500);
        }
    }
}