<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sell_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type', 20)->default('sell'); // sell | trade-in
            $table->string('customer_name');
            $table->string('phone', 40);
            $table->string('email')->nullable();
            $table->string('brand');
            $table->string('model');
            $table->string('variant')->nullable();
            $table->unsignedSmallInteger('year');
            $table->string('body_type', 30)->nullable();
            $table->string('transmission', 30)->nullable();
            $table->string('fuel', 30)->nullable();
            $table->unsignedInteger('mileage')->default(0);
            $table->string('color', 40)->nullable();
            $table->string('location')->nullable();
            $table->string('condition', 30)->nullable();
            $table->unsignedBigInteger('asking_price')->default(0);
            $table->unsignedBigInteger('estimate_low')->default(0);
            $table->unsignedBigInteger('estimate_high')->default(0);
            $table->date('inspection_date')->nullable();
            $table->string('inspection_time', 5)->nullable();
            $table->foreignId('trade_in_car_id')->nullable()->constrained('cars')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('new')->index(); // new | scheduled | inspected | purchased | rejected
            $table->unsignedBigInteger('offer_price')->default(0);
            $table->foreignId('purchased_car_id')->nullable()->constrained('cars')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('test_drives', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('car_id')->nullable()->constrained()->nullOnDelete();
            $table->string('car_name');
            $table->string('customer_name');
            $table->string('phone', 40);
            $table->date('date');
            $table->string('time', 5);
            $table->string('location', 20)->default('showroom'); // showroom | rumah
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending | confirmed | done | cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_drives');
        Schema::dropIfExists('sell_requests');
    }
};
