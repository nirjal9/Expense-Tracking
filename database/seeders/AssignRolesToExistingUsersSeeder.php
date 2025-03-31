<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignRolesToExistingUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultRole = Role::where('name', 'user')->first();

        // Assign 'user' role to all users who don't have a role
        User::whereDoesntHave('roles')->each(function ($user) use ($defaultRole) {
            $user->roles()->attach($defaultRole->id);
        });

    }
}
