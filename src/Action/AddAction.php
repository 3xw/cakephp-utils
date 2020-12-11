<?php
namespace Trois\Utils\Action;

use Crud\Action\AddAction as BaseAction;

class AddAction extends BaseAction
{
  protected function _post()
  {
    // before marshall
    $subject = $this->_subject([
      'data' => $this->_request()->getData(),
      'saveOptions' => $this->saveOptions(),
    ]);
    $event = $this->_trigger('beforeMarshal', $subject);
    if ($event->isStopped()) {
      return $this->_stopped($subject);
    }

    // before Save
    $subject = $this->_subject([
      'entity' => $this->_entity($subject->data, $subject->saveOptions),
      'saveMethod' => $this->saveMethod(),
      'saveOptions' => $subject->saveOptions,
    ]);

    $event = $this->_trigger('beforeSave', $subject);
    if ($event->isStopped()) {
      return $this->_stopped($subject);
    }

    $saveCallback = [$this->_table(), $subject->saveMethod];
    if (call_user_func($saveCallback, $subject->entity, $subject->saveOptions)) {
      return $this->_success($subject);
    }

    $this->_error($subject);
  }
}
