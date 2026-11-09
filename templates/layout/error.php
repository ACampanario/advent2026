<?php
/**
 * Error layout (production errors): Bootstrap 5 from the jsDelivr CDN.
 *
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Shop Assistant') ?><?= $this->fetch('title') ? ' · ' . h($this->fetch('title')) : '' ?></title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css('https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css', [
        'integrity' => 'sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB',
        'crossorigin' => 'anonymous',
    ]) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="card">
            <div class="card-body">
                <?= $this->Flash->render() ?>
                <?= $this->fetch('content') ?>
                <?= $this->Html->link(__('Back to start'), '/', ['class' => 'btn btn-outline-secondary mt-3']) ?>
            </div>
        </div>
    </main>
</body>
</html>
