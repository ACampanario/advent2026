<?php
/**
 * Message form. Disabled with "Thinking…" while the agent runs (webroot/js/chat.js).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Conversation|null $conversation
 */
?>
<?= $this->Form->create(null, [
    'url' => ['controller' => 'Conversations', 'action' => 'message'],
    'class' => 'chat-composer',
    'data-chat-form' => 'true',
]) ?>
<?= $this->Form->hidden('conversation_id', ['value' => $conversation?->id]) ?>
<div class="input-group">
    <?= $this->Form->textarea('message', [
        'class' => 'form-control',
        'rows' => 2,
        'maxlength' => \App\Controller\ConversationsController::MAX_MESSAGE_LENGTH,
        'placeholder' => __('Ask the shop assistant…'),
        'required' => true,
        'label' => false,
        'data-chat-input' => 'true',
    ]) ?>
    <button type="submit" class="btn btn-primary" data-chat-submit="true" data-busy-text="<?= h(__('Thinking…')) ?>">
        <?= __('Send') ?>
    </button>
</div>
<?= $this->Form->end() ?>
