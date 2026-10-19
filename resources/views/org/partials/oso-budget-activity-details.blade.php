@php
    $detailExpenses = collect($entry['expenses'] ?? []);
    $detailApproved = (float) ($entry['approvedBudget'] ?? 0);
    $detailActual = (float) ($entry['actualExpenses'] ?? 0);
    $detailEncoded = round((float) $detailExpenses->sum('amount'), 2);
    $detailRemaining = round($detailApproved - $detailActual, 2);
    $detailLegacy = max(0, round($detailActual - $detailEncoded, 2));
    $detailRate = $detailApproved > 0 ? $detailActual / $detailApproved * 100 : null;
    $detailRateLabel = $detailRate === null ? 'N/A' : number_format($detailRate, 1).'%';
    $detailProgress = $detailRate === null ? 0 : min(100, max(0, $detailRate));
    $detailPaymentMethods = $detailExpenses
        ->map(fn ($expense) => filled($expense['paymentMethod'] ?? null) ? $expense['paymentMethod'] : 'Not recorded')
        ->unique()->values();
    $detailCategories = $detailExpenses
        ->groupBy(fn ($expense) => filled($expense['cat'] ?? null) ? $expense['cat'] : 'General')
        ->map(fn ($expenses) => round((float) $expenses->sum('amount'), 2));
    if ($detailLegacy > 0) {
        $detailCategories['Earlier recorded spending'] = ($detailCategories['Earlier recorded spending'] ?? 0) + $detailLegacy;
    }
    $detailColors = ['#95132e', '#d7a126', '#315fc4', '#21805c', '#8054a5', '#81858f'];
    $detailChartMax = max($detailApproved, $detailActual, 0);
    $detailApprovedHeight = $detailChartMax > 0 ? min(100, max(0, $detailApproved / $detailChartMax * 100)) : 0;
    $detailActualHeight = $detailChartMax > 0 ? min(100, max(0, $detailActual / $detailChartMax * 100)) : 0;
    $detailCircumference = 2 * pi() * 60;
    $detailSegmentOffset = 0;
@endphp

