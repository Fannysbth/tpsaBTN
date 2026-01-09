@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Questionnaire">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

@php
    $visibleCategories = $categories->take(3);
    $moreCategories = $categories->slice(3);
@endphp

<form method="POST" action="{{ route('questionnaire.updateAll') }}">
    @csrf
    @method('PUT')

    <div style="max-width: 1202px; box-sizing: border-box; background: #F5F6FA; padding: 1px; margin: 10px 0 0 10px;">

        <div class="filter-edit-wrapper" style="margin-bottom: 3px">
            <div class="filter-container">

                {{-- ALL --}}
                <div class="filter-item fixed active">
                    <span class="filter-text">All</span>
                    <div class="divider"></div>
                </div>

                {{-- 3 KATEGORI PERTAMA --}}
                @foreach ($visibleCategories as $category)
                    <div class="filter-item" data-category-id="{{ $category->id }}">
                        <span class="filter-text truncate">
                            {{ $category->name }}
                        </span>
                        <div class="divider"></div>
                    </div>
                @endforeach

                {{-- MORE --}}
                @if ($moreCategories->count())
                    <div class="dropdown filter-more">
                        <button class="dropdown-toggle btn btn-link p-0 text-decoration-none"
                                type="button"
                                data-bs-toggle="dropdown">
                            More...
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end mt-2">
                            @foreach ($moreCategories as $category)
                                <li>
                                    <a class="dropdown-item"
                                       href="#"
                                       data-category-id="{{ $category->id }}">
                                        {{ $category->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </div>

            {{-- ADD DROPDOWN --}}
            <div class="dropdown btn-editt">
    <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown" 
            style="color: white;">
         Add
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#" id="add-category">Category</a></li>
        <li><a class="dropdown-item" href="#" id="add-question">Question</a></li>
    </ul>
</div>

        </div>

        {{-- Questions Container --}}
        <div id="questions-container"  style="max-height: 500px; overflow-y: auto; padding-right: 10px; margin-bottom: 10px; margin-top: 10px;">

            {{-- QUESTION CARDS --}}
            @foreach($categories as $category)
                @foreach($category->questions as $question)
                    <div class="question-card" data-question-id="{{ $question->id }}" data-category="{{ $question->category_id }}" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; padding: 20px; border: 1px solid #E0E0E0; border-radius: 12px; background: #FFFFFF; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        
                        <div style="display: flex; gap: 30px; position: relative;">
                            <!-- Left Group: Question and Answer -->
                            <div style="flex: 1; display: flex; flex-direction: column; gap: 16px;">
                                <!-- Question Name -->
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label style="color: #202224; font-size: 12px; font-weight: 600;">Question</label>
                                    <textarea 
                                        name="questions[{{ $question->id }}][question_text]"
                                        placeholder="Enter question text..."
                                        class="question-textarea"
                                        style="color: #202224; font-size: 14px; background: #F8F9FA; border: 1px solid #DEE2E6; border-radius: 8px; padding: 12px; width: 100%; resize: vertical; min-height: 44px; transition: border 0.2s;"
                                        rows="2"
                                        oninput="this.style.height = 'auto'; this.style.height = (this.scrollHeight) + 'px';"
                                    >{{ $question->question_text }}</textarea>
                                </div>

                                <!-- Answer Section -->
                                <div class="answer-section" data-question-id="{{ $question->id }}">
                                    @if($question->question_type == 'pilihan')
                                        <!-- Multiple Choice Options -->
                                        <div style="display: flex; flex-direction: column; gap: 12px;">
                                            <label style="color: #202224; font-size: 12px; font-weight: 600;">Options</label>
                                            <div class="options-container" style="display: flex; flex-direction: column; gap: 8px;">
                                                @foreach($question->options as $i => $option)
                                                    <div class="option-item" style="display: flex; gap: 12px; align-items: center;">
                                                        <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                                    
                                                            <input 
                                                                type="text"
                                                                name="questions[{{ $question->id }}][options][{{ $i }}][text]"
                                                                value="{{ $option->option_text }}"
                                                                placeholder="Option text"
                                                                class="option-text-input"
                                                                style="flex: 1; padding: 8px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
                                                            />
                                                        </div>
                                                        <input 
                                                            type="number"
                                                            name="questions[{{ $question->id }}][options][{{ $i }}][score]"
                                                            value="{{ $option->score }}"
                                                            placeholder="0"
                                                            class="option-score-input"
                                                            style="width: 50px; padding: 8px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
                                                        />
                                                    </div>
                                                @endforeach
                                                {{-- Add empty option template --}}
                                                <div class="option-item new-option-template" style="display: flex; gap: 12px; align-items: center; display: none;">
                                                    <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                                        
                                                        <input 
                                                            type="text"
                                                            placeholder="Add new option"
                                                            class="new-option-input"
                                                            style="flex: 1; padding: 8px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
                                                        />
                                                    </div>
                                                    <input 
                                                        type="number"
                                                        placeholder="0"
                                                        class="new-option-score"
                                                        style="width: 50px; padding: 8px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
                                                    />
                                                </div>
                                            </div>
                                            <button type="button" class="add-option-btn" data-question-id="{{ $question->id }}" style="align-self: flex-start; padding: 8px 16px; background: #4880FF; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">
                                                <i class="fas fa-plus" style="margin-right: 6px;"></i>Add Option
                                            </button>
                                        </div>
                                    @elseif($question->question_type == 'isian')
                                        <!-- Text Answer Input -->
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <label style="color: #202224; font-size: 12px; font-weight: 600;">Answer</label>
                                            <input 
                                                type="text"
                                                name="questions[{{ $question->id }}][answer_text]"
                                                value="{{ $question->answer_text ?? '' }}"
                                                placeholder="{{ $question->clue ?? 'Optional clue...' }}"
                                                class="answer-input"
                                                style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px;"
                                            />
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Right Group: Type, Category, Indicator, Attachment -->
                            <div style="flex: 1; display: flex; flex-direction: column; gap: 16px;">
                                <!-- Type -->
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label style="color: #202224; font-size: 12px; font-weight: 600;">Type</label>
                                    <select 
                                        class="question-type-select" 
                                        data-question-id="{{ $question->id }}" 
                                        name="questions[{{ $question->id }}][question_type]"
                                        style="padding: 10px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; color: #202224; background: white; cursor: pointer;"
                                    >
                                        <option value="pilihan" {{ $question->question_type == 'pilihan' ? 'selected' : '' }}>Multiple Choice</option>
                                        <option value="isian" {{ $question->question_type == 'isian' ? 'selected' : '' }}>Text Answer</option>
                                    </select>
                                </div>

                                <!-- Category -->
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label style="color: #202224; font-size: 12px; font-weight: 600;">Category</label>
                                    <select 
                                        name="questions[{{ $question->id }}][category_id]"
                                        class="category-select"
                                        style="padding: 10px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; color: #202224; background: white; cursor: pointer;"
                                    >
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ $question->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Indicator -->
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label style="color: #202224; font-size: 12px; font-weight: 600;">Indicator</label>
                                    <div style="display: flex; gap: 16px;">
                                        @foreach(['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $indValue => $indLabel)
                                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                                

                                                <input 
    type="checkbox"
       name="questions[{{ $question->id }}][indicator][]"
       value="high"
    {{ in_array($indValue, $question->indicator ?? []) ? 'checked' : '' }}

    style="width: 16px; height: 16px; accent-color: #4880FF;"
/>

                                                <span style="font-size: 14px; color: #202224;">{{ $indLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Attachment -->
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label style="color: #202224; font-size: 12px; font-weight: 600;">Attachment</label>
                                    <input 
                                        type="text"
                                       name="questions[{{ $question->id }}][attachment_text]"
                                        value="{{ $question->attachment_text }}"
                                        placeholder="Please attach supporting document"
                                        style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px;"
                                    />
                                    
                                </div>

                                <!-- Delete Button -->
                                <button 
                                    type="button" 
                                    class="delete-question-btn"
                                    data-question-id="{{ $question->id }}"
                                    style="align-self: flex-end; margin-top: auto; padding: 10px 20px; background: #FF4D4F; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s;"
                                    onmouseover="this.style.background='#FF3333'"
                                    onmouseout="this.style.background='#FF4D4F'"
                                >
                                    <i class="fas fa-trash" style="margin-right: 6px;"></i>Delete Question
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>

        {{-- Buttons --}}
        <div style="display: flex; justify-content: flex-end; align-items: center; margin: 5px 0 0; gap: 12px;">
           

            <button 
    type="button"
    id="cancelBtn"
    style="padding: 12px 24px; background: white; color: #4880FF; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
    onclick="history.back()"
>
    Cancel
</button>

            
            <button 
    type="submit" 
    id="saveBtn"
    style="padding: 12px 24px; background: #4379EE; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s;">
    Save All Changes
</button>

        </div>

    </div>
</form>

<script>
let isSubmitting = false;

document.getElementById('saveBtn').addEventListener('click', function (e) {
    e.preventDefault();

    Swal.fire({
        title: 'Save changes?',
        text: 'All changes will be saved to the database.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4379EE',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, save it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            isSubmitting = true;
            e.target.closest('form').submit();
        }
    });
});
</script>

<script>
let formChanged = false;
const form = document.querySelector('form');

form.addEventListener('change', () => {
    formChanged = true;
});

window.addEventListener('beforeunload', function (e) {
    if (formChanged && !isSubmitting) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

@if(session('success'))
<script>
Swal.fire({
    icon: 'success',
    title: 'Saved!',
    text: '{{ session('success') }}',
    timer: 2000,
    showConfirmButton: false
});
</script>
@endif



<script>
document.addEventListener('DOMContentLoaded', function() {
    let questionCounter = {{ $categories->flatMap->questions->count() }} + 1;

    // Initialize existing textareas
    document.querySelectorAll('.question-textarea').forEach(textarea => {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    });

    // Question type change handler
    document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('question-type-select')) return;

    const select = e.target;
    const questionId = select.dataset.questionId;
    const answerSection = document.querySelector(
        `.answer-section[data-question-id="${questionId}"]`
    );

    if (!answerSection) return;

    if (select.value === 'pilihan') {
        answerSection.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <label style="color: #202224; font-size: 12px; font-weight: 600;">Options</label>
                <div class="options-container" style="display: flex; flex-direction: column; gap: 8px;">
                    <div class="option-item new-option-template" style="display:none;"></div>
                </div>
                <button type="button" class="add-option-btn" data-question-id="${questionId}" style="align-self: flex-start; padding: 8px 16px; background: #4880FF; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">
                    Add Option
                </button>
            </div>
        `;
        addOption(questionId);
    } else {
        answerSection.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="color: #202224; font-size: 12px; font-weight: 600;">Answer</label>
                <input 
                    type="text"
                    name="questions[${questionId}][answer_text]"
                    placeholder="Optional clue..."
                    style="padding:12px;border:1px solid #4880FF;border-radius:8px;"
                />
            </div>
        `;
    }
});


    // Add option function
    function addOption(questionId) {
        const optionsContainer = document.querySelector(`.answer-section[data-question-id="${questionId}"] .options-container`);
        const optionCount = optionsContainer.querySelectorAll('.option-item:not(.new-option-template)').length;
        const letter = String.fromCharCode(65 + optionCount);
        
        const newOption = document.createElement('div');
        newOption.className = 'option-item';
        newOption.innerHTML = `
            <div style="display: flex; gap: 12px; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                    
                    <input 
                        type="text"
                        name="questions[${questionId}][options][${optionCount}][text]"
                        placeholder="Option text"
                        class="option-text-input"
                        style="flex: 1; padding: 8px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
                    />
                </div>
                <input 
                    type="number"
                    name="questions[${questionId}][options][${optionCount}][score]"
                    placeholder="0"
                    class="option-score-input"
                    style="width: 50px; padding: 8px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
            </div>
        `;
        
        optionsContainer.insertBefore(newOption, optionsContainer.querySelector('.new-option-template'));
        updateOptionLetters(questionId);
    }

    // Update option letters
    function updateOptionLetters(questionId) {
        const optionsContainer = document.querySelector(`.answer-section[data-question-id="${questionId}"] .options-container`);
        const optionItems = optionsContainer.querySelectorAll('.option-item:not(.new-option-template)');
        
        optionItems.forEach((item, index) => {
            const letterSpan = item.querySelector('span');
            if (letterSpan) {
                letterSpan.textContent = String.fromCharCode(65 + index);
            }
        });
    }

    // Setup option input events
    function setupOptionEvents() {
        document.querySelectorAll('.option-text-input').forEach(input => {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const questionId = this.closest('.answer-section').dataset.questionId;
                    addOption(questionId);
                }
            });
        });
    }

    // Add option button click
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
            const questionId = button.dataset.questionId;
            
            if (questionId && !isNaN(questionId) && questionId > 0) {
                // Existing question - mark for deletion
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = `questions[${questionId}][_delete]`;
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
    document.getElementById('add-question').addEventListener('click', function(e) {
        e.preventDefault();
        
        const newQuestionId = 'new_' + Date.now();
        const container = document.getElementById('questions-container');
        
        const newCard = document.createElement('div');
        newCard.className = 'question-card';
        newCard.dataset.questionId = newQuestionId;
        newCard.dataset.category = '{{ $categories->first()->id ?? 1 }}';
        newCard.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; padding: 20px; border-radius: 12px; background: #FFFFFF; ">
                
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
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <label style="color: #202224; font-size: 12px; font-weight: 600;">Options</label>
                                <div class="options-container" style="display: flex; flex-direction: column; gap: 8px;">
                                    <div class="option-item new-option-template" style="display: flex; gap: 12px; align-items: center; display: none;">
                                        <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                            <div style="width: 24px; height: 24px; border-radius: 4px; border: 1px solid #4880FF; display: flex; align-items: center; justify-content: center;">
                                                <span style="color: #4880FF; font-size: 12px;">A</span>
                                            </div>
                                            <input 
                                                type="text"
                                                placeholder="Add new option"
                                                class="new-option-input"
                                                style="flex: 1; padding: 8px 12px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
                                            />
                                        </div>
                                        <input 
                                            type="number"
                                            placeholder="0"
                                            class="new-option-score"
                                            style="width: 50px; padding: 8px; border: 1px solid #4880FF; border-radius: 6px; font-size: 14px;"
                                        />
                                    </div>
                                </div>
                                <button type="button" class="add-option-btn" data-question-id="${newQuestionId}" style="align-self: flex-start; padding: 8px 16px; background: #4880FF; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">
                                    <i class="fas fa-plus" style="margin-right: 6px;"></i>Add Option
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
                            <select 
                                name="questions[${newQuestionId}][category_id]"
                                class="category-select"
                                style="padding: 10px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; color: #202224; background: white; cursor: pointer;"
                            >
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Indicator -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="color: #202224; font-size: 12px; font-weight: 600;">Indicator</label>
                            <div style="display: flex; gap: 16px;">
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input 
                                        type="checkbox" 
                                        name="questions[${newQuestionId}][indicator][]" 
                                        value="high"
                                        style="width: 16px; height: 16px; accent-color: #4880FF;"
                                    />
                                    <span style="font-size: 14px; color: #202224;">High</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input 
                                        type="checkbox" 
                                        name="questions[${newQuestionId}][indicator][]" 
                                        value="medium"
                                        style="width: 16px; height: 16px; accent-color: #4880FF;"
                                    />
                                    <span style="font-size: 14px; color: #202224;">Medium</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input 
                                        type="checkbox" 
                                        name="questions[${newQuestionId}][indicator][]" 
                                        value="low"
                                        style="width: 16px; height: 16px; accent-color: #4880FF;"
                                    />
                                    <span style="font-size: 14px; color: #202224;">Low</span>
                                </label>
                            </div>
                        </div>

                        <!-- Attachment -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="color: #202224; font-size: 12px; font-weight: 600;">Attachment</label>
                            <input 
                                type="text"
                                name="questions[${newQuestionId}][attachment]"
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
            </div>
        `;
        
        container.appendChild(newCard);
        
        // Add first option
        addOption(newQuestionId);
        
        // Initialize textarea auto-resize
        const textarea = newCard.querySelector('.question-textarea');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Scroll to new card
        newCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        questionCounter++;
    });

    // Category filter functionality
    document.querySelectorAll('.filter-item[data-category-id]').forEach(item => {
        item.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            
            // Update UI
            document.querySelectorAll('.filter-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Filter questions
            document.querySelectorAll('.question-card').forEach(card => {
                const cardCategory = card.dataset.category;
                card.style.display = cardCategory == categoryId ? 'flex' : 'none';
            });
        });
    });

    // All filter
    document.querySelector('.filter-item.fixed').addEventListener('click', function() {
        document.querySelectorAll('.filter-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.question-card').forEach(card => {
            card.style.display = 'flex';
        });
    });

    // Setup initial option events
    setupOptionEvents();
});
</script>


@endsection