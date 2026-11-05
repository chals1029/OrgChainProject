<!doctype html>
<html lang="en" class="staff-login-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title><?= e(($title ?? 'Staff Login') . ' | OrgChain Voting') ?></title>
    <link rel="icon" type="image/png" sizes="256x256" href="<?= e(voting_asset('img/ssc-favicon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(voting_asset('img/ssc-favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300..900&family=Public+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(voting_asset('css/staff-login.css')) ?>" rel="stylesheet">
</head>
<body class="staff-login-body">
    <?= $content ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
