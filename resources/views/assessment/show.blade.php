@extends('layouts.app')

@section('title', 'Assessment Detail')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Assessment">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div style="background: #F5F6FA; padding: 20px;">
    {{-- Modal untuk menampilkan error import --}}
@if(session('import_errors'))
<div class="modal fade" id="importErrorModal" tabindex="-1" aria-labelledby="importErrorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="importErrorModalLabel">Import Errors</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul>
          @foreach(session('import_errors') as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- Script untuk otomatis tampil --}}
@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var importErrorModal = new bootstrap.Modal(document.getElementById('importErrorModal'));
        importErrorModal.show();
    });
</script>
@endpush
@endif


    {{-- COMPANY INFO --}}

    <div class="company-header">
        <div class="company-header-left">
            <h2>{{ $assessment->company_name }}</h2>
            <p>
    @if($assessment->evaluated_at)
        Latest Update: {{ $assessment->evaluated_at->format('d/m/Y') }}
    @else
        Assessment Date: {{ $assessment->assessment_date->format('d/m/Y') }}
    @endif
</p>
        </div>
        <div class="company-header-right">
            <div style="display:flex; gap:10px; align-items:center;">

    {{-- BACK --}}
    <a href="{{ route('assessment.index') }}"
       style="
           height:40px;
           display:flex;
           align-items:center;
           justify-content:center;
           padding:0 22px;
           border-radius:6px;
           border:2px solid #4880FF;
           color:#4880FF;
           font-weight:bold;
           text-decoration:none;
           min-width:120px;
       ">
        Back to List
    </a>

    {{-- UPLOAD --}}
    <form action="{{ route('assessment.import', $assessment->id) }}"
          method="POST"
          enctype="multipart/form-data"
          style="margin:0;">

        @csrf

        <label class="btn btn-sm btn-warning"
               style="
                   height:40px;
                   display:flex;
                   align-items:center;
                   justify-content:center;
                   padding:0 22px;
                   border-radius:6px;
                   font-weight:bold;
                   cursor:pointer;
                   min-width:120px;
               ">

            Upload

            <input type="file"
                   name="excel_file"
                   accept=".xlsx,.xls"
                   style="display:none;"
                   onchange="this.form.submit()">
        </label>

    </form>

    {{-- DELETE --}}
    <form action="{{ route('assessment.destroy', $assessment) }}"
          method="POST"
          onsubmit="return confirm('Are you sure you want to delete this assessment?');"
          style="margin:0;">

        @csrf
        @method('DELETE')

        <button type="submit"
                style="
                    height:40px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    padding:0 22px;
                    border-radius:6px;
                    border:none;
                    background:#FF4D4F;
                    color:white;
                    font-weight:bold;
                    cursor:pointer;
                    min-width:120px;
                ">
            Delete
        </button>

    </form>

</div>

        </div>
    </div>

    {{-- DETAIL ASSESSMENT --}}
<div style="background:#FFF; border-radius:14px; padding:40px; margin-bottom:20px; box-shadow:6px 6px 54px #0000000D;">
    
    {{-- HEADER --}}
    <div style="display:flex; justify-content:space-between; align-items:center; ">
        <h3 style="font-size:24px; font-weight:bold; margin:0;">
            Detail Assessment
        </h3>
        

        {{-- EDIT BUTTON (SELALU ADA) --}}
        <a href="{{ route('assessment.edit', $assessment) }}"
           style="
               height:40px;
               display:flex;
               align-items:center;
               justify-content:center;
               padding:0 20px;
               border-radius:6px;
               background:#4880FF;
               color:white;
               font-weight:bold;
               text-decoration:none;
           
           ">
            Edit
        </a>
    </div>
    <div style="width:227px; height:2px; background:#4880FF; margin:8px 0 20px;"></div>

    @if($assessment->answers->isNotEmpty() && !is_null($assessment->total_score))

    <div style="display:flex; gap:60px;">

        {{-- LEFT COLUMN --}}
        <div style="flex:1; display:flex; flex-direction:column; gap:16px;">
            
            
                <div>
    <strong>Vendor Status</strong><br>

    @php
        $status = strtolower($assessment->vendor_status ?? '');
    @endphp

    @if($status === 'active')
        <span style="display:inline-flex; align-items:center; gap:8px; font-weight:500;">
            <span style="
                width:10px;
                height:10px;
                background:#22C55E;
                border-radius:50%;
                display:inline-block;
            "></span>
            <span style="color:#22C55E;">Active</span>
        </span>

    @elseif($status === 'inactive')
        <span style="display:inline-flex; align-items:center; gap:8px; font-weight:500;">
            <span style="
                width:10px;
                height:10px;
                background:#9CA3AF;
                border-radius:50%;
                display:inline-block;
            "></span>
            <span style="color:#6B7280;">Inactive</span>
        </span>

    @else
        <span>-</span>
    @endif
