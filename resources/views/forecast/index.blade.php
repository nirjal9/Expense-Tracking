@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-white dark:bg-gray-800 shadow-lg rounded-lg text-gray-900 dark:text-white flex flex-col">
        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-200">Expense Forecast</h2>

        @if (isset($errors) && $errors->any())
            <div class="bg-red-100 dark:bg-red-800 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 p-4 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="GET" action="{{ route('forecast') }}" class="mb-4 flex items-center space-x-2">
            <label for="forecastMonth" class="text-gray-700 dark:text-gray-300">Select Month & Year:</label>
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
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Forecast Method</th>
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
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">
                            <span class="px-2 py-1 rounded text-xs {{ str_contains($forecast['forecast_method'] ?? '', 'Machine Learning') ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200' }}">
                                {{ $forecast['forecast_method'] ?? 'Statistical' }}
                            </span>
                            @if(isset($forecast['ml_confidence']) && $forecast['ml_confidence'])
                                <br><small class="text-gray-500 dark:text-gray-400">
                                    Confidence: {{ number_format($forecast['ml_confidence'] * 100, 1) }}%
                                    @if(isset($forecast['raw_r2_score']) && $forecast['raw_r2_score'] < 0)
                                        <span class="text-yellow-600 dark:text-yellow-400" title="Model R² score: {{ number_format($forecast['raw_r2_score'], 3) }}">⚠️</span>
                                    @endif
                                </small>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

{{--@extends('layouts.app')--}}

{{--@section('content')--}}
{{--    <div class="max-w-6xl mx-auto p-6 bg-gray-800 shadow-lg rounded-lg text-white flex flex-col">--}}
{{--        <h2 class="text-xl font-bold mb-4 text-gray-200">Expense Forecast</h2>--}}

{{--        <form method="GET" action="{{ route('forecast') }}" class="mb-4 flex items-center space-x-2">--}}
{{--            <label for="forecastMonth" class="text-white">Select Month:</label>--}}
{{--            <input type="month" id="forecastMonth" name="date" value="{{ request('date', now()->format('Y-m')) }}"--}}
{{--                   class="p-2 rounded-md text-black">--}}
{{--            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-700">--}}
{{--                View Forecast--}}
{{--            </button>--}}
{{--        </form>--}}

{{--        <div class="w-full overflow-auto">--}}
{{--            <table class="w-full border-collapse border border-gray-600 mb-6">--}}
{{--                <thead>--}}
{{--                <tr class="bg-gray-700">--}}
{{--                    <th class="border border-gray-500 px-6 py-3">Category</th>--}}
{{--                    <th class="border border-gray-500 px-6 py-3">Budget %</th>--}}
{{--                    <th class="border border-gray-500 px-6 py-3">Estimated Expense</th>--}}
{{--                    <th class="border border-gray-500 px-6 py-3">Actual Expense</th>--}}
{{--                    <th class="border border-gray-500 px-6 py-3">% of Income</th>--}}
{{--                </tr>--}}
{{--                </thead>--}}
{{--                <tbody>--}}
{{--                @foreach($forecasts as $forecast)--}}
{{--                    <tr>--}}
{{--                        <td class="border border-gray-500 px-6 py-3">{{ $forecast['category'] }}</td>--}}
{{--                        <td class="border border-gray-500 px-6 py-3">{{ $forecast['budget_percentage'] }}%</td>--}}
{{--                        <td class="border border-gray-500 px-6 py-3">Rs.{{ number_format($forecast['estimated_expense'], 2) }}</td>--}}
{{--                        <td class="border border-gray-500 px-6 py-3">Rs.{{ number_format($forecast['actual_expense'], 2) }}</td>--}}
{{--                        <td class="border border-gray-500 px-6 py-3 {{ $forecast['expense_percentage'] > 100 ? 'text-red-500' : 'text-green-400' }}">--}}
{{--                            {{ $forecast['expense_percentage'] }}%--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                @endforeach--}}
{{--                </tbody>--}}
{{--            </table>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--@endsection--}}

{{--@extends('layouts.app')--}}

{{--@section('content')--}}
{{--    <div class="max-w-6xl mx-auto p-6 bg-white dark:bg-gray-800 shadow-lg rounded-lg text-gray-900 dark:text-white flex flex-col">--}}
{{--        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-200">Expense Forecast</h2>--}}

{{--        <form method="GET" action="{{ route('forecast') }}" class="mb-4 flex items-center space-x-2">--}}
{{--            <label for="forecastMonth" class="text-gray-700 dark:text-gray-300">Select Month:</label>--}}
{{--            <input type="month" id="forecastMonth" name="date" value="{{ request('date', now()->format('Y-m')) }}"--}}
{{--                   class="p-2 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">--}}
{{--            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-700">--}}
{{--                View Forecast--}}
{{--            </button>--}}
{{--        </form>--}}

{{--        <div class="w-full overflow-auto">--}}
{{--            <table class="w-full border-collapse border border-gray-200 dark:border-gray-600 mb-6">--}}
{{--                <thead>--}}
{{--                <tr class="bg-gray-100 dark:bg-gray-700">--}}
{{--                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Category</th>--}}
{{--                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Budget %</th>--}}
{{--                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Estimated Expense</th>--}}
{{--                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Actual Expense</th>--}}
{{--                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">% of Income</th>--}}
{{--                </tr>--}}
{{--                </thead>--}}
{{--                <tbody>--}}
{{--                @foreach($forecasts as $forecast)--}}
{{--                    <tr>--}}
{{--                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">{{ $forecast['category'] }}</td>--}}
{{--                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">{{ $forecast['budget_percentage'] }}%</td>--}}
{{--                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">Rs.{{ number_format($forecast['estimated_expense'], 2) }}</td>--}}
{{--                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">Rs.{{ number_format($forecast['actual_expense'], 2) }}</td>--}}
{{--                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3 {{ $forecast['expense_percentage'] > 100 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">--}}
{{--                            {{ $forecast['expense_percentage'] }}%--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                @endforeach--}}
{{--                </tbody>--}}
{{--            </table>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--@endsection--}}

