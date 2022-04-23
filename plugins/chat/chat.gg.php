<?php
//¤
registerCommand('gg','/gg [name(s)] : show a good game message');

// EMOTIC: good game
function chat_gg($author, $login, $params){
  global $_players;

	// replace login by nickname if exists
	if(isset($params[0]) && isset($_players[$params[0]]['NickName']))
		$params[0] = $_players[$params[0]]['NickName'];

	if(isset($params[1])){
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.gg2'),
																localeText(null,'highlight').$params[0].' '.$params[1].localeText(null,'emotic').' !');
	}elseif(isset($params[0])){
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.gg1'),
																localeText(null,'highlight').$params[0].localeText(null,'emotic').' !');
	}else{
		$msgarray = multiLocaleText(authorChat($login,$author).localeText(null,'emotic'),
																array('chat.ggall'));
	}
	addCall(null,'ChatSendServerMessageToLanguage',$msgarray);
}
?>
