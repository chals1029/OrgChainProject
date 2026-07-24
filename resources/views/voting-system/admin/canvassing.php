<?php
    $displayTitle = $election['title'] ?? 'OrgChain Election';
    $authUser = \App\VotingSystem\Core\Auth::user();
    $reportsPath = staff_reports_path_for($authUser);
    $tallyPath = canvassing_tally_path();
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
?>

<div class="main-header-area">
    <div class="main-header-left">
        <p class="eyebrow"><i class="bi bi-bar-chart-line"></i> CANVASSING CENTER</p>
        <h1><?= e($displayTitle) ?></h1>
        <p class="ssc-dashboard-tagline">Live vote canvassing, turnout review, and official report preparation for OrgChain elections.</p>
    </div>
    <div class="main-header-right">
        <div class="progress-box">
            <span><i class="bi bi-broadcast-pin"></i> Live Status</span>
            <div class="progress-bar-small">
                <div class="progress-bar-fill-small" style="width: <?= e($summary['turnout_rate']) ?>%; background-color: #22c55e;"></div>
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
            <small><?= e($summary['positions']) ?> ballot positions</small>
        </div>
    </article>
</div>

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
    <section class="admin-panel dashboard-chart-panel turnout-report-panel turnout-print-area" id="canvassingPartialVotingTurnoutReport">
        <div class="turnout-report-toolbar no-print">
            <button class="btn btn-outline-brown turnout-print-button" type="button" data-print-section="#canvassingPartialVotingTurnoutReport">
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

    <section class="admin-panel dashboard-mini-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">College-wise Breakdown</p>
                <h2>Turnout Monitor</h2>
            </div>
        </div>
        <div class="turnout-list">
            <?php foreach ($turnoutByCollege as $row): ?>
                <div class="turnout-item">
                    <div class="turnout-info">
                        <div class="turnout-meta">
                            <strong><?= e(college_abbreviation($row['college'])) ?></strong>
                            <span class="percentage"><?= e($row['turnout_rate']) ?>%</span>
                        </div>
                        <div class="turnout-progress">
                            <div class="progress-fill" style="width: <?= e($row['turnout_rate']) ?>%"></div>
                        </div>
                        <small class="voter-count"><?= e($row['votes_cast']) ?> / <?= e($row['total_voters']) ?> students voted</small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<section class="dashboard-activity-panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Recent Submissions</p>
            <h2>Latest Ballots</h2>
        </div>
        <span class="dashboard-soft-pill"><?= e(count($recentVotes)) ?> latest</span>
    </div>

    <div class="activity-list">
        <?php if (empty($recentVotes)): ?>
            <article class="activity-item">
                <span class="activity-dot is-muted"></span>
                <div>
                    <strong>No ballots submitted yet</strong>
                    <small>New voter submissions will appear here.</small>
                </div>
            </article>
        <?php endif; ?>

        <?php foreach ($recentVotes as $voter): ?>
            <article class="activity-item">
                <span class="activity-dot"></span>
                <div>
                    <strong><?= e($voter['full_name']) ?> submitted a ballot</strong>
                    <small><?= e(college_abbreviation($voter['college'])) ?> &middot; <?= e($voter['voted_at']) ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
