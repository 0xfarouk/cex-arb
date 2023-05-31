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
        DB::statement('DROP VIEW IF EXISTS order_book_peaks');
        DB::statement("
            CREATE view order_book_peaks AS
            SELECT
                exchange,
                symbol,
                JSON_EXTRACT(JSON_EXTRACT(JSON_EXTRACT(ob.order_book, '$.asks'), '$[0]'), '$[0]') ask,
                JSON_EXTRACT(JSON_EXTRACT(JSON_EXTRACT(ob.order_book, '$.bids'), '$[0]'), '$[0]') bid
            FROM order_book ob
            WHERE
                JSON_EXTRACT(JSON_EXTRACT(JSON_EXTRACT(ob.order_book, '$.asks'), '$[0]'), '$[0]') IS NOT NULL
                AND JSON_EXTRACT(JSON_EXTRACT(JSON_EXTRACT(ob.order_book, '$.bids'), '$[0]'), '$[0]') IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_book_peaks');
    }
};
