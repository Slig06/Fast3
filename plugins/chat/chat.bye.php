<?php
//¤
registerCommand('bye','/bye [name(s)] : show a bye bye message');

// EMOTIC: user has to go
function chat_bye($author, $login, $params){
	if(isset($params[1])){
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.bye2'),
																localeText(null,'highlight').$params[0].' '.$params[1].localeText(null,'emotic').' !');
	}elseif(isset($params[0])){
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.bye1'),
																localeText(null,'highlight').$params[0].localeText(null,'emotic').' !');
	}else{
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.bye'));
	}
	addCall(null,'ChatSendServerMessageToLanguage',$msgarray);
}
?>
