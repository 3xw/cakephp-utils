<?php
namespace Trois\Utils\Auth\Storage;

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
    'key' => 'Auth.User',
    'redirect' => 'Auth.redirect'
  ];

  public function __construct(ServerRequest $request, Response $response, array $config = [])
  {
    $this->setConfig($config);
  }

  public function read()
  {
    if ($this->_user !== null) return $this->_user ?: null;
    $this->_user = Cache::read($this->_config['key'],$this->_config['cache']) ?: false;
    return $this->_user ?: null;
  }

  public function write($user)
  {
    $this->_user = $user;
    Cache::write($this->_config['key'], $user,$this->_config['cache']);
  }

  public function delete()
  {
    $this->_user = false;
    Cache::delete($this->_config['key'], $user,$this->_config['cache']);
  }
}
