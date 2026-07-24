<?php
    $messages = [
        'success' => ['class' => 'success', 'icon' => 'check-circle'],
        'error' => ['class' => 'danger', 'icon' => 'exclamation-triangle'],
        'warning' => ['class' => 'warning', 'icon' => 'exclamation-circle'],
    ];
?>

<?php foreach ($messages as $key => $meta): ?>
    <?php if ($message = voting_flash($key)): ?>
        <div class="container flash-container">
            <div class="alert alert-<?= e($meta['class']) ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= e($meta['icon']) ?>"></i>
                <?= e($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
