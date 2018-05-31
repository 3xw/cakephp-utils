<?php

namespace Trois\Utils\Cache\Engine;

use Cake\Error\FatalErrorException;
use Cake\Cache\Engine\RedisEngine as CakeRedisEngine;

class RedisEngine extends CakeRedisEngine
{
  protected $_defaultConfig = [
    'database' => 0,
    'duration' => 3600,
    'groups' => [],
    'password' => false,
    'persistent' => true,
    'port' => 6379,
    'prefix' => 'cake_',
    'probability' => 100,
    'host' => null,
    'server' => '127.0.0.1',
    'timeout' => 0,
    'unix_socket' => false,
    'serialize' => true
  ];

  public function init(array $config = [])
  {
    if (!extension_loaded('redis'))
    {
      throw new FatalErrorException('Missing php-redis extension! Please add php-redis extension in yout php.ini file in order to use Awallef\Redis\Cache\Engine\RedisEngine... ');
    }
    return parent::init($config);
  }

  public function key($key)
  {
    if (empty($key)) {
      return false;
    }

    $prefix = '';
    if (!empty($this->_groupPrefix)) {
      $prefix = vsprintf($this->_groupPrefix, $this->groups());
    }

    //$key = preg_replace('/[\s]+/', '_', strtolower(trim(str_replace([DS, '/', '.'], '_', strval($key)))));
    return $prefix . $key;
  }

  public function write($key, $value)
  {
    if ($value === '') {
      return false;
    }

    $key = $this->_key($key);
    if (!is_int($value) && $this->_config['serialize'] ) {
      $value = serialize($value);
    }

    $duration = $this->_config['duration'];
    if ($duration === 0) {
      return $this->_Redis->set($key, $value);
    }

    return $this->_Redis->setex($key, $duration, $value);
  }

  public function read($key)
  {
    $key = $this->_key($key);

    $value = $this->_Redis->get($key);
    if (ctype_digit($value)) {
      $value = (int)$value;
    }
    if ($value !== false && is_string($value) && $this->_config['serialize'] ) {
      $value = unserialize($value);
    }
    return $value;
  }

  public function delete($key)
  {
    $keys = $this->_Redis->getKeys($this->_config['prefix'].$key);
    return $this->_Redis->del($keys) > 0;
  }
}
