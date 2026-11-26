<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('scale_barcode_enabled')->default(true);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->json('scale_barcodes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('scale_barcode_enabled');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('scale_barcodes');
        });
    }
};
