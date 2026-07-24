/**
 * OrgChain landing interactions:
 * - Student login modal
 * - Office tabs (dots / prev-next) for block details
 * - Smooth in-page nav
 * - Mobile nav
 */

const BLOCK_DATA = {
    0: {
        title: 'Student Organization',
        short: 'SO',
        hash: '0x7a3f…b2c1 · genesis · student-org',
        description:
            'Student organizations form the first block in OrgChain. Registered orgs create proposals, activity plans, and compliance submissions that kick off the on-chain workflow.',
        meta: [
            ['Role', 'Initiator'],
            ['Chain position', 'Block #01'],
            ['Actions', 'Submit · Update · Track'],
            ['Status', 'Active on chain'],
        ],
    },
    1: {
        title: 'Office of Student Organization',
        short: 'OSO',
        hash: '0x91c4…e8d0 · review · oso',
        description:
            'The Office of Student Organization (OSO) reviews and endorses organization requests. Approvals and remarks are sealed into this block for a transparent audit trail.',
        meta: [
            ['Role', 'Validator'],
            ['Chain position', 'Block #02'],
            ['Actions', 'Review · Endorse · Return'],
            ['Status', 'Linked to SO'],
        ],
    },
    2: {
        title: 'Sustainable Development Office',
        short: 'SDO',
        hash: '0xd12e…4af9 · sustainability · sdo',
        description:
            'The Sustainable Development Office aligns student organization initiatives with campus sustainability goals and records acknowledgements on the chain.',
        meta: [
            ['Role', 'Steward'],
            ['Chain position', 'Block #03'],
            ['Actions', 'Align · Acknowledge · Archive'],
            ['Status', 'Linked to OSO'],
        ],
    },
    3: {
        title: 'OVCAA',
        short: 'OVCAA',
        hash: '0xb8e1…c4a2 · academic · ovcaa',
        description:
            'The Office of the Vice Chancellor for Academic Affairs (OVCAA) reviews academic alignment and policy compliance for student organization activities.',
        meta: [
            ['Role', 'Academic review'],
            ['Chain position', 'Block #04'],
            ['Actions', 'Review · Align · Endorse'],
            ['Status', 'Final link'],
        ],
    },
};

const BLOCK_COUNT = 4;

function qs(sel, root = document) {
    return root.querySelector(sel);
}

function qsa(sel, root = document) {
    return [...root.querySelectorAll(sel)];
}

/**
 * Smooth-scroll to in-page sections (Chain, Blockchain, Offices, About).
 * Uses animated scroll so it slides instead of teleporting.
 */
function smoothScrollTo(target, { updateHash = true } = {}) {
    const el = typeof target === 'string' ? qs(target) : target;
    if (!el) return;

    // On mobile the pill header docks at the bottom, so no top offset is needed
    const isBottomDock = window.matchMedia('(max-width: 899px)').matches;
    const header = qs('#siteHeader');
    const headerH = isBottomDock ? 8 : (header ? header.getBoundingClientRect().height + 14 : 84);
    const top = window.scrollY + el.getBoundingClientRect().top - headerH - 12;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion) {
        window.scrollTo(0, Math.max(0, top));
    } else {
        window.scrollTo({
            top: Math.max(0, top),
            behavior: 'smooth',
        });
    }

    if (updateHash && el.id) {
        // Keep URL in sync without an extra jump
        history.pushState(null, '', `#${el.id}`);
    }
}

function initSmoothNav() {
    // All same-page anchor links (header + mobile)
    qsa('a[href^="#"]').forEach((link) => {
        const hash = link.getAttribute('href');
        if (!hash || hash === '#') return;
        const id = hash.slice(1);
        if (!document.getElementById(id)) return;

        link.addEventListener('click', (e) => {
            e.preventDefault();
            // Close mobile menu if open
            const nav = qs('#mobileNav');
            const toggle = qs('#menuToggle');
            if (nav && !nav.hidden) {
                nav.hidden = true;
                toggle?.setAttribute('aria-expanded', 'false');
                toggle?.setAttribute('aria-label', 'Open menu');
            }
            // Small delay so mobile menu collapse doesn't fight the scroll
            window.requestAnimationFrame(() => smoothScrollTo(`#${id}`));
        });
    });

    // If page loaded with a hash, slide there after layout
    if (window.location.hash) {
        const id = window.location.hash;
        window.setTimeout(() => smoothScrollTo(id, { updateHash: false }), 80);
    }
}

