<?php
//¤
registerCommand('pay','/pay <coppers> : to give coppers to server account.');


// ml
function chat_pay($author, $login, $params){
	global $_debug,$_Game,$_players,$_ServerOptions;
	
	$msg = localeText(null,'server_message').localeText(null,'interact')."/pay coppers [comment] : pay coppers to server account (3 min).";
	$cost = $params[0]+0;
	$comment = localeText(null,'interact').'Paid on  $z'.stripColors($_ServerOptions['Name'])
		.localeText(null,'interact').'  server by  $z'.$author.localeText(null,'interact').'  ('.$login.').$z$o$ff0';

	if($cost>2){
		addCall($login,'SendBill',$login,$cost,$comment,'');
	}else{
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}
?>
