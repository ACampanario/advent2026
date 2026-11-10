<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Table\ConversationsTable;
use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

/**
 * Conversation list and creation: every user (admins included) only sees their own chats.
 */
class ConversationsTablePolicy
{
    /**
     * Any logged-in user can start a conversation.
     *
     * @param \Authorization\IdentityInterface $identity Logged-in user.
     * @param \App\Model\Table\ConversationsTable $conversations Conversations table.
     * @return bool
     */
    public function canAdd(IdentityInterface $identity, ConversationsTable $conversations): bool
    {
        return true;
    }

    /**
     * Restricts a conversations query to the identity's own conversations.
     *
     * @param \Authorization\IdentityInterface $identity Logged-in user.
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Conversation> $query Conversations query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Conversation>
     */
    public function scopeIndex(IdentityInterface $identity, SelectQuery $query): SelectQuery
    {
        return $query->where(['Conversations.user_id' => $identity['id']]);
    }
}
