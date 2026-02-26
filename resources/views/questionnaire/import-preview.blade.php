@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Questionnaire">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div class="container">
    {{-- FILE ERROR --}}
    @if($errors->has('file'))
    <div class="alert alert-danger">
        {{ $errors->first('file') }}
    </div>
    @endif

    <div class="preview-card">
        <div class="preview-header">
            <h2>Preview Import Questionnaire</h2>
            <div class="header-actions">
                <button type="button" onclick="history.back()" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back</span>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('questionnaire.import') }}" id="import-form">
        @csrf

        {{-- QUESTIONS CONTAINER --}}
        <div class="questions-container" id="questions-container">
              @if(count($importData) === 0)
        <div class="no-changes" style="text-align:center; padding:40px;">
            <h3>Tidak ada perubahan terdeteksi</h3>
            <p>File Excel yang diupload sama dengan data di database.</p>
        </div>
    @else
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
                $isNew = $action === 'new' || $action === 'add';
                
                // Pastikan question_no ada
                $questionNo = $q['question_no'] ?? $q['no'] ?? $loop->iteration;
                
                $indicators = is_array($q['indicator'] ?? null)
                    ? $q['indicator']
                    : (is_string($q['indicator'] ?? null)
                        ? json_decode($q['indicator'], true) ?? []
                        : []);
            @endphp

            <div class="question-card import-card {{ $isDeleted ? 'deleted' : '' }}" 
                 data-question-index="{{ $i }}"
                 data-action="{{ $action }}">
                
                {{-- CARD HEADER --}}
                <div class="question-card-header">
                    <div class="question-header-left">
                        <div class="question-number">{{ $questionNo }}</div>
                        <div class="question-info">
                            <div class="question-title-row">
                                <span class="action-icon">{{ $actionIcons[$action] ?? '' }}</span>
                                <span class="question-title">{{ Str::limit($q['question_text'] ?? '', 80) }}</span>
                            </div>
                            <div class="question-meta">
                                <span class="action-badge {{ $action }}">{{ $actionTexts[$action] ?? strtoupper($action) }}</span>
                                @if(!$isDeleted)
                                <span class="meta-divider">•</span>
                                <span class="question-type">
                                    {{ ($q['question_type'] ?? '') === 'pilihan' ? 'Multiple Choice' : 'Text Answer' }}
                                </span>
                                <span class="meta-divider">•</span>
                                <span class="question-category">
                                    {{ $categories->firstWhere('id',$q['category_id'])->name ?? '' }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <label class="switch">
    <input type="checkbox"
           name="questions[{{ $i }}][import]"
           class="question-checkbox"
           data-question-index="{{ $i }}"
           {{ !$isDeleted ? 'checked' : '' }}>
    <span class="slider {{ $isDeleted ? 'deleted' : '' }}"></span>
</label>

                </div>

                {{-- CARD BODY - SEMUA CARD DEFAULT TERTUTUP --}}
                <div class="question-card-body" style="display:none">
                    <input type="hidden" name="questions[{{ $i }}][action]" value="{{ $action }}">

                    @if($q['id'] ?? false)
                    <input type="hidden" name="questions[{{ $i }}][id]" value="{{ $q['id'] }}">
                    @endif

                    @if(!$isDeleted)
                    <input type="hidden" name="questions[{{ $i }}][question_no]" value="{{ $questionNo }}">
                    @endif

                    {{-- @if($isDeleted)
                        <div class="deleted-info-grid">
                            <div class="deleted-info-item">
                                <label>Question</label>
                                <div class="deleted-value">{{ $q['question_text'] ?? '' }}</div>
                            </div>
                            <div class="deleted-info-item">
                                <label>Category</label>
                                <div class="deleted-value">{{ $categories->firstWhere('id',$q['category_id'])->name ?? 'Unknown' }}</div>
                            </div>
                            <div class="deleted-info-item">
                                <label>Type</label>
                                <div class="deleted-value">{{ ($q['question_type'] ?? '') === 'pilihan' ? 'Multiple Choice' : 'Text Answer' }}</div>
                            </div>
                            <div class="deleted-info-item">
                                <label>Status</label>
                                <div class="deleted-status">Will be deleted</div>
                            </div>
                        </div>
                   
                    
                    @else --}}
                    {{-- NORMAL QUESTION VIEW (NEW/UPDATE) --}}
                    <div class="question-form-grid">
                        {{-- LEFT COLUMN --}}
                        <div class="form-left">
                            <div class="form-group">
                                <label class="form-label">Question</label>
                                <textarea class="form-textarea question-text" 
                                          rows="3"
                                          name="questions[{{ $i }}][question_text]">{{ $q['question_text'] ?? '' }}</textarea>
                            </div>

                            {{-- ANSWER SECTION --}}
                            <div class="answer-section" id="answer-section-{{ $i }}">

    {{-- OPTIONS --}}
    <div class="options-section"
         id="options-wrapper-{{ $i }}"
         style="{{ ($q['question_type'] ?? '') === 'pilihan' ? '' : 'display:none;' }}">

        <div class="options-header">
            <label class="form-label">Options</label>
            <span class="score-badge">
                Score: {{ collect($q['options'] ?? [])->sum('score') }}
            </span>
        </div>

        <div class="options-container" id="options-{{ $i }}">
            @foreach($q['options'] ?? [] as $oi => $opt)
            <div class="option-item">
                <div class="option-number">{{ $oi + 1 }}.</div>

                <input type="text"
                       name="questions[{{ $i }}][options][{{ $oi }}][text]"
                       value="{{ $opt['text'] ?? '' }}"
                       placeholder="Option text"
                       class="option-input">

                <input type="number"
                       name="questions[{{ $i }}][options][{{ $oi }}][score]"
                       value="{{ $opt['score'] ?? '' }}"
                       placeholder="Score"
                       min="0"
                       step="1"
                       class="option-score">

                @if(count($q['options'] ?? []) > 1)
                <button type="button"
                        onclick="removeOption(this)"
                        class="btn-remove-option">
                        <i class="fas fa-times"></i>
                </button>
                @endif
            </div>
            @endforeach
        </div>

        <button type="button"
                onclick="addOption({{ $i }})"
                class="btn-add-option">
                <i class="fas fa-plus"></i>
                <span>Add Option</span>
        </button>
    </div>


    {{-- CLUE --}}
    <div class="form-group"
         id="clue-wrapper-{{ $i }}"
         style="{{ ($q['question_type'] ?? '') === 'isian' ? '' : 'display:none;' }}">

        <label class="form-label">Answer Clue</label>

        <input type="text"
               class="form-input clue-input"
               name="questions[{{ $i }}][clue]"
               value="{{ $q['clue'] ?? '' }}"
               placeholder="Enter answer clue">
    </div>

</div>

                        </div>

                        {{-- RIGHT COLUMN --}}
                        <div class="form-right">
                            <div class="form-group">
                                <label class="form-label">Type</label>
                                <select class="form-select question-type-select"
                                        name="questions[{{ $i }}][question_type]"
                                        onchange="changeQuestionType({{ $i }}, this.value)">
                                    <option value="isian" {{ ($q['question_type'] ?? '') == 'isian' ? 'selected' : '' }}>Text Answer</option>
                                    <option value="pilihan" {{ ($q['question_type'] ?? '') == 'pilihan' ? 'selected' : '' }}>Multiple Choice</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select class="form-select" 
                                        name="questions[{{ $i }}][category_id]">
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $cat->id == ($q['category_id'] ?? '') ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Indicator</label>
                                <div class="indicators-grid">
                                    @foreach(['umum'=>'Umum','high'=>'High','medium'=>'Medium','low'=>'Low'] as $v=>$l)
                                    <label class="checkbox-label">
                                        <input type="checkbox"
                                               name="questions[{{ $i }}][indicator][]"
                                               value="{{ $v }}"
                                               {{ in_array($v,$indicators)?'checked':'' }}>
                                        <span class="checkmark"></span>
                                        <span class="checkbox-text">{{ $l }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Sub Category</label>
                                <input type="text"
                                       class="form-input"
                                       name="questions[{{ $i }}][sub]"
                                       value="{{ $q['sub'] ?? '' }}">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Attachment Note</label>
                                <input type="text"
                                       class="form-input"
                                       name="questions[{{ $i }}][attachment_text]"
                                       value="{{ $q['attachment_text'] ?? '-' }}">
                            </div>
                        </div>
                    </div>
                    {{-- @endif --}}
                </div>
            </div>
            @endforeach
            @endif
        </div>

        {{-- FOOTER --}}
        <div class="preview-footer">
            <div class="footer-left">
                <div class="stats-row">
                    <div class="stat-item">
                        <span class="stat-label">Total questions:</span>
                        <span class="stat-value">{{ $totalQuestions }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Selected:</span>
                        <span class="stat-value" id="selected-count">0</span>
                    </div>
                </div>
                <label class="checkbox-label select-all">
                    <input type="checkbox" id="select-all" checked onchange="toggleAllQuestions(this.checked)">
                    <span class="checkmark"></span>
                    <span class="checkbox-text">Select All</span>
                </label>
            </div>
            
            <div class="footer-right">
                <button type="button"
                        onclick="addQuestion()"
                        class="btn-secondary">
                        <i class="fas fa-plus"></i>
                        <span>Add Question</span>
                </button>
                <button type="submit" 
                        id="confirm-import-btn"
                        class="btn-primary">
                        <i class="fas fa-file-import"></i>
                        <span>Confirm Import</span>
                </button>
            </div>
        </div>

        </form>
    </div>
</div>

<script>
let questionIndex = {{ count($importData) }};

function addQuestion() {

    const container = document.getElementById('questions-container');

    const index = questionIndex;

    const html = `
    <div class="question-card import-card"
     data-question-index="${index}"
     data-action="create">

    <!-- HEADER -->
    <div class="question-card-header">
        <div class="question-header-left">
            <div class="question-number">NEW</div>
            <div class="question-info">
                <div class="question-title-row">
                    <span class="action-icon">➕</span>
                    <span class="question-title">New Question</span>
                </div>
                <div class="question-meta">
                    <span class="action-badge add">New Question</span>
                    <span class="meta-divider">•</span>
                    <span class="question-type">Text Answer</span>
                    <span class="meta-divider">•</span>
                    <span class="question-category">-</span>
                </div>
            </div>
        </div>

        <label class="switch">
            <input type="checkbox"
       name="questions[${index}][import]"
       class="question-checkbox"
       data-question-index="${index}"
       checked>
<span class="slider"></span>
        </label>
    </div>

    <!-- BODY -->
    <div class="question-card-body" style="display:block">

        <input type="hidden"
               name="questions[${index}][action]"
               value="create">

        <div class="question-form-grid">

            <!-- LEFT -->
            <div class="form-left">

                <div class="form-group">
                    <label class="form-label">Question</label>
                    <textarea class="form-textarea question-text"
                              rows="3"
                              name="questions[${index}][question_text]"
                              placeholder="Enter question"></textarea>
                </div>

                <!-- Answer Section -->
                <div class="answer-section"
     id="answer-section-${index}">

    <!-- OPTIONS -->
    <div class="options-section"
         id="options-wrapper-${index}"
         style="display:none;">

        <div class="options-header">
            <label class="form-label">Options</label>
            <span class="score-badge">Score: 0</span>
        </div>

        <div class="options-container"
             id="options-${index}">
        </div>

        <button type="button"
                onclick="addOption(${index})"
                class="btn-add-option"
                style="margin-top:10px;">
            <i class="fas fa-plus"></i>
            <span>Add Option</span>
        </button>
    </div>

    <!-- CLUE -->
    <div class="form-group"
         id="clue-wrapper-${index}">

        <label class="form-label">Answer Clue</label>
        <input type="text"
               class="form-input clue-input"
               name="questions[${index}][clue]"
               placeholder="Enter answer clue">
    </div>

</div>

            </div>

            <!-- RIGHT -->
            <div class="form-right">

                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select class="form-select question-type-select"
                            name="questions[${index}][question_type]"
                            onchange="changeQuestionType(${index}, this.value)">
                        <option value="isian">Text Answer</option>
                        <option value="pilihan">Multiple Choice</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-select"
                            name="questions[${index}][category_id]">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Indicator</label>
                    <div class="indicators-grid">
                        <label class="checkbox-label">
                            <input type="checkbox"
                                   name="questions[${index}][indicator][]"
                                   value="umum">
                            <span class="checkmark"></span>
                            <span class="checkbox-text">Umum</span>
                        </label>

                        <label class="checkbox-label">
                            <input type="checkbox"
                                   name="questions[${index}][indicator][]"
                                   value="high">
                            <span class="checkmark"></span>
                            <span class="checkbox-text">High</span>
                        </label>

                        <label class="checkbox-label">
                            <input type="checkbox"
                                   name="questions[${index}][indicator][]"
                                   value="medium">
                            <span class="checkmark"></span>
                            <span class="checkbox-text">Medium</span>
                        </label>

                        <label class="checkbox-label">
                            <input type="checkbox"
                                   name="questions[${index}][indicator][]"
                                   value="low">
                            <span class="checkmark"></span>
                            <span class="checkbox-text">Low</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Sub Category</label>
                    <input type="text"
                           class="form-input"
                           name="questions[${index}][sub]">
                </div>

                <div class="form-group">
                    <label class="form-label">Attachment Note</label>
                    <input type="text"
                           class="form-input"
                           name="questions[${index}][attachment_text]"
                           value="-">
                </div>

            </div>
        </div>
    </div>
</div>

    `;

    container.insertAdjacentHTML('beforeend', html);

    // setup checkbox event
    const newCheckbox = container.querySelector(
        `.question-checkbox[data-question-index="${index}"]`
    );

    newCheckbox.addEventListener('change', function () {
        toggleCardEnabled(index);
    });

    questionIndex++;

    updateCount(); // 🔥 penting
}

function changeQuestionType(index, value) {

    const optionsWrapper = document.getElementById('options-wrapper-' + index);
    const clueWrapper = document.getElementById('clue-wrapper-' + index);

    if (!optionsWrapper || !clueWrapper) return;

    if (value === 'pilihan') {

        optionsWrapper.style.display = 'block';
        clueWrapper.style.display = 'none';

    } else {

        optionsWrapper.style.display = 'none';
        clueWrapper.style.display = 'block';
    }

    updateQuestionMeta(index, value);
}

function updateQuestionMeta(index, type) {

    const card = document.querySelector(
        `.import-card[data-question-index="${index}"]`
    );

    if (!card) return;

    const typeSpan = card.querySelector('.question-type');

    if (typeSpan) {
        typeSpan.textContent =
            type === 'pilihan'
                ? 'Multiple Choice'
                : 'Text Answer';
    }
}


function toggleOptions(select, index) {

    const container = document.getElementById('options-container-' + index);

    if (select.value === 'pilihan') {
        container.classList.remove('d-none');
    } else {
        container.classList.add('d-none');
    }
}

function addOption(index) {

    const container = document.getElementById('options-' + index);
    if (!container) return;

    const optionIndex = container.children.length;

    const html = `
        <div class="option-item">

            <input type="text"
                   name="questions[${index}][options][${optionIndex}][text]"
                   placeholder="Option text"
                   class="option-input">

            <input type="number"
                   name="questions[${index}][options][${optionIndex}][score]"
                   placeholder="Score"
                   min="0"
                   step="1"
                   class="option-score">

            <button type="button"
                    onclick="removeOption(this)"
                    class="btn-remove-option">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
}

</script>


<script>
// Data untuk tracking
let questionStates = {};

// Fungsi toggle expand/collapse card body
function toggleCardBody(index) {
    const card = document.querySelector(`.import-card[data-question-index="${index}"]`);
    if (!card) return;

    const body = card.querySelector('.question-card-body');
    const isVisible = body.style.display === 'block';
    
    // Toggle visibility
    body.style.display = isVisible ? 'none' : 'block';
    
    // Simpan state
    questionStates[index] = !isVisible;
    
    return false;
}

// Fungsi untuk mengaktifkan/nonaktifkan card berdasarkan checkbox
function toggleCardEnabled(index) {

    const card = document.querySelector(
        `.import-card[data-question-index="${index}"]`
    );

    if (!card) return;

    const checkbox = card.querySelector('.question-checkbox');
    const actionInput = card.querySelector(
        `input[name="questions[${index}][action]"]`
    );

    if (checkbox.checked) {

        card.style.opacity = '1';

        if (actionInput.value === 'delete') {
            actionInput.value = 'unchanged';
        }

    } else {

        card.style.opacity = '0.4';
        actionInput.value = 'delete';
    }

    updateCount();
}




// Update event handler di checkbox
function setupCheckboxEvents() {
    document.querySelectorAll('.question-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const index = this.getAttribute('data-question-index');
            toggleCardEnabled(index);
        });
    });
}

function toggleAllQuestions(checked) {
    const checkboxes = document.querySelectorAll('.question-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checked;
        const index = cb.getAttribute('data-question-index');
        toggleCardEnabled(index);
    });
    updateCount();
}

function updateCount() {

    let processCount = 0;

    document.querySelectorAll('.import-card').forEach(card => {

        const actionInput = card.querySelector(
            'input[name$="[action]"]'
        );

        if (!actionInput) return;

        if (actionInput.value !== 'unchanged') {
            processCount++;
        }
    });

    document.getElementById('selected-count').textContent = processCount;

    const importBtn = document.getElementById('confirm-import-btn');

    // Disable hanya jika benar-benar tidak ada perubahan
    importBtn.disabled = processCount === 0;
    importBtn.style.opacity = processCount === 0 ? '0.5' : '1';
    importBtn.style.cursor = processCount === 0 ? 'not-allowed' : 'pointer';

    return processCount;
}



// Initialize semua checkbox saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Setup checkbox events
    setupCheckboxEvents();
    
    // Set semua checkbox checked
    document.querySelectorAll('.question-checkbox').forEach(cb => {
        const index = cb.getAttribute('data-question-index');
        toggleCardEnabled(index);
    });

    // Set select all
    document.getElementById('select-all').checked = true;

    // Hitung awal
    updateCount();
    
    // Setup header click untuk toggle card body
    document.querySelectorAll('.question-card-header').forEach(header => {
        header.addEventListener('click', function(e) {
            // Jangan trigger jika klik pada switch
            if (e.target.closest('.switch')) return;
            
            const card = this.closest('.import-card');
            const index = card.getAttribute('data-question-index');
            toggleCardBody(index);
        });
    });
});

// Form submission handler
document.getElementById('import-form').addEventListener('submit', function(e) {
    const count = updateCount();
    if (count === 0) {
        e.preventDefault();
        alert('Please select at least one question to import');
        return false;
    }
    
    // Validasi tambahan
    let hasInvalidData = false;
    document.querySelectorAll('.import-card:not([data-action="delete"])').forEach(card => {
        const checkbox = card.querySelector('.question-checkbox');
        if (checkbox && checkbox.checked) {
            const questionText = card.querySelector('textarea[name$="[question_text]"]');
            if (questionText && questionText.value.trim() === '') {
                hasInvalidData = true;
                questionText.style.borderColor = 'red';
            }
        }
    });
    
    if (hasInvalidData) {
        e.preventDefault();
        alert('Please fill in all required fields');
        return false;
    }
    
    return true;
});
</script>

<style>
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.alert {
    background: #FFEAEA;
    color: #842029;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #f5c2c7;
}

.preview-card {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
}

.preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.preview-header h2 {
    margin: 0;
    color: #202224;
    font-size: 24px;
    font-weight: 600;
}

.btn-back {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #4880FF;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.2s;
}

.btn-back:hover {
    background: #3a6ce8;
}

.questions-container {
    max-height: 55vh;
    overflow-y: auto;
    padding: 10px;
    margin-bottom: 20px;
}

.question-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    margin-bottom: 16px;
    overflow: hidden;
    transition: all 0.3s;
}

.question-card.deleted {
    border-color: #DC3545;
    opacity: 0.9;
}

.question-card-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    background: #f8f9fa;
    transition: background 0.2s;
}

.question-card-header:hover {
    background: #e9ecef;
}

.question-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.question-number {
    width: 32px;
    height: 32px;
    background: #4880FF;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.question-info {
    flex: 1;
}

.question-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.action-icon {
    font-size: 16px;
}

.question-title {
    font-weight: 600;
    color: #202224;
    font-size: 15px;
}

.question-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #6C757D;
}

.action-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
}

.action-badge.add {
    background: #E7F1FF;
    color: #0d6efd;
}

.action-badge.update {
    background: #FFF3CD;
    color: #856404;
}

.action-badge.delete {
    background: #F8D7DA;
    color: #721c24;
}

.meta-divider {
    color: #adb5bd;
}

.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    margin-left: 12px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #4880FF;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.slider.deleted {
    background-color: #DC3545;
}

.question-card-body {
    padding: 24px;
    border-top: 1px solid #e9ecef;
}

.deleted-question-view {
    background: #f8d7da;
    border-radius: 8px;
    padding: 20px;
}

.deleted-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.deleted-info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
    
}

.deleted-info-item label {
    font-weight: 600;
    color: #721c24;
    font-size: 14px;
}

.deleted-value {
    background: white;
    padding: 12px;
    border: 1px solid #721c24;
    padding: 12px;
    font-size: 14px;
    color: #495057;
}

.deleted-status {
    background: white;
    padding: 12px;
    border-radius: 6px;
    font-size: 14px;
    color: #DC3545;
    font-weight: bold;
}

.question-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.form-left, .form-right {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    color: #202224;
    font-size: 14px;
    font-weight: 600;
}

.form-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #DEE2E6;
    border-radius: 8px;
    font-size: 14px;
    color: #202224;
    background: #fff;
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
}

.form-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #DEE2E6;
    border-radius: 8px;
    font-size: 14px;
    color: #202224;
    background: #fff;
}

.form-select {
    width: 100%;
    padding: 12px;
    border: 1px solid #DEE2E6;
    border-radius: 8px;
    font-size: 14px;
    color: #202224;
    background: #fff;
    cursor: pointer;
}

.options-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.options-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.score-badge {
    background: #E7F1FF;
    color: #4880FF;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.options-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.option-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.option-number {
    min-width: 24px;
    text-align: center;
    font-weight: 600;
    color: #6C757D;
}

.option-input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #DEE2E6;
    border-radius: 6px;
    font-size: 14px;
    background: #fff;
}

.option-score {
    width: 100px;
    padding: 10px;
    border: 1px solid #DEE2E6;
    border-radius: 6px;
    font-size: 14px;
    background: #fff;
}

.btn-remove-option {
    background: none;
    border: none;
    color: #FF4D4F;
    font-size: 18px;
    cursor: pointer;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    transition: background 0.2s;
}

.btn-remove-option:hover {
    background: #ffeaea;
}

.btn-add-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #4880FF;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    width: fit-content;
}

.btn-add-option:hover {
    background: #3a6ce8;
}

.indicators-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #495057;
}

.checkbox-label input {
    display: none;
}

.checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid #DEE2E6;
    border-radius: 4px;
    position: relative;
    transition: all 0.2s;
}

.checkbox-label input:checked + .checkmark {
    background: #4880FF;
    border-color: #4880FF;
}

.checkbox-label input:checked + .checkmark:after {
    content: "";
    position: absolute;
    left: 5px;
    top: 2px;
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.checkbox-text {
    user-select: none;
}

.preview-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
    margin-top: 20px;
}

.footer-left {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.stats-row {
    display: flex;
    gap: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stat-label {
    color: #6C757D;
    font-size: 14px;
}

.stat-value {
    color: #202224;
    font-weight: 600;
    font-size: 16px;
}

.select-all {
    margin-top: 4px;
}

.footer-right {
    display: flex;
    gap: 12px;
}

.btn-secondary {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #F8FAFF;
    color: #4880FF;
    border: 1px solid #4880FF;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: #e8f0ff;
}

.btn-primary {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    background: #4880FF;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) {
    background: #3a6ce8;
}

.btn-primary:disabled {
    background: #cccccc;
    cursor: not-allowed;
    opacity: 0.6;
}

.question-card.disabled {
    opacity: 0.6;
    background: #f8f9fa;
}

.question-card.disabled .question-card-header {
    background: #e9ecef;
}
</style>

@endsection