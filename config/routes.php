<?php
declare(strict_types=1);

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->plugin(
        'Trois/Utils',
        ['path' => '/utils'],
        function (RouteBuilder $builder): void {
            $builder->fallbacks(DashedRoute::class);
        }
    );

    $routes->connect('/auth/two-factor', [
        'prefix' => null,
        'controller' => 'TwoFactorAuth',
        'action' => 'verify',
        'plugin' => 'Trois/Utils',
    ]);
};
