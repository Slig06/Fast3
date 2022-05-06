<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      07.12.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
// uncomment the next line to activate the howto plugin and see all events in log !

//registerPlugin('howto',98);



////////////////////////////////////////////////////////////////
//
// Here is an example of all possible events, with some indication about their origin
// To active the plugin and have most events indicated in log and console, just
// uncomment the registerPlugin() call.
//
// Note that for each event, the plugins functions will be called in the order defined
// by the plugins priority.
// Advanced usage : for some specials use it is possible that a plugin need to have
// it's function called later, ie after the plugins which have bigger priority number.
// It is possible by adding _Reverse ou _Post at the end of the function name. For example,
// for Init event, xxxInit() are called first in priority order, then xxxInit_Reverse() are 
// called in reversed priority order, then finally xxxInit_Post() are called in priority order.
//


////////////////////////////////////////////////////////////////
//
// Fast now handle ChatEnableManualRouting. See in fast_general.php the comments of
// the function sendPlayerChat() to see how to handle it in plugins !


////////////////////////////////////////////////////////////////
// general variables/arrays description :
////////////////////////////////////////////////////////////////

// $_currentTime : current time (ms)
// $everysecond : seconds (used for Everysecond callback)
// $every5seconds : seconds, 5s rounded (used for Every5seconds callback)
// $everyminute : minutes (used for Everyminute callback)

// $_Version : GetVersion values
// $_SystemInfo : GetSystemInfo values
// $_ServerInfos : GetDetailedPlayerInfo values for serverlogin
// $_ServerPackMask : GetServerPackMask value
// $_ServerId : playerid of server
// $_methods_list : list of known methods (from system.listMethods)
// $_use_flowcontrol : true/false, is the server using manualflowcontrol ?
// $_is_relay : true/false, is the server a relay ?
// $_master : if relay, GetMainServerPlayerInfo values
// $_relays : list of connected relays
// $_multidest_logins : true if server support multi comma separated logins in xxxToLogin() methods

// $_StatusCode : current game status code value
// $_Status : current game status, array of kind ('Code'=>4,'Name'=>'Running - Play')
// $_old_Status : old value of $_Status
// $_ChallengeList : challenges list
// $_CurrentChallengeIndex : current challenge index
// $_NextChallengeIndex : next challenge index
// $_ChallengeInfo : current challenge infos
// $_NextChallengeInfo : next challenge infos (supposed until EndPodium)
// $_PrevChallengeInfo : previous challenge infos
// $_GameInfos : current game infos
// $_NextGameInfos : next game infos
// $_ServerOptions : current server options
// $_PlayerList : players list (use $_players instead)
// $_Ranking : rankings list (use $_players instead)
// $_PlayerInfo : players detailed infos (use $_players instead)
// $_NetworkStats : server/players NetworkStats
// $_RoundCustomPoints : server RoundCustomPoints (see also plugin.04.roundspoints.php)
// $_CallVoteRatios : votes ratios
// $_ForcedMods : ForcedMods
// $_ForcedMusic : ForcedMusic
// $_ServerCoppers : ServerCoppers
// $_LadderServerLimits: LadderServerLimits
// $_EndMatchCondition : EndMatchCondition state in Cup ('Finished','ChangeMap','Playing')
// $_GuestList : guest players list
// $_IgnoreList : ignored players list
// $_BanList : banned players list
// $_BlackList : blacklisted players list

// $_IsFalseStart : is it currently a false start ?



////////////////////////////////////////////////////////////////
// players plugin variables/arrays (see also the beginning of plugin.01.players.php for them) :

// State info tables :
// $_players : main players table
//  note: disconnected players have $_players['Active']===false (old disconnected players are in: $_players_old)
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



// Helps for debugging, you can set those values in fast.php :

// general debug level
//$_debug = 1;

// general debug level for manialinks
//$_mldebug = 0;

// debug level for dedicated callbacks/events
//$_dedebug = 0;

// debug level for memtests
//$_memdebug = 0;

// specific debug info for specified dedicated methods calls, example:
// $_cdebug['ForceSpectator'] = 0;
//    means that addCall(,'ForceSpectator',) will show a calling line info if debug level > 0

// specific debug info for specified Fast events calls, example:
// $_edebug['PlayerCheckpoint'] = 0;
//    means that addEvent(,'PlayerCheckpoint',) will show a calling line info if debug level > 0

