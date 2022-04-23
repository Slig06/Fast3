<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

// make control that max players is respected (because ForceSpectator can surpass it), done only if no play password
//$_control_maxplayers = true;

// if control_maxplayers is on, ladderserver limit above which guest players are forbidden (and so are counted in max control)
//$_ladderserver_guestlimit = 60000;

// timeout limit (ms) after which players are shown as netlost
//$_netlost_limit = 4000;


//--------------------------------------------------------------
// Events list. See plugins/plugin.98.howto.php to view how using them
//--------------------------------------------------------------
$_func_list = array(
  // ------------- TM server events
	// PlayerCheckpoint($event,$login,$time,$lapnum,$checkpt,$hiddenabort): player passed a checkpoint
	'PlayerCheckpoint'=>array(),
	// PlayerFinish($event,$login,$time,$checkpts): player has finished the round/run/lap ($checkpts added by players plugin)
	'PlayerFinish'=>array(),    
	// PlayerManialinkPageAnswer($event,$login,$answer,$action): the player made a manialink answer
	'PlayerManialinkPageAnswer'=>array(),
	// BillUpdated($event,$billid,$bill)
	'BillUpdated'=>array(),
	// PlayerIncoherence($event,$login)
	'PlayerIncoherence'=>array(),
	// TunnelDataReceived($event,$login,$data)
	'TunnelDataReceived'=>array(),
	// ChallengeListModified($event,$curchalindex,$nextchalindex,$islistmodified): the challenge list or indexes have changed
	'ChallengeListModified'=>array(),
	// PlayerInfoChanged($event,$login,$playerinfo)
	'PlayerInfoChanged'=>array(),
	// Echo($event,$public,$internal): callback caused by Echo() dedicated method, same parameter order
	'Echo'=>array(),
	// VoteUpdated($event,$StateName,$Login,$CmdName,$CmdParam)
	//  from TrackMania.VoteUpdated(string StateName, string Login, string CmdName, string CmdParam);
	//      StateName values: NewVote, VoteCancelled, VotePassed or VoteFailed
	'VoteUpdated'=>array(),
	// BeforePlay($event,$delay) : 'Synchro -> Play' FlowControlTransition, delay since transition received (ms)
	'BeforePlay'=>array(),
	// BeforeEndRound($event,$delay) : 'Play -> Xxxx' FlowControlTransition, delay since transition received (ms)
	'BeforeEndRound'=>array(),
	// EndPodium($event,$delay) : 'Podium -> Synchro' FlowControlTransition, delay since transition received (ms)
	'EndPodium'=>array(),

  // ------------- TM server events, or simulated by Fast if needed
	// StatusChanged($event,$Status,$StatusCode): 
	'StatusChanged'=>array(),
	// BeginRound($event): 
	'BeginRound'=>array(),   
	// EndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting): end of the round
	'EndRound'=>array(),
	// BeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup): start of race
	'BeginRace'=>array(),  
	// EndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup): 
	'EndRace'=>array(),    
	// ServerStart($event): 
	'ServerStart'=>array(),
	// ServerStop($event): 
	'ServerStop'=>array(),     
	// PlayerConnect($event,$login,$pinfo,$pdetailedinfo,$pranking): player has connected
	'PlayerConnect'=>array(),
	// PlayerDisconnect($event,$login): player has gone
	'PlayerDisconnect'=>array(),
	// PlayerChat($event,$login,$message,$iscommand): the player wrote a text in chat
	'PlayerChat'=>array(),

	// ------------- Fast events
	// Init($event): used for init of plugins, called after includes and config, but before StartToServe
	'Init'=>array(),            
	// StartToServe($event): called at beginning, before the main loop, but after plugins Init
	'StartToServe'=>array(),    
	// BeginChallenge($event,$ChallengeInfo,$GameInfos): called just after BeginRace, kept for compatibility
	'BeginChallenge'=>array(),  
	// EndResult($event): result table at end of race
	'EndResult'=>array(),
	// PlayerUpdate($event,$login,$player): some change in player list or ranking data ($player is an array with changes)
	'PlayerUpdate'=>array(),  
  // PlayerStart($event,$login,$starttime): send this event when player start (in TA)
	'PlayerStart'=>array(),  
  // PlayerSpecFinish($event,$login): in TA, send this event when player go spec (the standard PlayerFinish,0 come later and not immediately in shuch case)
	'PlayerSpecFinish'=>array(),  
	// PlayerNetInfos($event,$login,$netinfos): player part of GetNetworkStats with successive identical value counters added
	'PlayerNetInfos'=>array(),
	// ChallengeListChange($event) : the challengelist has changed (see $_ChallengeList)
	'ChallengeListChange'=>array(),
	// GuestListChange($event,$guestlist) : the guestlist has changed
	'GuestListChange'=>array(),
	// IgnoreListChange($event,$ignorelist) : the ignorelist has changed
	'IgnoreListChange'=>array(),
	// BanListChange($event,$banlist) : the banlist has changed
	'BanListChange'=>array(),
	// BlackListChange($event,$blacklist) : the blacklist has changed
	'BlackListChange'=>array(),
	// WarmUpChange($event,$status) : the warmup has changed
	'WarmUpChange'=>array(),
	// FWarmUpChange($event,$status) : the fwarmup has changed
	'FWarmUpChange'=>array(),
	// RoundCustomPointsChange($event,$custompoints) : the RoundCustomPoints has changed
	'RoundCustomPointsChange'=>array(),
	// AdminChange($event,$login,$isadmin) : an admin has changed
	'AdminChange'=>array(),
	// FGameModeChange($event,$old_fgmode,$new_fgmode) : FGameMode has changed
	'FGameModeChange'=>array(),

	// ------------- players plugin events
	// PlayerBest($event,$login,$time,$ChallengeInfo,$GameInfos,$checkpts): player made his best time (or best lap in Laps mode)
	'PlayerBest'=>array(),
	// PlayerLap($event,$login,$time,$lapnum,$checkpt,$checkpts): player lap time (in Laps mode)
	'PlayerLap'=>array(),
	// PlayerPositionChange($event,$login,$changes): player position change (rounds, team, laps and cup)
	//  ($changes: bit 0=position change, bit 1=check change ,bit 2=previous player or time change, bit 3=next player or time change) 
	'PlayerPositionChange'=>array(),
	// PlayerSpecChange($event,$login,$isspec,$specstatus,$oldspecstatus): player spectator state change
	'PlayerSpecChange'=>array(),
	// PlayerTeamChange($event,$login,$teamid): player team change
	'PlayerTeamChange'=>array(),
	// PlayerFlagsChange($event,$login,$flags): player flags change
	'PlayerFlagsChange'=>array(),
	// PlayerStatusChange($event,$login,$status,$oldstatus): player Status change 
	//   (game hud: 0=playing, 1=spec, 2=race finished)
	'PlayerStatusChange'=>array(),
	// PlayerStatus2Change($event,$login,$status2,$oldstatus2): player Status2 change 
	//   (logical: 0=playing, 1=spec, 2=race finished)
	// status 1 will for temporary spec will stop being set in degraded mode
	'PlayerStatus2Change'=>array(),
	// PlayerRemove($event,$login,$fully): player is removed from $_players list
	// if $fully is true then removed from $_players_old too.  can be usefull to free resources.
	'PlayerRemove'=>array(),
	// RoundPositions($event,$sec): players positions in round have been recomputed
	// Special $sec values when called by: BeginRound: -1, PlayerFinish: -2, BeforeEndRound: -3
	'RoundPositions'=>array(),

	// FTeamsChange($event): there is a player change in some team(s), check $_fteams[tid]['Changed'] to know in which team(s)
	'FTeamsChange'=>array(),

	// ------------- manialinks plugin events
	// PlayerShowML($event,$login,$ShowML): player ShowML has changed
	'PlayerShowML'=>array(),

	// ------------- ml_menus plugin events
	// PlayerMenuBuild($event,$login): build player menu
	'PlayerMenuBuild'=>array(),
	// PlayerMenuAction($event,$login,$action,$state): player have triggered a menu entry
	'PlayerMenuAction'=>array(),

	// ------------- database plugin events -> response from $_tm_db[$tm_db_n] database server (after database plugin request)
	// PlayerArrive($event,$tm_db_n,$login): $_players[$login] is up to date
	'PlayerArrive'=>array(),    
	// StartRace($event,$tm_db_n,$chalinfo,$ChallengeInfo): $chalinfo is the array returned by the database server
	'StartRace'=>array(),
	// FinishRace($event,$tm_db_n,$chalinfo,$ChallengeInfo): $chalinfo is the array returned by the database server
	'FinishRace'=>array(),      
	// PlayerRecord($event,$tm_db_n,$login,$time,$rank,$old_time,$old_rank,$ChallengeInfo,$GameInfos,$checkpts): player made a new record for this database
	'PlayerRecord'=>array(),

	// ------------- match plugin events
	// EndMatch($event,$match_map,$players_round_current,$maxscore,$match_scores,$match_config): called at end of match
	'EndMatch'=>array(),

	// ------------- Special Fast event
	// Everyminute($event,$minutes,$is2min,$is5min): 
	//       called once every minute, after all other events (before Every5seconds)
	//       for other timing, use test   if(($minutes%XX)==0)  where XX if the every wanted minutes
	'Everyminute'=>array(),
	// Every5seconds($event,$seconds): called once every 5 seconds, after all other events (before Everysecond)
	'Every5seconds'=>array(),
	// Everysecond($event,$seconds): called once every second, after all other events (before Everytime)
	'Everysecond'=>array(),
	// Everytime($event): called at every mainloop, after all other events
	'Everytime'=>array(),

	// StoreInfos($event): called regulary to build stored infos (for restore system)
	//       add infos to store in global $_StoredInfos array
	'StoreInfos'=>array(),
	// RestoreInfos($event,$restoretype,$liveage,$playerschanged,$rankingchanged):
	//       called at beginning to restore scripts infos from global $_StoredInfos array
	//       $restoretype='previous' if want to restore old state (ie maps,name,pass) from previous use
	//       $restovetype='live' if want to restore after crash/quick script restart (but the dedicated stayed alive)
	//         $liveage=seconds since last keepalive (-1 if dedicated was restarted !)
	//         $playerschanged=true if playerlist has changed
	//         $rankingchanged=true if rankinglist has changed
	//       $storedinfos=value previously stored in $_StoredInfos during StoreInfos event.
	'RestoreInfos'=>array(),
	// DatasFromMaster($event,$data): infos sent by the (Fast) master to the (Fast) relay
	//       see also sendToRelay() and sendToMaster()
	'DatasFromMaster'=>array(),
	// GetInfosForRelay($event,$relaylogin,$state): ask infos which will be sent to relay (true for all).
	//       Infos must be stored in the global $_RelayInfos array. $state can be 'init' or 'race'.
	//       see also getFastInfosForRelay()
	'GetInfosForRelay'=>array(),

	// ------------- Special Function event
	// Function(): will call only the function named in arg 1, and with next args. Usefull to call a delayed function
	'Function'=>array(),       
);

