<?php
namespace Trois\Utils\Listener;

use Cake\Event\Event;

interface ListenerInterface
{
  public function respond(Event $event);
}
