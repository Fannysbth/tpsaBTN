@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Assessment">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div class="assessment-page">
    <div class="toolbar" >
    <a href="{{ route('assessment.create') }}" class="btn-edit">
        <i class="fas fa-plus"></i>
        <span>Add</span>
    </a>

    {{-- MONTH DROPDOWN --}}
    <form method="GET" 
          action="{{ route('assessment.index') }}" 
          id="filter-form"
          style="display:flex; gap:10px; align-items:center;">
    {{-- SEARCH COMPANY --}}
        <input type="text"
       id="company-search"
       name="company"
       value="{{ request('company') }}"
       placeholder="Search company name..."
       class="button-row-view"
       style="width:220px;">

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
<div class="question-card" style=" width:100%; margin:10px 100px 0 0;">

    {{-- TABLE HEADER --}}
    <div class="question-header" style="grid-template-columns: 30px 150px 1fr 180px 200px 100px; margin:10px">
        <div>No</div>
        <div style= "text-align:center;">Date</div>
        <div>Company Name</div>
        <div style= "text-align:center;">Risk Level</div>
        <div style= "text-align:center;">Action</div>
        <div style= "text-align:center;">Detail</div>
    </div>

    {{-- TABLE DATA --}}
    {{-- TABLE DATA --}}
<div id="assessment-table-body">
    @forelse($assessments as $index => $assessment)
        <div class="question-row" style="grid-template-columns: 30px 150px 1fr 180px 200px 100px">
            <div>{{ $index + 1 }}</div>
            <div style= "text-align:center;">{{ $assessment->assessment_date->format('d/m/Y') }}</div>
            <div>{{ $assessment->company_name }}</div>
            <div style= "text-align:center;">{{ $assessment->risk_level_label ? strtoupper($assessment->risk_level_label) : '-' }}</div>

            <div class="action-btns" style="display:flex; justify-content:center; gap:5px; flex-wrap: wrap;">
                
                <form action="{{ route('assessment.import', $assessment) }}"
      method="POST"
      enctype="multipart/form-data">
    @csrf

    <label for="excel_file"
            class="btn btn-sm btn-warning"
            style="
               font-weight:bold;
               width: 80px;
           "
           >
        Upload
    </label>

    <input type="file"
           id="excel_file"
           name="excel_file"
           accept=".xls,.xlsx"
           required
           style="display:none"
           onchange="this.form.submit()">
</form>

                <a href="{{ route('assessment.export', $assessment) }}" class="btn btn-sm btn-primary" style="width: 80px;">
                    Export
                </a>
            </div>

            <div style="text-align: center;">
                <a href="{{ route('assessment.show', $assessment->id) }}">
                    <i class="fa-solid fa-arrow-up-right-from-square icon-detail"></i>
                </a>
            </div>
        </div>
    @empty
         @forelse($assessments as $index => $assessment)
    <div class="question-row">
        <div>{{ $index + 1 }}</div>
        <div style= "text-align:center;">{{ $assessment->assessment_date->format('d/m/Y') }}</div>
        <div>{{ $assessment->company_name }}</div>
        <div style= "text-align:center;">{{ strtoupper($assessment->risk_level_label ?? '-') }}</div>

        <div class="action-btns">
            <form action="{{ route('assessment.import', $assessment) }}"
      method="POST"
      enctype="multipart/form-data">
    @csrf

    <label for="excel_file"
            class="btn btn-sm btn-warning"
            style="
               font-weight:bold;
               width: 80px;
           "
           >
        Upload
    </label>

    <input type="file"
           id="excel_file"
           name="excel_file"
           accept=".xls,.xlsx"
           required
           style="display:none"
           onchange="this.form.submit()">
</form>

            <a href="{{ route('assessment.export', $assessment) }}" class="btn btn-primary btn-sm">Export</a>
        </div>

        <div>
            <a href="{{ route('assessment.show', $assessment->id) }}">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>
    </div>
@empty
    {{-- 🔥 1 baris penuh --}}
    <div class="question-row empty-row">
        <div class="empty-cell">
            Data tidak ditemukan.
        </div>
    </div>
@endforelse
    @endforelse
</div>

</div>

<div style="margin-top:20px; display:flex; justify-content:flex-end; ">
<a href="{{ route('assessment.export.report', request()->query()) }}"
   class="btn-success"
   style="padding: 10px 24px; border-radius: 8px; font-weight: 500;">
    <i class="fas fa-file-excel"></i>
    <span>Export Excel</span>
</a>
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

document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('company-search');
    const tableBody = document.getElementById('assessment-table-body');

    let typingTimer;
    const delay = 400;

    input.addEventListener('input', function () {
        clearTimeout(typingTimer);

        typingTimer = setTimeout(() => {
            const params = new URLSearchParams({
                company: input.value
            });

            fetch(`{{ route('assessment.index') }}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                tableBody.innerHTML = html;
                input.focus(); // ⌨ keyboard aman
            });
        }, delay);
    });
});
</script>


</div>
@endsection
