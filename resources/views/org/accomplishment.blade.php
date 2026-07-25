@extends('org.layout')

@section('title', 'Accomplishment Report')

@section('header')
    <h1>Accomplishment Report</h1>
    <p class="org-welcome">Welcome, {{ $brand['role'] }}</p>
@endsection

@section('content')
    <section class="org-budget-hero liquid-glass">
        <div class="org-budget-hero-copy">
            <p class="org-eyebrow">Narrative &amp; documentation</p>
            <h2>Accomplishment Report</h2>
            <p>
                Summarize completed activities for the selected semester and academic year.
                Attach supporting documents so OSO can verify outcomes alongside the Financial Report.
            </p>
        </div>
        <div class="org-budget-hero-stats">
            <article>
                <span>AR attachments</span>
                <strong>{{ count($arAttachments) }}</strong>
            </article>
            <article class="is-utilized">
                <span>Highlights</span>
                <strong>{{ count($highlights) }}</strong>
            </article>
        </div>
    </section>

    <section class="org-panel liquid-glass org-period-panel">
        <div class="org-panel-head">
            <h2><i class="bi bi-calendar2-range"></i> Report Period</h2>
        </div>
        <form method="get" action="{{ route('office.accomplishment') }}" class="org-period-form">
            <label>
                <span>Semester</span>
                <select name="semester" onchange="this.form.submit()">
                    @foreach ($semesters as $option)
                        <option value="{{ $option }}" @selected($selectedSemester === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Academic Year</span>
                <select name="academic_year" onchange="this.form.submit()">
                    @foreach ($academicYears as $option)
                        <option value="{{ $option }}" @selected($selectedYear === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            <div class="org-period-selected">
                <small>Selected period</small>
                <strong>{{ $selectedSemester }} · AY {{ $selectedYear }}</strong>
            </div>
        </form>
    </section>

    <section class="org-panel liquid-glass">
        <div class="org-panel-head">
            <h2><i class="bi bi-stars"></i> Period Highlights</h2>
        </div>
        <ul class="org-updates">
            @foreach ($highlights as $item)
                <li>
                    <span class="org-dot org-dot-completed"></span>
                    <div>
                        <strong>{{ $item }}</strong>
                        <small>{{ $selectedSemester }} · AY {{ $selectedYear }}</small>
                    </div>
                    <i class="bi bi-chevron-right"></i>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="org-panel liquid-glass">
        <div class="org-panel-head">
            <h2><i class="bi bi-paperclip"></i> AR Attachments</h2>
            <span>{{ count($arAttachments) }} files</span>
        </div>
        <ul class="org-attachment-list">
            @foreach ($arAttachments as $file)
                <li>
                    <i class="bi bi-file-earmark-text"></i>
                    <div>
                        <strong>{{ $file['name'] }}</strong>
                        <small>{{ $file['type'] }} attachment</small>
                    </div>
                    <em>Attached</em>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
