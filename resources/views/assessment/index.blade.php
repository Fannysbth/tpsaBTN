@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Assessment">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div class="assessment-page">



<div class="toolbar">
    <a href="{{ route('assessment.create') }}" class="btn-edit">
        <i class="fas fa-plus"></i>
        <span>Add</span>
    </a>

    {{-- MONTH DROPDOWN --}}
    <form method="GET" action="{{ route('assessment.index') }}" style="display:flex; gap:10px; align-items:center; margin-right: 30px;">
    {{-- MONTH --}}
    <select name="month" class="button-row-view" onchange="this.form.submit()">
        <option value="">All Month</option>
        @for($i=1;$i<=12;$i++)
            <option value="{{ $i }}" {{ $i==$month?'selected':'' }}>
                {{ date('F', mktime(0,0,0,$i,1)) }}
            </option>
        @endfor
    </select>

    {{-- YEAR --}}
    <select name="year" class="button-row-view" onchange="this.form.submit()">
        <option value="">All Year</option>
        @for($y = date('Y'); $y >= 2020; $y--)
            <option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>
        @endfor
    </select>
</form>

</div>

@if(session('success'))
    <div style="background:#d4edda;color:#155724;padding:10px;margin-bottom:10px;border-radius:6px;">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div style="background:#f8d7da;color:#721c24;padding:10px;margin-bottom:10px;border-radius:6px;">
        {{ session('error') }}
    </div>
@endif

{{-- ASSESSMENT TABLE --}}
<div class="question-card" style="margin-top: 10px; margin-right: 30px;">

    {{-- TABLE HEADER --}}
    <div class="question-header">
        <div>No</div>
        <div>Date</div>
        <div>Company Name</div>
        <div>Risk Level</div>
        <div style="text-align:center; width: 250px;">Action</div> {{-- Tambah width untuk action column --}}
        <div style="text-align:center;">Detail</div>
    </div>

    {{-- TABLE DATA --}}
    @forelse($assessments as $index => $assessment)
        <div class="question-row">
            <div>{{ $index + 1 }}</div>
            <div>{{ $assessment->assessment_date->format('d/m/Y') }}</div>
            <div>{{ $assessment->company_name }}</div>
            <div>{{ $assessment->risk_level_label ? strtoupper($assessment->risk_level_label) : '-' }}</div>

            {{-- ACTION BUTTONS --}}
            <div class="action-btns" style="display:flex; justify-content:center; gap:5px; flex-wrap: wrap;">
                {{-- Edit Button --}}
                <a href="{{ route('assessment.edit', $assessment->id) }}" class="btn btn-sm btn-warning" title="Edit Assessment">
                    <i class="fas fa-edit"></i> Edit
                </a>
                
                {{-- Export Button --}}
                <a href="{{ route('assessment.export', $assessment) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-download"></i> Export
                </a>
            </div>

            {{-- DETAIL / PROGRESS BAR --}}
            <div style="text-align: center;">
                <a href="{{ route('assessment.show', $assessment->id) }}">
                    <i class="fa-solid fa-arrow-up-right-from-square icon-detail"></i>
                </a>
            </div>
        </div>
    @empty
        <div class="question-row" style="justify-content:center; padding:20px;">
            <div>Belum ada data assessment</div>
        </div>
    @endforelse
</div>

</div>

{{-- Tambahkan script untuk konfirmasi upload --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            if (!this.value) return;
            
            if (!confirm('Apakah Anda yakin ingin mengupload file ini? Data jawaban sebelumnya akan diganti.')) {
                this.value = '';
                return;
            }
        });
    });
});
</script>

</div>
@endsection
