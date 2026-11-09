<?php
/**
 * Usage line, e.g. `in 1,234 · out 210 · cache write 0 · cache read 980 · $0.00021`.
 *
 * @var \App\View\AppView $this
 * @var array<string, int|float> $usage
 * @var string|null $prefix
 */
?>
<small class="text-muted font-monospace">
    <?= h($prefix ?? '') ?>in <?= $this->Number->format($usage['input']) ?>
    · out <?= $this->Number->format($usage['output']) ?>
    · cache write <?= $this->Number->format($usage['cacheWrite']) ?>
    · cache read <?= $this->Number->format($usage['cacheRead']) ?>
    · $<?= $this->Number->format($usage['cost'], ['places' => 5, 'precision' => 5]) ?>
</small>
