<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ToolCalls Model
 *
 * @property \App\Model\Table\MessagesTable&\Cake\ORM\Association\BelongsTo $Messages
 * @method \App\Model\Entity\ToolCall newEmptyEntity()
 * @method \App\Model\Entity\ToolCall newEntity(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\ToolCall> newEntities(array<array<string, mixed>> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\ToolCall get(mixed $primaryKey, array<string, mixed>|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ToolCall findOrCreate($search, ?callable $callback = null, array<string, mixed> $options = [])
 * @method \App\Model\Entity\ToolCall patchEntity(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\ToolCall> patchEntities(iterable<\App\Model\Entity\ToolCall> $entities, array<array<string, mixed>> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\ToolCall|false save(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method \App\Model\Entity\ToolCall saveOrFail(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\ToolCall>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ToolCall>|false saveMany(iterable<\App\Model\Entity\ToolCall> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\ToolCall>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ToolCall> saveManyOrFail(iterable<\App\Model\Entity\ToolCall> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\ToolCall>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ToolCall>|false deleteMany(iterable<\App\Model\Entity\ToolCall> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\ToolCall>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ToolCall> deleteManyOrFail(iterable<\App\Model\Entity\ToolCall> $entities, array<string, mixed> $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ToolCallsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('tool_calls');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Messages', [
            'foreignKey' => 'message_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('message_id')
            ->notEmptyString('message_id');

        $validator
            ->scalar('tool_use_id')
            ->maxLength('tool_use_id', 100)
            ->requirePresence('tool_use_id', 'create')
            ->notEmptyString('tool_use_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 60)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->requirePresence('input', 'create')
            ->array('input');

        $validator
            ->integer('row_count')
            ->notEmptyString('row_count');

        $validator
            ->boolean('truncated')
            ->notEmptyString('truncated');

        $validator
            ->boolean('is_error')
            ->notEmptyString('is_error');

        $validator
            ->integer('duration_ms')
            ->notEmptyString('duration_ms');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['message_id'], 'Messages'), ['errorField' => 'message_id']);

        return $rules;
    }
}
