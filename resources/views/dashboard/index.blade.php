@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Dashboard">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div class="assessment-page">
    {{-- Tombol Export dan Filter --}}
    <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
        <button id="exportPpt" class="btn btn-primary">
            Export Dashboard to PPT
        </button>

        <form method="GET" action="{{ route('dashboard.index') }}" class="filter-bar">
    <div class="filter-group">
        <select name="year" onchange="this.form.submit()">
            <option value="all">Semua Tahun</option>
            @foreach(range(now()->year, now()->year - 5) as $y)
                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endforeach
        </select>
    </div>
</form>
    </div>

    {{-- LAYOUT UTAMA: 2 KOLOM --}}
    <div class="dashboard-layout" style="display: grid; grid-template-columns: 5fr 1fr; gap: 20px; align-items: start; ">

        {{-- KOLOM KIRI (LEBAR) --}}
        <div class="left-column" style="display: grid; grid-template-rows: auto auto; gap: 20px;">

            <div style="display:flex; flex-direction:row; gap:20px; width:100%;">

    {{-- TOTAL VENDOR KESELURUHAN --}}
    <div id="card-total-vendor"
         class="summary-card export-card"
         style="flex:1;
                background:#fff;
                padding:20px;
                border-radius:12px;
                box-shadow:0 2px 8px rgba(0,0,0,0.05);
                display:flex;
                justify-content:space-between;
                align-items:center;
                ">
                <i class="fa-solid fa-building"
           style="color:#8280FF; font-size:40px; margin-right:20px;">
        </i>

        <div style="display:flex; flex-direction:column; gap:4px; width:100%  ">
        <span style="font-size:14px; color:#6c757d;">Total Assessment</span>

        <div style="display:flex; flex-direction:row; gap:0;">

    <span style="font-size:32px; font-weight:700; line-height:1;">
        {{ $activeVendor }}
    </span>

    <span style="font-size:12px; color:#9e9e9e; align-self:flex-end">
        ( {{ $inactiveVendor ?? 0 }} inactive )
    </span>

</div>
    </div>

        
    </div>

    {{-- AVERAGE SCORE --}}
    <div id="card-avg-score"
         class="summary-card export-card"
         style="flex:1;
                background:#fff;
                padding:20px;
                border-radius:12px;
                box-shadow:0 2px 8px rgba(0,0,0,0.05);
                display:flex;
                justify-content:space-between;
                align-items:center;">

        <i class="fa-solid fa-star"
           style="color:#FEC53D; font-size:40px; margin-right:10px;">
        </i>

        <div style="display:flex; flex-direction:column; gap:4px; width:100%;">
            <span style="font-size:14px; color:#6c757d;">Rata-rata Score</span>
            <span style="font-size:32px; font-weight:700;">{{ $averageScore }}%</span>
        </div>

        
    </div>

</div>

            {{-- BARIS 2: BAR CHART (KIRI) dan HEATMAP (KANAN) --}}
            <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 10px; ">

     <div id="card-summary-risk" style="
    display:flex;
    flex-direction:column;
    gap:12px;
    width:100%;
    max-width:320px;
">

@php
$riskStyle = [
    'Sangat Memadai' => ['bg'=>'#E8FAF0','color'=>'#2FBF71'],
    'Memadai' => ['bg'=>'#FFF4E0','color'=>'#FEC53D'],
    'Kurang Memadai' => ['bg'=>'#FFE5E5','color'=>'#FF6B6B'],
];
@endphp

@foreach($summaryRisk as $label => $count)

@php
$style = $riskStyle[$label] ?? ['bg'=>'#f5f5f5','color'=>'#888'];
@endphp

<div style="
    background:#fff;
    border-radius:14px;
    padding-bottom:20px;
    box-shadow:0 2px 8px rgba(0,0,0,0.06);
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:12px;
">

    <span style="
    font-size:15px;
    font-weight:600;
    width:100%;
    height:40px;
    padding:4px 10px;
    border-radius:8px;
    background:{{ $style['bg'] }};
    color:{{ $style['color'] }};
    display:flex;
    align-items:center;
    justify-content:center;
">
    {{ $label }}
