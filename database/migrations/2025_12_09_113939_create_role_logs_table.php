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
        Schema::create('role_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('role')->nullable();
            $table->bigInteger('order_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('date')->nullable();
            $table->morphs('loggable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_logs');
    }
};
