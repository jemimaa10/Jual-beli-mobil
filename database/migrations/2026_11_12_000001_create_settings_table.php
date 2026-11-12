<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris (id = 1): profil showroom, kontak, dan tarif.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Auto18');
            $table->string('tagline')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('call_center', 40)->nullable();
            $table->string('whatsapp', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('instagram')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('youtube')->nullable();
            $table->string('support_partner')->nullable();
            $table->string('cashier_name')->default('Admin Showroom');
            $table->text('receipt_footer')->nullable();
            $table->boolean('tax_enabled')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(11);
            $table->decimal('credit_rate', 5, 2)->default(7);
            $table->decimal('min_dp_percent', 5, 2)->default(20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
