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
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>['required', 'string', 'max:255',Rule::unique('categories')->whereNull('deleted_at')],
            'budget_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
    $existingCategory=Category::withTrashed()->where('name',$request->name)->first();
        if ($existingCategory){
            $existingCategory->restore();
            if ($existingCategory->user_id !== Auth::id()) {
                $existingCategory->user_id = Auth::id();
                $existingCategory->save();
            }
            if (!$existingCategory->users->contains(Auth::id())) {
                Auth::user()->categories()->attach($existingCategory->id, ['budget_percentage' => $request->budget_percentage]);
            }
            return redirect()->route('categories.index')->with('success', 'Category restored successfully.');
        }
        $category=Category::firstOrcreate([
            'name'=>$request->name,
            'user_id'=>Auth::id(),
        ]);

        Auth::user()->categories()->attach($category->id,['budget_percentage' => $request->budget_percentage]);
        return redirect()->route('categories.index')->with('success','Category created successfully');
    }


    public function edit(Category $category)
    {
        $user = Auth::user();

        if (!$user->categories->contains($category)) {
            abort(403, 'Unauthorized action.');
        }
        $categoryWithPivot = $user->categories()->where('categories.id', $category->id)->first();
        return view('categories.edit', compact('categoryWithPivot'));
    }

    public function update(Request $request, Category $category)
    {
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
        if (Auth::user()->hasRole('admin')) {
            $category->delete();
            return redirect()->route('admin.dashboard')->with('success', 'Category deleted successfully (soft deleted).');
        }

        if(Auth::user()->categories->contains($category))
        {
            Auth::user()->categories()->detach($category->id);

            if ($category->user_id !== 1) {
                $category->delete();
            }

            return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
        }
        abort(403, 'Unauthorized action.');
    }

}
