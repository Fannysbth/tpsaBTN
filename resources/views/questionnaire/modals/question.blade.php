<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;">

            <div class="modal-header" style="background:#FCFCFC;">
                <h5 class="modal-title fw-bold">Tambah Pertanyaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('questionnaire.categories.store') }}" method="POST">
                @csrf

                <div class="modal-body">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kategori</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Pilih Kategori</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Urutan</label>
                            <input type="number" 
                                   name="order"
                                   class="form-control"
                                   placeholder="1"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Teks Pertanyaan</label>
                        <textarea name="question_text"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Masukkan pertanyaan"
                                  required></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipe Jawaban</label>
                            <select name="question_type" class="form-select" required>
                                <option value="text">Text</option>
                                <option value="pilihan">Pilihan</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Indicator</label>
                            <select name="indicator" class="form-select">
                                <option value="">-</option>
                                <option value="low">LOW</option>
                                <option value="medium">MEDIUM</option>
                                <option value="high">HIGH</option>
                            </select>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="has_attachment"
                                       value="1"
                                       id="hasAttachment">
                                <label class="form-check-label fw-bold" for="hasAttachment">
                                    Butuh Attachment
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- OPSI PILIHAN (DISIAPKAN) --}}
                    <div id="optionsContainer" style="display:none;">
                        <label class="form-label fw-bold">Opsi Jawaban</label>
                        <div class="border rounded p-3 mb-2">
                            <input type="text" 
                                   name="options[]"
                                   class="form-control mb-2"
                                   placeholder="Opsi 1">
                            <input type="text" 
                                   name="options[]"
                                   class="form-control"
                                   placeholder="Opsi 2">
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Simpan
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
