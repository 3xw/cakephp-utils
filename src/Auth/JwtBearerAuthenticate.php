<?php
namespace Trois\Utils\Auth;

use Cake\Auth\BasicAuthenticate as CakeBasicAuthenticate;
use Cake\Network\Request;
use Cake\Utility\Security;
use Cake\Http\Exception\UnauthorizedException;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JwtBearerAuthenticate extends CakeBasicAuthenticate
{
  // ... (le reste identique)

  protected function _decode($token)
  {
    $config = $this->_config;

    $key = (string) ($config['key'] ?: Security::getSalt());

    // HS256 uniquement => 1 Key suffit
    try {
      return JWT::decode($token, new Key($key, 'HS256'));
    } catch (ExpiredException $e) {
      throw new UnauthorizedException($e->getMessage());
    } catch (SignatureInvalidException $e) {
      throw new UnauthorizedException($e->getMessage());
    } catch (\DomainException $e) {
      throw new UnauthorizedException($e->getMessage());
    } catch (\UnexpectedValueException $e) {
      // jwt mal formé / invalid
      throw new UnauthorizedException($e->getMessage());
    }
  }
}
