<?php
namespace Trois\Utils\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Cake\Utility\Hash;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\Exception\Exception;
use Cake\Http\Cookie\CookieCollection;

class CookieConsentMiddleware implements MiddlewareInterface
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
    'cookieName' => 'cookieconsent_status',
    'value' => 'allow',
  ];

  public static $allow = false;

  public function __construct(array $config = [])
  {
    $this->setConfig($config);
  }

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $cookies = $request->getCookieParams();
    $cookieData = Hash::get($cookies, $this->_config['cookieName']);

    if (is_string($cookieData) && strlen($cookieData) > 0 && $cookieData == $this->_config['value'])
    {
      self::$allow = true;
      return $handler->handle($request);
    }

    self::$allow = false;
    $response = $handler->handle($request);
    $response = $response->withCookieCollection(new CookieCollection());
    return $response;
  }
}