// specific debug level overiding the general one during specified plugin events, examples:
// $_pdebug['fteamrelay']['debug'] = 3; // all 'fteamrelay' events will have $_debug = 3
// $_pdebug['ml_main']['mldebug'] = 4; // all 'ml_main'events will have $_mldebug = 4
// $_pdebug['ALL']['BeginRound']['debug'] = 6; // BeginRound() will have $_debug = 6 for all plugins
// $_pdebug['fteamrelay']['PlayerConnect']['debug'] = 5; // except fteamrelayPlayerConnect() will have $_debug = 5
// $_pdebug['fteamrelay']['BeginRound']['debug'] = 1; // except fteamrelayBeginRound() will have $_debug = 1
// $_pdebug['players']['PlayerCheckpoint']['debug'] = 2; // except fteamrelayBeginRound() will have $_debug = 1
// $_pdebug['players']['PlayerFinish']['debug'] = 2; // except fteamrelayBeginRound() will have $_debug = 1






////////////////////////////////////////////////////////////////
// Example for all events handled by Fast
////////////////////////////////////////////////////////////////

// Init($event): used for init of plugins, called after includes and config, but before StartToServe
// (Fast event)
function howtoInit($event){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// StartToServe($event): called at beginning, before the main loop, but after plugins Init
// (Fast event)
function howtoStartToServe($event){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// ServerStart($event): called at beginning when all server infos are read
// (from TrackMania.ServerStart callback, or Fast simulated)
function howtoServerStart($event){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// BeginRace($event,$GameInfos,$newcup,$warmup): start of race
// (from TrackMania.BeginRace callback, or Fast simulated)
function howtoBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($newcup,$warmup,$fwarmup)");
}

// StartRace($event,$tm_db_n,$chalinfo,$ChallengeInfo): $chalinfo is the array returned by the database server
// (Fast database plugin event)
function howtoStartRace($event,$tm_db_n,$chalinfo,$ChallengeInfo){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// BeforePlay($event,$delay,$time)
// (from TrackMania.ManualFlowControlTransition callback)
// need ManualFlowControl to be activated for script !!! ie only if global $_use_flowcontrol == true
//  Transition 'Synchro -> Play' : before BeginRound and StatusChanged 3->4, seconds after StatusChanged 2->3 or 4->3 and EndRound
// $delay is milliseconds since callback was received. Can delay transition using: delayTransition($delay);
// $time is the script time at TrackMania.ManualFlowControlTransition callback
// The transition method is called a first time with delay 0, then eventually other times
// and called a last time with delay -1 just before to proceed transition (where delayTransition($delay) will have no effect)
// time is the original time when transition was received
function howtoBeforePlay($event,$delay,$time){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($delay,$time)");
}

// BeginRound($event)
// (from TrackMania.BeginRound callback, or Fast simulated)
function howtoBeginRound($event){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// PlayerConnect($event,$login,$pinfo,$pdetailedinfo,$pranking): player has connected
// (from TrackMania.PlayerConnect callback, or Fast simulated)
function howtoPlayerConnect($event,$login,$pinfo,$pdetailedinfo,$pranking){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login')");
}

// FTeamsChange($event): there is a player change in some team(s)
// check $_fteams[tid]['Changed'] to know in which team(s)
// (Fast players plugin event)
function howtoFTeamsChange($event){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]()");
}

// PlayerMenuBuild($event,$login): build player menu
function howtoPlayerMenuBuild($event,$login){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login')");
}

// PlayerArrive($event,$tm_db_n,$login): $_players[$login] is up to date
// (Fast database plugin event)
function howtoPlayerArrive($event,$tm_db_n,$login){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login')");
}

// PlayerUpdate($event,$login,$player): some change in player list or ranking data ($player is an array with changes)
// (Fast event)
function howtoPlayerUpdate($event,$login,$player){
	global $_debug;
	if($_debug>0){
		$msg = "";
		$sep = "";
		foreach($player as $key => $val){
			$msg .= $sep.$key."=".$val;
			$sep = ",";
		}
		console("howto.Event[$event]('$login',$msg)");
	}
}

// PlayerNetInfos($event,$login,$netinfos): player part of GetNetworkStats with successive identical value counters added
// (Fast event)
function howtoPlayerNetInfos($event,$login,$netinfos){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login') ".($netinfos===null ? 'null' : $netinfos['LatestNetworkActivity']));
}

// StatusChanged($event,$Status,$StatusCode)
// (from TrackMania.StatusChanged callback)
function howtoStatusChanged($event,$Status,$StatusCode){
	global $_old_Status,$_debug;
	if($_debug>0) console("howto.Event[$event]($StatusCode) [old=".$_old_Status['Code']."]");
	//Status[Code]=1, Status[Name]=Waiting
	//Status[Code]=2, Status[Name]=Launching
	//Status[Code]=3, Status[Name]=Running - Synchronization
	//Status[Code]=4, Status[Name]=Running - Play
	//Status[Code]=5, Status[Name]=Running - Finish
}

// Everyminute($event,$minutes,$is2min,$is5min): 
//        called once every minute, after all other events (before Every5seconds)
//       for other timing, use test   if(($minutes%XX)==0)  where XX if the every wanted minutes
// (Fast event)
function howtoEveryminute($event,$minutes,$is2min,$is5min){
	global $_debug;
	if($_debug>0){
		//console("howto.Event[$event]($minutes,$is2min,$is5min)");
		if(($minutes%8)==0) console("howtoEveryminute - every 8 minutes");
	}
}

// Every5seconds($event,$seconds): called once every 5 seconds, after all other events (before Everysecond)
// (Fast event)
function howtoEvery5seconds($event,$seconds){
	global $_debug;
	//if($_debug>2) console("howto.Event[$event]($seconds)");
}

// Everysecond($event,$seconds): called once every second, after all other events (before Everytime)
// (Fast event)
function howtoEverysecond($event,$seconds){
	global $_debug;
	//if($_debug>3) console("howto.Event[$event]($seconds)");
}

// Everytime($event): called at every mainloop, after all other events
// (Fast event)
function howtoEverytime($event){
	global $_debug;
	//if($_debug>4) console("howto.Event[$event]");
}

// PlayerChat($event,$login,$message): the player wrote a text in chat
// (from TrackMania.PlayerChat callback, or Fast simulated)
function howtoPlayerChat($event,$login,$message,$iscommand){
	global $_debug;
	if($_debug>3) console("howto.Event[$event]('$login','$message',$iscommand)");
}

// PlayerStart($event,$login,$starttime): send this event when player start (in TA)
// (from TrackMania.PlayerFinish(login,0))
function howtoPlayerStart($event,$login,$starttime){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($login,$starttime)");
}

// PlayerCheckpoint($event,$login,$time,$lapnum,$checkpt,$hiddenabort): player passed a checkpoint
// (from TrackMania.PlayerCheckpoint callback)
// $hiddenabort is true in case of TA with $checkpt==0 after a respawn+del,
// which don't make a PlayerFinish(login,0). Exists since Fast 3.2.3g
function howtoPlayerCheckpoint($event,$login,$time,$lapnum,$checkpt,$hiddenabort=false){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$time,$lapnum,$checkpt,$hiddenabort)");
}

// PlayerLap($event,$login,$time,$lapnum,$checkpt): player lap time (in Laps mode only)
// (Fast players plugin event)
function howtoPlayerLap($event,$login,$time,$lapnum,$checkpt,$checkpts){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$time,$lapnum,$checkpt)");
}

// PlayerManialinkPageAnswer($event,$login,$answer): the player made a manialink answer
// (from TrackMania.ManialinkPageAnswer callback)
function howtoPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$answer,'$action')");
}


