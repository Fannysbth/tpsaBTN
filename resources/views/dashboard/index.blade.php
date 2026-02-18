@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Dashboard">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div class="assessment-page">
    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
    <button id="exportPpt" class="btn btn-primary">
    Export Dashboard to PPT
</button>
    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <select name="month" onchange="this.form.submit()">
                <option value="all">All Month</option>
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                    </option>
                @endforeach
            </select>

            <select name="year" onchange="this.form.submit()">
                <option value="all">All Year</option>
                @foreach(range(now()->year, now()->year - 5) as $y)
                    <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                        {{ $y }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>
    </div>    

    {{-- SUMMARY CARD --}}
    <div class="summary-row">
        <div  id="card-summary-1" class="summary-card export-card">
            <div class="card-content">
                <span class="text2">Not Yet Scored</span>
                <span class="text3">{{ number_format($totalWithoutRiskLevel) }}</span>
            </div>
            <i class="fa-solid fa-layer-group icon-card" style="color: #8280FF;"></i>
        </div>

        <div  id="card-summary-2" class="summary-card export-card">
            <div class="card-content">
               <span class="text2">Scored</span>
                <span class="text3">{{ number_format($totalWithRiskLevel) }}</span>
            </div>
             <i class="fa-solid fa-circle-question icon-card" style="color: #FEC53D;"></i>
        </div>

        <div  id="card-summary-3" class="summary-card export-card">
            <div class="card-content">
              <span class="text2">Total Assessment</span>
                <span class="text3">{{ number_format($totalAssessments) }}</span>
            </div>
            <i class="fa-solid fa-chart-simple icon-card" style="color: #4AD991;"></i>
        </div>
    </div>

    {{-- CHART CONTAINER --}}
    <div class="chart-container">
        {{-- VENDOR PERFORMANCE CHART --}}
        <div id="card-chart-1" class="chart-card export-card">
            <div class="chart-header">
                <h3>
                    <i class="fa-solid fa-chart-bar" style="color: #8280FF; margin-right: 10px;"></i>
                    Performance Chart
                </h3>
                        </div>
            <div class="chart-body">
                <canvas id="vendorScoresChart"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-color high"></div>
                    <span>Sangat Memadai</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color medium"></div>
                    <span>Memadai</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color low"></div>
                    <span>Kurang Memadai</span>
                </div>
            </div>
        </div>

        {{-- HEATMAP SECTION --}}
        <div id="card-chart-2" class="heatmap-card export-card">
            <div class="chart-header">
                <h3>
                    <i class="fa-solid fa-fire" style="color: #FF6B6B; margin-right: 10px;"></i>
                    {{ $vendorHeatmap['title'] }}
                </h3>
            </div>
            
            <div class="heatmap-body">
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
                                        
                                        switch ($indicator) {
                                            case 'high':
                                                $color = '#4AD991';
                                                $label = 'High / Sangat Memadai';
                                                $textColor = 'white';
                                                break;
                                            case 'medium':
                                                $color = '#FEC53D';
                                                $label = 'Medium / Cukup Memadai';
                                                $textColor = '#333';
                                                break;
                                            case 'low':
                                                $color = '#FF6B6B';
                                                $label = 'Low / Kurang Memadai';
                                                $textColor = 'white';
                                                break;
                                            default:
                                                $color = '#f8f9fa';
                                                $label = 'Tidak ada data';
                                                $textColor = '#aaa';
                                        }
                                    @endphp
                                    <td class="heatmap-cell" 
                                        style="background-color: {{ $color }}; color: {{ $textColor }};"
                                        title="{{ $category }}: {{ $label }}">
                                        @if(!empty($categoryData['score']))
                                            {{ round($categoryData['score']) }}%
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-color high"></div>
                    <span>High </span>
                </div>
                <div class="legend-item">
                    <div class="legend-color medium"></div>
                    <span>Medium </span>
                </div>
                <div class="legend-item">
                    <div class="legend-color low"></div>
                    <span>Low </span>
                </div>
                <div class="legend-item">
                    <div class="legend-color no-data"></div>
                    <span>Tidak ada data</span>
                </div>
            </div>
        </div>
    </div>
    

</div>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
document.getElementById('exportPpt').addEventListener('click', async () => {

    const cardIds = [
        'card-summary-1',
        'card-summary-2',
        'card-summary-3',
        'card-chart-1',
        'card-chart-2'
    ];

    const images = [];

    for (const id of cardIds) {
        const el = document.getElementById(id);
        if (!el) continue;

        const canvas = await html2canvas(el, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff'
        });

        images.push(canvas.toDataURL('image/png'));
    }

    fetch("{{ route('dashboard.export.ppt') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
       body: JSON.stringify({
        images,
        month: "{{ $selectedMonth }}",
        year: "{{ $selectedYear }}"
    })
    })
    .then(res => res.blob())
    .then(blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'dashboard-report.pptx';
        a.click();
    });
});
</script>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colors = {!! json_encode($vendorScoresChart['colors'] ?? [], JSON_THROW_ON_ERROR) !!};
    const labels = {!! json_encode($vendorScoresChart['labels'] ?? [], JSON_THROW_ON_ERROR) !!};
    const scores = {!! json_encode($vendorScoresChart['scores'] ?? [], JSON_THROW_ON_ERROR) !!};
    const levels = {!! json_encode($vendorScoresChart['levels'] ?? [], JSON_THROW_ON_ERROR) !!};

    const hoverColors = colors.map(color => color === '#f8f9fa' ? '#e9ecef' : color);

    const chartData = {
        labels: labels,
        datasets: [{
            label: 'Total Score',
            data: scores,
            backgroundColor: colors,
            borderColor: '#ddd',
            borderWidth: 1,
            borderRadius: 4,
            hoverBackgroundColor: hoverColors,
        }]
    };

    new Chart(document.getElementById('vendorScoresChart').getContext('2d'), {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed.y;
                            const level = levels[context.dataIndex] ?? '';
                            return `Total Score: ${value}% (${level})`;
                        },
                        title: function(context) { return context[0].label; }
                    },
                    backgroundColor: 'rgba(0,0,0,0.7)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 10
                }
            },
            scales: { 
                y: { 
                    beginAtZero: true, 
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }, 
                x: {
                    ticks: {
                        font: { 
                            size: 10 
                        }
                    },
                    grid: { 
                        display: false 
                    }
                } 
            }
        }
    });
});
</script>

