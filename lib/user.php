<?php
namespace Kuro;

class User
{
	# PRIVATE ##############################

	# constructor data
	private $loader;
	private $db;
	private $use_session;
	private $session_time;
	private $use_cookie;
	private $cookie_time;
	private $password_cost;
	private $global_salt;

	# state
	private $logged_in = false;
	private $request_data_result = null;

	# these should be known after login
	private $sessions  = [];
	private $id        = null;
	private $nick      = null;    # required when registering a new user
	private $passhash  = null;
	private $salt      = null;
	private $name      = null;

	# optional data
	private $password  = null;    # required when registering a new user
	private $email     = null;    # required when registering a new user

	# Want to add a new optional data?
	#   - Just grep for "email" and do the same with your new variable.
	# Want to add a new required data?
	#   - Add an optional data.
	#   - Add a new validation code to register().
	#   - Add new exceptions to processRequestData(), inc/global-info-box
	#     and loc/* files. Don't forget to update register()'s comment.
	# Want to add a new data, which should be known after each login?
	#   - Add an optional or required data.
	#   - Make sure your new data is loaded by logInUsingPassword() and
	#     logInUsingSession().
	# Don't forget to update user UI and loc/* files! And the database, ofc.

	private function generateNewSalt()
	{
		$this->salt = $this->loader->getRandomAsciiString(32);
	}

	private function getSaltedPassword()
	{
		return $this->password . $this->salt . $this->global_salt;
	}

	private function calcPassHash()
	{
		$this->passhash = password_hash(
			$this->getSaltedPassword(),
			PASSWORD_DEFAULT,
			[
				'cost' => $this->password_cost
			]
		);
	}

	private function getUserIdFromDb($nick)
	{
		# Returns user ID or null if user does not exists.

		$stmt = $this->db->base->prepare('SELECT id, nick FROM user WHERE LOWER(nick) = LOWER(:nick) LIMIT 1');
		$stmt->execute(array(':nick' => $nick));
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		$this->nick = $nick;
		$this->id   = $row['id'];

		//if ($this->id === null)
		//	$this->loader->debugLog(__METHOD__, 'User `' . $this->nick . '` does not exists.', DEBUG_STATUS_WARNING);

		return $this->id;
	}

	# PUBLIC ###############################

	public function __construct($loader, $db,
		$password_cost = USER_PASSWORD_COST, $global_salt  = GLOBAL_SALT,
		$use_session   = SESSION_ENABLE    , $session_time = SESSION_TIME,
		$use_cookie    = COOKIE_ENABLE     , $cookie_time  = COOKIE_TIME)
	{
		$this->loader        = $loader;
		$this->db            = $db;
		$this->password_cost = $password_cost;
		$this->global_salt   = $global_salt;
		$this->use_session   = $use_session;
		$this->session_time  = $session_time;
		$this->use_cookie    = $use_cookie;
		$this->cookie_time   = $cookie_time;
	}

	public function getActions()
	{
		return array(
			array('/^logout$/', $this, 'actionLogOut')
		);
	}

	public function actionLogOut($action)
	{
		$this->logOut();

		# true = reload the page to get a new URL without that param
		return true;
	}

	public function isLoggedIn()
	{
		return $this->logged_in;
	}

	public function getRequestDataResult()
	{
		return $this->request_data_result;
	}

