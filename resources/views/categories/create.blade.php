@extends('layouts.app')

@section('content')
<x-header title="Questionnaire">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<div style="display:flex; flex-wrap:wrap; gap:12px; margin:10px 40px">
    @foreach($categories as $category)
        <div class="category-chip">
            <span class="category-name">{{ $category->name }}</span>

            <button
                type="button"
                class="delete-category-btn"
                data-id="{{ $category->id }}"
                data-name="{{ $category->name }}"
                title="Delete category"
            >
                ×
            </button>
        </div>
    @endforeach
</div>

<form id="delete-category-form" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>


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

       

        {{-- New Questions Container --}}
        <div id="questions-container" style="display:flex; flex-direction:column; gap:16px; margin-top:16px;"></div>

        {{-- Add Question Button di sini --}}
        <div style="display: flex; justify-content: flex-end; margin: 20px 0 40px 0;">
            <button type="button" id="add-question-btn" 
                style="padding: 10px 20px; background: #4880FF; color: white; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                <i class="fas fa-plus" style="margin-right: 6px;"></i> Add Question
            </button>
        </div>

        {{-- Tombol Cancel dan Save --}}
        <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:20px;">
            <a href="{{ route('questionnaire.editAll') }}"
                style="padding:12px 24px; background:white; border:1px solid #4880FF; color:#4880FF; border-radius:8px;">Cancel</a>
            <button type="submit" id="save-btn"
                style="padding:12px 24px; background:#4379EE; color:white; border:none; border-radius:8px;">Save</button>
        </div>
    </form>
</div>
<form id="delete-category-form" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>


