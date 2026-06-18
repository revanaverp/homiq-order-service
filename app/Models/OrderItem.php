<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
    ];

    /**
     * RELASI BALIKAN: Setiap rincian barang (item) pasti dimiliki oleh satu Orderan Utama
     * * Hubungan ini disebut BelongsTo (Bentuk kebalikan dari HasMany di file Order.php)
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
