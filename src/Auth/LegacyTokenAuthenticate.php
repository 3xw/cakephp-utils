<?php
namespace Trois\Utils\Auth;

use Trois\Utils\Utility\Crypto\Base64Url;
use Trois\Utils\Utility\Crypto\OpenSsl;
use Cake\Utility\Security;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\Exception\UnauthorizedException;
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
    'finder' => 'all',
    'passwordHasher' => 'Default'
  ];

  public function authenticate(ServerRequest $request, Response $response)
  {
    return $this->getUser($request);
  }

  public function getUser(ServerRequest $request)
  {
    if($request->getHeader('API-TOKEN') || $request->getHeader('X-API-TOKEN'))
    {
      $cipher = $request->getHeader('API-TOKEN')? $request->getHeader('API-TOKEN'): $request->getHeader('X-API-TOKEN');
      $cipher = Base64Url::decode($cipher[0]);
      Security::engine(new OpenSsl());

      if(!$username = Security::decrypt($cipher, $this->getConfig('key'), $this->getConfig('salt'))) throw new UnauthorizedException(__('Unable to decrypt the LegacyToken, token is not valid or the security encryption engine seems not to be OpenSsl'));
      $user = $this->_findUser($username);

  		return empty($user)? false: $user;
    }

    return false;
  }

  public function unauthenticated(ServerRequest $request, Response $response)
  {
    throw new UnauthorizedException('Ah ah ah! You didn\'t say the magic word!');
  }
}
