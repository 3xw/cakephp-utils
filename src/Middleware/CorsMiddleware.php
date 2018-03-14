<?php
namespace Trois\Utils\Middleware;

class CorsMiddleware
{
  public function __invoke($request, $response, $next)
  {
    // Calling $next() delegates control to the *next* middleware
    // In your application's queue.
    $response = $next($request, $response);

    // When modifying the response, you should do it
    // *after* calling next.

    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
      $response = $response->withHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN'])     // or '*'
      ->withHeader('Access-Control-Allow-Credentials', 'true')
      //->withHeader('Access-Control-Max-Age', '0');    // no cache
      ->withHeader('Access-Control-Max-Age', '86400');    // cache for 1 day


      // Access-Control headers are received during OPTIONS requests
      if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
          $response = $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        }


        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
          $response = $response->withHeader('Access-Control-Allow-Headers', $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
        }

        $response = $response->withoutHeader('Location');
        $response = $response->withStatus(200);

      }
    }

    return $response;
  }
}
