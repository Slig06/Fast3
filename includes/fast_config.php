<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

// Please do not modify it, it is used for autoupdate and dedimania info
// eventually modofied FASTver strings will be refused by Dedimania !
$_FASTver = '3.2.4c';

//$_GAMEFIXURL = 'http://www.tm-forum.com/viewtopic.php?f=6&t=16357';
$_GAMEFIXURL = 'http://www.tm-forum.com/viewtopic.php?f=28&t=19288';

//--------------------------------------------------------------
// TMF
define('SCRIPT',-1);
define('MINMODE',0);
define('ROUNDS',0);
define('TA',1);
define('TEAM',2);
define('LAPS',3);
define('STUNTS',4);
define('CUP',5);
define('MAXMODE',5);

$_modelist[0] = 'Rounds';
$_modelist[1] = 'TimeAttack';
$_modelist[2] = 'Team';
$_modelist[3] = 'Laps';
$_modelist[4] = 'Stunts';
$_modelist[5] = 'Cup';


//--------------------------------------------------------------
// some non english locale can change the way that float numbers are written
// and break manialinks values !  test it and try to change it if needed...
$loc = localeconv();
if($loc['decimal_point'] != '.'){
	// number decimal point is not a dot !!!
	echo 'Set locale: '.setlocale(LC_ALL, 'C');
	$loc = localeconv();
	if($loc['decimal_point'] != '.'){
		echo 'Sorry failed to set a locale to get a dot decimal point... you can try to fix it by setting language env variables before starting the script';
		exit(1);
	}
}

//--------------------------------------------------------------
if(isset($_server_timezone) && $_server_timezone != '')
	date_default_timezone_set($_server_timezone);
if(function_exists('date_default_timezone_set') && 
	 function_exists('date_default_timezone_get')){
	@date_default_timezone_set(date_default_timezone_get());
	$_server_timezone = date_default_timezone_get();
	echo "\nTimezone: {$_server_timezone}\n";
	echo "If you have a timezone problem, then try to set in fast.php {$_server_timezone}\nto a valid value, for example: $_server_timezone='Europe/Paris'\n\n";
}

$_currentTime = floor(microtime(true)*1000);

//--------------------------------------------------------------
// no need for so much memory, btw it doesn't hurt
ini_set('memory_limit','300M');

//--------------------------------------------------------------
if(!isset($_debug)){
	$_debug = 1; // main general debug level
}
if(!isset($_mldebug)){
	$_mldebug = 1; // main general manialinks debug level
}
if(!isset($_dedebug)){
	$_dedebug = 0; // dedicated callbacks/events debug level
}
if(!isset($_cdebug)){
	$_cdebug = array(); // specific debug info for specified dedicated methods calls, example:
	// $_cdebug['ForceSpectator'] = 0;  // means that addCall(,'ForceSpectator',) will show a calling line info if debug level > 0
}
if(!isset($_edebug)){
	$_edebug = array(); // specific debug info for specified Fast events calls, example:
	// $_edebug['PlayerCheckpoint'] = 0;  // means that addEvent(,'ForceSpectator',) will show a calling line info if debug level > 0
}
if(!isset($_pdebug)){
	$_pdebug = array(); // specific debug level overiding the general one during specified plugin events, examples:
	// $_pdebug['ml_main']['mldebug'] = 4; // all 'ml_main'events will have $_mldebug = 4
	// $_pdebug['ALL']['BeginRound']['debug'] = 6; // BeginRound() will have $_debug = 6 for all plugins
	// $_pdebug['fteamrelay']['debug'] = 3; // all 'fteamrelay' events will have $_debug = 3
	// $_pdebug['fteamrelay']['PlayerConnect']['debug'] = 5; // except fteamrelayPlayerConnect() will have $_debug = 5
	// $_pdebug['fteamrelay']['BeginRound']['debug'] = 1; // except fteamrelayBeginRound() will have $_debug = 1
}
if(!function_exists('memory_get_usage')){
	$_memdebug = 0;
}elseif(!isset($_memdebug)){
	$_memdebug = 1;
}
if($_memdebug > 0){
	$_memdebugmode = true;
	$_memdebugs = array();
	$_memdebugs['callMulti-sync'] = 0;
	$_memdebugs['callMulti-async'] = 0;
	$_memdebugs['callMulti-async-resp'] = 0;
}
if(!isset($_loadsimul)){
	$_loadsimul = 0;
}


