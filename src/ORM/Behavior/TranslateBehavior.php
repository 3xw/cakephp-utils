<?php
namespace Trois\Utils\ORM\Behavior;

use Cake\ORM\Query;
use Cake\I18n\I18n;
use Cake\ORM\Behavior\TranslateBehavior as BaseBehavior;

class TranslateBehavior extends BaseBehavior
{
  public function findTranslations(Query $query, array $options): Query
  {
    $this->setLocale(I18n::getDefaultLocale());
    return parent::findTranslations($query,$options);
  }
}
