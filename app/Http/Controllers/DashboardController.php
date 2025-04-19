<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();

        // Get monthly income and expenses
        $monthlyIncome = Income::where('user_id', $user->id)
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        $monthlyExpenses = Expense::where('user_id', $user->id)
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        $netSavings = $monthlyIncome - $monthlyExpenses;

        // Get recent expenses and incomes
        $recentExpenses = Expense::with('category')
            ->where('user_id', $user->id)
            ->latest('date')
            ->take(5)
            ->get();

        $recentIncomes = Income::where('user_id', $user->id)
            ->latest('date')
            ->take(5)
            ->get();

        // Get budget information for each category
        $budgetInfo = [];
        $userCategories = $user->categories()->withPivot('budget_percentage')->get();

        foreach ($userCategories as $category) {
            $monthlyExpenseForCategory = Expense::where('user_id', $user->id)
                ->where('category_id', $category->id)
                ->whereMonth('date', $now->month)
                ->whereYear('date', $now->year)
                ->sum('amount');

            $budgetAmount = ($monthlyIncome * $category->pivot->budget_percentage) / 100;
            $remainingBudget = $budgetAmount - $monthlyExpenseForCategory;
            $percentageSpent = $budgetAmount > 0 ? ($monthlyExpenseForCategory / $budgetAmount) * 100 : 0;

            $budgetInfo[] = [
                'category' => $category,
                'spent' => $monthlyExpenseForCategory,
                'budget' => $budgetAmount,
                'remaining' => $remainingBudget,
                'percentage' => $percentageSpent
            ];
        }

        return view('dashboard', compact(
            'monthlyIncome',
            'monthlyExpenses',
            'netSavings',
            'recentExpenses',
            'recentIncomes',
            'budgetInfo'
        ));
    }
}
