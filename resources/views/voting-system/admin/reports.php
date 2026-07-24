<section class="admin-panel printable-report">
    <div class="report-header">
        <div class="d-flex align-items-center gap-4">
            <div class="report-logo" style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <img src="<?= e(voting_asset('img/orgchain-logo.png')) ?>" alt="OrgChain Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <div>
                <p class="eyebrow"><i class="bi bi-file-earmark-bar-graph"></i> Official Election Summary</p>
                <h2><?= e(str_replace('OrgChain Election 2026', 'OrgChain Voting System', $election['title'] ?? 'OrgChain Voting System')) ?></h2>
                <p class="m-0" style="color: #64748b; font-weight: 700;"><i class="bi bi-calendar-check"></i> Generated on <?= e(date('F d, Y h:i A')) ?></p>
            </div>
        </div>
        <button class="btn btn-brown no-print" type="button" onclick="window.print()" style="padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 800;">
            <i class="bi bi-printer-fill"></i> PRINT REPORT
        </button>
    </div>

    <div class="report-metrics">
        <article class="metric-card voters">
            <i class="bi bi-people-fill"></i>
            <span>Total Voters</span>
            <strong><?= e($summary['total_voters']) ?></strong>
        </article>
        <article class="metric-card votes-cast">
            <i class="bi bi-check2-square"></i>
            <span>Total Votes Cast</span>
            <strong><?= e($summary['votes_cast']) ?></strong>
        </article>
        <article class="metric-card turnout">
            <i class="bi bi-pie-chart-fill"></i>
            <span>Turnout Rate</span>
            <strong><?= e($summary['turnout_rate']) ?>%</strong>
        </article>
    </div>

    <h3><i class="bi bi-building"></i> Turnout by College</h3>
    <table class="report-table">
        <thead>
            <tr>
                <th><i class="bi bi-mortarboard"></i> College</th>
                <th class="text-center"><i class="bi bi-person-lines-fill"></i> Total Voters</th>
                <th class="text-center"><i class="bi bi-envelope-check"></i> Votes Cast</th>
                <th class="text-center"><i class="bi bi-percent"></i> Turnout Rate</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($turnoutByCollege as $row): ?>
                <tr>
                    <td style="font-weight: 700; color: #1e293b;"><?= e(college_abbreviation($row['college'])) ?></td>
                    <td class="text-center"><?= e($row['total_voters']) ?></td>
                    <td class="text-center"><?= e($row['votes_cast']) ?></td>
                    <td class="text-center" style="font-weight: 800; color: #b06a24;"><?= e($row['turnout_rate']) ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="page-break-before: always;">
        <h3><i class="bi bi-person-badge-fill"></i> Candidate Vote Tally</h3>
        <?php foreach ($results as $position): ?>
            <div style="margin-bottom: 2rem;">
                <h4 style="font-weight: 800; color: #b06a24; background: #fffbeb; padding: 0.5rem 1rem; border-radius: 8px; font-size: 1.1rem; display: inline-block; margin-bottom: 1rem;">
                    <i class="bi bi-check-circle"></i> <?= e(display_position_title($position['title'] ?? 'Position')) ?>
                </h4>
                <?php if (!empty($position['abstain_leads'])): ?>
                    <p style="font-weight: 800; color: #b45309; margin: -0.25rem 0 1rem;">
                        Abstain currently has the highest count for this position. No candidate should be proclaimed while abstain leads.
                    </p>
                <?php endif; ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 55%;"><i class="bi bi-person"></i> Candidate</th>
                            <th class="text-center" style="width: 20%;"><i class="bi bi-box-seam"></i> Votes</th>
                            <th class="text-center" style="width: 25%;"><i class="bi bi-percent"></i> Percentage Garnered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($position['candidates'] as $candidate): ?>
                            <?php $isAbstain = !empty($candidate['is_abstain']); ?>
                            <tr>
                                <td style="font-weight: 700; color: <?= $isAbstain ? '#92400e' : '#1e293b' ?>;"><?= e($candidate['name']) ?></td>
                                <td class="text-center" style="font-weight: 900; color: #1e293b; font-size: 1.1rem;">
                                    <?= e($candidate['vote_count']) ?>
                                </td>
                                <td class="text-center" style="font-weight: 900; color: #b06a24; font-size: 1.1rem;">
                                    <?= e($candidate['vote_percent'] ?? 0) ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="system-generated-note">
        <strong>System-generated report</strong>
        <p>This document was generated by the Official Voting System using recorded ballot receipts, voter turnout records, and candidate tally data available at the time of printing.</p>
    </div>
</section>
