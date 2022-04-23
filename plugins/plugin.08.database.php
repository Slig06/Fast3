<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
global $_tm_db,$_tm_db_defaults,$_XmlrpcDB_bad_retry_timeout;
//
// 
registerPlugin('database',8);


// how many seconds before retrying connection
$_XmlrpcDB_bad_retry_timeout = 1200;


// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// !!! modify those values in fast_config_xx.php !!!
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

$_tm_db_defaults = array('Name' => 'Dedimania',
												 'Tag' => 'Dm',
												 'NameForTrack' => 'Dedimania',
												 'ShowNews' => false,
												 'SavePlayerAlias' => false,
												 'SaveRecords' => true,
												 'ShowRecords' => 5,
												 'ShowNewRecordToAll' => 10,
												 'ShowNewRecordToAllRatio' => 0.5,
												 'ShowNewRecordToPlayer' => 50,
												 'SaveMatchs' => false,
);


//------------------------------------------
// Init : 
//------------------------------------------
function databaseInit(){
	global $_debug,$_webaccess,$_locale,$_tm_db,$_FASTver,$_DedConfig,$_tm_db_defaults,$database_ChallengeInfo,$database_GameInfos,$database_lastsent,$_currentTime,$_Game,$_FAST_tool,$_DisabledPlugins;

	$datas = array();
	// get standard Dedimania database config
	if(!isset($_DisabledPlugins['database.dedimania.xml.txt']))
		$datas = loadLocaleFile('./plugins/database.dedimania.xml.txt');
	// get custom database config (for Dedimania overrules and/or custom database) 
	if(!isset($_DisabledPlugins['database.custom.xml.txt'])){
		$data2 = loadLocaleFile('./plugins/database.custom.xml.txt');
		if($data2)
			array_merge_deep($datas,$data2);

		elseif(!isset($_DisabledPlugins['custom'])){
			$data2 = loadLocaleFile('./custom/database.custom.xml.txt');
			if($data2)
				array_merge_deep($datas,$data2);
		}
	}

	// make database info array
	$_tm_db = array();
	$tm_db_num = 0;
	if(isset($datas['fast']['database']) && is_array($datas['fast']['database'])){

		foreach($datas['fast']['database'] as $key => &$dbdatas){

			if(is_array($dbdatas) && isset($dbdatas['url'])){
				if(!is_array($dbdatas['url']))
					$_tm_db[$tm_db_num]['Url'] = array($dbdatas['url']);
				else
					$_tm_db[$tm_db_num]['Url'] = array_values($dbdatas['url']);

				if(isset($dbdatas['name']))
					$_tm_db[$tm_db_num]['Name'] = sprintf($dbdatas['name'],$_Game);
				else
					$_tm_db[$tm_db_num]['Name'] = $_tm_db_defaults['Name'];

				if(isset($dbdatas['tag']))
					$_tm_db[$tm_db_num]['Tag'] = sprintf($dbdatas['tag'],$_Game);
				else
					$_tm_db[$tm_db_num]['Tag'] = $_tm_db_defaults['Tag'];

				if(isset($dbdatas['name_for_track']))
					$_tm_db[$tm_db_num]['NameForTrack'] = $dbdatas['name_for_track'];
				else
					$_tm_db[$tm_db_num]['NameForTrack'] = $_tm_db_defaults['NameForTrack'];

				if(isset($dbdatas['welcome_tag']))
					$_tm_db[$tm_db_num]['WelcomeTag'] = $dbdatas['welcome_tag'];

				if(isset($dbdatas['show_records']))
					$_tm_db[$tm_db_num]['ShowRecords'] = $dbdatas['show_records']+0;
				else
					$_tm_db[$tm_db_num]['ShowRecords'] = $_tm_db_defaults['ShowRecords'];

				if(isset($dbdatas['show_new_record_to_all']))
					$_tm_db[$tm_db_num]['ShowNewRecordToAll'] = $dbdatas['show_new_record_to_all']+0;
				else
					$_tm_db[$tm_db_num]['ShowNewRecordToAll'] = $_tm_db_defaults['ShowNewRecordToAll'];

				if(isset($dbdatas['show_new_record_to_all_ratio']))
					$_tm_db[$tm_db_num]['ShowNewRecordToAllRatio'] = $dbdatas['show_new_record_to_all_ratio']+0;
				else
					$_tm_db[$tm_db_num]['ShowNewRecordToAllRatio'] = $_tm_db_defaults['ShowNewRecordToAllRatio'];

				if(isset($dbdatas['show_new_record_to_player']))
					$_tm_db[$tm_db_num]['ShowNewRecordToPlayer'] = $dbdatas['show_new_record_to_player']+0;
				else
					$_tm_db[$tm_db_num]['ShowNewRecordToPlayer'] = $_tm_db_defaults['ShowNewRecordToPlayer'];

				if(isset($dbdatas['save_player_alias']))
					$_tm_db[$tm_db_num]['SavePlayerAlias'] = $dbdatas['save_player_alias'] != 0;
				else
					$_tm_db[$tm_db_num]['SavePlayerAlias'] = $_tm_db_defaults['SavePlayerAlias'];

				if(isset($dbdatas['show_news']))
					$_tm_db[$tm_db_num]['ShowNews'] = $dbdatas['show_news'] != 0;
				else
					$_tm_db[$tm_db_num]['ShowNews'] = $_tm_db_defaults['ShowNews'];

				$tm_db_num++;
			}else
				console("No Url specified in <fast><database><$key>, ignoring it.");
		}
	}
	//if($_debug>8) debugPrint("databaseInit - _locale",$_locale);
	//if($_debug>8) debugPrint("databaseInit - datas",$datas);
	if($_debug>3) debugPrint("databaseInit - _tm_db",$_tm_db);


	// set connection information and test...
	for($i=0; $i < count($_tm_db); $i++){
		$tmdb =& $_tm_db[$i];
		//debugPrint("databaseInit - _tm_db[$i]",$_tm_db[$i]);
			
		$_tm_db[$i]['MaxRecords'] = $_tm_db[$i]['ShowRecords'];
		if($_tm_db[$i]['MaxRecords'] < $_tm_db[$i]['ShowNewRecordToAll'])
			$_tm_db[$i]['MaxRecords'] = $_tm_db[$i]['ShowNewRecordToAll'];
		if($_tm_db[$i]['MaxRecords'] < $_tm_db[$i]['ShowNewRecordToPlayer'])
			$_tm_db[$i]['MaxRecords'] = $_tm_db[$i]['ShowNewRecordToPlayer'];
			
		console("************* (db.$i) *************");
		databaseTryConnect($i);
		console("------------- (db.$i) -------------\n");
	}


	$database_ChallengeInfo = array();
	$database_GameInfos = array();
	$database_lastsent = $_currentTime;


	registerCommand('cp','/cp [-d] ideal|best|1|2|etc. : select the checkpoints list used for live time gaps (-d to show in chat the time between cps).');

}

