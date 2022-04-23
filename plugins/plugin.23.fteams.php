<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      03.05.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST3.2 plugin to handle script specific teams (FTeams), for special script modes
// (nothing to do with the builtin Team gamemode)
//
if(!$_is_relay) registerPlugin('fteams',23,1.0);


// Fast teams infos : scrit teams for use by special script modes
// see $_fteams_rules and $_fteams details in plugin.01.players.php

// Will be used automatically for plugins using $_FGameModes['fgamemodename']['FTeams'] = true
//
// If $_FGameModes['fgamemodename']['Podium'] === true then the fteams plugin show
//          values from $_fteams[tid]['Score'] and ['Rank'] which are handled by the players plugin
//	$_FGameModes['fgamemodename']['ScoreMode'] = 'Score'; or 'Time' or 'CPTime' to show wanted score
//
// Else, if $_FGameModes['fgamemodename']['Podium'] !== false then the plugin have
//    to call fgmodesSetScoretable directly with its own infos
//
// Only teams with $_fteams[tid]['Active'] == true are considered


//  $_FGameModes['fgamemodename']['ShowMinJoin'] = 2; // min 'to join' teams to show


// xml tags to $_FGameModes['fgamemodename'] entries for FTeams, see beginning comments in players plugin
global $_ConvFTeamInfos;
$_ConvFTeamInfos = array(
	'fteam_max_teams' => 'MaxTeams',
	'fteam_max_players' => 'MaxTPlayers',
	'fteam_max_playing' => 'MaxPlaying',
	'fteam_max_playing_rule' => 'MaxPlayingRule',
	'fteam_max_playing_keep' => 'MaxPlayingKeep',
	'fteam_finish_bonus_points' => 'FinishBonusPoints',
	'fteam_notfinish_multiplier' => 'NotFinishMultiplier',
	'fteam_points_rule' => 'PointsRule',
	'fteam_draw_rule' => 'DrawRule',
	'fteam_ranks_rule' => 'RanksRule',
	'fteam_scores_rule' => 'ScoresRule',
	'fteam_mapscores_rule' => 'MapScoresRule',
	'fteam_join_mode' => 'JoinMode',
	'fteam_lock_name' => 'LockName',
	'fteam_autoleave' => 'AutoLeave',
	'fteam_lock_mode' => 'LockMode',
	'fteam_join_spec_mode' => 'JoinSpecMode',
	'fteam_connect_spec_mode' => 'ConnectSpecMode',
	);


