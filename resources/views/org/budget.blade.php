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

    @if (session('success'))
        <div class="org-alert org-alert-success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif

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

            <form class="org-budget-form" id="budgetExpenseForm" method="post" action="{{ route('office.budget.receipts.store') }}" enctype="multipart/form-data">
                @csrf
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
                    <span>Merchant / Item Name</span>
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
                    <input type="file" name="receipt" id="receiptUpload" accept="image/*,.pdf">
                </label>

                <div class="org-receipt-verification" id="receiptVerification" aria-live="polite">
                    <div class="org-receipt-verification-icon"><i class="bi bi-receipt"></i></div>
                    <div>
                        <strong id="receiptVerificationTitle">Receipt verification</strong>
                        <p id="receiptVerificationText">Upload a clear receipt image to scan and pre-fill the expense details.</p>
                        <small id="receiptVerificationMeta">Scanning runs locally in your browser. Review all detected information before submitting.</small>
                    </div>
                </div>
                <label class="org-receipt-confirm" id="receiptReviewConfirm" hidden>
                    <input type="checkbox" name="receipt_reviewed" value="1">
                    <span>I reviewed the detected receipt details and confirm they are correct.</span>
                </label>
                <input type="hidden" name="ocr_confidence" id="ocrConfidence">
                <input type="hidden" name="receipt_detected" id="receiptDetected" value="0">
                @if ($errors->any())
                    <div class="org-alert"><i class="bi bi-exclamation-triangle-fill"></i> {{ $errors->first() }}</div>
                @endif

                <button type="submit" class="org-btn org-btn-primary org-budget-submit">
                    <i class="bi bi-save2"></i> Save Expense for Review
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

    @if ($receiptReviews->isNotEmpty())
        <section class="org-panel liquid-glass org-receipt-review-list">
            <div class="org-panel-head">
                <h2><i class="bi bi-clipboard2-check-fill"></i> Receipt verification queue</h2>
                <span>{{ $receiptReviews->count() }} submitted</span>
            </div>
            <div class="org-receipt-review-items">
                @foreach ($receiptReviews as $review)
                    <article>
                        <span class="org-receipt-review-icon"><i class="bi bi-receipt-cutoff"></i></span>
                        <div>
                            <strong>{{ $review->item_name }}</strong>
                            <p>{{ $review->activity_title }} · {{ $review->expense_date->format('M j, Y') }} · Qty {{ $review->quantity }}</p>
                            <small><i class="bi bi-paperclip"></i> <a href="{{ asset('storage/'.$review->receipt_path) }}" target="_blank" rel="noopener">{{ $review->receipt_name }}</a></small>
                        </div>
                        <div>
                            <strong>₱{{ number_format((float) $review->unit_cost * $review->quantity, 2) }}</strong>
                            <span class="org-receipt-review-status"><i class="bi bi-hourglass-split"></i> Ready for review</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <div class="org-budget-tip liquid-glass">
        <p><i class="bi bi-lightbulb"></i> Tip: Make sure to upload clear receipts or proof of purchase for all expenses.</p>
        <a href="#">? Need Help?</a>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js" defer></script>
    <script>
    (() => {
        const select = document.getElementById('budgetActivitySelect');
        const hint = document.getElementById('budgetRemainingHint');
        const form = document.getElementById('budgetExpenseForm');
        const resetBtn = document.getElementById('budgetResetBtn');
        const receiptUpload = document.getElementById('receiptUpload');
        const verification = document.getElementById('receiptVerification');
        const verificationTitle = document.getElementById('receiptVerificationTitle');
        const verificationText = document.getElementById('receiptVerificationText');
        const verificationMeta = document.getElementById('receiptVerificationMeta');
        const reviewConfirm = document.getElementById('receiptReviewConfirm');
        const reviewCheckbox = reviewConfirm?.querySelector('input');
        const ocrConfidence = document.getElementById('ocrConfidence');
        const receiptDetectedInput = document.getElementById('receiptDetected');
        const itemName = form?.elements.namedItem('item_name');
        const unitCost = form?.elements.namedItem('unit_cost');
        const expenseDate = form?.elements.namedItem('expense_date');
        const quantity = form?.elements.namedItem('quantity');

        const syncHint = () => {
            if (!select || !hint) return;
            const option = select.options[select.selectedIndex];
            const remaining = Number(option?.dataset?.remaining || 0);
            hint.textContent = `Remaining Budget: ₱${remaining.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        };

        const setVerification = (state, title, text, meta = '') => {
            if (!verification) return;
            verification.dataset.state = state;
            verificationTitle.textContent = title;
            verificationText.textContent = text;
            verificationMeta.textContent = meta;
        };

        const isReceiptContentValid = (text, confidence) => {
            if (!text || text.trim().length < 8) return false;
            if (confidence !== null && confidence < 12) return false;

            const hasKeyword = /(receipt|invoice|official|total|subtotal|amount|due|cash|change|vat|tax|tin|or#|item|qty|price|sales|vendor|merchant|store|store#|php|₱|\b\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}\b)/i.test(text);
            const hasPrice = /(?:₱|PHP|Php|P)?\s*[0-9]{1,3}(?:,[0-9]{3})*(?:\.\d{2})|[0-9]+\.\d{2}/i.test(text);

            return hasKeyword || hasPrice;
        };

        const parseReceipt = (text) => {
            const lines = text.split(/\r?\n/).map(line => line.trim()).filter(Boolean);
            const merchant = lines.find(line => (
                /[a-z]/i.test(line)
                && !/(receipt|invoice|date|time|cashier|total|change|thank you|vat|tel|tin)/i.test(line)
                && line.length > 2
            ));
            const dateMatch = text.match(/\b(\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}|\d{4}[\/.-]\d{1,2}[\/.-]\d{1,2}|(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\.?\s+\d{1,2},?\s+\d{2,4})\b/i);
            const totalLine = lines.find(line => /(grand\s*total|total\s*(due|amount|sale)?|amount\s*due|net\s*amount)/i.test(line));
            const getAmount = (value = '') => {
                const matches = [...value.matchAll(/(?:₱|PHP|Php|P)?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.\d{2})|[0-9]+\.\d{2})/gi)];
                return matches.length ? Number(matches.at(-1)[1].replace(/,/g, '')) : null;
            };
            const amount = getAmount(totalLine) ?? Math.max(
                0,
                ...[...text.matchAll(/(?:₱|PHP|Php|P)\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.\d{2})|[0-9]+\.\d{2})/gi)]
                    .map(match => Number(match[1].replace(/,/g, '')))
            );
            return { merchant, date: dateMatch?.[1] ?? null, amount: amount || null };
        };

        const normalizeDate = (value) => {
            if (!value) return null;
            const parsed = new Date(value);
            return Number.isNaN(parsed.getTime()) ? null : parsed.toISOString().slice(0, 10);
        };

        const checkCompleteness = (confidence = null) => {
            if (!receiptUpload?.files?.length) return;
            if (receiptDetectedInput && receiptDetectedInput.value === "0") return;

            const missing = [];
            if (!itemName?.value.trim()) missing.push('merchant or item name');
            if (!Number(unitCost?.value)) missing.push('amount');
            if (!expenseDate?.value) missing.push('expense date');

            if (missing.length) {
                reviewConfirm.hidden = true;
                setVerification('needs-review', 'Needs review', `Complete the missing receipt details: ${missing.join(', ')}.`, 'OCR is a helper only. Check the receipt before submitting.');
                return;
            }

            reviewConfirm.hidden = false;
            const confidenceNote = confidence === null ? '' : ` OCR confidence: ${Math.round(confidence)}%.`;
            if (ocrConfidence && confidence !== null) ocrConfidence.value = Math.round(confidence);
            setVerification('complete', 'Complete — ready for your review', 'Required receipt details are filled in. Please compare them with the original receipt, then confirm below.', `The system cannot guarantee accuracy.${confidenceNote}`);
        };

        const scanReceipt = async (file) => {
            reviewConfirm.hidden = true;

            if (file.type === 'application/pdf') {
                if (receiptDetectedInput) receiptDetectedInput.value = "1";
                setVerification('needs-review', 'PDF proof attached', 'Enter the receipt details from this PDF manually.', 'You can submit the PDF as official proof of purchase.');
                return;
            }

            if (!file.type.startsWith('image/')) {
                if (receiptDetectedInput) receiptDetectedInput.value = "0";
                setVerification('rejected', 'REJECTED — Unsupported file format', 'Upload a clear receipt image (JPG, PNG) or PDF.', 'Form submission is disabled until a valid receipt file is provided.');
                return;
            }

            if (!window.Tesseract) {
                if (receiptDetectedInput) receiptDetectedInput.value = "1";
                setVerification('needs-review', 'Scanner unavailable', 'The local receipt scanner could not load. Enter the details manually.', 'Check your internet connection and refresh to load the scanner.');
                return;
            }

            setVerification('scanning', 'Scanning receipt…', 'Reading the receipt image and looking for merchant, date, and total.', 'Keep this page open while the scan finishes.');
            try {
                const { data } = await window.Tesseract.recognize(file, 'eng');
                const text = data.text || '';
                const confidence = data.confidence || 0;

                const isValidReceipt = isReceiptContentValid(text, confidence);
                if (!isValidReceipt) {
                    if (receiptDetectedInput) receiptDetectedInput.value = "0";
                    reviewConfirm.hidden = true;
                    setVerification('rejected', 'REJECTED — No receipt detected', 'The uploaded file does not contain a recognizable receipt or proof of purchase.', 'Please upload a clear, legible receipt photo or official invoice image.');
                    return;
                }

                if (receiptDetectedInput) receiptDetectedInput.value = "1";
                const detected = parseReceipt(text);
                if (!itemName?.value.trim() && detected.merchant) itemName.value = detected.merchant;
                if (!unitCost?.value && detected.amount) {
                    unitCost.value = detected.amount.toFixed(2);
                    if (quantity && !quantity.value) quantity.value = 1;
                }
                if (!expenseDate?.value && detected.date) {
                    const date = normalizeDate(detected.date);
                    if (date) expenseDate.value = date;
                }
                checkCompleteness(confidence);
            } catch (error) {
                if (receiptDetectedInput) receiptDetectedInput.value = "0";
                setVerification('rejected', 'REJECTED — Receipt scan failed', 'The uploaded file could not be parsed as a valid receipt.', 'Upload a clear, uncorrupted receipt image or PDF.');
            }
        };

        select?.addEventListener('change', syncHint);
        receiptUpload?.addEventListener('change', () => {
            reviewCheckbox.checked = false;
            if (ocrConfidence) ocrConfidence.value = '';
            const file = receiptUpload.files?.[0];
            if (file) scanReceipt(file);
        });
        [itemName, unitCost, expenseDate].forEach((input) => input?.addEventListener('input', () => checkCompleteness()));
        resetBtn?.addEventListener('click', () => {
            form?.reset();
            reviewConfirm.hidden = true;
            if (receiptDetectedInput) receiptDetectedInput.value = "0";
            setVerification('idle', 'Receipt verification', 'Upload a clear receipt image to scan and pre-fill the expense details.', 'Scanning runs locally in your browser. Review all detected information before submitting.');
            syncHint();
        });
        form?.addEventListener('submit', (event) => {
            if (!receiptUpload?.files?.length) {
                event.preventDefault();
                setVerification('rejected', 'REJECTED — Receipt file required', 'Please attach a proof of purchase before saving the expense.', 'Upload a clear receipt image or PDF file.');
                alert('Expense submission rejected: Please upload a receipt file.');
                return;
            }

            if (receiptDetectedInput && receiptDetectedInput.value === "0") {
                event.preventDefault();
                setVerification('rejected', 'REJECTED — No receipt detected', 'Submission blocked: The uploaded file was rejected because no valid receipt was detected.', 'Re-upload a clear receipt image or PDF to proceed.');
                alert('Expense submission rejected: No valid receipt detected in the uploaded file.');
                return;
            }

            if (!reviewCheckbox?.checked) {
                event.preventDefault();
                setVerification('needs-review', 'Review required', 'Confirm that the scanned receipt details match the original receipt before submitting.', 'Edit any incorrect details, then tick the confirmation box.');
            }
        });
        syncHint();
    })();
    </script>
    @endpush
@endsection
