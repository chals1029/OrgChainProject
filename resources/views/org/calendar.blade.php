@extends('org.layout')

@section('title', 'Calendar')

@section('header')
    <h1><strong>Calendar</strong></h1>
    <p class="org-welcome">Track activities, submission deadlines, and scheduled meetings.</p>
@endsection

@section('actions')
   
    <a href="{{ route('office.activities.create') }}" class="org-btn org-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Event
    </a>
@endsection

@section('content')
    <style>
        .org-cal-view-toggle {
            display: inline-flex;
            background: #ffffff;
            border: 1.5px solid #f0e6e8;
            border-radius: 9999px;
            padding: 3px;
        }

        .org-toggle-pill {
            border: none;
            background: transparent;
            color: #554d50;
            padding: 0.35rem 1.15rem;
            border-radius: 9999px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-toggle-pill.is-active {
            background: #7a1222;
            color: #ffffff;
        }

        /* Scope Bar */
        .org-cal-scope-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .org-cal-legend-dots {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            font-size: 0.84rem;
            font-weight: 600;
            color: #554d50;
        }

        .org-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .org-dot-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .org-dot-indicator.is-red { background: #8b1828; }
        .org-dot-indicator.is-blue { background: #2563eb; }

        .org-scope-filter-group {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 0.84rem;
            font-weight: 600;
            color: #554d50;
        }

        .org-scope-pill-btn {
            border: 1.5px solid #e8dedf;
            background: #ffffff;
            color: #554d50;
            padding: 0.3rem 0.95rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .org-scope-pill-btn.is-active {
            background: #7a1222;
            border-color: #7a1222;
            color: #ffffff;
        }

        .org-scope-check-label {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            cursor: pointer;
            font-size: 0.82rem;
            color: #554d50;
        }

        /* 2-Column Calendar & Upcoming List */
        .org-cal-main-grid {
            display: grid;
            grid-template-columns: 1.8fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .org-cal-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
        }

        .org-cal-header-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .org-cal-nav-btn {
            background: #faf4f5;
            border: 1px solid #f0e6e8;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #554d50;
            transition: all 0.15s ease;
        }

        .org-cal-nav-btn:hover {
            background: #7a1222;
            color: #ffffff;
            border-color: #7a1222;
        }

        .org-cal-month-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0;
        }

        /* Calendar Days Grid */
        .org-cal-grid-table {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }

        .org-cal-dow-header {
            text-align: center;
            font-size: 0.72rem;
            font-weight: 800;
            color: #7a7074;
            padding-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .org-cal-day-cell {
            min-height: 72px;
            background: #ffffff;
            border: 1px solid #f5edee;
            border-radius: 12px;
            padding: 0.45rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
        }

        .org-cal-day-cell:hover {
            border-color: #d8c2c7;
            background: #fffafa;
        }

        .org-cal-day-cell.is-selected {
            border: 2px solid #7a1222;
            box-shadow: 0 0 0 2px rgba(122, 18, 34, 0.15);
        }

        .org-cal-day-num {
            font-size: 0.82rem;
            font-weight: 700;
            color: #3b3336;
        }

        .org-cal-day-cell.is-selected .org-cal-day-num {
            color: #7a1222;
            font-weight: 800;
        }

        .org-cal-event-pill {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.35rem;
            border-radius: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .org-cal-event-pill.is-red {
            background: #fdf0f2;
            color: #8b1828;
            border: 1px solid #f8d7dc;
        }

        .org-cal-event-pill.is-blue {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }

        .org-cal-event-pill span.dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .org-cal-event-pill.is-red span.dot { background: #8b1828; }
        .org-cal-event-pill.is-blue span.dot { background: #2563eb; }

        /* Upcoming Sidebar List */
        .org-upcoming-sidebar-head {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1618;
            margin-bottom: 1.15rem;
        }

        .org-upcoming-side-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .org-side-event-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 0.65rem;
            border-bottom: 1px solid #f8f1f2;
        }

        .org-side-event-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .org-side-event-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .org-side-date-badge {
            width: 38px;
            text-align: center;
            line-height: 1.1;
            flex-shrink: 0;
        }

        .org-side-date-badge small {
            display: block;
            font-size: 0.65rem;
            font-weight: 800;
            color: #7a7074;
            text-transform: uppercase;
        }

        .org-side-date-badge strong {
            display: block;
            font-size: 1.05rem;
            font-weight: 800;
            color: #1a1618;
        }

        .org-side-event-info strong {
            display: block;
            font-size: 0.84rem;
            font-weight: 700;
            color: #1a1618;
        }

        .org-side-event-info small {
            display: block;
            font-size: 0.74rem;
            color: #7a7074;
            margin-top: 0.1rem;
        }

        .org-side-event-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .org-side-event-dot.is-red { background: #8b1828; }
        .org-side-event-dot.is-blue { background: #2563eb; }

        /* Selected Day Bottom Card */
        .org-selected-day-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #f0e6e8;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 4px 16px rgba(90, 15, 30, 0.03);
        }

        .org-selected-day-head {
            margin-bottom: 0.95rem;
        }

        .org-selected-day-head h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.05rem;
            font-weight: 800;
            color: #1a1618;
            margin: 0 0 0.2rem;
        }

        .org-selected-day-head small {
            font-size: 0.78rem;
            color: #7a7074;
        }

        .org-selected-event-card {
            background: #fdf2f4;
            border: 1px solid #fce3e6;
            border-radius: 16px;
            padding: 1.15rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .org-selected-event-title-row {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .org-selected-event-title-row strong {
            font-size: 0.95rem;
            font-weight: 800;
            color: #1a1618;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .org-selected-event-meta {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            font-size: 0.8rem;
            color: #554d50;
            font-weight: 600;
            margin-top: 0.15rem;
        }
    </style>

    {{-- Scope Filter Bar --}}
    <div class="org-cal-scope-bar">
        <div class="org-cal-legend-dots">
            <span class="org-legend-item"><span class="org-dot-indicator is-red"></span> In-Campus</span>
            <span class="org-legend-item"><span class="org-dot-indicator is-blue"></span> Off-Campus</span>
        </div>

        <div class="org-scope-filter-group">
            <span>Scope:</span>
            <label class="org-scope-check-label">
                <input type="checkbox" id="checkAllCampus"> All
            </label>
            <label class="org-scope-check-label">
                <input type="checkbox" id="checkInCampus"> In-Campus
            </label>
            <label class="org-scope-check-label">
                <input type="checkbox" id="checkOffCampus"> Off-Campus
            </label>
        </div>
    </div>

    {{-- Main 2-Column Split --}}
    <div class="org-cal-main-grid">
        {{-- Calendar Grid Card --}}
        <section class="org-cal-card">
            <div class="org-cal-header-nav">
                <button type="button" class="org-cal-nav-btn" aria-label="Previous month"><i class="bi bi-chevron-left"></i></button>
                <h2 class="org-cal-month-title">September 2026</h2>
                <button type="button" class="org-cal-nav-btn" aria-label="Next month"><i class="bi bi-chevron-right"></i></button>
            </div>

            <div class="org-cal-grid-table">
                {{-- Days of week header --}}
                <div class="org-cal-dow-header">MON</div>
                <div class="org-cal-dow-header">TUE</div>
                <div class="org-cal-dow-header">WED</div>
                <div class="org-cal-dow-header">THU</div>
                <div class="org-cal-dow-header">FRI</div>
                <div class="org-cal-dow-header">SAT</div>
                <div class="org-cal-dow-header">SUN</div>

                {{-- Week 1: 1 - 6 --}}
                <div class="org-cal-day-cell"><span class="org-cal-day-num"></span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">1</span></div>
                <div class="org-cal-day-cell">
                    <span class="org-cal-day-num">2</span>
                    <span class="org-cal-event-pill is-red"><span class="dot"></span> General Assembly...</span>
                </div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">3</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">4</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">5</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">6</span></div>

                {{-- Week 2: 7 - 13 (Day 8 Selected) --}}
                <div class="org-cal-day-cell"><span class="org-cal-day-num">7</span></div>
                <div class="org-cal-day-cell is-selected">
                    <span class="org-cal-day-num">8</span>
                    <span class="org-cal-event-pill is-red"><span class="dot"></span> Campus Wellness...</span>
                </div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">9</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">10</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">11</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">12</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">13</span></div>

                {{-- Week 3: 14 - 20 --}}
                <div class="org-cal-day-cell"><span class="org-cal-day-num">14</span></div>
                <div class="org-cal-day-cell">
                    <span class="org-cal-day-num">15</span>
                    <span class="org-cal-event-pill is-red"><span class="dot"></span> Accomplishment...</span>
                </div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">16</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">17</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">18</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">19</span></div>
                <div class="org-cal-day-cell">
                    <span class="org-cal-day-num">20</span>
                    <span class="org-cal-event-pill is-red"><span class="dot"></span> OSO Doc Review</span>
                    <span class="org-cal-event-pill is-blue"><span class="dot"></span> Leadership Summit</span>
                </div>

                {{-- Week 4: 21 - 27 --}}
                <div class="org-cal-day-cell"><span class="org-cal-day-num">21</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">22</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">23</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">24</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">25</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">26</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">27</span></div>

                {{-- Week 5: 28 - 30 --}}
                <div class="org-cal-day-cell"><span class="org-cal-day-num">28</span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num">29</span></div>
                <div class="org-cal-day-cell">
                    <span class="org-cal-day-num">30</span>
                    <span class="org-cal-event-pill is-red"><span class="dot"></span> Financial Report...</span>
                </div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num"></span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num"></span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num"></span></div>
                <div class="org-cal-day-cell"><span class="org-cal-day-num"></span></div>
            </div>
        </section>

        {{-- Upcoming on Calendar Sidebar Card --}}
        <section class="org-cal-card">
            <h3 class="org-upcoming-sidebar-head">
                <i class="bi bi-calendar-check" style="color: #8b1828;"></i> Upcoming on Calendar
            </h3>

            <div class="org-upcoming-side-list">
                <div class="org-side-event-item">
                    <div class="org-side-event-left">
                        <div class="org-side-date-badge">
                            <small>SEP</small>
                            <strong>2</strong>
                        </div>
                        <div class="org-side-event-info">
                            <strong>General Assembly 2026</strong>
                            <small>1:00 PM · Gymnasium</small>
                        </div>
                    </div>
                    <span class="org-side-event-dot is-red"></span>
                </div>

                <div class="org-side-event-item">
                    <div class="org-side-event-left">
                        <div class="org-side-date-badge">
                            <small>SEP</small>
                            <strong>8</strong>
                        </div>
                        <div class="org-side-event-info">
                            <strong>Campus Wellness Week – Start</strong>
                            <small>10:00 AM · Gymnasium</small>
                        </div>
                    </div>
                    <span class="org-side-event-dot is-red"></span>
                </div>

                <div class="org-side-event-item">
                    <div class="org-side-event-left">
                        <div class="org-side-date-badge">
                            <small>SEP</small>
                            <strong>15</strong>
                        </div>
                        <div class="org-side-event-info">
                            <strong>Accomplishment Report Deadline</strong>
                            <small>5:00 PM · OrgChain Portal</small>
                        </div>
                    </div>
                    <span class="org-side-event-dot is-red"></span>
                </div>

                <div class="org-side-event-item">
                    <div class="org-side-event-left">
                        <div class="org-side-date-badge">
                            <small>SEP</small>
                            <strong>20</strong>
                        </div>
                        <div class="org-side-event-info">
                            <strong>OSO Document Review</strong>
                            <small>2:00 PM · OSO Office</small>
                        </div>
                    </div>
                    <span class="org-side-event-dot is-red"></span>
                </div>

                <div class="org-side-event-item">
                    <div class="org-side-event-left">
                        <div class="org-side-date-badge">
                            <small>SEP</small>
                            <strong>20</strong>
                        </div>
                        <div class="org-side-event-info">
                            <strong>Leadership Summit – Travel Day</strong>
                            <small>6:00 AM · Taal Building</small>
                        </div>
                    </div>
                    <span class="org-side-event-dot is-blue"></span>
                </div>

                <div class="org-side-event-item">
                    <div class="org-side-event-left">
                        <div class="org-side-date-badge">
                            <small>SEP</small>
                            <strong>30</strong>
                        </div>
                        <div class="org-side-event-info">
                            <strong>Financial Report Submission Deadline</strong>
                            <small>5:00 PM · OrgChain Portal</small>
                        </div>
                    </div>
                    <span class="org-side-event-dot is-red"></span>
                </div>

                <div class="org-side-event-item">
                    <div class="org-side-event-left">
                        <div class="org-side-date-badge">
                            <small>OCT</small>
                            <strong>15</strong>
                        </div>
                        <div class="org-side-event-info">
                            <strong>BatStateU Sportsfest 2026</strong>
                            <small>7:00 AM · Sports Complex</small>
                        </div>
                    </div>
                    <span class="org-side-event-dot is-red"></span>
                </div>
            </div>
        </section>
    </div>

    {{-- Bottom Selected Day Event Details Card --}}
    <section class="org-selected-day-card">
        <div class="org-selected-day-head">
            <h3><i class="bi bi-calendar-event" style="color: #8b1828;"></i> September 8, 2026</h3>
            <small>1 item scheduled</small>
        </div>

        <div class="org-selected-event-card">
            <div class="org-selected-event-title-row">
                <strong><span class="org-dot-indicator is-red"></span> Campus Wellness Week – Start</strong>
                <span class="org-status-pill org-status-in-review" style="font-size: 0.72rem; padding: 0.15rem 0.65rem;">
                    <span class="org-status-dot"></span> Upcoming
                </span>
            </div>
            <div class="org-selected-event-meta">
                <span><i class="bi bi-clock"></i> 10:00 AM</span>
                <span><i class="bi bi-geo-alt-fill" style="color: #8b1828;"></i> Gymnasium</span>
                <span><i class="bi bi-tag-fill" style="color: #64748b;"></i> Activity</span>
            </div>
        </div>
    </section>
@endsection
