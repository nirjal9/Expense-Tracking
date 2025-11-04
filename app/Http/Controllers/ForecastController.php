<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\MLForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class ForecastController extends Controller
{
    public function createForecast(Request $request)
    {
        try {
            $date = $request->date ? Carbon::parse($request->date) : Carbon::now();
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'Invalid date format. Please use YYYY-MM format.']);
        }

        $user = Auth::user();
        $income = $user->totalIncome;

        $categories = $user->categories()
            ->with(['expenses' => function($query) use ($date) {
                $query->whereBetween('date', [
                    $date->copy()->startOfYear()->toDateString(),
                    $date->copy()->endOfMonth()->toDateString()
                ])->orderBy('date', 'desc');
            }])
            ->withTrashed()
            ->get();

        $currentMonthStart = $date->copy()->startOfMonth()->toDateString();
        $currentMonthEnd = $date->copy()->endOfMonth()->toDateString();

        $mlService = new MLForecastService();

        $forecasts = $categories->map(function ($category) use ($income, $currentMonthStart, $currentMonthEnd, $user, $date, $mlService) {
            $budgetPercentage = $category->pivot->budget_percentage ?? 0;
            $budgetedAmount = ($budgetPercentage / 100) * $income;

            $actualExpense = Expense::where('user_id', $user->id)
                ->where('category_id', $category->id)
                ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
                ->sum('amount');

            //  Get all individual historical expenses for this user/category before the forecast month
            $individualExpenses = $category->expenses
                ->where('user_id', $user->id)
                ->where('date', '<', $date->copy()->startOfMonth()->toDateString())
                ->pluck('amount');

            //  Remove outliers from individual expenses using IQR
            $expensesArray = $individualExpenses->filter(function($v) { return $v > 0; })->values()->toArray();
            if (count($expensesArray) >= 2) {
                sort($expensesArray);
                $q1 = $expensesArray[(int) (0.25 * count($expensesArray))];
                $q3 = $expensesArray[(int) (0.75 * count($expensesArray))];
                $iqr = $q3 - $q1;
                $lower = $q1 - 1.5 * $iqr;
                $upper = $q3 + 1.5 * $iqr;
                $filteredExpenses = array_filter($expensesArray, function($v) use ($lower, $upper) {
                    return $v >= $lower && $v <= $upper;
                });
                $cleanedExpenses = array_values($filteredExpenses);
            } else {
                $cleanedExpenses = $expensesArray;
            }

            //  Group cleaned expenses by month and sum for monthly totals
            $historicalExpenses = $category->expenses
                ->where('user_id', $user->id)
                ->where('date', '<', $date->copy()->startOfMonth()->toDateString())
                ->filter(function($expense) use ($cleanedExpenses) {
                    // Only keep expenses whose amount is in cleanedExpenses (preserves count)
                    // This is safe because outliers are rare; if there are duplicate amounts, all are kept
                    return in_array($expense->amount, $cleanedExpenses);
                })
                ->groupBy(function ($expense) {
                    return Carbon::parse($expense->date)->format('Y-m');
                })
                ->map(function ($monthExpenses) {
                    return $monthExpenses->sum('amount');
                })
                ->sortBy(function ($amount, $month) {
                    return $month;
                })
                ->values();

            $monthlyTotals = $historicalExpenses->filter(function($total) {
                return $total > 0;
            })->values();

            $totalsArray = $monthlyTotals->toArray();
            $cleanedTotals = $totalsArray;

            //  Try ML forecasting first (pass target date)
            $mlForecast = $mlService->getForecast($user, $category, $date);
            $estimatedExpense = null;
            $forecastMethod = 'Statistical';

            if ($mlForecast && isset($mlForecast['prediction'])) {
                $estimatedExpense = $mlForecast['prediction'];
                $forecastMethod = 'Machine Learning (' . ($mlForecast['model_type'] ?? 'ML') . ')';
            } else {
                //  Fall back to statistical forecasting
                if (count($cleanedTotals) >= 6) {
                    $regressionEstimate = $this->predictNextExpenseUsingLinearRegression($cleanedTotals);
                    $last3 = array_slice($cleanedTotals, -3);
                    sort($last3);
                    $median = $last3 ? $last3[(int)floor(count($last3)/2)] : $budgetedAmount;
                    if ($regressionEstimate < 0 || $regressionEstimate > $budgetedAmount * 2) {
                        $estimatedExpense = $median;
                    } else {
                        $estimatedExpense = $regressionEstimate;
                    }
                } elseif (count($cleanedTotals) >= 1) {
                    // Exponential smoothing for <6 months
                    $alpha = 0.7;
                    $smoothed = $cleanedTotals[0];
                    for ($i = 1; $i < count($cleanedTotals); $i++) {
                        $smoothed = $alpha * $cleanedTotals[$i] + (1 - $alpha) * $smoothed;
                    }
                    $estimatedExpense = $smoothed;
                } else {
                    $estimatedExpense = $budgetedAmount;
                }
            }

            $expensePercentage = $income > 0 ? ($actualExpense / $income) * 100 : 0;

            // Convert R² score to confidence percentage (handle negative R²)
            $mlConfidence = null;
            if (isset($mlForecast['accuracy'])) {
                $r2Score = $mlForecast['accuracy'];
                // Convert R² to confidence: negative R² means poor model, cap at 0%
                // Good R² (0.7+) = high confidence, poor R² (0.3-) = low confidence
                if ($r2Score < 0) {
                    $mlConfidence = 0.05; // 5% minimum confidence for any ML prediction
                } elseif ($r2Score > 0.9) {
                    $mlConfidence = 0.95; // 95% max confidence
                } else {
                    // Scale R² (0-0.9) to confidence (20%-95%)
                    $mlConfidence = 0.20 + ($r2Score * 0.75);
                }
            }

            return [
                'category' => $category->name,
                'budget_percentage' => $budgetPercentage,
                'estimated_expense' => round($estimatedExpense, 2),
                'actual_expense' => round($actualExpense, 2),
                'expense_percentage' => round($expensePercentage, 2),
                'forecast_method' => $forecastMethod,
                'ml_confidence' => $mlConfidence,
                'raw_r2_score' => $mlForecast['accuracy'] ?? null, // Keep original for debugging
            ];
        })->all();

        $response = response()->view('forecast.index', compact('forecasts', 'date'));
        
        // Disable browser caching completely
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        return $response;
    }

    /**
     * Performs simple linear regression on a dataset.
     * @param array $expenses An array of expense amounts (y values) indexed by month order (1, 2, 3, ...)
     * @return float Predicted expense for the next month
     */
    private function predictNextExpenseUsingLinearRegression(array $expenses): float
    {
        $n = count($expenses);
        if ($n < 2) return 0;

        $x = range(1, $n);
        $y = array_values($expenses);

        $sumX = array_sum($x);
        $sumY = array_sum($y);

        $sumXY = 0;
        $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] ** 2;
        }

        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = $n * $sumX2 - ($sumX ** 2);
        if ($denominator == 0) return 0;

        $m = $numerator / $denominator;
        $c = ($sumY - $m * $sumX) / $n;

        return $m * ($n + 1) + $c;
    }

    //Weighted Average Method
