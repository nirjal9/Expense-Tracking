<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\AdminController;
//use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\PermissionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified','check.initial.registration'])->name('dashboard');

//Route::middleware(['auth', 'admin'])->group(function () {
//    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
//    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
//    Route::delete('/permissions/{role}/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
//});


Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
//    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.delete')
//        ->middleware('permission:delete-category');
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::delete('/permissions/{role}/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    Route::delete('/categories/{category}/force', [CategoryController::class, 'forceDelete'])
        ->name('categories.forceDelete');
});


//Route::get('/register/categories',[RegisteredUserController::class,'showCategories'])->name('register.categories')->middleware(['check.income']);
//Route::post('/register/categories',[RegisteredUserController::class,'storeCategories'])->name('register.categories.store')->middleware(['auth', 'check.income']);
Route::get('/register/categories',[RegisteredUserController::class,'showCategories'])
    ->name('register.categories')
    ->middleware(['check.initial.registration', 'check.income','redirect.if.initial.registration.complete']);

Route::post('/register/categories',[RegisteredUserController::class,'storeCategories'])
    ->name('register.categories.store')
    ->middleware(['check.initial.registration', 'check.income','redirect.if.initial.registration.complete']);

Route::get('/register/income',[RegisteredUserController::class,'showIncome'])
    ->name('register.income')
    ->middleware(['check.initial.registration']);

Route::post('/register/income',[RegisteredUserController::class,'storeIncome'])
    ->name('register.income.store')
    ->middleware([ 'check.initial.registration']);

Route::get('/register/budget',[RegisteredUserController::class,'showBudget'])
    ->name('register.budget')
    ->middleware(['check.initial.registration', 'check.income','redirect.if.initial.registration.complete']);
Route::post('/register/budget',[RegisteredUserController::class,'storeBudget'])
    ->name('register.budget.store')
    ->middleware(['check.initial.registration', 'check.income','redirect.if.initial.registration.complete']);

Route::middleware(['auth','check.initial.registration'])->group(function () {
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index')->middleware('permission:expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware('permission:expenses.store');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create')->middleware('permission:expenses.create');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware('permission:expenses.destroy');
});
Route::get('/forecast', [ForecastController::class, 'createForecast'])->name('forecast')->middleware('permission:forecast','check.initial.registration');

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


require __DIR__.'/auth.php';