<style>


/* FILTER STYLES */
.filter-bar {
    display: flex;
    justify-content: flex-end;
}

.filter-group {
    display: flex;
    gap: 12px;
}

.filter-group select {
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 14px;
    background-color: white;
    cursor: pointer;
    min-width: 150px;
}

.filter-group select:focus {
    outline: none;
    border-color: #8280FF;
}

/* SUMMARY CARDS */
.summary-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 10px;
}

.summary-card {
    background: #fff;
    padding: 15px;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.text2 {
    font-size: 14px;
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 4px;
}

.text3 {
    font-size: 32px;
    font-weight: 700;
    color: #333;
}

.icon-card {
    font-size: 40px;
    opacity: 0.8;
}

/* CHART CONTAINER */
.chart-container {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 24px;
    align-items: stretch;
}

.chart-card, .heatmap-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.chart-header {
    margin-bottom: 20px;
}

.chart-header h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    display: flex;
    align-items: center;
}

.chart-header p {
    color: #777;
    font-size: 13px;
    line-height: 1.5;
}

.chart-body {
    flex: 1;
    min-height: 200px;
    position: relative;
}

.heatmap-body {
    flex: 1;
    overflow: auto;
}

/* HEATMAP TABLE */
.heatmap-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    min-width: 500px;
}

.heatmap-table thead {
    background: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
}

.heatmap-table th {
    padding: 8px 6px;
    border: 1px solid #dee2e6;
    text-align: center;
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.3px;

    white-space: normal;     /* ⬅️ BOLEH TURUN BARIS */
    word-break: break-word; /* ⬅️ POTONG KATA PANJANG */
    line-height: 1.3;

    max-width: 90px;         /* ⬅️ PAKSA TIDAK MELEBAR */
    font-size: 11px;         /* ⬅️ LEBIH KECIL */
}


.heatmap-table td {
    padding: 10px 8px;
    border: 1px solid #dee2e6;
    text-align: center;
    transition: filter 0.2s ease;
}

.heatmap-table td:hover {
    filter: brightness(0.95);
}

.vendor-name {
    font-weight: 600;
    background-color: #f8f9fa;
    position: sticky;
    left: 0;
    z-index: 5;
}

.heatmap-cell {
    min-width: 60px;
    font-weight: 700;
    font-size: 11px;
}

/* LEGEND STYLES */
.chart-legend {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 12px;
}

.chart-legend {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.legend-color.high {
    background: #4AD991;
}

.legend-color.medium {
    background: #FEC53D;
}

.legend-color.low {
    background: #FF6B6B;
}

.legend-color.no-data {
    background: #f8f9fa;
    border: 1px solid #ddd;
}

/* RESPONSIVE DESIGN */
@media (max-width: 1200px) {
    .chart-container {
        grid-template-columns: 1fr;
        height: auto;
    }
    
    .chart-body {
        height: 300px;
    }
}

@media (max-width: 768px) {
    .summary-row {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .chart-container {
        gap: 16px;
    }
    
    .chart-card, .heatmap-card {
        padding: 16px;
    }
    
    .filter-group {
        flex-direction: column;
        width: 100%;
    }
    
    .filter-group select {
        width: 100%;
    }
}
</style>

@endsection