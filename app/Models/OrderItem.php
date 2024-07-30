<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function orders()
    {
        return $this->belongsTo(Order::class, 'id_order');
    }
    public function products()
    {
        return $this->belongsTo(Product::class, 'id_product');
    }
}
