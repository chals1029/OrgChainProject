<?php
    $displayTitle = str_replace('OrgChain Election 2026', 'OrgChain Voting System', $election['title'] ?? 'OrgChain Voting System');
    $authUser = \App\VotingSystem\Core\Auth::user();
    $dashboardPath = staff_dashboard_path_for($authUser);
    $reportsPath = staff_reports_path_for($authUser);
    $collegeOptions = $turnoutByCollege ?? [];
    $programOptions = $turnoutByProgram ?? [];
    $programFilterOptions = $programFilterOptions ?? [];
    $gradeLevelSummary = $turnoutByGradeLevel ?? ['Eleven' => ['total_voters' => 0, 'votes_cast' => 0], 'Twelve' => ['total_voters' => 0, 'votes_cast' => 0]];
    $positionOptions = $positionOptions ?? [];
    $selectedCollege = trim((string) ($filterCollege ?? ''));
    $selectedProgram = trim((string) ($filterProgram ?? ''));
    $selectedPosition = (string) ($filterPosition ?? '');
    $selectedYearLevel = trim((string) ($filterYearLevel ?? ''));
    $turnoutAsOf = date('F d, Y, h:i A');
    $yearLevelOptions = [
        'First' => 'First Year',
        'Second' => 'Second Year',
        'Third' => 'Third Year',
        'Fourth' => 'Fourth Year',
    ];
    $tallyFilterLabelParts = [];

    if ($selectedCollege !== '') {
        $tallyFilterLabelParts[] = college_abbreviation($selectedCollege);
    }

    if ($selectedYearLevel !== '') {
        $tallyFilterLabelParts[] = $yearLevelOptions[$selectedYearLevel] ?? $selectedYearLevel;
    }

    if ($selectedProgram !== '') {
        $tallyFilterLabelParts[] = $selectedProgram;
    }

    $collegeFilterUrl = static function (?string $collegeName) use ($dashboardPath, $selectedPosition, $selectedYearLevel): string {
        $params = [];

        if ($selectedPosition !== '') {
            $params['position'] = $selectedPosition;
        }

        if ($selectedYearLevel !== '') {
            $params['year_level'] = $selectedYearLevel;
        }

        $collegeName = trim((string) $collegeName);

        if ($collegeName !== '') {
            $params['college'] = $collegeName;
        }

        return $dashboardPath . ($params !== [] ? '?' . http_build_query($params) : '');
    };
?>

<div class="main-header-area">
    <div class="main-header-left">
        <p class="eyebrow"><i class="bi bi-speedometer2"></i> ADMIN DASHBOARD</p>
        <h1><?= e($displayTitle) ?></h1>
        <p class="ssc-dashboard-tagline">Monitoring and managing OrgChain elections with transparency and efficiency</p>
    </div>
    <div class="main-header-right">
        <div class="progress-box">
            <span><i class="bi bi-broadcast-pin"></i> Live Status</span>
            <div class="progress-bar-small">
                <div class="progress-bar-fill-small" style="width: <?= e($summary['turnout_rate']) ?>%; background-color: #8b1828;"></div>
            </div>
            <span><?= e($summary['turnout_rate']) ?>% Turnout</span>
        </div>
    </div>
</div>

<div class="dashboard-stat-grid">
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-voters"><i class="bi bi-people"></i></div>
        <div>
            <span>Total Voters</span>
            <strong><?= e($summary['total_voters']) ?></strong>
            <small>Official voter list</small>
        </div>
    </article>
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-cast"><i class="bi bi-check2-square"></i></div>
        <div>
            <span>Total Votes Cast</span>
            <strong><?= e($summary['votes_cast']) ?></strong>
            <small>Submitted ballots</small>
        </div>
    </article>
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-turnout"><i class="bi bi-broadcast-pin"></i></div>
        <div>
            <span>Voter Turnout</span>
            <strong><?= e($summary['turnout_rate']) ?>%</strong>
            <small>Live participation</small>
        </div>
    </article>
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-status"><i class="bi bi-shield-check"></i></div>
        <div>
            <span>Election Status</span>
            <strong><?= e(ucfirst($election['status'])) ?></strong>
            <small><?= e($summary['candidates']) ?> candidates listed</small>
        </div>
    </article>
