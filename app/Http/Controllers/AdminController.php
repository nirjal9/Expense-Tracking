<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
//    public function dashboard()
//    {
//        $categories = Category::with('creator')->withCount('users')->get();
//        return view('admin.dashboard',compact('categories'));
//    }
//user->wherehascategory ->wherehasuser.
    public function dashboard()
    {
        $categories = Category::whereHas('users')
        ->withCount('users')->get();
        return view('admin.dashboard', compact('categories'));
    }

    public function users()
    {
        $users = User::with('roles')->get();
        return view('admin.users',compact('users'));
    }
}