//------------------------------------------
function databaseTryConnect($i){
	global $_debug,$_tm_db,$_webaccess,$_XmlrpcDB_bad_retry_timeout,$_Game,$_FAST_tool,$_FASTver,$_DedConfig,$_ServerInfos,$_ServerPackMask,$_is_relay;
	if(!isset($_tm_db[$i]))
		return;
	$tmdb =& $_tm_db[$i];
	$time = time();

	if(!isset($tmdb['XmlrpcDB']) && 
		 (!isset($tmdb['XmlrpcDBbadTime']) || ($time-$tmdb['XmlrpcDBbadTime']) > $_XmlrpcDB_bad_retry_timeout)){

		if(!isset($tmdb['Urltest'])){
			$urltest = 0;
			if(count($tmdb['Url']) > 1)
				$urltest = rand(0,count($tmdb['Url'])-1);
		}else{
			$urltest = ($tmdb['Urltest']+1) % count($tmdb['Url']);
		}
		$url = $tmdb['Url'][$urltest];

		console("*** Dataserver connection on ".stripColors($tmdb['Name'])." ...\n*****");

		console2("* Try connection on ".$url." ...");
		$xmlrpcdb = new XmlrpcDB($_webaccess,$url,$_Game,$_DedConfig['login'],$_DedConfig['password'],$_FAST_tool,$_FASTver,$_ServerInfos['Path'],$_ServerPackMask);
			
		$response = $xmlrpcdb->RequestWait('dedimania.ValidateAccount');
		//debugPrint("databaseInit - response",$response['Data']);
		//debugPrint("databaseInit - response",$response);
		
		if($response === false){
			console2("  !!!!!!\n  !!!!!! Error bad database response !\n  !!!!!!");
			
		}elseif(isset($response['Data']['params']['Status'])  && $response['Data']['params']['Status']){
			$tmdb['XmlrpcDB'] = $xmlrpcdb;
			$tmdb['News'] = $response['Data']['params']['Messages'];
			$tmdb['Events'] = array();
			console2("  Connection and status ok ! ({$response['Headers']['server'][0]})");
			
			if(isset($response['Data']['errors']) && databaseIsError($response['Data']['errors'])) 
				debugPrint("  !!!!!!\n  !!!!!! ... with some authenticate warning: ",$response['Data']['errors']);
			
		}elseif(isset($response['Data']['errors'])){
			
			console2("  !!!!!!\n  !!!!!! Connection Error !!!!!! ({$response['Headers']['server'][0]})\n".$response['Data']['errors']."\n  !!!!!!");
			//if($_debug>2) debugPrint("databaseInit - response",$response);
			
		}elseif(!isset($response['Code'])){
			console2("  !!!!!!\n  !!!!!! Error no database response (".$url.")\n  !!!!!!");
			//if($_debug>2) debugPrint("databaseInit - response",$response);
			
		}else{
			console2("  !!!!!!\n  !!!!!! Error bad database response or contents ({$response['Headers']['server'][0]}) (".$response['Code'].",".$response['Reason'].")\n  !!!!!!");
			if($_debug>0){
				if($response['Code'] == 200)
					debugPrint("databaseInit - response[Message]",$response['Message']);
				elseif($response['Code'] != 404) 
					debugPrint("databaseInit - response",$response);
			}
			
		}

		if(isset($tmdb['XmlrpcDB']))
			return;

		$tmdb['Urltest'] = $urltest;
		$tmdb['XmlrpcDBbadTime'] = $time;
	}

}


//------------------------------------------
// StartToServe : 
//------------------------------------------
function databaseStartToServe(){
	global $debug,$FASTver,$_tm_db;

	console("*********** databaseStartToServe()");
	for($i=0; $i < count($_tm_db); $i++){
		$tmdb =& $_tm_db[$i];
		// if database if ok
		if(isset($tmdb['XmlrpcDB']) && isset($_tm_db[$i]['News'])){
			foreach($_tm_db[$i]['News'] as $news)
				console2("NEWS(".$_tm_db[$i]['Name'].",".$news['Date']."): ".$news['Text']);
		}
	}
}


//------------------------------------------
// Everysecond : send requests
//------------------------------------------
function databaseEverysecond_Post($event,$seconds){
	global $_debug,$_tm_db,$database_lastsent,$_currentTime,$_XmlrpcDB_bad_retry_timeout,$_players_actives;
	if($_debug>6) console("database.Event[$event]($seconds)");

	// every 3 min after BeginRace or EndRace, update DB infos
	if($database_lastsent+180000 < $_currentTime){
		database_AnnonceToDB();
	}

	// really send the requests to databases after all other events
	for($i=0; $i < count($_tm_db); $i++){
		$tmdb =& $_tm_db[$i];
		// test database if ok
		if(isset($tmdb['XmlrpcDB'])){

			if($tmdb['XmlrpcDB']->isBad()){
				// will retry, after 30min of bad state without player
				if(($_players_actives <= 1) && ($tmdb['XmlrpcDB']->badTime() > $_XmlrpcDB_bad_retry_timeout)){
					console("DB _tm_db[$i]: retry to send...");
					$tmdb['XmlrpcDB']->retry();
				}
				
			}else{
				$response = $tmdb['XmlrpcDB']->sendRequests();
				if($response == false){
					console("DB _tm_db[$i] have consecutive connection errors !");
					
				}
			}
		}elseif($_players_actives <= 0){
			databaseTryConnect($i);
		}
	}
}		


//--------------------------------------------------------------
// EndResult
//--------------------------------------------------------------
function databaseEndResult_Post($event){
	global $_debug,$_tm_db;
	if($_debug>3) console("database.Event_Post[$event]()");

	// try reconnect if in error
	for($i=0; $i < count($_tm_db); $i++){
		$tmdb =& $_tm_db[$i];

		// test database if ok
		if(isset($tmdb['XmlrpcDB'])){
			if($tmdb['XmlrpcDB']->isBad()){
				// will retry, if bad state more than 5 min old
				if($tmdb['XmlrpcDB']->badTime() > 300){
					console("DB _tm_db[$i]: retry to send...");
					$tmdb['XmlrpcDB']->retry();
				}
			}
		}else{
			// make a delayed databaseTryConnect($i)
			addEventDelay(1000,'Function','databaseTryConnect',$i);
		}
	}
}


