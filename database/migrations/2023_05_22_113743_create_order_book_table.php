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
        Schema::connection('mysql')->create('order_book', function (Blueprint $table) {
            $table->id();
            $table->string('exchange');
            $table->string('symbol');
            $table->text('order_book');
            $table->timestamps();

            $table->unique(['exchange', 'symbol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('order_book');
    }
};
