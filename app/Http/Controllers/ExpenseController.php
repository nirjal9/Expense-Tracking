<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
////        $categories = $user->categories()->with('expenses')->get();
//        $categories = $user->categories()->with(['expenses.category' => function ($query) {
//            $query->withTrashed();
//          }])->withTrashed()->get();

        $startDate = $request->input('start_date')?Carbon::parse($request->input('start_date'))->startOfDay():now()->startOfMonth();
        $endDate = $request->input('end_date')?Carbon::parse($request->input('end_date'))->endOfday():now()->endOfMonth();
        if ($startDate->gt($endDate)) {
            return back()->withErrors(['date' => 'Start date cannot be after end date.']);
        }
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();

        $categories = $user->categories()->with(['expenses' => function ($query) use ($currentMonthStart, $currentMonthEnd, $user) {
            $query->where('user_id' , $user->id)
                ->whereBetween('date', [$currentMonthStart, $currentMonthEnd]);
        }])->withTrashed()->get();

        $categoryWarnings=[];
        $categoryBudgets = $categories->map(function ($category) use ($user,&$categoryWarnings,$currentMonthStart,$currentMonthEnd) {
            $budgetPercentage= $category->pivot->budget_percentage;
            $allocatedBudget = ($user->income * $budgetPercentage)/100;
//            $spentAmount= $category->expenses()->where('user_id', $user->id)->sum('amount');
            $spentAmount= $category->expenses()->where('user_id', $user->id)->whereBetween('date',[$currentMonthStart, $currentMonthEnd])->sum('amount');
            $remainingBudget= $allocatedBudget - $spentAmount;
            $budgetUsedPercentage =$allocatedBudget >0?($spentAmount/$allocatedBudget)*100:0;
            if ($remainingBudget < 0) {
                $categoryWarnings[$category->name]="You have exceeded the budget for {$category->name} by Rs. ".abs($remainingBudget);
            }
            return [
                'category' => $category->name,
                'allocated' => number_format($allocatedBudget, 2),
                'spent' => number_format($spentAmount, 2),
                'remaining' => number_format($remainingBudget, 2),
                'budget_used_percentage'=>round($budgetUsedPercentage,2),
            ];
        });
//        $expenses = Expense::where('user_id',Auth::id())->get();
//        return view('expenses.index',compact('expenses','categoryBudgets','categoryWarnings'));
        $expenses = $user->expenses()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->paginate(10);
        $totalExpenses = $expenses->sum('amount');

        $months = collect(range(1, 12))->mapWithKeys(function ($month) {
            return [$month => date('F', mktime(0, 0, 0, $month, 1))];
        });

        $years = collect(range(now()->year - 2, now()->year))->reverse();
        $currentDate = now()->setYear($startDate->year)->setMonth($startDate->month);
        $previousMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        return view('expenses.index', compact('expenses', 'categoryBudgets','categoryWarnings','months','years','startDate','endDate','currentDate','previousMonth','nextMonth','totalExpenses'));
    }
    public function create()
    {
        $user=Auth::user();
        $categories=$user->categories;
        return view('expenses.create',compact('categories'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'category_id'=>'required',
            'amount'=>'required|numeric|min:0|max:999999999.99',
            'description'=>'nullable|string|',
            'date'=>['required','date','before:tomorrow']
        ]);
        $user=Auth::user();
        $categoryBudgetPercentage =$user->categories()
            ->where('category_id',$request->category_id)
            ->first()?->pivot->budget_percentage;

        if ($categoryBudgetPercentage === null) {
            return back()->withInput()->withErrors(['category_id' => 'Invalid category selection.']);
        }
        $categoryBudget=($user->income * $categoryBudgetPercentage)/100;
        $totalExpenses=Expense::where('user_id',$user->id)->where('category_id',$request->category_id)->sum('amount');
//        $totalExpenses=Expense::where('category_id',$request->category_id)->sum('amount');
        $user->expenses()->create([
            'category_id'=> $request->category_id,
            'amount'=> $request->amount,
            'description'=>$request->description,
            'date'=>$request->date,
        ]);

        return redirect()->route('expenses.index')->with('success','Expense added successfully.');
    }
    public function edit(Expense $expense)
    {
        if ($expense->user_id !== Auth::id()) {
            abort(403);
        }

        $user = Auth::user();
        $categories = $user->categories;
        $currentCategory = $expense->category;

        if (!$categories->contains($currentCategory)) {
            $categories->push($currentCategory);
        }
        $formattedDate = Carbon::parse($expense->date)->format('Y-m-d');
        return view('expenses.edit', compact('expense', 'categories', 'formattedDate'));
    }
    public function update(Request $request, Expense $expense)
    {
        if ($expense->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'category_id' => 'required',
            'amount' => 'required|numeric|min:0|max:999999999.99',
            'description' => 'nullable|string',
            'date' => ['required','date','before:tomorrow']
        ]);

        $user = Auth::user();
        $categoryBudgetPercentage = $user->categories()
            ->where('category_id', $request->category_id)
            ->first()?->pivot->budget_percentage;
        $isValidCategory = $user->categories->contains('id', $request->category_id) ||
            $expense->category_id == $request->category_id;

        if (!$isValidCategory) {
            return back()->withErrors(['category_id' => 'Invalid category selection.']);
        }

//        if ($categoryBudgetPercentage === null) {
//            return back()->withInput()->withErrors(['category_id' => 'Invalid category selection.']);
//        }

        $expense->update([
            'category_id' => $request->category_id,
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => $request->date,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }
    public function destroy(Expense $expense)
    {
        $expense = Expense::findOrFail($expense->id);
        if ($expense->user_id !== Auth::id()) {
            return redirect()->route('expenses.index')->withErrors(['error' =>'Unauthorized action.']);
        }
        $expense->delete();
        return redirect()->route('expenses.index')->with('success','Expense deleted successfully.');
    }

}
