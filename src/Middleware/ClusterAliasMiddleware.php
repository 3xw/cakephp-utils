<?php
namespace Trois\Utils\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Cache\Cache;

class ClusterAliasMiddleware
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
    'rules' => [
      [ // here we always use "reader" instead of "default" connection
        'slave' => true,
        'latency' => 1500, //milliseconds
        'from' => 'default',
        'to' => 'reader',
        'debug' => '*',
        'method' => '*', // ['GET','POST','PUT','DELETE'] or '*'
        'prefix' => '*',
        'plugin' => '*',
        'controller' => '*',
        'action' => '*',
        'extension' => '*',
      ],
    ],
  ];

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
  {
    $this->_init();
    $this->_execRule($request, $response);

    return $next($request, $response);
  }

  protected function _init()
  {
    if(empty(Configure::read('ClusterAliasRules')))
    {
      $key = 'cluster_alias_rules';
      try {
        Configure::load($key, 'default');
      } catch (Exception $ex) {
        throw new Exception(__('Missing configuration file: "config/{0}.php"!!!', $key), 1);
      }
    }

    $this->setConfig('rules', Configure::read('ClusterAliasRules'));
  }

  protected function _execRule($request, $response)
  {
    $rule = $this->_checkRules($request, $response);
    if(!$this->_checkLatency($rule) && $rule)
    {
      debug($rule);
      ConnectionManager::alias($rule->to, $rule->from);
    }
  }

  protected function _checkLatency($rule)
  {
    if(
      !$rule ||
      (
        ($rule = (object) array_merge($this->_defaultConfig['rules'][0], (array) $rule)) &&
        !$rule->slave
      )
    ) return !$this->_write();

    return $this->_hasLatency($rule);
  }

  protected function _write()
  {
    return Cache::write('ClusterAliasMiddleware',  (int) (microtime(true) * 1000), '_cake_core_');
  }

  protected function _hasLatency($rule)
  {
    if(!(int) $lastAction = Cache::read('ClusterAliasMiddleware', '_cake_core_')) return false;
    return $lastAction + $rule->latency >= (int) (microtime(true) * 1000);
  }

  protected function _checkRules($request, $response)
  {
    foreach ($this->getConfig('rules') as $key => $rule) if($rule = $this->_matchRule($rule, $request, $response)) return $rule;
    return false;
  }

  protected function _matchRule($rule, $request, $response)
  {
    $debug = Configure::read('debug');
    $method = $request->getMethod();
    $plugin = $request->plugin;
    $controller = $request->controller;
    $action = $request->action;
    $prefix = null;
    $extension = null;
    if (!empty($request->params['prefix'])) $prefix = $request->params['prefix'];
    if (!empty($request->params['_ext'])) $extension = $request->params['_ext'];

    if (
      $this->_matchOrAsterisk($rule, 'debug', $debug, true) &&
      $this->_matchOrAsterisk($rule, 'method', $method, true) &&
      $this->_matchOrAsterisk($rule, 'prefix', $prefix, true) &&
      $this->_matchOrAsterisk($rule, 'plugin', $plugin, true) &&
      $this->_matchOrAsterisk($rule, 'extension', $extension, true) &&
      $this->_matchOrAsterisk($rule, 'controller', $controller) &&
      $this->_matchOrAsterisk($rule, 'action', $action)
    ) return (object) $rule;

    return null;
  }

  protected function _matchOrAsterisk($permission, $key, $value, $allowEmpty = false)
  {
    $possibleValues = (array) Hash::get($permission, $key);

    if ($allowEmpty && empty($possibleValues) && $value === null) return true;

    if (
      Hash::get($permission, $key) === '*' ||
      in_array($value, $possibleValues) ||
      in_array(Inflector::camelize($value, '-'), $possibleValues)
    ) return true;

    return false;
  }
}