	public function getNick()
	{
		return $this->nick;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getUserDataFromDb(/* ??? */)
	{
		// TODO this function will replace some code in
		// logInUsingPassword() and logInUsingSession(),
		// the parameter should be $nick XOR $id
	}

	public function processRequestData()
	{
		# This function tries to log in using session.
		# If it doesn't work, then it tries to log in using cookie.
		# If it doesn't work either, it tries to log in using POST data.
		# And then, if user is still not logged in, it tries to register
		# a new user using POST data.
		# It doesn't return anything.

		# logout has a higher priority and is handled by a loader
		# action, which reloads the page after logout so when logging
		# out, so if the logout flag is set, it never reaches this
		# point, so I just ignore logout

		$login_status = false;
		$result = 0;

		# try to log in using session
		if (
			!$login_status &&
			$this->use_session &&
			isset($_SESSION['user_id']) &&
			isset($_SESSION['session_hash'])
		) {
			switch ($this->logInUsingSession($_SESSION['user_id'], $_SESSION['session_hash'])) {
			case 0:
				$login_status = true;
				break;
			case 1:
				$result = 11;
				break;
			case 2:
				$result = 12;
				break;
			default:
				$result = 10;
			}
		}

		# try to log in using cookie
		if (
			!$login_status &&
			$this->use_cookie &&
			isset($_COOKIE['user_id']) &&
			isset($_COOKIE['session_hash'])
		) {
			switch ($this->logInUsingCookie($_COOKIE['user_id'], $_COOKIE['session_hash'])) {
			case 0:
				$login_status = true;
				break;
			case 1:
				$result = 101;
				break;
			case 2:
				$result = 102;
				break;
			default:
				$result = 100;
			}
		}

		# try to log in using password
		if (
			!$login_status &&
			isset($_POST['login_nick']) &&
			isset($_POST['login_password'])
		) {
			switch ($this->logInUsingPassword($_POST['login_nick'], $_POST['login_password'])) {
			case 0:
				$login_status = true;
				break;
			case 1:
				$result = 1001;
				break;
			case 2:
				$result = 1002;
				break;
			case 3:
				$result = 1003;
				$login_status = true;
				break;
			default:
				$result = 1000;
			}
		}

		# try to register a new user
		if (
			!$login_status &&
			isset($_POST['register_nick']) &&
			isset($_POST['register_password']) &&
			isset($_POST['register_password_retype']) &&
			isset($_POST['register_email'])
		) {
			if ($_POST['register_password'] != $_POST['register_password_retype']) {
				$result = 11000;
			} else {
				switch ($this->register($_POST['register_nick'], $_POST['register_password'], $_POST['register_email'], $_POST['register_name'])) {
				case 0:
					$login_status = true;
					break;
				case 1:
					$result = 10001;
					break;
				case 2:
					$result = 10002;
					break;
				case 3:
					$result = 10003;
					break;
				case 10:
					$result = 10004;
					break;
				case 20:
					$result = 10005;
					break;
				case 30:
					$result = 10006;
					break;
				case 100:
					$result = 10007;
					$login_status = true;
					break;
				case 200:
					$result = 10008;
					$login_status = true;
					break;
				case 300:
					$result = 10009;
					$login_status = true;
					break;
				default:
					$result = 10000;
				}
			}
		}

		$this->request_data_result = $login_status ? 0 : $result;
	}

	public function register($nick, $password, $email, $name)
	{
		# Return codes:
		#   0 - OK
		#   1 - wrong nick (contains '/' or starts with 'set=')
		#   2 - wrong password (empty)
		#   3 - wrong email
		#  X0 - inherited from saveUserToBase(), multiplied by 10
		# X00 - inherited from logInUsingPassword(), multiplied by 100

		# validate nick
		if (preg_match("/\//", $nick) or preg_match("/^set=/", $nick)) {
			$this->loader->debugLog(__METHOD__, 'Cannot register user `' . $nick . '`, nick name doesn\'t meet the requirements.', DEBUG_STATUS_WARNING);
			return 1;
		}

		# validate password
		if ($password == '') {
			$this->loader->debugLog(__METHOD__, 'Cannot register user `' . $nick . '`, password is empty.', DEBUG_STATUS_WARNING);
			return 2;
		}

		# validate email
		if ($email == '' or strpos($email, '@') === false) {
			$this->loader->debugLog(__METHOD__, 'Cannot register user `' . $nick . '`, e-mail `' . $email . '` is not considered valid.', DEBUG_STATUS_WARNING);
			return 3;
		}

		# name is optional and it doesn't need to be validated anyway

		# everything's ok, register user
		$this->nick     = $nick;
		$this->password = $password;
		$this->email    = $email;
		$this->name     = $name;

		$this->generateNewSalt();
		$this->calcPassHash();
		$ret = $this->saveUserToDb() * 10;

		# if everything's OK, try to log in
		if ($ret === 0) {
			$this->loader->debugLog(__METHOD__, 'Registered user `' . $nick . '`.', DEBUG_STATUS_OK);
			$ret = $this->logInUsingPassword($this->nick, $this->password) * 100;
		}

		# clear user data
		if (!$this->isLoggedIn()) {
			$this->nick     = null;
			$this->password = null;
			$this->email    = null;
			$this->name     = null;
		}

		return $ret;
	}

	public function saveUserToDb()
	{
		# Return codes:
		# 0 - OK
		# 1 - tried to register new user, but user already exists
		# 2 - tried to update user, but user does not exist or has different ID
		# 3 - database error

		# check if we should register a new user or update existing one
		if ($this->id === null) {
			# user does not exists, register new user
			if ($this->getUserIdFromDb($this->nick) === null) {
				# prepare data (not all data is required)
				$data_array;
				if ($this->nick     !== null) $data_array[':nick']  = 'nick';
				if ($this->passhash !== null) $data_array[':pass']  = 'pass';
				if ($this->salt     !== null) $data_array[':salt']  = 'salt';
				if ($this->email    !== null) $data_array[':email'] = 'email';
				if ($this->name     !== null) $data_array[':name']  = 'name';

				$sql_columns = '';
				$sql_values  = '';
				foreach ($data_array as $key => $value) {
					$sql_columns .= $value . ', ';
					$sql_values  .= $key   . ', ';
				}
				$sql_columns = trim($sql_columns, ', ');
				$sql_values  = trim($sql_values , ', ');

				# save to db
				$stmt = $this->db->base->prepare('INSERT INTO user (' . $sql_columns . ') VALUES (' . $sql_values . ')');
				$stmt->execute(array_intersect_key(
					array(':nick'  => $this->nick,
					      ':pass'  => $this->passhash,
					      ':salt'  => $this->salt,
					      ':email' => $this->email,
					      ':name'  => $this->name),
					$data_array
				));

				# check if it actually worked
				if ($stmt->rowCount() !== 1) {
					# cry ;_;
					$this->loader->debugLog(__METHOD__, 'User `' . $this->nick . '` has probably not been registered, wrong number of affected rows after query to database: `' . $stmt->rowCount() . '`', DEBUG_STATUS_WARNING);
					return 3;
				}
			} else {
				# user doesn't exists, we have a fake ID
				$this->id = null;
				$this->loader->debugLog(__METHOD__, 'Cannot register user `' . $this->nick . '`, user already exists.', DEBUG_STATUS_WARNING);
				return 1;
			}
		} else {
			# user exists, update info

			# check if the id is valid
			$id = $this->id;
			$this->getUserIdFromDb($this->nick);
			if ($id === $this->id) {
				# prepare data
				$data_array;
				if ($this->nick     !== null) $data_array[':nick']  = 'nick';
				if ($this->passhash !== null) $data_array[':pass']  = 'pass';
				if ($this->salt     !== null) $data_array[':salt']  = 'salt';
				if ($this->email    !== null) $data_array[':email'] = 'email';
				if ($this->name     !== null) $data_array[':name']  = 'name';
				$sql_columns_and_values = '';
				foreach ($data_array as $key => $value)
					$sql_columns_and_values .= $value . '=' . $key . ', ';
				$sql_columns_and_values = trim($sql_columns_and_values, ', ');

				# save to db
				$stmt = $this->db->base->prepare('UPDATE user SET ' . $sql_columns_and_values . ' WHERE id=:id');
				$data_array[':id'] = 'id';
				$stmt->execute(array_intersect_key(
					array(':id'    => $this->id,
					      ':nick'  => $this->nick,
					      ':pass'  => $this->passhash,
					      ':salt'  => $this->salt,
					      ':email' => $this->email,
					      ':name'  => $this->name),
					$data_array
				));

				# check if that worked
				if ($stmt->rowCount() !== 1) {
					$this->loader->debugLog(__METHOD__, 'User `' . $this->nick . '` has probably not been updated, wrong number of affected rows after query to database: `' . $stmt->rowCount() . '`', DEBUG_STATUS_WARNING);
					return 3;
				}
			} else {
				# the id is fake
				$this->id = $id;
				$this->loader->debugLog(__METHOD__, 'Cannot update user `' . $this->nick . '`, user does not exist or has different ID in database.', DEBUG_STATUS_WARNING);
				return 2;
			}
		}
		return 0;

		# All work and no play makes Jack a dull boy
		# All work nad no play makes Jack a dull boy
		# All work and no paly makes Jack a dull boy
		# All work and no play makes Jack a dull boy
		# All work and no play make jack a dull boy
		# All wrok and no play makes Jack a dull boy
		# All work and no play makes Javk a dull boy
		# All work and no play makes Jack a dull boy
	}

	public function logInUsingPassword($nick, $password)
	{
		# Return codes:
		# 0 - OK
		# 1 - wrong nick (user does not exists in database)
		# 2 - wrong password
		# 3 - OK, but cannot save session to database

		# check if user with that nick exists
		$stmt = $this->db->base->prepare('SELECT id, nick, pass, salt, name FROM user WHERE LOWER(nick) = LOWER(:nick) LIMIT 1');
		$stmt->execute(array(':nick' => $nick));
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		# nope
		if ($row['id'] == '') {
			$this->loader->debugLog(__METHOD__, 'Cannot log in as user `' . $nick . '`, wrong nick.', DEBUG_STATUS_WARNING);
			return 1;
		}

		# yep
		$this->nick     = $nick;
		$this->password = $password;
		$this->id       = $row['id'];
		$this->passhash = $row['pass'];
		$this->salt     = $row['salt'];
		$this->name     = $row['name'];

		# check login data
		if (!password_verify($this->getSaltedPassword(), $this->passhash)) {
			$this->nick     = null;
			$this->password = null;
			$this->id       = null;
			$this->passhash = null;
			$this->salt     = null;
			$this->name     = null;
			$this->loader->debugLog(__METHOD__, 'Cannot log in as user `' . $nick . '`, wrong password.', DEBUG_STATUS_WARNING);
			return 2;
		}

		$this->logged_in = true;
		$this->loader->debugLog(__METHOD__, 'Logged in as user `' . $nick . '` using password.', DEBUG_STATUS_OK);

		# check if passhash needs to be rehashed
		if (password_needs_rehash(
			$this->passhash,
			PASSWORD_DEFAULT,
			[
				'cost' => $this->password_cost
			]
		)) {
			$this->calcPassHash();
			$this->saveUserToDb();
		}

		# generate new session in database
		if ($this->use_session or $this->use_cookie) {
			$this->sessions[] = new \Kuro\Session($this->loader,
			                                      $this->db,
			                                      $this->session_time);
			if (end($this->sessions)->create($this->id,
			                                 $this->nick,
			                                 $this->salt) !== 0) {
				return 1;
			}

			$session_data = end($this->sessions)->getSessionData();

			# set $_SESSION
			if ($this->use_session) {
				$_SESSION['user_id']      = $session_data['user_id'];
				$_SESSION['session_hash'] = $session_data['hash'];
			}

			# set $_COOKIE
			if ($this->use_cookie) {
				setcookie('user_id'     , $session_data['user_id'], time() + $this->cookie_time, GLOBAL_ROOT);
				setcookie('session_hash', $session_data['hash']   , time() + $this->cookie_time, GLOBAL_ROOT);
			}
		}

		return 0;
	}

	public function logInUsingSession($user_id, $session_hash)
	{
		# Return codes:
		# 0 - OK
		# 1 - invalid session
		# 2 - user doesn't exist

		# check if session is valid
		$this->sessions[] = new \Kuro\Session($this->loader,
		                                      $this->db,
		                                      $this->session_time);
		if (end($this->sessions)->checkSession($user_id, $session_hash) === 0) {
			# get user info from the db
			$stmt = $this->db->base->prepare('SELECT id, nick, pass, salt, name FROM user WHERE id = :id LIMIT 1');
			$stmt->execute(array(':id' => $user_id));
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);

			# if there is no such user, then the session should be removed
			# (it may happen if a user has been removed, but his sessions are still valid)
			if (!isset($row['nick'])) {
				$stmt = $this->db->base->prepare('DELETE FROM session WHERE user_id = :user_id AND hash = :hash');
				$stmt->execute(array(':user_id' => $user_id,
				                     ':hash'    => $session_hash));

				unset($this->sessions[count($this->sessions) - 1]);

				if ($this->use_session) {
					unset($_SESSION['user_id']);
					unset($_SESSION['session_hash']);
				}

				$this->loader->debugLog(__METHOD__, 'Cannot log in as user ID `' . $user_id . '`, user does not exist.', DEBUG_STATUS_WARNING);
				return 2;
			}

			# get user data
			$this->id        = $user_id;
			$this->nick      = $row['nick'];
			$this->passhash  = $row['pass'];
			$this->salt      = $row['salt'];
			$this->name      = $row['name'];
			$this->logged_in = true;

			$this->loader->debugLog(__METHOD__, 'Logged in as user `' . $this->nick . '` using session.', DEBUG_STATUS_OK);
			return 0;
		} else {
			# session is not valid, just remove it
			unset($this->sessions[count($this->sessions) - 1]);

			if ($this->use_session) {
				unset($_SESSION['user_id']);
				unset($_SESSION['session_hash']);
			}

			$this->loader->debugLog(__METHOD__, 'Cannot log in as user ID `' . $user_id . '`, invalid session.', DEBUG_STATUS_WARNING);
			return 1;
		}
	}

