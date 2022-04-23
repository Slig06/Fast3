<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      04.09.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// Number of players to fall in degraded mode
// (if not set then will get a value based on server configured rates)
//$_DegradedModePlayers = 40;
// 
// Set milliseconds without known network activity before kick player
//$_NetStats_KickTime_Playing = 900000;
//$_NetStats_KickTime_Synchro =  80000;
//
// Set preferred spectator status (for the first time the player become spec)
//$_preferredspec_default = 1; // -1=don't change, 0=replay, 1=follow, 2=free
//
// Used to add the laps a player had before a disconnection in Laps mode
// the effect is only visible in live infos and logs, not in game score panel
//$_LapsDiscoFix = true; // default false
//
// Cheat tests
//$_CheatTests = true; // default true

registerPlugin('players',1,1.0,null);


// State info tables :
// $_players : main players table
//  note: disconnected players have $_players['Active'] === false (old disconnected players are in: $_players_old)
// $_players_positions : player positions table in current round
// $_players_rounds_scores : previous rounds scores
// $_teams : scores infos for teams.

// State infos :
// $_players_round_restarting : the round is currently in special restart (falsestart/error)
// $_players_round_restarting_wu : state of warmup before the special restart
// $_players_prev_map_uid : uid of previous map (not changed by special restart)

// $_players_round_time : msec at start of round (make diff with $_currentTime to know round duration)
// $_players_round_current : num of the current round (1 for 1st, 0 before 1st BeginRound)
// $_players_roundplayed_current : num of really played current round
// $_players_round_finished : if >0 then current round was really finished (count all finishes)
// $_players_actives : number of players (including spectators)
// $_players_spec : number of spectators (pure spectators, not tmp specs who gave up)
// $_players_playing : number of players actually playing
// $_players_finished : number of player who have finished the current round (ie who have finished at least one time)
// $_players_giveup : number of players who 'del' (after at least 1 checkpoint)
// $_players_giveup2 : number of players who 'del'
// $_players_round_checkpoints
// $_players_firstmap : true only during 1st map when the script is started.
// $_players_firstround : true only during 1st map and round when the script is started.
// $_players_missings : number of missing PlayerFinish callbacks (should never happen, btw it occasionally happens...)
// $_players_round_checkpoints : number of checkpoints events (of all players) in round.

// $_NumberOfChecks : number of checkpoints by lap.
// $_LastCheckNum : last race checkpoint index.

// $_always_use_FWarmUp : if true the try to always use FWarmUp instead of classic WarmUp
// $_FWarmUpDuration : FWarmUp duration (game config for next)
// $_FWarmUp : FWarmUp current (duration)
// $_NextFWarmUp : FWarmUp set at EndRace for next race (duration)
// $_FWarmUpState : FWarmUp current state (0 to duration)



//
// $_players_rules : working rules for players points/scores for round/map
// $_players_rules['PointsRule'] : round points win by player in Rounds/Laps modes (in $_fteams_round)
//                                (those who drove no cp or above MaxPlaying get 0)
//           if 'incremental', then the last get 1, last-1 get 2, last-2 get 3, etc.
//           if array(....) val, then 1st get the 1st value, 2nd the 2nd, etc.
//           if string val, then use the array from $_roundspoints_points[string]
//           if 'custom', then will do nothing, the sub plugin has to fill values in $_fteams_round
// $_players_rules['NotFinishMultiplier'] : float, -1 to disable, else this bonus muliplied to points of players who don't finish round
// $_players_rules['FinishBonusPoints'] : int, -1 to disable, else this bonus is added to points of players who finish round
//                                and not finishing ones get points too if they have passed at least 2 cps
//                                0 can be used to count not finishing players without giving a bonus



// Fast teams infos, local teams for use by special script modes (nothing to do with the game Team mode)
//     fgmodes and fteams plugins are needed for correct handling !
//     note: fteamsBeginRace and fteamsBeginRound set $_fteams_on=true if $_FGameModes[$_FGameMode]['FTeams'] ,
//           then copy from $_FGameModes[$_FGameMode] to $_fteams_rules all values which belong to it.
//           That means that $_FGameModes[$_FGameMode] can set any $_fteams_rules value.
//
// $_fteams_max : max accepted number of teams (ie 0 <= tid < $_fteams_max)
// $_fteams_on : true/false to have teams points computed by this plugin
// $_fteams_pointsrule : take the real value from $_fteams_rules['PointsRule'] at BeginRound
// $_fteams_drawrule : take the real value from $_fteams_rules['DrawRule'] at BeginRound
// $_fteams_ranksrule : take the real value from $_fteams_rules['RanksRule'] at BeginRound
// $_fteams_scoresrule : take the real value from $_fteams_rules['ScoresRule'] at BeginRound
// $_fteams_mapscoresrule : take the real value from $_fteams_rules['MapScoresRule'] at BeginRound

// $_fteams_rules : working rules for teams
// $_fteams_rules['MaxTeams'] : current max teams, -1 if not limited
// $_fteams_rules['MaxTPlayers'] : max players in team, -1 if not limited
// $_fteams_rules['MaxPlaying'] : max playing players in team, -1 if not limited
// $_fteams_rules['MaxPlayingRule'] : rule for handling MaxPlaying (others are put spec), default to roundcp
//           'free' none is put spec automatically, but only first MaxPlaying of each team will count
//           'strict' to refuse to add to play (ie not spec) if max is reached, 
//           'map' to reject players other than the first MaxPlaying players at beginning of first map round
//           'round' to reject players other than the first MaxPlaying players at beginning of round
//           'mapcp' to reject players other than the first MaxPlaying players of a team who drove the first cp of first map round
//           'roundcp' to reject players other than the first MaxPlaying players of a team who drove the first cp of each round
// $_fteams_rules['MaxPlayingKeep'] : true/false, does a player who keep or go spec free a playing slot so he can be replaced
//           after the beginning of map. Usefull only for strict, map and mapcp. If true then the not playing slots will be
//           freed at EndRace and BeginRace
// $_fteams_rules['NotFinishMultiplier'] : float, -1 to disable, else this bonus muliplied to points of players who don't finish round
// $_fteams_rules['FinishBonusPoints'] : int, -1 to disable, else this bonus is added to points of players who finish round
//                                and not finishing ones get points too if they have passed at least 2 cps
//                                0 can be used to count not finishing players without giving a bonus
// $_fteams_rules['PointsRule'] : round points win by player in Rounds/Laps modes (in $_fteams_round)
//                                (those who drove no cp or above MaxPlaying get 0)
//           if 'incremental', then the last get 1, last-1 get 2, last-2 get 3, etc.
//           if array(....) val, then 1st get the 1st value, 2nd the 2nd, etc.
//           if string val, then use the array from $_roundspoints_points[string]
//             if $_roundspoints_points[string] does not exist then try to convert to array with explode(',',...)
//           if 'custom', then will do nothing, the sub plugin has to fill values in $_fteams_round
//
// $_fteams_rules['DrawRule'] : if there is a draw in a round (in $_fteams_round, and not using RanksRule=CPTime),
//           'Average','Bestplayer','PreviousRank','Lowest','Highest'   (in $_fteams_round)
//           if draw (teams sum points as same), make average score, or rank them based on bestplayers,
//           or base on previous rank, of all get highest rank, or all get lowest rank
//
// $_fteams_rules['RanksRule'] : 'Points' for round rank based on team sum points,
//                               'CPTime' for round rank based on team sum cp+time
//                                (default 'Points',   both for $_fteams_round and $_fteams map/match sort)
//
// $_fteams_rules['ScoresRule'] : round scores win by team in Rounds/Laps modes. (in $_fteams_round)
//                                draw teams score is the average of concerned teams pos scores, round up
//                                (ie if 1st and 2nd are draw, their score is average scores of pos 1 and 2)
//           if 'CPTime', then do not compute scores (supposing that CPs/Times are used)
//           if 'Points', then the round scores is the round team points (ie sum of players points)
//           if int val > 0, then sort by team points, then first team get val, last team get 0, intermediate get proportional in val-0 range
//           if int val < 0 (usually -1), then sort by team points, then last team get -val, last-1 get -val*2, last-2 get -val*3, etc.
//           if array(...) val, then sort by team points, then first get 1st value, 2nd the 2nd, etc.
//           if string val, then sort by team points, then use the array from $_roundspoints_points[string]
//             if $_roundspoints_points[string] does not exist then try to convert to array with explode(',',...)
//
// $_fteams_rules['MapScoresRule'] : 'Sum' to add round scores/times/cp, 'Copy' to copy round scores/times/cp
//
// $_fteams_rules['JoinMode'] : join strategy
//           'Free' : free join up to MaxTPlayers
//           'Script' to forbid players to change (also prevent free team creation)
// $_fteams_rules['LockName'] : lock strategy for name, true/false by script
// $_fteams_rules['AutoLeave'] : autoleave, 'BeginRace' (default), 'PlayerDisconnect', 'ForceOnly'
// $_fteams_rules['LockMode'] : lock strategy
//           'Players' to permit team players to lock the team access and accept new players
//           'Script' to forbid players to change (also prevent free team creation)
// $_fteams_rules['JoinSpecMode'] : spec/play strategy when joinning a fteam. default 'Free'
//           'Free','PlayFree','PlayForce','SpecFree','SpecForce','Unchanged'
//           (usually a sub-plugin making its own stuff would set to Free or Unchanged)
//           note: players who are not in a team are always SpecForce by the fteams plugin
// $_fteams_rules['ConnectSpecMode'] : spec/play strategy when connecting but was previously in a fteam. default 'Free'
//           'Free','PlayFree','PlayForce','SpecFree','SpecForce','Unchanged'
//           (usually Free or Unchanged, and sub-plugin will make its stuff about it)
//           note: players who are not in a team are always SpecForce by the fteams plugin
//
// $_fteams : info about local teams (the order can change because of sorts)
//            note: real current match score is Score+MatchScore, same for CPs and Time
//            the array is regulary sorted by rank
// $_fteams[tid]['Tid'] : same as tid (never modify it !)
// $_fteams[tid]['ExtId'] : external id for plugins
// $_fteams[tid]['Name'] : name of team (for chat)  (set it using tm_substr($name,0,13)
// $_fteams[tid]['NameDraw'] : name of team (for manialinks)  (set it using htmlspecialchars($_fteams[tid]['Name'],ENT_QUOTES,'UTF-8')
// $_fteams[tid]['Players'] : list of players logins in team (login=>order)
// $_fteams[tid]['AllPlayers'] : list of players logins who have finished in race (login=>nb_of_finishes)
// $_fteams[tid]['Active'] : true/false
// $_fteams[tid]['Changed'] : true/false, indicate that someting has changed in players list, name, etc. (ie need redraw)
// $_fteams[tid]['Lock'] : true/false
// $_fteams[tid]['Rank'] : current rank
// $_fteams[tid]['Score'] : cumulated team score on map/cup
// $_fteams[tid]['CPs'] : cumulated checkpoints on map/cup
// $_fteams[tid]['Time'] : cumulated team times on map/cup
// $_fteams[tid]['MatchScore'] : cumulated team score on match (used by match plugin)
// $_fteams[tid]['MatchCPs'] : cumulated checkpoints on match (used by match plugin)
// $_fteams[tid]['MatchTime'] : cumulated team times on match (used by match plugin)
// $_fteams[tid]['MatchPrevScore'] : optional initial score of previous match part
//
// $_fteams_round : fteams current round scores (regulary sorted)
//                  (all have temporary estimation value until after BeforeEndRound)
// $_fteams_round[tid]['Tid'] : same as tid (never modify it !)
// $_fteams_round[tid]['RoundRank'] : current round team rank (default 1000 if no team player running or finished)
// $_fteams_round[tid]['RoundScore'] : current round team score ()
// $_fteams_round[tid]['RoundPoints'] : current round team points 
//                            (sum of team players points, which depend of $_fteams_rules['PointsRule'])
// $_fteams_round[tid]['RoundCPs'] : current round team checkpoints (sum of team players cps)
// $_fteams_round[tid]['RoundTime'] : current round team times (sum of team players times)
// $_fteams_round[tid]['NbPlaying'] : num of players in team (counting within $_fteams_rules['MaxPlaying'] value)
//                            (can be use to average RoundTime and RoundCPs)
// $_fteams_round[tid]['CP0Players'] : list of players of team who passed cp 0 this round
// $_fteams_round[tid]['TmpNbPlayers'] : internal use (temporary num of players in team)
// $_fteams_round[tid]['RoundSortRank'] : internal use (round team rank used to sort with previous value if draw)
// $_fteams_round[tid]['BestPlayerPos'] : internal use (round team best player position)
//
// $_teamcolor[tid] : string color for each tid ( '$fff' )
// $_teambgcolor[tid] : bgcolor for each tid ( 'fff' )


// make a special round restart/quick map restart if better) without incrementing round number,
// and dropping all Everysecond/Every5seconds/PlayerCheckpoint/PlayerFinish/EndRace/BeginRace/BeginChallenge
// events, until new BeginRound
function playersSpecialRoundRestart($text){
	global $_debug,$_GameInfos,$_WarmUp,$_players_round_current,$_players_round_restarting,$_players_round_restarting_wu,$_players_round_checkpoints,$_StatusCode;
	if($_StatusCode <= 3){
		// wait if in synchro
		if($_debug>0) console("Delay playersSpecialRoundRestart while StatusCode<=3 ...");
		addEventDelay(300,'Function','playersSpecialRoundRestart',$text);
		return;
	}
	if($_debug>0) console("playersSpecialRoundRestart:: restarting round ({$text})");

	$_players_round_restarting = true;
	$_players_round_restarting_wu = $_WarmUp;
	if($_GameInfos['GameMode'] != ROUNDS && $_GameInfos['GameMode'] != TEAM && $_GameInfos['GameMode'] != CUP){
		// if not round based mode
		console("playersSpecialRoundRestart:: quickRestart");
		mapQuickRestart(null,$_WarmUp);

	}elseif($_WarmUp && $_players_round_current <= 1 && $_StatusCode == 4){
		// first warmup round : use warmup to restart map
		console("playersSpecialRoundRestart:: quick restart");
		mapQuickRestart(null,$_WarmUp);

	}else{
		console("playersSpecialRoundRestart:: using endround");
		$action2 = $_WarmUp ? array('Calls'=>array(3000,array(null,array('SetWarmUp',true)))) : null;
		addCall($action2,'ForceEndRound');
	}
	// set warmup state, to avoid having plugins consider a end of race despite _players_round_restarting
	setWarmUpState(true,true,'SpecialRoundRestart');
}


	// 
function playersPlayerSetup($login){
	global $_debug,$_currentTime,$_preferredspec_default;
	return array('Login'=>$login,
							 'NickName'=>'',
							 'PlayerId'=>-1,
							 'TeamId'=>-1,
							 'SpectatorStatus'=>-1,
							 'LadderRanking'=>-1,
							 'Flags'=>-1,
							 'Relayed'=>true,
							 'PlayRights'=>false,
							 'IsSpectator'=>false,
							 'IsTemporarySpectator'=>false,
							 'IsPureSpectator'=>false,
							 'EarlySpecToPlay'=>false,
							 'IsAutoTarget'=>false,
							 'CurrentTargetId'=>-1,
							 'ForceSpectator'=>-1,
							 'IsReferee'=>false,
							 'IsPodiumReady'=>false,
							 'IsUsingStereoscopy'=>false,
							 'IsInOfficialMode'=>false,
							 'Rank'=>-1,
							 'RBestTime'=>-1,
							 'BestTime'=>-1,
							 'Score'=>0,
							 'TeamScore'=>0, // player points in current round (classic Team)
							 'FTeamPoints'=>0, // player points in current round (FTeam)
							 'FRank'=>-1, // real rank computed by playersComputePositions()
							 'NbrLapsFinished'=>-1,
							 'LadderScore'=>-1,
							 'ForcedOld'=>false,
							 'Forced'=>false,
							 'ForcedByHimself'=>false,
							 'NickDraw'=>'',  // htmlspecialchars(tm_substr(stripColors($val),0,13),ENT_QUOTES,'UTF-8')
							 'NickDraw2'=>'', // htmlspecialchars(tm_substr(stripColors($val),0,24),ENT_QUOTES,'UTF-8');
							 'NickDraw3'=>'', // htmlspecialchars(tm_substr(stripEnlarge($val),0,24),ENT_QUOTES,'UTF-8');
							 'NickDraw4'=>'', // htmlspecialchars(tm_substr($val,0,24),ENT_QUOTES,'UTF-8');
							 'Nation'=>'',
							 'Path'=>'',
							 'Country'=>'',
							 'Language'=>'',
							 'TeamName'=>'',
							 'PlayOrder'=>1000,
							 'OldSpectatorStatus'=>2551000,
							 'PreferredSpec'=>$_preferredspec_default,
							 'Active'=>false,
							 'ChatFloodRate'=>0,
							 'ChatFloodRateIgnore'=>false,
							 'ExtId'=>-1, // external id for plugins
							 'FTeamId'=>-1, // FTeam id
							 'Quiet'=>false, // use to indicate that now method on player should be sent any more
							 'Cheating'=>false,
							 'Cheats'=>0,
							 'CheckCPs'=>false, // in Connect() it will get the value !$_players_firstround
							 'Status'=>1, // game hud: 0=playing, 1=spec, 2=race finished
							 'Status2'=>1, // logical: 0=playing, 1=spec, 2=race finished
							 'ConnectionTime'=>$_currentTime,
							 'PlayTime'=>0,
							 'PlayerActionTime'=>$_currentTime,
							 'ActiveTime'=>$_currentTime,
							 'NoticeTime'=>0,
							 'LapNumber'=>-1,
							 'PrevLapNumber'=>0, // previous full laps made in the current round (before disconnection-reconnection)
							 'CheckpointNumber'=>-1,
							 'FinalTime'=>-1,
							 'LastCpTime'=>-1,
							 'BestDate'=>-1,
							 'BestLapTime'=>-1,
							 'CPseq'=>-1, // use to keep order info about received checkpoints events
							 'CPdelay'=>false,             // min delay between script time since round beginning and last cp time
							 'RSdelays'=>0, // cumulated wrong delays (respawn+del without PlayerFinish event) since last PlayerFinish or BeginRound
							 'FinishEventTime'=>$_currentTime, // fast current time at last playerfinish or spec -> play change
							 'LatestNetworkActivity'=>-1, // big if deconnected (ms since not reachable)
							 'Maniagroups'=>array(),
							 'PlayerInfo'=>array(),
							 'TeamScores'=>array(0=>0),
							 'FTeamPointsList'=>array(0=>0),
							 'Position'=>array(),
							 'PlayerInfo'=>array(),
							 'Checkpoints'=>array(),
							 'BestCheckpoints'=>array(),
							 'Laps'=>array(),
							 'LapCheckpoints'=>array(),
							 'BestLapCheckpoints'=>array(),
							 'RoundsPos'=>array(),
							 'ChecksGaps'=>'best',
							 );
}



function playersInit($event){
	global $_debug,$_players,$_memdebug,$_memdebugs,$_memdebugmode,$_players_old,$_players_checkchange,$_players_positions,$_players_actives,$_players_spec,$_players_giveup,$_players_giveup2,$_players_playing,$_used_languages,$_players_round_current,$_players_firstmap,$_players_firstround,$_NetStats_KickTime_Playing,$_NetStats_KickTime_Synchro,$_players_round_time,$_DegradedMode,$_LastCheckNum,$_teams,$_players_missings,$_mem,$_preferredspec_default,$_players_maxlist,$_players_round_finished,$_players_roundplayed_current,$_players_round_restarting,$_players_round_restarting_wu,$_players_round_restartplayers,$_players_antilock,$_players_prev_map_uid,$_players_round_checkpoints,$_players_rounds_scores,$_always_use_FWarmUp,$_ChatFloodRateMax,$_fteams_rules,$_fteams,$_fteams_round,$_fteams_max,$_fteams_scoretable,$_fteams_changes,$_fteams_ranksrule,$_fteams_drawrule,$_fteams_pointsrule,$_fteams_scoresrule,$_fteams_mapscoresrule,$_fteams_on;
	if($_debug>6) console("players{$event}::");

  if($_memdebug>0) $_mem = memory_get_usage($_memdebugmode); else $_mem = 0;

	$_players = array();
	$_players_old = array();
	$_players_positions = array();
	$_players_round_current = 0;
	$_players_roundplayed_current = 0;
	$_players_round_finished = 0;
	$_players_round_playerfinished = 0;
	$_players_checkchange = false;
	$_players_actives = 0;
	$_players_spec = 0;
	$_players_giveup = 0;
	$_players_giveup2 = 0;
	$_players_finished = 0;
	$_players_playing = 0;
	$_players_firstmap = true;
	$_players_firstround = true;
	$_players_round_time = 0;
	$_used_languages = array();
	$_LastCheckNum = -1;
	$_players_missings = 0;
	$_players_maxlist = array();
	$_players_round_restarting = false;
	$_players_round_restarting_wu = false;
	$_players_round_restartplayers = array();
	$_players_antilock = 0;
	$_players_prev_map_uid = '';
	$_players_round_checkpoints = 0;
	$_players_rounds_scores = array();

	if(!isset($_ChatFloodRateMax))
		$_ChatFloodRateMax = 7; // max flood rate within 5 seconds (max burst is double)
	if($_ChatFloodRateMax < 3)
		$_ChatFloodRateMax = 3; // 3 / 5s is the min

	if(!isset($_always_use_FWarmUp))
		$_always_use_FWarmUp = false;

	if(!isset($_preferredspec_default))
		$_preferredspec_default = 1;

	if(!isset($_NetStats_KickTime_Playing))
		$_NetStats_KickTime_Playing = 900000;
	if(!isset($_NetStats_KickTime_Synchro))
		$_NetStats_KickTime_Synchro =  80000;

	if(!isset($_LapsDiscoFix))
		$_LapsDiscoFix = false;

	if(!isset($_CheatTests))
		$_CheatTests = true;

	$_DegradedMode = 0;


	$_fteams_on = false;
	$_fteams_changes = false;
	$_fteams_max = 24;

	$_fteams_scoretable = array();

	for($teamid = 0; $teamid < $_fteams_max; $teamid++){
		$_fteams[$teamid] = array('Tid'=>$teamid,
															'ExtId'=>-1,
															'Name'=>"Team {$teamid}",
															'NameDraw'=>"Team {$teamid}",
															'Players'=>array(),
															'AllPlayers'=>array(),
															'Active'=>false,
															'Changed'=>false,
															'Lock'=>false,
															'Rank'=>-1,
															'Score'=>0,
															'CPs'=>0,
															'Time'=>0,
															'MatchPrevScore'=>0,
															'MatchScore'=>0,
															'MatchCPs'=>0,
															'MatchTime'=>0);
		
		$_fteams_round[$teamid] = array('Tid'=>$teamid,
																		'RoundRank'=>1000,
																		'RoundScore'=>0,
																		'RoundPoints'=>0,
																		'RoundCPs'=>0,
																		'RoundTime'=>0,
																		'CP0Players'=>array(),
																		'TmpNbPlayers'=>0,
																		'RoundSortRank'=>1000,
																		'BestPlayerPos'=>1000);
		
	}

	if(!isset($_fteams_rules['MaxTeams']))
		$_fteams_rules['MaxTeams'] = $_fteams_max;

	if(!isset($_fteams_rules['MaxTPlayers']))
		$_fteams_rules['MaxTPlayers'] = -1;

	if(!isset($_fteams_rules['MaxPlaying']))
		$_fteams_rules['MaxPlaying'] = 3;

	if(!isset($_fteams_rules['MaxPlayingRule']))
		$_fteams_rules['MaxPlayingRule'] = 'mapcp';

	if(!isset($_fteams_rules['MaxPlayingKeep']))
		$_fteams_rules['MaxPlayingKeep'] = false;

	if(!isset($_fteams_rules['LockName']))
		$_fteams_rules['LockMode'] = false;

	if(!isset($_fteams_rules['LockMode']))
		$_fteams_rules['LockMode'] = 'players';

	if(!isset($_fteams_rules['JoinMode']))
		$_fteams_rules['JoinMode'] = 'players';

	if(!isset($_fteams_rules['JoinSpecMode']))
		$_fteams_rules['JoinSpecMode'] = 'Free';

	if(!isset($_fteams_rules['ConnectSpecMode']))
		$_fteams_rules['ConnectSpecMode'] = 'Free';

	if(!isset($_fteams_rules['NotFinishMultiplier']))
		$_fteams_rules['NotFinishMultiplier'] = -1;

	if(!isset($_fteams_rules['FinishBonusPoints']))
		$_fteams_rules['FinishBonusPoints'] = -1;

	if(!isset($_fteams_rules['PointsRule']))
		$_fteams_rules['PointsRule'] = 'incremental';

	if(!isset($_fteams_rules['RanksRule']))
		$_fteams_rules['RanksRule'] = 'Points';

	if(!isset($_fteams_rules['DrawRule']))
		$_fteams_rules['DrawRule'] = 'Average';

	if(!isset($_fteams_rules['ScoresRule']))
		$_fteams_rules['ScoresRule'] = 24;

	if(!isset($_fteams_rules['MapScoresRule']))
		$_fteams_rules['MapScoresRule'] = 'Sum';

	if(!isset($_fteams_rules['AutoLeave']))
		$_fteams_rules['AutoLeave'] = 'BeginRace';

	$_fteams_pointsrule = $_fteams_rules['PointsRule'];
	$_fteams_ranksrule = $_fteams_rules['RanksRule'];
	$_fteams_drawrule = $_fteams_rules['DrawRule'];
	$_fteams_scoresrule = $_fteams_rules['ScoresRule'];
	$_fteams_mapscoresrule = $_fteams_rules['MapScoresRule'];


	$_teams[0]['Num'] = 0;
	$_teams[1]['Num'] = 0;
	$_teams[0]['Score'] = 0;
	$_teams[1]['Score'] = 0;
}


