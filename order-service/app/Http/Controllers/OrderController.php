<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderBusinessException;
use App\Exceptions\CartServiceUnavailableException;
use App\Exceptions\ProductServiceUnavailableException;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
            ], $e->httpStatus);

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
     * Daftar pesanan berdasarkan user ID dan status.
     */
    public function getByUser(
        int $userId,
        Request $request,
        OrderService $orderService
    ): JsonResponse {
        if ($userId < 1) {
            return response()->json([
                'message' => 'userId harus lebih besar dari 0.',
            ], 422);
        }

        $status = $request->query('status');

        if (
            $status !== null &&
            !in_array($status, ['PENDING', 'PAID'], true)
        ) {
            return response()->json([
                'message' => 'Status harus PENDING atau PAID.',
                'errors' => [
                    'status' => [
                        'Status tidak valid.',
                    ],
                ],
            ], 422);
        }

        try {
            $orders = $orderService->getByUser(
                $userId,
                $status
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
     * Detail pesanan berdasarkan ID.
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

    /**
     * Simulasi pembayaran pesanan.
     */
    public function pay(
        int $id,
        OrderService $orderService
    ): JsonResponse {
        try {
            $order = $orderService->payOrder($id);

            if ($order === null) {
                return response()->json([
                    'message' => 'Pesanan tidak ditemukan.',
                ], 404);
            }

            return response()->json([
                'message' => 'Pembayaran berhasil. Status pesanan menjadi PAID.',
                'data' => $order,
            ], 200);

        } catch (OrderBusinessException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->httpStatus);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan pada Order Service.',
            ], 500);
        }
    }

    /**
     * Endpoint lama untuk daftar pesanan melalui /api/orders.
     */
    public function index(
        Request $request,
        OrderService $orderService
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|min:1',
            ]);

            $orders = $orderService->getByUser(
                (int) $validated['user_id']
            );

            return response()->json([
                'message' => 'Daftar pesanan berhasil diambil.',
                'data' => $orders,
            ], 200);

        } catch (ValidationException $e) {
            throw $e;

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan pada Order Service.',
            ], 500);
        }
    }
}