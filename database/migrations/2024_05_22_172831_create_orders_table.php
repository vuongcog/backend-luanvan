<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code');
            $table->decimal('order_total_prices', 12, 3);
            $table->string('customer');
            $table->string('email');
            $table->string('delivery_address');
            $table->string('phone');
            $table->string('payment_type');
            $table->enum('status', ['active', 'inactive','ordered'])->default('active');
            $table->string('order_notes');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->unsignedBigInteger('id_coupon')->nullable();
            $table->unsignedBigInteger('id_payment')->nullable();
            $table->timestamps();
            $table->foreign('id_user')->references('id')->on('users');
            $table->foreign('id_payment')->references('id')->on('payments');
            $table->foreign('id_coupon')->references('id')->on('coupons');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
