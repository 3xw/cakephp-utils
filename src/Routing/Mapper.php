<?php
namespace Trois\Utils\Routing;

use Cake\Routing\RouteBuilder;

class Mapper
{
  static public function mapRessources($resources = [], RouteBuilder $builder)
  {
    foreach($resources as $key => $value)
    {
      if(is_numeric($key)) $builder->resources($value,['inflect' => 'dasherize']);
      else $builder->resources($key, self::getNestedRessource($value, $builder, $key));
    }
  }

  static public function getNestedRessource($resources = [], RouteBuilder $builder, $prefix)
  {
    //debug($resources);
    return function (RouteBuilder $builder) use($prefix, $resources)
    {
      foreach($resources as $key => $value)
      {
        if(is_numeric($key)) $builder->resources($value, ['prefix' => $prefix]);
        else $builder->resources($key, ['prefix' => $prefix], self::getNestedRessource($value, $builder, $prefix.'/'.$key));
      }
    };
  }
}
