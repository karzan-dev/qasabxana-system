<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Additive schema for gram precision, per-kg pricing and scale metadata. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('is_weighted')->default(false)->after('unit_id');
            $table->decimal('price_per_kg', 12, 2)->nullable()->after('selling_price');
            $table->string('scale_product_code', 50)->nullable()->after('barcode');
            $table->decimal('tare_weight', 12, 3)->default(0.000)->after('price_per_kg');
            $table->index('scale_product_code');
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->decimal('gross_weight', 12, 3)->nullable()->after('quantity');
            $table->decimal('tare_weight', 12, 3)->nullable()->after('gross_weight');
            $table->decimal('net_weight', 12, 3)->nullable()->after('tare_weight');
            $table->string('weight_unit', 10)->nullable()->after('net_weight');
            $table->string('scale_source', 30)->nullable()->after('weight_unit');
            $table->decimal('price_per_kg', 12, 2)->nullable()->after('scale_source');
        });

        // 0.01 kg steps (10 g) were too coarse for butcher scales.
        $quantityColumns = [
            ['products', 'quantity'],
            ['products', 'min_stock'],
            ['sale_items', 'quantity'],
            ['purchase_items', 'quantity'],
            ['stock_movements', 'quantity'],
            ['return_items', 'quantity'],
        ];

        foreach ($quantityColumns as [$table, $column]) {
            if (Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                    $blueprint->decimal($column, 12, 3)->change();
                });
            }
        }

        // Existing kilogram/gram products become weighted products. A gram price
        // is converted to the equivalent per-kilogram price.
        DB::table('products')
            ->join('units', 'products.unit_id', '=', 'units.id')
            ->where('units.short_code', 'kg')
            ->update([
                'products.is_weighted' => true,
                'products.price_per_kg' => DB::raw('products.selling_price'),
            ]);
        DB::table('products')
            ->join('units', 'products.unit_id', '=', 'units.id')
            ->where('units.short_code', 'g')
            ->update([
                'products.is_weighted' => true,
                'products.price_per_kg' => DB::raw('products.selling_price * 1000'),
            ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['scale_product_code']);
            $table->dropColumn(['is_weighted', 'price_per_kg', 'scale_product_code', 'tare_weight']);
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropColumn([
                'gross_weight', 'tare_weight', 'net_weight',
                'weight_unit', 'scale_source', 'price_per_kg',
            ]);
        });

        $quantityColumns = [
            ['products', 'quantity'],
            ['products', 'min_stock'],
            ['sale_items', 'quantity'],
            ['purchase_items', 'quantity'],
            ['stock_movements', 'quantity'],
            ['return_items', 'quantity'],
        ];

        foreach ($quantityColumns as [$table, $column]) {
            if (Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                    $blueprint->decimal($column, 12, 2)->change();
                });
            }
        }
    }
};