//--------------------------------------------------------------
if(!isset($_remote_controller_chat_is_admin)){
	$_remote_controller_chat_is_admin = false;
}


//--------------------------------------------------------------
ini_set('display_errors','On');
if($_debug>3) // report all and strict errors!
	error_reporting(E_STRICT | E_ALL);
elseif($_debug>0) // report all errors!
  error_reporting(E_ALL);
else // report only important errors!
  error_reporting(E_ALL ^ E_NOTICE);



//--------------------------------------------------------------
// change current to fast directory if ever started from another
if(isset($_SERVER['SCRIPT_FILENAME'])){
	chdir(dirname($_SERVER['SCRIPT_FILENAME']));
}elseif(isset($_SERVER['argv'][0])){
	chdir(dirname($_SERVER['argv'][0]));
}


//--------------------------------------------------------------
// maximal execution time for one loop.
// set this higher as your trackloading-time, Slower pcs should use higher timeout.
$_max_exec_time = 300;


//--------------------------------------------------------------
// Fast will be halted each main loop to reach this max time by loop. (milliseconds)
$_sleep_time = 200;  // current used value
$_sleep_time1 = 200; // when players are connected
$_sleep_time2 = 1000; // when no player
$_sleep_short = false; // set to true to reduce main loop time (back to false at BeginRace)

//--------------------------------------------------------------
// open general log file
//--------------------------------------------------------------
if(!file_exists('fastlog'))
  mkdir('fastlog');
$_logfilename = 'fastlog/fastlog.txt';
$_logfile = fopen($_logfilename,'ab');


//--------------------------------------------------------------
// List of disabled plugins, locale files, and database files
//--------------------------------------------------------------
$_DisabledPlugins = isset($_DisabledPlugins) ? array_flip($_DisabledPlugins) : array();
$_EnabledPlugins = isset($_EnabledPlugins) ? array_flip($_EnabledPlugins) : array();


//--------------------------------------------------------------
// read xml config
//--------------------------------------------------------------
require_once('includes/xml_parser.php');
require_once('includes/fast_general.php');
loadLocales();
loadConfig();


//--------------------------------------------------------------
// fast includes
//--------------------------------------------------------------
require_once('includes/urlsafebase64.php');
require_once('includes/GbxRemote.fast.php');
require_once('includes/GbxRemote.response.php');
require_once('includes/web_access.php');
require_once('includes/xmlrpc_db_access.php');
require_once('includes/fast_common.php');


//--------------------------------------------------------------
// verify if PHP version >= 5.0.0
//--------------------------------------------------------------
if($_debug>2) console('Test php version...');
$_hasphp5=(version_compare(phpversion (), '5.0.0')>=0);
if(!$_hasphp5){
	die("ERROR: Sorry, FAST need php-5.0 or later !\n\r");
}


// ----------- short game name from GetVersion name ------------
$_game_name['TmForever'] = 'TMU';


//--------------------------------------------------------------
// Nadeo's Server connection
//--------------------------------------------------------------
$_client = new IXR_Client_Gbx;

if(!isset($_want_flowcontrol)) $_want_flowcontrol = true;
if(!isset($_need_flowcontrol)) $_need_flowcontrol = false;
if($_need_flowcontrol && !$_want_flowcontrol) $_want_flowcontrol = true;
$_use_flowcontrol = false;
$_methods_list = array();
$_use_cb = false;
$_is_dedicated = true;
$_is_ingame = false;
$_is_relay = false;
$_islogedin = false;
$_starting = true;
$_Game = 'tmx';

$_Version = array();
$_SystemInfos = array();
$_ServerInfos = array();
$_ServerPackMask = '';
$_relays = array();
$_master = array();

//--------------------------------------------------------------
$_fast_id = rand(11111,99999);
$_fastecho = time().'.'.$_fast_id;

// ------------------ connect to TM server ---------------------
set_time_limit(600);
connectTM();
// if not connected then die
if(!$_islogedin){
	die("Fatal error: could not connect to TM server !\n");
}

