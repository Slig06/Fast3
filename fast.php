<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       http://slig.free.fr/fast3.2/
// Date:      15.12.2010
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// lines beginning with // are comments !!!
//

// Can be used to ask Fast to try to restore most of its states if the script have been stopped/crashed
//  only few seconds before but the dedicated still worked.
// It defaults to true. Can also be set in commandline, using 'restorelive'/'norestorelive' arg.
//$_RestoreLive = true;

// Can be used to ask Fast to try to restore most of the dedicated and script previous states,
// including server name, player and spec passwords, gamemode, challenges list, etc.
// It defaults to false. Can also be set in commandline, using 'restore'/'norestore' arg.
//$_RestorePrevious = false;


// Can be used to set a specific FGameMode at startup, most usuals
// are 'TeamRelay','TeamLaps','TeamRounds' (else set empty '' or don't set at all)
//$_StartFGameMode = 'TeamRelay';


// Can be used to send calls to server at startup (look in ListMethods.html for more infos)
// each element should be an array('TMServerMethod',...)
// example: $_starting_calls = array( array('AutoSaveReplays',true) );
//$_starting_calls = array();


// if your server fails to get right php timezone, or if you want to set
// another one. See values at http://www.php.net/manual/en/timezones.php
//$_server_timezone = 'Europe/Paris';


// Configure those values if you want Fast to be able to use a locale mysql database !
// You also need your php to have mysql support, and of course a working mysql server !
//$_DBserver = 'localhost';
//$_DBbase = 'mysql database name';
//$_DBuser = 'mysql login';
//$_DBpassword = 'mysql pass';


// should a remote controller chat be able to send admin commands ? (the chat responses won't be seen)
$_remote_controller_chat_is_admin = false;


// Used to add the the number of laps a player had before a disconnection
// in Laps mode.
// The effect is only visible in live infos and logs, not in game score panel
$_LapsDiscoFix = false;


// List of chat commands which should be disabled
// $_DisableChatCommands = array('play','spec');
$_DisabledChatCommands = array();


// List of plugins names which should be disabled
// (can also be used to disable a locale or database xml file)
// Note: you can also just remove the concerned file to remove a plugin
// $_DisabledPlugins = array('ml_vote');
$_DisabledPlugins = array();


// List of plugins names *in ./custom/ directory* which should be enabled
// (can also be used to enable a locale or database xml file *in ./custom/ directory*)
// Note: setting 'custom' will enable all files and plugins in ./custom/ directory !
// $_EnabledPlugins = array('custom');
$_EnabledPlugins = array();


// Automatic callvote enable/disable when admins are here or not
// The vote timeout value is in milliseconds.
// set a negative value to activate automatic feature
// To change value in game, use : /adm votetimeout xxx
$_CallVoteTimeOut = -60000;


// Custom Rounds mode points replacing the standard 10,6,4,3,2,1... (TMU dedicated only!)
// standard possible values: '','motogp','motogp5','champcar'
$_roundspoints_rule = 'motogp'; 

// here are predefined score arrays for $_roundspoints_rule values :
//$_roundspoints_points['f1gp'] = array(10,8,6,5,4,3,2,1); // F1 GP style points
//$_roundspoints_points['motogp'] = array(25,20,16,13,11,10,9,8,7,6,5,4,3,2,1); // MotoGP style points
//$_roundspoints_points['motogp5'] = array(30,25,21,18,16,15,14,13,12,11,10,9,8,7,6,5,4,3,2,1); // MotoGP+5 style points
//$_roundspoints_points['champcar'] = array(31,27,25,23,21,19,17,15,13,11,10,9,8,7,6,5,4,3,2,1); // Champ Car style points

// You can add your own custom array just modifying the array name and contents, and uncommenting the line :
//$_roundspoints_points['own'] = array(10,8,6,5,4,3,2,1); // my custom points


// Custom Rounds limit based on number of rounds played (ony one player finish). Set > 0 to activate mode.
$_roundslimit_rule = -1;


// Minimum gap of 2 between scores to win in Team mode
//  0 is normal, >1 value is limit to reach with 2 more points than opponents
$_teamgap_rule = 0; 


// Set to true to hide score panel at end of rounds
$_scorepanel_hide = true;
// Set to true to delay the right round panel until end of round
$_scorepanel_round_hide = true;



// ask to vote for the challenge at end ?
// only those who don't have already voted on the map and have made a time on it
// will be asked, others still can make a vote manually  ;)
$_ml_vote_ask = false;

// style for challenge vote. Current available values : 0=0 to 10, 1=0 to 5
//                 2=bad/maybe/good, 3=trashit/keepit, 4=bad/good, 5=no/yes
$_vote_list_default = 0;



// Map autorestart , will autorestart automatically the map except
// if using /adm next (or a nextchallenge vote success while the podium)
$_autorestart_map = false;


