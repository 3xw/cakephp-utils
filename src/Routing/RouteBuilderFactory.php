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

  static public function create($key, $config, $addJsonExtention)
  {
    $prefix = new self();
    $prefix->key = is_string($config)? $config: $key;

    // merge
    if(!is_string($config)) foreach($config as $key => $value) $prefix->{$key} = $value;
    
    // add Json
    if($addJsonExtention) $prefix->extensions[] = 'json';
    
    return $prefix;
  }
}

class RouteBuilderFactory
{
  public static function build($prefixes = [], $addJsonExtention = true)
  {
    return static function (RouteBuilder $routes) use($prefixes, $addJsonExtention) {
      
      $routes->setRouteClass(DashedRoute::class);
    
      foreach($prefixes as $key => $config)
      {
        $prefix = Prefix::create($key, $config, $addJsonExtention);
        $routes->{$prefix->method}($prefix->key, function (RouteBuilder $builder) use ($prefix)
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
