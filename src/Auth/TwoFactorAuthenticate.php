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

class TwoFactorAuthenticate extends FormAuthenticate
{
  protected $_defaultConfig = [
    'code' => [
      'length' => 8,
      'field' => 'code'
    ],
    'verifyAction' => [
      'prefix' => false,
      'controller' => 'TwoFactorAuth',
      'action' => 'verify',
      'plugin' => 'Trois/Utils'
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

      // create code + token and redirect to verify action
      $this->token = JWT::encode(['username' => $user[$this->getConfig('fields.username')],'code' => $this->genCode(),'exp' =>  time() + $this->getConfig('token.duration')], Security::salt());
      $this->_registry->getController()->setResponse($response->withLocation(Router::url($this->getConfig('verifyAction'), true)));
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