function playersServerStart(){
	global $_debug,$_DegradedMode,$_DegradedModePlayers,$_DegradedMode2Players,$_OrigVehicleNetQuality,$_ServerInfos;

	// degraded mode setup
	if(!isset($_DegradedModePlayers) || $_DegradedModePlayers < 0){
		// set rate value from server upload/download rates
		$rate = 8000000;
		if($_ServerInfos['DownloadRate'] < $rate)
			$rate = $_ServerInfos['DownloadRate'];
		if($_ServerInfos['UploadRate'] < $rate)
			$rate = $_ServerInfos['UploadRate'];
		if($rate < 4000)
			$rate = 4000;
		
		$_DegradedModePlayers = (int)floor(sqrt($rate*0.05)/10)+2;
	}
	$_DegradedMode2Players = (int)floor(1.8 * $_DegradedModePlayers);
	if($_OrigVehicleNetQuality > 0){
		if($_debug>0) console("Reduce degraded mode level 1 limit because VehicleNetQuality is true !");
		$_DegradedModePlayers = (int)floor($_DegradedModePlayers / 1.6);
	}
	if($_debug>0) console("Degraded mode limit: $_DegradedModePlayers / $_DegradedMode2Players\n*** This limit is set from configured server rates,\n*** but you can force its value setting, \$_DegradedModePlayers, in fast.php.");
	$_DegradedMode = 0;

	// change to FWarmUp if wanted
	playersWarmUp2FWarmUp();
}


function playersServerStart_Reverse(){
	// restore Fast states if was Fast recently on the server
	playersRestoreFastState();
}


function playersReconnectTM(){
	global $_debug,$_players,$_players_old,$_players_positions;

	// TM reconnect : remove all players from active list
	foreach($_players as $login => &$pl){
		if(!is_string($login))
			$login = ''.$login;
		$pl['BestCheckpoints'] = array();
		$pl['BestTime'] = -1;
		$pl['BestLapTime'] = -1;
		$pl['Active'] = false;
		$pl['CheckpointNumber'] = -1;
		$pl['NbrLapsFinished'] = -1;
		$pl['LapNumber'] = -1;
		$pl['LapCheckpoints'] = array();
		$pl['BestLapCheckpoints'] = array();
		// move to $_players_old
		if($_debug>1) console("******* MOVE $login to _players_old !!! (reconnectTM)");
		$_players_old[$login] = $_players[$login];
		unset($_players[$login]);
		addEvent('PlayerRemove',$login,false);
	}
	$_players_positions = array();
}


function playersIsPlayerTmpSpec($pid){
	global $_debug,$_players;
	foreach($_players as &$pl){
		if($pl['PlayerId'] == $pid){
			if($pl['IsTemporarySpectator'])
				return true;
			break;
		}
	}
	return false;
}


function playersIsPlayerPlaying($pid){
	global $_debug,$_players;
	foreach($_players as &$pl){
		if($pl['PlayerId'] == $pid){
			if($pl['SpectatorStatus'] == 0)
				return true;
			break;
		}
	}
	return false;
}


function playersPlayerUpdate($event,$login,$player){
	global $_debug,$_players,$_players_old,$_GameInfos,$_NumberOfChecks,$_locale_default,$_locale,$_currentTime,$_StatusCode,$_DegradedMode,$_StatusCode,$_players_checkchange,$_players_round_current;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['Active']) || !$_players[$login]['Active']){
		dropEvent();
		return;
	}
	$pl = &$_players[$login];

	// if not active then store infos until connect
	if(!$pl['Active']){
		dropEvent();
		return;
	}

	if($_debug>2){
		$msg = '';
		$sep = '';
		foreach($player as $key => $val){
			$msg .= $sep.$key."=".$val;
			$sep = ',';
		}
		console("players{$event}('$login',$msg)::");
	}

	// update player infos
	$get_player_info = false;
	foreach($player as $key => $val){
		if($key == 'BestTime') // use 'BestTime' for PlayerFinish values, keep Rankings values in RBestTime instead.
			$key = 'RBestTime';

		if(!isset($pl[$key]) || ($pl[$key] !== $val)){

			// manage info change
			if($key == 'SpectatorStatus'){
				// SpectatorStatus = Spectator + TemporarySpectator * 10 + PureSpectator * 100 + AutoTarget * 1000 + + CurrentTargetId * 10000
				if($pl['SpectatorStatus'] != $val){
					// player Status2
					$oldstatus2 = $pl['Status2'];
					if($val > 0 && $pl['Status2'] == 0){
						if($_DegradedMode < 2 || (floor($val/100) % 10) > 0){
							$pl['Status2'] = 1;
							insertEvent('PlayerStatus2Change',$login,$pl['Status2'],$oldstatus2); // addEvent('PlayerStatus2Change',login,status2,oldstatus2)
						}
					}elseif($val == 0 && $pl['Status2'] == 1 && $pl['IsSpectator']){
						$pl['Status2'] = 0;
						insertEvent('PlayerStatus2Change',$login,$pl['Status2'],$oldstatus2); // addEvent('PlayerStatus2Change',login,status2,oldstatus2)
					}

					// player Status
					$oldstatus = $pl['Status'];
					if($val > 0 && $pl['Status'] == 0){
						$pl['Status'] = ($_StatusCode == 5) ? 2 : 1;
						insertEvent('PlayerStatusChange',$login,$pl['Status'],$oldstatus); // addEvent('PlayerStatusChange',login,status,oldstatus)
					}elseif($val == 0 && $pl['Status'] == 1){
						$pl['Status'] = 0;
						insertEvent('PlayerStatusChange',$login,$pl['Status'],$oldstatus); // addEvent('PlayerStatusChange',login,status,oldstatus)
					}

					// new spec values
					$old_isspec = $pl['IsSpectator'];
					$IsSpectator = ($val % 10) > 0;
					$IsTemporarySpectator = (floor($val/10) % 10) > 0;
					$IsPureSpectator = (floor($val/100) % 10) > 0;
					$IsAutoTarget = (floor($val/1000) % 10) > 0;
					$CurrentTargetId = (int)floor($val/10000);

					//if($_StatusCode < 4 && $pl['IsPureSpectator'] && !$IsPureSpectator && $IsTemporarySpectator){
					if($_players_round_current <= 0 && $_StatusCode < 4 && $pl['IsSpectator'] && !$IsSpectator && $IsTemporarySpectator){
						$pl['EarlySpecToPlay'] = true;
					}
					
					// change spec state if needed
					if($_StatusCode == 4 && $val != 0){
						$target = false;
						$store = false;

						$IsTarget = $CurrentTargetId != 255 && !$IsAutoTarget;
						$IsFree = $CurrentTargetId == 255 && !$IsAutoTarget;

						// first time seen as spec : force camera
						// -1=don't change,0=replay, 1=follow, 2=free
						if($pl['PreferredSpec'] >= 0){
							$targetid = ($IsAutoTarget || $CurrentTargetId == 255) ? -1 : $CurrentTargetId;
							addCall(true,'ForceSpectatorTargetId',$pl['PlayerId'],$targetid,$pl['PreferredSpec']);
							$pl['PreferredSpec'] = -1;
						}
						//if($_debug>0) console("Store spec state for $login : $val");
						$pl['OldSpectatorStatus'] = $val;
					}

					if($_StatusCode == 4 && $_GameInfos['GameMode'] == TA && !$pl['IsSpectator'] && $IsSpectator){ // && $pl['CheckpointNumber'] >= 0){
						// there is no immediate PlayerFinish,0 in TA when the player is put spec, so create it
						if($_debug>2) console("playersPlayerUpdate:: {$login} player -> spectator : send PlayerSpecFinish to compensate missing PlayerFinish,0");
						insertEvent('PlayerSpecFinish',$login);
					}

					//if($_StatusCode == 4 && $_GameInfos['GameMode'] == TA && $pl['IsSpectator'] && !$IsSpectator){
					//if($_debug>0) console("playersPlayerUpdate:: {$login} specator -> player : create missing PlayerFinish,0");
					//insertEvent('PlayerFinish',$login,0,null);
					//}

					// store spec values
					$pl['IsSpectator'] = $IsSpectator;
					$pl['PlayerInfo']['IsSpectator'] = $IsSpectator;
					$pl['IsTemporarySpectator'] = $IsTemporarySpectator;
					$pl['IsPureSpectator'] = $IsPureSpectator;
					$pl['IsAutoTarget'] = $IsAutoTarget;
					$pl['CurrentTargetId'] = $CurrentTargetId;

					insertEvent('PlayerSpecChange',$login,$pl['IsSpectator'],$val,$pl['SpectatorStatus']); // insertEvent('PlayerSpecChange',login,isspec,specstatus,oldspecstatus)
					
					// if was spec then consider it like a run give up (but not if FinalTime > 0 which can mean a reconnect after finishing)
					if($old_isspec == true && $pl['IsSpectator'] == false && $pl['FinalTime'] <= 0){
						$pl['FinalTime'] = 0;
						$pl['PlayRights'] = true;
					}
				}

			}elseif($key == 'Flags'){
				// Flags = ForceSpectator(0,1,2) + IsReferee * 10 + IsPodiumReady * 100 + IsUsingStereoscopy * 1000
				if($pl['Flags'] != $val){
					$pl['ForceSpectator'] = $val % 10;
					$pl['IsReferee'] = (floor($val/10) % 10) > 0;
					$pl['IsPodiumReady'] = (floor($val/100) % 10) > 0;
					$pl['IsUsingStereoscopy'] = (floor($val/1000) % 10) > 0;

					$relayed = (floor($pl['Flags']/10000) % 10) > 0;
					if($relayed != $pl['Relayed']){
						// special case (player moved from master to relay)
						$pl['Relayed'] = $relayed;
						playerDisconnect($login,'RelayedChanged');
						playerConnect($login,'RelayedChanged');
						return;
					}
					insertEvent('PlayerFlagsChange',$login,$val); // insertEvent('PlayerFlagsChange',login,flags)
				}

			}elseif($key == 'TeamId'){
				if($pl['TeamId'] != $val){
					$pl['PlayerInfo']['TeamId'] = $val;
					$_players_checkchange = true;
					insertEvent('PlayerTeamChange',$login,$val); // insertEvent('PlayerTeamChange',login,teamid)
				}

			}elseif($key == 'NickName'){
				$val = tm_substr($val);
				if($pl['NickName'] != $val){
					$pl['NickDraw'] = htmlspecialchars(tm_substr(stripColors($val),0,13),ENT_QUOTES,'UTF-8');
					$pl['NickDraw2'] = htmlspecialchars(tm_substr(stripColors($val),0,24),ENT_QUOTES,'UTF-8');
					$pl['NickDraw3'] = htmlspecialchars(tm_substr(stripEnlarge($val),0,24),ENT_QUOTES,'UTF-8');
					$pl['NickDraw4'] = htmlspecialchars(tm_substr($val,0,24),ENT_QUOTES,'UTF-8');
				}

			}elseif($key == 'PlayerInfo'){
				if($_debug>9) debugPrint('playersPlayerUpdate - PlayerInfo[LadderStats]',$val['LadderStats']);
				playersPlayerInfoUpdate($login,$pl,$val);
			}
			// update info
			$pl[$key] = $val;
			if(isset($pl['PlayerInfo'][$key]) && $pl['PlayerInfo'][$key] !== $pl[$key])
				$get_player_info = true;
		}
	}
	// if some value was different in PlayerInfo array, then get it
	if($get_player_info || !isset($pl['PlayerInfo']['Language']))
		addCall(true,'GetDetailedPlayerInfo',$login);
}


function playersPlayerInfoUpdate($login,&$pl,&$playerinfo){
	global $_debug;
  if(!is_string($login))
    $login = ''.$login;

	if(isset($playerinfo['NickName'])){
		$playerinfo['NickName'] = tm_substr($playerinfo['NickName']);
		if((!isset($pl['NickName']) || !isset($pl['NickDraw']) ||	$pl['NickName'] != $playerinfo['NickName'])){
			$pl['NickName'] = $playerinfo['NickName'];
			$pl['NickDraw'] = htmlspecialchars(tm_substr(stripColors($playerinfo['NickName']),0,13),ENT_QUOTES,'UTF-8');
			$pl['NickDraw2'] = htmlspecialchars(tm_substr(stripColors($playerinfo['NickName']),0,24),ENT_QUOTES,'UTF-8');
			$pl['NickDraw3'] = htmlspecialchars(tm_substr(stripEnlarge($playerinfo['NickName']),0,24),ENT_QUOTES,'UTF-8');
			$pl['NickDraw4'] = htmlspecialchars(tm_substr($playerinfo['NickName'],0,24),ENT_QUOTES,'UTF-8');
		}
	}
	$pl['PlayerInfo'] = $playerinfo;

	if(isset($playerinfo['Nation']) && ($playerinfo['Nation'] != '' || $pl['Nation'] == '')){
		$pl['Nation'] = $playerinfo['Nation'];
	}
	if(isset($playerinfo['Path']) && ($playerinfo['Path'] != '' || $pl['Path'] == '')){
		$pl['Path'] = $playerinfo['Path'];
		$list = explode('|',$playerinfo['Path'],3);
		if(isset($list[1]))
			$pl['Country'] = trim($list[1]);
	}
	if(isset($playerinfo['LadderStats']['TeamName']) && 
		 ($playerinfo['LadderStats']['TeamName'] != '' || $pl['TeamName'] == ''))
		$pl['TeamName'] = $playerinfo['LadderStats']['TeamName'];
	if(isset($playerinfo['LadderStats']['Ranking']))
		$pl['LadderRanking'] = $playerinfo['LadderStats']['Ranking'];
	if(isset($playerinfo['IsInOfficialMode']))
		$pl['IsInOfficialMode'] = $playerinfo['IsInOfficialMode'];
	
	if(isset($playerinfo['Language']) && $pl['Language'] == ''){
		$pl['Language'] = $playerinfo['Language'];
		players_count_languages();
	}
}


