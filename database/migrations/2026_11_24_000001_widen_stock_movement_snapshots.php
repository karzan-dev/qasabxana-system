<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Stock snapshots must keep grams, like the quantity columns widened on 2026-11-20. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->decimal('previous_quantity', 12, 3)->change();
            $table->decimal('new_quantity', 12, 3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->decimal('previous_quantity', 12, 2)->change();
            $table->decimal('new_quantity', 12, 2)->change();
        });
    }
};
