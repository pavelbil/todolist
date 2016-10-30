<?php
namespace App\Task;

use Doctrine\DBAL\Connection;
use Silex\Application;
use \Doctrine\Common\Cache\XcacheCache;

class TaskManager
{
  /**
   * @var Connection
   */
  protected $conn;
  /**
   * @var Application
   */
  protected $app;
  protected $cache;

  /**
   * Constructor.
   *
   * @param Connection $conn
   * @param Application $app
   */
  public function __construct(Connection $conn, Application $app)
  {
    $this->conn = $conn;
    $this->app = $app;
    $this->cache = new XcacheCache();
  }

  /**
   * Factory method for creating a new Task instance.
   *
   * @param string $id_list
   * @param string $name
   * @return Task
   */
  public function createTask($id_list, $name)
  {

    $task = new Task($id_list, $name);

    $task->setCreatedBy($this->app['user']->getId());
    $task->setIsCompleted(0);

    return $task;
  }

  /**
   * Validate a task object.
   *
   * @param Task $task
   * @return array An array of error messages, or an empty array if the Task is valid.
   */
  public function validate(Task $task)
  {
    $errors = $task->validate();

    $hasPermission = $this->hasEditPermissions($task->getListId());
    if(!$hasPermission) {
      $errors['list_id'] = 'You don\'t have permissions to edit this todo list.';
    }
    return $errors;
  }

  /**
   * Check current user edit permission
   * @param $task_id
   * @return array|bool
   */
  public function hasEditPermissions($task_id) {
    if(!$this->app['user.manager']->isLoggedIn()) {
      return false;
    }

    $sql = "SELECT owner_id FROM todo_lists WHERE id = :id AND owner_id = :user_id";

    $is_owner = $this->conn->fetchAll($sql, array(
      'id' => $task_id,
      'user_id' => $this->app['user']->getId()
      ));

    return $is_owner;
  }

  /**
   * Get a Task instance by its ID.
   *
   * @param int $id
   * @return Task|null The Task, or null if there is no User with that ID.
   */
  public function getTask($id)
  {
    $queryBuilder = $this->conn->createQueryBuilder();
    $queryBuilder
      ->select('*')
      ->from('tasks')
      ->where('id = ?')
      ->setParameter(0, $id)
      ->setMaxResults(1);
    $result = $queryBuilder->execute()->fetchAll();


    return !empty($result) ? $this->hydrateTask(reset($result)) : null;
  }

  /**
   * Reconstitute a Task object from stored data.
   * @param array $data
   * @return Task
   */
  protected function hydrateTask(array $data) {
    $task = new Task($data['list_id'], $data['name']);

    $task->setId($data['id']);
    $task->setCreatedBy($data['created_by']);
    $task->setIsCompleted($data['is_completed']);

    return $task;
  }

  /**
   * Add new task
   * @param Task $task
   */
  public function insertTask(Task $task) {
    $queryBuilder = $this->conn->createQueryBuilder();
    $queryBuilder
      ->insert('tasks')
      ->values(
        array(
          'list_id' => '?',
          'name' => '?',
          'is_completed' => '?',
          'created_by' => '?'
        )
      )
      ->setParameter(0, $task->getListId())
      ->setParameter(1, $task->getName())
      ->setParameter(2, $task->getIsCompleted())
      ->setParameter(3, $task->getCreatedBy());

    $queryBuilder->execute();
    $task->setId($this->conn->lastInsertId());
    $this->deleteTaskListCache($task->getListId());
  }

  /**
   * Update task
   * @param Task $task
   */
  public function updateTask(Task $task) {

    $queryBuilder = $this->conn->createQueryBuilder();
    $queryBuilder
      ->update('tasks')
      ->set('list_id', '?')
      ->set('name', '?')
      ->set('is_completed', '?')
      ->set('created_by', '?')
      ->where('id = ?')
      ->setParameter(0, $task->getListId())
      ->setParameter(1, $task->getName())
      ->setParameter(2, $task->getIsCompleted())
      ->setParameter(3, $task->getCreatedBy())
      ->setParameter(4, $task->getId());

    $queryBuilder->execute();
    $this->deleteTaskListCache($task->getListId());
  }

  /**
   * Delete task
   * @param Task $task
   */
  public function deleteTask(Task $task) {
    $queryBuilder = $this->conn->createQueryBuilder();
    $queryBuilder
      ->delete('tasks')
      ->where('id = ?')
      ->setParameter(0, $task->getId());

    $queryBuilder->execute();
    $this->deleteTaskListCache($task->getListId());
  }

  /**
   * Get todo list by current user
   * @return null
   */
  public function getListByUser() {
    $user_id = $this->app['user']->getId();

    $queryBuilder = $this->conn->createQueryBuilder();
    $queryBuilder
      ->select('*')
      ->from('todo_lists')
      ->where('owner_id = ?')
      ->setParameter(0, $user_id)
      ->setMaxResults(1);

    $result = $queryBuilder->execute()->fetchAll();

    return !empty($result) ? reset($result)['id'] : null;
  }

  /**
   * Create first todo list for new user
   * @return string
   */
  public function createFirstTodoList() {
    $user_id = $this->app['user']->getId();

    $queryBuilder = $this->conn->createQueryBuilder();
    $queryBuilder
      ->insert('todo_lists')
      ->values(
        array(
          'owner_id' => '?',
        )
      )
      ->setParameter(0, $user_id);

    $queryBuilder->execute();
    return $this->conn->lastInsertId();
  }

  /**
   * Get tasks by todo list id
   * @param $list_id
   * @return array|false|mixed|null
   */
  public function findByListId($list_id) {

    if($result = $this->getTaskListCache($list_id)) {
      return $result;
    }

    $queryBuilder = $this->conn->createQueryBuilder();
    $queryBuilder
      ->select('*')
      ->from('tasks')
      ->where('list_id = ?')
      ->setParameter(0, $list_id);
    $result = $queryBuilder->execute()->fetchAll();
    $this->setTaskListCache($list_id, $result);

    return $result;
  }

  /**
   * Check cache by key
   * @param $key
   * @return bool
   */
  public function hasCache($key) {
    return $this->cache->contains($key);
  }

  /**
   * Set data to cache
   * @param $key
   * @param $data
   * @return bool
   */
  public function setCache($key, $data) {
    return $this->cache->save($key, $data);
  }

  /**
   * Get cache by key
   * @param $key
   * @return false|mixed
   */
  public function getCache($key) {
    return $this->cache->fetch($key);
  }

  /**
   * Delete cache by key
   * @param $key
   * @return bool
   */
  public function deleteCache($key) {
    return $this->cache->delete($key);
  }

  /**
   * Get task list cache
   * @param $list_id
   * @return false|mixed|null
   */
  public function getTaskListCache($list_id) {
    $cache_key = $this->generateTaskListCacheKey($list_id);
    return $this->hasCache($cache_key) ? $this->getCache($cache_key) : null;
  }

  /**
   * Set task list cache
   * @param $list_id
   * @param $data
   * @return bool
   */
  public function setTaskListCache($list_id, $data) {
    $cache_key = $this->generateTaskListCacheKey($list_id);
    return $this->setCache($cache_key, $data);
  }

  /**
   * Flush task list cache
   * @param $list_id
   * @return bool
   */
  public function deleteTaskListCache($list_id) {
    $cache_key = $this->generateTaskListCacheKey($list_id);
    return $this->deleteCache($cache_key);
  }

  /**
   * Generate key for task list caching
   * @param $list_id
   * @return string
   */
  protected function generateTaskListCacheKey($list_id) {
    return "todo_list_$list_id";
  }
}