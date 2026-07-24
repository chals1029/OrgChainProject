document.addEventListener('DOMContentLoaded', () => {
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    // Special styling for CABEIHM college card
    const applySpecialCardStyles = () => {
        const specialColleges = [
            {
                name: "College of Accountancy, Business, Economics, and International Hospitality Management",
                aliases: ["CABEIHM"],
                className: "college-card-yellow-white"
            },
            {
                name: "College of Informatics and Computing Sciences",
                aliases: ["CICS"],
                className: "college-card-green-white"
            },
            {
                name: "College of Arts and Sciences",
                aliases: ["CAS"],
                className: "college-card-red-white"
            },
            {
                name: "College of Criminal Justice Education",
                aliases: ["CCJE"],
                className: "college-card-gray-white"
            },
            {
                name: "College of Nursing and Allied Health Sciences",
                aliases: ["CHS"],
                className: "college-card-purple-white"
            },
            {
                name: "College of Teacher Education",
                aliases: ["CTE"],
                className: "college-card-blue-white"
            }
        ];

        const cards = document.querySelectorAll('.college-turnout-row');
        cards.forEach(card => {
            const titleEl = card.querySelector('.college-row-title');
            if (!titleEl) return;

            const titleText = titleEl.textContent.trim();
            specialColleges.forEach(college => {
                if (titleText === college.name || (college.aliases || []).includes(titleText)) {
                    card.classList.add(college.className);
                }
            });

            // Highlight 0% turnout
            const turnoutEl = card.querySelector('.turnout-badge');
            if (turnoutEl && turnoutEl.textContent.includes('0%')) {
                turnoutEl.classList.add('turnout-zero');
            }
        });

        // Global check for 0% in main stats
        document.querySelectorAll('.dashboard-stat-card strong, .progress-box span').forEach(el => {
            if (el.textContent.includes('0%')) {
                el.classList.add('turnout-zero');
            }
        });
    };

    applySpecialCardStyles();

    document.querySelectorAll('[data-print-section]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetSelector = button.getAttribute('data-print-section') || '';
            const target = targetSelector ? document.querySelector(targetSelector) : null;

            if (!target) {
                window.print();
                return;
            }

            document.body.classList.add('is-printing-section');

            const cleanup = () => {
                document.body.classList.remove('is-printing-section');
                window.removeEventListener('afterprint', cleanup);
            };

            window.addEventListener('afterprint', cleanup);
            window.print();
            window.setTimeout(cleanup, 1200);
        });
    });

    const announcementBanner = document.querySelector('[data-announcement-expires-at]');
    if (announcementBanner) {
        const countdown = announcementBanner.querySelector('[data-announcement-countdown]');
        const expiryTime = Date.parse(announcementBanner.getAttribute('data-announcement-expires-at') || '');
        let announcementCountdownTimer = null;

        const pluralize = (value, label) => `${value} ${label}${value === 1 ? '' : 's'}`;

        const updateAnnouncementCountdown = () => {
            if (!Number.isFinite(expiryTime)) return;

            const secondsLeft = Math.max(0, Math.floor((expiryTime - Date.now()) / 1000));

            if (secondsLeft <= 0) {
                announcementBanner.remove();
                if (announcementCountdownTimer !== null) {
                    clearInterval(announcementCountdownTimer);
                }
                return;
            }

            if (!countdown) return;

            const days = Math.floor(secondsLeft / 86400);
            const hours = Math.floor((secondsLeft % 86400) / 3600);
            const minutes = Math.floor((secondsLeft % 3600) / 60);
            const seconds = secondsLeft % 60;

            if (days > 0) {
                countdown.textContent = `${pluralize(days, 'day')} ${pluralize(hours, 'hour')} left`;
            } else if (hours > 0) {
                countdown.textContent = `${pluralize(hours, 'hour')} ${pluralize(minutes, 'minute')} left`;
            } else {
                countdown.textContent = `${pluralize(minutes, 'minute')} ${pluralize(seconds, 'second')} left`;
            }
        };

        updateAnnouncementCountdown();
        announcementCountdownTimer = setInterval(updateAnnouncementCountdown, 1000);
    }

    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');

    if (window.bootstrap) {
        [...tooltipTriggerList].forEach((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    const privacyConsent = document.getElementById('dataPrivacyConsent');
    const googleVerifyBtn = document.getElementById('googleVerifyBtn');
    const privacyConsentHint = document.getElementById('privacyConsentHint');

    function syncGooglePrivacyConsent(showHint = false) {
        if (!privacyConsent || !googleVerifyBtn) return;

        const consented = privacyConsent.checked;
        googleVerifyBtn.classList.toggle('is-disabled', !consented);
        googleVerifyBtn.setAttribute('aria-disabled', consented ? 'false' : 'true');

        if (privacyConsentHint) {
            privacyConsentHint.classList.toggle('is-visible', showHint && !consented);
        }
    }

    if (privacyConsent && googleVerifyBtn) {
        syncGooglePrivacyConsent();

        privacyConsent.addEventListener('change', () => {
            syncGooglePrivacyConsent();
        });

        googleVerifyBtn.addEventListener('click', (event) => {
            if (privacyConsent.checked) return;

            event.preventDefault();
            syncGooglePrivacyConsent(true);
            privacyConsent.focus();
        });
    }

    const tutorialModal = document.getElementById('tutorialModal');
    const errorFlashModal = document.getElementById('errorFlashModal');

    if (tutorialModal && !errorFlashModal && window.bootstrap) {
        const tutorial = bootstrap.Modal.getOrCreateInstance(tutorialModal);
        tutorial.show();
    }

    const loadTallySection = async (url, updateHistory = true) => {
        const currentTally = document.getElementById('live-tally') || document.getElementById('canvassing-results');

        if (!currentTally || !window.DOMParser) {
            window.location.href = url;
            return;
        }

        currentTally.classList.add('is-loading');
        currentTally.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to load filtered tally.');
            }

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextTally = nextDocument.getElementById(currentTally.id);

            if (!nextTally) {
                throw new Error('Filtered tally was not found.');
            }

            currentTally.replaceWith(nextTally);
            document.querySelectorAll('.js-tally-filter-form').forEach(syncTallyProgramOptions);
            if (typeof applySpecialCardStyles === 'function') applySpecialCardStyles();

            if (updateHistory) {
                window.history.pushState({}, '', response.url || url);
            }
        } catch (error) {
            window.location.href = url;
        }
    };

    function syncTallyProgramOptions(form) {
        if (!form) return;

        const collegeSelect = form.querySelector('select[name="college"]');
        const programSelect = form.querySelector('select[name="program"][data-program-filter]');

        if (!collegeSelect || !programSelect) return;

        const selectedCollege = String(collegeSelect.value || '').trim();
        let selectedStillVisible = programSelect.value === '';

        Array.from(programSelect.options).forEach((option) => {
            const optionCollege = String(option.dataset.college || '').trim();
            const showOption = option.value === '' || selectedCollege === '' || optionCollege === selectedCollege;

            option.hidden = !showOption;
            option.disabled = !showOption;

            if (option.selected && showOption) {
                selectedStillVisible = true;
            }
        });

        if (!selectedStillVisible) {
            programSelect.value = '';
        }
    }

    document.querySelectorAll('.js-tally-filter-form').forEach(syncTallyProgramOptions);

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('.js-tally-filter-form');

        if (!form) return;

        event.preventDefault();
        syncTallyProgramOptions(form);
        const formData = new FormData(form);
        const params = new URLSearchParams();

        formData.forEach((value, key) => {
            const normalized = String(value).trim();

            if (normalized !== '') {
                params.set(key, normalized);
            }
        });

        const url = `${form.action}${params.toString() ? `?${params.toString()}` : ''}`;
        loadTallySection(url);
    });

    document.addEventListener('change', (event) => {
        const select = event.target.closest('.js-tally-filter-form select');

        if (select && select.form) {
            if (select.name === 'college') {
                syncTallyProgramOptions(select.form);
            }

            select.form.requestSubmit();
        }
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('.js-tally-filter-link');

        if (!link) return;

        event.preventDefault();
        loadTallySection(link.href);
    });

    window.addEventListener('popstate', () => {
        loadTallySection(window.location.href, false);
    });

    const positionBlocksAll = Array.from(document.querySelectorAll('.position-block-new'));
    const positionBlocks = positionBlocksAll.filter((block) => block.dataset.requiresChoice !== '0');
    const totalPositions = positionBlocks.length;

    const ballotForm = document.getElementById('ballotForm');
    const ballotDraftSchema = 'v1';
    const ballotDraftTtlMs = 24 * 60 * 60 * 1000;

    /** Ballot drafts: sessionStorage keyed by election + voter; survives reload, clears on tab close */
    function ballotDraftIdsValid(id) {
        return /^[1-9]\d*$/.test(String(id));
    }

    function getBallotDraftStorageKey(form) {
        if (!form) {
            return '';
        }

        const key = String(form.dataset.ballotDraftKey || '').trim();

        return key !== '' ? `sscBallotDraft_${ballotDraftSchema}_${key}` : '';
    }

    function saveBallotSelectionsDraft(form) {
        const storageKey = getBallotDraftStorageKey(form);

        if (!storageKey || typeof sessionStorage === 'undefined') {
            return;
        }

        try {
            const choices = {};

            form.querySelectorAll('.candidate-choice-input:checked').forEach((input) => {
                const pos = input.dataset.position;

                if (pos && ballotDraftIdsValid(pos) && ballotDraftIdsValid(input.value)) {
                    choices[String(pos)] = String(input.value);
                }
            });

            sessionStorage.setItem(storageKey, JSON.stringify({ choices, savedAt: Date.now() }));
        } catch (e) {
            /* QuotaExceededError / private mode */
        }
    }

    function clearBallotSelectionsDraft(form) {
        const storageKey = getBallotDraftStorageKey(form);

        if (!storageKey || typeof sessionStorage === 'undefined') return;

        try {
            sessionStorage.removeItem(storageKey);
        } catch (e) {
            /* ignore */
        }
    }

    function normalizeOneChoicePerContestBlock() {
        positionBlocks.forEach((block) => {
            const checked = Array.from(block.querySelectorAll('.candidate-choice-input:checked'));

            checked.slice(1).forEach((input) => {
                input.checked = false;
            });
        });
    }

    function restoreBallotSelectionsDraft(form) {
        const storageKey = getBallotDraftStorageKey(form);

        if (!storageKey || typeof sessionStorage === 'undefined') return;

        let raw;

        try {
            raw = sessionStorage.getItem(storageKey);
        } catch (e) {
            return;
        }

        if (!raw) return;

        let parsed;

        try {
            parsed = JSON.parse(raw);
        } catch (e) {
            sessionStorage.removeItem(storageKey);

            return;
        }

        if (
            parsed === null
            || typeof parsed !== 'object'
            || typeof parsed.choices !== 'object'
            || typeof parsed.savedAt !== 'number'
        ) {
            sessionStorage.removeItem(storageKey);

            return;
        }

        if (Date.now() - parsed.savedAt > ballotDraftTtlMs) {
            sessionStorage.removeItem(storageKey);

            return;
        }

        Object.entries(parsed.choices).forEach(([positionId, candidateId]) => {
            if (!ballotDraftIdsValid(positionId) || !ballotDraftIdsValid(candidateId)) return;

            const input = [...form.querySelectorAll(`input.candidate-choice-input[data-position="${positionId}"]`)].find(
                (element) => String(element.value) === String(candidateId),
            );

            if (!input || input.disabled) return;

            input.checked = true;
        });

        normalizeOneChoicePerContestBlock();
    }

    function syncCandidateCardChecks(block) {
        if (!block) return;
        block.querySelectorAll('.candidate-card-new').forEach((label) => {
            const input = label.querySelector('.candidate-choice-input');
            label.classList.toggle('is-checked', Boolean(input?.checked));
        });
    }

    // Progress Elements
    const sidebarProgressRing = document.getElementById('sidebarProgressRing');
    const sidebarProgressText = document.getElementById('sidebarProgressText');
    const sidebarProgressCount = document.getElementById('sidebarProgressCount');
    const topProgressBar = document.getElementById('topProgressBar');
    const topProgressText = document.getElementById('topProgressText');

    function updateProgress() {
        let completed = 0;

        positionBlocksAll.forEach((block) => {
            syncCandidateCardChecks(block);
        });

        if (totalPositions === 0) {
            if (topProgressBar) topProgressBar.style.width = '100%';
            if (topProgressText) topProgressText.innerText = '0 / 0';
            if (sidebarProgressCount) sidebarProgressCount.innerText = '0';
            if (sidebarProgressRing && sidebarProgressText) {
                sidebarProgressRing.style.strokeDashoffset = '0';
                sidebarProgressText.innerText = '100%';
            }
            const reviewBtnSidebarEarly = document.getElementById('reviewBtnSidebar');
            const reviewBtnMobileEarly = document.getElementById('reviewBtnMobile');
            if (reviewBtnSidebarEarly) reviewBtnSidebarEarly.classList.add('btn-ready-green');
            if (reviewBtnMobileEarly) reviewBtnMobileEarly.classList.add('btn-ready-green');
            return;
        }

        positionBlocks.forEach((block) => {
            const posId = block.id.replace('position_', '');
            const jumpItem = document.getElementById(`jump_${posId}`);
            const choiceInputs = Array.from(block.querySelectorAll('.candidate-choice-input:checked'));
            const voted = choiceInputs.length > 0;

            if (voted) {
                completed += 1;
                if (jumpItem) {
                    jumpItem.classList.add('filled');
                    const icon = jumpItem.querySelector('.jump-nav-name i');
                    if (icon) {
                        icon.classList.remove('bi-circle');
                        icon.classList.add('bi-check-circle');
                    }
                }
            } else if (jumpItem) {
                jumpItem.classList.remove('filled');
                const icon = jumpItem.querySelector('.jump-nav-name i');
                if (icon) {
                    icon.classList.remove('bi-check-circle');
                    icon.classList.add('bi-circle');
                }
            }

            const counter = document.getElementById(`counter_${posId}`);
            if (counter) {
                const maxChoices = String(Number(block.dataset.maxChoices || 1) || 1);
                const selectedCount = document.createElement('strong');
                const maxCount = document.createElement('small');
                selectedCount.textContent = String(choiceInputs.length);
                maxCount.textContent = maxChoices;
                counter.replaceChildren(selectedCount, document.createTextNode(' / '), maxCount);
            }
        });

        positionBlocksAll.forEach((block) => {
            if (block.dataset.requiresChoice !== '0') {
                return;
            }
            const posId = block.id.replace('position_', '');
            const jumpItem = document.getElementById(`jump_${posId}`);
            if (jumpItem) {
                jumpItem.classList.add('filled');
                const icon = jumpItem.querySelector('.jump-nav-name i');
                if (icon) {
                    icon.classList.remove('bi-circle');
                    icon.classList.add('bi-check-circle');
                }
            }
        });

        const percentage = Math.round((completed / Math.max(totalPositions, 1)) * 100);

        // Update top bar
        if (topProgressBar) topProgressBar.style.width = `${percentage}%`;
        if (topProgressText) topProgressText.innerText = `${completed} / ${totalPositions}`;
        if (sidebarProgressCount) sidebarProgressCount.innerText = completed;

        // Update sidebar ring
        if (sidebarProgressRing && sidebarProgressText) {
            const circumference = 163.36; // 2 * pi * 26
            const offset = circumference - (percentage / 100) * circumference;
            sidebarProgressRing.style.strokeDashoffset = offset;
            sidebarProgressText.innerText = `${percentage}%`;
        }

        const reviewBtnSidebar = document.getElementById('reviewBtnSidebar');
        const reviewBtnMobile = document.getElementById('reviewBtnMobile');
        if (positionBlocksAll.length > 0) {
            if (reviewBtnSidebar) reviewBtnSidebar.classList.add('btn-ready-green');
            if (reviewBtnMobile) reviewBtnMobile.classList.add('btn-ready-green');
        }
    }

    positionBlocksAll.forEach((block) => {
        const maxChoices = Number(block.dataset.maxChoices || 1);

        block.addEventListener('change', (event) => {
            const input = event.target;
            const errorContainer = block.querySelector('.inline-error');

            if (input.classList.contains('candidate-choice-input')) {
                if (errorContainer) errorContainer.style.display = 'none';

                if (input.type === 'checkbox' && input.checked) {
                    block.querySelectorAll('.candidate-choice-input').forEach((choiceInput) => {
                        if (choiceInput !== input) choiceInput.checked = false;
                    });
                }

                if (input.type === 'checkbox') {
                    const checkedChoices = Array.from(block.querySelectorAll('.candidate-choice-input:checked'));
                    if (checkedChoices.length > maxChoices) {
                        input.checked = false;
                        if (errorContainer) {
                            const msg = block.querySelector('.error-msg');
                            if (msg) msg.textContent = `You can only select up to ${maxChoices} candidate(s) for this position.`;
                            errorContainer.style.display = 'flex';
                        }
                    }
                }

                syncCandidateCardChecks(block);
                updateProgress();
                if (ballotForm) {
                    saveBallotSelectionsDraft(ballotForm);
                }
            }
        });
    });

    // Global function for Clear Selection
    window.clearSelection = function (positionId) {
        const block = document.getElementById(`position_${positionId}`);
        if (block) {
            block.querySelectorAll('.candidate-choice-input').forEach((input) => { input.checked = false; });
            const errorContainer = block.querySelector('.inline-error');
            if (errorContainer) errorContainer.style.display = 'none';
            syncCandidateCardChecks(block);
            updateProgress();
            if (ballotForm) {
                saveBallotSelectionsDraft(ballotForm);
            }
        }
    };
    const reviewModal = document.getElementById('reviewModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const editVoteBtn = document.getElementById('editVoteBtn');
    const reviewSummary = document.getElementById('reviewSummary');
    const sendBallotCodeBtn = document.getElementById('sendBallotCodeBtn');
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    const ballotEmailCodePanel = document.getElementById('ballotEmailCodePanel');
    const ballotEmailCodeStatus = document.getElementById('ballotEmailCodeStatus');
    const ballotDisplayedCode = document.getElementById('ballotDisplayedCode');
    const ballotCodeTimer = document.getElementById('ballotCodeTimer');
    const ballotCodeInput = document.getElementById('ballotCodeInput');
    const candidatePhotoPreview = document.getElementById('candidatePhotoPreview');
    const candidatePhotoPreviewImg = document.getElementById('candidatePhotoPreviewImg');
    const candidatePhotoPreviewTitle = document.getElementById('candidatePhotoPreviewTitle');
    const candidatePhotoPreviewDialog = document.querySelector('.candidate-photo-preview-dialog');
    let ballotCodeSent = false;
    let ballotCodeResendUntil = 0;
    let ballotCodeResendTimerId = null;
    let lastCandidatePhotoPreviewButton = null;
    /** Ballot may include blank lines (implicit abstain); review/submit always allowed once code is verified. */
    let currentReviewBallotComplete = false;

    // Bind to both review buttons (sidebar and mobile)
    const reviewBtnSidebar = document.getElementById('reviewBtnSidebar');
    const reviewBtnMobile = document.getElementById('reviewBtnMobile');

    function closeCandidatePhotoPreview() {
        if (!candidatePhotoPreview) return;

        candidatePhotoPreview.hidden = true;
        candidatePhotoPreview.classList.remove('is-open');
        document.body.classList.remove('candidate-preview-open');

        if (candidatePhotoPreviewImg) {
            candidatePhotoPreviewImg.src = '';
            candidatePhotoPreviewImg.alt = '';
        }

        if (candidatePhotoPreviewTitle) {
            candidatePhotoPreviewTitle.textContent = '';
        }

        if (lastCandidatePhotoPreviewButton) {
            lastCandidatePhotoPreviewButton.focus({ preventScroll: true });
            lastCandidatePhotoPreviewButton = null;
        }
    }

    document.addEventListener('click', (event) => {
        const previewButton = event.target.closest('.candidate-photo-preview-btn');

        if (previewButton) {
            event.preventDefault();
            event.stopPropagation();

            if (!candidatePhotoPreview || !candidatePhotoPreviewImg) return;

            const candidateName = previewButton.dataset.previewName || 'Candidate';
            candidatePhotoPreviewImg.src = previewButton.dataset.previewSrc || '';
            candidatePhotoPreviewImg.alt = `${candidateName} pubmat`;

            if (candidatePhotoPreviewTitle) {
                candidatePhotoPreviewTitle.textContent = candidateName;
            }

            lastCandidatePhotoPreviewButton = previewButton;
            candidatePhotoPreview.hidden = false;
            candidatePhotoPreview.classList.add('is-open');
            document.body.classList.add('candidate-preview-open');
            candidatePhotoPreviewDialog?.focus({ preventScroll: true });
            return;
        }

        if (event.target.closest('[data-candidate-preview-close]')) {
            closeCandidatePhotoPreview();
        }
    });

    document.addEventListener('keypress', (event) => {
        if (event.target.closest('.candidate-photo-preview-btn')) {
            event.stopPropagation();
        }
    }, true);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && candidatePhotoPreview && !candidatePhotoPreview.hidden) {
            closeCandidatePhotoPreview();
        }
    });

    function setBallotCodeStatus(message, isError = false) {
        if (!ballotEmailCodeStatus) return;

        ballotEmailCodeStatus.classList.toggle('is-error', isError);
        ballotEmailCodeStatus.replaceChildren();

        const icon = document.createElement('i');
        icon.className = `bi ${isError ? 'bi-exclamation-triangle' : 'bi-shield-check'}`;

        const text = document.createElement('span');
        text.textContent = message;

        ballotEmailCodeStatus.append(icon, text);
    }

    function updateBallotCodeControls() {
        if (!sendBallotCodeBtn || !confirmSubmitBtn) return;

        const codeReady = ballotCodeInput && ballotCodeInput.value.replace(/\D/g, '').length === 6;
        const ballotReady = currentReviewBallotComplete;
        const resendCoolingDown = Date.now() < ballotCodeResendUntil;

        sendBallotCodeBtn.disabled = !ballotReady || resendCoolingDown;
        sendBallotCodeBtn.style.opacity = ballotReady && !resendCoolingDown ? '1' : '0.55';
        sendBallotCodeBtn.style.cursor = ballotReady && !resendCoolingDown ? 'pointer' : 'not-allowed';

        if (ballotEmailCodePanel) {
            const shouldShowCodePanel = ballotCodeSent || ballotEmailCodeStatus?.classList.contains('is-error');
            ballotEmailCodePanel.style.display = shouldShowCodePanel ? 'block' : 'none';
        }

        confirmSubmitBtn.style.display = ballotCodeSent ? 'block' : 'none';
        confirmSubmitBtn.disabled = !ballotReady || !ballotCodeSent || !codeReady;
        confirmSubmitBtn.style.opacity = !confirmSubmitBtn.disabled ? '1' : '0.5';
        confirmSubmitBtn.style.cursor = !confirmSubmitBtn.disabled ? 'pointer' : 'not-allowed';
    }

    function formatCountdown(seconds) {
        const safeSeconds = Math.max(0, Number(seconds) || 0);
        const minutes = Math.floor(safeSeconds / 60);
        const remainingSeconds = safeSeconds % 60;

        return `${minutes}:${String(remainingSeconds).padStart(2, '0')}`;
    }

    function setGenerateButtonLabel(label, iconClass = 'bi-shield-lock') {
        if (!sendBallotCodeBtn) return;

        sendBallotCodeBtn.replaceChildren();

        const icon = document.createElement('i');
        icon.className = `bi ${iconClass}`;
        icon.style.marginRight = '0.5rem';

        sendBallotCodeBtn.append(icon, document.createTextNode(label));
    }

    function stopBallotCodeResendTimer() {
        if (ballotCodeResendTimerId !== null) {
            window.clearInterval(ballotCodeResendTimerId);
            ballotCodeResendTimerId = null;
        }

        ballotCodeResendUntil = 0;

        if (ballotCodeTimer) {
            ballotCodeTimer.hidden = true;
            ballotCodeTimer.textContent = '';
        }

        setGenerateButtonLabel(ballotCodeSent ? 'Generate New Number' : 'Generate Verification Number', ballotCodeSent ? 'bi-arrow-clockwise' : 'bi-shield-lock');
        updateBallotCodeControls();
    }

    function renderBallotCodeResendTimer() {
        const remaining = Math.ceil((ballotCodeResendUntil - Date.now()) / 1000);

        if (remaining <= 0) {
            stopBallotCodeResendTimer();
            return;
        }

        const formatted = formatCountdown(remaining);

        if (ballotCodeTimer) {
            ballotCodeTimer.hidden = false;
            ballotCodeTimer.textContent = `You can generate a new number in ${formatted}.`;
        }

        setGenerateButtonLabel(`Generate New Number (${formatted})`, 'bi-hourglass-split');
        updateBallotCodeControls();
    }

    function startBallotCodeResendTimer(seconds = 60) {
        const duration = Math.max(1, Number(seconds) || 60);

        if (ballotCodeResendTimerId !== null) {
            window.clearInterval(ballotCodeResendTimerId);
        }

        ballotCodeResendUntil = Date.now() + (duration * 1000);
        renderBallotCodeResendTimer();
        ballotCodeResendTimerId = window.setInterval(renderBallotCodeResendTimer, 1000);
    }

    function openReviewModal() {
        if (!ballotForm) return;

        let totalCandidateSelections = 0;

        positionBlocks.forEach((block) => {
            const choiceInputs = Array.from(block.querySelectorAll('.candidate-choice-input:checked'));
            totalCandidateSelections += choiceInputs.length;
        });

        let rowsHtml = '';

        positionBlocksAll.forEach((block) => {
            const requiresChoice = block.dataset.requiresChoice !== '0';
            const positionName = escapeHtml(block.querySelector('h2')?.innerText || '');

            if (!requiresChoice) {
                rowsHtml += `
                    <tr style="background: rgba(15,118,110,0.04);">
                        <td style="font-weight: 700; color: #4F564C;">${positionName}</td>
                        <td style="color: #475569; font-weight: 600;">No nominees listed—nothing to vote on.</td>
                        <td class="text-center">
                            <i class="bi bi-info-circle-fill" style="color: #0f766e; font-size: 1.25rem;"></i>
                        </td>
                    </tr>
                `;
                return;
            }

            const checkedChoices = Array.from(block.querySelectorAll('.candidate-choice-input:checked'));

            checkedChoices.forEach((input, index) => {
                rowsHtml += `
                    <tr>
                        <td style="font-weight: 700; color: #4F564C;">${index === 0 ? positionName : ''}</td>
                        <td style="font-weight: 800; color: #1f1817;">${escapeHtml(input.dataset.candidateName || '')}</td>
                        <td class="text-center">
                            <i class="bi bi-check-circle-fill" style="color: #22c55e; font-size: 1.25rem;"></i>
                        </td>
                    </tr>
                `;
            });

            if (checkedChoices.length === 0) {
                rowsHtml += `
                    <tr style="background: rgba(0,0,0,0.02);">
                        <td style="font-weight: 700; color: #4F564C;">${positionName}</td>
                        <td style="color: #64748b; font-weight: 600;"><i class="bi bi-dash-circle"></i> Abstain</td>
                        <td class="text-center">
                            <i class="bi bi-check-circle-fill" style="color: #94a3b8; font-size: 1.25rem;"></i>
                        </td>
                    </tr>
                `;
            }
        });

        reviewSummary.innerHTML = `
            <div class="table-responsive" style="border-radius: 12px; border: 1px solid rgba(111, 115, 95, 0.2); background: #fff;">
                <table class="table align-middle mb-0">
                    <thead style="background: #FBE5C1;">
                        <tr>
                            <th style="padding: 1rem; color: #8B5E3C; font-weight: 800; text-transform: uppercase; font-size: 0.75rem;">Position</th>
                            <th style="padding: 1rem; color: #8B5E3C; font-weight: 800; text-transform: uppercase; font-size: 0.75rem;">Candidate Name</th>
                            <th class="text-center" style="padding: 1rem; color: #8B5E3C; font-weight: 800; text-transform: uppercase; font-size: 0.75rem;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>
            </div>
        `;

        if (totalPositions > 0 && totalCandidateSelections === 0) {
            const noticeHtml = `
                <div style="background: rgba(254, 243, 199, 0.9); color: #78350f; padding: 1rem; border-radius: 8px; border: 1px solid rgba(251,191,36,0.5); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                    <i class="bi bi-info-circle-fill" style="font-size: 1.6rem; color: #ca8a04;"></i>
                    <div>
                        <strong style="display: block; font-size: 1.05rem; margin-bottom: 0.2rem;">No candidate selected on any line</strong>
                        <span style="font-size: 0.9rem;">Blank lines are recorded as Abstain for that position. Number verification is still required before your ballot is accepted.</span>
                    </div>
                </div>
            `;
            reviewSummary.insertAdjacentHTML('afterbegin', noticeHtml);
        }

        const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
        if (confirmSubmitBtn) {
            confirmSubmitBtn.style.boxShadow = '0 8px 24px rgba(34, 197, 94, 0.3)';
            confirmSubmitBtn.innerHTML = '<i class="bi bi-shield-check" style="margin-right: 0.5rem;"></i> Confirm & Submit Ballot';
        }

        currentReviewBallotComplete = true;
        updateBallotCodeControls();
        reviewModal.style.display = 'flex';
    }

    if (sendBallotCodeBtn) {
        sendBallotCodeBtn.addEventListener('click', async () => {
            if (!ballotForm || !currentReviewBallotComplete) return;

            if (Date.now() < ballotCodeResendUntil) {
                renderBallotCodeResendTimer();
                return;
            }

            const sendUrl = new URL(ballotForm.dataset.sendCodeUrl || '/vote/send-code', window.location.href);
            if (sendUrl.origin !== window.location.origin) {
                setBallotCodeStatus('The verification request was blocked because it is not from this site.', true);
                return;
            }
            const csrfInput = ballotForm.querySelector('input[name="_csrf"]');
            const formData = new FormData();
            formData.append('_csrf', csrfInput ? csrfInput.value : '');

            sendBallotCodeBtn.disabled = true;
            setGenerateButtonLabel('Generating Verification Number...', 'bi-arrow-repeat spin');
            setBallotCodeStatus('For security, we are generating a number you must type below to verify your vote.');
            if (ballotDisplayedCode) {
                ballotDisplayedCode.textContent = '------';
            }

            try {
                const response = await fetch(sendUrl.toString(), {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok || !payload.success) {
                    const requestError = new Error(payload.message || 'The verification number could not be generated.');
                    requestError.retryIn = payload.retry_in || 0;
                    throw requestError;
                }

                ballotCodeSent = true;
                if (ballotDisplayedCode) {
                    ballotDisplayedCode.textContent = String(payload.code || '------');
                }
                if (ballotCodeInput) {
                    ballotCodeInput.value = '';
                    ballotCodeInput.focus();
                }
                setBallotCodeStatus(payload.message || 'Type the displayed 6-digit number to verify your vote. Your receipt will be sent by email after submission.');
                startBallotCodeResendTimer(payload.resend_in || 60);
            } catch (error) {
                setBallotCodeStatus(error.message || 'The verification number could not be generated.', true);
                if (error.retryIn) {
                    startBallotCodeResendTimer(error.retryIn);
                } else {
                    setGenerateButtonLabel('Generate Verification Number', 'bi-shield-lock');
                }
            } finally {
                updateBallotCodeControls();
            }
        });
    }

    if (ballotCodeInput) {
        ballotCodeInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            updateBallotCodeControls();
        });
    }

    if (ballotForm) {
        ballotForm.addEventListener('submit', (event) => {
            const codeReady = ballotCodeInput && ballotCodeInput.value.replace(/\D/g, '').length === 6;

            if (!ballotCodeSent || !codeReady) {
                event.preventDefault();
                setBallotCodeStatus('For security, enter the displayed 6-digit number to verify your vote.', true);
                updateBallotCodeControls();
                return;
            }

            const finalConfirm = window.confirm('Are you sure you want to submit your ballot? Once submitted, your vote cannot be changed.');

            if (!finalConfirm) {
                event.preventDefault();
                updateBallotCodeControls();
                return;
            }

            if (confirmSubmitBtn) {
                confirmSubmitBtn.disabled = true;
                confirmSubmitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin" style="margin-right: 0.5rem;"></i> Submitting Ballot...';
            }

            clearBallotSelectionsDraft(ballotForm);
        });
    }

    if (reviewBtnSidebar) reviewBtnSidebar.addEventListener('click', openReviewModal);
    if (reviewBtnMobile) reviewBtnMobile.addEventListener('click', openReviewModal);

    // Ballot liquid-glass mobile drawer + position chips
    (function initBallotMobileNav() {
        const shell = document.querySelector('.ballot-glass-shell');
        if (!shell) return;

        const toggle = document.getElementById('ballotMenuToggle');
        const sidebar = document.getElementById('ballotSidebar');
        const backdrop = document.getElementById('ballotSidebarBackdrop');
        const closeBtn = document.getElementById('ballotSidebarClose');

        const openDrawer = () => {
            sidebar?.classList.add('is-open');
            backdrop?.classList.add('is-open');
            if (backdrop) backdrop.hidden = false;
            toggle?.setAttribute('aria-expanded', 'true');
            toggle?.setAttribute('aria-label', 'Close positions menu');
            document.body.classList.add('ballot-drawer-open');
        };

        const closeDrawer = () => {
            sidebar?.classList.remove('is-open');
            backdrop?.classList.remove('is-open');
            if (backdrop) backdrop.hidden = true;
            toggle?.setAttribute('aria-expanded', 'false');
            toggle?.setAttribute('aria-label', 'Open positions menu');
            document.body.classList.remove('ballot-drawer-open');
        };

        toggle?.addEventListener('click', () => {
            if (sidebar?.classList.contains('is-open')) closeDrawer();
            else openDrawer();
        });
        closeBtn?.addEventListener('click', closeDrawer);
        backdrop?.addEventListener('click', closeDrawer);

        document.getElementById('jumpNavList')?.addEventListener('click', (e) => {
            if (e.target.closest('li') && window.matchMedia('(max-width: 900px)').matches) {
                window.setTimeout(closeDrawer, 180);
            }
        });

        shell.querySelectorAll('.ballot-pos-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                const id = chip.getAttribute('data-jump-target');
                const target = id ? document.getElementById(id) : null;
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        window.addEventListener('resize', () => {
            if (!window.matchMedia('(max-width: 900px)').matches) closeDrawer();
        });
    })();


    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            reviewModal.style.display = 'none';
        });
    }

    if (editVoteBtn) {
        editVoteBtn.addEventListener('click', () => {
            reviewModal.style.display = 'none';
        });
    }

    const exitBtn = document.getElementById('exitBtn');
    const exitBtnHeader = document.getElementById('exitBtnHeader');
    const exitModal = document.getElementById('exitModal');
    const closeExitModalBtn = document.getElementById('closeExitModalBtn');
    const cancelExitBtn = document.getElementById('cancelExitBtn');

    const triggerExit = () => {
        if (exitModal) exitModal.style.display = 'flex';
    };

    if (exitBtn) exitBtn.addEventListener('click', triggerExit);
    if (exitBtnHeader) exitBtnHeader.addEventListener('click', triggerExit);

    if (closeExitModalBtn) {
        closeExitModalBtn.addEventListener('click', () => {
            exitModal.style.display = 'none';
        });
    }

    if (cancelExitBtn) {
        cancelExitBtn.addEventListener('click', () => {
            exitModal.style.display = 'none';
        });
    }

    // Restore draft choices after reload, then sync progress / jump nav
    if (ballotForm && getBallotDraftStorageKey(ballotForm)) {
        restoreBallotSelectionsDraft(ballotForm);
    }

    if (positionBlocksAll.length > 0) {
        positionBlocksAll.forEach((block) => syncCandidateCardChecks(block));
        updateProgress();

        if (ballotForm && getBallotDraftStorageKey(ballotForm)) {
            saveBallotSelectionsDraft(ballotForm);
        }
    }

    const chartCanvas = document.getElementById('turnoutChart');

    if (chartCanvas && window.Chart) {
        const labels = JSON.parse(chartCanvas.dataset.labels || '[]');
        const votes = JSON.parse(chartCanvas.dataset.votes || '[]').map((value) => Number(value) || 0);
        const totalVotes = votes.reduce((sum, value) => sum + value, 0);
        const formatPercent = (value) => {
            if (totalVotes <= 0) return '0';

            const percent = (value / totalVotes) * 100;
            return Number.isInteger(percent) ? String(percent) : percent.toFixed(1);
        };
        const chartLabels = labels.map((label) => {
            const cleaned = String(label)
                .replace(/^College of\s+/i, '')
                .replace('Accountancy, Business, Economics and International Hospitality Management', 'CABEIHM')
                .replace('Criminal Justice Education', 'CCJE')
                .replace('Health Sciences', 'CHS')
                .replace('Nursing and Allied Health Sciences', 'CHS')
                .replace('Informatics and Computing Sciences', 'CICS')
                .replace('Teacher Education', 'CTE')
                .replace(/^Laboratory School$/i, 'LAB SCHOOL')
                .replace(/^Lab School$/i, 'LAB SCHOOL')
                .trim();

            return cleaned;
        });
        const collegeColors = {
            CABEIHM: '#F49322',
            CAS: '#E4803F',
            CCJE: '#6BB2B3',
            CHS: '#9CA18E',
            CICS: '#3F6665',
            CTE: '#4F564C',
            'LAB SCHOOL': '#111111',
        };
        const colorForCollege = (label, index) => {
            const key = String(label).toUpperCase();

            if (key.includes('LAB')) return '#111111';

            return collegeColors[key] || ['#F49322', '#E4803F', '#6BB2B3', '#9CA18E', '#3F6665', '#4F564C'][index % 6];
        };
        const hasVotes = totalVotes > 0;
        const visibleLabels = hasVotes ? chartLabels : ['No ballots yet'];
        const chartData = hasVotes ? votes : [1];
        const chartColors = hasVotes ? chartLabels.map(colorForCollege) : ['#d8ded6'];
        const turnoutLabelsPlugin = {
            id: 'turnoutLabels',
            afterDraw(chart) {
                if (!hasVotes) return;

                const { ctx } = chart;
                const meta = chart.getDatasetMeta(0);
                const dataset = chart.data.datasets[0];
                const fontSize = chart.width < 420 ? 10 : 11;
                const marginX = 10;
                const marginY = 8;

                ctx.save();
                ctx.font = `800 ${fontSize}px Arial, sans-serif`;
                ctx.textBaseline = 'middle';

                meta.data.forEach((arc, index) => {
                    const value = Number(dataset.data[index]) || 0;

                    if (value <= 0) return;

                    const angle = (arc.startAngle + arc.endAngle) / 2;
                    const cos = Math.cos(angle);
                    const sin = Math.sin(angle);
                    const labelText = `${visibleLabels[index]} ${formatPercent(value)}%`;
                    const textWidth = ctx.measureText(labelText).width;
                    const donutLeft = arc.x - arc.outerRadius;
                    const donutRight = arc.x + arc.outerRadius;
                    const lineStartX = arc.x + cos * (arc.outerRadius - 2);
                    const lineStartY = arc.y + sin * (arc.outerRadius - 2);
                    const lineBreakX = arc.x + cos * (arc.outerRadius + 8);
                    const lineBreakY = arc.y + sin * (arc.outerRadius + 8);
                    const labelX = cos >= 0
                        ? Math.min(
                            chart.width - marginX - textWidth,
                            Math.max(donutRight + 12, lineBreakX + 10),
                        )
                        : Math.max(
                            marginX + textWidth,
                            Math.min(donutLeft - 12, lineBreakX - 10),
                        );
                    const labelY = Math.max(
                        marginY + fontSize,
                        Math.min(chart.height - marginY - fontSize, arc.y + sin * (arc.outerRadius + 14)),
                    );
                    const connectorEndX = cos >= 0 ? labelX - 4 : labelX + 4;

                    ctx.strokeStyle = chartColors[index];
                    ctx.lineWidth = 1.5;
                    ctx.beginPath();
                    ctx.moveTo(lineStartX, lineStartY);
                    ctx.lineTo(lineBreakX, lineBreakY);
                    ctx.lineTo(connectorEndX, labelY);
                    ctx.stroke();

                    ctx.fillStyle = '#2F312A';
                    ctx.textAlign = cos >= 0 ? 'left' : 'right';
                    ctx.fillText(labelText, labelX, labelY);
                });

                ctx.restore();
            },
        };

        new Chart(chartCanvas, {
            type: 'doughnut',
            data: {
                labels: visibleLabels,
                datasets: [
                    {
                        label: 'Votes',
                        data: chartData,
                        backgroundColor: chartColors,
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 8,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '48%',
                layout: {
                    padding: hasVotes ? {
                        top: 34,
                        right: 112,
                        bottom: 34,
                        left: 96,
                    } : 8,
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                if (!hasVotes) return 'No ballots yet';

                                const value = Number(context.raw) || 0;
                                return `${context.label}: ${value.toLocaleString()} votes (${formatPercent(value)}%)`;
                            },
                        },
                    },
                },
            },
            plugins: [turnoutLabelsPlugin],
        });
    }

    // Live session clock
    function updateClock() {
        const timerElement = document.getElementById('sessionTimer');
        if (!timerElement) return;

        const now = new Date();
        let hours = now.getHours();
        let minutes = now.getMinutes();
        let seconds = now.getSeconds();
        const ampm = hours >= 12 ? 'PM' : 'AM';

        hours = hours % 12;
        hours = hours ? hours : 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;

        timerElement.innerText = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
    }

    if (document.getElementById('sessionTimer')) {
        setInterval(updateClock, 1000);
        updateClock();
    }

    window.toggleActivityPanel = function () {
        const list = document.getElementById('activityListContent');
        const btn = document.getElementById('activityToggleBtn');
        const icon = btn.querySelector('i');

        list.classList.toggle('is-collapsed');

        if (list.classList.contains('is-collapsed')) {
            icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
        } else {
            icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
        }
    };

    // Auto-scroll logic for Recent Activity
    const activityList = document.getElementById('activityListContent');
    if (activityList) {
        // Initial scroll to top (latest activities)
        activityList.scrollTop = 0;

        // Watch for new activities being added and scroll to top automatically
        const activityObserver = new MutationObserver(() => {
            activityList.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        activityObserver.observe(activityList, { childList: true });
    }
});