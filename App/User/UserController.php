<?php

namespace App\User;

use Silex\Application;
use Silex\Route;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use App\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserController
 * @package App\User
 */
class UserController implements ControllerProviderInterface {

  /** @var UserManager */
  protected $userManager;

  /**
   * Constructor.
   *
   * @param UserManager $userManager
   * @param array $deprecated - Deprecated. No longer used.
   */
  public function __construct(UserManager $userManager, $deprecated = null)
  {
    $this->userManager = $userManager;
  }

  /**
   * @param Application $app
   */
  public function connect(Application $app) {}

  /**
   * @param Application $app
   * @param Request $request
   * @return mixed
   */
  public function loginAction(Application $app, Request $request) {
    return $app['twig']->render('@user/login.twig', array(
      'title' => 'Login page',
      'error' => null,
      'last_username' => $app['session']->get('_security.last_username'),
    ));
  }

  /**
   * Registration
   * @param Application $app
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function registerAction(Application $app, Request $request) {

    if ($request->isMethod('POST')) {
      try {
        /* var User $user*/
        $user = $this->createUserFromRequest($request);

        $this->userManager->insert($user);
        // Log the user in to the new account.
        $this->userManager->loginAsUser($user);
        $app['session']->getFlashBag()->set('alert', 'Account created.');
        return $app->redirect($app['url_generator']->generate('task'));
      } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
      }
    }

    return $app['twig']->render('@user/register.twig', [
      'error' => isset($error) ? $error : null,
      'name' => $request->request->get('name'),
      'email' => $request->request->get('email'),
      'username' => $request->request->get('username'),
    ]);
  }

  /**
   * @param Request $request
   * @return \App\User\User
   * @throws InvalidArgumentException
   */
  protected function createUserFromRequest(Request $request)
  {
    if ($request->request->get('password') != $request->request->get('confirm_password')) {
      throw new InvalidArgumentException('Passwords don\'t match.');
    }
    $user = $this->userManager->createUser(
      $request->request->get('email'),
      $request->request->get('password'),
      $request->request->get('name') ?: null);
    $errors = $this->userManager->validate($user);
    if (!empty($errors)) {
      throw new InvalidArgumentException(implode("\n", $errors));
    }
    return $user;
  }
}