<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->dateTime('date')->index();
            $table->string('sale_type', 20)->index(); // retail | car
            $table->string('customer_name');
            $table->string('customer_phone', 40)->nullable();
            $table->string('cashier');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('amount_due')->default(0);
            $table->string('payment_method', 20); // cash | qris | transfer | credit
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('change_amount')->default(0);
            $table->bigInteger('total_profit')->default(0);
            $table->string('status', 20)->default('completed')->index(); // completed | void
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('car_id')->nullable()->constrained()->nullOnDelete();
            $table->json('car_snapshot')->nullable();
            $table->json('credit')->nullable();
            $table->json('trade_in')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Item menyalin nama, harga, dan modal saat terjual agar nota lama tidak berubah
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('unit', 30)->default('pcs');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('buy_price')->default(0);
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
    }
};
