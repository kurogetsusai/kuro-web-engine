<?php

///////////////////////  K U R O   W E B   E N G I N E  ////////////////////////

namespace Kuro;

define('DEBUG_STATUS_UNDEFINED'    , 0);
define('DEBUG_STATUS_OK'           , 1);
define('DEBUG_STATUS_NOTICE'       , 2);
define('DEBUG_STATUS_WARNING'      , 3);
define('DEBUG_STATUS_ERROR'        , 4); # calls die()
define('DEBUG_STATUS_ERROR_NOT_DIE', 5); # doesn't call die() - use only if you know it's safe!
define('DEBUG_STATUS_DEPRECATED'   , 6);

class Loader
{
	private $actions = [];

	public $param;

	public function __construct($kuroLocation, $cmd, $debugEnabled = false)
	{
		$this->kuro_run_time = microtime();
		$this->kuro_location = $kuroLocation;
		$this->param = explode('/', $cmd);
		$this->debug = $debugEnabled;

		if ($debugEnabled) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
			ini_set('track_errors'  , 1);
			ini_set('html_errors'   , 1);
			set_error_handler(array($this, 'debugErrorHandler'));
			register_shutdown_function(array($this, 'debugRegisterShutdown'));
			$this->debugLog(__METHOD__, 'Debug mode is active.', DEBUG_STATUS_NOTICE);
			$this->debugLog(__METHOD__, 'Load module: lib/loader', DEBUG_STATUS_OK);
		} else {
			error_reporting(E_NONE);
			ini_set('display_errors', 0);
			ini_set('track_errors'  , 0);
			ini_set('html_errors'   , 0);
		}

