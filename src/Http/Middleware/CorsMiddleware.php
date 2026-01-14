<?php
namespace Trois\Utils\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Cake\Core\InstanceConfigTrait;

class CorsMiddleware implements MiddlewareInterface
{
  use InstanceConfigTrait;

  protected array $_defaultConfig = [
    'all' => [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Credentials' => 'true',
      'Access-Control-Expose-Headers' => 'X-Token',
      'Access-Control-Max-Age' => '86400'
    ],
    'options' => [
      'methods' => 'GET, POST, OPTIONS, PUT, DELETE'
    ]
  ];

  public function __construct(array $config = [])
  {
    $this->setConfig($config);
  }

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $response =  $handler->handle($request);

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
    {
      $response = $this->_setHeaders($response);

      if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        $response = $response->withHeader('Access-Control-Allow-Methods', $this->getConfig('options.methods'));
      }

      if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        $response = $response->withHeader('Access-Control-Allow-Headers', $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
      }

      $response = $response->withoutHeader('Location');
      $response = $response->withStatus(200);
      $response = $this->_setHeaders($response);

    }else{

      $response = $this->_setHeaders($response);
    }

    return $response;
  }

  protected function _setHeaders($response)
  {
    foreach($this->getConfig('all') as $header => $value)
    {
      $response = $response->withHeader($header, $value);
    }

    return $response;
  }
}
