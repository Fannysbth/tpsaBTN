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

<form method="POST" action="{{ route('questionnaire.updateAll') }}" id="questionnaire-form">
    @csrf
    @method('PUT')

    <div class="filter-edit-wrapper"
     style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;  width:100%;">

    {{-- FILTER --}}
    <div style="display:flex; gap:20px; flex-wrap:wrap; background:#ffffff; padding:10px; border-radius:12px;">

        {{-- ALL --}}
        <span
            class="filter-item active"
            style="padding:6px 6px; font-size:14px; cursor:pointer;
                   color:#4379EE; border-radius:6px;
                   background:rgba(67,121,238,0.1); border:1px solid #4379EE;">
            All
        </span>

        {{-- KATEGORI --}}
        @foreach ($visibleCategories as $category)
            <span
                class="filter-item"
                data-category-id="{{ $category->id }}"
                style="padding:6px 6px; font-size:14px; cursor:pointer;
                       color:#555; border-radius:6px; border:1px solid transparent;"
                onmouseover="this.style.borderColor='#4379EE'; this.style.background='rgba(67,121,238,0.05)'"
                onmouseout="this.style.borderColor='transparent'; this.style.background='transparent'">
                {{ $category->name }}
            </span>
        @endforeach

        {{-- MORE --}}
        @if ($moreCategories->count())
            <div class="dropdown">
                <span
                    class="dropdown-toggle"
                    data-bs-toggle="dropdown"
                    style="padding:6px 6px; font-size:14px; cursor:pointer; color:#555;">
                    More
                </span>

                <ul class="dropdown-menu">
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

    {{-- ADD --}}
    <div class="dropdown" style="height: 100%">
        <button type="button"
                class="btn btn-primary btn-sm"
                style="height: 100%"
                data-bs-toggle="dropdown">
            <i class="fas fa-plus me-1"></i> Add
        </button>

        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item" href="{{ route('categories.create') }}">
                    Category
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="#" id="add-question">
                    Question
                </a>
            </li>
        </ul>
    </div>