<div class="oso-expense-detail" data-budget-activity-detail="{{ $entry['activityId'] }}">
    <button type="button" class="oso-expense-back" data-budget-activities-back>
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to activities
    </button>

    <article class="oso-expense-selected">
        <p class="oso-expense-kicker">Selected activity</p>
        <h3 class="oso-expense-title" tabindex="-1" data-budget-activity-title>{{ $entry['actName'] }}</h3>
        <p class="oso-expense-meta">{{ $entry['actDate'] }} · {{ $entry['orgName'] }}</p>
        <div class="oso-expense-tags">
            <span class="oso-expense-tag">Activity kind: <strong>{{ $entry['scope'] }}</strong></span>
            @foreach ($detailPaymentMethods as $detailPaymentMethod)
                <span class="oso-expense-tag">Payment method: <strong>{{ $detailPaymentMethod }}</strong></span>
            @endforeach
            @if ($detailPaymentMethods->isEmpty())
                <span class="oso-expense-tag">Payment methods: <strong>No encoded expenses</strong></span>
            @endif
        </div>
    </article>

    <div class="oso-expense-kpis">
        <article class="oso-expense-kpi">
            <p class="oso-expense-label">Approved Budget</p>
            <strong class="oso-expense-value" data-budget-activity-metric="approved">₱{{ number_format($detailApproved, 2) }}</strong>
            <p class="oso-expense-caption">Allocated for this activity</p>
        </article>
        <article class="oso-expense-kpi is-spent">
            <p class="oso-expense-label">Actual Expenses</p>
            <strong class="oso-expense-value" data-budget-activity-metric="spent">₱{{ number_format($detailActual, 2) }}</strong>
            <p class="oso-expense-caption">{{ $detailExpenses->count() }} encoded {{ $detailExpenses->count() === 1 ? 'item' : 'items' }}</p>
        </article>
        <article class="oso-expense-kpi is-remaining{{ $detailRemaining < 0 ? ' is-over-budget' : '' }}">
            <p class="oso-expense-label">Remaining Balance</p>
            <strong class="oso-expense-value" data-budget-activity-metric="remaining">₱{{ number_format($detailRemaining, 2) }}</strong>
            <p class="oso-expense-caption">{{ $detailRemaining < 0 ? 'Spending exceeds the approved budget' : 'Available activity budget' }}</p>
        </article>
        <article class="oso-expense-kpi is-rate">
            <p class="oso-expense-label">Budget Utilization Rate</p>
            <strong class="oso-expense-value" data-budget-activity-metric="rate">{{ $detailRateLabel }}</strong>
            <div class="oso-expense-progress" aria-hidden="true"><span style="width: {{ number_format($detailProgress, 4, '.', '') }}%"></span></div>
            <p class="oso-expense-caption">{{ $detailRate === null ? 'No approved budget to calculate utilization' : 'Actual expenses / approved budget' }}</p>
        </article>
    </div>
    @if ($detailLegacy > 0)
        <p class="oso-expense-legacy-note">Actual expenses include ₱{{ number_format($detailLegacy, 2) }} in earlier recorded spending, in addition to ₱{{ number_format($detailEncoded, 2) }} in encoded expenses. Earlier spending has no encoded receipt item in this table.</p>
    @endif

    <section class="oso-expense-charts" aria-label="Budget charts and trend details">
        <h4 class="oso-expense-section-label">Budget charts and trend details</h4>
        <div class="oso-expense-chart-grid">
            <article class="oso-expense-chart">
                <header class="oso-expense-chart-head">
                    <div>
                        <h4>Expense Breakdown</h4>
                        <p>By expense category · unfilled ring is remaining budget</p>
                    </div>
                    <span class="oso-expense-chart-badge">₱{{ number_format($detailActual, 2) }} total</span>
                </header>
                <div class="oso-expense-breakdown">
                    <svg class="oso-expense-donut" viewBox="0 0 160 160" role="img" aria-label="Expense breakdown. Budget utilization: {{ $detailRateLabel }}. Category amounts are listed beside the chart.">
                        <circle cx="80" cy="80" r="60" fill="none" stroke="#f3e7eb" stroke-width="18" />
                        @foreach ($detailCategories as $detailCategory => $detailCategoryAmount)
                            @php
                                $detailSegmentLength = $detailChartMax > 0 ? max(0, $detailCategoryAmount) / $detailChartMax * $detailCircumference : 0;
                                $detailCategoryColor = $detailColors[$loop->index % count($detailColors)];
                            @endphp
                            @if ($detailSegmentLength > 0)
                                <circle cx="80" cy="80" r="60" fill="none" stroke="{{ $detailCategoryColor }}" stroke-width="18" stroke-dasharray="{{ number_format($detailSegmentLength, 6, '.', '') }} {{ number_format($detailCircumference, 6, '.', '') }}" stroke-dashoffset="{{ number_format(-$detailSegmentOffset, 6, '.', '') }}" transform="rotate(-90 80 80)" />
                            @endif
                            @php $detailSegmentOffset += $detailSegmentLength; @endphp
                        @endforeach
                        <text x="80" y="77" text-anchor="middle">{{ $detailRateLabel }}</text>
                        <text x="80" y="94" text-anchor="middle">UTILIZED</text>
                    </svg>
                    <ul class="oso-expense-legend">
                        @foreach ($detailCategories as $detailCategory => $detailCategoryAmount)
                            <li>
                                <span class="oso-expense-legend-name"><i class="oso-expense-dot" style="--category-color: {{ $detailColors[$loop->index % count($detailColors)] }}" aria-hidden="true"></i>{{ $detailCategory }}</span>
                                <strong>₱{{ number_format($detailCategoryAmount, 2) }}</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @if ($detailCategories->isEmpty())
                    <p class="oso-expense-empty">No expense categories recorded for this activity.</p>
                @elseif ($detailActual == 0)
                    <p class="oso-expense-empty">Recorded expense items total ₱0.00.</p>
                @endif
            </article>

            <article class="oso-expense-chart">
                <header class="oso-expense-chart-head">
                    <div>
                        <h4>Budget vs. Actual Expenses</h4>
                        <p>Approved allocation compared with recorded expenses</p>
                    </div>
                    <span class="oso-expense-chart-badge">Approved vs. recorded</span>
                </header>
                <div class="oso-expense-bars" role="img" aria-label="Approved budget: ₱{{ number_format($detailApproved, 2) }}. Actual expenses: ₱{{ number_format($detailActual, 2) }}. Bars share the same scale.">
                    <div class="oso-expense-bar-column">
                        <span class="oso-expense-bar-value">₱{{ number_format($detailApproved, 2) }}</span>
                        <div class="oso-expense-bar-track"><div class="oso-expense-bar-fill is-approved" style="height: {{ number_format($detailApprovedHeight, 4, '.', '') }}%"></div></div>
                        <span class="oso-expense-bar-label">Approved Budget</span>
                    </div>
                    <div class="oso-expense-bar-column">
                        <span class="oso-expense-bar-value">₱{{ number_format($detailActual, 2) }}</span>
                        <div class="oso-expense-bar-track"><div class="oso-expense-bar-fill is-actual" style="height: {{ number_format($detailActualHeight, 4, '.', '') }}%"></div></div>
                        <span class="oso-expense-bar-label">Actual Expenses</span>
                    </div>
                </div>
                @if ($detailChartMax == 0)
                    <p class="oso-expense-empty">No approved budget or actual expenses recorded.</p>
                @endif
            </article>
        </div>
    </section>

    <section class="oso-expense-table-card">
        <header class="oso-expense-table-head">
            <div>
                <h4 class="oso-expense-table-title"><i class="bi bi-receipt" aria-hidden="true"></i> Expense Details &amp; Itemization</h4>
                <p class="oso-expense-caption">Recorded receipts and complete payment information.</p>
            </div>
            <input class="oso-expense-search" type="search" data-budget-expense-search aria-label="Search activity expenses" placeholder="Search item, category, amount…" autocomplete="off">
        </header>
        <div class="oso-expense-table-scroll" tabindex="0" aria-label="Activity expense items">
            <table class="oso-expense-table">
                <thead>
                    <tr>
                        <th scope="col">Description / Merchant</th>
                        <th scope="col">Date</th>
                        <th scope="col">Qty</th>
                        <th scope="col">Payment method</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Receipt status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($detailExpenses as $detailExpense)
                        @php
                            $detailExpenseMethod = filled($detailExpense['paymentMethod'] ?? null) ? $detailExpense['paymentMethod'] : 'Not recorded';
                            $detailExpenseSearch = implode(' ', [
                                $detailExpense['desc'] ?? '', $detailExpense['date'] ?? '', $detailExpense['cat'] ?? '',
                                $detailExpenseMethod, $detailExpense['amount'] ?? 0,
                                number_format((float) ($detailExpense['amount'] ?? 0), 2), $detailExpense['receiptFile'] ?? '',
                            ]);
                        @endphp
                        <tr data-budget-expense-row data-search-value="{{ $detailExpenseSearch }}">
                            <td>
                                <strong>{{ $detailExpense['desc'] ?? 'Description not recorded' }}</strong>
                                <small>{{ $detailExpense['cat'] ?? 'General' }} · {{ $detailExpense['receiptFile'] ?? 'Receipt filename not recorded' }}</small>
                            </td>
                            <td>{{ $detailExpense['date'] ?? 'Not recorded' }}</td>
                            <td>{{ $detailExpense['qty'] ?? 'Not recorded' }}</td>
                            <td><span class="oso-expense-tag">{{ $detailExpenseMethod }}</span></td>
                            <td>₱{{ number_format((float) ($detailExpense['amount'] ?? 0), 2) }}</td>
                            <td><span class="oso-expense-tag">{{ $detailExpense['status'] ?? 'Not recorded' }}</span></td>
                            <td>
                                @if (filled($detailExpense['receiptUrl'] ?? null))
                                    <a class="oso-expense-receipt app-view-action" href="{{ $detailExpense['receiptUrl'] }}" data-budget-dialog-receipt="{{ $detailExpense['id'] }}" aria-haspopup="dialog" aria-controls="budgetReceiptPreviewDialog"><i class="bi bi-eye" aria-hidden="true"></i> View</a>
                                @else
                                    <span class="oso-expense-empty">Original unavailable</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="oso-expense-empty">No encoded expense items for this activity.{{ $detailLegacy > 0 ? ' Earlier recorded spending is shown in the totals and chart above.' : '' }}</td></tr>
                    @endforelse
                    <tr data-budget-expense-empty hidden><td colspan="7" class="oso-expense-empty">No encoded expense items match your search.</td></tr>
                </tbody>
            </table>
        </div>
        <p class="oso-expense-search-count" data-budget-expense-count role="status" aria-live="polite">{{ $detailExpenses->count() }} encoded {{ $detailExpenses->count() === 1 ? 'item' : 'items' }}</p>
    </section>
</div>
