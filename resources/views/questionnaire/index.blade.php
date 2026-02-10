@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Questionnaire">
    {{-- ERROR PREVIEW IMPORT --}}
@if ($errors->any())
    <div class="alert alert-danger" style="margin:20px">
        <ul style="margin:0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>

    {{-- buka modal lagi kalau error --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = new bootstrap.Modal(
                document.getElementById('importModal')
            );
            modal.show();
        });
    </script>
@endif

    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>



<div  class="questionnaire-page">

    {{-- TOOLBAR --}}

    <div class="filter-edit-wrapper">
        <div class="filter-box">
@php
    $visibleCategories = $categories->take(3);
    $moreCategories = $categories->slice(3);
@endphp

            {{-- ALL --}}
            <span class="filter-item active" data-category-id="">All</span>

            {{-- 3 KATEGORI PERTAMA --}}
            @foreach ($visibleCategories as $category)
                <span class="filter-item" 
                     data-category-id="{{ $category->id }}">
                        {{ $category->name }}
                </span>
            @endforeach

            {{-- MORE --}}
            @if ($moreCategories->count())
<div class="dropdown" style="padding:5px;">
    <span
        class="dropdown-toggle filter-item"
        data-bs-toggle="dropdown"
        id="moreFilter"
        style="align-items: stretch; height: 100%;"
    >
        <span id="moreFilterText">More</span>
    </span>

    <div class="dropdown-menu">
        @foreach ($moreCategories as $category)
            <a
                href="#"
                class="dropdown-item filter-item"
                data-category-id="{{ $category->id }}">
                {{ $category->name }}
            </a>
        @endforeach
    </div>
</div>
@endif
        </div>

        {{-- INDICATOR FILTER --}}
<div class="dropdown"  style="display:flex; padding:10px;background:#FFFFFF; border-radius: 10px;
    border: 1px solid transparent; align-items:center;">
    <span
        class="dropdown-toggle filter-item"
        data-bs-toggle="dropdown"
        id="indicatorFilter"
    >
        <span id="indicatorFilterText">Indicator</span>
    </span>

    <div class="dropdown-menu">
        <a href="#" class="dropdown-item indicator-item" data-indicator="">
            All Indicator
        </a>
        <a href="#" class="dropdown-item indicator-item" data-indicator="umum">
            Umum
        </a>
        <a href="#" class="dropdown-item indicator-item" data-indicator="high">
            High
        </a>
        <a href="#" class="dropdown-item indicator-item" data-indicator="medium">
            Medium
        </a>
        <a href="#" class="dropdown-item indicator-item" data-indicator="low">
            Low
        </a>
    </div>
</div>

     <div class="dropdown add-wrapper">
        <button type="button"
                class="btn btn-primary"
                data-bs-toggle="dropdown">
            <i class="fas fa-plus me-1"></i> Edit
        </button>

        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item" href="{{ route('categories.create') }}">
                    Category
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('questionnaire.editAll') }}">
                    Question
                </a>
            </li>
        </ul>
    </div>


    </div>

    {{-- QUESTION TABLE --}}
    <div class="question-card" style="margin-top: 10px; margin-left:10px;">

        {{-- HEADER --}}
        <div class="question-header">
            <div >No</div>
            <div>Category</div>
            <div style="text-align:center;">Sub Category</div>
            <div style="text-align:center;">Indicator</div>
            <div style= "text-align: center;">Question</div>
            <div style= "text-align: center;">Type Answer</div>
            <div style= "text-align: center;">Aksi</div>
        </div>
        
@php $no = 1; @endphp

        {{-- DATA --}} 
        @foreach($categories as $category)
    @foreach($category->questions as $question)
    @php
    $indicatorArray = is_array($question->indicator)
        ? $question->indicator
        : json_decode($question->indicator ?? '[]', true);
@endphp
        <div class="question-row" 
             data-category-id="{{ $category->id }}"
             data-indicator="{{ implode(',', $indicatorArray) }}">

            <div>{{ $no++ }}</div>
            <div>{{ $category->name }}</div>
            <div style="text-align:center;">{{ $question->sub ?? '-' }}</div>

            <div style="text-align:center;">
    @php
        $indicators = $indicatorArray;
        $colors = [
            'high' => '#E74C3C',    // merah
            'medium' => '#F1C40F',  // kuning
            'low' => '#2ECC71',     // hijau
        ];
    @endphp

    @if($indicators)
        @foreach($indicators as $index => $indicator)
            <span style="color: {{ $colors[$indicator] ?? '#000' }}; font-weight:600;">
                {{ strtoupper($indicator) }}
            </span>@if(!$loop->last), @endif
        @endforeach
    @else
        -
    @endif
</div>


            <div style="margin:0 20px">{{ $question->question_text }}</div>

            <div style="text-align:center; margin:0 5px;">
            @if($question->question_type === 'pilihan')
                <select style="padding:6px 8px;border-radius:6px;margin:0 5px;border:1px solid #4880FF;width:130px;
                              overflow:hidden;text-overflow:ellipsis;white-space:nowrap; ">
                <option value="">-- Pilihan --</option>
                @foreach($question->options as $opt)
                    <option>{{ $opt->option_text }}</option>
                @endforeach
                </select>
            @else
                <span style="color:#000; justify-content:center; display:flex;">
                {{ $question->clue ?? '-' }}
                </span>
            @endif
            </div>

            <form
    action="{{ route('questionnaire.questions.destroy', $question) }}"
    method="POST"
    onsubmit="return confirm('Yakin ingin menghapus question ini?');"
    style="margin:0;"
