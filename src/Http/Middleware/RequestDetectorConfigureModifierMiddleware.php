<?php
declare(strict_types=1);

namespace Trois\Utils\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;

class RequestDetectorConfigureModifierMiddleware implements MiddlewareInterface
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
    'json' => [
      'detector' => null, // or https://book.cakephp.org/4/en/controllers/request-response.html#checking-request-conditions
      'configFile' => null,
      'config' => null
    ]
  ];

  public function __construct(array $config = [])
  {
    $this->setConfig($config);
  }

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {

    foreach($this->getConfig() as $detector => $config)
    {
      if(!empty($config['detector'])) $request->addDetector($detector,$config['detector']);
      if($request->is($detector))
      {
        if(!empty($config['configFile'])) Configure::load($config['configFile']);
        if(!empty($config['config'])) foreach($config['config'] as $key => $value) Configure::write($key, $value);
      }
    }

    return $handler->handle($request);
  }
}
