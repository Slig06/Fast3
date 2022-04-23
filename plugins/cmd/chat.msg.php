<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.1 (First Automatic Server for Trackmania)
// Web:       
// Date:      02.08.2007
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('msg','/msg [login|player_id] message : send a private message, use /playerlist to have id');

// msg
function chat_msg($author, $login, $params){
	global $_PlayerList;

	if(isset($params[0]) && isset($params[1]) && $params[0]!='help'){
		$msg = '$ff0['.$author.'$m$ff0] '.$params[1];
		// send message to user who wrote command
		$id = -1;
		for($i = 0; $i < sizeof($_PlayerList); $i++){
			if($_PlayerList[$i]['PlayerId']==$params[0] || $_PlayerList[$i]['Login']==$params[0]){
				$id = $_PlayerList[$i]['PlayerId'];
				$idnick = $_PlayerList[$i]['NickName'];
				break;
			}
		}
		if($id>=0){
			addCall($login,'ChatSendToId', $msg, $id);
			addCall(null,'ChatSendToLogin', $params[1].'$0f0 -> '.$idnick.' (succeeded)', $login);
		}else{
			addCall(null,'ChatSendToLogin', $params[1].'$f00 (failed: bad id)', $login);
		}
		
	// help
	}else{
		$msg = localeText(null,'server_message') . localeText(null,'interact').'/msg [login|player_id] message : send a private message, use /playerlist to have id';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}


}

?>