// ------------------ filter locales utf8 ----------------------
filterLocalesUtf8();


// ---------- set server name for ChatSend messages ------------
if(!isset($_ServerChatName)){
	$_ServerChatName = '';
}elseif($_ServerChatName==''){
	$_ServerChatName = $_SystemInfo['ServerLogin'];
}


//--------------------------------------------------------------
// open specifics log files
//--------------------------------------------------------------
fclose($_logfile);
$_logfilename = 'fastlog/fastlog.'.strtolower($_Game).'.'.$_DedConfig['login'].'.txt';
$_logfile = fopen($_logfilename,'ab');

$_chatfilename = 'fastlog/chatlog.'.strtolower($_Game).'.'.$_DedConfig['login'].'.txt';
if($_do_chat_log){
	$_chatfile = fopen($_chatfilename,'ab');
}


//--------------------------------------------------------------
// Load admin file
//--------------------------------------------------------------
$_adminfile = 'admin.'.strtolower($_Game).'.'.$_DedConfig['login'].'.xml.txt';
$_adminfile_date = 0;
if(!isset($_AdminList) || !is_array($_AdminList))
		 $_AdminList = array();
loadAdmins(false);



//--------------------------------------------------------------
// store Fast and dedicated state
//--------------------------------------------------------------
$_StoreFile = 'store.'.strtolower($_Game).'.'.$_DedConfig['login'].'.fast';

$_StoredInfos = array();

// restore previous state ?
if(array_search('restore',$_SERVER['argv'])!==false){
	console('Will try to restore previous state !');
	$_RestorePrevious = true;
}elseif(array_search('norestore',$_SERVER['argv'])!==false){
	$_RestorePrevious = false;
}elseif(!isset($_RestorePrevious)){
	$_RestorePrevious = false;
}

// restore recent state ?
if(array_search('restorelive',$_SERVER['argv'])!==false){
	$_RestoreLive = true;
}elseif(array_search('norestorelive',$_SERVER['argv'])!==false){
	console('Will not try to restore previous live state !');
	$_RestoreLive = false;
}elseif(!isset($_RestoreLive)){
	$_RestoreLive = true;
}



//--------------------------------------------------------------
// Filename to save current matchsettings in (will go in GameData/Tracks/)
//--------------------------------------------------------------
$_last_matchsettings = 'MatchSettings/last.'.strtolower($_Game).'.'.$_DedConfig['login'].'.txt';


