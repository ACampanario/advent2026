<?php
declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

/**
 * Scope for customer queries: admins see every customer, sales reps only their own.
 */
class CustomersTablePolicy
{
    /**
     * Restricts a customers query to the records the identity may see.
     *
     * @param \Authorization\IdentityInterface $identity Logged-in user.
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Customer> $query Customers query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Customer>
     */
    public function scopeIndex(IdentityInterface $identity, SelectQuery $query): SelectQuery
    {
        if ($identity['role'] === 'admin') {
            return $query;
        }

        /** @see \App\Model\Table\CustomersTable::findOwnedBy() */
        return $query->find('ownedBy', userId: $identity['id']);
    }
}
