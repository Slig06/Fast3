<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      08.07.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('plist','/plist : list players and id');
registerCommand('pl','/plist : list players and id');

// playerlist
function chat_plist($author, $login, $params){
	global $_PlayerList,$_players;
	$player = array();
	$msg = localeText(null,'server_message').'Players: ';
	$sep = '';
	foreach($_PlayerList as $player){
		if(isset($_players[$player['Login']]['Relayed']) && $_players[$player['Login']]['Relayed'])
			$col = '$afa';
		else
			$col = '$ffa';
		$msg .= $sep.$col.'[$fff'.$player['PlayerId'].'$m$faa,'.$player['Login'].$col.']'.stripColors($player['NickName']);
		$sep = ", ";
	}
	// send message to user who wrote command
	addCall(null,'ChatSendToLogin', $msg, $login);
}

function chat_pl($author, $login, $params){
	chat_plist($author, $login, $params);
}
?>