//------------------------------------------
// Load config function, all var must be stated as global in the file
//------------------------------------------
// CALLED BY: main program
//------------------------------------------
function loadConfig(){
  global $_debug,$_DedFile,$_DedConfig,$_FASTver,$_autoupdateStop10,$_netstatRunDelay,$_netstatRunDelay1,$_netstatRunDelay2,$_netstatSyncDelay,$_playerRunDelay;

	$_DedFile = 'ded.cfg';
	$_DedConfig = array();
	if($_SERVER['argc'] > 1){
		$df1 = './GameData/Config/'.$_SERVER['argv'][1];
		$df2 = './'.$_SERVER['argv'][1];
		$df3 = $_SERVER['argv'][1];
		if(file_exists($df1))
			$_DedFile = $df1;
		elseif(file_exists($df2))
			$_DedFile = $df2;
		elseif(file_exists($df3))
			$_DedFile = $df3;
		else
			die("ERROR: give a dedicated.cfg like file as first argument !!!\n\rCan't find file $df1 or $df2 !\n\r");
	}else{
		die("ERROR: give a dedicated.cfg like file as first argument !\n\r");
	}
	if(array_search('update_stop10',$_SERVER['argv']) !== false)
		$_autoupdateStop10 = true;
	else
		$_autoupdateStop10 = false;


	if(file_exists($_DedFile)){	
		console2("\nUsing dedicated config file : {$_DedFile}\n");

		$config = xml_parse_file($_DedFile);

		$nation = '';
		if(isset($config['dedicated']['masterserver_account']['nation']) &&
			 $config['dedicated']['masterserver_account']['nation']!='')
			$nation = $config['dedicated']['masterserver_account']['nation'];

		//$server_ip = 'localhost';
		$server_ip = '127.0.0.1';

		if(isset($config['dedicated']['system_config']['server_ip']))
			$config['dedicated']['system_config']['server_ip'] = 
				trim($config['dedicated']['system_config']['server_ip']);
			
		if(isset($config['dedicated']['system_config']['force_ip_address']))
			$config['dedicated']['system_config']['force_ip_address'] = 
				trim($config['dedicated']['system_config']['force_ip_address']);
		
		if(isset($config['dedicated']['system_config']['bind_ip_address']))
			$config['dedicated']['system_config']['bind_ip_address'] = 
				trim($config['dedicated']['system_config']['bind_ip_address']);

		if(isset($config['dedicated']['system_config']['server_ip']) &&
			 $config['dedicated']['system_config']['server_ip']!=''){
			$server_ip = $config['dedicated']['system_config']['server_ip'];
			console2("Note: you have set a value to <server_ip> in dedicated config,\n"
							 ."      it is ok if Fast is not on the same computer as your server,\n"
							 ."      or if on same computer but <force_ip_address> or\n"
							 ."      <bind_ip_address> are configured, else it should be set empty !");

		}elseif(isset($config['dedicated']['system_config']['force_ip_address']) &&
						$config['dedicated']['system_config']['force_ip_address']!=''){
			$server_ip = $config['dedicated']['system_config']['force_ip_address'];
			console2("Note: you have set a value to <force_ip_address> in dedicated config,\n"
							 ."      it is ok if Fast is not on the same computer as your server, or \n"
							 ."      eventually in very special case, else it should always be set empty !\n"
							 ."      Also, if you really need <force_ip_address> to make your server work,\n"
							 ."      and if Fast is on the same computer, then add \n"
							 ."      <server_ip>127.0.0.1</server_ip> in the dedicated configuration.");

		}elseif(isset($config['dedicated']['system_config']['bind_ip_address']) &&
						$config['dedicated']['system_config']['bind_ip_address']!=''){
			$server_ip = $config['dedicated']['system_config']['bind_ip_address'];
			console2("Note: you have set a value to <bind_ip_address> in dedicated config,\n"
							 ."      it is ok if Fast is not on the same computer as your server, or\n"
							 ."      eventually in very special case, else it should always be set empty !\n"
							 ."      Also, if you really need <bind_ip_address> to make your server work,\n"
							 ."      and if Fast is on the same computer, then add\n"
							 ."      <server_ip>127.0.0.1</server_ip> in the dedicated configuration.");
		}

		// pool values less often when remote than local
		// don't set less than 1000 for getnetstat
		if($server_ip == '127.0.0.1' || $server_ip == 'localhost'){
			$server_ip = '127.0.0.1';
			$_netstatRunDelay1 = 5000;
			$_netstatRunDelay2 = 2000;
			$_netstatRunDelay = $_netstatRunDelay1;
			$_netstatSyncDelay = 1500;
			$_playerRunDelay = 30100;

		}else{
			$_netstatRunDelay1 = 10000;
			$_netstatRunDelay2 = 4000;
			$_netstatRunDelay = $_netstatRunDelay1;
			$_netstatSyncDelay = 2000;
			$_playerRunDelay = 60200;
		}

		$public_ip = '0.0.0.0';
		if(isset($config['dedicated']['system_config']['force_ip_address']) &&
			 $config['dedicated']['system_config']['force_ip_address']!='')
			$public_ip = $config['dedicated']['system_config']['force_ip_address'];

		$superadmin = '';
		if(isset($config['dedicated']['authorization_levels']['level']['name']) &&
			 $config['dedicated']['authorization_levels']['level']['name']=='SuperAdmin')
			$superadmin = $config['dedicated']['authorization_levels']['level']['password'];
		elseif(isset($config['dedicated']['authorization_levels']['level'][0]['name']) &&
					 $config['dedicated']['authorization_levels']['level'][0]['name']=='SuperAdmin')
			$superadmin = $config['dedicated']['authorization_levels']['level'][0]['password'];
		elseif(isset($config['dedicated']['authorization_levels']['level'][1]['name']) &&
					 $config['dedicated']['authorization_levels']['level'][1]['name']=='SuperAdmin')
			$superadmin = $config['dedicated']['authorization_levels']['level'][1]['password'];
		elseif(isset($config['dedicated']['authorization_levels']['level'][2]['name']) &&
					 $config['dedicated']['authorization_levels']['level'][2]['name']=='SuperAdmin')
			$superadmin = $config['dedicated']['authorization_levels']['level'][2]['password'];
		else
			die("ERROR: missing SuperAdmin password in $_DedFile !\n\rConfig contents: ".print_r($config,true));

		if(!isset($config['dedicated']['authorization_levels']['level']) ||
			 !isset($config['dedicated']['masterserver_account']['login']) ||
			 !isset($config['dedicated']['masterserver_account']['password']) ||
			 !isset($config['dedicated']['system_config']['xmlrpc_port']))
			die("ERROR: missing infos in $_DedFile !\n\rConfig contents: ".print_r($config,true));

		$_DedConfig = array_merge($config['dedicated']['masterserver_account'],$config['dedicated']['system_config']);
		$_DedConfig['public_server_ip'] = $public_ip;
		$_DedConfig['server_ip'] = $server_ip;
		$_DedConfig['super_admin'] = $superadmin;
		$_DedConfig['nation'] = $nation;

		if($_debug>3) debugPrint("loadConfig - _DedConfig",$_DedConfig);
		
	}else{
		die("ERROR: You have to give a dedicated.cfg like file in argument !\n\r");
	}
}


