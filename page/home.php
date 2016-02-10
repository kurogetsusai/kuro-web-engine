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

		<h1><?php include KURO_LOCATION . '/inc/lorem-ipsum-line.php' ?></h1>
		<p><?php include KURO_LOCATION . '/inc/lorem-ipsum-paragraph.php' ?></p>

		<a href="<?= GLOBAL_ROOT ?>/admin">admin</a>

		<pre>
<?php
#foreach (hash_algos() as $v) {
#        $r = hash($v, $data, false);
#        printf("%-12s %3d %s\n", $v, strlen($r), $r);
#}
?>
		</pre>
	</main>
<?php $loader->loadTranslatedModule('inc/global-footer'); ?>
</body>
</html>
