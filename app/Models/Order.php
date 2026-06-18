<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Mengizinkan kolom-kolom ini untuk diisi data
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
    ];

    /**
     * RELASI: Satu Orderan bisa memiliki banyak Item Barang (One-to-Many)
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
