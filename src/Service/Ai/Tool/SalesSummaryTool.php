<?php
declare(strict_types=1);

namespace App\Service\Ai\Tool;

use App\Model\Table\OrdersTable;
use Authorization\IdentityInterface;

/**
 * sales_summary: revenue aggregated by month, customer, product or category.
 */
class SalesSummaryTool extends AbstractTool
{
    /**
     * Allowed groupings.
     *
     * @var array<string>
     */
    public const GROUP_BY = ['month', 'customer', 'product', 'category'];

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
        return 'sales_summary';
    }

    /**
     * @inheritDoc
     */
    public function description(): string
    {
        return 'Sales between two dates (both included) grouped by month, customer, product or category. '
            . 'Returns rows {group, orders, revenue}; cancelled orders are excluded. Max 20 rows.';
    }

    /**
     * @inheritDoc
     */
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date_from' => ['type' => 'string', 'description' => 'First day included, YYYY-MM-DD.'],
                'date_to' => ['type' => 'string', 'description' => 'Last day included, YYYY-MM-DD.'],
                'group_by' => ['type' => 'string', 'enum' => self::GROUP_BY],
            ],
            'required' => ['date_from', 'date_to', 'group_by'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function run(array $input, IdentityInterface $identity): ToolResult
    {
        $dateFrom = $this->date($input, 'date_from', true);
        $dateTo = $this->date($input, 'date_to', true);
        $groupBy = $this->enum($input, 'group_by', self::GROUP_BY, true);
        assert($dateFrom !== null && $dateTo !== null && $groupBy !== null);
        $this->assertRange($dateFrom, $dateTo);

        /** @see \App\Model\Table\OrdersTable::findSalesSummary() */
        $query = $this->orders->find('salesSummary', dateFrom: $dateFrom, dateTo: $dateTo, dimension: $groupBy);
        $query = $identity->applyScope('index', $query);

        $result = $this->cappedResult($query, 'rows');
        $data = $result->data;
        $data['rows'] = array_map(
            fn(array $row): array => [
                'group' => $row['group_label'],
                'orders' => (int)$row['orders'],
                'revenue' => $row['revenue'],
            ],
            $data['rows'],
        );

        return ToolResult::ok($data, $result->rowCount, $result->truncated);
    }
}
