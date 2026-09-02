@extends('org.layout')

@php
    $isOso = ($office->office_role ?? '') === 'oso' || ($brand['role'] ?? '') === 'OSO Officer';
@endphp

@section('title', 'Updates & Announcements')

@section('header')
    <h1><strong>Updates &amp; Announcements</h1>
    <p class="org-welcome">
        @if ($isOso)
            Broadcast official notices, guidelines, and manage compliance templates for all student organizations.
        @else
            Official announcements, templates, and resources from the Student Organization Office.
        @endif
    </p>
@endsection

@section('actions')
    @if ($isOso)
        <button type="button" class="org-btn org-btn-primary" onclick="focusAnnouncementComposer()">
            <i class="bi bi-megaphone-fill"></i> New Announcement
        </button>
    @endif
@endsection

@section('content')
    <style>
        /* Post Composer Card (Facebook / Social Style) */
        .org-fb-composer-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1.5px solid #f0e6e8;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.75rem;
            box-shadow: 0 8px 24px rgba(90, 15, 30, 0.04);
            transition: all 0.2s ease;
        }

        .org-fb-composer-header {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 1rem;
        }

        .org-fb-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b1828, #4a0d18);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(139, 24, 40, 0.25);
            flex-shrink: 0;
        }

        .org-fb-composer-meta strong {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.96rem;
            font-weight: 700;
            color: #1a1618;
        }

        .org-fb-audience-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: #faf2f4;
            color: #8b1828;
            padding: 0.2rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 600;
            margin-top: 0.2rem;
            border: 1px solid #f2e2e5;
        }

        .org-fb-input-title {
            width: 100%;
            border: 1.5px solid #f0e6e8;
            border-radius: 14px;
            padding: 0.65rem 1rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #1a1618;
            margin-bottom: 0.75rem;
            outline: none;
            transition: border-color 0.15s ease;
            box-sizing: border-box;
        }

        .org-fb-input-title:focus {
            border-color: #8b1828;
        }

        .org-fb-textarea {
            width: 100%;
            border: 1.5px solid #f0e6e8;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            font-size: 0.92rem;
            font-family: inherit;
            color: #1a1618;
            min-height: 90px;
            resize: vertical;
            outline: none;
            transition: border-color 0.15s ease;
            box-sizing: border-box;
        }

        .org-fb-textarea:focus {
            border-color: #8b1828;
        }

        .org-fb-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 0.85rem;
            padding-top: 0.85rem;
            border-top: 1px solid #f7eff0;
            flex-wrap: wrap;
        }

        .org-fb-options {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .org-fb-opt-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            border-radius: 10px;
            border: 1px solid #e8e2e4;
            background: #ffffff;
            color: #554d50;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-fb-opt-btn:hover {
            background: #faf4f5;
            border-color: #d8c2c7;
            color: #8b1828;
        }

        .org-fb-post-btn {
            background: #6a1020;
            color: #ffffff;
            border: none;
            padding: 0.55rem 1.4rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 4px 14px rgba(106, 16, 32, 0.25);
            transition: all 0.15s ease;
        }

        .org-fb-post-btn:hover {
            background: #8b1828;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(106, 16, 32, 0.35);
        }

        /* Template Dialog Styling */
        .org-tpl-dialog {
            border: none;
            border-radius: 24px;
            padding: 0;
            background: transparent;
            max-width: 520px;
            width: 100%;
        }

        .org-tpl-dialog::backdrop {
            background: rgba(26, 10, 13, 0.5);
            backdrop-filter: blur(4px);
        }

        .org-tpl-dialog-content {
            background: #ffffff;
            border-radius: 24px;
            border: 1.5px solid #f0e6e8;
            padding: 1.75rem 2rem;
            box-shadow: 0 20px 48px rgba(90, 15, 30, 0.2);
        }
    </style>

    {{-- OSO Officer Facebook-Style Announcement Composer --}}
    @if ($isOso)
        <div class="org-fb-composer-card" id="composerCard">
            <div class="org-fb-composer-header">
                <div class="org-fb-avatar">{{ $office->initials() }}</div>
                <div class="org-fb-composer-meta">
                    <strong>{{ $office->name }} <i class="bi bi-patch-check-fill" style="color: #2563eb;"></i></strong>
                    <span class="org-fb-audience-pill">
                        <i class="bi bi-globe2"></i> Broadcast to All Student Organizations
                    </span>
                </div>
            </div>

            <form id="announcementPostForm" onsubmit="handlePostAnnouncement(event)">
                <textarea id="postContent" class="org-fb-textarea" placeholder="What's on your mind? Post an official memo, deadline, or update to student leaders..." required></textarea>

                <div class="org-fb-toolbar">
                    <div class="org-fb-options">
                        <select id="postPriority" class="org-fb-opt-btn" style="outline: none;">
                            <option value="normal">Normal Priority</option>
                            <option value="high">High Priority (Urgent)</option>
                        </select>
                        <select id="postCategory" class="org-fb-opt-btn" style="outline: none;">
                            <option value="General">General Announcement</option>
                            <option value="Proposals">Activity Proposals</option>
                            <option value="Finance">Financial Guidelines</option>
                            <option value="Compliance">Compliance Directive</option>
                        </select>
                        <button type="button" class="org-fb-opt-btn" onclick="document.getElementById('postAttachmentFile').click()">
                            <i class="bi bi-paperclip" style="color: #8b1828;"></i> <span id="attachLabel">Attach File</span>
                        </button>
                        <input type="file" id="postAttachmentFile" style="display: none;" onchange="handleFileSelected(this)">
                    </div>

                    <button type="submit" class="org-fb-post-btn">
                        <i class="bi bi-send-fill"></i> Post Announcement
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Official Announcements Feed --}}
    <section class="org-panel liquid-glass">
        <div class="org-panel-head">
            <h2><i class="bi bi-megaphone-fill"></i> Official Announcements</h2>
            <span id="announcementCountBadge">{{ count($announcements) }} announcements</span>
        </div>
        <ul class="org-announce-list" id="announcementsList">
            @foreach ($announcements as $item)
                <li class="org-announce-item">
                    <div class="org-announce-bar org-announce-bar-{{ $item['priority'] }}"></div>
                    <div class="org-announce-body">
                        <div class="org-announce-title-row">
                            <strong>{{ $item['title'] }}</strong>
                            @if ($item['priority'] === 'high')
                                <span class="org-chip org-chip-high">HIGH</span>
                            @endif
                        </div>
                        <p>{{ $item['body'] }}</p>
                        <small class="org-announce-meta">{{ $item['author'] }} · {{ $item['time'] }}</small>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Official Template Documents Section (Horizontally Aligned) --}}
    <section class="org-panel liquid-glass">
        <div class="org-panel-head" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h2 style="margin: 0;"><i class="bi bi-file-earmark-ruled-fill"></i> Official Template Documents</h2>
                <span id="templateCountBadge" style="font-size: 0.82rem; color: #7a7074; background: #faf4f5; border: 1px solid #f2e2e5; padding: 0.2rem 0.65rem; border-radius: 9999px; font-weight: 600;">{{ count($templates) }} available</span>
            </div>
            @if ($isOso)
                <button type="button" class="org-btn org-btn-primary org-btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 1.15rem; font-size: 0.84rem; font-weight: 700; border-radius: 9999px;" onclick="openUploadTemplateModal()">
                    <i class="bi bi-cloud-upload"></i> Upload Template / Document
                </button>
            @endif
        </div>
        <div class="org-template-grid" id="templateGrid">
            @foreach ($templates as $tpl)
                <article class="org-template-card">
                    <div class="org-template-icon is-{{ $tpl['color'] }}">
                        <i class="bi bi-{{ $tpl['icon'] }}"></i>
                    </div>
                    <strong class="org-template-name">{{ $tpl['name'] }}</strong>
                    <small class="org-template-cat">{{ $tpl['category'] }}</small>
                    <div class="org-template-meta">
                        <span>{{ $tpl['size'] }}</span>
                        <span>{{ $tpl['downloads'] }} downloads</span>
                    </div>
                    <div class="org-template-actions">
                        <button class="org-btn-icon" title="Preview" onclick="alert('Previewing {{ $tpl['name'] }}')"><i class="bi bi-eye"></i></button>
                        <button class="org-btn-icon" title="Download" onclick="alert('Downloading {{ $tpl['name'] }}')"><i class="bi bi-download"></i></button>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Upload Template Modal Dialog --}}
    <dialog class="org-tpl-dialog" id="uploadTemplateModal">
        <div class="org-tpl-dialog-content">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f6eff0;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #1a1618;">
                    <i class="bi bi-cloud-upload" style="color: #8b1828;"></i> Upload Official Template
                </h3>
                <button type="button" onclick="closeUploadTemplateModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #7a7074;">&times;</button>
            </div>
            <form onsubmit="handleUploadTemplateSubmit(event)">
                <div style="display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1rem;">
                    <label style="font-size: 0.84rem; font-weight: 700; color: #3b3336;">Template / Document Name *</label>
                    <input type="text" id="tplNameInput" required placeholder="e.g., Activity Liquidation Matrix" style="padding: 0.65rem 0.95rem; border-radius: 12px; border: 1.5px solid #e8dedf; font-size: 0.9rem;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1rem;">
                    <label style="font-size: 0.84rem; font-weight: 700; color: #3b3336;">Category</label>
                    <select id="tplCatInput" style="padding: 0.65rem 0.95rem; border-radius: 12px; border: 1.5px solid #e8dedf; font-size: 0.9rem;">
                        <option value="Proposal">Proposal</option>
                        <option value="Finance">Finance & Budget</option>
                        <option value="Forms">Forms & Registration</option>
                        <option value="Report">Accomplishment & Reports</option>
                        <option value="Compliance">Compliance & Clearances</option>
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1.25rem;">
                    <label style="font-size: 0.84rem; font-weight: 700; color: #3b3336;">Choose Document File (.pdf, .docx, .xlsx) *</label>
                    <input type="file" id="tplFileInput" required accept=".pdf,.doc,.docx,.xls,.xlsx" style="padding: 0.65rem; border-radius: 12px; border: 1.5px dashed #d8c2c7; background: #faf4f5; font-size: 0.86rem;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="org-btn org-btn-ghost" onclick="closeUploadTemplateModal()">Cancel</button>
                    <button type="submit" class="org-btn org-btn-primary"><i class="bi bi-cloud-upload"></i> Upload &amp; Publish</button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        function focusAnnouncementComposer() {
            const textarea = document.getElementById('postContent');
            if (textarea) {
                textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                textarea.focus();
            }
        }

        function handleFileSelected(input) {
            const label = document.getElementById('attachLabel');
            if (input.files && input.files[0]) {
                label.textContent = input.files[0].name;
            } else {
                label.textContent = 'Attach File';
            }
        }

        function handlePostAnnouncement(e) {
            e.preventDefault();
            const title = document.getElementById('postTitle').value.trim();
            const body = document.getElementById('postContent').value.trim();
            const priority = document.getElementById('postPriority').value;
            const author = '{{ $office->name ?? "OSO Officer" }}';

            if (!title || !body) return;

            const list = document.getElementById('announcementsList');
            if (list) {
                const li = document.createElement('li');
                li.className = 'org-announce-item';
                li.style.animation = 'fadeIn 0.4s ease';
                li.innerHTML = `
                    <div class="org-announce-bar org-announce-bar-${priority}"></div>
                    <div class="org-announce-body">
                        <div class="org-announce-title-row">
                            <strong>${escapeHtml(title)}</strong>
                            ${priority === 'high' ? '<span class="org-chip org-chip-high">HIGH</span>' : ''}
                        </div>
                        <p>${escapeHtml(body)}</p>
                        <small class="org-announce-meta">${author} · Just now (Live)</small>
                    </div>
                `;
                list.insertBefore(li, list.firstChild);
            }

            // Reset form
            document.getElementById('announcementPostForm').reset();
            document.getElementById('attachLabel').textContent = 'Attach File';
            alert('Announcement posted successfully to all student organizations!');
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
            const name = document.getElementById('tplNameInput').value.trim();
            const cat = document.getElementById('tplCatInput').value;
            const file = document.getElementById('tplFileInput').files[0];

            if (!name) return;

            const grid = document.getElementById('templateGrid');
            if (grid) {
                const card = document.createElement('article');
                card.className = 'org-template-card';
                card.innerHTML = `
                    <div class="org-template-icon is-red">
                        <i class="bi bi-file-earmark-arrow-down-fill"></i>
                    </div>
                    <strong class="org-template-name">${escapeHtml(name)}</strong>
                    <small class="org-template-cat">${escapeHtml(cat)}</small>
                    <div class="org-template-meta">
                        <span>${file ? Math.round(file.size / 1024) + ' KB' : '220 KB'}</span>
                        <span>0 downloads</span>
                    </div>
                    <div class="org-template-actions">
                        <button class="org-btn-icon" title="Preview"><i class="bi bi-eye"></i></button>
                        <button class="org-btn-icon" title="Download"><i class="bi bi-download"></i></button>
                    </div>
                `;
                grid.insertBefore(card, grid.firstChild);
            }

            closeUploadTemplateModal();
            alert(`Template "${name}" uploaded successfully!`);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
@endsection
