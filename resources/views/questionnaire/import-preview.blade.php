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

{{-- FOOTER --}}
<div style="margin-top:20px;display:flex;justify-content:space-between;">
    <p>Total pertanyaan: <strong>{{ $totalQuestions }}</strong></p>
    <label>
        <input type="checkbox" checked onchange="toggleAllQuestions(this.checked)">
        Pilih Semua
    </label>
</div>




@if($totalQuestions === 0)
<div style="background:#FFF3CD;color:#664D03;padding:16px;border-radius:8px;">
    Tidak ada pertanyaan baru.
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

<div class="question-card import-card" data-question-index="{{ $i }}">
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
                    • {{ $categories->firstWhere('id',$q['category_id'])->name ?? '' }}
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
            <div style="flex:1;display:flex;flex-direction:column;gap:10px;">

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Question</label>
                    <textarea class="form-control question-text" 
                              rows="2"
                              style="color: #202224; font-size: 14px; background: #F8F9FA; border: 1px solid #DEE2E6; border-radius: 8px; 
                                                   padding: 12px; width: 100%; resize: vertical; min-height: 36px; transition: border 0.2s;" 
                              readonly>
{{ $q['question_text'] }}
                    </textarea>
                </div>

                {{-- ANSWER SECTION --}}
                <div class="answer-section" id="answer-section-{{ $i }}">
                    @if($q['question_type']=='pilihan')
                    <div>
                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 12px; display: block;">Options</label>
                        <div class="options-container" id="options-{{ $i }}">
                            @foreach($q['options'] ?? [] as $oi => $opt)
                            <div class="option-item" style="display:flex;gap:8px;margin-top:6px;">
                                <input type="text"
                                       name="questions[{{ $i }}][options][{{ $oi }}][text]"
                                       value="{{ $opt['text'] }}"
                                       placeholder="Option text"
                                       style="flex: 1; padding: 10px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;">
                                <input type="number"
                                       name="questions[{{ $i }}][options][{{ $oi }}][score]"
                                       value="{{ $opt['score'] }}"
                                       placeholder="Score"
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
                                class="add-option-btn"
                                style="margin-top: 12px; padding: 10px 20px; background: #4880FF; color: white; border: none; 
                                                                   border-radius: 6px; font-size: 14px; cursor: pointer;">
                            + Add Option
                        </button>
                    </div>
                    @else
                    <div>
                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Answer Clue</label>
                        <input type="text"
                               class="form-control clue-input"
                               name="questions[{{ $i }}][clue]"
                               value="{{ $q['clue'] ?? '' }}"
                               placeholder="Enter answer clue"
                               style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; width: 100%;">
                    </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div style="flex:1;display:flex;flex-direction:column;gap:10px;">

                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Type</label>
                    <select class="form-control question-type-select"
                            name="questions[{{ $i }}][question_type]"
                            onchange="changeQuestionType({{ $i }}, this.value)"
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
    <button type="button"
        onclick="addNewQuestion()"
        style="padding:10px 18px;border:1px dashed #4880FF;background:#F8FAFF;color:#4880FF;
               border-radius:8px;cursor:pointer;">
    + Add Question
</button>
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