// array of events names
$_event_list = array_keys($_func_list);


// TM callback events: 'StatusChanged','BeginRace','BeginRound','RaceOver','PlayerConnect','PlayerDisconnect','PlayerChat','PlayerCheckpoint','PlayerFinish','ServerStart','ServerStop'

//--------------------------------------------------------------

$_plugin_funclist = array();
$_funcs_plugin = array();
$_plugin_list = array();

$_response = NULL;
$_response_error = NULL;
$_multicall = array();
$_multicall_action = array();
$_callback_responses = array();
$_multicall_response = array();
$_multicall_response_action = array();

$_multicall_async = array();
$_multicall_async_action = array();
$_multicall_async_list = array();

$_syncstatus_calls = array();
$_delay_actions = array();

$_events = array();
$_events_end = array();

$_cbTime = 0;
$_playerlistTime = 0;
$_playernetstatTime = 0;

$_HelpCmd = array();
$_HelpAdmCmd = array();

// will get the ChatEnableManualRouting state (only if changed using addCall() )
$_chatmanualrouting = false;

// will be true if server support comma list of logins in ChatSendToLogin etc.
// that feature appeard on dedicated 2.11.21/2009-04-08
// (on which came also NbLaps and NbCheckpoints in GetCurrentChallengeInfo)
$_multidest_logins = false; 

