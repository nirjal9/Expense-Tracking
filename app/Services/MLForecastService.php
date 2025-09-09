<?php

namespace App\Services;

use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

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
     * Get ML forecast for a user and category
     */
    public function getForecast(User $user, Category $category)
    {
        try {
            if (!$this->hasEnoughData($user, $category)) {
                Log::info("Insufficient data for ML forecast in category: {$category->name}");
                return null;
            }
            
            return $this->trainAndForecast($user, $category);
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
            
        return $expenseCount >= 6; // Need at least 6 months of data
    }
    
    /**
     * Train ML model and get forecast
     */
    private function trainAndForecast(User $user, Category $category)
    {
        $dbConfig = $this->getMySQLDatabaseConfig();
        
        $command = sprintf(
            '%s %s --db-type mysql --db-host %s --db-port %s --db-name %s --db-user %s --db-password %s --user-id %d --category-id %d',
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
        
        return $result;
    }
    
    /**
     * Get ML model performance metrics
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
     * Get MySQL database configuration
     */
    private function getMySQLDatabaseConfig(): array
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