		$this->actions = array(
			array('/^lang-/', $this, 'actionChangeLanguage')
		);
	}

	public function addActions($newActions)
	{
		$this->actions = array_merge($this->actions, $newActions);
	}

	public function processActions()
	{
		# TODO this should check all params, not just the last one
		# then it will be necessary to fix reloading, bc unset removes only the last param
		# (line 74: unset($this->param[count($this->param) - 1]);)

		# all params starting with 'set=' are supposed to be processed
		if (preg_match('/^set=/', end($this->param))) {
			# trim the leading 'set='
			$action = mb_substr(end($this->param), 4);

			# cycle through all actions
			foreach ($this->actions as $item) {
				# if action matches
				if (preg_match($item[0], $action)) {
					# run action's function
					# if it returns true, then remove the last 'set=' param and reload the page
					# otherwise don't
					if ($item[1]->$item[2]($action)) {
						# remove that param
						unset($this->param[count($this->param) - 1]);
						# reload
						$cmd = implode('/', $this->param);
						header('Location: ' . GLOBAL_ROOT . '/' . $cmd);
						exit();
					}
				}
			}
		}
	}

	public function actionChangeLanguage($action)
	{
		# example input: lang-en
		#                lang-szl

		$lang = mb_substr($action, 5);
		if ($lang != $this->getLanguage() && in_array($lang, $this->getLanguageList())) {
			$this->setLanguage($lang);
			$this->rememberLanguage();
		}

		# true = reload the page to get a new URL without that param
		return true;
	}

	public function loadModule($module, $require = true)
	{
		$file = $this->kuro_location . '/' . $module . '.php';

		if (is_readable($file)) {
			$status = DEBUG_STATUS_OK;
		} else {
			$status = $require ? DEBUG_STATUS_ERROR : DEBUG_STATUS_WARNING;
			$status_tmp = $require ? DEBUG_STATUS_ERROR_NOT_DIE : DEBUG_STATUS_WARNING;
			if (file_exists($file))
				$this->debugLog(__METHOD__, 'File exists, but is not readable, check permissions: ' . $file, $status_tmp);
			else
				$this->debugLog(__METHOD__, 'File does not exists: ' . $file, $status_tmp);
		}
		$this->debugLog(__METHOD__, 'Load module: ' . $module, $status);

		if ($status === DEBUG_STATUS_OK) {
			if ($require)
				require $file;
			else
				include $file;
		}
	}

	public function loadTranslatedModule($module, $require = false)
	{
		# It's complicated. If it works, don't touch it.
		# Don't try to optimize it, it's already quite fast.
		#
		# Translation arrays:
		# input:  loc/eng.ini
		# output: var/cache/loc_eng/loc/eng.ini_TIMESTAMP.php
		#
		# Translated modules:
		# input:  inc/dummy.php
		# output: var/cache/loc_eng/inc/dummy.php_TIMESTAMP.php

		$lang_cache_dir = $this->kuro_location . '/var/cache/loc_' . $this->lang;

		$translation_array_input_filename  = $this->kuro_location .
		'/loc/' . $this->lang . '.ini';
		$translation_array_output_filename = $lang_cache_dir .
		'/loc/' . $this->lang . '.ini_' . filemtime($translation_array_input_filename) . '.php';

		$translated_module_input_filename  = $this->kuro_location .
		'/' . $module . '.php';
		$translated_module_output_filename = $lang_cache_dir .
		'/' . $module . '.php_' . filemtime($translation_array_input_filename) .
		                    '_' . filemtime($translated_module_input_filename) . '.php';

		# make sure the translated module exists and is up to date
		if (!file_exists($translated_module_output_filename)) {
			# generate new translated module

			# make sure the translation array exists and is up to date
			if (!file_exists($translation_array_output_filename)) {
				# generate new translation array
				if (file_exists($translation_array_input_filename)) {
					# remove old files
					if (file_exists($lang_cache_dir)) {
						$it = new \RecursiveDirectoryIterator($lang_cache_dir, \RecursiveDirectoryIterator::SKIP_DOTS);
						$files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
						foreach ($files as $file)
							if ($file->isDir())
								rmdir($file->getRealPath());
							else
								unlink($file->getRealPath());
						rmdir($lang_cache_dir);
					}

					# create new array
					mkdir(dirname($translation_array_output_filename), 0775, true); // TODO change permissions?
					file_put_contents($translation_array_output_filename,
					'<?php $translation_array = ' . var_export(parse_ini_file($translation_array_input_filename), true) . ';', LOCK_EX);
				} else {
					$this->debugLog(__METHOD__, 'Cannot open translation file: loc/' . $this->lang . '.ini', $require ? DEBUG_STATUS_ERROR : DEBUG_STATUS_WARNING);
				}
			}

			if (file_exists($translated_module_input_filename)) {
				# remove old version of translated module
				array_map('unlink', glob($lang_cache_dir . '/' . $module . '.php_*.php'));

				# create new translated module
				if (!file_exists(dirname($translated_module_output_filename)))
					mkdir(dirname($translated_module_output_filename), 0775, true);

				file_put_contents($translated_module_output_filename,
				preg_replace_callback(
					'!\{t\}([^\{]+)\{/t\}!',
					function ($m) use (&$translation_array_output_filename) {
						static $translation_array = null;
						if ($translation_array === null)
							require $translation_array_output_filename;
						return isset($translation_array[$m[1]]) ? $translation_array[$m[1]] : $m[1];
					},
					file_get_contents($translated_module_input_filename)
				), LOCK_EX);
			} else {
				$this->debugLog(__METHOD__, 'Cannot open module file: ' . $module, $require ? DEBUG_STATUS_ERROR : DEBUG_STATUS_WARNING);
			}
		}

		# load translated module
		if ($this->debug) {
			if (is_readable($translated_module_output_filename))
				$status = DEBUG_STATUS_OK;
			else
				$status = $require ? DEBUG_STATUS_ERROR : DEBUG_STATUS_WARNING;
			$this->debugLog(__METHOD__, 'Load translated module: ' . $module, $status);
		}

		if ($require)
			require $translated_module_output_filename;
		else
			include $translated_module_output_filename;
	}

	public function setLanguage($lang)
	{
		$this->lang = $lang;
	}

	public function getLanguage()
	{
		return $this->lang;
	}

	public function rememberLanguage()
	{
		if (SESSION_ENABLE)
			$_SESSION['lang'] = $this->lang;
		if (COOKIE_ENABLE)
			setcookie('lang', $this->lang, time() + COOKIE_TIME);
	}

	public function remindLanguage()
	{
		$lang = DEFAULT_LANG;
		if (SESSION_ENABLE && isset($_SESSION['lang']) && in_array($_SESSION['lang'], $this->getLanguageList()))
			$lang = $_SESSION['lang'];
		elseif (COOKIE_ENABLE && isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $this->getLanguageList()))
			$lang = $_COOKIE['lang'];
		else {
			# get accepted language from header
			$langs = array_map('trim', explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']));

			# try to find a matching language
			$found = false;
			foreach ($langs as $key => $item) {
				# lowercase
				$item = strtolower($item);
				# some item on list are like 'en;q=0.9', so we need to cut it
				$pos = strpos($item, ';q');
				if ($pos !== false) {
					$item = substr($item, 0, $pos);
				}
				# update lang array
				$langs[$key] = $item;

				# check if a language is available
				if (in_array($item, $this->getLanguageList())) {
					$lang = $item;
					$found = true;
					break;
				}
			}

			# if the previous loop didn't find anything, we can try cutting things like 'en-gb' to 'en'
			if (!$found) {
				foreach ($langs as $key => $item) {
					$pos = strpos($item, '-');
					if ($pos !== false) {
						$item = substr($item, 0, $pos);
					}
					$langs[$key] = $item;

					if (in_array($item, $this->getLanguageList())) {
						$lang = $item;
						$found = true;
						break;
					}
				}
			}

			# if still not found, then the DEFAULT_LANG will be used
		}

		return $lang;
	}

	public function getLanguageList($hideUnusable = true)
	{
		$i = 0;
		$lang_list = array();
		foreach (glob($this->kuro_location . '/loc/*.ini') as $lang) {
			$path_len = mb_strlen($this->kuro_location . '/loc/');
			$lang = mb_substr($lang, $path_len, mb_strlen($lang) - $path_len - mb_strlen('.ini'));
			if ($lang == 'debug' and $hideUnusable) continue;
			$lang_list[$i] = $lang;
			++$i;
		}
		return $lang_list;
	}

	public function getPage()
	{
		if ($this->page == '') {
			if ($this->param[0] == '') {
				$this->page = DEFAULT_PAGE; # TODO
			} elseif (is_readable($this->kuro_location . '/page/' . $this->param[0] . '.php')) {
				$this->page = $this->param[0];
			} else {
				# TODO redirect to error 404
				$this->page = DEFAULT_PAGE; # tmp
			}
		}
		return $this->page;
	}

	public function getRandomAsciiString($length = 10, $include_space = false)
	{
		$str = '';
		while (--$length >= 0)
			$str .= chr(mt_rand($include_space ? 32 : 33, 126));

		return $str;
	}