//     public function createForecast(Request $request)
//     {
// //        $date = $request->date ? Carbon::parse($request->date) : Carbon::now();
//         try {
//             $date = $request->date ? Carbon::parse($request->date) : Carbon::now();
//         } catch (\Exception $e) {
//             return back()->withErrors(['date' => 'Invalid date format. Please use YYYY-MM format.']);
//         }
//         $user = Auth::user();
//         $income = $user->totalIncome;

//         $categories = $user->categories()
//             ->with(['expenses' => function($query) use ($date) {
//                 $query->whereBetween('date', [
//                     $date->copy()->startOfYear()->toDateString(),
//                     $date->copy()->endOfMonth()->toDateString()
//                 ])->orderBy('date', 'desc');
//             }])
//             ->withTrashed()
//             ->get();

//         $currentMonthStart = $date->copy()->startOfMonth()->toDateString();
//         $currentMonthEnd = $date->copy()->endOfMonth()->toDateString();
//         $previousMonthEnd = $date->copy()->subMonth()->endOfMonth()->toDateString();

//         $forecasts = $categories->map(function ($category) use ($income, $currentMonthStart, $currentMonthEnd, $previousMonthEnd, $user, $date) {
//             $budgetPercentage = $category->pivot->budget_percentage ?? 0;
//             $budgetedAmount = ($budgetPercentage / 100) * $income;

//             $actualExpense = Expense::where('user_id', $user->id)
//                 ->where('category_id', $category->id)
//                 ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
//                 ->sum('amount');

//             $historicalExpenses = $category->expenses
//                 ->where('date', '<', $date->copy()->startOfMonth()->toDateString())
//                 ->groupBy(function ($expense) {
//                     return Carbon::parse($expense->date)->format('Y-m');
//                 })
//                 ->map(function ($monthExpenses) {
//                     return $monthExpenses->sum('amount');
//                 })
//                 ->sortByDesc(function ($amount, $month) {
//                     return $month;
//                 })
//                 ->take(3)
//                 ->values();

