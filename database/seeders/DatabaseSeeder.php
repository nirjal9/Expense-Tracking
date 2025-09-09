<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
    $this->call([
        RoleSeeder::class,
        AdminUserSeeder::class,
        PermissionSeeder::class,
        AssignRolesToExistingUsersSeeder::class,
        CategorySeeder::class,
    ]);
        // Seed users with IDs 101-105
        foreach (range(101, 105) as $id) {
            User::updateOrCreate(
                ['id' => $id],
                [
                    'name' => 'Test User ' . $id,
                    'email' => 'testuser' . $id . '@example.com',
                    'password' => Hash::make('password'),
                    'is_completed' => 1,
                ]
            );
        }
        // Complete registration for users 101-105
        $categories = range(1, 6);
        $budgetPercentages = array_fill_keys($categories, 16); // 16% each (total 96%)
        foreach (range(101, 105) as $id) {
            $user = User::find($id);
            if ($user) {
                // Assign income
                $user->incomes()->create([
                    'amount' => 50000,
                    'date' => now(),
                    'description' => 'Seeder income',
                ]);
                // Attach categories with budget percentages
                $syncData = [];
                foreach ($budgetPercentages as $catId => $percent) {
                    $syncData[$catId] = ['budget_percentage' => $percent];
                }
                $user->categories()->syncWithoutDetaching($syncData);
                // Mark registration as complete
                $user->update(['is_completed' => true]);
            }
        }
    }
}
