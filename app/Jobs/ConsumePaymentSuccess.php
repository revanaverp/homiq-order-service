<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConsumePaymentSuccess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    /**
     * Konstruktor: Menerima data ID Order yang dikirim dari Service Payment (Dappopapo)
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Eksekusi Job: Mengubah status orderan di PostgreSQL menjadi PAID
     */
    public function handle()
    {
        Log::info("Menerima Event payment.success untuk Order ID: {$this->orderId}");

        // Cari data order berdasarkan ID di database
        $order = Order::find($this->orderId);

        if ($order) {
            // Update status menjadi PAID
            $order->update(['status' => 'PAID']);
            Log::info("Sukses memproses event! Status Order ID {$this->orderId} sekarang berubah menjadi PAID.");
        } else {
            Log::warning("Gagal memproses event. Order ID {$this->orderId} tidak ditemukan di database!");
        }
    }
}
