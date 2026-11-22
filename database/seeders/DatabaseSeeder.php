<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Roles
        Role::updateOrCreate(
            ['name' => 'ADMIN'],
            [
                'display_name_ku' => 'بەڕێوەبەری سەرەکی',
                'display_name_en' => 'Super Administrator',
                'display_name_ar' => 'مدير النظام الرئيسي',
                'description' => 'دەسەڵاتی تەواو بەسەر سەرجەم بەش و داتاکاندا',
            ],
        );

        Role::updateOrCreate(
            ['name' => 'MANAGER'],
            [
                'display_name_ku' => 'بەڕێوەبەری لق / کۆگا',
                'display_name_en' => 'Store Manager',
                'display_name_ar' => 'مدير الفرع والمخزن',
                'description' => 'بەڕێوەبردنی کۆگا، کڕین، فرۆشتن و کڕیاران',
            ],
        );

        Role::updateOrCreate(
            ['name' => 'CASHIER'],
            [
                'display_name_ku' => 'کاشێر / فرۆشیار',
                'display_name_en' => 'POS Cashier',
                'display_name_ar' => 'محاسب كاشير',
                'description' => 'تەنها دەسەڵاتی تۆمارکردنی فرۆشتن لە کاشێر',
            ],
        );

        // 2. Seed login users
        $this->call(UserSeeder::class);
        $this->call(RolePermissionSeeder::class);

        // 3. Seed Categories
        $categories = [
            ['id' => 1, 'name_ku' => 'گۆش و قەسابخانە', 'name_en' => 'Meat & Butcher Shop', 'name_ar' => 'اللحوم والمجزر', 'icon' => 'Beef'],
            ['id' => 2, 'name_ku' => 'مریشک و پەلەوەر', 'name_en' => 'Chicken & Poultry', 'name_ar' => 'الدواجن', 'icon' => 'Bird'],
            ['id' => 3, 'name_ku' => 'ماسی و خۆراکی دەریا', 'name_en' => 'Fish & Seafood', 'name_ar' => 'الأسماك والمأكولات البحرية', 'icon' => 'Fish'],
            ['id' => 4, 'name_ku' => 'بەهارات و تامەکان', 'name_en' => 'Spices & Seasonings', 'name_ar' => 'التوابل والبهارات', 'icon' => 'Flame'],
            ['id' => 5, 'name_ku' => 'پێداویستیی چێشت و تر', 'name_en' => 'Cooking Ingredients & Other', 'name_ar' => 'مكونات الطبخ وغيرها', 'icon' => 'CookingPot'],
            ['id' => 6, 'name_ku' => 'جگەر و بەشەکانی تر', 'name_en' => 'Offal & By-products', 'name_ar' => 'الأحشاء والمنتجات الثانوية', 'icon' => 'Layers'],
            ['id' => 7, 'name_ku' => 'گۆشتی بەستوو و ئامادەکراو', 'name_en' => 'Frozen & Prepared', 'name_ar' => 'المجمدات والمجهزات', 'icon' => 'Snowflake'],
        ];
        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert(
                ['id' => $cat['id']],
                [...$cat, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            );
        }

        // 4. Seed Units
        $units = [
            ['id' => 1, 'name_ku' => 'دانە', 'name_en' => 'Piece', 'name_ar' => 'قطعة', 'short_code' => 'pcs'],
            ['id' => 2, 'name_ku' => 'کیلۆگرام', 'name_en' => 'Kilogram', 'name_ar' => 'كيلوغرام', 'short_code' => 'kg'],
            ['id' => 3, 'name_ku' => 'پاکەت', 'name_en' => 'Pack', 'name_ar' => 'علبة / باكت', 'short_code' => 'pack'],
            ['id' => 4, 'name_ku' => 'کارتۆن', 'name_en' => 'Carton', 'name_ar' => 'كرتونة', 'short_code' => 'ctn'],
            ['id' => 5, 'name_ku' => 'لیتر', 'name_en' => 'Liter', 'name_ar' => 'لتر', 'short_code' => 'L'],
            ['id' => 7, 'name_ku' => 'گرام', 'name_en' => 'Gram', 'name_ar' => 'غرام', 'short_code' => 'g'],
        ];
        foreach ($units as $u) {
            DB::table('units')->updateOrInsert(['id' => $u['id']], $u);
        }

        // 5. Seed Products
        $products = [
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

        foreach ($products as $p) {
            unset($p['name_en'], $p['name_ar']);
            DB::table('products')->updateOrInsert(
                ['id' => $p['id']],
                [...$p, 'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now()],
            );
        }

        // 6. Seed Customers
        DB::table('customers')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'دانا محەمەد ڕەشید',
                'phone' => '0750 445 1122',
                'address' => 'هەولێر - گەڕەکی بەختیاری',
                'total_purchases' => 158500,
                'total_paid' => 108500,
                'debt' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('customers')->updateOrInsert(
            ['id' => 2],
            [
                'name' => 'شڤان سەردار کەریم',
                'phone' => '0770 112 3344',
                'address' => 'سلێمانی - سەرچنار',
                'total_purchases' => 45000,
                'total_paid' => 45000,
                'debt' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('customers')->updateOrInsert(
            ['id' => 3],
            [
                'name' => 'Demo Customer',
                'phone' => '0750 000 0003',
                'address' => 'Erbil',
                'total_purchases' => 8500,
                'total_paid' => 8500,
                'debt' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // 7. Seed Suppliers
        DB::table('suppliers')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'کۆمپانیای بازرگانی زۆزان',
                'company' => 'Zozan Food Trading LLC',
                'phone' => '0750 999 8877',
                'address' => 'هەولێر - ناوچەی پیشەسازی باکوور',
                'total_purchases' => 2400000,
                'total_paid' => 1400000,
                'debt' => 1000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('suppliers')->updateOrInsert(
            ['id' => 2],
            [
                'name' => 'Demo Supplier',
                'company' => 'Sample Wholesale Foods',
                'phone' => '0750 000 0002',
                'address' => 'Erbil',
                'total_purchases' => 32500,
                'total_paid' => 32500,
                'debt' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // 8. Seed Settings
        $settings = [
            'store_name' => 'قەسابخانەی من',
            'store_name_en' => 'My Butcher Shop',
            'store_name_ar' => 'مجزري',
            'store_phone' => '+964 750 123 4567',
            'store_address' => 'هەولێر - شەقامی ٦٠ مەتری - بەرامبەر پارکی نیشتمان',
            'currency' => 'IQD',
            'invoice_prefix' => 'INV-',
            'receipt_footer' => 'سوپاس بۆ سەردانەکەتان — گۆشتی تازە و کوالیتی متمانەپێکراو',
            'default_language' => 'ku',
            'allow_negative_stock' => '0',
            'low_stock_threshold' => '5',
            'scale_enabled' => '0',
            'scale_mode' => 'manual',
            'scale_port' => '',
            'scale_baud_rate' => '9600',
            'weight_decimals' => '3',
            'receipt_width' => '80mm',
        ];

        foreach ($settings as $k => $v) {
            Setting::set($k, $v);
        }

        $this->seedBusinessTables();
    }

    private function seedBusinessTables(): void
    {
        $userId = User::query()->whereHas('role', fn ($query) => $query->where('name', 'ADMIN'))->value('id');

        $expenseCategories = [
            ['id' => 1, 'name_ku' => 'کارەبا', 'name_en' => 'Electricity', 'name_ar' => 'الكهرباء'],
            ['id' => 2, 'name_ku' => 'کرێی شوێن', 'name_en' => 'Rent', 'name_ar' => 'الإيجار'],
            ['id' => 3, 'name_ku' => 'گواستنەوە', 'name_en' => 'Transport', 'name_ar' => 'النقل'],
        ];

        foreach ($expenseCategories as $category) {
            DB::table('expense_categories')->updateOrInsert(['id' => $category['id']], $category);
        }

        $purchaseDate = now()->subDays(5);
        DB::table('purchases')->updateOrInsert(
            ['invoice_number' => 'DEMO-PUR-0001'],
            [
                'supplier_id' => 2,
                'user_id' => $userId,
                'total' => 32500,
                'paid_amount' => 32500,
                'remaining_amount' => 0,
                'payment_status' => 'PAID',
                'notes' => 'Seeded sample purchase',
                'created_at' => $purchaseDate,
                'updated_at' => $purchaseDate,
            ],
        );
        $purchaseId = DB::table('purchases')->where('invoice_number', 'DEMO-PUR-0001')->value('id');
        DB::table('purchase_items')->updateOrInsert(
            ['purchase_id' => $purchaseId, 'product_id' => 6],
            ['quantity' => 5, 'purchase_price' => 6500, 'total' => 32500],
        );

        $saleDate = now()->subDays(3);
        DB::table('sales')->updateOrInsert(
            ['invoice_number' => 'DEMO-SALE-0001'],
            [
                'user_id' => $userId,
                'customer_id' => 3,
                'subtotal' => 8500,
                'discount' => 0,
                'tax' => 0,
                'total' => 8500,
                'cost_of_goods' => 6250,
                'profit' => 2250,
                'cash_received' => 8500,
                'change_amount' => 0,
                'payment_method' => 'CASH',
                'payment_status' => 'PAID',
                'notes' => 'Seeded sample sale',
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ],
        );
        $saleId = DB::table('sales')->where('invoice_number', 'DEMO-SALE-0001')->value('id');

        foreach ([
            ['product_id' => 1, 'quantity' => 2, 'unit_price' => 1750, 'purchase_cost' => 1250, 'total' => 3500, 'profit' => 1000],
            ['product_id' => 5, 'quantity' => 1, 'unit_price' => 5000, 'purchase_cost' => 3750, 'total' => 5000, 'profit' => 1250],
        ] as $item) {
            DB::table('sale_items')->updateOrInsert(
                ['sale_id' => $saleId, 'product_id' => $item['product_id']],
                [...$item, 'discount' => 0],
            );
        }

        DB::table('payments')->updateOrInsert(
            ['payment_type' => 'SALE_PAYMENT', 'reference_id' => $saleId, 'notes' => 'Seeded sample sale payment'],
            [
                'party_type' => 'CUSTOMER',
                'party_id' => 3,
                'amount' => 8500,
                'payment_method' => 'CASH',
                'user_id' => $userId,
                'created_at' => $saleDate,
            ],
        );
        DB::table('payments')->updateOrInsert(
            ['payment_type' => 'SUPPLIER_PAYMENT', 'reference_id' => $purchaseId, 'notes' => 'Seeded sample supplier payment'],
            [
                'party_type' => 'SUPPLIER',
                'party_id' => 2,
                'amount' => 32500,
                'payment_method' => 'CASH',
                'user_id' => $userId,
                'created_at' => $purchaseDate,
            ],
        );

        $returnDate = now()->subDay();
        DB::table('returns')->updateOrInsert(
            ['sale_id' => $saleId, 'reason' => 'Seeded sample return'],
            [
                'invoice_number' => 'DEMO-SALE-0001',
                'customer_id' => 3,
                'user_id' => $userId,
                'total_refund' => 1750,
                'created_at' => $returnDate,
                'updated_at' => $returnDate,
            ],
        );
        $returnId = DB::table('returns')->where('sale_id', $saleId)->where('reason', 'Seeded sample return')->value('id');
        DB::table('return_items')->updateOrInsert(
            ['return_id' => $returnId, 'product_id' => 1],
            ['quantity' => 1, 'unit_price' => 1750, 'total' => 1750],
        );
        DB::table('payments')->updateOrInsert(
            ['payment_type' => 'REFUND', 'reference_id' => $returnId, 'notes' => 'Seeded sample return refund'],
            [
                'party_type' => 'CUSTOMER',
                'party_id' => 3,
                'amount' => 1750,
                'payment_method' => 'CASH',
                'user_id' => $userId,
                'created_at' => $returnDate,
            ],
        );

        DB::table('expenses')->updateOrInsert(
            ['description' => 'Seeded sample electricity expense'],
            [
                'category_id' => 1,
                'amount' => 25000,
                'date' => now()->subDays(2)->toDateString(),
                'user_id' => $userId,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
        );

        $movements = [
            ['product_id' => 6, 'movement_type' => 'PURCHASE', 'quantity' => 5, 'previous_quantity' => 25, 'new_quantity' => 30, 'reference_type' => 'PURCHASE', 'reference_id' => $purchaseId, 'notes' => 'Seeded sample purchase movement', 'created_at' => $purchaseDate],
            ['product_id' => 1, 'movement_type' => 'SALE', 'quantity' => -2, 'previous_quantity' => 49, 'new_quantity' => 47, 'reference_type' => 'SALE', 'reference_id' => $saleId, 'notes' => 'Seeded sample sale movement', 'created_at' => $saleDate],
            ['product_id' => 5, 'movement_type' => 'SALE', 'quantity' => -1, 'previous_quantity' => 61, 'new_quantity' => 60, 'reference_type' => 'SALE', 'reference_id' => $saleId, 'notes' => 'Seeded sample sale movement', 'created_at' => $saleDate],
            ['product_id' => 1, 'movement_type' => 'RETURN', 'quantity' => 1, 'previous_quantity' => 47, 'new_quantity' => 48, 'reference_type' => 'RETURN', 'reference_id' => $returnId, 'notes' => 'Seeded sample return movement', 'created_at' => $returnDate],
        ];

        foreach ($movements as $movement) {
            DB::table('stock_movements')->updateOrInsert(
                [
                    'product_id' => $movement['product_id'],
                    'movement_type' => $movement['movement_type'],
                    'reference_type' => $movement['reference_type'],
                    'reference_id' => $movement['reference_id'],
                    'notes' => $movement['notes'],
                ],
                [...$movement, 'user_id' => $userId],
            );
        }

        DB::table('activity_logs')->updateOrInsert(
            ['action' => 'SEED', 'entity' => 'DEMO_DATA', 'entity_id' => $saleId],
            [
                'user_id' => $userId,
                'ip_address' => '127.0.0.1',
                'description' => 'Seeded sample supermarket data',
                'created_at' => $saleDate,
            ],
        );
    }
}
