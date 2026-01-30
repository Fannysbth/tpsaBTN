@forelse($assessments as $index => $assessment)
    <div class="question-row">
        <div>{{ $index + 1 }}</div>
        <div>{{ $assessment->assessment_date->format('d/m/Y') }}</div>
        <div>{{ $assessment->company_name }}</div>
        <div>{{ strtoupper($assessment->risk_level_label ?? '-') }}</div>

        <div class="action-btns">
            <a href="{{ route('assessment.edit', $assessment->id) }}" class="btn btn-warning btn-sm">Edit</a>
            <a href="{{ route('assessment.export', $assessment) }}" class="btn btn-primary btn-sm">Export</a>
        </div>

        <div>
            <a href="{{ route('assessment.show', $assessment->id) }}">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>
    </div>
@empty
    @forelse($assessments as $index => $assessment)
    <div class="question-row">
        <div>{{ $index + 1 }}</div>
        <div>{{ $assessment->assessment_date->format('d/m/Y') }}</div>
        <div>{{ $assessment->company_name }}</div>
        <div>{{ strtoupper($assessment->risk_level_label ?? '-') }}</div>

        <div class="action-btns">
            <a href="{{ route('assessment.edit', $assessment->id) }}" class="btn btn-warning btn-sm">Edit</a>
            <a href="{{ route('assessment.export', $assessment) }}" class="btn btn-primary btn-sm">Export</a>
        </div>

        <div>
            <a href="{{ route('assessment.show', $assessment->id) }}">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>
    </div>
@empty
    {{-- 🔥 1 baris penuh --}}
    <div class="question-row empty-row">
        <div class="empty-cell">
            Data tidak ditemukan.
        </div>
    </div>
@endforelse

@endforelse
