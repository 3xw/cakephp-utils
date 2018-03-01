# cakephp-utils plugin for CakePHP
This plugin is full of handly stuff

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

	composer require 3xw/cakephp-utils

Load it in your config/boostrap.php

	Plugin::load('Trois/Utils');
	
## Auth
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
