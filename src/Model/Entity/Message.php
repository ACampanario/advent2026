<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Message Entity
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $role
 * @property array $content
 * @property string|null $stop_reason
 * @property string|null $model
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $cache_creation_input_tokens
 * @property int $cache_read_input_tokens
 * @property string $cost_usd
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Conversation $conversation
 * @property \App\Model\Entity\ToolCall[] $tool_calls
 */
class Message extends Entity
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
        'conversation_id' => true,
        'role' => true,
        'content' => true,
        'stop_reason' => true,
        'model' => true,
        'input_tokens' => true,
        'output_tokens' => true,
        'cache_creation_input_tokens' => true,
        'cache_read_input_tokens' => true,
        'cost_usd' => true,
        'created' => true,
        'conversation' => true,
        'tool_calls' => true,
    ];
}
