<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('info','/info : show current and next game/challenge infos');


// user requests current and next track info
function chat_info($author, $login, $params){
	global $_GameInfos,$_NextGameInfos,$_ChallengeInfo,$_RoundCustomPoints,$_roundspoints_rule,$_roundspoints_points,$_is_relay,$_FGameMode,$_NextFGameMode;

	// current track
	$msg = localeText(null,'interact').'Current Game: '.stringInfos($_GameInfos,$_ChallengeInfo,$_FGameMode);
	// next track
	if(!$_is_relay)
		$msg .= "\n".localeText(null,'server_message').localeText(null,'interact').'Next Game: '.stringInfos($_NextGameInfos,null,$_NextFGameMode);

	if($_GameInfos['GameMode'] == ROUNDS || $_GameInfos['GameMode'] == CUP ||
		 $_NextGameInfos['GameMode'] == ROUNDS || $_NextGameInfos['GameMode'] == CUP){
		// show custom points
		if(isset($_RoundCustomPoints[0])){
			if(!isset($_roundspoints_rule))
				$_roundspoints_rule = '';

			if(isset($_RoundCustomPoints[1]) && $_RoundCustomPoints[0] == 1 && $_RoundCustomPoints[1] == 0 && isset($_roundspoints_points[$_roundspoints_rule])){
				// special limited roundspoints case
				$msg .= localeText(null,'server_message').localeText(null,'interact')."\nCustom points: ".$_roundspoints_rule.' $n'.implode(',',$_roundspoints_points[$_roundspoints_rule]).'... (tmp: 1,0...)';
			}elseif(isset($_RoundCustomPoints[1]) && $_RoundCustomPoints[0] == 0 && $_RoundCustomPoints[1] == 0 && isset($_roundspoints_points[$_roundspoints_rule])){
				// special limited roundspoints case for FWarmUp
				$msg .= localeText(null,'server_message').localeText(null,'interact')."\nCustom points: ".$_roundspoints_rule.' $n'.implode(',',$_roundspoints_points[$_roundspoints_rule]).'... (tmp: 0,0...)';
			}elseif($_RoundCustomPoints[0]>0 || !isset($_roundspoints_points[$_roundspoints_rule])){
				// else if custom is not null
				$msg .= localeText(null,'server_message').localeText(null,'interact')."\nCustom points: ".$_roundspoints_rule.' $n'.implode(',',$_RoundCustomPoints).'...';
			}else{
				// else supose that it's handle by roundspoints and show it
				$msg .= localeText(null,'server_message').localeText(null,'interact')."\nCustom points: ".$_roundspoints_rule.' $n'.implode(',',$_roundspoints_points[$_roundspoints_rule]).'...';
			}
		}
	}
	// send message to user who wrote command
	addCall(null,'ChatSendServerMessageToLogin', $msg, $login);
}
?>
