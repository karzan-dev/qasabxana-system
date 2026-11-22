<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Clears image paths inherited from the previous supermarket catalogue. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->update(['image' => null, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Image paths are legacy customer data and are intentionally not restored.
    }
};
