<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class ForecastController extends Controller
{

//    public function createForecast(Request $request)
//    {
//        $date =$request->date ? Carbon::parse($request->date) : Carbon::now();
//        $user=Auth::user();
//        $income =$user->income;
//        $categories= $user->categories()
//            ->with(['expenses' => function($query) {
//                $query->orderBy('date');
//            }])
//            ->withTrashed()
//            ->get();
//        $forecasts =[];
////        $actualExpenses = $user->expenses()
////            ->whereBetween('date', [$date->startOfMonth()->toDateString(),$date->endOfMonth()->toDatestring()])
//
//        foreach ($categories as $category) {
////            $actualExpense = Expense::where('user_id', $user->id)
////                ->where('category_id', $category->id)
//            $actualExpense = $category->expenses()
//                ->whereBetween('date', [$date->startOfMonth()->toDateString(), $date->endOfMonth()->toDateString()])
//                ->sum('amount');
//            $budgetPercentage = $category->pivot->budget_percentage ?? 0;
//            $budgetedAmount = ($budgetPercentage / 100) * $income;
//            $yearStart = $date->copy()->startOfYear();
//            $previousMonth = $date->copy()->subMonth();
////            $yearExpenses = Expense::where('user_id', $user->id)
////                ->where('category_id', $category->id)
//            $yearExpenses= $category->expenses()
//                ->whereBetween('date', [
//                    $yearStart->toDateString(),
//                    $previousMonth->endOfMonth()->toDateString()
//                ])
//                ->get();
//            if ($yearExpenses->count() > 0) {
//                $monthlyExpenses = $yearExpenses->groupBy(function ($expense) {
//                    return Carbon::parse($expense->date)->format('Y-m');
//                });
//                $totalSpent = $yearExpenses->sum('amount');
//                $monthsWithExpenses = $monthlyExpenses->count();
//                $estimatedExpense = $totalSpent / $monthsWithExpenses;
//            } else {
//                $estimatedExpense = $budgetedAmount;
//            }
//            $expensePercentage = $income > 0 ? ($actualExpense / $income) * 100 : 0;
//            $forecasts[] = [
//                'category' => $category->name,
//                'budget_percentage' => $budgetPercentage,
//                'estimated_expense' => round($estimatedExpense, 2),
//                'actual_expense' => round($actualExpense, 2),
//                'expense_percentage' => round($expensePercentage, 2),
//            ];
//        }
//        return view('forecast.index', compact('forecasts', 'date'));
//    }

    public function createForecast(Request $request)
    {
        $date =$request->date ? Carbon::parse($request->date) : Carbon::now();
        $user=Auth::user();
        $income =$user->income;
        $categories= $user->categories()
            ->with(['expenses' => function($query) use($date){
                $query->whereBetween('date',[$date->copy()->startOfYear()->toDateString(),$date->copy()->endOfMonth()->toDateString()]);
            }])
            ->withTrashed()
            ->get();

        $currentMonthStart =$date->copy()->startOfMonth()->toDateString();
        $currentMonthEnd =$date->copy()->endOfMonth()->toDateString();
        $previousMonthEnd =$date->copy()->endOfMonth()->toDateString();

        //sbbai categorues ekkai choti using collection methods

        $forecasts = $categories->map(function ($category) use ($income,$currentMonthStart,$currentMonthEnd, $previousMonthEnd){
            $budgetPercentage = $category->pivot->budget_percentage??0;
            $budgetedAmount = ($budgetPercentage/100)*$income;
            $currentMonthExpenses = $category->expenses->whereBetween('date',[$currentMonthStart,$currentMonthEnd]);
            $actualExpense= $currentMonthExpenses->sum('amount');
            $previousMonthExpenses = $category->expenses->where('date','<=',$previousMonthEnd);
            if ($previousMonthExpenses->isNotEmpty()) {
                $monthlyExpenses = $previousMonthExpenses->groupBy(function ($expense) {
                    return Carbon::parse($expense->date)->format('Y-m');
                });
                $totalSpent = $previousMonthExpenses->sum('amount');
                $monthsWithExpenses = $monthlyExpenses->count();
                $estimatedExpense = $totalSpent / $monthsWithExpenses;
            } else {
                $estimatedExpense = $budgetedAmount;
            }
            $expensePercentage = $income > 0 ? ($actualExpense / $income) * 100 : 0;
            return [
                'category' => $category->name,
                'budget_percentage' => $budgetPercentage,
                'estimated_expense' => round($estimatedExpense, 2),
                'actual_expense' => round($actualExpense, 2),
                'expense_percentage' => round($expensePercentage, 2),
            ];
        })->all();
        return view('forecast.index',compact('forecasts','date'));
    }

}
