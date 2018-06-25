<?php
namespace Trois\Utils\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Event\Event;
use Cake\Utility\Security;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Auth\FormAuthenticate;
use Cake\Routing\Router;
use Trois\Utils\Auth\TwoFactor\EmailCodeTransmitter\AbstractCodeTransmitter;

class TwoFactorAuthenticate extends FormAuthenticate
{
  /** @var AbstractCodeTransmitter */
  protected $_transmitter;

  protected $_defaultConfig = [
    'transmitter' => [
      'class' => '\Trois\Utils\Auth\TwoFactor\EmailCodeTransmitter',
      'config' => []
    ],
    'verifyAction' => [
      'prefix' => false,
      'controller' => 'TwoFactorAuth',
      'action' => 'verify',
      'plugin' => 'Trois/Utils'
    ],
    'code' => [
      'length' => 8,
      'field' => 'code'
    ],
    'token' => [
      'allowedAlgs' => ['HS256'],
      'duration' => 3600,
      'sub' => 'id',
      'field' => 'token',
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
    $count = 0;
    while ( $count < $this->getConfig('code.length') ) {
      $digit = mt_rand(0, 9);
      $this->code .= $digit;
      $count++;
    }
    return $this->code;
  }

  protected function _checkFields(ServerRequest $request, array $fields)
  {
    foreach ($fields as $field) {
      $value = $request->getData($field);
      if (empty($value) || !is_string($value)) return false;
    }
    return true;
  }

  protected function _decode()
  {
    $config = $this->_config;
    try {
      $payload = JWT::decode($request->getData($this->getConfig('token.field')), Security::salt(), $this->getConfig('token.allowedAlgs'));
      return $payload;
    } catch (ExpiredException $e) {
      throw new UnauthorizedException($e->getMessage());
    }catch (SignatureInvalidException $e) {
      throw new UnauthorizedException($e->getMessage());
    }catch (\DomainException $e) {
      throw new UnauthorizedException($e->getMessage());
    }
  }

  protected function _transmit(string $code, array $user, ServerRequest $request, Response $response)
  {
    $transmitter = $this->getConfig('transmitter.class');
    $this->_transmitter = new $transmitter($this->getConfig('transmitter.config'));
    return $this->_transmitter->transmit($code, $user, $request, $response);
  }

  public function authenticate(ServerRequest $request, Response $response)
  {
    // look for form auth fields
    $formAuth = $this->_checkFields($request, $this->getConfig('fields'));

    // look for token auth fields
    $tokenCodeAuth = $this->_checkFields($request, [$this->getConfig('token.field'), $this->getConfig('code.field')]);

    // if none
    if(!$formAuth && !$tokenCodeAuth) return false;

    // form Auth
    if($formAuth)
    {
      // find and test user
      if(!$user = $this->_findUser($request->getData($this->getConfig('fields.username')),$request->getData($this->getConfig('fields.password')))) return false;

      // create code + token
      $this->token = JWT::encode(['username' => $user[$this->getConfig('fields.username')],'code' => $this->genCode(),'exp' =>  time() + $this->getConfig('token.duration')], Security::salt());

      // transmit
      $transmitted = $this->_transmit($this->code, $user, $request, $response);
      if(!$transmitted)
      {
        $this->_registry->getController()->Auth->config('authError', $this->_transmitter->getConfig('messages.error'));
        return false;
      }

      // set redirect to verify action and flash message
      $this->_registry->getController()->Flash->success($this->_transmitter->getConfig('messages.success'));
      $pass = ['?' => [
        'challenge' => $this->token,
        'token' => $this->getConfig('token.field'),
        'code' => $this->getConfig('code.field')
      ]];
      $this->_registry->getController()->setResponse($response->withLocation(Router::url($this->getConfig('verifyAction') + $pass, true)));

      // prevent Auth to store incomplete processed user
      $this->_registry->getController()->Auth->config('storage','Memory');
    }

    // token + code Auth
    if($tokenCodeAuth)
    {
      // read token
      $payload = $this->_decode();

      // look for user
      if (!$user = $this->_query($payload->username)->first()) return false;

      // test code
      if((string)$payload->code !== (string)$request->getData($this->getConfig('code.field'))) return false;

      // set Bearer token for BearerTokenAuth
      $this->token = JWT::encode(['sub' => $user[$this->getConfig('token.sub')],'exp' =>  time() + $this->getConfig('token.duration')], Security::salt());
    }

    return $user;
  }
}
