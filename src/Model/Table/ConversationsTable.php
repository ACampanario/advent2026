<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Conversation;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Conversations Model
 *
 * @property \App\Model\Table\MessagesTable&\Cake\ORM\Association\HasMany $Messages
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\Conversation newEmptyEntity()
 * @method \App\Model\Entity\Conversation newEntity(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Conversation> newEntities(array<array<string, mixed>> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Conversation get(mixed $primaryKey, array<string, mixed>|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Conversation findOrCreate($search, ?callable $callback = null, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Conversation patchEntity(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Conversation> patchEntities(iterable<\App\Model\Entity\Conversation> $entities, array<array<string, mixed>> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Conversation|false save(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Conversation saveOrFail(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Conversation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Conversation>|false saveMany(iterable<\App\Model\Entity\Conversation> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Conversation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Conversation> saveManyOrFail(iterable<\App\Model\Entity\Conversation> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Conversation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Conversation>|false deleteMany(iterable<\App\Model\Entity\Conversation> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Conversation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Conversation> deleteManyOrFail(iterable<\App\Model\Entity\Conversation> $entities, array<string, mixed> $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ConversationsTable extends Table
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

        $this->setTable('conversations');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Messages', [
            'foreignKey' => 'conversation_id',
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
            ->integer('user_id')
            ->notEmptyString('user_id');

        $validator
            ->scalar('title')
            ->maxLength('title', 120)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * Starts a conversation for a user. The title is the start of the first message (no LLM call).
     *
     * @param int $userId Owner.
     * @param string $firstMessage First user message.
     * @return \App\Model\Entity\Conversation
     */
    public function startFor(int $userId, string $firstMessage): Conversation
    {
        $title = mb_substr(trim((string)preg_replace('/\s+/', ' ', $firstMessage)), 0, 60);
        $conversation = $this->newEntity(['user_id' => $userId, 'title' => $title !== '' ? $title : 'New chat']);

        return $this->saveOrFail($conversation);
    }

    /**
     * Conversation list for the sidebar: title, dates, total tokens and cost, latest activity first.
     *
     * The authorization scope (own conversations only) is applied by the caller.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Conversation> $query Query to modify.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Conversation>
     */
    public function findSidebar(SelectQuery $query): SelectQuery
    {
        $func = $query->func();
        $tokens = $query->expr(
            'Messages.input_tokens + Messages.output_tokens'
            . ' + Messages.cache_creation_input_tokens + Messages.cache_read_input_tokens',
        );

        return $query
            ->select([
                'Conversations.id',
                'Conversations.title',
                'Conversations.created',
                'total_tokens' => $func->coalesce([$func->sum($tokens), 0]),
                'total_cost' => $func->coalesce([$func->sum('Messages.cost_usd'), 0]),
                'last_activity' => $func->coalesce([
                    $func->max('Messages.created'),
                    'Conversations.created' => 'identifier',
                ]),
            ])
            ->leftJoinWith('Messages')
            ->groupBy(['Conversations.id'])
            ->orderBy(['last_activity' => 'DESC', 'Conversations.id' => 'DESC']);
    }
}
