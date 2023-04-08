<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST3.2 plugin to handle script specific FastGameMode : TeamRounds
// (needs the fgmodes and fteams plugins)
//
if(!$_is_relay) registerPlugin('fteamrounds',53,1.0);


// FastGameMode TeamRounds, Rounds team race
//
function fteamroundsInit($event){
	global $_debug,$_players,$_FGameModes,$_FGameMode,$_NextFGameMode;
	if($_debug>6) console("players.Event[$event]");

	// Add/Set GameInfos constants for Teamrounds FGameMode (see plugin.17.fgmodes.php) :
	$_FGameModes['TeamRounds']['Aliases'] = array('trounds','tro','tr'); // mode name is not case sensitive, but aliases are.
	$_FGameModes['TeamRounds']['Podium'] = true;
	$_FGameModes['TeamRounds']['ScoreMode'] = 'Score';
	$_FGameModes['TeamRounds']['RoundPanel'] = true;
	$_FGameModes['TeamRounds']['FTeams'] = true;
	$_FGameModes['TeamRounds']['PointsRule'] = 'incremental'; // round points for players
	$_FGameModes['TeamRounds']['RanksRule'] = 'Points';  // team round ranks based on players points sum
	$_FGameModes['TeamRounds']['ScoresRule'] = 24; // team scores are in range 24-0
	$_FGameModes['TeamRounds']['GameInfos'] = array('GameMode'=>ROUNDS);
	$_FGameModes['TeamRounds']['RoundCustomPointsRule'] = 'none';

	// max players by team who are counted
	if(!isset($_FGameModes['TeamRounds']['TeamMaxPlayers']))
		$_FGameModes['TeamRounds']['TeamMaxPlayers'] = 3;

	//$_FGameModes['TeamRounds']['Set'] = array();
	//$_FGameModes['TeamRounds']['Next'] = array();
	//$_FGameModes['TeamRounds']['Current'] = array();
	
	registerCommand('trounds','/trounds ',true);
	registerCommand('teamrounds','/trounds ',true);
}

function fteamroundsBuildRoundPanel($refresh=false,$endround=false){
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
			else
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

function fteamroundsBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
	global $_debug,$_FGameMode;

	if($_FGameMode != 'TeamRounds')
		return;
	if($_debug>6) console("fteamrounds.Event[$event]($newcup,$warmup,$fwarmup)");

	fteamsBuildScore();
}

function fteamroundsBeginRound($event){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamRounds')
		return;
	if($_debug>6) console("fteamrounds.Event[$event]");
}

function fteamroundsPlayerConnect($event,$login){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamRounds')
		return;
	if($_debug>6) console("fteamrounds.Event[$event]('$login')");
}

function fteamroundsPlayerDisconnect($event,$login){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamRounds')
		return;
	if($_debug>6) console("fteamrounds.Event[$event]('$login')");
}

function fteamroundsPlayerCheckpoint($event,$login,$time,$lapnum,$checkpt){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamRounds')
		return;
	if($_debug>6) console("fteamrounds.Event[$event]('$login',$time,$lapnum,$checkpt)");
}

function fteamroundsPlayerPositionChange($event,$login,$changes){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamRounds' || $login !== true)
		return;

	fteamroundsBuildRoundPanel(true);
}

function fteamroundsBeforeEndRound($event,$delay){
	global $_debug,$_FGameMode,$_players,$_fteams,$_fteams_round;
	if($_FGameMode != 'TeamRounds')
		return;

	if($delay < 0){ // (final call)
		debugPrint('fteamroundsBeforeEndRound:: _fteams_round[0]: ',$_fteams_round[0]);
		debugPrint('fteamroundsBeforeEndRound:: _fteams[0]: ',$_fteams[0]);

		// update players scores
		$scores = array();
		foreach($_players as $login => &$pl){
			if($pl['Active']){
				$ftid = $pl['FTeamId'];
				if($ftid >= 0 && isset($_fteams[$ftid]['Score']))
					$scores[] = array('PlayerId'=>$pl['PlayerId'],'Score'=>$_fteams[$ftid]['Score']);
			}
		}
		addCall(null,'ForceScores',$scores,true);

		//fteamroundsBuildRoundPanel(true,true);
	}
}

function fteamroundsEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamRounds')
		return;
	if($_debug>6) console("fteamrounds.Event[$event]");

	fteamroundsBuildRoundPanel(true,true);
}

function fteamroundsEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup){
	global $_debug,$_FGameMode;
	if($_FGameMode != 'TeamRounds')
		return;
	if($_debug>6) console("fteamrounds.Event[$event]($continuecup,$warmup,$fwarmup)");
}


//------------------------------------------
// trounds Commands
//------------------------------------------
function chat_trounds($author, $login, $params, $params2){
	global $_debug,$_fteams,$_fteams_maxid;

	goStunts();

	//if(!verifyAdmin($login)){ }
	$msg = localeText(null,'server_message') . localeText(null,'interact');

	if(!isset($params[0]))
		$params[0] = 'help';

	if($params[0] == 'join'){
	}
}

function chat_teamrounds($author, $login, $params, $params2){
	chat_trounds($author, $login, $params, $params2);
}

?>
