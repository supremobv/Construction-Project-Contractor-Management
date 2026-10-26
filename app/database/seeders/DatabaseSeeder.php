<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::table('business_settings')->updateOrInsert(['id'=>1],['name'=>'Forma Construction','currency'=>'PHP','accent_color'=>'#d9f36c','updated_at'=>now()]);
    }
}
