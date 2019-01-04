<?php
namespace Trois\Utils;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;

class Plugin extends BasePlugin
{
  public function middleware($middleware)
  {
    // Add middleware here.
    return $middleware;
  }

  public function console($commands)
  {
    // Add console commands here.
    return $commands;
  }

  public function routes($routes)
  {
    parent::routes($routes);
  }
}
