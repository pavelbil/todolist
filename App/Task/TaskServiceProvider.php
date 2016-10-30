<?php

namespace App\Task;

use Pimple\Container;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class TaskServiceProvider
 * @package App\Task
 */
class TaskServiceProvider implements ControllerProviderInterface, ServiceProviderInterface
{

  /**
   * @param Application $app
   * @return ControllerCollection
   */
  public function connect(Application $app)
  {

    /** @var ControllerCollection $controllers */
    $controllers = $app['controllers_factory'];

    //Task list rout
    $controllers->get('/', 'task.controller:listAction')
      ->bind('task')
      ->before(function(Request $request) use ($app) {
        if (!$app['user']) {
          throw new AccessDeniedException();
        }
      });

    //Save task rout
    $controllers->post('/save', 'task.controller:saveAction')
      ->bind('task.save')
      ->before(function(Request $request) use ($app) {
        if (!$app['user']) {
          throw new AccessDeniedException();
        }
      });

    //Delete task rout
    $controllers->post('/delete', 'task.controller:deleteAction')
      ->bind('task.delete')
      ->before(function(Request $request) use ($app) {
        if (!$app['user']) {
          throw new AccessDeniedException();
        }
      });

    return $controllers;
  }

  /**
   * @param Container $app
   */
  public function register(Container $app)
  {

    if (isset($app['twig.loader.filesystem'])) {
      $app['twig.loader.filesystem']->addPath(__DIR__ . '/views/', 'task');
    }

    $app['task.manager'] = $app->factory(function($app) {
      return new TaskManager($app['db'], $app);
    });

    // Task controller service.
    $app['task.controller'] = $app->factory(function ($app) {
      $controller = new TaskController($app['task.manager']);

      return $controller;
    });

  }
}