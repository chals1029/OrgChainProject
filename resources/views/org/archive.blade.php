@extends('org.layout')

@section('title', $currentFolder ? $currentFolder->name . ' — Archive Vault' : 'Archive Vault')

@section('header')
    <h1><strong>Archive Vault &amp; Depository</strong></h1>
    <p class="org-welcome">Official permanent institutional repository for student organization proposals, financial liquidations, accomplishment reports, and compliance archives.</p>
@endsection

@section('actions')
    <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
        <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="openNewFolderModal()">
            <i class="bi bi-folder-plus"></i> {{ $currentFolder ? 'New Subfolder' : 'New Folder' }}
        </button>
        <button type="button" class="org-btn org-btn-primary org-btn-sm" onclick="openUploadDocumentModal()">
            <i class="bi bi-cloud-upload-fill"></i> Upload Document
        </button>
    </div>
@endsection

@section('content')
    <style>
        /* ==========================================================================
           Google Drive-Style Archive Vault System
           ========================================================================== */
        :root {
            --g-maroon: #8b1828;
            --g-maroon-dark: #62101c;
            --g-maroon-light: #fdf0f2;
            --g-maroon-border: #f2dfe2;
            --g-ink-dark: #1e293b;
            --g-ink-body: #334155;
            --g-ink-muted: #64748b;
            --g-border: #e2e8f0;
            --g-border-subtle: #f1f5f9;
            --g-bg-card: #ffffff;
            --g-bg-hover: #f8fafc;
            --g-radius-lg: 18px;
            --g-radius-md: 12px;
            --g-radius-sm: 8px;
            --g-shadow-sm: 0 2px 8px rgba(15, 23, 42, 0.04);
            --g-shadow-md: 0 6px 20px rgba(15, 23, 42, 0.07);
            --g-shadow-hover: 0 10px 28px rgba(139, 24, 40, 0.09);
        }

        /* 1. Summary KPI Bar */
        .gdrive-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .gdrive-stat-card {
            background: #ffffff;
            border: 1.5px solid var(--g-border);
            border-radius: var(--g-radius-lg);
            padding: 1.1rem 1.3rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--g-shadow-sm);
            transition: all 0.2s ease;
        }

        .gdrive-stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--g-maroon-border);
            box-shadow: var(--g-shadow-md);
        }

        .gdrive-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .gdrive-stat-icon.is-red { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .gdrive-stat-icon.is-gold { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .gdrive-stat-icon.is-green { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .gdrive-stat-icon.is-blue { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }

        .gdrive-stat-meta strong {
            display: block;
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--g-ink-dark);
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .gdrive-stat-meta span {
            font-size: 0.78rem;
            color: var(--g-ink-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* 2. Google Drive Navigation Toolbar & Breadcrumbs Bar */
        .gdrive-nav-panel {
            background: #ffffff;
            border: 1.5px solid var(--g-border);
            border-radius: var(--g-radius-lg);
            padding: 1rem 1.4rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--g-shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .gdrive-breadcrumb-trail {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: wrap;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--g-ink-dark);
            min-width: 0;
        }

        .gdrive-level-up-btn {
            background: #f1f5f9;
            color: var(--g-ink-dark);
            border: 1px solid var(--g-border);
            border-radius: var(--g-radius-sm);
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            margin-right: 0.35rem;
            flex-shrink: 0;
        }

        .gdrive-level-up-btn:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
            transform: translateX(-2px);
        }

        .gdrive-crumb-item {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--g-ink-muted);
            text-decoration: none;
            padding: 0.35rem 0.65rem;
            border-radius: 8px;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .gdrive-crumb-item:hover {
            color: var(--g-maroon);
            background: var(--g-maroon-light);
        }

        .gdrive-crumb-item.is-active {
            color: var(--g-maroon);
            font-weight: 800;
            background: var(--g-maroon-light);
            border: 1px solid var(--g-maroon-border);
        }

        .gdrive-crumb-divider {
            color: #cbd5e1;
            font-size: 0.85rem;
            user-select: none;
        }

        .gdrive-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .gdrive-search-box {
            position: relative;
            min-width: 240px;
        }

        .gdrive-search-box i {
            position: absolute;
            left: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--g-ink-muted);
            font-size: 0.9rem;
            pointer-events: none;
        }

        .gdrive-search-input {
            width: 100%;
            border: 1.5px solid var(--g-border);
            border-radius: 9999px;
            padding: 0.5rem 1rem 0.5rem 2.3rem;
            font-size: 0.86rem;
            font-weight: 600;
            color: var(--g-ink-dark);
            background: #f8fafc;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .gdrive-search-input:focus {
            background: #ffffff;
            border-color: var(--g-maroon);
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.08);
        }

        .gdrive-view-toggle-group {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 2px;
            gap: 2px;
        }

        .gdrive-view-btn {
            background: transparent;
            border: none;
            width: 34px;
            height: 32px;
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--g-ink-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .gdrive-view-btn:hover { color: var(--g-ink-dark); }

        .gdrive-view-btn.is-active {
            background: #ffffff;
            color: var(--g-maroon);
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
        }

        /* 3. Main Container Card */
        .gdrive-main-card {
            background: #ffffff;
            border: 1.5px solid var(--g-border);
            border-radius: var(--g-radius-lg);
            padding: 1.75rem;
            margin-bottom: 2rem;
            box-shadow: var(--g-shadow-md);
        }

        .gdrive-section-title-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .gdrive-section-heading {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--g-ink-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            letter-spacing: -0.01em;
        }

        .gdrive-badge-count {
            font-size: 0.75rem;
            font-weight: 700;
            background: #f1f5f9;
            color: var(--g-ink-muted);
            padding: 0.15rem 0.55rem;
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
        }

        /* 4. Google Drive Folder Grid */
        .gdrive-folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
            margin-bottom: 2.25rem;
        }

        .gdrive-folder-card {
            background: #ffffff;
            border: 1.5px solid var(--g-border);
            border-radius: var(--g-radius-md);
            padding: 1rem 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.18s ease;
            position: relative;
            box-shadow: var(--g-shadow-sm);
        }

        .gdrive-folder-card:hover {
            transform: translateY(-2px);
            border-color: var(--g-maroon);
            background: #faf7f8;
            box-shadow: var(--g-shadow-hover);
        }

        .gdrive-folder-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .gdrive-folder-card:hover .gdrive-folder-icon {
            transform: scale(1.08);
        }

        .gdrive-folder-icon.is-red { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .gdrive-folder-icon.is-blue { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .gdrive-folder-icon.is-green { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .gdrive-folder-icon.is-violet { background: #faf5ff; color: #9333ea; border: 1px solid #e9d5ff; }
        .gdrive-folder-icon.is-gold { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }

        .gdrive-folder-info {
            flex: 1;
            min-width: 0;
        }

        .gdrive-folder-name {
            display: block;
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--g-ink-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }

        .gdrive-folder-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.74rem;
            color: var(--g-ink-muted);
            margin-top: 0.25rem;
            font-weight: 600;
        }

        .gdrive-folder-subfolders {
            color: var(--g-maroon);
            font-weight: 700;
        }

        /* 5. Google Drive Document Cards (Grid View) */
        .gdrive-file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 1.15rem;
        }

        .gdrive-file-card {
            background: #ffffff;
            border: 1.5px solid var(--g-border);
            border-radius: var(--g-radius-md);
            padding: 1.15rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--g-shadow-sm);
            transition: all 0.2s ease;
            position: relative;
        }

        .gdrive-file-card:hover {
            transform: translateY(-2px);
            border-color: var(--g-maroon-border);
            box-shadow: var(--g-shadow-hover);
        }

        .gdrive-file-top {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            margin-bottom: 0.75rem;
        }

        .gdrive-file-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .gdrive-file-icon.is-pdf { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .gdrive-file-icon.is-xlsx { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .gdrive-file-icon.is-docx { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .gdrive-file-icon.is-other { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

        .gdrive-file-details {
            flex: 1;
            min-width: 0;
        }

        .gdrive-file-badge-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
            margin-bottom: 0.25rem;
        }

        .gdrive-file-format-badge {
            font-size: 0.68rem;
            font-weight: 800;
            color: #475569;
            background: #f1f5f9;
            padding: 0.12rem 0.45rem;
            border-radius: 6px;
            letter-spacing: 0.03em;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .gdrive-file-name {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--g-ink-dark);
            margin: 0;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .gdrive-file-meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--g-ink-muted);
            font-weight: 600;
            margin-top: 0.85rem;
            padding-top: 0.65rem;
            border-top: 1px solid var(--g-border-subtle);
        }

        .gdrive-file-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .gdrive-btn-preview {
            background: #f8fafc;
            border: 1px solid var(--g-border);
            border-radius: var(--g-radius-sm);
            padding: 0.45rem 0.65rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--g-ink-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .gdrive-btn-preview:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .gdrive-btn-download {
            background: var(--g-maroon-light);
            border: 1px solid var(--g-maroon-border);
            border-radius: var(--g-radius-sm);
            padding: 0.45rem 0.65rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--g-maroon);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .gdrive-btn-download:hover {
            background: var(--g-maroon);
            color: #ffffff;
            border-color: var(--g-maroon);
        }

        /* 6. Google Drive Document Table (List View) */
        .gdrive-table-wrapper {
            background: #ffffff;
            border: 1.5px solid var(--g-border);
            border-radius: var(--g-radius-md);
            overflow: hidden;
            box-shadow: var(--g-shadow-sm);
        }

        .gdrive-data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        .gdrive-data-table thead {
            background: #f8fafc;
            border-bottom: 1px solid var(--g-border);
        }

        .gdrive-data-table th {
            padding: 0.85rem 1.15rem;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--g-ink-muted);
            white-space: nowrap;
        }

        .gdrive-data-table tbody tr {
            border-bottom: 1px solid var(--g-border-subtle);
            transition: background 0.15s ease;
        }

        .gdrive-data-table tbody tr:last-child {
            border-bottom: none;
        }

        .gdrive-data-table tbody tr:hover {
            background: #faf7f8;
        }

        .gdrive-data-table td {
            padding: 0.85rem 1.15rem;
            vertical-align: middle;
            color: var(--g-ink-body);
        }

        .gdrive-tbl-row-doc {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .gdrive-tbl-doc-name {
            font-weight: 700;
            color: var(--g-ink-dark);
            font-size: 0.92rem;
            line-height: 1.3;
        }

        .gdrive-tbl-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .gdrive-tbl-btn {
            padding: 0.35rem 0.65rem;
            font-size: 0.78rem;
            font-weight: 700;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .gdrive-tbl-btn.is-preview {
            background: #f1f5f9;
            color: var(--g-ink-dark);
            border-color: #e2e8f0;
        }

        .gdrive-tbl-btn.is-preview:hover { background: #e2e8f0; }

        .gdrive-tbl-btn.is-download {
            background: var(--g-maroon-light);
            color: var(--g-maroon);
            border-color: var(--g-maroon-border);
        }

        .gdrive-tbl-btn.is-download:hover {
            background: var(--g-maroon);
            color: #ffffff;
            border-color: var(--g-maroon);
        }

        /* 7. Google Drive Empty State */
        .gdrive-empty-dropzone {
            text-align: center;
            padding: 4.5rem 1.5rem;
            background: #fbfcfe;
            border: 2px dashed #cbd5e1;
            border-radius: var(--g-radius-lg);
            margin: 1rem 0;
            transition: all 0.2s ease;
        }

        .gdrive-empty-dropzone:hover {
            border-color: var(--g-maroon);
            background: #ffffff;
        }

        .gdrive-empty-icon {
            font-size: 3.5rem;
            color: #94a3b8;
            margin-bottom: 1rem;
            display: inline-block;
            line-height: 1;
        }

        .gdrive-empty-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--g-ink-dark);
            margin: 0 0 0.4rem;
        }

        .gdrive-empty-desc {
            font-size: 0.88rem;
            color: var(--g-ink-muted);
            max-width: 440px;
            margin: 0 auto 1.5rem;
            line-height: 1.45;
        }

        .gdrive-empty-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.85rem;
            flex-wrap: wrap;
        }

        /* 8. Modals (Center Overlay Dialog) */
        .arc-modal {
            border: none;
            border-radius: 20px;
            padding: 0;
            background: transparent;
            max-width: 560px;
            width: 92%;
            margin: auto;
            position: fixed;
            inset: 0;
            outline: none;
            box-shadow: none;
            overflow: visible;
        }

        .arc-modal[open] {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .arc-modal::backdrop {
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(5px);
        }

        .arc-modal-box {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid var(--g-border);
            padding: 1.85rem;
            box-shadow: 0 24px 60px -12px rgba(15, 23, 42, 0.25);
            width: 100%;
            max-height: 88vh;
            overflow-y: auto;
            box-sizing: border-box;
            animation: arcModalPop 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes arcModalPop {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .arc-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--g-border-subtle);
            margin-bottom: 1.25rem;
        }

        .arc-modal-header h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--g-ink-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .arc-modal-close {
            background: none;
            border: none;
            font-size: 1.35rem;
            color: var(--g-ink-muted);
            cursor: pointer;
            line-height: 1;
            padding: 0.2rem;
            border-radius: 6px;
        }

        .arc-modal-close:hover { color: var(--g-ink-dark); background: #f1f5f9; }

        .arc-form-group {
            margin-bottom: 1rem;
        }

        .arc-form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--g-ink-muted);
            margin-bottom: 0.35rem;
        }

        .arc-form-input {
            width: 100%;
            border: 1.5px solid var(--g-border);
            border-radius: var(--g-radius-sm);
            padding: 0.65rem 0.95rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--g-ink-dark);
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .arc-form-input:focus {
            border-color: var(--g-maroon);
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.08);
        }

        .arc-select {
            border: 1.5px solid var(--g-border);
            border-radius: 10px;
            padding: 0.55rem 0.9rem;
            font-size: 0.86rem;
            font-weight: 600;
            color: var(--g-ink-dark);
            background: #ffffff;
            outline: none;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .arc-select:focus {
            border-color: var(--g-maroon);
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.08);
        }

        .arc-form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem;
        }

        .arc-parent-indicator {
            background: #f8fafc;
            border: 1px solid var(--g-border);
            border-radius: var(--g-radius-sm);
            padding: 0.6rem 0.85rem;
            font-size: 0.84rem;
            color: var(--g-ink-dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .arc-parent-indicator i {
            color: var(--g-maroon);
            font-size: 1.1rem;
        }

        /* Toast Container */
        .arc-toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: none;
        }

        .arc-toast {
            pointer-events: auto;
            background: #0f172a;
            color: #ffffff;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            animation: arcSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .arc-toast.is-success { border-left: 4px solid #10b981; }
        .arc-toast.is-info { border-left: 4px solid #3b82f6; }

        @keyframes arcSlideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    {{-- 1. Vault Overview KPI Cards --}}
    <div class="gdrive-stats-row">
        <div class="gdrive-stat-card">
            <div class="gdrive-stat-icon is-red">
                <i class="bi bi-files"></i>
            </div>
            <div class="gdrive-stat-meta">
                <strong>{{ $totalDocuments }}</strong>
                <span>Total Documents</span>
            </div>
        </div>

        <div class="gdrive-stat-card">
            <div class="gdrive-stat-icon is-gold">
                <i class="bi bi-folder-fill"></i>
            </div>
            <div class="gdrive-stat-meta">
                <strong>{{ $totalFolders }}</strong>
                <span>Archived Folders</span>
            </div>
        </div>

        <div class="gdrive-stat-card">
            <div class="gdrive-stat-icon is-green">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
            <div class="gdrive-stat-meta">
                <strong>{{ $currentSemester }}</strong>
                <span>Current Archive Period</span>
            </div>
        </div>

        <div class="gdrive-stat-card">
            <div class="gdrive-stat-icon is-blue">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="gdrive-stat-meta">
                <strong>{{ $storageUsedFormatted }}</strong>
                <span>Storage Vault Used</span>
            </div>
        </div>
    </div>

    {{-- 2. Google Drive Breadcrumbs & Controls Toolbar --}}
    <div class="gdrive-nav-panel">
        <div class="gdrive-breadcrumb-trail">
            {{-- Up One Level Button --}}
            @if ($currentFolder)
                @php
                    $upUrl = $parentFolderId
                        ? route('office.archive', ['folder_id' => $parentFolderId])
                        : route('office.archive');
                @endphp
                <a href="{{ $upUrl }}" class="gdrive-level-up-btn" title="Go up one folder level" aria-label="Go up one level">
                    <i class="bi bi-arrow-up-short"></i>
                </a>
            @endif

            {{-- Interactive Breadcrumb Path --}}
            @foreach ($breadcrumbs as $index => $crumb)
                @php
                    $isLast = $index === count($breadcrumbs) - 1;
                    $crumbUrl = $crumb['id']
                        ? route('office.archive', ['folder_id' => $crumb['id']])
                        : route('office.archive');
                @endphp

                @if ($isLast)
                    <span class="gdrive-crumb-item is-active" aria-current="page">
                        <i class="bi {{ $crumb['id'] ? 'bi-folder2-open' : 'bi-hdd-network' }}"></i>
                        <span>{{ $crumb['name'] }}</span>
                    </span>
                @else
                    <a href="{{ $crumbUrl }}" class="gdrive-crumb-item">
                        <i class="bi {{ $crumb['id'] ? 'bi-folder-fill' : 'bi-hdd-network' }}"></i>
                        <span>{{ $crumb['name'] }}</span>
                    </a>
                    <i class="bi bi-chevron-right gdrive-crumb-divider"></i>
                @endif
            @endforeach
        </div>

        {{-- Search & View Toggle --}}
        <div class="gdrive-toolbar-actions">
            <div class="gdrive-search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="gdriveSearchInput" class="gdrive-search-input" placeholder="Search folders &amp; files..." oninput="handleGdriveFilter(this.value)">
            </div>

            <div class="gdrive-view-toggle-group" role="group" aria-label="View Switcher">
                <button type="button" class="gdrive-view-btn is-active" id="btnGdriveGrid" onclick="setGdriveView('grid')" title="Grid View" aria-label="Grid View">
                    <i class="bi bi-grid-fill"></i>
                </button>
                <button type="button" class="gdrive-view-btn" id="btnGdriveList" onclick="setGdriveView('list')" title="List View" aria-label="List View">
                    <i class="bi bi-view-list"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- 3. Main Content Container (Folders & Files) --}}
    <main class="gdrive-main-card">
        @php
            $hasFolders = count($folders) > 0;
            $hasDocs = count($documents) > 0;
            $isEmptyFolder = !$hasFolders && !$hasDocs;
        @endphp

        @if ($isEmptyFolder)
            {{-- Google Drive Style Empty Folder State --}}
            <div class="gdrive-empty-dropzone">
                <i class="bi bi-folder2-open gdrive-empty-icon"></i>
                <h3 class="gdrive-empty-title">This folder is empty</h3>
                <p class="gdrive-empty-desc">
                    There are no subfolders or archived documents filed under <strong>{{ $currentFolder?->name ?? 'this folder' }}</strong> yet. Create a subfolder or upload your first document below.
                </p>
                <div class="gdrive-empty-actions">
                    <button type="button" class="org-btn org-btn-ghost" onclick="openNewFolderModal()">
                        <i class="bi bi-folder-plus"></i> Create Subfolder
                    </button>
                    <button type="button" class="org-btn org-btn-primary" onclick="openUploadDocumentModal()">
                        <i class="bi bi-cloud-upload-fill"></i> Upload Document
                    </button>
                </div>
            </div>
        @else
            {{-- Section A: Folders Grid (Only if folder count > 0) --}}
            @if ($hasFolders)
                <section id="gdriveFoldersSection">
                    <div class="gdrive-section-title-bar">
                        <h2 class="gdrive-section-heading">
                            <i class="bi bi-folder2-open" style="color: var(--g-maroon);"></i>
                            <span>Folders</span>
                            <span class="gdrive-badge-count" id="gdriveFolderCount">{{ count($folders) }}</span>
                        </h2>
                    </div>

                    <div class="gdrive-folder-grid" id="gdriveFolderGrid">
                        @foreach ($folders as $folder)
                            @php
                                $folderUrl = route('office.archive', ['folder_id' => $folder['id']]);
                                $subCount = $folder['subfolders'] ?? 0;
                                $docCount = $folder['documents'] ?? 0;
                                $metaText = [];
                                if ($subCount > 0) {
                                    $metaText[] = $subCount . ' ' . ($subCount === 1 ? 'folder' : 'folders');
                                }
                                $metaText[] = $docCount . ' ' . ($docCount === 1 ? 'file' : 'files');
                            @endphp
                            <a href="{{ $folderUrl }}" class="gdrive-folder-card" data-name="{{ strtolower($folder['name']) }}" title="Open {{ $folder['name'] }}">
                                <div class="gdrive-folder-icon is-{{ $folder['color'] ?? 'blue' }}">
                                    <i class="bi bi-{{ $folder['icon'] ?? 'folder-fill' }}"></i>
                                </div>
                                <div class="gdrive-folder-info">
                                    <strong class="gdrive-folder-name">{{ $folder['name'] }}</strong>
                                    <div class="gdrive-folder-meta">
                                        <span>{{ implode(', ', $metaText) }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Section B: Documents / Files Section --}}
            @if ($hasDocs)
                <section id="gdriveFilesSection">
                    <div class="gdrive-section-title-bar">
                        <h2 class="gdrive-section-heading">
                            <i class="bi bi-file-earmark-text-fill" style="color: var(--g-maroon);"></i>
                            <span>{{ $currentFolder ? 'Files in this folder' : 'Recent Documents' }}</span>
                            <span class="gdrive-badge-count" id="gdriveFileCount">{{ count($documents) }}</span>
                        </h2>
                    </div>

                    {{-- Mode 1: Grid View --}}
                    <div class="gdrive-file-grid" id="gdriveFilesGrid">
                        @foreach ($documents as $doc)
                            @php
                                $type = strtoupper($doc['type'] ?? pathinfo($doc['name'] ?? '', PATHINFO_EXTENSION) ?: 'DOC');
                                $iconClass = match($type) {
                                    'PDF' => 'is-pdf',
                                    'XLSX', 'XLS', 'CSV' => 'is-xlsx',
                                    'DOC', 'DOCX' => 'is-docx',
                                    default => 'is-other',
                                };
                                $iconBi = match($type) {
                                    'PDF' => 'bi-file-earmark-pdf-fill',
                                    'XLSX', 'XLS', 'CSV' => 'bi-file-earmark-spreadsheet-fill',
                                    'DOC', 'DOCX' => 'bi-file-earmark-word-fill',
                                    default => 'bi-file-earmark-fill',
                                };
                                $folderName = $doc['folder_name'] ?? 'Archive Vault';
                                $docUrl = $doc['url'] ?? '#';
                            @endphp
                            <article class="gdrive-file-card" data-name="{{ strtolower($doc['name']) }}" data-type="{{ $type }}" data-author="{{ strtolower($doc['author'] ?? '') }}">
                                <div class="gdrive-file-top">
                                    <div class="gdrive-file-icon {{ $iconClass }}">
                                        <i class="bi {{ $iconBi }}"></i>
                                    </div>
                                    <div class="gdrive-file-details">
                                        <div class="gdrive-file-badge-row">
                                            <span class="gdrive-file-format-badge">{{ $type }}</span>
                                        </div>
                                        <h3 class="gdrive-file-name" title="{{ $doc['name'] }}">{{ $doc['name'] }}</h3>
                                    </div>
                                </div>

                                <div>
                                    <div class="gdrive-file-meta-row">
                                        <span><i class="bi bi-person-fill"></i> {{ $doc['author'] ?? 'Student Org' }}</span>
                                        <span><i class="bi bi-hdd"></i> {{ $doc['size'] ?? '1.2 MB' }}</span>
                                        <span><i class="bi bi-calendar3"></i> {{ $doc['date'] ?? 'Apr 2026' }}</span>
                                    </div>
                                    <div class="gdrive-file-actions">
                                        <button type="button" class="gdrive-btn-preview" onclick="openArchivePreviewModal('{{ addslashes($doc['name']) }}', '{{ addslashes($folderName) }}', '{{ $type }}', '{{ $doc['size'] ?? '1.2 MB' }}', '{{ $doc['date'] ?? 'Recent' }}', '{{ addslashes($doc['author'] ?? 'Student Org') }}', '{{ $docUrl }}')">
                                            <i class="bi bi-eye"></i> Preview
                                        </button>
                                        <a href="{{ $docUrl }}" class="gdrive-btn-download" download="{{ $doc['name'] }}" onclick="handleDownloadToast('{{ addslashes($doc['name']) }}')">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- Mode 2: List View Table --}}
                    <div class="gdrive-table-wrapper" id="gdriveFilesTableWrapper" style="display: none;">
                        <table class="gdrive-data-table">
                            <thead>
                                <tr>
                                    <th>Name &amp; Type</th>
                                    <th>Location Folder</th>
                                    <th>File Size</th>
                                    <th>Uploaded Date</th>
                                    <th>Uploader</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="gdriveTableBody">
                                @foreach ($documents as $doc)
                                    @php
                                        $type = strtoupper($doc['type'] ?? pathinfo($doc['name'] ?? '', PATHINFO_EXTENSION) ?: 'DOC');
                                        $iconClass = match($type) {
                                            'PDF' => 'is-pdf',
                                            'XLSX', 'XLS', 'CSV' => 'is-xlsx',
                                            'DOC', 'DOCX' => 'is-docx',
                                            default => 'is-other',
                                        };
                                        $iconBi = match($type) {
                                            'PDF' => 'bi-file-earmark-pdf-fill',
                                            'XLSX', 'XLS', 'CSV' => 'bi-file-earmark-spreadsheet-fill',
                                            'DOC', 'DOCX' => 'bi-file-earmark-word-fill',
                                            default => 'bi-file-earmark-fill',
                                        };
                                        $folderName = $doc['folder_name'] ?? 'Archive Vault';
                                        $docUrl = $doc['url'] ?? '#';
                                    @endphp
                                    <tr data-name="{{ strtolower($doc['name']) }}" data-type="{{ $type }}" data-author="{{ strtolower($doc['author'] ?? '') }}">
                                        <td>
                                            <div class="gdrive-tbl-row-doc">
                                                <div class="gdrive-file-icon {{ $iconClass }}" style="width: 34px; height: 34px; font-size: 1.1rem; border-radius: 8px;">
                                                    <i class="bi {{ $iconBi }}"></i>
                                                </div>
                                                <div>
                                                    <div class="gdrive-tbl-doc-name">{{ $doc['name'] }}</div>
                                                    <span class="gdrive-file-format-badge" style="font-size: 0.65rem; padding: 0.1rem 0.35rem;">{{ $type }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.8rem; font-weight: 600; color: var(--g-maroon);">{{ $folderName }}</span>
                                        </td>
                                        <td style="font-weight: 600;">{{ $doc['size'] ?? '1.2 MB' }}</td>
                                        <td style="font-size: 0.82rem; color: var(--g-ink-muted);">{{ $doc['date'] ?? 'Apr 2026' }}</td>
                                        <td style="font-size: 0.84rem; font-weight: 600;">{{ $doc['author'] ?? 'Student Org' }}</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <div class="gdrive-tbl-actions">
                                                <button type="button" class="gdrive-tbl-btn is-preview" onclick="openArchivePreviewModal('{{ addslashes($doc['name']) }}', '{{ addslashes($folderName) }}', '{{ $type }}', '{{ $doc['size'] ?? '1.2 MB' }}', '{{ $doc['date'] ?? 'Recent' }}', '{{ addslashes($doc['author'] ?? 'Student Org') }}', '{{ $docUrl }}')">
                                                    <i class="bi bi-eye"></i> Preview
                                                </button>
                                                <a href="{{ $docUrl }}" class="gdrive-tbl-btn is-download" download="{{ $doc['name'] }}" onclick="handleDownloadToast('{{ addslashes($doc['name']) }}')">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            {{-- No Search Results Found --}}
            <div id="gdriveNoSearchResults" class="gdrive-empty-dropzone" style="display: none; padding: 3rem 1.5rem;">
                <i class="bi bi-search" style="font-size: 2.5rem; color: #cbd5e1; display: block; margin-bottom: 0.75rem;"></i>
                <h4 style="font-size: 1.1rem; font-weight: 800; color: var(--g-ink-dark); margin: 0 0 0.35rem;">No matching items found</h4>
                <p style="font-size: 0.85rem; color: var(--g-ink-muted); margin: 0;">Try adjusting your search query in this folder.</p>
            </div>
        @endif
    </main>

    {{-- Modal 1: Create New Folder / Subfolder --}}
    <dialog class="arc-modal" id="newFolderModal">
        <div class="arc-modal-box">
            <div class="arc-modal-header">
                <h3><i class="bi bi-folder-plus" style="color: var(--g-maroon);"></i> {{ $currentFolder ? 'Create Subfolder' : 'Create Archive Folder' }}</h3>
                <button type="button" class="arc-modal-close" onclick="closeNewFolderModal()">&times;</button>
            </div>
            <form method="post" action="{{ route('office.archive.folders.store') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                
                @if ($currentFolder && !($currentFolder->is_activity ?? false))
                    <input type="hidden" name="parent_id" value="{{ $currentFolder->id }}">
                    <div class="arc-parent-indicator">
                        <i class="bi bi-diagram-3-fill"></i>
                        <span>Creating subfolder inside: <strong>{{ $currentFolder->name }}</strong></span>
                    </div>
                @else
                    <div class="arc-form-group">
                        <label for="folderParentSelect">Parent Destination Folder</label>
                        <select id="folderParentSelect" name="parent_id" class="arc-select" style="width: 100%;">
                            <option value="">📁 Archive Vault (Root Level)</option>
                            @foreach ($allSavedFolders as $savedF)
                                <option value="{{ $savedF['id'] }}" {{ ($currentFolderId == $savedF['id']) ? 'selected' : '' }}>
                                    📁 {{ $savedF['path'] ?? $savedF['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="arc-form-group">
                    <label for="folderNameInput">Folder Name *</label>
                    <input type="text" id="folderNameInput" name="name" class="arc-form-input" placeholder="e.g., Financial Reports 2026" required maxlength="255" autofocus>
                </div>

                <div class="arc-form-group">
                    <label for="folderOrgInput">Organization Name</label>
                    <input type="text" id="folderOrgInput" name="organization_name" class="arc-form-input" placeholder="{{ $currentFolder->organization_name ?? 'e.g., BSIT Society' }}" value="{{ $currentFolder->organization_name ?? '' }}" maxlength="255">
                </div>

                <div class="arc-form-row-2">
                    <div class="arc-form-group">
                        <label for="folderSemesterSelect">Semester Period</label>
                        <select id="folderSemesterSelect" name="semester" class="arc-select" style="width: 100%;">
                            <option value="1st Semester" {{ ($currentFolder->semester ?? '') === '1st Semester' ? 'selected' : '' }}>1st Semester</option>
                            <option value="2nd Semester" {{ ($currentFolder->semester ?? '') !== '1st Semester' && ($currentFolder->semester ?? '') !== 'Midyear' ? 'selected' : '' }}>2nd Semester</option>
                            <option value="Midyear" {{ ($currentFolder->semester ?? '') === 'Midyear' ? 'selected' : '' }}>Midyear</option>
                        </select>
                    </div>
                    <div class="arc-form-group">
                        <label for="folderColorSelect">Folder Color Theme</label>
                        <select id="folderColorSelect" name="color" class="arc-select" style="width: 100%;">
                            <option value="blue" selected>Royal Blue</option>
                            <option value="red">Crimson Red</option>
                            <option value="green">Emerald Green</option>
                            <option value="violet">Deep Violet</option>
                            <option value="gold">Warm Gold</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem; border-top: 1px solid var(--g-border-subtle); padding-top: 1rem;">
                    <button type="button" class="org-btn org-btn-ghost" onclick="closeNewFolderModal()">Cancel</button>
                    <button type="submit" class="org-btn org-btn-primary">
                        <i class="bi bi-folder-plus"></i> Create Folder
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Modal 2: Upload Document to Archive --}}
    <dialog class="arc-modal" id="uploadDocumentModal">
        <div class="arc-modal-box">
            <div class="arc-modal-header">
                <h3><i class="bi bi-cloud-upload-fill" style="color: var(--g-maroon);"></i> Upload Document</h3>
                <button type="button" class="arc-modal-close" onclick="closeUploadDocumentModal()">&times;</button>
            </div>
            <form method="post" action="{{ route('office.archive.documents.store') }}" enctype="multipart/form-data" data-org-upload-form>
                @csrf
                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                
                <div class="arc-form-group">
                    <label for="uploadFolderSelect">Target Destination Folder *</label>
                    <select id="uploadFolderSelect" name="archive_folder_id" class="arc-select" style="width: 100%;" required @disabled($allSavedFolders->isEmpty())>
                        @forelse ($allSavedFolders as $f)
                            <option value="{{ $f['id'] }}" {{ ($currentFolderId == $f['id']) ? 'selected' : '' }}>
                                📁 {{ $f['path'] ?? $f['name'] }}
                            </option>
                        @empty
                            <option value="" selected disabled>Create an archive folder first</option>
                        @endforelse
                    </select>
                </div>

                <div class="arc-form-group">
                    <label for="uploadDocTitleInput">Document Title / Subject *</label>
                    <input type="text" id="uploadDocTitleInput" name="name" class="arc-form-input" placeholder="e.g., Annual Accomplishment Report 2026" maxlength="255" required>
                </div>

                <div class="arc-form-group">
                    <label for="uploadFileInput">Choose Document File (.pdf, .docx, .xlsx, .zip) *</label>
                    <input type="file" id="uploadFileInput" name="document" class="arc-form-input" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg" data-org-upload data-max-size="20480" data-upload-status-id="gdriveUploadStatus">
                    <span id="gdriveUploadStatus" class="org-upload-status" aria-live="polite">No file selected.</span>
                    <small style="font-size: 0.74rem; color: var(--g-ink-muted); display: block; margin-top: 0.35rem;">
                        Max file size: 20MB. Accepted formats: PDF, Word, Excel, PowerPoint, ZIP.
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem; border-top: 1px solid var(--g-border-subtle); padding-top: 1rem;">
                    <button type="button" class="org-btn org-btn-ghost" onclick="closeUploadDocumentModal()">Cancel</button>
                    <button type="submit" class="org-btn org-btn-primary">
                        <i class="bi bi-cloud-arrow-up-fill"></i> Upload &amp; Archive
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Modal 3: Document Preview & Details --}}
    <dialog class="arc-modal" id="archiveDocPreviewModal">
        <div class="arc-modal-box">
            <div class="arc-modal-header">
                <h3><i class="bi bi-file-earmark-check-fill" style="color: var(--g-maroon);"></i> Document Details</h3>
                <button type="button" class="arc-modal-close" onclick="closeArchivePreviewModal()">&times;</button>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--g-maroon); background: var(--g-maroon-light); padding: 0.15rem 0.6rem; border-radius: 9999px; border: 1px solid var(--g-maroon-border);" id="prevDocFolder">Organization Folder</span>
                    <span class="gdrive-file-format-badge" id="prevDocFormat">PDF</span>
                </div>
                <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--g-ink-dark); line-height: 1.35; margin: 0 0 0.85rem;" id="prevDocTitle">
                    Document Filename
                </h2>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.15rem; margin-bottom: 1rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.84rem;">
                        <div>
                            <span style="display: block; font-size: 0.72rem; text-transform: uppercase; color: var(--g-ink-muted); font-weight: 700;">File Size</span>
                            <strong style="color: var(--g-ink-dark);" id="prevDocSize">2.4 MB</strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 0.72rem; text-transform: uppercase; color: var(--g-ink-muted); font-weight: 700;">Uploaded On</span>
                            <strong style="color: var(--g-ink-dark);" id="prevDocDate">Apr 6, 2026</strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 0.72rem; text-transform: uppercase; color: var(--g-ink-muted); font-weight: 700;">Uploaded By</span>
                            <strong style="color: var(--g-ink-dark);" id="prevDocAuthor">Officer Name</strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 0.72rem; text-transform: uppercase; color: var(--g-ink-muted); font-weight: 700;">Vault Status</span>
                            <span style="color: #059669; font-weight: 700; display: inline-flex; align-items: center; gap: 0.25rem;"><i class="bi bi-patch-check-fill"></i> Verified Permanent</span>
                        </div>
                    </div>
                </div>

                <div style="background: #ffffff; border: 1.5px dashed var(--g-border); border-radius: 12px; padding: 1.5rem; text-align: center; color: var(--g-ink-muted);">
                    <i class="bi bi-file-earmark-pdf" style="font-size: 2.2rem; color: var(--g-maroon); display: block; margin-bottom: 0.5rem;"></i>
                    <span style="font-size: 0.86rem; font-weight: 600; display: block; color: var(--g-ink-dark);">Permanent Institutional Record</span>
                    <small style="font-size: 0.75rem; color: var(--g-ink-muted);">Encrypted &amp; logged in the official institutional depository.</small>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.65rem; border-top: 1px solid var(--g-border-subtle); padding-top: 1rem;">
                <button type="button" class="org-btn org-btn-ghost" onclick="closeArchivePreviewModal()">Close</button>
                <a href="#" class="org-btn org-btn-primary" id="prevDocDownloadLink" download>
                    <i class="bi bi-download"></i> Download Document
                </a>
            </div>
        </div>
    </dialog>

    {{-- Toast Container --}}
    <div class="arc-toast-container" id="arcToastContainer"></div>

    <script>
        let currentGdriveView = localStorage.getItem('gdrive_archive_view') || 'grid';

        function setGdriveView(view) {
            currentGdriveView = view;
            localStorage.setItem('gdrive_archive_view', view);

            const gridEl = document.getElementById('gdriveFilesGrid');
            const tableEl = document.getElementById('gdriveFilesTableWrapper');
            const btnGrid = document.getElementById('btnGdriveGrid');
            const btnList = document.getElementById('btnGdriveList');

            if (!gridEl || !tableEl) return;

            if (view === 'grid') {
                gridEl.style.display = 'grid';
                tableEl.style.display = 'none';
                if (btnGrid) btnGrid.classList.add('is-active');
                if (btnList) btnList.classList.remove('is-active');
            } else {
                gridEl.style.display = 'none';
                tableEl.style.display = 'block';
                if (btnList) btnList.classList.add('is-active');
                if (btnGrid) btnGrid.classList.remove('is-active');
            }
        }

        function handleGdriveFilter(query) {
            const q = (query || '').toLowerCase().trim();
            const folderCards = document.querySelectorAll('#gdriveFolderGrid .gdrive-folder-card');
            const fileCards = document.querySelectorAll('#gdriveFilesGrid .gdrive-file-card');
            const fileRows = document.querySelectorAll('#gdriveTableBody tr');

            let visibleFolders = 0;
            let visibleFiles = 0;

            folderCards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const match = !q || name.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visibleFolders++;
            });

            fileCards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const author = card.getAttribute('data-author') || '';
                const match = !q || name.includes(q) || author.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visibleFiles++;
            });

            fileRows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const author = row.getAttribute('data-author') || '';
                const match = !q || name.includes(q) || author.includes(q);
                row.style.display = match ? '' : 'none';
            });

            const folderSection = document.getElementById('gdriveFoldersSection');
            if (folderSection && folderCards.length > 0) {
                folderSection.style.display = visibleFolders > 0 ? '' : 'none';
            }

            const filesSection = document.getElementById('gdriveFilesSection');
            if (filesSection && (fileCards.length > 0 || fileRows.length > 0)) {
                filesSection.style.display = visibleFiles > 0 ? '' : 'none';
            }

            const noResults = document.getElementById('gdriveNoSearchResults');
            if (noResults) {
                const totalVisible = visibleFolders + visibleFiles;
                noResults.style.display = (q && totalVisible === 0) ? 'block' : 'none';
            }

            const folderCountBadge = document.getElementById('gdriveFolderCount');
            if (folderCountBadge) folderCountBadge.textContent = visibleFolders;

            const fileCountBadge = document.getElementById('gdriveFileCount');
            if (fileCountBadge) fileCountBadge.textContent = visibleFiles;
        }

        function openNewFolderModal() {
            const dialog = document.getElementById('newFolderModal');
            if (dialog) dialog.showModal();
        }

        function closeNewFolderModal() {
            const dialog = document.getElementById('newFolderModal');
            if (dialog) dialog.close();
        }

        function openUploadDocumentModal() {
            const dialog = document.getElementById('uploadDocumentModal');
            if (dialog) dialog.showModal();
        }

        function closeUploadDocumentModal() {
            const dialog = document.getElementById('uploadDocumentModal');
            if (dialog) dialog.close();
        }

        function openArchivePreviewModal(title, folder, format, size, date, author, url) {
            document.getElementById('prevDocTitle').textContent = title;
            document.getElementById('prevDocFolder').textContent = folder;
            document.getElementById('prevDocFormat').textContent = format;
            document.getElementById('prevDocSize').textContent = size;
            document.getElementById('prevDocDate').textContent = date;
            document.getElementById('prevDocAuthor').textContent = author;

            const dlLink = document.getElementById('prevDocDownloadLink');
            if (dlLink) {
                dlLink.href = url;
                dlLink.download = title;
                dlLink.onclick = function() {
                    handleDownloadToast(title);
                    closeArchivePreviewModal();
                };
            }

            const dialog = document.getElementById('archiveDocPreviewModal');
            if (dialog) dialog.showModal();
        }

        function closeArchivePreviewModal() {
            const dialog = document.getElementById('archiveDocPreviewModal');
            if (dialog) dialog.close();
        }

        function handleDownloadToast(name) {
            showArchiveToast(`Downloading "${name}"...`, 'success');
        }

        function showArchiveToast(message, type = 'info') {
            const container = document.getElementById('arcToastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `arc-toast is-${type}`;
            const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-info-circle-fill';
            toast.innerHTML = `<i class="bi ${icon}"></i> <span>${escapeHtml(message)}</span>`;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(12px)';
                setTimeout(() => toast.remove(), 300);
            }, 3200);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', () => {
            setGdriveView(currentGdriveView);
        });
    </script>
@endsection