	public function logInUsingCookie($user_id, $session_hash)
	{
		# Return codes:
		# inherited from logInUsingSession()

		# copy cookie data to session
		$_SESSION['user_id']      = $user_id;
		$_SESSION['session_hash'] = $session_hash;
		$this->loader->debugLog(__METHOD__, 'Restored session data from cookie.', DEBUG_STATUS_OK);

		# try to log in using session
		$ret = $this->logInUsingSession($user_id, $session_hash);
		switch ($ret) {
		case 0:
			break;
		case 1:
		case 2:
		default:
			# if cannot log in, then we don't need that garbage in cookies
			if ($this->use_cookie) {
				unset($_COOKIE['user_id']);
				unset($_COOKIE['session_hash']);
				setcookie('user_id'     , '', time() - 1);
				setcookie('session_hash', '', time() - 1);
			}
			$this->loader->debugLog(__METHOD__, 'Removed invalid session cookie.', DEBUG_STATUS_OK);
		}

		# return what logInUsingSession() returns
		return $ret;
	}

	public function logOut()
	{
		if ($this->isLoggedIn()) {
			if ($this->use_session) {
				# load user_id and session_hash, and remove then from session (and cookie)
				if ($this->use_cookie) {
					$user_id      = $_SESSION['user_id']      ?: $_COOKIE['user_id'];
					$session_hash = $_SESSION['session_hash'] ?: $_COOKIE['session_hash'];
					setcookie('user_id'     , '', time() - 1, GLOBAL_ROOT);
					setcookie('session_hash', '', time() - 1, GLOBAL_ROOT);
					unset($_COOKIE['user_id']);
					unset($_COOKIE['session_hash']);
				} else {
					$user_id      = $_SESSION['user_id'];
					$session_hash = $_SESSION['session_hash'];
				}
				unset($_SESSION['user_id']);
				unset($_SESSION['session_hash']);

				# check if session is valid; if it is, then delete it from the db
				$this->sessions[] = new \Kuro\Session($this->loader,
				                                      $this->db,
				                                      $this->session_time);
				if (end($this->sessions)->checkSession($user_id, $session_hash) === 0) {
					$stmt = $this->db->base->prepare('DELETE FROM session WHERE user_id = :user_id AND hash = :hash');
					$stmt->execute(array(':user_id' => $user_id,
					                     ':hash'    => $session_hash));
				}
			}

			# clear user data
			$this->logged_in           = false;
			$this->request_data_result = null;
			$this->sessions            = [];
			$this->id                  = null;
			$this->nick                = null;
			$this->passhash            = null;
			$this->salt                = null;
			$this->password            = null;
			$this->email               = null;
			$this->name                = null;
		}
	}
}

