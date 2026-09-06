@extends('org.layout')

@php
    $isOso = ($office->office_role ?? '') === 'oso' || ($brand['role'] ?? '') === 'OSO Officer';
@endphp

@section('title', 'Updates & Announcements')

@section('header')
    <h1><strong>Updates &amp; Announcements</strong></h1>
    <p class="org-welcome">
        @if ($isOso)
            Broadcast official notices, deadlines, guidelines, and manage official template documents for student organizations.
        @else
            Official announcements, notices, and compliance template downloads from the Office of Student Organizations.
        @endif
    </p>
@endsection

@section('actions')
    <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
        <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="scrollToTemplates()">
            <i class="bi bi-file-earmark-ruled"></i> Official Templates
        </button>
        @if ($isOso)
            <button type="button" class="org-btn org-btn-primary org-btn-sm" onclick="focusAnnouncementComposer()">
                <i class="bi bi-megaphone-fill"></i> Create Announcement
            </button>
        @endif
    </div>
@endsection

@section('content')
    <style>
        /* ==========================================================================
           Impeccable & Unslop Design System for Updates & Announcements
           ========================================================================== */
        
        :root {
            --aso-maroon: #8b1828;
            --aso-maroon-dark: #62101c;
            --aso-maroon-light: #fdf0f2;
            --aso-maroon-border: #f2dfe2;
            --aso-ink-dark: #1a1618;
            --aso-ink-body: #3f3538;
            --aso-ink-muted: #7a7074;
            --aso-card-bg: #ffffff;
            --aso-border: #f0e6e8;
            --aso-border-subtle: #f9f2f4;
            --aso-radius-lg: 20px;
            --aso-radius-md: 14px;
            --aso-radius-sm: 10px;
            --aso-shadow-sm: 0 4px 16px rgba(90, 15, 30, 0.03);
            --aso-shadow-md: 0 8px 24px rgba(90, 15, 30, 0.06);
            --aso-shadow-hover: 0 12px 32px rgba(90, 15, 30, 0.08);
        }

        /* Composer / Form Section */
        .aso-composer {
            background: #ffffff;
            border: 1.5px solid var(--aso-border);
            border-radius: var(--aso-radius-lg);
            padding: 1.75rem;
            margin-bottom: 2rem;
            box-shadow: var(--aso-shadow-md);
            position: relative;
            overflow: hidden;
            transition: all 0.25s ease;
        }

        .aso-composer:focus-within {
            border-color: var(--aso-maroon);
            box-shadow: 0 12px 36px -6px rgba(139, 24, 40, 0.1), 0 0 0 3px rgba(139, 24, 40, 0.06);
        }

        .aso-composer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--aso-border-subtle);
            flex-wrap: wrap;
        }

        .aso-composer-profile {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .aso-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--aso-maroon), var(--aso-maroon-dark));
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(139, 24, 40, 0.2);
            flex-shrink: 0;
            border: 2px solid #ffffff;
        }

        .aso-composer-info strong {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--aso-ink-dark);
        }

        .aso-composer-info span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.78rem;
            color: var(--aso-maroon);
            font-weight: 600;
            background: var(--aso-maroon-light);
            padding: 0.18rem 0.6rem;
            border-radius: 9999px;
            border: 1px solid var(--aso-maroon-border);
            margin-top: 0.2rem;
        }

        .aso-composer-pills-live {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .aso-form-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 0.85rem;
            margin-bottom: 0.85rem;
        }

        @media (max-width: 900px) {
            .aso-form-row {
                grid-template-columns: 1fr;
            }
        }

        .aso-input-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--aso-ink-muted);
            margin-bottom: 0.35rem;
        }

        .aso-input {
            width: 100%;
            border: 1.5px solid var(--aso-border);
            border-radius: var(--aso-radius-sm);
            padding: 0.65rem 0.95rem;
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--aso-ink-dark);
            background: #ffffff;
            transition: all 0.2s ease;
            box-sizing: border-box;
            outline: none;
            font-family: inherit;
        }

        .aso-input:focus {
            border-color: var(--aso-maroon);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.06);
        }

        .aso-select {
            width: 100%;
            border: 1.5px solid var(--aso-border);
            border-radius: var(--aso-radius-sm);
            padding: 0.65rem 0.95rem;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--aso-ink-dark);
            background: #ffffff;
            transition: all 0.2s ease;
            box-sizing: border-box;
            outline: none;
            cursor: pointer;
            font-family: inherit;
        }

        .aso-select:focus {
            border-color: var(--aso-maroon);
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.06);
        }

        .aso-textarea {
            width: 100%;
            border: 1.5px solid var(--aso-border);
            border-radius: var(--aso-radius-sm);
            padding: 0.9rem 1rem;
            font-size: 0.92rem;
            line-height: 1.55;
            color: var(--aso-ink-dark);
            background: #ffffff;
            min-height: 105px;
            resize: vertical;
            transition: all 0.2s ease;
            box-sizing: border-box;
            outline: none;
            font-family: inherit;
        }

        .aso-textarea:focus {
            border-color: var(--aso-maroon);
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.06);
        }

        /* Attachment preview chip in composer */
        .aso-attach-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--aso-ink-dark);
            margin-top: 0.65rem;
        }

        .aso-attach-chip button {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            padding: 0;
            line-height: 1;
        }

        .aso-attach-chip button:hover {
            color: #b91c1c;
        }

        /* Composer Toolbar */
        .aso-composer-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--aso-border-subtle);
            flex-wrap: wrap;
        }

        .aso-composer-actions-left {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .aso-btn-attach {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 0.95rem;
            border-radius: var(--aso-radius-sm);
            border: 1.5px dashed var(--aso-maroon-border);
            background: var(--aso-maroon-light);
            color: var(--aso-maroon);
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .aso-btn-attach:hover {
            background: #f4e3e6;
            border-color: var(--aso-maroon);
            transform: translateY(-1px);
        }

        .aso-btn-publish {
            background: linear-gradient(135deg, var(--aso-maroon), var(--aso-maroon-dark));
            color: #ffffff;
            border: none;
            padding: 0.65rem 1.65rem;
            border-radius: var(--aso-radius-sm);
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 16px rgba(139, 24, 40, 0.25);
            transition: all 0.2s ease;
        }

        .aso-btn-publish:hover {
            box-shadow: 0 6px 20px rgba(139, 24, 40, 0.35);
            transform: translateY(-1px);
        }

        .aso-btn-publish:active {
            transform: translateY(0);
        }

        /* Badges & Pills */
        .aso-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.22rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .aso-badge-priority-high {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .aso-badge-priority-high::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #ef4444;
            display: inline-block;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
            animation: pulseDot 1.8s infinite;
        }

        .aso-badge-priority-normal {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .aso-badge-type-deadline {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .aso-badge-type-guideline {
            background: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
        }

        .aso-badge-type-reminder {
            background: #faf5ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }

        .aso-badge-type-general {
            background: #f0fdfa;
            color: #0f766e;
            border: 1px solid #99f6e4;
        }

        .aso-badge-type-notice {
            background: var(--aso-maroon-light);
            color: var(--aso-maroon);
            border: 1px solid var(--aso-maroon-border);
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Section Container Cards */
        .aso-section-card {
            background: #ffffff;
            border: 1px solid var(--aso-border);
            border-radius: var(--aso-radius-lg);
            padding: 1.75rem;
            margin-bottom: 2rem;
            box-shadow: var(--aso-shadow-md);
        }

        .aso-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .aso-section-title-group h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--aso-ink-dark);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            letter-spacing: -0.01em;
        }

        .aso-section-title-group p {
            margin: 0.25rem 0 0;
            font-size: 0.85rem;
            color: var(--aso-ink-muted);
        }

        /* Filter Pills & Toolbar */
        .aso-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .aso-filters {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .aso-filter-btn {
            background: #f8fafc;
            border: 1px solid var(--aso-border);
            border-radius: 9999px;
            padding: 0.35rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--aso-ink-body);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .aso-filter-btn:hover {
            background: #f1f5f9;
            color: var(--aso-ink-dark);
            border-color: #cbd5e1;
        }

        .aso-filter-btn.is-active {
            background: var(--aso-maroon);
            color: #ffffff;
            border-color: var(--aso-maroon);
            box-shadow: 0 2px 8px rgba(139, 24, 40, 0.2);
        }

        .aso-search-box {
            position: relative;
            min-width: 220px;
        }

        .aso-search-box i {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--aso-ink-muted);
            font-size: 0.85rem;
            pointer-events: none;
        }

        .aso-search-box input {
            width: 100%;
            border: 1px solid var(--aso-border);
            border-radius: 9999px;
            padding: 0.45rem 0.95rem 0.45rem 2.2rem;
            font-size: 0.85rem;
            color: var(--aso-ink-dark);
            background: #f8fafc;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .aso-search-box input:focus {
            background: #ffffff;
            border-color: var(--aso-maroon);
            box-shadow: 0 0 0 3px rgba(139, 24, 40, 0.06);
        }

        /* View Switcher (Icon-only) */
        .aso-view-switcher {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 2px;
            gap: 2px;
        }

        .aso-view-toggle {
            background: transparent;
            border: none;
            width: 34px;
            height: 32px;
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--aso-ink-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .aso-view-toggle:hover {
            color: var(--aso-ink-dark);
        }

        .aso-view-toggle.is-active {
            background: #ffffff;
            color: var(--aso-maroon);
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
        }

        /* ==========================================================================
           1. Published Official Announcements: Compact Grid Cards View
           ========================================================================== */
        .aso-announce-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.25rem;
        }

        .aso-announce-card {
            background: #ffffff;
            border: 1px solid var(--aso-border);
            border-radius: var(--aso-radius-md);
            padding: 1.35rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--aso-shadow-sm);
            transition: all 0.2s ease;
            position: relative;
        }

        .aso-announce-card:hover {
            transform: translateY(-2px);
            border-color: var(--aso-maroon-border);
            box-shadow: var(--aso-shadow-hover);
        }

        .aso-announce-card.is-high-priority {
            border-top: 3px solid #ef4444;
        }

        .aso-announce-card.is-normal-priority {
            border-top: 3px solid var(--aso-maroon);
        }

        .aso-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.85rem;
            flex-wrap: wrap;
        }

        .aso-card-author {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .aso-card-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--aso-maroon-light);
            color: var(--aso-maroon);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .aso-card-author-name {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--aso-ink-dark);
        }

        .aso-card-time {
            font-size: 0.74rem;
            color: var(--aso-ink-muted);
            font-weight: 500;
        }

        .aso-card-badges {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
            margin-bottom: 0.65rem;
            flex-wrap: wrap;
        }

        .aso-card-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--aso-ink-dark);
            margin: 0 0 0.45rem;
            line-height: 1.35;
            letter-spacing: -0.01em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .aso-card-snippet {
            font-size: 0.85rem;
            line-height: 1.5;
            color: var(--aso-ink-muted);
            margin: 0 0 0.95rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Compact Attachment Indicator */
        .aso-card-attach-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--aso-ink-body);
            margin-bottom: 0.85rem;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .aso-card-attach-pill i {
            color: var(--aso-maroon);
        }

        .aso-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--aso-border-subtle);
        }

        .aso-btn-view-notice {
            background: var(--aso-maroon-light);
            border: 1px solid var(--aso-maroon-border);
            color: var(--aso-maroon);
            font-size: 0.8rem;
            font-weight: 700;
            padding: 0.35rem 0.85rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .aso-btn-view-notice:hover {
            background: var(--aso-maroon);
            color: #ffffff;
            border-color: var(--aso-maroon);
        }

        /* ==========================================================================
           2. Published Official Announcements: Compact List Table View
           ========================================================================== */
        .aso-table-wrapper {
            background: #ffffff;
            border: 1px solid var(--aso-border);
            border-radius: var(--aso-radius-md);
            overflow: hidden;
            box-shadow: var(--aso-shadow-sm);
        }

        .aso-data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        .aso-data-table thead {
            background: #f8fafc;
            border-bottom: 1px solid var(--aso-border);
        }

        .aso-data-table th {
            padding: 0.85rem 1.15rem;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--aso-ink-muted);
            white-space: nowrap;
        }

        .aso-data-table tbody tr {
            border-bottom: 1px solid var(--aso-border-subtle);
            transition: background 0.15s ease;
        }

        .aso-data-table tbody tr:last-child {
            border-bottom: none;
        }

        .aso-data-table tbody tr:hover {
            background: #faf7f8;
        }

        .aso-data-table td {
            padding: 0.9rem 1.15rem;
            vertical-align: middle;
            color: var(--aso-ink-body);
        }

        .aso-tbl-title-cell {
            max-width: 380px;
        }

        .aso-tbl-title-text {
            font-weight: 700;
            color: var(--aso-ink-dark);
            font-size: 0.92rem;
            line-height: 1.3;
        }

        .aso-tbl-teaser {
            font-size: 0.78rem;
            color: var(--aso-ink-muted);
            margin-top: 0.15rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ==========================================================================
           3. Official Template Documents Grid & List Table
           ========================================================================== */
        .aso-template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.25rem;
        }

        .aso-tpl-card {
            background: #ffffff;
            border: 1px solid var(--aso-border);
            border-radius: var(--aso-radius-md);
            padding: 1.35rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--aso-shadow-sm);
            transition: all 0.2s ease;
            position: relative;
        }

        .aso-tpl-card:hover {
            transform: translateY(-2px);
            border-color: var(--aso-maroon-border);
            box-shadow: var(--aso-shadow-hover);
        }

        .aso-tpl-top {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 0.85rem;
        }

        .aso-tpl-filetype-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .aso-tpl-filetype-icon.is-pdf {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .aso-tpl-filetype-icon.is-xlsx {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .aso-tpl-filetype-icon.is-docx {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .aso-tpl-details {
            flex: 1;
            min-width: 0;
        }

        .aso-tpl-tag-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.35rem;
            width: 100%;
        }

        .aso-tpl-category {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--aso-maroon);
            background: var(--aso-maroon-light);
            padding: 0.15rem 0.55rem;
            border-radius: 9999px;
            border: 1px solid var(--aso-maroon-border);
            text-transform: uppercase;
            white-space: nowrap;
        }

        .aso-tpl-format-badge {
            margin-left: auto;
            font-size: 0.68rem;
            font-weight: 800;
            color: #475569;
            background: #f1f5f9;
            padding: 0.14rem 0.55rem;
            border-radius: 6px;
            letter-spacing: 0.03em;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .aso-tpl-name {
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--aso-ink-dark);
            margin: 0;
            line-height: 1.3;
        }

        .aso-tpl-desc {
            font-size: 0.82rem;
            color: var(--aso-ink-muted);
            line-height: 1.45;
            margin: 0.5rem 0 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .aso-tpl-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            color: var(--aso-ink-muted);
            font-weight: 600;
            padding-top: 0.75rem;
            border-top: 1px solid var(--aso-border-subtle);
            margin-bottom: 0.85rem;
        }

        .aso-tpl-actions {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 0.6rem;
        }

        .aso-btn-preview {
            background: #f8fafc;
            border: 1px solid var(--aso-border);
            border-radius: var(--aso-radius-sm);
            padding: 0.45rem 0.75rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--aso-ink-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .aso-btn-preview:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .aso-btn-download {
            background: var(--aso-maroon-light);
            border: 1px solid var(--aso-maroon-border);
            border-radius: var(--aso-radius-sm);
            padding: 0.45rem 0.75rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--aso-maroon);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .aso-btn-download:hover {
            background: var(--aso-maroon);
            color: #ffffff;
            border-color: var(--aso-maroon);
            box-shadow: 0 2px 8px rgba(139, 24, 40, 0.2);
        }

        /* Row Actions in Tables */
        .aso-tbl-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .aso-tbl-btn-action {
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

        .aso-tbl-btn-action.is-preview {
            background: #f1f5f9;
            color: var(--aso-ink-dark);
            border-color: #e2e8f0;
        }

        .aso-tbl-btn-action.is-preview:hover {
            background: #e2e8f0;
        }

        .aso-tbl-btn-action.is-download {
            background: var(--aso-maroon-light);
            color: var(--aso-maroon);
            border-color: var(--aso-maroon-border);
        }

        .aso-tbl-btn-action.is-download:hover {
            background: var(--aso-maroon);
            color: #ffffff;
            border-color: var(--aso-maroon);
        }

        /* Modal Dialog - Perfectly Centered in Viewport */
        .aso-modal {
            border: none;
            border-radius: 20px;
            padding: 0;
            background: transparent;
            max-width: 580px;
            width: 92%;
            margin: auto;
            position: fixed;
            inset: 0;
            outline: none;
            box-shadow: none;
            overflow: visible;
        }

        .aso-modal[open] {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .aso-modal::backdrop {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(5px);
        }

        .aso-modal-box {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid var(--aso-border);
            padding: 1.85rem;
            box-shadow: 0 24px 60px -12px rgba(15, 23, 42, 0.25);
            width: 100%;
            max-height: 88vh;
            overflow-y: auto;
            box-sizing: border-box;
            animation: asoModalPop 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes asoModalPop {
            from {
                opacity: 0;
                transform: scale(0.96) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .aso-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--aso-border-subtle);
            margin-bottom: 1.25rem;
        }

        .aso-modal-header h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--aso-ink-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .aso-modal-close {
            background: none;
            border: none;
            font-size: 1.35rem;
            color: var(--aso-ink-muted);
            cursor: pointer;
            line-height: 1;
            padding: 0.2rem;
            border-radius: 6px;
        }

        .aso-modal-close:hover {
            color: var(--aso-ink-dark);
            background: #f1f5f9;
        }

        /* Toast notification */
        .aso-toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: none;
        }

        .aso-toast {
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
            animation: slideUpToast 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .aso-toast.is-success {
            border-left: 4px solid #10b981;
        }

        .aso-toast.is-info {
            border-left: 4px solid #3b82f6;
        }

        @keyframes slideUpToast {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Empty State */
        .aso-empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--aso-ink-muted);
        }

        .aso-empty-state i {
            font-size: 2.5rem;
            color: #cbd5e1;
            margin-bottom: 0.75rem;
            display: block;
        }
    </style>

    {{-- 1. Create Announcement: Official Notice / Composer Form --}}
    @if ($isOso)
        <section class="aso-composer" id="composerSection">
            <div class="aso-composer-header">
                <div class="aso-composer-profile">
                    <div class="aso-avatar">{{ $office->initials() }}</div>
                    <div class="aso-composer-info">
                        <strong>{{ $office->name }} <i class="bi bi-patch-check-fill" style="color: #2563eb;" title="Verified University Entity"></i></strong>
                        <span><i class="bi bi-broadcast"></i> Broadcast to All 48 Student Organizations</span>
                    </div>
                </div>
                <div class="aso-composer-pills-live">
                    <span class="aso-badge aso-badge-type-notice" id="composerTypeBadgePreview">
                        <i class="bi bi-tag-fill"></i> General Announcement
                    </span>
                    <span class="aso-badge aso-badge-priority-normal" id="composerPriorityBadgePreview">
                        Normal Priority
                    </span>
                </div>
            </div>

            <form id="announcementComposerForm" onsubmit="handlePublishAnnouncement(event)">
                <div class="aso-form-row">
                    <div class="aso-input-group">
                        <label for="postTitle">Announcement Title / Subject *</label>
                        <input type="text" id="postTitle" class="aso-input" placeholder="e.g., Extended Activity Proposal Deadline for 2nd Semester" required>
                    </div>

                    <div class="aso-input-group">
                        <label for="postTypeSelect">Announcement Type *</label>
                        <select id="postTypeSelect" class="aso-select" onchange="updateComposerTypePreview(this.value)">
                            <option value="General Announcement">General Announcement</option>
                            <option value="Deadline">Deadline Notice</option>
                            <option value="Guideline">Policy &amp; Guideline</option>
                            <option value="Reminder">Compliance Reminder</option>
                            <option value="Official Notice">Official Notice</option>
                        </select>
                    </div>

                    <div class="aso-input-group">
                        <label for="postPrioritySelect">Announcement Priority *</label>
                        <select id="postPrioritySelect" class="aso-select" onchange="updateComposerPriorityPreview(this.value)">
                            <option value="normal">Normal Priority</option>
                            <option value="high">High Priority (Urgent)</option>
                        </select>
                    </div>
                </div>

                <div class="aso-input-group">
                    <label for="postBody">Official Announcement Details &amp; Directives *</label>
                    <textarea id="postBody" class="aso-textarea" placeholder="Write the complete details, guidelines, requirements, schedule breakdown, or instructions for student leaders..." required></textarea>
                </div>

                {{-- Attached File Preview Chip --}}
                <div id="attachedFileContainer" style="display: none;">
                    <div class="aso-attach-chip">
                        <i class="bi bi-paperclip" style="color: var(--aso-maroon);"></i>
                        <span id="attachedFileName">Document.pdf</span>
                        <span style="color: var(--aso-ink-muted); font-size: 0.75rem;" id="attachedFileSize">(1.2 MB)</span>
                        <button type="button" onclick="removeAttachedFile()" title="Remove file">&times;</button>
                    </div>
                </div>

                <div class="aso-composer-footer">
                    <div class="aso-composer-actions-left">
                        <button type="button" class="aso-btn-attach" onclick="document.getElementById('postFileInput').click()">
                            <i class="bi bi-paperclip"></i> <span id="attachButtonLabel">Attach Supporting File</span>
                        </button>
                        <input type="file" id="postFileInput" style="display: none;" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" onchange="handleComposerFileChange(this)">
                        <span style="font-size: 0.78rem; color: var(--aso-ink-muted);">PDF, Word, Excel, or Image (Max 15MB)</span>
                    </div>

                    <div style="display: flex; gap: 0.65rem; align-items: center;">
                        <button type="button" class="org-btn org-btn-ghost org-btn-sm" onclick="resetComposerForm()">
                            Clear
                        </button>
                        <button type="submit" class="aso-btn-publish" id="publishSubmitBtn">
                            <i class="bi bi-send-fill"></i> Publish Announcement
                        </button>
                    </div>
                </div>
            </form>
        </section>
    @endif

    {{-- 2. Published Official Announcements (With Grid & List Table Options) --}}
    <section class="aso-section-card">
        <div class="aso-section-header">
            <div class="aso-section-title-group">
                <h2><i class="bi bi-megaphone-fill" style="color: var(--aso-maroon);"></i> Published Official Announcements</h2>
                <p>Broadcast feed of official guidelines, deadlines, memos, and directives for student organizations.</p>
            </div>
            <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
                <div class="aso-search-box" style="min-width: 190px;">
                    <i class="bi bi-search"></i>
                    <input type="text" id="announcementSearchInput" placeholder="Search notices..." oninput="handleSearchAnnouncements(this.value)">
                </div>

                {{-- View Switcher for Announcements (Icon-only on the right) --}}
                <div class="aso-view-switcher" role="group" aria-label="Announcements View Options">
                    <button type="button" class="aso-view-toggle is-active" id="btnAnnounceGrid" onclick="setAnnouncementView('grid')" title="Card Grid View" aria-label="Grid View">
                        <i class="bi bi-grid-fill"></i>
                    </button>
                    <button type="button" class="aso-view-toggle" id="btnAnnounceList" onclick="setAnnouncementView('list')" title="List Table View" aria-label="List Table View">
                        <i class="bi bi-view-list"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toolbar: Filter Pills --}}
        <div class="aso-toolbar">
            <div class="aso-filters" id="feedFilterButtons">
                <button type="button" class="aso-filter-btn is-active" data-filter="all" onclick="filterAnnouncements('all', this)">
                    All Updates ({{ count($announcements) }})
                </button>
                <button type="button" class="aso-filter-btn" data-filter="high" onclick="filterAnnouncements('high', this)">
                    <i class="bi bi-exclamation-circle-fill" style="color: #ef4444;"></i> High Priority
                </button>
                <button type="button" class="aso-filter-btn" data-filter="Deadline" onclick="filterAnnouncements('Deadline', this)">
                    ⏳ Deadlines
                </button>
                <button type="button" class="aso-filter-btn" data-filter="Guideline" onclick="filterAnnouncements('Guideline', this)">
                    📋 Guidelines
                </button>
                <button type="button" class="aso-filter-btn" data-filter="Reminder" onclick="filterAnnouncements('Reminder', this)">
                    🔔 Reminders
                </button>
                <button type="button" class="aso-filter-btn" data-filter="General" onclick="filterAnnouncements('General', this)">
                    📢 General
                </button>
            </div>
        </div>

        {{-- View Option A: Compact Announcements Grid --}}
        <div class="aso-announce-grid" id="announcementsGrid">
            @foreach ($announcements as $item)
                @php
                    $isHigh = ($item['priority'] ?? '') === 'high';
                    $type = $item['type'] ?? 'General Announcement';
                    $typeBadgeClass = match (true) {
                        str_contains($type, 'Deadline') => 'aso-badge-type-deadline',
                        str_contains($type, 'Guideline') => 'aso-badge-type-guideline',
                        str_contains($type, 'Reminder') => 'aso-badge-type-reminder',
                        str_contains($type, 'Notice') => 'aso-badge-type-notice',
                        default => 'aso-badge-type-general',
                    };
                    $announceId = $item['id'] ?? $loop->index;
                @endphp
                <article class="aso-announce-card {{ $isHigh ? 'is-high-priority' : 'is-normal-priority' }}" 
                         data-priority="{{ $item['priority'] ?? 'normal' }}" 
                         data-type="{{ $type }}" 
                         data-title="{{ strtolower($item['title']) }}" 
                         data-body="{{ strtolower($item['body']) }}"
                         data-id="{{ $announceId }}">
                    <div>
                        <div class="aso-card-head">
                            <div class="aso-card-author">
                                <div class="aso-card-avatar"><i class="bi bi-building"></i></div>
                                <span class="aso-card-author-name">{{ $item['author'] }}</span>
                            </div>
                            <span class="aso-card-time">{{ $item['time'] }}</span>
                        </div>

                        <div class="aso-card-badges">
                            <span class="aso-badge {{ $typeBadgeClass }}">
                                {{ $type }}
                            </span>
                            @if ($isHigh)
                                <span class="aso-badge aso-badge-priority-high">HIGH</span>
                            @else
                                <span class="aso-badge aso-badge-priority-normal">NORMAL</span>
                            @endif
                        </div>

                        <h3 class="aso-card-title">{{ $item['title'] }}</h3>
                        <p class="aso-card-snippet">{{ $item['body'] }}</p>

                        @if (!empty($item['attachment']))
                            <div class="aso-card-attach-pill" title="{{ $item['attachment'] }}">
                                <i class="bi bi-paperclip"></i>
                                <span>{{ $item['attachment'] }}</span>
                                <span style="color: var(--aso-ink-muted); font-size: 0.7rem;">({{ $item['attachment_size'] ?? 'Doc' }})</span>
                            </div>
                        @endif
                    </div>

                    <div class="aso-card-footer">
                        <button type="button" class="aso-btn-view-notice" onclick="openAnnouncementDetailModal('{{ addslashes($item['title']) }}', '{{ $type }}', '{{ $item['priority'] ?? 'normal' }}', '{{ addslashes($item['author']) }}', '{{ $item['time'] }}', '{{ addslashes($item['body']) }}', '{{ addslashes($item['attachment'] ?? '') }}', '{{ $item['attachment_size'] ?? '' }}')">
                            <span>View Notice</span> <i class="bi bi-arrow-right"></i>
                        </button>
                        <div style="display: flex; gap: 0.35rem;">
                            @if (!empty($item['attachment']))
                                <button type="button" class="aso-action-btn" title="Download Attachment" onclick="downloadAttachment('{{ $item['attachment'] }}')">
                                    <i class="bi bi-download"></i>
                                </button>
                            @endif
                            <button type="button" class="aso-action-btn" title="Copy Link" onclick="copyAnnouncementLink('{{ addslashes($item['title']) }}')">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- View Option B: Compact Announcements List Table --}}
        <div class="aso-table-wrapper" id="announcementsTableView" style="display: none;">
            <div style="overflow-x: auto;">
                <table class="aso-data-table">
                    <thead>
                        <tr>
                            <th>Priority &amp; Type</th>
                            <th>Announcement Title &amp; Summary</th>
                            <th>Author</th>
                            <th>Published</th>
                            <th>Attachment</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="announcementsTableBody">
                        @foreach ($announcements as $item)
                            @php
                                $isHigh = ($item['priority'] ?? '') === 'high';
                                $type = $item['type'] ?? 'General Announcement';
                                $typeBadgeClass = match (true) {
                                    str_contains($type, 'Deadline') => 'aso-badge-type-deadline',
                                    str_contains($type, 'Guideline') => 'aso-badge-type-guideline',
                                    str_contains($type, 'Reminder') => 'aso-badge-type-reminder',
                                    str_contains($type, 'Notice') => 'aso-badge-type-notice',
                                    default => 'aso-badge-type-general',
                                };
                                $announceId = $item['id'] ?? $loop->index;
                            @endphp
                            <tr data-priority="{{ $item['priority'] ?? 'normal' }}" 
                                data-type="{{ $type }}" 
                                data-title="{{ strtolower($item['title']) }}" 
                                data-body="{{ strtolower($item['body']) }}"
                                data-id="{{ $announceId }}">
                                <td style="white-space: nowrap;">
                                    <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                        <span class="aso-badge {{ $typeBadgeClass }}">{{ $type }}</span>
                                        @if ($isHigh)
                                            <span class="aso-badge aso-badge-priority-high">HIGH</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="aso-tbl-title-cell">
                                    <div class="aso-tbl-title-text">{{ $item['title'] }}</div>
                                    <div class="aso-tbl-teaser">{{ $item['body'] }}</div>
                                </td>
                                <td style="white-space: nowrap; font-size: 0.82rem; font-weight: 600; color: var(--aso-ink-dark);">
                                    {{ $item['author'] }}
                                </td>
                                <td style="white-space: nowrap; font-size: 0.8rem; color: var(--aso-ink-muted);">
                                    {{ $item['time'] }}
                                </td>
                                <td style="white-space: nowrap;">
                                    @if (!empty($item['attachment']))
                                        <button type="button" class="aso-tbl-btn-action is-download" title="{{ $item['attachment'] }}" onclick="downloadAttachment('{{ $item['attachment'] }}')">
                                            <i class="bi bi-paperclip"></i> {{ $item['attachment_size'] ?? 'Doc' }}
                                        </button>
                                    @else
                                        <span style="color: var(--aso-ink-muted); font-size: 0.8rem;">—</span>
                                    @endif
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div class="aso-tbl-actions">
                                        <button type="button" class="aso-tbl-btn-action is-preview" onclick="openAnnouncementDetailModal('{{ addslashes($item['title']) }}', '{{ $type }}', '{{ $item['priority'] ?? 'normal' }}', '{{ addslashes($item['author']) }}', '{{ $item['time'] }}', '{{ addslashes($item['body']) }}', '{{ addslashes($item['attachment'] ?? '') }}', '{{ $item['attachment_size'] ?? '' }}')">
                                            <i class="bi bi-eye"></i> View Notice
                                        </button>
                                        <button type="button" class="aso-action-btn" title="Copy Link" onclick="copyAnnouncementLink('{{ addslashes($item['title']) }}')">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div id="feedEmptyState" class="aso-empty-state" style="display: none;">
            <i class="bi bi-inbox"></i>
            <strong style="display: block; font-size: 1rem; color: var(--aso-ink-dark); margin-bottom: 0.25rem;">No Announcements Found</strong>
            <span>Try clearing your search query or selecting another filter category.</span>
        </div>
    </section>

    {{-- 3. Official Template Documents Section (With Grid & List Table Options) --}}
    <section class="aso-section-card" id="templatesSection">
        <div class="aso-section-header">
            <div class="aso-section-title-group">
                <h2><i class="bi bi-file-earmark-ruled-fill" style="color: var(--aso-maroon);"></i> Official Template Documents</h2>
                <p>Standard university templates for activity proposals, budget allocation, attendance sheets, and accomplishment reports.</p>
            </div>
            <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
                <div class="aso-search-box" style="min-width: 190px;">
                    <i class="bi bi-search"></i>
                    <input type="text" id="templateSearchInput" placeholder="Filter templates..." oninput="handleSearchTemplates(this.value)">
                </div>

                @if ($isOso)
                    <button type="button" class="org-btn org-btn-primary org-btn-sm" onclick="openUploadTemplateModal()">
                        <i class="bi bi-cloud-upload-fill"></i> Upload Template Document
                    </button>
                @endif

                {{-- Grid vs List Table View Switcher (Icon-only, Right End) --}}
                <div class="aso-view-switcher" role="group" aria-label="Template View Options">
                    <button type="button" class="aso-view-toggle is-active" id="btnViewGrid" onclick="setTemplateView('grid')" title="Card Grid View" aria-label="Grid View">
                        <i class="bi bi-grid-fill"></i>
                    </button>
                    <button type="button" class="aso-view-toggle" id="btnViewList" onclick="setTemplateView('list')" title="List Table View" aria-label="List Table View">
                        <i class="bi bi-view-list"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Option A: Grid View --}}
        <div class="aso-template-grid" id="templateGrid">
            @foreach ($templates as $tpl)
                @php
                    $format = $tpl['format'] ?? 'DOCX';
                    $iconClass = match($format) {
                        'PDF' => 'is-pdf',
                        'XLSX' => 'is-xlsx',
                        default => 'is-docx',
                    };
                @endphp
                <article class="aso-tpl-card" data-name="{{ strtolower($tpl['name']) }}" data-category="{{ strtolower($tpl['category']) }}" data-id="{{ $tpl['id'] ?? $loop->index }}">
                    <div>
                        <div class="aso-tpl-top">
                            <div class="aso-tpl-filetype-icon {{ $iconClass }}">
                                <i class="bi bi-{{ $tpl['icon'] ?? 'file-earmark-text-fill' }}"></i>
                            </div>
                            <div class="aso-tpl-details">
                                <div class="aso-tpl-tag-row">
                                    <span class="aso-tpl-category">{{ $tpl['category'] }}</span>
                                    <span class="aso-tpl-format-badge">{{ $format }}</span>
                                </div>
                                <h3 class="aso-tpl-name">{{ $tpl['name'] }}</h3>
                            </div>
                        </div>
                        <p class="aso-tpl-desc">{{ $tpl['description'] ?? 'Official standardized template issued by the Office of Student Organizations.' }}</p>
                    </div>

                    <div>
                        <div class="aso-tpl-meta">
                            <span><i class="bi bi-hdd"></i> {{ $tpl['size'] }}</span>
                            <span><i class="bi bi-arrow-down-circle"></i> <strong class="tpl-download-count">{{ $tpl['downloads'] }}</strong> downloads</span>
                        </div>
                        <div class="aso-tpl-actions">
                            <button type="button" class="aso-btn-preview" onclick="openTemplatePreviewModal('{{ addslashes($tpl['name']) }}', '{{ $tpl['category'] }}', '{{ $format }}', '{{ $tpl['size'] }}', '{{ $tpl['updated'] ?? 'Recent' }}', '{{ addslashes($tpl['description'] ?? '') }}')">
                                <i class="bi bi-eye"></i> Preview
                            </button>
                            <button type="button" class="aso-btn-download" onclick="downloadTemplateFile('{{ addslashes($tpl['name']) }}', '{{ $tpl['id'] ?? $loop->index }}')">
                                <i class="bi bi-download"></i> Download
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Option B: List Table View --}}
        <div class="aso-table-wrapper" id="templateTableView" style="display: none;">
            <div style="overflow-x: auto;">
                <table class="aso-data-table">
                    <thead>
                        <tr>
                            <th>Document Title &amp; Description</th>
                            <th>Category</th>
                            <th>Format</th>
                            <th>File Size</th>
                            <th>Downloads</th>
                            <th>Updated</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="templateTableBody">
                        @foreach ($templates as $tpl)
                            @php
                                $format = $tpl['format'] ?? 'DOCX';
                                $iconClass = match($format) {
                                    'PDF' => 'is-pdf',
                                    'XLSX' => 'is-xlsx',
                                    default => 'is-docx',
                                };
                            @endphp
                            <tr data-name="{{ strtolower($tpl['name']) }}" data-category="{{ strtolower($tpl['category']) }}" data-id="{{ $tpl['id'] ?? $loop->index }}">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                                        <div class="aso-tpl-filetype-icon {{ $iconClass }}" style="width: 36px; height: 36px; font-size: 1.15rem; border-radius: 10px;">
                                            <i class="bi bi-{{ $tpl['icon'] ?? 'file-earmark-text-fill' }}"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: var(--aso-ink-dark); font-size: 0.92rem; line-height: 1.3;">{{ $tpl['name'] }}</div>
                                            <div style="font-size: 0.76rem; color: var(--aso-ink-muted); margin-top: 0.15rem;">{{ $tpl['description'] ?? 'Official university template.' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="aso-tpl-category">{{ $tpl['category'] }}</span>
                                </td>
                                <td>
                                    <span class="aso-tpl-format-badge">{{ $format }}</span>
                                </td>
                                <td style="font-weight: 600; color: var(--aso-ink-body);">
                                    {{ $tpl['size'] }}
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: var(--aso-ink-dark);"><i class="bi bi-download" style="color: var(--aso-ink-muted);"></i> <span class="tpl-download-count">{{ $tpl['downloads'] }}</span></span>
                                </td>
                                <td style="font-size: 0.8rem; color: var(--aso-ink-muted);">
                                    {{ $tpl['updated'] ?? 'Aug 2026' }}
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div class="aso-tbl-actions">
                                        <button type="button" class="aso-tbl-btn-action is-preview" onclick="openTemplatePreviewModal('{{ addslashes($tpl['name']) }}', '{{ $tpl['category'] }}', '{{ $format }}', '{{ $tpl['size'] }}', '{{ $tpl['updated'] ?? 'Recent' }}', '{{ addslashes($tpl['description'] ?? '') }}')">
                                            <i class="bi bi-eye"></i> Preview
                                        </button>
                                        <button type="button" class="aso-tbl-btn-action is-download" onclick="downloadTemplateFile('{{ addslashes($tpl['name']) }}', '{{ $tpl['id'] ?? $loop->index }}')">
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

        <div id="templatesEmptyState" class="aso-empty-state" style="display: none;">
            <i class="bi bi-file-earmark-x"></i>
            <strong style="display: block; font-size: 1rem; color: var(--aso-ink-dark); margin-bottom: 0.25rem;">No Templates Matched</strong>
            <span>Check your keyword or browse the official document categories.</span>
        </div>
    </section>

    {{-- Announcement Full Detail Modal --}}
    <dialog class="aso-modal" id="announcementDetailModal">
        <div class="aso-modal-box">
            <div class="aso-modal-header">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span class="aso-badge" id="modalAnnounceTypeBadge">General</span>
                    <span class="aso-badge" id="modalAnnouncePriorityBadge">Normal</span>
                </div>
                <button type="button" class="aso-modal-close" onclick="closeAnnouncementDetailModal()">&times;</button>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--aso-ink-dark); line-height: 1.35; margin: 0 0 0.5rem;" id="modalAnnounceTitle">
                    Announcement Subject
                </h2>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: var(--aso-ink-muted); margin-bottom: 1.15rem;">
                    <span style="font-weight: 700; color: var(--aso-ink-dark);" id="modalAnnounceAuthor">Office of Student Organizations</span>
                    <span>·</span>
                    <span id="modalAnnounceTime">2 hours ago</span>
                    <span>·</span>
                    <span style="color: #10b981; font-weight: 600;"><i class="bi bi-patch-check-fill"></i> Official Notice</span>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; font-size: 0.92rem; color: var(--aso-ink-body); line-height: 1.7; white-space: pre-line;" id="modalAnnounceBody">
                    Announcement body details...
                </div>

                {{-- Attachment box inside detail modal --}}
                <div id="modalAttachmentSection" style="margin-top: 1rem; display: none;">
                    <div style="background: #ffffff; border: 1.5px solid var(--aso-border); border-radius: 10px; padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.65rem; min-width: 0;">
                            <i class="bi bi-file-earmark-arrow-down-fill" style="color: var(--aso-maroon); font-size: 1.25rem;"></i>
                            <div style="min-width: 0;">
                                <strong style="display: block; font-size: 0.88rem; color: var(--aso-ink-dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" id="modalAttachName">Document.pdf</strong>
                                <span style="font-size: 0.75rem; color: var(--aso-ink-muted);" id="modalAttachSize">1.2 MB</span>
                            </div>
                        </div>
                        <button type="button" class="aso-btn-download" id="modalAttachDownloadBtn">
                            <i class="bi bi-download"></i> Download
                        </button>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--aso-border-subtle); padding-top: 1rem;">
                <button type="button" class="aso-action-btn" id="modalCopyLinkBtn">
                    <i class="bi bi-link-45deg"></i> Copy Link
                </button>
                <div style="display: flex; gap: 0.65rem;">
                    <button type="button" class="org-btn org-btn-ghost" onclick="closeAnnouncementDetailModal()">Close</button>
                    <button type="button" class="aso-btn-publish" onclick="showToast('Notice acknowledged', 'info'); closeAnnouncementDetailModal();">
                        <i class="bi bi-check2-circle"></i> Acknowledge Notice
                    </button>
                </div>
            </div>
        </div>
    </dialog>

    {{-- Upload Official Template Modal --}}
    @if ($isOso)
        <dialog class="aso-modal" id="uploadTemplateModal">
            <div class="aso-modal-box">
                <div class="aso-modal-header">
                    <h3><i class="bi bi-cloud-upload-fill" style="color: var(--aso-maroon);"></i> Upload Official Template</h3>
                    <button type="button" class="aso-modal-close" onclick="closeUploadTemplateModal()">&times;</button>
                </div>
                <form onsubmit="handleUploadTemplateSubmit(event)">
                    <div class="aso-input-group" style="margin-bottom: 1rem;">
                        <label for="newTplName">Template / Document Title *</label>
                        <input type="text" id="newTplName" class="aso-input" placeholder="e.g., Financial Liquidation &amp; Receipt Form" required>
                    </div>

                    <div class="aso-form-row" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
                        <div class="aso-input-group">
                            <label for="newTplCategory">Document Category *</label>
                            <select id="newTplCategory" class="aso-select" required>
                                <option value="Proposal">Activity Proposal</option>
                                <option value="Finance">Finance &amp; Budget</option>
                                <option value="Forms">Registration &amp; Forms</option>
                                <option value="Report">Accomplishment &amp; Reports</option>
                                <option value="Compliance">Clearance &amp; Compliance</option>
                            </select>
                        </div>
                        <div class="aso-input-group">
                            <label for="newTplFormat">File Format *</label>
                            <select id="newTplFormat" class="aso-select" required>
                                <option value="PDF">PDF (.pdf)</option>
                                <option value="XLSX">Excel Spreadsheet (.xlsx)</option>
                                <option value="DOCX">Word Document (.docx)</option>
                            </select>
                        </div>
                    </div>

                    <div class="aso-input-group" style="margin-bottom: 1rem;">
                        <label for="newTplDesc">Description / Instructions</label>
                        <textarea id="newTplDesc" class="aso-textarea" style="min-height: 70px;" placeholder="Brief instructions for student officers when utilizing this template..."></textarea>
                    </div>

                    <div class="aso-input-group" style="margin-bottom: 1.5rem;">
                        <label for="newTplFile">Choose Document File *</label>
                        <input type="file" id="newTplFile" class="aso-input" required accept=".pdf,.docx,.doc,.xlsx,.xls">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" class="org-btn org-btn-ghost" onclick="closeUploadTemplateModal()">Cancel</button>
                        <button type="submit" class="aso-btn-publish"><i class="bi bi-cloud-arrow-up-fill"></i> Upload &amp; Publish Template</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif

    {{-- Template Preview Modal --}}
    <dialog class="aso-modal" id="templatePreviewModal">
        <div class="aso-modal-box">
            <div class="aso-modal-header">
                <h3><i class="bi bi-file-earmark-ruled-fill" style="color: var(--aso-maroon);"></i> <span id="prevTplTitle">Document Preview</span></h3>
                <button type="button" class="aso-modal-close" onclick="closeTemplatePreviewModal()">&times;</button>
            </div>
            
            <div style="margin-bottom: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <span class="aso-tpl-category" id="prevTplCategory">Proposal</span>
                    <span class="aso-tpl-format-badge" id="prevTplFormat">PDF</span>
                    <span style="font-size: 0.8rem; color: var(--aso-ink-muted); margin-left: auto;" id="prevTplSize">245 KB</span>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; font-size: 0.9rem; color: var(--aso-ink-body); line-height: 1.6;" id="prevTplBody">
                    Official template document details and preview instructions.
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.8rem; color: var(--aso-ink-muted);" id="prevTplUpdated">Updated recently</span>
                <div style="display: flex; gap: 0.65rem;">
                    <button type="button" class="org-btn org-btn-ghost" onclick="closeTemplatePreviewModal()">Close</button>
                    <button type="button" class="aso-btn-publish" id="prevTplDownloadBtn">
                        <i class="bi bi-download"></i> Download Template
                    </button>
                </div>
            </div>
        </div>
    </dialog>

    {{-- Toast Notification Box --}}
    <div class="aso-toast-container" id="toastContainer"></div>

    <script>
        let currentFilter = 'all';
        let currentSearch = '';
        let pendingAttachedFile = null;
        let currentAnnouncementView = 'grid'; // 'grid' | 'list'
        let currentTemplateView = 'grid'; // 'grid' | 'list'

        function scrollToTemplates() {
            const el = document.getElementById('templatesSection');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function focusAnnouncementComposer() {
            const section = document.getElementById('composerSection');
            const titleInput = document.getElementById('postTitle');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (titleInput) titleInput.focus();
            }
        }

        // =========================================================================
        // Announcement View Switcher (Grid vs List Table)
        // =========================================================================
        function setAnnouncementView(view) {
            currentAnnouncementView = view;
            const gridEl = document.getElementById('announcementsGrid');
            const tableEl = document.getElementById('announcementsTableView');
            const btnGrid = document.getElementById('btnAnnounceGrid');
            const btnList = document.getElementById('btnAnnounceList');

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

            applyAnnouncementFilters();
        }

        // =========================================================================
        // Template View Switcher (Grid vs List Table)
        // =========================================================================
        function setTemplateView(view) {
            currentTemplateView = view;
            const gridEl = document.getElementById('templateGrid');
            const tableEl = document.getElementById('templateTableView');
            const btnGrid = document.getElementById('btnViewGrid');
            const btnList = document.getElementById('btnViewList');

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

            handleSearchTemplates(document.getElementById('templateSearchInput')?.value || '');
        }

        function updateComposerTypePreview(type) {
            const badge = document.getElementById('composerTypeBadgePreview');
            if (!badge) return;
            badge.textContent = type;
            badge.className = 'aso-badge ' + getTypeBadgeClass(type);
        }

        function updateComposerPriorityPreview(priority) {
            const badge = document.getElementById('composerPriorityBadgePreview');
            if (!badge) return;
            if (priority === 'high') {
                badge.textContent = 'HIGH PRIORITY';
                badge.className = 'aso-badge aso-badge-priority-high';
            } else {
                badge.textContent = 'Normal Priority';
                badge.className = 'aso-badge aso-badge-priority-normal';
            }
        }

        function getTypeBadgeClass(type) {
            if (type.includes('Deadline')) return 'aso-badge-type-deadline';
            if (type.includes('Guideline')) return 'aso-badge-type-guideline';
            if (type.includes('Reminder')) return 'aso-badge-type-reminder';
            if (type.includes('Notice')) return 'aso-badge-type-notice';
            return 'aso-badge-type-general';
        }

        function handleComposerFileChange(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                pendingAttachedFile = file;
                document.getElementById('attachedFileName').textContent = file.name;
                document.getElementById('attachedFileSize').textContent = `(${formatBytes(file.size)})`;
                document.getElementById('attachedFileContainer').style.display = 'block';
                document.getElementById('attachButtonLabel').textContent = 'Change File';
                showToast(`Attached ${file.name}`, 'info');
            }
        }

        function removeAttachedFile() {
            pendingAttachedFile = null;
            document.getElementById('postFileInput').value = '';
            document.getElementById('attachedFileContainer').style.display = 'none';
            document.getElementById('attachButtonLabel').textContent = 'Attach Supporting File';
        }

        function resetComposerForm() {
            document.getElementById('announcementComposerForm').reset();
            removeAttachedFile();
            updateComposerTypePreview('General Announcement');
            updateComposerPriorityPreview('normal');
        }

        // =========================================================================
        // Publish Announcement
        // =========================================================================
        function handlePublishAnnouncement(e) {
            e.preventDefault();
            const title = document.getElementById('postTitle').value.trim();
            const type = document.getElementById('postTypeSelect').value;
            const priority = document.getElementById('postPrioritySelect').value;
            const body = document.getElementById('postBody').value.trim();
            const author = '{{ $office->name ?? "Office of Student Organizations (OSO)" }}';

            if (!title || !body) return;

            const isHigh = priority === 'high';
            const typeBadgeClass = getTypeBadgeClass(type);
            const newId = 'ann_' + Date.now();
            const attachName = pendingAttachedFile ? pendingAttachedFile.name : '';
            const attachSize = pendingAttachedFile ? formatBytes(pendingAttachedFile.size) : '';

            // 1. Insert into Grid View
            const grid = document.getElementById('announcementsGrid');
            const card = document.createElement('article');
            card.className = `aso-announce-card ${isHigh ? 'is-high-priority' : 'is-normal-priority'}`;
            card.setAttribute('data-priority', priority);
            card.setAttribute('data-type', type);
            card.setAttribute('data-title', title.toLowerCase());
            card.setAttribute('data-body', body.toLowerCase());
            card.setAttribute('data-id', newId);
            card.style.animation = 'slideUpToast 0.35s ease';

            let attachHtmlGrid = '';
            if (pendingAttachedFile) {
                attachHtmlGrid = `
                    <div class="aso-card-attach-pill" title="${escapeHtml(attachName)}">
                        <i class="bi bi-paperclip"></i>
                        <span>${escapeHtml(attachName)}</span>
                        <span style="color: var(--aso-ink-muted); font-size: 0.7rem;">(${attachSize})</span>
                    </div>
                `;
            }

            card.innerHTML = `
                <div>
                    <div class="aso-card-head">
                        <div class="aso-card-author">
                            <div class="aso-card-avatar"><i class="bi bi-building"></i></div>
                            <span class="aso-card-author-name">${escapeHtml(author)}</span>
                        </div>
                        <span class="aso-card-time">Just now</span>
                    </div>
                    <div class="aso-card-badges">
                        <span class="aso-badge ${typeBadgeClass}">${escapeHtml(type)}</span>
                        ${isHigh ? '<span class="aso-badge aso-badge-priority-high">HIGH</span>' : '<span class="aso-badge aso-badge-priority-normal">NORMAL</span>'}
                    </div>
                    <h3 class="aso-card-title">${escapeHtml(title)}</h3>
                    <p class="aso-card-snippet">${escapeHtml(body)}</p>
                    ${attachHtmlGrid}
                </div>
                <div class="aso-card-footer">
                    <button type="button" class="aso-btn-view-notice" onclick="openAnnouncementDetailModal('${escapeHtml(title)}', '${escapeHtml(type)}', '${priority}', '${escapeHtml(author)}', 'Just now', '${escapeHtml(body)}', '${escapeHtml(attachName)}', '${attachSize}')">
                        <span>View Notice</span> <i class="bi bi-arrow-right"></i>
                    </button>
                    <div style="display: flex; gap: 0.35rem;">
                        ${pendingAttachedFile ? `<button type="button" class="aso-action-btn" title="Download Attachment" onclick="downloadAttachment('${escapeHtml(attachName)}')"><i class="bi bi-download"></i></button>` : ''}
                        <button type="button" class="aso-action-btn" title="Copy Link" onclick="copyAnnouncementLink('${escapeHtml(title)}')"><i class="bi bi-link-45deg"></i></button>
                    </div>
                </div>
            `;
            grid.insertBefore(card, grid.firstChild);

            // 2. Insert into List Table View
            const tbody = document.getElementById('announcementsTableBody');
            const row = document.createElement('tr');
            row.setAttribute('data-priority', priority);
            row.setAttribute('data-type', type);
            row.setAttribute('data-title', title.toLowerCase());
            row.setAttribute('data-body', body.toLowerCase());
            row.setAttribute('data-id', newId);
            row.style.animation = 'slideUpToast 0.35s ease';

            row.innerHTML = `
                <td style="white-space: nowrap;">
                    <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                        <span class="aso-badge ${typeBadgeClass}">${escapeHtml(type)}</span>
                        ${isHigh ? '<span class="aso-badge aso-badge-priority-high">HIGH</span>' : ''}
                    </div>
                </td>
                <td class="aso-tbl-title-cell">
                    <div class="aso-tbl-title-text">${escapeHtml(title)}</div>
                    <div class="aso-tbl-teaser">${escapeHtml(body)}</div>
                </td>
                <td style="white-space: nowrap; font-size: 0.82rem; font-weight: 600; color: var(--aso-ink-dark);">
                    ${escapeHtml(author)}
                </td>
                <td style="white-space: nowrap; font-size: 0.8rem; color: var(--aso-ink-muted);">
                    Just now
                </td>
                <td style="white-space: nowrap;">
                    ${pendingAttachedFile ? `<button type="button" class="aso-tbl-btn-action is-download" title="${escapeHtml(attachName)}" onclick="downloadAttachment('${escapeHtml(attachName)}')"><i class="bi bi-paperclip"></i> ${attachSize}</button>` : '<span style="color: var(--aso-ink-muted); font-size: 0.8rem;">—</span>'}
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <div class="aso-tbl-actions">
                        <button type="button" class="aso-tbl-btn-action is-preview" onclick="openAnnouncementDetailModal('${escapeHtml(title)}', '${escapeHtml(type)}', '${priority}', '${escapeHtml(author)}', 'Just now', '${escapeHtml(body)}', '${escapeHtml(attachName)}', '${attachSize}')">
                            <i class="bi bi-eye"></i> View Notice
                        </button>
                        <button type="button" class="aso-action-btn" title="Copy Link" onclick="copyAnnouncementLink('${escapeHtml(title)}')">
                            <i class="bi bi-link-45deg"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.insertBefore(row, tbody.firstChild);

            resetComposerForm();
            showToast('Announcement successfully published to all student organizations!', 'success');
        }

        // =========================================================================
        // Filtering & Searching Announcements
        // =========================================================================
        function filterAnnouncements(filter, btn) {
            currentFilter = filter;
            document.querySelectorAll('#feedFilterButtons .aso-filter-btn').forEach(b => b.classList.remove('is-active'));
            if (btn) btn.classList.add('is-active');
            applyAnnouncementFilters();
        }

        function handleSearchAnnouncements(query) {
            currentSearch = query.toLowerCase().trim();
            applyAnnouncementFilters();
        }

        function applyAnnouncementFilters() {
            const cards = document.querySelectorAll('#announcementsGrid .aso-announce-card');
            const rows = document.querySelectorAll('#announcementsTableBody tr');
            let visibleCount = 0;

            const checkMatch = (item) => {
                const priority = item.getAttribute('data-priority') || '';
                const type = item.getAttribute('data-type') || '';
                const title = item.getAttribute('data-title') || '';
                const body = item.getAttribute('data-body') || '';

                let matchesFilter = true;
                if (currentFilter === 'high') {
                    matchesFilter = (priority === 'high');
                } else if (currentFilter !== 'all') {
                    matchesFilter = type.toLowerCase().includes(currentFilter.toLowerCase());
                }

                let matchesSearch = true;
                if (currentSearch) {
                    matchesSearch = title.includes(currentSearch) || body.includes(currentSearch) || type.toLowerCase().includes(currentSearch);
                }

                return matchesFilter && matchesSearch;
            };

            cards.forEach(card => {
                if (checkMatch(card)) {
                    card.style.display = '';
                    if (currentAnnouncementView === 'grid') visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            rows.forEach(row => {
                if (checkMatch(row)) {
                    row.style.display = '';
                    if (currentAnnouncementView === 'list') visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const emptyState = document.getElementById('feedEmptyState');
            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        // =========================================================================
        // Announcement Full Detail Modal
        // =========================================================================
        function openAnnouncementDetailModal(title, type, priority, author, time, body, attachment, attachSize) {
            document.getElementById('modalAnnounceTitle').textContent = title;
            document.getElementById('modalAnnounceAuthor').textContent = author;
            document.getElementById('modalAnnounceTime').textContent = time;
            document.getElementById('modalAnnounceBody').textContent = body;

            // Badges
            const typeBadge = document.getElementById('modalAnnounceTypeBadge');
            typeBadge.textContent = type;
            typeBadge.className = 'aso-badge ' + getTypeBadgeClass(type);

            const priorityBadge = document.getElementById('modalAnnouncePriorityBadge');
            if (priority === 'high') {
                priorityBadge.textContent = 'HIGH PRIORITY';
                priorityBadge.className = 'aso-badge aso-badge-priority-high';
            } else {
                priorityBadge.textContent = 'NORMAL PRIORITY';
                priorityBadge.className = 'aso-badge aso-badge-priority-normal';
            }

            // Attachment
            const attachSection = document.getElementById('modalAttachmentSection');
            if (attachment && attachment.trim() !== '') {
                document.getElementById('modalAttachName').textContent = attachment;
                document.getElementById('modalAttachSize').textContent = attachSize || 'Attached File';
                document.getElementById('modalAttachDownloadBtn').onclick = function() {
                    downloadAttachment(attachment);
                };
                attachSection.style.display = 'block';
            } else {
                attachSection.style.display = 'none';
            }

            document.getElementById('modalCopyLinkBtn').onclick = function() {
                copyAnnouncementLink(title);
            };

            const dialog = document.getElementById('announcementDetailModal');
            if (dialog) dialog.showModal();
        }

        function closeAnnouncementDetailModal() {
            const dialog = document.getElementById('announcementDetailModal');
            if (dialog) dialog.close();
        }

        // =========================================================================
        // Template Search, Preview & Upload
        // =========================================================================
        function handleSearchTemplates(query) {
            const q = query.toLowerCase().trim();
            const cards = document.querySelectorAll('#templateGrid .aso-tpl-card');
            const rows = document.querySelectorAll('#templateTableBody tr');
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const category = card.getAttribute('data-category') || '';
                if (!q || name.includes(q) || category.includes(q)) {
                    card.style.display = '';
                    if (currentTemplateView === 'grid') visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const category = row.getAttribute('data-category') || '';
                if (!q || name.includes(q) || category.includes(q)) {
                    row.style.display = '';
                    if (currentTemplateView === 'list') visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const emptyState = document.getElementById('templatesEmptyState');
            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        function openTemplatePreviewModal(name, category, format, size, updated, desc) {
            document.getElementById('prevTplTitle').textContent = name;
            document.getElementById('prevTplCategory').textContent = category;
            document.getElementById('prevTplFormat').textContent = format;
            document.getElementById('prevTplSize').textContent = size;
            document.getElementById('prevTplUpdated').textContent = `Last updated: ${updated}`;
            document.getElementById('prevTplBody').textContent = desc || `Official template format for ${name}. All organizations must comply with this standard university template.`;
            document.getElementById('prevTplDownloadBtn').onclick = function() {
                downloadTemplateFile(name);
                closeTemplatePreviewModal();
            };

            const dialog = document.getElementById('templatePreviewModal');
            if (dialog) dialog.showModal();
        }

        function closeTemplatePreviewModal() {
            const dialog = document.getElementById('templatePreviewModal');
            if (dialog) dialog.close();
        }

        function openUploadTemplateModal() {
            const dialog = document.getElementById('uploadTemplateModal');
            if (dialog) dialog.showModal();
        }

        function closeUploadTemplateModal() {
            const dialog = document.getElementById('uploadTemplateModal');
            if (dialog) dialog.close();
        }

        function handleUploadTemplateSubmit(e) {
            e.preventDefault();
            const name = document.getElementById('newTplName').value.trim();
            const category = document.getElementById('newTplCategory').value;
            const format = document.getElementById('newTplFormat').value;
            const desc = document.getElementById('newTplDesc').value.trim();
            const file = document.getElementById('newTplFile').files[0];

            if (!name) return;

            const iconClass = format === 'PDF' ? 'is-pdf' : (format === 'XLSX' ? 'is-xlsx' : 'is-docx');
            const iconBi = format === 'PDF' ? 'bi-file-earmark-pdf-fill' : (format === 'XLSX' ? 'bi-file-earmark-spreadsheet-fill' : 'bi-file-earmark-text-fill');
            const sizeStr = file ? formatBytes(file.size) : '180 KB';
            const newId = 'tpl_' + Date.now();

            // 1. Insert into Grid View
            const grid = document.getElementById('templateGrid');
            const card = document.createElement('article');
            card.className = 'aso-tpl-card';
            card.setAttribute('data-name', name.toLowerCase());
            card.setAttribute('data-category', category.toLowerCase());
            card.setAttribute('data-id', newId);
            card.style.animation = 'slideUpToast 0.35s ease';

            card.innerHTML = `
                <div>
                    <div class="aso-tpl-top">
                        <div class="aso-tpl-filetype-icon ${iconClass}">
                            <i class="bi ${iconBi}"></i>
                        </div>
                        <div class="aso-tpl-details">
                            <div class="aso-tpl-tag-row">
                                <span class="aso-tpl-category">${escapeHtml(category)}</span>
                                <span class="aso-tpl-format-badge">${format}</span>
                            </div>
                            <h3 class="aso-tpl-name">${escapeHtml(name)}</h3>
                        </div>
                    </div>
                    <p class="aso-tpl-desc">${escapeHtml(desc || 'Official template document uploaded by OSO.')}</p>
                </div>
                <div>
                    <div class="aso-tpl-meta">
                        <span><i class="bi bi-hdd"></i> ${sizeStr}</span>
                        <span><i class="bi bi-arrow-down-circle"></i> <strong class="tpl-download-count">0</strong> downloads</span>
                    </div>
                    <div class="aso-tpl-actions">
                        <button type="button" class="aso-btn-preview" onclick="openTemplatePreviewModal('${escapeHtml(name)}', '${escapeHtml(category)}', '${format}', '${sizeStr}', 'Just now', '${escapeHtml(desc)}')">
                            <i class="bi bi-eye"></i> Preview
                        </button>
                        <button type="button" class="aso-btn-download" onclick="downloadTemplateFile('${escapeHtml(name)}', '${newId}')">
                            <i class="bi bi-download"></i> Download
                        </button>
                    </div>
                </div>
            `;
            grid.insertBefore(card, grid.firstChild);

            // 2. Insert into List Table View
            const tbody = document.getElementById('templateTableBody');
            const row = document.createElement('tr');
            row.setAttribute('data-name', name.toLowerCase());
            row.setAttribute('data-category', category.toLowerCase());
            row.setAttribute('data-id', newId);
            row.style.animation = 'slideUpToast 0.35s ease';

            row.innerHTML = `
                <td>
                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                        <div class="aso-tpl-filetype-icon ${iconClass}" style="width: 36px; height: 36px; font-size: 1.15rem; border-radius: 10px;">
                            <i class="bi ${iconBi}"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: var(--aso-ink-dark); font-size: 0.92rem; line-height: 1.3;">${escapeHtml(name)}</div>
                            <div style="font-size: 0.76rem; color: var(--aso-ink-muted); margin-top: 0.15rem;">${escapeHtml(desc || 'Official template document.')}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="aso-tpl-category">${escapeHtml(category)}</span>
                </td>
                <td>
                    <span class="aso-tpl-format-badge">${format}</span>
                </td>
                <td style="font-weight: 600; color: var(--aso-ink-body);">
                    ${sizeStr}
                </td>
                <td>
                    <span style="font-weight: 700; color: var(--aso-ink-dark);"><i class="bi bi-download" style="color: var(--aso-ink-muted);"></i> <span class="tpl-download-count">0</span></span>
                </td>
                <td style="font-size: 0.8rem; color: var(--aso-ink-muted);">
                    Just now
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <div class="aso-tbl-actions">
                        <button type="button" class="aso-tbl-btn-action is-preview" onclick="openTemplatePreviewModal('${escapeHtml(name)}', '${escapeHtml(category)}', '${format}', '${sizeStr}', 'Just now', '${escapeHtml(desc)}')">
                            <i class="bi bi-eye"></i> Preview
                        </button>
                        <button type="button" class="aso-tbl-btn-action is-download" onclick="downloadTemplateFile('${escapeHtml(name)}', '${newId}')">
                            <i class="bi bi-download"></i> Download
                        </button>
                    </div>
                </td>
            `;
            tbody.insertBefore(row, tbody.firstChild);

            closeUploadTemplateModal();
            showToast(`Template "${name}" successfully published!`, 'success');
        }

        // =========================================================================
        // Helpers (Downloads, Copy, Toast, Formatters)
        // =========================================================================
        function downloadTemplateFile(name, templateId) {
            if (templateId !== undefined) {
                const gridCard = document.querySelector(`#templateGrid .aso-tpl-card[data-id="${templateId}"] .tpl-download-count`);
                if (gridCard) gridCard.textContent = parseInt(gridCard.textContent || '0') + 1;

                const tableRow = document.querySelector(`#templateTableBody tr[data-id="${templateId}"] .tpl-download-count`);
                if (tableRow) tableRow.textContent = parseInt(tableRow.textContent || '0') + 1;
            }

            showToast(`Downloading "${name}"...`, 'success');
        }

        function downloadAttachment(name) {
            showToast(`Downloading attachment "${name}"...`, 'success');
        }

        function copyAnnouncementLink(title) {
            navigator.clipboard?.writeText(window.location.href);
            showToast(`Direct link to "${title}" copied to clipboard!`, 'info');
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `aso-toast is-${type}`;
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
    </script>
@endsection
