@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Questionnaire">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>



<div  style="max-width: 1202px; box-sizing: border-box; background: #F5F6FA; padding: 1px; margin: 10px 0 0 10px;">

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

        <a href="{{ route('questionnaire.editAll') }}"    class="btn-edit">
    <i class="fas fa-edit"></i>
    Edit
</a>


    </div>

    {{-- QUESTION TABLE --}}
    <div class="question-card" style="margin-top: 10px; margin-right: 20px; margin-left:20px;">

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
        <div class="question-row" 
             data-category-id="{{ $category->id }}"
             data-indicator="{{ implode(',', json_decode($question->indicator ?? '[]', true)) }}">

            <div>{{ $no++ }}</div>
            <div>{{ $category->name }}</div>
            <div style="text-align:center;">{{ $question->sub ?? '-' }}</div>

            <div style="text-align:center;">
    @php
        $indicators = json_decode($question->indicator ?? '[]', true);
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


            <div style="margin:0 10px">{{ $question->question_text }}</div>

            <div style="text-align:center; margin:0 10px;padding: 0 30px;">
            @if($question->question_type === 'pilihan')
                <select style="padding:6px 8px;border-radius:6px;margin:0 10px;border:1px solid #4880FF;max-width:300px;
                              width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
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

            <div class="action-btns " style="margin-left: 10px;">
                <button class="btn btn-sm btn-danger delete-question"
                        data-id="{{ $question->id }}">
                <i class="fas fa-trash" ></i>
                </button>
            </div>
        </div>
    @endforeach
@endforeach
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



@endsection
