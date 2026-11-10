<?php
/**
 * One question + answer, with usage and technical details.
 *
 * @var \App\View\AppView $this
 * @var array<string, mixed> $exchange
 * @var int $index
 */
$detailsId = 'details-' . $index;
?>
<div class="d-flex justify-content-end mb-2">
    <div class="chat-bubble chat-bubble-user bg-primary text-white rounded-3 px-3 py-2"><?= nl2br(h($exchange['question'])) ?></div>
</div>

<div class="d-flex justify-content-start mb-4">
    <div class="chat-bubble chat-bubble-assistant bg-white border rounded-3 px-3 py-2">
        <?php if ($exchange['answerHtml'] !== null) : ?>
            <div class="chat-answer"><?= $exchange['answerHtml'] /* sanitized by MarkdownRenderer */ ?></div>
        <?php endif; ?>

        <?php if ($exchange['notice'] !== null) : ?>
            <div class="alert alert-warning py-1 px-2 small mb-2"><?= h($exchange['notice']) ?></div>
        <?php endif; ?>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <?= $this->element('conversations/usage', ['usage' => $exchange['usage'], 'prefix' => null]) ?>
            <button class="btn btn-link btn-sm p-0" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $detailsId ?>">
                <?= __('Details') ?>
            </button>
        </div>

        <div class="collapse mt-2" id="<?= $detailsId ?>">
            <?= $this->element('conversations/details', ['exchange' => $exchange]) ?>
        </div>
    </div>
</div>
