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
        <li><a class="dropdown-item" href="{{ route('categories.create') }}">Category</a></li>
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
                                                        <button type="button"
                    class="delete-option-btn"
                    style="background: transparent;
            border: none;
            padding: 0;
            margin: 0;
            color: #000;
            font-size: 16px;
            cursor: pointer;">
                X
            </button>
        
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
<div style="display: flex; flex-direction: column; gap: 8px;">
    <label>Answer</label>
    <input 
        type="text"
        name="questions[{{ $question->id }}][clue]"
        value="{{ $question->clue ?? '' }}"
        placeholder="Optional Clue"
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


@php
    $indicators = is_array($question->indicator)
        ? $question->indicator
        : (json_decode($question->indicator, true) ?? []);
@endphp

<input type="checkbox"
   name="questions[{{ $question->id }}][indicator][]"
   value="{{ $indValue }}"
   style="width: 16px; height: 16px; accent-color: #4880FF;"
   {{ in_array($indValue, $indicators) ? 'checked' : '' }}
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
function validateForm() {
    let valid = true;
    let message = '';

    document.querySelectorAll('.question-card').forEach(card => {
        if (card.style.display === 'none') return; // skip yang dihapus

        const qId = card.dataset.questionId;
        const question = card.querySelector(`[name="questions[${qId}][question_text]"]`);
        const category = card.querySelector(`[name="questions[${qId}][category_id]"]`);
        const indicators = card.querySelectorAll(`[name="questions[${qId}][indicator][]"]:checked`);
        const type = card.querySelector(`[name="questions[${qId}][question_type]"]`).value;

        if (!question || question.value.trim() === '') {
            valid = false;
            message = 'Question tidak boleh kosong';
        }

        if (!category || category.value === '') {
            valid = false;
            message = 'Category tidak boleh kosong';
        }

        if (indicators.length === 0) {
            valid = false;
            message = 'Indicator harus dipilih';
        }

        if (type === 'pilihan') {
            const options = card.querySelectorAll('.option-text-input');
            if (options.length === 0) {
                valid = false;
                message = 'Pilihan jawaban tidak boleh kosong';
            }
            options.forEach(opt => {
                if (opt.value.trim() === '') {
                    valid = false;
                    message = 'Text pilihan tidak boleh kosong';
                }
            });
        }

        if (type === 'isian') {
            const clue = card.querySelector(`[name="questions[${qId}][clue]"]`);
            if (!clue || clue.value.trim() === '') {
                valid = false;
                message = 'Clue tidak boleh kosong';
            }
        }
    });

    if (!valid) {
        Swal.fire({
            icon: 'error',
            title: 'Oops!',
            text: message
        });
    }

    return valid;
}
</script>


<script>
document.addEventListener('change', function(e) {
    if (!e.target.matches('input[type="checkbox"][name$="[indicator][]"]')) return;

    const checkbox = e.target;
    const group = checkbox.closest('.question-card')
                         .querySelectorAll('input[type="checkbox"][name$="[indicator][]"]');

    const map = {
        high: ['high'],
        medium: ['medium','high'],
        low: ['low', 'high','medium']
    };

    const selected = checkbox.value;

    // reset semua
    group.forEach(cb => cb.checked = false);

    // set sesuai aturan
    map[selected].forEach(val => {
        group.forEach(cb => {
            if (cb.value === val) cb.checked = true;
        });
    });
});

</script>


<script>
let isSubmitting = false;