// PlayerMenuAction($event,$login,$action,$state): player have triggered a menu entry
// (Fast ml_menus plugin event)
function howtoPlayerMenuAction($event,$login,$action,$state){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$action,$state)");
}


// PlayerPositionChange($event,$login,$changes): player position change (rounds, team and laps)
//  ($changes: bit 0=position change, bit 1=check change, bit 2=previous player or time change)
//  (bit 3=next player or time change, bit 4=first player or time change)
//  (bit 5=prev2 player or time change, bit 6=next2 player or time change)
//  (if $login===true then there where some changes in players positions or times)
// (Fast players plugin event)
function howtoPlayerPositionChange($event,$login,$changes){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$changes)");
	// $changes value :
	//   1=position changed  (in TA: best rank changed)
	//   2=checkpoint changed
	//   4=diff with previous player changed  (not in TA)
	//   8=diff with next player changed  (not in TA)
	//  16=diff with first player changed  (in TA: best diff)
	//  32=diff with previous2 player changed  (not in TA)
	//  64=diff with next2 player changed  (not in TA)
}

// RoundPositions($event,$sec): players positions in round have been recomputed
// Special $sec values when called by: BeginRound: -1, PlayerFinish: -2, BeforeEndRound: -3
function howtoRoundPositions($event,$sec){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$sec)");
}


// PlayerBest($event,$login,$time,$ChallengeInfo,$checkpts): player made his best time (or lap in Laps mode on dedicated)
// (Fast players plugin event)
function howtoPlayerBest($event,$login,$time,$ChallengeInfo,$GameInfos,$checkpts){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$time)");
}

