<?php
namespace Trois\Utils\Middleware;

use Cake\Core\InstanceConfigTrait;

class CorsMiddleware
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
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

  public function __invoke($request, $response, $next)
  {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
    {
      $response = $this->_setHeaders($response);

      if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        $response = $response->withHeader('Access-Control-Allow-Methods', $this->config('options.methods'));
      }

      if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        $response = $response->withHeader('Access-Control-Allow-Headers', $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
      }

      $response = $response->withoutHeader('Location');
      $response = $response->withStatus(200);

    }else{

      $response = $next($request, $response);
      $response = $this->_setHeaders($response);
    }

    return $response;
  }

  protected function _setHeaders($response)
  {
    if (isset($_SERVER['HTTP_ORIGIN']))
    {
      foreach($this->getConfig('all') as $header => $value)
      {
        $response = $response->withHeader($header, $value);
      }
    }

    return $response;
  }
}