//------------------------------------------
// Load global localization files
//------------------------------------------
function loadLocales(){
	global $_debug,$_locale,$_locale_default,$_DisabledPlugins,$_EnabledPlugins;
	$_locale = array();

	if(!isset($_locale_default))
		 $_locale_default = 'en';

	// get standard locales (./plugins/locale.xx.xml.txt)
	if($dir_handler = opendir('./plugins')){
		while($file = readdir($dir_handler)){
			$pos = strpos($file, 'locale.');
			$pos2 = strpos($file, '.xml.txt');
			if($pos !== false && $pos == 0 && ($pos2 == strlen($file) - 8)){
				if(!isset($_DisabledPlugins[$file])){
					//console("Load locale file: ./plugins/{$file}");
					loadLocaleFile('./plugins/'.$file);
				}else
					console("Disabled in config: ./plugins/{$file}");
			}
		}
		closedir($dir_handler);
	}

	// get custom locale (./plugins/locale.custom.xml.txt) again, to overwrite other values
	if(file_exists('./plugins/locale.custom.xml.txt')){
		if(!isset($_DisabledPlugins['locale.custom.xml.txt']))
			loadLocaleFile('./plugins/locale.custom.xml.txt');
		else
			console("Disabled in config: ./plugins/locale.custom.xml.txt");
	}


	// custom directory
	if(!file_exists('custom'))
		mkdir('custom');
	// get custom locales (./custom/locale.xx.xml.txt)
	if($dir_handler = opendir('./custom')){
		while($file = readdir($dir_handler)){
			$pos = strpos($file, 'locale.');
			$pos2 = strpos($file, '.xml.txt');
			if($pos !== false && $pos == 0 && ($pos2 == strlen($file) - 8)){
				if(isset($_EnabledPlugins[$file]) || !isset($_DisabledPlugins['custom']))
					loadLocaleFile('./custom/'.$file);
				else
					console("Not enabled in config: ./custom/{$file}");
			}
		}
		closedir($dir_handler);
	}

	// get custom locale (./custom/locale.custom.xml.txt) again, to overwrite other values
	if(file_exists('./custom/locale.custom.xml.txt')){
		if(isset($_EnabledPlugins['locale.custom.xml.txt']) || !isset($_DisabledPlugins['custom']))
			loadLocaleFile('./custom/locale.custom.xml.txt');
		else
			console("Not enabled in config: ./custom/locale.custom.xml.txt");
	}

	if($_debug>8) debugPrint("loadLocale (default=$_locale_default) - _locale",$_locale);
}

//------------------------------------------
// filter utf8 in localization files
//------------------------------------------
function filterLocalesUtf8(){
	global $_debug,$_locale,$_locale_default,$_DisabledPlugins,$_EnabledPlugins;
	foreach($_locale as &$lang){
		foreach($lang as $msg){
			$msg = tm_substr($msg);
		}
	}
}


?>
