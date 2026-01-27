@extends('layouts.app')

@section('content')
<div style="max-width:1400px;margin:0 auto;padding:20px;">

{{-- FILE ERROR --}}
@if($errors->has('file'))
<div style="background:#FFEAEA;color:#842029;padding:16px;border-radius:8px;margin-bottom:20px;">
    {{ $errors->first('file') }}
</div>
@endif

<div style="background:#fff;border-radius:12px;padding:30px;box-shadow:0 2px 12px rgba(0,0,0,.08);">

<h2>Preview Import Questionnaire</h2>
<p>Total pertanyaan: <strong>{{ $totalQuestions }}</strong></p>

{{-- IMPORT ERROR --}}
@if(!empty($importErrors))
<div style="background:#FFF2F0;padding:16px;border-radius:8px;margin-bottom:20px;">
    <strong>Terdapat {{ count($importErrors) }} error:</strong>
    <ul>
        @foreach($importErrors as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($totalQuestions === 0)
<div style="background:#FFF3CD;color:#664D03;padding:16px;border-radius:8px;">
    Tidak ada pertanyaan yang dapat dibaca.
</div>
@endif

@if($totalQuestions > 0)
<form method="POST" action="{{ route('questionnaire.import') }}">
@csrf

<div style="max-height:520px;overflow:auto;">

@foreach($importData as $i => $q)

@php
    // ==== AMANIN INDICATOR ====
    $indicators = is_array($q['indicator'] ?? null)
        ? $q['indicator']
        : (is_string($q['indicator'] ?? null)
            ? json_decode($q['indicator'], true) ?? []
            : []);
@endphp

<div class="question-card" style="border:1px solid #E0E0E0;border-radius:8px;padding:16px;margin-bottom:16px;">

    

    <div style="display:flex;justify-content:space-between;">
        <div>
            <span style="font-size:12px;background:#F0F7FF;padding:4px 8px;">
                Baris {{ $q['row_number'] }}
            </span>
            <h4 style="margin-top:8px;">{{ $q['question_text'] }}</h4>
        </div>

        <label class="switch">
            <input type="checkbox" name="questions[{{ $i }}][import]" checked
                onchange="toggleQuestion({{ $i }},this.checked)">
            <span class="slider"></span>
        </label>
    </div>

    {{-- HIDDEN --}}
    <input type="hidden" name="questions[{{ $i }}][question_text]" value="{{ $q['question_text'] }}">
    <input type="hidden" name="questions[{{ $i }}][no]" value="{{ $q['no'] }}">
    <input type="hidden" name="questions[{{ $i }}][is_new]" value="1">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:16px;">

        {{-- LEFT --}}
        <div>
            <label>Kategori</label>
            <select name="questions[{{ $i }}][category_id]" class="form-control">
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $cat->id==$q['category_id']?'selected':'' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>

            <label style="margin-top:10px;">Sub</label>
            <input type="text" name="questions[{{ $i }}][sub]" value="{{ $q['sub'] }}" class="form-control">

            <label style="margin-top:10px;">Clue</label>
            <input type="text" name="questions[{{ $i }}][clue]" value="{{ $q['clue'] ?? '' }}" class="form-control">
        </div>

        {{-- RIGHT --}}
        <div>
            <label style="font-weight:600;">Indicator</label><br>

            <label>
                <input type="checkbox"
                    name="questions[{{ $i }}][indicator][]"
                    value="high"
                    {{ in_array('high',$indicators)?'checked':'' }}>
                High
            </label>

            <label style="margin-left:12px;">
                <input type="checkbox"
                    name="questions[{{ $i }}][indicator][]"
                    value="medium"
                    {{ in_array('medium',$indicators)?'checked':'' }}>
                Medium
            </label>

            <label style="margin-left:12px;">
                <input type="checkbox"
                    name="questions[{{ $i }}][indicator][]"
                    value="low"
                    {{ in_array('low',$indicators)?'checked':'' }}>
                Low
            </label>

            <label style="margin-top:10px;">Tipe</label>
            <select name="questions[{{ $i }}][question_type]"
                onchange="toggleOption({{ $i }},this.value)"
                class="form-control">
                <option value="isian" {{ $q['question_type']=='isian'?'selected':'' }}>Isian</option>
                <option value="pilihan" {{ $q['question_type']=='pilihan'?'selected':'' }}>Pilihan</option>
            </select>

            {{-- OPTIONS --}}
            <div id="options-{{ $i }}" style="margin-top:10px;{{ $q['question_type']=='pilihan'?'':'display:none' }}">
                <strong>Options:</strong>

                @foreach($q['options'] ?? [] as $oi => $opt)
                <div style="display:flex;gap:8px;margin-top:6px;">
                    <input type="text"
                        name="questions[{{ $i }}][options][{{ $oi }}][text]"
                        value="{{ $opt['text'] }}"
                        class="form-control">
                    <input type="number"
                        name="questions[{{ $i }}][options][{{ $oi }}][score]"
                        value="{{ $opt['score'] }}"
                        style="width:80px;">
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endforeach
</div>

<div style="margin-top:20px;display:flex;justify-content:space-between;">
    <label>
        <input type="checkbox" checked onchange="toggleAllQuestions(this.checked)">
        Pilih Semua
    </label>
    <button class="btn btn-primary">Konfirmasi Import</button>
</div>

</form>
@endif
</div>
</div>

{{-- STYLE + SCRIPT --}}
<style>
.switch{position:relative;width:60px;height:34px}
.switch input{opacity:0}
.slider{position:absolute;inset:0;background:#ccc;border-radius:34px}
.slider:before{content:"";position:absolute;height:26px;width:26px;left:4px;bottom:4px;background:white;border-radius:50%}
input:checked+.slider{background:#2196F3}
input:checked+.slider:before{transform:translateX(26px)}
.question-card.disabled{opacity:.5}
</style>

<script>
function toggleQuestion(i,v){
    document.querySelectorAll('.question-card')[i]?.classList.toggle('disabled',!v)
}
function toggleAllQuestions(v){
    document.querySelectorAll('input[name$="[import]"]').forEach((c,i)=>{
        c.checked=v; toggleQuestion(i,v)
    })
}
function toggleOption(i,val){
    const d=document.getElementById('options-'+i)
    if(d) d.style.display=val==='pilihan'?'block':'none'
}
</script>
@endsection
