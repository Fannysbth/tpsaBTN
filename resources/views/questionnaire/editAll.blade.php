@extends('layouts.app')

@section('content')

{{-- HEADER COMPONENT --}}
<x-header title="Questionnaire">
    <i class="fa-solid fa-building-columns icon-header"></i>
    <i class="fa-solid fa-shield-halved icon-header"></i>
</x-header>

<form method="POST" action="{{ route('questionnaire.updateAll') }}">
    @csrf
    @method('PUT')

    <div style="max-width: 1202px; box-sizing: border-box; background: #F5F6FA; padding-top: 1px; margin-top:10px;margin-left: 10px;">

        {{-- Questions --}}
        @foreach($categories as $category)
            @foreach($category->questions as $question)
            <div class="question-card" style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px; padding:15px; border:1px solid #ccc; border-radius:8px; background:#fff;">

                <div style="display:flex; gap:20px;">
                    <!-- Left Group: Question Text + Options -->
                    <div style="flex:1; display:flex; flex-direction:column; gap:10px;">

                        <!-- Question Text -->
                        <textarea
                            name="questions[{{ $question->id }}][question_text]"
                            placeholder="Question text"
                            style="
                                color: #202224;
                                font-size: 14px;
                                font-weight: bold;
                                background: #F7F7F7;
                                border: none;
                                border-bottom: 2px solid #000000;
                                border-radius: 2px 2px 0 0;
                                padding: 12px;
                                width: 100%;
                                resize: vertical;
                                min-height: 40px;
                            "
                        >{{ $question->question_text }}</textarea>

                        <!-- Options / Answer Text -->
                        <div class="options-container" style="display: {{ in_array($question->question_type, ['pilihan','checkbox']) ? 'flex' : 'none' }}; flex-direction:column; gap:6px;">
                            @if(in_array($question->question_type, ['pilihan','checkbox']))
                                @foreach($question->options as $i => $option)
                                <div class="option-item" style="display:flex; gap:10px; align-items:center;">
                                    <input type="text"
                                        name="questions[{{ $question->id }}][options][{{ $i }}][text]"
                                        value="{{ $option->option_text }}"
                                        placeholder="Option text"
                                        style="flex:1; padding:5px; border:1px solid #4880FF; border-radius:6px;"
                                    />
                                    <input type="number"
                                        name="questions[{{ $question->id }}][options][{{ $i }}][score]"
                                        value="{{ $option->score }}"
                                        placeholder="Score"
                                        style="width:70px; padding:5px; border:1px solid #4880FF; border-radius:6px;"
                                    />
                                </div>
                                @endforeach
                                <!-- Empty input untuk tambah option baru -->
                                <div class="option-item" style="display:flex; gap:10px; align-items:center;">
                                    <input type="text" placeholder="Add option" class="new-option-input" style="flex:1; padding:5px; border:1px solid #4880FF; border-radius:6px;">
                                    <input type="number" placeholder="Score" class="new-option-score" style="width:70px; padding:5px; border:1px solid #4880FF; border-radius:6px;">
                                </div>
                            @elseif($question->question_type == 'isian')
                                <input type="text"
                                    class="answer-input"
                                    name="questions[{{ $question->id }}][answer_text]"
                                    placeholder="{{ $question->clue ?? 'optional clue' }}"
                                    style="padding:10px; border-radius:4px; border:1px solid #4880FF; width:100%;"
                                />
                            @endif
                        </div>

                    </div>

                    <!-- Right Group: Type, Category, Indicator, Attachment -->
                    <div style="flex:1; display:flex; flex-direction:column; gap:10px;">
                        <!-- Question Type -->
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <span>Type</span>
                            <select class="question-type" data-question-id="{{ $question->id }}" name="questions[{{ $question->id }}][question_type]" style="border:1px solid #4880FF; border-radius:6px; padding:5px;">
                                <option value="pilihan" {{ $question->question_type=='pilihan'?'selected':'' }}>Multiple Choice</option>
                                <option value="checkbox" {{ $question->question_type=='checkbox'?'selected':'' }}>Checkbox</option>
                                <option value="isian" {{ $question->question_type=='isian'?'selected':'' }}>Answer Text</option>
                            </select>
                        </div>

                        <!-- Category -->
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <span>Category</span>
                            <select name="questions[{{ $question->id }}][category_id]" style="border:1px solid #4880FF; border-radius:6px; padding:5px;">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $question->category_id==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Indicator -->
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <span>Indicator</span>
                            <div style="display:flex; gap:10px;">
                                @foreach(['high','medium','low'] as $ind)
                                    <label style="display:flex; align-items:center; gap:3px;">
                                        <input 
                                            type="checkbox" 
                                            name="questions[{{ $question->id }}][indicator][]" 
                                            value="{{ $ind }}" 
                                            {{ in_array($ind, explode(',', $question->indicator ?? '')) ? 'checked' : '' }}
                                        >
                                        <span>{{ ucfirst($ind) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Attachment -->
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <span>Attachment</span>
                            <input type="text" name="questions[{{ $question->id }}][attachment]" placeholder="Mohon lampirkan dokumen pendukung" style="padding:10px; border-radius:6px; border:1px solid #4880FF;">
                        </div>
                    </div>
                </div>

                <!-- Delete -->
                <div style="align-self:flex-end; cursor:pointer; color:red;" onclick="alert('Hapus pertanyaan')">Hapus</div>

            </div>
            @endforeach
        @endforeach

        {{-- Buttons --}}
        <div style="align-self: stretch; display: flex; justify-content: flex-end; align-items: center; margin-bottom: 63px; margin-top:20px;">
            <button type="button" style="flex-shrink:0; display:flex; align-items:center; background:none; border-radius:6px; border:1px solid #4880FF; padding:11px 16px; gap:22px; margin-right:10px;">Cancel</button>
            <button type="submit" style="flex-shrink:0; display:flex; align-items:flex-start; background:#4379EE; border-radius:6px; border:none; padding:12px 19px; gap:22px; color:#fff;">Save</button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // Function untuk handle dynamic option
    function setupNewOption(input) {
        input.addEventListener('keydown', function(e){
            if(e.key === 'Enter' && this.value.trim() !== ''){
                e.preventDefault();
                const container = this.closest('.options-container');
                const newItem = this.closest('.option-item').cloneNode(true);

                // Kosongkan input baru
                newItem.querySelector('input[type=text]').value = '';
                newItem.querySelector('input[type=number]').value = '';
                
                // Tambahkan di container
                container.appendChild(newItem);

                // Fokus ke input baru
                newItem.querySelector('input[type=text]').focus();

                // Setup listener lagi untuk input yang baru
                setupNewOption(newItem.querySelector('input[type=text]'));
            }
        });
    }

    document.querySelectorAll('.new-option-input').forEach(input => {
        setupNewOption(input);
    });

    // Change question type dynamically
    document.querySelectorAll('.question-type').forEach(select => {
        select.addEventListener('change', function(){
            const card = this.closest('.question-card');
            const optionsContainer = card.querySelector('.options-container');
            const answerInput = card.querySelector('.answer-input');

            if(this.value === 'pilihan' || this.value === 'checkbox') {
                // Tampilkan options container
                if(optionsContainer) optionsContainer.style.display = 'flex';
                if(answerInput) answerInput.style.display = 'none';
            } else if(this.value === 'isian') {
                // Sembunyikan options, tampilkan answer input
                if(optionsContainer) optionsContainer.style.display = 'none';
                if(answerInput) answerInput.style.display = 'block';
            }
        });
    });

});
</script>

@endsection
