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

</div>
@endsection
