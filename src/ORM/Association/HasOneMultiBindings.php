<?php
namespace Trois\Utils\ORM\Association;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Query;
use Cake\ORM\Association\HasOne;

class HasOneMultiBindings extends HasOne
{
  protected function _options(array $options): void
  {
    $this->_multiBindings = empty($options['multiBindings'])? []: $options['multiBindings'];
  }

  protected function extractBindings()
  {
    $bindigns = [];
    if(!empty($this->_multiBindings))
    {
      foreach($this->_multiBindings as $field => $cond)
      {
        if(is_callable($cond)) $bindigns[$field] = $cond();
        else if(is_numeric($field)) $bindigns[] = $cond();
        else $bindigns[$field] = $cond;
      }
    }
    return $bindigns;
  }

  public function attachTo(Query $query, array $options = []): void
  {
    // Logic
    $options += [
      'conditions' => $this->extractBindings()
    ];

    parent::attachTo($query, $options);
  }

  public function saveAssociated(EntityInterface $entity, array $options = [])
  {
    $targetEntity = $entity->get($this->getProperty());
    if (empty($targetEntity) || !($targetEntity instanceof EntityInterface)) {
      return $entity;
    }

    // VARS
    $sAlias = $this->getSource()->getAlias();
    $tAlias = $this->getTarget()->getAlias();
    $bindigns = $this->extractBindings();
    $targetBindigs = array_filter($bindigns, function($itm, $key) use($tAlias)
    {
      if(is_numeric($key)) return false;
      return strpos($key, $tAlias) !== false;
    }, ARRAY_FILTER_USE_BOTH);
    $sourceBindigs = array_filter($bindigns, function($itm, $key) use($sAlias)
    {
      if(is_numeric($key)) return false;
      return strpos($key, $sAlias) !== false;
    }, ARRAY_FILTER_USE_BOTH);

    // target things to Add
    $targetProps = [];
    if(!empty($targetBindigs))
    {
      $targetProps = [];
      array_walk($targetBindigs, function($item, &$key) use ($tAlias, &$targetProps)
      {
        $key = substr($key, strlen($tAlias) + 1);
        $targetProps[$key] = $item;
      });
    }

    $targetProps += array_combine(
      (array)$this->getForeignKey(),
      $entity->extract((array)$this->getBindingKey())
    );
    $targetEntity->set($targetProps, ['guard' => false]);
    if (!$this->getTarget()->save($targetEntity, $options)) {
      $targetEntity->unset(array_keys($targetProps));
      return false;
    }

    // source things to Add
    if(!empty($sourceBindigs))
    {
      $sourceProps = [];
      array_walk($sourceBindigs, function($item, &$key) use ($sAlias, &$sourceProps)
      {
        $key = substr($key, strlen($sAlias) + 1);
        $sourceProps[$key] = $item;
      });
      $entity->set($sourceProps, ['guard' => false]);
    }

    return $entity;
  }

  public function cascadeDelete(EntityInterface $entity, array $options = []): bool
  {
    if (!$this->getDependent()) {
      return true;
    }

    // VARS
    $tAlias = $this->getTarget()->getAlias();
    $tPrimaryKey = $this->getTarget()->getPrimaryKey();

    // LOGIC
    $foreignKey = (array)$this->getForeignKey();
    foreach($foreignKey as &$key) $key = "$tAlias.$key";
    $bindingKey = (array)$this->getBindingKey();
    $primaryConditions = array_combine($foreignKey, $entity->extract($bindingKey));

    $conditions = array_merge($primaryConditions + $this->extractBindings());

    // QUERY
    if(
      !$result = $this->getSource()->find()
      ->select(["$tAlias.$tPrimaryKey"])->contain([$tAlias])->where($conditions)->first()
      ) return true;

      // CHECK AND SET TARGET
      if(!$related = $result->{$this->_propertyName}) return true;
      $relatedId = $related->{$tPrimaryKey};


      // DELETE
      $table = $this->getTarget();
      if ($this->getCascadeCallbacks())
      {
        if (!$success = $table->delete($related, $options)) return false;
        return true;
      }

      // via where clause
      $table->deleteAll($primaryConditions);
      return true;
    }
  }
