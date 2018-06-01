# cakephp-utils plugin for CakePHP
This plugin is full of handly stuff

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

	composer require 3xw/cakephp-utils

Load it in your config/boostrap.php

	Plugin::load('Trois/Utils');

## Model

### Behaviors

 - Sluggable Behavior
 - Translate Behavior

### Rules

 - IsUniqueTranslationRule

## Shell

 - MissingTranslations

## View

 - LngSwitchCell

## Security

### GUARD Component
This component allows you to check constructed request object and clean it if needed...


in your src/Controller/AppController.php add following:

	public function initialize()
  	{
	    parent::initialize();

	    ...

	    // Auth
	    $this->loadComponent('Auth', [...]);

	    // Guard
	    $this->loadComponent('Trois/Utils.Guard',[
	      'autoload_configs' => [
	        'Guard.requestBody' => 'guard_request_body'
	      ]
	    ]);

	    ...
  	}

in config/guard\_request\_body.php

		<?php
		use Cake\Http\ServerRequest;

		return [
		  'Guard.requestBody' => [
		    [
		      'role' => '*',
		      'prefix' => 'Book',
		      'controller' => ['Users','Subscriptions'],
		      'action' => ['register','registerAndBook'],
		      'method' => ['POST','PUT'],
		      'rule' => function($user, $role, ServerRequest $request)
		      {
		        // magic here... manipulate request here
		      }
		    ],
		]];


### CORS Middleware

in your src/Application.php add following:

	use Trois\Utils\Middleware\CorsMiddleware;
	...
	public function middleware($middlewareQueue)
	{
		$middlewareQueue
		->add(new CorsMiddleware::class)

		/* -- OR  -- */
		->add(new CorsMiddleware([

			// thoses are default options
		    'all' => [
		      'Access-Control-Allow-Origin' => '*',
		      'Access-Control-Allow-Credentials' => 'true',
		      'Access-Control-Expose-Headers' => 'X-Token',
		      'Access-Control-Max-Age' => '86400'
		    ],
		    'options' => [
		      'methods' => 'GET, POST, OPTIONS, PUT, DELETE'
		    ]

	  	]))

		...
	}

### Auth tools
Config example:

config/bootsrap.php

	Configure::load('auth', 'default');


src/Controller/Appcontroller.php

	$this->loadComponent('Auth', Configure::read('Auth.V2'));

config/auth.php

	<?
	use Cake\Core\Configure;

	return [
	  'Auth.V2' => [

	    'loginAction' => false,
	    'unauthorizedRedirect' => false,
	    'checkAuthIn' => 'Controller.initialize' ,

	    // Authenticate
	    'authenticate' => [

	      // map role
	      'all' => ['finder' => 'Auth'],

	      // Legacy X-API-TOKEN header token
	      'Trois/Utils.LegacyToken' => [
	        'key' => Configure::read('Legacy.key'),
	        'salt' => Configure::read('Legacy.salt')
	      ],

	      // with Bearer JWT token
	      'Trois\Utils\Auth\JwtBearerAuthenticate' => [
	        'duration' => 3600
	      ],

	      // Basic username + pass
	      'Trois\Utils\Auth\BasicToJwtBearerAuthenticate' => [
	        'fields' => ['username' => 'username'],
	        'passwordHasher' => 'Trois\Utils\Auth\LegacyPasswordHasher',
	      ],
	    ],

	    // Cache Storage Engine
	    'storage' => [
	      'className' => 'Trois\Utils\Auth\Storage\CacheStorage',
	      'cache' => 'token'
	    ],

	    // Authorize
	    'authorize' => [
	      'CakeDC/Auth.SimpleRbac' => [
	        'autoload_config' => 'permissions',
	      ],
	    ],
	  ]
	];
	
## Cache

