<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'total_price',
        'status'
    ];

    // Relasi One-to-Many ke OrderItem (Satu order punya banyak item belanjaan)
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
