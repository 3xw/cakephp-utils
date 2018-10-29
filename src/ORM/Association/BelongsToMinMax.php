<?php
namespace Trois\Utils\ORM\Association;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;
use RuntimeException;

class BelongsToMinMax extends HasOneMinMax
{
  protected $_junctionTableName;

  protected $_targetForeignKey;

  public function __construct($alias, array $options = [])
  {
    parent::__construct($alias, $options);

    if(empty($options['targetForeignKey']) || empty($options['joinTable'])) throw new RuntimeException('You must provide a targetForeignKey and a  joinTablefor BelongsToMinMax Association');
    $this->_targetForeignKey = $options['targetForeignKey'];
    $this->_junctionTableName = $options['joinTable'];
  }

  public function attachTo(Query $query, array $options = [])
  {

    try {
      // Source
      $sAlias = $this->getSource()->getAlias();
      $sKey = $this->getSource()->getPrimaryKey();

      // Traget
      $tAlias = $this->getTarget()->getAlias();
      $tName = $this->getTarget()->getTable();
      $tKey = $this->getTarget()->getPrimaryKey();
      $tfKey = $this->_targetForeignKey;

      // Join sub query
      $jName = $this->_junctionTableName;
      $jAlias = strtoupper(substr($sAlias,0,1).substr($tName,0,1));
      $jTable = TableRegistry::get($jAlias, ['table' => $jName]);
      $fKey = $this->getForeignKey();

      // additionnal join
      $joinType = empty($options['joinType']) ? $this->getJoinType() : $options['joinType'];
      $joinAlias = 'T'.$this->getTarget()->getAlias();

      // Logic
      $field = $this->_field;

      // CREATE JOIN
      $subquery = $jTable->find();
      $subquery->select([$tfKey => "$jAlias.$tfKey",$fKey => "$jAlias.$fKey"]);

      // target table JOIN
      $fields = array_diff($this->getTarget()->getSchema()->columns(),[$field]);
      foreach($fields as $f) $subquery->select([$f => "$joinAlias.$f"]);
      $subquery
      ->select([
        $field => ($this->_type)? $subquery->func()->max("$joinAlias.$field"): $subquery->func()->min("$joinAlias.$field")
      ])
      ->join([
        'table' => $tName,
        'alias' => $joinAlias,
        'type' => $this->_joinType,
        'conditions' => "$jAlias.$tfKey = $joinAlias.$tKey",
      ])
      ->group(["$jAlias.$fKey"]);

      // associate...
      $query->join([
        'table' => $subquery,
        'alias' => $tAlias,
        'type' => $this->_joinType,
        'conditions' => "$tAlias.$fKey = $sAlias.$sKey",
      ]);

      $options += [
          'includeFields' => true,
          'foreignKey' => $this->getForeignKey(),
          'conditions' => [],
          'fields' => [],
          'type' => $joinType,
          'table' => $tName,
          'finder' => $this->getFinder()
      ];

      // add options and field to response
      list($finder, $opts) = $this->_extractFinder($options['finder']);
      $dummy = $this->find($finder, $opts)->eagerLoaded(true);
      $dummy->where($options['conditions']);
      $this->_dispatchBeforeFind($dummy);
      $options['conditions'] = $dummy->clause('where');
      $this->_appendFields($query, $dummy, $options);

    } catch (\Exception $e) {
      debug($e);
    }
  }

}
