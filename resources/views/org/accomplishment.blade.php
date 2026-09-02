@extends('org.layout')

@section('title', 'Accomplishment Report')

@section('header')
    <h1><strong>Accomplishment Report</strong></h1>
        <p class="org-welcome">Document planned objectives, completed tasks, and key project outcomes</p>
@endsection

@section('actions')
    <button type="button" class="org-btn-create-folder" onclick="openCreateFolderModal()">
        <i class="bi bi-plus-lg"></i> Create Folder
    </button>
@endsection

@section('content')
    <style>
        .org-acc-kicker {
            font-size: 0.92rem;
            color: #4b4548;
            font-weight: 600;
            margin: 0 0 0.15rem;
        }

        .org-acc-page-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .org-btn-create-folder {
            background: #6a1020;
            color: #ffffff;
            padding: 0.6rem 1.4rem;
            border-radius: 9999px;
            font-size: 0.88rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            box-shadow: 0 4px 14px rgba(106, 16, 32, 0.25);
            transition: all 0.15s ease;
        }

        .org-btn-create-folder:hover {
            background: #8b1828;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(106, 16, 32, 0.35);
        }

        /* Standard Accomplishment Panel Card */
        .org-acc-panel-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1.5px solid #f0e6e8;
            padding: 1.5rem 1.85rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 6px 24px rgba(90, 15, 30, 0.03);
        }

        .org-acc-panel-head {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1618;
            margin-bottom: 1.15rem;
        }

        .org-acc-panel-head i {
            color: #8b1828;
            font-size: 1.15rem;
        }

        /* Period Selector Form */
        .org-period-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .org-period-field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #635b5e;
            margin-bottom: 0.4rem;
        }

        .org-period-select-wrap {
            position: relative;
            width: 100%;
        }

        .org-period-select {
            width: 100%;
            padding: 0.7rem 2.25rem 0.7rem 1rem;
            border-radius: 14px;
            border: 1.5px solid #e8dedf;
            background: #ffffff;
            font-size: 0.92rem;
            font-weight: 600;
            color: #1a1618;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            box-sizing: border-box;
            transition: all 0.15s ease;
        }

        .org-period-select:focus {
            border-color: #8b1828;
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.08);
        }

        .org-period-chevron {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.78rem;
            color: #7a7074;
            pointer-events: none;
        }

        /* Folders List */
        .org-acc-folders-list {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .org-acc-folder-box {
            border-radius: 18px;
            border: 1.5px solid #f0e6e8;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(90, 15, 30, 0.02);
            transition: all 0.2s ease;
        }

        .org-acc-folder-box:hover {
            border-color: #e2d2d5;
            box-shadow: 0 6px 18px rgba(90, 15, 30, 0.04);
        }

        .org-acc-folder-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.35rem;
            background: #ffffff;
            cursor: pointer;
            gap: 1rem;
            flex-wrap: wrap;
            transition: background 0.15s ease;
        }

        .org-acc-folder-box.is-open .org-acc-folder-header {
            background: #faf4f5;
            border-bottom: 1px solid #f5eaec;
        }

        .org-acc-folder-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .org-folder-graphic {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .org-acc-folder-title-row {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.2rem;
        }

        .org-acc-folder-title-row strong {
            font-size: 1.02rem;
            font-weight: 700;
            color: #1a1618;
        }

        .org-folder-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #8b1828;
            display: inline-block;
        }

        .org-acc-folder-period {
            font-size: 0.84rem;
            color: #635b5e;
            margin: 0;
            font-weight: 500;
        }

        .org-btn-upload-doc {
            background: #6a1020;
            color: #ffffff;
            padding: 0.5rem 1.25rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.82rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
            box-shadow: 0 3px 10px rgba(106, 16, 32, 0.2);
            text-decoration: none;
        }

        .org-btn-upload-doc:hover {
            background: #8b1828;
            transform: translateY(-1px);
            box-shadow: 0 5px 14px rgba(106, 16, 32, 0.3);
        }

        .org-acc-folder-files {
            padding: 0.85rem 1.35rem 1.15rem 4.75rem;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .org-acc-file-row {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            padding: 0.25rem 0;
        }

        .org-acc-file-icon {
            font-size: 1.35rem;
            color: #635b5e;
            margin-top: -0.1rem;
            flex-shrink: 0;
        }

        .org-acc-file-meta strong {
            display: block;
            font-size: 0.9rem;
            font-weight: 700;
            color: #1a1618;
            margin-bottom: 0.15rem;
        }

        .org-acc-file-meta small {
            display: block;
            font-size: 0.8rem;
            color: #7a7074;
            font-weight: 500;
        }

        /* Modal Overlay for Creating Folder */
        .org-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(26, 10, 13, 0.45);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .org-modal-box {
            background: #ffffff;
            border-radius: 24px;
            border: 1.5px solid #f0e6e8;
            width: 100%;
            max-width: 480px;
            padding: 1.75rem 2rem;
            box-shadow: 0 16px 40px rgba(90, 15, 30, 0.18);
        }

        .org-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #f6eff0;
        }

        .org-modal-head h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a1618;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .org-modal-close-btn {
            background: transparent;
            border: none;
            font-size: 1.2rem;
            color: #7a7074;
            cursor: pointer;
        }

        .org-modal-close-btn:hover {
            color: #1a1618;
        }

        .org-modal-field {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            margin-bottom: 1rem;
        }

        .org-modal-field label {
            font-size: 0.84rem;
            font-weight: 700;
            color: #3b3336;
        }

        .org-modal-field input,
        .org-modal-field select {
            padding: 0.65rem 0.95rem;
            border-radius: 12px;
            border: 1.5px solid #e8dedf;
            font-size: 0.9rem;
            outline: none;
        }

        .org-modal-field input:focus,
        .org-modal-field select:focus {
            border-color: #8b1828;
        }

        .org-modal-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #f6eff0;
        }

        @media (max-width: 768px) {
            .org-period-grid {
                grid-template-columns: 1fr;
            }
            .org-acc-folder-files {
                padding-left: 1.5rem;
            }
        }
    </style>

    {{-- Card 1: Report Period (Matching Mockup) --}}
    <section class="org-acc-panel-card">
        <div class="org-acc-panel-head">
            <i class="bi bi-calendar2-range"></i>
            <span>Report Period</span>
        </div>

        <form method="get" action="{{ route('office.accomplishment') }}" class="org-period-grid">
            <div class="org-period-field">
                <label>Semester</label>
                <div class="org-period-select-wrap">
                    <select name="semester" class="org-period-select" onchange="this.form.submit()">
                        @foreach ($semesters as $option)
                            <option value="{{ $option }}" @selected($selectedSemester === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down org-period-chevron"></i>
                </div>
            </div>

            <div class="org-period-field">
                <label>Academic Year</label>
                <div class="org-period-select-wrap">
                    <select name="academic_year" class="org-period-select" onchange="this.form.submit()">
                        @foreach ($academicYears as $option)
                            <option value="{{ $option }}" @selected($selectedYear === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down org-period-chevron"></i>
                </div>
            </div>
        </form>
    </section>

    {{-- Card 2: Accomplishment Folders (Matching Mockup) --}}
    @php
        // Frontend-side fallback so the page never breaks if the controller
        // does not (yet) supply a dedicated $accomplishmentFolders payload.
        $accomplishmentFolders = $accomplishmentFolders ?? [
            [
                'id' => 1,
                'name' => $selectedSemester.' Folder',
                'period' => 'AY '.$selectedYear,
                'is_expanded' => true,
                'files' => collect($arAttachments ?? [])
                    ->map(fn ($file) => [
                        'name' => $file['name'],
                        'period' => 'AY '.$selectedYear,
                    ])
                    ->all(),
            ],
        ];
    @endphp
    <section class="org-acc-panel-card">
        <div class="org-acc-panel-head">
            <i class="bi bi-folder2"></i>
            <span>Accomplishment Folders</span>
        </div>

        <div class="org-acc-folders-list" id="accomplishmentFoldersList">
            @foreach ($accomplishmentFolders as $folder)
                <div class="org-acc-folder-box {{ !empty($folder['is_expanded']) ? 'is-open' : '' }}" id="folderBox{{ $folder['id'] }}">
                    <div class="org-acc-folder-header" onclick="toggleFolder({{ $folder['id'] }})">
                        <div class="org-acc-folder-left">
                            <div class="org-folder-graphic">
                                <svg width="44" height="36" viewBox="0 0 48 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 8C4 5.79086 5.79086 4 8 4H18.4C19.7824 4 21.0827 4.67086 21.8744 5.79545L23.4589 8.04545C24.2505 9.17005 25.5508 9.84091 26.9332 9.84091H40C42.2091 9.84091 44 11.6318 44 13.8409V32C44 34.2091 42.2091 36 40 36H8C5.79086 36 4 34.2091 4 32V8Z" fill="#E2B765"/>
                                    <path d="M4 14C4 11.7909 5.79086 10 8 10H40C42.2091 10 44 11.7909 44 14V32C44 34.2091 42.2091 36 40 36H8C5.79086 36 4 34.2091 4 32V14Z" fill="#F5D38A"/>
                                </svg>
                            </div>
                            <div>
                                <div class="org-acc-folder-title-row">
                                    <strong>{{ $folder['name'] }}</strong>
                                    <span class="org-folder-dot"></span>
                                </div>
                                <p class="org-acc-folder-period">{{ $folder['period'] }}</p>
                            </div>
                        </div>

                        <button type="button" class="org-btn-upload-doc" onclick="event.stopPropagation(); triggerUploadDoc('{{ $folder['name'] }}')">
                            <i class="bi bi-plus-lg"></i> Upload Related Documents
                        </button>
                    </div>

                    @if (!empty($folder['files']) && count($folder['files']) > 0)
                        <div class="org-acc-folder-files" id="folderFiles{{ $folder['id'] }}">
                            @foreach ($folder['files'] as $file)
                                <div class="org-acc-file-row">
                                    <i class="bi bi-file-earmark-pdf org-acc-file-icon"></i>
                                    <div class="org-acc-file-meta">
                                        <strong>{{ $file['name'] }}</strong>
                                        <small>{{ $file['period'] }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Create Folder Modal --}}
    <div class="org-modal-overlay" id="createFolderModal">
        <div class="org-modal-box">
            <div class="org-modal-head">
                <h3><i class="bi bi-folder-plus" style="color: #8b1828;"></i> Create New Folder</h3>
                <button type="button" class="org-modal-close-btn" onclick="closeCreateFolderModal()">&times;</button>
            </div>
            <form onsubmit="handleCreateFolderSubmit(event)">
                <div class="org-modal-field">
                    <label>Folder Name *</label>
                    <input type="text" id="newFolderName" required placeholder="e.g., Folder: 1st Semester">
                </div>
                <div class="org-modal-field">
                    <label>Semester</label>
                    <select id="newFolderSemester">
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Midyear">Midyear</option>
                    </select>
                </div>
                <div class="org-modal-field">
                    <label>Academic Year</label>
                    <select id="newFolderAY">
                        <option value="AY 2025-2026">AY 2025-2026</option>
                        <option value="AY 2026-2027">AY 2026-2027</option>
                        <option value="AY 2024-2025">AY 2024-2025</option>
                    </select>
                </div>
                <div class="org-modal-actions">
                    <button type="button" class="org-btn org-btn-ghost" onclick="closeCreateFolderModal()">Cancel</button>
                    <button type="submit" class="org-btn org-btn-primary">Create Folder</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleFolder(folderId) {
            const box = document.getElementById('folderBox' + folderId);
            const files = document.getElementById('folderFiles' + folderId);
            if (box) {
                box.classList.toggle('is-open');
                if (files) {
                    files.style.display = box.classList.contains('is-open') ? 'flex' : 'none';
                }
            }
        }

        function triggerUploadDoc(folderName) {
            alert('Upload document dialog opened for ' + folderName);
        }

        function openCreateFolderModal() {
            const modal = document.getElementById('createFolderModal');
            if (modal) modal.style.display = 'flex';
        }

        function closeCreateFolderModal() {
            const modal = document.getElementById('createFolderModal');
            if (modal) modal.style.display = 'none';
        }

        function handleCreateFolderSubmit(e) {
            e.preventDefault();
            const name = document.getElementById('newFolderName').value;
            const sem = document.getElementById('newFolderSemester').value;
            const ay = document.getElementById('newFolderAY').value;
            
            alert('Folder "' + name + '" for ' + sem + ' - ' + ay + ' created successfully!');
            closeCreateFolderModal();
        }
    </script>
@endsection
