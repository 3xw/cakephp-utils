<?php
namespace Trois\Utils\Auth\TwoFactor;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

abstract class AbstractCodeTransmitter
{
  use InstanceConfigTrait;

  protected array $_defaultConfig = [
    'messages' => [
      'success' => 'A two-factor authentication code has been tramsitted to you. Please find it and paste it here in order to finish the sign up process.',
      'error' => 'The app was not able to tramsit your two-factor authentication code. Please try again.'
    ]
  ];

  public function __construct(array $config = [])
  {
    $this->setConfig($config);
  }

  // returns bool
  abstract public function transmit(string $code, array $user,ServerRequest $request, Response $response);
}