<script>
function addNewQuestion() {
    const container = document.getElementById('questions-container');
    const index = document.querySelectorAll('.import-card').length;

    // init cache
    questionCache[index] = {
        pilihan: { values: [] },
        isian: ''
    };

    const html = `
    <div class="question-card import-card" data-question-index="${index}">
        <div class="question-header" onclick="toggleCard(this)">
            <div style="display:flex;align-items:center;gap:16px;">
                <div class="question-number">${index + 1}</div>
                <div>
                    <div style="font-weight:600;color:#202224;">
                        Pertanyaan baru
                    </div>
                    <div style="font-size:12px;color:#6C757D;">
                        Text Answer • -
                    </div>
                </div>
            </div>

            <label class="switch" onclick="event.stopPropagation()">
                <input type="checkbox"
                       name="questions[${index}][import]"
                       checked>
                <span class="slider"></span>
            </label>
        </div>

        <div class="question-body" style="display:block;">
            <input type="hidden" name="questions[${index}][is_new]" value="1">

            <div style="display:flex;gap:40px;">

                <!-- LEFT -->
                <div style="flex:1;display:flex;flex-direction:column;gap:10px;">

                    <div>
                        <label style="font-weight:600;">Question</label>
                        <textarea name="questions[${index}][question_text]"
                                  placeholder="Tulis pertanyaan di sini..."
                                  rows="2"
                                  style="width:100%;padding:12px;border:1px solid #DEE2E6;
                                         border-radius:8px;"></textarea>
                    </div>

                    <div class="answer-section" id="answer-section-${index}">
                        <div>
                            <label style="font-weight:600;">Answer Clue</label>
                            <input type="text"
                                   class="clue-input"
                                   name="questions[${index}][clue]"
                                   placeholder="Jawaban yang diharapkan"
                                   style="width:100%;padding:12px;border:1px solid #4880FF;
                                          border-radius:8px;">
                        </div>
                    </div>

                </div>

                <!-- RIGHT -->
                <div style="flex:1;display:flex;flex-direction:column;gap:10px;">

                    <div>
                        <label style="font-weight:600;">Type</label>
                        <select name="questions[${index}][question_type]"
                                class="question-type-select"
                                onchange="changeQuestionType(${index},this.value)"
                                style="width:100%;padding:12px;border:1px solid #4880FF;
                                       border-radius:8px;">
                            <option value="isian">Text Answer</option>
                            <option value="pilihan">Multiple Choice</option>
                        </select>
                    </div>

                    <div>
                        <label style="font-weight:600;">Category</label>
                        <select name="questions[${index}][category_id]"
                                style="width:100%;padding:12px;border:1px solid #4880FF;
                                       border-radius:8px;">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="font-weight:600;">Indicator</label>
                        <div style="display:flex;gap:20px;">
                            <label><input type="checkbox" name="questions[${index}][indicator][]" value="high"> High</label>
                            <label><input type="checkbox" name="questions[${index}][indicator][]" value="medium"> Medium</label>
                            <label><input type="checkbox" name="questions[${index}][indicator][]" value="low"> Low</label>
                        </div>
                    </div>

                    <div>
                        <label style="font-weight:600;">Sub Category</label>
                        <input type="text"
                               name="questions[${index}][sub]"
                               placeholder="Sub kategori"
                               style="width:100%;padding:12px;border:1px solid #4880FF;
                                      border-radius:8px;">
                    </div>

                    <div>
                        <label style="font-weight:600;">Attachment Note</label>
                        <input type="text"
                               name="questions[${index}][attachment_text]"
                               placeholder="Catatan lampiran"
                               style="width:100%;padding:12px;border:1px solid #4880FF;
                                      border-radius:8px;">
                    </div>

                </div>
            </div>
        </div>
    </div>
    `;

    container.insertAdjacentHTML('beforeend', html);

    // auto scroll ke bawah
    container.scrollTop = container.scrollHeight;
}
</script>

{{-- SCRIPT --}}
<script>
// Cache untuk menyimpan state setiap pertanyaan
const questionCache = {};

// Inisialisasi cache untuk setiap pertanyaan
document.querySelectorAll('.import-card').forEach(card => {
    const index = card.dataset.questionIndex;
    questionCache[index] = {
    pilihan: {
        values: []
    },
    isian: ''
};

    
    // Simpan state awal
    const typeSelect = card.querySelector('.question-type-select');
    const answerSection = card.querySelector('.answer-section');
    
    if (typeSelect.value === 'pilihan') {
        const optionsContainer = answerSection.querySelector('.options-container');
        if (optionsContainer) {
            questionCache[index].pilihan.html = optionsContainer.innerHTML;
            questionCache[index].pilihan.values = Array.from(optionsContainer.querySelectorAll('.option-item')).map(option => ({
                text: option.querySelector('input[type="text"]').value,
                score: option.querySelector('input[type="number"]').value
            }));
        }
    } else {
        const clueInput = answerSection.querySelector('.clue-input');
        if (clueInput) {
            questionCache[index].isian = clueInput.value;
        }
    }
});

function toggleCard(el){
    const body = el.nextElementSibling
    body.style.display = body.style.display === 'none' ? 'block' : 'none'
}

function toggleQuestion(i,v){
    document.querySelectorAll('.import-card')[i]?.classList.toggle('disabled',!v)
}

function toggleAllQuestions(v){
    document.querySelectorAll('input[name$="[import]"]').forEach((c,i)=>{
        c.checked=v; toggleQuestion(i,v)
    })
}

function changeQuestionType(i, val) {
    const card = document.querySelectorAll('.import-card')[i];
    const answerSection = document.getElementById(`answer-section-${i}`);
    const addBtn = card.querySelector('.add-option-btn');
    
    if (!questionCache[i]) {
        questionCache[i] = {
            pilihan: { html: '', values: [] },
            isian: ''
        };
    }
    
    const cache = questionCache[i];
    
    if (val === 'isian') {
    const optionsContainer = answerSection.querySelector('.options-container');
    if (optionsContainer) {
        cache.pilihan.values = Array.from(
            optionsContainer.querySelectorAll('.option-item')
        ).map(option => ({
            text: option.querySelector('input[type="text"]').value,
            score: option.querySelector('input[type="number"]').value
        }));
    }

        
        // Tampilkan input clue
        answerSection.innerHTML = `
            <div>
                <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Answer Clue</label>
                <input type="text"
                       class="form-control clue-input"
                       name="questions[${i}][clue]"
                       value="${cache.isian}"
                       placeholder="Enter answer clue"
                       style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; width: 100%;">
            </div>
        `;
        
        // Sembunyikan tombol add option
        if (addBtn) addBtn.style.display = 'none';
        
    } else if (val === 'pilihan') {
        // Simpan state isian sebelum berganti
        const clueInput = answerSection.querySelector('.clue-input');
        if (clueInput) {
            cache.isian = clueInput.value;
        }
        
        // Tampilkan opsi dari cache
        answerSection.innerHTML = `
            <div>
                <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 12px; display: block;">Options</label>
                <div class="options-container" id="options-${i}">
                    ${getDefaultOptionsHTML(i, cache.pilihan.values)}
                </div>
                <button type="button"
                        onclick="addOption(${i})"
                        class="add-option-btn"
                        style="margin-top: 12px; padding: 10px 20px; background: #4880FF; color: white; border: none; 
                                                           border-radius: 6px; font-size: 14px; cursor: pointer; display: inline-block;">
                    + Add Option
                </button>
            </div>
        `;
    }
}

