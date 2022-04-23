<?php
//¤
registerCommand('afk','/afk : show: '.localeText(null,'afk').' and go spec');


// EMOTIC: away from keyboard
function chat_afk($author, $login, $params){
	$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
															array('chat.afk'));
	addCall(null,'ChatSendServerMessageToLanguage',$msgarray);

	// put player spec
	chat_spec($author, $login, array());
}
?>
