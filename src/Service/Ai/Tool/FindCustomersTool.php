<?php
declare(strict_types=1);

namespace App\Service\Ai\Tool;

use App\Model\Table\CustomersTable;
use Authorization\IdentityInterface;

/**
 * find_customers: search the customers visible to the user.
 */
class FindCustomersTool extends AbstractTool
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
        return 'find_customers';
    }

    /**
     * @inheritDoc
     */
    public function description(): string
    {
        return 'Search customers by name, company or email (contains, case-insensitive) and/or country. '
            . 'Returns id, name, company, email, city, country. Max 20 rows.';
    }

    /**
     * @inheritDoc
     */
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'search' => ['type' => 'string', 'description' => 'Text contained in name, company or email.'],
                'country' => ['type' => 'string', 'description' => 'ISO 3166-1 alpha-2 country code, e.g. US.'],
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
        $search = $this->optionalString($input, 'search');
        $country = $this->optionalString($input, 'country', 2);
        if ($country !== null && !preg_match('/^[A-Za-z]{2}$/', $country)) {
            throw new InvalidToolInputException('"country" must be a 2-letter country code.');
        }

        /** @see \App\Model\Table\CustomersTable::findForAgent() */
        $query = $this->customers->find('forAgent', search: $search, country: $country);
        $query = $identity->applyScope('index', $query);

        return $this->cappedResult($query, 'customers');
    }
}
