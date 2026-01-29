<?php
namespace Trois\Utils\Auth;

use Firebase\JWT\JWT;
use Cake\Auth\BasicAuthenticate as CakeBasicAuthenticate;
use Cake\Event\Event;
use Cake\Utility\Security;

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
    $now = time();
    $payload = [
      'sub' => (string) $user[$this->getConfig('field')],
      'iat' => $now,
      'exp' => $now + (int) $this->getConfig('duration'),
    ];

    // v6/v7: encode(payload, key, alg)
    $token = JWT::encode($payload, (string) Security::getSalt(), 'HS256');

    $subject = $event->getSubject();
    $subject->response = $subject->response->withHeader($this->getConfig('headerKey'), $token);

    $event->result = $user;
  }

  public function implementedEvents()
  {
    return ['Auth.afterIdentify' => 'afterIdentify'];
  }
}