document.getElementById('saveBtn').addEventListener('click', function (e) {
    e.preventDefault();

     if (!validateForm()) return; 

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
    const answerState = {};

document.addEventListener('DOMContentLoaded', function() {
    let questionCounter = {{ $categories->flatMap->questions->count() }} + 1;

    document.querySelectorAll('.question-type-select').forEach(sel => {
    sel.dataset.prevType = sel.value;
});

    // Initialize existing textareas
    document.querySelectorAll('.question-textarea').forEach(textarea => {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    });

    const answerMemory = {};


    // Question type change handler
    document.addEventListener('change', function(e){
  if(!e.target.classList.contains('question-type-select')) return;

  const select = e.target;
  const qId = select.dataset.questionId;
  const card = select.closest('.question-card');
  const answerSection = card.querySelector('.answer-section');

  if(!answerMemory[qId]){
    answerMemory[qId] = { pilihan: [], isian: '' };
  }

  const prevType = select.dataset.prevType;

  // SIMPAN DATA LAMA
  if(prevType === 'pilihan'){
    const opts = [];
    answerSection.querySelectorAll('.option-item').forEach(item=>{
      const text = item.querySelector('.option-text-input')?.value || '';
      const score = item.querySelector('.option-score-input')?.value || '';
      if(text !== '' || score !== '') opts.push({text, score});
    });
    answerMemory[qId].pilihan = opts;
  }

  if(prevType === 'isian'){
    const clue = answerSection.querySelector('input[name$="[clue]"]');
    answerMemory[qId].isian = clue ? clue.value : '';
  }

  // RENDER ULANG SESUAI TIPE BARU
  if(select.value === 'pilihan'){
    const data = answerMemory[qId].pilihan;
    answerSection.innerHTML = `
      <div style="display:flex;flex-direction:column;gap:12px;">
        <label style="font-size:12px;font-weight:600;">Options</label>
        <div class="options-container"></div>
        <button type="button" class="add-option-btn" data-question-id="${qId}"
          style="align-self:flex-start;padding:8px 16px;background:#4880FF;color:white;border:none;border-radius:6px;">
          Add Option
        </button>
      </div>
    `;
    const cont = answerSection.querySelector('.options-container');

    if(data.length){
      data.forEach((opt,i)=>{
        cont.insertAdjacentHTML('beforeend', optionTemplate(qId,i,opt.text,opt.score));
      });
    }else{
      cont.insertAdjacentHTML('beforeend', optionTemplate(qId,0,'',''));
    }
  }

  if(select.value === 'isian'){
    const clue = answerMemory[qId].isian || '';
    answerSection.innerHTML = `
      <div style="display:flex;flex-direction:column;gap:8px;">
        <label style="font-size:12px;font-weight:600;">Answer</label>
        <input type="text"
          name="questions[${qId}][clue]"
          value="${clue}"
          placeholder="Optional Clue"
          style="padding:12px;border:1px solid #4880FF;border-radius:8px;">
      </div>
    `;
  }

  select.dataset.prevType = select.value;
});

document.addEventListener('click', function(e) {
    // Semua item filter, termasuk dropdown
    if(e.target.matches('.filter-item[data-category-id], .dropdown-item[data-category-id]')) {
        e.preventDefault();
        const categoryId = e.target.dataset.categoryId;

        // Hapus semua active
        document.querySelectorAll('.filter-item').forEach(i => i.classList.remove('active'));
        document.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));

        // Tambahkan active ke yang diklik
        e.target.classList.add('active');

        // Filter questions
        document.querySelectorAll('.question-card').forEach(card => {
            card.style.display = card.dataset.category == categoryId ? 'flex' : 'none';
        });
    }

    // Klik "All"
    if(e.target.matches('.filter-item.fixed')) {
        document.querySelectorAll('.filter-item, .dropdown-item').forEach(i => i.classList.remove('active'));
        e.target.classList.add('active');
        document.querySelectorAll('.question-card').forEach(card => {
            card.style.display = 'flex';
        });
    }
});




function optionTemplate(qId,i,text,score){
  return `
  <div class="option-item" style="display:flex;gap:12px;align-items:center;">
    <input type="text"
      name="questions[${qId}][options][${i}][text]"
      value="${text}"
      class="option-text-input"
      placeholder="Option text"
      style="flex:1;padding:8px 12px;border:1px solid #4880FF;border-radius:6px;">
    <input type="number"
      name="questions[${qId}][options][${i}][score]"
      value="${score}"
      class="option-score-input"
      placeholder="0"
      style="width:50px;padding:8px;border:1px solid #4880FF;border-radius:6px;">
    <button type="button"
                    class="delete-option-btn"
                    style="background: transparent;
            border: none;
            padding: 0;
            margin: 0;
            color: #000;
            font-size: 16px;
            cursor: pointer;">
                X
            </button>
  </div>`;
}


    // Add option function
    function addOption(questionId) {
  const wrapper = document.querySelector(`.answer-section[data-question-id="${questionId}"]`);
  if (!wrapper) return;

  const optionsContainer = wrapper.querySelector('.options-container');
  if (!optionsContainer) return;

  const index = optionsContainer.querySelectorAll('.option-item').length;

  const newOption = document.createElement('div');
  newOption.className = 'option-item';
  newOption.style.display = 'flex';
  newOption.style.gap = '12px';
  newOption.style.alignItems = 'center';

  newOption.innerHTML = `
    <div style="display:flex;align-items:center;gap:8px;flex:1;">
      <input 
        type="text"
        name="questions[${questionId}][options][${index}][text]"
        placeholder="Option"
        class="option-text-input"
        style="flex:1;padding:8px 12px;border:1px solid #4880FF;border-radius:6px;font-size:14px;"
      />
    </div>
    <input 
      type="number"
      name="questions[${questionId}][options][${index}][score]"
      placeholder="0"
      class="option-score-input"
      style="width:50px;padding:8px;border:1px solid #4880FF;border-radius:6px;font-size:14px;"
    />
    <button type="button"
      class="delete-option-btn"
      style="background:transparent;border:none;padding:0;margin:0;color:#000;font-size:16px;cursor:pointer;">
      X
    </button>
  `;

  optionsContainer.appendChild(newOption);
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
                                        <button type="button"
                    class="delete-option-btn"
                    style="background: transparent;
            border: none;
            padding: 0;
            margin: 0;
            color: #000;
            font-size: 16px;
            cursor: pointer;">
                X
            </button>
        
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

document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-option-btn')) {
            const item = e.target.closest('.option-item');
            if (item) {
                item.remove();
            }
        }
    });
</script>

<style>
    .filter-item.active,
    .dropdown-item.active {
        color: #4379EE !important; /* biru */
        font-weight: 600;
    }
</style>


@endsection