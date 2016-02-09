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
		<h1><?= sprintf('{t}PAGE_HOME_HEADER{/t}', $user->getNick() == '' ? 'guest' : $user->getNick()) ?></h1>

		<h2>{t}PAGE_HOME_HEADER_LOGIN{/t}</h2>
		<form method="post">
			<input type="text" name="login_nick" placeholder="{t}FORM_LOGIN_NICK{/t}"><br>
			<input type="password" name="login_password" placeholder="{t}FORM_LOGIN_PASSWORD{/t}"><br>
			<input type="submit" value="{t}FORM_LOGIN_SUBMIT{/t}">
		</form>

		<h2>{t}PAGE_HOME_HEADER_REGISTER{/t}</h2>
		<form method="post">
			<input type="text" name="register_nick" placeholder="{t}FORM_REGISTER_NICK{/t}"><br>
			<input type="password" name="register_password" placeholder="{t}FORM_REGISTER_PASSWORD{/t}"><br>
			<input type="password" name="register_password_retype" placeholder="{t}FORM_REGISTER_PASSWORD_RETYPE{/t}"><br>
			<input type="text" name="register_email" placeholder="{t}FORM_REGISTER_EMAIL{/t}"><br>
			<input type="submit" value="{t}FORM_REGISTER_SUBMIT{/t}">
		</form>

		<h1><?php include KURO_LOCATION . '/inc/lorem-ipsum-line.php' ?></h1>
		<p><?php include KURO_LOCATION . '/inc/lorem-ipsum-paragraph.php' ?></p>

		<a href="<?= CURRENT_PATH ?>/set=lang-en">lang-en</a>
		<a href="<?= CURRENT_PATH ?>/set=lang-szl">lang-szl</a>
		<br>
		<a href="<?= CURRENT_PATH ?>/set=logout">logout</a>
		<br>
		<a href="<?= GLOBAL_ROOT ?>/admin">admin</a>

		<pre>
<?php
$data = "hello";

#foreach (hash_algos() as $v) {
#        $r = hash($v, $data, false);
#        printf("%-12s %3d %s\n", $v, strlen($r), $r);
#}

$test1 = '';
$test2 = 'world';

echo $test1 ?: $test2;

?>
		</pre>
	</main>
<?php $loader->loadTranslatedModule('inc/global-footer'); ?>
</body>
</html>