function fteamsBuildScore($refresh=false){
	global $_debug,$_players,$_fteams_max,$_fteams_rules,$_fteams,$_teambgcolor,$_FGameModes,$_FGameMode,$_match_map;
	$scores = array();
	$scores2 = array();
	//console("fteamsBuildScore::");
	
	$panelname = 'Scores';
	$panelinfo = '';

	if(isset($_FGameModes[$_FGameMode]['FTeams']) && $_FGameModes[$_FGameMode]['FTeams']){
		$panelname = 'Scores &amp; Manage teams';
		$panelinfo = '(F6 key or left-up button to open/close)';

		// sort teams based on score etc.
		fteamsSortRanks();

		$score = '';
		$status = '';
		
		foreach($_fteams as $tid => &$fteam){
			if($fteam['Active']){
				if($_FGameModes[$_FGameMode]['Podium'] === true){
					if($_FGameModes[$_FGameMode]['ScoreMode'] == 'Time'){ // show time
						$score = ($fteam['Time'] > 0) ? MwTimeToString($fteam['Time']) : '';
						$status = '';
					}elseif($_FGameModes[$_FGameMode]['ScoreMode'] == 'CPTime'){ // show time + cp
						$score = ($fteam['Time'] > 0) ? MwTimeToString($fteam['Time']) : '';
						if($fteam['CPs'] > 1)
							$status .= "{$fteam['CPs']} cps";
						else if($fteam['CPs'] > 0)
							$status .= "{$fteam['CPs']} cp";
					}elseif($_FGameModes[$_FGameMode]['ScoreMode'] == 'ScCPTime'){ // show time + laps/cp
						$score = ($fteam['Time'] > 0) ? MwTimeToString($fteam['Time']) : '';
						if($fteam['CPs'] > 0){
							if($fteam['CPs'] > 1)
								$status = "{$fteam['CPs']} cps";
							else
								$status = "{$fteam['CPs']} cp";
							if($fteam['Score'] > 1)
								$status .= " ({$fteam['Score']} laps)";
							else
								$status .= " ({$fteam['Score']} lap)";
						}
					}else{ // show score, +match score
						$score = $fteam['Score'];
						$status = '';
						if(isset($_match_map) && ($_match_map > 0 || $_match_map == -2))
							$status .= '[ '.($fteam['MatchScore']+$fteam['Score']).' ]';
					}
				}
				$players = '';
				$sep = '$z';
				// add active players
				foreach(array_keys($fteam['Players']) as $login){
					if(isset($_players[$login]['Active']) && $_players[$login]['Active']){
						$players .= $sep.$_players[$login]['NickDraw4'];
						if($_FGameModes[$_FGameMode]['ScoreMode'] == 'Score' && $_players[$login]['FTeamPoints'] > 0)
							$players .= '$z ('.$_players[$login]['FTeamPoints'].')';
						$sep = '$z, ';
					}
				}
				// add inactive players
				$sep .= '$888$n';
				foreach(array_keys($fteam['Players']) as $login){
					if(!isset($_players[$login]['Active']) || !$_players[$login]['Active']){
						$players .= $sep.$login;
						if($_FGameModes[$_FGameMode]['ScoreMode'] == 'Score' && isset($_players[$login]['FTeamPoints']) && $_players[$login]['FTeamPoints'] > 0)
							$players .= ' ('.$_players[$login]['FTeamPoints'].')';
						$sep = ', ';
					}
				}
				$scores[] = array($tid,$_teambgcolor[$tid],$fteam['NameDraw'],$score,$status,$players);
			}else{
				$scores2[] = array($tid,$_teambgcolor[$tid],'','','','click to join team');
			}
		}

		if($_fteams_rules['MaxTeams'] > $_fteams_max)
			$_fteams_rules['MaxTeams'] = $_fteams_max;
		
		$tojoin = 0;
		while($_fteams_rules['JoinMode'] != 'Script' && count($scores) < $_fteams_rules['MaxTeams'] && count($scores2) > 0 &&
					($tojoin < $_FGameModes[$_FGameMode]['ShowMinJoin'] || count($scores) < 2)){
			$scores[] = array_shift($scores2);
			$tojoin++;
		}

		if($_FGameModes[$_FGameMode]['Podium'] === false){
			$panelname = 'Manage teams';
		}
	}

	fgmodesSetScoretable( $scores, $panelname, $panelinfo, $refresh);
}



function fteamsChatTeam($login_or_teamid,$message){
	global $_debug,$_players,$_fteams,$_fteams_max;
	if($message != ''){
		if(isset($_players[$login_or_teamid]['FTeamId']))
			$ftid = $_players[$login_or_teamid]['FTeamId'];
		else
			$ftid = $login_or_teamid;
		if(is_int($ftid) && isset($_fteams[$ftid]['Active']) && $_fteams[$ftid]['Active']){
			$msg = localeText(null,'server_message') . localeText(null,'interact'). $message;
			foreach($_fteams[$ftid]['Players'] as $login => $order){
				if(isset($_players[$login]['Active']) && $_players[$login]['Active']){
					addCall(null,'ChatSendToLogin', $msg, $login);
				}
			}
		}
	}
}



