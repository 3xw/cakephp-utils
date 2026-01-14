<?php
namespace Trois\Utils\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;

use Trois\Utils\Http\RequestMatchRule;

class ModifyConfigureMiddleware implements MiddlewareInterface
{
  use InstanceConfigTrait;

  protected array $_defaultConfig = [];

  public function __construct(array $config = [])
  {
    $key = 'modify_configure';
    try {
      Configure::load($key, 'default');
    } catch (Exception $ex) {
      throw new Exception(__('Missing configuration file: "config/{0}.php"!!!', $key), 1);
    }

    $this->setConfig('rules', Configure::read('Trois.modifyConfigure.rules'));
  }

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $this->_execRule($request);
    return $handler->handle($request);
  }

  protected function _execRule($request)
  {
    $rule = (new RequestMatchRule())->checkRules($this->getConfig('rules'), $request);
    if($rule)
    {
      if(is_string($rule['config'])) Configure::load($rule['config']);
      if(is_array($rule['config'])) foreach($rule['config'] as $key => $value) Configure::write($key, $value);
    }
  }
}
