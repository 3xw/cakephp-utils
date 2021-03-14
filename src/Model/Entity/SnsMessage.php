<?php
declare(strict_types=1);

namespace Trois\Utils\Model\Entity;

use Cake\ORM\Entity;
use Cake\Http\Exception\BadRequestException;
use Cake\Utility\Hash;

class SnsMessage extends Entity
{
  protected $_accessible = [
    '*' => true,
  ];

  function __construct(array $properties  = [], array $options = [])
  {
    parent::__construct($properties, $options);

    // subscribe
    if($this->isSub()) $this->subscribe();

    // extract message
    $this->extractMessage($options);
  }

  protected function subscribe()
  {
    file_get_contents($this->SubscribeURL);
    die('OK');
  }

  protected function extractMessage($options): void
  {
    if($this->isSub()) return;

    $payload = $this->get('Message');
    if(!$mustHave = Hash::get($payload, 'mustHave')) return;

    foreach($mustHave as $prop)
    {
      if($value = Hash::get($payload, $prop))
      {
        $props = explode('.', $prop);
        $p = array_pop($props);
        $this->set($p, $value);
        continue;
      }
      throw new BadRequestException("The property: $prop is missing");
    }
  }

  public function isSub()
  {
    return $this->Type === 'SubscriptionConfirmation';
  }

  public function isNotif()
  {
    return $this->Type === 'Notification';
  }

  protected function _getMessage($message)
  {
    return json_decode($message, true);
  }
}
