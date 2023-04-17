<?php
declare(strict_types=1);

namespace Trois\Utils\Auth\Identifier;

use Authentication\Identifier\TokenIdentifier;
use Cake\Cache\Cache;

class TokensIdentifier extends TokenIdentifier
{

    protected $_defaultConfig = [
      'matching' => [
        'sub' => 'id',
        'username' => 'username'
      ],
      'resolver' => ['className' => 'Authentication.Orm', 'finder' => 'Auth'],
      'cache'=> 'token',
      'prefix' => '_token_'
    ];

    public function identify(array $credentials)
    {
      foreach($this->getConfig('matching') as $tokenField => $dbField)
      {
        if (isset($credentials[$tokenField])) 
        {
          $value = $credentials[$tokenField];
          $prefix = $this->getConfig('prefix');
          $key = $prefix.$value;
          $user = Cache::read($key, $this->getConfig('cache'));

          if(!$user) $user =  $this->getResolver()->find([
            $dbField => $value,
          ]);

          if($user) Cache::write($key, $user, $this->getConfig('cache'));
          return $user;
        }
      }
      return null;
    }
}