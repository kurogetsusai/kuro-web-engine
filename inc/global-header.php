<?php
global $loader;
global $user;
?>
<header>
	<div id="top-bar">
		<span id="top-bar-header"><a href="<?= GLOBAL_ROOT ?>">KURO Web Engine</a></span>
		<span class="top-bar-item">
			<div class="popup-menu">
				{t}TOPBAR_BUTTON_LANGUAGE{/t}<br>
				<div>
					<?php
					foreach ($loader->getLanguageList() as $item)
						echo '<a href="' . CURRENT_PATH . '/set=lang-' . $item . '">' . $item . '</a><br>';
					?>
				</div>
			</div>
		</span>
<?php if ($user->isLoggedIn()) { ?>
		<span class="top-bar-item">
			<div class="popup-menu">
				<a href="<?= GLOBAL_ROOT ?>/user/<?= $user->getNick() ?>"><?= $user->getName() ?: $user->getNick() ?></a><br>
				<div>
					<a href="<?= GLOBAL_ROOT ?>/user/<?= $user->getNick() ?>">{t}TOPBAR_USERMENU_PROFILE{/t}</a><br>
					<a href="<?= CURRENT_PATH ?>/set=logout">{t}TOPBAR_USERMENU_LOGOUT{/t}</a>
				</div>
			</div>
		</span>
<?php } else { ?>
		<span class="top-bar-item"><a href="<?= GLOBAL_ROOT ?>/login">{t}TOPBAR_BUTTON_LOGIN{/t}</a></span>
		<span class="top-bar-item"><a href="<?= GLOBAL_ROOT ?>/register">{t}TOPBAR_BUTTON_REGISTER{/t}</a></span>
<?php } ?>
		<!-- <span class="top-bar-item"><img id="top-bar-item-menu" src="<?= GLOBAL_ROOT ?>/img/icon-menu.png" alt="Menu"></span> -->
	</div>
</header>