function playersPlayerConnect($event,$login,$pinfo,$pdetailedinfo,$pranking){
	global $_debug,$_players,$_relays,$_players_old,$_PlayerList,$_ServerOptions,$_currentTime,$_PlayerInfo,$_ChallengeInfo,$_GameInfos,$_players_checkchange,$_players_actives,$_players_firstmap,$_players_firstround,$_WarmUp,$_FWarmUp,$_StatusCode,$_DegradedMode,$_DegradedModePlayers,$_DegradedMode2Players,$_preferredspec_default,$_StatusCode,$_LapsDiscoFix,$_fteams;
  if(!is_string($login))
    $login = ''.$login;
	if($_debug>4) console("players{$event}('$login'):: [$ctries]");

	// if there were nobody in Rounds/Team/Cups mode, and not warmup, then restart map
	if(!$_players_firstmap && $_players_actives <= 0 && !$_WarmUp && $_FWarmUp <= 0 &&
		 ($_GameInfos['GameMode'] == ROUNDS || $_GameInfos['GameMode'] == TEAM || $_GameInfos['GameMode'] == CUP)){

		addCallDelay(3000,true,'ForceEndRound');
		if($_debug>0) console("Nobody was here: force next round in 3s.");
	}

	// get player info in $_players or $_players_old, move it in $_players if needed
	$init = false;
	if(!isset($_players[$login])){
		// player was not in normal list : full init
		if(!isset($_players_old[$login])){
			// did not exist at all
			$_players[$login] = playersPlayerSetup($login);
		}else{
			// was in old list
			$_players[$login] = $_players_old[$login];
			unset($_players_old[$login]);
			if(!isset($_players[$login]['ConnectionTime']))
				$_players[$login] = playersPlayerSetup($login);

			$_players[$login]['SpectatorStatus'] = 0;
			$_players[$login]['IsSpectator'] = false;
			if($_players[$login]['Score'] < 0)
				$_players[$login]['Score'] = 0;
		}
		$init = true;

	}elseif(!isset($_players[$login]['ConnectionTime'])){
		$_players[$login] = playersPlayerSetup($login);
		$init = true;
	}
	$pl = &$_players[$login];

	// init
	if($init){
		console("playersPlayerConnect - inits $login");
		$pl['FinalTime'] = -1;
		$pl['LastCpTime'] = -1;
		$pl['CheckpointNumber'] = -1;
		$pl['LapNumber'] = -1;
		$pl['NbrLapsFinished'] = -1;
		$pl['Checkpoints'] = array();
		$pl['LapCheckpoints'] = array();
		$pl['BestLapCheckpoints'] = array();
		$pl['Position'] = array('Pos'=>-1,'Check'=>-1,
														'FirstLogin'=>'','FirstDiffTime'=>0,'FirstDiffCheck'=>0,
														'Prev2Login'=>'','Prev2DiffTime'=>0,'Prev2DiffCheck'=>0,
														'PrevLogin'=>'','PrevDiffTime'=>0,'PrevDiffCheck'=>0,
														'NextLogin'=>'','NextDiffTime'=>0,'NextDiffCheck'=>0,
														'Next2Login'=>'','Next2DiffTime'=>0,'Next2DiffCheck'=>0);
		$pl['CPdelay'] = false;
		$pl['PreferredSpec'] = $_preferredspec_default;
		$pl['EarlySpecToPlay'] = false;

		$pl['RSdelays'] = 0;

		if($_GameInfos['GameMode'] == TEAM){
			// no player ranking infos available in team mode !
			$pl['Rank'] = -1;
			$pl['BestTime'] = -1;
			$pl['BestCheckpoints'] = array();
			$pl['Score'] = 0;
			$pl['FTeamPoints'] = 0;
			$pl['LadderScore'] = 0;
		}

		// update FTeamId
		foreach($_fteams as $ftid => $fteam){
			if(isset($fteam['Players'][$login])){
				$pl['FTeamId'] = $ftid;
				break;
			}
		}

	}

	$pl['Active'] = true;
	$pl['Quiet'] = false;
	$pl['ActiveTime'] = $_currentTime;
	$pl['Cheating'] = false;
	$pl['NoticeTime'] = 0;
	$pl['CheckCPs'] = !$_players_firstround;
	$pl['FinishEventTime'] = $_currentTime;

	// reset Net infos value
	$pl['LatestNetworkActivity'] = -1;


	// set pranking values
	if(isset($pranking['Login']) && $pranking['Login'] === $login){
		foreach($pranking as $key => $val){
			if($key == 'BestTime'){
				if($val > 0 && $val < $pl['BestTime'])
					$pl[$key] = $pranking[$key];
			}elseif($key == 'BestCheckpoints'){
				if(count($val) > 1 && (count($pl['BestCheckpoints']) <= 0 || end($val) < end($pl['BestCheckpoints'])))
					$pl[$key] = $pranking[$key];
			}else
				$pl[$key] = $pranking[$key];
		}
	}

	// set pinfo values
	foreach($pinfo as $key => $val)
		$pl[$key] = $val;

	// set pdetailedinfo values
	$pl['PlayerInfo'] = $pdetailedinfo;
	playersPlayerInfoUpdate($login,$pl,$pdetailedinfo);

	// is player relayed ?
	$pl['Relayed'] = (floor($pl['Flags']/10000) % 10) > 0;
	if($pl['Relayed'] && isset($pl['ML']['ShowML']) && !$pl['ML']['ShowML'])
		unset($pl['ML']);

	// make reduce and strip Nickname for some drawings
	if($pl['NickName'] == ''){
		if(isset($_PlayerInfo[$login]['NickName']))
			$pl['NickName'] = tm_substr($_PlayerInfo[$login]['NickName']);
		else
			$pl['NickName'] = tm_substr($login);
	}
	$pl['NickDraw'] = htmlspecialchars(tm_substr(stripColors($pl['NickName']),0,13),ENT_QUOTES,'UTF-8');
	$pl['NickDraw2'] = htmlspecialchars(tm_substr(stripColors($pl['NickName']),0,24),ENT_QUOTES,'UTF-8');
	$pl['NickDraw3'] = htmlspecialchars(tm_substr(stripEnlarge($pl['NickName']),0,24),ENT_QUOTES,'UTF-8');
	$pl['NickDraw4'] = htmlspecialchars(tm_substr($pl['NickName'],0,24),ENT_QUOTES,'UTF-8');

	$pl['ForcedOld'] = false;
	$pl['Forced'] = false;
	$pl['ForcedByHimself'] = false;
	$pl['ConnectionTime'] = $_currentTime;
	$pl['PlayTime'] = 0;

	// set player Status and Status2
	if($_StatusCode >= 5)
		$pl['Status'] = 2;
	else
		$pl['Status'] = $pl['SpectatorStatus'] > 0 ? 1 : 0;
	$pl['Status2'] = $pl['Status'];

	// announce player
	console(">> Player: ".stripColors($pl['NickName'])." ($login,{$pl['PlayerId']}) has connected! ("
					.($pl['Relayed'] ? 'relayed) (' : '')
					.count($_PlayerList)."/".$_ServerOptions['CurrentMaxPlayers'].")");

	// send PlayerBest event if BestTime exist for $login
	// should be validated only if some validation replay confirm it !
	//if($pl['BestTime'] > 0 && $_GameInfos['GameMode'] != LAPS){
	//if($_debug>0) console("* Best time exist for $login : ".MwTimeToString($pl['BestTime']).", send event.");
	//addEvent('PlayerBest',$login,$pl['BestTime']+0); // addEvent('PlayerBest',login,time)
	//}
	
	$pl['IsSpectator'] = ($pl['SpectatorStatus'] % 10) > 0;
	$pl['IsTemporarySpectator'] = (floor($pl['SpectatorStatus']/10) % 10) > 0;
	$pl['IsPureSpectator'] = (floor($pl['SpectatorStatus']/100) % 10) > 0;
	$pl['IsAutoTarget'] = (floor($pl['SpectatorStatus']/1000) % 10) > 0;
	$pl['CurrentTargetId'] = (int)floor($pl['SpectatorStatus']/10000);
	if(!$pl['IsSpectator'])
		$pl['PlayRights'] = true;
	

	if(!$init && $_GameInfos['GameMode'] == LAPS && $_StatusCode == 4){
		// players was already here in current Laps race ! 
		//if($_debug>0) debugPrint("playersPlayerConnect - $login - Laps,not init",$pl);

		if($pl['FinalTime'] > 0 && !$pl['Relayed']){
			// player have already finished Laps race : put him tmp spec !
			if($pl['IsSpectator']){
				if($_debug>0) console("playersPlayerConnect - $login - Laps already played, is spec !");
				$pl['toSpec'] = true;  // will put spec when come player
			}else{
				if($_debug>0) console("playersPlayerConnect - $login - Laps already played, go specplay !");
				$pl['toSpecPlay'] = true; // will put spec when possible, then set toPlay
			}
		}
		// keep some previous result values...
		$_players[$login]['PrevLapNumber'] = $_players[$login]['LapNumber'];

		if($_LapsDiscoFix && $_players[$login]['PrevLapNumber'] > 0){
			$msg = localeText(null,'server_message').localeText(null,'interact').'You already drove '.$_players[$login]['PrevLapNumber'].' laps... continue !';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
	}

	if($_StatusCode >= 5){
		if(isset($pl['toPlay']))
			unset($pl['toPlay']);
		if(isset($pl['toSpecPlay']))
			unset($pl['toSpecPlay']);
		if(isset($pl['toSpec']))
			unset($pl['toSpec']);
	}

	// update players language and count players languages
	players_count_languages();
	
	// verify low DownloadRate and UploadRate
	playersVerifyRates($login);
	
	$_players_checkchange = true;
	
	// reload admins file
	loadAdmins();

	// degraded mode test
	if($_DegradedMode < 2 && count($_players) > $_DegradedMode2Players){
		if($_debug>0) console("Degraded mode level 2 (more than $_DegradedMode2Players players)");
		$_DegradedMode = 2;
		addCall(null,'SetVehicleNetQuality',0);
		addCall(null,'EnableP2PUpload',false);
		addCall(null,'EnableP2PDownload',false);
	}
	elseif($_DegradedMode < 1 && count($_players) > $_DegradedModePlayers){
		if($_debug>0) console("Degraded mode level 1 (more than $_DegradedModePlayers players)");
		$_DegradedMode = 1;
		addCall(null,'SetVehicleNetQuality',0);
		addCall(null,'EnableP2PUpload',false);
		addCall(null,'EnableP2PDownload',false);
	}

	// show debug info
	if($_debug>5) debugPrint("playersPlayerConnect - pl",$pl);
	elseif($_debug>1){
		$p = $pl;
		foreach($p as $k => &$v){
			if(is_array($v))
				unset($p[$k]);
		}
		debugPrint("playersPlayerConnect - partial pl",$p);
	}
}


function playersPlayerConnect_Reverse($event,$login){
	playersTestGameFix($login);
}


function playersPlayerDisconnect($event,$login){
	global $_debug,$_players,$_relays,$_players_old,$_PlayerList,$_ServerOptions,$_GameInfos,$_currentTime,$_players_checkchange,$_players_actives,$_StatusCode,$_WarmUp,$_FWarmUp;
  if(!is_string($login))
    $login = ''.$login;
	if($_debug>4) console("players{$event}('$login')::");

	// if relay then remove it (should have been done in callbackEvents())
	if(isset($_relays[$login])){
		unset($_relays[$login]);
		return;
	}

	// get player info in $_players or $_players_old
	if(isset($_players[$login]))
		$pl = &$_players[$login];
	elseif(isset($_players_old[$login])){
		$pl = &$_players_old[$login];
		//dropEvent();
	}else{
		//dropEvent();
		return;
	}
	// if player was on relay server then don't propagate event
	if($pl['Relayed'])
		dropEvent();

	// play time (if quit while playing)
	if(count($pl['Checkpoints']) > 0){
		//console("add playtime($login) : ".end($pl['Checkpoints']));
		$pl['PlayTime'] += end($pl['Checkpoints']);
	}

	// usefull ???
	if($pl['Active'])
		$pl['ActiveTime'] = $_currentTime;

	// kick disconnected (this event come in case connected player trying to reconnect)
	/* no more on tmuf
		 addCall(true,'Kick',$login,'$w$f00 server kick for player trying to reconnect $z');
		 if($pl['Active'])
		 $pl['ActiveTime'] = $_currentTime;
		 else{
		 // Connection failed (probably)
		 $msgarray = multiLocaleText(localeText(null,'server_message').localeText(null,'interact'),
		 array('players.failedconnection'),' '.$login.'.');
		 addCall(null,'ChatSendServerMessageToLanguage',$msgarray);
		 }
	*/
	console("playersPlayerDisconnect - Active=".$pl['Active'].', '.$pl['ActiveTime'].', '.$_currentTime);

	$pl['Active'] = false;
	$pl['ForcedOld'] = false;
	$pl['Forced'] = false;
	$pl['ForcedByHimself'] = false;
	$pl['PlayRights'] = false;
	$pl['IsSpectator'] = false;
	$pl['SpectatorStatus'] = 0;
	$pl['EarlySpecToPlay'] = false;

	console('<< Player: '.stripColors($pl['NickName'])." ($login,{$pl['PlayerId']}) has disconnected! ("
					.($pl['Relayed'] ? 'relayed) (' : '')
					.count($_PlayerList)."/".$_ServerOptions['CurrentMaxPlayers'].') ['
					.(($_currentTime-$pl['ConnectionTime'])/1000).','.($pl['PlayTime']/1000).']');

	$pl['ConnectionTime'] = $_currentTime;
	$pl['PlayTime'] = 0;

	players_count_languages();
  $_players_checkchange = true;
}


function playersPlayerDisconnect_Reverse($event,$login){
	global $_debug,$_players;
	if(isset($_players[$login]['Quiet']))
		$_players[$login]['Quiet'] = true;

	fteamsCheckInactive($login,'PlayerDisconnect');
}


function playersPlayerSpecChange($event,$login,$isspec,$specstatus,$oldspecstatus){
	global $_debug,$_players,$_players_checkchange;
	//if($_debug>5) console("players{$event}('$login',$isspec,$specstatus,$oldspecstatus)::");
	if(!isset($_players[$login]['ConnectionTime']) || !isset($_players[$login]['Relayed']) || $_players[$login]['Relayed']){
		dropEvent();
		return;
	}
	if($_debug>5) console("players{$event}('$login',$isspec,$specstatus,$oldspecstatus)::");

  $_players_checkchange = true;
	if(isset($_players[$login]['ForcedOld']))
		$_players[$login]['ForcedOld'] = $_players[$login]['Forced'];
	if(isset($_players[$login]['PlayRights']) && !$isspec)
		$_players[$login]['PlayRights'] = true;
	
	if(isset($_players[$login]['toSpecPlay'])){
		if(!$isspec){
			if($_debug>3) console("playersPlayerSpecChange:: {$login} toSpecPlay : to spec !");
			addCall($login,'ForceSpectator',$login,1); // spec
			addCall($login,'ForceSpectator',$login,0); // then playerfree
		}
	}

	if($isspec && isset($_players[$login]['toPlay'])){
		playersPlayerCountPlayers();
		//if($_debug>0) console("playersPlayerSpecChange:: set {$login} back toPlay");
		addCall($login,'ForceSpectator',$login,2); // play
		addCall($login,'ForceSpectator',$login,0); // then playerfree
		unset($_players[$login]['toPlay']);

	}elseif(!$isspec && isset($_players[$login]['toSpec'])){
		if($_debug>3) console("playersPlayerSpecChange:: set {$login} back toSpec");
		addCall($login,'ForceSpectator',$login,1); // spec
		addCall($login,'ForceSpectator',$login,0); // then playerfree
		unset($_players[$login]['toSpec']);
	}
}


//function playersPlayerCheckpoint_Post($event,$login,$time,$lapnum,$checkpt,$hiddenabort){
//console("players.Event_Post[$event]('$login',$time,$lapnum,$checkpt)");
//}

function playersPlayerCheckpoint($event,$login,$time,$lapnum,$checkpt,$hiddenabort=false){
	global $_debug,$_players,$_GameInfos,$_LastCheckNum,$_NumberOfChecks,$_players_checkchange,$_players_round_time,$_currentTime,$_ChallengeInfo,$_players_round_sequence,$_players_missings,$_LapsDiscoFix,$_players_round_restarting,$_players_round_checkpoints,$_CheatTests,$_LapsDiscoFix,$_fteams_on,$_fteams,$_fteams_round,$_fteams_rules,$_fteams_max,$_callFuncsArgs,$_WarmUp,$_FWarmUp;
	$_players_round_checkpoints++;
  if(!is_string($login))
    $login = ''.$login;
	if($_players_round_restarting || !isset($_players[$login]['ConnectionTime'])){
		//if($_debug>0) console("players{$event}:: Drop Event ! ('$login',$time,$lapnum+$prevlap,$checkpt+$prevcp)($_NumberOfChecks,$cpdelay)");
		dropEvent();
		return;
	}

	// get player info in $_players or $_players_old, move it in $_players if needed
	if(!isset($_players[$login]['ConnectionTime'])){
		// should never happen !
		//if($_debug>0) console("players{$event}:: Drop Event & insert ! ('$login',$time,$lapnum+$prevlap,$checkpt+$prevcp)($_NumberOfChecks,$cpdelay)");
		insertEvent('PlayerCheckpoint',$login,$time,$lapnum,$checkpt,$hiddenabort);
		insertEvent('PlayerConnect',$login); // insertEvent('PlayerConnect',login)
		dropEvent();
		return;
	}
	$pl = &$_players[$login];

	// TimeAttack mode, 1st check = new run
	if($_GameInfos['GameMode'] == TA && $checkpt == 0){
		if(!$hiddenabort && $_LastCheckNum >= 0 && $pl['FinalTime'] <= 0 && count($pl['Checkpoints']) > 0){
			$finaltime = end($pl['Checkpoints']);
			if(key($pl['Checkpoints']) == $_LastCheckNum && count($pl['Checkpoints']) == $_LastCheckNum){
				// missing finish !!!  (should never happen)
				$oldfet = $pl['FinishEventTime'];
				$pl['FinishEventTime'] = $_currentTime - $time - $pl['CPdelay'];
				$difffet = $pl['FinishEventTime'] - $oldfet;
				$_players_missings++;
				if($_debug>0) console("playersPlayerCheckpoint({$login},{$time},{$lapnum},{$checkpt},true):: missing Finish,0 (TA,first cp) [{$_LastCheckNum}: ".implode(',',$pl['Checkpoints'])."]");

				// set $hiddenabort to true for next plugins
				$hiddenabort = true;
				$_callFuncsArgs[5] = $hiddenabort;

				// send a PlayerStart ...
				dropEvent();
				insertEvent('PlayerCheckpoint',$login,$time,$lapnum,$checkpt,$hiddenabort);
				insertEvent('PlayerStart',$login,$pl['FinishEventTime']);
				return;
			}
		}
		
		if(!$hiddenabort && $pl['CheckpointNumber'] >= 0 && $pl['FinalTime'] < 0){
			// missing PlayerFinish,0 (happens if respawn+del in TA)
			// create fake FinishEventTime, base on previous CPdelay !
			$oldfet = $pl['FinishEventTime'];
			$pl['FinishEventTime'] = $_currentTime - $time - $pl['CPdelay'];
			$difffet = $pl['FinishEventTime'] - $oldfet;
			$pl['RSdelays'] += $difffet;
			if($_debug>0) console("playersPlayerCheckpoint({$login},{$time},{$lapnum},{$checkpt},true):: missing Finish,0 (respawn+del) after {$difffet} ({$pl['RSdelays']})");

			// set $hiddenabort to true for next plugins
			$hiddenabort = true;
			$_callFuncsArgs[5] = $hiddenabort;

			// send a PlayerStart ...
			dropEvent();
			insertEvent('PlayerCheckpoint',$login,$time,$lapnum,$checkpt,$hiddenabort);
			insertEvent('PlayerStart',$login,$pl['FinishEventTime']);
			return;
		}
		
		$pl['CPdelay'] = false;
		$pl['CheckpointNumber'] = -1;
		$pl['Checkpoints'] = array();
		$pl['FinalTime'] = -1;
		$pl['LastCpTime'] = -1;
		$pl['Position'] = array('Pos'=>-1,'Check'=>-1,
														'FirstLogin'=>'','FirstDiffTime'=>0,'FirstDiffCheck'=>0,
														'Prev2Login'=>'','Prev2DiffTime'=>0,'Prev2DiffCheck'=>0,
														'PrevLogin'=>'','PrevDiffTime'=>0,'PrevDiffCheck'=>0,
														'NextLogin'=>'','NextDiffTime'=>0,'NextDiffCheck'=>0,
														'Next2Login'=>'','Next2DiffTime'=>0,'Next2DiffCheck'=>0);

	}
	$pl['CPseq'] = $_players_round_sequence++;

	// compute delay between script time from round start and cp time
	if($_GameInfos['GameMode'] == TA)
		$cpdelay = ($_currentTime - $pl['FinishEventTime']) - $time;
	else
		$cpdelay = ($_currentTime - $_players_round_time) - $time;
	if(($pl['CPdelay'] === false) || ($cpdelay < $pl['CPdelay']))
		$pl['CPdelay'] = $cpdelay;

	// Laps mode correction : add previous full laps for disco-reco cases
	$prevlap = ($_LapsDiscoFix && $_GameInfos['GameMode'] == LAPS) ? $pl['PrevLapNumber'] : 0;
	$prevcp = $prevlap * $_NumberOfChecks;

	if($prevlap > 0){
		if($_debug>0) console("players{$event}('$login',$time,$lapnum+$prevlap,$checkpt+$prevcp)($_NumberOfChecks,$cpdelay)::");
	}else{
		if($_debug>0) console("players{$event}('$login',$time,$lapnum,$checkpt)($_NumberOfChecks,$cpdelay)::");
	}

	// if first checkpoint then it will be ok for best and records on that run
	if($checkpt == 0 && !$pl['CheckCPs'])
		$pl['CheckCPs'] = true;

	// number of checkpoints
	if($_NumberOfChecks <= 0 && $lapnum == 1 && $pl['LapNumber'] == 0 && $checkpt >= 0 && $checkpt == $pl['CheckpointNumber']+1)
		$_NumberOfChecks = $checkpt+1;

	// if Laps mode then make correction : add previous full laps
	$lapnum += $prevlap;
	$checkpt += $prevcp;

	// if current is smaller than array size then it is cheating !!!
	// note: it can happen that cp are not received in order !  :(  should be tested....
	if($_CheatTests && $checkpt+1 < count($pl['Checkpoints'])){
		// Cheat !
		playersCheat($login,'bad checkpoint number',"new cp: $time,$lapnum,$checkpt, prev cps: ".implode(',',$pl['Checkpoints']).')');
		$pl['Checkpoints'] = array();
		dropEvent();
		return;
	}

	// if cp==0 and Laps and !$_LapsDiscoFix then empty cps list
	if($checkpt+0 == 0 && $_GameInfos['GameMode'] == LAPS && !$_LapsDiscoFix)
		$pl['Checkpoints'] = array();
	// if array is empty, fill it until the current
	if(count($pl['Checkpoints']) == 0){
		for($cp = -1; $cp < $checkpt+0; $cp++)
			$pl['Checkpoints'][$cp] = 0;
	}
	// complete array if needed ?!
	for($cp = count($pl['Checkpoints']); $cp < $checkpt+0; $cp++)
		$pl['Checkpoints'][$cp] = 0;
	
	// new cp time (or score)
	$pl['Checkpoints'][$checkpt+0] = $time+0;

	// reset finaltime
	if(($checkpt == 0 && $pl['FinalTime'] <= 0) || $pl['FinalTime'] == 0)
		$pl['FinalTime'] = -1;


	// Laps mode : special cases !
	if($_GameInfos['GameMode'] == LAPS){
		// lap checkpoints.  
		// note: should first sort the times, which make a problem with the -1 index...
		$laptime = -1;
		$lapcheckpt = ($_NumberOfChecks > 0)? $checkpt % $_NumberOfChecks : $checkpt; 
		$prevlapcheckpt = $checkpt - $lapcheckpt - 1;

		if($lapcheckpt == 0)
			$pl['LapCheckpoints'] = array();
		if(count($pl['LapCheckpoints']) == 0){
			for($cp = -1; $cp < $lapcheckpt+0; $cp++)
				$pl['LapCheckpoints'][$cp] = 0;
		}

		if($prevlapcheckpt >= -1){
			if(isset($pl['Checkpoints'][$prevlapcheckpt])){
				$laptime = $time - $pl['Checkpoints'][$prevlapcheckpt];
			}else{
				if($_debug>0) console("playersPlayerCheckpoint::laptime({$login},{$lapcheckpt}) BAD: no time for prev lap checkpoint {$prevlapcheckpt} !");
			}
		}
		$pl['LapCheckpoints'][$lapcheckpt] = $laptime;
		
		// end of lap in Laps mode
		if($pl['CheckCPs'] && $_NumberOfChecks > 0 && (($checkpt+1) % $_NumberOfChecks) == 0 &&
			 isset($pl['Checkpoints'][$checkpt-$_NumberOfChecks])){

			if($_LapsDiscoFix && $_players[$login]['PrevLapNumber'] > 0 && $lapnum < $_GameInfos['LapsNbLaps']){
				$msg = localeText(null,'server_message').localeText(null,'interact').'Lap '.($lapnum+1).' / '.$_GameInfos['LapsNbLaps'];
				addCall(null,'ChatSendToLogin', $msg, $login);
			}

			// make lap time
			$laptime = $time - $pl['Checkpoints'][$checkpt-$_NumberOfChecks];
			$pl['Laps'][$lapnum] = $laptime;

			// consistency check of checkpoints
			$cptime = reset($pl['Checkpoints'])-1;
			foreach($pl['Checkpoints'] as $cp){
				if($cp < 0 || $cp <= $cptime){
					// wrong checkpoints : don't give cp
					$cptime = false;
					insertEvent('PlayerLap',$login,$laptime,$lapnum,$checkpt,null); // insertEvent('PlayerLap',login,time,lapnum,checkpt,checkpoints)
					console("playersPlayerCheckpoint::laptime($login,$laptime) BAD: wrong checkpoint time $cp (".implode(',',$pl['Checkpoints']).')');
					$msg = localeText(null,'server_message').localeText(null,'interact').'Wrong checkpoints times ! bug or cheat ?... not used for best time and records !';
					addCall(null,'ChatSendToLogin', $msg, $login);

				}
				$cptime = $cp;
			}
			if($cptime !== false){
				// it's ok
				insertEvent('PlayerLap',$login,$laptime,$lapnum,$checkpt,$pl['LapCheckpoints']); // insertEvent('PlayerLap',login,time,lapnum,checkpt,checkpoints)
				// check for best lap
				if($pl['BestLapTime'] <= 0 || $laptime < $pl['BestLapTime']){
					$pl['BestLapTime'] = $laptime;
					$pl['BestLapCheckpoints'] = $pl['LapCheckpoints'];
					unset($pl['BestLapCheckpoints'][-1]);
					insertEvent('PlayerBest',$login,$laptime,$_ChallengeInfo,$_GameInfos,$pl['BestLapCheckpoints']); // insertEvent('PlayerBest',login,time,challengeinfo,gameinfos,checkpts)
					//if($_debug>2) debugPrint("playersPlayerCheckpoint - $laptime - LapCheckpoints",$pl['LapCheckpoints']);
				}
			}
		}
	}

	// various
	$_players_checkchange = true;
	$pl['PlayRights'] = true;

	// play time
	if($checkpt == 0 && count($pl['Checkpoints']) > 0){
		//console("add playtime($login) : ".end($pl['Checkpoints']));
		$pl['PlayTime'] += end($pl['Checkpoints']);
	}

	// update values
	$pl['CheckpointNumber'] = $checkpt+0;
	$pl['LapNumber'] = $lapnum+0;
	$pl['NbrLapsFinished'] = $pl['LapNumber'];
	$pl['LastCpTime'] = $time;


	if($prevlap > 0 && $_GameInfos['GameMode'] == LAPS && $lapnum >= $_GameInfos['LapsNbLaps']){
		// counting previous laps the player have finished ! make PlayerFinish event and put the players as spec !
		addEvent('PlayerFinish',$login,$time,$pl['Checkpoints']);
		if(!$pl['Relayed']){
			// player have already finished Laps race : put him spec !
			addCall($login,'ForceSpectator',$login,1); // spec
			addCallDelay(200,$login,'ForceSpectator',$login,2); // then play
			addCallDelay(300,$login,'ForceSpectator',$login,0); // then playerfree
			$msg = localeText(null,'server_message').localeText(null,'interact')."With your {$prevlap} previous lap(s), you have now finished ! It will be in log...";
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
	}


	// fteams : test if too many players in team -> put spec if needed
	if($checkpt == 0 && $_fteams_on && $pl['FTeamId'] >= 0 && $pl['FTeamId'] < $_fteams_max && !$_WarmUp && $_FWarmUp <= 0){
		$_fteams_round[$pl['FTeamId']]['CP0Players'][$login] = $login;
		$numpl = count($_fteams_round[$pl['FTeamId']]['CP0Players']);

		if($numpl > $_fteams_rules['MaxPlaying']){

			if($_fteams_rules['MaxPlayingRule'] == 'mapcp'){
				// too many in team, mapcp mode : spec
				if($_debug>1)console("playersPlayerCheckpoint:: {$login}(ftid={$pl['FTeamId']}), {$numpl} > {$_fteams_rules['MaxPlaying']},{$_fteams_rules['MaxPlayingRule']} => ForceSpec");
				addCall($login,'ForceSpectator',$login,1); // spec

			}elseif($_fteams_rules['MaxPlayingRule'] == 'roundcp'){
				// too many in team, roundcp mode : temp spec
				if($_debug>1)console("playersPlayerCheckpoint:: {$login}(ftid={$pl['FTeamId']}), {$numpl} > {$_fteams_rules['MaxPlaying']},{$_fteams_rules['MaxPlayingRule']} => TmpSpec");
				addCall($login,'ForceSpectator',$login,1); // spec
				addCall($login,'ForceSpectator',$login,2); // play
				addCall($login,'ForceSpectator',$login,0); // then playerfree
			}
		}
	}

	if($_debug>8) debugPrint("playersPlayerCheckpoint - _players[$login]",$pl);
}


function playersPlayerFinish($event,$login,$time,$checkpts=null){
	global $_debug,$_StatusCode,$_beginround_time,$_players,$_currentTime,$_GameInfos,$_ChallengeInfo,$_players_checkchange,$_NetStats_KickTime_Synchro,$_LastCheckNum,$_players_round_restarting,$_CheatTests,$_callFuncsArgs;
	if($_StatusCode != 4){
		if($time == 0){
			if($_debug>7) console("playersPlayerFinish({$login},{$time}):: StatusCode={$_StatusCode} -> drop event !");
		}else{
			if($_debug>0) console("playersPlayerFinish({$login},{$time}):: StatusCode={$_StatusCode} !!! -> drop event !");
		}
		dropEvent();
		return;
	}

	if($_players_round_restarting){
		if($_debug>4) console("playersPlayerFinish({$login},{$time}):: players_round_restarting -> drop event !");
		dropEvent();
		return;
	}

	if(!isset($_players[$login]['ConnectionTime']) || !isset($_players[$login]['FinalTime'])){
		if($_debug>3) console("playersPlayerFinish({$login},{$time}):: missing infos for player -> drop event !");
		dropEvent();
		return;
	}

	if($_players[$login]['Cheating']){
		if($_debug>1) console("playersPlayerFinish({$login},{$time}):: cheat({$_players[$login]['Cheating']}) -> drop event !");
		dropEvent();
		return;
	}


	if(!is_string($login))
		$login = ''.$login;

	$pl = &$_players[$login];


	if($time == 0){

		if($_GameInfos['GameMode'] == TA){
			// TA: a finish 0 is always a start
			insertEvent('PlayerStart',$login,$_currentTime);

			if($_currentTime - $_beginround_time < 3000){
				// finish 0 just after begin round, drop it
				if($_debug>3) console("playersPlayerFinish({$login},{$time}):: round start -> drop event !");
				$pl['FinishEventTime'] = $_currentTime;
				dropEvent();
				return;
			}
			if($_players[$login]['FinalTime'] > 0 && !$_ChallengeInfo['LapRace']){
				// finish 0 after the real finish, drop it and set new run (except if it's a laprace map)
				if($_debug>3) console("playersPlayerFinish({$login},{$time}):: after real finish -> drop event !");
				$pl['CheckpointNumber'] = -1;
				$pl['Checkpoints'] = array();
				$pl['FinalTime'] = -1;
				$pl['Position'] = array('Pos'=>-1,'Check'=>-1,
																'FirstLogin'=>'','FirstDiffTime'=>0,'FirstDiffCheck'=>0,
																'Prev2Login'=>'','Prev2DiffTime'=>0,'Prev2DiffCheck'=>0,
																'PrevLogin'=>'','PrevDiffTime'=>0,'PrevDiffCheck'=>0,
																'NextLogin'=>'','NextDiffTime'=>0,'NextDiffCheck'=>0,
																'Next2Login'=>'','Next2DiffTime'=>0,'Next2DiffCheck'=>0);
				$pl['FinishEventTime'] = $_currentTime;
				dropEvent();
				return;
			}

		}elseif($_GameInfos['GameMode'] == STUNTS){
			// Stunts

		}else{
			// other than TA and Stunts

			if($pl['FinalTime'] > 0){
				// player has already finished, just drop this one
				// (can happen with $_LapsDiscoFix in Laps mode, or a reconnect after having finished)
				dropEvent();
				return;
			}
		}

	}else{
		if($_players[$login]['FinalTime'] > 0){
			if($_debug>0) console("playersPlayerFinish({$login},{$time}):: already a final time ({$_players[$login]['FinalTime']}) -> drop event !");
			dropEvent();
			return;
		}
	}


	// dedicated event: add checkpoints in event args if time > 0
	if($checkpts === null && $time > 0){
		if(count($pl['Checkpoints']) <= 0){
			// no checkpoints stored, consider it a finish 0 !  set event args
			if($_debug>6) console("playersPlayerFinish::({$login}) no cp: set finish time to 0 !"); 
			$time = 0;
			$_callFuncsArgs[2] = $time;

		}else{
			// add/update checkpts to event args
			$checkpts = $pl['Checkpoints'];
			$_callFuncsArgs[3] = $checkpts;
		}
	}


	
	if($time <= 0 || $checkpts === null || !is_array($checkpts)){
		// time == 0 or no checkpoints, set time to 0 and modify event args
		$time = 0;
		$_callFuncsArgs[2] = $time;
		$checkpts = null;
		$_callFuncsArgs[3] = $checkpts;

	}else{
		// time > 0 and checks...
		// note: should first sort the times, which make a problem with the -1 index...
			
		if(end($pl['Checkpoints']) == $time){
			// last cp time and finish time are same
			if($pl['CheckCPs']){
				$endcpnum = key($pl['Checkpoints']);
				// verify last cp num
				if($_CheatTests && ($endcpnum < 0 || $endcpnum < $_LastCheckNum || $pl['LapNumber'] < 0)){
					// last checkpoint num smaller than the number of checkpoints for map : wrong ! it is cheat !!!
					playersCheat($login,'wrong last checkpoint num',"cpnum: {$endcpnum} < {$_LastCheckNum} ({$pl['LapNumber']}), cps: ".implode(',',$checkpts).')');
					/* 					if($_debug>3) console("playersPlayerFinish::({$login}) cheat: drop event and sent it with 0 time !"); */
					/* 					insertEvent('PlayerFinish',$login,0,null); */
					/* 					dropEvent(); */
					/* 					return; */
					if($_debug>3) console("playersPlayerFinish::({$login}) cheat: set finish time to 0 !"); 
					$time = 0;
					$_callFuncsArgs[2] = $time;
					
				}elseif($_GameInfos['GameMode'] != STUNTS){
					// consistency check of checkpoints
					$cptime = reset($pl['Checkpoints'])-1;
					foreach($pl['Checkpoints'] as $cp){
						if($cp < 0 || $cp <= $cptime){
							// wrong checkpoints : replace by 0 time
							// note: problem with MixChecksBlocks which can make several CP times identical :(
							$msg = localeText(null,'server_message').localeText(null,'interact').'Wrong checkpoints times ! bug or cheat ?... not used for best time and records !';
							addCall(null,'ChatSendToLogin', $msg, $login);
							$cptime = false;
							console("playersPlayerFinish - {$login},{$time} - BAD: wrong checkpoint time {$cp} (".implode(',',$checkpts).')');
							/* 						insertEvent('PlayerFinish',$login,0,null); */
							/* 						dropEvent(); */
							/* 						return; */
							$time = 0;
							$_callFuncsArgs[2] = $time;
						}
						$cptime = $cp;
					}
					if($time > 0){
						// seems ok...
						if($_LastCheckNum < 0 || $_LastCheckNum < $endcpnum){
							// new number of checkpoints for map
							end($pl['Checkpoints']);
							$_LastCheckNum = key($pl['Checkpoints']);
							if($_debug>2) console("LastCheckNumber={$_LastCheckNum} (from {$login},{$time},[".implode(',',$pl['Checkpoints'])."])".print_r($pl['Checkpoints'],true));
						}
						// re-send event with checkpoints list
						/* 						if($_debug>8) console("playersPlayerFinish::({$login}) : drop event and sent it with cps !"); */
						/* 						insertEvent('PlayerFinish',$login,$time,$pl['Checkpoints']); */
						/* 						dropEvent(); */
						/* 						return; */
						$checkpts = $pl['Checkpoints'];
						$_callFuncsArgs[3] = $checkpts;
					}
				}
				
			}else{
				// no consistency check : continue without cp times, each plugin will decide what to do in such state
				$msg = localeText(null,'server_message').localeText(null,'interact')."Can't verify checkpoints : not used for best time and records !";
				addCall(null,'ChatSendToLogin', $msg, $login);
			}
			
		}else{
			// last checkpoint time differ from finish, or no cp time : don't consider it for best time and let other plugin decide for themself
			if(count($pl['Checkpoints']) > 0){
				if($_debug>0) console("Warning: finish for $login, $time with different checkpoints [".implode(',',$pl['Checkpoints']).']');
				$msg = localeText(null,'server_message').localeText(null,'interact').'Wrong last checkpoint time ! bug or cheat ?...';
			}else{
				if($_debug>0) console("Warning: finish for $login, $time without checkpoints");
				$msg = localeText(null,'server_message').localeText(null,'interact').'No checkpoint time ! bug or cheat ?... not used for best time and records !';
			}
			addCall(null,'ChatSendToLogin', $msg, $login);
			return;
		}

		// update player fteam AllPlayers value
		$ftid = $pl['FTeamId'];
		if($ftid >= 0 && isset($_fteams[$ftid]['AllPlayers'])){
			if(!isset($_fteams[$ftid]['AllPlayers'][$login]))
				$_fteams[$ftid]['AllPlayers'][$login] = 0;
			$_fteams[$ftid]['AllPlayers'][$login]++;
		}
	}


	$dbg = $time > 0 ? 0 : 3;
	if($_GameInfos['GameMode'] == TA && $pl['RSdelays'] > 0){
		if($_debug>=$dbg) console("players{$event}($login,$time):: (best={$pl['BestTime']},mindelay={$pl['CPdelay']}) RSdelays={$pl['RSdelays']}");
	}else{
		if($_debug>=$dbg) console("players{$event}($login,$time):: (best={$pl['BestTime']},mindelay={$pl['CPdelay']})");
	}

	// final time
	if(!isset($pl['BestTime'])){
		$pl['BestCheckpoints'] = array();
		$pl['BestTime'] = -1;
	}

	$pl['FinalTime'] = $time+0;

	if($time > 0) 
		$pl['LastCpTime'] = $time+0;
	$pl['PlayRights'] = true;


	// test for PlayerBest
	if($time > 0){
		if($_GameInfos['GameMode'] == STUNTS){
			// test if best score (score is in time value in Stunts mode)
			if($pl['CheckCPs'] && $time > 0 && ($pl['BestTime'] <= 0 || $time > $pl['BestTime'])){
				$pl['BestTime'] = $time+0;
				$pl['BestCheckpoints'] = $checkpts;
				if(isset($pl['BestCheckpoints'][-1]))
					unset($pl['BestCheckpoints'][-1]);
				$pl['BestDate'] = $_currentTime;
				//if($_debug>6) console("players{$event}($login,$time):: sendbest $time !");
				insertEvent('PlayerBest',$login,$time+0,$_ChallengeInfo,$_GameInfos,$pl['BestCheckpoints']); // insertEvent('PlayerBest',login,time)
			}
			
		}else{
			// test if best time
			if($pl['CheckCPs'] && $time > 0 && $_GameInfos['GameMode'] != LAPS && ($pl['BestTime'] <= 0 || $time < $pl['BestTime'])){
				$pl['BestTime'] = $time+0;
				$pl['BestCheckpoints'] = $checkpts;
				if(isset($pl['BestCheckpoints'][-1]))
					unset($pl['BestCheckpoints'][-1]);
				$pl['BestDate'] = $_currentTime;
				//if($_debug>6) console("players{$event}($login,$time):: sendbest $time !");
				insertEvent('PlayerBest',$login,$time+0,$_ChallengeInfo,$_GameInfos,$pl['BestCheckpoints']); // insertEvent('PlayerBest',login,time)
			}
			
		}
	}


	if($_GameInfos['GameMode'] == TA && $time == 0){
		$pl['CheckpointNumber'] = -1;
		$pl['Checkpoints'] = array();
		$pl['FinalTime'] = -1;
		$pl['Position'] = array('Pos'=>-1,'Check'=>-1,
														'FirstLogin'=>'','FirstDiffTime'=>0,'FirstDiffCheck'=>0,
														'Prev2Login'=>'','Prev2DiffTime'=>0,'Prev2DiffCheck'=>0,
														'PrevLogin'=>'','PrevDiffTime'=>0,'PrevDiffCheck'=>0,
														'NextLogin'=>'','NextDiffTime'=>0,'NextDiffCheck'=>0,
														'Next2Login'=>'','Next2DiffTime'=>0,'Next2DiffCheck'=>0);
		
	}

	$_players_checkchange = true;
	if($_GameInfos['GameMode'] != TA && $_GameInfos['GameMode'] != STUNTS){
		// in rounds based modes, compute players positions
		playersComputePositions(-2);
	}


	// temporary debug
	//if($_debug>0) console("players{$event}($login,$time):: (best={$pl['BestTime']},mindelay={$pl['CPdelay']}): ft={$_players[$login]['FinalTime']}");

	// try to detect player disconnect at round auto-no-time-finished players
	if($time == 0 && 	$pl['LatestNetworkActivity'] > $_NetStats_KickTime_Synchro){
		
		$msg = localeText(null,'server_message')
			.localeText($login,'players.net_inactivity',
									stripColors($login),(int)($pl['LatestNetworkActivity']/1000));
		addCall(true,'ChatSendServerMessage', $msg);
		addCallDelay(2000,true,'Kick',$login,'$w$ff0 '.stripColors($msg).' $z');
	}
}


function playersPlayerFinish_Reverse($event,$login,$time,$checkpts=null){
	global $_debug,$_players_round_finished,$_players_round_restarting,$_players,$_GameInfos,$_currentTime,$_ChallengeInfo;
	if($_players_round_restarting)
		return;
	//console("playersPlayerFinish_Reverse({$login},{$time})");
	if(isset($_players[$login]['NbrLapsFinished']) &&  $time > 0){
		if($_GameInfos['GameMode'] != LAPS || $_players[$login]['NbrLapsFinished'] >= $_GameInfos['LapsNbLaps'])
			$_players_round_finished++;
	}
	// after a finish we are sure that next run should be checked : indicate it !
	if(isset($_players[$login]['CheckCPs']))
		$_players[$login]['CheckCPs'] = true;

	// set time of finish event
	if($_GameInfos['GameMode'] == TA){
		if($time <= 0 || !$_ChallengeInfo['LapRace']){
			$_players[$login]['FinishEventTime'] = $_currentTime;
		}else{
			$_players[$login]['FinishEventTime'] = $_currentTime - $_players[$login]['CPdelay'];
			addEvent('PlayerStart',$login,$_players[$login]['FinishEventTime']);
		}
		$_players[$login]['RSdelays'] = 0;
	}else{
		$_players[$login]['FinishEventTime'] = $_currentTime;
	}
}


function playersPlayerBest($event,$login,$time){
	global $_debug,$_players;
	if(!isset($_players[$login]['ConnectionTime']))
		dropEvent();
	if($_debug>5) console("players{$event}('$login',$time)::");
}


function playersPlayerLap($event,$login,$laptime,$lapnum,$checkpt,$checkpts){
	global $_debug,$_players;
	if(!isset($_players[$login]['ConnectionTime']))
		dropEvent();
	if($_debug>0) console("players{$event}('$login',$laptime,$lapnum,$checkpt)::");
}


function playersPlayerIncoherence($event,$login){
	global $_debug,$_players;
	if(!isset($_players[$login]['ConnectionTime']))
		dropEvent();
	if($_debug>0) console("players{$event}('$login')::");
}


// ChallengeListModified($event,$curchalindex,$nextchalindex,$islistmodified): the challenge list or indexes have changed
// (from TrackMania.ChallengeListModified callback)
function playersChallengeListModified($event,$curchalindex,$nextchalindex,$islistmodified){
	global $_debug,$_is_relay,$_players_round_restarting,$_last_matchsettings,$_StatusCode;
	if($_is_relay)
		return;

	// save current matchsettings
	addCall(true,'SaveMatchSettings',$_last_matchsettings);

	//if($_debug>0) console("playersChallengeListModified($curchalindex,$nextchalindex,$islistmodified)");
	if($_players_round_restarting && $_StatusCode >= 5){
		if($_debug>0) console("playersChallengeListModified:: while round_restarting, make a restart !!! ($curchalindex,$nextchalindex,$islistmodified)");
		addCall(true,'ChallengeRestart');
	}
}


function playersChallengeListChange($event){
	global $_debug,$_is_relay,$_players_round_restarting,$_StatusCode;
	if($_is_relay)
		return;

	//if($_debug>0) console("playersChallengeListChange::");
	if($_players_round_restarting && $_StatusCode >= 5){
		if($_debug>0) console("playersChallengeListChange:: while round_restarting !!!");
		addCall(true,'ChallengeRestart');
	}
}


function playersBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
	global $_debug,$_mldebug,$_memdebug,$_memdebugmode,$_players,$_players_old,$_PlayerList,$_PlayerInfo,$_StatusCode,$_Ranking,$_NumberOfChecks,$_players_positions,$_currentTime,$_last_matchsettings,$_players_round_current,$_DegradedModePlayers,$_DegradedMode2Players,$_DegradedMode,$_OrigVehicleNetQuality,$_ServerOptions,$_OrigIsP2PUpload,$_OrigIsP2PDownload,$_LastCheckNum,$_is_relay,$_players_roundplayed_current,$_players_round_finished,$_players_round_restarting,$_players_prev_map_uid,$_players_rounds_scores,$_players_round_time,$_GameInfos,$_WarmUp,$_FWarmUp,$_FWarmUpState,$_FWarmUpDuration,$_NextFWarmUp,$_PlayerList,$_match_map;

	if($_players_round_restarting){
		if($_players_prev_map_uid == $ChallengeInfo['UId']){
			dropEvent();
			return;
		}else{
			console("playersBeginRace:: round_restarting failed: map uid changed !!! ({$_players_prev_map_uid} -> {$ChallengeInfo['UId']})");
			$_players_round_restarting = false;
		}
	}

	// degraded num of players : reduce logs to speedup script
	if(count($_PlayerList) > $_DegradedModePlayers){
		$_debug = 0;
		$_mldebug = 0;
		$_memdebug = 1;
	}

	// FWarmUp for current race ?
	if($_FWarmUp != $_NextFWarmUp){
		// if real warmup then no fwarmup !
		if($_NextFWarmUp > 0 && $_WarmUp)
			$_NextFWarmUp = 0;
		// update duration value
		if($_NextFWarmUp > 0)
			$_NextFWarmUp = $_FWarmUpDuration;

		$_FWarmUp = $_NextFWarmUp;
		$_FWarmUpState = 0;
		// send FWarmUp change to help showing the next state
		addEvent('FWarmUpChange',$_FWarmUp); // addEvent('FWarmUpChange',status)

		// update $fwarmup in event args if needed
		if($_FWarmUp != $fwarmup){
			global $_callFuncsArgs;
			$fwarmup = $_FWarmUp;
			$_callFuncsArgs[5] = $fwarmup;
		}
	}

	if($_debug>0) console("players{$event}({$ChallengeInfo['UId']}):: (Status={$_StatusCode},newcup={$newcup},warmup={$warmup},fwarmup={$fwarmup})");

	if(!$_is_relay){
		// save current matchsettings
		addCall(true,'SaveMatchSettings',$_last_matchsettings);
		// get infos for relay then send to them !
		getFastInfosForRelay(true);
	}

	// reload admins file
	loadAdmins();

	// begin race general init
	$_NumberOfChecks = 0;

	$_LastCheckNum = -1;
	$_players_round_current = 0;
	$_players_roundplayed_current = 0;
	$_players_round_finished = 0; 
	$_players_round_time = 0;

	$_players_positions = array();
	$_players_rounds_scores = array(0=>playersGetScores());

	if($_GameInfos['GameMode'] != CUP || $newcup){
		// reset teams scores unless in a running cup
		fteamsClearRanks();
	}

	// init at each beginning of race
	foreach($_players as $login => &$pl){
		if(!is_string($login))
			$login = ''.$login;
		$pl['BestCheckpoints'] = array();
		$pl['BestTime'] = -1;
		$pl['RBestTime'] = -1;
		$pl['BestLapTime'] = -1;
		$pl['Laps'] = array();
		$pl['CheckpointNumber'] = -1;
		$pl['NbrLapsFinished'] = -1;
		$pl['LapNumber'] = -1;
		$pl['LapCheckpoints'] = array();
		$pl['BestLapCheckpoints'] = array();
		if($pl['ChecksGaps'] != 'ideal')
			$pl['ChecksGaps'] = 'best';

		if($_GameInfos['GameMode'] != CUP || $newcup){
			// reset score unless in a running cup
			$pl['Score'] = 0;
		}
		$pl['FTeamPoints'] = 0;
		$pl['FTeamPointsList'] = array(0=>0);
		$pl['TeamScore'] = 0;
		$pl['TeamScores'] = array(0=>0);
		$pl['Position'] = array('Pos'=>-1,'Check'=>-1,
														'FirstLogin'=>'','FirstDiffTime'=>0,'FirstDiffCheck'=>0,
														'Prev2Login'=>'','Prev2DiffTime'=>0,'Prev2DiffCheck'=>0,
														'PrevLogin'=>'','PrevDiffTime'=>0,'PrevDiffCheck'=>0,
														'NextLogin'=>'','NextDiffTime'=>0,'NextDiffCheck'=>0,
														'Next2Login'=>'','Next2DiffTime'=>0,'Next2DiffCheck'=>0);
		$pl['RoundsPos'] = array();
		if($GameInfos['GameMode'] == TEAM){
			// no player ranking infos available in team mode !
			$pl['Rank'] = -1;
		}

		// if inactive (deconnected) then default other infos
		if(!$pl['Active']){ 
			$pl['FinalTime'] = -1;
			$pl['LastCpTime'] = -1;
			$pl['Rank'] = -1;
			$pl['Checkpoints'] = array();
			$pl['BestDate'] = -1;
			$pl['NbrLapsFinished'] = -1;
			$pl['PlayerId'] = -1;
			$pl['TeamId'] = -1;
			$pl['PlayerInfo'] = array();
			$pl['PlayRights'] = false;
			$pl['IsSpectator'] = false;
			$pl['SpectatorStatus'] = 0;

			if($_GameInfos['GameMode'] != CUP || $newcup){
				$pl['Score'] = -1;

				// move to $_players_old
				if($_debug>1) console("******* MOVE $login to _players_old !!! (Active=false)");
				$_players_old[$login] = $_players[$login];
				$pl = &$_players_old[$login];
				unset($_players[$login]);
				addEvent('PlayerRemove',$login,false);
			}

		}else{
			// verify if is really here in $_PlayerList, else move to $_players_old
			$found = false;
			for($i = 0; $i < count($_PlayerList); $i++){
				if($_PlayerList[$i]['Login'] === $login){
					$found = true;
					break;
				}
			}
			if(!$found && strlen($login) > 0){
				$pl['Active'] = false;
				$pl['ActiveTime'] = $_currentTime;
				$pl['FinalTime'] = -1;
				$pl['LastCpTime'] = -1;
				$pl['Rank'] = -1;
				$pl['Checkpoints'] = array();
				$pl['BestDate'] = -1;
				$pl['NbrLapsFinished'] = -1;
				$pl['PlayerId'] = -1;
				$pl['TeamId'] = -1;
				$pl['PlayerInfo'] = array();
				$pl['PlayRights'] = false;
				$pl['IsSpectator'] = false;
				$pl['SpectatorStatus'] = 0;

				// not in server PlayerList while was still active, send a PlayerDisconnect
				addEvent('PlayerDisconnect',$login); // addEvent('PlayerDisconnect',login)

				if($_GameInfos['GameMode'] != CUP || $newcup){
					$pl['Score'] = -1;

					// move to $_players_old
					if($_debug>1) console("******* MOVE $login to _players_old !!! (not in PlayerList)");
					$_players_old[$login] = $_players[$login];
					$pl = &$_players_old[$login];
					unset($_players[$login]);
					addEvent('PlayerRemove',$login,false);
				}

			}else{
				// player here, update status
				if($pl['Active']){
					$oldstatus = $pl['Status'];
					$status = ($pl['SpectatorStatus'] > 0) ? 1 : 0;
					if($pl['Status'] != $status){
						$pl['Status'] = $status;
						insertEvent('PlayerStatusChange',$login,$pl['Status'],$oldstatus); // insertEvent('PlayerStatusChange',login,status,oldstatus)
					}
					$oldstatus2 = $pl['Status2'];
					if($pl['Status2'] != $status){
						$pl['Status2'] = $status;
						insertEvent('PlayerStatus2Change',$login,$pl['Status2'],$oldstatus2); // insertEvent('PlayerStatus2Change',login,status2,oldstatus2)
					}
				}
			}
		}
	}

	if($_GameInfos['GameMode'] != CUP || $newcup){
		// remove too old players (4h to 30min depending of number) from $_players_old and $_PlayerInfo
		$old = count($_players_old) < 20 ? 14400000 : ( count($_players_old) < 40 ? 7200000 : ( count($_players_old) < 60 ? 3600000 : 1800000 ) );
		foreach($_players_old as $login => &$pl){
			if(!is_string($login))
				$login = ''.$login;
			if(($_currentTime - $pl['ActiveTime']) > $old){
				if($_debug>1) console("******* REMOVE $login from _players_old !!! (".date("m/d,H:i:s",$pl['ActiveTime']/1000).")");
				if(isset($_PlayerInfo[$login]))
					unset($_PlayerInfo[$login]);
				unset($_players_old[$login]);
				addEvent('PlayerRemove',$login,true);
				
			}else{
				$pl['Score'] = -1;
			}
		}
	}
	//debugPrint("playersBeginRace - _players_old",$_players_old);
	//debugPrint("playersBeginRace - _players",$_players);

	// degraded mode off
	if($_DegradedMode > 0 && count($_players) < $_DegradedModePlayers){
		if($_debug>0) console("Degraded mode OFF (less than $_DegradedModePlayers players)");
		$_DegradedMode = 0;
		if($_OrigVehicleNetQuality > 0)
			addCall(null,'SetVehicleNetQuality',1);
		if($_OrigIsP2PUpload)
			addCall(null,'EnableP2PUpload',true);
		if($_OrigIsP2PDownload)
			addCall(null,'EnableP2PDownload',true);

	}elseif($_DegradedMode > 1 && count($_players) < $_DegradedMode2Players){
		if($_debug>0) console("Degraded mode level 1 (less than $_DegradedMode2Players players)");
		$_DegradedMode = 1;
	}

	playersTestGameFix(true);

	// remove inactive players if needed
	if($_match_map < 0 || $_GameInfos['GameMode'] != CUP || $newcup)
		fteamsCheckInactive(true,'BeginRace');

  if($_memdebug>0 ){
		$n = count($_players)+count($_players_old);
		$mem = memory_get_usage($_memdebugmode);
    console("###mem: $mem  ($n players)");
	}
}


function playersBeginRace_Post($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
	global $_debug,$_players_round_restarting,$_players_prev_map_uid,$_NextFWarmUp,$_players,$_fteams_on,$_fteams_rules,$_fteams_max,$_PrevFGameMode,$_FGameModes;
	if($_players_round_restarting)
		return;
	
	foreach($_players as $login => &$pl){
		// set player free if was previously a fteam mode
		if(!$_fteams_on && $pl['ForceSpectator'] > 0 && isset($_FGameModes[$_PrevFGameMode]['FTeams']) && !$_FGameModes[$_PrevFGameMode]['FTeams']){
			addCall($login,'ForceSpectator',''.$login,2); // play
			addCall($login,'ForceSpectator',''.$login,0); // playerfree
		}
	}
}


function playersBeginChallenge($event,$ChallengeInfo,$GameInfos){
	global $_debug,$_players_round_restarting;
	if($_players_round_restarting){
		dropEvent();
		return;
	}
}


function playersEndResult($event){
	global $_debug,$_players_round_restarting;
	if($_players_round_restarting){
		dropEvent();
		return;
	}
}


function playersEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup){
	global $_debug,$_players,$_StatusCode,$_players_firstmap,$_currentTime,$_WarmUp,$_FWarmUp,$_FWarmUpState,$_NextFWarmUp,$_FWarmUpDuration,$_players_round_restarting,$_players_round_restarting_wu,$_players_prev_map_uid,$_players_round_time,$_NextGameInfos;
	if($_players_round_restarting){
		// special restarting made a challenge restart : be sure to have a restart !
		$_players_prev_map_uid = $ChallengeInfo['UId'];
		addCall(true,'ChallengeRestart');
		// if was warmup then set it again
		if($_players_round_restarting_wu)
			addCallDelay(2000,null,'SetWarmUp',true);
		dropEvent();
		return;
	}
	if($_debug>0) console("players{$event}({$ChallengeInfo['UId']}):: (Status={$_StatusCode},contcup={$continuecup},warmup={$warmup},fwarmup={$fwarmup})");
	//debugPrint("playersEndRace - Ranking",$Ranking);
	//debugPrint("playersEndRace - _players",$_players);
	players_count_languages();
	// keep race final positions
	$_players_firstmap = false;
	$_players_round_time = 0;

	// end of race, update players Status
	foreach($_players as $login => &$pl){
		if($pl['Active']){
			$oldstatus = $pl['Status'];
			if($pl['Status'] != 2){
				$pl['Status'] = 2;
				insertEvent('PlayerStatusChange',''.$login,$pl['Status'],$oldstatus); // insertEvent('PlayerStatusChange',login,status,oldstatus)
			}
			$oldstatus2 = $pl['Status2'];
			if($pl['Status2'] != 2 && !$_WarmUp){
				$pl['Status2'] = 2;
				insertEvent('PlayerStatus2Change',''.$login,$pl['Status2'],$oldstatus2); // insertEvent('PlayerStatus2Change',login,status2,oldstatus2)
			}
			if(isset($pl['toPlay']))
				unset($pl['toPlay']);
			if(isset($pl['toSpecPlay']))
				unset($pl['toSpecPlay']);
			if(isset($pl['toSpec']))
				unset($pl['toSpec']);
		}
	}

	// change to FWarmUp if wanted
	playersWarmUp2FWarmUp();

	// FWarmUp for next race ?
	if($_FWarmUp > 0){
		// FWarmUp end
		$_NextFWarmUp = 0;
		// send FWarmUp change to help showing the next state
		addEventDelay(2000,'FWarmUpChange',$_FWarmUp); // addEvent('FWarmUpChange',status)

	}elseif($_FWarmUpDuration > 0){
		$classicwud = $_NextGameInfos['GameMode'] == CUP ? $_NextGameInfos['CupWarmUpDuration'] : $_NextGameInfos['AllWarmUpDuration'];
		if($classicwud <= 0){
			// FWarmUp will start
			$_NextFWarmUp = $_FWarmUpDuration;
			$_FWarmUpState = 0;
			// send FWarmUp change to help showing the next state
			addEventDelay(3000,'FWarmUpChange',$_FWarmUp); // addEvent('FWarmUpChange',status)

		}else{
			$_NextFWarmUp = 0;
		}

	}else{
		$_NextFWarmUp = 0;
	}
}


