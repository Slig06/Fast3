<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST3.2 plugin for multi round maps match with cumulative points
// 
//
if(!$_is_relay) registerPlugin('falsestart',24,1.0);

// How many max falsestart by player on each map ? (0=disable)
// $_MapFalsestart = 0;

// How many max falsestart by player on a match ? (0=disable)
// use falsestartResetMatchList()
// $_MatchFalsestart = 0;

// How much waiting time for player in netlost (ms) ?
//$_NetlostFalsestartTimeout = 50000;

// Max netlost time (ms) to consider it should make a falsestart ?
//$_MaxNetlostFalsestart = 60000;

// How many accepted giveup falsestart in the same round ? (number)
//$_MaxGiveUpFalsestartPerRound = 1;

// Accept giveup falsestart only at first round of map ? (true/false)
//$_GiveUpFalsestartOnlyAtFirstRound = true;

// Accept falsstart in warmup ?
//$_WarmUpFalsestart = false;

//--------------------------------------------------------------
// For match plugins : set max number of restart by player, 
// and reset the match flasestart players list
//--------------------------------------------------------------
function falsestartStartMatch($max){
	global $_falsestart_matchplayers,$_MatchFalsestart;
	$_falsestart_matchplayers = array();
	$_MatchFalsestart = $max;
	if($_MatchFalsestart < 1)
		$_MatchFalsestart = 0;
}


//--------------------------------------------------------------
// For match plugins : set max number of restart by player to 0 (disable)
//--------------------------------------------------------------
function falsestartEndMatch(){
	global $_falsestart_matchplayers,$_MatchFalsestart;
	$_falsestart_matchplayers = array();
	$_MatchFalsestart = 0;
}


//--------------------------------------------------------------
function falsestartInit($event){
	global $_debug,$_MapFalsestart,$_MatchFalsestart,$_NetlostFalsestartTimeout,$_MaxNetlostFalsestart,$_MaxGiveUpFalsestartPerRound,$_GiveUpFalsestartOnlyAtFirstRound,$_WarmUpFalsestart,$_IsFalsestart,$_falsestart_matchplayers,$_falsestart_mapplayers,$_falsestart_roundplayers,$_falsestart_testing,$_falsestart_prevround,$_falsestart_prevroundplayers,$_falsestart_currentround_giveups,$_falsestart_alert_xml;
	if($_debug>0) console("falsestart.Event[$event]");
	
	if(!isset($_MapFalsestart) || $_MapFalsestart < 1)
		$_MapFalsestart = 0;

	if(!isset($_MatchFalsestart) || $_MatchFalsestart < 1)
		$_MatchFalsestart = 0;

	if(!isset($_NetlostFalsestartTimeout) || $_NetlostFalsestartTimeout < 20000)
		$_NetlostFalsestartTimeout = 50000;

	if(!isset($_MaxNetlostFalsestart) || $_MaxNetlostFalsestart < 20000)
		$_MaxNetlostFalsestart = 60000;

	if(!isset($_MaxGiveUpFalsestartPerRound) || $_MaxGiveUpFalsestartPerRound < 0)
		$_MaxGiveUpFalsestartPerRound = 1;

	if(!isset($_GiveUpFalsestartOnlyAtFirstRound) || $_GiveUpFalsestartOnlyAtFirstRound !== false)
		$_GiveUpFalsestartOnlyAtFirstRound = true;

	if(!isset($_WarmUpFalsestart) || $_WarmUpFalsestart !== true)
		$_WarmUpFalsestart = false;

	$_IsFalsestart = false;
	$_falsestart_roundplayers = array();
	$_falsestart_prevround = -1;
	$_falsestart_prevroundplayers = array();
	$_falsestart_mapplayers = array();
	$_falsestart_matchplayers = array();
	$_falsestart_testing = 0; // 0=no test, 1=giveup test, 2=netlost test, 3=restarting
	$_falsestart_currentround_giveups = 0;

	$_falsestart_alert_xml = "<frame posn='0 9 0'>"
		."<quad  sizen='40 14' posn='0 0 0' halign='center' valign='center' style='Bgs1' substyle='BgWindow2'/>"
		."<label sizen='35 5' posn='0 0.5 1' halign='center' valign='center' textsize='7' textcolor='f00f' text='\$o\$s%s'/>"
		."</frame>";

	registerCommand('falsestart','/falsestart map #, match #, timeout #',false);
	registerCommand('fs','/falsestart map #, match #|init, timeout #',false);
}