//--------------------------------------------------------------
$_webaccess = new Webaccess();


// Delete obsolete files if present
//--------------------------------------------------------------
if(file_exists('plugins/plugin.02.fteams.php')) @unlink('plugins/plugin.02.fteams.php');
if(file_exists('plugins/plugin.14.ml_main.php')) @unlink('plugins/plugin.14.ml_main.php');
if(file_exists('plugins/adm/chat.ktlc.php')) @unlink('plugins/adm/chat.ktlc.php');
if(file_exists('plugins/adm/chat.msg.php')) @unlink('plugins/adm/chat.msg.php');
if(file_exists('plugins/adm/chat.debug.php')) @unlink('plugins/adm/chat.debug.php');

// Load Plugins
//--------------------------------------------------------------
$_needenable = false;
console('Loading plugins...');
// special plugins
loadPlugins('./includes', 'plugin.');
// general plugins
loadPlugins('./plugins', 'plugin.');
// custom plugins (mainly for dev)
$_needenable = true;
loadPlugins('./custom', 'plugin.');
$_needenable = false;


//--------------------------------------------------------------
// Init variables
//--------------------------------------------------------------

$_ServerId = 0;
//
$_StatusList = array(0=>array('Code'=>0,'Name'=>'Stopped'), // fake state for Fast
										 1=>array('Code'=>1,'Name'=>'Waiting'),
										 2=>array('Code'=>2,'Name'=>'Launching'), 
										 3=>array('Code'=>3,'Name'=>'Running - Synchronization'), // BeginRace, EndRound
										 4=>array('Code'=>4,'Name'=>'Running - Play'), // BeginRound
										 5=>array('Code'=>5,'Name'=>'Running - Finish')); // EndRace
