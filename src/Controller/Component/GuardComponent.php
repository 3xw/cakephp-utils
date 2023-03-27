<?php
namespace Trois\Utils\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Psr\Log\LogLevel;
use Cake\Http\ServerRequest;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

class GuardComponent extends Component
{
  public $components = ['Auth'];

  protected $_defaultConfig = [
    // autoload permissions based on a configuration
    'autoload_configs' => [
      'Guard.requestBody' => 'guard_request_body'
    ],
    // role field in the Users table
    'role_field' => 'role',
    // 'log' will match the value of 'debug' if not set on configuration
    'log' => false,
  ];

  protected $_ruleSettings = [];

  public function initialize(array $config):void
  {
    parent::initialize($config);

    $configs = $this->getConfig('autoload_configs');
    foreach($configs as $name => $key)
    {
      try {
        Configure::load($key, 'default');
        $this->_ruleSettings[] = $name;
      } catch (\Exception $e) {
        $msg = sprintf('Missing configuration file: "config/%s.php". Using default permissions', $key);
        $this->log($msg, LogLevel::WARNING);
      }
    }
  }

  public function startup(Event $event)
  {
    $this->_applyRules($this->Auth->user(), $this->request);
  }

  protected function _applyRules($user, ServerRequest $request)
  {
    $role = empty($user)? null: Hash::get($user, $this->getConfig('role_field'));
    foreach ($this->_ruleSettings as $setting)
    {
      $rules = Configure::read($setting);
      foreach ($rules as $rule) $this->_applyRule($rule, $user, $role,$request);
    }
  }

  protected function _applyRule($rule, $user, $role,$request)
  {
    $method = $request->getMethod();
    $plugin = $request->getParam('plugin');
    $controller = $request->getParam('controller');
    $action = $request->getParam('action');
    $prefix = null;
    $extension = null;
    if (!empty($request->getParam('prefix'))) {
      $prefix = $request->getParam('prefix');
    }
    if (!empty($request->getParam('_ext'))) {
      $extension = $request->getParam('_ext');
    }

    if ($this->_matchOrAsterisk($rule, 'role', $role) &&
    $this->_matchOrAsterisk($rule, 'method', $method, true) &&
    $this->_matchOrAsterisk($rule, 'prefix', $prefix, true) &&
    $this->_matchOrAsterisk($rule, 'plugin', $plugin, true) &&
    $this->_matchOrAsterisk($rule, 'extension', $extension, true) &&
    $this->_matchOrAsterisk($rule, 'controller', $controller) &&
    $this->_matchOrAsterisk($rule, 'action', $action)) {

      // Apply rule now
      if(isset($rule['rule']) && is_callable($rule['rule'])) call_user_func($rule['rule'], $user, $role,$request);
    }
  }

  protected function _matchOrAsterisk($permission, $key, $value, $allowEmpty = false)
  {
    $possibleValues = (array)Hash::get($permission, $key);
    if ($allowEmpty && empty($possibleValues) && $value === null) {
      return true;
    }
    if (Hash::get($permission, $key) === '*' ||
    in_array($value, $possibleValues) ||
    in_array(Inflector::camelize($value, '-'), $possibleValues)) {
      return true;
    }

    return false;
  }

}
