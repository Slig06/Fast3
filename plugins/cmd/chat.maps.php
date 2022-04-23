<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      30.10.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('maps','/maps [n]: list next challenges');

// maps list
function chat_maps($author, $login, $params){

	$maxi = 3;
	if(isset($params[0]) && is_numeric($params[0]))
		$maxi = $params[0]+0;

	$msg = localeText(null,'server_message').'Next challenges: ';
	$msg .= stringMapList($maxi);

	// send message to user who wrote command
	addCall(null,'ChatSendToLogin', $msg, $login);
}

?>
