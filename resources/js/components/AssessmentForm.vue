<template>
    <div class="assessment-form">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Assessment</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="companyName" class="form-label">Nama Perusahaan</label>
                        <input type="text" class="form-control" id="companyName" v-model="form.company_name" required>
                    </div>
                </div>

                <div v-for="category in categories" :key="category.id" class="category-section mb-5">
                    <h6 class="category-title">{{ category.name }}</h6>
                    <div class="card">
                        <div class="card-body">
                            <div v-for="question in category.active_questions" :key="question.id" class="mb-4">
                                <div class="question-item">
                                    <label class="form-label fw-bold">{{ question.order }}. {{ question.question_text }}</label>
                                    
                                    <div v-if="question.clue" class="text-muted small mb-2">
                                        <i class="fas fa-info-circle"></i> {{ question.clue }}
                                    </div>

                                    <!-- Input berdasarkan tipe -->
                                    <div v-if="question.question_type === 'isian'">
                                        <input type="text" 
                                               class="form-control"
                                               v-model="answers[question.id].answer"
                                               :placeholder="question.clue || 'Jawaban...'">
                                    </div>

                                    <div v-if="question.question_type === 'pilihan'">
                                        <div class="form-check" v-for="option in question.options" :key="option.id">
                                            <input class="form-check-input" 
                                                   type="radio"
                                                   :name="'question_' + question.id"
                                                   :value="option.option_text"
                                                   v-model="answers[question.id].answer">
                                            <label class="form-check-label">
                                                {{ option.option_text }} (Score: {{ option.score }})
                                            </label>
                                        </div>
                                    </div>

                                    <div v-if="question.question_type === 'checkbox'">
                                        <div class="form-check" v-for="option in question.options" :key="option.id">
                                            <input class="form-check-input" 
                                                   type="checkbox"
                                                   :value="option.option_text"
                                                   v-model="answers[question.id].answer">
                                            <label class="form-check-label">
                                                {{ option.option_text }} (Score: {{ option.score }})
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Attachment -->
                                    <div v-if="question.has_attachment" class="mt-2">
                                        <label class="form-label">Upload Dokumen Pendukung</label>
                                        <input type="file" 
                                               class="form-control"
                                               @change="handleAttachment($event, question.id)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="button" class="btn btn-primary" @click="submitAssessment">
                        Simpan Assessment
                    </button>
                    <button type="button" class="btn btn-success ms-2" @click="exportToExcel">
                        Export ke Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        categories: {
            type: Array,
            required: true
        },
        assessmentId: {
            type: Number,
            default: null
        }
    },

    data() {
        return {
            form: {
                company_name: ''
            },
            answers: {},
            attachments: {}
        };
    },

    mounted() {
        this.initializeAnswers();
    },

    methods: {
        initializeAnswers() {
            this.categories.forEach(category => {
                category.active_questions.forEach(question => {
                    if (question.question_type === 'checkbox') {
                        this.$set(this.answers, question.id, {
                            answer: [],
                            attachment: null
                        });
                    } else {
                        this.$set(this.answers, question.id, {
                            answer: '',
                            attachment: null
                        });
                    }
                });
            });
        },

        handleAttachment(event, questionId) {
            this.answers[questionId].attachment = event.target.files[0];
        },

        async submitAssessment() {
            try {
                const formData = new FormData();
                formData.append('company_name', this.form.company_name);
                formData.append('answers', JSON.stringify(this.answers));

                // Append attachments
                Object.keys(this.answers).forEach(questionId => {
                    if (this.answers[questionId].attachment) {
                        formData.append(`attachments[${questionId}]`, this.answers[questionId].attachment);
                    }
                });

                const response = await axios.post('/assessment', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Assessment berhasil disimpan'
                    }).then(() => {
                        window.location.href = `/assessment/${response.data.assessment_id}`;
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: error.response?.data?.message || 'Terjadi kesalahan'
                });
            }
        },

        async exportToExcel() {
            try {
                const response = await axios.get(`/assessment/${this.assessmentId}/export`);
                window.open(response.data.download_url);
            } catch (error) {
                console.error('Export error:', error);
            }
        }
    }
};
</script>

<style scoped>
.category-section {
    border-left: 4px solid #0d6efd;
    padding-left: 15px;
}

.category-title {
    color: #0d6efd;
    font-weight: bold;
    margin-bottom: 15px;
}

.question-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 10px;
}
</style>