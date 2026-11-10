<?php
declare(strict_types=1);

namespace App\Service\Ai\Tool;

use App\Model\Table\OrdersTable;
use Authorization\IdentityInterface;

/**
 * find_orders: list the orders visible to the user, newest first.
 */
class FindOrdersTool extends AbstractTool
{
    /**
     * Allowed order statuses.
     *
     * @var array<string>
     */
    public const STATUSES = ['pending', 'paid', 'shipped', 'cancelled'];

    /**
     * @param \App\Model\Table\OrdersTable $orders Orders table.
     * @param int $maxRows Row cap.
     */
    public function __construct(private OrdersTable $orders, int $maxRows)
    {
        parent::__construct($maxRows);
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'find_orders';
    }

    /**
     * @inheritDoc
     */
    public function description(): string
    {
        return 'List orders, newest first, optionally filtered by customer, status and date range (dates included). '
            . 'Returns id, reference, customer_name, status, ordered_at, total. Max 20 rows.';
    }

    /**
     * @inheritDoc
     */
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'integer', 'description' => 'Customer id.'],
                'status' => ['type' => 'string', 'enum' => self::STATUSES],
                'date_from' => ['type' => 'string', 'description' => 'First day included, YYYY-MM-DD.'],
                'date_to' => ['type' => 'string', 'description' => 'Last day included, YYYY-MM-DD.'],
            ],
            'required' => [],
            'additionalProperties' => false,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function run(array $input, IdentityInterface $identity): ToolResult
    {
        $customerId = $this->positiveInt($input, 'customer_id');
        $status = $this->enum($input, 'status', self::STATUSES);
        $dateFrom = $this->date($input, 'date_from');
        $dateTo = $this->date($input, 'date_to');
        $this->assertRange($dateFrom, $dateTo);

        /** @see \App\Model\Table\OrdersTable::findForAgent() */
        $query = $this->orders->find(
            'forAgent',
            customerId: $customerId,
            status: $status,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );
        $query = $identity->applyScope('index', $query);

        return $this->cappedResult($query, 'orders');
    }
}
