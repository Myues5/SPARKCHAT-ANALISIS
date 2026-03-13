<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabelsSeeder extends Seeder
{
    public function run()
    {
        $labels = [
            ['label_name' => 'sales', 'created_by' => 'system'],
            ['label_name' => 'complain', 'created_by' => 'system'],
            ['label_name' => 'informasi', 'created_by' => 'system'],
        ];

        foreach ($labels as $label) {
            DB::table('labels')->updateOrInsert(
                ['label_name' => $label['label_name']],
                $label
            );
        }
    }
}