<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - Initializing...');
    
    const newQuestionId = 'new_' + Date.now();
    const categoryInput = document.getElementById('category-input');
    const answerState = {};
    const form = document.getElementById('category-form');
    const saveBtn = document.getElementById('save-btn');
    const addQuestionBtn = document.getElementById('add-question-btn');
    const questionsContainer = document.getElementById('questions-container');
    
    console.log('Elements found:', {
        categoryInput: !!categoryInput,
        addQuestionBtn: !!addQuestionBtn,
        questionsContainer: !!questionsContainer
    });
    
    // ========== FUNGSI UNTUK UPDATE SEMUA CATEGORY DI QUESTION CARDS ==========
    function updateAllQuestionCategories(newCategoryValue) {
        console.log('Updating all question categories to:', newCategoryValue);
        
        // Update semua input category di question cards
        document.querySelectorAll('.question-category').forEach(categoryInput => {
            categoryInput.value = newCategoryValue;
        });
        
        // Update juga semua hidden fields untuk use_main_category
        document.querySelectorAll('input[name$="[use_main_category]"]').forEach(hiddenInput => {
            hiddenInput.value = newCategoryValue ? '1' : '0';
        });
    }
    
    // ========== EVENT LISTENER UNTUK CATEGORY INPUT ==========
    if (categoryInput) {
        categoryInput.addEventListener('input', function() {
            const newValue = this.value.trim();
            updateAllQuestionCategories(newValue);
        });
        
        // Juga trigger saat ada perubahan programatik
        categoryInput.addEventListener('change', function() {
            const newValue = this.value.trim();
            updateAllQuestionCategories(newValue);
        });
    }
    
    // ========== TOMBOL ADD QUESTION ==========
    if (addQuestionBtn) {
        console.log('Add Question button found, attaching event listener...');
        
        addQuestionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Add Question button clicked!');
            
            const categoryValue = categoryInput ? categoryInput.value.trim() : '';
            if (!categoryValue) {
                alert('Please fill Category first before adding a question');
                categoryInput.focus();
                return;
            }
            
            const questionNumber =
    document.querySelectorAll('.question-card').length + 1;


            const newQuestionId = 'new_' + Date.now();
            console.log('Creating new question with ID:', newQuestionId);
            
            // Pastikan container ada
            if (!questionsContainer) {
                console.error('Questions container not found!');
                return;
            }
            
            const newCard = document.createElement('div');
            newCard.className = 'question-card';
            newCard.dataset.questionId = newQuestionId;
            newCard.style.marginBottom = '16px';
            newCard.style.border = '1px solid #E0E0E0';
            newCard.style.borderRadius = '12px';
            newCard.style.background = '#FFFFFF';
            newCard.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)';
            newCard.style.cursor = 'pointer';
            newCard.style.transition = 'all 0.3s ease';
            
            newCard.innerHTML = `
               <div class="question-header" style="padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                        <div  class="question-number"
                        style="width: 32px; height: 32px; border-radius: 50%; background: #F0F7FF; 
                                          display: flex; align-items: center; justify-content: center; font-size: 14px; color: #4880FF; font-weight: bold;">
                               ${questionNumber}
                        </div>
                        <div >
                            <div style="font-weight: 600; color: #202224; font-size: 16px;">
                                New Question
                            </div>
                            <div style="font-size: 12px; color: #6C757D; margin-top: 4px;">
                                <span>Multiple Choice</span>
                                        • 
                                <span>${categoryValue}</span>
                            </div>
                        </div>
                    </div>
                    <div class="expand-icon" style="transition: transform 0.3s;">
                                <i class="fas fa-chevron-down" style="color: #6C757D; font-size: 16px;"></i>
                    </div>
                </div>
                
                <div class="question-body" style="display: none; padding: 0 20px 20px 20px; border-top: 1px solid #F0F0F0;">
                    <div style="display: flex; gap: 30px; position: relative;">
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 16px;">
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
                        
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 16px;">
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="color: #202224; font-size: 12px; font-weight: 600;">Type</label>
                                <select 
                                    class="question-type-select" 
                                    data-question-id="${newQuestionId}" 
                                    data-prev-type="pilihan"
                                    name="questions[${newQuestionId}][question_type]"
                                    style="padding: 10px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; color: #202224; background: white; cursor: pointer;">
                                    <option value="pilihan" selected>Multiple Choice</option>
                                    <option value="isian">Text Answer</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="color: #202224; font-size: 12px; font-weight: 600;">Category</label>
                                <input type="hidden" name="questions[${newQuestionId}][use_main_category]" value="1">
                                <input type="text"
                                      class="question-category category-select"
                                      readonly
                                      value="${categoryValue}"
                                      style="padding: 10px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px; color: #202224; background: white; cursor: pointer;">
                            </div>
                            
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
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="color: #202224; font-size: 12px; font-weight: 600;">Attachment</label>
                                <input 
                                    type="text"
                                    name="questions[${newQuestionId}][attachment_text]"
                                    placeholder="Please attach supporting document"
                                    style="padding: 12px; border: 1px solid #4880FF; border-radius: 8px; font-size: 14px;"
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
            
            questionsContainer.appendChild(newCard);
            renumberQuestions();
            console.log('New question card added to container');
            
            // Initialize state
            answerState[newQuestionId] = { pilihan: [], isian: '' };
            
            // Add initial option
            addOption(newQuestionId, '', '', 0);
            
            // Setup event listeners for new card
            setupQuestionCardEvents(newCard);
            
            // Trigger preview update
            updateQuestionPreview(newCard);
            
        });
    } else {
        console.error('Add Question button not found! Check if element with id="add-question-btn" exists.');
    }
    
    // ========== FUNGSI UTILITAS ==========
    function setupQuestionCardEvents(card) {
        const textarea = card.querySelector('.question-textarea');
        const typeSelect = card.querySelector('.question-type-select');
        
        // Auto-resize textarea
        if (textarea) {
            autoResizeTextarea(textarea);
            textarea.addEventListener('input', function() {
                autoResizeTextarea(this);
                updateQuestionPreview(card);
            });
        }
        
        // Type select change
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                updateQuestionPreview(card);
            });
        }
    }
    
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    function renumberQuestions() {
    document.querySelectorAll('.question-card').forEach((card, index) => {
        const numberCircle = card.querySelector('.question-number');
        if (numberCircle) {
            numberCircle.textContent = index + 1;
        }
    });
}

    
    function updateQuestionPreview(card) {
        const textarea = card.querySelector('.question-textarea');
        const typeSelect = card.querySelector('.question-type-select');
        const previewText = card.querySelector('.question-preview');
        const typePreview = card.querySelector('.question-type-preview');
        
        if (textarea && previewText) {
            const questionText = textarea.value.trim();
            previewText.textContent = questionText || 'New Question';
        }
        
        if (typeSelect && typePreview) {
            const questionType = typeSelect.value === 'pilihan' ? 'Multiple Choice' : 'Text Answer';
            typePreview.textContent = questionType;
        }
    }
    
    function addOption(questionId, text = '', score = '', index = null) {
        const wrapper = document.querySelector(`.answer-section[data-question-id="${questionId}"]`);
        if (!wrapper) {
            console.error('Answer section not found for question:', questionId);
            return;
        }
        
        const optionsContainer = wrapper.querySelector('.options-container');
        if (!optionsContainer) {
            console.error('Options container not found');
            return;
        }
        
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
                    X
                </button>
            </div>
        `;
        
        optionsContainer.appendChild(div);
        console.log('Option added for question:', questionId);
    }
    
    // ========== EVENT DELEGATION ==========
    document.addEventListener('click', function(e) {
        // Toggle expand/collapse
        if (e.target.closest('.question-header') || e.target.closest('.expand-icon')) {
            const card = e.target.closest('.question-card');
            const body = card.querySelector('.question-body');
            const expandIcon = card.querySelector('.expand-icon i');
            
            if (card.classList.contains('collapsed')) {
                card.classList.remove('collapsed');
                if (body) body.style.display = 'block';
                if (expandIcon) expandIcon.style.transform = 'rotate(180deg)';
                card.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            } else {
                card.classList.add('collapsed');
                if (body) body.style.display = 'none';
                if (expandIcon) expandIcon.style.transform = 'rotate(0deg)';
                card.style.boxShadow = 'none';
            }
        }
        
        // Delete option
        if (e.target.closest('.delete-option-btn')) {
            const item = e.target.closest('.option-item');
            if (item) {
                item.remove();
            }
        }
        
        // Add option
        if (e.target.closest('.add-option-btn')) {
            const button = e.target.closest('.add-option-btn');
            const questionId = button.dataset.questionId;
            addOption(questionId);
        }
        
        // Delete question
        if (e.target.closest('.delete-question-btn')) {
    const button = e.target.closest('.delete-question-btn');
    const questionCard = button.closest('.question-card');

    if (questionCard) {
        const qid = questionCard.dataset.questionId;

        if (confirm('Are you sure you want to delete this question?')) {
            delete answerState[qid]; // 🔥 bersihkan state
            questionCard.remove();
            renumberQuestions();
        }
    }
}


    });
    
    // Question type change delegation
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('question-type-select')) {
            const select = e.target;
            const questionId = select.dataset.questionId;
            const answerSection = document.querySelector(`.answer-section[data-question-id="${questionId}"]`);
            
            if (!answerSection) return;
            
            // Save current state
            if (!answerState[questionId]) {
                answerState[questionId] = { pilihan: [], isian: '' };
            }
            
            const prevType = select.dataset.prevType;
            
            if (prevType === 'pilihan') {
    answerState[questionId].pilihan = Array.from(
        answerSection.querySelectorAll('.option-item')
    ).map(item => ({
        text: item.querySelector('.option-text-input')?.value || '',
        score: item.querySelector('.option-score-input')?.value || ''
    }));
}else {
                const clueInput = answerSection.querySelector('input[name$="[clue]"]');
                if (clueInput) {
                    answerState[questionId].isian = clueInput.value;
                }
            }
            
            // Render new UI
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
                               style="padding:8px 12px; border:1px solid #4880FF; border-radius:8px;">
                    </div>`;
            }
            
            select.dataset.prevType = select.value;
        }
        
        // Checkbox indicator
        if (e.target.matches('input[type="checkbox"][name$="[indicator][]"]')) {
            const checkbox = e.target;
            const questionCard = checkbox.closest('.question-card');
            const allCheckboxes = questionCard.querySelectorAll('input[type="checkbox"][name$="[indicator][]"]');
            const map = {
                high: ['high'],
                medium: ['medium'],
                low: ['low']
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
        }
    });
    
    // Initialize existing textareas
    document.querySelectorAll('.question-textarea').forEach(textarea => {
        autoResizeTextarea(textarea);
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
            const card = this.closest('.question-card');
            if (card) updateQuestionPreview(card);
        });
    });
    
    // ========== INISIALISASI AWAL ==========
    // Set category awal untuk semua question cards yang sudah ada
    if (categoryInput && categoryInput.value.trim()) {
        updateAllQuestionCategories(categoryInput.value.trim());
    }
    
    console.log('Initialization complete');
});


document.addEventListener('click', function(e) {
    const btn = e.target.closest('.delete-category-btn');
    if (!btn) return;

    const categoryId = btn.dataset.id;
    const categoryName = btn.dataset.name;

    const confirmDelete = confirm(
        `PERINGATAN!\n\n` +
        `Jika category "${categoryName}" dihapus,\n` +
        `SEMUA pertanyaan di dalamnya juga akan TERHAPUS.\n\n` +
        `Yakin ingin melanjutkan?`
    );

    if (confirmDelete) {
        const form = document.getElementById('delete-category-form');
        form.action = `/categories/${categoryId}`;
        form.submit();
    }
});


</script>

<style>
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

    .expand-icon i {
        transition: transform 0.3s ease;
    }

</style>
@endsection