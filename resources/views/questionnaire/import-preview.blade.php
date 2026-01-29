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

{{-- QUESTIONS CONTAINER --}}
<div id="questions-container" style="height: calc(100vh - 260px); overflow-y:auto; padding:10px;">

@foreach($importData as $i => $q)
@php
    $indicators = is_array($q['indicator'] ?? null)
        ? $q['indicator']
        : (is_string($q['indicator'] ?? null)
            ? json_decode($q['indicator'], true) ?? []
            : []);
@endphp

<div class="question-card import-card">

    {{-- HEADER --}}
    <div class="question-header" onclick="toggleCard(this)">
        <div style="display:flex;align-items:center;gap:16px;">
            <div class="question-number">{{ $i + 1 }}</div>
            <div>
                <div style="font-weight:600;color:#202224;">
                    {{ Str::limit($q['question_text'],80) }}
                </div>
                <div style="font-size:12px;color:#6C757D;">
                    {{ $q['question_type']=='pilihan'?'Multiple Choice':'Text Answer' }}
                    • {{ $categories->firstWhere('id',$q['category_id'])->name ?? '-' }}
                </div>
            </div>
        </div>

        <label class="switch" onclick="event.stopPropagation()">
            <input type="checkbox"
                   name="questions[{{ $i }}][import]"
                   checked
                   onchange="toggleQuestion({{ $i }},this.checked)">
            <span class="slider"></span>
        </label>
    </div>

    {{-- BODY --}}
    <div class="question-body" style="display:none;">

        {{-- HIDDEN --}}
        <input type="hidden" name="questions[{{ $i }}][question_text]" value="{{ $q['question_text'] }}">
        <input type="hidden" name="questions[{{ $i }}][no]" value="{{ $q['no'] }}">
        <input type="hidden" name="questions[{{ $i }}][is_new]" value="1">

        <div style="display:flex;gap:40px;">

            {{-- LEFT COLUMN --}}
            <div style="flex:1;display:flex;flex-direction:column;gap:20px;">

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Question</label>
                    <textarea class="form-control" 
                              rows="2"
                              style="color: #202224; font-size: 14px; background: #F8F9FA; border: 1px solid #DEE2E6; border-radius: 8px; 
                                                   padding: 12px; width: 100%; resize: vertical; min-height: 36px; transition: border 0.2s;" 
                              readonly>
{{ $q['question_text'] }}
                    </textarea>
                </div>

                @if($q['question_type']=='pilihan')
                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 12px; display: block;">Options</label>
                    @foreach($q['options'] ?? [] as $oi => $opt)
                    <div style="display:flex;gap:8px;margin-top:6px;">
                        <input type="text"
                               name="questions[{{ $i }}][options][{{ $oi }}][text]"
                               value="{{ $opt['text'] }}"
                               style="flex: 1; padding: 10px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;">
                        <input type="number"
                               name="questions[{{ $i }}][options][{{ $oi }}][score]"
                               value="{{ $opt['score'] }}"
                               style="width: 80px; padding: 10px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;">
                        <button type="button"
                    onclick="removeOption(this)"
                    style="background:none;border:none;color:#FF4D4F;font-size:18px;cursor:pointer;">
                ×
            </button>
                    </div>
                    @endforeach
                </div>
                <button type="button"
            onclick="addOption({{ $i }})"
            style="margin-top: 12px; padding: 10px 20px; background: #4880FF; color: white; border: none; 
                                                               border-radius: 6px; font-size: 14px; cursor: pointer;">
        + Add Option
    </button>
                @else
                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Answer Clue</label>
                    <input type="text"
                           class="form-control"
                           name="questions[{{ $i }}][clue]"
                           value="{{ $q['clue'] ?? '' }}"
                           style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; width: 100%;">
                </div>
                @endif
            </div>

            {{-- RIGHT COLUMN --}}
            <div style="flex:1;display:flex;flex-direction:column;gap:10px;">

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Type</label>
                    <select class="form-control"
                            name="questions[{{ $i }}][question_type]"
                            onchange="toggleOption({{ $i }},this.value)"
                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; 
                                                   color: #202224; background: white; width: 100%; cursor: pointer;">
                        <option value="isian" {{ $q['question_type']=='isian'?'selected':'' }}>Text Answer</option>
                        <option value="pilihan" {{ $q['question_type']=='pilihan'?'selected':'' }}>Multiple Choice</option>
                    </select>
                </div>

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Category</label>
                    <select class="form-control" 
                            name="questions[{{ $i }}][category_id]"
                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; 
                                                   color: #202224; background: white; width: 100%; cursor: pointer;">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $cat->id==$q['category_id']?'selected':'' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Indicator</label>
                    <div style="display:flex;gap:24px;">
                        @foreach(['high'=>'High','medium'=>'Medium','low'=>'Low'] as $v=>$l)
                        <label>
                            <input type="checkbox"
                                   name="questions[{{ $i }}][indicator][]"
                                   value="{{ $v }}"
                                   {{ in_array($v,$indicators)?'checked':'' }}>
                            {{ $l }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Sub Category</label>
                    <input type="text"
                           class="form-control"
                           name="questions[{{ $i }}][sub]"
                           value="{{ $q['sub'] }}"
                           style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px;">
                </div>

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Attachment Note</label>
                    <input type="text"
                           class="form-control"
                           name="questions[{{ $i }}][attachment_text]"
                           value="{{ $q['attachment_text'] ?? '-' }}"
                           style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; width: 100%;">
                </div>

            </div>
        </div>
    </div>
</div>
@endforeach
</div>

{{-- FOOTER --}}
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

{{-- STYLE --}}
<style>
.import-card{border:1px solid #E0E0E0;border-radius:12px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:16px}
.question-header{padding:20px;display:flex;justify-content:space-between;align-items:center;cursor:pointer; background: #FCFCFC;}
.question-body{padding:5px;border-top:1px solid #F0F0F0}
.question-number{width:32px;height:32px;border-radius:50%;background:#F0F7FF;color:#4880FF;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:14px}
.switch{position:relative;width:60px;height:34px}
.switch input{opacity:0}
.slider{position:absolute;inset:0;background:#ccc;border-radius:34px}
.slider:before{content:"";position:absolute;height:26px;width:26px;left:4px;bottom:4px;background:white;border-radius:50%}
input:checked+.slider{background:#2196F3}
input:checked+.slider:before{transform:translateX(26px)}
.question-card.disabled{opacity:.5}
</style>

{{-- SCRIPT --}}
<script>
function toggleCard(el){
    const body = el.nextElementSibling
    body.style.display = body.style.display === 'none' ? 'block' : 'none'
}
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
