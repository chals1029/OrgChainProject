<link rel="stylesheet" href="{{ asset('css/tosa-workbook-preview.css') }}?v={{ filemtime(public_path('css/tosa-workbook-preview.css')) }}">
<dialog class="document-preview-dialog tosa-workbook-dialog" data-tosa-workbook-dialog aria-labelledby="tosaWorkbookTitle">
    <div class="document-preview-box">
        <header class="document-preview-head" style="display:flex;align-items:center;justify-content:space-between;">
            <div class="document-preview-heading">
                <span class="document-preview-icon" aria-hidden="true"><i class="bi bi-file-earmark-spreadsheet"></i></span>
                <div class="document-preview-title"><strong id="tosaWorkbookTitle">TOSA COMPUTATION</strong><small>Read-only workbook preview</small></div>
            </div>
            <button type="button" class="document-preview-action" data-workbook-close aria-label="Close workbook preview"><i class="bi bi-x-lg" aria-hidden="true"></i> Close</button>
        </header>
        <div class="tosa-workbook-main">
            <p class="tosa-workbook-note">Read-only, simplified layout. Formula cells show saved values and are not recalculated. Download the original to edit or calculate.</p>
            <div class="tosa-workbook-tabs" role="tablist" aria-label="Workbook worksheets" data-workbook-tabs></div>
            <p class="tosa-workbook-status" role="status" aria-live="polite" data-workbook-status>Loading workbook…</p>
            <div class="tosa-workbook-grid" id="tosaWorkbookSheet" role="tabpanel" aria-label="Worksheet cells" tabindex="0" data-workbook-grid></div>
            <nav class="tosa-workbook-pages" aria-label="Worksheet pages">
                <button type="button" class="document-preview-action" data-workbook-page="row-prev">Previous rows</button>
                <button type="button" class="document-preview-action" data-workbook-page="row-next">Next rows</button>
                <button type="button" class="document-preview-action" data-workbook-page="column-prev">Previous columns</button>
                <button type="button" class="document-preview-action" data-workbook-page="column-next">Next columns</button>
            </nav>
        </div>
        <footer class="document-preview-actions">
            <a class="document-preview-action document-preview-action-primary" data-workbook-download href="#">Download original</a>
        </footer>
    </div>
</dialog>
<script src="{{ asset('js/tosa-workbook-preview.js') }}?v={{ filemtime(public_path('js/tosa-workbook-preview.js')) }}" defer></script>
