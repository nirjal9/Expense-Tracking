<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $categories=$user->categories()->withPivot('budget_percentage')->get();
        return view('categories.index',compact('categories'));
    }

    public function create()
    {
        $predefinedCategories = Category::where('user_id', 1)->get();
        return view('categories.create', compact('predefinedCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'predefined_category' => ['nullable', 'exists:categories,id'],
            'name' => [
                'nullable',
                'required_without:predefined_category',
                'string',
                'max:255',
                Rule::unique('categories')->whereNull('deleted_at')
            ],
            'budget_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($request->predefined_category) {
            $category = Category::findOrFail($request->predefined_category);

            if (Auth::user()->categories->contains($category)) {
                Auth::user()->categories()->updateExistingPivot($category->id, [
                    'budget_percentage' => $request->budget_percentage
                ]);
                return redirect()->route('categories.index')
                    ->with('success', 'Category budget updated successfully.');
            }

            Auth::user()->categories()->attach($category->id, [
                'budget_percentage' => $request->budget_percentage
            ]);
            return redirect()->route('categories.index')
                ->with('success', 'Predefined category added successfully.');
        }

        $existingCategory = Category::withTrashed()
            ->where('name', $request->name)
            ->first();

        if ($existingCategory) {
            if ($existingCategory->trashed()) {
                $existingCategory->restore();

                if ($existingCategory->user_id !== Auth::id()) {
                    $existingCategory->user_id = Auth::id();
                    $existingCategory->save();
                }

                if (!$existingCategory->users->contains(Auth::id())) {
                    Auth::user()->categories()->attach($existingCategory->id, [
                        'budget_percentage' => $request->budget_percentage
                    ]);
                } else {
                    Auth::user()->categories()->updateExistingPivot($existingCategory->id, [
                        'budget_percentage' => $request->budget_percentage
                    ]);
                }

                return redirect()->route('categories.index')
                    ->with('success', 'Category restored successfully.');
            }
        }

        $category = Category::create([
            'name' => $request->name,
            'user_id' => Auth::id(),
        ]);

        Auth::user()->categories()->attach($category->id, [
            'budget_percentage' => $request->budget_percentage
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

//    public function store(Request $request)
//    {
//        $request->validate([
//            'name'=>['required', 'string', 'max:255',Rule::unique('categories')->whereNull('deleted_at')],
//            'budget_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
//        ]);
//    $existingCategory=Category::withTrashed()->where('name',$request->name)->first();
//        if ($existingCategory){
//            $existingCategory->restore();
//            if ($existingCategory->user_id !== Auth::id()) {
//                $existingCategory->user_id = Auth::id();
//                $existingCategory->save();
//            }
//            if (!$existingCategory->users->contains(Auth::id())) {
//                Auth::user()->categories()->attach($existingCategory->id, ['budget_percentage' => $request->budget_percentage]);
//            }
//            return redirect()->route('categories.index')->with('success', 'Category restored successfully.');
//        }
//        $category=Category::firstOrcreate([
//            'name'=>$request->name,
//            'user_id'=>Auth::id(),
//        ]);
//
//        Auth::user()->categories()->attach($category->id,['budget_percentage' => $request->budget_percentage]);
//        return redirect()->route('categories.index')->with('success','Category created successfully');
//    }
    public function edit(Category $category)
    {
//        $category = Category::findOrFail($category->id);
        $user = Auth::user();

        if (!$user->categories->contains($category)) {
            abort(401);
        }
        $categoryWithPivot = $user->categories()->where('categories.id', $category->id)->first();
        return view('categories.edit', compact('categoryWithPivot'));
    }

    public function update(Request $request, Category $category)
    {
        $category = Category::findOrFail($category->id);
        if(!Auth::user()->categories->contains($category))
        {
            abort(403,'Unauthorized action.');
        }
        $request->validate([
            'name'=>['required', 'string', 'max:255','unique:categories,name,'.$category->id],
            'budget_percentage' => ['required', 'numeric', 'min:0', 'max:100']
        ]);
        $category->update(['name'=>$request->name]);
        Auth::user()->categories()->updateExistingPivot($category->id, ['budget_percentage' => $request->budget_percentage]);
        return redirect()->route('categories.index')->with('success','Category updated successfully');
    }

//    public function destroy(Category $category)
//    {
//        if (Auth::user()->hasRole('admin')) {
//            $category->delete();
//            return redirect()->route('admin.dashboard')->with('success', 'Category deleted successfully (soft deleted).');
//        }
//
//        if(!Auth::user()->categories->contains($category))
//        {
//            abort(403,'Unauthorized action.');
//        }
//        Auth::user()->categories()->detach($category->id);
//    if($category->user_id!==1){
//        $category->delete();
//    }
//    return redirect()->route('categories.index')->with('success','Category deleted successfully');
//    }
public function destroy(Category $category)
    {
        $category = Category::findOrFail($category->id);
        if (Auth::user()->hasRole('admin')) {
            $category->delete();
            return redirect()->route('admin.dashboard')->with('success', 'Category deleted successfully (soft deleted).');
        }

        if(Auth::user()->categories->contains($category))
        {
            Auth::user()->categories()->detach($category->id);

            if ($category->user_id !== 1) {
                $otherUsersCount = $category->users()->count();
                if($otherUsersCount > 0)
                {
                    $category->delete();
                }
            }

            return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
        }
        abort(403, 'Unauthorized action.');
    }

    public function forceDelete($id)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $category = Category::withTrashed()->findOrFail($id);

        $category->users()->detach();

        $category->forceDelete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Category permanently deleted.');
    }
}

