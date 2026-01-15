@extends('layouts.app')

@section('content')
<x-header title="Questionnaire">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div style="background:transparent; padding:5px 26px 0 26px; margin:0 20px; ">
@if(isset($categories) && $categories->count() > 0)
    <div style="margin-bottom: 24px;">
        <h3 style="font-size: 24px; font-weight: bold; color: #202224; margin-bottom: 12px;">Existing Categories</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            @foreach($categories as $category)
            <div class="category-card" style="background: #F0F7FF; border: 1px solid #4880FF; border-radius: 8px; padding: 8px 16px; display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 14px; color: #202224;">{{ $category->name }}</span>
                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="delete-category-form" style="margin: 0;">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="delete-category-btn" 
                            data-category-name="{{ $category->name }}"
                            data-category-id="{{ $category->id }}"
                            style="background: transparent; border: none; padding: 0; margin: 0; color: #FF4D4F; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 16px; height: 16px;">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<div style="background:#FFFFFF; border-radius:14px; padding:26px; margin:20px 64px 60px 36px; box-shadow:6px 6px 54px #0000000D;">
   <h3 style="font-size: 24px; font-weight: bold; color: #202224; margin-bottom: 12px;">Add Categories</h3>

    <form action="{{ route('categories.store') }}" method="POST" id="category-form">
        @csrf

        <div style="margin-bottom: 16px;">
            <label>Name</label>
            <input type="text" name="name" id="category-input" placeholder="Category Name" 
                   value="{{ old('name') }}" required
                   style="width:100%; padding:10px; border:1px solid #4880FF; border-radius:8px;">
            @error('name')
                <div style="color:red; font-size:12px; margin-top:4px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 16px;">
            <label>Criteria</label>
            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach(['high'=>'High', 'medium'=>'Medium', 'low'=>'Low'] as $key => $label)
                    <input type="text" name="criteria[{{ $key }}]" 
                           value="{{ old("criteria.$key") }}" required
                           placeholder="{{ $label }} Criteria"
                           style="width:100%; padding:10px; border:1px solid #4880FF; border-radius:8px;">
                @endforeach
            </div>
        </div>

        {{-- Add Question Button --}}
        <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
            <button type="button" id="add-question-btn" 
                style="padding: 10px 20px; background: #4880FF; color: white; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                <i class="fas fa-plus" style="margin-right: 6px;"></i> Add Question
            </button>
        </div>

        {{-- Existing Questions --}}
        @php
            $questionCounter = 0;
        @endphp
        @foreach(old('questions', []) as $qid => $question)
            @php $questionCounter++; @endphp
            @include('partials.question-card', [
                'qid' => $qid,
                'question' => $question,
                'questionCounter' => $questionCounter
            ])
        @endforeach

        {{-- New Questions Container --}}
        <div id="questions-container" style="display:flex; flex-direction:column; gap:16px; margin-top:16px;"></div>

        <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:20px;">
            <a href="{{ route('questionnaire.index') }}"
                style="padding:12px 24px; background:white; border:1px solid #4880FF; color:#4880FF; border-radius:8px;">Cancel</a>
            <button type="submit" id="save-btn"
                style="padding:12px 24px; background:#4379EE; color:white; border:none; border-radius:8px;">Save</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let questionCounter = {{ $questionCounter }};
    const categoryInput = document.getElementById('category-input');
    const answerState = {};
    const form = document.getElementById('category-form');
    const saveBtn = document.getElementById('save-btn');
    
    // Fungsi untuk mengecek apakah semua field yang diperlukan sudah terisi
    function validateForm() {
        // Validasi kategori utama
        if (!categoryInput.value.trim()) {
            alert('Please fill Category Name');
            categoryInput.focus();
            return false;
        }
        
        // Validasi criteria
        const criteriaInputs = document.querySelectorAll('input[name^="criteria["]');
        for (let input of criteriaInputs) {
            if (!input.value.trim()) {
                alert('Please fill all Criteria fields');
                input.focus();
                return false;
            }
        }
        
        // Validasi pertanyaan
        const questionTextareas = document.querySelectorAll('.question-textarea');
        let hasQuestions = false;
        
        for (let textarea of questionTextareas) {
            if (textarea.value.trim()) {
                hasQuestions = true;
                
                // Validasi berdasarkan tipe pertanyaan
                const questionId = textarea.closest('.question-card').dataset.questionId || 
                                 textarea.closest('.question-card')?.querySelector('.question-type-select')?.dataset.questionId;
                
                if (!questionId) continue;
                
                const questionType = document.querySelector(`.question-type-select[data-question-id="${questionId}"]`)?.value;
                
                if (questionType === 'pilihan') {
                    const options = document.querySelectorAll(`.answer-section[data-question-id="${questionId}"] .option-text-input`);
                    let hasValidOption = false;
                    
                    for (let option of options) {
                        if (option.value.trim()) {
                            hasValidOption = true;
                            break;
                        }
                    }
                    
                    if (!hasValidOption) {
                        alert('Please add at least one option for the question: ' + textarea.value.substring(0, 50) + '...');
                        return false;
                    }
                } else if (questionType === 'isian') {
                    const clueInput = document.querySelector(`.answer-section[data-question-id="${questionId}"] input[name$="[clue]"]`);
                    if (!clueInput || !clueInput.value.trim()) {
                    }
                }
            }
        }
        
        if (!hasQuestions) {
            alert('Please add at least one question');
            return false;
        }
        
        return true;
    }
    
    // Event listener untuk form submit
    form.addEventListener('submit', function () {
    document.querySelectorAll('.question-type-select').forEach(select => {
        const qid = select.dataset.questionId;
        const type = select.value;
        const answerSection = document.querySelector(`.answer-section[data-question-id="${qid}"]`);
        if (!answerSection) return;

        if (type === 'isian') {
            if (!answerSection.querySelector('input[name$="[clue]"]')) {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `questions[${qid}][clue]`;
                input.value = '';
                answerSection.appendChild(input);
            }
        }
    });
});

    
    // Event listener untuk tombol save
    saveBtn.addEventListener('click', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
    });
    
    // Event listener untuk tombol hapus kategori
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-category-btn')) {
            e.preventDefault();
            const button = e.target.closest('.delete-category-btn');
            const categoryName = button.dataset.categoryName;
            const categoryId = button.dataset.categoryId;
            const form = button.closest('.delete-category-form');
            
            if (confirm(`Are you sure you want to delete the category "${categoryName}"? This will also delete all questions in this category.`)) {
                form.submit();
            }
        }
    });
    
    // Sinkron semua category di question
    categoryInput.addEventListener('input', function () {
        const val = this.value;
        document.querySelectorAll('.question-category').forEach(el => {
            el.value = val;
        });
    });
    
    // Auto-resize textareas
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }
    
    // Initialize existing textareas
    document.querySelectorAll('.question-textarea').forEach(textarea => {
        autoResizeTextarea(textarea);
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
    
    // Question type change handler
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('question-type-select')) return;
        
        const select = e.target;
        const questionId = select.dataset.questionId;
        const answerSection = document.querySelector(`.answer-section[data-question-id="${questionId}"]`);
        
        if (!answerSection) return;
        
        // Initialize state if not exists
        if (!answerState[questionId]) {
            answerState[questionId] = {
                pilihan: [],
                isian: ''
            };
        }
        
        // Save current state based on previous type
        const prevType = select.dataset.prevType || select.value;
        
        if (prevType === 'pilihan') {
            const opts = [];
            answerSection.querySelectorAll('.option-item').forEach(item => {
                const textInput = item.querySelector('.option-text-input');
                const scoreInput = item.querySelector('.option-score-input');
                if (textInput && scoreInput) {
                    opts.push({
                        text: textInput.value,
                        score: scoreInput.value
                    });
                }
            });
            answerState[questionId].pilihan = opts;
        } else {
            const clueInput = answerSection.querySelector('input[name$="[clue]"]');
            if (clueInput) {
                answerState[questionId].isian = clueInput.value;
            }
        }
        
        // Render new UI based on selected type
        if (select.value === 'pilihan') {
            answerSection.innerHTML = `
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <label style="font-size:12px; font-weight:600;">Options</label>
                    <div class="options-container" style="display:flex; flex-direction:column; gap:8px;"></div>
                    <button type="button" 
                            class="add-option-btn" 
                            data-question-id="${questionId}"
                            style="align-self:flex-start; padding:8px 16px; background:#4880FF; color:white; border:none; border-radius:6px;">
                        Add Option
                    </button>
                </div>`;
            
            const container = answerSection.querySelector('.options-container');
            const saved = answerState[questionId].pilihan;
            
            if (saved && saved.length > 0) {
                saved.forEach((opt, index) => {
                    addOption(questionId, opt.text, opt.score, index);
                });
            } else {
                addOption(questionId, '', '', 0);
            }
        } else {
            const savedClue = answerState[questionId].isian || '';
            answerSection.innerHTML = `
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <label style="font-size:12px; font-weight:600;">Answer</label>
                    <input type="text"
                           name="questions[${questionId}][clue]"
                           value="${savedClue}"
                           placeholder="Optional Clue"
                           style="padding:12px; border:1px solid #4880FF; border-radius:8px;">
                </div>`;
        }
        
        select.dataset.prevType = select.value;
    });
    
    function addOption(questionId, text = '', score = '', index = null) {
    const wrapper = document.querySelector(`.answer-section[data-question-id="${questionId}"]`);
    if (!wrapper) return;

    const optionsContainer = wrapper.querySelector('.options-container');
    if (index === null) {
        index = optionsContainer.querySelectorAll('.option-item').length;
    }

    const div = document.createElement('div');
    div.className = 'option-item';
    div.style.display = 'flex';
    div.style.gap = '12px';
    div.style.alignItems = 'center';

    div.innerHTML = `
    <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
        <input 
            type="text"
            name="questions[${questionId}][options][${index}][text]"
            value="${text}"
            placeholder="Option"
            class="option-text-input"
            style="flex: 1; padding: 8px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
        />
        <input 
            type="number"
            name="questions[${questionId}][options][${index}][score]"
            value="${score}"
            class="option-score-input"
            placeholder="0"
            style="width: 50px; padding: 8px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
        />
        <button type="button" class="delete-option-btn"
        style="background: transparent; border: none; padding: 0; margin: 0; color: #FF4D4F; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 16px; height: 16px;">
        X</button>
    `;

    optionsContainer.appendChild(div);
}

    
    
    // Delete option
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-option-btn')) {
            const item = e.target.closest('.option-item');
            if (item) {
                item.remove();
            }
        }
    });
    
    // Add option button
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-option-btn')) {
            const button = e.target.closest('.add-option-btn');
            const questionId = button.dataset.questionId;
            addOption(questionId);
        }
    });
    
    // Delete question
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-question-btn')) {
            const button = e.target.closest('.delete-question-btn');
            const questionCard = button.closest('.question-card');
            
            if (questionCard.dataset.questionId && questionCard.dataset.questionId.startsWith('existing_')) {
                // Existing question - mark for deletion
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = `questions[${questionCard.dataset.questionId.replace('existing_', '')}][_delete]`;
                deleteInput.value = '1';
                questionCard.appendChild(deleteInput);
                questionCard.style.display = 'none';
            } else {
                // New question - remove completely
                questionCard.remove();
            }
        }
    });

    
    // Add new question
    document.getElementById('add-question-btn').addEventListener('click', function(e) {
        e.preventDefault();
        
        const categoryValue = categoryInput.value.trim();
        if (!categoryValue) {
            alert('Please fill Category first before adding a question');
            return;
        }
        
        questionCounter++;
        const newQuestionId = 'new_' + Date.now() + '_' + questionCounter;
        const container = document.getElementById('questions-container');
        
        const newCard = document.createElement('div');
        newCard.className = 'question-card';
        newCard.style.marginBottom = '20px';
        newCard.style.padding = '20px';
        newCard.style.borderRadius = '12px';
        newCard.style.background = '#FFFFFF';
        newCard.style.border = '1px solid #E8E8E8';
        
        newCard.innerHTML = `
            <div style="display: flex; gap: 30px; position: relative;">
                <!-- Left Group: Question and Answer -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 16px;">
                    <!-- Question Name -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="color: #202224; font-size: 12px; font-weight: 600;">Question</label>
                        <textarea 
                            name="questions[${newQuestionId}][question_text]"
                            placeholder="Enter question text..."
                            class="question-textarea"
                            style="color: #202224; font-size: 14px; background: #F8F9FA; border: 1px solid #DEE2E6; border-radius: 8px; padding: 12px; width: 100%; resize: vertical; min-height: 44px; transition: border 0.2s;"
                            rows="2"
                        ></textarea>
                    </div>
                    
                    <!-- Answer Section - Default to Multiple Choice -->
                    <div class="answer-section" data-question-id="${newQuestionId}">
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <label style="font-size:12px; font-weight:600;">Options</label>
                            <div class="options-container" style="display:flex; flex-direction:column; gap:8px;"></div>
                            <button type="button" 
                                    class="add-option-btn" 
                                    data-question-id="${newQuestionId}"
                                    style="align-self:flex-start; padding:8px 16px; background:#4880FF; color:white; border:none; border-radius:6px;">
                                Add Option
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Right Group: Type, Category, Indicator, Attachment -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 16px;">
                    <!-- Type -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="color: #202224; font-size: 12px; font-weight: 600;">Type</label>
                        <select 
                            class="question-type-select" 
                            data-question-id="${newQuestionId}" 
                            data-prev-type="pilihan"
                            name="questions[${newQuestionId}][question_type]"
                            style="padding: 10px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; color: #202224; background: white; cursor: pointer;"
                        >
                            <option value="pilihan" selected>Multiple Choice</option>
                            <option value="isian">Text Answer</option>
                        </select>
                    </div>
                    
                    <!-- Category -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="color: #202224; font-size: 12px; font-weight: 600;">Category</label>
                        <input type="hidden" name="questions[${newQuestionId}][use_main_category]" value="1">
                        <input type="text"
                              class="question-category category-select"
                              readonly
                              value="${categoryValue}"
                              style="padding: 10px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; color: #202224; background: white; cursor: pointer;">
                    </div>
                    
                    <!-- Indicator -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="color: #202224; font-size: 12px; font-weight: 600;">Indicator</label>
                        <div style="display: flex; gap: 16px;">
    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
        <input type="checkbox" name="questions[${newQuestionId}][indicator][]" value="high" style="width: 16px; height: 16px; accent-color: #4880FF;">
        <span style="font-size: 14px; color: #202224;">High</span>
    </label>
    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
        <input type="checkbox" name="questions[${newQuestionId}][indicator][]" value="medium" style="width: 16px; height: 16px; accent-color: #4880FF;">
        <span style="font-size: 14px; color: #202224;">Medium</span>
    </label>
    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
        <input type="checkbox" name="questions[${newQuestionId}][indicator][]" value="low" style="width: 16px; height: 16px; accent-color: #4880FF;">
        <span style="font-size: 14px; color: #202224;">Low</span>
    </label>
</div>

                    </div>
                    
                    <!-- Attachment -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="color: #202224; font-size: 12px; font-weight: 600;">Attachment</label>
                        <input 
                            type="text"
                            name="questions[${newQuestionId}][attachment_text]"
                            placeholder="Please attach supporting document"
                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px;"
                        />
                    </div>
                    
                    <!-- Delete Button -->
                    <button 
                        type="button" 
                        class="delete-question-btn"
                        data-question-id="${newQuestionId}"
                        style="align-self: flex-end; margin-top: auto; padding: 10px 20px; background: #FF4D4F; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s;"
                        onmouseover="this.style.background='#FF3333'"
                        onmouseout="this.style.background='#FF4D4F'"
                    >
                        <i class="fas fa-trash" style="margin-right: 6px;"></i>Delete Question
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(newCard);
        
        // Initialize state for new question
        answerState[newQuestionId] = {
            pilihan: [],
            isian: ''
        };
        
        // Add initial options
        addOption(newQuestionId, '', '', 0);
        
        // Setup auto-resize for textarea
        const textarea = newCard.querySelector('.question-textarea');
        autoResizeTextarea(textarea);
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
});
</script>

<script>
document.addEventListener('change', function(e) {
    // Hanya checkbox indikator
    if (!e.target.matches('input[type="checkbox"][name$="[indicator][]"]')) return;

    const checkbox = e.target;
    const questionCard = checkbox.closest('.question-card');
    const allCheckboxes = questionCard.querySelectorAll('input[type="checkbox"][name$="[indicator][]"]');
const map = {
        high: ['high'],
        medium: ['medium','high'],
        low: ['low', 'high','medium']
    };

    const selected = checkbox.value;

    // Reset semua checkbox
    allCheckboxes.forEach(cb => cb.checked = false);

    // Set sesuai aturan
    map[selected].forEach(val => {
        allCheckboxes.forEach(cb => {
            if (cb.value === val) cb.checked = true;
        });
    });
});
</script>
@endsection