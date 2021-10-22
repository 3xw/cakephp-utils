<?php
namespace Trois\Utils\Routing;

use Cake\Routing\RouteBuilder;

class Mapper
{
  static public function mapRessources($resources = [], RouteBuilder $builder, $prefix = null)
  {
    $options = ['inflect' => 'dasherize', 'prefix' => $prefix];

    // LEVEL 0: void
    if(!$prefix)
    {
      foreach($resources as $key => $value)
      {
        // extract options and resource(s)
        list($opts, $res) = self::extractOptionsAndRessources($value, $options);

        // build path for ressource
        if(is_numeric($key)) $builder->resources($res, $opts); // NO SCOPE
        else $builder->resources($key, $opts, self::mapRessources($res, $builder, $key)); // SCOPED
      }
    }
    else // LEVEL > 0: callable
    {
      // extract options and resource(s)
      list($opts, $res) = self::extractOptionsAndRessources($resources, $options);

      // build path(s) for ressource(s)
      return function (RouteBuilder $builder) use($prefix, $opts, $res)
      {
        foreach($res as $key => $r)
        {
          if(is_numeric($key)) $builder->resources($r, $opts);
          else $builder->resources($key, $opts, self::mapRessources($r, $builder, $prefix.'/'.$key));
        }
      };
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