$_old_Status = $_StatusList[0];
$_Status = $_old_Status;
$_StatusCode = $_Status['Code'];

$_ChallengeList = array();
$_CurrentChallengeIndex = -1;
$_NextChallengeIndex = -1;
$_ChallengeInfo = array('Name'=>'unsure');
$_NextChallengeInfo = array('Name'=>'unsure');
$_PrevChallengeInfo = array('UId'=>'prev','Name'=>'unsure','FileName'=>'');

$_GameInfos = array();
$_NextGameInfos = array();
$_PrevGameInfos = array();

$_FGameMode = '';
$_NextFGameMode = '';
$_PrevFGameMode = '';
$_NextFGameModeFails = 0;
$_FGameModes = array(); // each element is 'game-mode-name'=>array('GameInfos'=>array('GameMode'=>..,...),'Set'=>array(...),'Next'=>array(...),'Current'=>array(...))

$_GameModeString = '';

$_ServerOptions = array('Name'=>'');

$_PlayerList = array();
$_old_PlayerList = array();

$_Ranking = array();
$_old_Ranking = array();

$_PlayerInfo = array();
$_NetworkStats = array();

$_FWarmUpDuration = 0; // FWarmUp duration (game config for next)
$_FWarmUp = 0; // FWarmUp current (duration)
$_FWarmUpState = 0; // FWarmUp current state (0 to duration)
$_NextFWarmUp = 0; // FWarmUp set at EndRace for next race (duration)
$_WarmUp = false; // GetWarmUp() state at BeginRace/BeginRound
$_RoundCustomPoints = array(); // GetRoundCustomPoints()
$_CallVoteRatios = array(); // GetCallVoteRatios()
$_ForcedMods = array(); // GetForcedMods()
$_ForcedMusic = array(); // GetForcedMusic()
$_ServerCoppers = 0;
$_LadderServerLimits = array();

