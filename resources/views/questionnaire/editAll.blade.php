@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Questionnaire">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>



<form method="POST" action="{{ route('questionnaire.updateAll') }}" id="questionnaire-form">
    @csrf
    @method('PUT')
    <input type="hidden" name="deleted_questions" id="deleted-questions" value="">

    

    <div class="filter-edit-wrapper">
    {{-- FILTER --}}
    <div class="filter-box">

        @php
    $mainCategories = $categories->take(3);
    $moreCategories = $categories->slice(3);
@endphp

<span class="filter-item active" data-category-id="">All</span>

@foreach ($mainCategories as $category)
    <span
        class="filter-item"
        data-category-id="{{ $category->id }}">
        {{ $category->name }}
    </span>
@endforeach


        @if ($moreCategories->count())
<div class="dropdown" style="padding:5px;">
    <span
        class="dropdown-toggle filter-item"
        data-bs-toggle="dropdown"
        id="moreFilter"
        style="align-items: stretch; height: 100%;"
    >
        <span id="moreFilterText">More</span>
    </span>

    <div class="dropdown-menu">
        @foreach ($moreCategories as $category)
            <a
                href="#"
                class="dropdown-item filter-item"
                data-category-id="{{ $category->id }}">
                {{ $category->name }}
            </a>
        @endforeach
    </div>
</div>
@endif
    </div>

    {{-- INDICATOR FILTER --}}
<div class="dropdown"  style="display:flex; padding:10px;background:#FFFFFF; border-radius: 10px;
    border: 1px solid transparent; align-items:center;">
    <span
        class="dropdown-toggle filter-item"
        data-bs-toggle="dropdown"
        id="indicatorFilter"
    >
        <span id="indicatorFilterText">Indicator</span>
    </span>

    <div class="dropdown-menu">
        <a href="#" class="dropdown-item indicator-item" data-indicator="">
            All Indicator
        </a>
        <a href="#" class="dropdown-item indicator-item" data-indicator="high">
            High
        </a>
        <a href="#" class="dropdown-item indicator-item" data-indicator="medium">
            Medium
        </a>
        <a href="#" class="dropdown-item indicator-item" data-indicator="low">
            Low
        </a>
    </div>
</div>


    {{-- ADD --}}
    <div class="dropdown add-wrapper">
        <button type="button"
                class="btn btn-primary"
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
        <div id="questions-container" style="height: calc(100vh - 260px); overflow-y: auto; padding: 10px;">
@php
    $questionNumber = 1;
@endphp

            {{-- QUESTION CARDS --}}
            @foreach($categories as $category)
                @foreach($category->questions as $question)
                @php
    $indicatorArray = is_array($question->indicator)
        ? $question->indicator
        : json_decode($question->indicator ?? '[]', true);
@endphp

                    <div class="question-card existing-card" 
                         data-question-id="{{ $question->id }}" 
                         data-category="{{ $question->category_id }}" 
                         data-indicator="{{ implode(',', $indicatorArray) }}"
                         style="margin-bottom: 16px; border: 1px solid #E0E0E0; border-radius: 12px; background: #FFFFFF; 
                                box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: all 0.3s ease; cursor: pointer;">
                        
                        {{-- Header yang bisa diklik --}}
                        <div class="question-header" style="padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div class="question-number"
                                    style="width: 32px; height: 32px; border-radius: 50%; background: #F0F7FF; 
                                          display: flex; align-items: center; justify-content: center; font-size: 14px; color: #4880FF; font-weight: bold;">
                                    {{ $questionNumber }}
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
                        <div class="question-body" style="padding: 0 20px 20px 20px; border-top: 1px solid #F0F0F0;">
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
                                                   padding: 12px; width: 100%; resize: vertical; min-height: 36px; transition: border 0.2s;"
                                            rows="2"
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
        data-question-id="{{ $question->id }}"
        data-category-name="{{ $category->name }}"
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
                                                        $indicators = $indicatorArray;
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
            @endforeach
        </div>

        {{-- Buttons --}}
        <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 30px; margin-right: 20px; gap: 12px;">
            <a  href="{{ route('questionnaire.index') }}"
                type="button"
                id="cancelBtn"
                style="padding: 12px 28px; background: white; color: #4880FF; border: 1px solid #4880FF; 
                       border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;"
            >
                Cancel
        </a>
            
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
function scrollToQuestion(card) {
    card.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}
function getNextQuestionNumber() {
    const nums = document.querySelectorAll('.question-number');
    return nums.length + 1;
}

