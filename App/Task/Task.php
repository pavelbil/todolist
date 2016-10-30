<?php

namespace App\Task;


class Task
{
  protected $id;
  protected $list_id;
  protected $name;
  protected $is_completed;
  protected $created_by;

  /**
   * Task constructor.
   * @param $list_id
   * @param $name
   */
  public function __construct($list_id, $name)
  {
    $this->list_id = $list_id;
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param mixed $id
   */
  public function setId($id)
  {
    $this->id = $id;
  }

  /**
   * @return mixed
   */
  public function getListId()
  {
    return $this->list_id;
  }

  /**
   * @param mixed $list_id
   */
  public function setListId($list_id)
  {
    $this->list_id = $list_id;
  }

  /**
   * @return mixed
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getIsCompleted()
  {
    return $this->is_completed;
  }

  /**
   * @param mixed $is_completed
   */
  public function setIsCompleted($is_completed)
  {
    $this->is_completed = (boolean)$is_completed;
  }

  /**
   * @return mixed
   */
  public function getCreatedBy()
  {
    return $this->created_by;
  }

  /**
   * @param mixed $created_by
   */
  public function setCreatedBy($created_by)
  {
    $this->created_by = $created_by;
  }

  /**
   * Validate the task object.
   *
   * @return array An array of error messages, or an empty array if there were no errors.
   */
  public function validate()
  {
    $errors = array();
    if (!$this->getName()) {
      $errors['name'] = 'Task name is required.';
    } else if (strlen($this->getName()) > 255) {
      $errors['name'] = 'Task name can\'t be longer than 255 characters.';
    }

    return $errors;
  }

}