<?php
    $statusInstructions = $election['instructions'] ?? 'OrgChain election status will appear here once the election is configured.';
    $statusInstructions = str_replace(['HirayaVotingSystem', 'HirayaVoting', 'Supreme Student Council'], 'OrgChain', $statusInstructions);
    $homeAnnouncement = trim((string) ($election['announcement'] ?? ''));
    $homeAnnouncementExpiresAt = trim((string) ($election['announcement_expires_at'] ?? ''));
    $homeAnnouncementExpiresTs = $homeAnnouncementExpiresAt !== '' ? strtotime($homeAnnouncementExpiresAt) : false;
    $showHomeAnnouncement = $homeAnnouncement !== '' && ($homeAnnouncementExpiresTs === false || $homeAnnouncementExpiresTs > time());
    $homeAnnouncementExpiryIso = $homeAnnouncementExpiresTs !== false ? date('c', $homeAnnouncementExpiresTs) : '';
    $homeAnnouncementExpiryLabel = $homeAnnouncementExpiresTs !== false ? date('M j, Y g:i A', $homeAnnouncementExpiresTs) : '';
    $isOpen = ($election['status'] ?? '') === 'open';
    $heroPubmatPath = trim((string) ($election['ballot_card_image_path'] ?? ''));
    $heroPubmatUrl = $heroPubmatPath !== ''
        ? (preg_match('#^https?://#i', $heroPubmatPath) ? $heroPubmatPath : voting_asset($heroPubmatPath))
        : voting_asset('img/HirayaNew.jpg');
?>



<!-- H4: Consistency & Standards - sticky topbar -->
<header class="ballot-topbar home-topbar" role="banner">
    <div class="topbar-left">
        <div class="topbar-logo-box">
            <img src="<?= e(voting_asset('img/orgchain-logo.png')) ?>" alt="OrgChain Logo" class="ssc-logo-img">
        </div>
        <div class="topbar-titles">
            <span class="sys-title">OrgChain Official Voting System</span>
            <span class="sys-subtitle">OrgChain Electoral Board</span>
        </div>
    </div>
    <div class="topbar-right home-topbar-actions" style="display:flex;align-items:center;gap:0.75rem;">
        <a href="#" class="btn-how-to-vote" data-bs-toggle="modal" data-bs-target="#tutorialModal" aria-label="Learn how to vote">
            How to Vote <i class="bi bi-question-circle"></i>
        </a>
        <span class="topbar-status-badge <?= $isOpen ? 'open' : 'closed' ?>">
            <i class="bi bi-<?= $isOpen ? 'circle-fill' : 'x-circle-fill' ?>"></i>
            <?= $isOpen ? 'ELECTION OPEN' : 'ELECTION CLOSED' ?>
        </span>
    </div>
</header>

