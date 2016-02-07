<?php
// XXX REWRITING

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

	# these should be known after login
	private $sessions  = [];
	private $id        = null;
	private $nick      = null;
	private $salt      = null;
	private $passhash  = null;

	# optional data
	private $password  = null;
	private $email     = null;

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
			array('/^logout/', $this, 'logOut')
		);
	}

	public function getNick()
	{
		return $this->nick;
	}

	public function register($nick, $password, $email)
	{
		# Return codes:
		#   0 - OK
		#   1 - wrong nick (contains '/' or starts with 'set=')
		#   2 - wrong password (empty)
		#   3 - wrong email
		#  X0 - inherited from saveUserToBase(), multiplied by 10
		# X00 - inherited from logIn(), multiplied by 100

		$this->nick     = $nick;
		$this->password = $password;
		$this->email    = $email;

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
			return 2;
		}

		# everything's ok, register user
		$this->generateNewSalt();
		$this->calcPassHash();
		$ret = $this->saveUserToDb() * 10;

		# if everything's OK, try to log in
		if ($ret === 0) {
			$this->loader->debugLog(__METHOD__, 'Registered user `' . $nick . '`.', DEBUG_STATUS_OK);
			$ret = $this->logInUsingPassword($this->nick, $this->password) * 100;
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
					      ':email' => $this->email),
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
					      ':email' => $this->email),
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
		$stmt = $this->db->base->prepare('SELECT id, nick, pass, salt FROM user WHERE LOWER(nick) = LOWER(:nick) LIMIT 1');
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
		$this->salt     = $row['salt'];
		$this->passhash = $row['pass'];

		# check login data
		if (!password_verify($this->getSaltedPassword(), $this->passhash)) {
			$this->id       = null;
			$this->salt     = null;
			$this->passhash = null;
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
				setcookie('user_id'     , $session_data['user_id'], time() + $this->cookie_time);
				setcookie('session_hash', $session_data['hash']   , time() + $this->cookie_time);
			}
		}

		return 0;
	}

	public function logOut()
	{
		echo 'TEST LOGOUT';
	}
}

class Session {
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
}

