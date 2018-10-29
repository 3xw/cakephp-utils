<?php
namespace Trois\Utils\ORM\Rule;

use Cake\Datasource\EntityInterface;

class IsUniqueTranslationRule
{
  protected $_fields;
  protected $_options;

  public function __construct(array $fields, array $options = [])
  {
    $this->_fields = $fields;
    $this->_options = $options;
  }

  public function __invoke(EntityInterface $entity, array $options)
  {
    // check if transaltion
    $behavior = $options['repository']->behaviors()->get('Translate');
    if(!$behavior) return true;

    $association = $options['repository']->association($behavior->getConfig('translationTable'));

    $result = true;
    foreach($this->_fields as $field)
    {
      foreach ($entity->get('_translations') as $locale => $translation)
      {
        $conditions = [
          $association->aliasField('field') => $field,
          $association->aliasField('locale') => $locale,
          $association->aliasField('content') => $translation->get($field)
        ];
        if ($entity->isNew() === false) $conditions[$association->aliasField('foreign_key').' IS NOT'] = $entity->get('id');

        if ($association->exists($conditions))
        {
          $translation->setErrors([
            $field => [
              'uniqueTranslation' => __d('cake', 'This value is already in use')
            ]
          ]);
          $result = false;
        }
      }
    }
    return $result;
  }
}
