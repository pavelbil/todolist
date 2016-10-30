<?php

namespace App\Task;

use Silex\Application;
use Silex\Route;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TaskController
 * @package App\Task
 */
class TaskController implements ControllerProviderInterface {

  /**
   * @var TaskManager TaskManager
   */
  protected $taskManager;

    /**
     * Constructor.
     *
     * @param TaskManager $taskManager
     * @param array $deprecated - Deprecated. No longer used.
     */
  public function __construct(TaskManager $taskManager, $deprecated = null)
  {
    $this->taskManager = $taskManager;
  }

  /**
   * @param Application $app
   */
  public function connect(Application $app) {}

  /**
   * Task list action
   *
   * @param Application $app
   * @param Request $request
   * @return mixed
   */
  public function listAction(Application $app, Request $request) {

    $list_id = $this->taskManager->getListByUser();

    if(empty($list_id)) {
      $list_id = $this->taskManager->createFirstTodoList();
    }

    $list = $this->taskManager->findByListId($list_id);

    return $app['twig']->render('@task/list.twig', array(
      'title' => 'Todo List',
      'error' => null,
      'list_id' => $list_id,
      'list' => $list,
    ));
  }

  /**
   * Save task action
   *
   * @param Application $app
   * @param Request $request
   * @return JsonResponse
   */
  public function saveAction(Application $app, Request $request) {
    $result = array();
    $status = 1;

    $id = $request->get('id', '');
    try {
      $errors = array();

      if(empty($id)) {
        //new task
        $task = $this->createTask($request);
        $this->taskManager->insertTask($task);
      } else {
        //update task
        $task = $this->taskManager->getTask($id);

        if(empty($task)) {
          throw new NotFoundHttpException('No task was found with that ID.');
        }

        if($request->get('name')) {
          $task->setName($request->get('name'));
        }

        $is_completed = $request->get('is_completed', null);

        if($is_completed !== null) {
          $task->setIsCompleted($is_completed === 'true');
        }

        $task = $this->updateTask($task);
        $this->taskManager->updateTask($task);
      }

      $result = array(
        'id' => $task->getId(),
        'name' => $task->getName(),
        'is_completed' => $task->getIsCompleted(),
      );
    } catch(\Exception $e) {
      $status = 0;
      $errors = $e->getMessage();
    }

    $result = array(
      'status' => $status,
      'data' => $result,
      'error' => $errors,
    );

    return new JsonResponse($result);
  }

  /**
   * Create new task
   * @param Request $request
   * @return Task
   * @throws InvalidArgumentException
   */
  protected function createTask($request)
  {
    $task = $this->taskManager->createTask(
      $request->get('list_id', null),
      $request->get('name', null));

    $errors = $this->taskManager->validate($task);
    if (!empty($errors)) {
      throw new InvalidArgumentException(implode("\n", $errors));
    }

    return $task;
  }

  /**
   * Check validation updating task
   * @param Task $request
   * @return Task
   * @throws InvalidArgumentException
   */
  protected function updateTask($task)
  {
    $errors = $this->taskManager->validate($task);
    if (!empty($errors)) {
      throw new InvalidArgumentException(implode("\n", $errors));
    }

    return $task;
  }

  /**
   * Delete task action
   * @param Application $app
   * @param Request $request
   * @return JsonResponse
   */
  public function deleteAction(Application $app, Request $request) {
    $id = $request->get('id');
    $status = 1;
    $errors = null;

    try {

      $task = $this->taskManager->getTask($id);

      if (empty($task)) {
        throw new NotFoundHttpException('No task was found with that ID.');
      }

      $this->taskManager->validate($task);
      $this->taskManager->deleteTask($task);

    } catch(\Exception $e) {
      $errors = $e->getMessage();
      $status = 0;
    }

    $result = array(
      'status' => $status,
      'data' => array(),
      'error' => $errors,
    );

    return new JsonResponse($result);

  }
}