//--------------------------------------------------------------
//--------------------------------------------------------------
function fteamsInit_Reverse($event){
	global $_mldebug,$_ml_act,$_fteams_hidepanel,$_FGameModes,$_FGameMode,$_fteams_rules;

	// complete FTeams FGameModes with default values (in _Reverse so they are already added)
	foreach($_FGameModes as $fgmode => $gm){
		if(isset($_FGameModes[$fgmode]['FTeams']) && $_FGameModes[$fgmode]['FTeams']){
			if(!isset($_FGameModes[$fgmode]['ShowMinJoin']))
				$_FGameModes[$fgmode]['ShowMinJoin'] = 2;
			
			if($_FGameModes[$fgmode]['Podium'] === true && !isset($_FGameModes[$fgmode]['ScoreMode']))
				$_FGameModes[$fgmode]['ScoreMode'] = 'Score';

			// copy all default $_fteams_rules values which are not set by sub-plugins
			foreach($_fteams_rules as $frule => $val){
				if(!isset($_FGameModes[$fgmode][$frule]))
					$_FGameModes[$fgmode][$frule] = $val;
			}
		}
	}
	//print_r($_FGameModes);

	$_fteams_hidepanel = true;

	manialinksAddAction('fteam.cross');
	manialinksAddAction('fteam.join');
	manialinksAddAction('fteam.leave');
	manialinksAddAction('fteam.lock');
	manialinksAddAction('fteam.bg');

	registerCommand('fteam','/fteam join team, list');
	registerCommand('ft','/ft join team, list');
}



function fteamsPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_mldebug,$_debug,$_players,$_ml_act,$_StatusCode,$_FGameModes,$_FGameMode,$_fteams_rules,$_fteams;
  if(!is_string($login))
    $login = ''.$login;
	//console("fgmodesPlayerManialinkPageAnswer:: {$login},{$answer},{$action}");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'] || !isset($_players[$login]['ML']))
		return;
	if($_FGameMode == '' || !isset($_FGameModes[$_FGameMode]['FTeams']) || !$_FGameModes[$_FGameMode]['FTeams'])
		return;
	//$pml = &$_players[$login]['ML'];

	if($action == 'ml_main.gamemode'){
		//manialinksHide($login,'fteams.panel');

	}elseif($action == 'ml_main.fteam'){
		fteamsUpdateTeampanelXml($login,'showrev',-1);

	}elseif($action == 'fteam.cross'){
		fteamsUpdateTeampanelXml($login,'hide');

	}elseif($action == 'fteam.bg'){
		if($_StatusCode < 5 && isset($_FGameModes[$_FGameMode]['Podium']) && $_FGameModes[$_FGameMode]['Podium'] === true)
			fgmodesUpdateScoretableXml($login,'reverse');

	}elseif($action == 'fgmodes.roundscore'){
		if($_StatusCode < 5 && isset($_FGameModes[$_FGameMode]['Podium']) && $_FGameModes[$_FGameMode]['Podium'] === true)
			fgmodesUpdateScoretableXml($login,'reverse');

	}elseif($action == 'fteam.join' && isset($_players[$login]['ML']['fteam.panel'])){
		$team = $_players[$login]['ML']['fteam.panel'];

		if($_fteams_rules['JoinMode'] != 'Script' && isset($_fteams[$team]['Lock']) && $_fteams[$team]['Lock'] === false && 
			 ($_fteams_rules['MaxTPlayers'] <= 0 || count($_fteams[$team]['Players']) < $_fteams_rules['MaxTPlayers'])){
			fteamsAddPlayer($team,$login,true);
			fteamsUpdateTeampanelXml($login,'refresh');
			fteamsBuildScore($login);
			fgmodesUpdateScoretableXml($login,'hide');
			fteamsChatTeam($login,'A player has joined your team : $z'.$_players[$login]['NickName']);
			$msg = localeText(null,'server_message') . localeText(null,'interact'). 'to change fteam name: /ft name teamname';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

	}elseif($action == 'fteam.leave' && isset($_players[$login]['ML']['fteam.panel'])){
		$team = $_players[$login]['ML']['fteam.panel'];
		if($_fteams_rules['JoinMode'] != 'Script' && isset($_fteams[$team]['Players'][$login])){
			fteamsRemPlayer($team,$login);
			fteamsUpdateTeampanelXml($login,'refresh');
			fteamsBuildScore($login);
			fgmodesUpdateScoretableXml($login,'show');
			fteamsChatTeam($login,'A player has leaved your team : $z'.$_players[$login]['NickName']);
		}

	}elseif($action == 'fteam.lock' && isset($_players[$login]['ML']['fteam.panel'])){
		$team = $_players[$login]['ML']['fteam.panel'];
		if($_fteams_rules['JoinMode'] != 'Script' && isset($_fteams[$team]['Players'][$login])){
			fteamsSetLock($team,!$_fteams[$team]['Lock']);
			fteamsUpdateTeampanelXml($login,'refresh');
		}

	}elseif($answer >= $_ml_act['fgmodes.0'] && $answer <= $_ml_act['fgmodes.16']){
		dropEvent();
		$team = $answer - $_ml_act['fgmodes.0'];
		console("fgmodesPlayerManialinkPageAnswer:: {$login},{$answer},{$action},{$team}");

		if($_fteams_rules['JoinMode'] != 'Script' && isset($_fteams[$team]['Lock']) && 
			 $_fteams[$team]['Lock'] === false && count($_fteams[$team]['Players']) <= 0){
			fteamsAddPlayer($team,$login,true);
			fteamsUpdateTeampanelXml($login,'show',$team);
			fteamsBuildScore($login);
			fgmodesUpdateScoretableXml($login,'hide');
			fteamsChatTeam($login,'A player has joined your team : $z'.$_players[$login]['NickName']);
			$msg = localeText(null,'server_message') . localeText(null,'interact'). 'to change fteam name: /ft name teamname';
			addCall(null,'ChatSendToLogin', $msg, ''.$login);

			if($_debug>0) console("fgmodesPlayerManialinkPageAnswer:: {$login},{$answer},{$action},{$team} -> join team");

		}else{
			fteamsUpdateTeampanelXml($login,'showrev',$team);
			if($_debug>0) console("fgmodesPlayerManialinkPageAnswer:: {$login},{$answer},{$action},{$team}");
		}
	}
}