//------------------------------------------
// PlayerConnect : 
//------------------------------------------
function databasePlayerConnect($event,$login){
	global $_debug,$_tm_db,$_players,$_Game,$_is_relay;
  if(!is_string($login))
    $login = ''.$login;
	if($_debug>2) console("database.Event[$event]('$login')");

	if(isset($_players[$login]) && !$_players[$login]['Relayed']){

		//if($_debug>2) debugPrint("databasePlayerConnect - PlayerInfo",$_players[$login]['PlayerInfo']);
		if($_debug>5) debugPrint("databasePlayerConnect - _player[$login]",$_players[$login]);
		
		$pinfo = database_mkPlayerInfo($login);

		if(is_LAN_login($login))
			return;	// local and not connected player, not accepted for records

		for($i=0; $i < count($_tm_db); $i++){
			$tmdb =& $_tm_db[$i];
			// if database if ok
			if(isset($tmdb['XmlrpcDB']) && !$tmdb['XmlrpcDB']->isBad()){
				
				$callback = array('databasePlayerConnect_DB',$login,$i);
				$tmdb['XmlrpcDB']->addRequest($callback, 'dedimania.PlayerArrive',
																			$_Game,$login,$pinfo['NickName'], 
																			$pinfo['Nation'],$pinfo['TeamName'],$pinfo['Ranking'],
																			$pinfo['IsSpec'],$pinfo['IsOff']);
				// dedimania.PlayerArrive(Game, Login, Nickname, Nation, TeamName, LadderRanking, IsSpec, IsOff)

				if($_debug>2){ // show dedimania.PlayerArrive sent infos...
					$req = array('dedimania.PlayerArrive',$_Game,$login,$pinfo['NickName'], 
											 $pinfo['Nation'],$pinfo['TeamName'],$pinfo['Ranking'],
											 $pinfo['IsSpec'],$pinfo['IsOff']);
					debugPrint("databasePlayerConnect - $i - $login - addRequest - req",$req);
				}

				// update nickname in records
				if($_players[$login]['NickName'] != ''){

					if(isset($tmdb['Challenge']['Records'])){
						foreach($tmdb['Challenge']['Records'] as &$rec){
							if($rec['Login'] == $login){
								$rec['NickName'] = tm_substr($_players[$login]['NickName']);
								$rec['NickDraw'] = htmlspecialchars(tm_substr(stripColors($rec['NickName']),0,13),ENT_QUOTES,'UTF-8');
								break;
							}
						}
					}
				}


			}
		}
	}
}


function databasePlayerConnect_DB($response,$login,$tm_db_n){
	global $_debug,$_tm_db,$_players;
  if(!is_string($login))
    $login = ''.$login;

	if($_debug>=0 && isset($response['Data']['errors']) && databaseIsError($response['Data']['errors'])) 
		debugPrint("Webaccess warnings: ",$response['Data']['errors']);
	if($_debug>=2 && isset($response['Error']))
		debugPrint("Webaccess connection error: ",$response['Error']);

	if(is_LAN_login($login))
		return;

	if(!isset($_players[$login])){
		if($_debug>2) console("databasePlayerConnect_DB - _players[$login] does not exist !");

	}elseif(isset($response['Data']['params'])){
		if(isset($response['Data']['params']['TeamName']))
			$_players[$login]['TeamName'] = $response['Data']['params']['TeamName'];
		if(isset($response['Data']['params']['Nation']))
			$_players[$login]['Nation'] = $response['Data']['params']['Nation'];

		if(isset($response['Data']['params']['Aliases'])){
			foreach($response['Data']['params']['Aliases'] as $alias){
				if(isset($$alias['Text']))
					$_players[$login]['Aliases'][$alias['Alias']] = $$alias['Text'];
			}
		}

		insertEvent('PlayerArrive',$tm_db_n,$login);
		if($_debug>2) debugPrint("databasePlayerConnect_DB - response",$response['Data']);
	}
}


//------------------------------------------
// PlayerArrive : 
//------------------------------------------
function databasePlayerArrive($event,$tm_db_n,$login){
	global $_players;
	if(!isset($_players[$login]))
		dropEvent();
}


//------------------------------------------
// PlayerRecord : 
//------------------------------------------
function databasePlayerRecord($event,$tm_db_n,$login,$time,$rank,$old_time,$old_rank,$ChallengeInfo,$GameInfos){
	global $_players;
	if(!isset($_players[$login]))
		dropEvent();
}


//------------------------------------------
// PlayerDisconnect : 
//------------------------------------------
function databasePlayerDisconnect($event,$login){
	global $_debug,$_tm_db,$_players,$_Game,$_is_relay;
	if(!isset($_players[$login]['Login']))
		return;
  if(!is_string($login))
    $login = ''.$login;
	if($_debug>2) console("database.Event[$event]('$login')");

	if($_players[$login]['Relayed'])
		return;

	if(is_LAN_login($login))
		return;

	for($i=0; $i < count($_tm_db); $i++){
		$tmdb =& $_tm_db[$i];
		// if database if ok
		if(isset($tmdb['XmlrpcDB']) && !$tmdb['XmlrpcDB']->isBad()){
			
			$tmdb['XmlrpcDB']->addRequest(null, 
																		'dedimania.PlayerLeave', 
																		$_Game, 
																		$login);
			// dedimania.PlayerLeave(Game,Login)
		}
	}
}		


//------------------------------------------
// BeginRace : 
//------------------------------------------
function databaseBeginRace($event,$GameInfos){
	global $_debug,$_tm_db,$database_GameInfos,$database_lastsent,$_currentTime,$database_race_time;

	$database_lastsent = $_currentTime;
	$database_race_time = $_currentTime;
	$database_GameInfos = $GameInfos;
}


