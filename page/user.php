<?php
global $loader;
global $db;

// TODO getUserDataFromDb() doesn't work yet
#$public_user = new \Kuro\User($loader, $db);
#if ($public_user->getUserDataFromDb($loader->param[1]) === null) {
#	// TODO redirect to 404, dont forget to return http 404
#	header('Location: ' . GLOBAL_ROOT);
#	exit();
#}

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
		<h1><?= $loader->param[1] ?></h1>
	</main>
<?php $loader->loadTranslatedModule('inc/global-footer'); ?>
</body>
</html>
