@once
    <link rel="stylesheet" href="{{ asset('css/document-preview.css') }}?v={{ filemtime(public_path('css/document-preview.css')) }}">
    <dialog class="document-preview-dialog" id="sharedDocumentPreviewDialog" data-document-preview-dialog data-document-conversion-url="{{ route('office.documents.preview.convert') }}" aria-labelledby="sharedDocumentPreviewTitle">
        <div class="document-preview-box">
            <header class="document-preview-head" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="document-preview-title">
                    <strong id="sharedDocumentPreviewTitle" data-document-preview-title>Document Preview</strong>
                    <small data-document-preview-status aria-live="polite">Original document</small>
                </div>
                <button type="button" class="document-preview-action" data-document-preview-close aria-label="Close preview" title="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i> Close
                </button>
            </header>
            <div class="document-preview-body" data-document-preview-body tabindex="0" aria-label="Document contents; scroll to read"></div>
            <footer class="document-preview-actions">
                <a class="document-preview-action" data-document-preview-external target="_blank" rel="noopener" hidden>Open in Tab</a>
                <a class="document-preview-action document-preview-action-primary" data-document-preview-download download>Download original</a>
            </footer>
        </div>
    </dialog>
    @push('scripts')
        <script src="{{ asset('js/vendor/jszip.min.js') }}"></script>
        <script src="{{ asset('js/vendor/docx-preview.min.js') }}"></script>
        <script src="{{ asset('js/document-preview.js') }}?v={{ filemtime(public_path('js/document-preview.js')) }}"></script>
    @endpush
@endonce