//------------------------------------------
// BeginChallenge : 
//------------------------------------------
function databaseBeginChallenge($event,$ChallengeInfo,$GameInfos){
	global $_debug,$_tm_db,$_Game,$_ServerOptions,$database_ChallengeInfo,$database_GameInfos,$database_lastsent,$_currentTime,$_BestChecks,$_BestPlayersChecks,$_BestChecksName,$_IdealChecks;

	if($_debug>1) debugPrint("database.Event[$event]('".stripColors($ChallengeInfo['Name'])."')",$ChallengeInfo);
	if($_debug>1) debugPrint("database.Event[$event] - _ChallengeInfo",$_ChallengeInfo);

	$database_ChallengeInfo = $ChallengeInfo;
	$database_GameInfos = $GameInfos;

	$players = database_mkPlayers();
	$_BestChecks = array();
	$_BestChecksName = 'Top$bb01';
	$_BestPlayersChecks = array();
	$_IdealChecks = array(-1=>array());

	if(isset($ChallengeInfo['UId'])){
		if($_debug>2) console("database.Event[$event]({$ChallengeInfo['UId']},'".stripColors($ChallengeInfo['Name'])."',{$ChallengeInfo['Environnement']},{$ChallengeInfo['Author']})");

		$serverInfos = database_mkServerInfos();

		for($i=0; $i < count($_tm_db); $i++){
			$tmdb =& $_tm_db[$i];
			// if database if ok
			if(isset($tmdb['XmlrpcDB'])){
				$tmdb['Events'] = array();
				$tmdb['Challenge'] = array();

				if(!$tmdb['XmlrpcDB']->isBad()){

					//debugPrint('databaseBeginChallenge - ServerOptions',$_ServerOptions);
					//debugPrint('databaseBeginChallenge - ChallengeInfo',$ChallengeInfo);
					//debugPrint('databaseBeginChallenge - GameInfos',$GameInfos);
					
					// race inits
					$callback = array('databaseBeginChallenge_DB',$i,$ChallengeInfo); 
					
					$tmdb['XmlrpcDB']->addRequest($callback, 
																				'dedimania.CurrentChallenge',
																				$ChallengeInfo['UId'],
																				tm_substr($ChallengeInfo['Name']),
																				$ChallengeInfo['Environnement'],
																				tm_substr($ChallengeInfo['Author']),
																				$_Game,
																				$GameInfos['GameMode'],
																				$serverInfos,
																				500, //$_tm_db[$i]['MaxRecords'],
																				$players);
					// CurrentChallenge(Uid, Name, Environment, Author, Game, Mode, SrvInfos, MaxGetTimes, players)
				}
			}
		}
	}else{
		if($_debug>2) console("database.Event[$event](no ChallengeInfo !)");
	}
}

function databaseBeginChallenge_DB($response,$tm_db_n,$ChallengeInfo){
	global $_debug,$_tm_db,$_Game,$_NumberOfChecks,$_ServerOptions,$_BestChecks,$_BestPlayersChecks,$_BestChecksName,$_players;
	if($_debug>6) debugPrint("databaseBeginChallenge_DB - response",$response['Data']);

	if($_debug>=0 && isset($response['Data']['errors']) && databaseIsError($response['Data']['errors'])) 
		debugPrint("Webaccess warnings: ",$response['Data']['errors']);
	if($_debug>=2 && isset($response['Error']))
		debugPrint("Webaccess connection error: ",$response['Error']);

	$tmdb =& $_tm_db[$tm_db_n];

	if(isset($response['Data']['params'])){
		$tmdb['Challenge'] = $response['Data']['params'];

		// if same challenge than in Results, remove Results one.
		if(isset($tmdb['Challenge']['Uid']) && isset($tmdb['Results']['Uid']) && $tmdb['Challenge']['Uid'] == $tmdb['Results']['Uid'])
			$tmdb['Results'] = array();
		
		// keep NumberOfChecks
		if($_NumberOfChecks <= 0 && isset($tmdb['Challenge']['NumberOfChecks']) && 
			 $tmdb['Challenge']['NumberOfChecks'] > 0){
			$_NumberOfChecks = $tmdb['Challenge']['NumberOfChecks'];
			console("DB NumberOfCheck = $_NumberOfChecks");
		}

		// get bestplayerschecks, make short name for records owners
		if(isset($tmdb['Challenge']['Records']) && is_array($tmdb['Challenge']['Records'])){
			foreach($tmdb['Challenge']['Records'] as $num => &$rec){
				
				if(count($rec['Checks']) > 0 && end($rec['Checks']) > 0){
					// Best Checks
					if((end($rec['Checks']) == $rec['Best']) &&
						 (count($_BestChecks) <= 0 || end($rec['Checks']) < end($_BestChecks))){
						$_BestChecks = $rec['Checks'];
						$_BestChecksName = 'Top'.($tm_db_n == 0 ? '$bb0':'$b80').($num+1);
					}
					
					// Build Ideal Checks
					databaseBuildIdealChecks($rec['Checks']);
					
					// Best Player Checks
					if(!isset($_BestPlayersChecks[$rec['Login']]) || end($rec['Checks']) < end($_BestPlayersChecks[$rec['Login']]))
						$_BestPlayersChecks[$rec['Login']] = $rec['Checks'];
				}

				// make short name for record owner
				$rec['NickDraw'] = htmlspecialchars(tm_substr(stripColors($rec['NickName']),0,13),ENT_QUOTES,'UTF-8');

				// adjust Game value if needed
				if($rec['Game'] != $_Game && ($rec['Game'] == 'TMU' || $rec['Game'] == 'TMF') && ($_Game == 'TMU' || $_Game == 'TMF'))
					$rec['Game'] = $_Game;
			}
		}

		if($_debug>2){
			$chal = $_tm_db[$tm_db_n]['Challenge'];
			$chal['Records'] = minmaxArray($chal['Records']);
			debugPrint("databaseBeginChallenge_DB - _tm_db[$tm_db_n]['Challenge']",$chal);
		}
		//if($_debug>6) debugPrint("databaseBeginChallenge_DB - TOP1 _BestChecks",$_BestChecks);

		insertEvent('StartRace',$tm_db_n,$tmdb['Challenge'],$ChallengeInfo);
		for($i=count($tmdb['Events'])-1; $i >= 0; $i--)
			insertEventArray($tmdb['Events'][$i]);
		$tmdb['Events'] = array();
	}
}


//--------------------------------------------------------------
// BeginRound :
//--------------------------------------------------------------
function databaseBeginRound($event){
	global $_debug,$_tm_db,$database_round_time,$_currentTime;

	$database_round_time = $_currentTime;
	//console("database.Event[$event]");

	for($i=0; $i < count($_tm_db); $i++){
		$tmdb =& $_tm_db[$i];
		$tmdb['Results'] = array();
	}
}


