<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>ML Accuracy (Bare)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;padding:24px;color:#111} h1{font-size:20px;margin:0 0 12px} .meta{margin:0 0 16px;color:#555} li{margin:4px 0}</style>
    </head>
<body>
    <h1>ML Forecasting Accuracy (Bare)</h1>
    <p class="meta">Service: <strong>{{ $mlServiceAvailable ? 'Available' : 'Unavailable' }}</strong> • Categories with ML: <strong>{{ data_get($overallPerformance,'categories_with_ml',0) }}</strong> • Avg R²: <strong>{{ data_get($overallPerformance,'avg_r2',0) }}</strong></p>
    @if(!empty($mlPerformance))
        <ol>
        @foreach($mlPerformance as $id => $c)
            <li>
                <strong>{{ $c['category_name'] }}</strong>
                — R²: {{ data_get($c,'performance.r2_score',0) }}, MAE: {{ number_format(data_get($c,'performance.mae',0), 6) }}, RMSE: {{ number_format(data_get($c,'performance.rmse',0), 6) }}
            </li>
        @endforeach
        </ol>
    @else
        <p>No performance data.</p>
    @endif
</body>
</html>



































