@extends('layouts.app')

@section('content')
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-bold mb-6">Dashboard</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-blue-100 dark:bg-blue-900 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">Monthly Income</h3>
                            <p class="text-2xl font-bold">Rs. {{ number_format($monthlyIncome, 2) }}</p>
                        </div>
                        <div class="bg-red-100 dark:bg-red-900 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">Monthly Expenses</h3>
                            <p class="text-2xl font-bold">Rs. {{ number_format($monthlyExpenses, 2) }}</p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">Net Savings</h3>
                            <p class="text-2xl font-bold">Rs. {{ number_format($netSavings, 2) }}</p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Budget Overview</h3>

                        @forelse($budgetInfo as $budget)
                            <div class="mb-4">
                                <div class="flex justify-between mb-1">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $budget['category']->name }}</span>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        Rs. {{ number_format($budget['spent'], 2) }} / Rs. {{ number_format($budget['budget'], 2) }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ min($budget['percentage'], 100) }}%"></div>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Remaining: Rs. {{ number_format($budget['remaining'], 2) }}
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400">No budget categories found.</p>
                        @endforelse
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Recent Expenses</h3>
                            @forelse($recentExpenses as $expense)
                                <div class="flex justify-between items-center mb-2">
                                    <div>
                                        <span class="font-medium">{{ $expense->category->name }}</span>
                                        <span class="text-sm text-gray-500">{{ $expense->date->format('M d, Y') }}</span>
                                    </div>
                                    <span class="text-red-600">-Rs. {{ number_format($expense->amount, 2) }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500">No recent expenses.</p>
                            @endforelse
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold mb-4">Recent Income</h3>
                            @forelse($recentIncomes as $income)
                                <div class="flex justify-between items-center mb-2">
                                    <div>
                                        <span class="font-medium">{{ $income->source }}</span>
                                        <span class="text-sm text-gray-500">{{ $income->date->format('M d, Y') }}</span>
                                    </div>
                                    <span class="text-green-600">+Rs. {{ number_format($income->amount, 2) }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500">No recent income.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