<?php if ($showHomeAnnouncement): ?>
<section class="home-announcement-band" aria-label="Election announcement" <?= $homeAnnouncementExpiryIso !== '' ? 'data-announcement-expires-at="' . e($homeAnnouncementExpiryIso) . '"' : '' ?>>
    <div class="container">
        <div class="home-announcement-card">
            <div class="home-announcement-icon" aria-hidden="true">
                <i class="bi bi-megaphone-fill"></i>
            </div>
            <div class="home-announcement-copy">
                <strong>Announcement</strong>
                <p><?= nl2br(e($homeAnnouncement), false) ?></p>
            </div>
            <?php if ($homeAnnouncementExpiryIso !== ''): ?>
                <div class="home-announcement-expiry">
                    <span><i class="bi bi-clock-history"></i> Expires</span>
                    <time datetime="<?= e($homeAnnouncementExpiryIso) ?>"><?= e($homeAnnouncementExpiryLabel) ?></time>
                    <strong data-announcement-countdown>Calculating...</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="hero-band">
    <div class="container hero-grid">
        <div style="grid-column: 1 / -1;">
            <?php require base_path('resources/views/voting-system/partials/flash.php'); ?>
        </div>
        <!-- H2: Match Real World | H6: Recognition -->
        <div class="hero-copy animate-fade-up">
            <p class="eyebrow"><i class="bi bi-patch-check-fill" style="color:var(--ssc-brown);"></i> OrgChain official voting platform</p>
            <h1><?= e($election['title'] ?? 'OrgChain Official Election') ?></h1>
            <p class="lead">
                Authenticate your institutional credentials, review the official profiles of qualified candidates, and securely cast your vote through the university’s authorized digital election platform.
            </p>

            <figure class="hero-intro-pubmat" aria-label="Official election pubmat">
                <img src="<?= e($heroPubmatUrl) ?>" alt="Official election pubmat">
            </figure>

            <!-- H5: Error Prevention - trust badges set expectations -->
            <div class="trust-strip">
                <span class="trust-badge-pill"><i class="bi bi-shield-check"></i> Secure &amp; Anonymous</span>
                <span class="trust-badge-pill"><i class="bi bi-person-check"></i> One Vote Per Student</span>
                <span class="trust-badge-pill"><i class="bi bi-lock-fill"></i> Privacy Act Compliant</span>
            </div>
        </div>

        <!-- Right Side: Verify Form -->
        <div class="hero-form-panel animate-fade-up delay-200">
            <!-- H3: User Control | H7: Flexibility -->
            <div class="form-card" style="margin: 0; box-shadow: 0 20px 60px rgba(0,0,0,0.13); border-radius: 16px;" aria-label="Voter verification panel">
                <!-- H6: Recognition - card header labels context -->
                <div class="form-card-header">
                    <h2><i class="bi bi-ballot"></i> Voter Access Portal</h2>
                    <p>Continue with your official BatStateU Google Workspace account</p>
                </div>
                <!-- Google BSU Verify Button -->
                <a href="<?= e(voting_url('/auth/google')) ?>" class="btn-google-bsu w-100 is-disabled" id="googleVerifyBtn" aria-disabled="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48" style="flex-shrink:0;">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        <path fill="none" d="M0 0h48v48H0z"/>
                    </svg>
                    <span>Sign in Using BatStateU Institutional Account</span>
                </a>
                <div class="data-privacy-consent">
                    <input type="checkbox" id="dataPrivacyConsent" class="form-check-input" aria-describedby="privacyConsentHint">
                    <label for="dataPrivacyConsent">
                        I acknowledge that I have read and understood the <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Data Privacy Policy</a> and voluntarily consent to the collection, processing, and protection of my personal voter information in accordance with the provisions of the Data Privacy Act of 2012.
                    </label>
                </div>
                <div class="privacy-consent-hint" id="privacyConsentHint">
                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                    <span>Please check the privacy consent box before continuing.</span>
                </div>
                <p class="google-access-note">
                    <i class="bi bi-person-check-fill" aria-hidden="true"></i>
                    <span>Access to the electronic ballot is strictly limited to officially verified voters using authorized BatStateU Google Workspace accounts.</span>
                </p>
            </div>
        </div>

    </div>
</section>

<!-- Flash Error Modal (for homepage verify errors) -->
<?php if ($errorMsg = voting_flash('error')): ?>
<div class="modal-overlay" id="errorFlashModal" style="display: flex; background: rgba(31, 24, 23, 0.6); backdrop-filter: blur(8px); z-index: 9999; position: fixed; top: 0; left: 0; width: 100%; height: 100%; justify-content: center; align-items: center;">
    <div class="modal-content animate-zoom-in" style="max-width: 420px; text-align: center; background: white; border-radius: 16px; padding: 1rem; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <div class="modal-header" style="border-bottom: none; padding-bottom: 0; display: flex; justify-content: flex-end;">
            <button type="button" onclick="document.getElementById('errorFlashModal').style.display='none';" aria-label="Close" style="background: transparent; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 0 2.5rem 2rem;">
            <div style="width: 80px; height: 80px; background: #FEE2E2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 2.5rem; color: #DC2626;"></i>
            </div>
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; color: #1f1817; margin-bottom: 0.8rem;">Verification Failed</h3>
            <p style="color: #4F564C; font-size: 1rem; line-height: 1.5; margin-bottom: 2rem;"><?= e($errorMsg) ?></p>
            <button type="button" class="btn btn-brown w-100" onclick="document.getElementById('errorFlashModal').style.display='none';" style="padding: 1rem; font-size: 1.1rem; border-radius: 12px;">Try Again</button>
        </div>
    </div>
</div>
<?php endif; ?>

