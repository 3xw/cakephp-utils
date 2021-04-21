<?php
namespace Trois\Utils\Action;

use Crud\Action\IndexAction as BaseAction;
use Cake\Http\Response;

class ListAction extends BaseAction
{
  protected function _handle(): ?Response
  {
    [$finder, $options] = $this->_extractFinder();
    $query = $this->_table()->find($finder, $options);
    $subject = $this->_subject(['success' => true, 'query' => $query]);

    $this->_trigger('beforeFind', $subject);
    try {
      $items = $subject->query->toArray();
    } catch (NotFoundException $e) {
      $url = Router::reverseToArray($this->_request());
      return $this->_controller()->redirect($url);
    }

    $subject->set(['entities' => $items]);

    $this->_trigger('afterFind', $subject);
    $this->_trigger('beforeRender', $subject);

    return null;
  }
}