function playersEndRace_Reverse($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_players_round_restarting,$_players_prev_map_uid,$_NextFWarmUp,$_players,$_fteams_on,$_fteams_rules,$_fteams_max,$_NextFGameMode,$_FGameModes;
	if($_players_round_restarting)
		return;

	// free players in fteam at end of map
	foreach($_players as $login => &$pl){
		if($_fteams_on && $pl['Active']){
			// set free players in teams
			if($_fteams_rules['MaxPlayingRule'] != 'strict' && $pl['FTeamId'] >= 0 && $pl['FTeamId'] < $_fteams_max){
				if($_fteams_rules['MaxPlayingRule'] != 'free')
					addCall($login,'ForceSpectator',$login,2); // play
				addCall($login,'ForceSpectator',$login,0); // playerfree
			}elseif(isset($_FGameModes[$_NextFGameMode]['FTeams']) || !$_FGameModes[$_NextFGameMode]['FTeams']){
				addCall($login,'ForceSpectator',$login,2); // play
				addCall($login,'ForceSpectator',$login,0); // playerfree
			}
		}
	}

	playersStoreFastState();
	$_players_prev_map_uid = $ChallengeInfo['UId'];
}


function playersBeforePlay($event,$delay){
	global $_debug,$_FWarmUp,$_FWarmUpState;
	//if($_debug>1) console("players{$event}($delay)::");

	if($delay == 0){
		// to show the change before round synchro
		if($_FWarmUp > 0){
			$_FWarmUpState++;
			addEvent('FWarmUpChange',$_FWarmUp); // addEvent('FWarmUpChange',status)
		}
	}
}


