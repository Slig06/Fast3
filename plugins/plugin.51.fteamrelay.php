<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      20.06.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST3.2 plugin to handle script specific FastGameMode : TeamRelay
// (needs the fteams and fgmodes plugins)
//
if(!$_is_relay) registerPlugin('fteamrelay',51,1.0);

//  /debug _fteams[0]['TRelay']
//  /debug _fteams[1]['TRelay']
//  /debug _fteamrelay
//  /debug _fteams[1]
//  /debug _fteams_round[1]

// FastGameMode TeamRelay, team relay race (similar to the RelayRace desbois system)
//

// Will take values from $_FGameModes['TeamRelay'] at BeginRace/BeginRound :
// $_fteamrelay['TRelayNbLaps'] :
// $_fteamrelay['TRelayWarmup'] : warmup duration (num of LapAuthorTime)
// $_fteamrelay['TRelayPenalty'] : array of player penalty for each lap he plays

// $_fteamrelay['StartTime'] : time when warmup or race have started
// $_fteamrelay['EndTime'] : time when warmup or race will stop at max
// $_fteamrelay['StartLapDelay'] : array of delay times for each lap
// $_fteamrelay['FinishTime'] : time when first team have finished race (-1=not finished)
// $_fteamrelay['FinishTimeout'] : how much time for finish timeout (based on $_GameInfos['FinishTimeout'])
// $_fteamrelay['State'] : 'BeginRace','Warmup','Race','Finished'

// Add some things in $_fteams :
// $_fteams[tid]['TeamRelay']['Players'][login]['Login'] : player login
// $_fteams[tid]['TeamRelay']['Players'][login]['StartOrder'] : player order at beginning
// $_fteams[tid]['TeamRelay']['Players'][login]['NbLaps'] : 0, nb laps played
// $_fteams[tid]['TeamRelay']['Players'][login]['Active'] : 0/200, 0=yes,200=no (added to NbLaps to sort)
// $_fteams[tid]['TeamRelay']['Players'][login]['Playing'] : -1000/0/10, 0=no,-1000=prepare,10=playing (added to NbLaps to sort)
// $_fteams[tid]['TeamRelay']['Players'][login]['AFK'] : 0/100, 0=no,1=yes (added to NbLaps to sort)
// $_fteams[tid]['TeamRelay']['Players'][login]['Leaved'] : 0=no,1000=yes  (added to NbLaps to sort)
// $_fteams[tid]['TeamRelay']['PlayerPlaying'] : login
// $_fteams[tid]['TeamRelay']['CurLap'] : 0 to ...
// $_fteams[tid]['TeamRelay']['LapStartTime'] : time when player will start (-1 when started)
// $_fteams[tid]['TeamRelay']['LapRaceTime'] : real race time at lap beginning
// $_fteams[tid]['TeamRelay']['LapRaceCPnum'] : race CP num at lap beginning
// $_fteams[tid]['TeamRelay']['RaceTime'] : race real time since beginning
// $_fteams[tid]['TeamRelay']['ScriptTime'] : script time since beginning
// $_fteams[tid]['TeamRelay']['LastCPscriptTime'] : script time when last cp was passed
// $_fteams[tid]['TeamRelay']['RaceDelay'] : current delay between race real time and script time
// $_fteams[tid]['TeamRelay']['RaceCPs'] : race CPs since beginning
// $_fteams[tid]['TeamRelay']['Lap'][num]['Login'] :
// $_fteams[tid]['TeamRelay']['Lap'][num]['Penalty'] :
// $_fteams[tid]['TeamRelay']['Lap'][num]['StartTime'] : time when player have started or rs (-1 before start)
// $_fteams[tid]['TeamRelay']['Lap'][num]['RSdelays'] : cumulated times of player restarts
// $_fteams[tid]['TeamRelay']['Lap'][num]['Finish0num'] : num of Finish,0 received (-1 when player have finished)
// $_fteams[tid]['TeamRelay']['Lap'][num]['CPnum'] :
// $_fteams[tid]['TeamRelay']['Lap'][num]['Time'] :
// $_fteams[tid]['TeamRelay']['Lap'][num]['CPs'] :


function fteamrelayInit($event){
	global $_debug,$_players,$_FGameModes,$_FGameMode,$_NextFGameMode,$_fteams,$_fteamrelay,$_currentTime,$_fteamrelay_position_change,$_fteamrelay_ready_xml,$_fteamrelay_nextplayer_xml,$_fteamrelay_nonextplayer_xml,$_fteamrelay_lapinfo_xml,$_fteamrelay_mainlaps_xml,$_fteamrelay_checks_bg_xml,$_fteamrelay_checks_xml,$_ConvFTeamInfos;
	if($_debug>6) console("players.Event[$event]");
	$_fteamrelay_position_change = false;

	// Add/Set GameInfos constants for TeamRelay FGameMode (see plugin.17.fgmodes.php) :
	$_FGameModes['TeamRelay']['Aliases'] = array('trelay','trel','tre'); // mode name is not case sensitive, but aliases are.
	$_FGameModes['TeamRelay']['Podium'] = true;
	$_FGameModes['TeamRelay']['ScoreMode'] = 'ScCPTime';
	$_FGameModes['TeamRelay']['RoundPanel'] = true;
	$_FGameModes['TeamRelay']['FTeams'] = true;
	//$_FGameModes['TeamRelay']['PointsRule'] = 'custom';
	$_FGameModes['TeamRelay']['PointsRuleFunc'] = 'fteamrelayPointsRuleFunc';
	$_FGameModes['TeamRelay']['DrawRule'] = 'PreviousRank';
	$_FGameModes['TeamRelay']['RanksRule'] = 'CPTime';
	$_FGameModes['TeamRelay']['ScoresRule'] = 'CPTime';
	$_FGameModes['TeamRelay']['MapScoresRule'] = 'Copy';
	$_FGameModes['TeamRelay']['GameInfos'] = array('GameMode'=>1,'TimeAttackSynchStartPeriod'=>0,'TimeAttackLimit'=>0,'AllWarmUpDuration'=>0);
	//$_FGameModes['TeamRelay']['MatchLogEndRound'] = 'fteamrelayMatchLogEndRound';
	$_FGameModes['TeamRelay']['MatchLogEndRace'] = 'fteamrelayMatchLogEndRace';
	$_FGameModes['TeamRelay']['MatchEndRace'] = 'fteamrelayMatchEndRace';

	$_FGameModes['TeamRelay']['JoinMode'] = 'Free'; // 'Script' while Race
	$_FGameModes['TeamRelay']['JoinSpecMode'] = 'PlayFree'; // 'SpecForce' while Race
	$_FGameModes['TeamRelay']['ConnectSpecMode'] = 'SpecForce'; // 'SpecForce' while Race

	if(!isset($_FGameModes['TeamRelay']['TRelayNbLaps']))
		$_FGameModes['TeamRelay']['TRelayNbLaps'] = 6; // min 2
	if(!isset($_FGameModes['TeamRelay']['TRelayWarmup']))
		$_FGameModes['TeamRelay']['TRelayWarmup'] = 1; // min 1
	if(!isset($_FGameModes['TeamRelay']['TRelayPenalty']))
		$_FGameModes['TeamRelay']['TRelayPenalty'] = '0,800,1500,2100,2600,3000'; // penalty for player for his lap num
	// ie for 6 rounds : 6pl -> 0, 5pl -> 800, 4pl -> 1600, 3pl -> 2400, 2pl -> 5600, 1pl -> 10000

	$_ConvFTeamInfos['trelay_nblaps'] = 'TRelayNbLaps';
	$_ConvFTeamInfos['trelay_warmup'] = 'TRelayWarmup';
	$_ConvFTeamInfos['trelay_penalty'] = 'TRelayPenalty';


	//fteamrelaySetup('Init');

	$_fteamrelay_ready_xml = "<frame posn='0 -16 0'>"
	."<quad  sizen='30 8' posn='0 0 0' halign='center' valign='center' style='Bgs1' substyle='BgWindow2'/>"
	."<label sizen='25 5' posn='0 0.5 1' halign='center' valign='center' textsize='7' textcolor='f00f'>\$o\$sReady ?</label>"
	."<label sizen='10 2' posn='13.6 -2.3 1' halign='right' valign='center' textsize='1' textcolor='ffff'>\$o\$s%d / %d</label>"
	."</frame>";
	// to show: manialinksShowForce($login,'fteamrelay.ready', sprintf($_fteamrelay_ready_xml,$_fteams[$ftid]['TRelay']['CurLap'],$_fteamrelay['TRelayNbLaps']) );
	// to hide: manialinksHide($login,'fteamrelay.ready');

	$_fteamrelay_nextplayer_xml = '<frame posn="0 -38.3 -20.1">'
	.'<quad sizen="18 4.7" posn="0 0.7 0" halign="center" style="Bgs1InRace" substyle="BgList"/>'
	.'<quad sizen="18 4.7" posn="0 0.7 0" halign="center" style="Bgs1InRace" substyle="BgList"/>'
	.'<label sizen="16 2" posn="0 -1 0.2" halign="center" valign="center2" textsize="2" textcolor="999f" text="next: $s$fff%s"/>'
	.'<label sizen="16 2" posn="0 -3 0.2" halign="center" valign="center2" textsize="1" textcolor="999f" text="penalty: $s$ddd%s"/>'
	.'</frame>';

	$_fteamrelay_nonextplayer_xml = '<frame posn="0 -38.3 -20.1">'
	.'<quad sizen="18 4.7" posn="0 0.7 0" halign="center" style="Bgs1InRace" substyle="BgList"/>'
	.'<quad sizen="18 4.7" posn="0 0.7 0" halign="center" style="Bgs1InRace" substyle="BgList"/>'
	.'<label sizen="16 2" posn="0 -1 0.2" halign="center" valign="center2" textsize="2" textcolor="999f" text="last lap"/>'
	.'</frame>';

	$_fteamrelay_lapinfo_xml =
	'<quad sizen="15 4.5" posn="52 32.2 -40" style="BgsPlayerCard" substyle="BgPlayerCardBig"/>'
	.'<label posn="63.4 31.2 -39" halign="right" text="%s / %s"/>';

	$_fteamrelay_mainlaps_xml = '';

	$_fteamrelay_checks_bg_xml = 
	'<frame posn="13 -35 -30.0" textsize="2" >'
	//.'<quad sizen="39 13" posn="-0.5 0.5 -0.1" style="Bgs1InRace" substyle="BgTitle2"/>'
	.'<quad sizen="24 2" posn="0 0 0" style="Bgs1InRace" substyle="BgList"/>'
	.'<quad sizen="14 2" posn="24 0 0" style="Bgs1InRace" substyle="BgTitle3"/>'
	.'<quad sizen="24 2" posn="0 -2 0" style="Bgs1InRace" substyle="BgList"/>'
	.'<quad sizen="14 2" posn="24 -2 0" style="Bgs1InRace" substyle="BgTitle3"/>'
	.'<quad sizen="24 2" posn="0 -4 0" style="Bgs1InRace" substyle="BgList"/>'
	.'<quad sizen="14 2" posn="24 -4 0" style="Bgs1InRace" substyle="BgTitle3"/>'
	.'<quad sizen="24 2" posn="0 -6 0" style="Bgs1InRace" substyle="BgList"/>'
	.'<quad sizen="14 2" posn="24 -6 0" style="Bgs1InRace" substyle="BgTitle3"/>'
	.'<quad sizen="24 2" posn="0 -8 0" style="Bgs1InRace" substyle="BgList"/>'
	.'<quad sizen="14 2" posn="24 -8 0" style="Bgs1InRace" substyle="BgTitle3"/>'
	.'<quad sizen="24 2" posn="0 -10 0" style="Bgs1InRace" substyle="BgList"/>'
	.'<quad sizen="14 2" posn="24 -10 0" style="Bgs1InRace" substyle="BgTitle3"/>'
	.'</frame>';

	$_fteamrelay_checks_xml = 
	'<frame posn="13 -35 -29.5" textsize="2" >'
	.'<quad sizen="2 1.7" posn="1 0.1 0.2" %s/>' // square color string, set something like: 'bgcolor="ff0"'
	.'<label sizen="1.8 2" posn="2 0 0.2" halign="center" textsize="1" textcolor="cccf" text="$s%s"/>' // pos value
	.'<label sizen="19 2" posn="3.5 0.2 0.2" textsize="2" textcolor="ffff" text="$s%s"/>' // nickname
	.'<label sizen="12 2" posn="31 0.2 0.2" halign="center" textsize="2" textcolor="eeef" text="$s%s"/>' // gap, like '+2cp (+5.45)'
	.'<quad sizen="2 1.7" posn="1 -1.9 0.2" %s/>' // square color string, set something like: 'bgcolor="ff0"'
	.'<label sizen="1.8 2" posn="2 -2 0.2" halign="center" textsize="1" textcolor="cccf" text="$s%s"/>' // pos value
	.'<label sizen="19 2" posn="3.5 -1.8 0.2" textsize="2" textcolor="ffff" text="$s%s"/>' // nickname
	.'<label sizen="12 2" posn="31 -1.8 0.2" halign="center" textsize="2" textcolor="eeef" text="$s%s"/>' // gap, like '+2cp (+5.45)'
	.'<quad sizen="2 1.7" posn="1 -3.9 0.2" %s/>' // square color string, set something like: 'bgcolor="ff0"'
	.'<label sizen="1.8 2" posn="2 -4 0.2" halign="center" textsize="1" textcolor="cccf" text="$s%s"/>' // pos value
	.'<label sizen="19 2" posn="3.5 -3.8 0.2" textsize="2" textcolor="ffff" text="$s%s"/>' // nickname
	.'<label sizen="12 2" posn="31 -3.8 0.2" halign="center" textsize="2" textcolor="eeef" text="$s%s"/>' // gap, like '+2cp (+5.45)'
	.'<quad sizen="2 1.7" posn="1 -5.9 0.2" %s/>' // square color string, set something like: 'bgcolor="ff0"'
	.'<label sizen="1.8 2" posn="2 -6 0.2" halign="center" textsize="1" textcolor="cccf" text="$s%s"/>' // pos value
	.'<label sizen="19 2" posn="3.5 -5.8 0.2" textsize="2" textcolor="ffff" text="$s%s"/>' // nickname
	.'<label sizen="12 2" posn="31 -5.8 0.2" halign="center" textsize="2" textcolor="eeef" text="$s%s"/>' // gap, like '+2cp (+5.45)'
	.'<quad sizen="2 1.7" posn="1 -7.9 0.2" %s/>' // square color string, set something like: 'bgcolor="ff0"'
	.'<label sizen="1.8 2" posn="2 -8 0.2" halign="center" textsize="1" textcolor="cccf" text="$s%s"/>' // pos value
	.'<label sizen="19 2" posn="3.5 -7.8 0.2" textsize="2" textcolor="ffff" text="$s%s"/>' // nickname
	.'<label sizen="12 2" posn="31 -7.8 0.2" halign="center" textsize="2" textcolor="eeef" text="$s%s"/>' // gap, like '+2cp (+5.45)'
	.'<quad sizen="2 1.7" posn="1 -9.9 0.2" %s/>' // square color string, set something like: 'bgcolor="ff0"'
	.'<label sizen="1.8 2" posn="2 -10 0.2" halign="center" textsize="1" textcolor="cccf" text="$s%s"/>' // pos value
	.'<label sizen="19 2" posn="3.5 -9.8 0.2" textsize="2" textcolor="ffff" text="$s%s"/>' // nickname
	.'<label sizen="12 2" posn="31 -9.8 0.2" halign="center" textsize="2" textcolor="eeef" text="$s%s"/>' // gap, like '+2cp (+5.45)'
 	.'</frame>'
	;

	registerCommand('trelay','/trelay ',true);
	registerCommand('teamrelay','/trelay ',true);
}