// PlayerRecord($event,$tm_db_n,$login,$time,$rank,$old_time,$old_rank,$ChallengeInfo): player made a new record for this database
// (Fast database plugin event)
function howtoPlayerRecord($event,$tm_db_n,$login,$time,$rank,$old_time,$old_rank,$ChallengeInfo,$GameInfos){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$time,$rank)");
}

// PlayerSpecFinish($event,$login): in TA, send this event when player go spec
// (the standard PlayerFinish,0 come later and not immediately in shuch case)
function howtoPlayerSpecFinish($event,$login){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login')");
}

// PlayerFinish($event,$login,$time,$checkpts): player has finished the round/run/lap
// (from TrackMania.PlayerFinish callback, $checkpts added by players plugin)
function howtoPlayerFinish($event,$login,$time,$checkpts){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$time)");
}

// PlayerIncoherence($event,$login)
// (from TrackMania.PlayerIncoherence callback)
function howtoPlayerIncoherence($event,$login){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login)");
}

// PlayerInfoChanged($event,$login,$playerinfo): player infos change
// (from TrackMania.PlayerInfoChanged callback)
function howtoPlayerInfoChanged($event,$login,$playerinfo){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',".implode(',',$playerinfo).")");
}

// PlayerSpecChange($event,$login,$isspec,$specstatus,$oldspecstatus): player spectator state change
// (Fast players plugin event)
function howtoPlayerSpecChange($event,$login,$isspec,$specstatus,$oldspecstatus){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',".($isspec?'true':'false').",$specstatus)");
}

// PlayerTeamChange($event,$login,$teamid): player team change
// (Fast players plugin event)
function howtoPlayerTeamChange($event,$login,$teamid){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$teamid)");
}

// PlayerFlagsChange($event,$login,$flags): player flags change
// (Fast players plugin event)
function howtoPlayerFlagsChange($event,$login,$flags){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$flags)");
}

// PlayerStatusChange($event,$login,$status,$oldstatus): player status change
// $status based on strict game hud: 0=playing, 1=spec, 2=race finished
// (Fast players plugin event)
function howtoPlayerStatusChange($event,$login,$status,$oldstatus){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$status)");
}

// PlayerStatus2Change($event,$login,$status2,$oldstatus2): player status change
// round logical $status2: 0=playing, 1=spec, 2=race finished
// (Fast players plugin event)
function howtoPlayerStatus2Change($event,$login,$status2,$oldstatus2){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$status2)");
}

// PlayerShowML($event,$login,$ShowML): player ShowML has changed
// (from manialinks plugin event)
function howtoPlayerShowML($event,$login,$ShowML){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',".($ShowML?'true':'false').")");
}

// PlayerDisconnect($event,$login): player has gone
// (from TrackMania.PlayerDisconnect callback, or Fast simulated)
function howtoPlayerDisconnect($event,$login){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login')");
}

// PlayerRemove($event,$login,$fully): player is removed from list
// (Fast players plugin event)
function howtoPlayerRemove($event,$login,$fully){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]('$login',$fully)");
}

// BeforeEndRound($event,$delay,$time)
// (from TrackMania.ManualFlowControlTransition callback)
// need ManualFlowControl to be activated for script !!! ie only if global $_use_flowcontrol == true
//  Transition 'Play -> Synchro' : before StatusChanged 4->3 and EndRound, after all PlayerFinish
//  Transition 'Play -> Podium' : before StatusChanged 4->5 and EndRound and EndRace, 
// $delay is milliseconds since callback was received. Can delay transition using: delayTransition($delay);
// $time is the script time at TrackMania.ManualFlowControlTransition callback
// The transition method is called a first time with delay 0, then eventually other times
// and called a last time with delay -1 just before to proceed transition (where delayTransition($delay) will have no effect)
// time is the original time when transition was received
function howtoBeforeEndRound($event,$delay,$time){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($delay,$time)");
}

// EndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting): end of the round
// (from TrackMania.EndRound callback, was previously a Fast event)
// $SpecialRestarting is true if special round restart (ie falsestart), same as $_players_round_restarting
function howtoEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// EndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup)
// (from TrackMania.EndRace callback)
function howtoEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($continuecup,$warmup,$fwarmup)");
}

// EndMatch($event,$match_map,$players_round_current,$maxscore,$match_scores,$match_config): called at end of match
// (from match plugin)
function howtoEndMatch($event,$match_map,$players_round_current,$maxscore,$match_scores,$match_config){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($match_map,$players_round_current,$maxscore)");
}

