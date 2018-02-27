<?php
namespace Trois\Utils\Auth;

use Trois\Utils\Utility\Base64Url;
use Cake\Utility\Security;
use Cake\Utility\Crypto\Mcrypt;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Auth\BaseAuthenticate;

class LegacyTokenAuthenticate extends BaseAuthenticate
{
  protected $_defaultConfig = [
    'key' => '***',
    'salt' => '***',
    'fields' => [
      'username' => 'username',
      'password' => 'password'
    ],
    'userModel' => 'Users',
    'scope' => [],
    'finder' => 'all',
    'contain' => ['Roles'],
    'passwordHasher' => 'Default'
  ];

  public function authenticate(ServerRequest $request, Response $response)
  {
    return $this->getUser($request);
  }

  public function getUser(ServerRequest $request)
  {
    if($request->header('API-TOKEN') || $request->header('X-API-TOKEN'))
    {
      $cipher = $request->header('API-TOKEN')? $request->header('API-TOKEN'): $request->header('X-API-TOKEN');
      $cipher = Base64Url::decode($cipher);
      Security::engine(new Mcrypt());
      $username = Security::decrypt($cipher, $this->config('key'), $this->config('salt'));
      $user = $this->_findUser($username);

  		if (empty($user)) {
  			return false;
  		}
      $role = $user['role'];
      $user['role'] = $role['name'];
      return $user;
    }

    return false;
  }

  public function unauthenticated(ServerRequest $request, Response $response)
  {
    throw new UnauthorizedException('Ah ah ah! You didn\'t say the magic word!');
  }
}
