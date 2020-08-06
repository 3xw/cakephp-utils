<?
namespace Trois\Utils\Middleware;

use Cake\Cache\Cache;
use Cake\Log\Log;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Trois\Utils\Utility\Html\Compressor;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

class ResponseCacheMiddleware
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
      'settings' => [],
      'rules' => [],
  ];

  protected $_ruleKey = -1;

  protected $_compressor;

  protected function _init()
  {
    if(empty(Configure::read('Trois.cache.settings')) || empty(Configure::read('Trois.cache.rules')))
    {
      $key = 'cache';
      try {
        Configure::load($key, 'default');
      } catch (Exception $ex) {
        throw new Exception(__('Missing configuration file: "config/{0}.php"!!!', $key), 1);
      }
    }
    $this->config('settings', Configure::read('Trois.cache.settings'));
    $this->config('rules', Configure::read('Trois.cache.rules'));
  }

  public function __invoke($request, $response, $next)
  {
    $response = $next($request, $response);
    $this->_init();
    $this->_execRule($request, $response);
    return $response;
  }

  public function checkRules($request, $response)
  {
    $this->_init();
    return $this->_checkRules($request, $response);
  }

  public function deleteMatchedRule()
  {
    if($this->_ruleKey !== -1)
    {
      $rules = Configure::read('Trois.cache.rules');
      unset($rules[$this->_ruleKey]);
      Configure::write('Trois.cache.rules', $rules);
    }
    return false;
  }

  protected function _execRule($request, $response)
  {
    $rule = $this->_checkRules($request, $response);
    if($rule){
      if($rule['clear'] && !$rule['skip']){
        if(is_array($rule['key'])){
          foreach($rule['key'] as $key){
            $this->_deleteCache($key, $rule);
          }
        }else{
          $this->_deleteCache($rule['key'], $rule);
        }
      }else if(!$rule['clear'] && !$rule['skip']){
        if(is_array($rule['key'])){
          foreach($rule['key'] as $key){
            $this->_writeCache($key, $response->body(), $rule);
          }
        }else{
          $this->_writeCache($rule['key'], $response->body(), $rule);
        }
      }
    }
  }

  protected function _deleteCache($key, $rule)
  {
    if($key == '*')
      return Cache::clear(false, $rule['cache']);

    Cache::delete($key, $rule['cache']);
  }

  protected function _writeCache($key, $content, $rule)
  {
    $content = ($rule['compress'])? $this->_compress($content): $content;
    Cache::write($key, $content, $rule['cache']);
  }

  protected function compressor()
  {
    return (!$this->_compressor)? $this->_compressor = new Compressor(): $this->_compressor;
  }

  protected function _compress($content)
  {
    //return preg_replace(array('/<!--(.*)-->/Uis',"/[[:blank:]]+/"),array('',' '),str_replace(array("\n","\r","\t"),'',$content));
    return $this->compressor()->compress($content);
  }

  protected function _checkRules($request, $response)
  {
    $rules = $this->config('rules');
    foreach ($rules as $key => $rule) {
      $rule = $this->_matchRule($rule, $request, $response);
      if ($rule !== null) {
        $this->_ruleKey = $key;
        return $rule;
      }
    }
    return false;
  }

  protected function _matchRule($rule, $request, $response)
  {
    $method = $request->getMethod();
    $plugin = $request->plugin;
    $controller = $request->controller;
    $action = $request->action;
    $code = $response->statusCode();
    $prefix = null;
    $extension = null;
    if (!empty($request->params['prefix'])) {
      $prefix = $request->params['prefix'];
    }
    if (!empty($request->params['_ext'])) {
      $extension = $request->params['_ext'];
    }

    if ($this->_matchOrAsterisk($rule, 'method', $method, true) &&
    $this->_matchOrAsterisk($rule, 'code', $code, true) &&
    $this->_matchOrAsterisk($rule, 'prefix', $prefix, true) &&
    $this->_matchOrAsterisk($rule, 'plugin', $plugin, true) &&
    $this->_matchOrAsterisk($rule, 'extension', $extension, true) &&
    $this->_matchOrAsterisk($rule, 'controller', $controller) &&
    $this->_matchOrAsterisk($rule, 'action', $action)) {

      $rule = [
        'skip' => Hash::get($rule, 'skip'),
        'cache' => Hash::get($rule, 'cache'),
        'clear' => Hash::get($rule, 'clear'),
        'key' => Hash::get($rule, 'key'),
        'compress' => Hash::get($rule, 'compress'),
      ];
      foreach($rule as $key => &$value){
        $value = $this->_getRuleBoolProperty($request, $rule, $key);
      }
      return $rule;
    }
    return null;
  }

  protected function _getRuleBoolProperty($request, $rule, $key)
  {
    $prop = $rule[$key];
    if ($prop === null) {
      //clear will be true by default
      switch($key)
      {
        case 'cache':
          return $this->config('settings')['default'];

        case 'key':
          return $request->here();

        case 'compress':
          return true;

        default:
          return false;
      }
    } elseif (is_callable($prop)) {
      return call_user_func($prop,$request);
    } else {
      return $prop;
    }
  }

  protected function _matchOrAsterisk($permission, $key, $value, $allowEmpty = false)
  {
    $possibleValues = (array)Hash::get($permission, $key);
    if ($allowEmpty && empty($possibleValues) && $value === null) {
      return true;
    }
    if (Hash::get($permission, $key) === '*' ||
    in_array($value, $possibleValues) ||
    in_array(Inflector::camelize($value, '-'), $possibleValues)) {
      return true;
    }
    //debug($key.': '.$value);
    return false;
  }

}
