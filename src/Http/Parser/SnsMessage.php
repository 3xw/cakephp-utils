<?php
declare(strict_types=1);

namespace Trois\Utils\Http\Parser;

use Psr\Http\Message\ServerRequestInterface;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Aws\Sns\Exception\InvalidSnsMessageException;

use Cake\Http\Exception\BadRequestException;
use Cake\Datasource\EntityInterface;

use Trois\Utils\Model\Entity\SnsMessage as Entity;

class SnsMessage
{
  public static function parse(
    ServerRequestInterface $request,
    array $options = []
  ): EntityInterface
  {
    try {
      $message = Message::fromPsrRequest($request);
      $validator = new MessageValidator();
      $validator->validate($message);
    } catch (InvalidSnsMessageException $e) {
      throw new BadRequestException($e->getMessage());
    }

    // create entity
    return new Entity($message->toArray(), $options);
  }
}
