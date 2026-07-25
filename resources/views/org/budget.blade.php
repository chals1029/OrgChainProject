@extends('org.layout')

@section('title', 'Budget Utilization')

@section('header')
    <h1>Budget Utilization</h1>
    <p class="org-welcome">Welcome, {{ $brand['role'] }}</p>
@endsection

@section('content')
    @php
        $firstActivity = $budget['activities'][0] ?? null;
    @endphp

    <section class="org-budget-hero liquid-glass">
        <div class="org-budget-hero-copy">
            <p class="org-eyebrow">Actual expense recording</p>
            <h2>Budget Utilization</h2>
            <p>
                Record actual expenses for each approved activity. These item-level entries become the source
                records for the semester Financial Report and public transparency review.
            </p>
        </div>
        <div class="org-budget-hero-stats">
            <article>
                <span>Approved</span>
                <strong>{{ $budget['approved_count'] }}</strong>
            </article>
            <article class="is-utilized">
                <span>Utilized</span>
                <strong>Php {{ number_format($budget['used'], 2) }}</strong>
            </article>
        </div>
    </section>

    <section class="org-budget-kpis">
        <article class="liquid-glass">
            <div class="org-budget-kpi-icon" aria-hidden="true"><i class="bi bi-currency-exchange"></i></div>
            <p class="org-eyebrow">Budget allocation</p>
            <strong>Php {{ number_format($budget['allocated'], 2) }}</strong>
            <small>Total proposed budgets from your activity records</small>
        </article>
        <article class="liquid-glass">
            <div class="org-budget-kpi-icon" aria-hidden="true"><i class="bi bi-receipt"></i></div>
            <p class="org-eyebrow">Actual expenses</p>
            <strong>Php {{ number_format($budget['used'], 2) }}</strong>
            <small>Total utilization already encoded</small>
        </article>
        <article class="liquid-glass">
            <div class="org-budget-kpi-icon" aria-hidden="true"><i class="bi bi-check2-circle"></i></div>
            <p class="org-eyebrow">Coverage</p>
            <strong>{{ $budget['covered_count'] }}/{{ $budget['approved_count'] }}</strong>
            <small>Approved activities with saved expense records</small>
        </article>
    </section>

    <section class="org-panel liquid-glass org-budget-overview">
        <div class="org-panel-head">
            <h2><i class="bi bi-pie-chart-fill"></i> Budget Overview</h2>
        </div>

        <div class="org-budget-overview-grid">
            <div class="org-budget-metric">
                <span class="org-budget-metric-ico is-alloc"><i class="bi bi-wallet2"></i></span>
                <div>
                    <small>Allocated</small>
                    <strong>₱{{ number_format($budget['allocated'], 2) }}</strong>
                </div>
            </div>
            <div class="org-budget-metric">
                <span class="org-budget-metric-ico is-used"><i class="bi bi-cash-stack"></i></span>
                <div>
                    <small>Used</small>
                    <strong>₱{{ number_format($budget['used'], 2) }}</strong>
                </div>
            </div>
            <div class="org-budget-metric">
                <span class="org-budget-metric-ico is-remain"><i class="bi bi-piggy-bank"></i></span>
                <div>
                    <small>Remaining</small>
                    <strong>₱{{ number_format($budget['remaining'], 2) }}</strong>
                </div>
            </div>
        </div>

        <div class="org-budget-util-row">
            <div>
                <span>Budget Utilization</span>
                <strong>{{ $budget['percent'] }}%</strong>
            </div>
            <div class="org-budget-util-bar" role="progressbar" aria-valuenow="{{ $budget['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                <span style="width: {{ min(100, $budget['percent']) }}%"></span>
            </div>
            <p class="org-budget-complete">
                <i class="bi bi-check-circle-fill"></i>
                {{ $budget['covered_count'] }} of {{ $budget['approved_count'] }} Activities submitted
            </p>
        </div>
    </section>

    <div class="org-budget-split">
        <section class="org-panel liquid-glass">
            <div class="org-panel-head">
                <h2><i class="bi bi-journal-plus"></i> Record Expense</h2>
                <button type="button" class="org-text-btn" id="budgetResetBtn">Reset Form</button>
            </div>

            <form class="org-budget-form" id="budgetExpenseForm" onsubmit="return false;">
                <label>
                    <span>Select Activity</span>
                    <select name="activity" id="budgetActivitySelect">
                        @foreach ($budget['activity_options'] as $option)
                            <option value="{{ $option['title'] }}" data-remaining="{{ $option['remaining'] }}">
                                {{ $option['title'] }}
                            </option>
                        @endforeach
                    </select>
                    <small id="budgetRemainingHint">
                        Remaining Budget: ₱{{ number_format($firstActivity['remaining'] ?? 0, 2) }}
                    </small>
                </label>

                <label>
                    <span>Item Name</span>
                    <input type="text" name="item_name" placeholder="e.g. Portable sound system rental">
                </label>

                <div class="org-budget-form-row">
                    <label>
                        <span>Category</span>
                        <select name="category">
                            <option value="">Select category</option>
                            <option>Equipment Rental</option>
                            <option>Supplies</option>
                            <option>Food &amp; Refreshments</option>
                            <option>Transportation</option>
                            <option>Printing</option>
                            <option>Other</option>
                        </select>
                    </label>
                    <label>
                        <span>Quantity</span>
                        <input type="number" name="quantity" min="1" value="1">
                    </label>
                </div>

                <div class="org-budget-form-row">
                    <label>
                        <span>Unit Cost</span>
                        <div class="org-input-peso">
                            <em>₱</em>
                            <input type="number" name="unit_cost" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </label>
                    <label>
                        <span>Expense Date</span>
                        <input type="date" name="expense_date" value="{{ now()->toDateString() }}">
                    </label>
                </div>

                <label class="org-budget-upload">
                    <span>Proof of purchase <em class="org-pill-soft">Proof required</em></span>
                    <input type="file" name="receipt" accept="image/*,.pdf">
                </label>

                <button type="submit" class="org-btn org-btn-primary org-budget-submit">
                    <i class="bi bi-save2"></i> Save Expense
                </button>
            </form>
        </section>

        <section class="org-panel liquid-glass">
            <div class="org-panel-head">
                <h2><i class="bi bi-bar-chart-line-fill"></i> Activity Summary</h2>
                <span>{{ count($budget['activities']) }} ready</span>
            </div>

            <div class="org-budget-activity-list">
                @foreach ($budget['activities'] as $activity)
                    <details class="org-budget-activity" {{ $loop->first ? 'open' : '' }}>
                        <summary>
                            <strong>{{ $activity['title'] }}</strong>
                            <i class="bi bi-chevron-down"></i>
                        </summary>
                        <div class="org-budget-activity-body">
                            <div class="org-budget-activity-metrics">
                                <div>
                                    <small>Budget</small>
                                    <strong>₱{{ number_format($activity['budget'], 2) }}</strong>
                                </div>
                                <div>
                                    <small>Spent</small>
                                    <strong class="is-spent">₱{{ number_format($activity['spent'], 2) }}</strong>
                                </div>
                                <div>
                                    <small>Remaining</small>
                                    <strong class="is-remain">₱{{ number_format($activity['remaining'], 2) }}</strong>
                                </div>
                            </div>
                            <div class="org-budget-util-bar is-thin">
                                <span style="width: {{ min(100, $activity['percent']) }}%"></span>
                            </div>

                            <div class="org-budget-expense-head">
                                <span>Recent Expenses</span>
                                <a href="#">View All</a>
                            </div>
                            <ul class="org-budget-expense-list">
                                @foreach ($activity['expenses'] as $expense)
                                    <li>
                                        <div>
                                            <strong>{{ $expense['name'] }}</strong>
                                            <small>{{ $expense['date'] }} · Qty {{ $expense['qty'] }}</small>
                                        </div>
                                        <div class="org-budget-expense-meta">
                                            <em>₱{{ number_format($expense['total'], 2) }}</em>
                                            @if ($expense['receipt'])
                                                <span class="org-receipt-ok"><i class="bi bi-check-lg"></i> Receipt Attached</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </details>
                @endforeach
            </div>
        </section>
    </div>

    <div class="org-budget-tip liquid-glass">
        <p><i class="bi bi-lightbulb"></i> Tip: Make sure to upload clear receipts or proof of purchase for all expenses.</p>
        <a href="#">? Need Help?</a>
    </div>

    <script>
    (() => {
        const select = document.getElementById('budgetActivitySelect');
        const hint = document.getElementById('budgetRemainingHint');
        const form = document.getElementById('budgetExpenseForm');
        const resetBtn = document.getElementById('budgetResetBtn');

        const syncHint = () => {
            if (!select || !hint) return;
            const option = select.options[select.selectedIndex];
            const remaining = Number(option?.dataset?.remaining || 0);
            hint.textContent = `Remaining Budget: ₱${remaining.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        };

        select?.addEventListener('change', syncHint);
        resetBtn?.addEventListener('click', () => {
            form?.reset();
            syncHint();
        });
        syncHint();
    })();
    </script>
@endsection