<section id="voting-flow" class="content-band" style="padding: 6rem 0; background: rgba(255,255,255,0.4);" aria-labelledby="voting-flow-heading">
    <div class="container">
        <!-- H2: Match Real World - section label uses voter language -->
        <div class="section-heading animate-fade-up delay-200" style="text-align: center; margin-bottom: 4.5rem;">
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(201,147,59,0.1); padding: 0.4rem 1.2rem; border-radius: 999px; margin-bottom: 1.25rem;">
                <i class="bi bi-stars" style="color: var(--ssc-orange);"></i>
                <span style="color:var(--ssc-brown); font-weight:800; font-size: 0.75rem; letter-spacing:0.1em; text-transform:uppercase;">The Voting Process</span>
            </div>
            <h2 id="voting-flow-heading" style="font-family: 'Outfit', sans-serif; font-weight: 900; color: var(--ssc-brown); font-size: clamp(2rem, 4vw, 2.75rem); margin-bottom: 1rem;">Simple Steps to Cast Your Vote</h2>
            <p style="max-width: 650px; margin: 0 auto 1.5rem; color: #4b5563; font-size: 1.15rem; line-height: 1.6; font-weight: 500;">
                Our streamlined interface is designed to be <span style="color: var(--ssc-nav); font-weight: 700;">intuitive, accessible, and error-free</span> - ensuring your voice is heard without any confusion.
            </p>
            <!-- H1: Visibility of System Status - show current election state inline -->
            <div class="flow-status-pill" style="display: inline-flex; align-items: center; gap: 0.75rem; background: #fff; padding: 0.5rem 1.5rem; border-radius: 999px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <?= $isOpen
                    ? '<span style="color:#15803d;font-weight:700;font-size:0.9rem;display:flex;align-items:center;gap:0.5rem;"><i class="bi bi-circle-fill" style="font-size:0.6rem;animation:pulse-live 1.5s infinite;"></i> ELECTION IS OPEN</span> <span style="color:#9ca3af;">|</span> <span style="color:#6b7280;font-size:0.85rem;">Proceed to verify your identity below.</span>'
                    : '<span style="color:#991b1b;font-weight:700;font-size:0.9rem;display:flex;align-items:center;gap:0.5rem;"><i class="bi bi-x-circle-fill"></i> ELECTION IS CLOSED</span> <span style="color:#9ca3af;">|</span> <span style="color:#6b7280;font-size:0.85rem;">Voting is temporarily unavailable.</span>' ?>
            </div>
        </div>

        <!-- H6: Recognition over Recall - numbered visual pipeline with connectors -->
        <div class="vf-steps-row" role="list" aria-label="Voting steps">

            <!-- Step 1 - Verify -->
            <!-- H4: Consistency - every card follows same structure -->
            <article class="vf-step-card animate-fade-up delay-300" role="listitem"
                     tabindex="0" aria-label="Step 1: Verify your identity">
                <!-- H8: Aesthetic & Minimalist - icon + number only, no noise -->
                <div class="vf-step-icon-wrap" aria-hidden="true">
                    <div class="vf-step-num">1</div>
                    <i class="bi bi-person-vcard-fill vf-icon"></i>
                </div>
                <h3 class="vf-step-title">Verify Identity</h3>
                <!-- H5: Error Prevention - tell user exactly what they need -->
                <p class="vf-step-desc">Click <strong>Continue with BatStateU Google Workspace Account</strong> and choose your official BatStateU account. The system will match your email with the enlisted voter list.</p>
                <div class="vf-step-tip">
                    <i class="bi bi-lightbulb-fill"></i>
                    <span>Tip: Your SR Code is printed on your student ID.</span>
                </div>
                <!-- H3: User Control - show what happens next -->
                <div class="vf-step-footer">
                    <span class="vf-badge"><i class="bi bi-clock"></i> ~30 seconds</span>
                </div>
            </article>

            <!-- Connector arrow (H4: Consistency & H2: Real-world flow) -->
            <div class="vf-connector" aria-hidden="true"><i class="bi bi-arrow-right-circle-fill"></i></div>

            <!-- Step 2 - Review -->
            <article class="vf-step-card animate-fade-up delay-400" role="listitem"
                     tabindex="0" aria-label="Step 2: Review candidates">
                <div class="vf-step-icon-wrap" aria-hidden="true">
                    <div class="vf-step-num">2</div>
                    <i class="bi bi-people-fill vf-icon"></i>
                </div>
                <h3 class="vf-step-title">Review Candidates</h3>
                <!-- H6: Recognition - view before choosing, no memory required -->
                <p class="vf-step-desc">Browse candidate <strong>pubmats and profiles</strong> grouped by position. Take your time - your ballot won't lock until you submit.</p>
                <div class="vf-step-tip">
                    <i class="bi bi-lightbulb-fill"></i>
                    <span>Tip: You can change selections freely before confirming.</span>
                </div>
                <div class="vf-step-footer">
                    <span class="vf-badge"><i class="bi bi-clock"></i> At your own pace</span>
                </div>
            </article>

            <div class="vf-connector" aria-hidden="true"><i class="bi bi-arrow-right-circle-fill"></i></div>

            <!-- Step 3 - Submit -->
            <article class="vf-step-card animate-fade-up delay-500" role="listitem"
                     tabindex="0" aria-label="Step 3: Submit your ballot">
                <div class="vf-step-icon-wrap" aria-hidden="true">
                    <div class="vf-step-num">3</div>
                    <i class="bi bi-send-check-fill vf-icon"></i>
                </div>
                <h3 class="vf-step-title">Submit & Confirm</h3>
                <!-- H9: Help users recognize errors - warn about irreversibility -->
                <p class="vf-step-desc">A <strong>confirmation summary</strong> appears before your vote is final. Review it carefully - <em>submitted ballots cannot be changed.</em></p>
                <div class="vf-step-tip vf-tip-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Each student may only vote once per election.</span>
                </div>
                <div class="vf-step-footer">
                    <span class="vf-badge vf-badge-green"><i class="bi bi-shield-lock-fill"></i> Secured & Anonymous</span>
                </div>
            </article>

        </div><!-- /.vf-steps-row -->

        <!-- H10: Help & Documentation - CTA for questions -->
        <div class="vf-help-row animate-fade-up delay-600">
            <span>Want to know more about the platform?</span>
            <a href="#" data-bs-toggle="modal" data-bs-target="#aboutSystemModal" class="vf-help-link">Know More</a>
        </div>

    </div>
