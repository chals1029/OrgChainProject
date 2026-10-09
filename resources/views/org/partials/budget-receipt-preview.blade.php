<style>
    body.budget-receipt-preview-open { overflow: hidden; }
    .budget-receipt-dialog { box-sizing: border-box; position: fixed; inset: 0; margin: auto; border: 0; border-radius: 16px; padding: 0; width: min(1040px, calc(100vw - 2rem)); height: min(88dvh, calc(100dvh - 2rem)); overflow: hidden; background: #fff; box-shadow: 0 24px 80px rgba(35, 15, 23, .25); animation: none; transform: none; }
    .budget-receipt-dialog::backdrop { background: rgba(35, 15, 23, .45); }
    .budget-receipt-preview { display: flex; flex-direction: column; height: 100%; min-height: 0; }
    .budget-receipt-head { display: flex; align-items: center; flex-wrap: wrap; gap: .75rem; padding: 1rem; border-bottom: 1px solid #eee5e8; }
    .budget-receipt-heading { flex: 1 1 18rem; min-width: 0; }
    .budget-receipt-heading h2 { margin: 0; font-size: 1rem; overflow-wrap: anywhere; }
    .budget-receipt-heading p { margin: .3rem 0 0; color: #786f73; font-size: .8rem; overflow-wrap: anywhere; }
    .budget-receipt-actions { display: flex; align-items: center; flex-wrap: wrap; gap: .5rem; }
    .budget-receipt-actions a[aria-disabled="true"] { opacity: .45; pointer-events: none; }
    .budget-receipt-files { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem; border-bottom: 1px solid #eee5e8; }
    .budget-receipt-files select { flex: 1; min-width: 0; padding: .5rem; border: 1px solid #e7dce0; border-radius: 8px; background: #fff; }
    .budget-receipt-files span { white-space: nowrap; color: #786f73; font-size: .8rem; }
    .budget-receipt-body { flex: 1 1 auto; min-height: 0; overflow: auto; background: #e9e6e5; }
    .budget-receipt-body > iframe { display: block; width: 100%; height: 100%; border: 0; }
    .budget-receipt-body:has(> img) { display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .budget-receipt-body > img { display: block; max-width: 100%; max-height: 100%; object-fit: contain; }
    .budget-receipt-body .docx-wrapper { min-width: fit-content; padding: 1rem !important; background: transparent !important; }
    .budget-receipt-body section.docx { margin: 0 auto 1rem !important; }
    .budget-receipt-message { display: grid; place-items: center; height: 100%; margin: 0; padding: 1.5rem; text-align: center; }
    @media (max-width: 640px) {
        .budget-receipt-dialog { width: calc(100vw - 1rem); height: calc(100dvh - 1rem); }
        .budget-receipt-head, .budget-receipt-files { padding: .75rem; }
    }
</style>
<dialog id="budgetReceiptPreviewDialog" class="budget-receipt-dialog" aria-labelledby="budgetReceiptPreviewTitle" aria-describedby="budgetReceiptPreviewContext">
    <div class="budget-receipt-preview">
        <header class="budget-receipt-head">
            <div class="budget-receipt-heading">
                <h2 id="budgetReceiptPreviewTitle">Receipt Preview</h2>
                <p id="budgetReceiptPreviewContext"></p>
            </div>
            <div class="budget-receipt-actions">
                <a id="budgetReceiptPreviewDownload" class="org-file-action-btn" download><i class="bi bi-download"></i> Download Original</a>
                <a id="budgetReceiptPreviewExternal" class="org-file-action-btn" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Open in Tab</a>
                <button type="button" id="budgetReceiptPreviewClose" class="org-file-action-btn" aria-label="Close receipt preview" autofocus><i class="bi bi-x-lg"></i></button>
            </div>
        </header>
        <nav class="budget-receipt-files" aria-label="Receipt attachments">
            <button type="button" id="budgetReceiptPreviewPrevious" class="org-file-action-btn" aria-label="Previous receipt file"><i class="bi bi-chevron-left"></i></button>
            <select id="budgetReceiptPreviewFile" aria-label="Select receipt file"></select>
            <span id="budgetReceiptPreviewPosition" aria-live="polite"></span>
            <button type="button" id="budgetReceiptPreviewNext" class="org-file-action-btn" aria-label="Next receipt file"><i class="bi bi-chevron-right"></i></button>
        </nav>
        <div id="budgetReceiptPreviewBody" class="budget-receipt-body"></div>
    </div>
</dialog>
<script src="{{ asset('js/vendor/jszip.min.js') }}" defer></script>
<script src="{{ asset('js/vendor/docx-preview.min.js') }}" defer></script>
<script src="{{ asset('js/budget-receipt-preview.js') }}?v={{ filemtime(public_path('js/budget-receipt-preview.js')) }}" defer></script>
