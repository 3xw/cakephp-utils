<?php
namespace Trois\Utils\Routing;

use Cake\Routing\RouteBuilder;

class Mapper
{
  static public function mapRessources($resources = [], RouteBuilder $builder, $prefix = null)
  {
    $options = ['inflect' => 'dasherize', 'prefix' => $prefix];

    // loop ressources
    foreach($resources as $key => $value)
    {
      // etract options and resource(s)
      list($opts, $res) = self::extractOptionsAndRessources($value, $options);

      // if no prefix then set first parent builders
      if(!$prefix)
      {
        if(is_numeric($key)) $builder->resources($res, $opts);
        else $builder->resources($key, $opts, self::mapRessources($res, $builder, $key));
      }
      else // if prefix return function !
      {
        return function (RouteBuilder $builder) use($prefix, $key, $opts, $res)
        {
          if(is_numeric($key)) $builder->resources($res, $opts);
          else $builder->resources($key, $opts, self::mapRessources($res, $builder, $prefix.'/'.$key));
        };
      }
    }
  }

  static public function extractOptionsAndRessources($res, $options = [])
  {
    if(!is_array($res)) return [$options, $res];

    $optionKeys = ['id','inflect','only', 'actions', 'map','prefix','connectOptions', 'path'];
    $resources = [];
    foreach($res as $key => $value)
    {
      if(!is_numeric($key) && in_array($key, $optionKeys)) $options[$key] = $value;
      else $resources[$key] = $value;
    }

    return [$options, $resources];
  }
}
