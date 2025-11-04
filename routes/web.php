<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\AdminController;
//use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncomeController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified','check.initial.registration'])
    ->name('dashboard');

//Route::middleware(['auth', 'admin'])->group(function () {
//    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
//    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
//    Route::delete('/permissions/{role}/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
//});


Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/categories', [AdminController::class, 'categories'])->name('admin.categories');
    Route::get('admin/categories/create', [AdminController::class, 'createCategory'])->name('admin.categories.create');
    Route::post('admin/categories/create', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
//    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.delete')
//        ->middleware('permission:delete-category');
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::delete('/permissions/{role}/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
//    Route::delete('/categories/{category}/force', [AdminController::class, 'forceDelete'])
//        ->name('categories.forceDelete');
    Route::get('/admin/categories/{category}/edit', [AdminController::class, 'editCategory'])->name('admin.categories.edit');
    Route::put('/admin/categories/{category}', [AdminController::class, 'updateCategory'])->name('admin.categories.update');
    Route::delete('/admin/categories/{category}', [AdminController::class, 'destroyCategory'])->name('admin.categories.delete');
});


//Route::get('/register/categories',[RegisteredUserController::class,'showCategories'])->name('register.categories')->middleware(['check.income']);
//Route::post('/register/categories',[RegisteredUserController::class,'storeCategories'])->name('register.categories.store')->middleware(['auth', 'check.income']);
Route::get('/register/categories',[RegisteredUserController::class,'showCategories'])
    ->name('register.categories')
    ->middleware(['redirect.if.initial.registration.complete', 'check.initial.registration', 'check.income']);

Route::post('/register/categories',[RegisteredUserController::class,'storeCategories'])
    ->name('register.categories.store')
    ->middleware(['redirect.if.initial.registration.complete', 'check.initial.registration', 'check.income']);

Route::get('/register/income',[RegisteredUserController::class,'showIncome'])
    ->name('register.income')
    ->middleware(['redirect.if.initial.registration.complete', 'check.initial.registration']);

Route::post('/register/income',[RegisteredUserController::class,'storeIncome'])
    ->name('register.income.store')
    ->middleware(['redirect.if.initial.registration.complete', 'check.initial.registration']);

Route::get('/register/budget',[RegisteredUserController::class,'showBudget'])
    ->name('register.budget')
    ->middleware(['redirect.if.initial.registration.complete', 'check.initial.registration', 'check.income']);
Route::post('/register/budget',[RegisteredUserController::class,'storeBudget'])
    ->name('register.budget.store')
    ->middleware(['redirect.if.initial.registration.complete', 'check.initial.registration', 'check.income']);

Route::middleware(['auth','check.initial.registration'])->group(function () {
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index')->middleware('permission:expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware('permission:expenses.store');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create')->middleware('permission:expenses.create');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware('permission:expenses.destroy');
});
Route::get('/forecast', [ForecastController::class, 'createForecast'])->name('forecast')->middleware('permission:forecast','check.initial.registration');

// ML Accuracy Routes
Route::middleware(['auth', 'check.initial.registration'])->group(function () {
    Route::get('/ml-accuracy', [App\Http\Controllers\MLAccuracyController::class, 'dashboard'])->name('ml-accuracy.dashboard');
    Route::get('/ml-accuracy/compare', [App\Http\Controllers\MLAccuracyController::class, 'compareMethods'])->name('ml-accuracy.compare');
});

// Payment Notification Routes
Route::middleware(['auth', 'check.initial.registration'])->group(function () {
    Route::get('/payment-notifications', [App\Http\Controllers\PaymentNotificationController::class, 'dashboard'])->name('payment-notifications.dashboard');
    Route::get('/payment-notifications/gmail/auth-url', [App\Http\Controllers\PaymentNotificationController::class, 'getGmailAuthUrl'])->name('payment-notifications.gmail.auth-url');
    Route::post('/payment-notifications/gmail/authenticate', [App\Http\Controllers\PaymentNotificationController::class, 'authenticateGmail'])->name('payment-notifications.gmail.authenticate');
    Route::post('/payment-notifications/process-emails', [App\Http\Controllers\PaymentNotificationController::class, 'processEmails'])->name('payment-notifications.process-emails');
    Route::post('/payment-notifications/process-sms', [App\Http\Controllers\PaymentNotificationController::class, 'processSMS'])->name('payment-notifications.process-sms');
    Route::get('/payment-notifications/auto-created-expenses', [App\Http\Controllers\PaymentNotificationController::class, 'getAutoCreatedExpenses'])->name('payment-notifications.auto-created-expenses');
    Route::post('/payment-notifications/expenses/{expense}/approve', [App\Http\Controllers\PaymentNotificationController::class, 'approveExpense'])->name('payment-notifications.expenses.approve');
    Route::post('/payment-notifications/expenses/{expense}/reject', [App\Http\Controllers\PaymentNotificationController::class, 'rejectExpense'])->name('payment-notifications.expenses.reject');
    Route::post('/payment-notifications/test-parsing', [App\Http\Controllers\PaymentNotificationController::class, 'testParsing'])->name('payment-notifications.test-parsing');
    Route::get('/payment-notifications/statistics', [App\Http\Controllers\PaymentNotificationController::class, 'getStatistics'])->name('payment-notifications.statistics');
    
});

