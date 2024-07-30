<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class, 'id_category');
    }
    public function ratedByUsers()
    {
        return $this->belongsToMany(User::class, 'feedback', 'id_product', 'id_user')
            ->withPivot('rate', 'comment')
            ->withTimestamps();
    }
}