function initMobileNav() {
    const toggle = qs('#menuToggle');
    const nav = qs('#mobileNav');
    if (!toggle || !nav) return;

    const close = () => {
        nav.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open menu');
    };

    const open = () => {
        nav.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Close menu');
    };

    toggle.addEventListener('click', () => {
        if (nav.hidden) open();
        else close();
    });

    qs('#openLoginBtnMobile')?.addEventListener('click', () => {
        close();
        openLogin();
    });
}

/**
 * Scroll-reveal: fade + rise elements in as they enter the viewport,
 * with a small stagger for items sharing the same parent grid/list.
 */
function initScrollReveal() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const targets = qsa(
        [
            '.section-header',
            '.bc-card',
            '.bc-steps',
            '.bc-steps-list li',
            '.office-card',
            '.about-copy',
            '.stat',
        ].join(',')
    );

    if (!targets.length) return;

    if (reduceMotion || !('IntersectionObserver' in window)) {
        document.documentElement.classList.add('no-observer');
        return;
    }

    // Stagger siblings so grids cascade in
    const groups = new Map();
    targets.forEach((el) => {
        const parent = el.parentElement;
        const index = groups.get(parent) ?? 0;
        groups.set(parent, index + 1);
        el.style.setProperty('--reveal-delay', `${Math.min(index * 90, 450)}ms`);
        el.classList.add('reveal');
    });

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
    );

    targets.forEach((el) => observer.observe(el));
}

/**
 * Scroll spy: highlight the pill nav link of the section in view.
 */
function initScrollSpy() {
    const links = qsa('.nav-desktop a[href^="#"]');
    if (!links.length || !('IntersectionObserver' in window)) return;

    const byId = new Map();
    links.forEach((link) => {
        const id = link.getAttribute('href').slice(1);
        const section = document.getElementById(id);
        if (section) byId.set(section, link);
    });

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                links.forEach((l) => l.classList.remove('is-active'));
                byId.get(entry.target)?.classList.add('is-active');
            });
        },
        { rootMargin: '-35% 0px -55% 0px' }
    );

    byId.forEach((_link, section) => observer.observe(section));
}

function initHeaderScroll() {
    const header = qs('#siteHeader');
    if (!header) return;

    const onScroll = () => {
        header.classList.toggle('is-scrolled', window.scrollY > 8);
    };

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

let loginOpen = false;

function openLogin() {
    const modal = qs('#loginModal');
    if (!modal) return;
    modal.hidden = false;
    loginOpen = true;
    document.body.style.overflow = 'hidden';
    setTimeout(() => qs('#sr_code')?.focus(), 50);
}

function closeLogin() {
    const modal = qs('#loginModal');
    if (!modal) return;
    modal.hidden = true;
    loginOpen = false;
    document.body.style.overflow = '';
}

function initLoginModal() {
    qs('#openLoginBtn')?.addEventListener('click', openLogin);
    qs('#closeLoginBtn')?.addEventListener('click', closeLogin);

    const modal = qs('#loginModal');
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closeLogin();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && loginOpen) closeLogin();
    });

    const pwd = qs('#password');
    const toggle = qs('#togglePassword');
    toggle?.addEventListener('click', () => {
        if (!pwd) return;
        const show = pwd.type === 'password';
        pwd.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Hide' : 'Show';
        toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });

    const sr = qs('#sr_code');
    sr?.addEventListener('input', () => {
        let v = sr.value.replace(/[^0-9-]/g, '');
        if (/^\d{3}/.test(v) && v[2] !== '-') {
            v = v.slice(0, 2) + '-' + v.slice(2);
        }
        if (v.length > 8) v = v.slice(0, 8);
        sr.value = v;
    });
}

/**
 * Smooth content update for the detail panel (fade + slight slide).
 */