document.getElementById('add-question').addEventListener('click', function (e) {
    e.preventDefault();
    addQuestionByClone();
});

function addQuestionByClone() {
    const container = document.getElementById('questions-container');
    const lastCard = container.querySelector('.question-card:last-child');
    if (!lastCard) return;

    const newQuestionId = 'new_' + Date.now();
    const newCard = lastCard.cloneNode(true);

    // dataset
    newCard.dataset.questionId = newQuestionId;

    // nomor baru
    const numberEl = newCard.querySelector('.question-number');
    if (numberEl) {
        numberEl.textContent = container.querySelectorAll('.question-card').length + 1;
    }

    // reset title
    const title = newCard.querySelector('.question-header div div div');
    if (title) title.textContent = 'Untitled Question';

    // reset inputs
    newCard.querySelectorAll('textarea').forEach(el => el.value = '');
    newCard.querySelectorAll('input').forEach(el => {
        if (['checkbox','radio'].includes(el.type)) el.checked = false;
        else el.value = '';
    });

    // clear options
    const options = newCard.querySelector('.options-container');
    if (options) options.innerHTML = '';

    // update name attributes
    newCard.querySelectorAll('[name]').forEach(el => {
        el.name = el.name.replace(/\[.*?\]/, `[${newQuestionId}]`);
    });

    // reset state
    newCard.classList.remove('active');
    const body = newCard.querySelector('.question-body');
    if (body) body.style.display = '';

    // append
    container.appendChild(newCard);

    // UX MAGIC ✨
    newCard.classList.add('active');   // buka
    scrollToQuestion(newCard);          // scroll ke dia
}

function openCard(card) {
    card.classList.add('active');
}


document.addEventListener('click', function (e) {
    const header = e.target.closest('.question-header');
    if (!header) return;

    const card = header.closest('.question-card');

    // toggle active
    card.classList.toggle('active');

    // rotate icon
    const icon = card.querySelector('.expand-icon i');
    if (icon) {
        icon.style.transform = card.classList.contains('active')
            ? 'rotate(180deg)'
            : 'rotate(0deg)';
    }
});

</script>

<script>
let activeCategory = null;
let activeIndicator = null;

function applyFilter() {
    document.querySelectorAll('.question-card').forEach(card => {
        const cardCategory = card.dataset.category;
        const cardIndicators = (card.dataset.indicator || '').split(',');

        const matchCategory = !activeCategory || cardCategory === activeCategory;
        const matchIndicator = !activeIndicator || cardIndicators.includes(activeIndicator);

        card.style.display = (matchCategory && matchIndicator) ? '' : 'none';
    });
}

/* CATEGORY FILTER */
document.querySelectorAll('.filter-item[data-category-id]').forEach(item => {
    item.addEventListener('click', function (e) {
        e.preventDefault();

        activeCategory = this.dataset.categoryId || null;

        document.querySelectorAll('.filter-item').forEach(f => f.classList.remove('active'));
        this.classList.add('active');

        // reset More kalau klik main / all
        const moreText = document.getElementById('moreFilterText');
        if (moreText && !this.closest('.dropdown-menu')) {
            moreText.textContent = 'More';
        }

        applyFilter();
    });
});

/* INDICATOR FILTER */
document.querySelectorAll('.indicator-item').forEach(item => {
    item.addEventListener('click', function (e) {
        e.preventDefault();

        activeIndicator = this.dataset.indicator || null;

        document.querySelectorAll('.indicator-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');

        const label = this.textContent.trim();
        document.getElementById('indicatorFilterText').textContent =
            label === 'All Indicator' ? 'Indicator' : label;

        applyFilter();
    });
});
</script>

<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.add-option-btn')
    if (!btn) return

    const questionId = btn.dataset.questionId
    const card = btn.closest('.question-card')
    const container = card.querySelector('.options-container')

    const index = container.children.length

    const option = document.createElement('div')
    option.className = 'option-item'
    option.style = 'display:flex;gap:12px;align-items:center;'

    option.innerHTML = `
        <input type="text"
               name="questions[${questionId}][options][${index}][text]"
               placeholder="Option text"
               style="flex:1;padding:10px;border:1px solid #4880FF;border-radius:6px;">
        <input type="number"
               name="questions[${questionId}][options][${index}][score]"
               placeholder="Score"
               style="width:80px;padding:10px;border:1px solid #4880FF;border-radius:6px;">
        <button type="button" class="delete-option-btn"
                style="background:none;border:none;color:#FF4D4F;font-size:18px;">
            ×
        </button>
    `

    container.appendChild(option)

    // 🔥 UPDATE CACHE
    updateOptionCache(card)
})
</script>

