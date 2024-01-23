<?php
namespace Trois\Utils\Auth;

use Cake\Auth\BasicAuthenticate AS CakeBasicAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Security;
use Cake\Http\ServerRequest;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Cake\Http\Exception\UnauthorizedException;

class JwtBearerAuthenticate extends CakeBasicAuthenticate
{
  protected $_token;

  protected $_payload;

  protected $_error;

  protected $_defaultConfig = [
    'fields' => [
        'username' => 'id',
        'password' => 'password'
    ],
    'userModel' => 'Users',
    'scope' => [],
    'finder' => 'all',
    'contain' => null,
    'passwordHasher' => 'Default',
    
    'header' => 'authorization',
    'prefix' => 'bearer',
    'parameter' => 'token',
    'queryDatasource' => true,
    'unauthenticatedException' => '\Cake\Http\Exception\UnauthorizedException',
    'key' => null,
    'allowedAlgs' => ['HS256']
  ];

  public function getUser(Request $request)
  {
    $payload = $this->getPayload($request);

    if (empty($payload)) {
      return false;
    }

    if (!$this->_config['queryDatasource']) {
      return json_decode(json_encode($payload), true);
    }

    if (!isset($payload->sub)) {
      return false;
    }

    $user = $this->_findUser($payload->sub);
    if (!$user) {
      return false;
    }

    unset($user[$this->_config['fields']['password']]);

    return $user;
  }

  public function getPayload($request = null)
  {
    if (!$request) {
      return $this->_payload;
    }

    $payload = null;

    $token = $this->getToken($request);
    if ($token) {
      $payload = $this->_decode($token);
    }
    return $this->_payload = $payload;
  }

  public function getToken($request = null)
  {
    $config = $this->_config;

    if (!$request) {
      return $this->_token;
    }

    $header = $request->getHeader($config['header']);
    if (!empty($header) && stripos($header[0], $config['prefix']) === 0) {
      return $this->_token = str_ireplace($config['prefix'] . ' ', '', $header[0]);
    }

    if (!empty($this->_config['parameter']) && !empty($request->getQueryParams()[$this->_config['parameter']])) {
      $this->_token = $request->getQueryParams()[$this->_config['parameter']];
    }

    return $this->_token;
  }

  protected function _decode($token)
  {
    $config = $this->_config;
    try {
      $payload = JWT::decode($token, new Key($config['key'] ?: Security::getSalt(), $config['allowedAlgs']));

      return $payload;
    } catch (ExpiredException $e) {
      throw new UnauthorizedException($e->getMessage());
    }catch (SignatureInvalidException $e) {
      throw new UnauthorizedException($e->getMessage());
    }catch (\DomainException $e) {
      throw new UnauthorizedException($e->getMessage());
    }
  }
}