//             if ($historicalExpenses->count() >= 3) {
//                 // Weights: 0.5 for most recent, 0.3 for second, 0.2 for third
//                 $weights = [0.5, 0.3, 0.2];
//                 $weightedSum = 0;
//                 foreach ($historicalExpenses as $index => $amount) {
//                     $weightedSum += $amount * $weights[$index];
//                 }
//                 $estimatedExpense = $weightedSum;

//                 $monthlyAverages = $category->expenses
//                     ->where('date', '<', $date->copy()->startOfMonth()->toDateString())
//                     ->groupBy(function ($expense) {
//                         return Carbon::parse($expense->date)->format('m');
//                     })
//                     ->map(function ($monthExpenses) {
//                         return $monthExpenses->avg('amount');
//                     });

//                 if ($monthlyAverages->count() > 0) {
//                     $currentMonth = $date->format('m');
//                     $monthlyAverage = $monthlyAverages->get($currentMonth, $monthlyAverages->avg());
//                     $overallAverage = $monthlyAverages->avg();

//                     if ($overallAverage > 0) {
//                         $seasonalFactor = $monthlyAverage / $overallAverage;
//                         $estimatedExpense *= $seasonalFactor;
//                     }
//                 }
//             } else {
//                 $estimatedExpense = $budgetedAmount;
//             }

//             $expensePercentage = $income > 0 ? ($actualExpense / $income) * 100 : 0;

//             return [
//                 'category' => $category->name,
//                 'budget_percentage' => $budgetPercentage,
//                 'estimated_expense' => round($estimatedExpense, 2),
//                 'actual_expense' => round($actualExpense, 2),
//                 'expense_percentage' => round($expensePercentage, 2)
//             ];
//         })->all();

//         return view('forecast.index', compact('forecasts', 'date'));
//     }



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

//    public function createForecast(Request $request)
//    {
//        $date =$request->date ? Carbon::parse($request->date) : Carbon::now();
//        $user=Auth::user();
//        $income =$user->totalIncome;
//        $categories= $user->categories()
//            ->with(['expenses' => function($query) use($date){
//                $query->whereBetween('date',[$date->copy()->startOfYear()->toDateString(),$date->copy()->endOfMonth()->toDateString()]);
//            }])
//            ->withTrashed()
//            ->get();
//
//        $currentMonthStart =$date->copy()->startOfMonth()->toDateString();
//        $currentMonthEnd =$date->copy()->endOfMonth()->toDateString();
//        $previousMonthEnd =$date->copy()->endOfMonth()->toDateString();
//
//        //sbbai categorues ekkai choti using collection methods
//
//        $forecasts = $categories->map(function ($category) use ($income,$currentMonthStart,$currentMonthEnd, $previousMonthEnd,$user){
//            $budgetPercentage = $category->pivot->budget_percentage ?? 0;
//            $budgetedAmount = ($budgetPercentage/100)*$income;
////            $currentMonthExpenses = $category->expenses->whereBetween('date',[$currentMonthStart,$currentMonthEnd]);
//            $actualExpense = Expense::where('user_id', $user->id)
//                ->where('category_id', $category->id)
//                ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
//                ->sum('amount');
////            $actualExpense= $currentMonthExpenses->sum('amount');
//            $previousMonthExpenses = $category->expenses->where('date','<=',$previousMonthEnd);
//            if ($previousMonthExpenses->isNotEmpty()) {
//                $monthlyExpenses = $previousMonthExpenses->groupBy(function ($expense) {
//                    return Carbon::parse($expense->date)->format('Y-m');
//                });
//                $totalSpent = $previousMonthExpenses->sum('amount');
//                $monthsWithExpenses = $monthlyExpenses->count();
//                $estimatedExpense = $totalSpent / $monthsWithExpenses;
//            } else {
//                $estimatedExpense = $budgetedAmount;
//            }
//            $expensePercentage = $income > 0 ? ($actualExpense / $income) * 100 : 0;
//            return [
//                'category' => $category->name,
//                'budget_percentage' => $budgetPercentage,
//                'estimated_expense' => round($estimatedExpense, 2),
//                'actual_expense' => round($actualExpense, 2),
//                'expense_percentage' => round($expensePercentage, 2),
//            ];
//        })->all();
//        return view('forecast.index',compact('forecasts','date'));
//    }

}