function fteamsFTeamsChange_Reverse($event){
	global $_debug,$_players,$_fteams,$_fteams_max;
	//console("fteamsFTeamsChange::");
	// after other got event : reset changed flag in all teams
	for($teamid = 0; $teamid < $_fteams_max; $teamid++){
		if($_fteams[$teamid]['Changed']){
			// refresh teampanel for players showing it
			foreach($_players as $login => &$pl){
				if($pl['Active'] && isset($pl['ML']['fteam.panel']) && $pl['ML']['fteam.panel'] == $teamid){
					//console("fteamsFTeamsChange:: update Teampanel {$teamid} for {$login}");
					fteamsUpdateTeampanelXml($login,'refresh');
				}
			}
		}
	}
	// rebuild scorepanel
	fteamsBuildScore(true);
}


function fteamsBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
	global $_mldebug,$_players,$_fteams_hidepanel,$_fteams_button_xml,$_FGameModes,$_FGameMode,$_NextFGameMode,$_PrevFGameMode,$_fteams_on,$_fteams_rules;

	if($_FGameMode == '')
		$_fteams_on = false;

	if($_fteams_hidepanel && (!isset($_FGameModes[$_FGameMode]['FTeams']) || !$_FGameModes[$_FGameMode]['FTeams'])){
		// not anymore in fteams, hide panel if any
		$_fteams_hidepanel = false;
		fteamsUpdateTeampanelXml(true,'hide');
	}

	//console("fteamsBeginRace:: {$_PrevFGameMode},{$_FGameMode},{$_NextFGameMode}");
	if($_FGameMode == '' || !isset($_FGameModes[$_FGameMode]['FTeams']) || !$_FGameModes[$_FGameMode]['FTeams'])
		return;

	// if FTeams then update $_fteams_xx values
	if(isset($_FGameModes[$_FGameMode]['FTeams']) && $_FGameModes[$_FGameMode]['FTeams']){
		$_fteams_on = true;
		foreach($_fteams_rules as $key => $val){
			if(isset($_FGameModes[$_FGameMode][$key]))
				$_fteams_rules[$key] = $_FGameModes[$_FGameMode][$key];
		}
	}else{
		$_fteams_on = false;
	}

	// force spec players not in a fteam
	foreach($_players as $login => &$pl){
		if($pl['Active'] && $pl['FTeamId'] < 0){
			addCall(null,'ForceSpectator',''.$login,1);
		}
	}
}


