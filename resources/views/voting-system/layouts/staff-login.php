<!doctype html>
<html lang="en" class="staff-login-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title><?= e(($title ?? 'Staff Login') . ' | OrgChain Voting') ?></title>
    <link rel="icon" type="image/png" sizes="256x256" href="<?= e(voting_asset('img/ssc-favicon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(voting_asset('img/ssc-favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(voting_asset('css/staff-login.css')) ?>" rel="stylesheet">
    <style>
        /* Hard lock: never inherit warm/cream/orange theme from elsewhere */
        html.staff-login-html,
        html.staff-login-html body {
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100% !important;
            background: #eef1f3 !important;
            background-image: none !important;
            color: #1f1817 !important;
            font-family: 'Instrument Sans', system-ui, -apple-system, sans-serif !important;
        }
    </style>
</head>
<body class="staff-login-body">
    <?= $content ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
