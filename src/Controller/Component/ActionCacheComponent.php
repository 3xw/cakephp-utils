<?php
namespace Trois\Utils\Controller\Component;

use Trois\Utils\Middleware\ResponseCacheMiddleware;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Event\Event;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component;

/**
* Cache component
*/
class ActionCacheComponent extends Component
{
  protected $_controller;

  protected $_defaultConfig = [
    'skip_debug' => true
  ];

  public function __construct(ComponentRegistry $collection, $config = [])
  {
    $this->_controller = $collection->getController();
    parent::__construct($collection, $config);
  }

  public function startup(Event $event)
  {
    if($this->config('skip_debug') && Configure::read('debug'))
      return true;

    $rcm = new ResponseCacheMiddleware();
    $response = new Response();
    if(!empty($this->_controller->request->params['_ext'])) $response->type($this->_controller->request->params['_ext']);
    $response->statusCode('200');
    $rule = $rcm->checkRules($this->_controller->request, $response);

    // if no rule exit
    if(empty($rule)) return true;

    // look for a cache response
    if(!$rule['clear'] && !$rule['skip'])
    {
      $body = Cache::read($this->_controller->request->here(), $rule['cache']);

      // no cache found
      if(empty($body)) return true;

      // cache found ! do not re write it..
      $rcm->deleteMatchedRule();

      // serve cache
      $response->body($body);
      return $response;
    }

    // no cache
    return true;
  }
}
