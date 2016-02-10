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
		<h1>{t}PAGE_REGISTER_HEADER{/t}</h1>
		<form method="post">
			<h3>{t}PAGE_REGISTER_HEADER_REQUIRED{/t}</h3>
			<input type="text" name="register_nick" placeholder="{t}PAGE_REGISTER_FORM_NICK{/t}"><br>
			<input type="password" name="register_password" placeholder="{t}PAGE_REGISTER_FORM_PASSWORD{/t}"><br>
			<input type="password" name="register_password_retype" placeholder="{t}PAGE_REGISTER_FORM_PASSWORD_RETYPE{/t}"><br>
			<input type="text" name="register_email" placeholder="{t}PAGE_REGISTER_FORM_EMAIL{/t}"><br>
			<h3>{t}PAGE_REGISTER_HEADER_OPTIONAL{/t}</h3>
			<input type="text" name="register_name" placeholder="{t}PAGE_REGISTER_FORM_NAME{/t}"><br>
			<br>
			<input type="submit" value="{t}PAGE_REGISTER_FORM_SUBMIT{/t}">
		</form>
	</main>
<?php $loader->loadTranslatedModule('inc/global-footer'); ?>
</body>
</html>
