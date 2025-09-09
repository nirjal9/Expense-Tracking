<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ML vs Statistical Forecasting Comparison') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Comparison Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Monthly Accuracy Comparison</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ML MAE</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statistical MAE</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Improvement</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ML MAPE</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statistical MAPE</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Improvement</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($comparisonData as $monthData)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $monthData['month'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($monthData['ml_accuracy']['mae'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($monthData['statistical_accuracy']['mae'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if(isset($monthData['improvement']['mae']))
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $monthData['improvement']['mae'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $monthData['improvement']['mae'] > 0 ? '+' : '' }}{{ $monthData['improvement']['mae'] }}%
                                            </span>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($monthData['ml_accuracy']['mape'], 2) }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($monthData['statistical_accuracy']['mape'], 2) }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if(isset($monthData['improvement']['mape']))
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $monthData['improvement']['mape'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $monthData['improvement']['mape'] > 0 ? '+' : '' }}{{ $monthData['improvement']['mape'] }}%
                                            </span>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h4 class="text-lg font-semibold mb-2">MAE Improvement</h4>
                        <div class="text-3xl font-bold text-green-600">
                            @php
                                $maeImprovements = array_filter(array_column($comparisonData, 'improvement.mae'));
                                $avgMaeImprovement = count($maeImprovements) > 0 ? array_sum($maeImprovements) / count($maeImprovements) : 0;
                            @endphp
                            {{ $avgMaeImprovement > 0 ? '+' : '' }}{{ number_format($avgMaeImprovement, 1) }}%
                        </div>
                        <p class="text-sm text-gray-600">Average improvement in Mean Absolute Error</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h4 class="text-lg font-semibold mb-2">MAPE Improvement</h4>
                        <div class="text-3xl font-bold text-blue-600">
                            @php
                                $mapeImprovements = array_filter(array_column($comparisonData, 'improvement.mape'));
                                $avgMapeImprovement = count($mapeImprovements) > 0 ? array_sum($mapeImprovements) / count($mapeImprovements) : 0;
                            @endphp
                            {{ $avgMapeImprovement > 0 ? '+' : '' }}{{ number_format($avgMapeImprovement, 1) }}%
                        </div>
                        <p class="text-sm text-gray-600">Average improvement in Mean Absolute Percentage Error</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h4 class="text-lg font-semibold mb-2">Months Analyzed</h4>
                        <div class="text-3xl font-bold text-purple-600">
                            {{ count($comparisonData) }}
                        </div>
                        <p class="text-sm text-gray-600">Total months used for comparison</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-between">
                <a href="{{ route('ml-accuracy.dashboard') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Dashboard
                </a>
                <a href="{{ route('forecast') }}" 
                   class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    View Current Forecasts
                </a>
            </div>
        </div>
    </div>
</x-app-layout> 