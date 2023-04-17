<?php
namespace Trois\Utils\Controller\Component;

use Cake\Core\Configure;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use ReCaptcha\ReCaptcha;
use ReCaptcha\Response;

class RecaptchaComponent extends Component
{
  protected $_defaultConfig = [];

  protected $recaptcha = null;

  public function getInstance(): ReCaptcha
  {
    if($this->recaptcha) return $this->recaptcha;
    return $this->recaptcha = new ReCaptcha(Configure::read('recaptcha.v3.secret'));
  }

  public function verify(): Response
  {
    $gRecaptchaResponse = $this->getController()->getRequest()->getData('g-recaptcha-response');
    $remoteIp = $this->getController()->getRequest()->clientIp();
    return $this->getInstance()->verify($gRecaptchaResponse, $remoteIp);
  }
}
