<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Messages Model
 *
 * @property \App\Model\Table\ConversationsTable&\Cake\ORM\Association\BelongsTo $Conversations
 * @property \App\Model\Table\ToolCallsTable&\Cake\ORM\Association\HasMany $ToolCalls
 * @method \App\Model\Entity\Message newEmptyEntity()
 * @method \App\Model\Entity\Message newEntity(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Message> newEntities(array<array<string, mixed>> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Message get(mixed $primaryKey, array<string, mixed>|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Message findOrCreate($search, ?callable $callback = null, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Message patchEntity(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Message> patchEntities(iterable<\App\Model\Entity\Message> $entities, array<array<string, mixed>> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Message|false save(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Message saveOrFail(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Message>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Message>|false saveMany(iterable<\App\Model\Entity\Message> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Message>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Message> saveManyOrFail(iterable<\App\Model\Entity\Message> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Message>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Message>|false deleteMany(iterable<\App\Model\Entity\Message> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Message>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Message> deleteManyOrFail(iterable<\App\Model\Entity\Message> $entities, array<string, mixed> $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MessagesTable extends Table
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

        $this->setTable('messages');
        $this->setDisplayField('role');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Conversations', [
            'foreignKey' => 'conversation_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('ToolCalls', [
            'foreignKey' => 'message_id',
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
            ->integer('conversation_id')
            ->notEmptyString('conversation_id');

        $validator
            ->scalar('role')
            ->maxLength('role', 10)
            ->requirePresence('role', 'create')
            ->notEmptyString('role');

        $validator
            ->requirePresence('content', 'create')
            ->notEmptyString('content');

        $validator
            ->scalar('stop_reason')
            ->maxLength('stop_reason', 30)
            ->allowEmptyString('stop_reason');

        $validator
            ->scalar('model')
            ->maxLength('model', 60)
            ->allowEmptyString('model');

        $validator
            ->integer('input_tokens')
            ->notEmptyString('input_tokens');

        $validator
            ->integer('output_tokens')
            ->notEmptyString('output_tokens');

        $validator
            ->integer('cache_creation_input_tokens')
            ->notEmptyString('cache_creation_input_tokens');

        $validator
            ->integer('cache_read_input_tokens')
            ->notEmptyString('cache_read_input_tokens');

        $validator
            ->decimal('cost_usd')
            ->notEmptyString('cost_usd');

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
        $rules->add($rules->existsIn(['conversation_id'], 'Conversations'), ['errorField' => 'conversation_id']);

        return $rules;
    }

    /**
     * Stored turns of a conversation, oldest first, as sent to the Messages API.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Message> $query Query to modify.
     * @param int $conversationId Conversation id.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Message>
     */
    public function findHistory(SelectQuery $query, int $conversationId): SelectQuery
    {
        return $query
            ->select(['Messages.id', 'Messages.role', 'Messages.content'])
            ->where(['Messages.conversation_id' => $conversationId])
            ->orderBy(['Messages.id' => 'ASC']);
    }

    /**
     * Appends turns to a conversation in one transaction (history is append-only).
     *
     * Used to store an assistant `tool_use` turn together with the user `tool_result`
     * turn that answers it, so the stored history is always valid. Each turn may carry
     * its `tool_calls` rows.
     *
     * @param int $conversationId Conversation id.
     * @param array<array<string, mixed>> $turns Message data, in order.
     * @return array<\App\Model\Entity\Message>
     */
    public function appendTurns(int $conversationId, array $turns): array
    {
        return $this->getConnection()->transactional(function () use ($conversationId, $turns): array {
            $saved = [];
            foreach ($turns as $turn) {
                $message = $this->newEntity(
                    ['conversation_id' => $conversationId] + $turn,
                    ['associated' => ['ToolCalls']],
                );
                $saved[] = $this->saveOrFail($message, ['associated' => ['ToolCalls']]);
            }

            return $saved;
        });
    }

    /**
     * Messages of a conversation with their tool calls, oldest first (chat view).
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Message> $query Query to modify.
     * @param int $conversationId Conversation id.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Message>
     */
    public function findForDisplay(SelectQuery $query, int $conversationId): SelectQuery
    {
        return $query
            ->where(['Messages.conversation_id' => $conversationId])
            ->contain(['ToolCalls' => function (SelectQuery $toolCalls): SelectQuery {
                return $toolCalls->orderBy(['ToolCalls.id' => 'ASC']);
            }])
            ->orderBy(['Messages.id' => 'ASC']);
    }
}
