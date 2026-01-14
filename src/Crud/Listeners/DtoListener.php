<?php
namespace Trois\Utils\Crud\Listeners;

use Cake\Event\Event;
use Cake\Http\ServerRequest;

use Crud\Error\ExceptionRenderer;
use Crud\Listener\BaseListener;

class DtoListener extends BaseListener
{
  use \Trois\Utils\Dto\DtoTrait;

  protected array $_defaultConfig = [
    'actionsDtos' => []
  ];

  public function implementedEvents(): array
  {
    return [
      'Crud.beforeHandle' => ['callable' => [$this, 'beforeHandle'], 'priority' => 10],
    ];
  }

  public function beforeHandle(Event $event)
  {
    $action = $event->getSubject()->action;
    $actionsDtos = $this->getConfig('actionsDtos');

    if(empty($actionsDtos[$action])) return;
    
    $dtos = $actionsDtos[$action];
    $dtos = is_array($dtos)? $dtos: [$dtos];

    $result;
    foreach($dtos as $dto)
    {
      $result = $this->dtoParse($this->_request(), $dto);
      if(is_array($result)) break;
    }

    if(!is_array($result)) throw $result;

    // replace data with DTO
    $this->_controller->setRequest($this->_request()->withParsedBody($result));
  }
}
