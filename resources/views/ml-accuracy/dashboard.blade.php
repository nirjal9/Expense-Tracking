@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-gray-800 shadow mb-6">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ML Forecasting Accuracy Dashboard') }}
        </h2>
    </div>
</div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Ultra-simple visible list to ensure content renders even if styles fail -->
            <div class="bg-white border border-gray-200 rounded-md p-4 mb-4">
                <h3 class="text-base font-semibold mb-2">Summary</h3>
                <ul class="list-disc list-inside text-sm text-gray-700">
                    <li>ML Service: {{ $mlServiceAvailable ? 'Available' : 'Unavailable' }}</li>
                    <li>Categories with ML: {{ data_get($overallPerformance,'categories_with_ml',0) }}</li>
                    <li>Avg R²: {{ data_get($overallPerformance,'avg_r2',0) }}</li>
                </ul>
            </div>
            <!-- Minimal summary to ensure visible content even if grids fail -->
            <div class="bg-white border border-gray-200 rounded-md p-4 mb-6">
                <p class="text-sm text-gray-700">
                    Service: <span class="font-semibold">{{ $mlServiceAvailable ? 'Available' : 'Unavailable' }}</span>
                    • Categories: <span class="font-semibold">{{ is_array($overallPerformance) ? ($overallPerformance['categories_with_ml'] ?? 0) : 0 }}</span>
                    • Avg R²: <span class="font-semibold">{{ is_array($overallPerformance) ? ($overallPerformance['avg_r2'] ?? 0) : 0 }}</span>
                </p>
            </div>
            <!-- ML Service Status -->
            @if(!$mlServiceAvailable)
            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            ML Service Not Available
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>The Machine Learning service is currently unavailable. This could be due to:</p>
                            <ul class="list-disc list-inside mt-1">
                                <li>Python dependencies not installed</li>
                                <li>ML script not found</li>
                                <li>Database connection issues</li>
                            </ul>
                            <p class="mt-2">Please run the setup script: <code class="bg-yellow-100 px-2 py-1 rounded">./setup_ml.sh</code></p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Overall Performance Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Overall ML Performance</h3>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ data_get($overallPerformance,'categories_with_ml',0) }}</div>
                            <div class="text-sm text-gray-600">Categories with ML</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ data_get($overallPerformance,'avg_r2',0) }}</div>
                            <div class="text-sm text-gray-600">Avg R² Score</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600">{{ data_get($overallPerformance,'avg_mae',0) }}</div>
                            <div class="text-sm text-gray-600">Avg MAE</div>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-red-600">{{ data_get($overallPerformance,'avg_mape',0) }}%</div>
                            <div class="text-sm text-gray-600">Avg MAPE</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">{{ data_get($overallPerformance,'avg_rmse',0) }}</div>
                            <div class="text-sm text-gray-600">Avg RMSE</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debug Information (remove in production) -->
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4 mb-6">
                <h3 class="text-lg font-semibold mb-2">Debug Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>ML Service Available:</strong> {{ $mlServiceAvailable ? 'Yes' : 'No' }}<br>
                        <strong>Categories Count:</strong> {{ count($mlPerformance) }}<br>
                        <strong>Overall Performance:</strong> {{ json_encode($overallPerformance) }}
                    </div>
                    <div>
                        <strong>Categories Data:</strong><br>
                        <pre class="text-xs bg-white p-2 rounded border">{{ json_encode($mlPerformance, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>

            <!-- Category Performance Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Category Performance Breakdown</h3>
                    @if(count($mlPerformance) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">R² Score</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MAE</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MAPE</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RMSE</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($mlPerformance as $categoryId => $categoryData)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $categoryData['category_name'] }}
                                        </td>
                                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if(isset($categoryData['performance']['error']))
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800" title="{{ $categoryData['performance']['error'] }}">
                                                Error
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $categoryData['performance']['r2_score'] >= 0.7 ? 'bg-green-100 text-green-800' : 
                                                   ($categoryData['performance']['r2_score'] >= 0.5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                {{ number_format($categoryData['performance']['r2_score'], 4) }}
                                            </span>
                                        @endif
                                    </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($categoryData['performance']['mae'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($categoryData['performance']['mape'], 2) }}%
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($categoryData['performance']['rmse'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($categoryData['has_sufficient_data'])
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    ML Active
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Insufficient Data
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500">No categories found or no performance data available.</p>
                            <p class="text-sm text-gray-400 mt-2">Make sure you have categories set up and some expense data.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-between">
                <a href="{{ route('ml-accuracy.compare') }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Compare ML vs Statistical Methods
                </a>
                <a href="{{ route('forecast') }}" 
                   class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    View Forecasts
                </a>
            </div>
        </div>
    </div>
@endsection 