// Cup CheckEndMatchCondition value ('Finished','ChangeMap','Playing')
$_EndMatchCondition = 'Playing';


$_BeginRaceSeen = false;
$_NumberOfChecks = 0;

$_GuestList = array();
$_IgnoreList = array();
$_BanList = array();
$_BlackList = array();

$_BestChecks = array();
$_BestChecksName = '';
$_IdealChecks = array();

$_IsFalseStart = false;

// manual flow control transition
$_MFCTransition = array('Time'=>0,'Transition'=>'','Event'=>'','DelayTime'=>0,'Proceed'=>false);
$_MFCTransitionGet = '';


// $_DegradedModePlayers can be set in fast.php, default depending of server configured rates (5 to 38)
$_DegradedMode = 0; 
$_OrigVehicleNetQuality = 0;
$_OrigIsP2PUpload = false;
$_OrigIsP2PDownload = false;

$_players = array();
$_teams = array();
$_guest_list = array();
$_ignore_list = array();
$_ban_list = array();
$_black_list = array();

$_bills = array();

$_used_languages = array();

$_oldTime = -1;
$_mapTime = -1;
$_inMapTime = -1;

$_map_control = true;

$_counter = 0;


$_ladderlist[0] = 'inactive';
$_ladderlist[1] = 'active';
$_ladderlist[2] = 'active';

$_laddermode[0] = 0;
$_laddermode[1] = 1;
$_laddermode[2] = 1;

$_laddermode['inactive'] = 0;
$_laddermode['off'] = 0;
$_laddermode['normal'] = 1;
$_laddermode['forced'] = $_laddermode[2];
$_laddermode['active'] = $_laddermode[2];
$_laddermode['on'] = $_laddermode[2];


if(!isset($_netlost_limit)){
	$_netlost_limit = 4000;
}

if(!isset($_control_maxplayers)){
	$_control_maxplayers = true;
}

if(!isset($_ladderserver_guestlimit)){
	$_ladderserver_guestlimit = 60000;
}

//$_NetStatsColors = array('$ff6','$ff5','$ff4','$ff3','$ff2','$ef2','$df2','$de2','$dd2','$dc2','$db2','$da2','$d92','$d82','$d72','$d62','$d52','$d42','$d32','$d22','$c22','$b22','$a22','$922','$822','$722','$622','$522','$422','$322','$311','$211','$200','$100','$000');
$_NetStatsColors = array('$ff2','$ef2','$df2','$de2','$dd2','$dc2','$db2','$da2','$d92','$d82','$d72','$d62','$d52','$d42','$d32','$d22','$c22','$b22','$a22','$922','$822','$722','$622','$522','$422','$322','$311','$211','$200','$100','$000');

if(!isset($_Quiet)){
	$_Quiet = false;
}


//$_currentTime = floor(microtime(true)*1000);
//--------------------------------------------------------------
// Init Plugins
//--------------------------------------------------------------
callFuncs('Init');


//----------------------------------------------------------------
if(!isset($_locale[$_locale_default]))
  $_locale_default = 'en';



//----------------------------------------------------------------
// list methods to help building the $_methods_related_to_call array
if(false){
	console2('$_methods_related_to_call = array(');
	foreach($_methods_list as $method){
		$method2 = 'G'.substr($method,1);
		$comm = isset($_methods_related_to_call[$method]) ? '//' : '';
		if(strncmp($method,'Set',3)==0 && isset($_methods_list[$method2]))
			console2("    {$comm}'$method'=>array('$method2'),");
		else
			console2("    {$comm}'$method'=>false,");
	}
	console2('  );');
}



//----------------------------------------------------------------
// Run WMServer...
//----------------------------------------------------------------

if($_islogedin){
	console2("\n");
	console2('##############################################');
	console2("$_FAST_tool $_FASTver running on: {$_DedConfig['server_ip']}:{$_DedConfig['xmlrpc_port']}, TM account: {$_DedConfig['login']}");
	console2('(c) 2008 by Gilles Masson');
	console2("Server: $_Game, Version: {$_Version['Version']}, Build: {$_Version['Build']}");
	if(isset($_SERVER['PWD']) && isset($_SERVER['_']))
		console2('Started using php '.phpversion().' ('.$_SERVER['_'].' from '.$_SERVER['PWD'].')');
	else
		console2('Started using php '.phpversion());
	console2("##############################################\n");
	callFuncs('StartToServe');
	console2('');
	console('Starting to serve... ('.date('D d M Y').')');
}

