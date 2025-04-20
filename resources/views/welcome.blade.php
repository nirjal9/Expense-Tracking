<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BudgetBuddy - Smart Financial Management</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
<!-- Logo Section -->
<div class="flex justify-center pt-16 pb-8">
    <div class="rounded-full overflow-hidden shadow-lg bg-white dark:bg-gray-800 p-2">
        <img src="{{ asset('image/logo.png') }}" alt="FinFlow Logo" class="h-32 w-32 object-cover rounded-full">
    </div>
</div>

<!-- Hero Section -->
<div class="relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-5xl font-extrabold text-gray-900 dark:text-white sm:text-6xl">
                <span class="text-indigo-600 dark:text-indigo-400">BudgetBuddy</span> - Take Control of Your
                <span class="text-indigo-600 dark:text-indigo-400">Finances</span>
            </h1>
            <p class="mt-6 text-xl text-gray-500 dark:text-gray-300 max-w-3xl mx-auto">
                A powerful expense tracking solution that helps you manage your money smarter and plan for the future.
            </p>
            <div class="mt-10 flex justify-center gap-4">
                <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Login
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create Account
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Expense Tracking -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform transition duration-300 hover:scale-105">
            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white mb-4">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Expense Tracking</h3>
            <p class="text-gray-500 dark:text-gray-300">Easily record and categorize your daily expenses. Get a clear view of where your money goes.</p>
        </div>

        <!-- Category Management -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform transition duration-300 hover:scale-105">
            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white mb-4">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Smart Categories</h3>
            <p class="text-gray-500 dark:text-gray-300">Organize your expenses with customizable categories. Get better insights into your spending patterns.</p>
        </div>

        <!-- Expense Forecasting -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform transition duration-300 hover:scale-105">
            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white mb-4">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Smart Forecasting</h3>
            <p class="text-gray-500 dark:text-gray-300">Predict future expenses and plan your budget accordingly. Make informed financial decisions.</p>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="bg-indigo-600 dark:bg-indigo-700">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
            <span class="block">Ready to take control of your finances?</span>
            <span class="block text-indigo-200">Start tracking your expenses today.</span>
        </h2>
        <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
            <div class="inline-flex rounded-md shadow">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50">
                    Get started
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
