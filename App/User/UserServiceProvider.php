<?php

namespace App\User;

use Pimple\Container;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 * Class UserServiceProvider
 * @package App\User
 */
class UserServiceProvider implements ServiceProviderInterface, ControllerProviderInterface
{
  /**
   * @param Application $app An Application instance
   */
  public function register(Container $app)
  {

    // Add twig template path.
    if (isset($app['twig.loader.filesystem'])) {
      $app['twig.loader.filesystem']->addPath(__DIR__ . '/views/', 'user');
    }

    // User manager.
    $app['user.manager'] = $app->factory(function($app) {

      $userManager = new UserManager($app['db'], $app);

      return $userManager;
    });

    // Current user.
    $app['user'] = $app->factory(function($app) {
      return ($app['user.manager']->getCurrentUser());
    });

    // User controller service.
    $app['user.controller'] = $app->factory(function ($app) {
      $controller = new UserController($app['user.manager']);

      return $controller;
    });
  }

  /**
   * @param Application $app
   * @return ControllerCollection
   */
  public function connect(Application $app)
  {
    /** @var ControllerCollection $controllers */
    $controllers = $app['controllers_factory'];

    $controllers->method('GET|POST')->match('/register', 'user.controller:registerAction')
      ->bind('user.register');

    $controllers->get('/login', 'user.controller:loginAction')
      ->bind('user.login');

    // login_check and logout are dummy routes so we can use the names.
    // The security provider should intercept these, so no controller is needed.
    $controllers->method('GET|POST')->match('/login_check', function() {})
      ->bind('user.login_check');

    $controllers->get('/logout', function() {})
      ->bind('user.logout');

    return $controllers;
  }
}