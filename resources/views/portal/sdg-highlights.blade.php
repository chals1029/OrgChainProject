@php
    $highlights = collect($sdgHighlights ?? []);
    $goalDefinitions = \App\Services\SdgCatalogue::GOALS;
@endphp

<section class="sp-sdg-highlights liquid-glass" data-sdg-highlights aria-labelledby="sdgHighlightsHeading">
    <div class="sp-sdg-header">
        <div class="sp-sdg-heading">
            <span class="sp-sdg-heading-icon" aria-hidden="true"><i class="bi bi-globe2"></i></span>
            <div>
                <h2 id="sdgHighlightsHeading">SDG Highlights</h2>
                <p>Which Sustainable Development Goals campus activities incorporate.</p>
            </div>
        </div>
        <form class="sp-sdg-filters" data-sdg-filters role="search" aria-label="Filter SDG highlights" hidden>
            <label class="sp-sdg-search">
                <span class="sr-only">Search SDG or activity</span>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="sdg_search" placeholder="Search SDG or activity..." autocomplete="off" data-sdg-search>
            </label>
            <label class="sp-sdg-select">
                <span class="sr-only">Sustainable Development Goal</span>
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <select name="sdg_goal" data-sdg-goal>
                    <option value="">All SDGs</option>
                    @foreach ($highlights as $sdg)
                        <option value="{{ $sdg['sdg'] }}">{{ $sdg['sdg'] }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </div>

    <p class="sp-sdg-result-count" data-sdg-count role="status" aria-live="polite" aria-atomic="true">{{ $highlights->count() }} {{ $highlights->count() === 1 ? 'SDG' : 'SDGs' }} found</p>
    <div class="sp-sdg-grid">
        @foreach ($highlights as $sdg)
            @php
                $number = preg_match('/^(?:SDG\s*)?(\d{1,2})$/i', (string) $sdg['sdg'], $match) ? (int) $match[1] : 0;
                [$goalName, $goalIcon, $goalColor] = $goalDefinitions[$number] ?? [$sdg['sdg'], 'globe2', '#7a1222'];
                $activityTitles = collect($sdg['activities'])->values();
            @endphp
            <article class="sp-sdg-card" data-sdg-card data-sdg-goal="{{ $sdg['sdg'] }}" data-sdg-name="{{ $goalName }}" data-sdg-activities="{{ json_encode($activityTitles, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) }}">
                <span class="sp-sdg-badge" style="--sdg-color: {{ $goalColor }}; padding: 0; overflow: hidden" aria-hidden="true">
                    @include('org.partials.sdg-symbol', ['number' => $number, 'goalIcon' => $goalIcon])
                </span>
                <div class="sp-sdg-card-copy">
                    <h3 title="{{ $goalName }}">{{ $sdg['sdg'] }}</h3>
                    <p data-sdg-activity-count>{{ $sdg['count'] }} {{ $sdg['count'] === 1 ? 'activity' : 'activities' }}</p>
                    <p class="sp-sdg-activity-titles" data-sdg-activity-titles>{{ $activityTitles->take(3)->implode(' · ') }}</p>
                    <button type="button" class="app-view-action" data-sdg-open aria-label="View activities for {{ $sdg['sdg'] }}"><i class="bi bi-eye" aria-hidden="true"></i> View activities</button>
                </div>
            </article>
        @endforeach
    </div>
    <p class="sp-sdg-empty" data-sdg-empty @if ($highlights->isNotEmpty()) hidden @endif>No SDG-tagged activities yet.</p>
    <p class="sp-sdg-source"><a href="https://www.un.org/sustainabledevelopment/" target="_blank" rel="noopener noreferrer">United Nations Sustainable Development Goals</a>. The content of this publication has not been approved by the United Nations and does not reflect the views of the United Nations or its officials or Member States.</p>
    <dialog class="sp-sdg-dialog" data-sdg-dialog aria-labelledby="sdgRelatedTitle">
        <header><div><h2 id="sdgRelatedTitle" data-sdg-dialog-title>Related activities</h2><p data-sdg-dialog-summary></p></div><button type="button" class="app-view-action" data-sdg-close aria-label="Close related activities"><i class="bi bi-x-lg" aria-hidden="true"></i> Close</button></header>
        <ul data-sdg-dialog-activities></ul>
    </dialog>
    <style>
        .sp-sdg-highlights, .sp-sdg-grid, .sp-sdg-card { overflow:hidden; width:100%; box-sizing:border-box; min-width:0; }
        .sp-sdg-source, .sp-sdg-source a { overflow-wrap:anywhere; word-break:break-word; }
        .sp-sdg-highlights [hidden] { display:none !important; }
        .sp-sdg-card-copy .app-view-action { margin-top:.6rem; }
        .sp-sdg-dialog { box-sizing:border-box; width:min(640px,calc(100vw - 2rem)); max-width:none; max-height:calc(100dvh - 2rem); margin:auto; padding:1.25rem; border:1px solid #e9d9df; border-radius:18px; background:#fff; color:#3b2b34; }
        .sp-sdg-dialog::backdrop { background:rgba(32,18,25,.5); backdrop-filter:blur(3px); }
        .sp-sdg-dialog header { display:flex; align-items:start; justify-content:space-between; gap:1rem; border-bottom:1px solid #eee1e7; padding-bottom:.8rem; }
        .sp-sdg-dialog h2 { margin:0; font-size:1.15rem; }
        .sp-sdg-dialog p { margin:.35rem 0 0; color:#8c7582; font-size:.8rem; }
        .sp-sdg-dialog ul { list-style:none; padding:0; margin:1rem 0 0; display:grid; gap:.6rem; }
        .sp-sdg-dialog li { padding:.85rem; border:1px solid #efdfE6; border-radius:10px; background:#fff8fb; font-size:.87rem; line-height:1.6; overflow-wrap:anywhere; }
        @media (max-width:768px) {
            .portal-body .sp-sdg-header { flex-direction:column; align-items:stretch; width:100%; min-width:0; }
            .portal-body .sp-sdg-heading, .portal-body .sp-sdg-filters { flex:0 1 auto; width:100%; min-width:0; }
            .portal-body .sp-sdg-heading > div { min-width:0; overflow-wrap:anywhere; }
            .portal-body .sp-sdg-filters { flex-direction:column; align-items:stretch; max-width:100%; }
            .portal-body .sp-sdg-search, .portal-body .sp-sdg-select { flex:0 1 auto; width:100%; min-width:0; min-height:44px; box-sizing:border-box; }
            .portal-body .sp-sdg-search input, .portal-body .sp-sdg-select select { width:100%; min-width:0; font-size:16px; }
            .portal-body .sp-sdg-grid { grid-template-columns:1fr !important; }
            .portal-body .sp-sdg-card { display:flex; flex-direction:row; align-items:flex-start; width:100%; min-width:0; padding:.85rem; overflow:hidden; }
            .portal-body .sp-sdg-badge { width:56px; height:56px; flex:0 0 56px; }
            .portal-body .sp-sdg-card-copy { flex:1 1 auto; min-width:0; }
            .portal-body .sp-sdg-activity-titles { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; overflow-wrap:anywhere; }
            .portal-body .sp-sdg-card-copy .app-view-action { min-height:44px; max-width:100%; white-space:normal; }
            .portal-body .sp-sdg-dialog { width:calc(100vw - 1.5rem); max-width:calc(100vw - 1.5rem); max-height:calc(100dvh - 2rem); }
        }
        @media (max-width:380px) {
            .portal-body .sp-sdg-badge { width:48px; height:48px; flex-basis:48px; }
            .portal-body .sp-sdg-card-copy h3 { font-size:.85rem; }
        }
    </style>
</section>
