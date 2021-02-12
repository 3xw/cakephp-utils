<?php
namespace Trois\Utils\ORM\Traits;

use Trois\Utils\ORM\Association\HasOneMinMax;
use Trois\Utils\ORM\Association\BelongsToMinMax;
use Trois\Utils\ORM\Association\HasOneMultiBindings;

trait AssocationsTrait
{
  public function HasOneMultiBindings($associated, array $options = [])
  {
    $options += ['sourceTable' => $this];
    $association = $this->_associations->load(HasOneMultiBindings::class, $associated, $options);
    return $association;
  }

  public function HasOneMinMax($associated, array $options = [])
  {
    $options += ['sourceTable' => $this];
    $association = $this->_associations->load(HasOneMinMax::class, $associated, $options);
    return $association;
  }

  public function BelongsToMinMax($associated, array $options = [])
  {
    $options += ['sourceTable' => $this];
    $association = $this->_associations->load(BelongsToMinMax::class, $associated, $options);
    return $association;
  }
}
