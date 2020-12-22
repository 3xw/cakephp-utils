<?php
declare(strict_types=1);

namespace Trois\Utils\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Event\EventInterface;
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait;

use Trois\Utils\Listener\ListenerInterface;

class EventDispatcherComponent extends Component
{
  use EventDispatcherTrait;

  protected $_defaultConfig = [
    'listeners' => []
  ];

  public function __construct(ComponentRegistry $collection, $config = [])
  {
    parent::__construct($collection, $config);
    $this->_setEventListeners();
  }

  // basic calls
  public function beforeFilter(EventInterface $event) { $this->getEventManager()->dispatch($event); }

  public function startup(EventInterface $event) { $this->getEventManager()->dispatch($event); }

  public function beforeRender(EventInterface $event) { $this->getEventManager()->dispatch($event); }

  public function shutdown(EventInterface $event) { $this->getEventManager()->dispatch($event); }

  public function beforeRedirect(EventInterface $event, $url, Response $response)
  {
    $this->dispatchEvent('beforeRedirect',[
      'url' => $url,
      'response' => $response,
    ]);
  }

  protected function _setEventListeners()
  {
    foreach($this->getConfig('listeners') as $eventName => $listeners) $this->getEventManager()->on($eventName, [$this, 'respond']);
  }

  public function respond(Event $event)
  {
    // check if exists
    $name = $event->getName();
    if(!array_key_exists($name, $this->getConfig('listeners'))) return;

    // add request to event
    $subject = clone $event->getSubject();
    $listeners = $this->getConfig('listeners')[$name];

    // exec listeners
    foreach($listeners as $key => $value)
    {
      $config = is_array($value)? $value: [];
      $listener = is_array($value)? $key: $value;
      $this->callListenerResponderMethod($listener, $config, $name, $subject);
    }
  }

  protected function callListenerResponderMethod(ListenerInterface $listener, array $config, string $name, $subject): void
  {
    (new $listener($config))->respond(new Event($name, $subject));
  }
}
