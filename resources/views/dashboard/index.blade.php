@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Dashboard">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div class="assessment-page">

    {{-- SUMMARY CARD --}}
    <div class="summary-row">
        <div style="background: #fff; padding: 30px 40px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-direction: column; align-items: center;">
                <span class="text2">Total Category</span>
                <span class="text3">{{ number_format($totalCategories) }}</span>
            </div>
            <i class="fa-solid fa-layer-group icon-card" style="color: #8280FF;"></i>
        </div>

        <div style="background: #fff; padding: 30px 40px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-direction: column; align-items: center;">
               <span class="text2">Total Question</span>
                <span class="text3">{{ number_format($totalQuestions) }}</span>
            </div>
             <i class="fa-solid fa-circle-question icon-card" style="color: #FEC53D;"></i>
        </div>

        <div style="background: #fff; padding: 30px 40px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-direction: column; align-items: center;">
              <span class="text2">Total Assessment</span>
                <span class="text3">{{ number_format($totalAssessments) }}</span>
            </div>
            <i class="fa-solid fa-chart-simple icon-card" style="color: #4AD991;"></i>
        </div>
    </div>

    {{-- CHART SECTION --}}
    <div style="margin-top: 40px; background: white; border-radius: 12px; padding: 30px;">
        <h2 style="margin-bottom: 20px; color: #333; font-weight: 600;">
            <i class="fa-solid fa-chart-bar" style="color: #8280FF; margin-right: 10px;"></i>
            Vendor Performance Chart
        </h2>
        <p style="color: #777; margin-bottom: 20px;">Diagram batang menunjukkan total score yang diperoleh setiap vendor dalam assessment</p>
        <div style="height: 400px;">
            <canvas id="vendorScoresChart"></canvas>
        </div>
        {{-- LEGEND --}}
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 13px;">
            <div style="display: flex; align-items: center; gap: 25px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 24px; height: 24px; border-radius: 4px; background: #4AD991; margin-right: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;"></div>
                    <span>High / Sangat Memadai</span>
                </div>
                <div style="display: flex; align-items: center;">
                    <div style="width: 24px; height: 24px; border-radius: 4px; background: #ffc107; margin-right: 8px; display: flex; align-items: center; justify-content: center; color: #333; font-size: 14px;"></div>
                    <span>Medium / Cukup Memadai</span>
                </div>
                <div style="display: flex; align-items: center;">
                    <div style="width: 24px; height: 24px; border-radius: 4px; background: #FF6B6B; margin-right: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;"></div>
                    <span>Low / Kurang Memadai</span>
                </div>
            </div>
        </div>
    </div>

    {{-- HEATMAP SECTION --}}
    <div style="margin-top: 40px; background: white; border-radius: 12px; padding: 30px;">
        <h2 style="margin-bottom: 20px; color: #333; font-weight: 600;">
            <i class="fa-solid fa-fire" style="color: #FF6B6B; margin-right: 10px;"></i>
            {{ $vendorHeatmap['title'] }}
        </h2>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left; font-weight: 600; min-width: 120px;">Vendor</th>
                        @foreach($vendorHeatmap['categories'] as $category)
                        <th style="padding: 12px; border: 1px solid #dee2e6; text-align: center; font-weight: 600; min-width: 150px;">{{ $category }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
@foreach($vendorHeatmap['vendors'] as $vendor)
    @php
        $vendorData = $vendorHeatmap['matrix'][$vendor]['categories'] ?? [];
    @endphp

    <tr>
        <td style="padding: 12px; border: 1px solid #dee2e6; font-weight: 600;">
            {{ $vendor }}
        </td>

        @foreach($vendorHeatmap['categories'] as $category)
            @php
                $categoryData = $vendorData[$category] ?? null;
                $indicator = strtolower($categoryData['indicator'] ?? '');

                switch ($indicator) {
                    case 'high':
                        $color = '#4AD991';
                        $label  = 'High / Sangat Memadai';
                        $textColor = 'white';
                        break;

                    case 'medium':
                        $color = '#FEC53D';
                        $label  = 'Medium / Cukup Memadai';
                        $textColor = '#333';
                        break;

                    case 'low':
                        $color = '#FF6B6B';
                        $label  = 'Low / Kurang Memadai';
                        $textColor = 'white';
                        break;

                    default:
                        $color = '#f8f9fa';
                        $label  = 'Tidak ada data';
                        $textColor = '#aaa';
                }
            @endphp

            <td style="
                padding: 12px;
                border: 1px solid #dee2e6;
                text-align: center;
                background-color: {{ $color }};
                color: {{ $textColor }};
                
            " title="{{ $category }}: {{ $label }}">
                <div style="font-size: 18px;">

                @if(!empty($categoryData['score']))
                    <div style="font-size: 11px; margin-top: 4px;font-weight: 700;">
                        {{ round($categoryData['score']) }}%
                    </div>
                @endif
                </div>
            </td>
        @endforeach
    </tr>
@endforeach
</tbody>

            </table>
        </div>
        
        {{-- LEGEND --}}
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 13px;">
            <div style="display: flex; align-items: center; gap: 25px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 24px; height: 24px; border-radius: 4px; background: #4AD991; margin-right: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">🟢</div>
                    <span>High / Sangat Memadai</span>
                </div>
                <div style="display: flex; align-items: center;">
                    <div style="width: 24px; height: 24px; border-radius: 4px; background: #ffc107; margin-right: 8px; display: flex; align-items: center; justify-content: center; color: #333; font-size: 14px;">🟡</div>
                    <span>Medium / Cukup Memadai</span>
                </div>
                <div style="display: flex; align-items: center;">
                    <div style="width: 24px; height: 24px; border-radius: 4px; background: #FF6B6B; margin-right: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">🔴</div>
                    <span>Low / Kurang Memadai</span>
                </div>
                <div style="display: flex; align-items: center;">
                    <div style="width: 24px; height: 24px; border-radius: 4px; background: #f8f9fa; border: 1px solid #ddd; margin-right: 8px; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 14px;">○</div>
                    <span>Tidak ada data</span>
                </div>
            </div>
        </div>
    </div>
</div>

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
                            return `Total Score: ${value} (${level})`;
                        },
                        title: function(context) { return context[0].label; }
                    },
                    backgroundColor: 'rgba(0,0,0,0.7)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 10
                }
            },
            scales: { y: { beginAtZero: true, max: 100 }, x: { grid: { display: false } } }
        }
    });
});
</script>

<style>
.assessment-page table {
    border: 1px solid #dee2e6;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}
.assessment-page th { background-color: #f8f9fa; color: #495057; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
.assessment-page td { transition: background-color 0.2s ease; }
.assessment-page td:hover { filter: brightness(0.95); }
.assessment-page tr:nth-child(even) { background-color: #fafafa; }
.assessment-page tr:hover { background-color: #f5f5f5; }

.summary-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
.text2 { font-size: 14px; color: #6c757d; font-weight: 500; }
.text3 { font-size: 32px; font-weight: 700; color: #333; margin-top: 5px; }
.icon-card { font-size: 40px; opacity: 0.8; }
#vendorScoresChart { width: 100% !important; height: 100% !important; }
</style>

@endsection
