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
use Cake\Network\Exception\UnauthorizedException;
use Firebase\JWT\JWT;

class JwtBearerAuthenticate extends CakeBasicAuthenticate
{
  protected $_token;

  protected $_payload;

  protected $_error;

  protected $_defaultConfig = [
    'userModel' => 'Users',
    'header' => 'authorization',
    'prefix' => 'bearer',
    'parameter' => 'token',
    'queryDatasource' => true,
    'fields' => [
      'username' => 'id',
      'password' => 'password'
    ],
    'unauthenticatedException' => '\Cake\Network\Exception\UnauthorizedException',
    'key' => null,
    'allowedAlgs' => ['HS256']
  ];

  public function unauthenticated(ServerRequest $request, Response $response)
  {
    $Exception = new UnauthorizedException('Ah ah ah! You didn\'t say the magic word!');
    $Exception->responseHeader([$this->loginHeaders($request)]);
    throw $Exception;
  }

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

    $header = $request->header($config['header']);
    if ($header && stripos($header, $config['prefix']) === 0) {
      return $this->_token = str_ireplace($config['prefix'] . ' ', '', $header);
    }

    if (!empty($this->_config['parameter'])) {
      $this->_token = $request->query($this->_config['parameter']);
    }

    return $this->_token;
  }

  protected function _decode($token)
  {
    $config = $this->_config;
    try {
      $payload = JWT::decode($token, $config['key'] ?: Security::salt(), $config['allowedAlgs']);

      return $payload;
    } catch (ExpiredException $e) {
      throw new UnauthorizedException($e->getMessage());  
    }
  }
}
