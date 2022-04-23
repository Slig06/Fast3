<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      11.11.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('spec','/spec [force] : make [force] playerself to spec');
registerCommand('play','/play : make playerself to play');
registerCommand('blue','/blue [play] : put playerself in blue team [and play]');
registerCommand('red','/red [play] : put playerself in red team [and play]');


// spec [force]
function chat_spec($author, $login, $params){
	global $_players,$_is_relay;
	if($_is_relay)
		return;
	$msg = localeText(null,'server_message').localeText(null,'interact');

	if($_players[$login]['Forced'] && !$_players[$login]['ForcedByHimself']){
		$msg .= 'Not possible : someone else forced your play state.';

	}else{
		addCall($login,'ForceSpectator',$login,1);

		if(isset($params[0]) && $params[0]=='force'){
			$msg .= 'you are forced spectator now.';
			$_players[$login]['ForcedByHimself'] = true;

		}else{
			addCall($login,'ForceSpectator',$login,0);
			$msg .= 'you are spectator now.';
		}
	}
	addCall(null,'ChatSendToLogin', $msg, $login);
}


// play [force|password]
function chat_play($author, $login, $params){
	global $_players,$_ServerOptions,$_is_relay;
	if($_is_relay)
		return;
	$msg = localeText(null,'server_message').localeText(null,'interact');

	if($_players[$login]['Forced'] && !$_players[$login]['ForcedByHimself']){
		$msg .= 'Not possible : someone else forced your play state.';

	}else{
		
		if($_ServerOptions['CurrentLadderMode'] > 0 &&
			 isset($_players[$login]['PlayerInfo']['LadderStats']['PlayerRankings'][0]['Score']) &&
			 $_players[$login]['PlayerInfo']['LadderStats']['PlayerRankings'][0]['Score'] < $_ServerOptions['LadderServerLimitMin'] &&
			 !verifyAdmin($login)){
			// refuse /play to players with to low ladder level for server
			$_players[$login]['PlayRights'] = false;
			$msg .= 'Ladder level too low : '.floor($_players[$login]['PlayerInfo']['LadderStats']['PlayerRankings'][0]['Score']).' pts for '.$_ServerOptions['LadderServerLimitMin'].' min.';

		}elseif(!$_players[$login]['PlayRights']){
			// test if player was already player before, or know the pass
			if($_ServerOptions['Password']=='')
				$_players[$login]['PlayRights'] = true;
			elseif(!isset($params[0]))
				$msg .= 'You need to give the server play password (/play password).';
			elseif($params[0]!=$_ServerOptions['Password'])
				$msg .= 'Wrong password.';
			else
				$_players[$login]['PlayRights'] = true;
		}

		if($_players[$login]['PlayRights']){

			if(addCall($login,'ForceSpectator',$login,2)!==-1){
				if(isset($params[0]) && $params[0]=='force'){
					$msg .= 'you are forced player now.';
					$_players[$login]['ForcedByHimself'] = true;
					
				}else{
					addCall($login,'ForceSpectator',$login,0);
					$msg .= 'you are player now.';
				}
			}else{
				$msg .= "Sorry, you can't become player actually !...";
			}
		}
	}
	addCall(null,'ChatSendToLogin', $msg, $login);
}


// blue [play]
function chat_blue($author, $login, $params){
	global $_players,$_is_relay;
	if($_is_relay)
		return;
	$msg = localeText(null,'server_message').localeText(null,'interact');

	addCall($login,'ForcePlayerTeam',$login,0);
	$msg .= 'You are now in blue team. ';

	if(isset($params[0]) && $params[0]=='play'){
		if($_ServerOptions['Password']=='')
			$_players[$login]['PlayRights'] = true;

		if($_players[$login]['PlayRights']){
			if($_players[$login]['Forced'] && !$_players[$login]['ForcedByHimself']){
				$msg .= 'Not possible to play : someone else forced your play state.';
				
			}else{
				addCall($login,'ForceSpectator',$login,2);
				addCall($login,'ForceSpectator',$login,0);
				
			}
		}else{
			$msg .= 'You need to give the server play password. Use: /play password.';
		}
	}
	addCall(null,'ChatSendToLogin', $msg, $login);
}


// red [play]
function chat_red($author, $login, $params){
	global $_players,$_is_relay;
	if($_is_relay)
		return;
	$msg = localeText(null,'server_message').localeText(null,'interact');

	addCall($login,'ForcePlayerTeam',$login,1);
	$msg .= 'You are now in red team. ';

	if(isset($params[0]) && $params[0]=='play'){
		if($_ServerOptions['Password']=='')
			$_players[$login]['PlayRights'] = true;

		if($_players[$login]['PlayRights']){
			if($_players[$login]['Forced'] && !$_players[$login]['ForcedByHimself']){
				$msg .= 'Not possible to play : someone else forced your play state.';
				
			}else{
				addCall($login,'ForceSpectator',$login,2);
				addCall($login,'ForceSpectator',$login,0);
				
			}
		}else{
			$msg .= 'You need to give the server play password. Use: /play password.';
		}
	}
	addCall(null,'ChatSendToLogin', $msg, $login);
}



?>
