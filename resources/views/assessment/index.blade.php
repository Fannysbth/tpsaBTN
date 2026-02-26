@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Assessment">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div class="assessment-page">
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

    <div class="toolbar d-flex flex-wrap gap-2 align-items-center mb-3">

    {{-- Tombol Add --}}
    <a href="{{ route('assessment.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add
    </a>

    {{-- Tab Filter Full Width --}}
    <div class="btn-group flex-fill" role="group" aria-label="Assessment tabs">
        <a href="{{ route('assessment.index', array_merge(request()->query(), ['tab' => 'active'])) }}" 
           class="btn w-100 {{ request('tab', 'active') == 'active' ? 'btn-primary' : 'btn-outline-primary' }}">
            Active
        </a>
        <a href="{{ route('assessment.index', array_merge(request()->query(), ['tab' => 'inactive'])) }}" 
           class="btn w-100 {{ request('tab') == 'inactive' ? 'btn-primary' : 'btn-outline-primary' }}">
            Inactive
        </a>
    </div>

    {{-- FILTER FORM --}}
    <form method="GET" 
          action="{{ route('assessment.index') }}" 
          id="filter-form" 
          class="d-flex gap-2 align-items-center ms-auto">

        <input type="hidden" name="tab" value="{{ request('tab', 'active') }}">

        <input type="text"
               name="company"
               value="{{ request('company') }}"
               placeholder="Search company name..."
               class="form-control"
               style="max-width:220px;">

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i>
        </button>

        <select name="month" class="form-select" onchange="this.form.submit()" style="max-width:160px;">
            <option value="">All Month</option>
            @for($i=1;$i<=12;$i++)
                <option value="{{ $i }}" {{ $i==request('month')?'selected':'' }}>
                    {{ date('F', mktime(0,0,0,$i,1)) }}
                </option>
            @endfor
        </select>

        <select name="year" class="form-select" onchange="this.form.submit()" style="max-width:130px;">
            <option value="">All Year</option>
            @for($y = date('Y'); $y >= 2020; $y--)
                <option value="{{ $y }}" {{ $y==request('year')?'selected':'' }}>
                    {{ $y }}
                </option>
            @endfor
        </select>

    </form>
</div>
    {{-- KONTEN TABEL (hanya satu card, konten berubah sesuai tab) --}}
    <div class="question-card" style="width:100%; margin:10px 100px 0 0;">
        {{-- TABLE HEADER --}}
        <div class="question-header" style="grid-template-columns: 30px 150px 1fr 180px 200px 100px; margin:10px">
            <div>No</div>
            <div style="text-align:center;">Date</div>
            <div>Company Name</div>
            <div style="text-align:center;">Tier Criticality</div>
            <div style="text-align:center;">Risk Level</div>
            <div style="text-align:center;">Detail</div>
        </div>

        {{-- TABLE DATA --}}
        <div id="assessment-table-body">
            @if(request('tab', 'active') == 'active')
                @forelse($activeAssessments as $index => $assessment)
                    <div class="question-row" style="grid-template-columns: 30px 150px 1fr 180px 200px 100px">
                        <div>{{ $index + 1 }}</div>
                        <div style="text-align:center;">{{ $assessment->assessment_date->format('d/m/Y') }}</div>
                        <div>{{ $assessment->company_name }}</div>
                        <div style="text-align:center;">{{ $assessment->tier_criticality ?? '-' }}</div>
                        <div style="text-align:center;">{{ $assessment->risk_level_label ? strtoupper($assessment->risk_level_label) : '-' }}</div>
                        <div style="text-align: center;">
                            <a href="{{ route('assessment.show', $assessment->id) }}">
                                <i class="fa-solid fa-arrow-up-right-from-square icon-detail"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    {{-- Tampilkan semua assessment jika active kosong (sesuai logic awal) --}}
                    @forelse($assessments as $index => $assessment)
                        <div class="question-row">
                            <div>{{ $index + 1 }}</div>
                            <div style="text-align:center;">{{ $assessment->assessment_date->format('d/m/Y') }}</div>
                            <div>{{ $assessment->company_name }}</div>
                            <div style="text-align:center;">{{ strtoupper($assessment->risk_level_label ?? '-') }}</div>
                            <div class="action-btns">
                                <form action="{{ route('assessment.import', $assessment) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <label for="excel_file" class="btn btn-sm btn-warning" style="font-weight:bold; width: 80px;">Upload</label>
                                    <input type="file" id="excel_file" name="excel_file" accept=".xls,.xlsx" required style="display:none" onchange="this.form.submit()">
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
                        <div class="question-row empty-row">
                            <div class="empty-cell">Data tidak ditemukan.</div>
                        </div>
                    @endforelse
                @endforelse
            @else
                @forelse($inactiveAssessments as $index => $assessment)
                    <div class="question-row" style="grid-template-columns: 30px 150px 1fr 180px 200px 100px">
                        <div>{{ $index + 1 }}</div>
                        <div style="text-align:center;">{{ $assessment->assessment_date->format('d/m/Y') }}</div>
                        <div>{{ $assessment->company_name }}</div>
                        <div style="text-align:center;">{{ $assessment->tier_criticality ?? '-' }}</div>
                        <div style="text-align:center;">{{ $assessment->risk_level_label ? strtoupper($assessment->risk_level_label) : '-' }}</div>
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
                            <div style="text-align:center;">{{ $assessment->assessment_date->format('d/m/Y') }}</div>
                            <div>{{ $assessment->company_name }}</div>
                            <div style="text-align:center;">{{ strtoupper($assessment->risk_level_label ?? '-') }}</div>
                            <div class="action-btns">
                                <form action="{{ route('assessment.import', $assessment) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <label for="excel_file" class="btn btn-sm btn-warning" style="font-weight:bold; width: 80px;">Upload</label>
                                    <input type="file" id="excel_file" name="excel_file" accept=".xls,.xlsx" required style="display:none" onchange="this.form.submit()">
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
                        <div class="question-row empty-row">
                            <div class="empty-cell">Data tidak ditemukan.</div>
                        </div>
                    @endforelse
                @endforelse
            @endif
        </div>
    </div>

    {{-- TOMBOL EXPORT REPORT --}}
    <div style="margin-top:20px; display:flex; justify-content:flex-end;">
        <a href="{{ route('assessment.export.report', request()->query()) }}"
           class="btn-primary"
           style="padding: 10px 24px; border-radius: 8px; font-weight: 500;">
            <i class="fas fa-file-excel"></i>
            <span>Export Report</span>
        </a>
    </div>
</div>

{{-- Script untuk konfirmasi upload --}}
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

@endsection