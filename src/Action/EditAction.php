<?php
namespace Trois\Utils\Action;

use Crud\Action\EditAction as BaseAction;

class EditAction extends BaseAction
{
  protected function _put(?string $id = null)
  {
    $subject = $this->_subject();
    $subject->set(['id' => $id]);

    // before marshall
    $bmSubject =$this->_subject([
      'data' => $this->_request()->getData(),
      'saveOptions' => $this->saveOptions(),
    ]);
    $event = $this->_trigger('beforeMarshal', $bmSubject);
    if ($event->isStopped()) {
      return $this->_stopped($bmSubject);
    }

    $entity = $this->_table()->patchEntity(
      $this->_findRecord($id, $subject),
      $bmSubject->data,
      $bmSubject->saveOptions
    );

    $this->_trigger('beforeSave', $subject);
    if (call_user_func([$this->_table(), $this->saveMethod()], $entity, $bmSubject->saveOptions)) {
      return $this->_success($subject);
    }

    $this->_error($subject);
  }
}
