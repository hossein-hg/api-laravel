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
           
                $table->id();  // id: number (auto increment)
                $table->string('name');  // name: string (required)
               
                $table->tinyInteger('stars')->nullable();  // stars: number (decimal for rating)
                $table->string('url')->nullable();  // optional
                $table->string('price');  // price: string (required)
                $table->string('oldPrice')->nullable();  // optional
                $table->string('cover');  // image: string (required)
                
                $table->tinyInteger('inventory')->default(0)->comment('0: out of stock, 1: in stock');  // inventory: 0 | 1
                $table->text('shortDescription')->nullable();  // optional
                $table->json('tags')->nullable();  // tags: string[] (JSON array)
                $table->integer('salesCount')->default(0)->nullable();  // optional
                $table->longText('description')->nullable();  // optional
                $table->integer('countdown')->nullable();  // optional
                $table->integer('warehouseInventory')->default(0)->nullable();  // optional
                $table->decimal('satisfaction', 3, 2)->nullable();  // optional
                
                $table->text('additionalInformation')->nullable();  // optional
                $table->timestamps();  // created_at, updated_at
                $table->softDeletes();
            
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
