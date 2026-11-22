<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Converts the in-place catalogue to butcher-shop terminology without
 * touching table names, column names, IDs, or product references.
 */
return new class extends Migration
{
    private const CATEGORIES = [
        ['name_ku' => 'گۆش و قەسابخانە', 'name_en' => 'Meat & Butcher Shop', 'name_ar' => 'اللحوم والمجزر', 'icon' => 'Beef'],
        ['name_ku' => 'مریشک و پەلەوەر', 'name_en' => 'Chicken & Poultry', 'name_ar' => 'الدواجن', 'icon' => 'Bird'],
        ['name_ku' => 'ماسی و خۆراکی دەریا', 'name_en' => 'Fish & Seafood', 'name_ar' => 'الأسماك والمأكولات البحرية', 'icon' => 'Fish'],
        ['name_ku' => 'بەهارات و تامەکان', 'name_en' => 'Spices & Seasonings', 'name_ar' => 'التوابل والبهارات', 'icon' => 'Flame'],
        ['name_ku' => 'پێداویستیی چێشت و تر', 'name_en' => 'Cooking Ingredients & Other', 'name_ar' => 'مكونات الطبخ وغيرها', 'icon' => 'CookingPot'],
        ['name_ku' => 'جگەر و بەشەکانی تر', 'name_en' => 'Offal & By-products', 'name_ar' => 'الأحشاء والمنتجات الثانوية', 'icon' => 'Layers'],
        ['name_ku' => 'گۆشتی بەستوو و ئامادەکراو', 'name_en' => 'Frozen & Prepared', 'name_ar' => 'المجمدات والمجهزات', 'icon' => 'Snowflake'],
    ];

    public function up(): void
    {
        // Rename existing category rows in place so product foreign keys stay valid.
        DB::table('categories')->orderBy('id')->get()->each(function ($category, int $index): void {
            $values = self::CATEGORIES[$index] ?? self::CATEGORIES[array_key_last(self::CATEGORIES)];
            DB::table('categories')->where('id', $category->id)->update([
                'name_ku' => $values['name_ku'],
                'name_en' => $values['name_en'],
                'name_ar' => $values['name_ar'],
                'icon' => $values['icon'],
                'status' => true,
            ]);
        });

        foreach (self::CATEGORIES as $category) {
            $exists = DB::table('categories')->where('name_en', $category['name_en'])->exists();
            if (! $exists) {
                DB::table('categories')->insert($category);
            }
        }

        // Weight units used by the scales and POS.
        DB::table('units')->updateOrInsert(
            ['short_code' => 'kg'],
            ['name_ku' => 'کیلۆگرام', 'name_en' => 'Kilogram', 'name_ar' => 'كيلوغرام', 'short_code' => 'kg']
        );
        DB::table('units')->updateOrInsert(
            ['short_code' => 'g'],
            ['name_ku' => 'گرام', 'name_en' => 'Gram', 'name_ar' => 'غرام', 'short_code' => 'g']
        );

        $this->setLegacySetting('store_name', 'قەسابخانەی من', ['سەنگەر مارکێت', 'Supermarket', 'Singer Markat', 'فرۆشگای من', '']);
        $this->setLegacySetting('store_name_en', 'My Butcher Shop', ['Singer Markat', 'Sangar Markat', 'Supermarket', 'My Supermarket', '']);
        $this->setLegacySetting('store_name_ar', 'مجزري', ['المغنية ماركات', 'المتجر', 'متجري', '']);
        $this->setLegacySetting('currency', 'IQD', ['']);
        $this->setLegacySetting('receipt_footer', 'سوپاس بۆ سەردانەکەتان — گۆشتی تازە و کوالیتی متمانەپێکراو', [
            '',
            'Thank you for shopping with us',
            'سوپاس بۆ سەردانەکەتان، کاڵای فرۆشراو تا ٢٤ کاتژمێر دەگۆڕدرێتەوە.',
            'سوپاس بۆ سەردانەکەتان، کاڵای فرۆشراو تا ٢٤ کاتژمێر دەگۆڕدرێتەوە',
        ]);
        $this->setLegacySetting('invoice_prefix', 'INV', ['']);
    }

    private function setLegacySetting(string $key, string $value, array $legacyValues): void
    {
        $setting = DB::table('settings')->where('key_name', $key)->first();

        if (! $setting) {
            DB::table('settings')->insert([
                'key_name' => $key,
                'key_value' => $value,
                'group_name' => 'general',
            ]);

            return;
        }

        if (in_array((string) $setting->key_value, $legacyValues, true)) {
            DB::table('settings')->where('key_name', $key)->update(['key_value' => $value]);
        }
    }

    public function down(): void
    {
        // Catalogue data is customer data; it is intentionally not reverted.
    }
};
