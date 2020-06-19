<?php
namespace Trois\Utils\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Cache\Cache;

class RequestCacheMiddleware
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
    'cache' => 'default',
  ];

  public function __construct($config = [])
  {
    $this->setConfig($config);
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
  {
    $key = $request->here();
    if($body = Cache::read($key, $this->getConfig('cache')))
    {
      $response = $response->withStringBody($body);
      $response = $response->withHeader('X-Cache', 'Hit from cake RequestCacheMiddleware');

      return $response;
    }

    return $next($request, $response);
  }
}
