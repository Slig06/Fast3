<?php
//¤
registerCommand('me','/me msg : show message from you');

// EMOTIC: player message
function chat_me($author, $login, $params){
	if(is_array($params) && count($params) > 0){
		$msg = implode(' ',$params);
		$msg = authorMeChat($login,$author).localeText(null,'emotic').'  '.$msg;
		addCall(null,'ChatSendServerMessage', $msg);
	}
}

?>