function fteamsBeginRound($event){
	global $_mldebug,$_players,$_FGameModes,$_FGameMode,$_PrevFGameMode,$_fteams_on,$_fteams_rules;

	if($_FGameMode == '')
		$_fteams_on = false;

	if($_PrevFGameMode == '' || $_FGameMode == '' || !isset($_FGameModes[$_FGameMode]['FTeams']) || !$_FGameModes[$_FGameMode]['FTeams'])
		return;

	fteamsUpdateTeampanelXml(true,'hideifplayer');

	// if FTeams then update $_fteams_xx values
	if(isset($_FGameModes[$_FGameMode]['FTeams']) && $_FGameModes[$_FGameMode]['FTeams']){
		$_fteams_on = true;
		foreach($_fteams_rules as $key => $val){
			if(isset($_FGameModes[$_FGameMode][$key]))
				$_fteams_rules[$key] = $_FGameModes[$_FGameMode][$key];
		}
	}else{
		$_fteams_on = false;
	}

	// force spec players not in a fteam
	foreach($_players as $login => &$pl){
		if($pl['Active'] && $pl['FTeamId'] < 0){
			addCall(null,'ForceSpectator',''.$login,1);
		}
	}
}


function fteamsBeginRound_Post($event){
	global $_debug,$_fteams_on,$_fteams_rules,$_fteams_pointsrule,$_fteams_drawrule,$_fteams_ranksrule,$_fteams_scoresrule,$_fteams_mapscoresrule;
	// $_fteams_on : true/false to have teams points computed by this plugin
	// $_fteams_pointsrule : take the real value from $_fteams_rules['PointsRule'] at BeginRound
	// $_fteams_drawrule : take the real value from $_fteams_rules['DrawRule'] at BeginRound
	// $_fteams_ranksrule : take the real value from $_fteams_rules['RanksRule'] at BeginRound
	// $_fteams_scoresrule : take the real value from $_fteams_rules['ScoresRule'] at BeginRound
	// $_fteams_mapscoresrule : take the real value from $_fteams_rules['MapScoresRule'] at BeginRound
	if($_debug>3 && $_fteams_on){
		console("fteamsBeginRound_Post:: fteams_rules: ".print_r($_fteams_rules,true));
		console("fteamsBeginRound_Post:: fteams_pointsrule: ".print_r($_fteams_pointsrule,true));
		console("fteamsBeginRound_Post:: fteams_drawrule: ".print_r($_fteams_drawrule,true));
		console("fteamsBeginRound_Post:: fteams_ranksrule: ".print_r($_fteams_ranksrule,true));
		console("fteamsBeginRound_Post:: fteams_scoresrule: ".print_r($_fteams_scoresrule,true));
		console("fteamsBeginRound_Post:: fteams_mapscoresrule: ".print_r($_fteams_mapscoresrule,true));
	}
}


function fteamsPlayerConnect($event,$login,$pinfo,$pdetailedinfo,$pranking){
	global $_mldebug,$_starting,$_players,$_FGameModes,$_FGameMode,$_PrevFGameMode,$_fteams_rules;

	if($_FGameMode == '' || !isset($_FGameModes[$_FGameMode]['FTeams']) || !$_FGameModes[$_FGameMode]['FTeams'])
		return;

	if($_players[$login]['FTeamId'] < 0){
		addCall(null,'ForceSpectator',''.$login,1);
		fgmodesUpdateScoretableXml($login,'show');

	}elseif(!$_starting){
		// player already in a team connect : change spec state of player depending on $_fteams_rules['ConnectSpecMode']
		if($_fteams_rules['ConnectSpecMode'] == 'PlayFree' || $_fteams_rules['ConnectSpecMode'] == 'PlayForce')
			addCall(null,'ForceSpectator',''.$login,2);
		else if($_fteams_rules['ConnectSpecMode'] == 'SpecFree' || $_fteams_rules['ConnectSpecMode'] == 'SpecForce')
			addCall(null,'ForceSpectator',''.$login,1);
		
		if($_fteams_rules['ConnectSpecMode'] == 'PlayFree' || $_fteams_rules['ConnectSpecMode'] == 'SpecFree' || $_fteams_rules['ConnectSpecMode'] == 'Free')
			addCall(null,'ForceSpectator',''.$login,0);
	}
}


