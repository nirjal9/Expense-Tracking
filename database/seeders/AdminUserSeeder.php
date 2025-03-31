<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'password' => Hash::make('password'), // Change this to a secure password
        ]);

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $admin->roles()->sync([$adminRole->id]); // Sync ensures only one role
        } else {
            echo "Admin role not found! Please seed roles first.\n";
        }
    }
}