</div>

<section class="admin-panel college-count-panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Real-time Canvassing</p>
            <h2>Live Participation Analysis</h2>
        </div>
    </div>

    <div class="campus-turnout-hero">
        <a href="<?= e(voting_url($collegeFilterUrl(null))) ?>" class="campus-turnout-card">
            <div class="campus-turnout-content">
                <div class="campus-turnout-left">
                    <p class="campus-tag">CAMPUS-WIDE TURNOUT</p>
                    <div class="campus-big-number"><?= e(number_format($summary['votes_cast'])) ?></div>
                    <p class="campus-student-count">of <?= e(number_format($summary['total_voters'])) ?> students</p>
                </div>
                <div class="campus-turnout-right">
                    <p class="overall-label">OVERALL</p>
                    <div class="overall-percentage"><?= e($summary['turnout_rate']) ?>%</div>
                </div>
            </div>
            <div class="campus-turnout-progress">
                <div class="campus-progress-bar" style="width: <?= e($summary['turnout_rate']) ?>%"></div>
            </div>
        </a>
    </div>

    <div class="college-turnout-list">
        <?php foreach ($collegeOptions as $collegeRow): ?>
            <?php
                $collegeName = (string) ($collegeRow['college'] ?? '');
                $isSelectedCollege = $selectedCollege === $collegeName;
            ?>
            <a href="<?= e(voting_url($collegeFilterUrl($collegeName))) ?>" class="college-turnout-row <?= $isSelectedCollege ? 'is-active' : '' ?>">
                <div class="college-row-main">
                    <div class="college-row-details">
                        <h3 class="college-row-title"><?= e(college_abbreviation($collegeName)) ?></h3>
                        <div class="college-row-meta">
                            <span class="ratio-label"><?= e(number_format($collegeRow['votes_cast'])) ?> / <?= e(number_format($collegeRow['total_voters'])) ?> students</span>
                        </div>
                    </div>
                    <div class="college-row-right">
                        <span class="turnout-badge"><?= e($collegeRow['turnout_rate']) ?>%</span>
                    </div>
                </div>
                <div class="college-row-progress">
                    <div class="progress-fill" style="width: <?= e($collegeRow['turnout_rate']) ?>%"></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php
    $tallyId = 'live-tally';
    $tallyAction = $dashboardPath;
    require __DIR__ . '/../partials/tally.php';
?>

