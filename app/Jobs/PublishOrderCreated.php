<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishOrderCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Properti untuk menyimpan data order
    public $order;

    /**
     * Create a new job instance.
     * * @param Order $order
     */
    public function __construct(Order $order)
    {
        // Membawa data order beserta item rinciannya (eager loading 'items')
        $this->order = $order->load('items');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Mencatat log di laravel.log sebagai bukti event berhasil dipicu
        Log::info("Event order.created BERHASIL dipublish ke Redis untuk Order ID: {$this->order->id}");

        // 2. Format data yang akan dilempar ke Redis (dalam bentuk Array/JSON)
        $payload = [
            'event' => 'order.created',
            'order_id' => $this->order->id,
            'user_id' => $this->order->user_id,
            'total_price' => $this->order->total_price,
            'status' => $this->order->status,
            'items' => $this->order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            })->toArray(),
            'created_at' => $this->order->created_at->toIso8601String(),
        ];

        // 3. Menampilkan payload di log untuk mempermudah debugging kelompok
        Log::info("Payload data order: " . json_encode($payload));

        // Catatan: Karena kamu menggunakan `QUEUE_CONNECTION=redis`, saat job ini di-dispatch,
        // Laravel secara otomatis akan menyimpannya ke dalam antrean Redis local kamu.
    }
}
