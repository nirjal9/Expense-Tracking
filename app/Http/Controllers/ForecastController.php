<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class ForecastController extends Controller
{
    public function createForecast(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::now();
        $user = Auth::user();
        $income = $user->income;
        $categories = $user->categories()->with(['expenses.category'=>function($query){
            $query->withTrashed();
    }])->withTrashed()->get();
        $forecasts = [];
        foreach ($categories as $category) {
            $actualExpense = Expense::where('user_id', $user->id)
                ->where('category_id', $category->id)->whereBetween('date', [$date->startOfMonth()->toDateString(), $date->endOfMonth()->toDateString()])->sum('amount');
            $pastExpenses = Expense::where('user_id', $user->id)->where('category_id', $category->id)->where('date', '<=', $date->endOfMonth()->toDateString())->sum('amount');
            $totalSpent = $pastExpenses;
            $monthsUsed = max(1, $date->month);
            $averageMonthlySpending = $totalSpent / $monthsUsed;
            $budgetPercentage = $category->pivot->budget_percentage ?? 0;
            $estimatedExpense = $averageMonthlySpending;
            $expensePercentage = $income > 0 ? ($actualExpense / $income) * 100 : 0;
            $forecasts[] = [
                'category' => $category->name,
                'budget_percentage' => $budgetPercentage,
                'estimated_expense' => round($estimatedExpense, 2),
                'actual_expense' => round($actualExpense, 2),
                'expense_percentage' => round($expensePercentage, 2),
            ];
        }
        return view('forecast.index', compact('forecasts', 'date'));
    }





//    public function createForecast(Request $request)
//    {
//        $date = $request->date ? Carbon::parse($request->date) : Carbon::now();
//        $user = Auth::user();
//        $income = $user->income;
//        $categories = $user->categories()->with('expenses')->get();
//        $forecasts = [];
//        foreach ($categories as $category) {
//            $actualExpense = Expense::where('user_id', $user->id)
//                ->where('category_id', $category->id)->whereBetween('date', [$date->startOfMonth()->toDateString(), $date->endOfMonth()->toDateString()])->sum('amount');
//
////            $pastExpenses = Expense::where('user_id', $user->id)->where('category_id', $category->id)->where('date', '<=', $date->endOfMonth()->toDateString())->sum('amount');
//            $budgetPercentage = $category->pivot->budget_percentage ?? 0;
//            $totalSpent = 0;
//            $monthsUsed = max(1, $date->month);
//            for ($m = 1; $m <= $date->month; $m++) {
//                $monthStart = Carbon::createFromDate($date->year, $m, 1)->startOfMonth();
//                $monthEnd = $monthStart->endOfMonth();
//
//                $monthlyExpense = Expense::where('user_id', $user->id)
//                    ->where('category_id', $category->id)
//                    ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
//                    ->sum('amount');
//
//                if ($monthlyExpense == 0 && $budgetPercentage > 0 && $income > 0) {
//                    $monthlyExpense = ($budgetPercentage / 100) * $income;
//                }
//
//                $totalSpent += $monthlyExpense;
//            }
//
//            $averageMonthlySpending = $totalSpent / $monthsUsed;
////            $averageMonthlySpending =$totalSpent>0? ($totalSpent / $monthsUsed):0;
////            if ($totalSpent == 0 && $budgetPercentage > 0 && $income > 0) {
////                $averageMonthlySpending = ($budgetPercentage / 100) * $income;
////            }
//            $estimatedExpense = $averageMonthlySpending;
//            $expensePercentage = $income > 0 ? ($actualExpense / $income) * 100 : 0;
//
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





//    public function createForecast(Request $request)
//    {
//        $date =$request->date?Carbon::parse($request->date) :Carbon::now();
//        $user =Auth::User();
//        $income =$user->income;
//
////        $categories=$user->categories()->with(['expenses'=>function($query) use($date,$user){
////            $query->where('user_id',$user->id)->where('date','>=',$date->copy()->startOfYear())->where('date','<=',$date->endOfMonth());
////        }])->get();
//
//        $categories = $user->categories()->with([
//            'expenses' => function ($query) use ($date, $user) {
//                $query->where('user_id', $user->id)
//                    ->whereBetween('date', [$date->startOfMonth()->toDateString(), $date->endOfMonth()->toDateString()]);
//            }
//        ])->get();
//
////        $categories = $user->categories()->with(['expenses' => function ($query) use ($date, $user) {
////            $query->where('user_id', $user->id)
////                ->whereRaw('MONTH(date) = ?', [$date->month])
////                ->whereRaw('YEAR(date) = ?', [$date->year]);
////        }])->get();
//
//        $forecasts=[];
//        foreach ($categories as $category){
////            $pastExpenses=$category->expenses->where('date','<=',$date->endOfMonth())->pluck('amount');
//            $pastExpenses=$category->expenses->where('date','<=',$date->endOfMonth());
////            $monthsUsed=max(1,$date->month);
////            $totalSpent=$pastExpenses->sum();
//            $totalSpent=$pastExpenses->sum('amount');
//            $distinctMonths = $pastExpenses->groupBy(function ($expense) {
//                return Carbon::parse($expense->date)->format('Y-m');
//            })->count();
//            $monthsUsed = max(1, $distinctMonths);
////            $monthlyAverage=$totalSpent/$monthsUsed;
//            $monthlyAverage=$totalSpent/$monthsUsed;
////            dd($category->expenses->whereBetween('date', [$date->startOfMonth()->toDateString(), $date->endOfMonth()->toDateString()]));
//            $actualExpense = $category->expenses->whereBetween('date', [$date->startOfMonth(), $date->endOfMonth()])->sum('amount');
//            $budgetPercentage = $category->pivot->budget_percentage ?? 0;
//            $estimatedExpense = $monthlyAverage > 0 ? $monthlyAverage : ($budgetPercentage / 100) * $income;
//            $expensePercentage = $income > 0 ? ($actualExpense / $income) * 100 : 0;
//            $forecasts[] = [
//                'category' => $category->name,
//                'budget_percentage' => $budgetPercentage,
//                'estimated_expense' => round($estimatedExpense, 2),
//                'actual_expense' => round($actualExpense, 2),
//                'expense_percentage' => round($expensePercentage, 2),
//            ];
//        }
//
//        return view('forecast.index', compact('forecasts', 'date'));
//    }

//    public function createForecast(Request $request)
//    {
//        $date =$request->date?Carbon::parse($request->date) :Carbon::now();
//        $user =Auth::User();
//        $income =$user->income;
//
//        $categories=$user->categories()
//            ->with(['expenses'=>function($query) use($date,$user){
//                $query->where('user_id',$user->id)->whereMonth('date',$date->month)->whereYear('date',$date->year);
//
//            }])->get();
//        $forecasts=[];
//        foreach($categories as $category) {
//            $actualExpense = $category->expenses->sum('amount');
//
//            $budgetPercentage = $category->pivot->budget_percentage ?? 0;
//            $estimatedExpense = ($budgetPercentage / 100) * $income;
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
}
