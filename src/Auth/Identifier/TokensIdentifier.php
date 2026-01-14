<?php
declare(strict_types=1);

namespace Trois\Utils\Auth\Identifier;

use Authentication\Identifier\TokenIdentifier;

class TokensIdentifier extends TokenIdentifier
{

  protected array $_defaultConfig = [
      'dataField' => 'username',
      'resolver' => 'Authentication.Orm',
  ];

  public function identify(array $credentials)
  {
    $tokenField = 'legacy_token_field';
    $dataField = $this->getConfig('dataField');

    if (isset($credentials[$tokenField])) 
    {
      $value = $credentials[$tokenField];
      $user =  $this->getResolver()->find([
        $dataField => is_string($value)? trim($value): $value,
      ]);

      return $user;
    }
    return null;
  }
}