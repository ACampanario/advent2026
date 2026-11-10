<?php
/**
 * Flash message rendered as a dismissible Bootstrap alert (warning).
 *
 * @var \App\View\AppView $this
 * @var array<string, mixed> $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= __('Close') ?>"></button>
</div>
