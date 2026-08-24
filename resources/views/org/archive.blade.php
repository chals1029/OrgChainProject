@extends('org.layout')

@section('title', 'Archive')

@section('header')
    <h1>Archive</h1>
    <p class="org-welcome">Browse and manage archived documents by organization</p>
@endsection

@section('actions')
    <button type="button" class="org-btn org-btn-primary" data-open-dialog="newFolderDialog"><i class="bi bi-folder-plus"></i> New Folder</button>
    <button type="button" class="org-btn org-btn-primary" data-open-dialog="uploadDocumentDialog"><i class="bi bi-cloud-upload"></i> Upload</button>
@endsection

@section('content')
    @if (session('success'))
        <div class="org-alert org-alert-success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <section class="org-stats">
        <article class="org-stat-card liquid-glass">
            <span class="org-stat-icon is-red"><i class="bi bi-files"></i></span>
            <strong>{{ $totalDocuments }}</strong>
            <span>Total Documents</span>
            <small>Across all folders</small>
        </article>
        <article class="org-stat-card liquid-glass">
            <span class="org-stat-icon is-gold"><i class="bi bi-folder-fill"></i></span>
            <strong>{{ $totalFolders }}</strong>
            <span>Folders</span>
            <small>Organizations archived</small>
        </article>
        <article class="org-stat-card liquid-glass">
            <span class="org-stat-icon is-green"><i class="bi bi-calendar-check"></i></span>
            <strong>{{ $currentSemester }}</strong>
            <span>Current Semester</span>
            <small>Active archive period</small>
        </article>
    </section>

    {{-- Search & Filter --}}
    <section class="org-panel liquid-glass">
        <div class="org-archive-toolbar">
            <div class="org-search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="archiveSearchInput" placeholder="Search folders and documents..." class="org-search-input">
            </div>
        </div>
        <div class="org-archive-filters">
            <div class="org-filter-group">
                <label><i class="bi bi-building"></i> Organization</label>
                <select class="org-select" id="orgSelectFilter">
                    <option value="">All Organizations</option>
                    @foreach ($folders as $folder)
                        <option value="{{ $folder['name'] }}">{{ $folder['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="org-filter-group">
                <label><i class="bi bi-funnel"></i> Semester Filter</label>
                <select class="org-select" id="semesterSelectFilter">
                    <option value="">All Semesters</option>
                    <option value="1st Semester">1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                    <option value="Midyear">Midyear</option>
                </select>
            </div>
        </div>
    </section>

    {{-- Folder Grid --}}
    <div class="org-folder-grid" id="archiveFolderGrid">
        @foreach ($folders as $folder)
            <article class="org-folder-card liquid-glass" data-folder-name="{{ $folder['name'] }}" data-folder-semester="{{ $folder['semester'] }}" title="Click to open folder">
                <div class="org-folder-icon is-{{ $folder['color'] }}">
                    <i class="bi bi-{{ $folder['icon'] }}"></i>
                </div>
                <div class="org-folder-info">
                    <strong>{{ $folder['name'] }}</strong>
                    <small>{{ $folder['org'] }}</small>
                </div>
                <div class="org-folder-meta">
                    <span class="org-chip">{{ $folder['semester'] }}</span>
                    <small class="doc-count-badge">{{ $folder['documents'] }} documents</small>
                </div>
            </article>
        @endforeach
    </div>

    {{-- Documents List --}}
    <section class="org-panel liquid-glass" id="archiveDocumentsPanel">
        <div class="org-panel-head">
            <h2><i class="bi bi-folder2-open"></i> <span id="activeFolderName">All Documents</span></h2>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="resetFolderFilterBtn" style="display: none;">
                    <i class="bi bi-x-circle"></i> Show All Documents
                </button>
                <button type="button" class="org-btn org-btn-primary org-btn-sm" data-open-dialog="uploadDocumentDialog">
                    <i class="bi bi-cloud-upload"></i> Upload
                </button>
            </div>
        </div>
        <ul class="org-doc-list" id="archiveDocList">
            @foreach ($documents as $doc)
                <li class="org-doc-item" data-doc-folder="{{ $doc['folder_name'] ?? '' }}" data-doc-name="{{ strtolower($doc['name']) }}" data-doc-author="{{ strtolower($doc['author'] ?? '') }}">
                    <div class="org-doc-icon">
                        @if ($doc['type'] === 'PDF')
                            <i class="bi bi-file-earmark-pdf-fill" style="color: var(--org-red);"></i>
                        @elseif ($doc['type'] === 'XLSX')
                            <i class="bi bi-file-earmark-spreadsheet-fill" style="color: var(--org-green);"></i>
                        @else
                            <i class="bi bi-file-earmark-fill"></i>
                        @endif
                    </div>
                    <div class="org-doc-info">
                        <strong>{{ $doc['name'] }}</strong>
                        <small>{{ $doc['size'] }} · {{ $doc['date'] }} · by {{ $doc['author'] }} @if(!empty($doc['folder_name'])) · <em style="color: var(--org-red);">{{ $doc['folder_name'] }}</em> @endif</small>
                    </div>
                    @if (!empty($doc['url']))
                        <a class="org-btn-icon" href="{{ $doc['url'] }}" title="Download" download><i class="bi bi-download"></i></a>
                    @else
                        <button type="button" class="org-btn-icon" title="Demo document downloaded sample"><i class="bi bi-download"></i></button>
                    @endif
                </li>
            @endforeach
        </ul>
        <div id="noDocsNotice" style="display: none; padding: 2rem; text-align: center; color: var(--org-muted);">
            <i class="bi bi-folder-x" style="font-size: 2.2rem; display: block; margin-bottom: 0.5rem;"></i>
            <p>No documents found in this folder.</p>
        </div>
    </section>

    <dialog class="org-archive-dialog" id="newFolderDialog">
        <form method="post" action="{{ route('office.archive.folders.store') }}" class="org-archive-form">
            @csrf
            <div class="org-dialog-head"><h2><i class="bi bi-folder-plus"></i> Create archive folder</h2><button type="button" data-close-dialog aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
            <label><span>Folder name</span><input name="name" required maxlength="255" placeholder="e.g., Leadership Summit 2026"></label>
            <label><span>Organization name</span><input name="organization_name" required maxlength="255" placeholder="e.g., Supreme Student Council"></label>
            <div class="org-archive-form-row">
                <label><span>Semester</span><select name="semester"><option>1st Semester</option><option selected>2nd Semester</option><option>Midyear</option></select></label>
                <label><span>Folder color</span><select name="color"><option value="red">Crimson</option><option value="blue">Blue</option><option value="green">Green</option><option value="violet">Violet</option><option value="gold">Gold</option></select></label>
            </div>
            <div class="org-dialog-actions"><button type="button" class="org-btn org-btn-ghost" data-close-dialog>Cancel</button><button class="org-btn org-btn-primary"><i class="bi bi-folder-plus"></i> Create folder</button></div>
        </form>
    </dialog>

    <dialog class="org-archive-dialog" id="uploadDocumentDialog">
        <form method="post" action="{{ route('office.archive.documents.store') }}" enctype="multipart/form-data" class="org-archive-form">
            @csrf
            <div class="org-dialog-head"><h2><i class="bi bi-cloud-upload"></i> Upload archive document</h2><button type="button" data-close-dialog aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
            @if ($savedFolders->isEmpty())
                <div class="org-alert"><i class="bi bi-info-circle-fill"></i> Create an archive folder before uploading a document.</div>
            @else
                <label><span>Archive folder</span><select name="archive_folder_id" required>@foreach ($savedFolders as $folder)<option value="{{ $folder['id'] }}">{{ $folder['name'] }} · {{ $folder['org'] }}</option>@endforeach</select></label>
                <label><span>Document title <small>(optional)</small></span><input name="name" maxlength="255" placeholder="Defaults to the uploaded filename"></label>
                <label><span>Choose document</span><input type="file" name="document" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg"><small>PDF, Office files, ZIP, JPG, or PNG · Maximum 20 MB</small></label>
                <div class="org-dialog-actions"><button type="button" class="org-btn org-btn-ghost" data-close-dialog>Cancel</button><button class="org-btn org-btn-primary"><i class="bi bi-cloud-upload"></i> Upload document</button></div>
            @endif
        </form>
    </dialog>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const folderCards = document.querySelectorAll('.org-folder-card');
            const docItems = document.querySelectorAll('.org-doc-item');
            const activeFolderName = document.getElementById('activeFolderName');
            const resetFolderFilterBtn = document.getElementById('resetFolderFilterBtn');
            const searchInput = document.getElementById('archiveSearchInput');
            const orgSelect = document.getElementById('orgSelectFilter');
            const semesterSelect = document.getElementById('semesterSelectFilter');
            const noDocsNotice = document.getElementById('noDocsNotice');
            const documentsPanel = document.getElementById('archiveDocumentsPanel');

            let activeFolder = null;

            const filterItems = () => {
                const searchVal = searchInput?.value.trim().toLowerCase() || '';
                const selectedOrg = orgSelect?.value || '';
                const selectedSemester = semesterSelect?.value || '';

                // Filter folders
                folderCards.forEach(card => {
                    const name = card.dataset.folderName.toLowerCase();
                    const sem = card.dataset.folderSemester;
                    const matchesSearch = !searchVal || name.includes(searchVal);
                    const matchesOrg = !selectedOrg || card.dataset.folderName === selectedOrg;
                    const matchesSem = !selectedSemester || sem === selectedSemester;

                    card.style.display = (matchesSearch && matchesOrg && matchesSem) ? '' : 'none';
                });

                // Filter documents
                let visibleCount = 0;
                docItems.forEach(doc => {
                    const docFolder = doc.dataset.docFolder || '';
                    const docName = doc.dataset.docName || '';
                    const docAuthor = doc.dataset.docAuthor || '';

                    const matchesFolder = !activeFolder || docFolder === activeFolder;
                    const matchesOrg = !selectedOrg || docFolder === selectedOrg;
                    const matchesSearch = !searchVal || docName.includes(searchVal) || docAuthor.includes(searchVal) || docFolder.toLowerCase().includes(searchVal);

                    const isVisible = matchesFolder && matchesOrg && matchesSearch;
                    doc.style.display = isVisible ? '' : 'none';
                    if (isVisible) visibleCount++;
                });

                if (noDocsNotice) {
                    noDocsNotice.style.display = visibleCount === 0 ? 'block' : 'none';
                }
            };

            const selectFolder = (folderName) => {
                activeFolder = folderName;
                folderCards.forEach(card => {
                    const isMatch = card.dataset.folderName === folderName;
                    card.classList.toggle('is-active', isMatch);
                });

                if (folderName) {
                    activeFolderName.textContent = `Documents in: ${folderName}`;
                    resetFolderFilterBtn.style.display = 'inline-flex';
                    if (orgSelect) orgSelect.value = folderName;
                } else {
                    activeFolderName.textContent = 'All Documents';
                    resetFolderFilterBtn.style.display = 'none';
                    if (orgSelect) orgSelect.value = '';
                }

                filterItems();
                documentsPanel?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            };

            folderCards.forEach(card => {
                card.addEventListener('click', () => {
                    const name = card.dataset.folderName;
                    if (activeFolder === name) {
                        selectFolder(null);
                    } else {
                        selectFolder(name);
                    }
                });
            });

            resetFolderFilterBtn?.addEventListener('click', () => selectFolder(null));
            orgSelect?.addEventListener('change', (e) => selectFolder(e.target.value || null));
            semesterSelect?.addEventListener('change', filterItems);
            searchInput?.addEventListener('input', filterItems);

            document.querySelectorAll('[data-open-dialog]').forEach((button) => button.addEventListener('click', () => document.getElementById(button.dataset.openDialog)?.showModal()));
            document.querySelectorAll('[data-close-dialog]').forEach((button) => button.addEventListener('click', () => button.closest('dialog')?.close()));
        });
    </script>
@endpush