//--------------------------------------------------------------
// EndRace :
//--------------------------------------------------------------
function databaseEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_tm_db,$_Game,$_ServerOptions,$_players,$database_lastsent,$_currentTime,$_NumberOfChecks,$database_GameInfos,$database_race_time,$_is_relay;
	if($_debug>2) console("database.Event[$event]()");

	// if relay server then just announce players (spectators), don't send final times
	if($_is_relay){
		database_AnnonceToDB();
		return;
	}

	// if race take less than 6s (script time) then don't store records at all
	// it should probably wrong because of script time sync problem with dedicated
	// or it is an immediate restart/next
	if($_currentTime - $database_race_time < 6000){
		// not enough time for records, don't store them
		return;
	}

	$database_lastsent = $_currentTime;

	//debugPrint('databaseEndRace - ServerOptions',$_ServerOptions);
	//debugPrint('databaseEndRace - ChallengeInfo',$ChallengeInfo);
	//debugPrint('databaseEndRace - GameInfos',$GameInfos);
	//if($_debug>1) debugPrint('databaseEndRace - Ranking',$Ranking);
	//if($_debug>2) debugPrint('databaseEndRace - players',$_players);

	$times = array();
	$bestchecks = array();

	if($GameInfos['GameMode'] == LAPS){
		$BestTime = 'BestLapTime';
		$BestCheckpoints = 'BestLapCheckpoints';
	}else{
		$BestTime = 'BestTime';
		$BestCheckpoints = 'BestCheckpoints';
	}

	if($GameInfos['GameMode'] == CUP){
		// ignore Stunts records

	}elseif($GameInfos['GameMode'] == ROUNDS && (!isset($ChallengeInfo['LapRace']) || $ChallengeInfo['LapRace']) &&
		 isset($GameInfos['RoundsForcedLaps']) && $GameInfos['RoundsForcedLaps'] != 0){
		// special Rounds mode with forced lap on multilap map
		// just don't make the time tables in that case to not make records
		if($_debug>0) console('databaseEndRace - RoundsForcedLaps='.$GameInfos['RoundsForcedLaps'].' so ignore records !');

	}elseif($ChallengeInfo['AuthorTime'] < 6200 && $ChallengeInfo['Author'] != 'nadeo' && $ChallengeInfo['Author'] != 'Nadeo' && $ChallengeInfo['Author'] != 'slig'){
		// only special maps under 6.2s are accepted (dedimania will not store records under 6s for them anyway)
		if($_debug>0) console('databaseEndRace - Author time under 6.2s : records ignored !');

	}else{
		// get best times of players
		foreach($_players as $key => &$player){
			if(isset($player['Login']) && $player['Login'] == $key &&
				 isset($player['Active']) && isset($player[$BestTime]) &&
				 !is_LAN_login($player['Login']) &&
				 ($player['Active'] || $player[$BestTime] > 0)){
				
				$login = $player['Login'];

				// add time only if no inconsistency
				if(count($player[$BestCheckpoints]) > 0 && $player[$BestTime] == end($player[$BestCheckpoints]))
					$times[] = array('Login'=>$login ,'Best'=>$player[$BestTime]+0,'Checks'=>$player[$BestCheckpoints]);
			}
		}
		// create inconsistency !!!
		if(isset($times[0]['Checks'][1])){
			$last = count($times[0]['Checks'])-1;
			$best = end($times[0]['Checks']);
			//echo "!!!!!!!!!!!!!!!!!!!!! Create inconsistency (for fake cheat tests) !!!";
			//$times[0]['Checks'][$last] += 1;
			//$times[0]['Checks'][1] = -1;
			//$times[0]['Checks'][1] = 0;
			//$times[0]['Checks'][1] = $times[0]['Checks'][0]+30;
			//$times[0]['Checks'][$last-1] = $times[0]['Checks'][$last]-100;
			//$times[0]['Checks'][$last-1] = $times[0]['Checks'][$last]-20;
			//$times[0]['Checks'][$last] = $times[0]['Checks'][$last-1]; $times[0]['Checks'][$last-1] = $best;
			//$times[0]['Checks'] = array();
			//unset($times[0]['Checks']);
			//$times[0]['Checks'] = '';
		}

		usort($times,"databaseRecCompare");
		if($_debug>=0){
			debugPrint('databaseEndRace - times',minmaxArray($times));
		}
		// get BestCheckpoints of best player
		//if(isset($times[0]['Login'])){
		//$login = $times[0]['Login'];
		//if(isset($_players[$login][$BestCheckpoints][0]) && end($_players[$login][$BestCheckpoints]) == $times[0]['Best'])
		//$bestchecks = $_players[$login][$BestCheckpoints];
		//}
		//if($_debug>2) debugPrint('databaseEndRace - bestchecks',$bestchecks);
	}

	// send times etc.
	if(isset($ChallengeInfo['UId'])){
		if($_debug>2) console("database.Event[$event]({$ChallengeInfo['UId']},'".stripColors($ChallengeInfo['Name'])."',{$ChallengeInfo['Environnement']},{$ChallengeInfo['Author']})");

		for($i=0; $i < count($_tm_db); $i++){
			$tmdb =& $_tm_db[$i];
			// if database if ok
			if(isset($tmdb['XmlrpcDB']) && !$tmdb['XmlrpcDB']->isBad()){
				
				$callback = array('databaseEndRace_DB',$i,$ChallengeInfo); 
				$tmdb['XmlrpcDB']->addRequest($callback, 
																			'dedimania.ChallengeRaceTimes',
																			$ChallengeInfo['UId'],
																			tm_substr($ChallengeInfo['Name']),
																			$ChallengeInfo['Environnement'],
																			tm_substr($ChallengeInfo['Author']),
																			$_Game,
																			$GameInfos['GameMode'],
																			$_NumberOfChecks,
																			500, //$_tm_db[$i]['MaxRecords'],
																			$times);
				// ChallengeRaceTimes(Uid, Name, Environment, Author, Game, Mode, MaxGetTimes, Times)
				// Times is an array of struct {'Login': string, 'Best': int}
			}
		}
	}
}

function databaseEndRace_DB($response,$tm_db_n,$ChallengeInfo){
	global $_debug,$_tm_db,$_players,$_Game;
	if($_debug>6) debugPrint("databaseEndRace_DB - response ($tm_db_n)",$response['Data']);

	if($_debug>=0 && isset($response['Data']['errors']) && databaseIsError($response['Data']['errors'])) 
		debugPrint("Webaccess warnings: ",$response['Data']['errors']);
	if($_debug>=2 && isset($response['Error']))
		debugPrint("Webaccess connection error: ",$response['Error']);

	$tmdb =& $_tm_db[$tm_db_n];
	if(isset($response['Data']['params'])){
		$tmdb['Results'] = $response['Data']['params'];

		if(isset($tmdb['Results']['Records'])){
			// make short name for records owners
			foreach($tmdb['Results']['Records'] as &$rec){
				// make short name for record owner
				$rec['NickDraw'] = htmlspecialchars(tm_substr(stripColors($rec['NickName']),0,13),ENT_QUOTES,'UTF-8');

				// adjust Game value if needed
				if($rec['Game'] != $_Game && ($rec['Game'] == 'TMU' || $rec['Game'] == 'TMF') && ($_Game == 'TMU' || $_Game == 'TMF'))
					$rec['Game'] = $_Game;
			}
		}

		if($_debug>2){
			$chal = $_tm_db[$tm_db_n]['Results'];
			$chal['Records'] = minmaxArray($chal['Records']);
			debugPrint("databaseEndRace_DB - _tm_db[$tm_db_n]['Challenge']",$chal);
		}

		insertEvent('FinishRace',$tm_db_n,$tmdb['Results'],$ChallengeInfo);
	}
	//Reply a struct {'Uid': string, 'TotalRaces': int, 'TotalPlayers': int,  
	//        'TimeAttackRaces': int, 'TimeAttackPlayers': int, 'ServerMaxRecords': int, 
	//        'Records': struct {'Login': string, 'NickName': string, 'Best': int, 'Rank': int, 'NewBest': boolean} }
}


// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function databaseRecCompare($a, $b)
{
	global $_players;
	// no best valid, use rank
	if($a['Best'] <= 0 && $b['Best'] <= 0)
		return ($_players[$a['Login']]['Rank'] < $_players[$b['Login']]['Rank']) ?  -1 : 1;
	// one best valid
	elseif($b['Best'] <= 0)
		return -1;
	// other best valid
	elseif($a['Best'] <= 0)
		return 1;
	// best a better than best b
	elseif($a['Best'] < $b['Best'])
		return -1;
	// best b better than best a
	elseif($a['Best'] > $b['Best'])
		return 1;
	// same best, use rank
	else
		return ($_players[$a['Login']]['BestDate'] < $_players[$b['Login']]['BestDate']) ?  -1 : 1;
}



//--------------------------------------------------------------
// PlayerBest :
//--------------------------------------------------------------
function databasePlayerBest($event,$login,$time,$ChallengeInfo,$GameInfos,$checkpts){
	global $_debug,$_tm_db,$_Game,$_players,$_BestChecks,$_BestChecksName,$_BestPlayersChecks,$database_GameInfos,$_currentTime,$database_round_time;
  if(!is_string($login))
    $login = ''.$login;
	if($_debug>0) console("database.Event[$event]('$login',$time)");

	if($database_GameInfos['GameMode'] != $GameInfos['GameMode']){
		// not same game mode in database and best infos
		return;
	}

	if($GameInfos['GameMode'] == STUNTS){
		// no records in Stunts
		return;
	}

	if($time <= 0 || is_LAN_login($login)){
		// no time or LAN login : don't make record
		return;
	}

	for($tm_db_n = 0; $tm_db_n < count($_tm_db); $tm_db_n++){
		$tmdb =& $_tm_db[$tm_db_n];
		// if database if ok
		if(isset($tmdb['XmlrpcDB']) && !$tmdb['XmlrpcDB']->isBad()){

			// can't handle the best know, put in in the specific event table
			if(!isset($tmdb['Challenge']['Uid'])){
				$tmdb['Events'][] = func_get_args();

				// if it's the same challenge uid, insert the best in records if needed
			}elseif($tmdb['Challenge']['Uid'] == $ChallengeInfo['UId']){
				$records = &$_tm_db[$tm_db_n]['Challenge']['Records'];
				$max = $tmdb['Challenge']['ServerMaxRecords']*2;
				
				// search for 1st worse record, or himself
				for($j = 0; ($j < count($records)) && ($time >= $records[$j]['Best']) && ($records[$j]['Login'] != $login || $records[$j]['Game'] != $_Game); $j++)
					;

				if($_debug>4) debugPrint("databasePlayerBest - records($login,$time,$j)",$records);

				$old_rank = -1;
				$old_best = -1;
				if($j < count($records) && $records[$j]['Login'] == $login && $records[$j]['Game'] == $_Game){
					$old_rank = $j+1;
					$old_best = $records[$j]['Best'];
				}

				// if no rec and not found a better from himself and not more than max, add it
				if(($j >= count($records) && $j < $max) || ($j < count($records) && $time < $records[$j]['Best'])){

					// verify that the time is potentially valid and that the script time is not wrong
					$cpdelay = ($_currentTime - $database_round_time) - $time;
					if($cpdelay < 0){ 
						// something is bad with times : don't store record !
						if($_debug>0) console("Wrong record($login,$time,$j): player should have started before the round !\n(can be an anormal script timeout which made it)");
						$msg = localeText(null,'server_message')."Record not stored ($login,$time): wrong times or script time sync problem !";
						addCall(null,'ChatSendServerMessageToLogin', $msg, $login);
						return;
					}

					$k = -2;
					$l = -2;
					// do shift stuff unless if array is empty or $j is player
					if(count($records) > 0 && $j < count($records) && ($records[$j]['Login'] != $login || $records[$j]['Game'] != $_Game)){
						// find an old record of himself or go to end
						for($k=$j+1;$k < count($records) && ($records[$k]['Login'] != $login || $records[$k]['Game'] != $_Game) && $k < $max;$k++)
							;
						if($k < count($records) && ($records[$k]['Login'] == $login || $records[$k]['Game'] != $_Game)){
							$old_rank = $k+1;
							$old_best = $records[$k]['Best'];
						}
						// shift values from $k to $j
						for($l=$k; $l > $j; $l--){
							$records[$l] = $records[$l-1];
							$records[$l]['Rank']++;
						}
					}
					$records[$j] = array('Game'=>$_Game,'Login'=>$login,'NickName'=>tm_substr($_players[$login]['NickName']),'Best'=>$time,'Rank'=>$j+1,'NewBest'=>true,'NickDraw'=>$_players[$login]['NickDraw'],'Checks'=>$checkpts);

					if($j < $max){
						insertEvent('PlayerRecord',$tm_db_n,$login,$time,$j+1,$old_best,$old_rank,$ChallengeInfo,$GameInfos,$checkpts);
						if($_debug>3) debugPrint("databasePlayerBest - records - 2 ($login,$time,$j,$k,$l)",$records);

						// update _BestChecks array
						if(end($checkpts) == $time &&
							 (count($_BestChecks) <= 0 || end($_BestChecks) > $time)){
							$_BestChecks = $checkpts;
							$_BestChecksName = 'Top'.($tm_db_n == 0 ? '$bb0':'$b80').($j+1);
							if($_debug>3) debugPrint("BestChecks taken by $login for record :",$time);
						}

					}
				}
			}
		}
	}

	if(count($checkpts) > 0 && 
		 end($checkpts) > 0 &&
		 (!isset($_BestPlayersChecks[$login]) ||	
			end($checkpts) < end($_BestPlayersChecks[$login]))){
		$_BestPlayersChecks[$login] = $checkpts;
		databaseBuildIdealChecks($_BestPlayersChecks[$login]);
	}
	
	//if($_debug>3) debugPrint("databasePlayerBest - records - 3 ($login,$time)",$records);
}


