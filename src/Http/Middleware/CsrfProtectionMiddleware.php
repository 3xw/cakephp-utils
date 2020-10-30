<?php
namespace Trois\Utils\Http\Middleware;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Cake\Core\Configure;
use Cake\Http\Middleware\CsrfProtectionMiddleware as BasMiddleware;
use Trois\Utils\Http\RequestMatchRule;

class CsrfProtectionMiddleware extends BasMiddleware
{
  public function __construct(array $config = [])
  {
    parent::__construct($config);

    $key = 'csrf';
    try {
      Configure::load($key, 'default');
    } catch (Exception $ex) {
      throw new Exception(__('Missing configuration file: "config/{0}.php"!!!', $key), 1);
    }

    $this->skipCheckCallback(Closure::fromCallable([$this, 'whitelistHandler']));
  }

  public function whitelistHandler(ServerRequestInterface $request)
  {
    return (bool) (new RequestMatchRule())
    ->checkRules(Configure::read('Trois.csrf.rules'), $request);
  }
}
