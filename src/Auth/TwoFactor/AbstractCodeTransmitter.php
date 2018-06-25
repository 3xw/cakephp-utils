<?php
namespace Trois\Utils\Auth\TwoFactor;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

abstract class AbstractCodeTransmitter
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [];

  public $successFlashMessage = __('A two-factor authentication code has been tramsitted to you. Please find it and paste it here in order to finish the sign up process.');

  public $errorFlashMessage = __('The app was not able to tramsit your two-factor authentication code. Please try again.');

  // returns bool
  abstract public function transmit(string $code,ServerRequest $request, Response $response);
}