</span>

    <span style="
        font-size:32px;
        font-weight:700;
        line-height:1;
        text-align:center;
    ">
        {{ $count }}
    </span>

</div>

@endforeach

</div>                

                {{-- HEATMAP RISK TIER --}}
                <div id="card-chart-heatmap" class="heatmap-card export-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <div class="chart-header">
                        <h3 style="font-size: 16px; font-weight: 600; display: flex; align-items: center;">
                            <i class="fa-solid fa-fire" style="color:#FF6B6B; margin-right:10px;"></i>
                            TPSA Risk Tier Heatmap
                        </h3>
                    </div>
                    <div class="heatmap-body" style="overflow: auto;">
                        <table class="heatmap-table" style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead>
                                <tr>
                                    <th style="padding: 8px; border: 1px solid #dee2e6; background: #f8f9fa;">Risk Level</th>
                                    @foreach(array_keys($heatmapRiskTier ?? []) as $tier)
                                        <th style="padding: 8px; border: 1px solid #dee2e6; background: #f8f9fa;">{{ $tier }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($heatmapRiskTier ?? [] as $riskLevel => $tiers)
                                <tr>
                                    <td class="vendor-name" style="font-weight: 600; background: #f8f9fa; padding: 8px; border: 1px solid #dee2e6;">{{ $riskLevel }}</td>
                                    @foreach($tiers as $tierData)
                                        @php
                                            $count = $tierData['count'] ?? 0;
                                            $color = $tierData['color'] ?? '#ffffff';
                                            $vendors = $tierData['vendors'] ?? [];
                                            $tooltip = empty($vendors) ? 'No vendor' : implode("\n", $vendors);
                                            $textColor = (array_sum(sscanf($color, "#%02x%02x%02x")) / 3 < 128) ? '#fff' : '#000';
                                        @endphp
                                        <td class="heatmap-cell" style="background: {{ $color }}; color: {{ $textColor }}; padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: 700;" title="{{ $tooltip }}">
                                            {{ $count }}
                                        </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{-- Gradient Legend --}}
                    <div class="heatmap-gradient-legend" style="width: 100%; margin-top: 15px;">
                        <div class="gradient-bar" style="height: 18px; border-radius: 6px; background: linear-gradient(to right, {{ $legendGradient['low'] ?? '#FF6B6B' }}, {{ $legendGradient['medium'] ?? '#FEC53D' }}, {{ $legendGradient['high'] ?? '#4AD991' }});"></div>
                        <div class="gradient-labels" style="display: flex; justify-content: space-between; font-size: 12px; margin-top: 4px; color: #666;">
                            <span>0</span>
                            <span>{{ round($legendMax / 2) }}</span>
                            <span>{{ round($legendMax * 0.8) }}</span>
                            <span>{{ $legendMax }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN (SEMPIT) --}}
        <div class="right-column" style="display: grid; grid-template-rows: auto auto auto; gap: 20px;">
            <div id="card-chart-pie" class="chart-card export-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div class="chart-header">
                    <h3 style="font-size: 16px; font-weight: 600; display: flex; align-items: center;">
                        <i class="fa-solid fa-chart-pie" style="color: #8280FF; margin-right: 10px;"></i>
                        Status Penilaian Tahun Ini
                    </h3>
                </div>
                <div class="chart-body" style="height: 150px;">
                    <canvas id="pieComparisonChart"></canvas>
                </div>
            </div>

            {{-- BAR CHART TIER --}}
                <div id="card-chart-bar" class="chart-card export-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <div class="chart-header">
                        <h3 style="font-size: 16px; font-weight: 600; display: flex; align-items: center;">
                            <i class="fa-solid fa-chart-bar" style="color: #8280FF; margin-right: 10px;"></i>
                            Jumlah Vendor per Tier
                        </h3>
                    </div>
                    <div class="chart-body" style="height: 160px;">
                        <canvas id="barTierChart"></canvas>
                    </div>
                </div>
            
        </div>
    </div>
