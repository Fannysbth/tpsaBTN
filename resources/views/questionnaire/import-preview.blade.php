@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Questionnaire">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div style="max-width:1400px;margin:0 auto;padding:20px;">

{{-- FILE ERROR --}}
@if($errors->has('file'))
<div style="background:#FFEAEA;color:#842029;padding:16px;border-radius:8px;margin-bottom:20px;">
    {{ $errors->first('file') }}
</div>
@endif

<div style="background:#fff;border-radius:12px;padding:30px;box-shadow:0 2px 12px rgba(0,0,0,.08);">

<div style="display:flex;align-items:center;gap:12px;margin-bottom:10px; justify-content:space-between;">
    <h2 style="margin:0;">Preview Import Questionnaire</h2>
    <button type="button"
            onclick="history.back()"
            style="padding:8px 14px;
                   border:1px solid #DEE2E6;
                   background:#4880FF;
                   border-radius:8px;
                   cursor:pointer;
                   font-size:14px;
                   color:#fff;">
        ← Back
    </button>
</div>

<form method="POST" action="{{ route('questionnaire.import') }}">
@csrf

{{-- QUESTIONS CONTAINER --}}
<div id="questions-container" style="height: calc(100vh - 260px); overflow-y:auto; padding:10px;">

@php
    $actionColors = [
        'add' => '#E7F1FF',
        'update' => '#FFF3CD',
        'delete' => '#F8D7DA'
    ];
    
    $actionTexts = [
        'add' => 'New Question',
        'update' => 'Updated Question',
        'delete' => 'Deleted Question'
    ];
    
    $actionIcons = [
        'add' => '➕',
        'update' => '✏️',
        'delete' => '🗑️'
    ];
@endphp

@foreach($importData as $i => $q)
@php
    $action = $q['action'] ?? 'new';
    $isDeleted = $action === 'delete';
    $isNew = $action === 'new';
    
    $indicators = is_array($q['indicator'] ?? null)
        ? $q['indicator']
        : (is_string($q['indicator'] ?? null)
            ? json_decode($q['indicator'], true) ?? []
            : []);
@endphp

