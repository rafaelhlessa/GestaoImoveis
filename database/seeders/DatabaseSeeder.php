<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\TypeOwnershipSeeder;
use Database\Seeders\ActivitySeeder;
use Database\Seeders\ProfileSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProfileSeeder::class,
            TypeOwnershipSeeder::class,
            ActivitySeeder::class,
        ]);
    }
}