//--------------------------------------------------------------
function falsestartPlayerConnect($event,$login){
	global $_mldebug,$_IsFalsestart,$_StatusCode;

	if($_IsFalsestart && $_StatusCode==4){
			falsestartUpdateAlertXml($login,'show');
	}
}


//--------------------------------------------------------------
function falsestartPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_IsFalsestart,$_StatusCode;

	if($_IsFalsestart && $_StatusCode==4){
			falsestartUpdateAlertXml($login,'show');
	}
}


//--------------------------------------------------------------
// Everysecond at beginning of round
//--------------------------------------------------------------
function falsestartEverysecond($event,$seconds){
	global $_debug,$_IsFalsestart,$_players,$_players_round_time,$_currentTime,$_StatusCode,$_WarmUp,$_FWarmUp,$_GameInfos,$_MapFalsestart,$_NetlostFalsestartTimeout,$_MaxNetlostFalsestart,$_falsestart_roundplayers,$_falsestart_testing,$_players_round_restarting;
	//console("falsestart.Event[$event]($seconds)");
	if($_StatusCode!=4 || $_falsestart_testing < 1 || $_players_round_restarting)
		return;
	
	$difftime = $_currentTime - $_players_round_time;
	if($_falsestart_testing == 1 && $difftime > 500){ // giveup falsestart max delay: 0.5s
		// end of giveup testing
		if($_IsFalsestart){
			// giveup netlost : restart round !
			falsestartRoundRestart('falsestart-giveup: '.implode(',',array_keys($_falsestart_roundplayers)));
			return;
		}
		$_falsestart_testing = 2;

	}elseif($_falsestart_testing == 2){
		// testing netlost...

		if($difftime < 13000){ // netlost falsestart max delay: 13s
			// test netlost
			if($difftime > 2500){ // really test netlost only after 2.5s
				foreach($_players as $login => &$pl){
					if($pl['Active'] && !$pl['IsSpectator'] && $pl['LatestNetworkActivity'] > 4000 && $pl['LatestNetworkActivity'] < $_MaxNetlostFalsestart){
						// player netlost
						falsestartHandle($login,$pl['LatestNetworkActivity'],'netlost:'.floor($pl['LatestNetworkActivity']/1000));
					}
				}
			}

		}else{
			// end of netlost testing
			if(!$_IsFalsestart){
				// no falsestart
				$_falsestart_testing = 0;

			}else{
				// netlost falsestart : restart round ! (in 1s to avoid any play/spec problem)
				falsestartRoundRestart('falsestart-netlost: '.implode(',',array_keys($_falsestart_roundplayers)),1000);
			}
		}
	}
}