// EndResult($event): result table/podium at end of race
// (Fast event)
function howtoEndResult($event){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// EndPodium($event,$delay,$time)
// (from TrackMania.ManualFlowControlTransition callback)
// need ManualFlowControl to be activated for script !!! ie only if global $_use_flowcontrol == true
//  Transition 'Podium -> Synchro' : before StatusChanged 5->2 and BeginRace, seconds after EndRace
// $delay is milliseconds since callback was received. Can delay transition using: delayTransition($delay);
// $time is the script time at TrackMania.ManualFlowControlTransition callback
// The transition method is called a first time with delay 0, then eventually other times
// and called a last time with delay -1 just before to proceed transition (where delayTransition($delay) will have no effect)
// time is the original time when transition was received
function howtoEndPodium($event,$delay,$time){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($delay,$time)");
}

// FinishRace($event,$tm_db_n,$chalinfo,$ChallengeInfo): $chalinfo is the array returned by the database server
// (Fast database plugin event)
function howtoFinishRace($event,$tm_db_n,$chalinfo,$ChallengeInfo){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// ServerStop($event)
// (from TrackMania.ServerStop callback, or Fast simulated)
function howtoServerStop($event){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}
    
// BillUpdated($event,$billid,$bill)
// (from TrackMania.BillUpdated callback)
function howtoBillUpdated($event,$billid,$bill){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($billid,".$bill['StateName'].")");
}
    
// ChallengeListModified($event,$curchalindex,$nextchalindex,$islistmodified): the challenge list or indexes have changed
// (from TrackMania.ChallengeListModified callback)
function howtoChallengeListModified($event,$curchalindex,$nextchalindex,$islistmodified){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($curchalindex,$nextchalindex,$islistmodified)");
}

// ChallengeListChange($event) : the challengelist has changed 
// (see $_ChallengeList, $_CurrentChallengeIndex and $_NextChallengeIndex)
// (Fast event, for compatibility, now derived from ChallengeListModified event)
function howtoChallengeListChange($event){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// WarmUpChange($event,$status) : the warmup has changed
// (Fast event)
function howtoWarmUpChange($event,$status){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($status)");
}

// FWarmUpChange($event,$status) : the fwarmup has changed
// (Fast event)
function howtoFWarmUpChange($event,$status){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($status)");
}

// RoundCustomPointsChange($event,$custompoints) : the RoundCustomPoints has changed
// (Fast event)
function howtoRoundCustomPointsChange($event,$custompoints){
	global $_debug;
	if($_debug>0) console("howto.Event[$event](".implode(',',$custompoints).")");
}

// GuestListChange($event,$guestlist) : the guestlist has changed
// (Fast event)
function howtoGuestListChange($event,$guestlist){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}
    
// IgnoreListChange($event,$ignorelist) : the ignorelist has changed
// (Fast event)
function howtoIgnoreListChange($event,$ignorelist){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}
    
// BanListChange($event,$banlist) : the banlist has changed
// (Fast event)
function howtoBanListChange($event,$banlist){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// BlackListChange($event,$blacklist) : the blacklist has changed
// (Fast event)
function howtoBlackListChange($event,$blacklist){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// AdminChange($event,$login,$isadmin) : an admin has change
function howtoAdminChange($event,$login,$isadmin){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($login,$isadmin)");
}

// FGameModeChange($event,$old_fgmode,$fgmode) : FGameMode has changed
function FGameModeChange($event,$old_fgmode,$fgmode){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($old_fgmode,$fgmode)");
}

// TunnelDataReceived($event,$login,$data)
// (from TrackMania.TunnelDataReceived callback)
function howtoTunnelDataReceived($event,$login,$data){
	global $_debug;
	if($_debug>0) console("howto.Event[$event]($login)");
}

// StoreInfos(): called regulary to build stored infos (for restore system)
//       add infos to store in global $_StoredInfos array
function howtoStoreInfos($event){
	global $_StoredInfos,$_debug;
	if($_debug>0) console("howto.Event[$event]");
}

// RestoreInfos($event,$restoretype,$liveage,$playerschanged,$rankingchanged):
//       called at beginning (after ServerStart) to restore scripts infos from global $_StoredInfos array
//       $restoretype='previous' if want to restore old state (ie maps,name,pass) from previous use
//       $restoretype='live' if want to restore after crash/quick script restart (but the dedicated stayed alive)
//       $restoretype='start' if normal start without restoring previous values
//         $liveage=seconds since last keepalive (-1 if dedicated was restarted !)
//         $playerschanged=true if playerlist has changed
//         $rankingchanged=true if rankinglist has changed
function howtoRestoreInfos($event,$restoretype,$liveage,$playerschanged,$rankingchanged){
	global $_StoredInfos,$_debug;
	if($_debug>0) console("howto.Event[$event]");
}

?>