function fteamrelayRestoreInfos($event,$restoretype,$liveage,$playerschanged,$rankingchanged){
	global $_debug,$_StoredInfos,$_FGameMode,$_NextFGameMode,$_FGameModes;

}


function fteamrelayCheckEnd(){
	global $_debug,$_FGameMode,$_fteams,$_fteamrelay,$_currentTime,$_GameInfos,$_ChallengeInfo;

	if($_fteamrelay['State'] == 'Warmup'){
		if($_currentTime > $_fteamrelay['EndTime'] + 500){
			if($_debug>0) console("fteamrelayCheckEnd::{$_fteamrelay['State']}): -> Race");
			fteamrelaySetup('Race');
		}else{
			//$remain = floor($_fteamrelay['EndTime'] - $_currentTime);
			//if($_debug>0) console("fteamrelayCheckEnd::{$_fteamrelay['State']}): {$remain}ms");
		}

	}elseif($_fteamrelay['State'] == 'Race'){
		// some or all have finished ?
		$maxlaps = 0;
		$allfinished = true; // false if not finished teams have done more than half of race and played last 30s
		foreach($_fteams as $ftid => &$fteam){
			if($fteam['Active'] && count($fteam['TRelay']['RaceCPs']) > 1){
				if($fteam['TRelay']['CurLap'] < $_fteamrelay['TRelayNbLaps'] && 
					 $fteam['TRelay']['CurLap'] + 1 >= $_fteamrelay['TRelayNbLaps'] / 2 &&
					 $_currentTime - $fteam['TRelay']['LastCPscriptTime'] < 30000)
					$allfinished = false;
				if($fteam['TRelay']['CurLap'] > $maxlaps)
					$maxlaps = $fteam['TRelay']['CurLap'];
			}
		}

		if($_fteamrelay['FinishTime'] <= 0 && $maxlaps >= $_fteamrelay['TRelayNbLaps']){
			// just finished : set finishtimeout
			$_fteamrelay['FinishTime'] = $_currentTime;

			// compute finish timeout
			if($_GameInfos['FinishTimeout'] < 2){
				// auto value : 15s + 10% of first team race time
				$_fteamrelay['FinishTimeout'] = 15000 + (int) floor( ($_fteamrelay['FinishTime'] - $_fteamrelay['StartTime']) * 0.1 );
			}else{
				// value * num of laps
				$_fteamrelay['FinishTimeout'] = $_GameInfos['FinishTimeout'] * $_fteamrelay['TRelayNbLaps'];
			}
			if($_debug>2) console("fteamrelayCheckEnd::{$_fteamrelay['State']}): finishtimeout: {$_fteamrelay['FinishTimeout']}");

			$_fteamrelay['EndTime'] = $_fteamrelay['FinishTime'] + $_fteamrelay['FinishTimeout'];
			if($allfinished)
				ml_mainSetTimer(-1);
			else
				ml_mainSetTimer($_fteamrelay['EndTime']);
		}

		// allfinished or FinishTimeout for race
		if($_fteamrelay['FinishTime'] > 0){
			if($allfinished || $_currentTime > $_fteamrelay['EndTime'] + 500){
				// all is finished : map is finished !
				if($_debug>0) console("fteamrelayCheckEnd::{$_fteamrelay['State']}): -> Finished !");
				fteamrelaySetup('Finished');

			}else{
				//$remain = floor($_fteamrelay['EndTime'] + 500 - $_currentTime);
				//if($_debug>0) console("fteamrelayCheckEnd::{$_fteamrelay['State']}): {$remain}ms");
			}

		}elseif($_currentTime > $_fteamrelay['EndTime'] + 1000){
			// security time to finish the race : 10s + (LapAuthorTime+20s) * (laps done + 3)
			//$racetime = $_currentTime - $_fteamrelay['StartTime'];
			//if($_debug>0) console("fteamrelayCheckEnd::{$_fteamrelay['State']}): too much time ({$racetime}) -> Finished !");
			
			fteamrelaySetup('Finished');
		}
	}
}


// state: 'Warmup','Race'
function fteamrelaySetup($state='BeginRace'){
	global $_debug,$_fteams,$_fteamrelay,$_FGameModes,$_currentTime,$_players,$_ChallengeInfo,$_fteams_rules,$_fteamrelay_mainlaps_xml,$_fteamrelay_lapinfo_xml;

	if($state == 'BeginRace'){
		$_fteamrelay['State'] = 'BeginRace';
		if($_debug>0) console("fteamrelaySetup:: BeginRace");

		$_fteams_rules['JoinSpecMode'] = $_FGameModes['TeamRelay']['JoinSpecMode'];
		$_fteams_rules['ConnectSpecMode'] = $_FGameModes['TeamRelay']['ConnectSpecMode'];

		$_fteamrelay['StartTime'] = $_currentTime;
		$_fteamrelay['EndTime'] = -1;
		$_fteamrelay['StartLapDelay'] = array(0);
		$_fteamrelay['FinishTime'] = -1;

		$_fteamrelay['TRelayNbLaps'] = ($_FGameModes['TeamRelay']['TRelayNbLaps'] < 2) ? 2 : $_FGameModes['TeamRelay']['TRelayNbLaps'];
		$_fteamrelay['TRelayWarmup'] = ($_FGameModes['TeamRelay']['TRelayWarmup'] < 1) ? 1 : $_FGameModes['TeamRelay']['TRelayWarmup'];

		if(is_string($_FGameModes['TeamRelay']['TRelayPenalty']))
			$_fteamrelay['TRelayPenalty'] = explode(',',$_FGameModes['TeamRelay']['TRelayPenalty']);
		else if(!is_array($_FGameModes['TeamRelay']['TRelayPenalty']))
			$_fteamrelay['TRelayPenalty'] = array($_FGameModes['TeamRelay']['TRelayPenalty']+0);
		else
			$_fteamrelay['TRelayPenalty'] = array_values($_FGameModes['TeamRelay']['TRelayPenalty']);
		if(count($_fteamrelay['TRelayPenalty']) <= 0)
			$_fteamrelay['TRelayPenalty'] = array(0);

		$min = 0;
		for($n = 0; $n < $_fteamrelay['TRelayNbLaps'] + 1; $n++){
			if(!isset($_fteamrelay['TRelayPenalty'][$n]) || $_fteamrelay['TRelayPenalty'][$n] < $min || $_fteamrelay['TRelayPenalty'][$n] > 30000)
				$_fteamrelay['TRelayPenalty'][$n] = $min;
			$min = $_fteamrelay['TRelayPenalty'][$n];
		}

		foreach($_fteams as $ftid => &$fteam){
			$fteam['TRelay']['Players'] = array();
			$fteam['TRelay']['CurLap'] = -1;
			$fteam['TRelay']['PlayerPlaying'] = '';
			$fteam['TRelay']['LapStartTime'] = -1;
			$fteam['TRelay']['LapRaceTime'] = 0;
			$fteam['TRelay']['LapRaceCPnum'] = 0;
			$fteam['TRelay']['RaceTime'] = 0;
			$fteam['TRelay']['ScriptTime'] = $_currentTime;
			$fteam['TRelay']['LastCPscriptTime'] = 0;
			$fteam['TRelay']['RaceDelay'] = 0;
			$fteam['TRelay']['PrevLock'] = false;
			$fteam['TRelay']['RaceCPs'] = array(-1=>0);
			$fteam['TRelay']['Lap'] = array();
		}

		ml_mainFWarmUpShow();
		fteamrelayShowNextPlayers('hide');
		manialinksHide(true,'fteamrelay.next');
		manialinksHide(true,'fteamrelay.checkpoints.bg');
		manialinksHide(true,'fteamrelay.checkpoints');

		fteamsUpdateTeampanelXml(true,'show');

		$_fteamrelay_mainlaps_xml = sprintf($_fteamrelay_lapinfo_xml,'',$_fteamrelay['TRelayNbLaps']);
		manialinksShowForce(true,'fteamrelay.laps', $_fteamrelay_mainlaps_xml );

		foreach($_players as $login => &$pl){
			if($pl['Active'] && $pl['FTeamId'] < 0)
				fgmodesUpdateScoretableXml($login,'show');
		}

	}elseif($state == 'Warmup'){
		$_fteamrelay['State'] = 'Warmup';
		if($_debug>0) console("fteamrelaySetup:: Warmup");

		$_fteamrelay['StartTime'] = $_currentTime;
		// end : (10s + LapAuthorTime * 130%) * TRelayWarmup + 10s
		$_fteamrelay['EndTime'] = $_fteamrelay['StartTime'] + (int) floor(10000 + $_ChallengeInfo['LapAuthorTime'] * 1.3) * $_fteamrelay['TRelayWarmup'] + 10000;
		$_fteamrelay['StartLapDelay'] = array(0);
		$_fteamrelay['FinishTime'] = -1;

		fteamrelayShowNextPlayers('hide');
		ml_mainFWarmUpShow();
		ml_mainSetTimer($_fteamrelay['EndTime']);

		$_fteamrelay_mainlaps_xml = sprintf($_fteamrelay_lapinfo_xml,'',$_fteamrelay['TRelayNbLaps']);
		manialinksShowForce(true,'fteamrelay.laps', $_fteamrelay_mainlaps_xml );

		fteamsUpdateTeampanelXml(true,'show');

		// set all as free players
		foreach($_players as $login => &$pl){
			if($pl['Active']){
				if($_debug>3) console("fteamrelaySetup({$state}):: set {$login} player");
				addCall(null,'ForceSpectator',''.$login,2);
				addCall(null,'ForceSpectator',''.$login,0);
			}
		}

	}elseif($state == 'Race'){
		$_fteamrelay['State'] = 'Race';
		if($_debug>0) console("fteamrelaySetup:: Race");

		$_fteamrelay_mainlaps_xml = sprintf($_fteamrelay_lapinfo_xml,'1',$_fteamrelay['TRelayNbLaps']);
		manialinksShowForce(true,'fteamrelay.laps', $_fteamrelay_mainlaps_xml );

		// change team join strategy while race
		$_fteams_rules['JoinSpecMode'] = 'SpecForce';
		$_fteams_rules['ConnectSpecMode'] = 'SpecForce';

		// start in future  ;)
		$_fteamrelay['StartTime'] = $_currentTime + 6000;

		// set max end : (20s + LapAuthorTime * 200%) * Race nb laps
		$_fteamrelay['EndTime'] = $_fteamrelay['StartTime'] + (int) floor($_fteamrelay['TRelayNbLaps'] * (20000 + $_ChallengeInfo['LapAuthorTime'] * 2));
		$_fteamrelay['StartLapDelay'] = array(0);
		$_fteamrelay['FinishTime'] = -1;

		ml_mainSetTimer($_fteamrelay['EndTime']);
		ml_mainFWarmUpHide();

		fgmodesUpdateScoretableXml(true,'show');
		fteamsUpdateTeampanelXml(true,'show',-1);

		manialinksHide(true,'fteamrelay.checkpoints.bg');
		manialinksHide(true,'fteamrelay.checkpoints');

		// set all as specs, show score/team board if not in a team
		/*
		foreach($_players as $login => &$pl){
			if($pl['Active'])
				addCall(null,'ForceSpectator',''.$login,1);
			if($pl['FTeamId'] < 0)
				fgmodesUpdateScoretableXml($login,'show');
		}
		*/

		// set teams players
		foreach($_fteams as $ftid => &$fteam){
			$fteam['TRelay']['Players'] = array();
			$fteam['TRelay']['CurLap'] = -1;
			$fteam['TRelay']['PlayerPlaying'] = '';
			$fteam['TRelay']['LapStartTime'] = -1;
			$fteam['TRelay']['LapRaceTime'] = 0;
			$fteam['TRelay']['LapRaceCPnum'] = 0;
			$fteam['TRelay']['RaceCPs'] = array(-1=>0);
			$fteam['TRelay']['RaceTime'] = 0;
			$fteam['TRelay']['ScriptTime'] = $_fteamrelay['StartTime'];
			$fteam['TRelay']['LastCPscriptTime'] = 0;
			$fteam['TRelay']['RaceDelay'] = 0;
			$fteam['TRelay']['Lap'] = array();
			if($fteam['Active']){
				$order = 1;
				foreach($fteam['Players'] as $login => $o){
					if(isset($_players[$login]['Active']) && $_players[$login]['Active']){
						$fteam['TRelay']['Players'][$login] = array('Login'=>''.$login,'PlayOrder'=>$_players[$login]['PlayOrder'],'StartOrder'=>$order++,'NbLaps'=>0,'Active'=>0,'Playing'=>0,'AFK'=>0,'Leaved'=>0);
						$_players[$login]['FTeamId'] = $ftid;
					}
				}
				if(count($fteam['TRelay']['Players']) > 0){
					fteamrelaySetNextPlayer($ftid);
				}
			}
		}
		fteamrelayLockTeams(true);

	}elseif($state == 'Finished'){
		$_fteamrelay['State'] = 'Finished';
		if($_debug>0) console("fteamrelaySetup:: Finished");

		$_fteams_rules['JoinSpecMode'] = $_FGameModes['TeamRelay']['JoinSpecMode'];
		$_fteams_rules['ConnectSpecMode'] = $_FGameModes['TeamRelay']['ConnectSpecMode'];

		fteamrelayBuildRoundPanel(true,true);
		fteamrelayShowNextPlayers('hide');
		manialinksHide(true,'fteamrelay.next');
		manialinksHide(true,'fteamrelay.laps');
		manialinksHide(true,'fteamrelay.checkpoints.bg');
		manialinksHide(true,'fteamrelay.checkpoints');

		// set all as spec
		foreach($_players as $login => &$pl){
			if($pl['Active']){
				if($_debug>3) console("fteamrelaySetup({$state}):: set {$login} player");
				addCallDelay(1000,null,'ForceSpectator',''.$login,2);
				addCallDelay(1100,null,'ForceSpectator',''.$login,0);
			}
		}

		addCallDelay(2000,null,'NextChallenge');
	}
}


