<?php
/**
 * Technical details of one exchange: every model call and every tool call.
 *
 * @var \App\View\AppView $this
 * @var array<string, mixed> $exchange
 */
?>
<div class="small">
    <h2 class="h6"><?= __('Model calls') ?></h2>
    <div class="table-responsive">
        <table class="table table-sm table-bordered font-monospace mb-3">
            <thead>
                <tr>
                    <th>#</th><th>stop_reason</th><th>model</th><th>in</th><th>out</th>
                    <th>cache write</th><th>cache read</th><th>cost</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exchange['calls'] as $call) : ?>
                    <tr>
                        <td><?= $call['iteration'] ?></td>
                        <td><?= h($call['stopReason'] ?? '-') ?></td>
                        <td><?= h($call['model'] ?? '-') ?></td>
                        <td><?= $this->Number->format($call['input']) ?></td>
                        <td><?= $this->Number->format($call['output']) ?></td>
                        <td><?= $this->Number->format($call['cacheWrite']) ?></td>
                        <td><?= $this->Number->format($call['cacheRead']) ?></td>
                        <td>$<?= $this->Number->format($call['cost'], ['places' => 6, 'precision' => 6]) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($exchange['calls'] === []) : ?>
                    <tr><td colspan="8"><?= __('No model response stored.') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($exchange['tools'] !== []) : ?>
        <h2 class="h6"><?= __('Tool calls') ?></h2>
        <?php foreach ($exchange['tools'] as $tool) : ?>
            <div class="border rounded p-2 mb-2">
                <div class="font-monospace mb-1">
                    <strong><?= h($tool['name']) ?></strong>
                    · rows <?= $tool['rows'] ?>
                    · truncated <?= $tool['truncated'] ? 'true' : 'false' ?>
                    · is_error <?= $tool['isError'] ? 'true' : 'false' ?>
                    · <?= $tool['durationMs'] ?> ms
                </div>
                <div class="text-muted"><?= __('Input') ?></div>
                <pre class="chat-json bg-body-tertiary p-2 mb-1"><?= h((string)json_encode($tool['input'] ?: new \stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                <div class="text-muted"><?= __('Result sent to the model') ?></div>
                <pre class="chat-json bg-body-tertiary p-2 mb-0"><?= h($tool['result'] ?? '-') ?></pre>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
