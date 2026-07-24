<?php
    $displayTitle = $election['title'] ?? 'OrgChain Election';
    $authUser = \App\VotingSystem\Core\Auth::user();
    $tallyPath = canvassing_path();
    $reportsPath = staff_reports_path_for($authUser);
    $collegeOptions = $turnoutByCollege ?? [];
    $programOptions = $turnoutByProgram ?? [];
    $gradeLevelSummary = $turnoutByGradeLevel ?? ['Eleven' => ['total_voters' => 0, 'votes_cast' => 0], 'Twelve' => ['total_voters' => 0, 'votes_cast' => 0]];
    $remainingVoters = max(0, (int) $summary['total_voters'] - (int) $summary['votes_cast']);
    $collegeTallyUrl = static function (?string $collegeName) use ($tallyPath): string {
        $collegeName = trim((string) $collegeName);

        if ($collegeName === '') {
            return $tallyPath;
        }

        return $tallyPath . '?' . http_build_query(['college' => $collegeName]);
    };

    $sortedTurnout = $turnoutByCollege;
    usort($sortedTurnout, static function (array $left, array $right): int {
        return ($right['turnout_rate'] <=> $left['turnout_rate'])
            ?: strcmp((string) $left['college'], (string) $right['college']);
    });

    $leadingPositions = array_slice(array_map(static function (array $position): array {
        if (!empty($position['abstain_leads'])) {
            return [
                'title' => display_position_title($position['title'] ?? 'Position'),
                'leader' => 'No elected - Abstain leads',
                'party' => '',
                'votes' => (int) ($position['abstain_count'] ?? 0),
            ];
        }

        $candidates = array_values(array_filter($position['candidates'] ?? [], static function (array $candidate): bool {
            return empty($candidate['is_abstain']);
        }));
        usort($candidates, static function (array $left, array $right): int {
            return ((int) ($right['vote_count'] ?? 0) <=> (int) ($left['vote_count'] ?? 0))
                ?: strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        $leader = $candidates[0] ?? null;

        return [
            'title' => display_position_title($position['title'] ?? 'Position'),
            'leader' => $leader['name'] ?? 'No votes yet',
            'party' => (string) ($leader['party'] ?? ''),
            'votes' => (int) ($leader['vote_count'] ?? 0),
        ];
    }, $results), 0, 5);
?>

<div class="main-header-area canvassing-dashboard-hero">
    <div class="main-header-left">
        <p class="eyebrow"><i class="bi bi-clipboard-data"></i> CANVASSING DASHBOARD</p>
        <h1>Canvassing Overview</h1>
        <p class="ssc-dashboard-tagline"><?= e($displayTitle) ?> - focused monitoring for turnout, tally readiness, and report preparation.</p>
    </div>
    <div class="main-header-right canvassing-dashboard-actions">
        <a href="<?= e(voting_url($tallyPath)) ?>" class="btn btn-gold">
            <i class="bi bi-bar-chart-line"></i> Live Tally
        </a>
        <a href="<?= e(voting_url($reportsPath)) ?>" class="btn btn-outline-brown">
            <i class="bi bi-printer"></i> Reports
        </a>
    </div>
</div>

<div class="canvassing-command-grid">
    <section class="admin-panel canvassing-command-panel">
        <div class="canvassing-command-status">
            <span class="pulse-dot"></span>
            <div>
                <p class="eyebrow">Election Status</p>
                <h2><?= e(ucfirst($election['status'])) ?> Canvassing</h2>
            </div>
        </div>
        <div class="canvassing-command-meter">
            <div class="canvassing-command-meter-fill" style="width: <?= e($summary['turnout_rate']) ?>%"></div>
        </div>
        <p><?= e($summary['votes_cast']) ?> of <?= e($summary['total_voters']) ?> enlisted voters have submitted ballots. <?= e($remainingVoters) ?> voter(s) remain unrecorded.</p>
    </section>

    <section class="admin-panel canvassing-checklist-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Canvassing Tasks</p>
                <h2>Officer Shortcuts</h2>
            </div>
        </div>
        <div class="canvassing-action-list">
            <a href="<?= e(voting_url($tallyPath)) ?>">
                <i class="bi bi-bar-chart-steps"></i>
                <span>Review live candidate counts</span>
            </a>
            <a href="<?= e(voting_url($reportsPath)) ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Prepare printable election summary</span>
            </a>
            <a href="#latest-submissions">
                <i class="bi bi-clock-history"></i>
                <span>Check latest ballot submissions</span>
            </a>
        </div>
    </section>
</div>

<div class="dashboard-stat-grid canvassing-stat-grid">
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-voters"><i class="bi bi-people"></i></div>
        <div>
            <span>Total Voters</span>
            <strong><?= e($summary['total_voters']) ?></strong>
            <small>Enlisted voter records</small>
        </div>
    </article>
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-cast"><i class="bi bi-check2-square"></i></div>
        <div>
            <span>Votes Cast</span>
            <strong><?= e($summary['votes_cast']) ?></strong>
            <small>Verified ballot submissions</small>
        </div>
    </article>
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-turnout"><i class="bi bi-broadcast-pin"></i></div>
        <div>
            <span>Turnout</span>
            <strong><?= e($summary['turnout_rate']) ?>%</strong>
            <small>Live participation rate</small>
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

<section class="admin-panel college-count-panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">College Vote Count</p>
            <h2>Count Votes Per Colleges</h2>
        </div>
        <span class="dashboard-soft-pill"><?= e(count($collegeOptions)) ?> colleges tracked</span>
    </div>

    <div class="campus-turnout-hero">
        <a href="<?= e(voting_url($collegeTallyUrl(null))) ?>" class="campus-turnout-card">
            <div class="campus-turnout-content">
                <div class="campus-turnout-left">
                    <p class="campus-tag">CAMPUS-WIDE TURNOUT</p>
                    <div class="campus-big-number"><?= e(number_format((int) $summary['votes_cast'])) ?></div>
                    <p class="campus-student-count">of <?= e(number_format((int) $summary['total_voters'])) ?> students</p>
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

    <div class="college-turnout-list college-turnout-list--canvassing-dashboard">
        <?php if (empty($collegeOptions)): ?>
            <div class="college-turnout-empty text-muted small px-2 py-3">No per-college turnout records are available yet.</div>
        <?php endif; ?>

        <?php foreach ($collegeOptions as $collegeRow): ?>
            <?php $collegeName = (string) ($collegeRow['college'] ?? ''); ?>
            <a href="<?= e(voting_url($collegeTallyUrl($collegeName))) ?>" class="college-turnout-row">
                <div class="college-row-main">
                    <div class="college-row-details">
                        <h3 class="college-row-title"><?= e(college_abbreviation($collegeName)) ?></h3>
                        <div class="college-row-meta">
                            <span class="ratio-label"><?= e(number_format((int) $collegeRow['votes_cast'])) ?> / <?= e(number_format((int) $collegeRow['total_voters'])) ?> students</span>
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

<section class="admin-panel college-count-panel program-count-panel program-count-panel--plain">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Program Vote Count</p>
            <h2>Count Votes Per Program</h2>
        </div>
        <span class="dashboard-soft-pill"><?= e(count($programOptions)) ?> programs tracked</span>
    </div>

    <div class="college-turnout-list college-turnout-list--canvassing-dashboard program-turnout-list">
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

<section class="admin-panel college-count-panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Turnout Watch</p>
            <h2>College Ranking</h2>
        </div>
        <span class="dashboard-soft-pill"><?= e(count($turnoutByCollege)) ?> groups</span>
    </div>
    <div class="college-turnout-list college-turnout-list--canvassing-dashboard">
        <?php if (empty($sortedTurnout)): ?>
            <div class="college-turnout-empty text-muted small px-2 py-3">No college records yet.</div>
        <?php endif; ?>

        <?php foreach (array_slice($sortedTurnout, 0, 6) as $row): ?>
            <article class="college-turnout-row">
                <div class="college-row-main">
                    <div class="college-row-details">
                        <h3 class="college-row-title"><?= e(college_abbreviation($row['college'])) ?></h3>
                        <div class="college-row-meta">
                            <span class="ratio-label"><?= e(number_format((int) $row['votes_cast'])) ?> / <?= e(number_format((int) $row['total_voters'])) ?> voters</span>
                        </div>
                    </div>
                    <div class="college-row-right">
                        <span class="turnout-badge"><?= e($row['turnout_rate']) ?>%</span>
                    </div>
                </div>
                <div class="college-row-progress">
                    <div class="progress-fill" style="width: <?= e($row['turnout_rate']) ?>%"></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="dashboard-activity-panel" id="latest-submissions">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Latest Submissions</p>
            <h2>Recent Ballot Receipts</h2>
        </div>
        <span class="dashboard-soft-pill"><?= e(count($recentVotes)) ?> latest</span>
    </div>

    <div class="activity-list">
        <?php if (empty($recentVotes)): ?>
            <article class="activity-item">
                <span class="activity-dot is-muted"></span>
                <div>
                    <strong>No ballots submitted yet</strong>
                    <small>Recent voter submissions will appear here.</small>
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