</div>
            

            <div>
                <strong>Tier Criticality</strong><br>
                <span>{{ $assessment->tier_criticality ?? '-' }}</span>
            </div>

        </div>

        {{-- RIGHT COLUMN --}}
        <div style="flex:1; display:flex; flex-direction:column; gap:16px;">

            <div>
                <strong>Assessor</strong><br>
                <span>{{ $assessment->assessor ?? '-' }}</span>
            </div>

            <div>
                <strong>Score</strong><br>
                <span>{{ $assessment->total_score ?? '-' }}%</span>
            </div>

            <div>
                <strong>Risk Level</strong><br>
                <span>
                    {{ $assessment->risk_level_label 
                        ? strtoupper($assessment->risk_level_label) 
                        : '-' }}
                </span>
            </div>

            <div>
                <strong>Description</strong><br>
                <span>{{ $assessment->notes ?? '-' }}</span>
            </div>

        </div>

    </div>

    @else

        <p style="font-size:16px; margin-bottom:0;">
            Please upload the completed Excel questionnaire to process the scoring and risk analysis.
        </p>

    @endif
</div>

    {{-- DETAIL TIER CRITICALITY --}}
<div style="background:#FFF; border-radius:14px; padding:40px; margin-bottom:20px; box-shadow:6px 6px 54px #0000000D;">
    
    <h3 style="font-size:24px; font-weight:bold; margin-bottom:30px;">
        Detail Tier Criticality
    </h3>

    <div style="overflow-x:auto;">
        <div style="min-width:800px;">

            {{-- TABLE HEADER --}}
            <div style="display:flex; background:#F1F3F9; padding:12px; border-radius:12px; font-weight:bold;">
                <div style="flex:2;">Category</div>
                <div style="flex:2;">Criteria</div>
                <div style="flex:3; text-align:center;">Justification</div>
                <div style="width:120px; text-align:right;">Score</div>
            </div>

            {{-- TABLE DATA --}}
            @foreach(\App\Models\Category::where('id', '!=', 0)->get() as $category)

                @php
                    $categoryScoreData = $assessment->category_scores[$category->id] ?? null;

                    $indicator = $categoryScoreData['indicator'] ?? null;
                    $justification = $categoryScoreData['justification'] ?? '-';
                    $categoryTotal = $categoryScoreData['score'] ?? 0;

                    // Tentukan criteria berdasarkan indicator
                    $indicator = strtolower($indicator);

                    $criteria = $category->criteria[$indicator] 
                        ?? ucfirst($indicator).' Criteria';
                @endphp

                <div style="display:flex; padding:14px 12px; border-bottom:1px solid #eee; align-items:flex-start;">
                    
                    {{-- CATEGORY --}}
                    <div style="flex:2; font-weight:600;">
                        {{ $category->name }}
                    </div>

                    {{-- CRITERIA --}}
                    <div style="flex:3;">
                        {{ $criteria }}
                    </div>

                    {{-- JUSTIFICATION --}}
                    <div style="flex:3;">
                        {{ $justification }}
                    </div>

                    {{-- SCORE --}}
                    <div style="width:40px; text-align:right; font-weight:bold;">
                        {{ $categoryTotal }}%
                    </div>

                </div>

            @endforeach

        </div>
    </div>

</div>

