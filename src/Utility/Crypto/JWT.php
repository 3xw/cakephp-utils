<?php
namespace Trois\Utils\Utility\Crypto;

use Cake\Utility\Security;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\JWT as FJWT;

class JWT {

  public static function encode(array $token, $salt = null)
  {
    return FJWT::encode($token, $salt? $salt: Security::getSalt());
  }

  public static function decode($token, $salt = null, $allowedAlgs = ['HS256'])
  {
    try {
      $decoded = FJWT::decode($token, $salt? $salt: Security::getSalt(), $allowedAlgs);
    } catch (ExpiredException $e) {
      throw new \Exception("Token expired", 1);
    } catch (SignatureInvalidException $e) {
      throw new \Exception("Invalid token: ".$e->getMessage(), 1);
    } catch(\DomainException $e){
      throw new \Exception("Invalid token: ".$e->getMessage(), 1);
    }
    return $decoded;
  }
}
