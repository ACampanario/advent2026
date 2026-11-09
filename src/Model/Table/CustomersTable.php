<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Customers Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\OrdersTable&\Cake\ORM\Association\HasMany $Orders
 * @method \App\Model\Entity\Customer newEmptyEntity()
 * @method \App\Model\Entity\Customer newEntity(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Customer> newEntities(array<array<string, mixed>> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Customer get(mixed $primaryKey, array<string, mixed>|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Customer findOrCreate($search, ?callable $callback = null, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Customer patchEntity(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Customer> patchEntities(iterable<\App\Model\Entity\Customer> $entities, array<array<string, mixed>> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Customer|false save(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Customer saveOrFail(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Customer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Customer>|false saveMany(iterable<\App\Model\Entity\Customer> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Customer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Customer> saveManyOrFail(iterable<\App\Model\Entity\Customer> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Customer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Customer>|false deleteMany(iterable<\App\Model\Entity\Customer> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Customer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Customer> deleteManyOrFail(iterable<\App\Model\Entity\Customer> $entities, array<string, mixed> $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CustomersTable extends Table
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

        $this->setTable('customers');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Orders', [
            'foreignKey' => 'customer_id',
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
            ->scalar('name')
            ->maxLength('name', 150)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('company')
            ->maxLength('company', 150)
            ->requirePresence('company', 'create')
            ->notEmptyString('company');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('city')
            ->maxLength('city', 100)
            ->requirePresence('city', 'create')
            ->notEmptyString('city');

        $validator
            ->scalar('country')
            ->maxLength('country', 2)
            ->requirePresence('country', 'create')
            ->notEmptyString('country');

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
     * Customers assigned to a sales rep. Used by the authorization scope.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Customer> $query Query to modify.
     * @param int $userId Sales rep (users.id).
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Customer>
     */
    public function findOwnedBy(SelectQuery $query, int $userId): SelectQuery
    {
        return $query->where(['Customers.user_id' => $userId]);
    }

    /**
     * Customer data exposed to the AI agent (find_customers / get_customer tools).
     *
     * The authorization scope is NOT applied here; the calling tool applies it.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Customer> $query Query to modify.
     * @param string|null $search Case-insensitive "contains" on name, company or email.
     * @param string|null $country ISO 3166-1 alpha-2 country code.
     * @param int|null $customerId Single customer id.
     * @param bool $withStats Add `orders_count` and `total_spent` (non-cancelled orders).
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Customer>
     */
    public function findForAgent(
        SelectQuery $query,
        ?string $search = null,
        ?string $country = null,
        ?int $customerId = null,
        bool $withStats = false,
    ): SelectQuery {
        $query
            ->select([
                'Customers.id',
                'Customers.name',
                'Customers.company',
                'Customers.email',
                'Customers.city',
                'Customers.country',
            ])
            ->orderBy(['Customers.name' => 'ASC']);

        if ($search !== null && $search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(['OR' => [
                'Customers.name ILIKE' => $like,
                'Customers.company ILIKE' => $like,
                'Customers.email ILIKE' => $like,
            ]]);
        }
        if ($country !== null) {
            $query->where(['Customers.country' => strtoupper($country)]);
        }
        if ($customerId !== null) {
            $query->where(['Customers.id' => $customerId]);
        }
        if ($withStats) {
            $query
                ->leftJoinWith('Orders', function (SelectQuery $orders): SelectQuery {
                    return $orders->where(['Orders.status !=' => 'cancelled']);
                })
                ->select([
                    'orders_count' => $query->func()->count('Orders.id'),
                    'total_spent' => $query->func()->coalesce([$query->func()->sum('Orders.total'), 0]),
                ])
                ->groupBy(['Customers.id']);
        }

        return $query;
    }
}
