<?php
namespace Trois\Utils\ORM\Association;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\Datasource\FactoryLocator;

use RuntimeException;

class BelongsToMinMax extends HasOneMinMax
{
  protected $_junctionTableName;

  protected $_targetForeignKey;

  protected $_joinExtraConditions;

  protected $_groupField;

  protected $_joinAlias;

  public function __construct($alias, array $options = [])
  {
    parent::__construct($alias, $options);

    if(empty($options['targetForeignKey']) || empty($options['joinTable'])) throw new RuntimeException('You must provide a targetForeignKey and a  joinTablefor BelongsToMinMax Association');
    $this->_targetForeignKey = $options['targetForeignKey'];
    $this->_junctionTableName = $options['joinTable'];

    // extra stuff
    $this->_joinExtraConditions = empty($options['joinExtraConditions'])? []: $options['joinExtraConditions'];
    $this->_groupField = empty($options['groupField'])? false: $options['groupField'];
    $this->_joinAlias = empty($options['joinAlias'])? false: $options['joinAlias'];
  }

  public function attachTo(Query $query, array $options = []): void
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
      $jAlias = $this->_joinAlias? $this->_joinAlias: strtoupper(substr($sAlias,0,1).substr($tName,0,1));
      $jTable = FactoryLocator::get('Table')->get($jAlias, ['table' => $jName, 'className' => 'Cake\ORM\Table']);
      $fKey = $this->getForeignKey();

      // additionnal join
      $joinType = empty($options['joinType']) ? $this->getJoinType() : $options['joinType'];
      $j2Alias = 'T'.$this->getTarget()->getAlias();

      // Logic
      $field = $this->_field;
      $logic = sprintf($this->_types[$this->_type], $field);

      // CREATE JOIN
      $subquery = $this->getTarget()->find();
      $subquery->select([$tfKey => "$jAlias.$tfKey",$fKey => "$jAlias.$fKey"]);

      // target table JOIN
      $fields = array_diff($this->getTarget()->getSchema()->columns(),[$field]);
      foreach($fields as $f) $subquery->select([$f => "$tAlias.$f"]);
      $subquery
      ->select([$field => "$tAlias.$field"])
      ->join([
        'table' => $jName,
        'alias' => $jAlias,
        'type' => $this->_joinType,
        'conditions' => ["$jAlias.$tfKey = $tAlias.$tKey"] + $this->_joinExtraConditions,
      ])
      ->group([
        //"$tAlias.$tKey",
        //"$jAlias.$tfKey",
        // Only on association should match
        "$jAlias.$fKey"
      ]);

      if($this->_groupField)
      {
        $subquery->join([
          'table' =>"(SELECT $logic AS `$field`, $this->_groupField FROM $tName GROUP BY $this->_groupField )",
          'alias' => 'MinMax',
          'type' => 'INNER',
          'conditions' => [
            "MinMax.$field = $tAlias.$field",
            "MinMax.$this->_groupField = $tAlias.$this->_groupField"
          ],
        ]);
      }
      else
      {
        $subquery->join([
          'table' =>"(SELECT $logic AS `$field`FROM $tName )",
          'alias' => 'MinMax',
          'type' => 'INNER',
          'conditions' => [
            "MinMax.$field = $tAlias.$field"
          ],
        ]);
      }

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
