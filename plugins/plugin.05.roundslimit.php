<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST3.2 Rounds and Team limit alternative : fixed rounds number !
// 
if(!$_is_relay) registerPlugin('roundslimit',5);

// $_roundslimit_rule = -1; // standard possible values: -1 or positive number of rounds
// $_teamroundslimit_rule = -1; // standard possible values: -1 or positive number of rounds


//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function roundslimitInit($event){
	global $_debug,$_GameInfos,$_roundslimit_rule,$_teamroundslimit_rule;
	
	if(!isset($_roundslimit_rule))
		$_roundslimit_rule = -1; // standard possible values: -1 or positive number of rounds
	if(!isset($_teamroundslimit_rule))
		$_teamroundslimit_rule = -1; // standard possible values: -1 or positive number of rounds
}


//--------------------------------------------------------------
// Get Fast infos for relays
function roundslimitGetInfosForRelay($event,$relaylogin,$state){
	global $_debug,$_RelayInfos,$_roundslimit_rule,$_teamroundslimit_rule;
	if($_debug>8) console("roundslimitGetInfosForRelay:: ");
	$_RelayInfos['roundslimit']['rule'] = $_roundslimit_rule;
	$_RelayInfos['roundslimit']['teamrule'] = $_teamroundslimit_rule;
}


//--------------------------------------------------------------
// Fast Infos from master (to relay)
function roundslimitDatasFromMaster($event,$data){
	if(isset($data['roundslimit'])){
		global $_debug,$_roundslimit_rule,$_teamroundslimit_rule;
		if($_debug>3) console("roundslimitDatasFromMaster:: ".print_r($data['roundslimit'],true));

		if(isset($data['roundslimit']['rule']))
			$_roundslimit_rule = $data['roundslimit']['rule'];
		if(isset($data['roundslimit']['teamrule']))
			$_teamroundslimit_rule = $data['roundslimit']['teamrule'];
	}
}


