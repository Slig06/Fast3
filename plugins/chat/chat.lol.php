<?php
//¤
registerCommand('lol','/lol [name(s)] : show a lol message');
registerCommand('lool','/lool [name(s)] : show a lool message');


// EMOTIC: user laughs
function chat_lool($author, $login, $params, $params2){
	chat_lol($author, $login, $params, $params2, 3);
}

// EMOTIC: user laughs
function chat_lol($author, $login, $params, $params2, $lollevel=0){
	$lolnum = $lollevel + rand(0,4); // 0 to 7 !
	if(isset($params[1])){
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.lol.'.$lolnum),
																localeText(null,'highlight').$params[0].' '.$params[1].localeText(null,'emotic').' !');
	}elseif(isset($params[0])){
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.lol.'.$lolnum),
																localeText(null,'highlight').$params[0].localeText(null,'emotic').' !');
	}else{
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.lol.'.$lolnum));
	}
	addCall(null,'ChatSendServerMessageToLanguage',$msgarray);
}

?>