function playersBeginRound($event){
	global $_debug,$_players,$_StatusCode,$_GameInfos,$_players_checkchange,$_players_round_current,$_players_round_time,$_currentTime,$_LastCheckNum,$_players_round_sequence,$_PlayerList,$_players_round_finished,$_players_roundplayed_current,$_players_round_restarting,$_players_round_restarting_wu,$_players_round_restartplayers,$_WarmUp,$_FWarmUp,$_FWarmUpState,$_players_round_checkpoints,$_fteams_on,$_fteams,$_fteams_round;
	if($_debug>0) console("players{$event}:: (Status=$_StatusCode)");
	$_players_round_checkpoints = 0;

	// supposed real begining of round script time (approx >200ms if not callback mode)
	$_players_round_time = $_currentTime; 
	$_players_round_sequence = 0;
	if($_players_round_current < 1)
		$_players_round_current = 1;
	elseif(!$_players_round_restarting)
		$_players_round_current++;
	if($_players_roundplayed_current < 1)
		$_players_roundplayed_current = 1;
	$_players_round_finished = 0;
	$_players_checkchange = true;
	if($_players_round_restarting && $_players_round_restarting_wu && !$_WarmUp){
		addCall(null,'SetWarmUp',true);
		$_WarmUp = true;
	}
	$_players_round_restarting = false;
	$_players_round_restarting_wu = false;

	if($_FWarmUp > 0){
		// set FWarmUpState to the round number (ie 1 for non rounds modes)
		$_FWarmUpState = $_players_round_current;

		// in case BeforeEndRound would not have finished the warmup in rounds mode
		if($_FWarmUpState > $_FWarmUp){
			if($_debug>0) console("playersBeginRound:: FWarmUp finished (round {$_FWarmUpState}/{$_FWarmUp}) -> quickRestart");
			mapQuickRestart();
		}else{
			addEvent('FWarmUpChange',$_FWarmUp); // addEvent('FWarmUpChange',status)
		}
	}

	// init at each beginning of round
	foreach($_players as $login => &$pl){
		if(!is_string($login))
			$login = ''.$login;

		if(!$pl['IsSpectator'])
			$pl['PlayRights'] = true;

		// play time
		if(count($pl['Checkpoints']) > 0){
			//console("add playtime($login) : ".end($pl['Checkpoints']));
			$pl['PlayTime'] += end($pl['Checkpoints']);
		}

		$pl['CheckpointNumber'] = -1;
		$pl['LapNumber'] = -1;
		$pl['PrevLapNumber'] = 0;
		$pl['NbrLapsFinished'] = -1;
		$pl['FinalTime'] = -1;
		$pl['LastCpTime'] = -1;
		$pl['CPdelay'] = false;
		$pl['Laps'] = array();
		$pl['Checkpoints'] = array();
		$pl['LapCheckpoints'] = array();
		$pl['TeamScore'] = 0;
		$pl['FTeamPoints'] = 0;
		$pl['CPseq'] = 0;
		$pl['FinishEventTime'] = $_currentTime;
		$pl['RSdelays'] = 0;

		$pl['Position'] = array('Pos'=>-1,'Check'=>-1,
														'FirstLogin'=>'','FirstDiffTime'=>0,'FirstDiffCheck'=>0,
														'Prev2Login'=>'','Prev2DiffTime'=>0,'Prev2DiffCheck'=>0,
														'PrevLogin'=>'','PrevDiffTime'=>0,'PrevDiffCheck'=>0,
														'NextLogin'=>'','NextDiffTime'=>0,'NextDiffCheck'=>0,
														'Next2Login'=>'','Next2DiffTime'=>0,'Next2DiffCheck'=>0);
	}

	// update fteams scores
	if($_fteams_on){
		fteamsClearRoundRanks();
	}

	// compute begining positions
	playersComputePositions(-1);

	// test temporary spectators at beginning of 1st round
	if(($_GameInfos['GameMode'] != TA && $_GameInfos['GameMode'] != STUNTS) && $_players_round_current <= 1){
		$istmpspec = false;
		foreach($_players as $login => &$pl){
			//if($pl['Active'] && $pl['IsTemporarySpectator'] && !$pl['IsSpectator'] && !isset($_players_round_restartplayers[$login])){
			if($pl['Active'] && $pl['EarlySpecToPlay'] && !isset($_players_round_restartplayers[$login])){
				$pl['EarlySpecToPlay'] = false;
				$_players_round_restartplayers[$login] = $login;
				$istmpspec = true;
			}
		}
		if($istmpspec){
			// temporary spectators at beginning of round : restart round
			$msg = localeText(null,'server_message').localeText(null,'interact').'Restarting round... (become player: '.implode(', ',$_players_round_restartplayers).')';
			addCall(true,'ChatSendServerMessage', $msg);
			playersSpecialRoundRestart('temporary spectators: '.implode(',',$_players_round_restartplayers));
			dropEvent();
			return;
		}
	}
	$_players_round_restartplayers = array();
}


function playersBeginRound_Post($event){
	global $_players_round_restarting,$_fteams_on,$_fteams_ranksrule,$_fteams_drawrule,$_fteams_pointsrule,$_fteams_scoresrule,$_fteams_mapscoresrule,$_fteams_rules,$_roundspoints_points;
	if($_players_round_restarting)
		return;
	playersStoreFastState();

	// set fteams real points rule
	if($_fteams_on){
		$_fteams_ranksrule = $_fteams_rules['RanksRule'];
		$_fteams_drawrule = $_fteams_rules['DrawRule'];
		$_fteams_mapscoresrule = $_fteams_rules['MapScoresRule'];

		$_fteams_pointsrule = 'incremental';
		if($_fteams_rules['PointsRule'] == 'custom'){
			// custom points set by external plugin
			$_fteams_pointsrule = 'custom';
		}elseif(!is_string($_fteams_rules['PointsRule']) && is_array($_fteams_rules['PointsRule']) && isset($_fteams_rules['PointsRule'][0])){
			// array of int
			$_fteams_pointsrule = $_fteams_rules['PointsRule'];
		}elseif(isset($_roundspoints_points[$_fteams_rules['PointsRule']]) &&
						is_array($_roundspoints_points[$_fteams_rules['PointsRule']]) &&
						isset($_roundspoints_points[$_fteams_rules['PointsRule']][0])){
			// existing custom $_roundspoints_points['customname'] array
			$_fteams_pointsrule = $_roundspoints_points[$_fteams_rules['PointsRule']];
		}elseif(is_string($_fteams_rules['PointsRule'])){
			// string 25,20,... converted as array
			$_fteams_pointsrule = explode(',',$_fteams_rules['PointsRule']);
		}
		//console("playersBeginRound_Post:: fteams_rules['PointsRule']: {$_fteams_rules['PointsRule']} -> ".print_r($_fteams_pointsrule,true));

		$_fteams_scoresrule = 24;
		if(is_array($_fteams_rules['ScoresRule']) && isset($_fteams_rules['ScoresRule'][0])){
			// array of int
			$_fteams_scoresrule = $_fteams_rules['ScoresRule'];
		}elseif(isset($_roundspoints_points[$_fteams_rules['ScoresRule']]) &&
						is_array($_roundspoints_points[$_fteams_rules['ScoresRule']]) &&
						isset($_roundspoints_points[$_fteams_rules['ScoresRule']][0])){
			// existing custom $_roundspoints_points['customname'] array
			$_fteams_scoresrule = $_roundspoints_points[$_fteams_rules['ScoresRule']];
		}elseif(is_int($_fteams_rules['ScoresRule']) || is_numeric($_fteams_rules['ScoresRule'])){
			// single numeric value
			$_fteams_scoresrule = $_fteams_rules['ScoresRule']+0;
		}elseif(is_string($_fteams_rules['ScoresRule'])){
			if($_fteams_rules['ScoresRule'] == 'CPTime' || $_fteams_rules['ScoresRule'] == 'Points')
				$_fteams_scoresrule = $_fteams_rules['ScoresRule'];
			else
				$_fteams_scoresrule = explode(',',$_fteams_rules['ScoresRule']);
		}
		//console("playersBeginRound_Post:: fteams_rules['ScoresRule']: {$_fteams_rules['ScoresRule']} -> ".print_r($_fteams_scoresrule,true));
	}
}


function playersBeforeEndRound($event,$delay){
	global $_debug,$_players_round_restarting,$_GameInfos,$_FWarmUp,$_FWarmUpState,$_players_checkchange,$_players_round_current,$_players,$_players_positions,$_fteams_on,$_fteams,$_fteams_round,$_fteams_changes;
	if($_debug>1) console("players{$event}($delay)::");

	if($delay == 0){
		// first BeforeEndRound call for this end of round...
		if($_players_round_restarting)
			return;

		if($_FWarmUp > 0 && $_FWarmUpState >= $_FWarmUp){
			if($_GameInfos['GameMode'] == ROUNDS || $_GameInfos['GameMode'] == TEAM || $_GameInfos['GameMode'] == CUP){
				if($_debug>0) console("playersBeforeEndRound:: FWarmUp finished (round {$_FWarmUpState}/{$_FWarmUp}) -> quickRestart");
				mapQuickRestart();
				delayTransition(2000);
			}
		}

	}elseif($delay < 0){
		// last BeforeEndRound call for this end of round...
		if($_players_round_restarting)
			return;

		// update player positions list
		playersComputePositions(-3);

		// update fteams scores
		if($_fteams_on)
			fteamsUpdateMapScores();
		
		// update players team and fteam scores
		$pos = 1;
		foreach($_players_positions as &$plp){
			if(isset($_players[$plp['Login']]['RoundsPos'])){
				$pl2 = &$_players[$plp['Login']];
				if($pl2['FinalTime'] > 0){
					$pl2['RoundsPos'][$_players_round_current] = $pos;
					$pl2['TeamScores'][$_players_round_current] = $pl2['TeamScore'];
					$pl2['TeamScores'][0] += $pl2['TeamScore'];
					$pl2['FTeamPointsList'][$_players_round_current] = $pl2['FTeamPoints'];
					$pl2['FTeamPointsList'][0] += $pl2['FTeamPoints'];
				}
			}
			$pos++;
		}

	}else{
		// delayed transition
		if($_debug>2) console("playersBeforeEndRound:: delayed ({$delay})");
	}
}


function playersEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_debug,$_players,$_StatusCode,$_GameInfos,$_WarmUp,$_FWarmUp,$_players_round_current,$_players_positions,$_LastCheckNum,$_players_missings,$_players_round_restarting,$_players_rounds_scores,$_players_antilock,$_players_round_restarting,$_players_firstround;

	// in Cup mode when round finish for map change, it can take time and release antilock...
	$_players_antilock = -15;
	$_players_firstround = false;

  // update player positions list
  //(just for race condition in case a EverySecond event playersComputePositions
	// came between BeforeEndRound and StatusCode change, and changed the right values !)
  playersComputePositions(-3);

	foreach($_players as $login => &$pl){
		$pl['EarlySpecToPlay'] = false;
		if(isset($pl['toPlay'])){
			if($pl['IsSpectator']){
				addCall(null,'ForceSpectator',''.$login,2); // play
				addCall(null,'ForceSpectator',''.$login,0); // playerfree
			}
			unset($pl['toPlay']);
		}
		if(isset($pl['toSpecPlay'])){
			if($pl['IsSpectator']){
				addCall(null,'ForceSpectator',''.$login,2); // play
				addCall(null,'ForceSpectator',''.$login,0); // playerfree
			}
			unset($pl['toSpecPlay']);
		}
		if(isset($pl['toSpec'])){
			if(!$pl['IsSpectator']){
				addCall(null,'ForceSpectator',''.$login,2); // spec
				addCall(null,'ForceSpectator',''.$login,0); // playerfree
			}
			unset($pl['toSpec']);
		}


		if($_GameInfos['GameMode'] == LAPS){
		}
	}

	if($_players_round_restarting){
		//dropEvent();
		return;
	}

	// store scores of round
	if(!isset($_players_rounds_scores[$_players_round_current])){
		$_players_rounds_scores[$_players_round_current] = playersGetScores();
	}
	
	// verify missing finishes !!! (should never happen, btw it occasionally happens)
	if($_LastCheckNum >= 0){
		$tmpevents = array();
		foreach($_players as $login => &$plt){
			if(!is_string($login))
				$login = ''.$login;
			if($plt['Active'] && $plt['FinalTime'] <= 0 && count($plt['Checkpoints']) > 0){
				$finaltime = end($plt['Checkpoints']);
				if(key($plt['Checkpoints']) == $_LastCheckNum && count($plt['Checkpoints']) == $_LastCheckNum){
					// missing finish !!! 
					$_players_missings++;
					if($_debug>0) console("Missing finish for $login, {$plt['FinalTime']} ! (EndRound) [{$_LastCheckNum}: ".implode(',',$plt['Checkpoints'])."]");
					$tmpevents[] = array('PlayerFinish',$login,$finaltime,$plt['Checkpoints']);
				}
			}
		}
		// if any missing finishes, send them and resend EndRound...
		if(count($tmpevents) > 0){
			dropEvent();
			console("Missing finishes : send them and resend EndRound...");
			insertEvent('EndRound',$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting);
			for($i = count($tmpevents)-1; $i >= 0; $i--)
				insertEventArray($tmpevents[$i]);
			return;
		}
	}

	// standard EndRound stuff...

	if($_debug>0) console("players{$event}:: (Status=$_StatusCode)");

	// end of warmup in Rounds/Team/Cup mode
	if($_WarmUp && !$_players_round_restarting && ($_GameInfos['GameMode'] == ROUNDS || $_GameInfos['GameMode'] == TEAM || $_GameInfos['GameMode'] == CUP)){
		if($_GameInfos['GameMode'] == CUP)
			$wuduration = $_GameInfos['CupWarmUpDuration'] > 0 ? $_GameInfos['CupWarmUpDuration'] : 1;
		else
			$wuduration = $_GameInfos['AllWarmUpDuration'] > 0 ? $_GameInfos['AllWarmUpDuration'] : 1;
		//console("playersEndRound:: Rounds/Team/Cup warmup : {$_players_round_current} / {$wuduration} ...");
		if($_players_round_current >= $wuduration){
			if($_players_round_current > $wuduration)
				console("playersEndRound:: force end of Rounds/Team/Cup warmup : {$_players_round_current} / {$wuduration} ...");
			addCall(null,'SetWarmUp',false);
		}
	}

	// change to FWarmUp if wanted
	playersWarmUp2FWarmUp();

	//debugPrint("playersEndRound - Ranking",$Ranking);
	//debugPrint("playersEndRound - _players",$_players);
}


function playersEndRound_Reverse($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_players_round_finished,$_players_round_current,$_players_roundplayed_current,$_players_round_restarting;
	if($_players_round_restarting)
		return;
	if($_players_round_finished > 0)
		$_players_roundplayed_current++;
}


function playersEvery5seconds($event){
	global $_debug,$_memdebug,$_memdebugs,$_memdebugmode,$_control_maxplayers,$_mem,$_players_round_restarting,$_players,$_players_old,$_ChatFloodRateMax;

	// handle chat floods per player : $_ChatFloodRateMax message / 5s (twice max in burst)
	foreach($_players as $login => &$pl){
		$pl['ChatFloodRate'] /= 1.2; // reduce player chat flood rate
		if($pl['ChatFloodRateIgnore'] && $pl['ChatFloodRate'] <= 1){
			if($_debug>0) console("playersEvery5s:: ChatFloodRate({$login}) => UnIgnore");
			$pl['ChatFloodRateIgnore'] = false;
			addCall(null,'UnIgnore',$login.'');
		}
	}


	if($_players_round_restarting){
		dropEvent();
		return;
	}
	// reload admins file
	loadAdmins();

	// control max players
	if($_control_maxplayers)
		playersControlMaxPlayers();
	
	// show php memory info
	if($_memdebug > 1){
		$mem = memory_get_usage($_memdebugmode);
		$dmem = $mem - $_mem;
		$_mem = $mem;
		$n = count($_players)+count($_players_old);
		if($dmem != 0 && $mem > (10000000+200000*$n)) console("##mem: $mem # $dmem ($n pl) # cms: {$_memdebugs['callMulti-sync']} # cmas: {$_memdebugs['callMulti-async']} # cmasr: {$_memdebugs['callMulti-async-resp']}");
	}
}