function fteamsPlayerCheckpoint($event,$login,$time,$lapnum,$checkpt){
	global $_mldebug,$_players,$_FGameModes,$_FGameMode,$_PrevFGameMode;

	if($_FGameMode == '' || !isset($_FGameModes[$_FGameMode]['FTeams']) || !$_FGameModes[$_FGameMode]['FTeams'])
		return;

	//if(manialinksIsOpened($login,'fteams.panel'))
	//manialinksHide($login,'fteams.panel');
}


function fteamsUpdateTeampanelXml($login,$action,$team=-1){
	global $_mldebug,$_players,$_ml_act,$_fteams,$_teambgcolor,$_StatusCode,$_fteams_hidepanel,$_fteams_rules;
	// to all users
	if($login === true){
		foreach($_players as $login => &$pl){
			if($pl['Active'])
				fteamsUpdateTeampanelXml(''.$login,$action);
		}
		return;
	}
	// nothing is player is not active
	if(!isset($_players[$login]['Active']) || !$_players[$login]['Active'])
		return;
	if(!isset($_players[$login]['ML']['fteam.panel']))
		$_players[$login]['ML']['fteam.panel'] = -1;

	if($team < 0)
		$team = $_players[$login]['FTeamId'];

	if($action == 'reverse' && manialinksIsOpened($login,'fteams.panel')){
		// reverse to hide
		$_players[$login]['ML']['fteam.panel'] = -1;
		manialinksHide($login,'fteams.panel');
		return;
	}elseif($action == 'showrev' && manialinksIsOpened($login,'fteams.panel')){
		// reverse to hide if same team
		if($team < 0 || (isset($_players[$login]['ML']['fteam.panel']) && $_players[$login]['ML']['fteam.panel'] == $team)){
			manialinksHide($login,'fteams.panel');
			return;
		}
	}elseif($action == 'remove'){
		// remove manialink
		$_players[$login]['ML']['fteam.panel'] = -1;
		manialinksRemove($login,'fteams.panel');
		return;
	}elseif($action == 'hideifplayer'){
		if(manialinksIsOpened($login,'fteams.panel') && !$_players[$login]['IsSpectator']){
			$_players[$login]['ML']['fteam.panel'] = -1;
			manialinksHide($login,'fteams.panel');
		}
		return;
	}elseif($action == 'hide'){
		// hide manialink
		$_players[$login]['ML']['fteam.panel'] = -1;
		manialinksHide($login,'fteams.panel');
		return;
	}elseif($action == 'refresh'){
		// refresh
		if(!manialinksIsOpened($login,'fteams.panel'))
			return;
		if($_players[$login]['ML']['fteam.panel'] < 0){
			manialinksHide($login,'fteams.panel');
			return;
		}
		$team = $_players[$login]['ML']['fteam.panel'];
	}
	if($team < 0 || $team > 15){
		// bad team number
		return;
	}
	
	// show team panel ($team)
	$_players[$login]['ML']['fteam.panel'] = $team;
	$tn = 'Team '.$team;
	$tname = (isset($_fteams[$team]['NameDraw']) && $_fteams[$team]['NameDraw'] != '') ? $_fteams[$team]['NameDraw'] : $tn;


	// team players
	$pnb = count($_fteams[$team]['Players']);
	//$pnb *= 10;
	$height = ($pnb < 2) ? 6 : (($pnb <= 8) ? $pnb * 3 : 8 * 3); // after 8 it is scaled to same as 8
	$pscale = ($pnb <= 8) ? 1.0 : 8 / $pnb;
	$xml2 = sprintf('<frame posn="0 -5 0.2" scale="%0.2F">',$pscale);
	$y = 0;
	foreach(array_keys($_fteams[$team]['Players']) as $tlogin){
		$nick = (isset($_players[$tlogin]['Active']) && $_players[$tlogin]['Active']) ? $_players[$tlogin]['NickDraw4'] : '$888'.$tlogin;
		$xml2 .= sprintf('<label sizen="22 3" posn="-11 %0.2F 0.2" valign="center2" textsize="2" text=" %s " action="0"/>',
										$y,$nick);
		$y -= 3;
	}
	$xml2 .= '</frame>';
	
	$h = 12 + $height;
	$y = -47 + $h * 0.75;
	
	$xml = sprintf('<frame posn="31.8 %0.2F 20" scale="0.75">'
								 .'<quad sizen="26 %0.2F" posn="0 1 0" halign="center" style="Bgs1InRace" substyle="BgList"/>'
								 .'<quad sizen="26 %0.2F" posn="0 1 0" halign="center" style="Bgs1InRace" substyle="BgList"/>'
								 .'<quad sizen="24 3" posn="0 0 0.1" halign="center" style="Bgs1InRace" substyle="BgTitle3" action="%d"/>'
								 .'<quad sizen="2 2" posn="-11.3 -0.5 0.3" style="Icons64x64_1" substyle="Close" action="%d"/>'
								 .'<label sizen="13.4 3" posn="-9 -1.7 0.2" valign="center2" textsize="2" textcolor="ffff" text="%s"/>'
								 .'<quad sizen="6.2 2.55" posn="8.2 -0.27 0.3" halign="center" bgcolor="%sb"/>'
								 .'<label sizen="6 3" posn="8.2 -1.7 0.3" halign="center" valign="center2" textsize="2" textcolor="ffff" text=" %s "/>',
								 $y,$h,$h,$_ml_act['fteam.bg'],$_ml_act['fteam.cross'],$tname,$_teambgcolor[$team],$tn);

	//print_r($_fteams_rules);
	//print_r($_fteams[$team]);
	$jtext = '-';
	$jaction = '';
	$jcolor = '888a';
	$lockable = false;
	if($_fteams_rules['JoinMode'] != 'Script'){
		if(isset($_fteams[$team]['Players'][$login])){
			// leave team button
			$jtext = 'Leave team';
			$jaction = 'action="'.$_ml_act['fteam.leave'].'"';
			$jcolor = '000f';
			$lockable = true;
		}elseif(fteamsGetPlayerTeamId($login) < 0){
			// join team button
			$jtext = 'Join team';
			if($_fteams[$team]['Lock']){
				$jtext = 'Locked';
			}elseif($_fteams_rules['MaxTPlayers'] >= 0 && count($_fteams[$team]['Players']) >= $_fteams_rules['MaxTPlayers']){
				$jtext = 'Full';
			}else{			
				$jaction = 'action="'.$_ml_act['fteam.join'].'"';
				$jcolor = '000f';
			}
		}
	}else{
		$jtext = 'Locked';
	}
	// join/leave button
	$xml .= sprintf('<quad sizen="22 3.2" posn="0 %0.2F 0.1" halign="center" style="Bgs1InRace" substyle="BgButtonSmall" %s/>'
									.'<label sizen="20 3" posn="0 %0.2F 0.2" halign="center" textsize="2" textcolor="%s" text="%s"/>',
									8.4 - $h,$jaction,    8 - $h,$jcolor,$jtext);

	// lock button
	if($lockable && ($_fteams[$team]['Lock'] || count($_fteams[$team]['Players']) > 1)){
		if($_fteams[$team]['Lock']){
			$ltext = 'Unlock';
		}else{
			$ltext = 'Lock';
		}
		$xml .= sprintf('<quad sizen="22 3.2" posn="0 %0.2F 0.1" halign="center" style="Bgs1InRace" substyle="BgButtonSmall" action="%d"/>'
										.'<label sizen="20 3" posn="0 %0.2F 0.2" halign="center" textsize="2" textcolor="000f" text="%s"/>',
										5.4 - $h,$_ml_act['fteam.lock'],   5 - $h,$ltext);
	}else{
		// team name change info
		$xml .= sprintf('<label sizen="22 3" posn="0 %0.2F 0.2" halign="center" textsize="2" text="/ft name &lt;teamname&gt;"/>',
										4.8 - $h);
	}


	$xml .= $xml2.'</frame>';

	//console("fgmodesUpdateScoretableXml - {$login} - {$_fgmodes_scoretable_xml}");
	$_fteams_hidepanel = true;
	manialinksShowForce($login,'fteams.panel',$xml);
}








