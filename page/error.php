<?php
global $loader;

if (isset($loader->param[1]) and $loader->param[0] == 'error' and $loader->param[1] != '') {
	$loader->http_code = $loader->param[1];
}

http_response_code($this->http_code);
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
		<h1>Error <?= $this->http_code ?></h1>
	</main>
<?php $loader->loadTranslatedModule('inc/global-footer'); ?>
</body>
</html>