</div>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // ===================== BAR CHART TIER =====================
        const barTierData = @json($barTier);
        if (barTierData.labels && barTierData.values) {
            new Chart(document.getElementById('barTierChart'), {
                type: 'bar',
                data: {
                    labels: barTierData.labels,
                    datasets: [{
                        label: 'Jumlah Vendor',
                        data: barTierData.values,
                        backgroundColor: ['#FF6B6B', '#FEC53D', '#4AD991'],
                        borderColor: '#ddd',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // MEMBUAT BAR CHART VERTIKAL (sesuai permintaan: sumbu Y nilai, sumbu X tier)
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => `${ctx.raw} vendor` } }
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { display: false } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }

        // ===================== PIE CHART COMPARISON =====================
        const pieComparison = @json($pieComparison);
        if (pieComparison.labels && pieComparison.values) {
            const total = pieComparison.values.reduce((a, b) => a + b, 0);
            new Chart(document.getElementById('pieComparisonChart'), {
                type: 'pie',
                data: {
                    labels: pieComparison.labels,
                    datasets: [{
                        data: pieComparison.values,
                        backgroundColor: ['#4AD991', '#FF6B6B'],
                        borderWidth: 1
                    }]
                },
                options: {
    responsive: true,
    maintainAspectRatio: false, // ⭐ WAJIB kalau mau kontrol size manual

    plugins: {
    legend: {
        position: 'right', // ⭐ bikin legend di samping
        align: 'center',
        labels: {
            boxWidth: 12,
            padding: 15,
            font: {
                size: 12
            }
        }
    },
    datalabels: {
        color: '#fff',
        font: { weight: 'bold', size: 12 },
        formatter: (value) => {
            if (!value) return '';
            const pct = ((value / total) * 100).toFixed(1);
            return `${value}\n(${pct}%)`;
        }
    }
}
},
                plugins: [ChartDataLabels]
            });
        }
    });

    // ===================== EXPORT PPT =====================
    document.getElementById('exportPpt').addEventListener('click', async () => {

    const btn = document.getElementById('exportPpt');
    btn.disabled  = true;
    btn.innerText = 'Generating…';

    // Peta id kartu → kunci yang dikirim ke controller
    const cardMap = {
        'card-total-vendor'   : 'total',
        'card-avg-score'      : 'avg',
        'card-chart-pie'      : 'pie',
        'card-chart-bar'      : 'bar',
        'card-chart-heatmap'  : 'heatmap',
        'card-summary-risk'   : 'summary',   // ← BARU
    };

    const images = {};

    for (const [id, key] of Object.entries(cardMap)) {
        const el = document.getElementById(id);
        if (!el) continue;
        try {
            const canvas = await html2canvas(el, {
                scale          : 2,
                useCORS        : true,
                backgroundColor: null,        // transparent agar bg kartu ikut ter-capture
                logging        : false,
            });
            images[key] = canvas.toDataURL('image/png');
        } catch (e) {
            console.warn('html2canvas failed for', id, e);
        }
    }

    fetch("{{ route('dashboard.export.ppt') }}", {
        method : 'POST',
        headers: {
            'Content-Type' : 'application/json',
            'X-CSRF-TOKEN' : '{{ csrf_token() }}',
        },
        body: JSON.stringify({
            images,
            year: '{{ $selectedYear }}',
        }),
    })
    .then(res => {
        if (!res.ok) throw new Error('Server error ' + res.status);
        return res.blob();
    })
    .then(blob => {
        const url      = URL.createObjectURL(blob);
        const anchor   = document.createElement('a');
        anchor.href    = url;
        anchor.download = 'Dashboard-TPSA-{{ now()->format("Ymd") }}.pptx';
        anchor.click();
        URL.revokeObjectURL(url);
    })
    .catch(err => {
        alert('Export gagal: ' + err.message);
    })
    .finally(() => {
        btn.disabled  = false;
        btn.innerText = 'Export Dashboard to PPT';
    });
});
</script>

<style>
    /* Gaya tambahan untuk memastikan tampilan rapi */
    .assessment-page {
        padding: 20px;
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
    .chart-card, .heatmap-card, .summary-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .chart-card:hover, .heatmap-card:hover, .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .heatmap-table th, .heatmap-table td {
        white-space: normal;
        word-break: break-word;
    }
    #barTierChart {
    height: 180px !important;
    max-height: 170px;
}
    #pieComparisonChart {
        max-height: 250px;
    }
</style>

@endsection