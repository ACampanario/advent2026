<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Ai\AgentService;
use App\Service\Ai\NullAgentEventListener;
use App\Service\Ai\TranscriptBuilder;
use Cake\Http\Response;

/**
 * Chat with the shop assistant.
 *
 * @property \App\Model\Table\ConversationsTable $Conversations
 */
class ConversationsController extends AppController
{
    /**
     * Maximum length of a user message.
     *
     * @var int
     */
    public const MAX_MESSAGE_LENGTH = 2000;

    /**
     * Conversation list and an empty chat ("New chat").
     *
     * @return void
     */
    public function index(): void
    {
        $this->setSidebar();
        $this->set('conversation', null);
        $this->set('transcript', ['exchanges' => [], 'totals' => []]);
    }

    /**
     * One conversation (owner only).
     *
     * @param \App\Service\Ai\TranscriptBuilder $transcriptBuilder Groups messages for display.
     * @param string $id Conversation id.
     * @return void
     */
    public function view(TranscriptBuilder $transcriptBuilder, string $id): void
    {
        $conversation = $this->Conversations->get((int)$id);
        $this->Authorization->authorize($conversation, 'view');

        /** @see \App\Model\Table\MessagesTable::findForDisplay() */
        $messages = $this->Conversations->Messages->find('forDisplay', conversationId: $conversation->id)->all();

        $this->setSidebar();
        $this->set('conversation', $conversation);
        /** @see \App\Service\Ai\TranscriptBuilder::build() */
        $this->set('transcript', $transcriptBuilder->build($messages));
        $this->render('index');
    }

    /**
     * Sends a message: runs the agent, then redirects to the conversation (PRG).
     *
     * @param \App\Service\Ai\AgentService $agent Agent loop.
     * @return \Cake\Http\Response|null
     */
    public function message(AgentService $agent): ?Response
    {
        $this->request->allowMethod(['post']);
        set_time_limit(120);

        /** @var \Authorization\IdentityInterface $identity */
        $identity = $this->request->getAttribute('identity');
        $text = trim((string)$this->request->getData('message'));
        $conversationId = filter_var($this->request->getData('conversation_id'), FILTER_VALIDATE_INT);

        if ($conversationId !== false) {
            $conversation = $this->Conversations->get($conversationId);
            $this->Authorization->authorize($conversation, 'continue');
        } else {
            $this->Authorization->authorize($this->Conversations, 'add');
            $conversation = null;
        }

        if ($text === '' || mb_strlen($text) > self::MAX_MESSAGE_LENGTH) {
            $this->Flash->error(__('The message must be between 1 and {0} characters.', self::MAX_MESSAGE_LENGTH));

            return $this->redirect($conversation ? ['action' => 'view', $conversation->id] : ['action' => 'index']);
        }

        /** @see \App\Model\Table\ConversationsTable::startFor() */
        $conversation ??= $this->Conversations->startFor((int)$identity['id'], $text);

        /** @see \App\Service\Ai\AgentService::run() */
        $result = $agent->run($conversation, $text, $identity, new NullAgentEventListener());
        if ($result->notice !== null) {
            $this->Flash->warning($result->notice);
        }

        return $this->redirect(['action' => 'view', $conversation->id]);
    }

    /**
     * Sidebar data: the identity's own conversations (scope via ConversationsTablePolicy).
     *
     * @return void
     */
    private function setSidebar(): void
    {
        /** @see \App\Model\Table\ConversationsTable::findSidebar() */
        $query = $this->Conversations->find('sidebar');
        /** @see \App\Policy\ConversationsTablePolicy::scopeIndex() */
        $this->set('conversations', $this->Authorization->applyScope($query, 'index')->all());
    }
}
