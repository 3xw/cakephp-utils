<?php
namespace Trois\Utils\Utility\Crypto;

use Cake\Utility\Security;
use Firebase\JWT\JWT as FJWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JWT
{
  /**
   * Encode a JWT.
   *
   * @param array $token Payload
   * @param string|null $salt Secret (optional)
   * @param string $alg Algorithm (default HS256)
   * @return string
   */
  public static function encode(array $token, $salt = null, string $alg = 'HS256'): string
  {
    $key = $salt ?: self::getKey();

    // firebase/php-jwt v6/v7
    return FJWT::encode($token, $key, $alg);
  }

  /**
   * Decode a JWT.
   *
   * @param string $token
   * @param string|null $salt Secret (optional)
   * @param array $allowedAlgs Legacy signature support; first alg is used
   * @return object
   * @throws \Exception
   */
  public static function decode($token, $salt = null, $allowedAlgs = ['HS256'])
  {
    $key = $salt ?: self::getKey();

    // In v6/v7, you must provide a Key (or an array of Key).
    // Your code uses HS256 only, so we take the first allowed alg.
    $alg = is_array($allowedAlgs) ? ($allowedAlgs[0] ?? 'HS256') : (string) $allowedAlgs;

    try {
      return FJWT::decode($token, new Key($key, $alg));
    } catch (ExpiredException $e) {
      throw new \Exception("Token expired", 1);
    } catch (SignatureInvalidException $e) {
      throw new \Exception("Invalid token: " . $e->getMessage(), 1);
    } catch (\DomainException $e) {
      throw new \Exception("Invalid token: " . $e->getMessage(), 1);
    } catch (\UnexpectedValueException $e) {
      // malformed token, invalid base64, etc.
      throw new \Exception("Invalid token: " . $e->getMessage(), 1);
    }
  }

  /**
   * CakePHP 3 uses Security::salt()
   * CakePHP 4+ uses Security::getSalt()
   */
  protected static function getKey(): string
  {
    if (method_exists(Security::class, 'salt')) {
      return (string) Security::salt();
    }
    return (string) Security::getSalt();
  }
}
