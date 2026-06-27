<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@legacy.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            StockMovementSeeder::class,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
