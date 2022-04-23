<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      19.07.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('admin','/admin list, add on_login|id, remove login, addlogin login',true);

//------------------------------------------
// Admin Commands
//------------------------------------------
function chat_admin($author, $author_login, $params){
	global $_AdminList,$_PlayerList,$doInfosNext;

	// verify if author is in admin list
	if(!verifyAdmin($author_login))
		return;

			
	if(isset($params[0]) && $params[0]=='list'){
		$msg = localeText(null,'server_message').localeText(null,'interact').'AdminList: ';
		$sep = '';
		foreach($_AdminList as $admin){
			$msg .= $sep.$admin;
			$sep = ',';
		}
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $author_login);

	}elseif(isset($params[0]) && $params[0]=='add'){
		foreach($_PlayerList as $player){
			if($player['PlayerId']==$params[1] || $player['Login']==$params[1] ||
				 (strlen($params[1])>2 && strncmp($player['Login'],$params[1],strlen($params[1]))==0)){
				if(addAdmin($player['Login']))
					$msg = localeText(null,'server_message') . localeText(null,'interact').'Admin added: '.$player['Login'];
				else
					$msg = localeText(null,'server_message') . localeText(null,'interact').'Was already admin: '.$player['Login'].' !';
				// send message to user who wrote command
				addCall(null,'ChatSendToLogin', $msg, $author_login);
				return;
			}
		}
		$msg = localeText(null,'server_message') . localeText(null,'interact')."Failed: login '{$params[1]}' not in connected players.";
		addCall(null,'ChatSendToLogin', $msg, $author_login);
		
	}elseif(isset($params[0]) && $params[0]=='addlogin'){
		if(isset($params[1]) && strlen($params[1])>0){
			$ladmin = strtolower($params[1]);
			if(addAdmin($ladmin))
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Admin added: '.$ladmin;
			else
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Was already admin: '.$ladmin.' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $author_login);
		}
		
	}elseif(isset($params[0]) && $params[0]=='remove'){
		if (removeAdmin($params[1]))
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Admin removed : '.$params[1];
		else
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Remove admin: '.$params[1].' failed !';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $author_login);
		
		// help
	}else{
		$msg = localeText(null,'server_message') . localeText(null,'interact').'/admin list, add on_login|id, remove login, addlogin login';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $author_login);
	}
}

?>