function showBlockDetail(index, direction = 'left') {
    const data = BLOCK_DATA[index];
    if (!data) return;

    const panel = qs('#blockDetail');
    const title = qs('#blockDetailTitle');
    const desc = qs('#blockDetailDesc');
    const hash = qs('#blockDetailHash');
    const meta = qs('#blockDetailMeta');

    if (!panel || !title || !desc || !hash || !meta) return;

    const prev = Number(panel.dataset.activeBlock ?? 0);
    if (prev === index && panel.dataset.ready === '1') return;

    const dir = direction || (index >= prev ? 'left' : 'right');
    panel.dataset.activeBlock = String(index);

    // Animate out
    panel.classList.remove('is-sliding-in', 'from-left', 'from-right');
    panel.classList.add('is-sliding-out', dir === 'left' ? 'to-left' : 'to-right');

    window.setTimeout(() => {
        title.textContent = data.title;
        desc.textContent = data.description;
        hash.textContent = data.hash;
        meta.innerHTML = data.meta
            .map(([k, v]) => `<li><span>${k}</span><span>${v}</span></li>`)
            .join('');

        // Snap to opposite side without transition, then slide in
        panel.classList.remove('is-sliding-out', 'to-left', 'to-right');
        panel.classList.add('is-pre-in', dir === 'left' ? 'from-right' : 'from-left');

        // Force reflow so the browser registers the off-screen position
        // eslint-disable-next-line no-unused-expressions
        panel.offsetWidth;

        panel.classList.remove('is-pre-in');
        panel.classList.add('is-sliding-in');
        panel.dataset.ready = '1';

        window.setTimeout(() => {
            panel.classList.remove('is-sliding-in', 'from-left', 'from-right');
        }, 420);
    }, 200);
}

function initBlockchain() {
    const scene = qs('#blockchain');
    if (!scene) return;

    let active = 0;

    const updateFrontLabel = (index) => {
        const nameEl = qs('#chainFrontName');
        const data = BLOCK_DATA[index];
        if (!nameEl || !data) return;

        nameEl.classList.add('is-swapping');
        window.setTimeout(() => {
            nameEl.textContent = data.title;
            nameEl.classList.remove('is-swapping');
        }, 180);
    };

    const setActiveUI = (index) => {
        qsa('.chain-dot').forEach((dot) => {
            const on = Number(dot.dataset.block) === index;
            dot.classList.toggle('is-active', on);
            dot.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        updateFrontLabel(index);
    };

    const goTo = (index, { showDetail = true } = {}) => {
        index = Math.min(BLOCK_COUNT - 1, Math.max(0, index));
        const prev = active;
        const direction = index >= prev ? 'left' : 'right';

        active = index;
        setActiveUI(index);

        if (showDetail) {
            showBlockDetail(index, direction);
        }
    };

    const panel = qs('#blockDetail');
    if (panel) {
        panel.dataset.activeBlock = '0';
        panel.dataset.ready = '1';
    }
    setActiveUI(0);

    qsa('.chain-dot').forEach((dot) => {
        dot.addEventListener('click', () => {
            goTo(Number(dot.dataset.block), { showDetail: true });
        });
    });

    qs('#chainPrev')?.addEventListener('click', () => {
        goTo(active - 1, { showDetail: true });
    });
    qs('#chainNext')?.addEventListener('click', () => {
        goTo(active + 1, { showDetail: true });
    });

    qsa('.focus-block-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            smoothScrollTo('#blockchain');
            window.setTimeout(() => goTo(Number(btn.dataset.block), { showDetail: true }), 280);
        });
    });

    qs('#scrollDownBtn')?.addEventListener('click', () => {
        smoothScrollTo('#blockchain');
        window.setTimeout(() => goTo(0, { showDetail: true }), 280);
    });

    // Hide the scroll cue once the user has moved past the hero
    const scrollCue = qs('#scrollDownBtn');
    if (scrollCue) {
        const onScroll = () => {
            scrollCue.classList.toggle('is-hidden', window.scrollY > 120);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    if (scene) scene.blockchainGoTo = goTo;
}

document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initHeaderScroll();
    initSmoothNav();
    initLoginModal();
    initBlockchain();
    initScrollReveal();
    initScrollSpy();
});