</div>




        {{-- Questions Container --}}
        <div id="questions-container" style="max-height: 600px; overflow-y: auto; padding: 10px;">

            {{-- QUESTION CARDS --}}
            @foreach($categories as $category)
                @foreach($category->questions as $question)
                    <div class="question-card" data-question-id="{{ $question->id }}" data-category="{{ $question->category_id }}" 
                         style="margin-bottom: 16px; border: 1px solid #E0E0E0; border-radius: 12px; background: #FFFFFF; 
                                box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: all 0.3s ease; cursor: pointer;">
                        
                        {{-- Header yang bisa diklik --}}
                        <div class="question-header" style="padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #F0F7FF; 
                                          display: flex; align-items: center; justify-content: center; font-size: 14px; color: #4880FF; font-weight: bold;">
                                    {{ $loop->iteration }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #202224; font-size: 16px;">
                                        {{ $question->question_text ? Str::limit($question->question_text, 80) : 'Untitled Question' }}
                                    </div>
                                    <div style="font-size: 12px; color: #6C757D; margin-top: 4px;">
                                        <span>{{ $question->question_type == 'pilihan' ? 'Multiple Choice' : 'Text Answer' }}</span>
                                        • 
                                        <span>{{ $category->name }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="expand-icon" style="transition: transform 0.3s;">
                                <i class="fas fa-chevron-down" style="color: #6C757D; font-size: 16px;"></i>
                            </div>
                        </div>

                        {{-- Body (awalnya tersembunyi) --}}
                        <div class="question-body" style="display: none; padding: 0 20px 20px 20px; border-top: 1px solid #F0F0F0;">
                            <div style="display: flex; gap: 40px; margin-top: 20px;">
                                
                                {{-- Left Column --}}
                                <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                                    {{-- Question Text --}}
                                    <div>
                                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Question</label>
                                        <textarea 
                                            name="questions[{{ $question->id }}][question_text]"
                                            placeholder="Enter question text..."
                                            class="question-textarea"
                                            style="color: #202224; font-size: 14px; background: #F8F9FA; border: 1px solid #DEE2E6; border-radius: 8px; 
                                                   padding: 12px; width: 100%; resize: vertical; min-height: 100px; transition: border 0.2s;"
                                            rows="4"
                                        >{{ $question->question_text }}</textarea>
                                    </div>

                                    {{-- Answer Section --}}
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
                                    <div style="margin-top: auto;">
    <button 
        type="button" 
        class="delete-question-btn"
        data-question-id="${newQuestionId}"
        style="align-self: flex-start; padding: 10px 20px; background: #FF4D4F; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s;"
        onmouseover="this.style.background='#FF3333'"
        onmouseout="this.style.background='#FF4D4F'">
        <i class="fas fa-trash" style="margin-right: 6px;"></i>Delete Question
    </button>
</div>
                                </div>

                                {{-- Right Column --}}
                                <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                                    {{-- Question Type --}}
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

                                    {{-- Category --}}
                                    <div>
                                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Category</label>
                                        <select 
                                            name="questions[{{ $question->id }}][category_id]"
                                            class="category-select"
                                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; 
                                                   color: #202224; background: white; width: 100%; cursor: pointer;"
                                        >
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" {{ $question->category_id == $cat->id ? 'selected' : '' }}>
                                                    {{ $cat->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Indicator --}}
                                    <div>
                                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Indicator</label>
                                        <div style="display: flex; gap: 24px;">
                                            @foreach(['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $indValue => $indLabel)
                                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                                    @php
                                                        $indicators = is_array($question->indicator)
                                                            ? $question->indicator
                                                            : (json_decode($question->indicator, true) ?? []);
                                                    @endphp
                                                    <input type="checkbox"
                                                           name="questions[{{ $question->id }}][indicator][]"
                                                           value="{{ $indValue }}"
                                                           style="width: 18px; height: 18px; accent-color: #4880FF;"
                                                           {{ in_array($indValue, $indicators) ? 'checked' : '' }}
                                                    />
                                                    <span style="font-size: 14px; color: #202224;">{{ $indLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Attachment --}}
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

                                    {{-- Sub Category --}}
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <label style="color: #202224; font-size: 14px; font-weight: 600; margin-bottom: 8px; display: block;">Attachment Note</label>
                                        <input 
                                            type="text"
                                            name="questions[{{ $question->id }}][sub]"
                                            value="{{ $question->sub }}"
                                            placeholder="Please insert sub category"
                                            style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px;"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>

        {{-- Buttons --}}
        <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 30px; gap: 16px;">
            <button 
                type="button"
                id="cancelBtn"
                style="padding: 12px 28px; background: white; color: #4880FF; border: 1px solid #4880FF; 
                       border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;"
                onclick="history.back()"
            >
                Cancel
            </button>
            
            <button 
                type="submit" 
                id="saveBtn"
                style="padding: 12px 28px; background: #4379EE; color: white; border: none; 
                       border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                Save All Changes
            </button>
        </div>

    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - Initializing...');
    
    let questionCounter = {{ $categories->flatMap->questions->count() }} + 1;
    const answerMemory = {};
    const questionnaireForm = document.getElementById('questionnaire-form');
    
    // ========== EXPAND/COLLAPSE FUNCTIONALITY ==========
    document.addEventListener('click', function(e) {
        // Click pada question header untuk expand/collapse
        if (e.target.closest('.question-header')) {
            const header = e.target.closest('.question-header');
            const card = header.closest('.question-card');
            const body = card.querySelector('.question-body');
            const expandIcon = header.querySelector('.expand-icon i');
            
            if (body.style.display === 'none' || body.style.display === '') {
                // Expand
                body.style.display = 'block';
                expandIcon.style.transform = 'rotate(180deg)';
                card.style.boxShadow = '0 8px 24px rgba(0,0,0,0.1)';
                card.style.borderColor = '#4880FF';
                
                // Auto-resize textarea saat expand
                const textarea = body.querySelector('.question-textarea');
                if (textarea) {
                    textarea.style.height = 'auto';
                    textarea.style.height = (textarea.scrollHeight) + 'px';
                }
            } else {
                // Collapse
                body.style.display = 'none';
                expandIcon.style.transform = 'rotate(0deg)';
                card.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)';
                card.style.borderColor = '#E0E0E0';
            }
        }
    });
    
    // ========== AUTO-RESIZE TEXTAREA ==========
    document.querySelectorAll('.question-textarea').forEach(textarea => {
        // Set initial height
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
        
        // Auto-resize on input
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            
            // Update preview text in header
            const card = this.closest('.question-card');
            const headerText = card.querySelector('.question-header > div > div > div:first-child');
            if (headerText) {
                const previewText = this.value.trim() || 'Untitled Question';
                headerText.textContent = previewText.length > 80 ? previewText.substring(0, 80) + '...' : previewText;
            }
        });
    });
    
    // ... existing JavaScript code dari kode kedua ...
    // (sisakan semua fungsi yang sudah ada seperti answerMemory, filter, dll)
    
    // ========== UPDATE CARD WIDTH ON EXPAND ==========
    function setupQuestionCardWidth(card) {
        const body = card.querySelector('.question-body');
        const header = card.querySelector('.question-header');
        
        if (header) {
            header.addEventListener('click', function() {
                if (body.style.display === 'block' || body.style.display === '') {
                    // Saat expanded, set max width yang lebih besar
                    card.style.maxWidth = '100%';
                    card.style.width = '100%';
                    
                    // Update inner elements width
                    const innerColumns = card.querySelectorAll('.question-body > div > div');
                    innerColumns.forEach(col => {
                        col.style.minWidth = '45%';
                    });
                }
            });
        }
    }
    
    // Setup untuk semua existing cards
    document.querySelectorAll('.question-card').forEach(setupQuestionCardWidth);
    
    // ========== STYLING FOR EXPANDED CARDS ==========
    const style = document.createElement('style');
    style.textContent = `
        .question-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .question-card:hover {
            border-color: #4880FF !important;
            box-shadow: 0 4px 12px rgba(72, 128, 255, 0.1);
        }
        
        .question-header {
            transition: background-color 0.2s;
        }
        
        .question-header:hover {
            background-color: rgba(72, 128, 255, 0.02);
        }
        
        .expand-icon {
            transition: transform 0.3s;
        }
        
        .question-body {
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
    
    console.log('Initialization complete');
});
</script>

@endsection