//--------------------------------------------------------------
// PlayerFinish 0 at beginning, in rounds based modes
//--------------------------------------------------------------
function falsestartPlayerFinish($event,$login,$time){
	global $_debug,$_players,$_players_round_time,$_currentTime,$_StatusCode,$_WarmUp,$_FWarmUp,$_GameInfos,$_MapFalsestart,$_IsFalsestart,$_falsestart_roundplayers,$_falsestart_testing;
	//console("falsestart.Event[$event]('$login',$time)");

	if($_StatusCode!=4 || $_falsestart_testing < 1)
		return;
	//console("falsestart.Event[$event]('$login',$time) !!!".($_currentTime - $_players_round_time));

	if($time == 0 && !$_WarmUp && $_FWarmUp <= 0 && isset($_players[$login]['Active']) && 
		 $_players[$login]['Active'] && !$_players[$login]['IsSpectator']){
		// special case...
		if($_GameInfos['GameMode'] == CUP){
			if($_players[$login]['Score'] > $_GameInfos['CupPointsLimit']){
				// no false start : the player has winned the Cup and is not playing !
				return;

			}
			if($_debug>0){
				$msg = '';
				$sep = '';
				foreach($_players as $login => $pl){
					if(@$pl['Active']){
						$msg .= "{$sep}{$pl['Login']}={$pl['Score']}";
						$sep = ',';
					}
				}
				console("falsestartPlayerFinish({$login},{$time}):: {$msg}");
			}
		}

		if($_falsestart_testing == 1){
			//console("falsestart.Event[$event]('$login',$time) !!! ");
			// player hit 'del' ?!
			falsestartHandle($login,true,'giveup');
		}
		if($_IsFalsestart && $_falsestart_testing < 3 && 
			 isset($_falsestart_roundplayers[$login]) && $_falsestart_roundplayers[$login]!==true){
			console("falsestartPlayerFinish:: netlost player {$login} ok ???");
			$_falsestart_roundplayers[$login] = true;
		}
	}
}


//--------------------------------------------------------------
// BeginRace :
//--------------------------------------------------------------
function falsestartBeginRace_Post($event,$GameInfos){
	global $_debug,$_IsFalsestart,$_falsestart_mapplayers,$_falsestart_roundplayers,$_falsestart_prevround,$_falsestart_prevroundplayers,$_ChallengeInfo,$_PrevChallengeInfo;

	$_IsFalsestart = false;
	// reset map false starts counters
	$_falsestart_roundplayers = array();
	$_falsestart_prevround = -1;
	$_falsestart_prevroundplayers = array();

	if($_ChallengeInfo['UId']!=$_PrevChallengeInfo['UId'] || $_ChallengeInfo['FileName']!=$_PrevChallengeInfo['FileName']){
		// map changed : reset falsestart counters
		$_falsestart_mapplayers = array();
	}
}


//------------------------------------------
// BeginRound : 
//------------------------------------------
function falsestartBeginRound_Post(){
	global $_debug,$_IsFalsestart,$_MapFalsestart,$_GiveUpFalsestartOnlyAtFirstRound,$_GameInfos,$_WarmUp,$_FWarmUp,$_falsestart_roundplayers,$_falsestart_prevround,$_falsestart_prevroundplayers,$_falsestart_testing,$_players_round_current,$_falsestart_currentround_giveups,$_WarmUpFalsestart;

	// reset round falsestarts list
	$_IsFalsestart = false;
	if($_falsestart_prevround != $_players_round_current){
		// really new round
		$_falsestart_prevroundplayers = array();
		$_falsestart_currentround_giveups = 0;
	}else{
		$_falsestart_prevroundplayers = $_falsestart_roundplayers;
	}
	$_falsestart_prevround = $_players_round_current;
	$_falsestart_roundplayers = array();

	if($_MapFalsestart > 0){
		// remove false start info
		falsestartUpdateAlertXml(true,'hide');
	}
	
	if($_MapFalsestart > 0 && $_GameInfos['GameMode'] != TA && $_GameInfos['GameMode'] != STUNTS &&
		 ((!$_WarmUp && $_FWarmUp <= 0) || $_players_round_current <= 1)){
		// will test falsestarts
		$_falsestart_testing = 1;

		if($_players_round_current > 1 && $_GiveUpFalsestartOnlyAtFirstRound)
			$_falsestart_testing = 2;

		// no falsestart in warmup except is asked to
		if(!$_WarmUpFalsestart && ($_WarmUp || $_FWarmUp > 0))
			$_falsestart_testing = 0;
	}
}


//------------------------------------------
// EndRound : 
//------------------------------------------
function falsestartEndRound_Post(){
	global $_debug,$_IsFalsestart;

	if($_IsFalsestart){
		// falsestart restarting

		// remove false start info
		falsestartUpdateAlertXml(true,'hide');
	}
}


