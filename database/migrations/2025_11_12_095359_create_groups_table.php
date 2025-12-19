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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('level');
            $table->integer('parent')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->string('url')->nullable();
            $table->text('image')->nullable();
            $table->text('description')->nullable();
            $table->string('keyword')->nullable();
            $table->integer('turn')->nullable();
            $table->tinyInteger('flag')->nullable()->default(0);
            $table->string('color')->nullable()->default('#424242');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
