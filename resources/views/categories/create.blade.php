@extends('layouts.app')

@section('content')

<x-header title="Questionnaire">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div style="display:flex; flex-wrap:wrap; gap:12px; margin:10px 40px">
    @foreach($categories as $category)
        <button type="button"
        class="category-chip @if($selectedCategory && $selectedCategory->id == $category->id) active @endif"
        onclick="toggleCategory({{ $category->id }}, {{ $selectedCategory?->id ?? 'null' }})">
    <span class="category-name">{{ $category->name }}</span>
</button>

    @endforeach
</div>



<form id="delete-category-form" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

@if($selectedCategory)
<div id="edit-category-form" style="background:#FFFFFF; border-radius:14px; padding:26px; margin:20px 40px 60px; box-shadow:6px 6px 54px #0000000D;">
   <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="font-size: 24px; font-weight: bold; color: #202224; margin:0;">Edit Category</h3>
        <button type="button" 
                onclick="deleteCategory({{ $selectedCategory->id }}, '{{ $selectedCategory->name }}')"
                style="padding:8px 16px; background:#FF4D4F; color:white; border:none; border-radius:8px; cursor:pointer;">
            <i class="fas fa-trash" style="margin-right:6px;"></i>Delete Category
        </button>
    </div>

    <form action="{{ route('categories.update', $selectedCategory->id) }}" method="POST" id="category-edit-form">
        @csrf
        @method('PUT')

        <div style="margin-bottom: 16px;">
            <label style="display:block; margin-bottom:8px; font-weight:600;">Name</label>
            <input type="text" name="name" value="{{ $selectedCategory->name }}" required
                   style="width:100%; padding:10px; border:1px solid #4880FF; border-radius:8px;">
        </div>

        <div style="margin-bottom: 16px;">
            <div style="display:flex; flex-direction:column; gap:8px;">
                @php
                    $criteria = is_array($selectedCategory->criteria) 
                        ? $selectedCategory->criteria 
                        : json_decode($selectedCategory->criteria ?? '{}', true);
                @endphp
                @foreach([ 'high'=>'High', 'medium'=>'Medium', 'low'=>'Low'] as $key => $label)
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                    <label style="font-weight:500;width: 15%">{{ $label }} Criteria</label>
                    <input type="text" name="criteria[{{ $key }}]" 
                           value="{{ $criteria[$key] ?? '' }}" required
                           placeholder="{{ $label }} Criteria"
                           style="width:85%; padding:10px; border:1px solid #4880FF; border-radius:8px;">
                </div>
                @endforeach
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h4 style="font-size: 18px; font-weight: 600; color: #202224; margin:0;">Questions</h4>
                <button type="button" id="add-question-btn" 
                    style="padding: 10px 20px; background: #4880FF; color: white; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                    <i class="fas fa-plus" style="margin-right: 6px;"></i> Add Question
                </button>
            </div>
            
            <div id="questions-container" style="display:flex; flex-direction:column; gap:16px;">
                @php $questionNumber = 1; @endphp
                @foreach($selectedCategory->questions as $question)
                    <div class="question-card existing-card" 
                         data-question-id="{{ $question->id }}"
                         style="border: 1px solid #E0E0E0; border-radius: 12px; background: #FFFFFF; box-shadow: 0 2px 8px rgba(0,0,0,0.05); cursor: pointer;">
                        
                        <div class="question-header" style="padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div class="question-number"
                                    style="width: 32px; height: 32px; border-radius: 50%; background: #F0F7FF; 
                                          display: flex; align-items: center; justify-content: center; font-size: 14px; color: #4880FF; font-weight: bold;">
                                    {{ $question->question_no}}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #202224; font-size: 16px;">
                                        {{ $question->question_text ? Str::limit($question->question_text, 80) : 'Untitled Question' }}
                                    </div>
                                    <div style="font-size: 12px; color: #6C757D; margin-top: 4px;">
                                        <span>{{ $question->question_type == 'pilihan' ? 'Multiple Choice' : 'Text Answer' }}</span>
                                        • 
                                        <span>{{ $selectedCategory->name }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="expand-icon">
                                <i class="fas fa-chevron-down" style="color: #6C757D; font-size: 16px;"></i>
                            </div>
                        </div>

                        <div class="question-body" style="display: none; padding: 0 20px 20px; border-top: 1px solid #F0F0F0;">
                            <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-top: 20px;">
                                
                                <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 20px;">
                                    <div>
                                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Question</label>
                                        <textarea 
                                            name="questions[{{ $question->id }}][question_text]"
                                            placeholder="Enter question text..."
                                            class="question-textarea"
                                            style="color: #202224; font-size: 14px; background: #F8F9FA; border: 1px solid #DEE2E6; border-radius: 8px; 
                                                   padding: 12px; width: 100%; resize: vertical; min-height: 36px;"
                                            rows="2"
                                        >{{ $question->question_text }}</textarea>
                                    </div>

                                    <div class="answer-section" data-question-id="{{ $question->id }}">
                                        @if($question->question_type == 'pilihan')
                                            <div>
                                                <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 12px; display: block;">Options</label>
                                                <div class="options-container" style="display: flex; flex-direction: column; gap: 12px;">
                                                    @foreach($question->options as $i => $option)
                                                        <div class="option-item" style="display: flex; gap: 12px; align-items: center;">
                                                            <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                                                <input 
                                                                    type="text"
                                                                    name="questions[{{ $question->id }}][options][{{ $i }}][text]"
                                                                    value="{{ $option->option_text }}"
                                                                    placeholder="Option text"
                                                                    style="flex: 1; padding: 10px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
                                                                />
                                                            </div>
                                                            <input 
                                                                type="number"
                                                                name="questions[{{ $question->id }}][options][{{ $i }}][score]"
                                                                value="{{ $option->score }}"
                                                                placeholder="Score"
                                                                style="width: 80px; padding: 10px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
                                                            />
                                                            <button type="button" class="delete-option-btn"
                                                                    style="background: transparent; border: none; padding: 0; margin: 0; color: #FF4D4F; 
                                                                           font-size: 18px; cursor: pointer; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                                                                ×
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <button type="button" class="add-option-btn" data-question-id="{{ $question->id }}" 
                                                        style="margin-top: 12px; padding: 10px 20px; background: #4880FF; color: white; border: none; 
                                                               border-radius: 6px; font-size: 14px; cursor: pointer;">
                                                    <i class="fas fa-plus" style="margin-right: 6px;"></i>Add Option
                                                </button>
                                            </div>
                                        @else
                                            <div>
                                                <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Answer Clue</label>
                                                <input 
                                                    type="text"
                                                    name="questions[{{ $question->id }}][clue]"
                                                    value="{{ $question->clue ?? '' }}"
                                                    placeholder="Optional clue for text answer"
                                                    style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; width: 100%;"
                                                />
                                            </div>
                                        @endif
                                    </div>
                                    <div style="margin-top: auto; width: 100%;">
    <button type="button" class="delete-question-btn"
        data-question-id="{{ $question->id }}"
        style="align-self: flex-start; padding: 10px 20px; background: #FF4D4F; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s;">
        <i class="fas fa-trash" style="margin-right: 6px;"></i>Delete Question
    </button>
    </div>


                                </div>

                                <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 20px;">
                                    <div>
                                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Type</label>
                                        <select 
                                            class="question-type-select" 
                                            data-question-id="{{ $question->id }}" 
                                            name="questions[{{ $question->id }}][question_type]"
                                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; 
                                                   color: #202224; background: white; width: 100%; cursor: pointer;"
                                        >
                                            <option value="pilihan" {{ $question->question_type == 'pilihan' ? 'selected' : '' }}>Multiple Choice</option>
                                            <option value="isian" {{ $question->question_type == 'isian' ? 'selected' : '' }}>Text Answer</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Indicator</label>
                                        <div style="display: flex; gap: 24px;">
                                            @php
                                                $indicatorArray = is_array($question->indicator)
                                                    ? $question->indicator
                                                    : json_decode($question->indicator ?? '[]', true);
                                            @endphp
                                            @foreach(['umum'=>'Umum','high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $indValue => $indLabel)
                                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                                    <input type="checkbox"
                                                           name="questions[{{ $question->id }}][indicator][]"
                                                           value="{{ $indValue }}"
                                                           style="width: 18px; height: 18px; accent-color: #4880FF;"
                                                           {{ in_array($indValue, $indicatorArray) ? 'checked' : '' }}
                                                    />
                                                    <span style="font-size: 14px; color: #202224;">{{ $indLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Attachment Note</label>
                                        <input 
                                            type="text"
                                            name="questions[{{ $question->id }}][attachment_text]"
                                            value="{{ $question->attachment_text }}"
                                            placeholder="Please attach supporting document"
                                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; width: 100%;"
                                        />
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <label style="color: #202224; font-size: 12px; font-weight: 600;">Sub Category</label>
                                        <input 
                                            type="text"
                                            name="questions[{{ $question->id }}][sub]"
                                            value="{{ $question->sub}}"
                                            placeholder="Please insert sub category"
                                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px;"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @php $questionNumber++; @endphp
                @endforeach
            </div>

            <input type="hidden" name="deleted_questions" id="deleted-questions" value="">

            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:30px;">
                <button type="button" onclick="window.location.href='{{ route('questionnaire.index') }}'"
                        style="padding:12px 24px; background:white; border:1px solid #4880FF; color:#4880FF; border-radius:8px;">Cancel</button>
                <button type="submit"
                        style="padding:12px 24px; background:#4379EE; color:white; border:none; border-radius:8px;">Save Changes</button>
            </div>
        </div>
    </form>
</div>
@else
<div id="create-category-form" style="background:#FFFFFF; border-radius:14px; padding:26px; margin:20px 40px 60px; box-shadow:6px 6px 54px #0000000D;">
   <h3 style="font-size: 24px; font-weight: bold; color: #202224; margin-bottom: 12px;">Create New Category</h3>

    <form action="{{ route('categories.store') }}" method="POST" id="category-form">
        @csrf

        <div style="margin-bottom: 16px;">
            <label style="display:block; margin-bottom:8px; font-weight:600;">Name</label>
            <input type="text" name="name" id="category-input" placeholder="Category Name" 
                   value="{{ old('name') }}" required
                   style="width:100%; padding:10px; border:1px solid #4880FF; border-radius:8px;">
            @error('name')
                <div style="color:red; font-size:12px; margin-top:4px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 16px;">
            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach([ 'high'=>'High', 'medium'=>'Medium', 'low'=>'Low'] as $key => $label)
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                    <label style="font-weight:500;width: 15%">{{ $label }} Criteria</label>
                    <input type="text" name="criteria[{{ $key }}]" 
                           value="{{ old("criteria.$key") }}" required
                           placeholder="{{ $label }} Criteria"
                           style="width:85%; padding:10px; border:1px solid #4880FF; border-radius:8px;">
                </div>
                @endforeach
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h4 style="font-size: 18px; font-weight: 600; color: #202224; margin:0;">Questions (Optional)</h4>
                <button type="button" id="add-question-btn" 
                    style="padding: 10px 20px; background: #4880FF; color: white; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                    <i class="fas fa-plus" style="margin-right: 6px;"></i> Add Question
                </button>
            </div>
            
            <div id="questions-container" style="display:flex; flex-direction:column; gap:16px;"></div>
        </div>

        <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:20px;">
            <a  href="{{ route('questionnaire.index') }}"
                style="padding:12px 24px; background:white; border:1px solid #4880FF; color:#4880FF; border-radius:8px;">Cancel</a>
            <button type="submit" id="save-btn"
                    style="padding:12px 24px; background:#4379EE; color:white; border:none; border-radius:8px;">Create Category</button>
        </div>
    </form>
</div>
@endif

<script>
function selectCategory(categoryId) {
    window.location.href = `/questionnaire/categories?selected=${categoryId}`;
}

function selectCategory(categoryId) {
    window.location.href = `/questionnaire/categories?selected=${categoryId}`;
}


function deleteCategory(id, name) {
    const confirmDelete = confirm(
        `PERINGATAN!\n\n` +
        `Jika category "${name}" dihapus,\n` +
        `SEMUA pertanyaan di dalamnya juga akan TERHAPUS.\n\n` +
        `Yakin ingin melanjutkan?`
    );

    if (confirmDelete) {
        const form = document.getElementById('delete-category-form');
        form.action = `/categories/${id}`;
        form.submit();
    }
}

// Question card toggle functionality
document.addEventListener('click', function(e) {
    const header = e.target.closest('.question-header');
    if (header) {
        const card = header.closest('.question-card');
        const body = card.querySelector('.question-body');
        const icon = card.querySelector('.expand-icon i');
        
        if (body.style.display === 'none' || !body.style.display) {
            body.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
        } else {
            body.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    }
    
    // Delete question button
    const deleteBtn = e.target.closest('.delete-question-btn');
    if (deleteBtn) {
        const questionId = deleteBtn.dataset.questionId;
        const card = deleteBtn.closest('.question-card');
        
        if (confirm('Are you sure you want to delete this question?')) {
            // If it's an existing question (not new), add to deleted list
            if (questionId && !questionId.startsWith('new_')) {
                const deletedInput = document.getElementById('deleted-questions');
                const deleted = deletedInput.value ? deletedInput.value.split(',') : [];
                deleted.push(questionId);
                deletedInput.value = deleted.join(',');
            }
            
            card.remove();
            renumberQuestions();
        }
    }
    
    // Add option button
    const addOptionBtn = e.target.closest('.add-option-btn');
    if (addOptionBtn) {
        const questionId = addOptionBtn.dataset.questionId;
        const container = document.querySelector(`.answer-section[data-question-id="${questionId}"] .options-container`);
        const index = container.children.length;
        
        const div = document.createElement('div');
        div.className = 'option-item';
        div.style.display = 'flex';
        div.style.gap = '12px';
        div.style.alignItems = 'center';
        
        div.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                <input 
                    type="text"
                    name="questions[${questionId}][options][${index}][text]"
                    placeholder="Option text"
                    style="flex: 1; padding: 10px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
                />
            </div>
            <input 
                type="number"
                name="questions[${questionId}][options][${index}][score]"
                placeholder="Score"
                style="width: 80px; padding: 10px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
            />
            <button type="button" class="delete-option-btn"
                    style="background: transparent; border: none; padding: 0; margin: 0; color: #FF4D4F; 
                           font-size: 18px; cursor: pointer; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                ×
            </button>
        `;
        
        container.appendChild(div);
    }
    
    // Delete option button
    const deleteOptionBtn = e.target.closest('.delete-option-btn');
    if (deleteOptionBtn && !deleteOptionBtn.closest('.add-option-btn')) {
        const optionItem = deleteOptionBtn.closest('.option-item');
        if (optionItem) {
            optionItem.remove();
        }
    }
});

// Question type change
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('question-type-select')) {
        const select = e.target;
        const questionId = select.dataset.questionId;
        const answerSection = document.querySelector(`.answer-section[data-question-id="${questionId}"]`);
        
        if (select.value === 'pilihan') {
            answerSection.innerHTML = `
                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 12px; display: block;">Options</label>
                    <div class="options-container" style="display: flex; flex-direction: column; gap: 12px;"></div>
                    <button type="button" class="add-option-btn" data-question-id="${questionId}" 
                            style="margin-top: 12px; padding: 10px 20px; background: #4880FF; color: white; border: none; 
                                   border-radius: 6px; font-size: 14px; cursor: pointer;">
                        <i class="fas fa-plus" style="margin-right: 6px;"></i>Add Option
                    </button>
                </div>`;
        } else {
            answerSection.innerHTML = `
                <div>
                    <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Answer Clue</label>
                    <input 
                        type="text"
                        name="questions[${questionId}][clue]"
                        placeholder="Optional clue for text answer"
                        style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; width: 100%;"
                    />
                </div>`;
        }
    }
});

// Add new question
document.getElementById('add-question-btn')?.addEventListener('click', function() {
    const container = document.getElementById('questions-container');
    const questionNumber = container.children.length + 1;
    const newQuestionId = 'new_' + Date.now();
    const categoryName = document.getElementById('category-input')?.value || '';
    
    const card = document.createElement('div');
    card.className = 'question-card';
    card.dataset.questionId = newQuestionId;
    card.style.border = '1px solid #E0E0E0';
    card.style.borderRadius = '12px';
    card.style.background = '#FFFFFF';
    card.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)';
    card.style.cursor = 'pointer';
    
    card.innerHTML = `
        <div class="question-header" style="padding: 20px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div class="question-number"
                    style="width: 32px; height: 32px; border-radius: 50%; background: #F0F7FF; 
                          display: flex; align-items: center; justify-content: center; font-size: 14px; color: #4880FF; font-weight: bold;">
                    ${questionNumber}
                </div>
                <div>
                    <div style="font-weight: 600; color: #202224; font-size: 16px;">
                        New Question
                    </div>
                    <div style="font-size: 12px; color: #6C757D; margin-top: 4px;">
                        <span>Multiple Choice</span>
                        • 
                        <span>${categoryName}</span>
                    </div>
                </div>
            </div>
            <div class="expand-icon">
                <i class="fas fa-chevron-down" style="color: #6C757D; font-size: 16px;"></i>
            </div>
        </div>

        <div class="question-body" style="display: none; padding: 0 20px 20px; border-top: 1px solid #F0F0F0;">
            <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-top: 20px;">
                
                <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 20px;">
                    <div>
                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Question</label>
                        <textarea 
                            name="questions[${newQuestionId}][question_text]"
                            placeholder="Enter question text..."
                            class="question-textarea"
                            style="color: #202224; font-size: 14px; background: #F8F9FA; border: 1px solid #DEE2E6; border-radius: 8px; 
                                   padding: 12px; width: 100%; resize: vertical; min-height: 36px;"
                            rows="2"
                        ></textarea>
                    </div>

                    <div class="answer-section" data-question-id="${newQuestionId}">
                        <div>
                            <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 12px; display: block;">Options</label>
                            <div class="options-container" style="display: flex; flex-direction: column; gap: 12px;"></div>
                            <button type="button" class="add-option-btn" data-question-id="${newQuestionId}" 
                                    style="margin-top: 12px; padding: 10px 20px; background: #4880FF; color: white; border: none; 
                                           border-radius: 6px; font-size: 14px; cursor: pointer;">
                                <i class="fas fa-plus" style="margin-right: 6px;"></i>Add Option
                            </button>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="delete-question-btn"
                                data-question-id="${newQuestionId}"
                                style="align-self: flex-start; padding: 10px 20px; background: #FF4D4F; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                            <i class="fas fa-trash" style="margin-right: 6px;"></i>Delete Question
                        </button>
                    </div>
                </div>

                <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 20px;">
                    <div>
                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Type</label>
                        <select 
                            class="question-type-select" 
                            data-question-id="${newQuestionId}" 
                            name="questions[${newQuestionId}][question_type]"
                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; 
                                   color: #202224; background: white; width: 100%; cursor: pointer;"
                        >
                            <option value="pilihan" selected>Multiple Choice</option>
                            <option value="isian">Text Answer</option>
                        </select>
                    </div>

                    <div>
                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Indicator</label>
                        <div style="display: flex; gap: 24px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox"
                                       name="questions[${newQuestionId}][indicator][]"
                                       value="high"
                                       style="width: 18px; height: 18px; accent-color: #4880FF;"
                                />
                                <span style="font-size: 14px; color: #202224;">High</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox"
                                       name="questions[${newQuestionId}][indicator][]"
                                       value="medium"
                                       style="width: 18px; height: 18px; accent-color: #4880FF;"
                                />
                                <span style="font-size: 14px; color: #202224;">Medium</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox"
                                       name="questions[${newQuestionId}][indicator][]"
                                       value="low"
                                       style="width: 18px; height: 18px; accent-color: #4880FF;"
                                />
                                <span style="font-size: 14px; color: #202224;">Low</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Attachment Note</label>
                        <input 
                            type="text"
                            name="questions[${newQuestionId}][attachment_text]"
                            placeholder="Please attach supporting document"
                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; width: 100%;"
                        />
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="color: #202224; font-size: 12px; font-weight: 600;">Sub Category</label>
                        <input 
                            type="text"
                            name="questions[${newQuestionId}][sub]"
                            placeholder="Please insert sub category"
                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px;"
                        />
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(card);
});

function renumberQuestions() {
    document.querySelectorAll('.question-card').forEach((card, index) => {
        const numberEl = card.querySelector('.question-number');
        if (numberEl) {
            numberEl.textContent = index + 1;
        }
    });
}
</script>

<script>
function toggleCategory(categoryId, selectedId) {
    // kalau klik category yang sama → UNSELECT
    if (selectedId === categoryId) {
        window.location.href = '{{ route("categories.create") }}';
    } else {
        window.location.href = `{{ route("categories.create") }}?selected=${categoryId}`;
    }
}
</script>


<style>
.category-chip {
    padding: 8px 16px;
    background: #F8F9FA;
    border: 1px solid #E0E0E0;
    border-radius: 20px;
    font-size: 14px;
    color: #202224;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.category-chip:hover {
    border-color: #4880FF;
    background: #F0F7FF;
}

.category-chip.active {
    background: #4880FF;
    color: white;
    border-color: #4880FF;
}

.question-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.question-card:hover {
    border-color: #4880FF !important;
    box-shadow: 0 4px 12px rgba(72, 128, 255, 0.1);
}

.expand-icon i {
    transition: transform 0.3s ease;
}

.question-body {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endsection