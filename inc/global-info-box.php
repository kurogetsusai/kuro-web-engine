<div class="info-box">
<?php
global $user;

if (DEBUG_MODE)
	echo '<div class="notice">Debug mode is active.</div>';
if (SESSION_ENABLE)
	echo '<div class="notice">Session is enabled.</div>';
if (COOKIE_ENABLE)
	echo '<div class="notice">Cookies are enabled.</div>';

$login_msg = $user->getLoginMsg();
if ($login_msg != null) {
	echo '<div class="' . $login_msg[0] . '">' . $login_msg[1] . '</div>';
	unset($login_msg);
}
?>
</div>

