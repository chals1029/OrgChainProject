@extends('org.layout')

@section('title', 'Updates')

@section('header')
    <h1>Updates</h1>
    <p class="org-welcome">Official announcements, templates, and resources from the Student Organization Office.</p>
@endsection

@section('content')
    <section class="org-panel liquid-glass">
        <div class="org-panel-head">
            <h2><i class="bi bi-megaphone-fill"></i> Official Announcements</h2>
            <span>{{ count($announcements) }} announcements</span>
        </div>
        <ul class="org-announce-list">
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

    <section class="org-panel liquid-glass">
        <div class="org-panel-head">
            <h2><i class="bi bi-file-earmark-ruled-fill"></i> Official Template Documents</h2>
            <span>{{ count($templates) }} available</span>
        </div>
        <div class="org-template-grid">
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
                        <button class="org-btn-icon" title="Preview"><i class="bi bi-eye"></i></button>
                        <button class="org-btn-icon" title="Download"><i class="bi bi-download"></i></button>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endsection
