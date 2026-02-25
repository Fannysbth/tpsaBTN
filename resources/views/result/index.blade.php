@extends('layouts.app')

@section('content')

<x-header title="Result">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

{{-- Form Filter --}}
<div class="filter-wrapper" style="margin-top:10px">
    <form method="GET" class="filter-form">
        <select name="year" onchange="this.form.submit()" class="filter-select">
            <option value="all" {{ $selectedYear == 'all' ? 'selected' : '' }}>All Years</option>
            @foreach($availableYears as $year)
                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
            @endforeach
        </select>

        <select name="filter" onchange="this.form.submit()" class="filter-select">
            <option value="latest" {{ $selectedFilter == 'latest' ? 'selected' : '' }}>Latest per Vendor</option>
            <option value="all" {{ $selectedFilter == 'all' ? 'selected' : '' }}>All Data</option>
        </select>
    </form>
</div>

{{-- Main Content --}}
<div class="dashboard-grid">

    {{-- Chart Card --}}
    <div class="card chart-card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-chart-bar" style="color:#8280FF;"></i>
                Performance Chart
            </h3>
        </div>
        <div class="card-body chart-container">
            <canvas id="vendorScoresChart"></canvas>
        </div>
        <div class="legend">
            <span><span class="legend-color" style="background:#4AD991;"></span> Sangat Memadai</span>
            <span><span class="legend-color" style="background:#FEC53D;"></span> Memadai</span>
            <span><span class="legend-color" style="background:#FF6B6B;"></span> Kurang Memadai</span>
        </div>
    </div>

    {{-- Heatmap Card --}}
    <div class="card heatmap-card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-fire" style="color:#FF6B6B;"></i>
                Assessment Heatmap
            </h3>
        </div>
        <div class="card-body heatmap-container" style="margin-top:10px;">
            <table class="heatmap-table">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        @foreach($vendorHeatmap['categories'] as $category)
                            <th>{{ $category }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendorHeatmap['vendors'] as $vendor)
                        @php
                            $vendorData = $vendorHeatmap['matrix'][$vendor]['categories'] ?? [];
                        @endphp
                        <tr>
                            <td class="vendor-name">{{ $vendor }}</td>
                            @foreach($vendorHeatmap['categories'] as $category)
                                @php
                                    $categoryData = $vendorData[$category] ?? null;
                                    $indicator = strtolower($categoryData['indicator'] ?? '');
                                    $score = !empty($categoryData['score']) ? round($categoryData['score']) : '';

                                    switch ($indicator) {
                                        case 'high':
                                            $bgColor = '#4AD991';
                                            $textColor = 'white';
                                            break;
                                        case 'medium':
                                            $bgColor = '#FEC53D';
                                            $textColor = '#333';
                                            break;
                                        case 'low':
                                            $bgColor = '#FF6B6B';
                                            $textColor = 'white';
                                            break;
                                        default:
                                            $bgColor = '#f8f9fa';
                                            $textColor = '#aaa';
                                    }
                                @endphp
                                <td class="heatmap-cell" style="background:{{ $bgColor }}; color:{{ $textColor }};">
                                    {{ $score }}%
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="legend">
            <span><span class="legend-color" style="background:#4AD991;"></span> High</span>
            <span><span class="legend-color" style="background:#FEC53D;"></span> Medium</span>
            <span><span class="legend-color" style="background:#FF6B6B;"></span> Low</span>
        </div>
    </div>

</div>

{{-- Chart JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = {!! json_encode($vendorScoresChart['labels'] ?? []) !!};
    const scores = {!! json_encode($vendorScoresChart['scores'] ?? []) !!};
    const colors = {!! json_encode($vendorScoresChart['colors'] ?? []) !!};
    const levels = {!! json_encode($vendorScoresChart['levels'] ?? []) !!};

    new Chart(document.getElementById('vendorScoresChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: scores,
                backgroundColor: colors,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + '% (' + (levels[context.dataIndex] ?? '') + ')';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: value => value + '%' }
                }
            }
        }
    });
});
</script>

<style>
/* Reset & Base */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    background-color: #f4f7fb;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Filter */
.filter-wrapper {
    display: flex;
    justify-content: flex-end;
    max-width: 1400px;
    margin: 0 auto 20px auto;
    padding: 0 20px;
}

.filter-form {
    display: flex;
    gap: 12px;
}

.filter-select {
    padding: 10px 20px;
    border-radius: 30px;
    border: 1px solid #e2e8f0;
    background: white;
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
    cursor: pointer;
    outline: none;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.filter-select:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

/* Dashboard Grid */
.dashboard-grid {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px 40px 20px;
    display: flex;
    flex-direction: column;
    gap: 30px;
}

/* Card */
.card {
    background: white;
    border-radius: 24px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    box-shadow: 0 20px 30px -10px rgba(0,0,0,0.1);
}

.card-header {
    padding: 20px 24px 0 24px;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-body {
    padding: 20px 24px;
}

/* Chart Card */
.chart-card .card-body {
    min-height: 350px;
    position: relative;
}

.chart-container {
    width: 100%;
    height: 300px;
}

/* Heatmap Card */
.heatmap-container {
    overflow-x: auto;
    padding: 0 24px 20px 24px;
}

.heatmap-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    min-width: 600px;
}

.heatmap-table th {
    text-align: center;
    padding: 12px 8px;
    background: #ffffff;
    font-weight: 600;
    color: #334155;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.heatmap-table td {
    padding: 12px 8px;
    text-align: center;
    border: 1px solid #e9edf2;
}

.vendor-name {
    font-weight: 600;
    color: #1e293b;
    background-color: #ffffff;
    text-align: left;
    white-space: nowrap;
}

.heatmap-cell {
    font-weight: 700;
    font-size: 12px;
}

/* Legend */
.legend {
    padding: 16px 24px 20px 24px;
    background: #f8fafc;
    border-top: 1px solid #e9edf2;
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    font-size: 13px;
    color: #475569;
}

.legend-color {
    display: inline-block;
    width: 18px;
    height: 18px;
    border-radius: 6px;
    margin-right: 8px;
    vertical-align: middle;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-wrapper {
        justify-content: center;
    }
    .filter-form {
        width: 100%;
        justify-content: center;
    }
    .filter-select {
        flex: 1;
    }
}
</style>

@endsection