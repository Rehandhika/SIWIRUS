<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Ensure table names
        $typesTable = 'penalty_types';
        $penaltiesTable = 'penalties';

        // Helper to get id by code
        $getTypeId = function (string $code) use ($typesTable) {
            $row = DB::table($typesTable)->where('code', $code)->first();
            return $row?->id;
        };

        // Create or ensure new types exist
        $newTypes = [
            ['code' => 'LATE_A', 'name' => 'Keterlambatan A (10–30 menit)', 'points' => 5],
            ['code' => 'LATE_B', 'name' => 'Keterlambatan B (31–60 menit)', 'points' => 10],
            ['code' => 'LATE_C', 'name' => 'Keterlambatan C (>60 menit)', 'points' => 15],
        ];

        foreach ($newTypes as $t) {
            $exists = DB::table($typesTable)->where('code', $t['code'])->exists();
            if (! $exists) {
                DB::table($typesTable)->insert([
                    'code' => $t['code'],
                    'name' => $t['name'],
                    'points' => $t['points'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table($typesTable)->where('code', $t['code'])->update([
                    'name' => $t['name'],
                    'points' => $t['points'],
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            }
        }

        // Map old -> new
        $map = [
            'LATE_MINOR' => 'LATE_A',
            'LATE_MODERATE' => 'LATE_B',
            'LATE_SEVERE' => 'LATE_C',
        ];

        foreach ($map as $old => $new) {
            $oldId = $getTypeId($old);
            $newId = $getTypeId($new);
            if ($oldId && $newId) {
                // Move penalties to new type
                DB::table($penaltiesTable)->where('penalty_type_id', $oldId)->update([
                    'penalty_type_id' => $newId,
                    'updated_at' => now(),
                ]);
            }
        }

        // Delete old types
        DB::table($typesTable)->whereIn('code', array_keys($map))->delete();
    }

    public function down(): void
    {
        $typesTable = 'penalty_types';
        $penaltiesTable = 'penalties';

        // Recreate old types minimally (points approximate original tiers)
        $oldTypes = [
            ['code' => 'LATE_MINOR', 'name' => 'Keterlambatan Minor', 'points' => 5],
            ['code' => 'LATE_MODERATE', 'name' => 'Keterlambatan Sedang', 'points' => 10],
            ['code' => 'LATE_SEVERE', 'name' => 'Keterlambatan Berat', 'points' => 15],
        ];

        foreach ($oldTypes as $t) {
            $exists = DB::table($typesTable)->where('code', $t['code'])->exists();
            if (! $exists) {
                DB::table($typesTable)->insert([
                    'code' => $t['code'],
                    'name' => $t['name'],
                    'points' => $t['points'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Map new -> old
        $getTypeId = function (string $code) use ($typesTable) {
            $row = DB::table($typesTable)->where('code', $code)->first();
            return $row?->id;
        };

        $reverseMap = [
            'LATE_A' => 'LATE_MINOR',
            'LATE_B' => 'LATE_MODERATE',
            'LATE_C' => 'LATE_SEVERE',
        ];

        foreach ($reverseMap as $new => $old) {
            $newId = $getTypeId($new);
            $oldId = $getTypeId($old);
            if ($newId && $oldId) {
                DB::table($penaltiesTable)->where('penalty_type_id', $newId)->update([
                    'penalty_type_id' => $oldId,
                    'updated_at' => now(),
                ]);
            }
        }

        // Hapus tipe baru
        DB::table($typesTable)->whereIn('code', ['LATE_A', 'LATE_B', 'LATE_C'])->delete();
    }
};

