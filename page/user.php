<?php
global $loader;
global $db;

// TODO getUserDataFromDb() doesn't work yet
$public_user = new \Kuro\User($loader, $db);
if (!isset($loader->param[1]) or !$public_user->getUserDataFromDb('nick', $loader->param[1])) {
	$loader->http_code = 404;
	$loader->loadTranslatedModule('page/error');
} else {
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
		<h1><?= $public_user->getNameOrNick() ?></h1>
		<p>
		ID: <?= $public_user->getId() ?><br>
		Nick: <?= $public_user->getNick() ?><br>
		E-mail: <?= $public_user->getEmail() ?><br>
		Name: <?= $public_user->getName() ?><br>
		</p>
	</main>
<?php $loader->loadTranslatedModule('inc/global-footer'); ?>
</body>
</html>
<?php } ?>