</section>

<div class="modal fade privacy-guide-modal" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable privacy-guide-dialog">
        <div class="modal-content privacy-guide-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <p class="eyebrow mb-1">Privacy Notice</p>
                    <h2 class="modal-title" id="privacyModalLabel">Data Privacy Policy</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body privacy-guide-body">
                <section class="privacy-guide-section">
                    <h3><i class="bi bi-shield-lock-fill"></i> Data Privacy Act Compliance</h3>
                    <p>This voting system processes voter information in accordance with Republic Act No. 10173, also known as the Data Privacy Act of 2012.</p>
                    <ul>
                        <li>Your information is used only to verify eligibility, prevent duplicate voting, and support official election administration.</li>
                        <li>Only authorized election administrators may access voter management records.</li>
                        <li>Reasonable security measures are applied to protect voter information and ballot records.</li>
                    </ul>
                </section>

                <section class="privacy-guide-section">
                    <h3><i class="bi bi-person-vcard-fill"></i> Information We Use</h3>
                    <p>Your official BatStateU email is checked against the enlisted voter list before ballot access is granted.</p>
                    <ul>
                        <li>Official school Google email address and enlisted voter status.</li>
                        <li>Basic voter registry details needed to validate your record.</li>
                        <li>Voting activity needed to enforce one vote per student.</li>
                    </ul>
                </section>

                <section class="privacy-guide-section">
                    <h3><i class="bi bi-incognito"></i> Ballot Privacy</h3>
                    <p>Your selected candidates are protected as election records and are not shown in receipt emails.</p>
                    <ul>
                        <li>The system records that you have voted to prevent duplicate submissions.</li>
                        <li>Published or downloadable results are intended for tallies, not individual voter choices.</li>
                        <li>Submitted ballots cannot be changed or resubmitted.</li>
                    </ul>
                </section>

                <section class="privacy-guide-section privacy-guide-warning">
                    <h3><i class="bi bi-check2-circle"></i> Your Consent</h3>
                    <p class="mb-0">Before continuing with Google, you must confirm that you have read this notice and consent to the processing of your information for official OrgChain election purposes.</p>
                </section>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-brown w-100" data-bs-dismiss="modal">Got It</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade privacy-guide-modal" id="aboutSystemModal" tabindex="-1" aria-labelledby="aboutSystemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable privacy-guide-dialog tutorial-guide-dialog">
        <div class="modal-content privacy-guide-content tutorial-guide-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <p class="eyebrow mb-1">Know More</p>
                    <h2 class="modal-title" id="aboutSystemModalLabel">What Is This System About?</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body privacy-guide-body">
                <section class="privacy-guide-section">
                    <h3><i class="bi bi-ballot-fill"></i> Official OrgChain Voting Platform</h3>
                    <p>This platform is the official OrgChain voting system for enlisted BatStateU students.</p>
                    <ul>
                        <li>Students verify their identity using an official BatStateU Google Workspace account.</li>
                        <li>The system checks voter eligibility against the enlisted voter list.</li>
                        <li>Voters can review candidates and submit their ballot securely online.</li>
                    </ul>
                </section>

                <section class="privacy-guide-section">
                    <h3><i class="bi bi-shield-check"></i> Secure Voting Flow</h3>
                    <p>The system is designed to protect election integrity and reduce manual voting errors.</p>
                    <ul>
                        <li>Each enlisted student can vote only once per election.</li>
                        <li>Submitted ballots cannot be changed or resubmitted.</li>
                        <li>Election administrators manage voters, candidates, tallies, and reports.</li>
                    </ul>
                </section>

                <section class="privacy-guide-section privacy-guide-warning">
                    <h3><i class="bi bi-lock-fill"></i> Privacy First</h3>
                    <p class="mb-0">The system uses voter information only for verification, election administration, and duplicate-vote prevention under the Data Privacy Act of 2012.</p>
                </section>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-brown w-100" data-bs-dismiss="modal">Got It</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade privacy-guide-modal" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable privacy-guide-dialog tutorial-guide-dialog">
        <div class="modal-content privacy-guide-content tutorial-guide-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <p class="eyebrow mb-1">Voting Guidelines and Procedures</p>
                    <h2 class="modal-title" id="tutorialModalLabel">How to Vote in the System</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body privacy-guide-body">
                <section class="privacy-guide-section tutorial-step-section">
                    <h3><span class="tutorial-step-number">1</span> Review and Confirm Data Privacy Consent</h3>
                    <p>Carefully read the Data Privacy Policy and provide your consent by selecting the acknowledgment checkbox. After confirmation, click <strong>Continue with BatStateU Google Workspace Account</strong> to proceed securely.</p>
                </section>

                <section class="privacy-guide-section tutorial-step-section">
                    <h3><span class="tutorial-step-number">2</span> Authenticate Institutional Account Credentials</h3>
                    <p>Select your official BatStateU Google Workspace account for authentication. The system will validate your eligibility by verifying your institutional email address against the official voter registry.</p>
                </section>

                <section class="privacy-guide-section tutorial-step-section">
                    <h3><span class="tutorial-step-number">3</span> Review Candidate Profiles and Select Preferred Candidates</h3>
                    <p>Carefully review the list of candidates according to their respective positions. Voters may modify their selections prior to the final submission of the electronic ballot.</p>
                </section>

                <section class="privacy-guide-section tutorial-step-section">
                    <h3><span class="tutorial-step-number">4</span>Finalize and Confirm Ballot Submission</h3>
                    <p>Review the ballot summary thoroughly before final submission. Once the electronic ballot has been officially submitted, no further modifications or additional submissions will be permitted.</p>
                </section>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-brown w-100" data-bs-dismiss="modal">Proceed to Voting</button>
            </div>
        </div>
    </div>