# Debug functions ##############################################################

	public function debugLog($method, $message, $status = DEBUG_STATUS_UNDEFINED)
	{
		if (!DEBUG_MODE) return;
		$this->debug_log[$this->debug_log_i]['time']    = microtime() - $this->kuro_run_time;
		$this->debug_log[$this->debug_log_i]['method']  = $method;
		$this->debug_log[$this->debug_log_i]['message'] = $message;
		$this->debug_log[$this->debug_log_i]['status']  = $status;
		++$this->debug_log_i;

		if ($status == DEBUG_STATUS_ERROR) {
			$this->printDebugLog();
			die();
		}
	}

	public function printDebugLog()
	{
		if (!$this->debug)
			exit();

		# I know there shouldn't be any HTML, but this is a debug
		# function, so it have to be as simple as possible.

		echo '<link rel="stylesheet" type="text/css" href="' . GLOBAL_ROOT . '/css/main.css">';
		echo '<link rel="stylesheet" type="text/css" href="' . GLOBAL_ROOT . '/css/debug.css">';
		echo '<div id="kuro-debug-log">';
		echo '<table>';
		foreach ($this->debug_log as $item) {
			echo '<tr>';
			echo '<td>[' . number_format($item['time'], 6) . ']</td>';
			switch ($item['status']) {
			case DEBUG_STATUS_OK:
				echo '<td class="kuro-debug-status-ok">' . $item['message'] . ' <span class="kuro-debug-method">(' . $item['method'] . ')</span></td>';
				echo '<td>[<span class="kuro-debug-status-ok">OK</span>]</td>';
				break;
			case DEBUG_STATUS_NOTICE:
				echo '<td class="kuro-debug-status-notice">' . $item['message'] . ' <span class="kuro-debug-method">(' . $item['method'] . ')</span></td>';
				echo '<td>[<span class="kuro-debug-status-notice">NOTICE</span>]</td>';
				break;
			case DEBUG_STATUS_WARNING:
				echo '<td class="kuro-debug-status-warning">' . $item['message'] . ' <span class="kuro-debug-method">(' . $item['method'] . ')</span></td>';
				echo '<td>[<span class="kuro-debug-status-warning">WARNING</span>]</td>';
				break;
			case DEBUG_STATUS_ERROR:
			case DEBUG_STATUS_ERROR_NOT_DIE:
				echo '<td class="kuro-debug-status-error">' . $item['message'] . ' <span class="kuro-debug-method">(' . $item['method'] . ')</span></td>';
				echo '<td>[<span class="kuro-debug-status-error">ERROR</span>]</td>';
				break;
			case DEBUG_STATUS_DEPRECATED:
				echo '<td class="kuro-debug-status-deprecated">' . $item['message'] . ' <span class="kuro-debug-method">(' . $item['method'] . ')</span></td>';
				echo '<td>[<span class="kuro-debug-status-deprecated">DEPRECATED</span>]</td>';
				break;
			default:
				echo '<td></td>';
			}
			echo '</td>';
		}

		$time = number_format(microtime() - $this->kuro_run_time, 6);
		ob_start();
		echo '<pre>';
		echo '<span class="kuro-debug-env">Kuro\Loader::param</span> = ';
		var_dump($this->param);
		echo '<br><span class="kuro-debug-env">Kuro\Loader::lang</span> = ';
		var_dump($this->lang);
		echo '<br><span class="kuro-debug-env">Kuro\Loader::getLanguageList()</span> = ';
		var_dump($this->getLanguageList());
		echo '<br><span class="kuro-debug-env">$_GET</span> = ';
		var_dump($_GET);
		echo '<br><span class="kuro-debug-env">$_POST</span> = ';
		var_dump($_POST);
		echo '<br><span class="kuro-debug-env">$_SESSION</span> = ';
		var_dump($_SESSION);
		echo '<br><span class="kuro-debug-env">$_COOKIE</span> = ';
		var_dump($_COOKIE);
		echo '<br><span class="kuro-debug-env">$_SERVER</span> = ';
		var_dump($_SERVER);
		echo '</pre>';
		$report = ob_get_clean();
		echo
		'<tr>' .
			'<td>[' . $time . ']</td>' .
			'<td>Environment variables report:' . $report . '</td>' .
			'<td>[<span class="kuro-debug-status-notice">REPORT</span>]</td>' .
		'</tr>';
		echo '</table>';
	}

	public function debugErrorHandler($errno, $errstr, $errfile, $errline)
	{
		switch ($errno) {
		case E_USER_WARNING:
		case E_WARNING:
			$errstatus = DEBUG_STATUS_WARNING;
			break;
		case E_USER_NOTICE:
		case E_NOTICE:
			$errstatus = DEBUG_STATUS_NOTICE;
			break;
		case E_USER_DEPRECATED:
		case E_DEPRECATED:
			$errstatus = DEBUG_STATUS_DEPRECATED;
			break;
		default:
			$errstatus = DEBUG_STATUS_ERROR;
			break;
		}
		$errstr  = str_replace(KURO_LOCATION . '/', '', $errstr);
		$errfile = str_replace(KURO_LOCATION . '/', '', $errfile);
		$this->debugLog(__METHOD__, $errfile . ' error (' . $this->debugFriendlyErrorType($errno) . ') on line ' . $errline . ': ' . $errstr, $errstatus);
		if ($errstatus == DEBUG_STATUS_ERROR) {
			$this->printDebugLog();
			die();
		}
	}

	public function debugRegisterShutdown()
	{
		$errfile = 'unknown file';
		$errstr  = 'shutdown';
		$errno   = E_CORE_ERROR;
		$errline = 0;

		$error = error_get_last();

		if ($error !== NULL) {
			$errno   = $error['type'];
			$errfile = $error['file'];
			$errline = $error['line'];
			$errstr  = $error['message'];

			$this->debugErrorHandler($errno, $errstr, $errfile, $errline);
		}
	}

	private $kuro_location;
	private $kuro_run_time;
	private $lang;
	private $page;
	private $debug = false;
	private $debug_log;
	private $debug_log_i = 0;

	private function debugFriendlyErrorType($type)
	{
		switch($type)
		{
		case E_ERROR: // 1 //
			return 'E_ERROR';
		case E_WARNING: // 2 //
			return 'E_WARNING';
		case E_PARSE: // 4 //
			return 'E_PARSE';
		case E_NOTICE: // 8 //
			return 'E_NOTICE';
		case E_CORE_ERROR: // 16 //
			return 'E_CORE_ERROR';
		case E_CORE_WARNING: // 32 //
			return 'E_CORE_WARNING';
		case E_COMPILE_ERROR: // 64 //
			return 'E_COMPILE_ERROR';
		case E_COMPILE_WARNING: // 128 //
			return 'E_COMPILE_WARNING';
		case E_USER_ERROR: // 256 //
			return 'E_USER_ERROR';
		case E_USER_WARNING: // 512 //
			return 'E_USER_WARNING';
		case E_USER_NOTICE: // 1024 //
			return 'E_USER_NOTICE';
		case E_STRICT: // 2048 //
			return 'E_STRICT';
		case E_RECOVERABLE_ERROR: // 4096 //
			return 'E_RECOVERABLE_ERROR';
		case E_DEPRECATED: // 8192 //
			return 'E_DEPRECATED';
		case E_USER_DEPRECATED: // 16384 //
			return 'E_USER_DEPRECATED';
		}
		return $type;
	}
}
