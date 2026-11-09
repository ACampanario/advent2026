<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use InvalidArgumentException;

/**
 * Orders Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\OrderItemsTable&\Cake\ORM\Association\HasMany $OrderItems
 * @method \App\Model\Entity\Order newEmptyEntity()
 * @method \App\Model\Entity\Order newEntity(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Order> newEntities(array<array<string, mixed>> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Order get(mixed $primaryKey, array<string, mixed>|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Order findOrCreate($search, ?callable $callback = null, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Order patchEntity(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Order> patchEntities(iterable<\App\Model\Entity\Order> $entities, array<array<string, mixed>> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Order|false save(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Order saveOrFail(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Order>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Order>|false saveMany(iterable<\App\Model\Entity\Order> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Order>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Order> saveManyOrFail(iterable<\App\Model\Entity\Order> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Order>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Order>|false deleteMany(iterable<\App\Model\Entity\Order> $entities, array<string, mixed> $options = [])
 * @method iterable<\App\Model\Entity\Order>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Order> deleteManyOrFail(iterable<\App\Model\Entity\Order> $entities, array<string, mixed> $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class OrdersTable extends Table
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

        $this->setTable('orders');
        $this->setDisplayField('reference');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('OrderItems', [
            'foreignKey' => 'order_id',
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
            ->integer('customer_id')
            ->notEmptyString('customer_id');

        $validator
            ->scalar('reference')
            ->maxLength('reference', 20)
            ->requirePresence('reference', 'create')
            ->notEmptyString('reference')
            ->add('reference', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->dateTime('ordered_at')
            ->requirePresence('ordered_at', 'create')
            ->notEmptyDateTime('ordered_at');

        $validator
            ->decimal('total')
            ->requirePresence('total', 'create')
            ->notEmptyString('total');

        $validator
            ->scalar('notes')
            ->allowEmptyString('notes');

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
        $rules->add($rules->isUnique(['reference']), ['errorField' => 'reference']);
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);

        return $rules;
    }

    /**
     * Order data exposed to the AI agent (find_orders / get_order tools), newest first.
     *
     * The authorization scope is NOT applied here; the calling tool applies it.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Order> $query Query to modify.
     * @param int|null $customerId Filter by customer.
     * @param string|null $status Filter by status.
     * @param \Cake\I18n\Date|null $dateFrom First day included (UTC).
     * @param \Cake\I18n\Date|null $dateTo Last day included (UTC).
     * @param int|null $orderId Single order by id.
     * @param string|null $reference Single order by reference.
     * @param bool $withItems Add `notes` and the order lines with their products.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Order>
     */
    public function findForAgent(
        SelectQuery $query,
        ?int $customerId = null,
        ?string $status = null,
        ?Date $dateFrom = null,
        ?Date $dateTo = null,
        ?int $orderId = null,
        ?string $reference = null,
        bool $withItems = false,
    ): SelectQuery {
        $query
            ->select([
                'Orders.id',
                'Orders.reference',
                'customer_name' => 'Customers.name',
                'Orders.status',
                'Orders.ordered_at',
                'Orders.total',
            ])
            ->innerJoinWith('Customers')
            ->orderBy(['Orders.ordered_at' => 'DESC', 'Orders.id' => 'DESC']);

        if ($customerId !== null) {
            $query->where(['Orders.customer_id' => $customerId]);
        }
        if ($status !== null) {
            $query->where(['Orders.status' => $status]);
        }
        $this->whereOrderedBetween($query, $dateFrom, $dateTo);
        if ($orderId !== null) {
            $query->where(['Orders.id' => $orderId]);
        }
        if ($reference !== null) {
            $query->where(['Orders.reference' => strtoupper($reference)]);
        }
        if ($withItems) {
            $query
                ->select(['Orders.notes'])
                ->contain(['OrderItems' => function (SelectQuery $items): SelectQuery {
                    return $items
                        ->select([
                            'OrderItems.order_id',
                            'OrderItems.quantity',
                            'OrderItems.unit_price',
                            'OrderItems.line_total',
                            'Products.sku',
                            'Products.name',
                        ])
                        ->contain(['Products'])
                        ->orderBy(['OrderItems.id' => 'ASC']);
                }]);
        }

        return $query;
    }

    /**
     * Sales aggregated by month, customer, product or category (sales_summary tool).
     *
     * Rows: `group_label` ("group" is a reserved SQL word), `orders` (distinct orders), `revenue`. Cancelled orders are excluded.
     * Month and customer use `orders.total`; product and category use `order_items.line_total`.
     * The authorization scope is NOT applied here; the calling tool applies it.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Order> $query Query to modify.
     * @param \Cake\I18n\Date $dateFrom First day included (UTC).
     * @param \Cake\I18n\Date $dateTo Last day included (UTC).
     * @param string $dimension Grouping (named so because `groupBy` is a reserved query option): one of `month`, `customer`, `product`, `category`.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Order>
     */
    public function findSalesSummary(SelectQuery $query, Date $dateFrom, Date $dateTo, string $dimension): SelectQuery
    {
        $func = $query->func();
        $query
            ->where(['Orders.status !=' => 'cancelled'])
            ->disableHydration();
        $this->whereOrderedBetween($query, $dateFrom, $dateTo);

        switch ($dimension) {
            case 'month':
                $month = $func->to_char(['Orders.ordered_at' => 'identifier', "'YYYY-MM'" => 'literal']);

                return $query
                    ->select([
                        'group_label' => $month,
                        'orders' => $func->count('Orders.id'),
                        'revenue' => $func->sum('Orders.total'),
                    ])
                    ->groupBy([$month])
                    ->orderBy(['group_label' => 'ASC']);
            case 'customer':
                return $query
                    ->innerJoinWith('Customers')
                    ->select([
                        'group_label' => 'Customers.name',
                        'orders' => $func->count('Orders.id'),
                        'revenue' => $func->sum('Orders.total'),
                    ])
                    ->groupBy(['Customers.id', 'Customers.name'])
                    ->orderBy(['revenue' => 'DESC']);
            case 'product':
            case 'category':
                $group = $dimension === 'product' ? 'Products.name' : 'Products.category';

                return $query
                    ->innerJoinWith('OrderItems.Products')
                    ->select([
                        'group_label' => $group,
                        'orders' => $func->count('DISTINCT Orders.id'),
                        'revenue' => $func->sum('OrderItems.line_total'),
                    ])
                    ->groupBy($dimension === 'product' ? ['Products.id', 'Products.name'] : ['Products.category'])
                    ->orderBy(['revenue' => 'DESC']);
        }

        throw new InvalidArgumentException(sprintf('Unsupported dimension "%s".', $dimension));
    }

    /**
     * Limits a query to orders placed between two days, both included (UTC).
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Order> $query Query to modify.
     * @param \Cake\I18n\Date|null $dateFrom First day included.
     * @param \Cake\I18n\Date|null $dateTo Last day included.
     * @return void
     */
    protected function whereOrderedBetween(SelectQuery $query, ?Date $dateFrom, ?Date $dateTo): void
    {
        if ($dateFrom !== null) {
            $query->where(['Orders.ordered_at >=' => $dateFrom->format('Y-m-d') . ' 00:00:00']);
        }
        if ($dateTo !== null) {
            $query->where(['Orders.ordered_at <' => $dateTo->addDays(1)->format('Y-m-d') . ' 00:00:00']);
        }
    }
}