</div>

<a href="#" class="floating-tutorial-btn" data-bs-toggle="modal" data-bs-target="#tutorialModal" aria-label="Open voting tutorial">
    <i class="bi bi-play-circle-fill" aria-hidden="true"></i>
    <span>Tutorial</span>
</a>


<!-- Footer -->
<footer class="home-footer" style="background: var(--ssc-brown); color: rgba(255,255,255,0.85); padding: 2rem 0;">
    <div class="container-fluid home-footer-inner" style="padding: 0 2.5rem;">
        <div class="home-footer-grid" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <!-- Left: Branding -->
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <img src="<?= e(voting_asset('img/orgchain-logo.png')) ?>" alt="OrgChain Logo" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover;">
                <div>
                    <div style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 0.95rem; color: #fff;">OrgChain</div>
                    <div style="font-size: 0.78rem; color: rgba(255,255,255,0.6);">OrgChain Electoral Board</div>
                </div>
            </div>

            <!-- Center: Links -->
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; justify-content: center;">
                <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">
                    <i class="bi bi-info-circle"></i> About the System
                </a>
                <span style="color: rgba(255,255,255,0.25);">|</span>
                <a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">
                    <i class="bi bi-question-circle"></i> Help
                </a>
            </div>

            <!-- Right: Copyright -->
            <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5); text-align: right;">
                &copy; 2026 Batangas State University ARASOF-Nasugbu. OrgChain Official Voting. All Rights Reserved.
            </div>
        </div>

        <!-- Bottom Bar -->
        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.12); text-align: center;">
            <small style="color: rgba(255,255,255,0.4); font-size: 0.75rem;">
                <i class="bi bi-shield-check" style="color: #22c55e;"></i>
                This platform is secured and complies with the Data Privacy Act of 2012. Votes are anonymous and cannot be traced back to individual voters.
            </small>
        </div>
    </div>
</footer>
