<?php
//¤
registerCommand('hi','/hi [name(s)] : show a hello message');

// EMOTIC: user says hello
function chat_hi($author, $login, $params){
	if(isset($params[1])){
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.hi2'),
																localeText(null,'highlight').$params[0].' '.$params[1].localeText(null,'emotic').' !');
	}elseif(isset($params[0])){
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.hi1'),
																localeText(null,'highlight').$params[0].localeText(null,'emotic').' !');
	}else{
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.hi'));
	}
	addCall(null,'ChatSendServerMessageToLanguage',$msgarray);
}
?>
