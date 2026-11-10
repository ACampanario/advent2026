<?php
declare(strict_types=1);

namespace App\Service\Ai\Tool;

use App\Model\Entity\Order;
use App\Model\Table\OrdersTable;
use Authorization\IdentityInterface;

/**
 * get_order: one order with notes and lines.
 */
class GetOrderTool extends AbstractTool
{
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
        return 'get_order';
    }

    /**
     * @inheritDoc
     */
    public function description(): string
    {
        return 'Get one order by id or by reference (e.g. ORD-2026-00123), with notes and items '
            . '(sku, product, quantity, unit_price, line_total). '
            . 'Returns {"found": false} if the order does not exist or is not visible.';
    }

    /**
     * @inheritDoc
     */
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'order_id' => ['type' => 'integer', 'description' => 'Order id.'],
                'reference' => ['type' => 'string', 'description' => 'Order reference, e.g. ORD-2026-00123.'],
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
        $orderId = $this->positiveInt($input, 'order_id');
        $reference = $this->optionalString($input, 'reference', 20);
        if ($orderId === null && $reference === null) {
            throw new InvalidToolInputException('Provide "order_id" or "reference".');
        }

        /** @see \App\Model\Table\OrdersTable::findForAgent() */
        $query = $this->orders->find('forAgent', orderId: $orderId, reference: $reference, withItems: true);
        $order = $identity->applyScope('index', $query)->first();

        if (!$order instanceof Order) {
            return ToolResult::notFound();
        }

        $items = [];
        foreach ($order->order_items as $item) {
            $items[] = [
                'sku' => $item->product->sku,
                'product' => $item->product->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ];
        }

        $data = $order->extract(['id', 'reference', 'customer_name', 'status', 'ordered_at', 'total', 'notes']);
        $data['items'] = $items;

        return ToolResult::ok(['order' => $data], 1);
    }
}
