<?php
namespace Trois\Utils\Controller;

use Cake\Event\Event;
use App\Controller\AppController;

class TwoFactorAuthController extends AppController
{
  public function beforeFilter(Event $event)
  {
    parent::beforeFilter($event);
    $this->Auth->allow(['verify']);
  }

  public function verify()
  {
    $this->set('loginAction', $this->Auth->getConfig('loginAction'));
  }
}
