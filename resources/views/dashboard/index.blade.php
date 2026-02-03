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
        {{-- Total Category --}}
        <div style="background: #ffff; padding-top: 30px; padding-bottom: 30px; padding-left: 40px; padding-right: 40px; border-radius: 12px; display: flex;justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-direction: column; align-items: center;">
                <span class="text2">Total Category</span>
                <span class="text3">{{ number_format($totalCategories) }}</span>
            </div>
            <i class="fa-solid fa-layer-group icon-card" style="color: #8280FF;"></i>
        </div>

        {{-- Total Question --}}
        <div style="background: #ffff; padding-top: 30px; padding-bottom: 30px; padding-left: 40px; padding-right: 40px; border-radius: 12px; display: flex;justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-direction: column; align-items: center;">
               <span class="text2">Total Question</span>
                <span class="text3">{{ number_format($totalQuestions) }}</span>
            </div>
             <i class="fa-solid fa-circle-question icon-card" style="color: #FEC53D;"></i>
        </div>

        {{-- Total Assessment --}}
        <div style="background: #ffff; padding-top: 30px; padding-bottom: 30px; padding-left: 40px; padding-right: 40px; border-radius: 12px; display: flex;justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-direction: column; align-items: center;">
              <span class="text2">Total Assessment</span>
                <span class="text3">{{ number_format($totalAssessments) }}</span>
            </div>
            <i class="fa-solid fa-chart-simple icon-card" style="color: #4AD991;"></i>
        </div>
    </div>

    {{-- HEATMAP SECTION --}}
    <div style="margin-top: 40px; background: white; border-radius: 12px; padding: 30px;">
        <h2 style="margin-bottom: 20px; color: #333; font-weight: 600;">
            <i class="fa-solid fa-fire" style="color: #FF6B6B; margin-right: 10px;"></i>
            TPSA Heatmaps Analysis
        </h2>
        
        {{-- Heatmap Tabs --}}
        <ul class="nav nav-tabs" id="heatmapTabs" role="tablist" style="border-bottom: 2px solid #e9ecef;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab">
                    <i class="fa-solid fa-filter"></i> Category × Compliance
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab">
                    <i class="fa-solid fa-building"></i> Vendor × Compliance
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab3-tab" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab">
                    <i class="fa-solid fa-globe"></i> Global TPSA Matrix
                </button>
            </li>
        </ul>

        <div class="tab-content" id="heatmapContent" style="margin-top: 20px;">
            {{-- TAB 1: Category × Compliance --}}
            <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #555; font-weight: 600;">{{ $heatmap1['title'] }}</h4>
                    <p style="color: #777; font-size: 14px;">{{ $heatmap1['subtitle'] }}</p>
                </div>
                
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                        <thead>
                            <tr>
                                <th style="padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; text-align: center; min-width: 200px;">
                                    Level / Category
                                </th>
                                @foreach($heatmap1['xAxis'] as $category)
                                <th style="padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; text-align: center; font-size: 12px;">
                                    {{ $category }}
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $maxCount = 0;
                                foreach($heatmap1['data'] as $levelData) {
                                    $maxCount = max($maxCount, max($levelData));
                                }
                            @endphp
                            
                            @foreach($heatmap1['yAxis'] as $level)
                            <tr>
                                <td style="padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; font-weight: 600; text-align: center;">
                                    {{ $level }}
                                </td>
                                @foreach($heatmap1['xAxis'] as $category)
                                    @php
                                        $count = $heatmap1['data'][$level][$category] ?? 0;
                                        // Determine color intensity
                                        $intensity = $maxCount > 0 ? ($count / $maxCount) : 0;
                                        $red = 255;
                                        $green = 255 - (int)(200 * $intensity);
                                        $blue = 255 - (int)(200 * $intensity);
                                        $color = "rgb($red, $green, $blue)";
                                        
                                        // Text color based on intensity
                                        $textColor = $intensity > 0.5 ? 'white' : '#333';
                                    @endphp
                                <td style="padding: 15px; border: 1px solid #dee2e6; text-align: center; background-color: {{ $color }}; color: {{ $textColor }}; font-weight: {{ $intensity > 0.3 ? '600' : '400' }}; cursor: pointer;"
                                    title="{{ $count }} vendor(s) - {{ $category }} dengan {{ $level }}">
                                    {{ $count }}
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 13px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 20px; height: 20px; background: rgb(255, 255, 255); border: 1px solid #ddd; margin-right: 8px;"></div>
                            <span>0 vendors</span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 20px; height: 20px; background: rgb(255, 200, 200); border: 1px solid #ddd; margin-right: 8px;"></div>
                            <span>Beberapa vendors</span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 20px; height: 20px; background: rgb(255, 100, 100); border: 1px solid #ddd; margin-right: 8px;"></div>
                            <span>Banyak vendors</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: Vendor × Compliance --}}
            <div class="tab-pane fade" id="tab2" role="tabpanel">
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #555; font-weight: 600;">{{ $heatmap2['title'] }}</h4>
                    <p style="color: #777; font-size: 14px;">{{ $heatmap2['subtitle'] }}</p>
                </div>
                
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                        <thead>
                            <tr>
                                <th style="padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; text-align: center; min-width: 200px;">
                                    Level / Vendor
                                </th>
                                @foreach($heatmap2['xAxis'] as $vendor)
                                <th style="padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; text-align: center; font-size: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                                    {{ Str::limit($vendor, 20) }}
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heatmap2['yAxis'] as $level)
                            <tr>
                                <td style="padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; font-weight: 600; text-align: center;">
                                    {{ $level }}
                                </td>
                                @foreach($heatmap2['xAxis'] as $vendor)
                                    @php
                                        $hasVendor = $heatmap2['data'][$level][$vendor] ?? 0;
                                        $color = $hasVendor ? '#FF6B6B' : '#f8f9fa';
                                        $textColor = $hasVendor ? 'white' : '#aaa';
                                    @endphp
                                <td style="padding: 15px; border: 1px solid #dee2e6; text-align: center; background-color: {{ $color }}; color: {{ $textColor }}; font-weight: 600; cursor: pointer;"
                                    title="{{ $hasVendor ? "Vendor $vendor - $level" : "Tidak ada data" }}">
                                    {{ $hasVendor ? '●' : '○' }}
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 3: Category × Vendor (Global TPSA) --}}
            <div class="tab-pane fade" id="tab3" role="tabpanel">
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #555; font-weight: 600;">{{ $heatmap3['title'] }}</h4>
                    <p style="color: #777; font-size: 14px;">{{ $heatmap3['subtitle'] }}</p>
                </div>
                
                <div style="overflow-x: auto; max-height: 600px;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 1000px;">
                        <thead>
                            <tr>
                                <th style="padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; text-align: center; position: sticky; left: 0; z-index: 10; min-width: 200px;">
                                    Category / Vendor
                                </th>
                                @foreach($heatmap3['xAxis'] as $vendor)
                                <th style="padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; text-align: center; font-size: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; position: sticky; top: 0; z-index: 5;">
                                    {{ Str::limit($vendor, 15) }}
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heatmap3['yAxis'] as $category)
                            <tr>
                                <td style="padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; font-weight: 600; text-align: left; position: sticky; left: 0; z-index: 2; min-width: 200px;">
                                    {{ $category }}
                                </td>
                                @foreach($heatmap3['xAxis'] as $vendor)
                                    @php
                                        $score = $heatmap3['data'][$category][$vendor] ?? 0;
                                        $color = '#f8f9fa'; // Default
                                        if ($score > 0) {
                                            if ($score >= 80) $color = '#4AD991';
                                            elseif ($score >= 50) $color = '#FEC53D';
                                            else $color = '#FF6B6B';
                                        }
                                        $textColor = $score > 0 ? (($score >= 80 || $score < 50) ? 'white' : '#333') : '#aaa';
                                    @endphp
                                <td style="padding: 12px; border: 1px solid #dee2e6; text-align: center; background-color: {{ $color }}; color: {{ $textColor }}; font-weight: 600; cursor: pointer;"
                                    title="{{ $score > 0 ? "$category - $vendor: Score $score" : 'No data' }}">
                                    {{ $score > 0 ? $score : '-' }}
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 13px;">
                    <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 20px; height: 20px; background: #4AD991; border: 1px solid #ddd; margin-right: 8px;"></div>
                            <span>Sangat Memadai (≥80)</span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 20px; height: 20px; background: #FEC53D; border: 1px solid #ddd; margin-right: 8px;"></div>
                            <span>Cukup Memadai (50-79)</span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 20px; height: 20px; background: #FF6B6B; border: 1px solid #ddd; margin-right: 8px;"></div>
                            <span>Kurang Memadai (<50)</span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 20px; height: 20px; background: #f8f9fa; border: 1px solid #ddd; margin-right: 8px;"></div>
                            <span>Tidak ada data</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
