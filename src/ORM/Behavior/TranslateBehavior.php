<?php
namespace Trois\Utils\ORM\Behavior;

use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use Cake\I18n\I18n;
use Cake\ORM\Behavior\TranslateBehavior as BaseBehavior;

class TranslateBehavior extends BaseBehavior
{
  public function findTranslations(SelectQuery $query, array $options): SelectQuery
  {
    $this->setLocale(I18n::getDefaultLocale());
    return parent::findTranslations($query, $options);
  }
}
