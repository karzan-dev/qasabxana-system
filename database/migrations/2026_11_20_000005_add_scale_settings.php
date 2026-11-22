<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Adds the smart-scale configuration keys used by Settings and POS. */
return new class extends Migration
{
    private const SETTINGS = [
        'scale_enabled' => '0',
        'scale_mode' => 'manual',
        'scale_port' => '',
        'scale_baud_rate' => '9600',
        'weight_decimals' => '3',
        'receipt_width' => '80mm',
    ];

    public function up(): void
    {
        foreach (self::SETTINGS as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key_name' => $key],
                ['key_value' => $value, 'group_name' => 'scale'],
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key_name', array_keys(self::SETTINGS))->delete();
    }
};
