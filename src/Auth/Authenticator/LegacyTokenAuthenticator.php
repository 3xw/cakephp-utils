<?php
declare(strict_types=1);

namespace Trois\Utils\Auth\Authenticator;

use stdClass;
use Psr\Http\Message\ServerRequestInterface;

use Cake\Utility\Security;
use Authentication\Identifier\IdentifierInterface;
use Authentication\Authenticator\JwtAuthenticator;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\Result;

use Trois\Utils\Utility\Crypto\Base64Url;
use Trois\Utils\Utility\Crypto\OpenSsl;

use App\Auth\Identifier\LegacyIdentifier;

class LegacyTokenAuthenticator extends JwtAuthenticator
{
  protected $_defaultConfig = [
    'key' => '***',
    'salt' => '***',
    'headerKey' => ['API-TOKEN', 'X-API-TOKEN'],
    'subjectKey' => 'username',
    'returnPayload' => true
  ];

  public function __construct(IdentifierInterface $identifier, array $config = [])
  {
    parent::__construct($identifier, $config);
  }

  public function authenticate(ServerRequestInterface $request): ResultInterface
  {
      try {
          $result = $this->getPayload($request);
      } catch (Exception $e) {
          return new Result(
              null,
              Result::FAILURE_CREDENTIALS_INVALID,
              [
                  'message' => $e->getMessage(),
                  'exception' => $e,
              ]
          );
      }

      if (!($result instanceof stdClass)) {
          return new Result(null, Result::FAILURE_CREDENTIALS_INVALID);
      }

      $result = json_decode(json_encode($result), true);

      $subjectKey = $this->getConfig('subjectKey');
      if (empty($result[$subjectKey])) {
          return new Result(null, Result::FAILURE_CREDENTIALS_MISSING);
      }
      
      if ($this->getConfig('returnPayload')) {
          $user = new ArrayObject($result);

          return new Result($user, Result::SUCCESS);
      }
      
      $user = $this->_identifier->identify([
          'legacy_token_field' => $result[$subjectKey],
      ]);
      
      if (empty($user)) {
          return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND, $this->_identifier->getErrors());
      }
      
      return new Result($user, Result::SUCCESS);
  }

  public function getPayload(?ServerRequestInterface $request = null): ?object
  {
    if (!$request) return $this->payload;

    foreach( $this->getConfig('headerKey') as $hKey)
    {
      if(!$cipher = $request->getHeader($hKey)) continue;
      
      // decipher
      if(empty($cipher)) return $this->payload;
      $cipher = Base64Url::decode($cipher[0]);
      $subjectKey = $this->getConfig('subjectKey');
      
      Security::engine(new OpenSsl());
      $decipher = Security::decrypt($cipher, $this->getConfig('key'), $this->getConfig('salt'));
      if($decipher) $this->payload = json_decode(json_encode([$subjectKey => $decipher]));
    }
    
    return $this->payload;
  }
}