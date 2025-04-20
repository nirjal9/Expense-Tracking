@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-white dark:bg-gray-800 shadow-lg rounded-lg text-gray-900 dark:text-white flex flex-col">
        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-200">Expense Forecast</h2>

        <form method="GET" action="{{ route('forecast') }}" class="mb-4 flex items-center space-x-2">
            <label for="forecastMonth" class="text-gray-700 dark:text-gray-300">Select Month:</label>
            <input type="month" id="forecastMonth" name="date" value="{{ request('date', now()->format('Y-m')) }}"
                   class="p-2 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                View Forecast
            </button>
        </form>

        <div class="w-full overflow-auto">
            <table class="w-full border-collapse border border-gray-200 dark:border-gray-600 mb-6">
                <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Category</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Budget %</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Estimated Expense</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Actual Expense</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">% of Income</th>
                </tr>
                </thead>
                <tbody>
                @foreach($forecasts as $forecast)
                    <tr>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">{{ $forecast['category'] }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">{{ $forecast['budget_percentage'] }}%</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">Rs.{{ number_format($forecast['estimated_expense'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">Rs.{{ number_format($forecast['actual_expense'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3 {{ $forecast['expense_percentage'] > 100 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $forecast['expense_percentage'] }}%
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
