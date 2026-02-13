<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        // Add new setting for SHU conversion amount with default 10,000
        Setting::set('shu_point_conversion_amount', '10000');
        
        // Remove old setting
        Setting::where('key', 'shu_point_percentage_bps')->delete();
    }

    public function down(): void
    {
        // Revert (though we lose old data)
        Setting::set('shu_point_percentage_bps', '0');
        Setting::where('key', 'shu_point_conversion_amount')->delete();
    }
};
