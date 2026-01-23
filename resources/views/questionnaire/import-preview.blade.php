@extends('layouts.app')

@section('content')
<div style="max-width: 1400px; margin: 0 auto; padding: 20px;">
    <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h2 style="font-weight: 600; color: #202224; margin: 0;">Preview Import Questionnaire</h2>
                <p style="color: #6C757D; margin-top: 8px;">
                    Total pertanyaan yang akan diimport: <strong>{{ $totalQuestions }}</strong>
                </p>
            </div>
            <div>
                <a href="{{ route('questionnaire.index') }}" class="btn btn-secondary" 
                   style="padding: 10px 24px; border-radius: 8px; font-weight: 500;">
                    <i class="fas fa-arrow-left" style="margin-right: 6px;"></i>Kembali
                </a>
            </div>
        </div>

        @if(count($errors) > 0)
        <div style="background: #FFF2F0; border: 1px solid #FFCCC7; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; margin-bottom: 12px;">
                <i class="fas fa-exclamation-triangle" style="color: #FF4D4F; margin-right: 8px;"></i>
                <strong style="color: #FF4D4F;">Terdapat {{ count($errors) }} error</strong>
            </div>
            <ul style="margin: 0; padding-left: 20px; color: #595959;">
                @foreach($errors as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($totalQuestions > 0)
        <form action="{{ route('questionnaire.import') }}" method="POST" id="importForm">
            @csrf
            
            <div style="height: 500px; overflow-y: auto; border: 1px solid #E8E8E8; border-radius: 8px; padding: 20px; background: #FAFAFA;">
                @foreach($importData as $index => $data)
                <div class="question-card" style="background: white; border: 1px solid #E0E0E0; border-radius: 8px; padding: 20px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <span style="background: #F0F7FF; color: #4880FF; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    Baris {{ $data['row_number'] }}
                                </span>
                                <span style="background: #F6FFED; color: #52C41A; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    Baru
                                </span>
                            </div>
                            <h4 style="font-weight: 600; color: #202224; margin: 0;">{{ $data['question_text'] }}</h4>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="questions[{{ $index }}][import]" checked 
                                   onchange="toggleQuestion({{ $index }}, this.checked)">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <!-- Kolom Kiri -->
                        <div>
                            <div style="margin-bottom: 16px;">
                                <label style="display: block; color: #595959; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Kategori</label>
                                <select name="questions[{{ $index }}][category_id]" 
                                        style="width: 100%; padding: 10px 12px; border: 1px solid #D9D9D9; border-radius: 6px; font-size: 14px;">
                                    @foreach(App\Models\Category::all() as $category)
                                    <option value="{{ $category->id }}" {{ $data['category_id'] == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <label style="display: block; color: #595959; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Sub Kategori</label>
                                <input type="text" name="questions[{{ $index }}][sub]" value="{{ $data['sub'] ?? '' }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #D9D9D9; border-radius: 6px; font-size: 14px;">
                            </div>

                            <div style="margin-bottom: 16px;">
                                <label style="display: block; color: #595959; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Clue/Contoh Jawaban</label>
                                <input type="text" name="questions[{{ $index }}][clue]" value="{{ $data['clue'] ?? '' }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #D9D9D9; border-radius: 6px; font-size: 14px;">
                            </div>
                        </div>

                        <!-- Kolom Kanan -->
                        <div>
                            <div style="margin-bottom: 16px;">
                                <label style="display: block; color: #595959; font-size: 12px; font-weight: 600; margin-bottom: 8px;">Indicator</label>
                                <div style="display: flex; gap: 20px;">
                                    <label style="display: flex; align-items: center; gap: 6px;">
                                        <input type="checkbox" name="questions[{{ $index }}][indicator][]" value="high"
                                               {{ in_array('high', $data['indicator']) ? 'checked' : '' }}>
                                        <span style="font-size: 14px; color: #202224;">High</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 6px;">
                                        <input type="checkbox" name="questions[{{ $index }}][indicator][]" value="medium"
                                               {{ in_array('medium', $data['indicator']) ? 'checked' : '' }}>
                                        <span style="font-size: 14px; color: #202224;">Medium</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 6px;">
                                        <input type="checkbox" name="questions[{{ $index }}][indicator][]" value="low"
                                               {{ in_array('low', $data['indicator']) ? 'checked' : '' }}>
                                        <span style="font-size: 14px; color: #202224;">Low</span>
                                    </label>
                                </div>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <label style="display: block; color: #595959; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Tipe Pertanyaan</label>
                                <select name="questions[{{ $index }}][question_type]" 
                                        class="question-type-select"
                                        style="width: 100%; padding: 10px 12px; border: 1px solid #D9D9D9; border-radius: 6px; font-size: 14px;">
                                    <option value="isian" {{ ($data['question_type'] ?? 'isian') == 'isian' ? 'selected' : '' }}>Text Answer</option>
                                    <option value="pilihan" {{ ($data['question_type'] ?? 'isian') == 'pilihan' ? 'selected' : '' }}>Multiple Choice</option>
                                </select>
                            </div>

                            <!-- Field hidden untuk data lainnya -->
                            <input type="hidden" name="questions[{{ $index }}][question_text]" value="{{ $data['question_text'] }}">
                            <input type="hidden" name="questions[{{ $index }}][is_new]" value="1">
                            <input type="hidden" name="questions[{{ $index }}][no]" value="{{ $data['no'] }}">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 24px; border-top: 1px solid #F0F0F0;">
                <div>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="selectAll" checked onchange="toggleAllQuestions(this.checked)">
                        <span style="font-size: 14px; color: #202224; font-weight: 500;">Pilih Semua</span>
                    </label>
                </div>
                <div style="display: flex; gap: 12px;">
                    <a href="{{ route('questionnaire.index') }}" class="btn btn-secondary"
                       style="padding: 10px 24px; border-radius: 8px; font-weight: 500;">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary"
                            style="padding: 10px 24px; border-radius: 8px; font-weight: 500; background: #4880FF; border: none; color: white;">
                        <i class="fas fa-upload" style="margin-right: 6px;"></i>Konfirmasi Import
                    </button>
                </div>
            </div>
        </form>
        @else
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-file-excel" style="font-size: 48px; color: #D9D9D9; margin-bottom: 20px;"></i>
            <h4 style="color: #595959; margin-bottom: 12px;">Tidak ada data untuk diimport</h4>
            <p style="color: #8C8C8C; margin-bottom: 24px;">File Excel tidak berisi pertanyaan baru yang valid.</p>
            <a href="{{ route('questionnaire.index') }}" class="btn btn-primary"
               style="padding: 10px 24px; border-radius: 8px; font-weight: 500;">
                Kembali ke Daftar Pertanyaan
            </a>
        </div>
        @endif
    </div>
</div>

<style>
.switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
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
    transform: translateX(20px);
}

.question-card.disabled {
    opacity: 0.6;
    background: #FAFAFA;
}

.question-card.disabled input,
.question-card.disabled select {
    pointer-events: none;
    background: #F5F5F5;
}
</style>

<script>
function toggleQuestion(index, isChecked) {
    const card = document.querySelectorAll('.question-card')[index];
    if (isChecked) {
        card.classList.remove('disabled');
    } else {
        card.classList.add('disabled');
    }
}

function toggleAllQuestions(isChecked) {
    const checkboxes = document.querySelectorAll('input[name^="questions"][name$="[import]"]');
    const cards = document.querySelectorAll('.question-card');
    
    checkboxes.forEach((checkbox, index) => {
        checkbox.checked = isChecked;
        if (isChecked) {
            cards[index].classList.remove('disabled');
        } else {
            cards[index].classList.add('disabled');
        }
    });
}

// Submit form
document.getElementById('importForm').addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('input[name^="questions"][name$="[import]"]:checked');
    if (checkboxes.length === 0) {
        e.preventDefault();
        alert('Pilih setidaknya satu pertanyaan untuk diimport');
        return false;
    }
});
</script>
@endsection