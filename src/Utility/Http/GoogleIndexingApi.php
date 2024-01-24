<?php
namespace Trois\Utils\Utility\Http;

use Psr\Http\Message\ServerRequestInterface;
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;

class GoogleIndexingApi {

    /*
     REFs: 
      - https://developers.google.com/search/apis/indexing-api/v3/prereqs?hl=en#php
      - https://developers.google.com/search/apis/indexing-api/v3/using-api?hl=en
      - https://googleapis.github.io/google-api-php-client/main/Google/Client.html#method_setAuthConfig
      
    */

    protected $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
    protected $scope = 'https://www.googleapis.com/auth/indexing';

    public function updateUrl($url){

        $client = new \Google_Client();

        $authConfig = Configure::read('ApiKeys.Google-indexing');
        $client->setAuthConfig($authConfig);
        $client->addScope($this->scope);

        $httpClient = $client->authorize();

        $content = [
            'url' => $url,
            'type' => 'URL_UPDATED'
        ];
        
        $response = $httpClient->post($this->endpoint, ['json' => $content]);
        
        return $response;
    }
    
    public function removeUrl($url)
    {
        $client = new \Google_Client();

        $authConfig = Configure::read('ApiKeys.Google-indexing');

        $client->setAuthConfig($authConfig);
        $client->addScope($this->scope);

        $httpClient = $client->authorize();

        $content = [
            'url' => $url,
            'type' => 'URL_DELETED'
        ];

        $response = $httpClient->post($this->endpoint, ['json' => $content]);

        return $response;
    }
}
