<?php
namespace Trois\Utils\Listener;

use Cake\Core\InstanceConfigTrait;
use Cake\Event\Event;

class BaseListener implements ListenerInterface
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [  ];

  public function __construct($config = [])
  {
    $this->setConfig($config);
  }

  public function respond(Event $event): void {}
}
