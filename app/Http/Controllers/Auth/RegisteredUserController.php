<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    public function store(Request $request): RedirectResponse
    {

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_completed'=>false,
        ]);
        $user->roles()->attach(Role::where('name', 'user')->first());

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('register.income');
    }
    public function showCategories(Request $request)
    {
        $categories = Category::whereBetween('id',[1,6])->get();
        return view('auth.categories', compact('categories'));
    }

    public function storeCategories(Request $request)
    {
//        dd($request->all());
//        dd($request->categories);
        $request->validate([
            'categories' => ['required', 'array'],
            'custom_category' => ['nullable', 'string', 'max:255'],
        ]);
        $user=Auth::user();
        if ($request->has('categories')) {
            $user->categories()->syncWithoutDetaching($request->categories);
        }
//        if($request->filled('custom_category')) {
//            $customCategory=Category::create([
//                'name'=> $request->custom_category,
//                'budget_amount'=>0,
//            ]);
//            $user->categories()->attach($customCategory->id);
//        }
        if($request->filled('custom_category')) {
            $existingCategory = Category::withTrashed()
                ->where('name', $request->custom_category)
                ->first();

            if ($existingCategory) {
                if ($existingCategory->trashed()) {
                    $existingCategory->restore();
                }
                if (!$existingCategory->users->contains($user->id)) {
                    $user->categories()->attach($existingCategory->id);
                }
            } else {
                $customCategory = Category::create([
                    'name' => $request->custom_category,
                    'user_id' => $user->id,
                ]);
                $user->categories()->attach($customCategory->id);
            }
        }

        return redirect()->route('register.budget');
    }
    public function showIncome()
    {
        return view('auth.income');
    }
    public function storeIncome(Request $request)
    {
        $request->validate([
            'income' => ['required', 'numeric', 'min:0'],
            'income_type' => ['required', 'in:monthly,yearly'],
        ]);
        $income = $request->income;
        if ($request->income_type === 'yearly') {
            $income = round($income / 12, 2);
        }
        $user=Auth::user();
        $user->update(['income'=>$income]);
        return redirect()->route('register.categories');

    }
    public function showBudget(){
        $user=Auth::user();
        $categories=$user->categories;
        return view('auth.budget',compact('categories'));
    }
    public function storeBudget(Request $request)
    {
//        dd('stop');
//        dd($request->percentages);
        $request->validate([
            'percentages' => ['required', 'array'],
            'percentages.*' => ['regex:/^\d+(\.\d{1,2})?$/', 'min:0'],
        ]);
        $totalPercentage=array_sum($request->percentages);
        if($totalPercentage>100){
            return back()->withErrors(['total' => 'total must be less than 100']);
        }
        $user = Auth::user();
        foreach ($request->percentages as $categoryId => $percentage) {
            $user->categories()->syncWithoutDetaching([$categoryId=>['budget_percentage'=>$percentage]]);
        }

        $user->update(['is_completed'=> true]);

        return redirect()->route('dashboard');

    }
}