function fteamrelayLockTeams($lock=true){
	global $_fteams,$_fteams_changes,$_currentTime,$_FGameMode;
  if($_FGameMode != 'TeamRelay')
    return;

	foreach($_fteams as $ftid => &$fteam){

		if($lock){
			$fteam['TRelay']['PrevLock'] = $fteam['Lock'];
			if($fteam['Active'] && !$fteam['Lock'] && count($fteam['TRelay']['Players']) > 1){
				$fteam['Lock'] = true;
				$fteam['Changed'] = true;
				$_fteams_changes = true;
			}

		}else{
			if(!$fteam['TRelay']['PrevLock'] && $fteam['Lock']){
				$fteam['Lock'] = false;
				if($fteam['Active']){
					$fteam['Changed'] = true;
					$_fteams_changes = true;
				}
			}
		}
	}
}


function fteamrelayFTeamsChange($event){
	global $_debug,$_players,$_fteams,$_fteamrelay,$_fteams_changes,$_FGameMode;
  if($_FGameMode != 'TeamRelay')
    return;

	foreach($_fteams as $ftid => &$fteam){
		if($fteam['Changed']){
			//console("fteamrelayFTeamsChange::[$ftid]");

			if($fteam['Active'] && $_fteamrelay['State'] == 'Race'){
				//console("fteamrelayFTeamsChange::[$ftid]Race");
			
				$changeplayer = false;

				// check if new player in team
				$newplayer = 0;
				foreach($fteam['Players'] as $login => $order){
					if(isset($_players[$login]['FTeamId'])){
						// player join the team
						if($_debug>0) console("fteamrelayFTeamsChange::[$ftid] Race, join: $login");

						if($_players[$login]['FTeamId'] >= 0 && $_players[$login]['FTeamId'] != $ftid){
							// set leaved in old team
							$oldftid = $_players[$login]['FTeamId'];
							if(isset($_fteams[$oldftid]['TRelay']['Players'][$login]['Leaved'])){
								$_fteams[$oldftid]['TRelay']['Players'][$login]['Leaved'] = 1000;
								if($_fteams[$oldftid]['Active'])
									fteamrelayShowNextPlayers($oldftid);
							}
						}

						$active = (isset($_players[$login]['Active']) && $_players[$login]['Active']) ? 0 : 200;
						if(!isset($fteam['TRelay']['Players'][$login])){
							// new player in the team
							$fteam['TRelay']['Players'][$login] = array('Login'=>''.$login,'StartOrder'=>$order++,'NbLaps'=>0,'Active'=>$active,'Playing'=>0,'AFK'=>0,'Leaved'=>0);
						}else{
							$fteam['TRelay']['Players'][$login]['Active'] = $active;
							$fteam['TRelay']['Players'][$login]['Leaved'] = 0;
						}
						$newplayer++;
						if($fteam['TRelay']['PlayerPlaying'] == '')
							$changeplayer = true;
					}
				}

				$leaved = 0;
				foreach($fteam['TRelay']['Players'] as $login => $player){
					if(!isset($_players[$login]['FTeamId']) || $_players[$login]['FTeamId'] != $ftid){
						// a player have leaved the team !

						if($_debug>0) console("fteamrelayFTeamsChange::[$ftid] Race, leave: $login");

						$leaved++;
						$fteam['TRelay']['Players'][$login]['Leaved'] = 1000;
						if($fteam['TRelay']['PlayerPlaying'] == '' || $fteam['TRelay']['PlayerPlaying'] == $login){
							// playing player not in team any more, replace him !
							$changeplayer = true;
						}

						if(count($fteam['Players']) <= 1 && $fteam['Lock']){
							// one player or less, unlock team
							$fteam['Lock'] = false;
							//fteamsUpdateTeampanelXml($login,'refresh');
							console("fteamrelayFTeamsChange:: unlock team {$ftid}...");
							$fteam['Changed'] = true;
							$_fteams_changes = true;
						}
					}
				}
				if($changeplayer){
					if($_debug>0) console("fteamrelayFTeamsChange::[$ftid] SetNextPlayer change");
					fteamrelaySetNextPlayer($ftid,true);

				}elseif($newplayer + $leaved > 0){
					fteamrelayShowNextPlayers($ftid);
				}
			}

		}
	}
}


