<?php
// XXX TO CHECK

namespace Kuro;

class Database {
	public $base;

	public function __construct($loader)
	{
		$this->loader = $loader;
	}

	public function connect($host, $base, $login, $password, $engine = 'mysql', $charset = 'utf8')
	{
		try {
			$this->base = new \PDO($engine
				. ':host=' . $host
				. ';dbname=' . $base
				. ';charset=' . $charset,
				$login, $password);
			$this->loader->debugLog(__METHOD__, 'Database connected (' . $login . '@' . $host . ', base: ' . $base . ').', DEBUG_STATUS_OK);
			$this->base->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
		} catch (\PDOException $e) {
			$this->loader->loadTranslatedModule('inc/database-connection-error', true);
			$this->loader->debugLog(__METHOD__, 'Database connection error (' . $login . '@' . $host . ', base: ' . $base . ').', DEBUG_STATUS_ERROR);
		}
	}

	public function setup()
	{
		// TODO
	}

	private $loader;
	/*private $host;
	private $base;
	private $login;
	private $password;*/
}
