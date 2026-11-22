<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Reapplies the guarded store-name conversion for databases migrated before the legacy list was extended. */
return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            'store_name' => ['قەسابخانەی من', ['سەنگەر مارکێت', 'Supermarket', 'Singer Markat', 'فرۆشگای من', '']],
            'store_name_en' => ['My Butcher Shop', ['Singer Markat', 'Sangar Markat', 'Supermarket', 'My Supermarket', '']],
            'store_name_ar' => ['مجزري', ['المغنية ماركات', 'المتجر', 'متجري', '']],
            'receipt_footer' => [
                'سوپاس بۆ سەردانەکەتان — گۆشتی تازە و کوالیتی متمانەپێکراو',
                [
                    '',
                    'Thank you for shopping with us',
                    'سوپاس بۆ سەردانەکەتان، کاڵای فرۆشراو تا ٢٤ کاتژمێر دەگۆڕدرێتەوە.',
                    'سوپاس بۆ سەردانەکەتان، کاڵای فرۆشراو تا ٢٤ کاتژمێر دەگۆڕدرێتەوە',
                ],
            ],
        ];

        foreach ($updates as $key => [$value, $legacyValues]) {
            DB::table('settings')
                ->where('key_name', $key)
                ->whereIn('key_value', $legacyValues)
                ->update(['key_value' => $value]);
        }
    }

    public function down(): void
    {
        // Store branding data is intentionally not reverted.
    }
};