function fteamrelaySetNextPlayer($ftid,$replace_previous=false){
	global $_debug,$_FGameMode,$_players,$_fteams,$_fteamrelay,$_currentTime,$_GameInfos,$_ChallengeInfo,$_fteamrelay_position_change,$_fteams_round,$_fteamrelay_ready_xml,$_fteamrelay_lapinfo_xml,$_fteamrelay_mainlaps_xml;
	if(!isset($_fteams[$ftid]['TRelay']['CurLap'])){
		if($_debug>0) console("fteamrelaySetNextPlayer({$ftid}):: bad team number !");
		return;
	}

	if($_fteams[$ftid]['TRelay']['CurLap'] >= $_fteamrelay['TRelayNbLaps']){
		if($_debug>0) console("fteamrelaySetNextPlayer({$ftid}):: team has already finished ! ({$_fteams[$ftid]['TRelay']['CurLap']} >= {$_fteamrelay['TRelayNbLaps']})");
		return;
	}

	// all team players as spec
	foreach($_fteams[$ftid]['TRelay']['Players'] as $login => $player){
		if(!isset($_players[$login]['Active'])){
			$_fteams[$ftid]['TRelay']['Players'][$login]['Active'] = 200;
			$_fteams[$ftid]['TRelay']['Players'][$login]['Leaved'] = 1000;

		}else{
			if(!$_players[$login]['Active'])
				$_fteams[$ftid]['TRelay']['Players'][$login]['Active'] = 200;

			else{
				$_fteams[$ftid]['TRelay']['Players'][$login]['Active'] = 0;
				
				if($_players[$login]['FTeamId'] != $ftid)
					$_fteams[$ftid]['TRelay']['Players'][$login]['Leaved'] = 1000;
				else{
					// active and in same team : set to spec
					if($_debug>3) console("fteamrelaySetNextPlayer::active and in same team , put {$login} spec");
					$_fteams[$ftid]['TRelay']['Players'][$login]['Leaved'] = 0;
					addCall(true,'ForceSpectator',''.$login,1);
				}
			}
		}
	}

	// sort and show to team players who is next player
	fteamrelayShowNextPlayers($ftid);

	// get player/login for next team player
	$login = '';
	$n = 1;
	foreach($_fteams[$ftid]['TRelay']['Players'] as $player){
		$plogin = $player['Login'];
		if($player['Active'] <= 0 && $player['Leaved'] <= 0 && $_players[$plogin]['LatestNetworkActivity'] < 10000){
			$login = $plogin;
			//console("fteamrelaySetNextPlayer({$ftid})::  select $n : $plogin {$player['Active']} {$player['Leaved']} {$_players[$plogin]['LatestNetworkActivity']}");
			break;
		}else{
			//console("fteamrelaySetNextPlayer({$ftid})::  reject $n : $plogin {$player['Active']} {$player['Leaved']} {$_players[$plogin]['LatestNetworkActivity']}");
		}
		$n++;
	}
	//console("fteamrelaySetNextPlayer({$ftid}):: --> $login");
	if($login == ''){
		$player = reset($_fteams[$ftid]['TRelay']['Players']);
		if($player['Active'] <= 0 && $player['Leaved'] <= 0)
			$login = $player['Login'];
		else{
			// no active player in team
			$_fteams[$ftid]['TRelay']['PlayerPlaying'] = '';
			$_fteamrelay_position_change = true;
			if($_debug>0) console("fteamrelaySetNextPlayer({$ftid}):: no player available");
			return;
		}
	}
	//console("fteamrelaySetNextPlayer({$ftid}):: ---> $login");


	// ----------------------------
	if($replace_previous && $_fteams[$ftid]['TRelay']['CurLap'] >= 0){
		// -----------------------------------------------------------------------
		// replace by new player on current lap
		// -----------------------------------------------------------------------
		//console("fteamrelaySetNextPlayer({$ftid}):: R---> $login");

		$curlap = $_fteams[$ftid]['TRelay']['CurLap'];
		if(!isset($_fteams[$ftid]['TRelay']['Lap'][$curlap]['Login'])){
			if($_debug>0) console("fteamrelaySetNextPlayer({$ftid}):: no lap info for lap {$curlap} ! fteams[{$ftid}][TRelay]: ".print_r($_fteams[$ftid]['TRelay'],true));
 			return;
		}
		$oldlogin = $_fteams[$ftid]['TRelay']['Lap'][$curlap]['Login'];
		if(isset($_players[$oldlogin]['Active']) && $_players[$oldlogin]['Active']){
			addCall(null,'ForceSpectator',$oldlogin,1);
	}
		
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['Login'] = $login;
		$_fteams[$ftid]['TRelay']['PlayerPlaying'] = $login;

		// setup new player start, looks like a player rs
		$diff = $_currentTime - $_fteams[$ftid]['TRelay']['Lap'][$curlap]['StartTime'];
		if($diff < 0)
			$diff = 0;
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['StartTime'] = -1;
		//$_fteams[$ftid]['TRelay']['Lap'][$curlap]['Penalty'] = keep previous value; // already included in LapRaceTime...
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['CPnum'] = -1;
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['Time'] = 0;
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['Finish0num'] = 0;
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['RSdelays'] += $diff;
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['CPs'] = array(-1=>0);

		$cpn = $_fteams[$ftid]['TRelay']['LapRaceCPnum'];
		while(isset($_fteams[$ftid]['TRelay']['RaceCPs'][$cpn])){
			unset($_fteams[$ftid]['TRelay']['RaceCPs'][$cpn++]);
		}

		$_fteams[$ftid]['TRelay']['ScriptTime'] = $_currentTime;
		$_fteams[$ftid]['TRelay']['LastCPscriptTime'] = $_currentTime;
		$_fteams[$ftid]['TRelay']['RaceTime'] = $_fteams[$ftid]['TRelay']['LapRaceTime'] + $_fteams[$ftid]['TRelay']['Lap'][$curlap]['Time'] + $_fteams[$ftid]['TRelay']['Lap'][$curlap]['RSdelays'];
		$_fteams[$ftid]['TRelay']['RaceDelay'] = $_currentTime - $_fteamrelay['StartTime'] - $_fteams[$ftid]['TRelay']['RaceTime'];

		$_fteams[$ftid]['TRelay']['LapStartTime'] = $_fteamrelay['StartTime'] + $_fteamrelay['StartLapDelay'][$curlap] + $_fteams[$ftid]['TRelay']['LapRaceTime'];

		$_fteams_round[$ftid]['RoundCPs'] = $_fteams[$ftid]['TRelay']['LapRaceCPnum'];
		$_fteams_round[$ftid]['RoundTime'] = $_fteams[$ftid]['TRelay']['RaceTime'];
		$_fteams_round[$ftid]['RoundScore'] = $curlap;

		$_fteamrelay_position_change = true;

		$diff = $_fteams[$ftid]['TRelay']['LapStartTime'] - $_currentTime;
		//if($_debug>0) console("fteamrelaySetNextPlayer({$ftid}):: change player {$login} => {$login} -> {$diff} ({$_fteams[$ftid]['TRelay']['RaceDelay']})");
		if($_debug>0) console("fteamrelaySetNextPlayer({$ftid}):: change player {$login} => {$login} -> {$diff} ({$_fteams[$ftid]['TRelay']['RaceTime']},{$_fteams[$ftid]['TRelay']['RaceDelay']})  curtime={$_currentTime} fteams[{$ftid}][TRelay]: ".print_r($_fteams[$ftid]['TRelay'],true));


	}else{
		// -----------------------------------------------------------------------
		// next player for next team lap !
		// -----------------------------------------------------------------------
		//console("fteamrelaySetNextPlayer::$ftid N---> $login");

		$curlap = count($_fteams[$ftid]['TRelay']['Lap']);

		if(!isset($_fteamrelay['StartLapDelay'][$curlap])){
			// compute next StartLapDelay (first team which needs it)
			$strdelay = '';
			$lapdelay = 0;
			foreach($_fteams as $ftid2 => &$fteam){
				if($fteam['Active']){
					// compute the race delay for next lap for the team : consider 3500ms for each future lap
					$racedelay = $fteam['TRelay']['RaceDelay'] + ($curlap - $_fteams[$ftid2]['TRelay']['CurLap']) * 3500;
					$strdelay .= ", {$ftid2}:{$fteam['TRelay']['RaceDelay']}/{$_fteams[$ftid2]['TRelay']['CurLap']}->{$racedelay}";
					if($racedelay > $lapdelay)
						$lapdelay = $racedelay;
				}
			}
			$_fteamrelay['StartLapDelay'][$curlap] = $lapdelay;
			if($_debug>0) console("ftrelaySetNextPlayer({$ftid}):: StartLapDelay[{$curlap}]={$lapdelay}{$strdelay}");
			
			// update max end : 10s + (LapAuthorTime + 20s) * (3 * laps done)
			//$_fteamrelay['EndTime'] = $_fteamrelay['StartTime'] + 10000 + ($_fteamrelay['TRelayNbLaps'] + 1) * ($_ChallengeInfo['LapAuthorTime'] + 20000);
			//$_fteamrelay['EndTime'] = $_fteamrelay['StartTime'] + 10000 + ($curlap + 3) * ($_ChallengeInfo['LapAuthorTime'] + 20000);
			//ml_mainSetTimer($_fteamrelay['EndTime']);

			// show laps to all not in a team
			if($curlap < $_fteamrelay['TRelayNbLaps']){
				$_fteamrelay_mainlaps_xml = sprintf($_fteamrelay_lapinfo_xml,$curlap+1,$_fteamrelay['TRelayNbLaps']);
				foreach($_players as $plogin => &$pl){
					if($pl['FTeamId'] < 0){
						//console("ftrelaySetNextPlayer({$ftid}):: spec laps to $plogin");
						manialinksShowForce(''.$plogin,'fteamrelay.laps', $_fteamrelay_mainlaps_xml );
					}
				}
			}
		}

		// update the NbLaps of previous player
		$oldplap = end($_fteams[$ftid]['TRelay']['Lap']);
		$oldplapnum = key($_fteams[$ftid]['TRelay']['Lap']);
		if($oldplap !== false && isset($_fteams[$ftid]['TRelay']['Players'][$oldplap['Login']]['NbLaps'])){
			$_fteams[$ftid]['TRelay']['Players'][$oldplap['Login']]['NbLaps']++;

			if($_fteams[$ftid]['TRelay']['CurLap'] < 0 || $_fteams[$ftid]['TRelay']['CurLap'] != $oldplapnum){
				if($_debug>0) console("ftrelaySetNextPlayer({$ftid}):: bad lap num {$_fteams[$ftid]['TRelay']['CurLap']}/{$oldplap} with [Lap] not empty !  fteams[{$ftid}][TRelay]: ".print_r($_fteams[$ftid]['TRelay'],true));
			}

		}else{
			// no previous user : initial race time is $_currentTime - $_fteamrelay['StartTime'] (min 0)
			if($_fteamrelay['StartTime'] < $_currentTime)
				$_fteams[$ftid]['TRelay']['RaceTime'] = $_currentTime - $_fteamrelay['StartTime'];

			if($_fteams[$ftid]['TRelay']['CurLap'] >= 0){
				if($_debug>0) console("ftrelaySetNextPlayer({$ftid}):: bad lap num {$_fteams[$ftid]['TRelay']['CurLap']} with [Lap] empty !  fteams[{$ftid}][TRelay]: ".print_r($_fteams[$ftid]['TRelay'],true));
			}
		}

		// setup new lap infos
		$_fteams[$ftid]['TRelay']['CurLap'] = $curlap;
		$_fteams[$ftid]['TRelay']['LapRaceTime'] = $_fteams[$ftid]['TRelay']['RaceTime'];
		$_fteams[$ftid]['TRelay']['LapRaceCPnum'] = count($_fteams[$ftid]['TRelay']['RaceCPs']) - 1;

		if($curlap >= $_fteamrelay['TRelayNbLaps']){
			// ----------------------------
			// team has finished its laps !
			// ----------------------------

			$_fteams[$ftid]['TRelay']['PlayerPlaying'] = '';

			$laps_xml = sprintf($_fteamrelay_lapinfo_xml,(($curlap < $_fteamrelay['TRelayNbLaps']) ? $curlap + 1 : 'finished'),$_fteamrelay['TRelayNbLaps']);
			foreach($_fteams[$ftid]['TRelay']['Players'] as $plogin => $player){
				if($player['Active'] <= 0 && $player['Leaved'] <= 0){
					manialinksShowForce(''.$plogin,'fteamrelay.laps', $laps_xml );
					manialinksHide(''.$plogin,'fteamrelay.checkpoints.bg');
					manialinksHide(''.$plogin,'fteamrelay.checkpoints');
					fteamsUpdateTeampanelXml(''.$plogin,'show');
				}
				$_fteams[$ftid]['TRelay']['Players'][$plogin]['Playing'] = 0;
			}
			$_fteams[$ftid]['TRelay']['ScriptTime'] = $_currentTime;
			$_fteams[$ftid]['TRelay']['LastCPscriptTime'] = $_currentTime;

			fteamrelayShowNextPlayers($ftid);

			if($_debug>0) console("fteamrelaySetNextPlayer({$ftid}):: finished.  fteams[{$ftid}][TRelay]: ".print_r($_fteams[$ftid]['TRelay'],true));
			return;
		}
		
		$plnblaps = $player['NbLaps'] + ($player['Playing'] > 0 ? 1 : 0);
		if(isset($_fteamrelay['TRelayPenalty'][$plnblaps]))
			$playerpenalty = $_fteamrelay['TRelayPenalty'][$plnblaps];
		else
			$playerpenalty = end($_fteamrelay['TRelayPenalty']);
			
		$_fteams[$ftid]['TRelay']['Lap'][$curlap] = array('Login'=>$login,
																											'Penalty'=>$playerpenalty,
																											'StartTime'=>-1,
																											'RSdelays'=>0,
																											'Finish0num'=>0,
																											'CPnum'=>-1,
																											'Time'=>0,
																											'CPs'=>array(-1=>0));
		$_fteams[$ftid]['TRelay']['PlayerPlaying'] = $login;
		
		$_fteams[$ftid]['TRelay']['LapRaceTime'] += $playerpenalty;
		$_fteams[$ftid]['TRelay']['RaceTime'] = $_fteams[$ftid]['TRelay']['LapRaceTime'];
		$_fteams[$ftid]['TRelay']['ScriptTime'] = $_currentTime;
		$_fteams[$ftid]['TRelay']['LastCPscriptTime'] = $_currentTime;
		$_fteams[$ftid]['TRelay']['LapStartTime'] = $_fteamrelay['StartTime'] + $_fteamrelay['StartLapDelay'][$curlap] + $_fteams[$ftid]['TRelay']['LapRaceTime'];
		$_fteams[$ftid]['TRelay']['RaceDelay'] = $_fteams[$ftid]['TRelay']['LapStartTime'] - $_fteamrelay['StartTime'] - $_fteams[$ftid]['TRelay']['RaceTime'];
		
		$_fteams_round[$ftid]['RoundCPs'] = $_fteams[$ftid]['TRelay']['LapRaceCPnum'];
		$_fteams_round[$ftid]['RoundTime'] = $_fteams[$ftid]['TRelay']['RaceTime'];
		$_fteams_round[$ftid]['RoundScore'] = $curlap;

		$_fteamrelay_position_change = true;

		$diff = $_fteams[$ftid]['TRelay']['LapStartTime'] - $_currentTime;
		//if($_debug>0) console("fteamrelaySetNextPlayer({$ftid}):: {$login} -> {$diff} ({$_fteams[$ftid]['TRelay']['RaceDelay']})");
		if($_debug>0) console("fteamrelaySetNextPlayer({$ftid}):: {$login} -> {$diff} ({$_fteams[$ftid]['TRelay']['RaceTime']},{$_fteams[$ftid]['TRelay']['RaceDelay']})  curtime={$_currentTime} fteams[{$ftid}][TRelay]: ".print_r($_fteams[$ftid]['TRelay'],true));
	}

	// ----------------------------
	manialinksShowForce($login,'fteamrelay.ready', sprintf($_fteamrelay_ready_xml,$_fteams[$ftid]['TRelay']['CurLap']+1,$_fteamrelay['TRelayNbLaps']) );

	// set the elected and show again
	$laps_xml = sprintf($_fteamrelay_lapinfo_xml,$curlap + 1,$_fteamrelay['TRelayNbLaps']);
	foreach($_fteams[$ftid]['TRelay']['Players'] as $plogin => $player){
		if($player['Active'] <= 0 && $player['Leaved'] <= 0){
			manialinksShowForce(''.$plogin,'fteamrelay.laps', $laps_xml );
			manialinksHide(''.$plogin,'fteamrelay.checkpoints.bg');
			manialinksHide(''.$plogin,'fteamrelay.checkpoints');
			fteamsUpdateTeampanelXml(''.$plogin,'show');
		}

		$_fteams[$ftid]['TRelay']['Players'][$plogin]['Playing'] = 0;
	}
	$_fteams[$ftid]['TRelay']['Players'][$login]['Playing'] = -1000;
	fteamrelayShowNextPlayers($ftid);
}