//--------------------------------------------------------------
// Build ideal checks array : $_IdealChecks[$cp][$cp] is supposed best time at checkpoint $cp
//--------------------------------------------------------------
function databaseBuildIdealChecks(&$checks){
	global $_debug,$_IdealChecks;
	foreach($checks as $num => $time){
		if($num > 0){
			if(!isset($_IdealChecks[$num-1][$num]) || ($time < $_IdealChecks[$num-1][$num]) ||
				 (($time == $_IdealChecks[$num-1][$num]) && $checks[$num-1] < $_IdealChecks[$num-1][$num-1])){
				$_IdealChecks[$num-1] = $checks;
				$_IdealChecks[-1][$num-1] = $checks[$num-1];
			}
		}
	}
	if(!isset($_IdealChecks[$num][$num]) || ($time < $_IdealChecks[$num][$num])){
		$_IdealChecks[$num] = $checks;
		$_IdealChecks[-1][$num] = $checks[$num];
	}
	if($_debug>3) console('NewChecks: '.implode(',',$checks));
	if($_debug>3) console('IdealChecks: '.implode(',',$_IdealChecks[-1]));
	if($_debug>4) debugPrint('databaseBuildIdealChecks - _IdealChecks (full) ',$_IdealChecks);
}



//--------------------------------------------------------------
function database_AnnonceToDB(){
	global $_debug,$_tm_db,$_Game,$_ServerOptions,$database_ChallengeInfo,$database_GameInfos,$database_lastsent,$_currentTime,$_is_relay;

	$database_lastsent = $_currentTime;

	if(isset($database_ChallengeInfo['UId'])){
		if($_debug>3) console("** Update server DB infos.");

		$serverInfos = database_mkServerInfos();
		$players = database_mkPlayers();

		for($i=0; $i < count($_tm_db); $i++){
			$tmdb =& $_tm_db[$i];
			// if database if ok
			if(isset($tmdb['XmlrpcDB']) && !$tmdb['XmlrpcDB']->isBad()){

				//debugPrint('database_AnnonceToDB - ServerOptions',$_ServerOptions);
				//debugPrint('database_AnnonceToDB - database_ChallengeInfo',$database_ChallengeInfo);
				//debugPrint('database_AnnonceToDB - database_GameInfos',$database_GameInfos);

				$callback = array('database_AnnonceToDB_DB',$i);
				$tmdb['XmlrpcDB']->addRequest($callback,
																			'dedimania.UpdateServerPlayers',
																			$_Game,
																			$database_GameInfos['GameMode'],
																			$serverInfos,
																			$players);
				// UpdateServerPlayers(Game, Mode, SrvName, players)
				if($_debug>4 || ($_debug>2 && count($players) > 0)) debugPrint("database_AnnonceToDB ($i) - players",$players);
			}
		}
	}
}

function database_AnnonceToDB_DB($response,$tm_db_n){
	global $_debug,$_tm_db;

	if($_debug>=0 && isset($response['Data']['errors']) && databaseIsError($response['Data']['errors'])) 
		debugPrint("Webaccess warnings: ",$response['Data']['errors']);
	if($_debug>=2 && isset($response['Error']))
		debugPrint("Webaccess connection error: ",$response['Error']);
}

//--------------------------------------------------------------
function database_mkPlayers(){
	global $_debug,$_players;

	$players = array();
	foreach($_players as $login => &$pl){
		if(!$pl['Relayed']){
			$pinfo = database_mkPlayerInfo($login);
			if($pinfo !== false){
				$players[] = $pinfo;
			}
		}
	}
	return $players;
}


//--------------------------------------------------------------
function database_mkPlayerInfo($login){
	global $_Game,$_players;
  if(!is_string($login))
    $login = ''.$login;

	if(isset($_players[$login]['Login']) &&  $_players[$login]['Login'] == $login &&
		 isset($_players[$login]['Active']) && $_players[$login]['Active'] &&
		 !$_players[$login]['Relayed'] && !is_LAN_login($login)){
		$player = &$_players[$login];

		$nickname = tm_substr($player['NickName'],0,100);
		if(isset($player['Nation']) && $player['Nation'] != '')
			$nation = tm_substr($player['Nation'],0,100);
		elseif(isset($player['Country']) && $player['Country'] != '')
			$nation = tm_substr($player['Country'],0,100);
		else
			$nation = '';
		if(isset($player['TeamName']) && $player['TeamName'] != '')
			$teamname = tm_substr($player['TeamName'],0,100);
		else
			$teamname = '';
		if(isset($player['LadderRanking']))
			$ranking = $player['LadderRanking'];
		else
			$ranking = 0;
		if(isset($player['IsSpectator']))
			$isspec = $player['IsSpectator'];
		else
			$isspec = false;
		if(isset($player['IsInOfficialMode']))
			$isoff = $player['IsInOfficialMode'];
		else
			$isoff = false;
		if(isset($player['TeamId']))
			$teamid = $player['TeamId'];
		else
			$teamid = -1;

		return array('Game'=>$_Game,'Login'=>$login,'NickName'=>$nickname,'Nation'=>$nation,'TeamName'=>$teamname,
								 'TeamId'=>$teamid,'IsSpec'=>$isspec,'Ranking'=>$ranking,'IsOff'=>$isoff);
		// array('Game','Login','Nation','TeamName','TeamId','IsSpec','Ranking','IsOff')
	}
	return false;
}


//------------------------------------------
// build server infos :
//------------------------------------------
function database_mkServerInfos(){
	global $_debug,$_ServerOptions,$_ChallengeList,$_CurrentChallengeIndex,$_PlayerList,$_DedConfig;
	
	// compute number of players and specs
	$numplayers = 0;
	$numspecs = 0;
	foreach($_PlayerList as &$pl){
		if(($pl['SpectatorStatus']%10) > 0)
			$numspecs++;
		else
			$numplayers++;
	}

	// compute list of next 5 challenges uid
	$uidlist = '';
	$sep = '';
	if(is_array($_ChallengeList) && count($_ChallengeList) > 0){
		$maxi = 5;
		if($maxi > count($_ChallengeList))
			$maxi = count($_ChallengeList);
		for($index = $_CurrentChallengeIndex+1; $index <= $_CurrentChallengeIndex+$maxi; $index++){
			$uidlist .= $sep.$_ChallengeList[($index%count($_ChallengeList))]['UId'];
			$sep = '/';
		}
	}
	
  $serverInfos = array('SrvName'=>tm_substr($_ServerOptions['Name']),
											 'Comment'=>tm_substr($_ServerOptions['Comment']),
											 'Private'=>($_ServerOptions['Password'] != ''),
											 'SrvIP'=>$_DedConfig['public_server_ip'],
											 'SrvPort'=>$_DedConfig['server_port'], 
											 'XmlrpcPort'=>$_DedConfig['xmlrpc_port'],
											 'NumPlayers'=>$numplayers,
											 'MaxPlayers'=>$_ServerOptions['CurrentMaxPlayers'],
											 'NumSpecs'=>$numspecs,
											 'MaxSpecs'=>$_ServerOptions['CurrentMaxSpectators'],
											 'LadderMode'=>$_ServerOptions['CurrentLadderMode'],
											 'NextFiveUID'=>$uidlist
											 );
	//if($_debug>2) debugPrint('database_mkServerInfos - serverInfos',$serverInfos);
	return $serverInfos;
}




