<?php
declare(strict_types=1);

namespace Trois\Utils\ORM\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use CakeDC\Users\Model\Behavior\RegisterBehavior as CakeDCRegisterBehavior;
use Cake\Core\Configure;
use Cake\Utility\Text;

/**
 * Register behavior
 */
class RegisterBehavior extends CakeDCRegisterBehavior
{
  public function initialize(array $config): void
  {
      parent::initialize($config);
  }

  public function registerFromAdmin($user, $data, $options = [])
  {
    $options = array_merge(['validate_email' => false,'use_tos' => false], $options);
    $password = $data['password']?? $this->_generateRandomPassword();
    $data['password'] = $data['password_confirm'] = $password;
    if(!$user = $this->register($user, $data, $options)) return false;

    return $user->set('originalPassword',$password);
  }

  protected function _generateRandomPassword()
  {
      return str_replace('-', '', Text::uuid());
  }

  public function register($user, $data, $options)
  {
      $publicRoles = Configure::read('Users.Registration.publicRoles') ?: ['user'];
      $defaultRole = Configure::read('Users.Registration.defaultRole') ?: 'user';
      $validateEmail = $options['validate_email'] ?? null;
      $tokenExpiration = $options['token_expiration'] ?? null;
      $validator = $options['validator'] ?? null;
      if(empty($data['username'])) $data['username'] = str_replace('@', '.', $data['email']);
      $user = $this->_table->patchEntity(
          $user,
          $data,
          ['validate' => $validator ?: $this->getRegisterValidators($options)]
      );
      $user['role'] = (!in_array($data['role'], $publicRoles))? $defaultRole : $data['role'];
      $user->validated = false;
      $user = $this->_updateActive($user, $validateEmail, $tokenExpiration);
      $this->_table->isValidateEmail = $validateEmail;
      $userSaved = $this->_table->save($user);
      if ($userSaved && $validateEmail) {
          $this->_sendValidationEmail($user);
      }

      return $userSaved;
  }

}
