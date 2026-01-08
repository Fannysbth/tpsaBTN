@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Dashboard">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div class="assessment-page">

    {{-- SUMMARY CARD --}}
    <div class="summary-row">

        {{-- TOTAL CATEGORY --}}
        <div   style="background: #ffff; padding-top: 30px;
    padding-bottom: 30px;
    padding-left: 40px;
    padding-right: 40px; border-radius: 12px; display: flex;justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-direction: column; align-items: center;">
                <span class="text2">Total Category</span>
                <span class="text3">{{ number_format($totalCategories) }}</span>
            </div>
            <i class="fa-solid fa-layer-group icon-card"  style= "color: #8280FF;"></i>
        </div>

        <div   style="background: #ffff; padding-top: 30px;
    padding-bottom: 30px;
    padding-left: 40px;
    padding-right: 40px; border-radius: 12px; display: flex;justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-direction: column; align-items: center;">
               <span class="text2">Total Question</span>
                <span class="text3">{{ number_format($totalQuestions) }}</span>
            </div>
             <i class="fa-solid fa-circle-question icon-card" style= "color: #FEC53D;"></i>
        </div>

        <div   style="background: #ffff; padding-top: 30px;
    padding-bottom: 30px;
    padding-left: 40px;
    padding-right: 40px; border-radius: 12px; display: flex;justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-direction: column; align-items: center;">
              <span class="text2">Total Assessment</span>
                <span class="text3">{{ number_format($totalAssessments) }}</span>
            </div>
            <i class="fa-solid fa-chart-simple icon-card" style= "color: #4AD991;"></i>
        </div>

    </div>

    {{-- TABLE --}}
    <div class="table-card" >

        {{-- TABLE HEADER --}}

        <div class="table-header" style="
    display: flex;
    justify-content: space-between;
    align-items: center;
">
            <span class="text4">All Results Assessment</span>

            <select id="monthFilter" class="button-row-view">
                @for($i=1;$i<=12;$i++)
                    <option value="{{ $i }}" {{ $i==$month?'selected':'' }}>
                        {{ date('F', mktime(0,0,0,$i,1)) }}
                    </option>
                @endfor
            </select>
        </div>

        {{-- TABLE HEAD --}}
        <div class="table-head" style=" 
    background-color: #F1F4F9;
    padding: 12px 16px;
    border-radius: 8px;
    margin-top: 20px;text-align: center;">
            <span>No</span>
            <span>Company Name</span>
            <span>Total Score</span>
            <span>Risk Level</span>
            <span>Detail</span>
        </div>

        {{-- TABLE BODY --}}
        @foreach($assessments as $index => $assessment)
        @php
            $risk = $assessment['risk_level'];
            $btnClass = $risk === 'high' ? 'button3' :
                        ($risk === 'medium' ? 'button2' : 'button');
            $label = $risk === 'high' ? 'Kurang Memadai' :
                     ($risk === 'medium' ? 'Cukup Memadai' : 'Sangat Memadai');
        @endphp

       <div class="table-row">
    <span style="text-align: center;">{{ str_pad($index+1,2,'0',STR_PAD_LEFT) }}</span>

    <span>{{ $assessment['company_name'] }}</span>

    <span style="text-align: center;">
        {{ $assessment['total_score'] }}
    </span>

    <button class="{{ $btnClass }}" style="margin: 0 auto;">
        {{ $label }}
    </button>

    <a href="{{ route('assessment.show', $assessment['id']) }}" style="text-align: center;">
        <i class="fa-solid fa-arrow-up-right-from-square icon-detail"></i>
    </a>
</div>

        @endforeach

    </div>

</div>
@endsection