### cache settings
in config folder create a cache.php file with as exemple:

	<?php
	return [
  		'Trois.cache.settings' => [
    		'default' => 'default', // default cache config to use if not set in rules...
  		],
		'Trois.cache.rules' => [

			// cache request
			[
			  'cache' => 'html', // default: 'default', can be a fct($request)
			  'skip' => false, // default: false, can be a fct($request)
			  'clear' => false, // default: false, can be a fct($request)
			  'compress' => true, // default: false, can be a fct($request)
			  //'key' => 'whatEver',// default is fct($request) => return $request->here()
			  'method' => ['GET'],
			  'code' => '200', // must be set or '*' !!!!!
			  'prefix' => '*',
			  'plugin' => '*',
			  'controller' => '*',
			  'action' => '*',
			  'extension' => '*'
			],

			// clear request
			[
			  'cache' => 'html', // default: 'default'
			  'skip' => false, // default: false
			  'clear' => true, // default: false,
			  'key' => '*', // * => Cache::clear(false, cache) (Will clear all keys), 'whatEver' => Cache::delete('whatEver', cache), null => Cache::delete($request->here(), cache)
			  'method' => ['POST','PUT','DELETE'],
			  'code' => ['200','201','202'],
			  'prefix' => '*',
			  'plugin' => '*',
			  'controller' => ['Users','Pages'],
			  'action' => '*',
			  'extension' => '*'
			],
	  	]
	];

### Cache as your last middleware
in your src/Application.php file add the middleware as last chain block.
This will create or delete view renders as cache ( html/json /etc...)

	<?php
	namespace App;
	...
	use Awallef\Cache\Middleware\ResponseCacheMiddleware;

	class Application extends BaseApplication
	{
	    public function middleware($middleware)
	    {
	        $middleware
				...
	            // Apply Response caching
	            ->add(ResponseCacheMiddleware::class);

	        return $middleware;
	    }
	}

### Retrieve cache via ActionCacheComponent
in your AppController load the component AFTER Auth!!

	$this->loadComponent('Awallef/Cache.ActionCache');

### Retrieve cache via Nginx
First install nginx redis extension. Then set your cache config to store in redis. You can use my plugin...

	composer require awallef/cakephp-redis


Configure the engine in app.php like follow:

	'Cache' => [
		...
		'redis' => [
			'className' => 'Trois/Utils.Redis',
			'prefix' => 'hello.com:',
			'duration' => '+24 hours',
			'serialize' => true
		],
		...
	]

Configure cache.php like follow:

	return [
			'Trois.cache.settings' => [
				'default' => 'redis', // default cache config to use if not set in rules...
			],
		'Trois.cache.rules' => [

			// cache request
			[
				'skip' => false, // default: false, can be a fct($request)
				'clear' => false, // default: false, can be a fct($request)
				'compress' => true, // default: false, can be a fct($request)
				//'key' => 'whatEver',// default is fct($request) => return $request->here()
				'method' => ['GET'],
				'code' => '200', // must be set or '*' !!!!!
				'prefix' => '*',
				'plugin' => '*',
				'controller' => '*',
				'action' => '*',
				'extension' => '*'
			],
			
			// clear request
			[
		      'clear' => true,
		      'key' => '*',
		      'method' => ['POST','PUT','DELETE'],
			  'code' => ['200','201','202','302'], // 302 is often triggered by cakephp in case of success crud operation...
			  'prefix' => '*',
			  'plugin' => '*',
			  'controller' => '*',
			  'action' => '*',
			  'extension' => '*'
			],
		]
	];

Configure Nginx too:

	map $http_accept $hello_com_response_header {
		default   "text/html; charset=UTF-8";
		"~*json"  "application/json; charset=UTF-8";
	}
	server {
		listen 443;
		server_name hello.com;

		ssl on;
		...

		# redis key
		set $redis_key  "hello.com:$request_uri";
		if ($args) {
			set $redis_key  "hello.com:$request_uri?$args";
		}

		location / {
			redis_pass 127.0.0.1:6379;
			error_page 404 405 502 504 = @fallback;
			more_set_headers "Content-Type: $hello_com_response_header";
		}

		#default cake handling
		location @fallback {
			try_files $uri $uri/ /index.php?$args;
		}

		location ~ \.php$ {
			try_files $uri =404;
			include /etc/nginx/fastcgi_params;
			fastcgi_intercept_errors on;
			fastcgi_pass unix:/var/run/php/php7.1-fpm.sock;
			fastcgi_index   index.php;
			fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
		}

	}
	
### Redis caching
This plugin provides a very little bit different redis engine based on cakephp's RedisEngine.
differences are:

- Engine config comes with a bool 'serialize' option ( default is true )
- Read and wirte fct use config 'serialize' option
- Keys are stored/read/deleted in order to uses : and :* redis skills!

Configure the engine in app.php like follow:

	'Cache' => [
	    ...
	    'redis' => [
	      'className' => 'Trois/Utils.Redis',
	      'prefix' => 'www.your-site.com:',
	      'duration' => '+24 hours',
	      'serialize' => true
	    ],
	    ...
	]
