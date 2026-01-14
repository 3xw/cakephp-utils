<?php
namespace Trois\Utils\Auth;

use Firebase\JWT\JWT;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Event\Event;
use Cake\Utility\Security;
use Cake\Core\Configure;
use Cake\Auth\BasicAuthenticate AS CakeBasicAuthenticate;

class BasicToJwtBearerAuthenticate extends CakeBasicAuthenticate
{
  protected array $_defaultConfig = [
    'fields' => [
        'username' => 'username',
        'password' => 'password'
    ],
    'userModel' => 'Users',
    'scope' => [],
    'finder' => 'all',
    'contain' => null,
    'passwordHasher' => 'Default',

    'field' => 'id',
    'duration' => 3600,
    'headerKey' => 'X-Token',
  ];

  public function afterIdentify(Event $event, array $user)
  {
    $token = JWT::encode(['sub' => $user[$this->getConfig('field')], 'exp' =>  time() + $this->getConfig('duration')], Security::getSalt());
    $event->getSubject()->response = $event->getSubject()->response->withHeader($this->getConfig('headerKey'), $token);
    $event->result = $user;
  }

  public function implementedEvents()
  {
    return ['Auth.afterIdentify' => 'afterIdentify'];
  }
}
