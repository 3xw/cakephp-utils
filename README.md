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
		      'rule' => function(array $user, $role, ServerRequest $request)
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
