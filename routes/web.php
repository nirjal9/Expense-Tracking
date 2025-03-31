<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\AdminMiddleware;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.delete')
        ->middleware('permission:delete-category');
});


Route::get('/register/categories',[RegisteredUserController::class,'showCategories'])->name('register.categories');
Route::post('/register/categories',[RegisteredUserController::class,'storeCategories'])->name('register.categories.store');

Route::get('/register/income',[RegisteredUserController::class,'showIncome'])->name('register.income');
Route::post('/register/income',[RegisteredUserController::class,'storeIncome'])->name('register.income.store');

Route::get('/register/budget',[RegisteredUserController::class,'showBudget'])->name('register.budget');
Route::post('/register/budget',[RegisteredUserController::class,'storeBudget'])->name('register.budget.store');

Route::middleware('auth')->group(function () {
   Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
   Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
   Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
   Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
});
//Route::get('/forecast', [ExpenseController::class, 'forecast'])->name('expenses.forecast');
Route::get('/forecast', [ForecastController::class, 'createForecast'])->name('forecast');

Route::middleware('auth')->group(function () {
    Route::resource('categories', CategoryController::class);
});



require __DIR__.'/auth.php';
