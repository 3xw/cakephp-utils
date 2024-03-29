<?php
namespace Trois\Utils\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Cake\Utility\Hash;
use Cake\Core\StaticConfigTrait;
use Cake\Core\Exception\Exception;
use Cake\Http\Cookie\CookieCollection;

class CookieConsentMiddleware implements MiddlewareInterface
{
  use StaticConfigTrait;

  public static $exists = false;

  public static $allow = true;

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $cookies = $request->getCookieParams();
    $cookieData = Hash::get($cookies, self::getConfig('cookieName'));

    if (is_string($cookieData) && strlen($cookieData) > 0)
    {
      self::$exists = true;
      if($cookieData == self::getConfig('value'))
      {
        self::$allow = true;
        return $handler->handle($request);
      }
    }

    self::$allow = false;
    try {
      ini_set('session.use_cookies', '0');
    } catch (\Exception $e) {}
    $response = $handler->handle($request);
    return $response;
  }
}