<div class="question-card import-card" 
     data-question-index="{{ $i }}"
     style="border: 1px solid {{ $isDeleted ? '#DC3545' : '#E0E0E0' }};
            background: {{ $actionColors[$action] ?? '#fff' }};
            {{ $isDeleted ? 'opacity: 0.8;' : '' }}">
    
    {{-- HEADER --}}
    <div  style="padding: 20px; display: flex; justify-content: space-between; align-items: center;" onclick="toggleCard(this)">
        <div style="display:flex;align-items:center;gap:16px; flex:1;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="question-number" >
                    {{ $i + 1 }}
                </div>
                <div>
                    <div style="font-weight:600;color:#202224; display:flex; align-items:center; gap:8px;">
                        <span>{{ $actionIcons[$action] ?? '' }}</span>
                        <span class="question-title">{{ Str::limit($q['question_text'], 80) }}</span>
                    </div>
                    <div style="font-size:12px;color:{{ $isDeleted ? '#DC3545' : '#6C757D' }}; font-weight:600;">
                        {{ $actionTexts[$action] ?? strtoupper($action) }}

                        @if(!$isDeleted)
                        • {{($q['question_type'] ?? '') === 'pilihan'? 'Multiple Choice' : 'Text Answer' }}
                        • {{ $categories->firstWhere('id',$q['category_id'])->name ?? '' }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <label class="switch" onclick="event.stopPropagation()">
            @if($isDeleted)
            <input type="checkbox"
                   name="questions[{{ $i }}][import]"
                   checked
                   disabled
                   style="cursor: not-allowed;">
            <span class="slider" style="background:#DC3545;"></span>
            @else
            <input type="checkbox"
                   name="questions[{{ $i }}][import]"
                   checked
                   onchange="toggleQuestion({{ $i }},this.checked)">
            <span class="slider"></span>
            @endif
        </label>
    </div>

    {{-- BODY --}}
    <div class="question-body" style="display:{{ $isNew ? 'block' : 'none' }}; padding:20px; border-top:1px solid rgba(0,0,0,0.1);">
        
        <input type="hidden" name="questions[{{ $i }}][action]" value="{{ $action }}">

@if($q['id'] ?? false)
    <input type="hidden" name="questions[{{ $i }}][id]" value="{{ $q['id'] }}">
@endif

@if(!$isDeleted)
    <input type="hidden" name="questions[{{ $i }}][no]" value="{{ $q['no'] ?? '' }}">
@endif


        @if($isDeleted)
        {{-- DELETED QUESTION VIEW --}}
        <div style="color:#721c24; padding:15px; background:#f8d7da; border-radius:8px;">
            <div style="display:flex; gap:30px;">
                <div style="flex:1;">
                    <div style="margin-bottom:10px;">
                        <strong>Question:</strong>
                        <div style="margin-top:5px; padding:10px; background:white; border-radius:6px;">
                            {{ $q['question_text'] }}
                        </div>
                    </div>
                    
                    <div>
                        <strong>Category:</strong>
                        <div style="margin-top:5px; padding:10px; background:white; border-radius:6px;">
                            {{ $categories->firstWhere('id',$q['category_id'])->name ?? 'Unknown' }}
                        </div>
                    </div>
                </div>
                
                <div style="flex:1;">
                    <div style="margin-bottom:10px;">
                        <strong>Type:</strong>
                        <div style="margin-top:5px; padding:10px; background:white; border-radius:6px;">
                            {{ ($q['question_type'] ?? '') === 'pilihan'? 'Multiple Choice' : 'Text Answer' }}
                        </div>
                    </div>
                    
                    <div>
                        <strong>Status:</strong>
                        <div style="margin-top:5px; padding:10px; background:white; border-radius:6px; color:#DC3545; font-weight:bold;">
                            Will be deleted
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        @else
        {{-- NORMAL QUESTION VIEW (NEW/UPDATE) --}}
        <div style="display:flex;gap:40px;">
            {{-- LEFT COLUMN --}}
            <div style="flex:1;display:flex;flex-direction:column;gap:15px;">
                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Question</label>
                    <textarea class="question-text" 
                              rows="3"
                              style="color: #202224; font-size: 14px; background: #fff; border: 1px solid #DEE2E6; border-radius: 8px; 
                                                   padding: 12px; width: 100%; resize: vertical; min-height: 60px;"
                              oninput="updateQuestionPreview({{ $i }}, this.value)"
                              name="questions[{{ $i }}][question_text]">{{ $q['question_text'] }}</textarea>
                </div>

                {{-- ANSWER SECTION --}}
                <div class="answer-section" id="answer-section-{{ $i }}">
                    @if(($q['question_type'] ?? null) === 'pilihan')
                    <div>
                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 12px; display: block;">
                            Options
                            <span style="font-weight:400;color:#6c757d;font-size:12px;margin-left:8px;">
                                (Score: {{ collect($q['options'] ?? [])->sum('score') }})
                            </span>
                        </label>
                        <div class="options-container" id="options-{{ $i }}">
                            @foreach($q['options'] ?? [] as $oi => $opt)
                            <div class="option-item" style="display:flex;gap:8px;margin-top:8px;align-items:center;">
                                <div style="min-width:24px;text-align:center;font-weight:600;">{{ $oi + 1 }}.</div>
                                <input type="text"
                                       name="questions[{{ $i }}][options][{{ $oi }}][text]"
                                       value="{{ $opt['text'] }}"
                                       placeholder="Option text"
                                       style="flex: 1; padding: 10px 12px; border: 1px solid #DEE2E6; border-radius: 6px; font-size: 14px; background:#fff;">
                                <input type="number"
                                       name="questions[{{ $i }}][options][{{ $oi }}][score]"
                                       value="{{ $opt['score'] }}"
                                       placeholder="Score"
                                       min="0"
                                       step="1"
                                       style="width: 100px; padding: 10px; border: 1px solid #DEE2E6; border-radius: 6px; font-size: 14px; background:#fff;">
                                @if(count($q['options'] ?? []) > 1)
                                <button type="button"
                                        onclick="removeOption(this)"
                                        style="background:none;border:none;color:#FF4D4F;font-size:20px;cursor:pointer;padding:5px;">
                                        ×
                                </button>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        <button type="button"
                                onclick="addOption({{ $i }})"
                                class="add-option-btn"
                                style="margin-top: 12px; padding: 8px 16px; background: #4880FF; color: white; border: none; 
                                                                   border-radius: 6px; font-size: 14px; cursor: pointer;">
                            + Add Option
                        </button>
                    </div>
                    @else
                    <div>
                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Answer Clue</label>
                        <input type="text"
                               class="clue-input"
                               name="questions[{{ $i }}][clue]"
                               value="{{ $q['clue'] ?? '' }}"
                               placeholder="Enter answer clue"
                               style="padding: 12px; border: 1px solid #DEE2E6; border-radius: 8px; font-size: 14px; width: 100%; background:#fff;">
                    </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div style="flex:1;display:flex;flex-direction:column;gap:15px;">
                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Type</label>
                    <select class="question-type-select"
                            name="questions[{{ $i }}][question_type]"
                            onchange="changeQuestionType({{ $i }}, this.value)"
                            style="padding: 12px; border: 1px solid #DEE2E6; border-radius: 8px; font-size: 14px; 
                                                   color: #202224; background: white; width: 100%; cursor: pointer;">
                        <option value="isian" {{ ($q['question_type'] ?? '') == 'isian' ? 'selected' : '' }}>Text Answer</option>
                        <option value="pilihan" {{ ($q['question_type'] ?? '') == 'pilihan' ? 'selected' : '' }}>Multiple Choice</option>
                    </select>
                </div>

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Category</label>
                    <select class="form-control" 
                            name="questions[{{ $i }}][category_id]"
                            style="padding: 12px; border: 1px solid #DEE2E6; border-radius: 8px; font-size: 14px; 
                                                   color: #202224; background: white; width: 100%; cursor: pointer;">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $cat->id == ($q['category_id'] ?? '') ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Indicator</label>
                    <div style="display:flex;gap:24px;">
                        @foreach(['high'=>'High','medium'=>'Medium','low'=>'Low'] as $v=>$l)
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox"
                                   name="questions[{{ $i }}][indicator][]"
                                   value="{{ $v }}"
                                   {{ in_array($v,$indicators)?'checked':'' }}
                                   style="cursor:pointer;">
                            <span>{{ $l }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Sub Category</label>
                    <input type="text"
                           class="form-control"
                           name="questions[{{ $i }}][sub]"
                           value="{{ $q['sub'] ?? '' }}"
                           style="padding: 12px; border: 1px solid #DEE2E6; border-radius: 8px; font-size: 14px; background:#fff;">
                </div>

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Attachment Note</label>
                    <input type="text"
                           class="form-control"
                           name="questions[{{ $i }}][attachment_text]"
                           value="{{ $q['attachment_text'] ?? '-' }}"
                           style="padding: 12px; border: 1px solid #DEE2E6; border-radius: 8px; font-size: 14px; width: 100%; background:#fff;">
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endforeach
</div>

{{-- FOOTER --}}
<div style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <p>Total pertanyaan: <strong>{{ $totalQuestions }}</strong></p>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" checked onchange="toggleAllQuestions(this.checked)">
            <span>Pilih Semua</span>
        </label>
    </div>
    
    <div style="display:flex;gap:12px;">
        <button type="button"
                onclick="addNewQuestion()"
                style="padding:10px 18px;border:1px dashed #4880FF;background:#F8FAFF;color:#4880FF;
                       border-radius:8px;cursor:pointer;font-weight:600;">
            + Add Question
        </button>
        <button type="submit" 
                class="btn btn-primary"
                style="padding:10px 24px;background:#4880FF;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;">
            Konfirmasi Import
        </button>
    </div>
</div>

</form>

</div>
</div>



<script>
const questionCache = {};

// INIT CACHE
document.querySelectorAll('.import-card').forEach(card => {
    const i = card.dataset.questionIndex;
    const action = card.querySelector('input[name$="[action]"]')?.value;
    if (action === 'delete') return;

    questionCache[i] = { pilihan: { values: [] }, isian: '' };

    const type = card.querySelector('.question-type-select')?.value;
    const answer = card.querySelector('.answer-section');

    if (type === 'pilihan') {
        answer?.querySelectorAll('.option-item').forEach(o => {
            questionCache[i].pilihan.values.push({
                text: o.querySelector('input[type="text"]').value,
                score: o.querySelector('input[type="number"]').value
            });
        });
    } else {
        const clue = answer?.querySelector('.clue-input');
        if (clue) questionCache[i].isian = clue.value;
    }
});

// TOGGLE CARD
function toggleCard(el) {
    const card = el.closest('.import-card');
    if (card.classList.contains('disabled')) return;
    const body = el.nextElementSibling;
    body.style.display = body.style.display === 'none' ? 'block' : 'none';
}

// TOGGLE QUESTION
function toggleQuestion(i, val) {
    const card = document.querySelector(`.import-card[data-question-index="${i}"]`);
    if (!card || card.querySelector('input[name$="[import]"]')?.disabled) return;
    card.classList.toggle('disabled', !val);
}


// TOGGLE ALL
function toggleAllQuestions(val) {
    document.querySelectorAll('.import-card').forEach(card => {
        const checkbox = card.querySelector('input[name$="[import]"]');
        if (!checkbox || checkbox.disabled) return;
        checkbox.checked = val;
        toggleQuestion(card.dataset.questionIndex, val);
    });
}

// UPDATE PREVIEW TITLE
function updateQuestionPreview(i, text) {
    const card = document.querySelector(`.import-card[data-question-index="${i}"]`);
    const title = card.querySelector('.question-title');
    if (title) {
        title.textContent = text.length > 80 ? text.slice(0, 77) + '...' : text;
    }
}

// CHANGE TYPE
function changeQuestionType(i, type) {
    const section = document.getElementById(`answer-section-${i}`);
    if (!questionCache[i]) questionCache[i] = { pilihan: { values: [] }, isian: '' };

    if (type === 'isian') {
        questionCache[i].pilihan.values = [...section.querySelectorAll('.option-item')].map(o => ({
            text: o.querySelector('input[type="text"]').value,
            score: o.querySelector('input[type="number"]').value
        }));

        section.innerHTML = `
            <label>Answer Clue</label>
            <input class="clue-input" name="questions[${i}][clue]" value="${questionCache[i].isian}">
        `;
    } else {
        questionCache[i].isian = section.querySelector('.clue-input')?.value || '';

        section.innerHTML = `
            <div class="options-container" id="options-${i}">
                ${renderOptions(i)}
            </div>
            <button type="button" onclick="addOption(${i})">+ Add Option</button>
        `;
    }
}

// RENDER OPTIONS
function renderOptions(i) {
    const values = questionCache[i].pilihan.values;
    if (!values.length) values.push({ text: '', score: '' });

    return values.map((o, idx) => `
        <div class="option-item">
            <input name="questions[${i}][options][${idx}][text]" value="${o.text}">
            <input name="questions[${i}][options][${idx}][score]" value="${o.score}">
            ${values.length > 1 ? `<button onclick="this.parentNode.remove()">×</button>` : ''}
        </div>
    `).join('');
}

// CACHE INPUT
document.addEventListener('input', e => {
    const card = e.target.closest('.import-card');
    if (!card) return;
    const i = card.dataset.questionIndex;

    if (e.target.classList.contains('clue-input')) {
        questionCache[i].isian = e.target.value;
    }

    if (e.target.closest('.option-item')) {
        questionCache[i].pilihan.values = [...card.querySelectorAll('.option-item')].map(o => ({
            text: o.querySelector('input[type="text"]').value,
            score: o.querySelector('input[type="number"]').value
        }));
    }
});
</script>


@endsection