<?php
declare(strict_types=1);

namespace App\Service\Ai\Tool;

use App\Model\Table\CustomersTable;
use Authorization\IdentityInterface;
use Cake\Datasource\EntityInterface;

/**
 * get_customer: one customer with order statistics.
 */
class GetCustomerTool extends AbstractTool
{
    /**
     * @param \App\Model\Table\CustomersTable $customers Customers table.
     * @param int $maxRows Row cap.
     */
    public function __construct(private CustomersTable $customers, int $maxRows)
    {
        parent::__construct($maxRows);
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'get_customer';
    }

    /**
     * @inheritDoc
     */
    public function description(): string
    {
        return 'Get one customer by id, with orders_count and total_spent (cancelled orders excluded). '
            . 'Returns {"found": false} if the customer does not exist or is not visible.';
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
            ],
            'required' => ['customer_id'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function run(array $input, IdentityInterface $identity): ToolResult
    {
        $customerId = $this->positiveInt($input, 'customer_id', true);

        /** @see \App\Model\Table\CustomersTable::findForAgent() */
        $query = $this->customers->find('forAgent', customerId: $customerId, withStats: true);
        $customer = $identity->applyScope('index', $query)->first();

        if (!$customer instanceof EntityInterface) {
            return ToolResult::notFound();
        }

        return ToolResult::ok(['customer' => $customer->toArray()], 1);
    }
}