function getDefaultOptionsHTML(i, values) {
    if (values && values.length > 0) {
        return values.map((opt, oi) => `
            <div class="option-item" style="display:flex;gap:8px;margin-top:6px;">
                <input type="text"
                       name="questions[${i}][options][${oi}][text]"
                       value="${opt.text}"
                       placeholder="Option text"
                       style="flex: 1; padding: 10px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;">
                <input type="number"
                       name="questions[${i}][options][${oi}][score]"
                       value="${opt.score}"
                       placeholder="Score"
                       style="width: 80px; padding: 10px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;">
                <button type="button"
                        onclick="removeOption(this)"
                        style="background:none;border:none;color:#FF4D4F;font-size:18px;cursor:pointer;">
                        ×
                </button>
            </div>
        `).join('');
    }
    
    // Default satu opsi kosong
    return `
        <div class="option-item" style="display:flex;gap:8px;margin-top:6px;">
            <input type="text"
                   name="questions[${i}][options][0][text]"
                   placeholder="Option text"
                   style="flex: 1; padding: 10px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;">
            <input type="number"
                   name="questions[${i}][options][0][score]"
                   placeholder="Score"
                   style="width: 80px; padding: 10px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;">
            <button type="button"
                    onclick="removeOption(this)"
                    style="background:none;border:none;color:#FF4D4F;font-size:18px;cursor:pointer;">
                    ×
            </button>
        </div>
    `;
}

function addOption(i) {
    const optionsContainer = document.getElementById(`options-${i}`);
    if (!optionsContainer) return;
    
    const optionCount = optionsContainer.querySelectorAll('.option-item').length;
    
    const optionHTML = `
        <div class="option-item" style="display:flex;gap:8px;margin-top:6px;">
            <input type="text"
                   name="questions[${i}][options][${optionCount}][text]"
                   placeholder="Option text"
                   style="flex: 1; padding: 10px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;">
            <input type="number"
                   name="questions[${i}][options][${optionCount}][score]"
                   placeholder="Score"
                   style="width: 80px; padding: 10px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;">
            <button type="button"
                    onclick="removeOption(this)"
                    style="background:none;border:none;color:#FF4D4F;font-size:18px;cursor:pointer;">
                    ×
            </button>
        </div>
    `;
    
    optionsContainer.insertAdjacentHTML('beforeend', optionHTML);
    
    // Update cache
    if (questionCache[i]) {
        const card = document.querySelectorAll('.import-card')[i];
        const answerSection = card.querySelector('.answer-section');
        const optionsContainer = answerSection.querySelector('.options-container');
        if (optionsContainer) {
            questionCache[i].pilihan.html = optionsContainer.innerHTML;
        }
    }
}

function removeOption(button) {
    const optionItem = button.closest('.option-item');
    if (optionItem) {
        optionItem.remove();
        
        // Update nomor index untuk semua option
        const card = button.closest('.import-card');
        const index = card.dataset.questionIndex;
        const optionsContainer = card.querySelector('.options-container');
        
        if (optionsContainer) {
            const options = optionsContainer.querySelectorAll('.option-item');
            options.forEach((option, oi) => {
                const textInput = option.querySelector('input[type="text"]');
                const scoreInput = option.querySelector('input[type="number"]');
                
                if (textInput) {
                    textInput.name = `questions[${index}][options][${oi}][text]`;
                }
                if (scoreInput) {
                    scoreInput.name = `questions[${index}][options][${oi}][score]`;
                }
            });
            
            // Update cache
            if (questionCache[index]) {
                questionCache[index].pilihan.html = optionsContainer.innerHTML;
            }
        }
    }
}

// Event listener untuk menyimpan perubahan clue ke cache
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('clue-input')) {
        const card = e.target.closest('.import-card');
        const index = card.dataset.questionIndex;
        if (questionCache[index]) {
            questionCache[index].isian = e.target.value;
        }
    }
});

// Event listener untuk menyimpan perubahan options ke cache
document.addEventListener('input', function(e) {
    const optionInput = e.target.closest('.option-item');
    if (optionInput) {
        const card = optionInput.closest('.import-card');
        const index = card.dataset.questionIndex;
        const optionsContainer = card.querySelector('.options-container');
        
        if (optionsContainer && questionCache[index]) {
            questionCache[index].pilihan.html = optionsContainer.innerHTML;
        }
    }
});
</script>

@endsection