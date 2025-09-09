<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Category;
use App\Services\MLForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class MLAccuracyController extends Controller
{
    private $mlService;
    
    public function __construct()
    {
        $this->mlService = new MLForecastService();
    }
    
    /**
     * Show ML accuracy dashboard
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        
        // Debug: Log user and categories
        Log::info("ML Accuracy Dashboard - User ID: {$user->id}");
        
        // Get all categories attached to the user (primary source)
        $categories = $user->categories()->withTrashed()->get();
        Log::info("Found {$categories->count()} categories for user (via pivot)");

        // Fallback: if no categories are attached to the user, infer from user's expenses
        if ($categories->count() === 0) {
            $categoryIdsFromExpenses = $user->expenses()
                ->select('category_id')
                ->whereNotNull('category_id')
                ->distinct()
                ->pluck('category_id');

            if ($categoryIdsFromExpenses->count() > 0) {
                $categories = Category::withTrashed()->whereIn('id', $categoryIdsFromExpenses)->get();
                Log::info("Inferred {$categories->count()} categories for user from expenses");
            }
        }
        
        // Check if ML service is available
        $mlServiceAvailable = false;
        try {
            if ($categories->count() > 0) {
                $testPerformance = $this->mlService->getModelPerformance($user, $categories->first());
                $mlServiceAvailable = true;
                Log::info("ML service is available");
            } else {
                Log::warning("No categories found for user");
            }
        } catch (\Exception $e) {
            $mlServiceAvailable = false;
            Log::warning("ML service not available: " . $e->getMessage());
        }
        
        $mlPerformance = [];
        
        foreach ($categories as $category) {
            Log::info("Processing category: {$category->name} (ID: {$category->id})");
            
            if ($mlServiceAvailable) {
                try {
                    // Count user expenses in this category (for diagnostics only; do not skip)
                    $userCategoryExpensesCount = $user->expenses()->where('category_id', $category->id)->count();
                    Log::info("Category {$category->name} - user expense rows: {$userCategoryExpensesCount}");

                    $performance = $this->mlService->getModelPerformance($user, $category);
                    $hasEnoughData = $this->mlService->hasEnoughData($user, $category);
                    
                    Log::info("Category {$category->name} - Performance: " . json_encode($performance) . ", Has enough data: " . ($hasEnoughData ? 'Yes' : 'No'));
                    
                    $mlPerformance[$category->id] = [
                        'category_name' => $category->name,
                        'performance' => $performance,
                        'has_sufficient_data' => $hasEnoughData
                    ];
                } catch (\Exception $e) {
                    Log::error("Failed to get ML performance for category {$category->name}: " . $e->getMessage());
                    // Fallback to default performance if ML service fails
                    $mlPerformance[$category->id] = [
                        'category_name' => $category->name,
                        'performance' => [
                            'mae' => 0,
                            'mape' => 0,
                            'rmse' => 0,
                            'r2_score' => 0
                        ],
                        'has_sufficient_data' => false
                    ];
                }
            } else {
                // ML service not available, show default performance
                $mlPerformance[$category->id] = [
                    'category_name' => $category->name,
                    'performance' => [
                        'mae' => 0,
                        'mape' => 0,
                        'rmse' => 0,
                        'r2_score' => 0
                    ],
                    'has_sufficient_data' => false
                ];
            }
        }
        
        Log::info("Final ML performance data: " . json_encode($mlPerformance));
        
        // Calculate overall ML performance
        $overallPerformance = $this->calculateOverallPerformance($mlPerformance);
        Log::info("Overall performance: " . json_encode($overallPerformance));

        // Optional raw debug output: /ml-accuracy?raw=1
        if ($request->boolean('raw')) {
            return response()->json([
                'mlServiceAvailable' => $mlServiceAvailable,
                'categories' => array_values($categories->map(function($c){ return ['id'=>$c->id,'name'=>$c->name]; })->toArray()),
                'mlPerformance' => $mlPerformance,
                'overallPerformance' => $overallPerformance,
            ]);
        }

        // Minimal fallback view: /ml-accuracy?simple=1
        if ($request->boolean('simple')) {
            return view('ml-accuracy.simple', compact('mlPerformance', 'overallPerformance', 'mlServiceAvailable'));
        }

        // Ultra-bare fallback: /ml-accuracy?bare=1 (no layout/components)
        if ($request->boolean('bare')) {
            return view('ml-accuracy.bare', [
                'mlServiceAvailable' => $mlServiceAvailable,
                'mlPerformance' => $mlPerformance,
                'overallPerformance' => $overallPerformance,
            ]);
        }

        // Default to the simple view to avoid any layout-related blank screen until full dashboard is needed
        if (!$request->boolean('full')) {
            return view('ml-accuracy.simple', compact('mlPerformance', 'overallPerformance', 'mlServiceAvailable'));
        }

        return view('ml-accuracy.dashboard', compact('mlPerformance', 'overallPerformance', 'mlServiceAvailable'));
    }
    
    /**
     * Compare ML vs Statistical forecasting accuracy
     */
    public function compareMethods(Request $request)
    {
        $user = Auth::user();
        $months = $request->get('months', 6);
        $comparisonData = [];
        
        for ($i = 1; $i <= $months; $i++) {
            $testDate = Carbon::now()->subMonths($i);
            $forecastDate = $testDate->copy()->subMonth();
            
            // Get actual expenses for test month
            $actualExpenses = $this->getActualExpensesForMonth($user, $testDate);
            
            // Get ML forecasts
            $mlForecasts = $this->getMLForecastsForDate($user, $forecastDate);
            
            // Get statistical forecasts (using existing method)
            $statisticalForecasts = $this->getStatisticalForecastsForDate($user, $forecastDate);
            
            // Calculate accuracy metrics
            $mlAccuracy = $this->calculateAccuracyMetrics($mlForecasts, $actualExpenses);
            $statisticalAccuracy = $this->calculateAccuracyMetrics($statisticalForecasts, $actualExpenses);
            
            $comparisonData[] = [
                'month' => $testDate->format('Y-m'),
                'ml_accuracy' => $mlAccuracy,
                'statistical_accuracy' => $statisticalAccuracy,
                'improvement' => $this->calculateImprovement($mlAccuracy, $statisticalAccuracy)
            ];
        }
        
        return view('ml-accuracy.comparison', compact('comparisonData'));
    }
    
    /**
     * Get actual expenses for a specific month
     */
    private function getActualExpensesForMonth(User $user, Carbon $date): array
    {
        $expenses = $user->expenses()
            ->whereBetween('date', [
                $date->copy()->startOfMonth()->toDateString(),
                $date->copy()->endOfMonth()->toDateString()
            ])
            ->get()
            ->groupBy('category_id');
            
        $actualExpenses = [];
        foreach ($expenses as $categoryId => $categoryExpenses) {
            $actualExpenses[$categoryId] = $categoryExpenses->sum('amount');
        }
        
        return $actualExpenses;
    }
    
    /**
     * Get ML forecasts for a specific date
     */
    private function getMLForecastsForDate(User $user, Carbon $date): array
    {
        $categories = $user->categories()->withTrashed()->get();
        $forecasts = [];
        
        foreach ($categories as $category) {
            $forecast = $this->mlService->getForecast($user, $category);
            if ($forecast && isset($forecast['prediction'])) {
                $forecasts[$category->id] = [
                    'estimated_expense' => $forecast['prediction'],
                    'method' => 'ML'
                ];
            }
        }
        
        return $forecasts;
    }
    
    /**
     * Get statistical forecasts for a specific date (placeholder)
     */
    private function getStatisticalForecastsForDate(User $user, Carbon $date): array
    {
        // This would use your existing statistical forecasting method
        // For now, returning empty array as placeholder
        return [];
    }
    
    /**
     * Calculate accuracy metrics
     */
    private function calculateAccuracyMetrics(array $forecasts, array $actuals): array
    {
        $totalError = 0;
        $totalPercentageError = 0;
        $totalSquaredError = 0;
        $count = 0;
        
        foreach ($forecasts as $categoryId => $forecast) {
            if (isset($actuals[$categoryId])) {
                $error = abs($forecast['estimated_expense'] - $actuals[$categoryId]);
                $totalError += $error;
                
                if ($actuals[$categoryId] > 0) {
                    $percentageError = ($error / $actuals[$categoryId]) * 100;
                    $totalPercentageError += $percentageError;
                }
                
                $totalSquaredError += $error * $error;
                $count++;
            }
        }
        
        if ($count === 0) {
            return [
                'mae' => 0,
                'mape' => 0,
                'rmse' => 0
            ];
        }
        
        return [
            'mae' => $totalError / $count,
            'mape' => $totalPercentageError / $count,
            'rmse' => sqrt($totalSquaredError / $count)
        ];
    }
    
    /**
     * Calculate improvement percentage
     */
    private function calculateImprovement(array $mlAccuracy, array $statisticalAccuracy): array
    {
        $improvements = [];
        
        foreach (['mae', 'mape', 'rmse'] as $metric) {
            if (isset($mlAccuracy[$metric]) && isset($statisticalAccuracy[$metric])) {
                if ($statisticalAccuracy[$metric] > 0) {
                    $improvement = (($statisticalAccuracy[$metric] - $mlAccuracy[$metric]) / $statisticalAccuracy[$metric]) * 100;
                    $improvements[$metric] = round($improvement, 2);
                } else {
                    $improvements[$metric] = 0;
                }
            }
        }
        
        return $improvements;
    }
    
    /**
     * Calculate overall ML performance
     */
    private function calculateOverallPerformance(array $mlPerformance): array
    {
        $totalMAE = 0;
        $totalMAPE = 0;
        $totalRMSE = 0;
        $totalR2 = 0;
        $count = 0;
        
        foreach ($mlPerformance as $categoryData) {
            if ($categoryData['has_sufficient_data']) {
                $performance = $categoryData['performance'];
                $totalMAE += $performance['mae'];
                $totalMAPE += $performance['mape'];
                $totalRMSE += $performance['rmse'];
                $totalR2 += $performance['r2_score'];
                $count++;
            }
        }
        
        if ($count === 0) {
            return [
                'avg_mae' => 0,
                'avg_mape' => 0,
                'avg_rmse' => 0,
                'avg_r2' => 0,
                'categories_with_ml' => 0
            ];
        }
        
        return [
            'avg_mae' => round($totalMAE / $count, 2),
            'avg_mape' => round($totalMAPE / $count, 2),
            'avg_rmse' => round($totalRMSE / $count, 2),
            'avg_r2' => round($totalR2 / $count, 4),
            'categories_with_ml' => $count
        ];
    }
} 