function playersPlayerCountPlayers(){
	global $_debug,$_players,$_players_actives,$_players_spec;
	$_players_actives = 0;
	$_players_spec = 0;
	foreach($_players as $login => &$pl){
		if(isset($pl['Active']) && $pl['Active']){
			$_players_actives++;
			if($pl['IsSpectator'])
				$_players_spec++;
		}
	}
}


// compute player positions and relative gaps
// Special $sec values when called by: BeginRound: -1, PlayerFinish: -2, BeforeEndRound: -3
function playersComputePositions($sec=-1){
	global $_debug,$_players_checkchange,$_players,$_players_positions,$_players_actives,$_players_spec,$_players_giveup,$_players_giveup2,$_players_playing,$_currentTime,$_players_finished,$_GameInfos,$_teams,$_PlayerList,$_GameInfos,$_StatusCode,$_players_antilock,$_players_round_restarting,$_players_round_time,$_MFCTransition,$_fteams_max,$_fteams_on,$_fteams_rules,$_fteams,$_fteams_round,$_fteams_pointsrule,$_fteams_scoresrule;

	$_players_checkchange = false;

	$old_giveup2 = $_players_giveup2;

	// build $_players_positions array
	$_players_actives = 0;
	$_players_spec = 0;
	$_players_giveup = 0;
	$_players_giveup2 = 0;
	$_players_finished = 0;
	$_players_playing = 0;
	$_players_positions = array();
	foreach($_players as $login => &$pl){
		if(!is_string($login))
			$login = ''.$login;
		if(isset($pl['Active']) && $pl['Active']){
			$_players_actives++;
			if($pl['IsSpectator'])
				$_players_spec++;

			// workaround to make an event when Force spec/play state changes
			if($pl['ForcedOld'] != $pl['Forced'])
				insertEvent('PlayerSpecChange',$login,$pl['IsSpectator'],$pl['SpectatorStatus'],$pl['SpectatorStatus']); // insertEvent('PlayerSpecChange',login,isspec,specstatus)
		}
		if(!$pl['IsSpectator'] && $pl['FinalTime'] < 0)
			$_players_playing++;
		else if($pl['FinalTime'] == 0)
		  $_players_giveup2++;

		if(isset($pl['Checkpoints'][0])){
			$time = end($pl['Checkpoints']);
			$key = key($pl['Checkpoints']);
			$_players_positions[] = array('Login'=>$login,'NickName'=>$pl['NickName'],
																		'NickDraw'=>$pl['NickDraw'],'NickDraw2'=>$pl['NickDraw2'],
																		'Check'=>$key,'Time'=>$time,'Checkpoints'=>$pl['Checkpoints'],
																		'Change'=>0,'FinalTime'=>$pl['FinalTime'],'Rank'=>$pl['Rank'],'CPseq'=>$pl['CPseq'],
																		'Score'=>$pl['Score'],'BestTime'=>$pl['BestTime'],
																		'FTeamId'=>$pl['FTeamId'],'FTeamPos'=>-1,'FTeamPoints'=>0);
			if($pl['FinalTime'] > 0){
				if($_GameInfos['GameMode'] != LAPS || $pl['NbrLapsFinished'] >= $_GameInfos['LapsNbLaps'])
					$_players_finished++;
			}else if($pl['FinalTime'] == 0)
				$_players_giveup++;
		}
	}
	// sort $_players_positions
	usort($_players_positions,'playersPosCompare');

	// no give-up (del) count for time attack and stunts
	if($_GameInfos['GameMode'] == TA || $_GameInfos['GameMode'] == STUNTS){
		$_players_giveup = 0;
		$_players_giveup2 = 0;
	}

	//if($_debug>4) debugPrint("playersComputePositions - _players_positions",$_players_positions);

	// set position, first, prev and next infos for all
	$prev2pl = null;
	$prev2position = null;
	$prev2login = '';
	$prev2dcheck = 0;
	$prev2dtime = 0;

	$prevpl = null;
	$prevposition = null;
	$prevlogin = '';
	$prevdcheck = 0;
	$prevdtime = 0;

	$firstpl = null;
	$firstposition = null;
	$firstlogin = '';
	$firstdcheck = 0;
	$firstdtime = 0;

	foreach($_players_positions as $pos => &$plp){
		$login = $plp['Login'];
		$position = &$_players[$login]['Position'];

		// prev2
		$prev2dtime = 0;
		$prev2dcheck = 0;
		if($prev2pl !== null){
			if($prev2pl['FinalTime'] == 0 || $plp['FinalTime'] == 0){
				$prev2dcheck = false;
			}else{
				// more than 1 is a real check gap
				$prev2dcheck = $prev2pl['Check'] - $plp['Check'] - 1;
				if($prev2dcheck < 0)
					$prev2dcheck = 0;
				// base gap is diff between times at the same check
				$prev2dtime = $plp['Time'] - $prev2pl['Checkpoints'][$plp['Check']];
			}
		}
		$plp['Prev2DiffTime'] = $prev2dtime;

		// prev
		$prevdtime = 0;
		$prevdcheck = 0;
		if($prevpl !== null){
			if($prevpl['FinalTime'] == 0 || $plp['FinalTime'] == 0){
				$prevdcheck = false;
			}else{
				// more than 1 is a real check gap
				$prevdcheck = $prevpl['Check'] - $plp['Check'] - 1;
				if($prevdcheck < 0)
					$prevdcheck = 0;
				// base gap is diff between times at the same check
				$prevdtime = $plp['Time'] - $prevpl['Checkpoints'][$plp['Check']];
			}
		}
		$plp['PrevDiffTime'] = $prevdtime;

		// first
		$firstdtime = 0;
		$firstdcheck = 0;
		if($firstpl !== null){
			if($firstpl['FinalTime'] == 0 || $plp['FinalTime'] == 0){
				$firstdcheck = false;
			}else{
				// more than 1 is a real check gap
				$firstdcheck = $firstpl['Check'] - $plp['Check'] - 1;
				if($firstdcheck < 0)	
					$firstdcheck = 0;
				// base gap is diff between times at the same check
				$firstdtime = $plp['Time'] - $firstpl['Checkpoints'][$plp['Check']];
			}
		}else{
			$firstlogin = $login;
			$firstposition = &$position;
			$firstdcheck = 0;
			$firstdtime = 0;
			$firstpl = &$plp;
		}
		$plp['FirstDiffCheck'] = $firstdcheck;
		$plp['FirstDiffTime'] = $firstdtime;

		// set position
		if($position['Pos'] != $pos){
			$plp['Change'] |= 1;
			$position['Pos'] = $pos;
		}
		if($position['Check'] != $plp['Check']){
			$plp['Change'] |= 2;
			$position['Check'] = $plp['Check'];
		}

		// set prev2 infos
		if($position['Prev2Login'] != $prev2login || 
			 $position['Prev2DiffTime'] != -$prev2dtime ||
			 $position['Prev2DiffCheck'] != -$prev2dcheck){
			$plp['Change'] |= 32;
			$position['Prev2Login'] = $prev2login;
			$position['Prev2DiffTime'] = -$prev2dtime;
			$position['Prev2DiffCheck'] = -$prev2dcheck;
		}

		// set prev infos
		if($position['PrevLogin'] != $prevlogin || 
			 $position['PrevDiffTime'] != -$prevdtime ||
			 $position['PrevDiffCheck'] != -$prevdcheck){
			$plp['Change'] |= 4;
			$position['PrevLogin'] = $prevlogin;
			$position['PrevDiffTime'] = -$prevdtime;
			$position['PrevDiffCheck'] = -$prevdcheck;
		}

		// set first infos
		if($position['FirstLogin'] != $firstlogin || 
			 $position['FirstDiffTime'] != -$firstdtime ||
			 $position['FirstDiffCheck'] != -$firstdcheck){
			$plp['Change'] |= 16;
			$position['FirstLogin'] = $firstlogin;
			$position['FirstDiffTime'] = -$firstdtime;
			$position['FirstDiffCheck'] = -$firstdcheck;
		}

		// set next infos of previous player
		if($prevposition !== null){
			if($prevposition['NextLogin'] != $login || 
				 $prevposition['NextDiffTime'] != $prevdtime || 
				 $prevposition['NextDiffCheck'] != $prevdcheck){
					
				$prevpl['Change'] |= 8;
				$prevposition['NextLogin'] = $login;
				$prevposition['NextDiffTime'] = $prevdtime;
				$prevposition['NextDiffCheck'] = $prevdcheck;
			}
		}

		// set next2 infos of previous2 player
		if($prev2position !== null){
			if($prev2position['Next2Login'] != $login || 
				 $prev2position['Next2DiffTime'] != $prev2dtime || 
				 $prev2position['Next2DiffCheck'] != $prev2dcheck){
					
				$prev2pl['Change'] |= 64;
				$prev2position['Next2Login'] = $login;
				$prev2position['Next2DiffTime'] = $prev2dtime;
				$prev2position['Next2DiffCheck'] = $prev2dcheck;
			}
		}

		$prev2pl = &$prevpl;
		$prev2position = &$prevposition;
		$prev2login = $prevlogin;

		$prevpl = &$plp;
		$prevposition = &$position;
		$prevlogin = $login;
	}



	// set next2 infos of penultimate player
	if($prev2position !== null){
		if($prev2position['Next2Login'] != ''){
			$prev2pl['Change'] |= 32;
			$prev2position['Next2Login'] = '';
			$prev2position['Next2DiffTime'] = 0;
			$prev2position['Next2DiffCheck'] = 0;
		}
	}

	// set next infos of last player
	if($prevposition !== null){
		if($prevposition['NextLogin'] != ''){
			$prevpl['Change'] |= 8;
			$prevposition['NextLogin'] = '';
			$prevposition['NextDiffTime'] = 0;
			$prevposition['NextDiffCheck'] = 0;
		}
	}
		
	// set next2 infos of last player
	if($prevposition !== null){
		if($prevposition['Next2Login'] != ''){
			$prevpl['Change'] |= 64;
			$prevposition['Next2Login'] = '';
			$prevposition['Next2DiffTime'] = 0;
			$prevposition['Next2DiffCheck'] = 0;
		}
	}
		
		
	//if($_debug>2) debugPrint("playersComputePositions - _players_positions",$_players_positions);

	// 'Change' value :
	//   1=position changed  (in TA: best rank changed)
	//   2=checkpoint changed
	//   4=diff with previous player changed  (not in TA)
	//   8=diff with next player changed  (not in TA)
	//  16=diff with first player changed  (in TA: best diff)
	//  32=diff with previous2 player changed  (not in TA)
	//  64=diff with next2 player changed  (not in TA)
	$change = 0;
	// send PlayerPositionChange event for players changes
	foreach($_players_positions as $pos => &$plp){
		if($plp['Change']  > 0){
			if(($plp['Change']&2) == 2){
				// try to prevent to send notice to player before this one (check change)
				if($_currentTime-$_players[$login]['NoticeTime'] > 1500)
					$_players[$login]['NoticeTime'] += 300;
			}
			// send change info
			addEvent('PlayerPositionChange',$plp['Login'],$plp['Change']); 
			$change |= $plp['Change'];
		}
	}
	if($change > 0 || $old_giveup2 != $_players_giveup2){
		// send change info (at least some change since last compute)
		addEvent('PlayerPositionChange',true,$change); 
	}
		
	// ------------
	// teams scores
	// ------------
	if($_GameInfos['GameMode'] == TEAM){
		$_teams[0]['Num'] = 0;
		$_teams[1]['Num'] = 0;
		$_teams[0]['Score'] = 0;
		$_teams[1]['Score'] = 0;
		$max = 0;
		foreach($_PlayerList as $key => &$pl2){
			if(($pl2['SpectatorStatus']%10) == 0){
				$max++;
				if($pl2['TeamId'] == 0)
					$_teams[0]['Num']++;
				elseif($pl2['TeamId'] == 1)
				$_teams[1]['Num']++;
			}	
		}

		if($max > $_GameInfos['TeamMaxPoints'])
			$max = $_GameInfos['TeamMaxPoints'];

		foreach($_PlayerList as $key => &$pl2){
			$login = $pl2['Login'];
			if(isset($_players[$login]['Active'])){
				if($_players[$login]['FinalTime'] != 0 && ($_players[$login]['FinalTime'] > 0 || $sec >= 0) &&
					 isset($_players[$login]['Position']['Pos']) && $_players[$login]['Position']['Pos'] >= 0){
					$score = $max - $_players[$login]['Position']['Pos'];
					if($score < 0)
						$score = 0;
					$_players[$login]['TeamScore'] = $score;
					if($pl2['TeamId'] == 0)
						$_teams[0]['Score'] += $score;
					elseif($pl2['TeamId'] == 1)
					$_teams[1]['Score'] += $score;
				}else{
					$_players[$login]['TeamScore'] = 0;
				}
			}
		}
	}

	fteamsComputePositions($sec);

	addEvent('RoundPositions',$sec);
}


// compute fteams positions
// unless special cases, it needs playersComputePositions first, so just call that one
// Special $sec values when called by: BeginRound: -1, PlayerFinish: -2, BeforeEndRound: -3
function fteamsComputePositions($sec){
	global $_debug,$_players_checkchange,$_players,$_players_positions,$_players_actives,$_players_spec,$_players_giveup,$_players_giveup2,$_players_playing,$_currentTime,$_players_finished,$_GameInfos,$_teams,$_PlayerList,$_GameInfos,$_StatusCode,$_players_antilock,$_players_round_restarting,$_players_round_time,$_MFCTransition,$_fteams_max,$_fteams_on,$_fteams_rules,$_fteams,$_fteams_round,$_fteams_pointsrule,$_fteams_scoresrule,$_FGameModes,$_FGameMode;

	// -------------
	// fteams scores
	// -------------
	if($_fteams_on){
		//console("playersFTeamsComputePositions:: update FTeams positions");
		/*
			$_players_positions[] = array('Login'=>$login,'NickName'=>$pl['NickName'],
																		'NickDraw'=>$pl['NickDraw'],'NickDraw2'=>$pl['NickDraw2'],
																		'Check'=>$key,'Time'=>$time,'Checkpoints'=>$pl['Checkpoints'],
																		'Change'=>0,'FinalTime'=>$pl['FinalTime'],'Rank'=>$pl['Rank'],'CPseq'=>$pl['CPseq'],
																		'Score'=>$pl['Score'],'BestTime'=>$pl['BestTime'],
																		'FTeamId'=>$pl['FTeamId'],'FTeamTeamPos'=>-1,'FTeamPos'=>-1,'FTeamPoints'=>0);
		*/
		// compute players positions in round, globally and within their team
		// (only players within the $_fteams_rules['MaxPlaying'] of their team will get a $plp['FTeamPos'] >= 0)
		$fteampos = 0;
		if(isset($_FGameModes[$_FGameMode]) && isset($_FGameModes[$_FGameMode]['PointsRuleFunc'])){
			if(!function_exists($_FGameModes[$_FGameMode]['PointsRuleFunc'])){
				console("fteamsComputePositions:: function {$_FGameModes[$_FGameMode]['PointsRuleFunc']}() does not exist !");
			}else{
				call_user_func($_FGameModes[$_FGameMode]['PointsRuleFunc']);
			}

		}else	if($_fteams_pointsrule !== 'custom'){

			foreach($_fteams_round as &$fteamr){
				$fteamr['RoundScore'] = 0;
				$fteamr['RoundPoints'] = 0;
				$fteamr['RoundCPs'] = 0;
				$fteamr['RoundTime'] = 0;
				$fteamr['NbPlaying'] = 0;
				$fteamr['TmpNbPlayers'] = 0;
				$fteamr['BestPlayerPos'] = 1000;
			}
			$ppos = 1;
			foreach($_players_positions as $pos => &$plp){
				$fteamid = $plp['FTeamId'];
				if($fteamid >= 0 && $fteamid < $_fteams_max && $plp['Check'] >= 0){
					$plp['FTeamTeamPos'] = $_fteams_round[$fteamid]['TmpNbPlayers']++;
					if($_fteams_rules['MaxPlaying'] <= 0 || $plp['FTeamTeamPos'] < $_fteams_rules['MaxPlaying'])
						$plp['FTeamPos'] = $fteampos++;
					if($ppos < $_fteams_round[$fteamid]['BestPlayerPos'])
						$_fteams_round[$fteamid]['BestPlayerPos'] = $ppos;
				}
				$ppos++;
			}

			// compute fteams points for players and teams
			foreach($_players_positions as $pos => &$plp){
				if($plp['FTeamPos'] >= 0){
					
					if($plp['FinalTime'] == 0 && (($_fteams_rules['FinishBonusPoints'] < 0 && $_fteams_rules['NotFinishMultiplier'] < 0) || $plp['Check'] < 1)){
						// not finished without bonus (or not passed 2 cps min)
						$plp['FTeamPoints'] = 0;
						if($_debug>0&&$sec<=-3 || $_debug>4&&$sec>-3) console("fteamsComputePositions:: {$plp['Login']},{$plp['FinalTime']},{$plp['Check']},{$plp['FTeamPos']} => not finished without bonus => 0 team pts");
						
					}elseif(is_array($_fteams_pointsrule)){
						// $_fteams_pointsrule : array points
						if(isset($_fteams_pointsrule[$plp['FTeamPos']]))
							$plp['FTeamPoints'] = $_fteams_pointsrule[$plp['FTeamPos']]+0;
						else
							$plp['FTeamPoints'] = end($_fteams_pointsrule)+0;
						if($_debug>0&&$sec<=-3 || $_debug>4&&$sec>-3) console("fteamsComputePositions:: {$plp['Login']},ft={$plp['FinalTime']},cp={$plp['Check']},lcpt={$plp['Checkpoints'][$plp['Check']]},t={$plp['Time']},sc={$plp['Score']},t.pos={$plp['FTeamPos']} => t.pts={$plp['FTeamPoints']} (from array)");
						
					}else{
						// $_fteams_pointsrule : incremental points
						$plp['FTeamPoints'] = $fteampos - $plp['FTeamPos'];
						if($_debug>0&&$sec<=-3 || $_debug>4&&$sec>-3) console("fteamsComputePositions:: {$plp['Login']},ft={$plp['FinalTime']},cp={$plp['Check']},lcpt={$plp['Checkpoints'][$plp['Check']]},t={$plp['Time']},sc={$plp['Score']},t.pos={$plp['FTeamPos']} => t.pts={$plp['FTeamPoints']} (incremental)");
					}

					// finish bonuses or multipliers
          if($sec >= -2 && $_StatusCode == 4){
            // not finished race : consider that all will finish !
            if($_fteams_rules['FinishBonusPoints'] > 0){
              $plp['FTeamPoints'] += $_fteams_rules['FinishBonusPoints'];
							if($_debug>4&&$sec>-3) console("fteamsComputePositions:: {$plp['Login']},ft={$plp['FinalTime']},cp={$plp['Check']},lcpt={$plp['Checkpoints'][$plp['Check']]},t={$plp['Time']},sc={$plp['Score']},t.pos={$plp['FTeamPos']} => t.pts={$plp['FTeamPoints']} (FinishBonusPoints while playing)");
						}

          }else{
            // finished : consider real finished/not finished state !
						if($plp['FinalTime'] > 0 && $_fteams_rules['FinishBonusPoints'] > 0){
							$plp['FTeamPoints'] += $_fteams_rules['FinishBonusPoints'];
							if($_debug>0&&$sec<=-3 || $_debug>4&&$sec>-3) console("fteamsComputePositions:: {$plp['Login']},ft={$plp['FinalTime']},cp={$plp['Check']},lcpt={$plp['Checkpoints'][$plp['Check']]},t={$plp['Time']},sc={$plp['Score']},t.pos={$plp['FTeamPos']} => t.pts={$plp['FTeamPoints']} (FinishBonusPoints)");
							
						}else if($plp['FinalTime'] <= 0 && $_fteams_rules['NotFinishMultiplier'] > 0){
							$plp['FTeamPoints'] = (int) ceil($plp['FTeamPoints'] * $_fteams_rules['NotFinishMultiplier']);
							if($_debug>0&&$sec<=-3 || $_debug>4&&$sec>-3) console("fteamsComputePositions:: {$plp['Login']},ft={$plp['FinalTime']},cp={$plp['Check']},lcpt={$plp['Checkpoints'][$plp['Check']]},t={$plp['Time']},sc={$plp['Score']},t.pos={$plp['FTeamPos']} => t.pts={$plp['FTeamPoints']} (NotFinishMultiplier)");
						}
					}

					$_players[$plp['Login']]['FTeamPoints'] = $plp['FTeamPoints'];
					$_fteams_round[$plp['FTeamId']]['RoundPoints'] += $plp['FTeamPoints'];
					$_fteams_round[$plp['FTeamId']]['RoundCPs'] += $plp['Check']+1;
					$_fteams_round[$plp['FTeamId']]['RoundTime'] += $plp['Time'];
					$_fteams_round[$plp['FTeamId']]['NbPlaying']++;
				}else{
					$_players[$plp['Login']]['FTeamPoints'] = 0;
				}
			}
		}
		$nbranks = fteamsSortRoundRanks();

		if($_fteams_scoresrule !== 'CPTime'){
			// compute fteams scores in current round
			$scorerank = 0;
			$prevrank = 0;
			$drawteams = array();
			foreach($_fteams_round as $ftid => &$fteamr){
				
				if($fteamr['RoundRank'] >= 1000){
					$fteamr['RoundScore'] = 0;

				}elseif(is_array($_fteams_scoresrule)){
					// $_fteams_scoresrule : array points
					if(isset($_fteams_scoresrule[$scorerank]))
						$fteamr['RoundScore'] = $_fteams_scoresrule[$scorerank]+0;
					else
						$fteamr['RoundScore'] = end($_fteams_scoresrule)+0;

				}elseif(is_int($_fteams_scoresrule)){
					$maxrank = $nbranks - 1;
					if($_fteams_scoresrule > 0){
						// $_fteams_scoresrule : int range scores
						if($maxrank > 0)
							$fteamr['RoundScore'] = (int) ceil(($maxrank - $scorerank) * $_fteams_scoresrule / $maxrank);
						else
							$fteamr['RoundScore'] = $_fteams_scoresrule;

					}else{
						// $_fteams_scoresrule : int incremental scores
						if($maxrank > 0)
							$fteamr['RoundScore'] = ($maxrank - $scorerank) * -$_fteams_scoresrule;
						else
							$fteamr['RoundScore'] = -$_fteams_scoresrule;
					}
				}else{
					// points
					$fteamr['RoundScore'] = $fteamr['RoundPoints'];
				}

				if($prevrank == $fteamr['RoundRank']){
					// draw : add info for average
					$drawteams[$ftid] = array('points'=>$fteamr['RoundPoints'],'rank'=>$fteamr['RoundRank'],'score'=>$fteamr['RoundScore']);

				}else{
					if(count($drawteams) > 1){
						// compute average score
						fteamsRoundDrawScores($drawteams);
					}
					$drawteams = array($ftid=>array('rank'=>$fteamr['RoundRank'],'score'=>$fteamr['RoundScore']));
				}
				
				$scorerank++;
				if($scorerank >= $nbranks)
					break;
				$prevrank = $fteamr['RoundRank'];
			}
			if(count($drawteams) > 1){
				// compute average score
				fteamsRoundDrawScores($drawteams);
			}
			
			//$_fteams[$plp['FTeamId']]['RoundScore'] += $plp['FTeamPoints'];
		}

		// debug positions/scores
		if($_debug>0 && $sec<=-3 || $_debug>4 && $sec>-3){
			foreach($_fteams_round as $ftid => &$fteamr){
				if($fteamr['CP0Players'] > 0) 
					console("fteamsComputePositions:: Team:{$ftid},RCPs={$fteamr['RoundCPs']},RTime={$fteamr['RoundTime']},RPoints={$fteamr['RoundPoints']},NbPl={$fteamr['NbPlaying']},BestPlPos={$fteamr['BestPlayerPos']},RSortRank={$fteamr['RoundSortRank']},RRank={$fteamr['RoundRank']},RScore={$fteamr['RoundScore']}");
			}
			//debugPrint("playersComputePositions($sec):: _fteams_round",$_fteams_round);		
			//debugPrint("playersComputePositions($sec):: _fteams",$_fteams);		
		}

	}
}


