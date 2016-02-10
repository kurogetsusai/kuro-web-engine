<div class="info-box">
<?php
global $user;

if (DEBUG_MODE)
	echo '<div class="notice">{t}INFOBOX_DEBUG_ACTIVE{/t}</div>';
if (SESSION_ENABLE)
	echo '<div class="notice">{t}INFOBOX_SESSION_ENABLED{/t}</div>';
if (COOKIE_ENABLE)
	echo '<div class="notice">{t}INFOBOX_COOKIES_ENABLED{/t}</div>';

function getRequestDataMsg($code)
{
	switch ($code) {
	case 10:
		return array('error', '{t}PROCESS_REQUEST_DATA_SESSION_DEFAULT{/t}');
	case 11:
		return array('error', '{t}PROCESS_REQUEST_DATA_SESSION_EXPIRED{/t}');
	case 12:
		return array('error', '{t}PROCESS_REQUEST_DATA_SESSION_USER_DOESNT_EXIST{/t}');
	case 100:
		return array('error', '{t}PROCESS_REQUEST_DATA_COOKIE_DEFAULT{/t}');
	case 101:
		return array('error', '{t}PROCESS_REQUEST_DATA_COOKIE_EXPIRED{/t}');
	case 102:
		return array('error', '{t}PROCESS_REQUEST_DATA_COOKIE_USER_DOESNT_EXIST{/t}');
	case 1000:
		return array('error', '{t}PROCESS_REQUEST_DATA_LOGIN_DEFAULT{/t}');
	case 1001:
		return array('error', '{t}PROCESS_REQUEST_DATA_LOGIN_WRONG_NICK{/t}');
	case 1002:
		return array('error', '{t}PROCESS_REQUEST_DATA_LOGIN_WRONG_PASS{/t}');
	case 1003:
		return array('warning', '{t}PROCESS_REQUEST_DATA_LOGIN_CANNOT_SAVE_SESSION{/t}');
	case 10000:
		return array('error', '{t}PROCESS_REQUEST_DATA_REGISTER_DEFAULT{/t}');
	case 10001:
		return array('error', '{t}PROCESS_REQUEST_DATA_REGISTER_ILLEGAL_NICK{/t}');
	case 10002:
		return array('error', '{t}PROCESS_REQUEST_DATA_REGISTER_EMPTY_PASS{/t}');
	case 10003:
		return array('error', '{t}PROCESS_REQUEST_DATA_REGISTER_INVALID_EMAIL{/t}');
	case 10004:
		return array('error', '{t}PROCESS_REQUEST_DATA_REGISTER_NICK_TAKEN{/t}');
	case 10005:
		return array('error', '{t}PROCESS_REQUEST_DATA_REGISTER_CANNOT_UPDATE{/t}');
	case 10006:
		return array('error', '{t}PROCESS_REQUEST_DATA_REGISTER_DATABASE_ERROR{/t}');
	case 10007:
		return array('warning', '{t}PROCESS_REQUEST_DATA_REGISTER_SUCCESS_WRONG_NICK{/t}');
	case 10008:
		return array('warning', '{t}PROCESS_REQUEST_DATA_REGISTER_SUCCESS_WRONG_PASS{/t}');
	case 10009:
		return array('warning', '{t}PROCESS_REQUEST_DATA_REGISTER_SUCCESS_CANNOT_SAVE_SESSION{/t}');
	case 0:
	default:
		return null;
	}
}

$login_msg = getRequestDataMsg($user->getRequestDataResult());
if ($login_msg != null)
	echo '<div class="' . $login_msg[0] . '">' . $login_msg[1] . '</div>';
unset($login_msg);
?>
</div>
