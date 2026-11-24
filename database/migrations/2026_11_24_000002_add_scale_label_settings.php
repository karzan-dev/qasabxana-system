<?php

use App\Services\ScaleLabel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (ScaleLabel::DEFAULTS as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'key_name' => $key, 'key_value' => $value, 'group_name' => 'scale',
            ]);
        }
    }

    public function down(): void
    {
        // Preserve administrator configuration when rolling back application code.
    }
};
