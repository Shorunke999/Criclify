<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Factories\PostFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Database\Seeders\ReferenceCountryAndCurrenciesSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            RolesAndPermissionsSeeder::class,
            ReferenceCountryAndCurrenciesSeeder::class
        ]);

        $admin = User::firstOrCreate([
            'first_name' => 'Admin',
            'last_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'account_status' =>AccountStatus::APPROVED,
            'email_verified_at' =>now()
        ]);
        $admin->assignRole('admin');
    }
}
