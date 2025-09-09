@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <p class="mb-4 text-sm">Service: <strong>{{ $mlServiceAvailable ? 'Available' : 'Unavailable' }}</strong></p>
                <p class="mb-6 text-sm">Categories with ML: <strong>{{ data_get($overallPerformance,'categories_with_ml',0) }}</strong> • Avg R²: <strong>{{ data_get($overallPerformance,'avg_r2',0) }}</strong></p>

                @if(!empty($mlPerformance))
                    <ul class="list-disc list-inside space-y-2">
                        @foreach($mlPerformance as $id => $c)
                            <li>
                                <span class="font-medium">{{ $c['category_name'] }}</span>
                                — R²: {{ data_get($c,'performance.r2_score',0) }}, MAE: {{ number_format(data_get($c,'performance.mae',0), 4) }}, RMSE: {{ number_format(data_get($c,'performance.rmse',0), 4) }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">No performance data.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
