<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      16.01.2009
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('versions','/versions: show server version and players game version');

// maps list
function chat_versions($author, $login, $params){
	global $_players,$_Version;

	$msg = localeText(null,'server_message')."\$fffServer\$aaa: {$_Version['Build']} ({$_Version['Version']}). Games:";

	$sep = ' ';
	foreach($_players as &$pl){
		if($pl['Active']){
			if($pl['PlayerInfo']['ClientVersion']=='')
				$msg .= $sep."\$faa{$pl['Login']}: \$a222.11.11";
			else
				$msg .= $sep."\$fff{$pl['Login']}: \$aaa{$pl['PlayerInfo']['ClientVersion']}";
			$sep = '$fff, ';
		}
	}

	// send message to user who wrote command
	addCall(null,'ChatSendToLogin', $msg, $login);
}

?>