function fteamrelayStartPlayers(){
	global $_debug,$_FGameMode,$_players,$_fteams,$_fteamrelay,$_currentTime,$_GameInfos,$_ChallengeInfo,$_fteamrelay_position_change;

	$startplayer = false;
	foreach($_fteams as $ftid => &$fteam){
		$curlap = $fteam['TRelay']['CurLap'];
		$lapstarttime = $fteam['TRelay']['LapStartTime'];

		if($fteam['Active'] && $curlap >= 0 && $lapstarttime > 0 && $lapstarttime <= $_currentTime){
			// time to start player !
			$login = $fteam['TRelay']['Lap'][$curlap]['Login'];
			$fteam['TRelay']['LapStartTime'] = -1;
			$fteam['TRelay']['Lap'][$curlap]['StartTime'] = $_currentTime;
			$fteam['TRelay']['ScriptTime'] = $_currentTime;
			$fteam['TRelay']['LastCPscriptTime'] = $_currentTime;
			$fteam['TRelay']['RaceDelay'] = $_currentTime - $_fteamrelay['StartTime'] - $_fteams[$ftid]['TRelay']['RaceTime'];
			if($_debug>0) console("fteamrelayStartPlayers::[$ftid] start player {$login} ({$fteam['TRelay']['RaceDelay']})");

			$fteam['TRelay']['Players'][$login]['Playing'] = 10;

			// set team players as spec on the playing mate
			foreach($fteam['TRelay']['Players'] as $plogin => $player){
				if($plogin != $login && isset($_players[$plogin]['Active']) && $_players[$plogin]['Active']){
					if($_debug>0) console("fteamrelayStartPlayers:: spec $plogin");
					addCall(null,'ForceSpectator',''.$plogin,1);
					addCallDelay(2000,null,'ForceSpectatorTarget',''.$plogin,$login,1);
					manialinksHide(''.$plogin,'fteamrelay.checkpoints.bg');
					manialinksHide(''.$plogin,'fteamrelay.checkpoints');

					fteamsUpdateTeampanelXml(''.$plogin,'show');
				}
			}

			// show team players who is next player
			fteamrelayShowNextPlayers($ftid);

			fgmodesUpdateScoretableXml($login,'hide');
			fteamsUpdateTeampanelXml($login,'hide');
			manialinksHide($login,'fteamrelay.ready');
			addCall(null,'ForceSpectator',$login,2);

			$startplayer = true;
		}
	}

	if($startplayer){
		$_fteamrelay_position_change = true;
		fteamsComputePositions(-2);
		fteamsUpdateMapScores();
	}
}


function fteamrelayCheckPlayers(){
	global $_debug,$_fteams,$_players,$_currentTime,$_fteamrelay;

	foreach($_fteams as $ftid => &$fteam){
		if($fteam['Active'] && $fteam['TRelay']['PlayerPlaying'] != '' && 
			 $fteam['TRelay']['LapStartTime'] < 0 && $fteam['TRelay']['CurLap'] < $_fteamrelay['TRelayNbLaps']){

			$login = $fteam['TRelay']['PlayerPlaying'];

			if(isset($_players[$login]['Active']) && $_players[$login]['Active']){

				if($_players[$login]['LatestNetworkActivity'] > 15000){
					// current playing player is in netlost for more than 15s : kick him !
					if($_debug>0) console("fteamrelayCheckPlayers::[$ftid] kick {$login} (netlost: {$_players[$login]['LatestNetworkActivity']})");
					addCall(true,'Kick',$login,'$w$ff0You were kicked because of netlost, it will permit to your team mates to play... $z');

				}elseif($_players[$login]['IsSpectator']){
					$player = end($fteam['TRelay']['Lap']);
					if($player['Login'] != $login){
						// should not happen !  replace the player
						if($_debug>0) console("fteamrelayCheckPlayers::[$ftid] SetNextPlayer: $login is spec and bad player ({$player['Login']})  fteams[{$ftid}][TRelay]: ".print_r($_fteams[$ftid]['TRelay'],true));

					}elseif($fteam['TRelay']['LapStartTime'] < 0 && $player['StartTime'] > 0 && $_currentTime - $player['StartTime'] > 5000){
						// should not happen !  replace the player
						if($_debug>0) console("fteamrelayCheckPlayers::[$ftid] SetNextPlayer: $login is spec but should be player (".($_currentTime - $player['StartTime']).")  fteams[{$ftid}][TRelay]: ".print_r($_fteams[$ftid]['TRelay'],true));
						fteamrelaySetNextPlayer($ftid,true);
					}
				}
			}
		}
	}
}



// used by : fteamrelayShowNextPlayers()
function fteamrelayTeamPlayersSort($a,$b){
	if($a['NbLaps']+$a['Active']+$a['Playing']+$a['AFK']+$a['Leaved'] < $b['NbLaps']+$b['Active']+$b['Playing']+$b['AFK']+$b['Leaved'])
		return -1;
	else if($a['NbLaps']+$a['Active']+$a['Playing']+$a['AFK']+$a['Leaved'] > $b['NbLaps']+$b['Active']+$b['Playing']+$b['AFK']+$b['Leaved'])
		return 1;
	if($a['PlayOrder'] < $b['PlayOrder'])
		return -1;
	else if($a['PlayOrder'] > $b['PlayOrder'])
		return 1;
	if($a['StartOrder'] < $b['StartOrder'])
		return -1;
	else if($a['StartOrder'] > $b['StartOrder'])
		return 1;
	return strcmp($a['Login'],$b['Login']);
}


function fteamrelayEverysecond($event,$seconds){
	global $_debug,$_FGameMode,$_fteamrelay,$_StatusCode,$_currentTime,$_fteamrelay_position_change;
	if($_FGameMode != 'TeamRelay')
		return;
	if($_debug>6) console("fteamrelay.Event[$event]");

	if($_StatusCode == 4){
		fteamrelayCheckEnd();
	}

	if($_fteamrelay_position_change){
		$_fteamrelay_position_change = false;

		if($_fteamrelay['State'] == 'Race'){
			fteamrelayBuildRoundPanel(true);
			fteamrelayShowCheckpoints();
		}
	}

	// check if a playing player is in netlost, to kick him
	if($_fteamrelay['State'] == 'Race')
		fteamrelayCheckPlayers();
}


function fteamrelayServerStart_Reverse($event){
	global $_debug,$_FGameMode,$_players,$_fteamrelay,$_fteams,$_fteamrelay_mainlaps_xml;
	if($_FGameMode != 'TeamRelay')
		return;
	if($_debug>6) console("fteamrelay.Event[$event]('$login')");

}


function fteamrelayPlayerShowML_Post($event,$login,$ShowML){
	global $_debug,$_FGameMode,$_players,$_fteamrelay,$_fteams,$_fteamrelay_mainlaps_xml;
	if($_FGameMode != 'TeamRelay')
		return;
	if($_debug>6) console("fteamrelay.Event[$event]('$login')");

	$ftid = $_players[$login]['FTeamId'];

	manialinksHideHudPart('fteamrelay','checkpoint_list',$login);

	if($_fteamrelay_mainlaps_xml != '')
		manialinksShowForce(''.$login,'fteamrelay.laps', $_fteamrelay_mainlaps_xml );

	if($ftid >= 0 && $_fteams[$ftid]['Active']){
		// is in team

		if($_fteamrelay['State'] == 'Race'){
			// in race
			if($_fteams[$ftid]['TRelay']['PlayerPlaying'] != '' &&
				 $_fteams[$ftid]['TRelay']['PlayerPlaying'] == $login){
				// current player
				fteamrelayShowCheckpoints();

			}else{
				// not current player
				fteamsUpdateTeampanelXml($login,'show');
				fteamrelayShowNextPlayers($ftid);
			}

		}elseif($_fteamrelay['State'] == 'Finished'){
			// finished

		}else{
			// wu
			fteamsUpdateTeampanelXml($login,'show');
			
		}

	}else{
		// not in team (is already spec), show score/team panel, can join if not script lock
		fgmodesUpdateScoretableXml($login,'show');
	}
}


function fteamrelayPlayerConnect($event,$login,$pinfo,$pdetailedinfo,$pranking){
	global $_debug,$_FGameMode,$_players,$_fteamrelay,$_fteams,$_fteamrelay_mainlaps_xml;
	if($_FGameMode != 'TeamRelay')
		return;
	if($_debug>6) console("fteamrelay.Event[$event]('$login')");
	$ftid = $_players[$login]['FTeamId'];

	manialinksHideHudPart('fteamrelay','checkpoint_list',$login);

	if($_fteamrelay_mainlaps_xml != '')
		manialinksShowForce(''.$login,'fteamrelay.laps', $_fteamrelay_mainlaps_xml );

	if($ftid >= 0 && $_fteams[$ftid]['Active']){
		// is in team

		fteamsUpdateTeampanelXml($login,'show');

		if($_fteamrelay['State'] == 'Race'){
			// in race
			if($_fteams[$ftid]['TRelay']['CurLap'] < $_fteamrelay['TRelayNbLaps']){
				// race not finished
				if($_debug>0) console("fteamrelayPlayerConnect($login)::[$ftid] is already in team");
				if(isset($_fteams[$ftid]['TRelay']['Players'][$login]['Active'])){
					// show next player panel if not current player
					$_fteams[$ftid]['TRelay']['Players'][$login]['Active'] = 0;
					fteamrelayShowNextPlayers($ftid);
				}
				
				if($_fteams[$ftid]['TRelay']['PlayerPlaying'] == ''){
					// change next player if no current team player
					if($_debug>0) console("fteamrelayPlayerConnect($login)::[$ftid] none was playing -> SetNextPlayer change");
					fteamrelaySetNextPlayer($ftid,true);
					
				}elseif($_fteams[$ftid]['TRelay']['PlayerPlaying'] == $login){
					// set player if current team player
					if($_debug>0) console("fteamrelayPlayerConnect($login)::[$ftid] was playing -> set player");
					addCall(null,'ForceSpectator',$login,2);
					
				}else{
					// is not the current player, set spec
					if($_debug>0) console("fteamrelayPlayerConnect($login)::[$ftid] was not playing -> set spec");
					addCall(null,'ForceSpectator',$login,1);
				}
			}else{
				// race finished, set spec
				if($_debug>0) console("fteamrelayPlayerConnect($login)::[$ftid] team has finished race -> set spec");
				addCall(null,'ForceSpectator',$login,1);
			}

		}else{
			// not in race, set as free player
			if($_debug>3) console("fteamrelayPlayerConnect:: set {$login} player");
			addCall(null,'ForceSpectator',$login,2);
			addCall(null,'ForceSpectator',$login,0);
		}

	}else{
		// not in team (is already spec), show score/team panel, can join if not script lock
		if($_debug>3) console("fteamrelayPlayerConnect($login)::not in team (is already spec), put spec");
		addCall(null,'ForceSpectator',$login,1);
		fgmodesUpdateScoretableXml($login,'show');
	}
}


