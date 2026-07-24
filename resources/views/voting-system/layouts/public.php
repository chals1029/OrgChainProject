<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Voting') . ' | OrgChain') ?></title>
    <link rel="icon" type="image/png" sizes="256x256" href="<?= e(voting_asset('img/ssc-favicon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(voting_asset('img/ssc-favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(voting_asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg site-navbar">
        <div class="container">
            <a class="navbar-brand brand-lockup" href="<?= e(voting_url('/')) ?>">
                <span class="brand-mark">
                    <img src="<?= e(voting_asset('img/orgchain-logo.png')) ?>" alt="OrgChain" onerror="this.src='<?= e(voting_asset('img/orgchain-logo.png')) ?>'">
                </span>
                <span class="brand-text-stack">
                    <span class="brand-title">OrgChain</span>
                    <span class="brand-sub d-none d-sm-inline">Official Voting System</span>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="publicNav">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <a class="nav-link" href="<?= e(voting_url('/')) ?>">Vote</a>
                    <a class="nav-link orgchain-home-link" href="/">Back to OrgChain</a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>OrgChain Official Voting</span>
            <span>Batangas State University - OrgChain official voting system</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(voting_asset('js/app.js')) ?>"></script>
</body>
</html>
