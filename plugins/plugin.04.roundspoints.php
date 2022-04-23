<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST3.2 Rounds alternative points
//
// Note: this plugin is needed for FWarmUp in all rounds based modes
//
// Note: now use the builtin SetRoundCustomPoints except for special cases
//
registerPlugin('roundspoints',4);
registerPlugin('ml_roundspoints',15);

// $_roundspoints_points = array( 'custom'=>array() );
// $_roundspoints_rule = 'custom';

// auto adjust custom points when finalists in Cup mode (actually only 0/1/2)
//   1=reduce custompoints when there are finalists
//   2=limit won points : only players in front of finalist win points, recuded by the virtual finalist round score
// $_cup_autoadjust = 2;

// $_show_autoadjust_scores = true;


//--------------------------------------------------------------
// setCustomPoints
//------------------------------------------
function setCustomPoints($rule=''){
	global $_debug,$_roundspoints_points,$_roundspoints_rule,$_roundspoints_CustomPoints,$_RoundCustomPoints,$_cup_autoadjust,$_GameInfos,$_FWarmUp;
	$changed = false;
	if($rule == ''){
		if($_roundspoints_rule != '')
			$changed = true;
		$_roundspoints_rule = '';
		if($_FWarmUp <= 0)
			addCall(null,'SetRoundCustomPoints',array(),true);
		$_RoundCustomPoints = array();
		roundspointsSetCustomPointsArray();

	}elseif(isset($_roundspoints_points[$rule])){
		if($_roundspoints_rule != $rule)
			$changed = true;
		$_roundspoints_rule = $rule;
		if($_FWarmUp <= 0)
			addCall(null,'SetRoundCustomPoints',$_roundspoints_points[$_roundspoints_rule],true);
		$_RoundCustomPoints = $_roundspoints_points[$_roundspoints_rule];
		roundspointsSetCustomPointsArray();

	}else{
		$custom = explode(',',$rule);
		foreach($custom as $k => $custval){
			if(!is_numeric($custval)){
				$custom = false;
				break;
			}
			$custom[$k] = (int)$custval;
		}
		if($custom !==  false){
			$changed = true;
			$_roundspoints_points['custom'] = $custom;
			$_roundspoints_rule = 'custom';
			if($_FWarmUp <= 0)
				addCall(null,'SetRoundCustomPoints',$_roundspoints_points[$_roundspoints_rule],true);
			$_RoundCustomPoints = $_roundspoints_points[$_roundspoints_rule];
			roundspointsSetCustomPointsArray();
		}
	}
	if($changed){
		if($_GameInfos['GameMode'] == CUP && $_cup_autoadjust > 0){
			// need to restart round
			if($_debug>0) console("setCupAutoAdjust:: change need a endround");
			$msg = localeText(null,'server_message').localeText(null,'interact')."CustomPoints changed, need to end round...";
			addCall(null,'ChatSendServerMessage', $msg);
			addCall(null,'ForceEndRound');
		}
		return true;
	}
	return false;
}


//--------------------------------------------------------------
// setCupAutoAdjust (0|1|2)
//--------------------------------------------------------------
function setCupAutoAdjust($cupautoadjust){
	global $_debug,$_GameInfos,$_cup_autoadjust;
	console("setCupAutoAdjust:: {$_cup_autoadjust} {$_GameInfos['GameMode']}");
	if($cupautoadjust != $_cup_autoadjust && $cupautoadjust >= 0 && $cupautoadjust <= 2){
		// changed: set it and restart round if needed...
		$_cup_autoadjust = $cupautoadjust;
		if($_GameInfos['GameMode'] == CUP){
			// need to restart round
			if($_debug>0) console("setCupAutoAdjust:: change need a endround");
			$msg = localeText(null,'server_message').localeText(null,'interact')."CupAutoadjust changed to: {$_cup_autoadjust}, need to end round...";
			addCall(null,'ChatSendServerMessage', $msg);
			addCall(null,'ForceEndRound');
		}
	}
}


//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function roundspointsInit($event){
	global $_debug,$_GameInfos,$_roundspoints_manual,$_roundspoints_rule,$_roundspoints_points,$_roundspoints_scores,$_cup_autoadjust,$_players;
	
	$_roundspoints_points['std'] = array(); // classic points (ie 10,6,4,3,2,1)
	$_roundspoints_points['none'] = array(0); // none take points
	$_roundspoints_points['single'] = array(1,0); // Only first take one point
	$_roundspoints_points['f1gp'] = array(10,8,6,5,4,3,2,1); // F1 GP style points
	$_roundspoints_points['motogp'] = array(25,20,16,13,11,10,9,8,7,6,5,4,3,2,1); // MotoGP style points
	$_roundspoints_points['motogp5'] = array(30,25,21,18,16,15,14,13,12,11,10,9,8,7,6,5,4,3,2,1); // MotoGP+5 style points
	$_roundspoints_points['champcar'] = array(31,27,25,23,21,19,17,15,13,11,10,9,8,7,6,5,4,3,2,1); // Champ Car style points
	$_roundspoints_points['fet'] = array(15,12,11,10,9,8,7,6,6,5,5,4,4,3,3,3,2,2,2,2,1,1,0); // FET style points
	$_roundspoints_points['fet2'] = array(20,17,16,15,14,13,12,11,11,10,10,9,9,8,8,7,7,6,6,5,5,4,4,3,3,2); // mFET2 style points
	$_roundspoints_points['fet3'] = array(25,22,20,19,18,17,16,15,14,13,12,11,10,10,9,9,8,8,7,7,6,6,5,5,4,4,3,3,2,2,1); // FET6 style points
	$_roundspoints_points['time1000'] = array(-1000,-100); // TM-Lique style points (1000 for author time)
	$_roundspoints_points['time2000'] = array(-2000,-100); // TM-Lique style points (2000 for author time)
	
	if(!isset($_roundspoints_rule))
		$_roundspoints_rule = ''; // standard possible values: '','motogp','motogp5','champcar'

	if(!isset($_cup_autoadjust))
		$_cup_autoadjust = 0;

	$_roundspoints_scores = array();
	$_roundspoints_manual = false;

	registerCommand('rpoints','/rpoints ['.implode(',',array_keys($_roundspoints_points)).'] : customise Rounds points.',true);
}

