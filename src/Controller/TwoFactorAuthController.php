<?php
namespace Trois\Utils\Controller;

use Cake\Event\Event;
use App\Controller\AppController;

class TwoFactorAuthController extends AppController
{
  public function initialize()
  {
    parent::initialize();
    if($this->Auth->getConfig('checkAuthIn') == 'Controller.startup') $this->Auth->allow(['verify']);
  }

  public function beforeFilter(Event $event)
  {
    parent::beforeFilter($event);
    if($this->Auth->getConfig('checkAuthIn') != 'Controller.startup') $this->Auth->allow(['verify']);
  }

  public function verify()
  {
    if ($this->request->is('post')) {
      $user = $this->Auth->identify();
      if ($user) {
        $this->Auth->setUser($user);
        return $this->redirect($this->Auth->redirectUrl());
      }
    }
    $this->set('loginAction', $this->Auth->getConfig('loginAction'));
  }
}
