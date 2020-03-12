<?php

namespace Trois\Utils\Utility\Crypto;

use LogicException;

/* use cakephp 2.10.20 Security */
class OpenSsl
{
  /**
  * Not implemented
  *
  * @param string $text Encrypted string to decrypt, normal string to encrypt
  * @param string $key Key to use as the encryption key for encrypted data.
  * @param string $operation Operation to perform, encrypt or decrypt
  * @throws \LogicException Rijndael compatibility does not exist with Openssl.
  * @return void
  */
  public static function rijndael($text, $key, $operation)
  {
    throw new LogicException('rijndael is not compatible with OpenSSL. Use mcrypt instead.');
  }

  /**
  * Encrypt a value using AES-256.
  *
  * *Caveat* You cannot properly encrypt/decrypt data with trailing null bytes.
  * Any trailing null bytes will be removed on decryption due to how PHP pads messages
  * with nulls prior to encryption.
  *
  * @param string $plain The value to encrypt.
  * @param string $key The 256 bit/32 byte key to use as a cipher key.
  * @return string Encrypted data.
  * @throws \InvalidArgumentException On invalid data or key.
  */
  public static function encrypt($plain, $key)
  {
    $method = 'AES-256-CBC';
    $ivSize = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($ivSize);
    $padLength = (int)ceil((strlen($plain) ?: 1) / $ivSize) * $ivSize;
    $ciphertext = openssl_encrypt(str_pad($plain, $padLength, "\0"), $method, $key, true, $iv);
    // Remove the PKCS#7 padding block for compatibility with mcrypt.
    // Since we have padded the provided data with \0, the final block contains only padded bytes.
    // So it can be removed safely.
    $ciphertext = $iv . substr($ciphertext, 0, -$ivSize);

    $hmac = hash_hmac('sha256', $ciphertext, $key);
		return $hmac . $ciphertext;
  }

  /**
  * Decrypt a value using AES-256.
  *
  * @param string $cipher The ciphertext to decrypt.
  * @param string $key The 256 bit/32 byte key to use as a cipher key.
  * @return string Decrypted data. Any trailing null bytes will be removed.
  * @throws \InvalidArgumentException On invalid data or key.
  */
  public static function decrypt($cipher, $key)
  {
    $method = 'AES-256-CBC';
    $ivSize = openssl_cipher_iv_length($method);
    $iv = substr($cipher, 0, $ivSize);
    $cipher = substr($cipher, $ivSize);
    // Regenerate PKCS#7 padding block
    $padding = openssl_encrypt('', $method, $key, true, substr($cipher, -$ivSize));
    $plain = openssl_decrypt($cipher . $padding, $method, $key, true, $iv);
    return rtrim($plain, "\0");
  }
}
