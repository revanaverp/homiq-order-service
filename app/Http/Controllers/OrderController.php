<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Jobs\PublishOrderCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // 1. POST /orders (Membuat Order Baru + Validasi REST Call ke Product Service + Publish Event)
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $itemsToCreate = [];
        $totalPrice = 0;

        // --- REST CALL: Mengarah ke DNS Service Container Docker Milik Cienly ---
        $productServiceUrl = "http://product-service/api/products/";

        foreach ($request->items as $item) {
            // Melakukan pengecekan stok langsung ke Product Service via jaringan internal Docker
            $response = Http::timeout(5)->get($productServiceUrl . $item['product_id']);

            if ($response->failed()) {
                return response()->json([
                    'message' => "Produk ID {$item['product_id']} tidak ditemukan di Product Service!"
                ], 404);
            }

            $productData = $response->json();

            // Validasi kecukupan stok riil dari Product Service
            if ($productData['stock'] < $item['quantity']) {
                return response()->json([
                    'message' => "Stok produk '{$productData['name']}' tidak mencukupi kebutuhan transaksi!"
                ], 400);
            }

            $subTotal = $productData['price'] * $item['quantity'];
            $totalPrice += $subTotal;

            $itemsToCreate[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $productData['price']
            ];
        }

        // --- DATABASE TRANSACTION: Simpan ke PostgreSQL ---
        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => $request->user_id,
                'total_price' => $totalPrice,
                'status' => 'PENDING'
            ]);

            foreach ($itemsToCreate as $item) {
                $order->items()->create($item);
            }

            DB::commit();

            // --- EVENT DRIVEN: Memicu antrean broker via Redis Kelompok ---
            PublishOrderCreated::dispatch($order);

            return response()->json([
                'message' => 'Order berhasil dibuat!',
                'data' => $order->load('items')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memproses pembuatan order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 2. GET /orders/:id (Detail Order)
    public function show($id)
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan!'], 404);
        }

        return response()->json($order);
    }

    // 3. GET /orders/user/:userId (Melihat Riwayat Order per User)
    public function getOrdersByUser($userId)
    {
        $orders = Order::with('items')->where('user_id', $userId)->get();

        return response()->json($orders);
    }

    // 4. PUT /orders/:id/status (Update Status Order - Dipicu Event/Job Payment Success)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:PENDING,PAID,CANCELLED'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan!'], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => "Status order ID {$id} berhasil diupdate menjadi {$request->status}.",
            'data' => $order
        ]);
    }
}