>
    @csrf
    @method('DELETE')

    <button
        type="submit"
        class="btn btn-sm btn-danger"
    >
        <i class="fas fa-trash"></i>
    </button>
</form>

        </div>
    @endforeach
@endforeach
 </div>
 {{-- TOMBOL EXPORT & IMPORT --}}
<div style="margin: 30px 0 20px 20px; display: flex; gap: 12px;">
    <a href="{{ route('questionnaire.export') }}" class="btn btn-success" 
       style="padding: 10px 24px; border-radius: 8px; font-weight: 500;">
        <i class="fas fa-file-excel" style="margin-right: 8px;"></i>Export to Excel
    </a>
    
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal"
            style="padding: 10px 24px; border-radius: 8px; font-weight: 500;">
        <i class="fas fa-file-import" style="margin-right: 8px;"></i>Import from Excel
    </button>
</div>

{{-- MODAL IMPORT --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Questionnaire</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('questionnaire.import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file" class="form-label">Pilih File Excel</label>
                        <input type="file" class="form-control" name="file" id="file" accept=".xlsx,.xls" required>
                        <div class="form-text">
                            Format file harus sesuai dengan template export. Hanya pertanyaan baru yang akan ditambahkan.
                        </div>
                    </div>
                    
                    <div style="background: #F0F7FF; padding: 12px; border-radius: 6px; margin-top: 16px;">
                        <h6 style="font-size: 13px; color: #4880FF; margin-bottom: 8px;">
                            <i class="fas fa-info-circle" style="margin-right: 6px;"></i>Catatan Import:
                        </h6>
                        <ul style="font-size: 12px; color: #595959; margin: 0; padding-left: 16px;">
                            <li>Hanya dapat menambahkan pertanyaan ke kategori yang sudah ada</li>
                            <li>Tidak dapat membuat kategori baru melalui import</li>
                            <li>Format harus sesuai dengan template export</li>
                            <li>Data akan divalidasi terlebih dahulu sebelum diimport</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-eye" style="margin-right: 6px;"></i>Preview Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>



{{-- ================= FILTER SCRIPT ================= --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    let activeCategory = null;
    let activeIndicator = null;

    const rows = document.querySelectorAll('.question-row');

    function applyFilter() {
        rows.forEach(row => {
            const rowCategory = row.dataset.categoryId;
            const rowIndicators = (row.dataset.indicator || '').split(',');

            const matchCategory =
                !activeCategory || rowCategory === activeCategory;

            const matchIndicator =
                !activeIndicator || rowIndicators.includes(activeIndicator);

            row.style.display =
                matchCategory && matchIndicator ? 'grid' : 'none';
        });
    }

    /* ================= CATEGORY FILTER ================= */

    document
        .querySelectorAll('.filter-item[data-category-id]')
        .forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                activeCategory = this.dataset.categoryId || null;

                // active state category aja
                document
                    .querySelectorAll('.filter-item[data-category-id]')
                    .forEach(i => i.classList.remove('active'));

                this.classList.add('active');

                // reset text more kalau klik All / kategori utama
                if (!this.closest('.dropdown-menu')) {
                    const moreText = document.getElementById('moreFilterText');
                    if (moreText) moreText.textContent = 'More';
                }

                applyFilter();
            });
        });

    /* ================= MORE DROPDOWN ================= */

    document
        .querySelectorAll('.dropdown-menu .filter-item')
        .forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                activeCategory = this.dataset.categoryId;

                document
                    .querySelectorAll('.filter-item[data-category-id]')
                    .forEach(i => i.classList.remove('active'));

                this.classList.add('active');

                document.getElementById('moreFilterText').textContent =
                    this.textContent.trim();

                applyFilter();
            });
        });

    /* ================= INDICATOR FILTER ================= */

    document
        .querySelectorAll('.indicator-item')
        .forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                activeIndicator = this.dataset.indicator || null;

                document
                    .querySelectorAll('.indicator-item')
                    .forEach(i => i.classList.remove('active'));

                this.classList.add('active');

                document.getElementById('indicatorFilterText').textContent =
                    this.textContent.trim() === 'All Indicator'
                        ? 'Indicator'
                        : this.textContent.trim();

                applyFilter();
            });
        });

});
</script>



<style>
.questions-container {
    height: calc(100vh - 260px);
    width: 100%;
    overflow-y: auto;
    padding: 0 24px;   /* jarak kiri kanan */
    box-sizing: border-box;
}

.btn-success {
    background: #52C41A;
    border: 1px solid #52C41A;
    color: white;
}

.btn-success:hover {
    background: #389E0D;
    border-color: #389E0D;
}

.btn-primary {
    background: #4880FF;
    border: 1px solid #4880FF;
    color: white;
}

.btn-primary:hover {
    background: #2F6DFF;
    border-color: #2F6DFF;
}

.btn-secondary {
    background: #8C8C8C;
    border: 1px solid #8C8C8C;
    color: white;
}

.btn-secondary:hover {
    background: #737373;
    border-color: #737373;
}
</style>

@endsection