function databaseIsError(&$error){
	if(is_string($error) && strlen($error) > 0)
		return true;
	if(is_array($error) && count($error) > 0)
		return true;
	return false;
}



// -----------------------------------------------------------
// -------------------- CHAT COMMAND -------------------------
// -----------------------------------------------------------


// checks
function chat_cp($author, $login, $params){
	global $_debug,$_Game,$_players,$_tm_db,$_BestChecks,$_BestChecksName,$_IdealChecks,$_BestPlayersChecks;
	
	$msg = localeText(null,'server_message').localeText(null,'interact')."/cp [-d] ideal|best|1|2|etc. : select the checkpoints list used for live time gaps (-d to show in chat the time between cps).";
	$showdiff = false;
	if(isset($params[0]) && ($params[0] == '-d' || $params[0] == '-diff')){
		array_shift($params);
		$showdiff = true;
	}
	if(isset($params[0]) && isset($_players[$login]['ChecksGaps'])){
		if($params[0] == 'ideal'){
			$_players[$login]['ChecksGaps'] = 'ideal';
			$msg = localeText(null,'server_message').localeText(null,'interact').'Set to ideal checkpoints times.';
			$msg .= "\nNote: it is done taking for each checkpoint the time of the top which had the best time on next checkpoint, and so is potentially a time for a best way to next checkpoint. Strangely you will probably have some time worse than on a specific top.";

		}elseif($params[0] == 'best' || $params[0] == '0'){
			$_players[$login]['ChecksGaps'] = 'best';
			$msg = localeText(null,'server_message').localeText(null,'interact').'Set to best top checkpoints times.';

		}elseif(is_numeric($params[0])){
			if(isset($_tm_db[0]['Challenge']['Records'][$params[0]-1]['Checks']) &&
				 count($_tm_db[0]['Challenge']['Records'][$params[0]-1]['Checks']) > 0){
				$_players[$login]['ChecksGaps'] = $_tm_db[0]['Challenge']['Records'][$params[0]-1]['Checks'];
				$_players[$login]['ChecksGaps']['Name'] = 'Top$bb0'.$params[0];
				$msg = localeText(null,'server_message').localeText(null,'interact').'Set to main Top'.$params[0].'.';

			}elseif(isset($_tm_db[1]['Challenge']['Records'][$params[0]-1]['Checks']) &&
							count($_tm_db[1]['Challenge']['Records'][$params[0]-1]['Checks']) > 0){
				$_players[$login]['ChecksGaps'] = $_tm_db[1]['Challenge']['Records'][$params[0]-1]['Checks'];
				$_players[$login]['ChecksGaps']['Name'] = 'Top$b80'.$params[0];
				$msg = localeText(null,'server_message').localeText(null,'interact').'Set to secondary Top'.$params[0].'.';

			}elseif(isset($_tm_db[2]['Challenge']['Records'][$params[0]-1]['Checks']) &&
							count($_tm_db[2]['Challenge']['Records'][$params[0]-1]['Checks']) > 0){
				$_players[$login]['ChecksGaps'] = $_tm_db[2]['Challenge']['Records'][$params[0]-1]['Checks'];
				$_players[$login]['ChecksGaps']['Name'] = 'Top$b40'.$params[0];
				$msg = localeText(null,'server_message').localeText(null,'interact').'Set to secondary Top'.$params[0].'.';

			}else{
				$msg .= "\nInvalid top value.";
			}
		}

	}
	if(is_array($_players[$login]['ChecksGaps'])){
		$cps = $_players[$login]['ChecksGaps'];
		$cpsname = '.';
	}elseif($_players[$login]['ChecksGaps'] == 'ideal'){
		$cps = $_IdealChecks[-1];
		$cpsname = ',Ideal.';
	}else{
		$cps = $_BestChecks;
		$cpsname = ','.$_BestChecksName.'.';
	}
	if($showdiff){
		$msg .= "\nValue(cp gaps): ";
		$sep = '$n';
		$prev0 = 0;
		$prev1 = 0;
		foreach($cps as $i => $val){
			$diff0 = $val - $prev0;
			$prev0 = $val;
			if(!is_numeric($i)){
				$msg .= $sep.$val;
			}elseif(!isset($_BestPlayersChecks[$login][$i])){
				$msg .= $sep.'$666'.($i+1).':$dcf'.MwTimeToString2($diff0);
			}elseif(is_numeric($i)){
				$diff1 = $_BestPlayersChecks[$login][$i] - $prev1;
				$prev1 = $_BestPlayersChecks[$login][$i];
				
				$diff = $diff1 - $diff0;
				if($diff > 0)
					$msg .= $sep.'$666'.($i+1).':$dcf'.MwTimeToString2($diff0).'($e11'.MwDiffTimeToString($diff).'$dcf)';
				else
					$msg .= $sep.'$666'.($i+1).':$dcf'.MwTimeToString2($diff0).'($11e'.MwDiffTimeToString($diff).'$dcf)';
			}
			$sep = ',';
		}
	}else{
		$msg .= "\nValue: ";
		$sep = '$n';
		foreach($cps as $i => $val){
			
			if(!is_numeric($i)){
				$msg .= $sep.$val;
			}elseif(!isset($_BestPlayersChecks[$login][$i])){
				$msg .= $sep.'$666'.($i+1).':$dcf'.MwTimeToString2($val);
			}else{
				$diff = $_BestPlayersChecks[$login][$i] - $val;
				if($diff > 0)
					$msg .= $sep.'$666'.($i+1).':$dcf'.MwTimeToString2($val).'($e11'.MwDiffTimeToString($diff).'$dcf)';
				else
					$msg .= $sep.'$666'.($i+1).':$dcf'.MwTimeToString2($val).'($11e'.MwDiffTimeToString($diff).'$dcf)';
			}
			$sep = ',';
		}
	}
	$msg .= $cpsname;
	addCall(null,'ChatSendToLogin', $msg, $login);
}



?>
