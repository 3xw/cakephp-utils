<?php
declare(strict_types=1);

namespace Trois\Utils\Utility\Crypto;

/**
 * OpenSSL implementation of crypto features for Cake\Utility\Security
 *
 * This class is not intended to be used directly and should only
 * be used in the context of {@link \Cake\Utility\Security}.
 *
 * @internal
 */
class OpenSsl
{
    /**
     * @var string
     */
    public const METHOD_AES_256_CBC = 'aes-256-cbc';

    public const METHOD_AES_128_CBC = 'aes-128-cbc';

    public static $method = 'aes-256-cbc';

    public static function setDefaultMethod($method)
    {
      self::$method = $method;
    }
    
    public static function encrypt(string $plain, string $key): string
    {
        $method = self::$method;
        $ivSize = openssl_cipher_iv_length($method);

        $iv = openssl_random_pseudo_bytes($ivSize);

        return $iv . openssl_encrypt($plain, $method, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Decrypt a value using AES-256.
     *
     * @param string $cipher The ciphertext to decrypt.
     * @param string $key The 256 bit/32 byte key to use as a cipher key.
     * @return string Decrypted data. Any trailing null bytes will be removed.
     * @throws \InvalidArgumentException On invalid data or key.
     */
    public static function decrypt(string $cipher, string $key): ?string
    {
      $ivSize = openssl_cipher_iv_length(self::$method);
      $iv = mb_substr($cipher, 0, $ivSize, '8bit');
      $cipher = substr($cipher, $ivSize);
      $plain = openssl_decrypt($cipher, self::$method, $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
      
      if (mb_strlen($iv, '8bit') % mb_strlen($plain, '8bit') == 0) {
          preg_match_all('#([\0]+)$#', $plain, $matches);
          if (!empty($matches[1]) && mb_strlen($matches[1][0], '8bit') > 1) {
            $plain = rtrim($plain, "\0");
            //trigger_error('Detected and stripped null padding. Please double-check results!');
          }
      }
      if ($plain === false) {
        return null;
      }
      return $plain;
    }
}
