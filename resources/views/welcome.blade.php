<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="OrgChain — Blockchain-powered student organization management for Batangas State University.">

    <title>OrgChain | Batangas State University</title>
    <link rel="icon" type="image/png" href="{{ asset('Orgchain logo.png') }}">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @endif
</head>
<body class="landing-body">
    {{-- Decorative ambient layers --}}
    <div class="page-ambient" aria-hidden="true">
        <span class="blob blob-a"></span>
        <span class="blob blob-b"></span>
        <span class="blob blob-c"></span>
    </div>

    <header class="site-header" id="siteHeader">
        <div class="header-pill liquid-glass">
            <a href="{{ url('/') }}" class="brand" aria-label="OrgChain home">
                <img src="{{ asset('Orgchain logo.png') }}" alt="OrgChain Logo" class="brand-logo-img">
            </a>

            <nav class="nav-desktop" aria-label="Primary">
                <a href="#blockchain">Chain</a>
                <a href="#what-is-blockchain">Blockchain</a>
                <a href="#offices">Offices</a>
                <a href="#about">About</a>
            </nav>

            <div class="header-actions">
                <a href="{{ url('/voting-system') }}" class="btn btn-primary btn-pill-action">
                    <svg class="ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span>Voting</span>
                </a>
                <button type="button" class="btn btn-login btn-pill-action" id="openLoginBtn" aria-haspopup="dialog">
                    <svg class="ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Login</span>
                </button>
                <button type="button" class="btn-menu" id="menuToggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobileNav">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>

        <nav class="nav-mobile liquid-glass" id="mobileNav" aria-label="Mobile" hidden>
            <a href="#blockchain">Chain</a>
            <a href="#what-is-blockchain">Blockchain</a>
            <a href="#offices">Offices</a>
            <a href="#about">About</a>
            <a href="{{ url('/voting-system') }}" class="btn btn-primary btn-block nav-mobile-cta">
                <svg class="ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Voting System
            </a>
            <button type="button" class="btn btn-login btn-block" id="openLoginBtnMobile">Student Login</button>
        </nav>
    </header>

    <main>
        {{-- Hero --}}
        <section class="hero" id="hero">
            <div class="hero-bg" aria-hidden="true">
                <div class="hero-mesh"></div>
                <div class="hero-grid"></div>
                <div class="hero-ring hero-ring-1"></div>
                <div class="hero-ring hero-ring-2"></div>
            </div>

            <div class="hero-content">
                <div class="hero-badge">
                    <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/>
                    </svg>
                    The National Engineering University
                </div>

                <h1>
                    Transparent. Secure.<br>
                    <span class="text-accent">Student Organizations</span>
                    on the Chain.
                </h1>

                <p class="hero-lead">
                    OrgChain connects Student Organizations, OSO, the Sustainable Development Office,
                    and OVCAA through a trusted blockchain workflow.
                </p>

                <div class="hero-pills" aria-label="Highlights">
                    <span class="pill">
                        <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Immutable records
                    </span>
                    <span class="pill">
                        <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        Linked offices
                    </span>
                    <span class="pill">
                        <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        SR Code access
                    </span>
                </div>

                <button type="button" class="scroll-down" id="scrollDownBtn" aria-label="Scroll to blockchain">
                    <span class="scroll-down-label">Scroll</span>
                    <span class="scroll-down-mouse" aria-hidden="true">
                        <span class="scroll-down-wheel"></span>
                    </span>
                    <svg class="scroll-down-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </button>
            </div>

            {{-- Blockchain scene (GIF) --}}
            <div class="blockchain-scene glass-panel" id="blockchain" aria-label="Blockchain overview">
                <div class="scene-header">
                    <div class="scene-header-text">
                        <span class="scene-kicker">Live chain</span>
                        <strong>Office workflow blocks</strong>
                    </div>
                    <div class="chain-controls">
                        <button type="button" class="chain-nav" id="chainPrev" aria-label="Previous block">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <div class="chain-dots" id="chainDots" role="tablist" aria-label="Select block">
                            <button type="button" class="chain-dot is-active" data-block="0" role="tab" aria-label="Student Organization" aria-selected="true"></button>
                            <button type="button" class="chain-dot" data-block="1" role="tab" aria-label="Office of Student Organization" aria-selected="false"></button>
                            <button type="button" class="chain-dot" data-block="2" role="tab" aria-label="Sustainable Development Office" aria-selected="false"></button>
                            <button type="button" class="chain-dot" data-block="3" role="tab" aria-label="OVCAA" aria-selected="false"></button>
                        </div>
                        <button type="button" class="chain-nav" id="chainNext" aria-label="Next block">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                </div>

                <div class="blockchain-scene-body">
                    <div class="chain-info-slot" id="chainInfoSlot">
                        <p class="chain-front-label" id="chainFrontLabel">
                            <span class="chain-front-kicker">Now viewing</span>
                            <strong id="chainFrontName">Student Organization</strong>
                        </p>

                        <div class="block-detail is-open" id="blockDetail" aria-live="polite">
                            <div class="block-detail-top">
                                <span class="block-detail-badge">
                                    <svg class="ico" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                                    On-chain record
                                </span>
                                <div class="block-detail-hash" id="blockDetailHash">0x7a3f…b2c1 · genesis · student-org</div>
                            </div>
                            <h2 id="blockDetailTitle">Student Organization</h2>
                            <p id="blockDetailDesc">
                                Student organizations form the first block in OrgChain. Registered orgs create proposals, activity plans, and compliance submissions that kick off the on-chain workflow.
                            </p>
                            <ul class="block-detail-meta" id="blockDetailMeta">
                                <li><span>Role</span><span>Initiator</span></li>
                                <li><span>Chain position</span><span>Block #01</span></li>
                                <li><span>Actions</span><span>Submit · Update · Track</span></li>
                                <li><span>Status</span><span>Active on chain</span></li>
                            </ul>
                        </div>
                    </div>

                    <div class="scene-stage scene-stage-gif" id="sceneStage">
                        <img
                            src="{{ asset('Blockchain Blocks.gif') }}"
                            alt="Animated blockchain blocks representing the OrgChain office workflow"
                            class="blockchain-gif"
                            loading="eager"
                            decoding="async"
                        >
                    </div>
                </div>
            </div>
        </section>

        {{-- What is Blockchain? --}}
        <section class="section what-blockchain" id="what-is-blockchain">
            <div class="section-inner">
                <header class="section-header">
                    <p class="section-kicker">
                        <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        Learn the basics
                    </p>
                    <h2>What is a blockchain?</h2>
                    <p>
                        A blockchain is a shared digital ledger made of linked records called <strong>blocks</strong>.
                        Each block stores data and a fingerprint (hash) of the previous block — forming a chain that is hard to alter without detection.
                    </p>
                </header>

                <div class="bc-explain-grid">
                    <article class="bc-card">
                        <div class="bc-card-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        </div>
                        <h3>Blocks</h3>
                        <p>A block packages data: who did what, when, and a unique hash. In OrgChain, SO, OSO, SDO, and OVCAA are blocks on the chain.</p>
                    </article>
                    <article class="bc-card">
                        <div class="bc-card-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        </div>
                        <h3>The chain</h3>
                        <p>Blocks link in order. Each new block stores the previous hash — that is why gold chain links connect the cubes above.</p>
                    </article>
                    <article class="bc-card">
                        <div class="bc-card-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <h3>Immutable &amp; transparent</h3>
                        <p>Changing an old record would break every hash after it. Students and offices can trust one shared approval trail.</p>
                    </article>
                    <article class="bc-card">
                        <div class="bc-card-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        </div>
                        <h3>Why OrgChain</h3>
                        <p>Requests move SO → OSO → SDO → OVCAA. Linking keeps each step ordered, traceable, and auditable.</p>
                    </article>
                </div>

                <div class="bc-steps">
                    <div class="bc-steps-head">
                        <svg class="ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        <h3 class="bc-steps-title">How a request moves on OrgChain</h3>
                    </div>
                    <ol class="bc-steps-list">
                        <li>
                            <span class="bc-step-num">1</span>
                            <div>
                                <strong>Student Organization</strong>
                                <p>Creates a proposal or activity request — the genesis action on the chain.</p>
                            </div>
                        </li>
                        <li>
                            <span class="bc-step-num">2</span>
                            <div>
                                <strong>Office of Student Organization</strong>
                                <p>Reviews and endorses — a new block links to the org submission.</p>
                            </div>
                        </li>
                        <li>
                            <span class="bc-step-num">3</span>
                            <div>
                                <strong>Sustainable Development Office</strong>
                                <p>Checks sustainability alignment and seals the next record.</p>
                            </div>
                        </li>
                        <li>
                            <span class="bc-step-num">4</span>
                            <div>
                                <strong>OVCAA</strong>
                                <p>Final academic alignment, policy compliance, and approval review.</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>
        </section>

        {{-- Offices --}}
        <section class="section offices" id="offices">
            <div class="section-inner">
                <header class="section-header">
                    <p class="section-kicker">
                        <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        On the chain
                    </p>
                    <h2>Four offices. One immutable trail.</h2>
                    <p>Each block is a stakeholder in the student organization lifecycle at BatStateU.</p>
                </header>

                <div class="office-grid office-grid-4">
                    <article class="office-card" data-focus-block="0">
                        <div class="office-card-top">
                            <span class="office-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </span>
                            <div class="office-num">01</div>
                        </div>
                        <h3>Student Organization</h3>
                        <p>Registered orgs submit proposals, activities, and compliance documents as the first link.</p>
                        <button type="button" class="link-btn focus-block-btn" data-block="0">
                            View on chain
                            <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                        </button>
                    </article>
                    <article class="office-card" data-focus-block="1">
                        <div class="office-card-top">
                            <span class="office-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/></svg>
                            </span>
                            <div class="office-num">02</div>
                        </div>
                        <h3>Office of Student Organization</h3>
                        <p>OSO reviews, endorses, and validates organization requests with a clear audit trail.</p>
                        <button type="button" class="link-btn focus-block-btn" data-block="1">
                            View on chain
                            <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                        </button>
                    </article>
                    <article class="office-card" data-focus-block="2">
                        <div class="office-card-top">
                            <span class="office-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                            </span>
                            <div class="office-num">03</div>
                        </div>
                        <h3>Sustainable Development Office</h3>
                        <p>SDO aligns initiatives with campus sustainability goals and seals final acknowledgements.</p>
                        <button type="button" class="link-btn focus-block-btn" data-block="2">
                            View on chain
                            <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                        </button>
                    </article>
                    <article class="office-card" data-focus-block="3">
                        <div class="office-card-top">
                            <span class="office-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15l2 2 4-4"/></svg>
                            </span>
                            <div class="office-num">04</div>
                        </div>
                        <h3>OVCAA</h3>
                        <p>Office of the Vice Chancellor for Academic Affairs reviews academic and policy alignment.</p>
                        <button type="button" class="link-btn focus-block-btn" data-block="3">
                            View on chain
                            <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                        </button>
                    </article>
                </div>
            </div>
        </section>

        {{-- About --}}
        <section class="section about" id="about">
            <div class="section-inner about-grid">
                <div class="about-copy glass-panel">
                    <p class="section-kicker">
                        <svg class="ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg>
                        Why OrgChain
                    </p>
                    <h2>Built for Batangas State University</h2>
                    <p class="about-lead">
                        OrgChain modernizes how student organizations interact with university offices.
                        Every approval, revision, and acknowledgment is recorded in a linked block structure —
                        red and white, just like the BatStateU spirit.
                    </p>
                    <ul class="about-list">
                        <li>
                            <span class="about-check" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                            </span>
                            Immutable request history
                        </li>
                        <li>
                            <span class="about-check" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                            </span>
                            Role-based office workflow
                        </li>
                        <li>
                            <span class="about-check" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                            </span>
                            Student access via SR Code
                        </li>
                        <li>
                            <span class="about-check" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                            </span>
                            Mobile-ready experience
                        </li>
                    </ul>
                </div>
                <div class="about-panel">
                    <div class="stat">
                        <span class="stat-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        </span>
                        <strong>4</strong>
                        <span>Linked offices</span>
                    </div>
                    <div class="stat">
                        <span class="stat-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        </span>
                        <strong>1</strong>
                        <span>Shared chain of trust</span>
                    </div>
                    <div class="stat">
                        <span class="stat-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </span>
                        <strong>24/7</strong>
                        <span>Transparent records</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <img src="{{ asset('Orgchain logo.png') }}" alt="OrgChain Logo" class="footer-logo-img">
                <div>
                    <strong>OrgChain</strong>
                    <span>Batangas State University — The National Engineering University</span>
                </div>
            </div>
            <p class="footer-copy">&copy; {{ date('Y') }} OrgChain. All rights reserved.</p>
        </div>
    </footer>

    {{-- Student Login Modal --}}
    <div class="modal-overlay" id="loginModal" role="dialog" aria-modal="true" aria-labelledby="loginTitle" hidden>
        <div class="modal login-modal liquid-glass">
            <button type="button" class="modal-close" id="closeLoginBtn" aria-label="Close login">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>

            <div class="modal-header">
                <div class="login-brand">
                    <img src="{{ asset('Orgchain logo.png') }}" alt="" class="login-brand-logo" width="44" height="44">
                    <div class="modal-badge">
                        <svg class="ico" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Student Portal
                    </div>
                </div>
                <h2 id="loginTitle">Student Login</h2>
                <p>Sign in with your BatStateU institutional account, or request a verification code with your SR Code.</p>
            </div>

            <div class="login-auth-stack">
                <a href="{{ route('student.auth.google') }}" class="btn-google-bsu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        <path fill="none" d="M0 0h48v48H0z"/>
                    </svg>
                    <span>Continue with Institutional Account</span>
                </a>

                <div class="login-divider" role="separator" aria-label="Or">
                    <span>or</span>
                </div>

                @if (session('code_sent'))
                    <form class="login-form" id="studentVerifyForm" method="POST" action="{{ route('student.code.verify') }}">
                        @csrf
                        <input type="hidden" name="sr_code" value="{{ session('code_sr', old('sr_code')) }}">
                        <div class="form-field">
                            <label for="code">Verification Code</label>
                            <div class="input-wrap">
                                <svg class="input-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2l3 7h7l-5.5 4.5L18 22l-6-4-6 4 1.5-8.5L2 9h7z"/></svg>
                                <input
                                    type="text"
                                    id="code"
                                    name="code"
                                    placeholder="e.g. 123456"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    maxlength="6"
                                    pattern="[0-9]{6}"
                                    required
                                    autofocus
                                    value="{{ old('code') }}"
                                >
                            </div>
                            <span class="field-hint">Code sent to {{ session('code_email', 'your email') }} · expires in 10 min</span>
                        </div>

                        @if ($errors->any())
                            <div class="form-alert" role="alert">{{ $errors->first() }}</div>
                        @endif

                        <button type="submit" class="btn btn-primary btn-block btn-login-submit">
                            <svg class="ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>
                            Verify &amp; Log In
                        </button>

                        <div class="login-step-switch">
                            <button type="button" class="link-btn" id="backToSrCode">&larr; Use a different SR Code</button>
                            <button type="button" class="link-btn" id="resendCode">Resend code</button>
                        </div>
                    </form>
                @else
                    <form class="login-form" id="studentCodeForm" method="POST" action="{{ route('student.code.send') }}">
                        @csrf
                        <div class="form-field">
                            <label for="sr_code">SR Code</label>
                            <div class="input-wrap">
                                <svg class="input-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h4M7 12h10M7 16h6"/></svg>
                                <input
                                    type="text"
                                    id="sr_code"
                                    name="sr_code"
                                    placeholder="e.g. 21-12345"
                                    autocomplete="username"
                                    required
                                    pattern="[0-9]{2}-[0-9]{5}"
                                    title="Format: YY-XXXXX (e.g. 21-12345)"
                                    value="{{ old('sr_code') }}"
                                    autofocus
                                >
                            </div>
                            <span class="field-hint">We'll email a 6-digit code to your registered BatStateU email</span>
                        </div>

                        @if ($errors->any())
                            <div class="form-alert" role="alert">{{ $errors->first() }}</div>
                        @endif

                        @if (session('status'))
                            <div class="form-alert form-alert-info" role="status">{{ session('status') }}</div>
                        @endif

                        <button type="submit" class="btn btn-primary btn-block btn-login-submit">
                            <svg class="ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                            Send Verification Code
                        </button>
                    </form>
                @endif

                <p class="login-google-note">Institutional sign-in uses your @g.batstate-u.edu.ph Google Workspace account.</p>
            </div>
        </div>
    </div>


    @if (!file_exists(public_path('build/manifest.json')) && !file_exists(public_path('hot')))
        <script src="{{ asset('js/landing.js') }}"></script>
    @endif

    <script>
        @if ($errors->any() || request()->boolean('login') || session('status') || session('login') || session('code_sent'))
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('openLoginBtn')?.click();
            });
        @endif

        // Login modal — step switching helpers
        document.addEventListener('DOMContentLoaded', function () {
            // "Use a different SR Code" — reload the page without the code_sent session flag.
            document.getElementById('backToSrCode')?.addEventListener('click', function () {
                window.location.href = '{{ url("/?login=1") }}';
            });

            // "Resend code" — re-submit the send-code form for the same SR Code.
            document.getElementById('resendCode')?.addEventListener('click', function () {
                const form = document.getElementById('studentVerifyForm');
                if (!form) return;
                const sr = form.querySelector('input[name="sr_code"]')?.value;
                if (!sr) return;

                const resendForm = document.createElement('form');
                resendForm.method = 'POST';
                resendForm.action = '{{ route("student.code.send") }}';

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                if (csrf) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrf;
                    resendForm.appendChild(csrfInput);
                }

                const srInput = document.createElement('input');
                srInput.type = 'hidden';
                srInput.name = 'sr_code';
                srInput.value = sr;
                resendForm.appendChild(srInput);

                document.body.appendChild(resendForm);
                resendForm.submit();
            });
        });
    </script>
</body>
</html>