$maxtime = 0;
$fulltime = 0;
$maxtime2 = 0;
$fulltime2 = 0;
$_currentTime = floor(microtime(true)*1000);
$_sleep_time = $_sleep_time2;
$_tickTime = $_currentTime;
$_tick = false;
$loopTime = $_currentTime;

$_beginrace_time = $_currentTime;
$_beginround_time = $_currentTime;
$_endround_time = $_currentTime;
$_endrace_time = $_currentTime;

//------------------------ Init calls to dedicated ------------------------
manageInit();


// -------------------------- main loop part ------------------------------
while($_islogedin){

	// ---------------- wait if previous loop was too short -----------------
	// increase min loop time with player number, it will reduce a little the
	// callback precision (incresing the delay to handle them) , but should save cpu...
	$minLoopTime = 10 + count($_PlayerList)/2; 
	$loopTimeOld = $loopTime;
	$loopTime = floor(microtime(true) * 1000);
	$sleepTime = $loopTime - $loopTimeOld;
	if($sleepTime < $minLoopTime)
		doSleep($minLoopTime);

	// ------------ compute $sleepTime to wait until next tick --------------
	$sleepTime = $_tickTime + 10 - floor(microtime(true) * 1000);
	if($sleepTime < 5)
		$sleepTime = 5;

	// -- wait webaccess and callback socket avaible datas, or $sleepTime ---
	$read = array($_client->socket);
	$write = NULL;
	$except = NULL;

	$nbc = $_webaccess->select($read, $write, $except, 0, $sleepTime * 1000);
	if($nbc === false){
		reconnectTM();
		continue;
	}

	$_oldTime = $_currentTime;
	$_currentTime = floor(microtime(true) * 1000);
	//console("MainLoop:: ".($_currentTime - $_oldTime)."ms , sT={$sleepTime} , mLT={$minLoopTime} , {$_sleep_time}");

	// ---------------- compute $_tick and choose sleep_time ----------------
	if($_currentTime >= $_tickTime){
		$_tick = true;

		// adjust sleep time depending on number of players and mode
		// (less accurracy is needed in TA, and when more players : it can save some % cpu)
		$nbp = count($_PlayerList);
		if($nbp > 0){
			$_sleep_time = $_sleep_time1 + ( (@$_GameInfos['GameMode'] == TA) ? 2 * $nbp : $nbp);
			if($_sleep_short)
				$_sleep_time = (int) floor($_sleep_time / 2);
		}else{
			$_sleep_time = $_sleep_time2;
		}

		if(($_tickTime + $_sleep_time - 30) < $_currentTime)
			$_tickTime = $_currentTime;
		$_tickTime += $_sleep_time;

	}else{
		$_tick = false;
	}

	// loop info in console/log
	if(!$_tick || ($_currentTime - $_oldTime > $_sleep_time+20) || ($_tickTime-$_currentTime == $_sleep_time)){
		if($_debug>3) console("loop - $_currentTime - ".($_currentTime-$_oldTime).' - '.($_tickTime-$_currentTime).($_tick?' - tick':''));
	}

	// --------------- server callbacks -------------
	manageCallbacks($nbc > 0);

	// ------------------do all changes stuff and data events ---------------
	manageEvents($_tick);


	// set php time to live
	set_time_limit($_max_exec_time);
}

if($_islogedin){
	console("Game server stopped ! (exec time limit ?)");
}


//Status[Code]=1, Status[Name]=Waiting
//Status[Code]=2, Status[Name]=Launching
//Status[Code]=3, Status[Name]=Running - Synchronization
//Status[Code]=4, Status[Name]=Running - Play
//Status[Code]=5, Status[Name]=Running - Finish



?>
