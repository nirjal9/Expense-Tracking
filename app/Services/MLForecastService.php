<?php

namespace App\Services;

use App\Models\User;
use App\Models\Category;
use App\Models\MLModel;
use App\Jobs\TrainMLModelJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MLForecastService
{
    public $pythonPath;
    public $scriptPath;
    
    public function __construct()
    {
        $this->pythonPath = 'python3';
        $this->scriptPath = base_path('ml_scripts/forecast.py');
    }
    
    /**
     * Get ML forecast for a user and category with model persistence and caching
     */
    public function getForecast(User $user, Category $category, $targetDate = null)
    {
        try {
            if (!$this->hasEnoughData($user, $category)) {
                Log::info("Insufficient data for ML forecast in category: {$category->name}");
                return null;
            }
            
            // Use current date if no target date provided
            $targetDate = $targetDate ?: now();
            $targetMonth = $targetDate->format('n'); // 1-12
            $targetYear = $targetDate->format('Y');
            
            // Try to get prediction from saved model first (no cache)
            $savedModelResult = $this->getPredictionFromSavedModel($user, $category, $targetDate);
            if ($savedModelResult && isset($savedModelResult['prediction']) && $savedModelResult['prediction'] > 0) {
                // Verify data_points to ensure we're using monthly aggregated model
                $dataPoints = $savedModelResult['data_points'] ?? 0;
                // CRITICAL: Reject models trained on individual transactions (data_points > 10)
                // Monthly aggregated models should have <= 10 data points (one per month)
                if ($dataPoints > 10) {
                    Log::warning("Rejecting saved model trained on individual transactions: data_points={$dataPoints} (should be <=10 for monthly data). Forcing retrain for user {$user->id}, category {$category->name}");
                    // Delete the invalid model file to force fresh training
                    $modelPath = storage_path("app/ml_models/forecast_user_{$user->id}_cat_{$category->id}.pkl");
                    if (file_exists($modelPath)) {
                        unlink($modelPath);
                        Log::info("Deleted invalid model file: {$modelPath}");
                    }
                } else {
                    Log::info("Using saved ML model for user {$user->id}, category {$category->name}, target: {$targetDate->format('Y-m')}, data_points: {$dataPoints}");
                    return $savedModelResult;
                }
            }
            
            // Fall back to fresh training (no cache to force fresh predictions)
            Log::info("Training fresh ML model for user {$user->id}, category {$category->name}, target: {$targetDate->format('Y-m')}");
            
            // Queue background training for future use
            TrainMLModelJob::dispatch($user, $category, 'forecast');
            
            return $this->trainAndForecast($user, $category, $targetDate);
            
        } catch (\Exception $e) {
            Log::error("ML forecast failed for category {$category->name}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if there's enough data for ML forecasting
     */
    public function hasEnoughData(User $user, Category $category): bool
    {
        $expenseCount = $user->expenses()
            ->where('category_id', $category->id)
            ->count();
            
        Log::info("ML Data Check - User: {$user->id}, Category: {$category->name}, Expense Count: {$expenseCount}");
        
        return $expenseCount >= 6; // Need at least 6 months of data
    }
    
    /**
     * Get prediction from saved model
     */
    private function getPredictionFromSavedModel(User $user, Category $category, $targetDate = null)
    {
        // Check if model file exists directly (bypass database to avoid sync issues)
        $modelPath = storage_path("app/ml_models/forecast_user_{$user->id}_cat_{$category->id}.pkl");
        
        if (!file_exists($modelPath)) {
            Log::info("Model file not found at: {$modelPath}");
            return null;
        }
        
        // Check model age - don't use models older than 7 days
        $modelAge = time() - filemtime($modelPath);
        if ($modelAge > (7 * 24 * 60 * 60)) {
            Log::info("Model file is too old (age: {$modelAge} seconds), forcing retrain");
            return null;
        }
        
        try {
            $dbConfig = $this->getMySQLDatabaseConfig();
            
            // Parse target date if provided
            $targetMonth = null;
            $targetYear = null;
            if ($targetDate) {
                $date = $targetDate instanceof \Carbon\Carbon ? $targetDate : \Carbon\Carbon::parse($targetDate);
                $targetMonth = $date->month;
                $targetYear = $date->year;
            }
            
            $command = sprintf(
                '%s %s --model-storage-path %s --db-type mysql --db-host %s --db-port %s --db-name %s --db-user %s --db-password %s --user-id %d --category-id %d',
                escapeshellarg($this->pythonPath),
                escapeshellarg($this->scriptPath),
                escapeshellarg(storage_path('app/ml_models')),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['port']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                $user->id,
                $category->id
            );
            
            // Add target month/year if provided
            if ($targetMonth && $targetYear) {
                $command .= sprintf(' --target-month %d --target-year %d', $targetMonth, $targetYear);
            }
            
            Log::info("Calling Python script for prediction: user_id={$user->id}, category_id={$category->id}, target={$targetMonth}/{$targetYear}");
            $output = shell_exec($command . ' 2>&1');
            Log::info("Python script output: " . substr($output, 0, 500));
            
            $result = json_decode($output, true);
            
            if ($result && !isset($result['error']) && isset($result['prediction'])) {
                // Verify the result has correct data_points before returning
                $dataPoints = $result['data_points'] ?? 0;
                $prediction = $result['prediction'] ?? 0;
                
                // CRITICAL: Reject models trained on individual transactions (data_points > 10)
                // Monthly aggregated models should have <= 10 data points (one per month)
                if ($dataPoints > 10) {
                    Log::warning("Rejecting saved model trained on individual transactions: data_points={$dataPoints} (should be <=10 for monthly data). Forcing retrain.");
                    return null;
                }
                
                if ($prediction > 0) {
                    Log::info("Using saved model prediction: Rs.{$prediction}, data_points: {$dataPoints}");
                    return $result;
                } else {
                    Log::warning("Rejecting saved model result: prediction={$prediction}");
                    return null;
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to use saved model: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Train ML model and get forecast
     */
    private function trainAndForecast(User $user, Category $category, $targetDate = null)
    {
        $dbConfig = $this->getMySQLDatabaseConfig();
        
        // Format target date for Python script
        $targetDate = $targetDate ?: now();
        $targetMonth = $targetDate->format('n'); // 1-12
        $targetYear = $targetDate->format('Y');
        
        $command = sprintf(
            '%s %s --db-type mysql --db-host %s --db-port %s --db-name %s --db-user %s --db-password %s --user-id %d --category-id %d --target-month %d --target-year %d',
            escapeshellarg($this->pythonPath),
            escapeshellarg($this->scriptPath),
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['port']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            $user->id,
            $category->id,
            $targetMonth,
            $targetYear
        );
        
        Log::info("Executing ML command: " . $command);
        
        $output = shell_exec($command);
        Log::info("ML forecast output: " . $output);
        
        if ($output === null) {
            Log::error("ML script execution failed");
            return null;
        }
        
        $result = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Failed to parse ML output JSON: " . json_last_error_msg());
            return null;
        }
        
        if (isset($result['error'])) {
            Log::error("ML script returned error: " . $result['error']);
            return null;
        }
        
        // CRITICAL: Validate data_points for ALL results (Cached or Fresh)
        $dataPoints = $result['data_points'] ?? 0;
        if ($dataPoints > 10) {
            Log::warning("Rejecting ML result trained on individual transactions: data_points={$dataPoints} (should be <=10 for monthly data). User: {$user->id}, Category: {$category->name}");
            // Delete the invalid model file if it exists
            $modelPath = storage_path("app/ml_models/forecast_user_{$user->id}_cat_{$category->id}.pkl");
            if (file_exists($modelPath)) {
                unlink($modelPath);
                Log::info("Deleted invalid model file: {$modelPath}");
            }
            return null; // Force fallback to statistical method
        }
        
        return $result;
    }
    
    /**
     * Get ML model performance metrics with caching
     */
    public function getModelPerformance(User $user, Category $category)
    {
        try {
            if (!$this->hasEnoughData($user, $category)) {
                return [
                    'mae' => 0,
                    'mape' => 0,
                    'rmse' => 0,
                    'r2_score' => 0
                ];
            }
            
            // Cache performance metrics for 6 hours
            $cacheKey = "ml_performance_{$user->id}_{$category->id}_" . now()->format('Y-m-d');
            
            return Cache::remember($cacheKey, 21600, function() use ($user, $category) {
                return $this->calculateModelPerformance($user, $category);
            });
            
        } catch (\Exception $e) {
            Log::error("ML performance check failed for category {$category->name}: " . $e->getMessage());
            return [
                'mae' => 0,
                'mape' => 0,
                'rmse' => 0,
                'r2_score' => 0
            ];
        }
    }
    
    /**
     * Calculate ML model performance metrics
     */
    private function calculateModelPerformance(User $user, Category $category)
    {
        $dbConfig = $this->getMySQLDatabaseConfig();
            
        $command = sprintf(
            '%s %s --performance-only --aggregate-monthly --db-type mysql --db-host %s --db-port %s --db-name %s --db-user %s --db-password %s --user-id %d --category-id %d',
            escapeshellarg($this->pythonPath),
            escapeshellarg($this->scriptPath),
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['port']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            $user->id,
            $category->id
        );
        
        Log::info("Executing ML performance command: " . $command);
        
        $output = shell_exec($command);
        Log::info("ML performance output: " . $output);
        
        if ($output === null) {
            Log::error("ML performance script execution failed");
            return [
                'mae' => 0,
                'mape' => 0,
                'rmse' => 0,
                'r2_score' => 0
            ];
        }
        
        $result = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Failed to parse ML performance output JSON: " . json_last_error_msg());
            return [
                'mae' => 0,
                'mape' => 0,
                'rmse' => 0,
                'r2_score' => 0
            ];
        }
        
        if (isset($result['error'])) {
            Log::error("ML performance script returned error: " . $result['error']);
            return [
                'mae' => 0,
                'mape' => 0,
                'rmse' => 0,
                'r2_score' => 0
            ];
        }
        
        return $result;
    }
    
    /**
     * Clear cache for a specific user and category
     */
    public function clearCache(User $user, Category $category)
    {
        $forecastPattern = "ml_forecast_{$user->id}_{$category->id}_*";
        $performancePattern = "ml_performance_{$user->id}_{$category->id}_*";
        
        // Clear forecast cache for all hours of today
        for ($hour = 0; $hour < 24; $hour++) {
            $cacheKey = "ml_forecast_{$user->id}_{$category->id}_" . now()->format('Y-m-d') . "-{$hour}";
            Cache::forget($cacheKey);
        }
        
        // Clear performance cache
        $performanceCacheKey = "ml_performance_{$user->id}_{$category->id}_" . now()->format('Y-m-d');
        Cache::forget($performanceCacheKey);
        
        Log::info("Cleared ML cache for user {$user->id}, category {$category->name}");
    }
    
    /**
     * Clear all ML cache for a user
     */
    public function clearUserCache(User $user)
    {
        $categories = $user->categories()->get();
        foreach ($categories as $category) {
            $this->clearCache($user, $category);
        }
    }

    /**
     * Get MySQL database configuration
     */
    public function getMySQLDatabaseConfig(): array
    {
        return [
            'host' => config('database.connections.mysql.host', '127.0.0.1'),
            'port' => config('database.connections.mysql.port', 3306),
            'database' => config('database.connections.mysql.database', 'expense_tracking_system'),
            'username' => config('database.connections.mysql.username', 'root'),
            'password' => config('database.connections.mysql.password', ''),
        ];
    }
} 