/* Heatmap Styles */
.nav-tabs .nav-link {
    padding: 12px 24px;
    font-weight: 600;
    color: #555;
    border: none;
    background: #f8f9fa;
    margin-right: 5px;
    border-radius: 8px 8px 0 0;
}

.nav-tabs .nav-link.active {
    background: #8280FF;
    color: white;
}

.nav-tabs .nav-link:hover {
    background: #e9ecef;
    color: #8280FF;
}

.nav-tabs .nav-link.active:hover {
    background: #8280FF;
    color: white;
}

.tab-content {
    padding: 20px 0;
}

table {
    font-size: 13px;
}

table th {
    font-weight: 600;
    color: #555;
}

table td:hover {
    transform: scale(1.05);
    transition: transform 0.2s;
    z-index: 1;
    position: relative;
}

/* Scrollbar styling */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const triggerTabList = [].slice.call(document.querySelectorAll('#heatmapTabs button'))
    triggerTabList.forEach(function (triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl)
        
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault()
            tabTrigger.show()
        })
    })
    
    // Add tooltips to heatmap cells
    document.querySelectorAll('table td[title]').forEach(cell => {
        cell.addEventListener('mouseenter', function(e) {
            const title = this.getAttribute('title');
            // You can enhance this with a custom tooltip if needed
        });
    });
});
</script>

@endsection