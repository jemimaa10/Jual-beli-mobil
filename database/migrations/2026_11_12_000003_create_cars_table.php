<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('brand');
            $table->string('model');
            $table->string('variant')->nullable();
            $table->unsignedSmallInteger('year');
            $table->string('body_type', 30);
            $table->string('transmission', 30);
            $table->string('fuel', 30);
            $table->unsignedInteger('mileage')->default(0);
            $table->unsignedInteger('engine_cc')->default(0);
            $table->unsignedTinyInteger('seats')->default(5);
            $table->string('color', 40)->nullable();
            $table->string('color_hex', 20)->nullable();
            $table->string('plate', 20)->nullable();
            $table->string('location')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('buy_price')->default(0);
            $table->string('status', 20)->default('available')->index(); // available | booked | sold
            $table->boolean('is_featured')->default(false);
            $table->unsignedTinyInteger('owner_count')->default(1);
            $table->string('tax_valid_until', 7)->nullable(); // YYYY-MM
            $table->boolean('service_record')->default(false);
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->string('image_key', 60)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->json('inspection')->nullable();
            $table->unsignedTinyInteger('inspection_score')->default(0);
            $table->dateTime('sold_at')->nullable();
            $table->unsignedBigInteger('sold_price')->nullable();
            // Tanpa foreign key: transactions dibuat setelah cars dan saling menunjuk
            $table->unsignedBigInteger('sold_transaction_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