function fteamrelayPlayerDisconnect($event,$login){
	global $_debug,$_FGameMode,$_fteamrelay,$_fteams,$_players;
	if($_FGameMode != 'TeamRelay')
		return;
	if($_debug>6) console("fteamrelay.Event[$event]('$login')");

	$ftid = $_players[$login]['FTeamId'];
	if($ftid >= 0 && $_fteamrelay['State'] == 'Race' && $_fteams[$ftid]['Active']){
		if(isset($_fteams[$ftid]['TRelay']['Players'][$login]['Active'])){
			$_fteams[$ftid]['TRelay']['Players'][$login]['Active'] = 200;
			fteamrelayShowNextPlayers($ftid);
		}

		if($_fteams[$ftid]['TRelay']['PlayerPlaying'] != '' &&
			 $_fteams[$ftid]['TRelay']['PlayerPlaying'] == $login){
			if($_debug>0) console("fteamrelayPlayerDisconnect::[$ftid] SetNextPlayer change");
			fteamrelaySetNextPlayer($ftid,true);
		}
	}
}


function fteamrelayBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
	global $_debug,$_players,$_FGameMode,$_NextFGameMode,$_PrevFGameMode,$_sleep_short;

	if($_FGameMode != 'TeamRelay'){
		if($_PrevFGameMode == 'TeamRelay'){
			// was previously in TeamRelay, release 'checkpoint_list' hud part control
			manialinksShowHudPart('fteamrelay','checkpoint_list',true);
			manialinksGetHudPartControl(true,'checkpoint_list');
		}
		return;
	}

	if($_PrevFGameMode != 'TeamRelay'){
		// was not previously in TeamRelay, take 'checkpoint_list' hud part control
		manialinksGetHudPartControl('fteamrelay','checkpoint_list');
	}

	// hide checkpoint_list for all
	manialinksHideHudPart('fteamrelay','checkpoint_list',true);

	$_sleep_short = true; // reduce mainloop time to increase precision in TeamRelay
	if($_debug>6) console("fteamrelay.Event[$event]($newcup,$warmup,$fwarmup)");

	fteamrelaySetup();
	manialinksHide(true,'fteamrelay.laps');
}


function fteamrelayBeginRound($event){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamRelay')
		return;
	if($_debug>6) console("fteamrelay.Event[$event]");

	fteamrelaySetup('Warmup');

	fteamsBuildScore();
}


function fteamrelayEverytime($event){
	global $_debug,$_FGameMode,$_fteamrelay;
	if($_FGameMode != 'TeamRelay' || $_fteamrelay['State'] != 'Race')
		return;

	fteamrelayStartPlayers();
}


// $status based on strict game hud: 0=playing, 1=spec, 2=race finished
function fteamrelayPlayerStatusChange($event,$login,$status,$oldstatus){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamRelay')
		return;

	if($status != 1){
		manialinksHide($login,'fteamrelay.ready');
	}
	if($status != 0){
		manialinksHide($login,'fteamrelay.checkpoints.bg');
		manialinksHide($login,'fteamrelay.checkpoints');
	}
}


function fteamrelayPlayerCheckpoint($event,$login,$time,$lapnum,$checkpt){
	global $_debug,$_FGameMode,$_fteamrelay,$_fteams,$_players,$_currentTime,$_fteams_round,$_fteamrelay_position_change,$_fteams_max;
	if($_FGameMode != 'TeamRelay' || $_fteamrelay['State'] != 'Race' || !isset($_players[$login]['FTeamId']))
		return;
	if($_debug>6) console("fteamrelay.Event[$event]('$login',$time,$lapnum,$checkpt)");

	// update team RaceTime, ScriptTime, RaceCps
	$ftid = $_players[$login]['FTeamId'];
	if($ftid < 0 || $ftid >= $_fteams_max){
		// player not in a fteam, force him spec !
		addCall(null,'ForceSpectator',$login,1);
		if($_debug>0) console("fteamrelayPlayerCheckpoint($login,$time,$lapnum,$checkpt): not in a fteam -> go spec !");
		return;
	}
	$curlap = $_fteams[$ftid]['TRelay']['CurLap'];
	if($_players[$login]['RSdelays'] > 0){
		if($_debug>0) console("fteamrelayPlayerCheckpoint($login,$time,$lapnum,$checkpt)::[$ftid] ghost restarts, cumulate RSdelay={$_fteams[$ftid]['TRelay']['Lap'][$curlap]['RSdelays']}+{$_players[$login]['RSdelays']}");
		fteamsChatTeam($ftid,$_players[$login]['NickName'].localeText(null,'interact').' ghost restarts, add time : '.MwDiffTimeToString($_players[$login]['RSdelays']));
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['RSdelays'] += $_players[$login]['RSdelays'];
		$_players[$login]['RSdelays'] = 0;
	}
	$_fteams[$ftid]['TRelay']['Lap'][$curlap]['CPs'][$checkpt] = $time;
	$_fteams[$ftid]['TRelay']['Lap'][$curlap]['CPnum'] = $checkpt;
	$_fteams[$ftid]['TRelay']['Lap'][$curlap]['Time'] = $time;

	$_fteams[$ftid]['TRelay']['ScriptTime'] = $_currentTime;
	$_fteams[$ftid]['TRelay']['LastCPscriptTime'] = $_currentTime;
	$_fteams[$ftid]['TRelay']['RaceTime'] = $_fteams[$ftid]['TRelay']['LapRaceTime'] + $_fteams[$ftid]['TRelay']['Lap'][$curlap]['Time'] + $_fteams[$ftid]['TRelay']['Lap'][$curlap]['RSdelays'];
	$racecpnum = $_fteams[$ftid]['TRelay']['LapRaceCPnum'] + $checkpt;
	$_fteams[$ftid]['TRelay']['RaceCPs'][$racecpnum] = $_fteams[$ftid]['TRelay']['RaceTime'];
	$_fteams[$ftid]['TRelay']['RaceDelay'] = $_currentTime - $_fteamrelay['StartTime'] - $_fteams[$ftid]['TRelay']['RaceTime'];

	$_fteams_round[$ftid]['RoundCPs'] = $racecpnum + 1;
	$_fteams_round[$ftid]['RoundTime'] = $_fteams[$ftid]['TRelay']['RaceTime'];
	$_fteams_round[$ftid]['RoundScore'] = $curlap;
	fteamsComputePositions(-1); // will sort $_fteams_round in current round
	fteamsUpdateMapScores();
	$_fteamrelay_position_change = true;

	if($_debug>0) console("fteamrelayPlayerCheckpoint($login,$time,$lapnum,$checkpt)->({$_fteams[$ftid]['TRelay']['RaceTime']},{$curlap},{$racecpnum})::[$ftid] rdelay={$_fteams[$ftid]['TRelay']['RaceDelay']}");
}


function fteamrelayPlayerSpecFinish($event,$login){
	fteamrelayPlayerFinish($event,$login,0,null);
}


function fteamrelayPlayerFinish($event,$login,$time,$checkpts){
	global $_debug,$_FGameMode,$_fteamrelay,$_fteams,$_players,$_currentTime,$_fteams_round,$_fteamrelay_position_change;
	if($_FGameMode != 'TeamRelay')
		return;
	//if($_debug>0) console("fteamrelayPlayerFinish('$login',$time):: {$_players[$login]['RSdelays']}");
	
	if($_fteamrelay['State'] != 'Race'){
		if($time > 0){
			if($_debug>3) console("fteamrelayPlayerFinish($login)::not in race, put spec");
			addCall(null,'ForceSpectator',$login,1);
			addCallDelay(1,null,'ForceSpectator',$login,2);
			addCallDelay(1,null,'ForceSpectator',$login,0);
		}
		return;
	}

	if(!isset($_players[$login]['FTeamId']) || $_players[$login]['FTeamId'] < 0){
		return;
	}
	$ftid = $_players[$login]['FTeamId'];
	$curlap = $_fteams[$ftid]['TRelay']['CurLap'];

	if(!isset($_fteams[$ftid]['TRelay']['Lap'][$curlap]['StartTime']) || $_fteams[$ftid]['TRelay']['Lap'][$curlap]['StartTime'] < 0)
		return;

	if($time > 0){
		// player has finished his lap
		if($_debug>3) console("fteamrelayPlayerFinish($login,$time)::player has finished his lap, put spec");
		addCall(null,'ForceSpectator',$login,1);

		if($_debug>0) console("fteamrelayPlayerFinish($login,$time)::[$ftid] -> fteamrelaySetNextPlayer({$ftid})");

		manialinksHide($login,'fteamrelay.checkpoints.bg');
		manialinksHide($login,'fteamrelay.checkpoints');

		fteamrelaySetNextPlayer($ftid);
		$_fteamrelay_position_change = true;

		fteamrelayCheckEnd();

	}else if($_fteams[$ftid]['TRelay']['Lap'][$curlap]['Login'] == $login){
		// playing player has done a rs ?
		$diff = $_currentTime - $_fteams[$ftid]['TRelay']['Lap'][$curlap]['StartTime'];
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['StartTime'] = $_currentTime;
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['CPnum'] = -1;
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['Time'] = 0;
		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['CPs'] = array(-1=>0);

		$cpn = $_fteams[$ftid]['TRelay']['LapRaceCPnum'];
		while(isset($_fteams[$ftid]['TRelay']['RaceCPs'][$cpn])){
			unset($_fteams[$ftid]['TRelay']['RaceCPs'][$cpn++]);
		}

		// if first finish,0 just after going player, then don't add RSdelay
		if($_fteams[$ftid]['TRelay']['Lap'][$curlap]['Finish0num'] > 0 || $diff > 3000){
			$_fteams[$ftid]['TRelay']['Lap'][$curlap]['RSdelays'] += $diff;
			fteamsChatTeam($ftid,$_players[$login]['NickName'].localeText(null,'interact').' have restarted, add time : '.MwDiffTimeToString($diff)." (#{$_fteams[$ftid]['TRelay']['Lap'][$curlap]['Finish0num']})");
			if($_debug>0) console("fteamrelayPlayerFinish({$login},{$time})::[$ftid] -> RS ({$diff},{$_fteams[$ftid]['TRelay']['Lap'][$curlap]['Finish0num']})");

		}else{
			if($diff > 800)
				fteamsChatTeam($ftid,$_players[$login]['NickName'].localeText(null,'interact').' start lag : time not added ('.MwDiffTimeToString($diff).')');
			if($_debug>0) console("fteamrelayPlayerFinish({$login},{$time})::[$ftid] -> start lag RS ({$diff} not added)");
		}

		$_fteams[$ftid]['TRelay']['Lap'][$curlap]['Finish0num']++;

		$_fteams[$ftid]['TRelay']['ScriptTime'] = $_currentTime;
		$_fteams[$ftid]['TRelay']['RaceTime'] = $_fteams[$ftid]['TRelay']['LapRaceTime'] + $_fteams[$ftid]['TRelay']['Lap'][$curlap]['Time'] + $_fteams[$ftid]['TRelay']['Lap'][$curlap]['RSdelays'];
		$_fteams[$ftid]['TRelay']['RaceDelay'] = $_currentTime - $_fteamrelay['StartTime'] - $_fteams[$ftid]['TRelay']['RaceTime'];

		$_fteams_round[$ftid]['RoundCPs'] = $_fteams[$ftid]['TRelay']['LapRaceCPnum'];
		$_fteams_round[$ftid]['RoundTime'] = $_fteams[$ftid]['TRelay']['RaceTime'];
		$_fteams_round[$ftid]['RoundScore'] = $curlap;
		fteamsComputePositions(-2);
		fteamsUpdateMapScores();
		$_fteamrelay_position_change = true;

		if($_debug>0 && $_fteams[$ftid]['TRelay']['Lap'][$curlap]['RSdelays'] > 0)
			console("ftrelayPlayerFinish({$login},{$time})::[$ftid] {$diff}/{$_fteams[$ftid]['TRelay']['Lap'][$curlap]['Finish0num']} -> RSdiff={$_fteams[$ftid]['TRelay']['Lap'][$curlap]['RSdelays']}");
	}
	console("ftrelayPlayerFinish({$login},{$time})::[$ftid] TRelay: ".print_r($_fteams[$ftid]['TRelay'],true)." tround: ".print_r($_fteams_round[$ftid]['TRelay'],true));
}


