<?php
namespace Trois\Utils\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Cake\Http\Exception\ForbiddenException;
use ReCaptcha\ReCaptcha;
use Cake\Log\Log;

/**
* Recaptcha middleware
*/
class RecaptchaMiddleware
{
  private $secret;

  public function __construct(string $secret)
  {
    $this->secret = $secret;
  }


  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
  {
    // TEST
    if (!$this->isValid($request)) throw new ForbiddenException('Recaptcha Error');

    return $next($request, $response);
  }

  private function isValid(ServerRequestInterface $request): bool
  {
    // Check Method
    $method = strtoupper($request->getMethod());
    if (in_array($method, ['GET', 'HEAD', 'CONNECT', 'TRACE', 'OPTIONS'], true)) return true; // white list

    // extract g-recaptcha-response in data
    $data = $request->getParsedBody();
    $token = $data['g-recaptcha-response'] ?? '';

    //try extract from header
    if($token == '' && $request->hasHeader('X-Recaptcha'))
    {
      $token = $request->getHeader('X-Recaptcha');
      if(is_array($token) && !empty($token)) $token = $token[0];
    }

    // Google stuff
    $recaptcha = new ReCaptcha($this->secret);
    $response = $recaptcha->verify($token);
    $isSuccess = $response->isSuccess();

    // debug ERROR
    if(!$isSuccess) Log::write('debug', $response->getErrorCodes());

    return $isSuccess;
  }
}
