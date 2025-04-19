<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class AdminController extends Controller
{
//    public function dashboard()
//    {
//        $categories = Category::with('creator')->withCount('users')->get();
//        return view('admin.dashboard',compact('categories'));
//    }
//user->wherehascategory ->wherehasuser.
//    public function dashboard()
//    {
//        $categories = Category::whereHas('users')
//        ->withCount('users')->get();
//        return view('admin.dashboard', compact('categories'));
//    }
//
//    public function users()
//    {
//        $users = User::with('roles')->get();
//        return view('admin.users',compact('users'));
//    }

    public function dashboard()
    {
        $totalUsers = User::count();

        $topSpendingCategories = Category::select('categories.id', 'categories.name', DB::raw('COALESCE(SUM(expenses.amount), 0) as total_spent'))
            ->leftJoin('expenses', 'categories.id', '=', 'expenses.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_spent', 'desc')
            ->limit(5)
            ->get();

        $topUserCategories = Category::withCount('users')
            ->orderBy('users_count', 'desc')
            ->limit(5)
            ->get();

        $categories = Category::with('creator')
            ->withCount('users')
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'topSpendingCategories',
            'topUserCategories',
        ));
    }
    public function categories(Request $request)
    {
        $query = Category::with('creator')
            ->withCount('users');


        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where('name', 'like', "%{$searchTerm}%");
        }


//        $categories = Category::with('creator')
//            ->withCount('users')
//            ->get();

        $sortBy = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        if ($sortBy === 'users_count') {
            $query->orderBy('users_count', $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }
        $categories = $query
            ->paginate(10)
            ->withQueryString();

        return view('admin.categories.index', compact('categories','sortBy', 'sortDirection'));
    }

    public function users(Request $request)
    {
        $query = User::with('roles');
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }
        if ($request->has('role') && !empty($request->role)) {
            $roleName = $request->role;
            $query->whereHas('roles', function($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }
        $users = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        return view('admin.users', compact('users'));
    }

    public function createCategory()
    {
        return view('admin.categories.create');
    }
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
        ]);

        // Check if category already exists (including soft-deleted ones)
        $existingCategory = Category::withTrashed()
            ->where('name', $request->name)
            ->first();

        if ($existingCategory) {
            if ($existingCategory->trashed()) {
                $existingCategory->restore();
                $existingCategory->user_id = Auth::id();
                $existingCategory->save();

                return redirect()->route('admin.categories')
                    ->with('success', 'Category restored successfully.');
            }
        }

        $category = Category::create([
            'name' => $request->name,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('admin.categories')
            ->with('success', 'Category created successfully.');
    }

    public function editCategory(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function updateCategory(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $existingCategory = Category::where('name', $request->name)
            ->where('id', '!=', $category->id)
            ->first();

        if ($existingCategory) {
            $category->update(['name' => $request->name]);
            return redirect()->route('admin.categories')
                ->with('success', 'Category updated successfully.');
        }

        $category->update(['name' => $request->name]);

        return redirect()->route('admin.categories')
            ->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(Category $category)
    {
        $category->delete();
        return redirect()->route('admin.categories')
            ->with('success', 'Category deleted successfully.');
    }
}
