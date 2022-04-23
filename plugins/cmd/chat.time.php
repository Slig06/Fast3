<?php
//¤
registerCommand('time','/time : show actual server time');

// time
function chat_time($author, $login, $params){
	$time = date("H:i:s T (e,P)");
	$msg = localeText(null,'server_message') . localeText(null,'interact') . "Actual server-time is: " . localeText(null,'highlight') . $time;
	// send message to user who wrote command
	addCall(null,'ChatSendToLogin', $msg, $login);
}
?>
