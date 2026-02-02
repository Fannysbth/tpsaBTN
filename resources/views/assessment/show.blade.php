@extends('layouts.app')

@section('title', 'Assessment Detail')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Assessment">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div style="background: #F5F6FA; padding: 20px;">

    {{-- COMPANY INFO --}}
    <div class="company-header">
        <div class="company-header-left">
            <h2>{{ $assessment->company_name }}</h2>
            <p>Assessment Date: {{ $assessment->assessment_date->format('d/m/Y') }}</p>
        </div>
        <div class="company-header-right">
            <div style="
    display:flex;
    gap:10px;
    align-items:center;
">

    {{-- BACK --}}
    <a href="{{ route('assessment.index') }}"
       style="
           height:40px;
           display:flex;
           align-items:center;
           justify-content:center;
           padding:0 20px;
           border-radius:6px;
           border:2px solid #4880FF;
           color:#4880FF;
           font-weight:bold;
           text-decoration:none;
       ">
        Back to List
    </a>

    {{-- EDIT --}}
    @if(is_null($assessment->risk_level))
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
    @endif

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
                    padding:0 20px;
                    border-radius:6px;
                    border:none;
                    background:#FF4D4F;
                    color:white;
                    font-weight:bold;
                    cursor:pointer;
                ">
            Delete
        </button>
    </form>

</div>

        </div>
    </div>

    {{-- UPLOAD / RESULT --}}
    <div style="background:#FFF; border-radius:14px; padding:40px; margin-bottom:20px; box-shadow:6px 6px 54px #0000000D;">
        <div style="justify-content: space-between; display:flex;align-items: center;">
        <h3 style="font-size:24px; font-weight:bold; margin-bottom:20px;">Result Assessment</h3>
        <div class="action-btns" style="display:flex; justify-content:flex-end; gap:5px; flex-wrap: wrap;">
                 {{-- Tombol Export di card result --}}
                <a href="{{ route('assessment.export', $assessment) }}" 
                   class="btn  btn-sm btn-success"  
                   style="width: 80px;  color:white; padding:8px 0; text-align:center;  font-weight:bold;">
                    Export
                </a>
                <form action="{{ route('assessment.import', $assessment) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      style="display:inline;">
                    @csrf
                    <label for="excel_file"
                           class="btn btn-sm btn-warning"
                           style="width: 80px;background:#F0AD4E; font-weight:bold; padding:8px 0; text-align:center; cursor:pointer;">
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
                

               
            </div>
        </div>

        @if($assessment->answers->isNotEmpty() && !is_null($assessment->total_score) && !is_null($assessment->risk_level))
        {{-- SUDAH ADA NILAI: Tampilkan data hasil dan tombol Edit --}}
        <div style="display:flex; justify-content: space-between; align-items: flex-start;">
            <div style="display:flex; flex-direction:column; gap:10px; width:100%; padding-left:5px;">
                <span style="font-size:16px; font-weight:bold;">
                    Total Score: {{ $assessment->total_score ?? '-' }}%
                </span>
                <span style="font-size:16px; font-weight:bold;">
                    Risk Level: {{$assessment->risk_level_label ? strtoupper($assessment->risk_level_label) : '-' }}
                </span>
                <span style="font-size:16px;">Deskripsi: {{ $assessment->notes ?? '-' }}</span>
            </div>
            
        </div>
        @else
        {{-- BELUM ADA NILAI: Tampilkan pesan dan tombol Upload & Export --}}
        <div style="display:flex; justify-content: space-between; align-items: center;">
            <p style="font-size:16px; margin-bottom:20px; flex:1;">
                Please upload the completed Excel questionnaire to process the scoring and risk analysis.
            </p>
        </div>
        @endif
    </div>

    {{-- DETAIL RESULTS --}}
    <div style="background:#FFF; border-radius:14px; padding:40px; margin-bottom:20px; box-shadow:6px 6px 54px #0000000D;">
        <h3 style="font-size:24px; font-weight:bold; margin-bottom:20px;">Detail Results Assessment</h3>

        <div style="overflow-x:auto;">
            <div style="min-width:800px;">
                
                {{-- TABLE HEADER --}}
                <div style="display:flex; background:#F1F3F9; padding:10px; border-radius:12px; font-weight:bold;">
                    <div style="width:40px;">No</div>
                    <div style="flex:2;">Criteria</div>
                    <div style="flex:2;">Answer</div>
                    <div style="width:80px;">Score</div>
                </div>

                {{-- TABLE DATA --}}
                @foreach(\App\Models\Category::with('questions')->get() as $category)
                @php
                    $categoryAnswers = $assessment->answers
                        ->filter(fn($a) => $a->question->category_id == $category->id)
                        ->values();

                    $indicator = $assessment->category_scores[$category->id]['indicator'] ?? '-';
                    $categoryTotal = $assessment->category_scores[$category->id]['score'] ?? 0;
                @endphp

                {{-- Category Header --}}
                <div style="font-weight:bold; margin-top:20px; display:flex; justify-content:space-between;">
                    <div>
                    <span style="width:200px;">{{ $category->name }}</span>
                    <span style="width:150px; color:#4880FF; text-align:center;">
                        ({{ ucfirst($indicator) }})
                    </span>
                    </div>
                    <span style="width:120px; color:#202224; text-align:center; margin-left:40px;">
                        Total: {{ $categoryTotal }}%
                    </span>
                </div>

                @if($categoryAnswers->isEmpty())
                    <div style="padding:10px; color:#999; font-style:italic;">
                        Tidak ada pertanyaan/jawaban untuk kategori ini.
                    </div>
                @else
                    @foreach($categoryAnswers as $answer)
                        <div style="display:flex; padding:10px; border-bottom:1px solid #eee;">
                            <div style="width:40px;">{{ $loop->iteration }}</div>
                            <div style="flex:2;">{{ $answer->question->question_text }}</div>
                            <div style="flex:2; padding-left:10px;">{{ $answer->answer_text ?? '-' }}</div>
                            <div style="width:80px; font-weight:bold;">
                                {{ $answer->score ?? '-' }}
                            </div>
                        </div>
                    @endforeach
                @endif
                @endforeach

            </div>
        </div>

        {{-- TOTAL SCORE --}}
        @if($assessment->answers->isNotEmpty() && !is_null($assessment->total_score) && !is_null($assessment->risk_level))
        <div style="display:flex; justify-content:flex-end; margin-top:20px; font-weight:bold;">
            <div>Total Score: {{ $assessment->total_score ?? '-' }}%</div>
        </div>
        @endif
    </div>

</div>
@endsection