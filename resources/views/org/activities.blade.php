@extends('org.layout')

@section('title', 'Activities')

@section('header')
    <h1>Activities</h1>
    <p class="org-welcome">Activity details, budget allocation, and generated documents are managed here. You can also create new activities and events for your organization.</p>
@endsection

@section('actions')
    <a href="{{ route('office.activities.create') }}" class="org-btn org-btn-primary">
        <i class="bi bi-plus-lg"></i> Create
    </a>
@endsection

@section('content')
    @if ($submissions->isNotEmpty())
        <section class="org-submission-list">
            <div class="org-section-heading">
                <div>
                    <span class="org-eyebrow"><i class="bi bi-file-earmark-check-fill"></i> Activity proposals</span>
                    <h2>Your saved submissions</h2>
                </div>
                <span>{{ $submissions->count() }} total</span>
            </div>
            @foreach ($submissions as $submission)
                @php $activity = $submission->activity; $attachments = $submission->attachments ?? []; $isOffCampus = $submission->isOffCampus(); @endphp
                <article class="org-submission-card liquid-glass">
                    <div>
                        <span class="org-submission-type">
                            <i class="bi {{ $isOffCampus ? 'bi-geo-alt-fill' : 'bi-building-check' }}"></i> 
                            {{ $isOffCampus ? 'Local off-campus activity' : 'In-campus activity' }}
                        </span>
                        <h2>{{ $activity?->title ?? 'Untitled activity' }}</h2>
                        <p>
                            @if ($activity?->starts_at)<span><i class="bi bi-calendar3"></i> {{ $activity->starts_at->format('M j, Y g:i A') }}</span>@endif
                            @if ($activity?->location)<span><i class="bi bi-geo-alt-fill"></i> {{ $activity->location }}</span>@endif
                            <span><i class="bi bi-paperclip"></i> {{ collect($attachments)->filter(fn ($value, $key) => $key !== 'conditions' && is_array($value) && !empty($value['path']))->count() }} attachment(s)</span>
                        </p>
                    </div>
                    <div class="org-submission-actions">
                        <span class="org-status org-status-{{ $submission->status === 'submitted' ? 'verification' : 'created' }}">{{ ucfirst($submission->status) }}</span>
                        <a href="{{ route('office.activities.edit', $submission) }}" class="org-btn org-btn-ghost"><i class="bi bi-pencil-square"></i> {{ $submission->status === 'submitted' ? 'View' : 'Continue' }}</a>
                    </div>
                </article>
            @endforeach
        </section>
    @endif

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
                                @if (!empty($activity['location']))
                                    <span class="org-meta-chip"><i class="bi bi-geo-alt-fill"></i> {{ $activity['location'] }}</span>
                                @endif
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