<section class="admin-panel college-count-panel program-count-panel program-count-panel--plain">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Program Vote Count</p>
            <h2>Count Votes Per Program</h2>
        </div>
        <span class="dashboard-soft-pill"><?= e(count($programOptions)) ?> programs tracked</span>
    </div>

    <div class="college-turnout-list program-turnout-list">
        <?php
            $grade11Total = (int) ($gradeLevelSummary['Eleven']['total_voters'] ?? 0);
            $grade11Votes = (int) ($gradeLevelSummary['Eleven']['votes_cast'] ?? 0);
            $grade11Rate = $grade11Total > 0 ? round(($grade11Votes / $grade11Total) * 100, 2) : 0;
            $grade12Total = (int) ($gradeLevelSummary['Twelve']['total_voters'] ?? 0);
            $grade12Votes = (int) ($gradeLevelSummary['Twelve']['votes_cast'] ?? 0);
            $grade12Rate = $grade12Total > 0 ? round(($grade12Votes / $grade12Total) * 100, 2) : 0;
        ?>
        <article class="college-turnout-row program-turnout-row">
            <div class="college-row-main">
                <div class="college-row-details">
                    <h3 class="college-row-title">Grade 11</h3>
                    <div class="college-row-meta">
                        <span class="ratio-label"><?= e(number_format($grade11Votes)) ?> / <?= e(number_format($grade11Total)) ?> voters</span>
                    </div>
                </div>
                <div class="college-row-right">
                    <span class="turnout-badge"><?= e($grade11Rate) ?>%</span>
                </div>
            </div>
            <div class="college-row-progress">
                <div class="progress-fill" style="width: <?= e($grade11Rate) ?>%"></div>
            </div>
        </article>
        <article class="college-turnout-row program-turnout-row">
            <div class="college-row-main">
                <div class="college-row-details">
                    <h3 class="college-row-title">Grade 12</h3>
                    <div class="college-row-meta">
                        <span class="ratio-label"><?= e(number_format($grade12Votes)) ?> / <?= e(number_format($grade12Total)) ?> voters</span>
                    </div>
                </div>
                <div class="college-row-right">
                    <span class="turnout-badge"><?= e($grade12Rate) ?>%</span>
                </div>
            </div>
            <div class="college-row-progress">
                <div class="progress-fill" style="width: <?= e($grade12Rate) ?>%"></div>
            </div>
        </article>

        <?php foreach ($programOptions as $programRow): ?>
            <?php
                $programName = (string) ($programRow['program'] ?? '');
                $gradeCounts = $programRow['grade_counts'] ?? ['Eleven' => 0, 'Twelve' => 0];
            ?>
            <article class="college-turnout-row program-turnout-row">
                <div class="college-row-main">
                    <div class="college-row-details">
                        <h3 class="college-row-title"><?= e($programName) ?></h3>
                        <div class="college-row-meta">
                            <span class="ratio-label"><?= e(number_format((int) ($programRow['votes_cast'] ?? 0))) ?> / <?= e(number_format((int) ($programRow['total_voters'] ?? 0))) ?> voters</span>
                        </div>
                    </div>
                    <div class="college-row-right">
                        <span class="turnout-badge"><?= e($programRow['turnout_rate'] ?? 0) ?>%</span>
                    </div>
                </div>
                <div class="college-row-progress">
                    <div class="progress-fill" style="width: <?= e($programRow['turnout_rate'] ?? 0) ?>%"></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>


<div class="dashboard-main-grid">
    <section class="admin-panel dashboard-chart-panel turnout-report-panel turnout-print-area" id="partialVotingTurnoutReport">
        <div class="turnout-report-toolbar no-print">
            <button class="btn btn-outline-brown turnout-print-button" type="button" data-print-section="#partialVotingTurnoutReport">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
        <div class="turnout-report-heading">
            <p class="eyebrow"><span class="pulse-dot"></span> Turnout by College</p>
            <h2>Partial Voting Turnout</h2>
            <span>As of <?= e($turnoutAsOf) ?></span>
        </div>

        <div class="turnout-report-grid">
            <div class="chart-frame turnout-donut-frame">
                <canvas
                    id="turnoutChart"
                    data-labels="<?= e(json_encode(array_map(static fn (array $row): string => college_abbreviation($row['college'] ?? ''), $turnoutByCollege))) ?>"
                    data-votes="<?= e(json_encode(array_column($turnoutByCollege, 'votes_cast'))) ?>"
                    data-totals="<?= e(json_encode(array_column($turnoutByCollege, 'total_voters'))) ?>"></canvas>
            </div>

            <div class="turnout-report-side">
                <div class="turnout-total-block">
                    <strong class="turnout-total-number"><?= e(number_format((int) ($summary['votes_cast'] ?? 0))) ?></strong>
                    <span class="turnout-total-label">Partial Total Number of Votes</span>
                </div>
            </div>
        </div>
    </section>
    <section class="dashboard-activity-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Recent Activity</p>
                <h2>Monitoring Feed</h2>
            </div>
        </div>

        <div class="activity-list" id="activityListContent">

            <?php foreach ($recentActivity as $activity): ?>
                <article class="activity-item">
                    <span class="activity-dot is-muted"></span>
                    <div>
                        <strong><?= e($activity['actor_name']) ?> · <?= e(str_replace('_', ' ', $activity['action'])) ?></strong>
                        <small><?= e($activity['details'] ?: 'System activity') ?> · <?= e($activity['created_at']) ?></small>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
