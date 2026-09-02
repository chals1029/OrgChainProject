@extends('org.layout')

@php
    $role = $office->office_role ?? '';
    $isOso = $role === 'oso';
    $isSdo = $role === 'sdo';
    $isOvcaa = $role === 'ovcaa';
    $isSo = !$isOso && !$isSdo && !$isOvcaa;
    $canRecordExpense = $isSo;
@endphp

@section('title', 'Budget Utilization')

@section('header')
    <h1><strong>Budget Utilization</strong></h1>
    @if ($isOso)
        <p class="org-welcome">Monitor and review budget allocations, utilization rates, and expense logs across student organization activities.</p>
    @elseif ($isSdo)
        <p class="org-welcome">Monitor and track sustainability budget allocations, resource utilization, and activity financial records.</p>
    @elseif ($isOvcaa)
        <p class="org-welcome">Review overall university budget utilization and financial execution records across student activities.</p>
    @else
        <p class="org-welcome">Record and track actual expenses for each approved activity.</p>
    @endif
@endsection

@section('content')
    <style>
        .org-budget-page-grid {
            display: flex;
            flex-direction: column;
            gap: 1.4rem;
        }

        /* Top 3 Summary Cards */
        .org-budget-top-kpis {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }

        .org-bkpi-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .org-bkpi-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            margin-bottom: 0.25rem;
        }

        .org-bkpi-icon.is-gold { background: #fef9c3; color: #ca8a04; }
        .org-bkpi-icon.is-red { background: #fee2e2; color: #dc2626; }
        .org-bkpi-icon.is-green { background: #dcfce7; color: #16a34a; }

        .org-bkpi-label {
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .org-bkpi-card.is-alloc .org-bkpi-label { color: #ca8a04; }
        .org-bkpi-card.is-used .org-bkpi-label { color: #dc2626; }
        .org-bkpi-card.is-bal .org-bkpi-label { color: #16a34a; }

        .org-bkpi-amount {
            font-size: 1.95rem;
            font-weight: 800;
            color: #1a1618;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .org-bkpi-sub {
            font-size: 0.78rem;
            color: #7a7074;
            margin: 0;
        }

        /* Overall Budget Utilization Banner */
        .org-overall-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.25rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
        }

        .org-overall-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.92rem;
            font-weight: 700;
            color: #1a1618;
            margin-bottom: 0.65rem;
        }

        .org-overall-track {
            height: 8px;
            border-radius: 9999px;
            background: #f1e8e9;
            overflow: hidden;
            margin-bottom: 0.65rem;
        }

        .org-overall-fill {
            height: 100%;
            background: #7a1222;
            border-radius: 9999px;
        }

        .org-overall-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #7a7074;
        }

        /* Activity Budget Cards Section */
        .org-act-budget-section {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.35rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
        }

        .org-act-budget-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
        }

        .org-act-budget-header h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
        }

        .org-act-budget-cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.15rem;
        }

        .org-act-budget-card {
            border: 1.5px solid #f0e6e8;
            border-radius: 16px;
            padding: 1.15rem 1.25rem;
            background: #faf4f5;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .org-act-budget-card:hover {
            transform: translateY(-2px);
            border-color: #d8c2c7;
            box-shadow: 0 6px 18px rgba(90, 15, 30, 0.06);
        }

        .org-act-budget-card.is-active {
            background: #7a1222;
            border-color: #7a1222;
            color: #ffffff;
            box-shadow: 0 8px 24px rgba(122, 18, 34, 0.25);
        }

        .org-act-budget-card.is-active .org-act-card-title {
            color: #ffffff !important;
        }

        .org-act-budget-card.is-active .org-act-card-nums span,
        .org-act-budget-card.is-active .org-act-card-nums strong,
        .org-act-budget-card.is-active .org-act-card-footer span {
            color: #fce8eb !important;
        }

        .org-act-budget-card.is-active .org-act-card-nums strong {
            color: #ffffff !important;
        }

        .org-act-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .org-act-card-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
            line-height: 1.3;
        }

        .org-scope-badge-mini {
            padding: 0.15rem 0.45rem;
            border-radius: 6px;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            background: #ffffff;
            color: #7a1222;
            border: 1px solid #e8d0d4;
            flex-shrink: 0;
        }

        .org-act-budget-card.is-active .org-scope-badge-mini {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .org-act-card-nums {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #7a7074;
        }

        .org-act-card-nums strong {
            color: #1a1618;
            font-weight: 700;
        }

        .org-act-progress-track {
            height: 6px;
            background: #e8dedf;
            border-radius: 9999px;
            overflow: hidden;
        }

        .org-act-budget-card.is-active .org-act-progress-track {
            background: rgba(255, 255, 255, 0.25);
        }

        .org-act-progress-fill {
            height: 100%;
            border-radius: 9999px;
        }

        .org-act-progress-fill.is-green { background: #16a34a; }
        .org-act-progress-fill.is-amber { background: #ca8a04; }

        .org-act-budget-card.is-active .org-act-progress-fill {
            background: #ffffff !important;
        }

        .org-act-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #7a7074;
            font-weight: 600;
        }

        /* 4. Bottom 2-Column Split: Expense Log & Record New Expense Form */
        .org-budget-bottom-split {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            gap: 1.25rem;
        }

        .org-budget-bottom-split.is-oso-full {
            grid-template-columns: 1fr;
        }

        .org-budget-card-panel {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
            display: flex;
            flex-direction: column;
        }

        .org-panel-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.35rem;
        }

        .org-panel-header-row h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
        }

        .org-panel-budget-tag {
            font-size: 0.8rem;
            color: #7a7074;
        }

        .org-panel-budget-tag strong {
            color: #7a1222;
        }

        .org-panel-sub-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #7a7074;
            margin-bottom: 0.85rem;
            display: block;
        }

        .org-expense-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            margin-bottom: 1.25rem;
        }

        .org-expense-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1rem;
            background: #faf4f5;
            border-radius: 12px;
            border: 1px solid #f2e6e8;
        }

        .org-expense-item-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .org-expense-item-left i {
            color: #7a1222;
            font-size: 1.1rem;
        }

        .org-expense-item-info strong {
            display: block;
            font-size: 0.86rem;
            font-weight: 700;
            color: #1a1618;
        }

        .org-expense-item-info small {
            display: block;
            font-size: 0.74rem;
            color: #7a7074;
            margin-top: 0.1rem;
        }

        .org-expense-item-right {
            text-align: right;
        }

        .org-expense-item-right strong {
            display: block;
            font-size: 0.88rem;
            font-weight: 800;
            color: #1a1618;
        }

        .org-expense-item-right small {
            display: block;
            font-size: 0.72rem;
            color: #16a34a;
            font-weight: 600;
        }

        .org-expense-total-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 0.85rem;
            border-top: 1.5px solid #f0e6e8;
            font-size: 0.92rem;
            font-weight: 700;
            color: #1a1618;
            margin-top: auto;
        }

        .org-expense-total-row strong {
            font-size: 1.15rem;
            font-weight: 800;
            color: #7a1222;
        }

        /* Form Styles */
        .org-form-group {
            margin-bottom: 0.9rem;
        }

        .org-form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #4b4548;
            margin-bottom: 0.35rem;
        }

        .org-form-group input,
        .org-form-group select,
        .org-form-group textarea {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            border: 1px solid #d8c2c7;
            background: #ffffff;
            font-family: inherit;
            font-size: 0.86rem;
            color: #1a1618;
            transition: all 0.15s ease;
        }

        .org-form-group input:focus,
        .org-form-group select:focus,
        .org-form-group textarea:focus {
            outline: none;
            border-color: #7a1222;
            box-shadow: 0 0 0 3px rgba(122, 18, 34, 0.1);
        }

        .org-form-row-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem;
        }

        .org-upload-receipt-box {
            border: 1.5px dashed #d8c2c7;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            background: #faf4f5;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
        }

        .org-upload-receipt-box:hover {
            border-color: #7a1222;
            background: #f8eaec;
        }

        .org-upload-receipt-box i {
            font-size: 1.4rem;
            color: #7a1222;
        }

        .org-upload-receipt-box span {
            font-size: 0.78rem;
            font-weight: 600;
            color: #554d50;
        }

        .org-warning-box-yellow {
            background: #fefce8;
            border: 1px solid #fef08a;
            border-radius: 10px;
            padding: 0.65rem 0.85rem;
            font-size: 0.78rem;
            color: #854d0e;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .org-btn-save-expense {
            padding: 0.75rem 1.5rem;
            background: #7a1222;
            color: #ffffff;
            border: none;
            border-radius: 9999px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 14px rgba(122, 18, 34, 0.25);
            transition: all 0.15s ease;
        }

        .org-btn-save-expense:hover {
            background: #600e1b;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(122, 18, 34, 0.35);
        }
    </style>

    <div class="org-budget-page-grid">
        {{-- 1. Top 3 Summary Cards --}}
        <div class="org-budget-top-kpis">
            <article class="org-bkpi-card is-alloc">
                <div class="org-bkpi-icon is-gold">
                    <i class="bi bi-wallet2"></i>
                </div>
                <span class="org-bkpi-label">Total Allocated Budget</span>
                <span class="org-bkpi-amount">₱185,000</span>
                <p class="org-bkpi-sub">AY 2025-2026 · 1st Semester</p>
            </article>

            <article class="org-bkpi-card is-used">
                <div class="org-bkpi-icon is-red">
                    <i class="bi bi-receipt"></i>
                </div>
                <span class="org-bkpi-label">Total Utilized</span>
                <span class="org-bkpi-amount">₱115,150</span>
                <p class="org-bkpi-sub">Across all 5 activities (62.2%)</p>
            </article>

            <article class="org-bkpi-card is-bal">
                <div class="org-bkpi-icon is-green">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <span class="org-bkpi-label">Remaining Balance</span>
                <span class="org-bkpi-amount">₱69,850</span>
                <p class="org-bkpi-sub">Available allocation (37.8%)</p>
            </article>
        </div>

        {{-- 2. Overall Budget Utilization Banner --}}
        <section class="org-overall-card">
            <div class="org-overall-head">
                <span>Overall Budget Utilization</span>
                <strong>62.2%</strong>
            </div>
            <div class="org-overall-track">
                <div class="org-overall-fill" style="width: 62.2%;"></div>
            </div>
            <div class="org-overall-footer">
                <span>₱115,150 utilized of ₱185,000</span>
                <span>₱69,850 remaining</span>
            </div>
        </section>

        {{-- 3. Activity Budget Cards Section --}}
        <section class="org-act-budget-section">
            <div class="org-act-budget-header">
                <h3>Activity Budget Cards</h3>
                <span style="font-size: 0.8rem; color: #7a7074;">Select an activity to view expense logs</span>
            </div>

            <div class="org-act-budget-cards-grid">
                {{-- Card 1: Innovation Fair Booth Series (Active by default) --}}
                <article class="org-act-budget-card is-active" id="actCard-innovation" onclick="selectActivityBudget('innovation')">
                    <div class="org-act-card-head">
                        <h4 class="org-act-card-title" style="color: #ffffff;">Innovation Fair Booth ...</h4>
                        <span class="org-scope-badge-mini">IC</span>
                    </div>
                    <div class="org-act-card-nums">
                        <span>Allocated <strong>₱15,000</strong></span>
                        <span>Used <strong>₱15,000</strong></span>
                    </div>
                    <div class="org-act-progress-track">
                        <div class="org-act-progress-fill is-green" style="width: 100%;"></div>
                    </div>
                    <div class="org-act-card-footer">
                        <span>₱0 left</span>
                        <span>100%</span>
                    </div>
                </article>

                {{-- Card 2: Volunteer Appreciation Day --}}
                <article class="org-act-budget-card" id="actCard-volunteer" onclick="selectActivityBudget('volunteer')">
                    <div class="org-act-card-head">
                        <h4 class="org-act-card-title" style="color: #1a1618;">Volunteer Appreciati...</h4>
                        <span class="org-scope-badge-mini">IC</span>
                    </div>
                    <div class="org-act-card-nums">
                        <span>Allocated <strong>₱12,500</strong></span>
                        <span>Used <strong>₱12,500</strong></span>
                    </div>
                    <div class="org-act-progress-track">
                        <div class="org-act-progress-fill is-green" style="width: 100%;"></div>
                    </div>
                    <div class="org-act-card-footer">
                        <span>₱0 left</span>
                        <span>100%</span>
                    </div>
                </article>

                {{-- Card 3: Campus Wellness Week --}}
                <article class="org-act-budget-card" id="actCard-wellness" onclick="selectActivityBudget('wellness')">
                    <div class="org-act-card-head">
                        <h4 class="org-act-card-title" style="color: #1a1618;">Campus Wellness ...</h4>
                        <span class="org-scope-badge-mini">IC</span>
                    </div>
                    <div class="org-act-card-nums">
                        <span>Allocated <strong>₱42,500</strong></span>
                        <span>Used <strong>₱24,900</strong></span>
                    </div>
                    <div class="org-act-progress-track">
                        <div class="org-act-progress-fill is-amber" style="width: 58.5%;"></div>
                    </div>
                    <div class="org-act-card-footer">
                        <span>₱17,600 left</span>
                        <span>58.5%</span>
                    </div>
                </article>
            </div>
        </section>

        {{-- 4. Bottom Section: Expense Log (Full width on OSO / SDO / OVCAA) & Record New Expense Form (Only for Student Org) --}}
        <div class="org-budget-bottom-split {{ !$canRecordExpense ? 'is-oso-full' : '' }}">
            {{-- Left: Expense Log --}}
            <section class="org-budget-card-panel">
                <div class="org-panel-header-row">
                    <h3>Expense Log</h3>
                    <span class="org-panel-budget-tag" id="expenseLogBudget">Budget <strong>₱15,000</strong></span>
                </div>
                <span class="org-panel-sub-label" id="expenseLogTitle">Innovation Fair Booth Series</span>

                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.78rem; font-weight: 700; color: #554d50; margin-bottom: 0.35rem;">
                    <span id="expenseLogUsed">Used ₱15,000 (100%)</span>
                    <span id="expenseLogLeft">Left ₱0</span>
                </div>
                <div class="org-overall-track" style="margin-bottom: 1.15rem;">
                    <div class="org-overall-fill" id="expenseLogProgressFill" style="width: 100%; background: #16a34a;"></div>
                </div>

                <div class="org-expense-list" id="expenseLogItems">
                    <div class="org-expense-item">
                        <div class="org-expense-item-left">
                            <i class="bi bi-file-earmark-text"></i>
                            <div class="org-expense-item-info">
                                <strong>AV Equipment Rental</strong>
                                <small>Jul 3, 2026 · Equipment · Qty 1</small>
                            </div>
                        </div>
                        <div class="org-expense-item-right">
                            <strong>₱8,000</strong>
                            <small><i class="bi bi-check2"></i> Receipt</small>
                        </div>
                    </div>

                    <div class="org-expense-item">
                        <div class="org-expense-item-left">
                            <i class="bi bi-file-earmark-text"></i>
                            <div class="org-expense-item-info">
                                <strong>Printing – Booth Backdrops</strong>
                                <small>Jul 2, 2026 · Supplies · Qty 5</small>
                            </div>
                        </div>
                        <div class="org-expense-item-right">
                            <strong>₱4,500</strong>
                            <small><i class="bi bi-check2"></i> Receipt</small>
                        </div>
                    </div>

                    <div class="org-expense-item">
                        <div class="org-expense-item-left">
                            <i class="bi bi-file-earmark-text"></i>
                            <div class="org-expense-item-info">
                                <strong>Snacks &amp; Refreshments</strong>
                                <small>Jul 4, 2026 · Food &amp; Beverage · Qty 50</small>
                            </div>
                        </div>
                        <div class="org-expense-item-right">
                            <strong>₱2,500</strong>
                            <small><i class="bi bi-check2"></i> Receipt</small>
                        </div>
                    </div>
                </div>

                <div class="org-expense-total-row">
                    <span>Total Logged</span>
                    <strong id="expenseLogTotal">₱15,000</strong>
                </div>
            </section>

            {{-- Right: Record New Expense Form (Only for Student Org) --}}
            @if ($canRecordExpense)
            <section class="org-budget-card-panel">
                <div class="org-panel-header-row" style="margin-bottom: 1.15rem;">
                    <h3>Record New Expense</h3>
                    <span class="org-chip" id="formActivityChip" style="background: #fdf0f2; color: #8b1828; font-weight: 700; font-size: 0.74rem;">
                        Innovation Fair Booth Series
                    </span>
                </div>

                <form onsubmit="event.preventDefault(); alert('Expense record saved successfully!');">
                    <div class="org-form-group">
                        <label>Select Activity *</label>
                        <select id="expenseFormActivitySelect" required onchange="handleDropdownActivityChange(this.value)">
                            <option value="innovation">Innovation Fair Booth Series</option>
                            <option value="volunteer">Volunteer Appreciation Day</option>
                            <option value="wellness">Campus Wellness Week</option>
                        </select>
                    </div>

                    <div class="org-form-group">
                        <label>Merchant / Item Name *</label>
                        <input type="text" required placeholder="e.g. Portable sound system rental">
                    </div>

                    <div class="org-form-row-2col">
                        <div class="org-form-group">
                            <label>Category *</label>
                            <select required>
                                <option value="Select Category" selected>Select Category</option>
                                <option value="Equipment">Equipment</option>
                                <option value="Supplies">Supplies</option>
                                <option value="Food & Beverage">Food &amp; Beverage</option>
                                <option value="Transportation">Transportation</option>
                            </select>
                        </div>
                        <div class="org-form-group">
                            <label>Expense Date *</label>
                            <input type="date" required value="2026-09-02">
                        </div>
                    </div>

                    <div class="org-form-row-2col">
                        <div class="org-form-group">
                            <label>Quantity *</label>
                            <input type="number" min="1" value="1" required>
                        </div>
                        <div class="org-form-group">
                            <label>Unit Cost (₱) *</label>
                            <input type="number" min="0" step="0.01" value="0.00" required>
                        </div>
                    </div>

                    <div class="org-form-group">
                        <label>Notes / Description</label>
                        <textarea rows="2" placeholder="Optional description of this expense..."></textarea>
                    </div>

                    <div class="org-form-group">
                        <label style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Proof of Purchase</span>
                            <span class="org-chip" style="background: #fee2e2; color: #dc2626; font-size: 0.68rem; font-weight: 700;">Required</span>
                        </label>
                        <div class="org-upload-receipt-box" onclick="document.getElementById('receiptFileInput').click()">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <span id="receiptFileName">Upload receipt image (JPG, PNG, PDF)</span>
                        </div>
                        <input type="file" id="receiptFileInput" style="display: none;" onchange="if(this.files[0]) document.getElementById('receiptFileName').textContent = this.files[0].name;">
                    </div>

                    <div class="org-warning-box-yellow" id="formBudgetWarning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span id="formWarningText">Budget fully utilized. Additional expenses require OSO approval.</span>
                    </div>

                    <button type="submit" class="org-btn-save-expense" style="width: 100%;">
                        <i class="bi bi-download"></i> Save Expense Entry
                    </button>
                </form>
            </section>
            @endif
        </div>
    </div>

    <script>
        const activityBudgetData = {
            innovation: {
                name: 'Innovation Fair Booth Series',
                budget: '₱15,000',
                used: '₱15,000 (100%)',
                left: '₱0',
                percent: 100,
                color: '#16a34a',
                totalLogged: '₱15,000',
                warning: 'Budget fully utilized. Additional expenses require OSO approval.',
                items: [
                    { title: 'AV Equipment Rental', meta: 'Jul 3, 2026 · Equipment · Qty 1', amount: '₱8,000' },
                    { title: 'Printing – Booth Backdrops', meta: 'Jul 2, 2026 · Supplies · Qty 5', amount: '₱4,500' },
                    { title: 'Snacks & Refreshments', meta: 'Jul 4, 2026 · Food & Beverage · Qty 50', amount: '₱2,500' }
                ]
            },
            volunteer: {
                name: 'Volunteer Appreciation Day',
                budget: '₱12,500',
                used: '₱12,500 (100%)',
                left: '₱0',
                percent: 100,
                color: '#16a34a',
                totalLogged: '₱12,500',
                warning: 'Budget fully utilized. Additional expenses require OSO approval.',
                items: [
                    { title: 'Catering & Packed Meals', meta: 'Aug 10, 2026 · Food & Beverage · Qty 60', amount: '₱7,500' },
                    { title: 'Tokens & Certificates', meta: 'Aug 8, 2026 · Supplies · Qty 45', amount: '₱3,200' },
                    { title: 'Venue Décor & Ribbons', meta: 'Aug 9, 2026 · Supplies · Qty 1', amount: '₱1,800' }
                ]
            },
            wellness: {
                name: 'Campus Wellness Week',
                budget: '₱42,500',
                used: '₱24,900 (58.5%)',
                left: '₱17,600',
                percent: 58.5,
                color: '#d97706',
                totalLogged: '₱24,900',
                warning: '₱17,600 remaining allocation available for encoding.',
                items: [
                    { title: 'Sound System & Stage Lights', meta: 'Sep 1, 2026 · Equipment · Qty 1', amount: '₱14,500' },
                    { title: 'Water Stations & Energy Snacks', meta: 'Sep 2, 2026 · Food & Beverage · Qty 120', amount: '₱6,400' },
                    { title: 'Event T-Shirts for Facilitators', meta: 'Aug 28, 2026 · Supplies · Qty 30', amount: '₱4,000' }
                ]
            }
        };

        function selectActivityBudget(key) {
            const data = activityBudgetData[key];
            if (!data) return;

            // 1. Update Card Active Classes
            ['innovation', 'volunteer', 'wellness'].forEach(k => {
                const card = document.getElementById('actCard-' + k);
                const title = card ? card.querySelector('.org-act-card-title') : null;
                if (card) {
                    if (k === key) {
                        card.classList.add('is-active');
                        if (title) title.style.color = '#ffffff';
                    } else {
                        card.classList.remove('is-active');
                        if (title) title.style.color = '#1a1618';
                    }
                }
            });

            // 2. Update Expense Log Panel
            const budgetEl = document.getElementById('expenseLogBudget');
            if (budgetEl) budgetEl.innerHTML = `Budget <strong>${data.budget}</strong>`;
            const titleEl = document.getElementById('expenseLogTitle');
            if (titleEl) titleEl.textContent = data.name;
            const usedEl = document.getElementById('expenseLogUsed');
            if (usedEl) usedEl.textContent = `Used ${data.used}`;
            const leftEl = document.getElementById('expenseLogLeft');
            if (leftEl) leftEl.textContent = `Left ${data.left}`;
            
            const fill = document.getElementById('expenseLogProgressFill');
            if (fill) {
                fill.style.width = data.percent + '%';
                fill.style.background = data.color;
            }

            const itemsContainer = document.getElementById('expenseLogItems');
            if (itemsContainer) {
                itemsContainer.innerHTML = data.items.map(item => `
                    <div class="org-expense-item">
                        <div class="org-expense-item-left">
                            <i class="bi bi-file-earmark-text"></i>
                            <div class="org-expense-item-info">
                                <strong>${item.title}</strong>
                                <small>${item.meta}</small>
                            </div>
                        </div>
                        <div class="org-expense-item-right">
                            <strong>${item.amount}</strong>
                            <small><i class="bi bi-check2"></i> Receipt</small>
                        </div>
                    </div>
                `).join('');
            }

            const totalEl = document.getElementById('expenseLogTotal');
            if (totalEl) totalEl.textContent = data.totalLogged;

            // 3. Update Record New Expense Form if present
            const chipEl = document.getElementById('formActivityChip');
            if (chipEl) chipEl.textContent = data.name;
            const select = document.getElementById('expenseFormActivitySelect');
            if (select) select.value = key;
            const warnEl = document.getElementById('formWarningText');
            if (warnEl) warnEl.textContent = data.warning;
        }

        function handleDropdownActivityChange(key) {
            selectActivityBudget(key);
        }
    </script>
@endsection
