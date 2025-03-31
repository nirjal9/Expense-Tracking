<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index()
    {
        $user = Auth::user();
//        $categories = $user->categories()->with('expenses')->get();
        $categories = $user->categories()->with(['expenses.category' => function ($query) {
            $query->withTrashed();
          }])->withTrashed()->get();

        $categoryWarnings=[];
        $categoryBudgets = $categories->map(function ($category) use ($user,&$categoryWarnings) {
            $budgetPercentage= $category->pivot->budget_percentage;
            $allocatedBudget = ($user->income * $budgetPercentage)/100;
            $spentAmount= $category->expenses()->where('user_id', $user->id)->sum('amount');
            $remainingBudget= $allocatedBudget - $spentAmount;
            $budgetUsedPercentage =$allocatedBudget >0 ?($spentAmount/$allocatedBudget)*100:0;
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
        $expenses = Expense::where('user_id',Auth::id())->get();
        return view('expenses.index',compact('expenses','categoryBudgets','categoryWarnings'));
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
            'amount'=>'required',
            'description'=>'required',
            'date'=>'required',
        ]);
        $user=Auth::user();
        $categoryBudgetPercentage = $user->categories()
            ->where('category_id', $request->category_id)
            ->first()?->pivot->budget_percentage;

        if ($categoryBudgetPercentage === null) {
            return back()->withErrors(['category_id' => 'Invalid category selection.']);
        }
        $categoryBudget=($user->income * $categoryBudgetPercentage)/100;
        $totalExpenses=Expense::where('user_id',$user->id)->where('category_id',$request->category_id)->sum('amount');
        $newTotal =$totalExpenses + $request->amount;
        $remainingBudget = $categoryBudget - $totalExpenses;

        if($newTotal > $categoryBudget){
            $exceededAmount = abs($remainingBudget -$request->amount);
//            $warningMessage ="Warning: You have exceeded the budget for this category by Rs. $exceededAmount.";
        }

//        if ($totalExpenses + $request->amount > $categoryBudget) {
//            $remainingBudget =$categoryBudget-$totalExpenses;
//            return back()->withErrors(['amount' => "Insufficient balance.Remaining budget: $remainingBudget"])->withInput();
//        }
        Expense::create([
            'user_id'=>$user->id,
            'category_id'=> $request->category_id,
            'amount'=> $request->amount,
            'description'=>$request->description,
            'date'=>$request->date,
        ]);
        return redirect()->route('expenses.index')->with('success','Expense added successfully.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->user_id !== Auth::id()) {
            return redirect()->route('expenses.index')->withErrors(['error' =>'Unauthorized action.']);
        }
        $expense->delete();
        return redirect()->route('expenses.index')->with('success','Expense deleted successfully.');
    }

    public function forecast()
    {
        $user = Auth::user();
        $categories =$user->categories()->with('expenses')->get();
        $forecasts=$categories->map(function ($category) use ($user) {
            $totalSpent= $category->expenses()->where('user_id', $user->id)->sum('amount');
            $firstExpense =$category->expenses()->where('user_id', $user->id)->orderBy('date', 'asc')->first();
            if ($firstExpense)
             {
//                $daysSinceFirstExpense =now()->diffInDays($firstExpense->date)?:1;
                $daysSinceFirstExpense = max(now()->diffInDays($firstExpense->date), 1);
                $dailyAverage=$totalSpent/$daysSinceFirstExpense;
                $forecastedExpense= round($dailyAverage* 30, 2);
            }
            else {
                $budgetPercentage =$category->pivot->budget_percentage ?? 0;
                $forecastedExpense= round(($user->income * $budgetPercentage) / 100, 2);
            }
            return [
                'category' => $category->name,
                'forecasted_expense' => $forecastedExpense,
            ];
        });
        return view('expenses.forecast', compact('forecasts'));
    }
}
