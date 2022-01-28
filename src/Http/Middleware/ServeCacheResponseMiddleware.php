<?php
declare(strict_types=1);

namespace Trois\Utils\Http\Middleware;

use Cake\Cache\Cache;
use Cake\Http\Response;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
* ServeCacheResponse middleware
*/
class ServeCacheResponseMiddleware implements MiddlewareInterface
{
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $rule = (new ResponseCacheMiddleware)->checkRules($request);
    if($rule)
    {
      $key = md5( $request->getUri()->__toString() );
      if($content = Cache::read($key, $rule['cache']))
      {
        $rsp = new Response();
        return $rsp->withStringBody($content);
      }
    }

    return $handler->handle($request);
  }
}
