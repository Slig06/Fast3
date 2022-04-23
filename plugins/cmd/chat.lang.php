<?php
//¤
registerCommand('lang','/lang xx : set language');


// lang
function chat_lang($author, $login, $params){
	global $_debug,$_Game,$_players,$_locale,$_locale_default;

	$msg = localeText(null,'server_message').localeText(null,'interact');
	if(!isset($params[0])){
		if(isset($_players[$login]['Language']))
			$msg .= localeText($login,'lang.current',$_players[$login]['Language'],$_locale_default);
		else
			$msg .= localeText($login,'lang.nocurrent',$_locale_default);
	}else{
		if(!isset($_locale[$params[0]]))
			$msg .= localeText($login,'lang.notavailable',$params[0]);
		else{
			if($_players[$login]['Language'] != $params[0]){
				$_players[$login]['Language'] = $params[0];
				addEvent('PlayerShowML',$login,false);
				addEvent('PlayerShowML',$login,true);
			}
			$msg .= localeText($login,'lang.set',$params[0]);
			players_count_languages();
		}
	}
	addCall(null,'ChatSendToLogin', $msg, $login);
}
?>
