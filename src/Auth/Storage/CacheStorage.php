<?php
namespace Trois\Utils\Auth\Storage;

use Firebase\JWT\JWT;
use Cake\Utility\Security;
use Cake\Cache\Cache;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Auth\Storage\MemoryStorage;

class CacheStorage extends MemoryStorage
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
    'cache' => 'default',
    'prefix' => 'token:',
    'field' => 'id',
    'token' => [
      'header' => 'authorization',
      'prefix' => 'bearer',
      'parameter' => 'token',
      'key' => null,
      'allowedAlgs' => ['HS256']
    ]
  ];

  protected $_id = '';

  public function __construct(ServerRequest $request, Response $response, array $config = [])
  {
    $this->setConfig($config);
    $this->parseRequest($request);
  }

  public function read()
  {
    if ($this->_user !== null) return $this->_user ?: null;
    if(empty($this->_id)) return $this->_user ?: null;
    $this->_user = Cache::read($this->_config['prefix'].$this->_id,$this->_config['cache']) ?: false;
    return $this->_user ?: null;
  }

  public function write($user)
  {
    $this->_user = $user;
    if(empty($this->_user[$this->_config['field']])) throw new \UnexpectedValueException('No uinq user filed provided for storage id!');
    $this->_id = $this->_id ?: $this->_user[$this->_config['field']];
    Cache::write($this->_config['prefix'].$this->_id, $user,$this->_config['cache']);
  }

  public function delete()
  {
    if(empty($this->_id)) throw new \UnexpectedValueException('No uinq user filed provided for storage id!');
    $this->_user = false;
    Cache::delete($this->_config['prefix'].$this->_id, $user,$this->_config['cache']);
  }

  public function parseRequest($request = null)
  {
    if (!$request) return;

    $header = $request->header($this->_config['token']['header']);
    if ($header && stripos($header, $this->_config['token']['prefix']) === 0)
    {
      $token = $this->_decode(str_ireplace($this->_config['token']['prefix'] . ' ', '', $header));
      return $this->_id = $token->sub;
    }
    if (!empty($request->query($this->_config['token']['parameter'])))
    {
      $token = $this->_decode($request->query($this->_config['token']['parameter']));
      return $this->_id = $token->sub;
    }
  }

  protected function _decode($token)
  {
    $config = $this->_config;
    try {
      $payload = JWT::decode($token, $this->_config['token']['key'] ?: Security::salt(), $this->_config['token']['allowedAlgs']);
      return $payload;
    } catch (Exception $e) {
      if (Configure::read('debug')) {
        throw $e;
      }
      $this->_error = $e;
    }
  }
}