class Session {
	# PRIVATE ##############################

	# constructor data
	private $loader;
	private $db;
	private $session_time;

	# user data
	private $user_id;
	private $user_nick;
	private $user_salt;

	# session data
	#private $id - auto_increment
	#private $user_id; - redundant
	private $hash;
	private $expire;
	private $ip_address;
	private $browser_ua;

	private function calcSessionHash()
	{
		$this->hash = hash(
			'whirlpool',
			$this->user_id . $this->user_nick . $this->user_salt . $this->loader->getRandomAsciiString(16)
		);
	}

	# PUBLIC ###############################

	public function __construct($loader, $db, $session_time)
	{
		$this->loader       = $loader;
		$this->db           = $db;
		$this->session_time = $session_time;
	}

	public function getSessionData()
	{
		return array('user_id'    => $this->user_id,
		             'hash'       => $this->hash,
		             'expire'     => $this->expire,
		             'ip_address' => $this->ip_address,
		             'browser_ua' => $this->browser_ua);
	}

	public function create($user_id, $user_nick, $user_salt)
	{
		# Return codes:
		# 0 - OK
		# 1 - database error

		# get user data
		$this->user_id   = $user_id;
		$this->user_nick = $user_nick;
		$this->user_salt = $user_salt;

		# set session data - hash
		$this->calcSessionHash();

		# set session data - expire
		$this->expire = time() + $this->session_time;

		# set session data - ip_address
		$this->ip_address = $_SERVER['REMOTE_ADDR'] ?: '';

		# set session data - browser_ua
		$this->browser_ua = $_SERVER['HTTP_USER_AGENT'] ?: '';

		# save session to db
		$stmt = $this->db->base->prepare('INSERT INTO session (user_id, hash, expire, ip_address, browser_ua)' .
		'VALUES (:user_id, :hash, FROM_UNIXTIME(:expire), :ip_address, :browser_ua)');
		$stmt->execute(array(':user_id'     => $this->user_id,
		                     ':hash'        => $this->hash,
		                     ':expire'      => $this->expire,
		                     ':ip_address'  => $this->ip_address,
		                     ':browser_ua'  => $this->browser_ua));

		# check
		if ($stmt->rowCount() !== 1) {
			$this->loader->debugLog(__METHOD__, 'Cannot save session to database - user `' . $this->user_nick . '`.', DEBUG_STATUS_WARNING);
			return 1;
		}

		return 0;
	}

	public function checkSession($user_id, $session_hash)
	{
		# Return codes:
		# 0 - OK
		# 1 - invalid session
		# 2 - database error

		$stmt = $this->db->base->prepare('SELECT COUNT(*) FROM session WHERE user_id = :user_id AND hash = :hash AND expire >= FROM_UNIXTIME(:expire)');
		$stmt->execute(array(':user_id' => $user_id,
		                     ':hash'    => $session_hash,
		                     ':expire'  => time()));
		$row = $stmt->fetchColumn();

		if ($row === false) {
			$this->loader->debugLog(__METHOD__, 'Database error: bad query.', DEBUG_STATUS_WARNING);
			return 2;
		} elseif ($row === '0') {
			$this->loader->debugLog(__METHOD__, 'Session `' . $session_hash . '` is invalid.', DEBUG_STATUS_WARNING);
			return 1;
		} else {
			$this->user_id = $user_id;
			$this->hash    = $session_hash;
			return 0;
		}
	}
}

