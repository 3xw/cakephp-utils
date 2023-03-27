<?php
namespace Trois\Utils\Routing;

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

class Prefix {
  public $key = '';
  public $method = 'prefix';
  public $middlewares = [];
  public $connections = [];
  public $ressources = [];
  public $extensions = [];

  static public function create($key, $config)
  {
    $prefix = new self();
    $prefix->key = is_string($config)? $config: $key;
    if(is_string($config)) return $prefix;
    foreach($config as $key => $value) $prefix->{$key} = $value;

    return $prefix;
  }
}

class RouteBuilderFactory
{
  public static function build($prefixes = [])
  {
    return static function (RouteBuilder $routes) use($prefixes) {

      $routes->setRouteClass(DashedRoute::class);
    
      foreach($prefixes as $key => $config)
      {
        $prefix = Prefix::create($key, $config);
        $routes->{$prefix->method}($prefix->key, function (RouteBuilder $builder)
        {
    
          // Middleware
          if(!empty($prefix->middlewares)) foreach($prefix->middlewares as $mName => $m)
          {
            $builder->registerMiddleware($mName, $m);
            $builder->applyMiddleware($mName);
          }
    
          // Connections
          if(!empty($prefix->connections)) foreach($prefix->connections as $cName => $c) $builder->connect($cName, $c);
    
          // Ressources
          if(!empty($prefix->ressources)) Mapper::mapRessources($prefix->ressources, $builder);
    
          // Extensions
          if(!empty($prefix->extensions)) $builder->setExtensions($prefix->extensions);
    
          $builder->fallbacks();
        });
      }
    
    };
  }
}
