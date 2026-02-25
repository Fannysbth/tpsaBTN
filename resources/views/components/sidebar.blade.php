<aside class="sidebar">
    <div class="sidebar-header">
        <h3 class="sidebar-title">
            TPSA<br>
            <small>Security Questionnaire</small>
        </h3>
        <div class="sidebar-divider"></div>
    </div>

    <ul class="sidebar-menu">
        <li class="{{ request()->is('/') ? 'active' : '' }}">
        <a href="{{ url('/') }}">
            <i class="fa-solid fa-gauge"></i>
            <span>Dashboard</span>
        </a>
        </li>

        <li class="{{ request()->is('questionnaire*') ? 'active' : '' }}">
            <a href="{{ url('questionnaire') }}">
                <i class="fa-solid fa-clipboard-question"></i>
                <span>Questionnaire</span>
            </a>
        </li>

        <li class="{{ request()->is('assessment*') ? 'active' : '' }}">
            <a href="{{ route('assessment.index') }}">
                <i class="fa-solid fa-chart-line"></i>
                <span>Assessment</span>
            </a>
        </li>

        <li class="{{ request()->is('result*') ? 'active' : '' }}">
            <a href="{{ url('result') }}">
                <i class="fa-solid fa-file-lines"></i>
                <span>Result</span>
            </a>
        </li>

    </ul>
</aside>
