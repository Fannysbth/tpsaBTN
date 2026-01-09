@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Questionnaire">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

@php
    $visibleCategories = $categories->take(3);
    $moreCategories = $categories->slice(3);
@endphp

<div  style="max-width: 1202px; box-sizing: border-box; background: #F5F6FA; padding: 1px; margin: 10px 0 0 10px;">

    <div class="filter-edit-wrapper">
        <div class="filter-container">

            {{-- ALL --}}
            <div class="filter-item fixed active">
                <span class="filter-text">All</span>
                <div class="divider"></div>
            </div>

            {{-- 3 KATEGORI PERTAMA --}}
            @foreach ($visibleCategories as $category)
                <div class="filter-item" data-category-id="{{ $category->id }}">
                    <span class="filter-text truncate">
                        {{ $category->name }}
                    </span>
                    <div class="divider"></div>
                </div>
            @endforeach

            {{-- MORE --}}
            @if ($moreCategories->count())
                <div class="dropdown filter-more">
                    <button class="dropdown-toggle btn btn-link p-0 text-decoration-none"
                            type="button"
                            data-bs-toggle="dropdown">
                        More...
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end mt-2">
                        @foreach ($moreCategories as $category)
                            <li>
                                <a class="dropdown-item"
                                   href="#"
                                   data-category-id="{{ $category->id }}">
                                    {{ $category->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

        </div>

        <a href="{{ route('questionnaire.editAll') }}"    class="btn-edit">
    <i class="fas fa-edit"></i>
    Edit
</a>


    </div>

    {{-- QUESTION TABLE --}}
    <div class="question-card" style="margin-top: 10px; margin-right: 30px;">

        {{-- HEADER --}}
        <div class="question-header">
            <div>No</div>
            <div>Category</div>
            <div >Question</div>
            <div>Type Answer</div>
            <div style="text-align: center;">Attachment</div>
            <div style="text-align: center;">Aksi</div>
        </div>

        {{-- DATA --}}
        @foreach($categories as $category)
            @foreach($category->questions as $question)
                <div class="question-row"
                     data-category-id="{{ $category->id }}">

                    <div>{{ $question->order }}</div>
                    <div>{{ $category->name }}</div>
                    <div>{{ $question->question_text }}</div>

                    <div>
                        <button class="type-btn">
                            {{ ucfirst($question->question_type) }}
                        </button>
                    </div>

                    <div class="attachment-text">
                        {{ $question->has_attachment ? $question->attachment_text : '-' }}
                    </div>

                    <div class="action-btns " style="margin-left: 50px;">
                        

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

@include('questionnaire.modals.category')
@include('questionnaire.modals.question')

{{-- ================= FILTER SCRIPT ================= --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const allBtn = document.querySelector('.filter-item.fixed');
    const filterItems = document.querySelectorAll('.filter-item[data-category-id]');
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    const rows = document.querySelectorAll('.question-row');

    function resetActive() {
        document.querySelectorAll('.filter-item').forEach(el => {
            el.classList.remove('active');
        });
    }

    function showAll() {
        rows.forEach(row => row.style.display = 'grid');
    }

    // ALL
    allBtn.addEventListener('click', function () {
        resetActive();
        this.classList.add('active');
        showAll();
    });

    // FILTER BUTTON
    filterItems.forEach(item => {
        item.addEventListener('click', function () {
            const id = this.dataset.categoryId;
            resetActive();
            this.classList.add('active');

            rows.forEach(row => {
                row.style.display =
                    row.dataset.categoryId === id ? 'grid' : 'none';
            });
        });
    });

    // FILTER DROPDOWN
    dropdownItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.categoryId;
            resetActive();

            rows.forEach(row => {
                row.style.display =
                    row.dataset.categoryId === id ? 'grid' : 'none';
            });
        });
    });

});
</script>



@endsection
