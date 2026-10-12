<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['username' => 'naufal_admin'],
            [
                'name' => 'Administrator Naufal',
                'email' => 'naufal_admin@naumilrice.com',
                'password' => \Illuminate\Support\Facades\Hash::make('naufal2020'),
            ]
        );

        $this->call(FoodEcommerceSeeder::class);
    }
}