//--------------------------------------------------------------
// Init2 :
//--------------------------------------------------------------
function roundspointsServerStart($event){
	global $_debug,$_StoredInfos,$_GameInfos,$_RoundCustomPoints,$_roundspoints_rule,$_roundspoints_points,$_players;

	//debugPrint("roundspointsServerStart - _RoundCustomPoints",$_RoundCustomPoints);

	if(!isset($_StoredInfos['roundspoints_rule']) || !isset($_StoredInfos['roundspoints_points'][$_StoredInfos['roundspoints_rule']])){
		// if custom points are set on server then take its values
		if((isset($_RoundCustomPoints[0]) && $_RoundCustomPoints[0] > 1) ||
			 (isset($_RoundCustomPoints[1]) && $_RoundCustomPoints[1] > 0)){
			foreach($_roundspoints_points as $rule => $points){
				if($_RoundCustomPoints == $points){
					$_roundspoints_rule = $rule;
					if($_debug>0) console("RoundsPoints on server was: $rule");
					roundspointsSetCustomPointsArray();
					break;
				}
			}
			if($_roundspoints_rule == ''){
				$_roundspoints_points['custom'] = $_RoundCustomPoints;
				$_roundspoints_rule = 'custom';
				if($_debug>0) console("RoundsPoints on server was: ".implode(',',$_RoundCustomPoints));
				roundspointsSetCustomPointsArray();
			}
			
			// not set on server but asked in script config : set it
		}elseif(isset($_roundspoints_points[$_roundspoints_rule][0])){
			roundspointsSetCustomPointsArray(true);

		}else{
			roundspointsSetCustomPointsArray();
		}
	}else{
		// roundspoints_rule restored
		$_roundspoints_rule = $_StoredInfos['roundspoints_rule'];
		$_roundspoints_points = $_StoredInfos['roundspoints_points'];
		roundspointsSetCustomPointsArray(true);
	}

	roundspointsCupAutoAdjust('Start');
}


//--------------------------------------------------------------
function roundspointsStoreInfos($event){
	global $_debug,$_StoredInfos,$_roundspoints_rule,$_roundspoints_points,$_cup_autoadjust;
	//if($_debug>0) console("roundspoints.Event[$event]");
	$_StoredInfos['roundspoints_points'] = $_roundspoints_points;
	$_StoredInfos['roundspoints_rule'] = $_roundspoints_rule;
	$_StoredInfos['cup_autoadjust'] = $_cup_autoadjust;
}


//--------------------------------------------------------------
function roundspointsRestoreInfos($event,$restoretype,$liveage,$playerschanged,$rankingchanged){
	global $_debug,$_is_relay,$_StoredInfos,$_roundspoints_rule,$_roundspoints_points,$_cup_autoadjust;
	if($_is_relay)
		return;
	//if($_debug>0) console("roundspoints.Event[$event]");
	if(isset($_StoredInfos['roundspoints_rule']) && isset($_StoredInfos['roundspoints_points'][$_StoredInfos['roundspoints_rule']])){
		$_roundspoints_rule = $_StoredInfos['roundspoints_rule'];
		if(isset($_StoredInfos['roundspoints_points'][$_roundspoints_rule]))
		$_roundspoints_points[$_roundspoints_rule] = $_StoredInfos['roundspoints_points'][$_roundspoints_rule];
		roundspointsSetCustomPointsArray(true);
	}
	if(isset($_StoredInfos['cup_autoadjust'])){
		setCupAutoAdjust($_StoredInfos['cup_autoadjust']);
	}
	if($_debug>2) console("roundspointsRestoreInfos:: _StoredInfos ".print_r($_StoredInfos,true));
}


//--------------------------------------------------------------
// Get Fast infos for relays
function roundspointsGetInfosForRelay($event,$relaylogin,$state){
	global $_debug,$_RelayInfos,$_roundspoints_CustomPoints,$_roundspoints_rule,$_cup_autoadjust;
	if($_debug>8) console("roundspointsGetInfosForRelay:: ");
	$_RelayInfos['roundspoints']['CustomPoints'] = $_roundspoints_CustomPoints;
	$_RelayInfos['roundspoints']['rule'] = $_roundspoints_rule;
	$_RelayInfos['roundspoints']['cup_autoadjust'] = $_cup_autoadjust;
}


//--------------------------------------------------------------
// Fast Infos from master (to relay)
function roundspointsDatasFromMaster($event,$data){
	if(isset($data['roundspoints'])){
		global $_debug,$_roundspoints_CustomPoints,$_roundspoints_rule,$_roundspoints_points,$_cup_autoadjust;
		if($_debug>3) console("roundspointsDatasFromMaster:: ".print_r($data['roundspoints'],true));

		if(isset($data['roundspoints']['cup_autoadjust']))
			$_cup_autoadjust = $data['roundspoints']['cup_autoadjust'];
		if(isset($data['roundspoints']['rule']))
			$_roundspoints_rule = $data['roundspoints']['rule'];
		if(isset($data['roundspoints']['CustomPoints']))
			$_roundspoints_CustomPoints = $data['roundspoints']['CustomPoints'];
		$_roundspoints_points[$_roundspoints_rule] = $_roundspoints_CustomPoints;
	}
}


