<?php
namespace Trois\Utils\Action;

use Crud\Event\Subject;
use Crud\Action\BaseAction;
use Crud\Traits\ViewVarTrait;

class CacheAction extends BaseAction
{
  use ViewVarTrait;

  protected function _handle($args = null)
  {
    
    // set scope
    $this->setConfig('scope', empty($args['id'])? 'table': 'entity');
    /*
    // set paging
    if(!empty($args['paging']))
    {
      $this->_controller->request->params['paging'] = $args['paging'];
      unset($args['paging']);
    }
    */
    //debug($this->_subject($args));
    $this->_trigger('beforeRender', $this->_subject($args));
  }
}
