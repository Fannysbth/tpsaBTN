@extends('layouts.app')

@section('title', 'Assessment')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Assessment">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div style="max-width: 1202px; box-sizing: border-box; background: #F5F6FA; padding: 1px; margin: 10px 0 0 10px;">

    {{-- ADD BUTTON --}}
<div class="btn-edit btn-blue" onclick="alert('Add pressed!')">
    <i class="fas fa-plus"></i>
    <span class="filter-text">Add</span>
</div>

{{-- MONTH DROPDOWN --}}
<div class="dropdown" style="display:inline-block; margin-left:10px;">
    <button class="filter-item dropdown-toggle" type="button" data-bs-toggle="dropdown" style="background-color:#FCFCFC;">
        <i class="fas fa-calendar"></i>
        January
    </button>
    <ul class="dropdown-menu" style="min-width: auto; background-color:#FCFCFC;">
        <li><a class="dropdown-item" href="#">January</a></li>
        <li><a class="dropdown-item" href="#">February</a></li>
        <li><a class="dropdown-item" href="#">March</a></li>
        <li><a class="dropdown-item" href="#">April</a></li>
        <li><a class="dropdown-item" href="#">May</a></li>
        <li><a class="dropdown-item" href="#">June</a></li>
        <li><a class="dropdown-item" href="#">July</a></li>
        <li><a class="dropdown-item" href="#">August</a></li>
        <li><a class="dropdown-item" href="#">September</a></li>
        <li><a class="dropdown-item" href="#">October</a></li>
        <li><a class="dropdown-item" href="#">November</a></li>
        <li><a class="dropdown-item" href="#">December</a></li>
    </ul>
</div>



    {{-- ASSESSMENT TABLE --}}
    <div class="question-card" style="margin-top: 10px; margin-right: 30px;">

        {{-- TABLE HEADER --}}
        <div class="question-header">
            <div>No</div>
            <div>Date</div>
            <div>Company Name</div>
            <div>Risk Level</div>
            <div style="text-align:center;">Action</div>
            <div style="text-align:center;">Detail</div>
        </div>

        {{-- TABLE DATA --}}
        @foreach($assessments as $index => $assessment)
            <div class="question-row">
                <div>{{ $index + 1 }}</div>
                <div>{{ $assessment->assessment_date->format('d/m/Y') }}</div>
                <div>{{ $assessment->company_name }}</div>
                <div>{{ strtoupper($assessment->risk_level) }}</div>

                {{-- ACTION BUTTONS --}}
                <div class="action-btns" style="display:flex; justify-content:center; gap:5px;">
                    <button class="btn btn-sm btn-primary" onclick="alert('Export pressed!')">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-sm btn-success" onclick="alert('Upload pressed!')">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </div>

                {{-- DETAIL / PROGRESS BAR --}}
                <a href="{{ route('assessment.show', $assessment['id']) }}" style="text-align: center;">
        <i class="fa-solid fa-arrow-up-right-from-square icon-detail"></i>
    </a>
            </div>
        @endforeach

    </div>

</div>

@endsection
