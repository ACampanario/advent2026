<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ToolCall Entity
 *
 * @property int $id
 * @property int $message_id
 * @property string $tool_use_id
 * @property string $name
 * @property array<string, mixed> $input
 * @property int $row_count
 * @property bool $truncated
 * @property bool $is_error
 * @property int $duration_ms
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Message $message
 */
class ToolCall extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'message_id' => true,
        'tool_use_id' => true,
        'name' => true,
        'input' => true,
        'row_count' => true,
        'truncated' => true,
        'is_error' => true,
        'duration_ms' => true,
        'created' => true,
        'message' => true,
    ];
}
