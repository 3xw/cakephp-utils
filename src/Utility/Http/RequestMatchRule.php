<?php
namespace Trois\Utils\Utility\Http;

use Psr\Http\Message\ServerRequestInterface;
use Cake\Core\Configure;
use Cake\Utility\Inflector;

class RequestMatchRule {

  public function checkRules(array $rules, ServerRequestInterface $request)
  {
    foreach ($rules as $rule)
    {
      if($matchResult = $this->matchRule($rule, $request)) return $matchResult;
    }
    return null;
  }

  public function matchRule(array $rule, ServerRequestInterface $request)
  {
    $debug = Configure::read('debug');
    $params = $request->getAttribute('params');
    $method = $request->getMethod();
    $reserved = [
      'callback' => null,
      'debug' => $debug,
      'method' => $method,
      'prefix' => $params['prefix'] ?? null,
      'plugin' => $params['plugin'] ?? null,
      'extension' => $params['_ext'] ?? null,
      'controller' => $params['controller'] ?? null,
      'action' => $params['action'] ?? null
    ];

    foreach ($reserved as $key => $v)
    {
      if(!array_key_exists($key, $rule)) continue;

      $value = $rule[$key];

      if (is_callable($value))
      {
        $return = call_user_func($value, $request);
      }
      else $return = $this->_matchOrAsterisk($value, $reserved[$key], true);

      if (!$return) return null;
    }

    return $rule;
  }

  protected function _matchOrAsterisk($possibleValues, $value, $allowEmpty = false)
  {
    $possibleArray = (array)$possibleValues;

    if (
      $possibleValues === '*' ||
      $value === $possibleValues ||
      in_array($value, $possibleArray) ||
      in_array(Inflector::camelize((string)$value, '-'), $possibleArray)
    ) {
      return true;
    }

    return false;
  }
}
