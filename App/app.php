<?php
require __DIR__.'/bootstrap.php';
$app = new Silex\Application();

// Include configuration
if (!file_exists(__DIR__.'/config/config.php')) {
  throw new RuntimeException('You must create your own configuration file ("config/config.php"). See "config/config.php.dist" for an example config file.');
}

require_once __DIR__.'/config/config.php';

//Security
$app->register(new Silex\Provider\SecurityServiceProvider());

//Service Controller
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

//Twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/templates',
));

//Doctrine
$app->register(new Silex\Provider\DoctrineServiceProvider());

//Session
$app->register(new Silex\Provider\SessionServiceProvider());

//User
$userProvider = new \App\User\UserServiceProvider();
$app->register($userProvider);
$app->mount('/user', $userProvider);

//security settings
$app['security.firewalls'] = array(
  'secured_area' => array(
    'pattern' => '^.*$',
    'anonymous' => true,
    'form' => array(
      'login_path' => '/user/login',
      'check_path' => '/user/login_check',
    ),
    'logout' => array(
      'logout_path' => '/user/logout',
    ),
    'users' => $app->factory(function($app) { return $app['user.manager']; }),
  ),
);

// Routes
$app->mount('/', new App\Controllers\IndexController());

//Task
$userProvider = new \App\Task\TaskServiceProvider();
$app->register($userProvider);
$app->mount('/task', $userProvider);

return $app;