<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryBlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// dang nhap va quan ly nguoi dung
Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::get('profile', 'userProfile');
    Route::post('change/password', 'changePassWord');
});

// nhom va phan quyen truy cap theo role tren route
Route::middleware(['auth:api', 'role:admin,manager,staff'])->group(function () {
    // User routes
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::patch('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Category routes
    Route::prefix('category')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::patch('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    // Product routes
    Route::prefix('product')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::patch('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    // Blog category routes
    Route::prefix('blog/category')->group(function () {
        Route::get('/', [CategoryBlogController::class, 'index']);
        Route::get('/{id}', [CategoryBlogController::class, 'show']);
        Route::post('/', [CategoryBlogController::class, 'store']);
        Route::put('/{id}', [CategoryBlogController::class, 'update']);
        Route::patch('/{id}', [CategoryBlogController::class, 'update']);
        Route::delete('/{id}', [CategoryBlogController::class, 'destroy']);
    });

    // Blog routes
    Route::prefix('blog')->group(function () {
        Route::get('/', [BlogController::class, 'index']);
        Route::get('/{id}', [BlogController::class, 'show']);
        Route::post('/', [BlogController::class, 'store']);
        Route::put('/{id}', [BlogController::class, 'update']);
        Route::patch('/{id}', [BlogController::class, 'update']);
        Route::delete('/{id}', [BlogController::class, 'destroy']);
    });

    // Order routes
    Route::prefix('order')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::put('/{id}', [OrderController::class, 'update']);
    });
    // Coupon routes
    Route::prefix('coupon')->group(function () {
        Route::get('/', [CouponController::class, 'index']);
        Route::get('/{id}', [CouponController::class, 'show']);
        Route::post('/', [CouponController::class, 'store']);
        Route::put('/{id}', [CouponController::class, 'update']);
        Route::patch('/{id}', [CouponController::class, 'update']);
        Route::delete('/{id}', [CouponController::class, 'destroy']);
    });
    // Banner Routes
    Route::prefix('banner')->group(function () {
        Route::get('/', [BannerController::class, 'index']);
        Route::get('/{id}', [BannerController::class, 'show']);
        Route::post('/', [BannerController::class, 'store']);
        Route::put('/{id}', [BannerController::class, 'update']);
        Route::patch('/{id}', [BannerController::class, 'update']);
        Route::delete('/{id}', [BannerController::class, 'destroy']);
    });

});

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('giohang')->group(function () {
        Route::get('/', [GuestController::class, 'giohang']);
        Route::post('themgiohang', [GuestController::class, 'themgiohang']);
        Route::put('/capnhatgiohang', [GuestController::class, 'capnhatgiohang']);
        Route::delete('/xoagiohang/{id_product}', [GuestController::class, 'xoagiohang']);
    });
    Route::prefix('taikhoan')->group(function () {
        Route::get('thongtin-taikhoan', [GuestController::class, 'taikhoan']);
        Route::get('thongtin-nguoidung', [GuestController::class, 'thongtinnguoidung']);
    });
    Route::prefix('danhgiasanpham')->group(function () {
        Route::post('/themdanhgia', [GuestController::class, 'danhgia']);
    });
    Route::prefix('thanhvien')->group(function () {
        Route::post('/donhang', [GuestController::class, 'donhang']);
    });
});
Route::controller(GuestController::class)->group(function () {
    Route::get('sanpham', 'sanpham');
    Route::get('chitietsanpham/{slug}', 'chitietsanpham');
    Route::get('sanpham-danhmuc/{id_category}', 'sanphamtheodanhmuc');
    Route::get('danhmuc', 'danhmuc');
    Route::get('baiviet', 'baiviet');
    Route::get('chitietbaiviet/{slug}', 'chitietbaiviet');
    Route::get('baiviet-danhmuc/{id_category}', 'baiviettheodanhmuc');
    Route::get('danhmucbaiviet', 'danhmucbaiviet');
    Route::get('timkiem', 'timkiem');
    Route::get('khuyenmai', 'xem_ma_giam_gia');
    Route::get('danhgiasanpham/{id_product}', 'xem_danh_gia');
    Route::post('khach/donhang', 'donhang');
    Route::get('donhang/thanhtoan', 'responeVNPAY');
});
