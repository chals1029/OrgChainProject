@extends('org.layout')

@section('title', 'Archive Vault')

@section('header')
    <h1><strong>Archive Vault &amp; Records</strong></h1>
    <p class="org-welcome">Official permanent depository for student organization proposals, financial liquidation records, accomplishment reports, and compliance archives.</p>
@endsection

@section('actions')
    <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
        <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="openNewFolderModal()">
            <i class="bi bi-folder-plus"></i> New Folder
        </button>
        <button type="button" class="org-btn org-btn-primary org-btn-sm" onclick="openUploadDocumentModal()">
            <i class="bi bi-cloud-upload-fill"></i> Upload Document
        </button>
    </div>
@endsection

@section('content')
    <style>
        /* ==========================================================================
           Impeccable & Unslop Design System for Archive Vault
           ========================================================================== */
        :root {
            --arc-maroon: #8b1828;
            --arc-maroon-dark: #62101c;
            --arc-maroon-light: #fdf0f2;
            --arc-maroon-border: #f2dfe2;
            --arc-ink-dark: #1a1618;
            --arc-ink-body: #3f3538;
            --arc-ink-muted: #7a7074;
            --arc-border: #f0e6e8;
            --arc-border-subtle: #f9f2f4;
            --arc-radius-lg: 20px;
            --arc-radius-md: 14px;
            --arc-radius-sm: 10px;
            --arc-shadow-sm: 0 4px 16px rgba(90, 15, 30, 0.03);
            --arc-shadow-md: 0 8px 24px rgba(90, 15, 30, 0.06);
            --arc-shadow-hover: 0 12px 32px rgba(90, 15, 30, 0.08);
        }

        /* 1. Archive Summary KPI Cards */
        .arc-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .arc-stat-card {
            background: #ffffff;
            border: 1.5px solid var(--arc-border);
            border-radius: var(--arc-radius-lg);
            padding: 1.25rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--arc-shadow-sm);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .arc-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--arc-shadow-md);
            border-color: var(--arc-maroon-border);
        }

        .arc-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .arc-stat-icon.is-red {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .arc-stat-icon.is-gold {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .arc-stat-icon.is-green {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .arc-stat-icon.is-blue {
            background: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

        .arc-stat-meta strong {
            display: block;
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--arc-ink-dark);
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .arc-stat-meta span {
            font-size: 0.8rem;
            color: var(--arc-ink-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* 2. Search & Filter Bar Section */
        .arc-filter-panel {
            background: #ffffff;
            border: 1px solid var(--arc-border);
            border-radius: var(--arc-radius-lg);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.75rem;
            box-shadow: var(--arc-shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .arc-search-wrapper {
            position: relative;
            flex: 1;
            min-width: 260px;
        }

        .arc-search-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--arc-ink-muted);
            font-size: 0.9rem;
            pointer-events: none;
        }

        .arc-search-input {
            width: 100%;
            border: 1.5px solid var(--arc-border);
            border-radius: 9999px;
            padding: 0.55rem 1.15rem 0.55rem 2.4rem;
            font-size: 0.88rem;
            color: var(--arc-ink-dark);
            background: #f8fafc;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .arc-search-input:focus {
            background: #ffffff;
            border-color: var(--arc-maroon);
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.06);
        }

        .arc-filter-dropdowns {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .arc-select {
            border: 1.5px solid var(--arc-border);
            border-radius: 10px;
            padding: 0.5rem 0.9rem;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--arc-ink-dark);
            background: #ffffff;
            outline: none;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .arc-select:focus {
            border-color: var(--arc-maroon);
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.06);
        }

        /* 3. Organization Folders Section */
        .arc-section-card {
            background: #ffffff;
            border: 1px solid var(--arc-border);
            border-radius: var(--arc-radius-lg);
            padding: 1.75rem;
            margin-bottom: 2rem;
            box-shadow: var(--arc-shadow-md);
        }

        .arc-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .arc-section-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--arc-ink-dark);
            display: flex;
            align-items: center;
            gap: 0.55rem;
            letter-spacing: -0.01em;
        }

        .arc-section-sub {
            margin: 0.2rem 0 0;
            font-size: 0.82rem;
            color: var(--arc-ink-muted);
        }

        .arc-folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
        }

        .arc-folder-card {
            background: #ffffff;
            border: 1.5px solid var(--arc-border);
            border-radius: var(--arc-radius-md);
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            box-shadow: var(--arc-shadow-sm);
        }

        .arc-folder-card:hover {
            transform: translateY(-2px);
            border-color: var(--arc-maroon-border);
            box-shadow: var(--arc-shadow-hover);
        }

        .arc-folder-card.is-active {
            border-color: var(--arc-maroon);
            background: linear-gradient(135deg, rgba(250, 242, 244, 0.7), #ffffff);
            box-shadow: 0 0 0 2px rgba(139, 24, 40, 0.15), var(--arc-shadow-md);
        }

        .arc-folder-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .arc-folder-icon.is-red { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .arc-folder-icon.is-blue { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .arc-folder-icon.is-green { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .arc-folder-icon.is-violet { background: #faf5ff; color: #9333ea; border: 1px solid #e9d5ff; }
        .arc-folder-icon.is-gold { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }

        .arc-folder-info {
            flex: 1;
            min-width: 0;
        }

        .arc-folder-name {
            display: block;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--arc-ink-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }

        .arc-folder-org {
            display: block;
            font-size: 0.76rem;
            color: var(--arc-ink-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 0.15rem;
        }

        .arc-folder-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.65rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--arc-border-subtle);
            font-size: 0.72rem;
            color: var(--arc-ink-muted);
            font-weight: 600;
        }

        .arc-chip-sem {
            background: #f1f5f9;
            color: #475569;
            padding: 0.12rem 0.45rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        /* 4. Archived Documents Grid & List Table */
        .arc-doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.25rem;
        }

        .arc-doc-card {
            background: #ffffff;
            border: 1px solid var(--arc-border);
            border-radius: var(--arc-radius-md);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--arc-shadow-sm);
            transition: all 0.2s ease;
            position: relative;
        }

        .arc-doc-card:hover {
            transform: translateY(-2px);
            border-color: var(--arc-maroon-border);
            box-shadow: var(--arc-shadow-hover);
        }

        .arc-doc-top {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            margin-bottom: 0.75rem;
        }

        .arc-file-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .arc-file-icon.is-pdf { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .arc-file-icon.is-xlsx { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .arc-file-icon.is-docx { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .arc-file-icon.is-other { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

        .arc-doc-details {
            flex: 1;
            min-width: 0;
        }

        .arc-doc-tag-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
            margin-bottom: 0.25rem;
        }

        .arc-doc-folder-pill {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--arc-maroon);
            background: var(--arc-maroon-light);
            padding: 0.12rem 0.5rem;
            border-radius: 9999px;
            border: 1px solid var(--arc-maroon-border);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 160px;
        }

        .arc-doc-format-badge {
            margin-left: auto;
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

        .arc-doc-name {
            font-size: 0.94rem;
            font-weight: 700;
            color: var(--arc-ink-dark);
            margin: 0;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .arc-doc-info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.76rem;
            color: var(--arc-ink-muted);
            font-weight: 600;
            margin-top: 0.85rem;
            padding-top: 0.65rem;
            border-top: 1px solid var(--arc-border-subtle);
        }

        .arc-doc-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .arc-btn-action-preview {
            background: #f8fafc;
            border: 1px solid var(--arc-border);
            border-radius: var(--arc-radius-sm);
            padding: 0.42rem 0.65rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--arc-ink-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .arc-btn-action-preview:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .arc-btn-action-download {
            background: var(--arc-maroon-light);
            border: 1px solid var(--arc-maroon-border);
            border-radius: var(--arc-radius-sm);
            padding: 0.42rem 0.65rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--arc-maroon);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .arc-btn-action-download:hover {
            background: var(--arc-maroon);
            color: #ffffff;
            border-color: var(--arc-maroon);
        }

        /* 5. List Table View */
        .arc-table-wrapper {
            background: #ffffff;
            border: 1px solid var(--arc-border);
            border-radius: var(--arc-radius-md);
            overflow: hidden;
            box-shadow: var(--arc-shadow-sm);
        }

        .arc-data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        .arc-data-table thead {
            background: #f8fafc;
            border-bottom: 1px solid var(--arc-border);
        }

        .arc-data-table th {
            padding: 0.85rem 1.15rem;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--arc-ink-muted);
            white-space: nowrap;
        }

        .arc-data-table tbody tr {
            border-bottom: 1px solid var(--arc-border-subtle);
            transition: background 0.15s ease;
        }

        .arc-data-table tbody tr:last-child {
            border-bottom: none;
        }

        .arc-data-table tbody tr:hover {
            background: #faf7f8;
        }

        .arc-data-table td {
            padding: 0.9rem 1.15rem;
            vertical-align: middle;
            color: var(--arc-ink-body);
        }

        .arc-tbl-row-doc {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .arc-tbl-doc-name {
            font-weight: 700;
            color: var(--arc-ink-dark);
            font-size: 0.92rem;
            line-height: 1.3;
        }

        .arc-tbl-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .arc-tbl-btn {
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
        }

        .arc-tbl-btn.is-preview {
            background: #f1f5f9;
            color: var(--arc-ink-dark);
            border-color: #e2e8f0;
        }

        .arc-tbl-btn.is-preview:hover { background: #e2e8f0; }

        .arc-tbl-btn.is-download {
            background: var(--arc-maroon-light);
            color: var(--arc-maroon);
            border-color: var(--arc-maroon-border);
        }

        .arc-tbl-btn.is-download:hover {
            background: var(--arc-maroon);
            color: #ffffff;
            border-color: var(--arc-maroon);
        }

        /* 6. View Switcher Icon-only */
        .arc-view-switcher {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 2px;
            gap: 2px;
        }

        .arc-view-toggle {
            background: transparent;
            border: none;
            width: 34px;
            height: 32px;
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--arc-ink-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .arc-view-toggle:hover { color: var(--arc-ink-dark); }

        .arc-view-toggle.is-active {
            background: #ffffff;
            color: var(--arc-maroon);
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
        }

        /* 7. Modal Dialogs - Centered in viewport */
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
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(5px);
        }

        .arc-modal-box {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid var(--arc-border);
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
            border-bottom: 1px solid var(--arc-border-subtle);
            margin-bottom: 1.25rem;
        }

        .arc-modal-header h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--arc-ink-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .arc-modal-close {
            background: none;
            border: none;
            font-size: 1.35rem;
            color: var(--arc-ink-muted);
            cursor: pointer;
            line-height: 1;
            padding: 0.2rem;
            border-radius: 6px;
        }

        .arc-modal-close:hover { color: var(--arc-ink-dark); background: #f1f5f9; }

        /* Form Inputs in Modals */
        .arc-form-group {
            margin-bottom: 1rem;
        }

        .arc-form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--arc-ink-muted);
            margin-bottom: 0.35rem;
        }

        .arc-form-input {
            width: 100%;
            border: 1.5px solid var(--arc-border);
            border-radius: var(--arc-radius-sm);
            padding: 0.65rem 0.95rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--arc-ink-dark);
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .arc-form-input:focus {
            border-color: var(--arc-maroon);
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.06);
        }

        .arc-form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem;
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

        /* Empty State */
        .arc-empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--arc-ink-muted);
        }

        .arc-empty-state i {
            font-size: 2.5rem;
            color: #cbd5e1;
            margin-bottom: 0.75rem;
            display: block;
        }

        /* 8. Pagination Controls (Unslop & Impeccable Style) */
        .arc-pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1.25rem;
            margin-top: 1.25rem;
            border-top: 1px solid var(--arc-border-subtle);
            flex-wrap: wrap;
            gap: 0.85rem;
        }

        .arc-pagination-info {
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--arc-ink-muted);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .arc-pagination-info strong {
            color: var(--arc-ink-dark);
            font-weight: 800;
        }

        .arc-pagination-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .arc-page-btn {
            min-width: 36px;
            height: 36px;
            padding: 0 0.6rem;
            border-radius: var(--arc-radius-sm);
            border: 1px solid var(--arc-border);
            background: #ffffff;
            color: var(--arc-ink-body);
            font-size: 0.84rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            user-select: none;
            text-decoration: none;
        }

        .arc-page-btn:hover:not(:disabled) {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: var(--arc-ink-dark);
            transform: translateY(-1px);
        }

        .arc-page-btn.is-active {
            background: var(--arc-maroon);
            color: #ffffff;
            border-color: var(--arc-maroon);
            box-shadow: 0 2px 8px rgba(139, 24, 40, 0.28);
        }

        .arc-page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f8fafc;
            border-color: var(--arc-border-subtle);
            transform: none;
        }

        .arc-page-ellipsis {
            padding: 0 0.35rem;
            color: var(--arc-ink-muted);
            font-weight: 700;
            font-size: 0.85rem;
            user-select: none;
        }
    </style>

    {{-- 1. Archive Summary / KPI Cards --}}
    <div class="arc-stats-row">
        <div class="arc-stat-card">
            <div class="arc-stat-icon is-red">
                <i class="bi bi-files"></i>
            </div>
            <div class="arc-stat-meta">
                <strong id="statTotalDocs">{{ $totalDocuments ?? count($documents) }}</strong>
                <span>Total Documents</span>
            </div>
        </div>

        <div class="arc-stat-card">
            <div class="arc-stat-icon is-gold">
                <i class="bi bi-folder-fill"></i>
            </div>
            <div class="arc-stat-meta">
                <strong id="statTotalFolders">{{ $totalFolders ?? count($folders) }}</strong>
                <span>Archived Folders</span>
            </div>
        </div>

        <div class="arc-stat-card">
            <div class="arc-stat-icon is-green">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
            <div class="arc-stat-meta">
                <strong>{{ $currentSemester ?? '2nd Semester' }}</strong>
                <span>Current Archive Period</span>
            </div>
        </div>

        <div class="arc-stat-card">
            <div class="arc-stat-icon is-blue">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="arc-stat-meta">
                <strong>48.5 MB</strong>
                <span>Storage Vault Used</span>
            </div>
        </div>
    </div>

    {{-- 2. Search & Filter Bar --}}
    <div class="arc-filter-panel">
        <div class="arc-search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" id="archiveSearchInput" placeholder="Search folders, documents, or authors..." class="arc-search-input" oninput="handleGlobalArchiveSearch(this.value)">
        </div>

        <div class="arc-filter-dropdowns">
            <select class="arc-select" id="orgSelectFilter" onchange="handleOrgFilterChange(this.value)">
                <option value="">All Organizations</option>
                @php
                    $uniqueOrgs = collect($folders)->pluck('name')->unique()->sort();
                @endphp
                @foreach ($uniqueOrgs as $orgName)
                    <option value="{{ $orgName }}">{{ $orgName }}</option>
                @endforeach
            </select>

            <select class="arc-select" id="semesterSelectFilter" onchange="handleSemesterFilterChange(this.value)">
                <option value="">All Semesters</option>
                <option value="1st Semester">1st Semester</option>
                <option value="2nd Semester">2nd Semester</option>
                <option value="Midyear">Midyear</option>
            </select>
        </div>
    </div>

    {{-- 3. Organization Folders Section --}}
    <section class="arc-section-card" id="folderSection">
        <div class="arc-section-header">
            <div>
                <h2 class="arc-section-title"><i class="bi bi-folder2-open" style="color: var(--arc-maroon);"></i> Organization Folders</h2>
                <p class="arc-section-sub">Archived folders grouped by student organization. Click any folder to filter documents below.</p>
            </div>
            <button type="button" class="org-btn org-btn-ghost org-btn-sm" id="resetFolderSelectionBtn" style="display: none;" onclick="clearFolderSelection()">
                <i class="bi bi-x-circle"></i> Clear Folder Filter
            </button>
        </div>

        <div class="arc-folder-grid" id="archiveFolderGrid">
            @foreach ($folders as $folder)
                <article class="arc-folder-card" data-folder-name="{{ $folder['name'] }}" data-folder-semester="{{ $folder['semester'] ?? '2nd Semester' }}" onclick="selectFolderCard('{{ addslashes($folder['name']) }}', this)" title="Click to view documents for {{ $folder['name'] }}">
                    <div class="arc-folder-icon is-{{ $folder['color'] ?? 'red' }}">
                        <i class="bi bi-{{ $folder['icon'] ?? 'folder-fill' }}"></i>
                    </div>
                    <div class="arc-folder-info">
                        <strong class="arc-folder-name">{{ $folder['name'] }}</strong>
                        <span class="arc-folder-org">{{ $folder['org'] ?? $folder['name'] }}</span>
                        <div class="arc-folder-meta">
                            <span class="arc-chip-sem">{{ $folder['semester'] ?? '2nd Sem' }}</span>
                            <span class="doc-count-badge"><strong class="folder-doc-count">{{ $folder['documents'] ?? 0 }}</strong> files</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div id="foldersEmptyState" class="arc-empty-state" style="display: none;">
            <i class="bi bi-folder-x"></i>
            <strong style="display: block; font-size: 1rem; color: var(--arc-ink-dark); margin-bottom: 0.25rem;">No Matching Folders</strong>
            <span>Try resetting your search query or semester filter.</span>
        </div>
    </section>

    {{-- 4. Archived Documents Section (With Grid & List Table Options) --}}
    <section class="arc-section-card" id="documentsSection">
        <div class="arc-section-header">
            <div>
                <h2 class="arc-section-title">
                    <i class="bi bi-file-earmark-text-fill" style="color: var(--arc-maroon);"></i> 
                    <span id="activeFolderNameTitle">All Archived Documents</span>
                </h2>
                <p class="arc-section-sub" id="activeFolderSubTitle">Showing all permanent compliance submissions and verified records.</p>
            </div>
            
            <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
                {{-- Grid vs List Table View Switcher (Icon-only on the right) --}}
                <div class="arc-view-switcher" role="group" aria-label="Document View Options">
                    <button type="button" class="arc-view-toggle is-active" id="btnDocGrid" onclick="setDocumentView('grid')" title="Card Grid View" aria-label="Grid View">
                        <i class="bi bi-grid-fill"></i>
                    </button>
                    <button type="button" class="arc-view-toggle" id="btnDocList" onclick="setDocumentView('list')" title="List Table View" aria-label="List Table View">
                        <i class="bi bi-view-list"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Option A: Document Cards Grid --}}
        <div class="arc-doc-grid" id="documentsGrid">
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
                    $folderName = $doc['folder_name'] ?? 'General Archive';
                @endphp
                <article class="arc-doc-card" 
                         data-doc-name="{{ strtolower($doc['name']) }}" 
                         data-doc-folder="{{ strtolower($folderName) }}" 
                         data-doc-folder-exact="{{ $folderName }}"
                         data-doc-author="{{ strtolower($doc['author'] ?? '') }}"
                         data-doc-type="{{ $type }}">
                    <div>
                        <div class="arc-doc-top">
                            <div class="arc-file-icon {{ $iconClass }}">
                                <i class="bi {{ $iconBi }}"></i>
                            </div>
                            <div class="arc-doc-details">
                                <div class="arc-doc-tag-row">
                                    <span class="arc-doc-folder-pill" title="{{ $folderName }}">{{ $folderName }}</span>
                                    <span class="arc-doc-format-badge">{{ $type }}</span>
                                </div>
                                <h3 class="arc-doc-name" title="{{ $doc['name'] }}">{{ $doc['name'] }}</h3>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="arc-doc-info-row">
                            <span><i class="bi bi-person-fill"></i> {{ $doc['author'] ?? 'Student Org' }}</span>
                            <span><i class="bi bi-hdd"></i> {{ $doc['size'] ?? '1.2 MB' }}</span>
                            <span><i class="bi bi-calendar3"></i> {{ $doc['date'] ?? 'Apr 2026' }}</span>
                        </div>
                        <div class="arc-doc-actions">
                            <button type="button" class="arc-btn-action-preview" onclick="openArchivePreviewModal('{{ addslashes($doc['name']) }}', '{{ addslashes($folderName) }}', '{{ $type }}', '{{ $doc['size'] ?? '1.2 MB' }}', '{{ $doc['date'] ?? 'Recent' }}', '{{ addslashes($doc['author'] ?? 'Student Org') }}', '{{ $doc['url'] ?? '' }}')">
                                <i class="bi bi-eye"></i> Preview
                            </button>
                            <button type="button" class="arc-btn-action-download" onclick="downloadArchiveDoc('{{ addslashes($doc['name']) }}', '{{ $doc['url'] ?? '' }}')">
                                <i class="bi bi-download"></i> Download
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Option B: Document List Table --}}
        <div class="arc-table-wrapper" id="documentsTableView" style="display: none;">
            <div style="overflow-x: auto;">
                <table class="arc-data-table">
                    <thead>
                        <tr>
                            <th>File Name &amp; Type</th>
                            <th>Organization / Folder</th>
                            <th>File Size</th>
                            <th>Uploaded Date</th>
                            <th>Uploader</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="documentsTableBody">
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
                                $folderName = $doc['folder_name'] ?? 'General Archive';
                            @endphp
                            <tr data-doc-name="{{ strtolower($doc['name']) }}" 
                                data-doc-folder="{{ strtolower($folderName) }}" 
                                data-doc-folder-exact="{{ $folderName }}"
                                data-doc-author="{{ strtolower($doc['author'] ?? '') }}"
                                data-doc-type="{{ $type }}">
                                <td>
                                    <div class="arc-tbl-row-doc">
                                        <div class="arc-file-icon {{ $iconClass }}" style="width: 36px; height: 36px; font-size: 1.15rem; border-radius: 8px;">
                                            <i class="bi {{ $iconBi }}"></i>
                                        </div>
                                        <div>
                                            <div class="arc-tbl-doc-name">{{ $doc['name'] }}</div>
                                            <span class="arc-doc-format-badge" style="font-size: 0.65rem; padding: 0.1rem 0.35rem;">{{ $type }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="arc-doc-folder-pill">{{ $folderName }}</span>
                                </td>
                                <td style="font-weight: 600; color: var(--arc-ink-body);">
                                    {{ $doc['size'] ?? '1.2 MB' }}
                                </td>
                                <td style="font-size: 0.8rem; color: var(--arc-ink-muted);">
                                    {{ $doc['date'] ?? 'Apr 2026' }}
                                </td>
                                <td style="font-size: 0.82rem; font-weight: 600; color: var(--arc-ink-dark);">
                                    {{ $doc['author'] ?? 'Student Org' }}
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div class="arc-tbl-actions">
                                        <button type="button" class="arc-tbl-btn is-preview" onclick="openArchivePreviewModal('{{ addslashes($doc['name']) }}', '{{ addslashes($folderName) }}', '{{ $type }}', '{{ $doc['size'] ?? '1.2 MB' }}', '{{ $doc['date'] ?? 'Recent' }}', '{{ addslashes($doc['author'] ?? 'Student Org') }}', '{{ $doc['url'] ?? '' }}')">
                                            <i class="bi bi-eye"></i> Preview
                                        </button>
                                        <button type="button" class="arc-tbl-btn is-download" onclick="downloadArchiveDoc('{{ addslashes($doc['name']) }}', '{{ $doc['url'] ?? '' }}')">
                                            <i class="bi bi-download"></i> Download
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination (Displays automatically when matching docs > 10) --}}
        <div class="arc-pagination-bar" id="archivePagination" style="display: none;">
            <div class="arc-pagination-info" id="paginationInfoText">
                Showing <strong>1</strong> to <strong>10</strong> of <strong>14</strong> archived documents
            </div>
            <div class="arc-pagination-nav" id="paginationNavButtons">
                <!-- Dynamically populated page buttons -->
            </div>
        </div>

        <div id="docsEmptyState" class="arc-empty-state" style="display: none;">
            <i class="bi bi-file-earmark-x"></i>
            <strong style="display: block; font-size: 1rem; color: var(--arc-ink-dark); margin-bottom: 0.25rem;">No Documents Found</strong>
            <span>No files match your search query in this archive folder.</span>
        </div>
    </section>

    {{-- 5. Document Preview Modal Dialog (Perfect Centered) --}}
    <dialog class="arc-modal" id="archiveDocPreviewModal">
        <div class="arc-modal-box">
            <div class="arc-modal-header">
                <h3><i class="bi bi-file-earmark-check-fill" style="color: var(--arc-maroon);"></i> Document Details</h3>
                <button type="button" class="arc-modal-close" onclick="closeArchivePreviewModal()">&times;</button>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                    <span class="arc-doc-folder-pill" id="prevDocFolder">Organization Folder</span>
                    <span class="arc-doc-format-badge" id="prevDocFormat">PDF</span>
                </div>
                <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--arc-ink-dark); line-height: 1.35; margin: 0 0 0.85rem;" id="prevDocTitle">
                    Document Filename
                </h2>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.15rem; margin-bottom: 1rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.84rem;">
                        <div>
                            <span style="display: block; font-size: 0.72rem; text-transform: uppercase; color: var(--arc-ink-muted); font-weight: 700;">File Size</span>
                            <strong style="color: var(--arc-ink-dark);" id="prevDocSize">2.4 MB</strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 0.72rem; text-transform: uppercase; color: var(--arc-ink-muted); font-weight: 700;">Uploaded On</span>
                            <strong style="color: var(--arc-ink-dark);" id="prevDocDate">Apr 6, 2026</strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 0.72rem; text-transform: uppercase; color: var(--arc-ink-muted); font-weight: 700;">Uploaded By</span>
                            <strong style="color: var(--arc-ink-dark);" id="prevDocAuthor">Officer Name</strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 0.72rem; text-transform: uppercase; color: var(--arc-ink-muted); font-weight: 700;">Archive Status</span>
                            <span style="color: #059669; font-weight: 700; display: inline-flex; align-items: center; gap: 0.25rem;"><i class="bi bi-patch-check-fill"></i> Verified Permanent</span>
                        </div>
                    </div>
                </div>

                <div style="background: #ffffff; border: 1.5px dashed var(--arc-border); border-radius: 12px; padding: 1.5rem; text-align: center; color: var(--arc-ink-muted);">
                    <i class="bi bi-file-earmark-pdf" style="font-size: 2.2rem; color: var(--arc-maroon); display: block; margin-bottom: 0.5rem;"></i>
                    <span style="font-size: 0.86rem; font-weight: 600; display: block; color: var(--arc-ink-dark);">Permanent Compliance Record</span>
                    <small style="font-size: 0.75rem; color: var(--arc-ink-muted);">Encrypted &amp; logged for official institutional review.</small>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.65rem; border-top: 1px solid var(--arc-border-subtle); padding-top: 1rem;">
                <button type="button" class="org-btn org-btn-ghost" onclick="closeArchivePreviewModal()">Close</button>
                <button type="button" class="org-btn org-btn-primary" id="prevDocDownloadBtn">
                    <i class="bi bi-download"></i> Download Document
                </button>
            </div>
        </div>
    </dialog>

    {{-- 6. New Folder Modal Dialog (Perfect Centered) --}}
    <dialog class="arc-modal" id="newFolderModal">
        <div class="arc-modal-box">
            <div class="arc-modal-header">
                <h3><i class="bi bi-folder-plus" style="color: var(--arc-maroon);"></i> Create Archive Folder</h3>
                <button type="button" class="arc-modal-close" onclick="closeNewFolderModal()">&times;</button>
            </div>
            <form onsubmit="handleCreateFolderSubmit(event)">
                <div class="arc-form-group">
                    <label for="folderNameInput">Folder Name *</label>
                    <input type="text" id="folderNameInput" class="arc-form-input" placeholder="e.g., Computer Science Guild" required>
                </div>

                <div class="arc-form-group">
                    <label for="folderOrgInput">Organization Name *</label>
                    <input type="text" id="folderOrgInput" class="arc-form-input" placeholder="e.g., Association of Computing Students" required>
                </div>

                <div class="arc-form-row-2">
                    <div class="arc-form-group">
                        <label for="folderSemesterSelect">Semester Period *</label>
                        <select id="folderSemesterSelect" class="arc-select" style="width: 100%;">
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester" selected>2nd Semester</option>
                            <option value="Midyear">Midyear</option>
                        </select>
                    </div>
                    <div class="arc-form-group">
                        <label for="folderColorSelect">Folder Color Theme</label>
                        <select id="folderColorSelect" class="arc-select" style="width: 100%;">
                            <option value="red">Crimson Red</option>
                            <option value="blue">Royal Blue</option>
                            <option value="green">Emerald Green</option>
                            <option value="violet">Deep Violet</option>
                            <option value="gold">Warm Gold</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem; border-top: 1px solid var(--arc-border-subtle); padding-top: 1rem;">
                    <button type="button" class="org-btn org-btn-ghost" onclick="closeNewFolderModal()">Cancel</button>
                    <button type="submit" class="org-btn org-btn-primary">
                        <i class="bi bi-folder-plus"></i> Create Folder
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- 7. Upload Archive Document Modal Dialog (Perfect Centered) --}}
    <dialog class="arc-modal" id="uploadDocumentModal">
        <div class="arc-modal-box">
            <div class="arc-modal-header">
                <h3><i class="bi bi-cloud-upload-fill" style="color: var(--arc-maroon);"></i> Upload Archived Document</h3>
                <button type="button" class="arc-modal-close" onclick="closeUploadDocumentModal()">&times;</button>
            </div>
            <form onsubmit="handleUploadDocSubmit(event)">
                <div class="arc-form-group">
                    <label for="uploadFolderSelect">Target Archive Folder *</label>
                    <select id="uploadFolderSelect" class="arc-select" style="width: 100%;" required>
                        @foreach ($folders as $f)
                            <option value="{{ $f['name'] }}">{{ $f['name'] }} ({{ $f['semester'] ?? '2nd Semester' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="arc-form-group">
                    <label for="uploadDocTitleInput">Document Title / Subject *</label>
                    <input type="text" id="uploadDocTitleInput" class="arc-form-input" placeholder="e.g., Annual Financial Audit Report 2026" required>
                </div>

                <div class="arc-form-group">
                    <label for="uploadAuthorInput">Uploader / Officer Name</label>
                    <input type="text" id="uploadAuthorInput" class="arc-form-input" placeholder="e.g., Student Officer Name" value="{{ $office->name ?? 'OSO Officer' }}">
                </div>

                <div class="arc-form-group">
                    <label for="uploadFileInput">Choose Document File (.pdf, .docx, .xlsx, .zip) *</label>
                    <input type="file" id="uploadFileInput" class="arc-form-input" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg">
                    <small style="font-size: 0.74rem; color: var(--arc-ink-muted); display: block; margin-top: 0.35rem;">
                        Max file size: 20MB. Accepted formats: PDF, Word, Excel, PowerPoint, ZIP.
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem; border-top: 1px solid var(--arc-border-subtle); padding-top: 1rem;">
                    <button type="button" class="org-btn org-btn-ghost" onclick="closeUploadDocumentModal()">Cancel</button>
                    <button type="submit" class="org-btn org-btn-primary">
                        <i class="bi bi-cloud-arrow-up-fill"></i> Upload &amp; Archive
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Toast Notification Container --}}
    <div class="arc-toast-container" id="arcToastContainer"></div>

    <script>
        let currentActiveFolder = null;
        let currentSearchQuery = '';
        let currentSelectedOrg = '';
        let currentSelectedSemester = '';
        let currentDocView = 'grid'; // 'grid' | 'list'
        const PAGE_SIZE = 10;
        let currentDocPage = 1;

        // =========================================================================
        // View Switcher (Grid vs List Table)
        // =========================================================================
        function setDocumentView(view) {
            currentDocView = view;
            const gridEl = document.getElementById('documentsGrid');
            const tableEl = document.getElementById('documentsTableView');
            const btnGrid = document.getElementById('btnDocGrid');
            const btnList = document.getElementById('btnDocList');

            if (view === 'grid') {
                gridEl.style.display = 'grid';
                tableEl.style.display = 'none';
                btnGrid.classList.add('is-active');
                btnList.classList.remove('is-active');
            } else {
                gridEl.style.display = 'none';
                tableEl.style.display = 'block';
                btnList.classList.add('is-active');
                btnGrid.classList.remove('is-active');
            }

            applyAllFilters();
        }

        // =========================================================================
        // Folder Selection Interaction
        // =========================================================================
        function selectFolderCard(folderName, cardEl) {
            if (currentActiveFolder === folderName) {
                clearFolderSelection();
                return;
            }

            currentActiveFolder = folderName;
            currentDocPage = 1;

            document.querySelectorAll('#archiveFolderGrid .arc-folder-card').forEach(c => c.classList.remove('is-active'));
            if (cardEl) cardEl.classList.add('is-active');

            document.getElementById('activeFolderNameTitle').textContent = `Folder: ${folderName}`;
            document.getElementById('activeFolderSubTitle').textContent = `Showing archived records exclusively filed under ${folderName}.`;
            document.getElementById('resetFolderSelectionBtn').style.display = 'inline-flex';

            const orgDropdown = document.getElementById('orgSelectFilter');
            if (orgDropdown) orgDropdown.value = folderName;
            currentSelectedOrg = folderName;

            applyAllFilters();

            const docsSec = document.getElementById('documentsSection');
            if (docsSec) docsSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function clearFolderSelection() {
            currentActiveFolder = null;
            currentSelectedOrg = '';
            currentDocPage = 1;
            document.querySelectorAll('#archiveFolderGrid .arc-folder-card').forEach(c => c.classList.remove('is-active'));
            document.getElementById('activeFolderNameTitle').textContent = 'All Archived Documents';
            document.getElementById('activeFolderSubTitle').textContent = 'Showing all permanent compliance submissions and verified records.';
            document.getElementById('resetFolderSelectionBtn').style.display = 'none';

            const orgDropdown = document.getElementById('orgSelectFilter');
            if (orgDropdown) orgDropdown.value = '';

            applyAllFilters();
        }

        // =========================================================================
        // Filtering & Pagination (Search, Org, Semester, Pagination)
        // =========================================================================
        function handleGlobalArchiveSearch(query) {
            currentSearchQuery = query.toLowerCase().trim();
            currentDocPage = 1;
            applyAllFilters();
        }

        function handleOrgFilterChange(org) {
            currentSelectedOrg = org;
            currentActiveFolder = org || null;
            currentDocPage = 1;

            document.querySelectorAll('#archiveFolderGrid .arc-folder-card').forEach(card => {
                const name = card.getAttribute('data-folder-name');
                card.classList.toggle('is-active', org && name === org);
            });

            if (org) {
                document.getElementById('activeFolderNameTitle').textContent = `Folder: ${org}`;
                document.getElementById('activeFolderSubTitle').textContent = `Showing archived records exclusively filed under ${org}.`;
                document.getElementById('resetFolderSelectionBtn').style.display = 'inline-flex';
            } else {
                document.getElementById('activeFolderNameTitle').textContent = 'All Archived Documents';
                document.getElementById('activeFolderSubTitle').textContent = 'Showing all permanent compliance submissions and verified records.';
                document.getElementById('resetFolderSelectionBtn').style.display = 'none';
            }

            applyAllFilters();
        }

        function handleSemesterFilterChange(sem) {
            currentSelectedSemester = sem;
            currentDocPage = 1;
            applyAllFilters();
        }

        function goToDocPage(page) {
            currentDocPage = page;
            applyAllFilters();
            const docsSec = document.getElementById('documentsSection');
            if (docsSec) {
                docsSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function applyAllFilters() {
            // 1. Filter Folders
            const folderCards = document.querySelectorAll('#archiveFolderGrid .arc-folder-card');
            let visibleFoldersCount = 0;

            folderCards.forEach(card => {
                const name = (card.getAttribute('data-folder-name') || '').toLowerCase();
                const sem = card.getAttribute('data-folder-semester') || '';

                let matchSearch = !currentSearchQuery || name.includes(currentSearchQuery);
                let matchOrg = !currentSelectedOrg || name === currentSelectedOrg.toLowerCase();
                let matchSem = !currentSelectedSemester || sem === currentSelectedSemester;

                if (matchSearch && matchOrg && matchSem) {
                    card.style.display = '';
                    visibleFoldersCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const foldersEmpty = document.getElementById('foldersEmptyState');
            if (foldersEmpty) foldersEmpty.style.display = visibleFoldersCount === 0 ? 'block' : 'none';

            // 2. Filter & Paginate Documents (Cards & Rows)
            const docCards = Array.from(document.querySelectorAll('#documentsGrid .arc-doc-card'));
            const docRows = Array.from(document.querySelectorAll('#documentsTableBody tr'));

            const checkDocMatch = (el) => {
                const name = el.getAttribute('data-doc-name') || '';
                const folder = el.getAttribute('data-doc-folder') || '';
                const author = el.getAttribute('data-doc-author') || '';

                let matchSearch = !currentSearchQuery || name.includes(currentSearchQuery) || folder.includes(currentSearchQuery) || author.includes(currentSearchQuery);
                let matchFolder = !currentActiveFolder || folder === currentActiveFolder.toLowerCase();
                let matchOrg = !currentSelectedOrg || folder === currentSelectedOrg.toLowerCase();

                return matchSearch && matchFolder && matchOrg;
            };

            const matchingCards = docCards.filter(checkDocMatch);
            const matchingRows = docRows.filter(checkDocMatch);
            const totalMatching = matchingCards.length;

            const totalPages = Math.ceil(totalMatching / PAGE_SIZE);
            if (currentDocPage > totalPages && totalPages > 0) {
                currentDocPage = totalPages;
            }
            if (currentDocPage < 1) {
                currentDocPage = 1;
            }

            const startIndex = (currentDocPage - 1) * PAGE_SIZE;
            const endIndex = Math.min(startIndex + PAGE_SIZE, totalMatching);

            // Hide all documents first
            docCards.forEach(card => card.style.display = 'none');
            docRows.forEach(row => row.style.display = 'none');

            // Show items for the current page only
            for (let i = startIndex; i < endIndex; i++) {
                if (matchingCards[i]) matchingCards[i].style.display = '';
                if (matchingRows[i]) matchingRows[i].style.display = '';
            }

            const docsEmpty = document.getElementById('docsEmptyState');
            if (docsEmpty) docsEmpty.style.display = totalMatching === 0 ? 'block' : 'none';

            // 3. Render Pagination Bar (Only when totalMatching > PAGE_SIZE)
            const paginationBar = document.getElementById('archivePagination');
            const paginationInfo = document.getElementById('paginationInfoText');
            const paginationNav = document.getElementById('paginationNavButtons');

            if (paginationBar && paginationInfo && paginationNav) {
                if (totalMatching > PAGE_SIZE) {
                    paginationBar.style.display = 'flex';
                    paginationInfo.innerHTML = `Showing <strong>${startIndex + 1}</strong> to <strong>${endIndex}</strong> of <strong>${totalMatching}</strong> archived documents`;

                    let navHtml = '';
                    // Previous button
                    navHtml += `<button type="button" class="arc-page-btn" ${currentDocPage === 1 ? 'disabled' : ''} onclick="goToDocPage(${currentDocPage - 1})" title="Previous Page" aria-label="Previous Page"><i class="bi bi-chevron-left"></i></button>`;

                    // Page numbers with smart truncation/ellipsis
                    for (let p = 1; p <= totalPages; p++) {
                        if (totalPages <= 7 || p === 1 || p === totalPages || (p >= currentDocPage - 1 && p <= currentDocPage + 1)) {
                            navHtml += `<button type="button" class="arc-page-btn ${p === currentDocPage ? 'is-active' : ''}" onclick="goToDocPage(${p})">${p}</button>`;
                        } else if (p === currentDocPage - 2 || p === currentDocPage + 2) {
                            navHtml += `<span class="arc-page-ellipsis">&hellip;</span>`;
                        }
                    }

                    // Next button
                    navHtml += `<button type="button" class="arc-page-btn ${currentDocPage === totalPages ? 'disabled' : ''} onclick="goToDocPage(${currentDocPage + 1})" title="Next Page" aria-label="Next Page"><i class="bi bi-chevron-right"></i></button>`;

                    paginationNav.innerHTML = navHtml;
                } else {
                    paginationBar.style.display = 'none';
                }
            }
        }

        // =========================================================================
        // Document Preview Modal Dialog
        // =========================================================================
        function openArchivePreviewModal(title, folder, format, size, date, author, url) {
            document.getElementById('prevDocTitle').textContent = title;
            document.getElementById('prevDocFolder').textContent = folder;
            document.getElementById('prevDocFormat').textContent = format;
            document.getElementById('prevDocSize').textContent = size;
            document.getElementById('prevDocDate').textContent = date;
            document.getElementById('prevDocAuthor').textContent = author;

            document.getElementById('prevDocDownloadBtn').onclick = function() {
                downloadArchiveDoc(title, url);
                closeArchivePreviewModal();
            };

            const dialog = document.getElementById('archiveDocPreviewModal');
            if (dialog) dialog.showModal();
        }

        function closeArchivePreviewModal() {
            const dialog = document.getElementById('archiveDocPreviewModal');
            if (dialog) dialog.close();
        }

        // =========================================================================
        // Create New Folder Modal Dialog
        // =========================================================================
        function openNewFolderModal() {
            const dialog = document.getElementById('newFolderModal');
            if (dialog) dialog.showModal();
        }

        function closeNewFolderModal() {
            const dialog = document.getElementById('newFolderModal');
            if (dialog) dialog.close();
        }

        function handleCreateFolderSubmit(e) {
            e.preventDefault();
            const name = document.getElementById('folderNameInput').value.trim();
            const org = document.getElementById('folderOrgInput').value.trim();
            const semester = document.getElementById('folderSemesterSelect').value;
            const color = document.getElementById('folderColorSelect').value;

            if (!name || !org) return;

            // Insert into Folder Grid
            const grid = document.getElementById('archiveFolderGrid');
            const card = document.createElement('article');
            card.className = 'arc-folder-card';
            card.setAttribute('data-folder-name', name);
            card.setAttribute('data-folder-semester', semester);
            card.onclick = function() { selectFolderCard(name, this); };
            card.style.animation = 'arcSlideUp 0.35s ease';

            card.innerHTML = `
                <div class="arc-folder-icon is-${color}">
                    <i class="bi bi-folder-fill"></i>
                </div>
                <div class="arc-folder-info">
                    <strong class="arc-folder-name">${escapeHtml(name)}</strong>
                    <span class="arc-folder-org">${escapeHtml(org)}</span>
                    <div class="arc-folder-meta">
                        <span class="arc-chip-sem">${escapeHtml(semester)}</span>
                        <span class="doc-count-badge"><strong class="folder-doc-count">0</strong> files</span>
                    </div>
                </div>
            `;
            grid.insertBefore(card, grid.firstChild);

            // Add to dropdowns
            const orgSelect = document.getElementById('orgSelectFilter');
            if (orgSelect) {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                orgSelect.appendChild(opt);
            }

            const uploadFolderSelect = document.getElementById('uploadFolderSelect');
            if (uploadFolderSelect) {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = `${name} (${semester})`;
                uploadFolderSelect.appendChild(opt);
            }

            // Update stats
            const statFolders = document.getElementById('statTotalFolders');
            if (statFolders) statFolders.textContent = parseInt(statFolders.textContent || '0') + 1;

            closeNewFolderModal();
            showArchiveToast(`Archive folder "${name}" successfully created!`, 'success');
        }

        // =========================================================================
        // Upload Document Modal Dialog
        // =========================================================================
        function openUploadDocumentModal() {
            const dialog = document.getElementById('uploadDocumentModal');
            if (dialog) dialog.showModal();
        }

        function closeUploadDocumentModal() {
            const dialog = document.getElementById('uploadDocumentModal');
            if (dialog) dialog.close();
        }

        function handleUploadDocSubmit(e) {
            e.preventDefault();
            const folder = document.getElementById('uploadFolderSelect').value;
            const title = document.getElementById('uploadDocTitleInput').value.trim();
            const author = document.getElementById('uploadAuthorInput').value.trim() || 'OSO Officer';
            const file = document.getElementById('uploadFileInput').files[0];

            if (!title || !file) return;

            const ext = file.name.split('.').pop().toUpperCase();
            const sizeStr = formatBytes(file.size);
            const iconClass = ext === 'PDF' ? 'is-pdf' : (['XLSX', 'XLS', 'CSV'].includes(ext) ? 'is-xlsx' : (['DOC', 'DOCX'].includes(ext) ? 'is-docx' : 'is-other'));
            const iconBi = ext === 'PDF' ? 'bi-file-earmark-pdf-fill' : (['XLSX', 'XLS', 'CSV'].includes(ext) ? 'bi-file-earmark-spreadsheet-fill' : 'bi-file-earmark-word-fill');

            // 1. Insert into Document Grid
            const grid = document.getElementById('documentsGrid');
            const card = document.createElement('article');
            card.className = 'arc-doc-card';
            card.setAttribute('data-doc-name', title.toLowerCase());
            card.setAttribute('data-doc-folder', folder.toLowerCase());
            card.setAttribute('data-doc-folder-exact', folder);
            card.setAttribute('data-doc-author', author.toLowerCase());
            card.setAttribute('data-doc-type', ext);
            card.style.animation = 'arcSlideUp 0.35s ease';

            card.innerHTML = `
                <div>
                    <div class="arc-doc-top">
                        <div class="arc-file-icon ${iconClass}">
                            <i class="bi ${iconBi}"></i>
                        </div>
                        <div class="arc-doc-details">
                            <div class="arc-doc-tag-row">
                                <span class="arc-doc-folder-pill" title="${escapeHtml(folder)}">${escapeHtml(folder)}</span>
                                <span class="arc-doc-format-badge">${ext}</span>
                            </div>
                            <h3 class="arc-doc-name" title="${escapeHtml(title)}">${escapeHtml(title)}</h3>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="arc-doc-info-row">
                        <span><i class="bi bi-person-fill"></i> ${escapeHtml(author)}</span>
                        <span><i class="bi bi-hdd"></i> ${sizeStr}</span>
                        <span><i class="bi bi-calendar3"></i> Just now</span>
                    </div>
                    <div class="arc-doc-actions">
                        <button type="button" class="arc-btn-action-preview" onclick="openArchivePreviewModal('${escapeHtml(title)}', '${escapeHtml(folder)}', '${ext}', '${sizeStr}', 'Just now', '${escapeHtml(author)}', '')">
                            <i class="bi bi-eye"></i> Preview
                        </button>
                        <button type="button" class="arc-btn-action-download" onclick="downloadArchiveDoc('${escapeHtml(title)}', '')">
                            <i class="bi bi-download"></i> Download
                        </button>
                    </div>
                </div>
            `;
            grid.insertBefore(card, grid.firstChild);

            // 2. Insert into Document List Table
            const tbody = document.getElementById('documentsTableBody');
            const row = document.createElement('tr');
            row.setAttribute('data-doc-name', title.toLowerCase());
            row.setAttribute('data-doc-folder', folder.toLowerCase());
            row.setAttribute('data-doc-folder-exact', folder);
            row.setAttribute('data-doc-author', author.toLowerCase());
            row.setAttribute('data-doc-type', ext);
            row.style.animation = 'arcSlideUp 0.35s ease';

            row.innerHTML = `
                <td>
                    <div class="arc-tbl-row-doc">
                        <div class="arc-file-icon ${iconClass}" style="width: 36px; height: 36px; font-size: 1.15rem; border-radius: 8px;">
                            <i class="bi ${iconBi}"></i>
                        </div>
                        <div>
                            <div class="arc-tbl-doc-name">${escapeHtml(title)}</div>
                            <span class="arc-doc-format-badge" style="font-size: 0.65rem; padding: 0.1rem 0.35rem;">${ext}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="arc-doc-folder-pill">${escapeHtml(folder)}</span>
                </td>
                <td style="font-weight: 600; color: var(--arc-ink-body);">
                    ${sizeStr}
                </td>
                <td style="font-size: 0.8rem; color: var(--arc-ink-muted);">
                    Just now
                </td>
                <td style="font-size: 0.82rem; font-weight: 600; color: var(--arc-ink-dark);">
                    ${escapeHtml(author)}
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <div class="arc-tbl-actions">
                        <button type="button" class="arc-tbl-btn is-preview" onclick="openArchivePreviewModal('${escapeHtml(title)}', '${escapeHtml(folder)}', '${ext}', '${sizeStr}', 'Just now', '${escapeHtml(author)}', '')">
                            <i class="bi bi-eye"></i> Preview
                        </button>
                        <button type="button" class="arc-tbl-btn is-download" onclick="downloadArchiveDoc('${escapeHtml(title)}', '')">
                            <i class="bi bi-download"></i> Download
                        </button>
                    </div>
                </td>
            `;
            tbody.insertBefore(row, tbody.firstChild);

            // Increment folder file count
            const folderCard = document.querySelector(`#archiveFolderGrid .arc-folder-card[data-folder-name="${folder}"] .folder-doc-count`);
            if (folderCard) folderCard.textContent = parseInt(folderCard.textContent || '0') + 1;

            // Increment total documents
            const statTotal = document.getElementById('statTotalDocs');
            if (statTotal) statTotal.textContent = parseInt(statTotal.textContent || '0') + 1;

            currentDocPage = 1;
            applyAllFilters();

            closeUploadDocumentModal();
            showArchiveToast(`Document "${title}" uploaded to ${folder}!`, 'success');
        }

        // =========================================================================
        // Download and Toast Helpers
        // =========================================================================
        function downloadArchiveDoc(name, url) {
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

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize view & pagination on DOM load
        document.addEventListener('DOMContentLoaded', () => {
            applyAllFilters();
        });
    </script>
@endsection
