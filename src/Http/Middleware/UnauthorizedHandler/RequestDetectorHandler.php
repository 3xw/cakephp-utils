<?php
declare(strict_types=1);

namespace Trois\Utils\Http\Middleware\UnauthorizedHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Authorization\Exception\Exception;
use CakeDC\Users\Middleware\UnauthorizedHandler\DefaultRedirectHandler;

class RequestDetectorHandler extends DefaultRedirectHandler
{
  public function handle(Exception $exception, ServerRequestInterface $request, array $options = []): ResponseInterface
  {
    $options += $this->defaultOptions;

    if(!empty($options['detectors']))
    {
      foreach($options['detectors'] as $detector => $config)
      {
        if(!empty($config['detector'])) $request->addDetector($detector,$config['detector']);
        if($request->is($detector))
        {
          if(!empty($config['handler']))
          {
            return (new $config['handler']())->handle($exception, $request, $options);
          }
        }
      }
    }

    return parent::handle($exception, $request, $options);
  }
}
