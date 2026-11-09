<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Conversation;
use Authorization\IdentityInterface;

/**
 * A user can only view and continue their own conversations (admins included).
 */
class ConversationPolicy
{
    /**
     * Whether the identity may open the conversation.
     *
     * @param \Authorization\IdentityInterface $identity Logged-in user.
     * @param \App\Model\Entity\Conversation $conversation Conversation.
     * @return bool
     */
    public function canView(IdentityInterface $identity, Conversation $conversation): bool
    {
        return $conversation->user_id === $identity['id'];
    }

    /**
     * Whether the identity may post a new message to the conversation.
     *
     * @param \Authorization\IdentityInterface $identity Logged-in user.
     * @param \App\Model\Entity\Conversation $conversation Conversation.
     * @return bool
     */
    public function canContinue(IdentityInterface $identity, Conversation $conversation): bool
    {
        return $this->canView($identity, $conversation);
    }
}
