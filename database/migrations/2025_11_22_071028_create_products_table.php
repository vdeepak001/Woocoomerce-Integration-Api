<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // WooCommerce mapping
            $table->unsignedBigInteger('woocommerce_id')->nullable()->unique();
            
            // Product details
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            
            // Categories stored as JSON array
            $table->json('categories')->nullable();
            
            // Sync status: pending, synced, failed
            $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending');
            $table->text('sync_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('woocommerce_id');
            $table->index('sku');
            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
