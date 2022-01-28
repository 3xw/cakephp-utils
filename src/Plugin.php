<?php
namespace Trois\Utils;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;
use Cake\Console\CommandCollection;
use Cake\Http\MiddlewareQueue;
use Cake\Core\InstanceConfigTrait;

use Trois\Utils\Http\Middleware\ServeCacheResponseMiddleware;
use Trois\Utils\Http\Middleware\ResponseCacheMiddleware;

class Plugin extends BasePlugin
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
    'middleware' => [
      'serveCache' => false,
      'responseCache' => false,
    ]
  ];

  public function __construct(array $options = [])
  {
    parent::__construct($options);
    $this->setConfig($options);
  }

  public function middleware(MiddlewareQueue $middleware): MiddlewareQueue
  {
    // cache
    if($this->getConfig('middleware.serveCache')) $middleware = $middleware->add(ServeCacheResponseMiddleware::class);
    if($this->getConfig('middleware.responseCache')) $middleware = $middleware->add(ResponseCacheMiddleware::class);

    return $middleware;
  }

  public function console(CommandCollection $commands): CommandCollection
  {
    return $commands
    ->add('tu_miss_i18n', \Trois\Utils\Shell\MissingTranslationsShell::class)
    ->add('tu_token', \Trois\Utils\Command\TokenCommand::class);
  }

  public function routes(RouteBuilder $routes): void
  {
    parent::routes($routes);
  }
}
