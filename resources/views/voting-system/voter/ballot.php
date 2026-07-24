<?php
    $electionTitle = $election['title'] ?? 'OrgChain Election 2026';
    $electionTitleWithoutYear = preg_replace('/\s+2026$/i', '', $electionTitle);
    $ballotDisplayTitle = stripos($electionTitle, 'Hiraya') === false
        ? 'Hiraya 2026: ' . $electionTitleWithoutYear
        : $electionTitle;
    $requiredVotePositions = count(array_filter($positions ?? [], static fn ($p): bool => !empty($p['candidates'])));

    $defaultBallotKicker = 'Hiraya 2026';
    $defaultBallotHeading = 'Championing Student Leadership Through Integrity, Service & Excellence';
    $defaultBallotBody = "A new chapter of student democracy unfolds as the OrgChain Electoral Board officially launches HIRAYA 2026 - the collective electoral process for the OrgChain Elections. This covers the filing of candidacy and verification of documents, to the campaign period, voting, and proclamation of winners.\n\nEach phase is set to ensure order, fairness, and clear direction for both aspirants and voters. These procedures are in place to protect the credibility of the elections and to make sure that every ARASOFian is given a proper avenue to participate and be represented.\n\nAs the process unfolds, the student body is encouraged to stay guided by the timeline, understand each stage, and take part with responsibility.";

    $ballotCardKicker = trim((string) ($election['ballot_card_kicker'] ?? '')) ?: $defaultBallotKicker;
    $ballotCardHeading = trim((string) ($election['ballot_card_heading'] ?? '')) ?: $defaultBallotHeading;
    $ballotCardBodyRaw = trim((string) ($election['ballot_card_body'] ?? ''));
    $ballotCardBodySource = $ballotCardBodyRaw !== '' ? $ballotCardBodyRaw : $defaultBallotBody;
    $ballotCardParagraphs = array_values(array_filter(array_map('trim', preg_split('/\R\R+/u', $ballotCardBodySource) ?: [])));
    $ballotCardImagePath = trim((string) ($election['ballot_card_image_path'] ?? ''));
    $ballotCardImageUrl = $ballotCardImagePath !== ''
        ? (preg_match('#^https?://#i', $ballotCardImagePath) ? $ballotCardImagePath : voting_asset($ballotCardImagePath))
        : voting_asset('img/HirayaNew.jpg');
?>
<style>
    /* Hide the default public layout navbar and footer specifically for the ballot page */
    .site-navbar, .site-footer { display: none !important; }
    body, main { padding: 0 !important; margin: 0 !important; }
    .ballot-draft-hint {
        display: block;
        margin-top: 0.55rem;
        font-size: 0.82rem;
        font-weight: 650;
        color: rgba(31, 24, 23, 0.72);
        line-height: 1.35;
    }
    .ballot-draft-hint .bi {
        margin-right: 0.35rem;
        opacity: 0.85;
    }