//------------------------------------------
// EndRace : 
//------------------------------------------
function falsestartEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_IsFalsestart,$_players_round_restarting;

	// should never happen except in case of netlost in Laps and the netlost player finally made a giveup, so the round/race finished
	if($_IsFalsestart && !$_players_round_restarting){
		console("falsestartEndRace:: restarting race (falsestart-netlost-late: ".implode(',',array_keys($_falsestart_roundplayers)).')');
		addCall(true,'ChallengeRestart');
	}
}


//--------------------------------------------------------------
//
//--------------------------------------------------------------
function falsestartRoundRestart($text,$delay=0){
	global $_debug,$_falsestart_testing;

	$_falsestart_testing = 3;
	$msg = localeText(null,'server_message').localeText(null,'interact').'$oRestart round...';
	addCall(null,'ChatSendServerMessage', $msg);
	// restart
	if($delay > 0){
		addEventDelay(2000,'Function','playersSpecialRoundRestart',$text);
	}else{
		playersSpecialRoundRestart($text);
	}
}


//--------------------------------------------------------------
// Handle false starts !
//--------------------------------------------------------------
function falsestartHandle($login,$fs_state,$text){
	global $_debug,$_players,$_players_round_time,$_currentTime,$_MapFalsestart,$_MatchFalsestart,$_IsFalsestart,$_falsestart_matchplayers,$_falsestart_mapplayers,$_falsestart_roundplayers,$_falsestart_prevroundplayers,$_falsestart_testing,$_NetlostFalsestartTimeout,$_falsestart_currentround_giveups,$_MaxGiveUpFalsestartPerRound;

	// init counters
	if(!isset($_falsestart_mapplayers[$login]))
		$_falsestart_mapplayers[$login] = 0;
	if(!isset($_falsestart_matchplayers[$login]))
		$_falsestart_matchplayers[$login] = 0;

	// already falsestart for player this round ? just update state
	if(isset($_falsestart_roundplayers[$login])){
		$_falsestart_roundplayers[$login] = $fs_state;
		return;
	}
	if($_debug>0) console("falsestartHandle($login,$fs_state,$text)");

	// setup falsestart for login in round (even if it will not be restarted for him)
	$_falsestart_roundplayers[$login] = $fs_state;

	$nolimit = false;
	if($_falsestart_testing==2 && $fs_state!==true && isset($_falsestart_prevroundplayers[$login]) &&
		 $_falsestart_prevroundplayers[$login]!==true){
		// special case : player was already netlost before round restart
		if($fs_state > $_NetlostFalsestartTimeout){
			// too long netlost : don't consider it any more
			$_falsestart_roundplayers[$login] = true;
			return;
		}
		$nolimit = true;
	}

	$dtime = $_currentTime - $_players_round_time;
	// limit reached ?
	if(!$nolimit && $_falsestart_mapplayers[$login] >= $_MapFalsestart){
		// over max permitted false start on map for player !
		console("FALSE START IGNORED(MAP): {$login} ({$text},{$dtime},".$_falsestart_mapplayers[$login].",{$_MapFalsestart})");
		if(!$_IsFalsestart){
			$msg = localeText(null,'server_message').localeText(null,'interact').'Sorry, you already made '.$_falsestart_mapplayers[$login].' false start(s) on this map : will not restart again for you !';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

	}elseif(!$nolimit && $fs_state===true && $_MapFalsestart > 0 && $_falsestart_currentround_giveups >= $_MaxGiveUpFalsestartPerRound){
		// over max permitted false start on falsestart for player !
		console("FALSE START IGNORED(MAP): {$login} ({$text},{$dtime},".$_falsestart_matchplayers[$login].",{$_MapFalsestart},{$_falsestart_currentround_giveups}>={$_MaxGiveUpFalsestartPerRound})");
		if(!$_IsFalsestart){
			$msg = localeText(null,'server_message').localeText(null,'interact')."Sorry, max {$_MaxGiveUpFalsestartPerRound} giveup false start(s) by round !";
			addCall(null,'ChatSendToLogin', $msg, $login);
		}		

	}elseif(!$nolimit && $_MatchFalsestart > 0 && $_falsestart_matchplayers[$login] >= $_MatchFalsestart){
		// over max permitted false start on falsestart for player !
		console("FALSE START IGNORED(MATCH): {$login} ({$text},{$dtime},".$_falsestart_matchplayers[$login].",{$_MatchFalsestart})");
		if(!$_IsFalsestart){
			$msg = localeText(null,'server_message').localeText(null,'interact').'Sorry, you already made '.$_falsestart_matchplayers[$login].' false start(s) on this match : will not restart again for you !';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}		

	}else{
		// false start for player...
		if(!$nolimit){
			$_falsestart_mapplayers[$login]++;
			$_falsestart_matchplayers[$login]++;
		}
		$fsnum = $_falsestart_mapplayers[$login]."/{$_MapFalsestart}";
		if($_MatchFalsestart > 0)
			$fsnum .= ','.$_falsestart_matchplayers[$login]."/{$_MatchFalsestart}";
		
		console("FALSE START: {$login} ({$text},{$dtime}) ({$fsnum})");
		$msg = localeText(null,'server_message').localeText(null,'interact').'$o$c00False start ('.$text.') : '.localeText(null,'interact').$_players[$login]['NickName'].localeText(null,'interact')." ({$fsnum})";
		addCall(null,'ChatSendServerMessage', $msg);

		if(!$_IsFalsestart){
			// first false start of round !
			$_IsFalsestart = true;
			if($fs_state===true)
				$_falsestart_currentround_giveups++;
			// announce it !
			falsestartUpdateAlertXml(true,'show');

			// if netlost falsestart then put all not netlost players in tmp spec !
			if($_falsestart_testing==2){
				// put all in tmp spec, to avoid finishing map...
				if($_debug>0) console("falsestartHandle:: netlost FalseStart: put all tmp spec !...");
				foreach($_players as $login2 => &$pl){
					if($login2!=$login && $pl['Active'] && $pl['SpectatorStatus']==0 && 
						 !isset($pl['toPlay']) && $pl['LatestNetworkActivity'] < 4000){
						addCall(true,'ForceSpectator',''.$login2,1); // spec
						addCall(true,'ForceSpectator',''.$login2,0); // then playerfree
						$_players[$login2]['toPlay'] = true; // will put back to play when comme spec
					}
				}
			}
		}
	}
}


//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide'
//--------------------------------------------------------------
function falsestartUpdateAlertXml($login,$action='show'){
	global $_debug,$_players,$_falsestart_alert_xml;

	if($login===true){
		foreach($_players as $login => &$pl){
			if(isset($pl['ML']))
				falsestartUpdateAlertXml($login,$action);
		}
		return;
	}

	if($action=='hide'){
		// hide manialink
		manialinksHide($login,'falsestart.alert');
		return;
	}
	// show/refresh
	manialinksShowForce($login,'falsestart.alert',sprintf($_falsestart_alert_xml,localeText($login,'falsestart.alert')));
}



//--------------------------------------------------------------
// '/falsestart map #, match #|init, timeout #'
//--------------------------------------------------------------

function chat_fs($author, $login, $params){
	chat_falsestart($author, $login, $params);
}

function chat_falsestart($author, $login, $params){
	global $_MapFalsestart,$_MatchFalsestart,$_NetlostFalsestartTimeout,$_MaxGiveUpFalsestartPerRound,$_GiveUpFalsestartOnlyAtFirstRound,$_falsestart_matchplayers,$_falsestart_mapplayers;

	// verify if author is in admin list
	if(!verifyAdmin($login)){
		// help
		$msg = localeText(null,'server_message').localeText(null,'interact')."Usage: /falsestart map #, match #|init, timeout #, giveup #|first|all.  Max for map: {$_MapFalsestart}. Max for match: {$_MatchFalsestart}. Timeout for netlost: ".floor($_NetlostFalsestartTimeout/1000)."s. Max giveup per round: {$_MaxGiveUpFalsestartPerRound}.";
		if($_GiveUpFalsestartOnlyAtFirstRound)
			$msg .= " Giveup falsestart only at first round.";
		addCall(null,'ChatSendToLogin', $msg, $login);
		return;
	}

	if(isset($params[0]) && $params[0]=='map'){
		if(!isset($params[1]) || $params[1]==''){
			$msg = localeText(null,'server_message').localeText(null,'interact')."Max falsestart by player for map : {$_MapFalsestart}. (0=disabled)";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}elseif($params[1]=='init'){
			$_falsestart_mapplayers = array();
			$msg = localeText(null,'server_message').localeText(null,'interact')."List of falsestart on map is now empty. (max={$_MapFalsestart})";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$_MapFalsestart = $params[1]+0;
			$msg = localeText(null,'server_message').localeText(null,'interact')."Max falsestart by player for map set to: {$_MapFalsestart}. (0 to disable)";
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

	}elseif(isset($params[0]) && $params[0]=='match'){
		if(!isset($params[1]) || $params[1]==''){
			$msg = localeText(null,'server_message').localeText(null,'interact')."Max falsestart by player for match : {$_MatchFalsestart}. (0=disabled)";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}elseif($params[1]=='init'){
			$_falsestart_matchplayers = array();
			$msg = localeText(null,'server_message').localeText(null,'interact')."List of falsestart on match is now empty. (max={$_MatchFalsestart})";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$_MatchFalsestart = $params[1]+0;
			$msg = localeText(null,'server_message').localeText(null,'interact')."Max falsestart by player for match set to: {$_MatchFalsestart}. (0 to disable)";
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

	}elseif(isset($params[0]) && ($params[0]=='timeout' || $params[0]=='to')){
		if(!isset($params[1]) || $params[1]=='' || $params[1]+0 <= 0){
			$msg = localeText(null,'server_message').localeText(null,'interact')."Falsestart timeout for player in netlost : ".floor($_NetlostFalsestartTimeout/1000)."s.";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$timeout = $params[1]+0;
			if($timeout < 5000)
				$timeout *= 1000;
			$_NetlostFalsestartTimeout = $timeout;
			$msg = localeText(null,'server_message').localeText(null,'interact')."Falsestart timeout for player in netlost set to: ".floor($_NetlostFalsestartTimeout/1000)."s.";
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

	}elseif(isset($params[0]) && $params[0]=='giveup'){
		if(!isset($params[1]) || $params[1]==''){
			$msg = localeText(null,'server_message').localeText(null,'interact')."Max giveup per round: {$_MaxGiveUpFalsestartPerRound}. Giveup falsestart only at first round: ".($_GiveUpFalsestartOnlyAtFirstRound ? 'on' : 'off');
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}elseif($params[1]=='first'){
			$_GiveUpFalsestartOnlyAtFirstRound = true;
			$msg = localeText(null,'server_message').localeText(null,'interact')."Giveup falsestart only at first round is now on";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}elseif($params[1]=='all'){
			$_GiveUpFalsestartOnlyAtFirstRound = true;
			$msg = localeText(null,'server_message').localeText(null,'interact')."Giveup falsestart only at first round is now off";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$_MaxGiveUpFalsestartPerRound = $params[1]+0;
			$msg = localeText(null,'server_message').localeText(null,'interact')."Max giveup per round is now set to: {$_MaxGiveUpFalsestartPerRound}.";
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

	}else{
		// help
		$msg = localeText(null,'server_message').localeText(null,'interact')."Usage: /falsestart map #, match #|init, timeout #, giveup #|first|all.  Max for map: {$_MapFalsestart}. Max for match: {$_MatchFalsestart}. Timeout for netlost: ".floor($_NetlostFalsestartTimeout/1000)."s. Max giveup per round: {$_MaxGiveUpFalsestartPerRound}.";
		if($_GiveUpFalsestartOnlyAtFirstRound)
			$msg .= " Giveup falsestart only at first round.";
		addCall(null,'ChatSendToLogin', $msg, $login);
	}

}


?>
