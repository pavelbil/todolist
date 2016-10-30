<?php

namespace App\Controllers;

use Silex\Application;
use Silex\Route;
use Silex\Api\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 * Class IndexController
 * @package App\Controllers
 */
class IndexController implements ControllerProviderInterface {

  /**
   * @param Application $app
   * @return ControllerCollection
   */
  public function connect(Application $app)
  {
    /** @var ControllerCollection $controllers */
    $controllers = $app['controllers_factory'];

    $controllers->get('/', function () use ($app) {
      return $app['twig']->render('index.twig', array(
        'title' => "Home page",
      ));
    });

    return $controllers;
  }
}