//--------------------------------------------------------------
function roundspointsSetCustomPointsArray($setit=false){
	global $_debug,$_roundspoints_CustomPoints,$_roundspoints_rule,$_roundspoints_points,$_StatusCode,$_FWarmUp,$_NextFWarmUp;

	if(($_StatusCode < 5 && $_FWarmUp > 0) || ($_StatusCode > 5 && $_NextFWarmUp > 0)){
		// FWarmUp : set custompoints to 0 
		if($_debug>2) console("roundspointsSetCustomPointsArray:: FWarmUp : set custom points to 0 !");
		addCall(null,'SetRoundCustomPoints',array(0,0),true);
		return;
	}elseif($_FWarmUp > 0){
		if($_debug>2) console("roundspointsSetCustomPointsArray:: FWarmUp finished : set custom points back !...");
		$setit = true;
	}

	if(isset($_roundspoints_points[$_roundspoints_rule][0])){
		if($_roundspoints_points[$_roundspoints_rule][0] >= 0){
			$_roundspoints_CustomPoints = $_roundspoints_points[$_roundspoints_rule];
			if($setit){
				if($_debug>0) console("Setting RoundsPoints to: $_roundspoints_rule (".implode(',',$_roundspoints_points[$_roundspoints_rule]).')');
				addCall(null,'SetRoundCustomPoints',$_roundspoints_points[$_roundspoints_rule],true);
			}
		}else{
			$_roundspoints_CustomPoints = array(10,6,4,3,2,1);
			if($setit){
				if($_debug>0) console("Setting RoundsPoints to: standard (10,6,4,3,2,1)");
				addCall(null,'SetRoundCustomPoints',array(),true);
			}
		}
	}else{
		$_roundspoints_CustomPoints = array(10,6,4,3,2,1);
		if($setit){
			if($_debug>0) console("Setting RoundsPoints to: standard (10,6,4,3,2,1)");
			addCall(null,'SetRoundCustomPoints',array(),true);
		}
	}
}