</style>
<div class="ballot-global-layout ballot-glass-shell">
    <!-- Top Bar -->
    <header class="ballot-topbar">
        <div class="topbar-left">
            <button type="button" class="ballot-menu-toggle" id="ballotMenuToggle" aria-label="Open positions menu" aria-expanded="false" aria-controls="ballotSidebar">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-logo-box">
                <img src="<?= e(voting_asset('img/orgchain-logo.png')) ?>" alt="OrgChain Logo" class="ssc-logo-img">
            </div>
            <div class="topbar-titles">
                <span class="sys-title">OrgChain Official Voting System</span>
                <span class="sys-subtitle">OrgChain Electoral Board </span>
            </div>
        </div>
        <div class="topbar-right">
            <span class="session-timer ballot-session-timer d-flex align-items-center flex-shrink-0" aria-live="polite"><i class="bi bi-clock" aria-hidden="true"></i> <span id="sessionTimer">--:--:--</span></span>

            <div class="dropdown ballot-voter-dropdown">
                <button class="voter-profile-btn ballot-voter-profile-btn" type="button" id="voterDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Voter menu">
                    <div class="voter-user-info ballot-voter-user-info">
                        <span class="ballot-voter-name"><?= e($voter['full_name'] ?? 'Voter') ?></span>
                        <small class="ballot-voter-college"><?= e($voter['college'] ?? 'Verified Voter') ?></small>
                    </div>
                    <div class="voter-avatar-circle ballot-voter-avatar-circle" aria-hidden="true">
                        <i class="bi bi-person-circle"></i>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end animate-zoom-in" aria-labelledby="voterDropdown" style="border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 15px 35px rgba(0,0,0,0.12); padding: 0.5rem; min-width: 180px; margin-top: 0.5rem;">
                    <li><h6 class="dropdown-header" style="font-weight: 800; color: #b06a24; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Session Control</h6></li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" id="exitBtnHeader" style="font-weight: 700; padding: 0.7rem 1rem; border-radius: 8px; color: #DC2626;">
                            <i class="bi bi-box-arrow-right"></i> Exit Ballot
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="ballot-sidebar-backdrop" id="ballotSidebarBackdrop" hidden></div>

    <div class="ballot-split-content">
        <!-- Left Sidebar -->
        <aside class="ballot-sidebar" id="ballotSidebar">
             <div class="sidebar-header">
                <div class="sidebar-header-titles">
                    <h2>Official Ballot</h2>
                    <p>OrgChain OFFICIAL VOTING SYSTEM</p>
                </div>
                <button type="button" class="ballot-sidebar-close" id="ballotSidebarClose" aria-label="Close positions menu">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>



            <div class="sidebar-progress-row">
                <div class="progress-ring-container-small">
                    <svg class="progress-ring-small" width="60" height="60">
                        <circle class="progress-ring-circle-bg-small" stroke-width="5" fill="transparent" r="26" cx="30" cy="30"/>
                        <circle class="progress-ring-circle-small" id="sidebarProgressRing" stroke-width="5" fill="transparent" r="26" cx="30" cy="30" stroke-dasharray="163.36" stroke-dashoffset="163.36"/>
                    </svg>
                    <div class="progress-ring-text-small" id="sidebarProgressText">0%</div>
                </div>
                <div class="sidebar-progress-text">
                    <strong><span id="sidebarProgressCount">0</span> of <?= (int) $requiredVotePositions ?></strong>
                    <span>positions with a vote</span>
                </div>
            </div>

            <nav class="jump-nav">
                <p class="jump-nav-title"><i class="bi bi-list-task"></i> POSITIONS</p>
                <ul id="jumpNavList">
                    <?php foreach ($positions as $position): ?>
                        <?php $positionTitle = display_position_title($position['title'] ?? 'Position'); ?>
                        <li id="jump_<?= e($position['id']) ?>" onclick="document.getElementById('position_<?= e($position['id']) ?>').scrollIntoView({behavior: 'smooth'})">
                            <span class="jump-nav-name"><i class="bi bi-circle"></i> <?= e($positionTitle) ?></span>
                            <i class="bi bi-chevron-right jump-nav-arrow"></i>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <button class="btn btn-review-sidebar" type="button" id="reviewBtnSidebar">
                    <i class="bi bi-eye"></i> Review & Submit
                </button>
            </div>
        </aside>

        <!-- Right Main Panel -->
        <main class="ballot-main">
            <div class="main-header-area">
                <div class="main-header-left">
                    <p class="eyebrow"><i class="bi bi-check-circle-fill"></i> OFFICIAL BALLOT</p>
                    <h1><?= e($ballotDisplayTitle) ?></h1>
                    <p class="hiraya-title-tagline"><?= e($ballotCardHeading) ?></p>
                </div>
                <div class="main-header-right">
                    <div class="progress-box">
                        <span><i class="bi bi-check-circle"></i> Progress</span>
                        <div class="progress-bar-small">
                            <div class="progress-bar-fill-small" id="topProgressBar" style="width: 0%;"></div>
                        </div>
                        <span id="topProgressText">0 / <?= (int) $requiredVotePositions ?></span>
                    </div>
                </div>
            </div>

            <div class="ballot-mobile-pos-strip" aria-label="Jump to position">
                <?php foreach ($positions as $position): ?>
                    <?php $positionTitle = display_position_title($position['title'] ?? 'Position'); ?>
                    <button type="button" class="ballot-pos-chip" data-jump-target="position_<?= e($position['id']) ?>">
                        <?= e($positionTitle) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="main-content">
                <section class="hiraya-ballot-brief" aria-labelledby="hirayaBallotBriefTitle">
                    <div class="hiraya-ballot-content">
                        <div class="hiraya-ballot-copy">
                            <p class="hiraya-ballot-kicker"><i class="bi bi-stars"></i> <?= e($ballotCardKicker) ?></p>
                            <h2 id="hirayaBallotBriefTitle"><?= e($ballotCardHeading) ?></h2>
                            <?php foreach ($ballotCardParagraphs as $paragraph): ?>
                                <p><?= nl2br(e($paragraph), false) ?></p>
                            <?php endforeach; ?>
                        </div>
                        <figure class="hiraya-ballot-poster">
                            <img src="<?= e($ballotCardImageUrl) ?>" alt="<?= e($ballotCardHeading) ?>">
                        </figure>
                    </div>
                </section>

                <div class="alert alert-warning instruction-banner alert-dismissible fade show" role="alert" style="display: flex; align-items: center; gap: 0.5rem; padding-right: 3rem;">
                    <i class="bi bi-exclamation-circle-fill" style="flex-shrink: 0; color: #d97706; font-size: 1.1rem; margin-top: 2px;"></i>
                    <div style="flex-grow: 1;">
                        You may vote for <strong>one candidate per position</strong> (tap again to clear your choice). Any position you leave blank is recorded as <strong>Abstain</strong> for that line. You can still submit when some lines have no selection.
                        <span class="ballot-draft-hint"><i class="bi bi-arrow-clockwise"></i> Your picks are remembered if you reload this tab.</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <form
                    method="post"
                    action="<?= e(voting_url('/vote/submit')) ?>"
                    id="ballotForm"
                    data-send-code-url="<?= e(voting_url('/vote/send-code')) ?>"
                    data-ballot-draft-key="<?= e((string) (($election['id'] ?? '') . '_' . ($voter['id'] ?? ''))) ?>">
                    <?= voting_csrf_field() ?>

                        <?php foreach ($positions as $position): ?>
                        <?php
                            $candidateCount = count($position['candidates']);
                            $effectiveMaxChoices = 1;
                            $inputType = 'checkbox';
                            $needsChoice = $candidateCount > 0;
                            $layoutClass = '';
                            if ($candidateCount === 1) {
                                $layoutClass = 'is-unopposed';
                            } elseif ($candidateCount === 2) {
                                $layoutClass = 'is-head-to-head';
                            } elseif ($candidateCount >= 3) {
                                $layoutClass = 'is-multi-grid';
                            }

                            $eyebrowText = !$needsChoice ? 'POSITION (NO NAMES YET)' : 'CHOOSE 1 (OPTIONAL)';
                            $positionTitle = display_position_title($position['title'] ?? 'Position');
                            $helpLine = !$needsChoice
                                ? 'No candidates configured for this ballot line yet.'
                                : 'Tap a candidate to select. Tap again to clear. Leave blank if you wish to abstain for ' . $positionTitle . '.';
                        ?>
                        <section
                            class="position-block-new"
                            data-max-choices="<?= e($effectiveMaxChoices) ?>"
                            data-requires-choice="<?= $needsChoice ? '1' : '0' ?>"
                            id="position_<?= e($position['id']) ?>"
                        >
                            <div class="position-heading-new">
                                <div>
                                    <p class="eyebrow">
                                        <i class="bi bi-plus-circle"></i> <?= e($eyebrowText) ?>
                                    </p>
                                    <h2><?= e($positionTitle) ?></h2>
                                    <p><?= e($helpLine) ?></p>
                                </div>
                                <?php if ($needsChoice): ?>
                                <div class="position-controls-new">
                                    <div class="selection-counter-box" id="counter_<?= e($position['id']) ?>">
                                        <strong>0</strong> / <small><?= e($effectiveMaxChoices) ?></small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($needsChoice): ?>
                            <div class="inline-error" id="error_<?= e($position['id']) ?>" style="display: none;">
                                <i class="bi bi-exclamation-triangle-fill"></i> <span class="error-msg">Only one candidate can be selected per position.</span>
                            </div>
                            <?php endif; ?>

                            <div class="candidate-grid-new <?= $layoutClass ?>" role="group" aria-labelledby="position_<?= e($position['id']) ?>">
                                <?php if (!$needsChoice): ?>
                                    <div class="empty-inline">No candidates are listed for this position yet.</div>
                                <?php endif; ?>
                                <?php foreach ($position['candidates'] as $candidate): ?>
                                    <?php
                                        $inputId = 'candidate_' . $candidate['id'];
                                        $candidateImageUrl = candidate_image_url($candidate);
                                    ?>
                                    <label class="candidate-card-new" for="<?= e($inputId) ?>" tabindex="0" data-card-for="<?= e($candidate['id']) ?>" onkeypress="if(event.key==='Enter'||event.key===' '){document.getElementById('<?= e($inputId) ?>').click(); event.preventDefault();}">
                                        <input
                                            id="<?= e($inputId) ?>"
                                            type="<?= $inputType ?>"
                                            name="choices[<?= e($position['id']) ?>][]"
                                            value="<?= e($candidate['id']) ?>"
                                            data-position="<?= e($position['id']) ?>"
                                            data-candidate-name="<?= e($candidate['name']) ?>"
                                            data-position-name="<?= e($positionTitle) ?>"
                                            class="candidate-input candidate-choice-input">

                                        <div class="card-check-indicator"><i class="bi bi-check-circle-fill"></i></div>

                                        <div class="candidate-img-wrapper">
                                            <span class="candidate-eyebrow">CANDIDATE</span>
                                            <button
                                                type="button"
                                                class="candidate-photo-preview-btn"
                                                data-preview-src="<?= e($candidateImageUrl) ?>"
                                                data-preview-name="<?= e($candidate['name']) ?>"
                                                aria-label="View <?= e($candidate['name']) ?> pubmat">
                                                <img src="<?= e($candidateImageUrl) ?>" alt="<?= e($candidate['name']) ?> pubmat" class="candidate-img-square">
                                                <span class="candidate-preview-hint"><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i></span>
                                            </button>
                                        </div>
                                        <div class="candidate-info-center">
                                            <span class="candidate-name"><?= e($candidate['name']) ?></span>
                                            <?php if ($candidate['party']): ?>
                                                <small class="candidate-party"><i class="bi bi-flag-fill"></i> <?= e($candidate['party']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>

                    <div class="submit-strip-mobile animate-fade-up delay-200">
                        <div>
                            <strong>Review choices</strong>
                        </div>
                        <button class="btn btn-brown btn-lg" type="button" id="reviewBtnMobile">
                            <i class="bi bi-shield-lock-fill"></i> Review & Submit
                        </button>
                    </div>

                <!-- Review Modal -->
                    <div class="modal-overlay" id="reviewModal" style="display: none;">
                        <div class="modal-content animate-zoom-in">
                            <div class="modal-header">
                                <h2>Review Your Vote</h2>
                                <button type="button" class="btn-close-modal" id="closeModalBtn" aria-label="Close modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <p class="modal-instruction">Please confirm your selections. For security, the system will display a number that you must type before the ballot is submitted.</p>
                                <div id="reviewSummary" class="review-summary">
                                    <!-- Summary injected via JS -->
                                </div>
                            </div>
                            <div class="modal-footer" style="display: flex; gap: 1rem; flex-direction: column;">
                                <div class="ballot-email-code-panel" id="ballotEmailCodePanel" style="display: none;">
                                    <div class="ballot-email-code-status" id="ballotEmailCodeStatus">
                                        <i class="bi bi-shield-check"></i>
                                        <span>For security, generate a number and type it below to verify your vote.</span>
                                    </div>
                                    <div class="ballot-displayed-code" id="ballotDisplayedCode" aria-live="polite">------</div>
                                    <div class="ballot-code-timer" id="ballotCodeTimer" aria-live="polite" hidden></div>
                                    <label for="ballotCodeInput">Enter Verification Number</label>
                                    <input id="ballotCodeInput" type="text" name="ballot_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" aria-required="true">
                                </div>
                                <button type="button" class="btn btn-bright-green w-100" id="sendBallotCodeBtn" style="padding: 1rem; font-size: 1.2rem; border-radius: 12px; background-color: #22c55e; border-color: #22c55e; box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3); font-weight: 800;"><i class="bi bi-shield-lock" style="margin-right: 0.5rem;"></i> Generate Verification Number</button>
                                <button type="submit" class="btn btn-bright-green w-100" id="confirmSubmitBtn" disabled style="display: none; padding: 1rem; font-size: 1.2rem; border-radius: 12px; background-color: #22c55e; border-color: #22c55e; box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3); font-weight: 800;"><i class="bi bi-shield-check" style="margin-right: 0.5rem;"></i> Confirm &amp; Submit Ballot</button>
                                <button type="button" class="btn btn-ghost-outline w-100" id="editVoteBtn" style="padding: 0.8rem; border-radius: 12px; font-weight: 700; color: #4F564C;">Back to Review</button>
                            </div>
                        </div>
                    </div>

                    <!-- Exit Confirmation Modal -->
                    <div class="modal-overlay" id="exitModal" style="display: none;">
                        <div class="modal-content animate-zoom-in" style="max-width: 450px;">
                            <div class="modal-header">
                                <h2>Exit Ballot</h2>
                                <button type="button" class="btn-close-modal" id="closeExitModalBtn" aria-label="Close modal">&times;</button>
                            </div>
                            <div class="modal-body text-center" style="padding: 2rem;">
                                <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem; color: #F49322; margin-bottom: 1rem; display: block;"></i>
                                <p class="modal-instruction" style="font-size: 1.1rem; margin-bottom: 0;">Are you sure you want to exit?</p>
                                <p style="color: #4F564C; font-size: 0.9rem; margin-top: 0.5rem;">Your current selections will be lost and you will need to start over.</p>
                            </div>
                            <div class="modal-footer" style="justify-content: center;">
                                <button type="button" class="btn btn-ghost-outline" id="cancelExitBtn">Cancel</button>
                                <a href="<?= e(voting_url('/')) ?>" class="btn btn-brown text-decoration-none">Yes, Exit</a>
                            </div>
                        </div>
                    </div>

                    <!-- Flash Error Modal -->
                    <?php if ($errorMsg = voting_flash('error')): ?>
                    <div class="modal-overlay" id="errorFlashModal" style="display: flex; background: rgba(31, 24, 23, 0.6); backdrop-filter: blur(8px); z-index: 9999;">
                        <div class="modal-content animate-zoom-in" style="max-width: 420px; text-align: center;">
                            <div class="modal-header" style="border-bottom: none; padding-bottom: 0; justify-content: flex-end;">
                                <button type="button" class="btn-close-modal" onclick="document.getElementById('errorFlashModal').style.display='none';" aria-label="Close modal">&times;</button>
                            </div>
                            <div class="modal-body" style="padding: 0 2.5rem 2rem;">
                                <div style="width: 80px; height: 80px; background: #FEE2E2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 2.5rem; color: #DC2626;"></i>
                                </div>
                                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; color: #1f1817; margin-bottom: 0.8rem;">Action Required</h3>
                                <p style="color: #4F564C; font-size: 1rem; line-height: 1.5; margin-bottom: 2rem;">
                                    <?= e($errorMsg) ?>
                                </p>
                                <button type="button" class="btn btn-brown w-100" onclick="document.getElementById('errorFlashModal').style.display='none';" style="padding: 1rem; font-size: 1.1rem; border-radius: 12px;">Return to Ballot</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="candidate-photo-preview-overlay" id="candidatePhotoPreview" hidden>
                        <button type="button" class="candidate-photo-preview-backdrop" data-candidate-preview-close aria-label="Close preview"></button>
                        <section class="candidate-photo-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="candidatePhotoPreviewTitle" tabindex="-1" data-candidate-preview-close>
                            <div class="candidate-photo-preview-frame">
                                <img id="candidatePhotoPreviewImg" src="" alt="">
                            </div>
                            <h3 id="candidatePhotoPreviewTitle"></h3>
                        </section>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <div class="ballot-mobile-dock">
        <button type="button" class="btn btn-review-mobile" id="reviewBtnMobile">
            <i class="bi bi-eye"></i> Review & Submit
        </button>
    </div>
</div>
