<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Demo profile selector: the user picks admin, sales1 or sales2 and that user
 * becomes the session identity. There is no password check on purpose; this is
 * a demo shortcut and must never be used in production.
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    /**
     * Allow the login page without an identity.
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event Event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['login']);
    }

    /**
     * GET: list the demo profiles. POST: start a session as the selected profile.
     *
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod(['get', 'post']);

        if ($this->request->is('post')) {
            $userId = filter_var($this->request->getData('user_id'), FILTER_VALIDATE_INT);
            /** @see \App\Model\Table\UsersTable::findForIdentity() */
            $user = $userId === false ? null : $this->Users->find('forIdentity', userId: $userId)->first();

            if ($user === null) {
                $this->Flash->error(__('Unknown profile.'));

                return $this->redirect(['action' => 'login']);
            }

            $this->Authentication->setIdentity($user);

            return $this->redirect($this->Authentication->getLoginRedirect() ?? '/');
        }

        /** @see \App\Model\Table\UsersTable::findLoginOptions() */
        $users = $this->Users->find('loginOptions')->all();
        $this->set(compact('users'));

        return null;
    }

    /**
     * End the session and go back to the profile selector.
     *
     * @return \Cake\Http\Response|null
     */
    public function logout(): ?Response
    {
        $this->Authorization->skipAuthorization();
        $this->Authentication->logout();

        return $this->redirect(['action' => 'login']);
    }
}
