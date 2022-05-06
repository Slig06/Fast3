<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      13.06.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST3.2 plugin to handle script specific FastGameMode : TeamLaps
// (needs the fteams and fgmodes plugins)
//
if(!$_is_relay) registerPlugin('fteamlaps',52,1.0);

// 
// FastGameMode TeamLaps, Laps team race
//_NextFGameMode=fteamsBuildScore()
function fteamlapsInit($event){
	global $_debug,$_players,$_FGameModes,$_FGameMode,$_NextFGameMode;
	if($_debug>6) console("players.Event[$event]");

	// Add/Set GameInfos constants for Teamlaps FGameMode(see plugin.17.fgmodes.php) :
	$_FGameModes['TeamLaps']['Aliases'] = array('tlaps','tla','tl'); // mode name is not case sensitive, but aliases are.
	$_FGameModes['TeamLaps']['Podium'] = true;
	$_FGameModes['TeamLaps']['ScoreMode'] = 'Score';
	$_FGameModes['TeamLaps']['RoundPanel'] = true;
	$_FGameModes['TeamLaps']['FTeams'] = true;
	$_FGameModes['TeamLaps']['PointsRule'] = 'incremental'; // round points for players
	$_FGameModes['TeamLaps']['RanksRule'] = 'Points';  // team round ranks based on players points sum
	$_FGameModes['TeamLaps']['ScoresRule'] = 'Points'; // team score is team points
	$_FGameModes['TeamLaps']['GameInfos'] = array('GameMode'=>3);

	$_FGameModes['TeamLaps']['MatchEndRace'] = 'fteamlapsMatchEndRace';

	// max players by team who are counted
	if(!isset($_FGameModes['TeamLaps']['TeamMaxPlayers']))
		$_FGameModes['TeamLaps']['TeamMaxPlayers'] = 3;

	//$_FGameModes['TeamLaps']['Set'] = array();
	//$_FGameModes['TeamLaps']['Next'] = array();
	//$_FGameModes['TeamLaps']['Current'] = array();

	registerCommand('tlaps','/tlaps ',true);
	registerCommand('teamlaps','/tlaps ',true);
}


function fteamlapsBuildRoundPanel($refresh=false,$endround=false){
	global $_debug,$_players,$_fteams_max,$_fteams_rules,$_fteams,$_fteams_round,$_teambgcolor,$_FGameModes,$_FGameMode,$_teambgcolor,$_players_positions;

	$scores = array();
	$scores2 = array();
	
	$col1 = $endround ? '' : '$aa8';
	$col2 = $endround ? '' : '$8a8';

	foreach($_fteams_round as $ftid => $ftr){
		if($_fteams[$ftid]['Active']){
			$scores[] = array('fgmodes.'.$ftid,$_teambgcolor[$ftid],$col1.$ftr['RoundPoints'],$col2.'+'.$ftr['RoundScore'],$_fteams[$ftid]['NameDraw']);
		}
	}

	if(count($_players_positions) > 0){
		foreach($_players_positions as $i => $plp){
			$ftid = $plp['FTeamId'];
			$login = $plp['Login'];
			if($plp['FinalTime'] == 0){
				$strtime = 'out';
			}else{
				if($i <= 0){
					// first
					if($plp['FinalTime'] > 0)
						$strtime = MwTimeToString($plp['FinalTime']);
					else
						$strtime = MwTimeToString($plp['Time']);
				}else{
					// other
					$position = &$_players[$login]['Position'];
					if($position['FirstDiffCheck'] < 0)
						$strtime = '+'.(-$position['FirstDiffCheck']).'cp ('.MwDiffTimeToString(-$position['FirstDiffTime']).')';
					else
						$strtime = MwDiffTimeToString(-$position['FirstDiffTime']);
				}
			}
			
			if($plp['FinalTime'] >= 0)
				$scores2[] = array('fgmodes.'.$ftid,$_teambgcolor[$ftid],$plp['FTeamPoints'],$strtime,$_players[$login]['NickDraw4']);
			elseif($plp['FTeamPoints'] > 0)
				$scores2[] = array('fgmodes.'.$ftid,$_teambgcolor[$ftid],$col1.$plp['FTeamPoints'],$col2.$strtime,'$bbb'.$plp['NickDraw2']);
		}
	}
	/*
	$scores = array(array('fgmodes.0','fff',218,'+10','TX1'),
									array('fgmodes.1','f00',118,'+5','Tra$ff0Xico'),
									array('fgmodes.2','0f0',56,'+2','LSD1'),
									);

	$scores2 = array(array('fgmodes.0','fff',24,'03:12.34','TX-Slig'),
									 array('fgmodes.1','f0f','0','','$iGogogo for life'),
									 array('fgmodes.2','00f',54,'+1','$oLSD1'),
									 array('fgmodes.2','ff0',52,'+0','$wLSD1'),
									 array('fgmodes.1','0ff',20,'','$sLSD1'),
									 array('fgmodes.0','fff','0','','$iGogogo for life'),
									 array('fgmodes.1','000','0','','$iGogogo for life'),
									 array('fgmodes.2','f00','+0','+0.23','LSD-Arkone'),
									 array('fgmodes.2','ff0','+2','+4.80','Mike'),
									 array('fgmodes.1','0ff','+2','+5.12','Schum'),
									 array('fgmodes.0x','0f0','','+1.10','bubule'),
									 array('fgmodes.1x','00f','+1','+2.50','alonso'),
									 array('fgmodes.1x','f0f','+5','+14.23','Delnuelo'),
									 );
	*/
	fgmodesSetRoundScore($scores,$scores2,0,1,$refresh);
}

function fteamlapsBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
	global $_debug,$_FGameMode;

	if($_FGameMode != 'TeamLaps')
		return;
	if($_debug>6) console("fteamlaps.Event[$event]($newcup,$warmup,$fwarmup)");

	fteamsBuildScore();
}

function fteamlapsBeginRound($event){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamLaps')
		return;
	if($_debug>6) console("fteamlaps.Event[$event]");
}

function fteamlapsPlayerConnect($event,$login){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamLaps')
		return;
	if($_debug>6) console("fteamlaps.Event[$event]('$login')");
}

function fteamlapsPlayerDisconnect($event,$login){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamLaps')
		return;
	if($_debug>6) console("fteamlaps.Event[$event]('$login')");
}

function fteamlapsPlayerCheckpoint($event,$login,$time,$lapnum,$checkpt){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamLaps')
		return;
	if($_debug>6) console("fteamlaps.Event[$event]('$login',$time,$lapnum,$checkpt)");
}

function fteamlapsPlayerPositionChange($event,$login,$changes){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamLaps' || $login !== true)
		return;

	fteamlapsBuildRoundPanel(true);
}

function fteamlapsBeforeEndRound($event,$delay){
	global $_debug,$_FGameMode,$_players,$_fteams,$_fteams_round;
	if($_FGameMode != 'TeamLaps')
		return;

	if($delay < 0){ // (final call)

		//fteamlapsBuildRoundPanel(true,true);
	}
}

function fteamlapsEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamLaps')
		return;
	if($_debug>6) console("fteamlaps.Event[$event]");

	fteamlapsBuildRoundPanel(true,true);
}

function fteamlapsEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamLaps')
		return;
	if($_debug>6) console("fteamlaps.Event[$event]($continuecup,$warmup,$fwarmup)");
}


//------------------------------------------
// tlaps Commands
//------------------------------------------
function chat_tlaps($author, $login, $params, $params2){
	global $_debug,$_fteams,$_fteams_maxid;

	//goNotStunts();

	//if(!verifyAdmin($login)){ }
	$msg = localeText(null,'server_message') . localeText(null,'interact');

	if(!isset($params[0]))
		$params[0] = 'help';

	if($params[0] == 'join'){
	}
}

function chat_teamlaps($author, $login, $params, $params2){
	chat_tlaps($author, $login, $params, $params2);
}




// called by matchEndRace() when a map is really ended
function fteamlapsMatchEndRace($Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup,$scoremax,$timemin){
	global $_debug,$_match_conf,$_players,$_fteams,$_match_map,$_match_map_rs,$_match_map_rswu,$_match_scores,$_GameInfos,$_WarmUp,$_FWarmUp,$_players_round_current,$_match_scoretable_xml,$_players_roundplayed_current,$_roundslimit_rule,$_EndMatchCondition,$_currentTime,$_players_round_time;

	$map_rs_next = false;

	if($_match_conf['EndMatch'] || $timemin > 0){
		// Laps, a player have finished
			
		$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
		$msg1 = "MULTIMAP TEAMLAPS MATCH [{$_match_map}/{$_match_conf['NumberOfMaps']}] on [".stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).')';
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


?>
