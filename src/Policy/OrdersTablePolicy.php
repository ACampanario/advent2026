<?php
declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

/**
 * Scope for order queries: admins see every order, sales reps only the orders
 * of their own customers.
 */
class OrdersTablePolicy
{
    /**
     * Restricts an orders query to the records the identity may see.
     *
     * @param \Authorization\IdentityInterface $identity Logged-in user.
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Order> $query Orders query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Order>
     */
    public function scopeIndex(IdentityInterface $identity, SelectQuery $query): SelectQuery
    {
        if ($identity['role'] === 'admin') {
            return $query;
        }

        // Subquery instead of a join, so finders that already join Customers keep working.
        /** @see \App\Model\Table\CustomersTable::findOwnedBy() */
        $ownCustomers = $query->getRepository()->getAssociation('Customers')->getTarget()
            ->find('ownedBy', userId: $identity['id'])
            ->select(['Customers.id']);

        return $query->where(['Orders.customer_id IN' => $ownCustomers]);
    }
}