//------------------------------------------
// fteam Commands
//------------------------------------------
function chat_fteam($author, $login, $params, $params2){
	global $_debug,$_players,$_fteams,$_fteams_max,$_fteams_rules;

	//if(!verifyAdmin($login)){ }
	$msg = localeText(null,'server_message') . localeText(null,'interact');

	if(!isset($params[0]))
		$params[0] = 'help';
	
	$teamid = fteamsGetPlayerTeamId($login);

	if($params[0] == 'join'){
		if(isset($params[1])){
			// join team
			$team = is_numeric($params[1]) ? $params[1]+0 : $params[1];
			$team = fteamsGetTeamId($team);

			if($team < 0){
				$msg .= 'invalid team';

			}elseif($_fteams_rules['JoinMode'] != 'Script' && isset($_fteams[$team]['Lock']) && 
				 $_fteams[$team]['Lock'] === false){
				fteamsAddPlayer($team,$login,true);
				fteamsUpdateTeampanelXml($login,'show',$team);
				fteamsBuildScore($login);
				fgmodesUpdateScoretableXml($login,'hide');
				fteamsChatTeam($login,'A player has joined your team : $z'.$_players[$login]['NickName']);
				$msg .= 'to change fteam name: /ft name teamname';
				if($_debug>0) console("chat_fteam:: {$login},{$team} -> join team");

			}else{
				$msg .= 'that team is locked.';
			}
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			// join help
			$msg .= "/ft join teamid|teamname";
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

	}elseif($params[0] == 'leave'){
		$team = isset($_players[$login]['FTeamId']) ? $_players[$login]['FTeamId'] : -1;
		if($team >= 0 && $team < $_fteams_max){
			if($_fteams_rules['JoinMode'] != 'Script'){
				if(fteamsRemPlayer($team,$login)){
					fteamsUpdateTeampanelXml($login,'refresh');
					fteamsBuildScore($login);
					fgmodesUpdateScoretableXml($login,'show');
					fteamsChatTeam($login,'A player has leaved your team : $z'.$_players[$login]['NickName']);
					$msg .= "You leaved team : \$z{$_fteams[$teamid]['Name']}";

				}else{
					$msg .= "Failed to leave the team : \$z{$_fteams[$team]['Name']}";
				}
			}else{
				$msg .= "Team is locked by script, leaving is not allowed !";
			}
		}else{
			$msg .= 'You are not in a team.';
		}
		addCall(null,'ChatSendToLogin', $msg, $login);

	}elseif($params[0] == 'list'){
		$msg2 = '';
		for($teamid = 0; $teamid < $_fteams_max; $teamid++){
			if($_fteams[$teamid]['Active']){
				$msg2 .= "\n[{$teamid}] \$z{$_fteams[$teamid]['Name']}: ";
				$sep = '';
				foreach($_fteams[$teamid]['Players'] as $ftlogin => $order){
					$msg2 .= $sep.$ftlogin;
					$sep = ',';
				}
			}
		}
		if($msg2 == '')
			$msg .= 'No teams.';
		else
			$msg .= 'Teams: '.$msg2;
		addCall(null,'ChatSendToLogin', $msg, $login);
		
	}elseif($params[0] == 'name'){
		$msg2 = '';
		if($teamid >= 0 && $teamid < $_fteams_max){
			if(isset($params[1]) && $params[1] != ''){
				// set team name if not script locked
				if(!$_fteams_rules['LockName']){
					fteamsSetName($teamid,$params[1]);
					fteamsUpdateTeampanelXml($login,'refresh');
					fteamsBuildScore($login);
					$msg .= "Team name changed to : \$z{$_fteams[$teamid]['Name']}";
				}else{
					$msg .= "Team is locked by script, changing name is not allowed !";
				}
			}else{
				$msg .= "Team name is : \$z{$_fteams[$teamid]['Name']}";
			}
		}else
			$msg .= 'You are not in a team. usage: /ft name teamname';
		addCall(null,'ChatSendToLogin', $msg, $login);
		
	}else{
		// help
		$msg .= '/ft join team, leave, name, list';
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}

function chat_ft($author, $login, $params, $params2){
	chat_fteam($author, $login, $params, $params2);
}



?>
