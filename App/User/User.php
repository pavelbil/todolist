<?php

namespace App\User;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;


/**
 * Class User
 * @package App\User
 */
class User implements AdvancedUserInterface
{

  /**
   * Id
   *
   * @var integer
   */
  protected $id;
  /**
   * Email
   *
   * @var string
   */
  protected $email;
  /**
   * Encoded password
   *
   * @var string
   */
  protected $password;
  /**
   * Salt
   *
   * @var string
   */
  protected $salt;
  /**
   * @var string
   */
  protected $name = '';

  /**
   * @return bool
   */
  public function isAccountNonExpired()
  {
    return true;
  }

  /**
   * @return bool
   */
  public function isAccountNonLocked()
  {
    return true;
  }

  /**
   * @return bool
   */
  public function isCredentialsNonExpired()
  {
    return true;
  }

  /**
   * @return bool
   */
  public function isEnabled()
  {
    return true;
  }

  /**
   *
   */
  public function eraseCredentials()
  {}

  /**
   * @return array
   */
  public function getRoles()
  {
    return array();
  }

  /**
   * @return mixed
   */
  public function getPassword()
  {
    return $this->password;
  }

  /**
   * @return mixed
   */
  public function getSalt()
  {
    return $this->salt;
  }

  /**
   * @param $salt
   */
  public function setSalt($salt)
  {
    $this->salt = $salt;
  }

  /**
   * @return mixed
   */
  public function getUsername()
  {
    return $this->email;
  }

  /**
   * @param string $email
   */
  public function __construct($email)
  {
    $this->setEmail($email);
    $this->setSalt(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36));
  }

  /**
   * Returns the name, if set, or else "Anonymous {id}".
   *
   * @return string
   */
  public function getDisplayName()
  {
    return $this->name ?: 'Anonymous ' . $this->id;
  }

  /**
   * Set the user ID.
   *
   * @param int $id
   */
  public function setId($id)
  {
    $this->id = $id;
  }

  /**
   * Get the user ID.
   *
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @return string The user's email address.
   */
  public function getEmail()
  {
    return $this->email;
  }
  /**
   * @param string $email
   */
  public function setEmail($email)
  {
    $this->email = $email;
  }

  /**
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set the encoded password.
   *
   * @param string $password
   */
  public function setPassword($password)
  {
    $this->password = $password;
  }

  /**
   * Validate the user object.
   *
   * @return array An array of error messages, or an empty array if there were no errors.
   */
  public function validate()
  {
    $errors = array();
    if (!$this->getEmail()) {
      $errors['email'] = 'Email address is required.';
    } else if (!strpos($this->getEmail(), '@')) {
      $errors['email'] = 'Email address appears to be invalid.';
    } else if (strlen($this->getEmail()) > 100) {
      $errors['email'] = 'Email address can\'t be longer than 100 characters.';
    }
    if (!$this->getPassword()) {
      $errors['password'] = 'Password is required.';
    } else if (strlen($this->getPassword()) > 255) {
      $errors['password'] = 'Password can\'t be longer than 255 characters.';
    }
    if (strlen($this->getName()) > 100) {
      $errors['name'] = 'Name can\'t be longer than 100 characters.';
    }
    return $errors;
  }
}