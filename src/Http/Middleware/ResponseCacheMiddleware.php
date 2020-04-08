<?
namespace Trois\Utils\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Cache\Cache;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Trois\Utils\Utility\Html\Compressor;
use Trois\Utils\Utility\Http\RequestMatchRule;

class ResponseCacheMiddleware implements MiddlewareInterface
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
      'settings' => [],
      'rules' => [],
  ];

  protected $_ruleKey = -1;

  protected $_compressor;

  public function __construct(array $config = [])
  {
    $this->setConfig($config);
  }

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $response = $handler->handle($request);
    $this->_init();
    $this->_execRule($request, $response);

    return $response;
  }

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

  public function checkRules($request)
  {
    $this->_init();
    return $this->_checkRules($request);
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
    $rule = $this->_checkRules($request);
    if($rule)
    {
      // setup rule default
      $rule = array_merge($rule, [
        'skip' => Hash::get($rule, 'skip'),
        'cache' => Hash::get($rule, 'cache'),
        'clear' => Hash::get($rule, 'clear'),
        'key' => Hash::get($rule, 'key'),
        'compress' => Hash::get($rule, 'compress'),
      ]);
      foreach($rule as $key => &$value) $value = $this->_getRuleBoolProperty($request, $rule, $key);

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

  protected function _checkRules($request)
  {
    return (new RequestMatchRule())->checkRules($this->getConfig('rules'), $request);
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
}
