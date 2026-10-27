<section class="admin-panel" id="<?= e($tallyId) ?>">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Live Tally</p>
            <h2>Candidate Vote Counts<?= !empty($tallyFilterLabelParts) ? ' - ' . e(implode(' - ', $tallyFilterLabelParts)) : '' ?></h2>
        </div>
        <span class="dashboard-soft-pill"><?= e($summary['positions']) ?> positions</span>
    </div>

    <?php if (!empty($tallyAction)): ?>
    <form action="<?= e(voting_url($tallyAction)) ?>" method="GET" class="brief-filter-bar js-tally-filter-form" style="margin-top: 0; margin-bottom: 1.5rem; background: rgba(0,0,0,0.03);">
        <div class="filter-group">
            <label for="tallyCollegeFilter"><i class="bi bi-building"></i> College</label>
            <select id="tallyCollegeFilter" name="college" class="filter-select">
                <option value="">All Colleges</option>
                <?php foreach ($collegeOptions as $collegeRow): ?>
                    <?php $collegeName = (string) ($collegeRow['college'] ?? ''); ?>
                    <option value="<?= e($collegeName) ?>" <?= $selectedCollege === $collegeName ? 'selected' : '' ?>><?= e(college_abbreviation($collegeName)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="tallyProgramFilter"><i class="bi bi-diagram-3"></i> Program</label>
            <select id="tallyProgramFilter" name="program" class="filter-select" data-program-filter>
                <option value="" data-college="">All Programs</option>
                <?php foreach ($programFilterOptions as $programOption): ?>
                    <?php
                        $programCollege = (string) ($programOption['college'] ?? '');
                        $programName = (string) ($programOption['program'] ?? '');
                    ?>
                    <option value="<?= e($programName) ?>" data-college="<?= e($programCollege) ?>" <?= $selectedProgram === $programName ? 'selected' : '' ?>><?= e($programName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="tallyPositionFilter"><i class="bi bi-briefcase"></i> Position</label>
            <select id="tallyPositionFilter" name="position" class="filter-select">
                <option value="">All Positions</option>
                <?php foreach ($positionOptions as $positionOption): ?>
                    <?php $positionTitle = (string) ($positionOption['title'] ?? ''); ?>
                    <option value="<?= e($positionTitle) ?>" <?= $selectedPosition === $positionTitle ? 'selected' : '' ?>><?= e(display_position_title($positionTitle)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="tallyYearLevelFilter"><i class="bi bi-mortarboard"></i> Year Level</label>
            <select id="tallyYearLevelFilter" name="year_level" class="filter-select">
                <option value="">All Years</option>
                <?php foreach ($yearLevelOptions as $yearLevelValue => $yearLevelLabel): ?>
                    <option value="<?= e($yearLevelValue) ?>" <?= $selectedYearLevel === $yearLevelValue ? 'selected' : '' ?>><?= e($yearLevelLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <a href="<?= e(voting_url($tallyAction)) ?>" class="btn-filter-reset js-tally-filter-link" style="text-decoration: none;"><i class="bi bi-arrow-clockwise"></i> Reset</a>
    </form>
    <?php endif; ?>

    <div class="result-grid <?= !empty($selectedPosition) || !empty($selectedYearLevel) || !empty($selectedProgram) ? 'is-filtered' : '' ?>">
        <?php foreach ($results as $position): ?>
            <article class="result-card">
                <h3><?= e(display_position_title($position['title'] ?? 'Position')) ?></h3>
                <?php if (!empty($position['abstain_leads'])): ?>
                    <div class="result-status-note"><i class="bi bi-exclamation-triangle"></i> Abstain leads - no elected candidate yet.</div>
                <?php endif; ?>
                <?php $max = max(array_column($position['candidates'], 'vote_count') ?: [0]); ?>
                <?php foreach ($position['candidates'] as $candidate): ?>
                    <?php
                        $width = $max > 0 ? (($candidate['vote_count'] / $max) * 100) : 0;
                        $isAbstain = !empty($candidate['is_abstain']);
                    ?>
                    <div class="result-row <?= $isAbstain ? 'result-row--abstain' : '' ?>">
                        <span><?= e($candidate['name']) ?></span>
                        <strong><?= e($candidate['vote_count']) ?> <small><?= e($candidate['vote_percent'] ?? 0) ?>%</small></strong>
                    </div>
                    <div class="result-bar <?= $isAbstain ? 'result-bar--abstain' : '' ?>"><span style="width: <?= e($width) ?>%"></span></div>
                <?php endforeach; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (empty($results)): ?><p class="text-muted">No candidate results are available for this election.</p><?php endif; ?>
</section>
