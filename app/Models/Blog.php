<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Blog extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Luu thong tin nguoi dung truoc khi tao moi 1 doi tuong
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($blog) {
            $blog->id_user = Auth::id();
        });
    }
    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'id_cat');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user',);
    }
}