// Map autorestart , will autorestart automatically before playing
// when the map just changed.
// It can be used also to restart the new map later : set a numeric int value for
// a delay in seconds, 'checkpoint' to restart at first passed checkpoint, 'finish'
// to restart at first finish, 'round' to restart at end of first round, or 'on'
// for immediate.
$_autorestart_newmap = false;



// The 'Connection: ...' message will be display if less players
// than this value are on the server (set 0 to disable message)
$_welcome_connect = 50;

// The 'Disconnection: ...' message will be display if less players
// than this value are on the server (set 0 to disable message)
$_welcome_disconnect = 50;


// Default matchmode which will be used if omited in: /match start [matchmode]
//$_match_mode = 'gc7';

// Will show 'FreePlay' onscreen when a match is not on.
//$_match_show_freeplay = true;


// use it instead of current server name for ChatSend server messages
// (if set as empty string then server login will be used)
//$_ServerChatName = ''; 


// Can be use to have automatic copy of matchlog file at end of map
// Be very carefull : it will stop other fast stuff while copying,
// so use remote copy for special case only !
//$_matchlog_copy = "/var/www/matchlogs/";
//$_matchlog_copy = "ftp://xxxx:yyy@ftpperso.free.fr/matchlogs/";
//$_matchlog_url = "http://xxxx.free.fr/matchlogs/" 



// Can be use to have automatic copy of match file at end of map
// Be very carefull : it will stop other fast stuff while copying,
// so use remote copy for special case only !
//$_match_copy = "/var/www/matchlogs/";
//$_match_copy = "ftp://xxxx:yyy@ftpperso.free.fr/matchlogs/";
//$_match_url = "http://xxxx.free.fr/matchlogs/" 



// Can be use to have automatic copy of ktlc result file at end of ktlc
// Be very carefull : it will stop other fast stuff while copying,
// so use remote copy for special case only !
//$_ktlc_result_copy = "/var/www/ktlc/";
//$_ktlc_result_copy = "ftp://xxxx:yyy@ftpperso.free.fr/ktlc/";
//$_ktlc_result_url = "http://xxxx.free.fr/ktlc/" 




// general debug level
$_debug = 1;

// general debug level for manialinks
$_mldebug = 0;

// debug level for dedicated callbacks/events
$_dedebug = 0;

// debug level for memtests
$_memdebug = 0;

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

// do a log of chat messages ?
$_do_chat_log = true;





// are Buddy notifications ON by default ? (only on dedicated 2008-05-07+)
$_buddy_notify_default = false;

// are manialinks ON by default ?
$_ml_default = true;

// are live infos ON by default ?
$_liveinfos_default = true;
// is live player position and player numbers ON by default ?
$_live_position_default = true;
// are live checkpoints gaps ON by default ?
$_live_checkpoints_default = true;
// are live checkpoints gaps ON by default ?
$_live_top_default = true;
// are live players positions and gaps ON by default ?
$_live_players_default = 1;  // 0=off, 1=on, 2=on in high pos

// is the 7 best times panel of current challenge ON by default ?
$_bests_default = 1;  // 0=off, 1=when not playing, 2=on
// is the times panel ON by default ? (records etc.)
$_times_default = true;
// is the spec players panel ON by default ?
$_spec_players_default = true;
// is the spec cp/lap info ON by default ?
$_spec_lapinfo_default = true;

// extended map infos ON by default ?
$_mapinfo_default = 1; // 0=off, 1=extended, 2=none
// number of players/specs on server ON by default ?
$_playernumber_default = true;
// chat panel state  by default
$_chatpanel_default = 2;  // 0=off, 1=when not playing, 2=on

// do admins see netlost by default ?
$_netlost_admin_default = true;

// are old fashion notices ON by default ?
$_old_notices_default = false;

// timeout limit (ms) after which players are shown as netlost
//$_netlost_limit = 4000;

// make control that max players is respected (ForceSpectator can surpass it)
//$_control_maxplayers = true;



// set milliseconds without known network activity before kick player
//$_NetStats_KickTime_Playing = 900000;
//$_NetStats_KickTime_Synchro =  80000;

// set preferred spectator status (for the first time the player become spec)
//$_preferredspec_default = 1; // -1=don't change, 0=replay, 1=follow, 2=free

// Number of players to fall in degraded mode
// (if not set then will get a value based on server configured rates)
//$_DegradedModePlayers = 40

// max players to build messages for all localized and
// sent individually, which make more bandwidth for fast
// and for the server.
$_individual_messages = 10;



// Set it if you want to be sure that ManualFlowControl will be used, which is
// needed for some functionalities using events BeforePlay,BeforeEndRound,EndPodium
// (if you set it and the script fail to get it then it will exit)
// Note: never set it to false but if you know what you are doing !
$_need_flowcontrol = true;


//--------------------------------------------------------------
// Call config loading
//--------------------------------------------------------------
require_once('includes/fast_config.php');

//--------------------------------------------------------------
// Call main program
//--------------------------------------------------------------
require_once('includes/fast_main.php');

?>
