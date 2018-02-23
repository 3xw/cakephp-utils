<?php
namespace Trois\Utils\Model\Behavior;

use Cake\ORM\Query;
use Cake\I18n\I18n;
use Cake\ORM\Behavior\TranslateBehavior as BaseBehavior;

class TranslateBehavior extends BaseBehavior
{
  public function findTranslations(Query $query, array $options)
  {
    $this->locale(I18n::defaultLocale());
    return parent::findTranslations($query,$options);
  }
}
