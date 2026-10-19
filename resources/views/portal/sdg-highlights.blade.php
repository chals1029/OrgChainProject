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
            <button type="reset" class="sp-sdg-clear"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Clear filters</button>
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
                <span class="sp-sdg-badge" style="--sdg-color: {{ $goalColor }}" aria-hidden="true">
                    <span class="sp-sdg-badge-heading"><strong>{{ $number ?: 'SDG' }}</strong><span>{{ $goalName }}</span></span>
                    @include('org.partials.sdg-symbol', ['number' => $number, 'goalIcon' => $goalIcon])
                </span>
                <div class="sp-sdg-card-copy">
                    <h3 title="{{ $goalName }}">{{ $sdg['sdg'] }}</h3>
                    <p data-sdg-activity-count>{{ $sdg['count'] }} {{ $sdg['count'] === 1 ? 'activity' : 'activities' }}</p>
                    <p class="sp-sdg-activity-titles" data-sdg-activity-titles>{{ $activityTitles->take(3)->implode(' · ') }}</p>
                </div>
            </article>
        @endforeach
    </div>
    <p class="sp-sdg-empty" data-sdg-empty @if ($highlights->isNotEmpty()) hidden @endif>No SDG-tagged activities yet.</p>
</section>
