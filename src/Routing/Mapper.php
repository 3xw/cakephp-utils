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
        else
        {
          if(is_array($res)) list($opts) = self::extractOptionsAndRessources($res, $opts);
          $builder->resources($key, $opts, self::mapRessources($res, $builder, $key)); // SCOPED
        }
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
          else
          {
            if(is_array($r)) list($opts) = self::extractOptionsAndRessources($r, $opts);
            $builder->resources($key, $opts, self::mapRessources($r, $builder, $prefix.'/'.$key));
          }
        }
      };
    }
  }
  /*
  static public function mapRessources($resources = [], RouteBuilder $builder, $prefix = null)
  {
    // options
    $options = ['inflect' => 'dasherize', 'prefix' => $prefix];
    list($options, $resources) = self::extractOptionsAndRessources($resources, $options);

    foreach($resources as $key => $resource)
    {
      // simple mapping
      if(is_string($key))
      {
        if(is_array($resource))
        {
          list($opts) = self::extractOptionsAndRessources($resource, $options);
          $builder->resources($key, $opts, self::mapRessources($resource, $builder, $prefix? $prefix.'/'.$key: $key));
        }
        else $builder->resources($key, $options);
      }
      if(is_numeric($key))
      {
        if(is_array($resource)) self::mapRessources($resource, $builder, $prefix? $prefix.'/'.$key: $key);
        else $builder->resources($resource, $options);
      }
    }
  }
  */

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
