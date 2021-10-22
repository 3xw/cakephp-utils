<?php
namespace Trois\Utils;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;
use Cake\Console\CommandCollection;
use Cake\Http\MiddlewareQueue;

class Plugin extends BasePlugin
{
  public function middleware(MiddlewareQueue $middleware): MiddlewareQueue
  {
    // Add middleware here.
    return $middleware;
  }

  public function console(CommandCollection $commands): CommandCollection
  {
    return $commands->add('tu_token', \Trois\Utils\Command\TokenCommand::class);
  }

  public function routes(RouteBuilder $routes): void
  {
    parent::routes($routes);
  }
}