function fteamrelayBeforeEndRound($event,$delay,$time){
  global $_debug,$_FGameMode,$_players,$_fteamrelay;
  if($_FGameMode != 'TeamRelay')
    return;

	// set all as players (seems to be too late in EndRace to avoid quick podium)
	foreach($_players as $login => &$pl){
		if($pl['Active']){
			if($_debug>3) console("fteamrelayBeforeEndRound({$state}):: set {$login} player");
			addCall(null,'ForceSpectator',''.$login,2);
			addCall(null,'ForceSpectator',''.$login,0);
		}
	}
}

function fteamrelayEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_debug,$_FGameMode,$_players,$_fteamrelay;
	if($_FGameMode != 'TeamRelay')
		return;
	if($_debug>6) console("fteamrelay.Event[$event]($continuecup,$warmup,$fwarmup)");

	//fteamrelayBuildRoundPanel(true,true);
	fteamrelayShowNextPlayers('hide');
	manialinksHide(true,'fteamrelay.ready');
	manialinksHide(true,'fteamrelay.next');
	manialinksHide(true,'fteamrelay.laps');

	console("fteamrelayEndRound:: _fteams: ".print_r($_fteams,true));
}


function fteamrelayEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup){
	global $_debug,$_FGameMode,$_players;
	if($_FGameMode != 'TeamRelay')
		return;
	if($_debug>6) console("fteamrelay.Event[$event]($continuecup,$warmup,$fwarmup)");

	fteamrelayLockTeams(false);
	manialinksHide(true,'fteamrelay.next');
	manialinksHide(true,'fteamrelay.laps');
	manialinksHide(true,'fteamrelay.checkpoints.bg');
	manialinksHide(true,'fteamrelay.checkpoints');

	ml_mainSetTimer(-1);
	ml_mainFWarmUpHide();
}

function fteamrelayPointsRuleFunc(){
	global $_debug,$_players_checkchange,$_players,$_players_positions,$_players_actives,$_players_spec,$_players_giveup,$_players_giveup2,$_players_playing,$_currentTime,$_players_finished,$_GameInfos,$_teams,$_PlayerList,$_GameInfos,$_StatusCode,$_players_antilock,$_players_round_restarting,$_players_round_time,$_MFCTransition,$_fteams_max,$_fteams_on,$_fteams_rules,$_fteams,$_fteams_round,$_fteams_pointsrule,$_fteams_scoresrule;

	foreach($_fteams_round as $ftid => &$fteamr){
		$fteamr['RoundScore'] = 0;
		$fteamr['RoundPoints'] = 0;
		$fteamr['RoundCPs'] = 0;
		$fteamr['RoundTime'] = 0;
		$fteamr['NbPlaying'] = 0;
		$fteamr['TmpNbPlayers'] = 0;
		$fteamr['BestPlayerPos'] = 1000;

		if(isset($_fteams[$ftid]['TRelay']['LapRaceCPnum'])){
			$fteamr['RoundCPs'] = $_fteams[$ftid]['TRelay']['LapRaceCPnum'];
			$fteamr['RoundTime'] = $_fteams[$ftid]['TRelay']['RaceTime'];
			//console("fteamrelayPointsRuleFunc:: copy TRelay LapRaceCPnum and RaceTime to _fteams_round[{$ftid}]: ".print_r($fteamr,true));
		}
	}
}

function fteamrelayBuildRoundPanel($refresh=false,$endround=false){
	global $_debug,$_players,$_fteams_max,$_fteams_rules,$_fteams,$_fteamrelay,$_fteams_round,$_teambgcolor,$_FGameModes,$_FGameMode,$_teambgcolor,$_players_positions;

	$scores = array();
	$scores2 = array();
	
	$col1 = $endround ? '' : '$aa8';
	$col2 = $endround ? '' : '$8a8';
	$firstCPs = null;
	$firstCPnum = 0;

	foreach($_fteams_round as $ftid => $ftr){
		if($_fteams[$ftid]['Active']){

			$time = end($_fteams[$ftid]['TRelay']['RaceCPs']);
			$cpnum = key($_fteams[$ftid]['TRelay']['RaceCPs']);

			if($endround || $_fteams[$ftid]['TRelay']['CurLap'] >= $_fteamrelay['TRelayNbLaps']){
				// finished
				$timestr = MwTimeToString($ftr['RoundTime']);
				$scores[] = array('fgmodes.'.$ftid,$_teambgcolor[$ftid],($cpnum+1).'cp',$timestr,$_fteams[$ftid]['NameDraw']);

			}else{
				$login = $_fteams[$ftid]['TRelay']['PlayerPlaying'];
				if($login !== ''){
					if(isset($_players[$login]['NickDraw']))
						$nick = '$z$s$ff8'.$_players[$login]['NickDraw'];
					else
						$nick = '$z$s$ff8'.$login;
				}else{
					$nick = '';
				}

				if($firstCPs === null || !isset($firstCPs[$cpnum])){
					$timestr = MwTimeToString2($time);
					//$timestr = '('.($cpnum+1).') '.MwTimeToString2($time);
				}else{
					$difftime = $time - $firstCPs[$cpnum];
					$diffcp = $firstCPnum - $cpnum - 1;
					if($diffcp > 0)
						$timestr = "+{$diffcp}cp (".MwDiffTimeToString($difftime).')';
					else
						$timestr = MwDiffTimeToString($difftime);
				}
				$scores[] = array('fgmodes.'.$ftid,$_teambgcolor[$ftid],$col1.($ftr['RoundScore'] + 1),$col2.$timestr,$_fteams[$ftid]['NameDraw'],$nick);
			}

			if($firstCPs === null){
				$firstCPs = $_fteams[$ftid]['TRelay']['RaceCPs'];
				$firstCPnum = $cpnum;
			}
		}
	}

	fgmodesSetRoundScore($scores,$scores2,2,2,$refresh);
}


function fteamrelayShowCheckpoints($ftid=-1){
	global $_debug,$_players,$_fteams_max,$_fteams_rules,$_fteams,$_fteamrelay,$_fteams_round,$_teambgcolor,$_FGameModes,$_FGameMode,$_teambgcolor,$_players_positions,$_fteamrelay_checks_bg_xml,$_fteamrelay_checks_xml;

	if($ftid < 0 || !isset($_fteams[$ftid]['Active']) || !isset($_fteams[$ftid]['TRelay']['PlayerPlaying'])){
		// send to all playing players in race
		foreach($_fteams_round as $ftid => $ftr){
			if($_fteams[$ftid]['Active'] && isset($_fteams[$ftid]['TRelay']['PlayerPlaying'])){
				if($_fteams[$ftid]['TRelay']['PlayerPlaying'] != '' && $ftr['RoundTime'] > 0)
					fteamrelayShowCheckpoints($ftid);
			}
		}
		return;
	}

	$plogin = $_fteams[$ftid]['TRelay']['PlayerPlaying'];
	if($plogin == '' || !isset($_players[$plogin]['Active']) || !$_players[$plogin]['Active'])
		return;
	$ptid = $ftid;
	$prank = $_fteams_round[$ptid]['RoundRank'];

	// show checkpoints to playing player of team: 1st, prank-2,prank-1,prank,prank+1,prank+2
	if(!manialinksIsOpened($plogin,'fteamrelay.checkpoints.bg'))
		manialinksShowForce($plogin,'fteamrelay.checkpoints.bg',$_fteamrelay_checks_bg_xml);

	$xml = '';
	$infos = array();
	for($n = 0; $n < 6; $n++){
		$infos[$n] = array('','','',''); // 'bgcolor="ff0"',pos value,nickname,gap, like '+2cp (+5.45)'
	}

	$minrank = ($prank > 4) ? $prank - 2 : 2;
	$firstCPs = null;
	$firstCPnum = 0;
	$n = 0;
	foreach($_fteams_round as $ftid => $ftr){
		if($_fteams[$ftid]['Active']){

			$time = end($_fteams[$ftid]['TRelay']['RaceCPs']);
			$cpnum = key($_fteams[$ftid]['TRelay']['RaceCPs']);

			if($ftr['RoundRank'] == 1 || ($ftr['RoundRank'] >= $minrank && $ftr['RoundRank'] <= $minrank + 4)){
				$infos[$n][0] = 'bgcolor="'.$_teambgcolor[$ftid].'"';
				$infos[$n][1] = ($ftr['RoundRank'] > 0 && $ftr['RoundRank'] < 1000) ? $ftr['RoundRank'] : '';
				$login = $_fteams[$ftid]['TRelay']['PlayerPlaying'];
				if($login !== ''){
					if(isset($_players[$login]['NickDraw4']))
						$infos[$n][2] = $_players[$login]['NickDraw4'];
					else
						$infos[$n][2] = $login;
				}
				
				$infos[$n][3] = ($ftid == $ptid) ? '$4f4' : '';
				if($firstCPs === null || !isset($firstCPs[$cpnum])){
					$infos[$n][3] .= MwTimeToString2($time);
				}else{
					$difftime = $time - $firstCPs[$cpnum];
					$diffcp = $firstCPnum - $cpnum - 1;
					if($diffcp > 0)
						$infos[$n][3] .= "+{$diffcp}cp (".MwDiffTimeToString($difftime).')';
					else
						$infos[$n][3] .= MwDiffTimeToString($difftime);
				}
				$n++;
			}

			if($firstCPs === null){
				$firstCPs = $_fteams[$ftid]['TRelay']['RaceCPs'];
				$firstCPnum = $cpnum;
			}
		}
	}
		
		$xml = sprintf($_fteamrelay_checks_xml,
									 $infos[0][0],$infos[0][1],$infos[0][2],$infos[0][3],
									 $infos[1][0],$infos[1][1],$infos[1][2],$infos[1][3],
									 $infos[2][0],$infos[2][1],$infos[2][2],$infos[2][3],
									 $infos[3][0],$infos[3][1],$infos[3][2],$infos[3][3],
									 $infos[4][0],$infos[4][1],$infos[4][2],$infos[4][3],
									 $infos[5][0],$infos[5][1],$infos[5][2],$infos[5][3]);	

	manialinksShowForce($plogin,'fteamrelay.checkpoints',$xml);

	//manialinksHide(true,'fteamrelay.checkpoints.bg');
	//manialinksHide(true,'fteamrelay.checkpoints');
}


