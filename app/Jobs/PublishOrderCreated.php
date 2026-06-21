<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PublishOrderCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order->load('items');
    }

    public function handle(): void
    {
        $payload = [
            'event'       => 'order.created',
            'order_id'    => $this->order->id,
            'user_id'     => $this->order->user_id,
            'total_price' => $this->order->total_price,
            'status'      => $this->order->status,
            'items'       => $this->order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'price'      => $item->price,
                ];
            })->toArray(),
            'created_at'  => $this->order->created_at->toIso8601String(),
        ];

        Redis::connection('publisher')->publish('order.created', json_encode($payload));

        Log::info("Event order.created BERHASIL dipublish ke Redis untuk Order ID: {$this->order->id}");
        Log::info("Payload data order: " . json_encode($payload));
    }
}
