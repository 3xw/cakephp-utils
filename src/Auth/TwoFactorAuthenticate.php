<?php
namespace Trois\Utils\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Utility\Security;
use Cake\Auth\FormAuthenticate;
use Cake\Routing\Router;
use Cake\Network\Request;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Auth\PasswordHasherFactory;

use Trois\Utils\Auth\TwoFactor\EmailCodeTransmitter\AbstractCodeTransmitter;

class TwoFactorAuthenticate extends FormAuthenticate
{
  /** @var AbstractCodeTransmitter */
  protected $_transmitter;

  protected array $_defaultConfig = [
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
      'passwordHasher' => 'Default'
    ],
    'token' => [
      'allowedAlgs' => ['HS256'],
      'duration' => 3600,
      'sub' => 'id',
      'field' => 'token',
      'parameter' => 'token',
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
    while ($count < $this->getConfig('code.length')) {
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
      if (empty($value) || !is_string($value)) {
        return false;
      }
    }
    return true;
  }

  /**
   * CakePHP 3 uses Security::salt()
   * CakePHP 4+ uses Security::getSalt()
   */
  protected function _getJwtKey(): string
  {
    if (method_exists(Security::class, 'salt')) {
      return (string) Security::salt();
    }
    return (string) Security::getSalt();
  }

  protected function _getJwtAlg(): string
  {
    $algs = (array) $this->getConfig('token.allowedAlgs');
    return $algs[0] ?? 'HS256';
  }

  protected function _decode($token)
  {
    try {
      $key = $this->_getJwtKey();
      $alg = $this->_getJwtAlg();

      // firebase/php-jwt v6/v7
      return JWT::decode($token, new Key($key, $alg));
    } catch (ExpiredException $e) {
      $this->_registry->getController()->Flash->error($e->getMessage());
      return false;
    } catch (SignatureInvalidException $e) {
      $this->_registry->getController()->Flash->error($e->getMessage());
      return false;
    } catch (\DomainException $e) {
      $this->_registry->getController()->Flash->error($e->getMessage());
      return false;
    } catch (\UnexpectedValueException $e) {
      $this->_registry->getController()->Flash->error($e->getMessage());
      return false;
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
    $formAuth = $this->_checkFields($request, (array) $this->getConfig('fields'));

    // look for token auth fields
    $tokenCodeAuth = $this->_checkFields($request, ['code']);

    // if none
    if (!$formAuth && !$tokenCodeAuth) {
      return false;
    }

    // construct hasher
    $hasher = PasswordHasherFactory::build($this->getConfig('code.passwordHasher'));

    // form Auth
    if ($formAuth) {
      // find and test user
      $usernameField = $this->getConfig('fields.username');
      $passwordField = $this->getConfig('fields.password');

      $username = $request->getData($usernameField);
      $password = $request->getData($passwordField);

      if (!$user = $this->_findUser($username, $password)) {
        return false;
      }

      // create code + token
      $this->genCode();

      $this->token = JWT::encode(
        [
          'username' => $user[$usernameField],
          'code' => $hasher->hash($this->code),
          'exp' => time() + (int) $this->getConfig('token.duration'),
        ],
        $this->_getJwtKey(),
        $this->_getJwtAlg()
      );

      // transmit
      $transmitted = $this->_transmit($this->code, $user, $request, $response);
      if (!$transmitted) {
        $this->_registry->getController()->Auth->config('authError', $this->_transmitter->getConfig('messages.error'));
        return false;
      }

      // set redirect to verify action and flash message
      $this->_registry->getController()->Flash->success($this->_transmitter->getConfig('messages.success'));
      $response = $response->withLocation(Router::url($this->getConfig('verifyAction'), true));
      $this->_registry->getController()->setResponse($response);
      $this->_registry->getController()->Auth->config('storage', 'Memory');

      // set session challenge
      $request->getSession()->write('TwoFactorAuthenticate.token', $this->token);

      // just say no!
      return false;
    }

    // token + code Auth
    if ($tokenCodeAuth) {
      // read token
      $token = $request->getSession()->read('TwoFactorAuthenticate.token');
      if (empty($token)) {
        return false;
      }

      $payload = $this->_decode($token);
      if (!$payload) {
        return false;
      }

      // look for user
      if (!$user = $this->_query($payload->username)->first()) {
        return false;
      }
      $user = $user->toArray();

      $request->getSession()->delete('TwoFactorAuthenticate.token');

      // test code
      $password = $request->getData('code');
      if (!$hasher->check($password, $payload->code)) {
        return false;
      }

      // set Bearer token for BearerTokenAuth
      $this->token = JWT::encode(
        [
          'sub' => $user[$this->getConfig('token.sub')],
          'exp' => time() + (int) $this->getConfig('token.duration'),
        ],
        $this->_getJwtKey(),
        $this->_getJwtAlg()
      );

      // if no cookie then pass token as an argument
      if ($this->_registry->getController()->Auth->getConfig('storage') != 'Session') {
        $this->_registry->getController()->Auth->config(
          'loginRedirect',
          $this->_registry->getController()->Auth->config('loginRedirect') + [
            '?' => [$this->getConfig('token.parameter') => $this->token]
          ]
        );
      }

      return $user;
    }

    return false;
  }
}
