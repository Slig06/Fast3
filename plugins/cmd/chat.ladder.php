<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      19.07.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('ladder','/ladder : show player ladder infos.');


// ladder
function chat_ladder($author, $login, $params){
	global $_debug,$_Game,$_players,$_ServerOptions;
	
	if(isset($_players[$login]['PlayerInfo']['LadderStats']['PlayerRankings'][0]['Score'])){
		$msg = localeText(null,'server_message').localeText(null,'interact')
			.'Ladder rank: '.$_players[$login]['PlayerInfo']['LadderStats']['PlayerRankings'][0]['Ranking']
			.', Points: '.$_players[$login]['PlayerInfo']['LadderStats']['PlayerRankings'][0]['Score'];
		
	}else{
		$msg = localeText(null,'server_message').localeText(null,'interact').'Ladder infos not available.';
	}
	addCall(null,'ChatSendToLogin', $msg, $login);
}
?>
