@extends('org.layout')

@section('title', 'Activities')

@section('header')
    <h1>Activities</h1>
    <p class="org-welcome">Activity details, budget allocation, and generated documents are managed here. You can also create new activities and events for your organization.</p>
@endsection

@section('actions')
    <a href="{{ route('office.activities') }}" class="org-btn org-btn-primary">
        <i class="bi bi-plus-lg"></i> Create
    </a>
@endsection

@section('content')
    <div class="org-activity-list">
        @foreach ($activities as $activity)
            <article class="org-activity-card liquid-glass">
                <div class="org-activity-head">
                    <div class="org-activity-title">
                        <span class="org-stat-icon is-red"><i class="bi bi-lightning-charge-fill"></i></span>
                        <div>
                            <h2>{{ $activity['title'] }}</h2>
                            <p>
                                <span class="org-meta-chip"><i class="bi bi-calendar3"></i> {{ $activity['date'] }}</span>
                                <span class="org-meta-chip"><i class="bi bi-cash-stack"></i> Php {{ number_format($activity['budget']) }}</span>
                            </p>
                        </div>
                    </div>
                    <span class="org-status org-status-{{ $activity['status_key'] }}">{{ $activity['status'] }}</span>
                </div>

                @if (!empty($activity['note']))
                    <div class="org-alert"><i class="bi bi-exclamation-triangle-fill"></i> {{ $activity['note'] }}</div>
                @endif

                <ul class="org-doc-list">
                    @foreach ($activity['docs'] as $doc)
                        <li>
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                            <span>{{ $doc }}</span>
                            <div class="org-doc-actions">
                                <button type="button" aria-label="View"><i class="bi bi-eye"></i></button>
                                <button type="button" aria-label="Download"><i class="bi bi-download"></i></button>
                                <button type="button" aria-label="Print"><i class="bi bi-printer"></i></button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </div>
@endsection
