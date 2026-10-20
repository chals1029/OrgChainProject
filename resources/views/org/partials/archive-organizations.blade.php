@if ($showOrganizationArchives)
    <form method="get" action="{{ route('office.archive') }}" class="archive-filters">
        <label class="archive-filter">
            Academic year
            <select name="year">
                <option value="all" @selected($archiveFilters['year'] === 'all')>All years</option>
                @foreach ($availableArchiveYears as $year)
                    <option value="{{ $year }}" @selected($archiveFilters['year'] === $year)>{{ $year }}</option>
                @endforeach
            </select>
        </label>
        <label class="archive-filter">
            Organization scope
            <select name="scope">
                <option value="all" @selected($archiveFilters['scope'] === 'all')>All organizations</option>
                @foreach ($availableArchiveScopes as $scope)
                    <option value="{{ $scope }}" @selected($archiveFilters['scope'] === $scope)>{{ $scope }}</option>
                @endforeach
            </select>
        </label>
        <label class="archive-filter is-search">
            Search archives
            <input type="search" name="search" value="{{ $archiveFilters['search'] }}" placeholder="Organization name or abbreviation">
        </label>
        <button type="submit" class="org-btn org-btn-primary org-btn-sm"><i class="bi bi-search"></i> Search</button>
        <a href="{{ route('office.archive') }}" class="org-btn org-btn-ghost org-btn-sm"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</a>
    </form>

    <section class="archive-panel" aria-labelledby="archiveOrganizationsHeading">
        <div class="archive-panel-heading">
            <div>
                <h2 id="archiveOrganizationsHeading"><i class="bi bi-folder2-open"></i> Organization archives</h2>
                <p>Read-only reports, renewal records and activity documents.</p>
            </div>
            <span class="archive-muted">{{ $archiveOrganizations->total() }} {{ $archiveOrganizations->total() === 1 ? 'organization' : 'organizations' }}</span>
        </div>
        <div class="archive-org-grid">
            @forelse ($archiveOrganizations as $organizationArchive)
                <article class="archive-org-card" id="archive-org-{{ substr(hash('sha256', $organizationArchive['key']), 0, 12) }}">
                    <div class="archive-org-identity">
                        <span class="archive-org-icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                        <span>{{ $organizationArchive['short_name'] ?: $organizationArchive['name'] }}</span>
                    </div>
                    <h3>{{ $organizationArchive['name'] }}</h3>
                    @if (!empty($organizationArchive['college']))
                        <p class="archive-muted">{{ $organizationArchive['college'] }}</p>
                    @endif
                    <div class="archive-org-meta">
                        <span><i class="bi bi-files"></i> {{ $organizationArchive['document_count'] }} documents</span>
                        <span><i class="bi bi-folder"></i> {{ $organizationArchive['folder_count'] }} folders</span>
                    </div>
                    @if (!empty($organizationArchive['updated_at']))
                        <small class="archive-muted">Last archived {{ $organizationArchive['updated_at'] }}</small>
                    @endif
                    <a href="{{ route('office.archive', array_merge($archiveQuery, ['archive_org' => $organizationArchive['key']])) }}" class="org-btn org-btn-ghost org-btn-sm" aria-label="Open archive for {{ $organizationArchive['name'] }}">Open archive <i class="bi bi-arrow-right"></i></a>
                </article>
            @empty
                <p class="archive-empty">No organization archives match these filters.</p>
            @endforelse
        </div>
        <div class="archive-pagination">
            <span>
                @if ($archiveOrganizations->total())
                    Showing {{ $archiveOrganizations->firstItem() }}–{{ $archiveOrganizations->lastItem() }} of {{ $archiveOrganizations->total() }} organizations
                @else
                    0 organizations
                @endif
            </span>
            {{ $archiveOrganizations->links('components.pagination') }}
        </div>
    </section>
@endif

@if ($selectedArchiveOrganization)
    @php
        $archiveCloseUrl = route('office.archive', $archiveQuery).'#archive-org-'.substr(hash('sha256', $selectedArchiveOrganization['key']), 0, 12);
    @endphp
    <dialog id="archiveOrganizationDialog" class="archive-org-dialog" aria-labelledby="archiveOrganizationTitle" data-close-url="{{ $archiveCloseUrl }}">
        <header class="archive-detail-header">
            <a href="{{ $archiveCloseUrl }}" class="org-btn org-btn-ghost org-btn-sm archive-dialog-close" data-archive-close autofocus><i class="bi bi-x-lg" aria-hidden="true"></i> Close</a>
            <h2 id="archiveOrganizationTitle">{{ $selectedArchiveOrganization['name'] }}</h2>
            <p class="archive-muted">
                {{ $selectedArchiveOrganization['short_name'] ?: $selectedArchiveOrganization['name'] }}
                @if (!empty($selectedArchiveOrganization['college'])) · {{ $selectedArchiveOrganization['college'] }} @endif
                · {{ $selectedArchiveOrganization['document_count'] }} documents · Read-only archive
            </p>
        </header>
        <div class="archive-detail-groups">
            @foreach ($selectedArchiveOrganization['groups'] as $group)
                <details class="archive-group" open>
                    <summary>
                        <i class="bi bi-folder2-open" aria-hidden="true"></i>
                        <span class="archive-group-title">
                            <strong>{{ $group['title'] }}</strong>
                            @if (!empty($group['description']))<small>{{ $group['description'] }}</small>@endif
                        </span>
                        <span class="archive-group-count">{{ count($group['documents']) }}</span>
                    </summary>
                    @forelse ($group['documents'] as $doc)
                        @php
                            $fileType = strtolower($doc['type'] ?? pathinfo($doc['original_name'], PATHINFO_EXTENSION));
                            $fileIcon = match ($fileType) {
                                'pdf' => 'bi-file-earmark-pdf',
                                'doc', 'docx' => 'bi-file-earmark-word',
                                'xls', 'xlsx', 'csv' => 'bi-file-earmark-spreadsheet',
                                'png', 'jpg', 'jpeg', 'gif', 'webp' => 'bi-file-earmark-image',
                                default => 'bi-file-earmark-text',
                            };
                        @endphp
                        <div class="archive-file-row">
                            <i class="bi {{ $fileIcon }}" aria-hidden="true"></i>
                            <div class="archive-file-name">
                                <strong>{{ $doc['original_name'] }}</strong>
                                <small>{{ $doc['name'] }}@if (!empty($doc['folder_name'])) · {{ $doc['folder_name'] }}@endif · {{ strtoupper($fileType) }}@if (!empty($doc['date'])) · {{ $doc['date'] }}@endif @if (!empty($doc['size'])) · {{ $doc['size'] }}@endif</small>
                            </div>
                            <button type="button" class="app-view-action" data-document-preview data-document-preview-title="{{ $doc['name'] }}" data-document-original-name="{{ $doc['original_name'] }}" data-document-preview-type="{{ $fileType }}" data-document-preview-url="{{ $doc['url'] }}" data-document-preview-download="{{ $doc['download_url'] }}" data-document-conversion-url="{{ route('office.documents.preview.convert') }}" aria-label="View {{ $doc['original_name'] }}"><i class="bi bi-eye" aria-hidden="true"></i> View</button>
                            <a class="org-btn org-btn-ghost org-btn-sm" href="{{ $doc['download_url'] }}" download aria-label="Download {{ $doc['original_name'] }}"><i class="bi bi-download" aria-hidden="true"></i> Download</a>
                        </div>
                    @empty
                        <p class="archive-empty">No archived documents in this category.</p>
                    @endforelse
                </details>
            @endforeach
        </div>
    </dialog>
@endif
