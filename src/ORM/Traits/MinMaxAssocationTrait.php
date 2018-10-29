<?php
namespace Trois\Utils\ORM\Traits;

use Trois\Utils\ORM\Association\HasOneMinMax;
use Trois\Utils\ORM\Association\BelongsToMinMax;

trait MinMaxAssocationTrait
{
  public function hasOneMinMax($associated, array $options = [])
  {
    $options += ['sourceTable' => $this];
    $association = $this->_associations->load(HasOneMinMax::class, $associated, $options);
    return $association;
  }

  public function belongsToMinMax($associated, array $options = [])
  {
    $options += ['sourceTable' => $this];
    $association = $this->_associations->load(BelongsToMinMax::class, $associated, $options);
    return $association;
  }
}
