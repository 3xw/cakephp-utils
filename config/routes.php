<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

// set plugin stuff : )
Router::plugin('Trois/Utils',['path' => '/utils'],function (RouteBuilder $routes){
  $routes->fallbacks('DashedRoute');
});

Router::connect('/auth/two-factor', [
  'prefix' => false,
  'controller' => 'TwoFactorAuth',
  'action' => 'verify',
  'plugin' => 'Trois/Utils',
]);
