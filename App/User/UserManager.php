<?php
namespace App\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Doctrine\DBAL\Connection;
use Silex\Application;

class UserManager implements UserProviderInterface
{


  /** @var string */
  protected $userTableName = 'users';

  /** @var array */
  protected $userColumns = array(
    'id' => 'id',
    'email' => 'email',
    'password' => 'password',
    'salt' => 'salt',
    'name' => 'name',
  );

  /** @var User[] */
  protected $identityMap = array();

  /**
   * Loads the user for the given email address.
   *
   * Required by UserProviderInterface.
   *
   * @param string $username The username
   * @return UserInterface
   * @throws UsernameNotFoundException if the user is not found
   */
  public function loadUserByUsername($username)
  {
    $user = $this->findOneBy(array($this->getUserColumns('email') => $username));
    if (!$user) {
      throw new UsernameNotFoundException(sprintf('Email "%s" does not exist.', $username));
    }
    return $user;
  }

  /**
   * @param UserInterface $user
   * @return UserInterface
   * @throws UnsupportedUserException if the account is not supported
   */
  public function refreshUser(UserInterface $user)
  {
    if (!$this->supportsClass(get_class($user))) {
      throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
    }
    return $this->getUser($user->getId());
  }

  /**
   * @param string $class
   * @return Boolean
   */
  public function supportsClass($class)
  {
    return ($class === 'App\User\User') || is_subclass_of($class, 'App\User\User');
  }

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
  }

  /**
   * Get a User instance by its ID.
   *
   * @param int $id
   * @return User|null The User, or null if there is no User with that ID.
   */
  public function getUser($id)
  {
    return $this->findOneBy(array($this->getUserColumns('id') => $id));
  }

  /**
   * Get a User instance for the currently logged in User, if any.
   *
   * @return UserInterface|null
   */
  public function getCurrentUser()
  {
    if ($this->isLoggedIn()) {
      return $this->app['security.token_storage']->getToken()->getUser();
    }
    return null;
  }

  /**
   * Test whether the current user is authenticated.
   *
   * @return boolean
   */
  function isLoggedIn()
  {
    $token = $this->app['security.token_storage']->getToken();
    if (null === $token) {
      return false;
    }
    return $this->app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_REMEMBERED');
  }

  /**
   * Get a single User instance that matches the given criteria. If more than one User matches, the first result is returned.
   *
   * @param array $criteria
   * @return User|null
   */
  public function findOneBy(array $criteria)
  {
    $users = $this->findBy($criteria);
    if (empty($users)) {
      return null;
    }
    return reset($users);
  }

  /**
   * Find User instances that match the given criteria.
   *
   * @param array $criteria
   * @param array $options An array of the following options (all optional):<pre>
   *      limit (int|array) The maximum number of results to return, or an array of (offset, limit).
   *      order_by (string|array) The name of the column to order by, or an array of column name and direction, ex. array(time_created, DESC)
   * </pre>
   * @return User[] An array of matching User instances, or an empty array if no matching users were found.
   */
  public function findBy(array $criteria = array(), array $options = array())
  {
    // Check the identity map first.
    if (array_key_exists($this->getUserColumns('id'), $criteria)
      && array_key_exists($criteria[$this->getUserColumns('id')], $this->identityMap)) {
      return array($this->identityMap[$criteria[$this->getUserColumns('id')]]);
    }
    list ($common_sql, $params) = $this->createCommonFindSql($criteria);
    $sql = 'SELECT * ' . $common_sql;
    if (array_key_exists('order_by', $options)) {
      list ($order_by, $order_dir) = is_array($options['order_by']) ? $options['order_by'] : array($options['order_by']);
      $sql .= 'ORDER BY ' . $this->conn->quoteIdentifier($order_by) . ' ' . ($order_dir == 'DESC' ? 'DESC' : 'ASC') . ' ';
    }
    if (array_key_exists('limit', $options)) {
      list ($offset, $limit) = is_array($options['limit']) ? $options['limit'] : array(0, $options['limit']);
      $sql .=   ' LIMIT ' . (int) $limit . ' ' .' OFFSET ' . (int) $offset ;
    }
    $data = $this->conn->fetchAll($sql, $params);
    $users = array();
    foreach ($data as $userData) {
      if (array_key_exists($userData[$this->getUserColumns('id')], $this->identityMap)) {
        $user = $this->identityMap[$userData[$this->getUserColumns('id')]];
      } else {
        $user = $this->hydrateUser($userData);
        $this->identityMap[$user->getId()] = $user;
      }
      $users[] = $user;
    }
    return $users;
  }

  /**
   * Get SQL query fragment common to both find and count querires.
   *
   * @param array $criteria
   * @return array An array of SQL and query parameters, in the form array($sql, $params)
   */
  protected function createCommonFindSql(array $criteria = array())
  {
    $params = array();
    $sql = 'FROM ' . $this->conn->quoteIdentifier($this->userTableName). ' ';

    $first_crit = true;
    foreach ($criteria as $key => $val) {
      $sql .= ($first_crit ? 'WHERE' : 'AND') . ' ' . $key . ' = :' . $key . ' ';
      $params[$key] = $val;
      $first_crit = false;
    }
    return array ($sql, $params);
  }



  /**
   * @param string $column
   * @return string
   */
  public function getUserColumns($column = ""){
    return ($column == "") ? $this->userColumns : $this->userColumns[$column];
  }

  /**
   * Reconstitute a User object from stored data.
   *
   * @param array $data
   * @return User
   * @throws \RuntimeException if database schema is out of date.
   */
  protected function hydrateUser(array $data)
  {
    /** @var User $user */
    $user = new User($data['email']);
    $user->setId($data['id']);
    $user->setPassword($data['password']);
    $user->setSalt($data['salt']);
    $user->setName($data['name']);
    return $user;
  }

  /**
   * Insert a new User instance into the database.
   *
   * @param User $user
   */
  public function insert(User $user)
  {

    $queryBuilder = $this->conn->createQueryBuilder();
    $queryBuilder
      ->insert($this->userTableName)
      ->values(
        array(
          'email' => '?',
          'password' => '?',
          'salt' => '?',
          'name' => '?'
        )
      )
      ->setParameter(0, $user->getEmail())
      ->setParameter(1, $user->getPassword())
      ->setParameter(2, $user->getSalt())
      ->setParameter(3, $user->getName());

    $queryBuilder->execute();
    $user->setId($this->conn->lastInsertId());
    $this->identityMap[$user->getId()] = $user;
  }

  /**
   * Factory method for creating a new User instance.
   *
   * @param string $email
   * @param string $plainPassword
   * @param string $name
   * @param array $roles
   * @return User
   */
  public function createUser($email, $plainPassword, $name = null)
  {
    $user = new User($email);
    if (!empty($plainPassword)) {
      $user->setPassword($this->encodeUserPassword($user, $plainPassword));
    }
    if ($name !== null) {
      $user->setName($name);
    }
    return $user;
  }

  /**
   * Encode a plain text password for a given user. Hashes the password with the given user's salt.
   *
   * @param User $user
   * @param string $password A plain text password.
   * @return string An encoded password.
   */
  public function encodeUserPassword(User $user, $password)
  {
    $encoder = $this->getEncoder($user);
    return $encoder->encodePassword($password, $user->getSalt());
  }


  /**
   * Get the password encoder to use for the given user object.
   *
   * @param UserInterface $user
   * @return PasswordEncoderInterface
   */
  protected function getEncoder(UserInterface $user)
  {
    return $this->app['security.encoder_factory']->getEncoder($user);
  }
  /**
   * Validate a user object.
   * @param User $user
   * @return array An array of error messages, or an empty array if the User is valid.
   */
  public function validate(User $user)
  {
    $errors = $user->validate();
    // Ensure email address is unique.
    $duplicates = $this->findBy(array($this->getUserColumns('email') => $user->getEmail()));
    if (!empty($duplicates)) {
      foreach ($duplicates as $dup) {
        if ($user->getId() && $dup->getId() == $user->getId()) {
          continue;
        }
        $errors['email'] = 'An account with that email address already exists.';
      }
    }
    return $errors;
  }

  /**
   * Log in as the given user.
   *
   * @param User $user
   */
  public function loginAsUser(User $user)
  {
    if (null !== ($current_token = $this->app['security.token_storage']->getToken())) {
      $providerKey = method_exists($current_token, 'getProviderKey') ? $current_token->getProviderKey() : $current_token->getSecret();
      $token = new UsernamePasswordToken($user, null, $providerKey);
      $this->app['security.token_storage']->setToken($token);
      $this->app['user'] = $user;
    }
  }

}