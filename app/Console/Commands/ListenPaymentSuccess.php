<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ListenPaymentSuccess extends Command
{
    protected $signature = 'listen:payment';
    protected $description = 'Listen payment.success event from Redis and update order status to PAID';

    public function handle()
    {
        $this->info('Listening to channel [payment.success]...');

        Redis::subscribe(['payment.success'], function ($message) {
            $data = json_decode($message, true);

            if (!$data || !isset($data['order_id'])) {
                $this->error('Invalid payload received');
                return;
            }

            $this->info('Payment success masuk: order_id ' . $data['order_id']);

            try {
                $order = Order::find($data['order_id']);

                if (!$order) {
                    $this->error('Order tidak ditemukan: ID ' . $data['order_id']);
                    return;
                }

                $order->update(['status' => 'PAID']);

                $this->info('Order ID ' . $order->id . ' berhasil diupdate menjadi PAID');

            } catch (\Exception $e) {
                $this->error('Gagal update status order: ' . $e->getMessage());
            }
        });
    }
}
