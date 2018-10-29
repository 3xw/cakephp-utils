<?php
namespace Trois\Utils\ORM\Association;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Association\DependentDeleteHelper;
use Cake\ORM\Association\Loader\SelectLoader;
use Cake\Utility\Inflector;
use RuntimeException;

class HasOneMinMax extends Association {

  protected $_type = 1;

  protected $_types = ['MIN(`%s`)','MAX(`%s`)'];

  protected $_field;

  public function __construct($alias, array $options = [])
  {
    parent::__construct($alias, $options);

    // Max Or Min!
    $this->_type = (!empty($options['type']) && $options['type'] == 'MIN')? 0: 1;

    // field
    if(empty($options['field'])) throw new RuntimeException('You must provide a field for HasOneMinMax Association');
    $this->_field = $options['field'];
  }

  public function attachTo(Query $query, array $options = [])
  {

    // Source
    $sAlias = $this->getSource()->getAlias();
    $sKey = $this->getSource()->getPrimaryKey();

    // Traget
    $tAlias = $this->getTarget()->getAlias();
    $tName = $this->getTarget()->getTable();
    $joinAlias = 'MinMax'.$this->getTarget()->getAlias();
    $fKey = $this->getForeignKey();

    // Logic
    $field = $this->_field;
    $logic = sprintf($this->_types[$this->_type], $field);

    $query->join([
      'table' =>"(SELECT $logic AS `$field`, $fKey FROM $tName GROUP BY $fKey )",
      'alias' => $joinAlias,
      'type' => $this->_joinType,
      'conditions' => "$joinAlias.$fKey = $sAlias.$sKey",
    ]);

    $options += [
      'conditions' => [
        "$joinAlias.$field = $tAlias.$field"
      ]
    ];

    parent::attachTo($query, $options);
  }

  public function type()
  {
    return self::ONE_TO_ONE;
  }

  protected function _propertyName()
  {
      list(, $name) = pluginSplit($this->_name);

      return Inflector::underscore(Inflector::singularize($name));
  }

  public function eagerLoader(array $options)
  {
    $loader = new SelectLoader([
      'alias' => $this->getAlias(),
      'sourceAlias' => $this->getSource()->getAlias(),
      'targetAlias' => $this->getTarget()->getAlias(),
      'foreignKey' => $this->getForeignKey(),
      'bindingKey' => $this->getBindingKey(),
      'strategy' => $this->getStrategy(),
      'associationType' => $this->type(),
      'sort' => $this->getSort(),
      'finder' => [$this, 'find']
    ]);

    return $loader->buildEagerLoader($options);
  }

  public function cascadeDelete(EntityInterface $entity, array $options = [])
  {
    $helper = new DependentDeleteHelper();
    return $helper->cascadeDelete($this, $entity, $options);
  }

  public function isOwningSide(Table $side)
  {
    return $side === $this->getSource();
  }

  public function saveAssociated(EntityInterface $entity, array $options = [])
  {
    return $entity;
  }
}
