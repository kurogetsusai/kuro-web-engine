<?php
global $loader;
global $user;
?>
<!DOCTYPE html>
<html lang="{t}GLOBAL_LANG{/t}">
<head>
	<title>{t}GLOBAL_TITLE{/t}</title>
<?php $loader->loadModule('inc/global-head'); ?>
</head>
<body>
<?php $loader->loadTranslatedModule('inc/global-header'); ?>
	<main id="page">
<?php $loader->loadTranslatedModule('inc/global-info-box'); ?>
		DEBUG_MODE = '<?= DEBUG_MODE ?>'<br>
		KURO_LOCATION = '<?= KURO_LOCATION ?>'<br>
		DEFAULT_LANG = '<?= DEFAULT_LANG ?>'<br>
		DEFAULT_PAGE = '<?= DEFAULT_PAGE ?>'<br>
		SESSION_ENABLE = '<?= SESSION_ENABLE ?>'<br>
		SESSION_TIME = '<?= SESSION_TIME ?>'<br>
		COOKIE_ENABLE = '<?= COOKIE_ENABLE ?>'<br>
		COOKIE_TIME = '<?= COOKIE_TIME ?>'<br>
		GLOBAL_SALT = '<?= GLOBAL_SALT ?>'<br>
		GLOBAL_DNT = '<?= GLOBAL_DNT ?>'<br>
		GLOBAL_ROOT = '<?= GLOBAL_ROOT ?>'<br>
		CURRENT_PATH = '<?= CURRENT_PATH ?>'<br>
		DATABASE_HOST = '<?= DATABASE_HOST ?>'<br>
		DATABASE_BASE = '<?= DATABASE_BASE ?>'<br>
		DATABASE_USER = '<?= DATABASE_USER ?>'<br>
		DATABASE_PASS = '<?= DATABASE_PASS ?>'<br>
		USER_PASSWORD_COST = '<?= USER_PASSWORD_COST ?>'<br>
	</main>
<?php $loader->loadTranslatedModule('inc/global-footer'); ?>
</body>
</html>
