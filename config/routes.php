<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

// set plugin stuff : )
Router::plugin(
    'Trois/Utils',
    ['path' => '/utils'],
    function (RouteBuilder $routes)
    {
      $routes->setExtensions(['json']);
      $routes->fallbacks('DashedRoute');
    }
);
