<?php
namespace Trois\Utils\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Cache\Cache;
use Trois\Utils\Http\RequestMatchRule;

class ClusterAliasMiddleware implements MiddlewareInterface
{
  use InstanceConfigTrait;

  protected array $_defaultConfig = [
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

  public function __construct(array $config = [])
  {
    $this->setConfig($config);
  }

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $this->_init();
    $this->_execRule($request);

    return $handler->handle($request);
  }

  protected function _init()
  {
    if(empty(Configure::read('Trois.clusterAlias.rules')))
    {
      $key = 'cluster_alias_rules';
      try {
        Configure::load($key, 'default');
      } catch (Exception $ex) {
        throw new Exception(__('Missing configuration file: "config/{0}.php"!!!', $key), 1);
      }
    }

    $this->setConfig('rules', Configure::read('Trois.clusterAlias.rules'));
  }

  protected function _execRule($request)
  {
    $rule = (new RequestMatchRule())->checkRules($this->getConfig('rules'), $request);
    if(!$this->_checkLatency($rule) && $rule)
    {
      $rule = (object) $rule;
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
}
