@extends('org.layout')

@section('title', 'Financial Report')

@section('header')
    <h1>Financial Report</h1>
    <p class="org-welcome">Welcome, {{ $brand['role'] }}</p>
@endsection

@section('actions')
    <button type="button" class="org-btn org-btn-primary" onclick="window.print()">
        <i class="bi bi-printer"></i> Print Report
    </button>
@endsection

@section('content')
    <section class="org-budget-hero liquid-glass">
        <div class="org-budget-hero-copy">
            <p class="org-eyebrow">Semester compilation</p>
            <h2>Financial Report</h2>
            <p>
                Compile saved Budget Utilization entries into a transparent semester financial report.
                Choose the semester and academic year below, then review account balance highlights before submission.
            </p>
        </div>
        <div class="org-budget-hero-stats">
            <article>
                <span>FR attachments</span>
                <strong>{{ count($frAttachments) }}</strong>
            </article>
            <article class="is-utilized">
                <span>Current cash</span>
                <strong>Php {{ number_format($account['current_cash'], 2) }}</strong>
            </article>
        </div>
    </section>

    <section class="org-panel liquid-glass org-period-panel">
        <div class="org-panel-head">
            <h2><i class="bi bi-calendar2-range"></i> Report Period</h2>
        </div>
        <form method="get" action="{{ route('office.financial') }}" class="org-period-form">
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
            <h2><i class="bi bi-safe2"></i> Account Balance Overview</h2>
            <span>Highlighted for FR</span>
        </div>
        <div class="org-balance-grid">
            <article class="org-balance-card is-cash">
                <div class="org-budget-kpi-icon" aria-hidden="true"><i class="bi bi-cash-stack"></i></div>
                <p class="org-eyebrow">Current cash</p>
                <strong>Php {{ number_format($account['current_cash'], 2) }}</strong>
                <small>Available balance after collections and disbursements</small>
            </article>
            <article class="org-balance-card is-collection">
                <div class="org-budget-kpi-icon" aria-hidden="true"><i class="bi bi-arrow-down-circle"></i></div>
                <p class="org-eyebrow">Total cash collection</p>
                <strong>Php {{ number_format($account['total_cash_collection'], 2) }}</strong>
                <small>All cash inflows recorded this period</small>
            </article>
            <article class="org-balance-card is-disburse">
                <div class="org-budget-kpi-icon" aria-hidden="true"><i class="bi bi-credit-card-2-front"></i></div>
                <p class="org-eyebrow">Total card disbursement</p>
                <strong>Php {{ number_format($account['total_card_disbursement'], 2) }}</strong>
                <small>Card / voucher releases against activities</small>
            </article>
        </div>
    </section>

    <section class="org-budget-kpis">
        <article class="liquid-glass">
            <div class="org-budget-kpi-icon" aria-hidden="true"><i class="bi bi-piggy-bank"></i></div>
            <p class="org-eyebrow">Allocated</p>
            <strong>Php {{ number_format($budget['allocated'], 2) }}</strong>
            <small>Total proposed budgets in this report period</small>
        </article>
        <article class="liquid-glass">
            <div class="org-budget-kpi-icon" aria-hidden="true"><i class="bi bi-cash-coin"></i></div>
            <p class="org-eyebrow">Utilized</p>
            <strong>Php {{ number_format($budget['used'], 2) }}</strong>
            <small>Actual expenses ready for OSO review</small>
        </article>
        <article class="liquid-glass">
            <div class="org-budget-kpi-icon" aria-hidden="true"><i class="bi bi-paperclip"></i></div>
            <p class="org-eyebrow">FR attachments</p>
            <strong>{{ count($frAttachments) }}</strong>
            <small>Supporting files included with this report</small>
        </article>
    </section>

    <section class="org-panel liquid-glass org-financial-meta">
        <div class="org-financial-meta-grid">
            <div>
                <small>Organization</small>
                <strong>{{ $brand['title'] }}</strong>
            </div>
            <div>
                <small>Prepared by</small>
                <strong>{{ $office->name }}</strong>
            </div>
            <div>
                <small>Semester</small>
                <strong>{{ $selectedSemester }}</strong>
            </div>
            <div>
                <small>Academic Year</small>
                <strong>{{ $selectedYear }}</strong>
            </div>
        </div>
    </section>

    <div class="org-budget-split">
        <section class="org-panel liquid-glass">
            <div class="org-panel-head">
                <h2><i class="bi bi-collection"></i> Per-Activity Totals</h2>
                <span>{{ count($budget['activities']) }} ready</span>
            </div>
            <ul class="org-financial-activity-totals">
                @foreach ($budget['activities'] as $activity)
                    <li>
                        <div>
                            <strong>{{ $activity['title'] }}</strong>
                            <small>{{ count($activity['expenses']) }} expense line(s) · {{ $activity['percent'] }}% used</small>
                        </div>
                        <div class="org-financial-activity-nums">
                            <span>Budget ₱{{ number_format($activity['budget'], 2) }}</span>
                            <em>Spent ₱{{ number_format($activity['spent'], 2) }}</em>
                        </div>
                        <div class="org-budget-util-bar is-thin">
                            <span style="width: {{ min(100, $activity['percent']) }}%"></span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="org-panel liquid-glass">
            <div class="org-panel-head">
                <h2><i class="bi bi-paperclip"></i> FR Attachments</h2>
                <span>{{ count($frAttachments) }} files</span>
            </div>
            <ul class="org-attachment-list">
                @foreach ($frAttachments as $file)
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
            <div class="org-financial-actions" style="margin-top: 0.85rem;">
                <a href="{{ route('office.budget') }}" class="org-btn org-btn-primary" style="justify-content: center;">
                    <i class="bi bi-pencil-square"></i> Update Budget Utilization
                </a>
                <button type="button" class="org-btn org-btn-primary" style="justify-content: center;" onclick="window.print()">
                    <i class="bi bi-download"></i> Download / Print PDF
                </button>
            </div>
        </section>
    </div>

    <section class="org-panel liquid-glass org-financial-table-wrap">
        <div class="org-panel-head">
            <h2><i class="bi bi-table"></i> Expense Ledger</h2>
            <span>{{ count($lines) }} entries · Generated {{ $generatedAt }}</span>
        </div>
        <div class="org-financial-table-scroll">
            <table class="org-financial-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Activity</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Amount</th>
                        <th>Proof</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lines as $line)
                        <tr>
                            <td>{{ $line['date'] }}</td>
                            <td>{{ $line['activity'] }}</td>
                            <td>{{ $line['item'] }}</td>
                            <td>{{ $line['qty'] }}</td>
                            <td>₱{{ number_format($line['total'], 2) }}</td>
                            <td>
                                @if ($line['receipt'])
                                    <span class="org-receipt-ok"><i class="bi bi-check-lg"></i> Attached</span>
                                @else
                                    <span class="org-receipt-missing">Missing</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No encoded expenses yet. Add items in Budget Utilization first.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Grand total</td>
                        <td colspan="2">₱{{ number_format($budget['used'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <div class="org-budget-tip liquid-glass">
        <p><i class="bi bi-shield-check"></i> Tip: Submit this Financial Report only after account balance figures and FR attachments are complete for the selected semester and academic year.</p>
        <a href="{{ route('office.budget') }}">Go to Budget Utilization</a>
    </div>
@endsection