// Webhook Routes (no auth required for external services)
Route::post('/webhooks/payment-notifications', [App\Http\Controllers\PaymentNotificationController::class, 'webhook'])->name('webhooks.payment-notifications');

// Test Routes (requires auth, no CSRF protection for AJAX)
Route::post('/test-create-from-sms', function(Request $request) {
    try {
        // Get the authenticated user
        $user = auth()->user();
        
        // If no authenticated user, return error
        if (!$user) {
            return response()->json(['error' => 'User not authenticated. Please login first.'], 401);
        }

        $content = $request->input('content');
        if (!$content) {
            return response()->json(['error' => 'Content is required'], 400);
        }

        // Try both SMS and Email parsing using properly injected services
        $smsParser = app(App\Services\PaymentNotification\SMSParserService::class);
        $emailParser = app(App\Services\PaymentNotification\EmailParserService::class);
        
        $transactionData = $smsParser->parse($content, 'test');
        if (!$transactionData) {
            $transactionData = $emailParser->parse($content, 'test');
        }
        
        if (!$transactionData) {
            return response()->json(['error' => 'Could not parse content as SMS or Email'], 400);
        }

        // Use auto-categorization service to determine the correct category
        $categorizationService = app(\App\Services\PaymentNotification\AutoCategorizationService::class);
        $category = $categorizationService->categorize(
            $transactionData['merchant'] ?? '',
            $transactionData['description'] ?? '',
            ['user_id' => $user->id]
        );

        // If no category found, use the first available category as fallback
        if (!$category) {
            $category = \App\Models\Category::first();
            if (!$category) {
                return response()->json(['error' => 'No categories found. Please create a category first.'], 400);
            }
        }

        // Create expense with minimal required fields
        $expenseData = [
            'user_id' => $user->id,
            'amount' => (float)$transactionData['amount'],
            'description' => $transactionData['description'] ?? 'Auto-created from notification',
            'date' => $transactionData['date'] ?? now()->format('Y-m-d'),
            'category_id' => $category->id,
        ];

        // Add auto-creation fields if they exist in the parsed data
        if (isset($transactionData['merchant'])) {
            $expenseData['merchant'] = $transactionData['merchant'];
        }
        if (isset($transactionData['transaction_id'])) {
            $expenseData['transaction_id'] = $transactionData['transaction_id'];
        }
        
        $expenseData['is_auto_created'] = true;
        $expenseData['source'] = 'test';
        $expenseData['requires_approval'] = false;
        $expenseData['auto_created_at'] = now();

        $expense = \App\Models\Expense::create($expenseData);
        
        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully',
            'expense_id' => $expense->id,
            'amount' => $expense->amount,
            'merchant' => $expense->merchant ?? 'N/A',
            'category' => $category->name,
            'parsed_data' => $transactionData
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
})->middleware('auth')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// API endpoint for merchant mappings
Route::get('/api/merchant-mappings', function() {
    try {
        $mappings = App\Models\MerchantCategoryMapping::with('category')->get()->map(function($mapping) {
            return [
                'merchant' => $mapping->merchant,
                'category' => $mapping->category->name ?? 'Unknown',
                'confidence' => $mapping->confidence,
                'usage_count' => $mapping->usage_count
            ];
        });
        
        return response()->json(['mappings' => $mappings]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Test Routes (remove in production)
Route::post('/test-sms-parsing', function(Request $request) {
    try {
        $content = $request->input('content');
        if (!$content) {
            return response()->json(['error' => 'Content is required'], 400);
        }

        $smsParser = new App\Services\PaymentNotification\SMSParserService();
        $transactionData = $smsParser->parse($content, 'test');
        
        if ($transactionData) {
            return response()->json([
                'success' => true,
                'message' => 'SMS parsed successfully',
                'parsed_data' => $transactionData
            ]);
        } else {
            return response()->json(['error' => 'Could not parse SMS content'], 400);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::post('/test-create-expense', function(Request $request) {
    try {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $paymentService = app(App\Services\PaymentNotification\PaymentNotificationService::class);
        
        // Sample transaction data
        $transactionData = [
            'amount' => $request->amount ?? 500.00,
            'merchant' => $request->merchant ?? 'Test Store',
            'transaction_id' => $request->transaction_id ?? 'TEST' . time(),
            'date' => now(),
            'source' => 'test',
            'notification_type' => 'test',
            'raw_data' => ['test' => true],
            'description' => 'Test expense created via API'
        ];

        $expense = $paymentService->createExpenseFromTransaction($transactionData, $user);
        
        if ($expense) {
            return response()->json([
                'success' => true,
                'message' => 'Test expense created successfully',
                'expense_id' => $expense->id,
                'amount' => $expense->amount,
                'merchant' => $expense->merchant
            ]);
        } else {
            return response()->json(['error' => 'Failed to create expense'], 500);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
        })->middleware('auth');

Route::get('/test-payment-parsing', function() {
    try {
        $emailParser = new App\Services\PaymentNotification\EmailParserService();
        $smsParser = new App\Services\PaymentNotification\SMSParserService();
        
        // Test email parsing
        $esewaEmail = 'Dear User, Payment of Rs. 500.00 to ABC Store successful. Transaction ID: ES123456789. Date: 15-Jan-2024. Thank you for using eSewa.';
        $emailResult = $emailParser->parse($esewaEmail, 'email');
        
        // Test SMS parsing
        $bankSMS = 'Rs. 1,500.00 debited from A/C **1234 on 15-Jan-24 at Petrol Pump ABC. Avl Bal: Rs. 25,000.00';
        $smsResult = $smsParser->parse($bankSMS, 'sms');
        
        return response()->json([
            'status' => 'success',
            'email_parsing' => $emailResult,
            'sms_parsing' => $smsResult,
            'message' => 'Payment notification parsing is working correctly!'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Test ML Service (remove in production)
Route::get('/test-ml', function() {
    try {
        $mlService = new App\Services\MLForecastService();
        
        // MySQL connection info
        $db = config('database.connections.mysql');
        $dbPingOk = false;
        try {
            \Illuminate\Support\Facades\DB::connection('mysql')->select('SELECT 1');
            $dbPingOk = true;
        } catch (\Throwable $t) {
            $dbPingOk = false;
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'ML Service initialized successfully',
            'test_data' => [
                'service_initialized' => true,
                'python_path' => $mlService->pythonPath ?? 'Not accessible',
                'script_path' => $mlService->scriptPath ?? 'Not accessible',
                'script_exists' => file_exists($mlService->scriptPath ?? ''),
                'db_connection' => config('database.default'),
                'mysql' => [
                    'host' => $db['host'] ?? null,
                    'port' => $db['port'] ?? null,
                    'database' => $db['database'] ?? null,
                    'username' => $db['username'] ?? null,
                    'ping_ok' => $dbPingOk,
                ],
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// Test ML Service with user data (remove in production)
Route::get('/test-ml-user', function() {
    try {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated']);
        }
        
        $mlService = new App\Services\MLForecastService();
        $categories = $user->categories()->withTrashed()->get();
        
        $testData = [
            'user_id' => $user->id,
            'categories_count' => $categories->count(),
            'categories' => $categories->map(function($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    // Count expenses for the CURRENT user, not the category owner
                    'expenses_count' => $cat->expenses()->where('user_id', Auth::id())->count()
                ];
            })->toArray()
        ];
        
        return response()->json([
            'status' => 'success', 
            'message' => 'User data retrieved successfully',
            'test_data' => $testData
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// Test database configuration (remove in production)
Route::get('/test-db', function() {
    try {
        $dbConfig = [
            'default_connection' => config('database.default'),
            'sqlite_database' => config('database.connections.sqlite.database'),
            'database_path' => database_path('database.sqlite'),
            'storage_path' => storage_path('database.sqlite'),
            'base_path' => base_path('database.sqlite'),
            'env_db_database' => env('DB_DATABASE'),
            'env_db_connection' => env('DB_CONNECTION'),
        ];
        
        // Check which files actually exist
        $dbFiles = [
            'database/database.sqlite' => file_exists(database_path('database.sqlite')),
            'storage/database.sqlite' => file_exists(storage_path('database.sqlite')),
            'base/database.sqlite' => file_exists(base_path('database.sqlite')),
        ];
        
        return response()->json([
            'status' => 'success',
            'database_config' => $dbConfig,
            'database_files' => $dbFiles,
            'current_working_directory' => getcwd(),
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

//Route::middleware('auth')->group(function () {
//    Route::resource('categories', CategoryController::class);
//});

Route::middleware(['auth','check.initial.registration'])->group(function () {
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index')->middleware('permission:categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create')->middleware('permission:categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store')->middleware('permission:categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit')->middleware('permission:categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update')->middleware('permission:categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy')->middleware('permission:categories.destroy');
});

Route::middleware(['auth','check.initial.registration'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware(['auth','check.initial.registration'])->group(function () {
    Route::resource('incomes', IncomeController::class);
});


require __DIR__.'/auth.php';
