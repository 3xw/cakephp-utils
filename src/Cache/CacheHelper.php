<?php
namespace Trois\Utils\Cache;

use Cake\Core\InstanceConfigTrait;
use Cake\Cache\Cache;
use Cake\Http\ServerRequest;

class CacheHelper
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
    'cache' => 'queries',
  ];

  function __construct($config = [])
  {
    $this->setConfig($config);
  }

  public function getSignForRequest(ServerRequest $request)
  {
    $sign = json_encode((object)[
      'path' => $request->getPath(),
      'params' => $request->getQueryParams(),
      'query' => $request->getQuery(),
      'body' => $request->getData()
    ]);

    return $sign;
  }

  public function getCacheForRequest(ServerRequest $request)
  {
    return $this->exists($this->getSignForRequest($request));
  }

  public function storeDataForRequest(ServerRequest $request, $data)
  {
    Cache::write(md5($this->getSignForRequest($request)), $data, $this->getConfig('cache'));
  }

  protected function exists($sign)
  {
    return Cache::read(
      md5($sign),
      $this->getConfig('cache')
    );
  }
}