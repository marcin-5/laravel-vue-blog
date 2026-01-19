<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Optionally seed other data here
        $this->call([
            AdminUserSeeder::class,
        ]);

        // Development seeder
        if (app()->environment(['local', 'development'])) {
            $this->call([
                DevelopmentSeeder::class,
                PostExtensionSeeder::class,
            ]);
        }
    }
}