// -----------------------------------
// set to fteams having the same round rank the Average/Highest/Lowest of their scores
// used by fteamsComputePositions()
// -----------------------------------
function fteamsRoundDrawScores($drawteams){
	global $_fteams_round,$_fteams_drawrule;
	$nb = count($drawteams);
	if($nb > 1){
		$drawteam1 = reset($drawteams);

		$hrank = $drawteam1['rank'];
		$lrank = $drawteam1['rank'] + $nb - 1;
		$sumranks = ($hrank + $lrank) * $nb / 2;

		$hscore = $drawteam1['score'];
		$lscore = $drawteam1['score'];
		$sumscore = 0;

		foreach($drawteams as $ftid => $drawteam){
			if($drawteam['score'] > $hscore)
				$hscore = $drawteam['score'];
			if($drawteam['score'] < $lscore)
				$lscore = $drawteam['score'];
			$sumscore += $drawteam['score'];
		}

		$averagerank =   (int)floor($sumranks / $nb);
		$averagescore =  (int)ceil($sumscore / $nb);

		foreach($drawteams as $ftid => $drawteam){

			if($_fteams_drawrule == 'Average'){
				$_fteams_round[$ftid]['RoundRank'] = $averagerank;
				$_fteams_round[$ftid]['RoundScore'] = $averagescore;

			}else if($_fteams_drawrule == 'Highest'){
				$_fteams_round[$ftid]['RoundRank'] = $hrank;
				$_fteams_round[$ftid]['RoundScore'] = $hscore;

			}else if($_fteams_drawrule == 'Lowest'){
				$_fteams_round[$ftid]['RoundRank'] = $lrank;
				$_fteams_round[$ftid]['RoundScore'] = $lscore;
			}
		}
	}
}


//--------------------------------------------------------------
// sort fteams round, based on scores or times depending on $_fteams_ranksrule (including match ones)
// return the number of teams having round points > 0 or cp > 0 (so playing)
// used by fteamsComputePositions()
//--------------------------------------------------------------
function fteamsSortRoundRanks(){
	global $_fteams_rules,$_fteams_ranksrule,$_fteams_drawrule,$_fteams_round,$_FGameModes,$_FGameMode,$_fteams_round_changes;
	
	// sort teams based on score etc.
	uasort($_fteams_round,'fteamsSortTeamsRound');

	$rank = 1;
	$nbrank = 0;
	$prevrank = 0;
	$prevpoints = 0;
	$prevcps = 0;
	$prevtime = 0;
	foreach($_fteams_round as &$fteamr){
		$oldrank = $fteamr['RoundRank'];

		if($_fteams_ranksrule != 'CPTime'){
			// points based
			if($fteamr['RoundPoints'] > 0){
				$nbrank++;
				if($_fteams_drawrule != 'Bestplayer' && $_fteams_drawrule != 'PreviousRank' && $prevrank > 0 && $prevpoints == $fteamr['RoundPoints'])
					// same as prev : get prev rank
					$fteamr['RoundRank'] = $prevrank;
				else
					$fteamr['RoundRank'] = $rank;
				$fteamr['RoundSortRank'] = $rank;
			}else{
				$fteamr['RoundRank'] = 1000;
				$fteamr['RoundSortRank'] = 1000;
			}
			$prevpoints = $fteamr['RoundPoints'];
				
		}else{
			// CPs/Time based
			if($fteamr['RoundCPs'] > 0 && $fteamr['RoundTime'] > 0){
				$nbrank++;
				if($_fteams_drawrule != 'Bestplayer' && $_fteams_drawrule != 'PreviousRank' && $prevrank > 0 && $prevcps == $fteamr['RoundCPs'] && $prevtime == $fteamr['RoundTime'])
					// same as prev : get prev rank
					$fteamr['RoundRank'] = $prevrank;
				else
					$fteamr['RoundRank'] = $rank;
				$fteamr['RoundSortRank'] = $rank;
			}else{
				$fteamr['RoundRank'] = 1000;
				$fteamr['RoundSortRank'] = 1000;
			}

			$prevcps = $fteamr['RoundCPs'];
			$prevtime = $fteamr['RoundTime'];
		}

		if($fteamr['RoundRank'] != $oldrank)
			$_fteams_round_changes = true;
		$prevrank = $fteamr['RoundRank'];
		$rank++;
	}
	return $nbrank;
}


// -----------------------------------
// compare function for uasort, return -1 if $a should be before $b
// used by fteamsSortRoundRanks()
// -----------------------------------
function fteamsSortTeamsRound($a, $b){
	global $_fteams_ranksrule,$_fteams_drawrule;

	if($_fteams_ranksrule != 'CPTime'){
		// compare points
		if($a['RoundPoints'] > $b['RoundPoints'])
			return -1;
		else if($a['RoundPoints'] < $b['RoundPoints'])
			return 1;
		
	}else{
		// compare CPs
		if($a['RoundCPs'] > $b['RoundCPs'])
			return -1;
		else if($a['RoundCPs'] < $b['RoundCPs'])
			return 1;
		// same CPs, compare times
		if($a['RoundTime'] < $b['RoundTime'])
			return -1;
		else if($a['RoundTime'] > $b['RoundTime'])
			return 1;
	}
	if($_fteams_drawrule == 'Bestplayer'){
		// if Bestplayer secondary sort is wanted, test it
		if($a['BestPlayerPos'] < $b['BestPlayerPos'])
			return -1;
		else if($a['BestPlayerPos'] < $b['BestPlayerPos'])
			return 1;
	}
	// same points or cps/times, compare previous rank
	if($a['RoundSortRank'] < $b['RoundSortRank'])
		return -1;
	else if($a['RoundSortRank'] < $b['RoundSortRank'])
		return 1;
	// same all
	if($a['Tid'] < $b['Tid'])
		return -1;
	return 1;
}


function playersFTeamsChange_Reverse($event){
	global $_debug,$_fteams,$_fteams_max,$_fteams_changes;
	// after other got event : reset changed flag in all teams
	for($teamid = 0; $teamid < $_fteams_max; $teamid++)
		$_fteams[$teamid]['Changed'] = false;
	$_fteams_changes = false;
}


// test locked round
// test end of warmup based on time
// compute player positions and relative gaps
function playersEverysecond($event,$sec){
	global $_debug,$_players_checkchange,$_players,$_players_positions,$_players_actives,$_players_spec,$_players_giveup,$_players_giveup2,$_players_playing,$_currentTime,$_players_finished,$_GameInfos,$_teams,$_PlayerList,$_GameInfos,$_StatusCode,$_players_antilock,$_players_round_restarting,$_WarmUp,$_FWarmUp,$_players_round_time,$_MFCTransition,$_fteams_changes;

	if($_fteams_changes){
		if($_debug>5) console("playersEverysecond:: fteams_changes");
		$_fteams_changes = false;
		addEvent('FTeamsChange');
	}

	if($_players_round_restarting){
		dropEvent();
		return;
	}

	// control that round is not locked by temporary players
	// (can happen essentially in rare cases after using ForceSpectator during synchro)
	if($_StatusCode == 4 && $_GameInfos['GameMode'] != TA && $_GameInfos['GameMode'] != STUNTS){
		$playing = 0;
		$tmpspec = 0;
		foreach($_players as $login => &$pl){
			if(isset($pl['Active']) && $pl['Active'] && !$pl['IsSpectator']){
				if($pl['IsTemporarySpectator'])
					$tmpspec++;
				else
					$playing++;
			}
		}
		if($tmpspec > 0 && $playing <= 0 && $_MFCTransition['Transition'] == ''){
			// all temporary spectators, not in delayed flow control transition !
			if($_players_antilock < 40){
				$_players_antilock++;
			}else{
				// locked for more than 40s : make endround
				if($_debug>0) console2("Round anti lock: all players are temporary specs for 40s without EndRound coming !");
				addCall(true,'ForceEndRound');
				$_players_antilock = -50;
			}
		}else{
			// it's ok
			$_players_antilock = 0;
		}
	}else{
		$_players_antilock = 0;
	}

	// control players in 'toSpecPlay' state !
	foreach($_players as $login => &$pl){
		if(isset($pl['toSpecPlay'])){
			if(!$pl['IsSpectator']){
				//if($_debug>0) console("playersEverysecond:: {$login} toSpecPlay : to spec !");
				addCall($login,'ForceSpectator',''.$login,1); // spec
				addCall($login,'ForceSpectator',''.$login,0); // then playerfree
			}
		}
	}

	// compute players positions while playing
  if($_StatusCode == 4){
		$every = (int) floor(($_players_actives - $_players_spec) / 15) + 1;
		if($_players_checkchange && $_GameInfos['GameMode'] != TA && $_GameInfos['GameMode'] != STUNTS && $every <= 3){
			// less than 45 players and in rounds based modes, compute each second
			playersComputePositions($sec);
			
		}elseif($_players_checkchange && ($sec % $every) == 0){
			// other cases: every $every seconds (ie 1s every 15 players)
			playersComputePositions($sec);
		}
	}

}


// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function playersPosCompare($a, $b)
{
	// no cp
	if($a['Check'] < 0 && $b['Check'] < 0)
		return strcmp($a['Login'],$b['Login']);
	// 2nd have del
	if($a['FinalTime'] > 0 && $b['FinalTime'] <= 0)
		return -1;
	// 1st have del
	elseif($a['FinalTime'] <= 0 && $b['FinalTime'] > 0)
		return 1;
	// only 1st
	if($b['Check'] < 0)
		return -1;
	// only 2nd
	elseif($a['Check'] < 0)
		return 1;
	// both ok, so...
	elseif($a['Check'] > $b['Check'])
		return -1;
	elseif($a['Check'] < $b['Check'])
		return 1;
	// same check, so test time
	elseif($a['Time'] < $b['Time'])
		return -1;
	elseif($a['Time'] > $b['Time'])
		return 1;
	// same check check and time, so test general rank
	elseif($a['Rank'] > 0 && $b['Rank'] > 0 && $a['Rank'] < $b['Rank'])
		return -1;
	elseif($a['Rank'] > 0 && $b['Rank'] > 0 && $a['Rank'] > $b['Rank'])
		return 1;
	// same check check, time and rank (only in team or beginning?), so test general scores
	elseif($a['Score'] > 0 && $b['Score'] > 0 && $a['Score'] > $b['Score'])
		return -1;
	elseif($a['Score'] > 0 && $b['Score'] > 0 && $a['Score'] < $b['Score'])
		return 1;
	// same check check, time, rank and general score, so test besttime
	elseif($a['BestTime'] > 0 && $b['BestTime'] > 0 && $a['BestTime'] < $b['BestTime'])
		return -1;
	elseif($a['BestTime'] > 0 && $b['BestTime'] > 0 && $a['BestTime'] > $b['BestTime'])
		return 1;
	// same check check, time, general score and besttime, so test check sequence order
	elseif($a['CPseq'] < $b['CPseq'])
		return -1;
	elseif($a['CPseq'] > $b['CPseq'])
		return 1;
	// all same... test time of previous checks
	for($key = $a['Check']-1; $key >= 0; $key--){
		if($a['Checkpoints'][$key] < $b['Checkpoints'][$key])
			return -1;
		elseif($a['Checkpoints'][$key] > $b['Checkpoints'][$key])
			return 1;
	}
	// really all same, use login  :p
	return strcmp($a['Login'],$b['Login']);
}


// verify low DownloadRate and UploadRate
function playersVerifyRates($login){
	global $_debug,$_players;
	if(!is_string($login))
		$login = ''.$login;
	if(isset($_players[$login]['PlayerInfo'])){
		$playerinfo = &$_players[$login]['PlayerInfo'];
		// verify low DownloadRate and UploadRate if present, kick player if low !
		if(isset($playerinfo['UploadRate']) && $playerinfo['UploadRate'] < 300){
			console("KICK $login for low UploadRate: ",$playerinfo['UploadRate']);
			$msg = localeText(null,'server_message').localeText($login,'players.low_upload');
			addCallDelay(2000,null,'ChatSendServerMessageToLogin', $msg, $login);
			addCallDelay(15000,null,'ChatSendServerMessageToLogin', $msg, $login);
			addCallDelay(30000,null,'ChatSendServerMessageToLogin', $msg, $login);
			addCallDelay(40000,null,'Kick',$login,'$w$ff0 server kick: too low upload rate is forbidden ! $z');
			
		}elseif(isset($playerinfo['DownloadRate']) && $playerinfo['DownloadRate'] < 300){
			console("KICK $login for LOW DownloadRate: ",$playerinfo['DownloadRate']);
			$msg = localeText(null,'server_message').localeText($login,'players.low_download');
			addCallDelay(2000,null,'ChatSendServerMessageToLogin', $msg, $login);
			addCallDelay(15000,null,'ChatSendServerMessageToLogin', $msg, $login);
			addCallDelay(30000,null,'ChatSendServerMessageToLogin', $msg, $login);
			addCallDelay(40000,null,'Kick',$login,'$w$ff0 server kick: too low download rate is forbidden ! $z');
		}
	}
}


function playersEveryminute($event,$minutes,$is2min,$is5min){
	global $_debug,$_players_actives,$_StatusCode,$_players_round_time,$_currentTime;
	playersFastKeepAlive();
	if($is5min && $_StatusCode == 4 && $_players_actives <= 0 && 
		 $_players_round_time > 0 && ($_currentTime-$_players_round_time) > 3600000){
		// if nobody and current round started 1 hour ago : restart map
		addCall(true,'ChallengeRestart');
		if($_debug>0) console("Nobody left on server: restart challenge.");
	}
}


function playersStatusChanged($event,$Status,$StatusCode){
	global $_debug,$_old_Status,$_players,$_GameInfos;
	if($_debug>0) console("players{$event}($StatusCode):: [old=".$_old_Status['Code']."]");

	if($StatusCode == 4){
		// update player status
		foreach($_players as $login => &$pl){
			if($pl['Active']){
				$status2 = $pl['SpectatorStatus'] > 0 ? 1 : 0;
				if($pl['Status2'] != $status2){
					$oldstatus2 = $pl['Status2'];
					$pl['Status2'] = $status2;
					insertEvent('PlayerStatus2Change',$login,$pl['Status2'],$oldstatus2); // insertEvent('PlayerStatus2Change',login,status)
				}
			}
		}
	}elseif($StatusCode == 3 && $_old_Status['Code'] == 4 &&
					($_GameInfos['GameMode'] == ROUNDS || $_GameInfos['GameMode'] == TEAM || $_GameInfos['GameMode'] == CUP)){
		// in rounds modes, update players status if not done
		foreach($_players as $login => &$pl){
			if($pl['Active'] && $pl['Status2'] < 1){
				$oldstatus2 = $pl['Status2'];
				$pl['Status2'] = 1;
				insertEvent('PlayerStatus2Change',$login,$pl['Status2'],$oldstatus2); // insertEvent('PlayerStatus2Change',login,status)
			}
		}
	}
}


function playersBillUpdated($event,$billid,$bill){
	global $_debug,$_players;
	$msg = ' Bill('.$bill['From'].','.$bill['To'].','.$bill['Coppers']
		.')=>('.$bill['State'].','.$bill['StateName'].','.$bill['TransactionId'].')';
	console("players{$event}::".$msg);
	if(strlen($bill['From']) > 0 && isset($_players[$bill['From']]))
		addCall(null,'ChatSendToLogin',localeText(null,'server_message').localeText(null,'interact').$msg,$bill['From']);
	if($bill['State'] == 4 && strlen($bill['To']) > 0 && isset($_players[$bill['To']])){
		if($bill['From'] === '')
			$msg2 = localeText($bill['To'],'players.billpayedbyserv',$bill['Coppers']);
		else
			$msg2 = localeText($bill['To'],'players.billpayed',$_players[$bill['From']]['Login'],$bill['Coppers']);
		if(strlen($bill['Comment']) > 0)
			$msg2 .= ' ['.$bill['Comment'].']';
		console(stripColors("BillTo ".$bill['To']." : ".$msg2));
		addCall(null,'ChatSendToLogin',localeText(null,'server_message').localeText(null,'interact').$msg2."\n".localeText(null,'interact').$msg,$bill['To']);
	}
}


function playersPlayerNetInfos($event,$login,$netinfos){
	global $_players;
	if(!isset($_players[$login]['ConnectionTime']))
		dropEvent();
}


function playersPlayerChat($event,$login,$message,$iscommand){
	global $_debug,$_players,$_ChatFloodRateMax,$_SystemInfo,$_remote_controller_chat_is_admin,$_is_relay;
	if(!isset($_players[$login]['Active'])){
		console("playersPlayerChat($login,$message,$iscommand):: {$_SystemInfo['ServerLogin']},{$_remote_controller_chat_is_admin}");
		if(!$iscommand || $login != $_SystemInfo['ServerLogin'] || !$_remote_controller_chat_is_admin)
			dropEvent();
		return;
	}

	$_players[$login]['ChatFloodRate']++;

  $chatfloodratemax = verifyAdmin($login) ? $_ChatFloodRateMax*4 : $_ChatFloodRateMax;
  if(!$_is_relay && $_players[$login]['IsSpectator'])
    $chatfloodratemax /= 1.6;
  if(!$_is_relay && isset($_match_map) && $_match_map > 0)
    $chatfloodratemax /= 1.6;

  if($_players[$login]['Active'] && !$_players[$login]['Relayed'] && !$_players[$login]['ChatFloodRateIgnore'] &&
     $_players[$login]['ChatFloodRate'] > $chatfloodratemax){
    // $login is above ChatFloodRateMax (twice in busrt, twice for admin, never for relayed players)
    if($_debug>0) console("playersPlayerChat:: ChatFloodRate({$login}) => Ignore");
    $_players[$login]['ChatFloodRateIgnore'] = true;
    addCall(null,'Ignore',$login.'');

    $msg = localeText(null,'server_message').localeText(null,'interact')."Chat flood exceeded, you have been temporary ignored !";
    addCall(null,'ChatSendToLogin',$msg,$login);
  }
}


// plugins can use this event to do things before the transition is proceeded
function playersManualFlowControlTransition($event,$transition){
	//global $_debug,$_GameInfos;
	//if($_debug>2) console("players{$event}($transition)::");
}


function playersEndPodium($event,$delay){
	//global $_debug;
	//if($_debug>1) console("players{$event}($delay)::");
}


function playersPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_players;
	if(!isset($_players[$login]['ConnectionTime'])){
		//console("playersPlayerManialinkPageAnswer({$login},{$answer},{$action}) DROP");
		dropEvent();
	}
}


function playersPlayerPositionChange($event,$login,$changes){
	global $_players;
	if($login !== true && !isset($_players[$login]['ConnectionTime']))
		dropEvent();
}


function playersPlayerInfoChanged($event,$login,$playerinfo){
	global $_players;
	if(!isset($_players[$login]['ConnectionTime']))
		dropEvent();
}


function playersPlayerTeamChange($event,$login,$teamid){
	global $_players;
	if(!isset($_players[$login]['ConnectionTime']))
		dropEvent();
}


function playersPlayerFlagsChange($event,$login,$flags){
	global $_players;
	if(!isset($_players[$login]['ConnectionTime']))
		dropEvent();
}


function playersPlayerStatusChange($event,$login,$status,$oldstatus){
	global $_debug,$_players;
	if($_debug>2) console("playersPlayerStatusChange:: $login,$status,$oldstatus");
	if(!isset($_players[$login]['ConnectionTime']))
		dropEvent();
}


function playersPlayerStatus2Change($event,$login,$status2,$oldstatus2){
	global $_debug,$_players;
	if($_debug>2) console("playersPlayerStatus2Change:: $login,$status2,$oldstatus2");
	if(!isset($_players[$login]['ConnectionTime']))
		dropEvent();
}


function playersWarmUp2FWarmUp(){
	global $_debug,$_WarmUp,$_FWarmUp,$_NextFWarmUp,$_FWarmUpDuration,$_always_use_FWarmUp,$_NextGameInfos;
	if($_always_use_FWarmUp){
		$classicwud = $_NextGameInfos['GameMode'] == CUP ? $_NextGameInfos['CupWarmUpDuration'] : $_NextGameInfos['AllWarmUpDuration'];
		if($classicwud > 0){
			// WarmUpDuration -> FWarmUpDuration
			$_FWarmUpDuration = $classicwud;
			if($_NextGameInfos['GameMode'] == CUP)
				addCall(null,'CupAllWarmUpDuration',0);
			else
				addCall(null,'SetAllWarmUpDuration',0);
		}
	}
}


function playersStoreInfos($event){
	global $_debug,$_StoredInfos,$_players,$_GameInfos,$_NextGameInfos,$_ServerOptions,$_ChallengeInfo,$_ChallengeList,$_CurrentChallengeIndex,$_NextChallengeIndex,$_PlayerList,$_Ranking,$_PlayerInfo,$_players,$_WarmUp,$_FWarmUp,$_FWarmUpDuration,$_always_use_FWarmUp,$_RoundCustomPoints,$_roundslimit_rule,$_teamroundslimit_rule,$_FGameMode,$_NextFGameMode,$_fteams_rules,$_fteams,$_fteams_on;

	// build data to store
	$_StoredInfos['GameInfos'] = $_GameInfos;
	$_StoredInfos['NextGameInfos'] = $_NextGameInfos;
	$_StoredInfos['FGameMode'] = $_FGameMode;
	$_StoredInfos['NextFGameMode'] = $_NextFGameMode;
	$_StoredInfos['ServerOptions'] = $_ServerOptions;
	$_StoredInfos['ChallengeInfo'] = $_ChallengeInfo;
	$_StoredInfos['ChallengeList'] = $_ChallengeList;
	$_StoredInfos['CurrentChallengeIndex'] = $_CurrentChallengeIndex;
	$_StoredInfos['NextChallengeIndex'] = $_NextChallengeIndex;
	$_StoredInfos['PlayerList'] = $_PlayerList;
	$_StoredInfos['Ranking'] = $_Ranking;
	$_StoredInfos['PlayerInfo'] = $_PlayerInfo;
	$_StoredInfos['players'] = $_players;
	$_StoredInfos['RoundCustomPoints'] = $_RoundCustomPoints;
	$_StoredInfos['RoundsLimit'] = isset($_roundslimit_rule) ? $_roundslimit_rule : -1;
	$_StoredInfos['TeamRoundsLimit'] = isset($_teamroundslimit_rule) ? $_teamroundslimit_rule : -1;

	$_StoredInfos['WarmUp'] = $_WarmUp;

	$_StoredInfos['FWarmUp'] = $_FWarmUp;
	$_StoredInfos['FWarmUpDuration'] = $_FWarmUpDuration;
	$_StoredInfos['AlwaysFWarmUp'] = $_always_use_FWarmUp;

	$_StoredInfos['FTeamsOn'] = $_fteams_on;
	$_StoredInfos['FTeamsRules'] = $_fteams_rules;
	$_StoredInfos['FTeams'] = $_fteams;
}