<script>
document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('question-type-select')) return

    const select = e.target
    const type = select.value
    const questionId = select.dataset.questionId
    const card = select.closest('.question-card')
    const answerSection = card.querySelector('.answer-section')

    if (!answerSection) return

    // init cache
    if (!card._optionCache) card._optionCache = null
    if (!card._clueCache) card._clueCache = ''

    /* ======================
       SWITCH TO TEXT
    ====================== */
    if (type === 'isian') {

        const container = answerSection.querySelector('.options-container')
        if (container) {
            card._optionCache = container.cloneNode(true)
        }

        answerSection.innerHTML = ''

        const wrap = document.createElement('div')

        wrap.innerHTML = `
            <label style="font-weight:600;margin-bottom:8px;display:block">
                Answer Clue
            </label>
        `

        const input = document.createElement('input')
        input.type = 'text'
        input.name = `questions[${questionId}][clue]`
        input.value = card._clueCache
        input.style = 'padding:12px;border:1px solid #4880FF;border-radius:8px;width:100%;'

        wrap.appendChild(input)
        answerSection.appendChild(wrap)

        return
    }

    /* ======================
       SWITCH TO PILIHAN
    ====================== */
    if (type === 'pilihan') {

        answerSection.innerHTML = ''

        const wrap = document.createElement('div')

        const label = document.createElement('label')
        label.textContent = 'Options'
        label.style = 'font-weight:600;margin-bottom:12px;display:block'
        wrap.appendChild(label)

        const container = card._optionCache
            ? card._optionCache.cloneNode(true)
            : document.createElement('div')

        container.classList.add('options-container')
        container.style.display = 'flex'
        container.style.flexDirection = 'column'
        container.style.gap = '12px'

        wrap.appendChild(container)

        const btn = document.createElement('button')
        btn.type = 'button'
        btn.className = 'add-option-btn'
        btn.dataset.questionId = questionId
        btn.textContent = '+ Add Option'
        btn.style = 'margin-top:12px;padding:10px 20px;background:#4880FF;color:white;border:none;border-radius:6px;'

        wrap.appendChild(btn)

        answerSection.appendChild(wrap)
    }
})


/* =========================
   SAVE CLUE WHILE TYPING
========================= */
document.addEventListener('input', function (e) {
    if (e.target.name && e.target.name.includes('[clue]')) {
        const card = e.target.closest('.question-card')
        if (card) {
            card._clueCache = e.target.value
        }
    }
})

function updateOptionCache(card) {
    const container = card.querySelector('.options-container')
    if (!container) return

    // CLONE DOM (INI KUNCI)
    card._optionCache = container.cloneNode(true)
}


document.addEventListener('input', function (e) {
    if (
        e.target.name?.includes('[options]') ||
        e.target.closest('.option-item')
    ) {
        const card = e.target.closest('.question-card')
        if (card) updateOptionCache(card)
    }
})

document.querySelectorAll('.question-card').forEach(card => {
    const optionsContainer = card.querySelector('.options-container')
    if (optionsContainer) {
        card._optionCache = optionsContainer.innerHTML
    } else {
        card._optionCache = ''
    }

    const clueInput = card.querySelector('input[name*="[clue]"]')
    card._clueCache = clueInput ? clueInput.value : ''
})
</script>

<script>
let deletedQuestions = []

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.delete-question-btn')
    if (!btn) return

    e.preventDefault()
    e.stopPropagation()

    const card = btn.closest('.question-card')
    if (!card) return

    const questionId = card.dataset.questionId

    const confirmDelete = confirm(
        `PERINGATAN!\n\n` +
        `Pertanyaan ini akan DIHAPUS setelah Save.\n` +
        `Jika Cancel, perubahan akan dibatalkan.\n\n` +
        `Yakin ingin melanjutkan?`
    )

    if (!confirmDelete) return

    // 👉 hanya simpan kalau EXISTING question (bukan new_xxx)
    if (!questionId.startsWith('new_')) {
        deletedQuestions.push(questionId)
        document.getElementById('deleted-questions').value =
            deletedQuestions.join(',')
    }

    // ❌ HAPUS DARI UI SAJA
    card.remove()

    // update nomor
    document.querySelectorAll('.question-number').forEach((el, i) => {
        el.textContent = i + 1
    })
})
</script>



<style>
.question-card.active {
    border-color: #4880FF;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.question-card .question-body {
    display: none;
}

.question-card.active .question-body {
    display: block;
}
</style>


@endsection