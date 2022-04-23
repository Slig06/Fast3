<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// Team limit with 2 points more than opponents to win
// (using newrules in Team mode)
// 
if(!$_is_relay) registerPlugin('teamgap',6);
global $_teamgap_rule;

// Minimum gap of 2 between scores to win in Team mode
//  0 is normal, >1 value is limit to reach with 2 more points than opponents
//$_teamgap_rule = 0; 




//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function teamgapInit($event){
	global $_debug,$_GameInfos,$_teamgap_rule;
	
	if(!isset($_teamgap_rule))
		$_teamgap_rule = 0; // standard possible values: 0 is normal, >0 value is limit to reach with 2 more points than opponents 
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function teamgapBeginRace($event,$GameInfos){
	global $_debug,$_GameInfos,$_teamgap_rule;

	// teamgap_rule activated and in supported modes
	if($_teamgap_rule > 1 && $_GameInfos['GameMode'] == TEAM){
		//if($_debug>2) console("teamgap.Event[$event]");
		addCall(null,'SetTeamPointsLimit',$_teamgap_rule*10);
	}
}


//--------------------------------------------------------------
// BeforeEndRound : check for end of race before the EndRound (EndChallenge would fail while EndRound)
//--------------------------------------------------------------
function teamgapBeforeEndRound($event,$delay){
	global $_debug,$_GameInfos,$_teamgap_rule,$_Ranking;
	if($delay >= 0)
		return;

	// teamgap_rule activated and in supported modes
	if($_teamgap_rule > 1 && $_GameInfos['GameMode'] == TEAM && $_GameInfos['TeamUseNewRules']){

		$scoregap = abs($_Ranking[0]['Score'] - $_Ranking[1]['Score']);
		$scoremax = $_Ranking[0]['Score'] > $_Ranking[1]['Score'] ? $_Ranking[0]['Score'] : $_Ranking[1]['Score'];
		if($scoremax >= $_teamgap_rule && $scoregap >= 2){
			if($_debug>0) console("teamgap.Event[$event] {$_Ranking[0]['Score']} <> {$_Ranking[1]['Score']} ({$_teamgap_rule},{$scoremax},{$scoregap}) -> EndRace");
			addCall(true,'NextChallenge');
		}
	}
}


//--------------------------------------------------------------
// In case manualflowcontrol would not be active and so BeforeEndRound not called
//--------------------------------------------------------------
function teamgapEndRound($event){
	// hmmm... it seems that sometimes the NextChallenge fails when called while EndRound (-1000,Change in progress.),
	// so better wait begining of next round to call it.
  //teamgapBeforeEndRound($event,-1);
}


//--------------------------------------------------------------
// BeginRound : for security, check the end conditions at round beginning too
//--------------------------------------------------------------
function teamgapBeginRound($event){
	global $_debug,$_GameInfos,$_teamgap_rule,$_Ranking;

	// teamgap_rule activated and in supported modes
	if($_teamgap_rule > 1 && $_GameInfos['GameMode'] == TEAM && $_GameInfos['TeamUseNewRules']){

		$scoregap = abs($_Ranking[0]['Score'] - $_Ranking[1]['Score']);
		$scoremax = $_Ranking[0]['Score'] > $_Ranking[1]['Score'] ? $_Ranking[0]['Score'] : $_Ranking[1]['Score'];
		if($scoremax >= $_teamgap_rule && $scoregap >= 2){
			if($_debug>0) console("teamgap.Event[$event] {$_Ranking[0]['Score']} <> {$_Ranking[1]['Score']} ({$_teamgap_rule},{$scoremax},{$scoregap}) -> EndRace");
			addCall(true,'NextChallenge');
		}
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function teamgapEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_GameInfos,$_teamgap_rule,$_Ranking;

	// teamgap_rule activated and in supported modes
	if($_teamgap_rule > 1 && $_GameInfos['GameMode'] == TEAM && $_GameInfos['TeamUseNewRules']){
		if($_debug>0) console("teamgap.Event[$event] {$_Ranking[0]['Score']} <> {$_Ranking[1]['Score']} ({$_teamgap_rule},{$_GameInfos['TeamPointsLimitNewRules']}) -> {$_teamgap_rule}");
		addCall(null,'SetTeamPointsLimit',$_teamgap_rule);
	}
}


?>