function playersRestoreInfos($event,$restoretype,$liveage,$playerschanged,$rankingchanged){
	global $_debug,$_methods_list,$_StoredInfos,$_GameInfos,$_NextGameInfos,$_ServerOptions,$_players,$_roundslimit_rule,$_teamroundslimit_rule,$_last_matchsettings,$_RoundCustomPoints,$_FWarmUp,$_FWarmUpDuration,$_always_use_FWarmUp,$_FGameMode,$_NextFGameMode,$_FGameModes,$_fteams_rules,$_fteams,$_fteams_on;

	if($_debug>0) console("playersRestoreInfos($restoretype,$liveage,$playerschanged,$rankingchanged)");
	if($restoretype == 'previous'){
		// restore previous server config (for case where the dedicated and Fast were restarted)

		// restore maps
		addCall(null,'LoadMatchSettings',$_last_matchsettings);
		// build list to have current as next
		$clsize = count($_StoredInfos['ChallengeList']);
		$start = $_StoredInfos['NextChallengeIndex']-1+$clsize;
		$clist = array();
		for($index = $start; $index < $start+$clsize; $index++){
			$ind = $index % $clsize;
			$clist[] = $_StoredInfos['ChallengeList'][$ind]['FileName'];
			if($_debug>0) console2("  -> map[$ind]: ".$_StoredInfos['ChallengeList'][$ind]['UId'].' , '.$_StoredInfos['ChallengeList'][$ind]['Name']);
		}
		addCall(true,'ChooseNextChallengeList',$clist);

		// restore ServerOptions values
		foreach($_StoredInfos['ServerOptions'] as $conf => $val){
			if($val != $_ServerOptions[$conf]){
				$num = 1;
				if(strncmp($conf,'Is',2) == 0)
					$setcmd = str_replace('Is','Enable',$conf,$num);
				else if(strncmp($conf,'Current',4) == 0)
					$setcmd = str_replace('Current','Set',$conf,$num);
				else if(isset($_methods_list['SetServer'.$conf]))
					$setcmd = 'SetServer'.$conf;
				else
					$setcmd = 'Set'.$conf;
				if(isset($_methods_list[$setcmd])){
					addCall(true,$setcmd,$val);
					if($_debug>0) console("playersRestoreInfos:: -> {$setcmd},{$val}");
				}
			}
		}

		// restore GameInfos values
		foreach($_StoredInfos['GameInfos'] as $conf => $val){
			if($val != $_NextGameInfos[$conf]){
				$setcmd = 'Set'.$conf;
				if(isset($_methods_list[$setcmd])){
					addCall(true,$setcmd,$val);
					if($_debug>0) console("playersRestoreInfos:: -> {$setcmd},{$val}");
				}
			}
		}

		// special rounds value not handled in matchsettings
		$_RoundCustomPoints = $_StoredInfos['RoundCustomPoints'];
		addCall(true,'SetRoundCustomPoints',$_RoundCustomPoints,true);
		$_roundslimit_rule = isset($_StoredInfos['RoundsLimit']) ? $_StoredInfos['RoundsLimit'] : -1;
		$_teamroundslimit_rule = isset($_StoredInfos['TeamRoundsLimit']) ? $_StoredInfos['TeamRoundsLimit'] : -1;

		$_FWarmUp = isset($_StoredInfos['FWarmUp']) ? $_StoredInfos['FWarmUp'] : 0;
		$_FWarmUpDuration = isset($_StoredInfos['FWarmUpDuration']) ? $_StoredInfos['FWarmUpDuration'] : 0;
		$_always_use_FWarmUp = isset($_StoredInfos['AlwaysFWarmUp']) ? $_StoredInfos['AlwaysFWarmUp'] : $_always_use_FWarmUp;

		// next map
		addCallDelay(50,true,'NextChallenge');

	}elseif($restoretype == 'live'){
		// restore previous Fast config (for case where only Fast was restarted)

		$_FWarmUp = isset($_StoredInfos['FWarmUp']) ? $_StoredInfos['FWarmUp'] : 0;
		$_FWarmUpDuration = isset($_StoredInfos['FWarmUpDuration']) ? $_StoredInfos['FWarmUpDuration'] : 0;
		$_always_use_FWarmUp = isset($_StoredInfos['AlwaysFWarmUp']) ? $_StoredInfos['AlwaysFWarmUp'] : $_always_use_FWarmUp;

		if(isset($_StoredInfos['FTeamsOn']))
			$_fteams_on = $_StoredInfos['FTeamsOn'];
		if(isset($_StoredInfos['FTeamsRules']))
			$_fteams_rules = array_update($_fteams_rules,$_StoredInfos['FTeamsRules']);
		if(isset($_StoredInfos['FTeams']))
			$_fteams = array_update($_fteams,$_StoredInfos['FTeams']);
	}

}


function playersStoreInfos_Reverse($event){
	global $_debug,$_StoredInfos,$_StoreFile,$_players;
	// save stored infos
	$datas = serialize($_StoredInfos);
	if(file_put_contents($_StoreFile,$datas,LOCK_EX) === false){
		console("playersStoreInfos_Reverse:: failed to write {$_StoreFile} to store state !");
	}
	$_StoredInfos = array();
}


function playersRestoreInfos_Reverse($event,$restoretype,$liveage,$playerschanged,$rankingchanged){
	global $_debug,$_StoredInfos;
	$_StoredInfos = array();
}


function players_count_languages(){
	global $_debug,$_players,$_PlayerInfo,$_locale,$_locale_default,$_used_languages;

	$languages = array($_locale_default=>0);
	foreach($_players as $login => &$pl){
		if(!is_string($login))
			$login = ''.$login;
		if($pl['Active']){

			if($pl['Language'] == '' &&
				 isset($_PlayerInfo[$login]['Language']) && $_PlayerInfo[$login]['Language'] != ''){
				$pl['Language'] = $_PlayerInfo[$login]['Language'];
			}
			
			if($pl['Language'] != '' && isset($_locale[$pl['Language']]))
				$lang = $pl['Language'];
			else
				$lang = $_locale_default;
			
			if(isset($languages[$lang]))
				$languages[$lang]++;
			else
				$languages[$lang] = 1;
		}
	}
	if($languages[$_locale_default] <= 0 && count($languages) > 1)
		unset($languages[$_locale_default]);
	$_used_languages = array_reverse($languages,true);
}


// if $_control_maxplayers and no password then make player maxlist and control max, put spec if needed
// note: guest players are in maxlist only for >=60K ladderservers
function playersControlMaxPlayers(){
	global $_debug,$_control_maxplayers,$_players_maxlist,$_players,$_guest_list,$_LadderServerLimits,$_ServerOptions,$_ladderserver_guestlimit;

	// control maxplayers (only if no player password on server !)
	if($_control_maxplayers && $_ServerOptions['Password'] == ''){
		$count_guests = false;
		if(isset($_LadderServerLimits['LadderServerLimitMax']) &&
			 $_LadderServerLimits['LadderServerLimitMax'] >= $_ladderserver_guestlimit)
			$count_guests = true;

		// remove not players from list
		foreach($_players_maxlist as $login){
			if(!isset($_players[$login]['Active']) ||
				 !$_players[$login]['Active'] ||
				 $_players[$login]['IsSpectator'])
				unset($_players_maxlist[$login]);
		}

		// add new players in list
		foreach($_players as $login => $pl){
			if($pl['Active'] && !$pl['IsSpectator']){
				if($count_guests || !isset($_guest_list[$login])){
					if(!isset($_players_maxlist[$login])){
						$_players_maxlist[$login] = $login;
					}
				}
			}
		}

		// exceeding players -> spectators !
		$max = $_ServerOptions['CurrentMaxPlayers'];
		while(count($_players_maxlist) > $max){
			$login = ''.array_pop($_players_maxlist);
			if($_debug>0) console("playersControlMaxPlayers:: maxplayer exceeded : put {$login} as spec !");
			console("maxplayer exceeded : put {$login} as spec !");
			addCall(null,'ForceSpectator',$login,1);
			addCall(null,'ForceSpectator',$login,0);
			$msg = localeText(null,'server_message').localeText(null,'interact')."Sorry, max players number exceeded, you are put in spectator !";
			addCall(null,'ChatSendToLogin',$msg,$login);
		}

	}elseif(count($_players_maxlist) > 0){
		$_players_maxlist = array();
	}
}


function playersSetScoresBack($round=false){
	global $_debug,$_GameInfos,$_players,$_players_round_current,$_players_rounds_scores,$_Ranking;
	if($round === false)
		$round = count($_players_rounds_scores) - 2;
	if(isset($_players_rounds_scores[$round])){
		$msg = '';
		$msg2 = '';

		if($_GameInfos['GameMode'] == TEAM){
			$val = array(array('PlayerId'=>0,'Score'=>$_players_rounds_scores[$round][0]['Score']),
									 array('PlayerId'=>1,'Score'=>$_players_rounds_scores[$round][1]['Score']));
			$msg = "blue={$_Ranking[0]['Score']},red={$_Ranking[1]['Score']}";
			$msg2 = "blue={$_players_rounds_scores[$round][0]['Score']},red={$_players_rounds_scores[$round][1]['Score']}";
			addCall(true,'ForceScores',$val,true);
			console("ForceScores: set round {$round} scores ! (was: {$msg} , now: {$msg2})");
			return "set round {$round} scores (was: {$msg} , now: {$msg2})";
			
		}else{
			$val = null;
			$sep = '';
			foreach($_players as &$pl){
				if($pl['Active']){
					$login = $pl['Login'];
					if(isset($_players_rounds_scores[$round][$login]['Score']))
						$score = $_players_rounds_scores[$round][$login]['Score'];
					else
						$score = 0;
					$val[] = array('PlayerId'=>$_players_rounds_scores[$round][$login]['PlayerId'],'Score'=>$score);
					$msg .= $sep.$login.'='.$pl['Score'];
					$msg2 .= $sep.$login.'='.$score;
					$sep = ',';
				}
			}
			if($val !== null){
				addCall(true,'ForceScores',$val,true);
				console("ForceScores: round {$round} scores ! (was: {$msg} , now: {$msg2})");
				return "set round {$round} scores (was: {$msg} , now: {$msg2})";
			}
		}
	}
	return false;
}


function playersGetScores(){
	global $_Ranking,$_GameInfos;
	$scores = array();
	foreach($_Ranking as $rank){
		$login = $rank['Login'];
		$playerid = $rank['PlayerId']+0;
		if($_GameInfos['GameMode'] == TEAM)
		  $playerid = $login+0;
		$scores[$login] = array('Login'=>$login,'PlayerId'=>$playerid,'Score'=>$rank['Score']);
	}
	return $scores;
}


// if ForceShowAllOpponents and player have old game version then indicate him the game FIX !
function playersTestGameFix($login=true){
	global $_debug,$_players,$_PlayerInfo,$_GameInfos,$_GAMEFIXURL;
	if(isset($_GameInfos['ForceShowAllOpponents']) && $_GameInfos['ForceShowAllOpponents'] > 0){
		if($login === true){
			
			foreach($_players as $login => $pl){
				if($pl['Active'] && !$pl['Relayed'] && isset($_PlayerInfo[$login]['Language']) &&
					 (!isset($_PlayerInfo[$login]['ClientVersion']) || 
						$_PlayerInfo[$login]['ClientVersion'] == '')){
					if($_debug>0) console("playersTestGameFix:: announce GAMEFIX to {$login} !");
					$msg = localeText(null,'server_message').'$c00'.localeText($login,'players.forceoppgamefix',"\$l[{$_GAMEFIXURL}]GAME FIX\$l");
					addCall(null,'ChatSendServerMessageToLogin', $msg, $login);
				}
			}
		}else{
			if($_players[$login]['Active'] && !$_players[$login]['Relayed'] && isset($_PlayerInfo[$login]['Language']) &&
				 (!isset($_PlayerInfo[$login]['ClientVersion']) || 
					$_PlayerInfo[$login]['ClientVersion'] == '')){
				if($_debug>0) console("playersTestGameFix:: announce GAMEFIX to {$login} !");
				$msg = localeText(null,'server_message').'$c00'.localeText($login,'players.forceoppgamefix',"\$l[{$_GAMEFIXURL}]GAME FIX\$l");
				addCall(null,'ChatSendServerMessageToLogin', $msg, $login);
			}
		}
	}
}


//
function playersCheat($login,$reason,$infos){
	global $_players;
	$pl = &$_players[$login];

	$pl['Cheats']++;
	console("CHEAT: {$login}, {$pl['Cheats']}, {$reason} ({$infos})... or dedicated bug.");
	if(!$pl['Cheating']){
		$msg = localeText(null,'server_message').localeText(null,'interact').'$e22';
		if($pl['Cheats'] > 3){
			addCall(null,'Ban',$login,"\n\n\$o\$s\$e22     You were banned for repeated cheating ! \n\n\$a11       ({$reason})\n\n\$z");
			$msg .= "$login was banned for repeated supposed cheat \$b11({$reason})";
		}else{
			addCall(null,'Kick',$login,"\n\n\$o\$s\$e22     You were kicked for cheating ! \n\n\$a11       ({$reason})\n\n\$z");
			$msg .= "$login was kicked for supposed cheat \$b11({$reason})";
		}
		addCall(true,'ChatSendServerMessage', $msg);
	}
	$pl['Cheating'] = true;
}


// A value in GetCallVoteRatios is used to store in dedicated a keepalive value
// so Fast will know it was crashed or stopped/started and should restore back it's values
function playersFastKeepAlive($cb=false){
	global $_debug,$_CallVoteRatios;
	if(!$cb){
		// get CallVoteRatios values (with callback when response received)
		$action = array('CB'=>array('playersFastKeepAlive',array(true)));
		addCall($action,'GetCallVoteRatios');
		//if($_debug>0) console("playersFastKeepAlive:: GetCallVoteRatios");

	}else{
		// It's the callback ! set keepalive !
		//if($_debug>0) debugPrint("playersFastKeepAlive:: CallVoteRatios",$_CallVoteRatios);

		$fka = 'FastKeepAlive:'.time();
		//if($_debug>0) console("playersFastKeepAlive:: set {$fka}");
		$addit = true;
		foreach($_CallVoteRatios as &$cvr){
			if(strncmp($cvr['Command'],$fka,14) == 0){
				$addit = false;
				$cvr['Command'] = $fka;
			}
		}
		if($addit)
			$_CallVoteRatios[] = array('Command'=>$fka,'Ratio'=>0.5);
		addCall(true,'SetCallVoteRatios',$_CallVoteRatios);
	}
}


function playersTunnelDataReceived($event,$login,$data){
	global $_debug,$_players,$_is_relay,$_master,$_relays,$_RelayInfos;
	if($_is_relay){
		// relay
		if($_master['Login'] == $login){
			// received from master !
			$data2 = unserialize(gzinflate($data));
			if($_debug>2) console("playersTunnelDataReceived::(".strlen($data).") master data for relay !...");
			if($_debug>8) console("playersTunnelDataReceived::(".strlen($data).") master data for relay : ".print_r($data2,true));
			addEvent('DatasFromMaster',$data2);
		}
	}else{
		// master
		if(isset($_relays[$login])){
			// received from a relay !
			if($_debug>8) console("playersTunnelDataReceived:: data from relay({$login}), brut: ".print_r($data,true));
			// probably sent using sendToMaster() , decode it...
			$datas = @unserialize(@gzinflate($data));
			if($_debug>8) console("playersTunnelDataReceived:: data from relay({$login}), decoded: ".print_r($datas,true));
			if($datas == 'GetFastConfigs'){
				if($_debug>0) console("playersTunnelDataReceived:: relay({$login}) ask GetFastConfigs !");
				// a relay ask the Fast config : mark it as Fast relay, then build datas and send to it !
				$_relays[$login]['FastRelay'] = true;
				getFastInfosForRelay($login,$state='init');
				dropEvent();
			}
		}
	}

	if(!isset($_players[$login]['ConnectionTime']))
		dropEvent();
}


// Get Fast infos for relays
function playersGetInfosForRelay($event,$relaylogin,$state){
	global $_debug,$_is_relay,$_RelayInfos,$_FASTver,$_FWarmUp,$_NextFWarmUp,$_FWarmUpDuration,$_always_use_FWarmUp,$_FGameMode,$_NextFGameMode;
	if($_is_relay){
		dropEvent();
		return;
	}
	$_RelayInfos = array();
	if($state == 'init'){
		if($_debug>8) console("playersGetInfosForRelay:: ({$state},add Fast version {$_FASTver})");
		$_RelayInfos['Fast'] = $_FASTver;
	}
	$_RelayInfos['FWarmUp'] = $_FWarmUp;
	$_RelayInfos['NextFWarmUp'] = $_NextFWarmUp;
	$_RelayInfos['FWarmUpDuration'] = $_FWarmUpDuration;
	$_RelayInfos['AlwaysFWarmUp'] = $_always_use_FWarmUp;
	$_RelayInfos['FGameMode'] = $_FGameMode;
	$_RelayInfos['NextFGameMode'] = $_NextFGameMode;
}


function playersGetInfosForRelay_Reverse($event,$relaylogin,$state){
	global $_debug,$_is_relay,$_relays,$_RelayInfos;
	if($_is_relay)
		return;

	if(count($_relays) > 0 && ($relaylogin === true || isset($_relays[$relaylogin]['PlayerId']))){
		if($_debug>0) console("playersGetInfosForRelay_Rev({$relaylogin},{$state}):: send Fast infos to relay(s) !...");
		if($_debug>8) console("playersGetInfosForRelay_Rev:: _RelayInfos: ".print_r($_RelayInfos,true));
		if($relaylogin === true)
			sendToRelay(true,$_RelayInfos);
		else
			sendToRelay($_relays[$relaylogin]['PlayerId'],$_RelayInfos);
	}
	$_RelayInfos = array();
}


// Fast Infos from master (to relay)
function playersDatasFromMaster($event,$data){
	global $_debug,$_is_relay,$_FWarmUp,$_NextFWarmUp,$_FWarmUpDuration,$_always_use_FWarmUp,$_FGameMode,$_NextFGameMode;
	if(!$_is_relay){
		dropEvent();
		return;
	}
	if($data === 'FastMasterStarted'){
		// master announce : ask config info to Fast master !
		if($_debug>1) console("playersDatasFromMaster:: FastMasterStarted received : ask GetFastConfigs to master...");
		sendToMaster('GetFastConfigs');
		dropEvent();

	}elseif(is_array($data)){
		// real datas from master
		if(isset($data['Fast'])){
			// was init
			if($_debug>1) console("playersDatasFromMaster:: Fast init infos received from master !...");
			if($_debug>8) console("playersDatasFromMaster:: ".print_r($data,true));
		}
		if(isset($data['FWarmUp']))
			$_FWarmUp = $data['FWarmUp'];
		if(isset($data['NextFWarmUp']))
			$_NextFWarmUp = $data['NextFWarmUp'];
		if(isset($data['FWarmUpDuration']))
			$_FWarmUpDuration = $data['FWarmUpDuration'];
		if(isset($data['AlwaysFWarmUp']))
			$_always_use_FWarmUp = $data['AlwaysFWarmUp'];
		if(isset($data['FGameMode']))
			$_FGameMode = $data['FGameMode'];
		if(isset($data['NextFGameMode']))
			$_NextFGameMode = $data['NextFGameMode'];

	}else{
		dropEvent();
	}
}


// Store Fast states
function playersStoreFastState(){
	global $_debug,$_is_relay;
	if($_is_relay)
		return;
	// do keepalive
	playersFastKeepAlive();
	// send StoreInfos event !
	addEvent('StoreInfos');
}


// Restore Fast states (if was Fast recently on the server)
function playersRestoreFastState(){
	global $_debug,$_is_relay,$_master,$_CallVoteRatios,$_StoredInfos,$_StoreFile,$_RestorePrevious,$_RestoreLive,$_ChallengeInfo,$_PlayerList,$_Ranking;
	if($_debug>2) console2("playersRestoreFastState !...");
	if($_is_relay){
		// relay : ask config info to Fast master !
		if($_debug>0) console2("playersRestoreFastState:: ask GetFastConfigs to master...");
		sendToMaster('GetFastConfigs');
		return;

	}else{
		// announce to relays that the master is a Fast
		sendToRelay(true,'FastMasterStarted');
	}

	// restore
	$_StoredInfos = array();
	if(!file_exists($_StoreFile) || (!$_RestorePrevious && !$_RestoreLive)){
		console2("playersRestoreFastState:: no stored datas file ({$_StoreFile}) !");
		insertEvent('RestoreInfos','start',-1,true,true);
		return;
	}
	if(($datas = file_get_contents($_StoreFile)) === false){
		console2("playersRestoreFastState:: failed to read file {$_StoreFile} !");
		insertEvent('RestoreInfos','start',-1,true,true);
		return;
	}
	if(($_StoredInfos = unserialize($datas)) === false){
		console2("playersRestoreFastState:: failed to read stored datas in {$_StoreFile} !");
		$_StoredInfos = array();
		insertEvent('RestoreInfos','start',-1,true,true);
		return;
	}
	//if($_debug>0) debugPrint("playersRestoreFastState:: _StoredInfos",$_StoredInfos);
	
	// is FastKeepAlive present ?   FastKeepAlive is stored every minute, so consider <90s for 'live' case
	$liveage = -1;
	foreach($_CallVoteRatios as $cvr){
		if(strncmp($cvr['Command'],'FastKeepAlive:',14) == 0){
			$liveage = time() - substr($cvr['Command'],14);
			break;
		}
	}
	//$liveage = -1;
	//if($_debug>0) debugPrint("playersRestoreFastState:: $liveage  - CallVoteRatios",$_CallVoteRatios);

	// send RestoreInfos event
	if($_RestoreLive && $liveage >= 0 && $liveage < 90 && 
		 isset($_ChallengeInfo['UId']) && isset($_StoredInfos['ChallengeInfo']['UId']) &&
		 $_ChallengeInfo['UId'] == $_StoredInfos['ChallengeInfo']['UId']){
		$playerschanged = ($_StoredInfos['PlayerList'] != $_PlayerList);
		$rankingchanged = ($_StoredInfos['Ranking'] != $_Ranking);
		insertEvent('RestoreInfos','live',$liveage,$playerschanged,$rankingchanged);
		
		console2("playersRestoreFastState:: will restore previous live config values, depending on each plugin ! ($liveage)");
		$msg = localeText(null,'server_message').localeText(null,'interact')."Fast restarted (restoring session) !";
		addCall(true,'ChatSendServerMessage', $msg);
		
	}elseif($_RestorePrevious){
		insertEvent('RestoreInfos','previous',-1,true,true);
		
		console2("playersRestoreFastState:: will restore previous config, depending on each plugin !  ($liveage)");
		$msg = localeText(null,'server_message').localeText(null,'interact')."Fast started (restoring config) !";
		addCall(true,'ChatSendServerMessage', $msg);
		
	}else{
		insertEvent('RestoreInfos','start',-1,true,true);

		console2("playersRestoreFastState:: will not restore live values ! ($liveage)");
		$msg = localeText(null,'server_message').localeText(null,'interact')."Fast restarted !";
		addCall(true,'ChatSendServerMessage', $msg);
	}
}





?>