function fteamrelayShowNextPlayers($ftid,$hide=false){
	global $_debug,$_StatusCode,$_fteams,$_players,$_fteamrelay,$_fteamrelay_nextplayer_xml,$_fteamrelay_nonextplayer_xml,$_teambgcolor;
	if($ftid === 'hide' || $_StatusCode > 4){
		//console("fteamrelayShowNextPlayers:: hide players to all...");
		manialinksHide(true,'fteamrelay.players');
		manialinksHide(true,'fteamrelay.next');
		return;
		
	}elseif($ftid >= 0 && isset($_fteams[$ftid]['TRelay']['Players'])){
		// sort team players to have next player in first pos
		uasort($_fteams[$ftid]['TRelay']['Players'],'fteamrelayTeamPlayersSort');

		// build list xml
		$xml = '';
		$xml3 = '';
		$n = 0;
		$yt = -1.0;

		// before first lap time
		$y = $yt - ($n++) * 2.0;
		$tname = (isset($_fteams[$ftid]['NameDraw']) && $_fteams[$ftid]['NameDraw'] != '') ? $_fteams[$ftid]['NameDraw'] : 'Team '.$ftid;
		$xml .= sprintf('<quad sizen="6 2" posn="1 %0.2F 0.1" bgcolor="%sb"/>'
										.'<label sizen="5.8 2" posn="4 %0.2F 0.2" halign="center" valign="center2" textsize="1" textcolor="eeef" text="$sT%d laps"/>'
										.'<label sizen="12 2" posn="7.6 %0.2F 0.2" valign="center2" textsize="1" textcolor="eeef" text="%s"/>',
										$y+1.2,$_teambgcolor[$ftid],   $y,$ftid,   $y,$tname);
		
		// list of team laps times
		$first = $n;
		foreach($_fteams[$ftid]['TRelay']['Lap'] as $curlap => $lap){
			$y = $yt - $n * 2;
			$login = $lap['Login'];
			$nick = isset($_players[$login]['NickDraw4']) ? $_players[$login]['NickDraw4'] : $login;
			if($curlap == $_fteams[$ftid]['TRelay']['CurLap'])
				$time = 'driving';
			else
				$time = MwTimeToString2($lap['Time']);
			$penalty = MwDiffTimeToString($lap['Penalty']+$lap['RSdelays']);
			$xml .= sprintf('<label sizen="1.8 2" posn="1.5 %0.2F 0.2" halign="center" valign="center2" textsize="1" textcolor="ff4f" text="$s%d"/>'
											.'<label sizen="8.8 2" posn="3.1 %0.2F 0.2" halign="left" valign="center2" textsize="1" textcolor="eeef" text="%s"/>'
											.'<label sizen="4.8 2" posn="14.5 %0.2F 0.2" halign="center" valign="center2" textsize="1" textcolor="cccf" text="%s"/>'
											.'<label sizen="2.8 2" posn="18.5 %0.2F 0.2" halign="center" valign="center2" textsize="1" textcolor="bbbf" text="%s"/>',
											$y,$curlap+1,     $y,$nick,    $y,$time,    $y,$penalty);
			$n++;
		}

		$n++;


		if($_fteams[$ftid]['TRelay']['CurLap'] < $_fteamrelay['TRelayNbLaps']){
			// before first next players entry
			$xml .= sprintf('<label sizen="18 2" posn="1.5 %0.2F 0.2" valign="center2" textsize="1" textcolor="eeef" text="$s$oNext players :"/>',
											($yt - ($n++) * 2.0));
		
			// list of next players
			$first = $n;
			foreach($_fteams[$ftid]['TRelay']['Players'] as $login => $player){
				$y = $yt - $n * 2;
				$nick = isset($_players[$login]['NickDraw4']) ? $_players[$login]['NickDraw4'] : $login;
				$plnblaps = $player['NbLaps'] + ($player['Playing'] > 0 ? 1 : 0);
				$penalty = MwDiffTimeToString($_fteamrelay['TRelayPenalty'][$plnblaps]).'s';

				if($n == $first){
					$xml3 = sprintf($_fteamrelay_nextplayer_xml,$nick,$penalty);
				}

				$nb = ($player['NbLaps'] > 0) ? ''.$player['NbLaps'] : '';
				if($player['Playing'] != 0)
					$nb .= '*';
				$penalty = '';
				if($player['Leaved'] > 0)
					$penalty = '$s$444Leaved';
				else if($player['Active'] > 0)
					$penalty = '$s$888Disco.';
				else if($player['AFK'] > 0)
					$penalty = '$s$ff2AFK';
				else if($_fteamrelay['TRelayPenalty'][$plnblaps] > 0)
					$penalty = '$ada'.$penalty;

				$xml .= sprintf('<label sizen="1.8 2" posn="1.5 %0.2F 0.2" halign="center" valign="center2" textsize="1" textcolor="ff4f" text="$s%d"/>'
												.'<label sizen="1.8 2" posn="3.5 %0.2F 0.2" halign="center" valign="center2" textsize="1" textcolor="bbbf" text="%s"/>'
												.'<label sizen="4.8 2" posn="7 %0.2F 0.2" halign="center" valign="center2" textsize="1" textcolor="4f4f" text="%s"/>'
												.'<label sizen="10 2" posn="9.7 %0.2F 0.2" halign="left" valign="center2" textsize="1" textcolor="eeef" text="%s"/>',
												$y,$n-$first+1,     $y,$nb,    $y,$penalty,    $y,$nick);
				$n++;
			}
		}
		
		if($xml != ''){
			$h = $n * 2.0 + 2.0;
			$y = -27.0 + $h;
			$xml2 = sprintf('<frame posn="-64.5 %0.2F -40.1">'
											.'<quad sizen="20.5 %0.2F" posn="0 1 0" style="Bgs1InRace" substyle="BgList"/>'
											.$xml.'</frame>',
											$y,$h);

			//console("fteamrelayShowNextPlayers::\n".$xml2);
			// show list to team players but playing one
			foreach($_fteams[$ftid]['TRelay']['Players'] as $login => $player){
				if($player['Active'] <= 0 && $player['Leaved'] <= 0){

					if($_fteams[$ftid]['TRelay']['CurLap'] >= $_fteamrelay['TRelayNbLaps']){
						// finished
						manialinksHide(''.$login,'fteamrelay.players');
						manialinksHide(''.$login,'fteamrelay.next');

					}else{
						if($player['Playing'] < 1){ // not playing
							//console("fteamrelayShowNextPlayers:: show players to {$login}...");
							manialinksShowForce(''.$login,'fteamrelay.players',$xml2);
						}else{ // playing
							//console("fteamrelayShowNextPlayers:: hide players to {$login}...");
							manialinksHide(''.$login,'fteamrelay.players');
						}

						if($_fteams[$ftid]['TRelay']['CurLap'] >= $_fteamrelay['TRelayNbLaps'] - 1){
							// last lap
							manialinksShowForce(''.$login,'fteamrelay.next',$_fteamrelay_nonextplayer_xml);
						}else{
							//console("fteamrelayShowNextPlayers:: $login  xml3={$xml3}");
							manialinksShowForce(''.$login,'fteamrelay.next',$xml3);
						}
					}
				}else{
					if($_debug>3) console("fteamrelayShowNextPlayers({$ftid}):: don't show to {$login} ({$player['Active']},{$player['Leaved']})");
				}
			}
		}
	}
}


function fteamrelayGetTeamPlayersActiveLogins($ftid){
	global $_fteams,$_players;
	$mlogin = '';
	$sep = '';
	foreach($_fteams[$ftid]['TRelay']['Players'] as $login => $player){
		if(isset($_players[$login]['Active']) && $_players[$login]['Active'] && $_players[$login]['FTeamId'] == $ftid){
			$mlogin .= $sep.$login;
			$sep = ',';
		}
	}
	return $mlogin;
}


function fteamrelayMatchLogEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_fteams,$_fteamrelay,$_players,$_FGameModes,$_FGameMode,$_PlayerList;
	
	$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
	$msg1 = $_FGameMode.' MATCH on ['.stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).') ['.$_fteamrelay['TRelayNbLaps'].'r]';

	
	$playlist = array();
	$msg2 = '';
	$i = 0;
	foreach($_fteams as $ftid => $fteam){
		if($fteam['CPs'] > 0 && $fteam['Time'] > 0){
			$msg2 .= "\n".$fteam['Rank'].','.$fteam['Score'].','.$fteam['CPs'].','.MwTimeToString($fteam['Time']).','.$ftid.','.stripColors($fteam['Name']);
			foreach($fteam['TRelay']['Lap'] as $num => $lap){
				$msg2 .= ','.$lap['Login'];
				$playlist[$lap['Login']] = true;
			}
		}
		$i++;
	}

	if($msg2 != ''){
		$sep = "\n* Spectators: ";
		for($i = 0; $i < sizeof($_PlayerList); $i++){
			$login = $_PlayerList[$i]['Login'];
			if(!isset($playlist[$login])){
				$msg2 .= $sep.stripColors($_PlayerList[$i]['Login']).'['.stripColors($_PlayerList[$i]['NickName']).']';
				$sep = ', ';
			}
		}
		matchlog($msg1.$msg2."\n\n");
	}
}


function fteamrelayMatchEndRace($Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup,$scoremax,$timemin){
	global $_debug,$_match_conf,$_players,$_fteams,$_match_map,$_match_map_rs,$_match_map_rswu,$_match_scores,$_GameInfos,$_WarmUp,$_FWarmUp,$_players_round_current,$_match_scoretable_xml,$_players_roundplayed_current,$_roundslimit_rule,$_EndMatchCondition,$_currentTime,$_players_round_time;

	$map_rs_next = false;

	if($_match_conf['EndMatch'] || $timemin > 0){
		// Laps, a player have finished
			
		$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
		$msg1 = "MULTIMAP TEAMRELAY MATCH [{$_match_map}/{$_match_conf['NumberOfMaps']}] on [".stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).')';
		$msg1 .= "\n# match rank, match score, besttime, map score, team name, team id ; login, score";
		foreach($_match_scores as &$pls){
			$tid = $pls['Login'];
			$extid = isset($_fteams[$tid]['ExtId']) && $_fteams[$tid]['ExtId'] >= 0 ? $_fteams[$tid]['ExtId'] : $tid;
			$msg1 .= "\n".$pls['Rank'].','.($pls['FullScore']+$pls['Bonus']+$pls['MapScore']).','.MwTimeToString($pls['BestTime']).','.$pls['MapScore'].','.stripColors(str_replace(array(',',';'),array('.','.'),$pls['NickName'])).','.$extid;
			$sep = ';';
			foreach($_players as $login => &$pl){
				if($pl['FTeamId'] == $tid && $pl['FTeamPoints'] > 0)
					$msg1 .= $sep.$login.','.$pl['FTeamPoints'];
			}
		}
		match_log($msg1."\n\n");
			
		// store in database (if available)
		matchDbStore($Ranking,$ChallengeInfo,$GameInfos);
			
		if($_match_map < $_match_conf['NumberOfMaps']){
			$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$o Challenge {$_match_map}/{$_match_conf['NumberOfMaps']} finished !";
			addCall(null,'ChatSendServerMessage', $msg);
		}

		// prepare next match map
		$_match_map++;
		$map_rs_next = true;

		foreach($_match_scores as &$pls){
			if($_match_conf['GlobalScore']){
				$pls['FullScore'] += $pls['MapScore'];
				$pls['FullTime'] += $pls['BestTime'];
				$pls['FullCPs'] += $pls['CPs'];
			}
			$pls['MapScore'] = 0;
			$pls['BestTime'] = -1;
			$pls['CPs'] = 0;
		}
		console("matchEndRace:(3): added full scores: ".print_r($_match_scores,true));
			
		// end of match
		if($_match_conf['EndMatch'] || $_match_map > $_match_conf['NumberOfMaps']){
			if($_debug>1) console("matchEndRace:: Laps, all maps played... match_map={$_match_map}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
			matchEnd();
		}
			
		// copy log
		match_log_copy();
	}
	return $map_rs_next;
}


//------------------------------------------
// trelay Commands
//------------------------------------------
function chat_trelay($author, $login, $params, $params2){
	global $_debug,$_fteams,$_players,$_fteamrelay;

	//if(!verifyAdmin($login)){ }
	$msg = localeText(null,'server_message') . localeText(null,'interact');

	$ftid = $_players[$login]['FTeamId'];
	$mlogin = $login;

	if(!isset($params[0]))
		$params[0] = 'help';
	if(!isset($params[1]))
		$params[1] = '';

	if($params[0] == 'afk'){
		// afk [login]
		$plogin = ($params[1] != '') ? $params[1] : $login;
		if($ftid >= 0 && $_fteams[$ftid]['Active']){
			if($_fteamrelay['State'] == 'Race'){
				if(isset($_fteams[$ftid]['TRelay']['Players'][$plogin]['AFK'])){
			
					if($_fteams[$ftid]['TRelay']['Players'][$plogin]['AFK'] > 0){
						$_fteams[$ftid]['TRelay']['Players'][$plogin]['AFK'] = 0;
						$msg .= "{$plogin} is back !";
					}else{
						$_fteams[$ftid]['TRelay']['Players'][$plogin]['AFK'] = 100;
						$msg .= "{$plogin} is AFK !";

						// if it's the playing player, then replace him
						if($plogin == $_fteams[$ftid]['TRelay']['PlayerPlaying']){
							if($_debug>0) console("chat_trelay:: SetNextPlayer replace ({$ftid})");
							fteamrelaySetNextPlayer($ftid,true);
						}
					}
					if($plogin != $login)
						$msg .= " (set by {$login})";

					// send msg to all players of team
					$mlogin = fteamrelayGetTeamPlayersActiveLogins($ftid);

					fteamrelayShowNextPlayers($ftid);
				}else{
					$msg .= "{$plogin} is not in your team !";
				}
			}else{
				$msg .= 'Can be used only in race !';
			}
		}else{
			$msg .= 'You have to be in a team !';
		}
		if($mlogin != '' && $msg != '')
			addCall(null,'ChatSendToLogin', $msg, $mlogin);

	}else if($params[0] == 'play'){
		if($params[1] != '' && $params[1]+0 > 0){
			$order = $params[1]+0;
			$msg .= "Old play order: {$_players[$login]['PlayOrder']}, new play order: {$order}. Standard value is 1000, set smaller to player before, set above to play after...";
			$_players[$login]['PlayOrder'] = $order;
		}else{
			$msg .= "Current play order: {$_players[$login]['PlayOrder']}. Standard value is 1000, set smaller to player before, set above to play after...";
		}
		if($mlogin != '' && $msg != '')
			addCall(null,'ChatSendToLogin', $msg, $mlogin);

	}else{
		// help
		$msg .= '/trelay afk [login], play <num>';
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}

function chat_teamrelay($author, $login, $params, $params2){
	chat_trelay($author, $login, $params, $params2);
}


?>
