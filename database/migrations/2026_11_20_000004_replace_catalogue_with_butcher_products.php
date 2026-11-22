<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Replaces the inherited supermarket items with butcher-shop and spice products in place. */
return new class extends Migration
{
    private const PRODUCTS = [
        ['id' => 1, 'barcode' => '6282010000001', 'scale_product_code' => 'PLU001', 'name_ku' => 'گۆشتی گا - سەرڕان', 'category_id' => 1, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 16000, 'selling_price' => 18500, 'price_per_kg' => 18500, 'tare_weight' => 0, 'wholesale_price' => 17200, 'quantity' => 42, 'min_stock' => 8, 'expiry_date' => '2026-11-25', 'description' => 'پارچەی سەرڕانی گا، تازە و بێ ئێسقان'],
        ['id' => 2, 'barcode' => '6282010000002', 'scale_product_code' => 'PLU002', 'name_ku' => 'گۆشتی گا - پەراسوو', 'category_id' => 1, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 14000, 'selling_price' => 16500, 'price_per_kg' => 16500, 'tare_weight' => 0, 'wholesale_price' => 15200, 'quantity' => 36, 'min_stock' => 7, 'expiry_date' => '2026-11-23', 'description' => 'پەراسووی گا بۆ برژاندن'],
        ['id' => 3, 'barcode' => '6282010000003', 'scale_product_code' => 'PLU003', 'name_ku' => 'گۆشتی گا - کۆڵەکە', 'category_id' => 1, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 11500, 'selling_price' => 13500, 'price_per_kg' => 13500, 'tare_weight' => 0, 'wholesale_price' => 12400, 'quantity' => 55, 'min_stock' => 10, 'expiry_date' => '2026-11-22', 'description' => 'گۆشتی کۆڵەکەی تازە'],
        ['id' => 4, 'barcode' => '6282010000004', 'scale_product_code' => 'PLU004', 'name_ku' => 'گۆشتی مەڕ - ڕان', 'category_id' => 1, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 19000, 'selling_price' => 22000, 'price_per_kg' => 22000, 'tare_weight' => 0, 'wholesale_price' => 20500, 'quantity' => 28, 'min_stock' => 6, 'expiry_date' => '2026-11-24', 'description' => 'ڕانی مەڕی تازە'],
        ['id' => 5, 'barcode' => '6282010000005', 'scale_product_code' => 'PLU005', 'name_ku' => 'گۆشتی مەڕ - شان', 'category_id' => 1, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 17000, 'selling_price' => 19500, 'price_per_kg' => 19500, 'tare_weight' => 0, 'wholesale_price' => 18200, 'quantity' => 30, 'min_stock' => 6, 'expiry_date' => '2026-11-24', 'description' => 'شانی مەڕ بۆ خواردن'],
        ['id' => 6, 'barcode' => '6282010000006', 'scale_product_code' => 'PLU006', 'name_ku' => 'سینگی مریشک', 'category_id' => 2, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 6800, 'selling_price' => 8500, 'price_per_kg' => 8500, 'tare_weight' => 0, 'wholesale_price' => 7600, 'quantity' => 48, 'min_stock' => 10, 'expiry_date' => '2026-11-21', 'description' => 'سینگی مریشکی تازە و بێ پێست'],
        ['id' => 7, 'barcode' => '6282010000007', 'scale_product_code' => 'PLU007', 'name_ku' => 'ڕانی مریشک', 'category_id' => 2, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 5600, 'selling_price' => 7200, 'price_per_kg' => 7200, 'tare_weight' => 0, 'wholesale_price' => 6400, 'quantity' => 52, 'min_stock' => 10, 'expiry_date' => '2026-11-21', 'description' => 'ڕانی مریشکی تازە'],
        ['id' => 8, 'barcode' => '6282010000008', 'scale_product_code' => 'PLU008', 'name_ku' => 'مریشکی تەواو', 'category_id' => 2, 'unit_id' => 1, 'is_weighted' => false, 'purchase_price' => 5200, 'selling_price' => 6500, 'price_per_kg' => null, 'tare_weight' => 0, 'wholesale_price' => 5800, 'quantity' => 40, 'min_stock' => 8, 'expiry_date' => '2026-11-20', 'description' => 'مریشکی تەواوی تازە'],
        ['id' => 9, 'barcode' => '6282010000009', 'scale_product_code' => 'PLU009', 'name_ku' => 'ماسی سەلمۆن', 'category_id' => 3, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 21000, 'selling_price' => 24500, 'price_per_kg' => 24500, 'tare_weight' => 0, 'wholesale_price' => 22800, 'quantity' => 18, 'min_stock' => 4, 'expiry_date' => '2026-11-21', 'description' => 'فیلێتی سەلمۆنی تازە'],
        ['id' => 10, 'barcode' => '6282010000010', 'scale_product_code' => 'PLU010', 'name_ku' => 'ماسی تیلاپیا', 'category_id' => 3, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 10500, 'selling_price' => 12500, 'price_per_kg' => 12500, 'tare_weight' => 0, 'wholesale_price' => 11400, 'quantity' => 26, 'min_stock' => 5, 'expiry_date' => '2026-11-20', 'description' => 'ماسی تیلاپیای تازە'],
        ['id' => 11, 'barcode' => '6282010000011', 'scale_product_code' => 'PLU011', 'name_ku' => 'بەردەڕەش (ئۆتا)', 'category_id' => 4, 'unit_id' => 7, 'is_weighted' => true, 'purchase_price' => 22, 'selling_price' => 35, 'price_per_kg' => 35000, 'tare_weight' => 0, 'wholesale_price' => 30, 'quantity' => 2500, 'min_stock' => 500, 'expiry_date' => '2027-06-01', 'description' => 'بەردەڕەشی ئۆتا بە گرام'],
        ['id' => 12, 'barcode' => '6282010000012', 'scale_product_code' => 'PLU012', 'name_ku' => 'زەنجەفیل', 'category_id' => 4, 'unit_id' => 7, 'is_weighted' => true, 'purchase_price' => 8, 'selling_price' => 12, 'price_per_kg' => 12000, 'tare_weight' => 0, 'wholesale_price' => 10, 'quantity' => 3000, 'min_stock' => 600, 'expiry_date' => '2026-12-15', 'description' => 'زەنجەفیل بە گرام'],
        ['id' => 13, 'barcode' => '6282010000013', 'scale_product_code' => 'PLU013', 'name_ku' => 'زەردەچەوە ١٠٠ گرام', 'category_id' => 4, 'unit_id' => 3, 'is_weighted' => false, 'purchase_price' => 2200, 'selling_price' => 3500, 'price_per_kg' => null, 'tare_weight' => 0, 'wholesale_price' => 3000, 'quantity' => 80, 'min_stock' => 20, 'expiry_date' => '2027-08-01', 'description' => 'زەردەچەوەی پاک'],
        ['id' => 14, 'barcode' => '6282010000014', 'scale_product_code' => 'PLU014', 'name_ku' => 'پاپریکا ١٠٠ گرام', 'category_id' => 4, 'unit_id' => 3, 'is_weighted' => false, 'purchase_price' => 1800, 'selling_price' => 2800, 'price_per_kg' => null, 'tare_weight' => 0, 'wholesale_price' => 2400, 'quantity' => 75, 'min_stock' => 18, 'expiry_date' => '2027-08-01', 'description' => 'پاپریکای سوور'],
        ['id' => 15, 'barcode' => '6282010000015', 'scale_product_code' => 'PLU015', 'name_ku' => 'ڕۆنی زەیتوون ١ لیتر', 'category_id' => 5, 'unit_id' => 5, 'is_weighted' => false, 'purchase_price' => 11000, 'selling_price' => 14500, 'price_per_kg' => null, 'tare_weight' => 0, 'wholesale_price' => 13000, 'quantity' => 45, 'min_stock' => 10, 'expiry_date' => '2027-10-01', 'description' => 'ڕۆنی زەیتوونی پاک'],
        ['id' => 16, 'barcode' => '6282010000016', 'scale_product_code' => 'PLU016', 'name_ku' => 'تەماتە ڕوو', 'category_id' => 5, 'unit_id' => 3, 'is_weighted' => false, 'purchase_price' => 1500, 'selling_price' => 2200, 'price_per_kg' => null, 'tare_weight' => 0, 'wholesale_price' => 1900, 'quantity' => 90, 'min_stock' => 20, 'expiry_date' => '2027-05-01', 'description' => 'تەماتە ڕووی تازە'],
        ['id' => 17, 'barcode' => '6282010000017', 'scale_product_code' => 'PLU017', 'name_ku' => 'جگەری گا', 'category_id' => 6, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 7800, 'selling_price' => 9500, 'price_per_kg' => 9500, 'tare_weight' => 0, 'wholesale_price' => 8600, 'quantity' => 32, 'min_stock' => 7, 'expiry_date' => '2026-11-22', 'description' => 'جگەری گای تازە'],
        ['id' => 18, 'barcode' => '6282010000018', 'scale_product_code' => 'PLU018', 'name_ku' => 'گورچیلەی مەڕ', 'category_id' => 6, 'unit_id' => 2, 'is_weighted' => true, 'purchase_price' => 9500, 'selling_price' => 11500, 'price_per_kg' => 11500, 'tare_weight' => 0, 'wholesale_price' => 10500, 'quantity' => 24, 'min_stock' => 5, 'expiry_date' => '2026-11-22', 'description' => 'گورچیلەی مەڕی تازە'],
        ['id' => 19, 'barcode' => '6282010000019', 'scale_product_code' => 'PLU019', 'name_ku' => 'گۆشتی بەستوو ١ کیلۆ', 'category_id' => 7, 'unit_id' => 3, 'is_weighted' => false, 'purchase_price' => 13000, 'selling_price' => 15500, 'price_per_kg' => null, 'tare_weight' => 0, 'wholesale_price' => 14200, 'quantity' => 50, 'min_stock' => 12, 'expiry_date' => '2027-04-01', 'description' => 'گۆشتی بەستووی پاکەجکراو'],
        ['id' => 20, 'barcode' => '6282010000020', 'scale_product_code' => 'PLU020', 'name_ku' => 'مریشکی بەستوو ١ کیلۆ', 'category_id' => 7, 'unit_id' => 3, 'is_weighted' => false, 'purchase_price' => 6000, 'selling_price' => 7200, 'price_per_kg' => null, 'tare_weight' => 0, 'wholesale_price' => 6600, 'quantity' => 60, 'min_stock' => 15, 'expiry_date' => '2027-04-01', 'description' => 'مریشکی بەستووی پاکەجکراو'],
    ];

    public function up(): void
    {
        foreach (self::PRODUCTS as $product) {
            DB::table('products')->where('id', $product['id'])->update([
                ...$product,
                'image' => null,
                'status' => 'ACTIVE',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Catalogue rows are customer data; the replacement is intentionally not reversed.
    }
};
