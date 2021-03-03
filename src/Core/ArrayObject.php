<?php
namespace Trois\Utils\Core;

use ArrayObject as Dady;

class ArrayObject extends Dady
{
  /**
  * Take self and turn it into an associative array
  *
  * @return array
  */
  public function getArrayCopyRecursive():array
  {
    return $this->getArray($this);
  }

  /**
  * Public wrapper for Protected getArray()
  *
  * @return array
  */
  public function toArray(): array
  {
    return $this->getArray($this->object);
  }

  /**
  * Take an ArrayObject and turn it into an associative array
  *
  * @param ArrayObject $obj
  *
  * @return array
  */
  protected function getArray($obj): array
  {
    $array  = array(); // noisy $array does not exist
    $arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
    foreach ($arrObj as $key => $val) {
      $val = (is_array($val) || is_object($val)) ? $this->getArray($val) : $val;
      $array[$key] = $val;
    }
    return $array;
  }
}