//--------------------------------------------------------------
// PlayerConnect :
//--------------------------------------------------------------
function roundspointsPlayerConnect($event,$login){
	global $_debug,$_GameInfos,$_roundspoints_rule,$_roundspoints_points,$_players;

  if(!is_string($login))
    $login = ''.$login;

	// rounds/cup and custom : welcome message
	if(isset($_roundspoints_points[$_roundspoints_rule][0]) && 
		 (($_GameInfos['GameMode'] == ROUNDS && !$_GameInfos['RoundsUseNewRules']) || ($_GameInfos['GameMode'] == CUP))){
		// send welcome message to player
		$msg = localeText(null,'server_message')
			.localeText($login,'roundspoints.announce',$_roundspoints_rule,implode(',',$_roundspoints_points[$_roundspoints_rule]));
		addCall(null,'ChatSendServerMessageToLogin', $msg, $login);
	}

	if($_GameInfos['GameMode'] == ROUNDS || $_GameInfos['GameMode'] == CUP || !isset($_players[$login]['ScoreOld']) || !isset($_players[$login]['ScoreNew'])){
		// not round or cup, or scorenew/scoreold not defined
		$_players[$login]['ScoreOld'] = ($_players[$login]['Score'] > 0 ) ? $_players[$login]['Score'] : 0;
		$_players[$login]['ScoreNew'] = -1;
		
	}elseif($_players[$login]['ScoreNew'] > 0){
		// if ScoreNew is set then set player score to it
		$_roundspoints_scores[$login] = array('PlayerId'=>$_players[$login]['PlayerId'],'Score'=>$_players[$login]['ScoreNew']);
		roundspointsSetScores();
		
	}elseif($_players[$login]['ScoreOld'] > 0){
		// if ScoreOld is set then set player score to it
		$_roundspoints_scores[$login] = array('PlayerId'=>$_players[$login]['PlayerId'],'Score'=>$_players[$login]['ScoreOld']);
		roundspointsSetScores();
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function roundspointsBeginRace($event,$GameInfos,$ChallengeInfo,$newcup){
	global $_debug,$_GameInfos,$_roundspoints_rule,$_roundspoints_points,$_roundspoints_scores,$_cup_autoadjust,$_players;

	if($_debug>0){
		$scores = '';
		$sep = '';
		foreach($_players as $login => &$pl){
			$scores .= "{$sep}{$login}={$pl['Score']}";
			$sep = ',';
		}
		if($_debug>2) console("roundspoints.Event[$event] ({$scores})");
	}

	if($_GameInfos['GameMode'] != CUP || $newcup){
		// clear scores if not Cup or if new Cup
		if($_debug>2) console("roundspointsBeginRace:: set ScoreNew and ScoreOld to 0 ! ({$newcup})");
		foreach($_players as $login => &$pl){
			$pl['ScoreNew'] = -1;
			$pl['ScoreOld'] = 0;
		}
		$_roundspoints_scores = array();
		
		if($_GameInfos['GameMode'] == CUP && ($_cup_autoadjust < 1 || $newcup))
			roundspointsSetCustomPointsArray(true);
		else
			roundspointsSetCustomPointsArray();

	}else{
		roundspointsSetScores();
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function roundspointsBeginRound_Reverse($event){
	global $_debug,$_WarmUp,$_FWarmUp,$_GameInfos,$_is_relay,$_use_flowcontrol,$_RoundCustomPoints,$_roundspoints_manual,$_roundspoints_rule,$_roundspoints_points,$_players,$_cup_autoadjust,$_ml_roundspoints_xml;
	//if($_debug>0) console("roundspoints.EventRev[$event]");

	$_ml_roundspoints_xml = '';

	if($_GameInfos['GameMode'] != ROUNDS && $_GameInfos['GameMode'] != CUP){
		$_roundspoints_manual = false;
		return;
	}
		
	// test if flow control
	if(!$_use_flowcontrol && !$_is_relay){
		if($_GameInfos['GameMode'] == CUP && $_cup_autoadjust == 2){
			console("roundspoints:: Can't do Cup autoadjust 2 without FlowControl !");
			$_cup_autoadjust = 0;
		}
	}
	// will the rounds points have to be set manually ? (need flow control !!!)
	$_roundspoints_manual = ($_is_relay || $_use_flowcontrol) && isset($_RoundCustomPoints[1]) && $_RoundCustomPoints[0] == 1 && $_RoundCustomPoints[1] == 0;
	//if($_roundspoints_manual) console("roundspointsBeginRound:: {$_use_flowcontrol},{$_is_relay},{$_RoundCustomPoints[0]},{$_roundspoints_manual}");
		
	if($_debug>0){
		$scores = '';
		$sep = '';
		foreach($_players as $login => &$pl){
			$scores .= "{$sep}{$login}={$pl['Score']}";
			$sep = ',';
		}
		if($_debug>2) console("roundspoints.EventRev[$event] ({$scores})");
	}
	
	
	$endrace = false;
	// set new old values
	foreach($_players as $login => &$pl){
		$pl['ScoreNew'] = -1;
		$pl['ScoreOld'] = $pl['Score'];
		
		if($_GameInfos['GameMode'] == ROUNDS && $_GameInfos['RoundsPointsLimit'] > 0 && 
			 ($pl['ScoreOld'] >= $_GameInfos['RoundsPointsLimit']))
			$endrace = true;
	}
	
	// send scores
	roundspointsSetScores(true);
	
	// sometimes the game miss the end of race at end of round,
	// probably because the ForceScores was too late, do it now if needed
	if($endrace){
		addCall(null,'NextChallenge');
		
		// else, test and set Cup custom points autoadjust
	}elseif(roundspointsCupAutoAdjust('BeginRound') && !$_WarmUp && !$_FWarmUp ){
		
		addCall(null,'ForceEndRound');
		if($_debug>0) console("roundspointsBeginRound:: CupAutoAdjust need a round restart...");
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function roundspointsPlayerFinish($event,$login,$time){
	global $_debug,$_GameInfos,$_roundspoints_rule,$_roundspoints_points,$_roundspoints_scores,$_players,$_ChallengeInfo;
	// $_ChallengeInfo['AuthorTime']

	if($_GameInfos['GameMode'] != ROUNDS && $_GameInfos['GameMode'] != CUP)
		return;
  if(!is_string($login))
    $login = ''.$login;

	if($_roundspoints_rule != '' && isset($_roundspoints_points[$_roundspoints_rule][0]) &&
		 $_roundspoints_points[$_roundspoints_rule][0] < 0 &&
		 $_GameInfos['GameMode'] == ROUNDS && !$_GameInfos['RoundsUseNewRules']){
		// Rounds, !NewRules and points array and special
		if($_debug>2) console("roundspoints.Event[$event]('$login',$time) {$_players[$login]['Score']},{$_players[$login]['ScoreNew']},{$_players[$login]['Position']['Pos']}");

		if($time > 0){
			// beware : $_roundspoints_points[$_roundspoints_rule][0] and $_roundspoints_points[$_roundspoints_rule][1] are negative !
			$points = (int)floor(-$_roundspoints_points[$_roundspoints_rule][0]
													 +($time - $_ChallengeInfo['AuthorTime'])*$_roundspoints_points[$_roundspoints_rule][1]/1000);
			if($points < 0)
				$points = 0;
			if($_debug>1) console("Points($login): ".($time/1000).' - '.($_ChallengeInfo['AuthorTime']/1000).' -> '.$points);
			
			$score = $_players[$login]['ScoreOld'] + $points;
				
			$msg = localeText(null,'server_message') . localeText(null,'interact').MwTimeToString($time)." => score: {$points} (+{$_players[$login]['ScoreOld']}=>{$score})";
			addCall(null,'ChatSendServerMessageToLogin', $msg, $login);

			// update player new score
			$_players[$login]['ScoreNew'] = $score;
			$_roundspoints_scores[$login] = array('PlayerId'=>$_players[$login]['PlayerId'],'Score'=>$score);
			if($_debug>2) console("roundspoints: Force $login ({$_players[$login]['PlayerId']}) score from {$_players[$rsl]['ScoreOld']} to {$score} ($points)");
		}
	}

	// send scores
	roundspointsSetScores();
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
// $_roundspoints_scores[$login] = array('PlayerId'=>$_players[$login]['PlayerId'],'Score'=>$score);
function roundspointsBeforeEndRound($event,$delay){
	global $_debug,$_GameInfos,$_players_positions;
	if($_GameInfos['GameMode'] != ROUNDS && $_GameInfos['GameMode'] != CUP)
		return;
	//if($_debug>1) console("roundspoints.Event[$event]($delay)");

	if($delay < 0){ // at real final BeforeEndRound
		//if($_debug>0) debugPrint("roundspoints.Event[$event]:: _players_positions ",$_players_positions);
		roundspointsCupAutoAdjust('BeforeEndRound');
		
		roundspointsSetScores();
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function roundspointsEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_debug,$_GameInfos;

	if($_GameInfos['GameMode'] != ROUNDS && $_GameInfos['GameMode'] != CUP)
		return;
	//if($_debug>1) console("roundspoints.Event[$event]");

	roundspointsCupAutoAdjust('EndRound');

	roundspointsSetScores();
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function roundspointsEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup){
	global $_debug,$_GameInfos,$_cup_autoadjust;
	if($_GameInfos['GameMode'] != ROUNDS && $_GameInfos['GameMode'] != CUP)
		return;
	//if($_debug>0) console("roundspoints.Event[$event]");

	roundspointsCupAutoAdjust($continuecup ? 'ContinueRace' : 'EndRace');

	roundspointsSetScores(!$continuecup);

	roundspointsSetCustomPointsArray();

	if($_GameInfos['GameMode'] == CUP && ($_cup_autoadjust < 1 || !$continuecup))
		roundspointsSetCustomPointsArray(true);
	else
		roundspointsSetCustomPointsArray();
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function roundspointsSetScores($clear=false){
	global $_debug,$_roundspoints_scores;

	if(count($_roundspoints_scores) > 0){
		addCall(null,'ForceScores',array_values($_roundspoints_scores),true);
		if($_debug>0) debugPrint("roundspointsSetScores - roundspoints_scores",$_roundspoints_scores);
	}
	if($clear){
		$_roundspoints_scores = array();
	}
}








//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function ml_roundspointsInit($event){
	global $_debug,$_is_relay,$_show_autoadjust_scores,$_ml_roundspoints_base1_xml,$_ml_roundspoints_base2_xml,$_ml_roundspoints_xml;

	if($_is_relay)
		roundspointsInit($event);

	if(!isset($_show_autoadjust_scores))
		$_show_autoadjust_scores = true;

	$_ml_roundspoints_base1_xml = "<quad sizen='3.3 2.1'  posn='47.7 8.7 3' halign='center' style='BgsPlayerCard' substyle='BgCardSystem'/><quad sizen='3.3 2.1'  posn='47.7 8.7 3' halign='center' style='BgsPlayerCard' substyle='BgCardSystem'/>";
	$_ml_roundspoints_base2_xml = "<label sizen='3.2 2'  posn='47.6 %d.55 4' halign='center' textsize='2'>$070%s</label>"; // %d=8-4*pos, %s=score
	$_ml_roundspoints_xml = '';
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function ml_roundspointsPlayerFinish($event,$login,$time){
	global $_mldebug,$_show_autoadjust_scores,$_players_round_finished,$_ml_roundspoints_xml;

	if($_show_autoadjust_scores){

		global $_players_positions;
		//if($_mldebug>2) console("ml_roundspointsPlayerFinish($login,$time)::  _players_positions: ".print_r($_players_positions,true));
		if($_mldebug>2) console("ml_roundspointsPlayerFinish($login,$time):: ".strlen($_ml_roundspoints_xml));

		if($time > 0){
			roundspointsCupAutoAdjust('PlayerFinish');
			if($_mldebug>2) console("ml_roundspointsPlayerFinish($login,$time)::show ".strlen($_ml_roundspoints_xml));
			ml_roundspointsUpdateScoresXml($login,'show');
			ml_roundspointsUpdateScoresXml(false,'show');

		}elseif($_players_round_finished > 0 && $_ml_roundspoints_xml != ''){
			ml_roundspointsUpdateScoresXml($login,'show');
		}
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function ml_roundspointsBeginRound($event){
	global $_mldebug,$_show_autoadjust_scores,$_is_relay,$_RoundCustomPoints,$_roundspoints_manual;

	if($_show_autoadjust_scores){
		if($_mldebug>2) console("ml_roundspointsBeginRound::hide");
		manialinksHide(true,'ml_roundspoints.score');
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function ml_roundspointsEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_mldebug,$_show_autoadjust_scores,$_cup_autoadjust,$_ml_roundspoints_xml;

	if($_show_autoadjust_scores && $_cup_autoadjust > 0 && $_ml_roundspoints_xml != ''){
		if($_mldebug>2) console("ml_roundspointsEndRound::show ".strlen($_ml_roundspoints_xml));
		ml_roundspointsUpdateScoresXml(true,'show');
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function ml_roundspointsEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup){
	global $_mldebug,$_show_autoadjust_scores;

	if($_show_autoadjust_scores){
		if($_mldebug>2) console("ml_roundspointsEndRace::hide");
		manialinksHide(true,'ml_roundspoints.score');
	}
}


//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove', 'refresh'
// login: true for all, false for specs
//--------------------------------------------------------------
function ml_roundspointsUpdateScoresXml($login,$action='show'){
	global $_mldebug,$_players,$_StatusCode,$_ml_roundspoints_xml,$_show_autoadjust_scores;
	if($_ml_roundspoints_xml == '' || !$_show_autoadjust_scores)
		$action = 'hide';
	if($login  === true){
		foreach($_players as $login => &$pl){
			if(isset($pl['ML']))
				ml_roundspointsUpdateScoresXml($login,$action);
		}
		return;
	}
	if($login  === false){
		foreach($_players as $login => &$pl){
			if(isset($pl['ML']) && ($pl['IsSpectator'] || $pl['IsTemporarySpectator']))
				ml_roundspointsUpdateScoresXml($login,$action);
		}
		return;
	}
	if($_mldebug>2) console("ml_roundspointsUpdateScoresXml:: $login,$action,".strlen($_ml_roundspoints_xml));
	if($action == 'remove'){
		// remove manialink
		manialinksRemove($login,'ml_roundspoints.score');
		return;
	}elseif($action == 'hide'){
		// hide manialink
		manialinksHide($login,'ml_roundspoints.score');
		return;
	}
	if($action == 'refresh' && !manialinksIsOpened($login,'ml_roundspoints.score'))
		return;
	// show only if scorepanel is visible !
	if(isset($_players[$login]['ML']['Hud']['round_scores']) && $_players[$login]['ML']['Hud']['round_scores']){
		//if($_mldebug>2) console("ml_roundspointsUpdateScoresXml - $login - {$_ml_roundspoints_xml}");
		manialinksShowForce($login,'ml_roundspoints.score',$_ml_roundspoints_xml);
	}else{
		//if($_mldebug>2) console("ml_roundspointsUpdateScoresXml - $login - NOOOOOOOO");
	}
}








//--------------------------------------------------------------
//
//--------------------------------------------------------------
// state: 'Start','BeginRound','EndRound','EndRace','ContinueRace'
// return true if changed (which need a endround except if called from BeforeEndRound or EndRace)
function roundspointsCupAutoAdjust($state){
	global $_debug,$_GameInfos,$_roundspoints_rule,$_roundspoints_points,$_players,$_players_positions,$_roundspoints_scores,$_RoundCustomPoints,$_roundspoints_CustomPoints,$_EndMatchCondition,$_cup_autoadjust,$_use_flowcontrol,$_WarmUp,$_FWarmUp,$_roundspoints_manual,$_ml_roundspoints_base1_xml,$_ml_roundspoints_base2_xml,$_ml_roundspoints_xml;

	if($_WarmUp || $_FWarmUp > 0 || $_GameInfos['GameMode'] != CUP)
		return;
	if($_debug>0) console("roundspointsCupAutoAdjust({$state})::(autoadjust={$_cup_autoadjust})");

	roundspointsSetCustomPointsArray();
	if($_cup_autoadjust == 1){
		// cup mode, autoadjust 1

		if($state == 'EndRace'){
			// race finished : set back to original value
			if($_debug>=0) console("roundspointsCupAutoAdjust-{$_cup_autoadjust}:: match finished: set default custom points");
			$cpts = isset($_roundspoints_points[$_roundspoints_rule]) ? $_roundspoints_points[$_roundspoints_rule] : array();
			addCall(null,'SetRoundCustomPoints',$cpts,true);
			if(count($cpts) <= 0)
				$cpts = array(10,6,4,3,2,1);
			$msg = localeText(null,'server_message').localeText(null,'interact').'Set custom points to '.implode(',',$cpts);
			addCall(null,'ChatSendServerMessage', $msg);
			return true;

		}elseif($state == 'ContinueRace'){
			// map finished, but not cup race : do nothing
			
		}elseif($state == 'EndRound'){
			// round finished : do nothing
			
		}else{
			// BeforeEndRound or BeginRound : control if changes are needed

			// serach finalists
			$scorestxt = '';
			$sep = '';
			$finalists = 0;
			$max = 0;
			foreach($_players as &$pl){
				if($pl['Active']){
					if($max < $pl['Score'])
						$max = $pl['Score'];
					$scorestxt = "{$sep}{$pl['Login']}={$pl['Score']}";
					$sep = ',';
					if(!$pl['IsSpectator'] && $pl['Score'] == $_GameInfos['CupPointsLimit'])
						$finalists++;
				}
			}
			if($max <= 0){
				// all scores are 0 : it happen in some cases at begining, btw in all cases do nothing
				return false;
			}

			// compute custom points to use :
			$cpts = isset($_roundspoints_points[$_roundspoints_rule]) ? $_roundspoints_points[$_roundspoints_rule] : array();
			if($finalists > 0){
				if(count($cpts) <= 0)
					$cpts = array(10,6,4,3,2,1);
				$num = 0;
				while(count($cpts) > 2 && $num++ < $finalists)
					array_shift($cpts);
			}
			$strcpts1 = implode(',',$cpts);
			$strcpts0 = implode(',',$_RoundCustomPoints);
			if($strcpts0 != $strcpts1){
				//console("\ncpts: ".print_r($cpts,true)."\nroundspoints_CustomPoints: ".print_r($_roundspoints_CustomPoints,true)."\n_RoundCustomPoints: ".print_r($_RoundCustomPoints,true));
				if($_debug>=0) console("roundspointsCupAutoAdjust-{$_cup_autoadjust}:: {$finalists} finalists: {$strcpts0} -> {$strcpts1} ({$_GameInfos['CupPointsLimit']} : {$scorestxt})");
				addCall(null,'SetRoundCustomPoints',$cpts,true);
				$msg = localeText(null,'server_message').localeText(null,'interact').'Auto adjust points to '.implode(',',$cpts);
				addCall(null,'ChatSendServerMessage', $msg);
				return true;
			}
		}
		
	}elseif($_cup_autoadjust == 2){
		// cup mode, autoadjust 2
		if($_debug>3) console("roundspointsCupAutoAdjust($state)::5/2");

		if($state == 'Start' || $state == 'BeforeEndRound'){
			// set custom points to 0 if finalists or winners
			$setit_manual = false;
			foreach($_players as $pl){
				if($pl['Active'] && $pl['Score'] >= $_GameInfos['CupPointsLimit'])
					$setit_manual = true;
			}
			if($setit_manual){
				console("roundspointsCupAutoAdjust($state):: set manual points !");
				addCall(null,'SetRoundCustomPoints',array(1,0),true);
			}
		}

		if($state == 'EndRace'){
			//addCall(null,'SetRoundCustomPoints',$_roundspoints_CustomPoints,true);
			$_ml_roundspoints_xml = '';

		}if($state == 'BeforeEndRound'){ // || $state == 'EndRound'){
			// Before endRound or EndRound : control if changes are needed
			$_ml_roundspoints_xml = '';

			// serach non finishing finalist !...
			$finalistpos = -1;
			$finalistlogin = '';
			foreach($_players_positions as $pos => &$plp){
				$login = $plp['Login'];
				if($_debug>0) console("roundspointsCupAutoAdjust($state)::finalist({$pos})? ({$login},{$plp['FinalTime']},{$_players[$login]['Score']},{$plp['Score']},{$_players[$login]['ScoreNew']},{$_players[$login]['ScoreOld']}/{$_GameInfos['CupPointsLimit']})");
				if($pos > 0 && $plp['FinalTime'] > 0 && $_players[$login]['ScoreOld'] == $_GameInfos['CupPointsLimit']){
					// found first finalist
					$finalistpos = $pos;
					$finalistlogin = $login;
					break;
				}
			}

			$pointstxt = '';
			$sep = '$z$fff$n$s';
			$changes = 0;

			if($finalistpos > 0){
				// build custompoints table
				$cpts = $_roundspoints_CustomPoints;
				while(count($cpts) < $finalistpos)
					$cpts[] = end($cpts);
				$finalistpts = $cpts[$finalistpos];

				if($_debug>=0) console("roundspointsCupAutoAdjust:: first finalist is pos ".($finalistpos+1)." ({$finalistlogin},{$finalistpts}) cpts: ".implode(',',$cpts)); 
				// limited points for players before finalist
				for($pos = 0; $pos < $finalistpos; $pos++){
					if($_players_positions[$pos]['FinalTime'] <= 0)
						continue;
					$login = $_players_positions[$pos]['Login'];
					// player points. always > 0, always new score <= CupPointsLimit
					if($pos == 0 && $_players[$login]['ScoreOld'] == $_GameInfos['CupPointsLimit']){
						$pointstxt .= $sep.stripColors($_players_positions[$pos]['NickName'])." : \$z\$s\$070Winner\$z";
						$sep = '$fff, $n$s';

						if($pos < 8) $_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,'W');

					}elseif($_players[$login]['ScoreOld'] < $_GameInfos['CupPointsLimit']){
						$points = $cpts[$pos] - $finalistpts;
						if($points < 0)
							$points = 0;
						$score = $_players[$login]['ScoreOld'] + $points;
						if($score > $_GameInfos['CupPointsLimit'])
							$score = $_GameInfos['CupPointsLimit'];
						
						$pointstxt .= $sep.stripColors($_players_positions[$pos]['NickName'])." : \$z\$s\$070+{$points}\$z";
						$sep = '$fff, $n$s';
						
						if($pos < 8) $_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,"+{$points}");

						// update player new score
						if($_players[$login]['Score'] != $score){
							$changes++;
							$_players[$login]['ScoreNew'] = $score;
							$_roundspoints_scores[$login] = array('PlayerId'=>$_players[$login]['PlayerId'],'Score'=>$score);
							if($_debug>=0) console("roundspointsCupAutoAdjust:: Force {$login} ({$_players[$login]['PlayerId']}) score from {$_players[$login]['ScoreOld']} to {$score} ({$points},{$pos})");
						}
					}
				}
				
				// 1st finalist !...
				$pointstxt .= $sep.stripColors($_players_positions[$finalistpos]['NickName'])." : \$z\$s\$070Finalist(-{$finalistpts})\$z";
				$sep = '$fff, $n$s';
				if($finalistpos < 8) $_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$finalistpos,'F');

				// no points for players after finalist
				for($pos = $finalistpos+1; $pos < count($_players_positions); $pos++){
					if($_players_positions[$pos]['FinalTime'] <= 0)
						continue;
					$login = $_players_positions[$pos]['Login'];
					// player points. always > 0, always new score <= CupPointsLimit
					$points = 0;

					if($_players[$login]['ScoreOld'] < $_GameInfos['CupPointsLimit']){
						$score = $_players[$login]['ScoreOld'];

						$pointstxt .= $sep.stripColors($_players_positions[$pos]['NickName'])." : \$z\$s\$070+{$points}\$z";
						$sep = '$fff, $n$s';
						
						if($pos < 8) $_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,"+{$points}");

						// update player new score
						if($_players[$login]['Score'] != $score){
							$changes++;
							$_players[$login]['ScoreNew'] = $score;
							$_roundspoints_scores[$login] = array('PlayerId'=>$_players[$login]['PlayerId'],'Score'=>$score);
							if($_debug>0) console("roundspointsCupAutoAdjust:: Force {$login} ({$_players[$login]['PlayerId']}) score from {$_players[$login]['ScoreOld']} to {$score} ({$points},{$pos})");
						}
					}else{
						// finalist
						$pointstxt .= $sep.stripColors($_players_positions[$pos]['NickName'])." : \$z\$s\$070Finalist\$z";
						$sep = '$fff, $n$s';

						if($pos < 8) $_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,'F');
					}
				}
				if($pointstxt != ''){
					$msg = localeText(null,'server_message').localeText(null,'interact').'Limited round points:  '.$pointstxt;
					addCall(null,'ChatSendServerMessage', $msg);
				}

			}elseif($_roundspoints_manual){
				// build custompoints table
				$maxpl = count($_players_positions) - 1;
				$cpts = $_roundspoints_CustomPoints;
				while(count($cpts) <= $maxpl)
					$cpts[] = end($cpts);

				// limited points for players before finalist
				for($pos = 0; $pos <= $maxpl; $pos++){
					if($_players_positions[$pos]['FinalTime'] <= 0)
						continue;
					$login = $_players_positions[$pos]['Login'];
					// player points. always > 0, always new score <= CupPointsLimit
					if($pos == 0 && $_players[$login]['ScoreOld'] == $_GameInfos['CupPointsLimit']){
						$pointstxt .= $sep.stripColors($_players_positions[$pos]['NickName'])." : \$z\$s\$070Winner\$z";
						$sep = '$fff, $n$s';

						if($pos < 8) $_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,'W');

					}elseif($_players[$login]['ScoreOld'] < $_GameInfos['CupPointsLimit']){
						$points = $cpts[$pos];
						$score = $_players[$login]['ScoreOld'] + $points;
						if($score > $_GameInfos['CupPointsLimit'])
							$score = $_GameInfos['CupPointsLimit'];
						
						$pointstxt .= $sep.stripColors($_players_positions[$pos]['NickName'])." : \$z\$s\$070+{$points}\$z";
						$sep = '$fff, $n$s';

						if($pos < 8) $_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,"+{$points}");

						// update player new score
						if($_players[$login]['Score'] != $score){
							$changes++;
							$_players[$login]['ScoreNew'] = $score;
							$_roundspoints_scores[$login] = array('PlayerId'=>$_players[$login]['PlayerId'],'Score'=>$score);
							if($_debug>=0) console("roundspointsCupAutoAdjust:: Force {$login} ({$_players[$login]['PlayerId']}) score from {$_players[$login]['ScoreOld']} to {$score} ({$points},{$pos})");
						}
					}
				}
				if($pointstxt != ''){
					$msg = localeText(null,'server_message').localeText(null,'interact').'Round points:  '.$pointstxt;
					addCall(null,'ChatSendServerMessage', $msg);
				}
				
			}
			if($_ml_roundspoints_xml != '')
				$_ml_roundspoints_xml = $_ml_roundspoints_base1_xml.$_ml_roundspoints_xml;

		}if($state == 'PlayerFinish'){
			// PlayerFinish : estimate future scores
			$_ml_roundspoints_xml = '';

			// serach non finishing finalist !...
			$finalistpos = -1;
			$finalistlogin = '';
			foreach($_players_positions as $pos => &$plp){
				$login = $plp['Login'];
				if($_debug>0) console("roundspointsCupAutoAdjust($state)::finalist({$pos})? ({$login},{$plp['FinalTime']},{$_players[$login]['Score']},{$plp['Score']},{$_players[$login]['ScoreNew']},{$_players[$login]['ScoreOld']}/{$_GameInfos['CupPointsLimit']})");
				if($pos > 0 && $plp['FinalTime'] > 0 && $_players[$login]['ScoreOld'] == $_GameInfos['CupPointsLimit']){
					// found first finalist
					$finalistpos = $pos;
					$finalistlogin = $login;
					break;
				}
			}

			if($finalistpos > 0){
				// build custompoints table
				$cpts = $_roundspoints_CustomPoints;
				while(count($cpts) < $finalistpos)
					$cpts[] = end($cpts);
				$finalistpts = $cpts[$finalistpos];

				if($_debug>=0) console("roundspointsCupAutoAdjust:: first finalist is pos ".($finalistpos+1)." ({$finalistlogin},{$finalistpts}) cpts: ".implode(',',$cpts)); 
				// limited points for players before finalist
				for($pos = 0; $pos < $finalistpos && $pos < 8; $pos++){
					if($_players_positions[$pos]['FinalTime'] <= 0)
						continue;
					$login = $_players_positions[$pos]['Login'];
					// player points. always > 0, always new score <= CupPointsLimit
					if($pos == 0 && $_players[$login]['ScoreOld'] == $_GameInfos['CupPointsLimit']){
						$_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,'W');

					}elseif($_players[$login]['ScoreOld'] < $_GameInfos['CupPointsLimit']){
						$points = $cpts[$pos] - $finalistpts;
						if($points < 0)
							$points = 0;
						$_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,"+{$points}");
					}
				}

				// no points for players after finalist
				for($pos = $finalistpos; $pos < count($_players_positions) && $pos < 8; $pos++){
					if($_players_positions[$pos]['FinalTime'] <= 0)
						continue;
					$login = $_players_positions[$pos]['Login'];
					// player points. always > 0, always new score <= CupPointsLimit
					$points = 0;

					if($_players[$login]['ScoreOld'] < $_GameInfos['CupPointsLimit']){
						$_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,"+{$points}");

					}else{
						// finalist
						$_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,'F');
					}
				}

			}elseif($_roundspoints_manual){
				// build custompoints table
				$maxpl = count($_players_positions) - 1;
				$cpts = $_roundspoints_CustomPoints;
				while(count($cpts) <= $maxpl)
					$cpts[] = end($cpts);

				// limited points for players before finalist
				for($pos = 0; $pos <= $maxpl && $pos < 8; $pos++){
					if($_players_positions[$pos]['FinalTime'] <= 0)
						continue;
					$login = $_players_positions[$pos]['Login'];
					// player points. always > 0, always new score <= CupPointsLimit
					if($pos == 0 && $_players[$login]['ScoreOld'] == $_GameInfos['CupPointsLimit']){
						$_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,'W');

					}elseif($_players[$login]['ScoreOld'] < $_GameInfos['CupPointsLimit']){
						$points = $cpts[$pos];
						$_ml_roundspoints_xml .= sprintf($_ml_roundspoints_base2_xml,8-4*$pos,"+{$points}");
					}
				}
			}
			if($_ml_roundspoints_xml != '')
				$_ml_roundspoints_xml = $_ml_roundspoints_base1_xml.$_ml_roundspoints_xml;
		}
	}
	return false;
}






//------------------------------------------
// Rounds points command
//------------------------------------------
function chat_rpoints($author, $login, $params, $command='/rpoints'){
	global $_debug,$_GameInfos,$_NextGameInfos,$_ServerOptions;
	global $_roundspoints_points,$_roundspoints_rule,$_is_relay,$_cup_autoadjust;

	// verify if author is in admin list
	if($_is_relay || !verifyAdmin($login))
		return;

	if(setCustomPoints($params[0])){
		if(!isset($_roundspoints_points[$_roundspoints_rule][0])){
			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
			." (admin) set standard Rounds points (10,6,4,3,2,1...)";
			console(stripColors($msg));
			
		}else{
			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
			." (admin) set Rounds points to: ".implode(',',$_roundspoints_points[$_roundspoints_rule]).'...';
			console(stripColors($msg));
		}
		addCall(null,'ChatSendServerMessage', $msg);

		// help
	}else{
		$msg = localeText(null,'server_message') . localeText(null,'interact')
			.$command.' ['.implode('|',array_keys($_roundspoints_points))."|x,x,x,x,x] : customise Rounds points.\nCurrent is ";
		if(!isset($_roundspoints_points[$_roundspoints_rule][0]))
			$msg .= 'standard (10,6,4,3,2,1...)';
		else
			$msg .= $_roundspoints_rule.': '.implode(',',$_roundspoints_points[$_roundspoints_rule]).'...';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}

}


?>
