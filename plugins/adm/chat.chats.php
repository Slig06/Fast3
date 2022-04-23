<?php
//¤

registerCommand('wu','/wu : show a GO GO GO message !',true);
registerCommand('go','/go : show a Warm-up message !',true);
registerCommand('break','/break x : show a break x minutes message !',true);

// admin say warmup
function chat_wu($author, $login, $params){
	$msgarray = multiLocaleText(array('chat.wu'));
	addCall(null,'ChatSendServerMessageToLanguage',$msgarray);
}

// admin say go
function chat_go($author, $login, $params){
	$msgarray = multiLocaleText(array('chat.go'));
	addCall(null,'ChatSendServerMessageToLanguage',$msgarray);
}

// admin say break
function chat_break($author, $login, $params){
	
	if(!isset($params[0]))
		$params[0] = '5';
	$msgarray = multiLocaleText(array('chat.break',''.$params[0]));
	addCall(null,'ChatSendServerMessageToLanguage',$msgarray);
}
?>
