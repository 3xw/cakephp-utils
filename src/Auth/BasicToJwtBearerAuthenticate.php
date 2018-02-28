<?php
namespace Trois\Utils\Auth;

use Firebase\JWT\JWT;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Event\Event;
use Cake\Utility\Security;
use Cake\Core\Configure;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Auth\BasicAuthenticate AS CakeBasicAuthenticate;

class BasicToJwtBearerAuthenticate extends CakeBasicAuthenticate
{
  public function afterIdentify(Event $event, array $user)
  {
  $token = JWT::encode(['sub' => $user['id'], 'exp' =>  time() + 3600/*$event->getSubject()->config()['storage']['redis']['duration']*/], Security::salt());
    $event->getSubject()->response = $event->getSubject()->response->withHeader('X-Token', $token);
    $user['X-Token'] = $token;
    $event->result = $user;
  }

  public function unauthenticated(ServerRequest $request, Response $response)
  {
    $Exception = new UnauthorizedException('Ah ah ah! You didn\'t say the magic word!');
    $Exception->responseHeader([$this->loginHeaders($request)]);
    throw $Exception;
  }

  public function implementedEvents()
  {
    return ['Auth.afterIdentify' => 'afterIdentify'];
  }
}
