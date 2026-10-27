<section class="sp-section sv-section" aria-labelledby="studentVoiceHeading">
    <style>
        .sv-section { min-width:0; }
        .sv-heading h2 { margin:0; font-size:1.15rem; }
        .sv-heading p { color:#7d6b74; font-size:.84rem; margin:.35rem 0 1rem; }
        .sv-grid { display:grid; grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr); gap:1rem; align-items:start; }
        .sv-card { border:1px solid #eadde2; border-radius:18px; background:#fff; padding:1.25rem; min-width:0; box-shadow:0 5px 18px rgba(95,25,45,.03); }
        .sv-card h3 { margin:0 0 .75rem; font-size:.96rem; }
        .sv-form { display:grid; gap:.85rem; }
        .sv-form label { display:grid; gap:.35rem; color:#6c5762; font-size:.78rem; font-weight:750; }
        .sv-form select,.sv-form textarea { width:100%; box-sizing:border-box; border:1px solid #e3d2d9; background:#fff; border-radius:10px; padding:.65rem .75rem; color:#3b2b34; font:inherit; font-size:.84rem; }
        .sv-form textarea { resize:vertical; min-height:150px; line-height:1.6; }
        .sv-form :focus-visible,.sv-record summary:focus-visible { outline:2px solid #8b1828; outline-offset:3px; }
        .sv-form small { font-weight:400; color:#8b7881; }
        .sv-submit { justify-self:start; padding:.65rem 1rem; border:0; border-radius:10px; background:#8b1828; color:#fff; font:inherit; font-size:.82rem; font-weight:750; cursor:pointer; }
        .sv-history { display:grid; gap:.65rem; max-height:42rem; overflow:auto; padding:.2rem; }
        .sv-record { border:1px solid #ecdee4; border-radius:12px; background:#fff; scroll-margin-top:1rem; }
        .sv-record summary { cursor:pointer; padding:.9rem; list-style:none; }
        .sv-record summary::-webkit-details-marker { display:none; }
        .sv-summary-line { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; }
        .sv-summary-line strong { font-size:.82rem; }
        .sv-summary-preview { display:block; margin-top:.45rem; font-size:.8rem; line-height:1.5; color:#796570; overflow-wrap:anywhere; }
        .sv-summary-date { display:block; margin-top:.4rem; color:#927c87; font-size:.7rem; }
        .sv-status { border:1px solid #fde68a; background:#fffbeb; color:#92400e; padding:.2rem .55rem; font-size:.67rem; font-weight:800; border-radius:999px; }
        .sv-status.verified { border-color:#a7f3d0; background:#ecfdf5; color:#065f46; }
        .sv-status.dismissed { border-color:#fecaca; background:#fef2f2; color:#991b1b; }
        .sv-detail { border-top:1px solid #efe3e8; padding:1rem; }
        .sv-body { margin:0; font-size:.85rem; color:#46313d; line-height:1.7; white-space:pre-wrap; overflow-wrap:anywhere; }
        .sv-remarks { margin:1rem 0; border-left:3px solid #bd8093; padding:.75rem; border-radius:0 8px 8px 0; background:#fff5f8; font-size:.8rem; line-height:1.6; white-space:pre-wrap; overflow-wrap:anywhere; }
        .sv-remarks strong { display:block; color:#8b1828; }
        .sv-timeline { list-style:none; padding:0 0 0 .55rem; margin:1rem 0 0; border-left:2px solid #ecd6df; }
        .sv-timeline li { position:relative; padding:0 0 .85rem .65rem; font-size:.78rem; }
        .sv-timeline li::before { content:''; position:absolute; width:7px; height:7px; border-radius:50%; background:#a44d69; left:-.83rem; top:.25rem; }
        .sv-timeline li:last-child { padding-bottom:0; }
        .sv-timeline strong,.sv-timeline small { display:block; }
        .sv-timeline small { color:#8e7682; margin-top:.2rem; }
        .sv-empty { color:#8b7580; font-size:.82rem; line-height:1.6; }
        @media(max-width:900px) { .sv-grid { grid-template-columns:minmax(0,1fr); } }
        @media(max-width:520px) { .sv-card { padding:1rem; } }
    </style>
    <header class="sv-heading"><h2 id="studentVoiceHeading"><i class="bi bi-chat-heart" aria-hidden="true"></i> Student Voice</h2><p>Share a campus concern with OSO and follow the recorded response to your own submissions.</p></header>
    <div class="sv-grid">
        <div class="sv-card">
            <h3><i class="bi bi-pencil-square" aria-hidden="true"></i> Submit a concern</h3>
            <form method="POST" action="{{ route('portal.feedback.store') }}" class="sv-form" data-confirm-message="Send this feedback to OSO?">
                @csrf
                <label>Topic<select name="category">@foreach (['campus' => 'Campus life', 'admin' => 'Admin / offices', 'activities' => 'Activities', 'general' => 'General'] as $value => $label)<option value="{{ $value }}" @selected(old('category', 'campus') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label>Your concern<textarea name="message" required maxlength="2000" rows="5" placeholder="Describe your concern so OSO can respond…">{{ old('message') }}</textarea><small>Up to 2,000 characters. Only your own submission history appears here.</small></label>
                @error('message')<p role="alert">{{ $message }}</p>@enderror
                <button type="submit" class="sv-submit"><i class="bi bi-send" aria-hidden="true"></i> Send to OSO</button>
            </form>
        </div>
        <div class="sv-card">
            <h3><i class="bi bi-clock-history" aria-hidden="true"></i> My submission history <small>({{ $myFeedback->count() }})</small></h3>
            <div class="sv-history" aria-label="Your feedback history">
                @forelse ($myFeedback as $feedback)
                    <details class="sv-record" id="feedback-{{ $feedback->id }}">
                        <summary>
                            <span class="sv-summary-line"><strong>{{ ucfirst($feedback->topic ?: 'General') }} · #{{ $feedback->id }}</strong><span class="sv-status {{ $feedback->status }}">{{ $feedback->status === 'verified' ? 'Acknowledged' : ucfirst($feedback->status) }}</span></span>
                            <span class="sv-summary-preview">{{ \Illuminate\Support\Str::limit($feedback->body, 110) }}</span>
                            <span class="sv-summary-date">{{ $feedback->created_at?->format('M j, Y · g:i A') ?? 'Submission time not recorded' }} · View details <i class="bi bi-chevron-down" aria-hidden="true"></i></span>
                        </summary>
                        <div class="sv-detail">
                            <p class="sv-body">{{ $feedback->body }}</p>
                            <div class="sv-remarks"><strong>OSO remarks</strong>{{ $feedback->review_note ?: 'No OSO remarks recorded.' }}</div>
                            <ol class="sv-timeline" aria-label="Recorded concern timeline">
                                <li><strong>Submitted</strong><small>{{ $feedback->created_at?->format('M j, Y · g:i A') ?? 'Submission time not recorded' }}</small></li>
                                @if ($feedback->status === 'verified')
                                    <li><strong>Acknowledged by OSO</strong><small>{{ $feedback->verified_at?->format('M j, Y · g:i A') ?? 'Acknowledgment time not recorded' }}</small></li>
                                @elseif ($feedback->status === 'dismissed')
                                    <li><strong>Dismissed by OSO</strong><small>Decision time not recorded.</small></li>
                                @else
                                    <li><strong>Awaiting OSO review</strong><small>No review decision recorded yet.</small></li>
                                @endif
                            </ol>
                        </div>
                    </details>
                @empty
                    <p class="sv-empty">No submissions yet. Your concerns and any recorded OSO responses will appear here after you send them.</p>
                @endforelse
            </div>
        </div>
    </div>
    <script>
        if (window.location.hash.startsWith('#feedback-')) {
            const feedback = document.getElementById(window.location.hash.slice(1));
            if (feedback?.classList.contains('sv-record')) feedback.open = true;
        }
    </script>
</section>