//--------------------------------------------------------------
// PlayerConnect :
//--------------------------------------------------------------
function roundslimitPlayerConnect($event,$login){
	global $_debug,$_GameInfos,$_roundslimit_rule,$_teamroundslimit_rule,$_players_roundplayed_current,$_players;

	// roundslimit_rule activated and in supported modes
	if($_GameInfos['GameMode'] == ROUNDS && $_roundslimit_rule > 0){
		// send welcome message to player
		$msg = localeText(null,'server_message')
		.localeText($login,'roundslimit.announce',$_roundslimit_rule,$_players_roundplayed_current);
		addCall(null,'ChatSendServerMessageToLogin', $msg, $login);

	}elseif($_GameInfos['GameMode'] == TEAM && $_teamroundslimit_rule > 0){
		// send welcome message to player
		$msg = localeText(null,'server_message')
		.localeText($login,'roundslimit.announce',$_teamroundslimit_rule,$_players_roundplayed_current);
		addCall(null,'ChatSendServerMessageToLogin', $msg, $login);
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function roundslimitBeginRace($event,$GameInfos){
	global $_debug,$_GameInfos,$_roundslimit_rule,$_teamroundslimit_rule;

	// roundslimit_rule activated and in supported modes
	if($_GameInfos['GameMode'] == ROUNDS && $_roundslimit_rule > 0){
		if($_debug>2) console("roundslimit.Event[$event] for Rounds");
		//console("roundslimitBeginRace - SetRoundPointsLimit: 0");
		addCall(true,'SetRoundPointsLimit',0);

	}elseif($_GameInfos['GameMode'] == TEAM && $_teamroundslimit_rule > 0){
		if($_debug>2) console("roundslimit.Event[$event] for Team");
		//console("roundslimitBeginRace - SetTeamPointsLimit: 0");
		addCall(true,'SetTeamPointsLimit',0);
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function roundslimitBeginRound($event){
	global $_debug,$_GameInfos,$_roundslimit_rule,$_teamroundslimit_rule,$_players_roundplayed_current,$_WarmUp,$_FWarmUp;
	if($_WarmUp || $_FWarmUp > 0)
		return;
	// roundslimit_rule activated and in supported modes
	if($_GameInfos['GameMode'] == ROUNDS && $_roundslimit_rule > 0){
		if($_debug>2) console("roundslimit.Event[$event] for Rounds");

		// end of game ?
		if($_players_roundplayed_current > $_roundslimit_rule){
			if($_debug>0) console("roundslimitBeginRound:: Next ! - rule: {$_roundslimit_rule} - _players_roundplayed_current: {$_players_roundplayed_current}");
			addCall(true,'NextChallenge');
			
		}else{
			// send  message to player
			$msg = localeText(null,'server_message')
			.localeText(null,'roundslimit.round',$_players_roundplayed_current,$_roundslimit_rule);
			addCall(null,'ChatSendServerMessage', $msg);
		}

	}elseif($_GameInfos['GameMode'] == TEAM && $_teamroundslimit_rule > 0){
		if($_debug>2) console("roundslimit.Event[$event] for Team");

		// end of game ?
		if($_players_roundplayed_current > $_teamroundslimit_rule){
			if($_debug>0) console("roundslimitBeginRound:: Next ! - rule: {$_teamroundslimit_rule} - roundplayed_current: {$_players_roundplayed_current}");
			addCall(true,'NextChallenge');
			
		}else{
			// send  message to player
			$msg = localeText(null,'server_message')
			.localeText(null,'roundslimit.round',$_players_roundplayed_current,$_teamroundslimit_rule);
			addCall(null,'ChatSendServerMessage', $msg);
		}
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function roundslimitBeforeEndRound($event,$delay){
	global $_debug,$_GameInfos,$_roundslimit_rule,$_teamroundslimit_rule,$_players_roundplayed_current,$_players_round_finished,$_players,$_WarmUp,$_FWarmUp;
	if($_WarmUp || $_FWarmUp > 0 || $delay >= 0)
		return;
	// only at real final BeforeEndRound (ie $delay < 0)

	// roundslimit_rule activated and in supported modes
	if($_GameInfos['GameMode'] == ROUNDS && $_roundslimit_rule > 0){
		if($_debug>2) console("roundslimit.Event[$event] for Rounds");

		// end of game ? (note: need to be EndRound_Post to have $_players_roundplayed_current accurate :: still true ???)
		if($_players_roundplayed_current >= $_roundslimit_rule && $_players_round_finished > 0){
			// end of map
			if($_debug>0) console("roundslimitEndRound:: Next ! - finished: {$_players_round_finished} - rule: {$_roundslimit_rule} - roundplayed_current: {$_players_roundplayed_current}");
			addCall(true,'NextChallenge');
		}

	}elseif($_GameInfos['GameMode'] == TEAM && $_teamroundslimit_rule > 0){
		if($_debug>2) console("roundslimit.Event[$event] for Team");

		// end of game ? (note: need to be EndRound_Post to have $_players_roundplayed_current accurate)
		if($_players_roundplayed_current >= $_teamroundslimit_rule && $_players_round_finished > 0){
			// end of map
			if($_debug>0) console("roundslimitEndRound:: Next ! - finished: {$_players_round_finished} - rule: {$_teamroundslimit_rule} - roundplayed_current: {$_players_roundplayed_current}");
			addCall(true,'NextChallenge');
		}
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function roundslimitEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_GameInfos,$_players_roundplayed_current,$_roundslimit_rule,$_teamroundslimit_rule,$_WarmUp,$_FWarmUp;

	if($_WarmUp || $_FWarmUp > 0)
		return;

	// roundslimit_rule activated and in supported modes
	if($_GameInfos['GameMode'] == ROUNDS && $_roundslimit_rule > 0){
		if($_debug>2) console("roundslimit.Event[$event] for Rounds");

		if($_players_roundplayed_current >= $_roundslimit_rule){
			$msg = localeText(null,'server_message')
			.localeText(null,'roundslimit.finish',$_roundslimit_rule);
			addCall(null,'ChatSendServerMessage', $msg);
		}

		//console("roundslimitBeginRace - SetRoundPointsLimit: 0");
		addCall(true,'SetRoundPointsLimit',0);

	}elseif($_GameInfos['GameMode'] == TEAM && $_teamroundslimit_rule > 0){
		if($_debug>2) console("roundslimit.Event[$event] for Team");

		if($_players_roundplayed_current >= $_teamroundslimit_rule){
			$msg = localeText(null,'server_message')
			.localeText(null,'roundslimit.finish',$_teamroundslimit_rule);
			addCall(null,'ChatSendServerMessage', $msg);
		}

		//console("roundslimitBeginRace - SetTeamPointsLimit: 0");
		addCall(true,'SetTeamPointsLimit',0);
	}
}


?>
