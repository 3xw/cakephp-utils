<?php
namespace Trois\Utils\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Event\Event;
use Cake\Utility\Security;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Auth\FormAuthenticate;

class TwoFactorAuthenticate extends FormAuthenticate
{
  protected $_defaultConfig = [
    'code' => [
      'length' => 8,
      'field' => 'code'
    ],
    'token' => [
      'allowedAlgs' => ['HS256'],
      'duration' => 3600,
      'field' => 'token',
      'sub' => 'id'
    ],
    'fields' => [
      'username' => 'email',
      'password' => 'password'
    ],
    'userModel' => 'Users',
    'scope' => [],
    'finder' => 'all',
    'contain' => null,
    'passwordHasher' => 'Default'
  ];

  public $code;

  public $token;

  public function genCode()
  {
    $this->code = '';
    while ( $count < $this->_config['code.length'] ) {
      $digit = mt_rand(0, 9);
      $this->code .= $digit;
      $count++;
    }
    return $this->code;
  }

  public function findUser(ServerRequest $request, Response $response)
  {
    $fields = $this->_config['fields'];
    if (!$this->_checkFields($request, $fields)) return false;

    $user = $this->_findUser($request->getData($fields['username']),$request->getData($fields['password']));

    if($user)
    {
      $this->genCode();
      $this->token = JWT::encode([
        'username' => $user[$this->config('field.username')],
        'code' => $this->code,
        'exp' =>  time() + $this->config('token.duration')
      ], Security::salt());
    }

    return $user;
  }

  public function authenticate(ServerRequest $request, Response $response)
  {
    // look for fileds
    $fields = [$this->_config['code.field'], $this->_config['token.field']];
    if (!$this->_checkFields($request, $fields)) return false;

    // read token
    try {
      $payload = JWT::decode($request->getData($this->_config['token.field']), Security::salt(), $this->_config['token.allowedAlgs']);
      $username = $payload->username;
      $code = $payload->code;
    } catch (ExpiredException $e) {
      throw new UnauthorizedException($e->getMessage());
    }

    // look for user
    $user = $this->_query($username)->first();
    if (empty($user)) return false;

    // test code
    if((string)$code !== (string)$request->getData($this->_config['code.field']))return false;

    // set $token
    $this->token = JWT::encode([
      'sub' => $user[$this->config('token.sub')],
      'exp' =>  time() + $this->config('token.duration')
    ], Security::salt());

    return $user;
  }
}
