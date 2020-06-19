<?php
namespace Trois\Utils\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Cake\Utility\Hash;
use Cake\Core\App;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Controller\ComponentRegistry;
use Cake\Core\InstanceConfigTrait;
use Trois\Utils\Utility\Http\RequestMatchRule;

class AuthMiddleware
{
  use InstanceConfigTrait;

  protected $_components = null;

  protected $_storage = null;

  protected $_matcher = null;

  protected $_defaultConfig = [
    'permissions' => [
      [
        'prefix' => false,
        'plugin' => false,
        'controller' => '*',
        'action' => '*',
        'bypassAuth' => true,
      ],
      [
        'role' => 'Admin',
        'prefix' => '*',
        'plugin' => '*',
        'controller' => '*',
        'action' => '*',
        'extension' => '*'
      ],
    ],
    'auth' =>
    [
      'loginAction' => false,
      'unauthorizedRedirect' => false,

      // Authenticate
      'authenticate' => [

        // map role
        'all' => ['finder' => 'Auth'],

        // with Bearer JWT token
        'Trois\Utils\Auth\JwtBearerAuthenticate' => [
          'duration' => 10800 // 3h
        ],
      ],

      // Cache Storage Engine
      'storage' => [
        'className' => 'Trois\Utils\Auth\Storage\CacheStorage',
        'cache' => 'token'
      ],
    ]
  ];

  public function __construct($config = [])
  {
    $this->setConfig($config);

    // extraConfig
    if($extraConfig = $this->getConfig('auth.authenticate.all')) $this->setConfig('auth.authenticate.all', null);
    else $extraConfig = [];

    foreach($this->getConfig('auth.authenticate') as $name => $config) $this->getComponents()->load($name, array_merge($extraConfig, $config));
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
  {
    // free urls
    if($this->unAuthenticatedAuthorized($request)) return $next($request, $response);

    // authenticate
    $this->authenticate($request, $response);

    // authorize
    $this->authorize($request);

    // Ok all good bro !
    return $next($request, $response);
  }

  public function unAuthenticatedAuthorized(ServerRequestInterface $request)
  {
    foreach($this->getConfig('permissions') as $rule) if(!empty($rule['bypassAuth']) && $this->getMatcher()->matchRule($rule, $request)) return true;
    return false;
  }

  public function authenticate(ServerRequestInterface $request, ResponseInterface $response)
  {
    $this->setStorage($request, $response); // invoke storage

    // test and store user if found
    if($user = $this->getStorage()->read()) return;
    foreach($this->getComponents()->getIterator() as $name => $comp) if($user = $comp->authenticate($request, $response)) return $this->getStorage()->write($user);

    // kick out
    throw new UnauthorizedException();
  }

  public function authorize(ServerRequestInterface $request )
  {
    if($rule = $this->getMatcher()->checkUserRules($this->getStorage()->read(), $this->getConfig('permissions'), $request)) return true;

    // kick out
    throw new ForbiddenException();
  }

  public function getMatcher()
  {
    if($this->_matcher === null) $this->_matcher = new RequestMatchRule();
    return $this->_matcher;
  }

  public function getComponents()
  {
    if($this->_components === null) $this->_components = new ComponentRegistry();
    return $this->_components;
  }

  public function getStorage()
  {
    return $this->_storage;
  }

  public function setStorage($request, $response)
  {
    $config = $this->getConfig('auth.storage');
    if (is_string($config)) { $class = $config; $config = []; }
    else { $class = $config['className']; unset($config['className']); }

    $className = App::className($class, 'Auth/Storage', 'Storage');
    if (!class_exists($className)) throw new Exception(sprintf('Auth storage adapter "%s" was not found.', $class));
    $this->_storage = new $className($request, $response, $config);
  }
}
