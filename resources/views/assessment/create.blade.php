@extends('layouts.app')

@section('title', $assessment ? 'Edit Assessment' : 'Add Assessment')

@section('content')

<x-header title="Assessment">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div style="max-width: 1202px; box-sizing: border-box; background: #F5F6FA; padding-top: 10px; margin-left: 20px;">

    {{-- FORM CARD --}}
    <div style="background:#FFFFFF; border-radius:14px; padding:26px; margin:0 20px 60px 16px; box-shadow:6px 6px 54px #0000000D;">

        <span style="font-size:24px; font-weight:bold;">
            {{ $assessment ? 'Edit Assessment' : 'Add Assessment' }}
        </span>
        <div style="width:227px; height:2px; background:#4880FF; margin:8px 0 20px;"></div>

        <form action="{{ $assessment ? route('assessment.update', $assessment->id) : route('assessment.store') }}" method="POST" id="assessmentForm">
            @csrf
            @if($assessment)
                @method('PUT')
            @endif

            {{-- COMPANY NAME --}}
            <div style="margin-bottom:22px;">
                <label style="font-size:20px; font-weight:bold;">Nama Perusahaan</label>
                <input type="text" name="company_name" placeholder="Contoh: PT XYZ" required
                       style="width:100%; border:1px solid #4880FF; border-radius:6px; padding:8px 20px; margin-top:8px;"
                       value="{{ old('company_name', $assessment->company_name ?? '') }}">
                @error('company_name')
                    <span style="color:red; font-size:12px;">{{ $message }}</span>
                @enderror
            </div>


{{-- INDIKATOR PERUSAHAAN --}}
@php
    $selectedLevels = $assessment->category_scores ?? [];
@endphp

@foreach($categories as $category)
    @php
        $criteria = $category->criteria ?? [];
        $lastLevel = $selectedLevels[$category->id]['indicator'] ?? '';
    @endphp

    <div class="category-row" 
         data-category-id="{{ $category->id }}" 
         data-criteria='@json($criteria)'
         style="display:flex; align-items:center; margin-top:12px;">

        <div style="width:300px; font-weight:bold;">{{ $category->name }}</div>

        <div style="width:150px; margin-right:20px;">
            <select class="category-level" name="category_level[{{ $category->id }}]"
                    style="width:100%; border:1px solid #4880FF; border-radius:6px; padding:4px 12px;">
                <option value="">-- Pilih Level --</option>
                <option value="low" {{ old("category_level.{$category->id}", $lastLevel) == 'low' ? 'selected' : '' }}>Low</option>
                <option value="medium" {{ old("category_level.{$category->id}", $lastLevel) == 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="high" {{ old("category_level.{$category->id}", $lastLevel) == 'high' ? 'selected' : '' }}>High</option>
            </select>
        </div>

        <div class="category-criteria" style="width:450px; font-style:italic; color:#4880FF;">
            @if($lastLevel && isset($criteria[$lastLevel]))
                {{ $criteria[$lastLevel] }}
            @endif
        </div>
    </div>
@endforeach


            {{-- BUTTONS --}}
            <div style="display:flex; justify-content:flex-end; gap:20px;">
                <a href="{{ url()->previous()  }}"
                   style="border:1px solid #4880FF; padding:10px 16px; border-radius:6px; color:#4880FF; font-weight:bold; text-decoration:none;">
                    Cancel
                </a>
                <button type="submit" id="submitBtn"
                        style="background:#4379EE; color:white; border:none; padding:10px 20px; border-radius:6px; font-weight:bold; cursor:pointer;">
                    {{ $assessment ? 'Update' : 'Add' }}
                </button>
            </div>

        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.category-row').forEach(row => {
        const criteria = row.dataset.criteria ? JSON.parse(row.dataset.criteria) : {};
        const select = row.querySelector('.category-level');
        const criteriaDiv = row.querySelector('.category-criteria');

        function updateCriteria() {
            const level = select.value;
            criteriaDiv.textContent = level && criteria[level] ? criteria[level] : '';
        }

        // Update saat halaman load
        updateCriteria();

        // Update saat user pilih level
        select.addEventListener('change', updateCriteria);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('assessmentForm');
    const submitBtn = document.getElementById('submitBtn');
    let isSubmitting = false;
    
    form.addEventListener('submit', function(e) {
        // Cek jika sedang submitting, prevent duplicate
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        
        // Validasi minimal satu kategori dipilih
        const categorySelects = document.querySelectorAll('.category-level');
        let atLeastOneSelected = false;
        
        categorySelects.forEach(select => {
            if (select.value) {
                atLeastOneSelected = true;
            }
        });
        
        if (!atLeastOneSelected) {
            e.preventDefault();
            alert('Pilih minimal satu kategori dengan level tertentu!');
            return false;
        }
        
        // Set submitting state
        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.style.opacity = '0.7';
        submitBtn.style.cursor = 'not-allowed';
        
        // Tambahkan hidden loading indicator
        let loadingDiv = document.createElement('div');
        loadingDiv.id = 'form-loading';
        loadingDiv.style.position = 'fixed';
        loadingDiv.style.top = '50%';
        loadingDiv.style.left = '50%';
        loadingDiv.style.transform = 'translate(-50%, -50%)';
        loadingDiv.style.backgroundColor = 'rgba(0,0,0,0.7)';
        loadingDiv.style.color = 'white';
        loadingDiv.style.padding = '20px';
        loadingDiv.style.borderRadius = '10px';
        loadingDiv.style.zIndex = '9999';
        loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan data...';
        document.body.appendChild(loadingDiv);
        
        // Allow form to submit normally
        return true;
    });
    
    // Reset submitting state jika page di-reload
    window.onbeforeunload = function() {
        if (isSubmitting) {
            isSubmitting = false;
        }
    };
});
</script>

@endsection