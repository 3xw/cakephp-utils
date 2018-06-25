<?php
namespace Trois\Utils\Auth\TwoFactor;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Mailer\Email;

class EmailCodeTransmitter extends AbstractCodeTransmitter
{

  protected $_defaultConfig = [
    'messages' => [
      'success' => 'An email with a two-factor authentication code has been tramsitted to you. Please find it and paste it here in order to finish the sign up process.',
      'error' => 'The app was not able to tramsit your an emial with your two-factor authentication code. Please try again.'
    ],
    'email' => [
      'field' => 'email',
      'profile' => 'default',
      'from' => 'me@example.com',
      'subject' => 'Your temporary code',
      'emailFormat' => 'both',
      'template' => 'Trois/Utils.default',
      'layout' => 'default',
    ]
  ];

  // returns bool
  public function transmit(string $code, array $user,ServerRequest $request, Response $response)
  {
    return true;
    $email = new Email();
    $email
    ->setProfile($this->getConfig('email.profile'))
    ->setTo($user[$this->getConfig('email.field')])
    ->setViewVars([
      'code' => $code,
      'user' => $user
    ])
    ->setFrom($this->getConfig('email.from'))
    ->setSubject($this->getConfig('email.subject'))
    ->setTemplate($this->getConfig('email.template'))
    ->setLayout($this->getConfig('email.layout'));

    return $email->send();
  }
}
