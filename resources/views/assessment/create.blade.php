@extends('layouts.app')

@section('title', $assessment ? 'Edit Assessment' : 'Add Assessment')

@section('content')

<x-header title="Assessment">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div style="max-width: 1202px; box-sizing: border-box; background: #F5F6FA; padding-top: 10px; margin-left: 20px;">

           

        <form action="{{ $assessment ? route('assessment.update', $assessment->id) : route('assessment.store') }}" method="POST" id="assessmentForm">
            @csrf
            @if($assessment)
                @method('PUT')
            @endif
            {{-- FORM CARD --}}
    <div style="background:#FFFFFF; border-radius:14px; padding:26px; margin:0 20px 60px 16px; box-shadow:6px 6px 54px #0000000D;">

        <div style="display:flex; justify-content:space-between; align-items:center;">
<div>
    <span style="font-size:24px; font-weight:bold;">
        {{ $assessment ? 'Edit Assessment' : 'Add Assessment' }}
    </span>
     <div style="width:227px; height:2px; background:#4880FF; margin:8px 0 20px;"></div>
</div>

    @if($assessment)
    <div style="display:flex; align-items:center; gap:12px;">
        
        <span id="statusText"
              style="font-weight:600; color:{{ $assessment->vendor_status === 'active' ? '#16A34A' : '#DC2626' }}">
            {{ ucfirst($assessment->vendor_status) }}
        </span>

        <input type="hidden" name="vendor_status" value="inactive">

        <input type="checkbox"
               id="statusToggle"
               name="vendor_status"
               value="active"
               {{ old('vendor_status', $assessment->vendor_status) === 'active' ? 'checked' : '' }}
               style="width:42px; height:22px; cursor:pointer; accent-color:#4880FF;">
    </div>
@endif

</div>


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
<div style="background:#F1F3F9; padding:10px; border-radius:10px; font-weight:600; display:flex;">
                <div style="flex:2;">Kategori</div>
                <div style="flex:2;">Kriteria</div>
                <div style="flex:1;">Justifikasi</div>
            </div>


@foreach($categories as $category)
    @if($category->id == 0)
        @continue
    @endif
    @php
        $criteria = $category->criteria ?? [];
        $lastLevel = $selectedLevels[$category->id]['indicator'] ?? '';
    @endphp

    
    <div class="category-row " 
         data-category-id="{{ $category->id }}" 
         data-criteria='@json($criteria)'
         style="display:flex; align-items:center; margin-top:12px; width:100%;">

        <div style="width:200px; font-weight:bold;">{{ $category->name }}</div>

        <div style="flex:1;">
            <select class="category-level select2-multiline" name="category_level[{{ $category->id }}]"
                    style="width:100%; border:1px solid #4880FF; border-radius:6px; padding:4px 12px;">
                <option value=""> </option>

@if(isset($criteria['low']))
    <option value="low" {{ old("category_level.{$category->id}", $lastLevel) == 'low' ? 'selected' : '' }}>
         {{ $criteria['low'] }}
    </option>
@endif

@if(isset($criteria['medium']))
    <option value="medium" {{ old("category_level.{$category->id}", $lastLevel) == 'medium' ? 'selected' : '' }}>
         {{ $criteria['medium'] }}
    </option>
@endif

@if(isset($criteria['high']))
    <option value="high" {{ old("category_level.{$category->id}", $lastLevel) == 'high' ? 'selected' : '' }}>
         {{ $criteria['high'] }}
    </option>
@endif
            </select>
            
        </div>
<div style="flex:1; margin-left:12px;">
    <textarea 
        name="category_justification[{{ $category->id }}]"
        placeholder="Masukkan justification..."
        style="width:100%; border:1px solid #4880FF; border-radius:6px; padding:8px 12px; resize:vertical; min-height:60px;">
        {{ old("category_justification.{$category->id}", $selectedLevels[$category->id]['justification'] ?? '') }}
    </textarea>
</div>
       
    </div>
@endforeach


            {{-- BUTTONS --}}
            <div style="display:flex; justify-content:flex-end; gap:20px;margin-top:30px;">
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
    if (criteriaDiv) {
        criteriaDiv.textContent = level && criteria[level] ? criteria[level] : '';
    }
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

<script>
$(document).ready(function() {
    $('.select2-multiline').select2({
        width: '100%',
        dropdownAutoWidth: true
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('statusToggle');
    const text = document.getElementById('statusText');

    if (toggle && text) {
        toggle.addEventListener('change', function() {
            if (this.checked) {
                text.textContent = 'Active';
                text.style.color = '#16A34A';
            } else {
                text.textContent = 'Inactive';
                text.style.color = '#DC2626';
            }
        });
    }
});
</script>

@endsection