{{-- HISTORY ASSESSMENT --}}
<div style="background:#FFF; border-radius:14px; padding:40px; margin-bottom:20px; box-shadow:6px 6px 54px #0000000D;">

     <div style="display:flex; justify-content:space-between; align-items:center; ">
    <h3 style="font-size:24px; font-weight:bold; ">
        History Assessment
    </h3>
    <form method="GET"
      action="{{ route('assessment.show', $assessment->id) }}"
      style="display:flex; gap:10px; align-items:center;">

    {{-- MONTH --}}
    <select name="month"
            class="form-control"
            style="width:150px;"
            onchange="this.form.submit()">

        <option value="">All Month</option>

        @for($i=1;$i<=12;$i++)
            <option value="{{ $i }}" {{ request('month')==$i ? 'selected' : '' }}>
                {{ date('F', mktime(0,0,0,$i,1)) }}
            </option>
        @endfor

    </select>

    {{-- YEAR --}}
    <select name="year"
            class="form-control"
            style="width:120px;"
            onchange="this.form.submit()">

        <option value="">All Year</option>

        @for($y=date('Y'); $y>=2020; $y--)
            <option value="{{ $y }}" {{ request('year')==$y ? 'selected' : '' }}>
                {{ $y }}
            </option>
        @endfor

    </select>

</form>
     </div>

    <div style="width:227px; height:2px; background:#4880FF; margin:8px 0 20px;"></div>

    <div style="display:flex; gap:40px; align-items:flex-start;">

        {{-- LEFT SIDE - HISTORY DETAIL --}}
        <div style="flex:1;">
            <h4 style="font-weight:600; margin-bottom:15px;">
                History Detail
            </h4>

            <div style="background:#F1F3F9; padding:10px; border-radius:10px; font-weight:600; display:flex;">
                <div style="flex:2;">Date</div>
                <div style="flex:1;">Status</div>
                <div style="flex:1;">Tier</div>
                <div style="width:80px; text-align:right;">Export</div>
            </div>

            @foreach($historyDetails as $history)
<div style="display:flex; padding:12px 10px; border-bottom:1px solid #eee; align-items:center;">

    <div style="flex:2;">
        {{ \Carbon\Carbon::parse($history->created_at)->format('d/m/Y') }}
    </div>

    <div style="flex:1;">
        {{ $history->new_value['vendor_status'] ?? '-' }}
    </div>

    <div style="flex:1;">
        {{ $history->new_value['tier_criticality'] ?? '-' }}
    </div>

    <div style="width:80px; text-align:center;">
        <a href="{{ route('assessment.export.blank', $history->id) }}"
           style="color:#4880FF; font-size:18px; text-decoration:none;">
            <i class="fa-solid fa-file-export"></i>
        </a>
    </div>

</div>
@endforeach

        </div>


        {{-- RIGHT SIDE - HISTORY SCORE --}}
        <div style="flex:1;">
            <h4 style="font-weight:600; margin-bottom:15px;">
                History Score
            </h4>

            <div style="background:#F1F3F9; padding:10px; border-radius:10px; font-weight:600; display:flex;">
                <div style="flex:2;">Date</div>
                <div style="flex:2;">Assessor</div>
                <div style="flex:1;">Score</div>
                <div style="flex:1;">Risk</div>
                <div style="width:80px; text-align:right;">Export</div>
            </div>

            @foreach($historyScores as $history)
<div style="display:flex; padding:12px 10px; border-bottom:1px solid #eee; align-items:center;">

    <div style="flex:2;">
        {{ \Carbon\Carbon::parse($history->new_value['evaluated_at'] ?? $history->created_at)->format('d/m/Y') }}
    </div>

    <div style="flex:2;">
        {{ $history->new_value['assessor'] ?? '-' }}
    </div>

    <div style="flex:1;">
        {{ $history->new_value['total_score'] ?? 0 }}%
    </div>

    <div style="flex:1;">
        {{ strtoupper($history->new_value['risk_level'] ?? '-') }}
    </div>

    <div style="width:80px;text-align:center;">
        <a href="{{ route('assessment.export.result', $history->id) }}"
   style="color:#4880FF; font-size:18px; text-decoration:none;">
    <i class="fa-solid fa-file-export"></i>
</a>
    </div>

</div>
@endforeach

        </div>

    </div>
</div>

</div>
@endsection