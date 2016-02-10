<?php
global $loader;
global $user;

# no entry for logged in
if ($user->isLoggedIn()) {
	header('Location: ' . GLOBAL_ROOT);
	exit();
}

?>
<!DOCTYPE html>
<html lang="{t}GLOBAL_LANG{/t}">
<head>
	<title>{t}GLOBAL_TITLE{/t}</title>
<?php $loader->loadModule('inc/global-head'); ?>
</head>
<body>
<?php $loader->loadTranslatedModule('inc/global-header'); ?>
	<main id="page" class="text-center">
<?php $loader->loadTranslatedModule('inc/global-info-box'); ?>
		<h1>{t}PAGE_LOGIN_HEADER{/t}</h1>
		<form method="post">
			<input type="text" name="login_nick" placeholder="{t}PAGE_LOGIN_FORM_NICK{/t}"><br>
			<input type="password" name="login_password" placeholder="{t}PAGE_LOGIN_FORM_PASSWORD{/t}"><br>
			<br>
			<input type="submit" value="{t}PAGE_LOGIN_FORM_SUBMIT{/t}">
		</form>
	</main>
<?php $loader->loadTranslatedModule('inc/global-footer'); ?>
</body>
</html>
