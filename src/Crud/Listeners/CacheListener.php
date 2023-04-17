<?php
namespace Trois\Utils\Crud\Listeners;

use Cake\Event\Event;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;

use Crud\Error\ExceptionRenderer;
use Crud\Listener\BaseListener;

use Trois\Utils\Cache\CacheHelper;

class CacheListener extends BaseListener
{
  protected $_defaultConfig = [
    'cache' => 'queries',
    'actions' => ['index','view']
  ];

  protected $key = null;
  protected $cache = null;
  protected $enabled = true;
  protected $helper = null;

  function __construct($controller)
  {
    parent::__construct($controller);
    $controller->Crud->mapAction('serveCache',['className' => 'App\Action\CacheAction']);
    $this->helper = new CacheHelper($this->getConfig());
  }

  public function implementedEvents(): array
  {
    return [
      'Crud.beforeHandle' => ['callable' => [$this, 'beforeHandle'], 'priority' => 10],
      'Crud.beforeRender' => ['callable' => [$this, 'beforeRender'], 'priority' => 10],
      'Crud.beforeRedirect' => ['callable' => [$this, 'beforeRender'], 'priority' => 10],
    ];
  }

  public function beforeHandle(Event $event)
  {
    if(!$this->enabled = in_array($event->getSubject()->action, $this->getConfig('actions'))) return;

    // check if cache exist
    if($this->cache = $this->getCache()) $event->getSubject()->set([
      'action' => 'serveCache',
      'args' => [$this->cache]
    ]);
  }

  public function getCache()
  {
    return $this->helper->getCacheForRequest($this->_request());
  }

  public function beforeRender(Event $event)
  {
    if(!$this->enabled) return;

    // store response elements
    if(!$this->cache && $event->getSubject()->success)
    {
      $data = ['success' => true];

      // pagination
      $request = $this->_request();
      if(!empty($request->getParam('paging'))) $data['paging'] = $request->getParam('paging');

      // content
      switch (true)
      {
        case property_exists($event->getSubject(), 'entities'):
          $data += ['entities' => $event->getSubject()->entities];
          break;

        case property_exists($event->getSubject(), 'entity'):
          $data += [
            'id' => $event->getSubject()->id,
            'entity' => $event->getSubject()->entity
          ];
          break;
      }

      $this->helper->storeDataForrequest($this->_request(), $data);
    }
  }
}
