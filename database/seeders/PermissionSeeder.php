<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $routes=collect(Route::getRoutes())
        ->map(function ($route) {
        $folder = explode('/', $route->uri())[0];
        return [
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'folder' => $folder,
        ];
    })->groupBy('folder');

        foreach($routes as $foldRoute) {
            foreach($foldRoute as $route) {
                if (
                    $route['uri'] == 'login' ||
                    $route['uri'] == 'forgot-password' ||
                    $route['uri'] == 'reset-password' ||
                    $route['uri'] == 'verify-email' ||
                    $route['uri'] == 'verify-email/{id}/{hash}' ||
                    $route['uri'] == 'storage/{path}' ||
                    $route['uri'] == 'up' ||
                    $route['uri'] == '/' ||
                    $route['uri'] == 'register' ||
                    $route['uri'] == 'reset-password/{token}' ||
                    $route['uri'] == 'confirm-password'
                ) {
                    break;
                }
                if (!empty($route['name'])) {
                    Permission::updateOrCreate([
                        'url' => $route['uri'],
                        'name' => $route['name'],
                        'group' => $route['folder'],
                        'slug' => Str::slug(str_replace('.', ' ', $route['name'])),
                    ]);
                }



            }
        }







//        $permissions = [
//            'create-user',
//            'edit-user',
//            'delete-user',
//            'create-post',
//            'edit-post',
//            'delete-post',
//            'create-category',
//            'edit-category',
//            'block-category',
//        ];
//        foreach ($permissions as $per) {
//            Permission::firstOrCreate(['name' => $per]);
//        }
//        $adminRole = Role::where('name', 'admin')->first();
//        if ($adminRole) {
//            $allPermissions = Permission::all();
//            $adminRole->permissions()->sync($allPermissions->pluck('id'));
//        }

    }
}
