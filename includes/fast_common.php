<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      25.08.2011
// Author:    Gilles Masson
// Contributor: Florian Schnell (for some parts coming from Fast2)
//
// These are private functions used by fast, and probably not used by standard plugins
//
////////////////////////////////////////////////////////////////

//Status[Code]=1, Status[Name]=Waiting
//Status[Code]=2, Status[Name]=Launching
//Status[Code]=3, Status[Name]=Running - Synchronization
//Status[Code]=4, Status[Name]=Running - Play
//Status[Code]=5, Status[Name]=Running - Finish


//------------------------------------------
// max number of maps in challenge list, can be set to another value in fast.php
//------------------------------------------
global $_MaxMaps;
if(!isset($_MaxMaps)) $_MaxMaps = 2000;


//------------------------------------------
// get infos and make events when fast start
//------------------------------------------
// CALLED BY: fast.php, main program
//------------------------------------------
function manageInit(){
	global $_debug,$_client,$_ServerId,$_events,$_use_cb,$_StatusCode,$_init,$_starting,$_CurrentChallengeIndex,$_ChallengeInfo,$_starting_calls,$_currentTime,$_lastEverysecond,$_lastEvery5seconds,$_lastEveryminute,$_Game,$_GameInfos,$_BeginRaceSeen,$_ServerOptions,$_OrigVehicleNetQuality,$_OrigIsP2PUpload,$_OrigIsP2PDownload,$_is_dedicated,$_PlayerList,$_is_ingame,$_methods_list,$_buddy_notify_default,$_CallTimes,$_CallTime,$_CallTimeTmp,$_CallNumbers,$_CallNumTmp,$_CallAsyncTimes,$_CallAsyncTime,$_CallAsyncTimeTmp,$_CallAsyncNumbers,$_CallAsyncNumTmp,$_EndMatchCondition,$_WarmUp,$_FWarmUp,$_NextFWarmUp,$_MaxMaps,$_starting,$_RankingTime,$_NeedGetRankings,$_NetLostGraceTime,$_ChatFloodRate,$_PrevChallengeInfo,$_PrevGameInfos,$_PrevFGameMode,$_FGameMode;

	$_init = true;
	// used for some stats on dedicated query/reply delays
	$_CallTimes = array();
	$_CallTime = 0;
	$_CallTimeTmp = 0;
	$_CallNumbers = 0;
	$_CallNumTmp = 0;
	$_CallAsyncTimes = array();
	$_CallAsyncTime = 0;
	$_CallAsyncTimeTmp = 0;
	$_CallAsyncNumbers = 0;
	$_CallAsyncNumTmp = 0;
	$_ChatFloodRate = 0;

	if(!isset($_NetLostGraceTime))
		$_NetLostGraceTime = 10000; // more time before the round begins if there are netlost players (ms)

	if(isset($_methods_list['SetBuddyNotification'])){
		if(!isset($_buddy_notify_default))
			$_buddy_notify_default = false;
		addCall(true,'SetBuddyNotification','',$_buddy_notify_default);
	}else{
		$_buddy_notify_default = null;
	}

	$_lastEverysecond = floor($_currentTime/1000);
	$_lastEvery5seconds = floor($_currentTime/5000)*5;
	$_lastEveryminute = floor($_currentTime/60000);

	$_RankingTime = 0;
	$_NeedGetRankings = false;

	console("\n**************** Init start ******************");
	// temporary unset callback feature to make startup events
	$_starting = true;
	$use_cb = $_use_cb;
	$_use_cb = false;

	$_events = array();

	// get all usefull server infos
	console('Get initial server infos...');
	addCall(true,'GetVersion');
	callMulti();
	console('Get initial server infos2...');
	addCall(true,'GetStatus');
	callMulti();
	console('Get initial server infos3...');
	addCall(array('CB'=>array('setWarmUpState',array(0,true,'init'),0)),'GetWarmUp');
	addCall(true,'GetServerCoppers');
	addCall(true,'GetServerOptions',1);
	addCall(true,'GetRoundCustomPoints');
	addCall(true,'GetForcedMods');
	addCall(true,'GetForcedMusic');
	addCall(true,'GetServerPackMask');
	addCall(true,'GetGameInfos',1);
	callMulti();
	console('Get initial server infos3...');
	addCall(true,'GetCurrentChallengeIndex');
	addCall(true,'GetCurrentChallengeInfo');
	addCall(true,'GetNextChallengeIndex');
	addCall(true,'GetNextChallengeInfo');
	addCall(true,'GetCallVoteRatios');
	if(isset($_methods_list['CheckEndMatchCondition']))
		addCall(null,'CheckEndMatchCondition');	
	//addCall(true,'GetChatLines');
	callMulti();

	console('Get initial challenges list...');
	addCall(true,'GetChallengeList',$_MaxMaps,0);
	callMulti();

	console('Get Ban list...');
	addCall(true,'GetBanList',260,0);
	callMulti();
	console('Get Black list...');
	addCall(true,'GetBlackList',260,0);
	callMulti();
	console('Get Guest list...');
	addCall(true,'GetGuestList',260,0);
	callMulti();
	console('Get Ignore list...');
	addCall(true,'GetIgnoreList',260,0);
	callMulti();

	$_PrevChallengeInfo = $_ChallengeInfo;
	$_PrevGameInfos = $_GameInfos;
	$_PrevFGameMode = $_FGameMode;

	// handle startup addcall configs
	if(isset($_starting_calls) && is_array($_starting_calls) && count($_starting_calls)>0){
		console('Autostart calls configured by server manager...');
		foreach($_starting_calls as $call){
			if(is_array($call))
				addCallArray(null,$call);
		}
		callMulti();
	}

	// get original values for normal (not degraded) mode
	$_OrigVehicleNetQuality = $_ServerOptions['CurrentVehicleNetQuality'];
	$_OrigIsP2PUpload = $_ServerOptions['IsP2PUpload'];
	$_OrigIsP2PDownload = $_ServerOptions['IsP2PDownload'];

	// some calls response can make new calls, so need to call 2 times to be sure...
	callMulti();

	// make starting events
	console("**************** Init events ({$_StatusCode}) ******************");
	insertEvent('ServerStart');
	manageEvents();
	callMulti();

	console('Get initial players list...');
	addCall(true,'GetCurrentRanking',260,0);
	addCall(true,'GetPlayerList',260,0,1);
	manageRanking(true);

	// manage events
	manageEvents();
	callMulti();

	if($_StatusCode > 2 && $_StatusCode < 5){
		// in case the server is already in state 3 or 4
		console("*** Begin Race ***");
		addEvent('BeginRace',$_GameInfos,$_ChallengeInfo,$_EndMatchCondition=='Finished',$_WarmUp,$_NextFWarmUp); // addEvent('BeginRace',GameInfos,ChallengeInfo,NewCup,WarmUp,FWarmUp)
		addEvent('BeginChallenge',$_ChallengeInfo,$_GameInfos); // addEvent('BeginChallenge',ChallengeInfo,GameInfos)
		$_BeginRaceSeen = true;
		if($_StatusCode == 4){
			console("*** Begin Round ***");
			addEvent('BeginRound');
		}
	}

	// set back cb value
	$_use_cb = $use_cb;

	// test if ingame
	if(isset($_PlayerList[0]['PlayerId']) && $_PlayerList[0]['PlayerId']==$_ServerId){
		if($_debug>0) console("*** Ingame server ***");
		$_is_dedicated = false;
		$_is_ingame = true;
	}

	$_starting = false;
	console("**************** Init end ******************\n");

	addCall(true,'GetPlayerList',260,0,1);
	addCall(true,'GetCurrentRanking',260,0);
	callMulti();

	// end of startup stuffs
	$_init = false;
}


//Status[Code]=1, Status[Name]=Waiting
//Status[Code]=2, Status[Name]=Launching
//Status[Code]=3, Status[Name]=Running - Synchronization
//Status[Code]=4, Status[Name]=Running - Play
//Status[Code]=5, Status[Name]=Running - Finish


//------------------------------------------
// get callback responses then manage them
//------------------------------------------
// CALLED BY: main loop
//------------------------------------------
function manageCallbacks($readcb=true) {
	global $_debug,$_loadsimul,$_client,$_cbTime,$_playerlistTime,$_playernetstatTime,$_currentTime,$_StatusCode,$_netstatRunDelay,$_netstatSyncDelay,$_playerRunDelay,$_PlayerList;

	if($readcb && @$_client->readCB(0) === false){
		// if error test if connection error
		if($_client->isError()){
			Error("manageCBs:: ".$_client->getErrorMessage(), $_client->getErrorCode());
			if(($_client->getErrorCode() == -32300) || ($_client->getErrorCode() == -32700))
				reconnectTM();
		}
	}
	callbackEvents();

	// if no callback for 2min and play status, then get something to keepalive connection
	$dt = ($_loadsimul > 0) ? 2000 : 120000;
	if(($_cbTime+$dt < $_currentTime) && ($_StatusCode == 4)){
		$_cbTime = $_currentTime;
		if($_debug>5) console('*** Do keepalive ! (GetServerName call)');
		addCallAsync(true,'GetServerName');
		if($_loadsimul>0) addCall(true,'GetServerName');
	}

	// if play status, get players list every ~30s
	if($_StatusCode == 4 && count($_PlayerList) > 0 && 
		 ($_playerlistTime+$_playerRunDelay < $_currentTime)){
		addCall(true,'GetPlayerList',260,0,1);
		$_playerlistTime = $_currentTime; // done in multicallAutoStoreInfos
	}
	
	// get net stats every ~2s when playing and every 1s while synchro
	if(($_StatusCode==4 && $_playernetstatTime+$_netstatRunDelay < $_currentTime) || 
		 ($_StatusCode==3 && $_playernetstatTime+$_netstatSyncDelay < $_currentTime)){
		addCall(true,'GetNetworkStats');
		$_playernetstatTime = $_currentTime; // done in multicallAutoStoreInfos
	}

	callMulti();
}


//------------------------------------------
// manage events
//------------------------------------------
// CALLED BY: main loop
//------------------------------------------
function manageEvents($tick=false) {
	global $_debug,$_events,$_events_end,$_delay_actions,$_Ranking,$_ChallengeInfo,$_GameInfos,$_func_list,$_currentTime,$_PlayerList,$_lastEverysecond,$_lastEvery5seconds,$_lastEveryminute,$_CallTime,$_CallTimeTmp,$_CallNumbers,$_CallNumTmp,$_methods_list,$_use_flowcontrol,$_EndMatchCondition,$_NeedGetRankings,$_ChatFloodRate;

	// manage delayed actions and add them in current if needed
	manageDelayedActions();

	// array to store waiting events
	$_nextevents = array();

	// if _events is empty then put _events_end
	if(count($_events) <= 0 && count($_events_end) > 0){
		$_events = $_events_end;
		$_events_end = array();
	}
	
	// call events plugins functions
	while(count($_events) > 0){
		//debugPrint('manageEvents - _events',$_events);
		$curevent = array_shift($_events);

		if(isset($curevent[0]) && is_string($curevent[0])){
			//console("**** {$curevent[0]},".count($curevent));

			// call events functions
			callFuncsArray($curevent);
			callMulti();
		}
		
		// add _events_end at end...
		if(count($_events) <= 0 && count($_events_end) > 0){
			$_events = $_events_end;
			$_events_end = array();
		}
	}

	// waiting events come in event for next time
	$_events = $_nextevents;

	if($tick){
		// Everysecond event ?
		$everysecond = floor($_currentTime/1000);
		if($everysecond != $_lastEverysecond){

			// Every5seconds event ?
			$every5seconds = floor($_currentTime/5000)*5;
			if($every5seconds != $_lastEvery5seconds){

				// get FlowControlTransition to be sure to avoid lockup !
				if($_use_flowcontrol && isset($_methods_list['ManualFlowControlGetCurTransition'])){
					addCall(null,'ManualFlowControlGetCurTransition');
				}

				// Everyminute event ?
				$everyminute = floor($_currentTime/60000);
				if($everyminute != $_lastEveryminute){

					// every minute... except if more than 10 minutes late
					if($everyminute - $_lastEveryminute < 10)
						$_lastEveryminute = $everyminute - 1;

					// call Everyminute event for every minute...
					while($_lastEveryminute < $everyminute){
						// Everyminute($event,$minutes,$is2min,$is5min): 
						callFuncs('Everyminute',$_lastEveryminute,(($_lastEveryminute%2)==0),(($_lastEveryminute%5)==0));

						if(!$_use_flowcontrol && ($_lastEveryminute%30)==0)
							enableFlowControl();
							
						$_lastEveryminute++;
					}
					$_lastEveryminute = $everyminute;
				}

				// used for some stats on dedicated query/reply delays
				$_CallNumbers = $_CallNumTmp;
				$_CallNumTmp = 0;
				$_CallTime = $_CallTimeTmp;
				$_CallTimeTmp = 0;

				// call Every5seconds event...
				$_lastEvery5seconds = $every5seconds;
				callFuncs('Every5seconds',$_lastEvery5seconds);
			}

			// call Everysecond event...
			$_lastEverysecond = $everysecond;
			callFuncs('Everysecond',$_lastEverysecond);

			$_ChatFloodRate /= 2; // reduce chat flood rate every second

			if($_NeedGetRankings){
				// use to get rankings every second if at least one player finished
				addCall(true,'GetCurrentRanking',260,0);
			}
		}

		// call Everytime event...
		callFuncs('Everytime');
	}
	
	// send/handle methods set during events
	callMulti();

	// handle flow control
	handleManualFlowControlProceed();
}


//------------------------------------------
// Note: to delay a flow transition, see  delayTransition($delay)  description (fast_general.php) !
function handleManualFlowControlProceed(){
	global $_debug,$_client,$_MFCTransition,$_MFCTransitionGet,$_use_flowcontrol,$_currentTime,$_PlayerList,$_NetworkStats,$_NetLostGraceTime,$_ChatFloodRate,$_endrace_time,$_WarmUp,$_FWarmUp;
	if($_use_flowcontrol && $_MFCTransition['Transition']!=''){
		// there is a transition to handle !
		// $_MFCTransition = array('Time'=>$_currentTime,'Transition'=>$transition,'Event'=>$event,'DelayTime'=>$_currentTime,'Proceed'=>false);

		$nbplayers = count($_PlayerList);

		// a minimum delay added to give time to send manialinks etc., increase delay with chat flooding
		$pdelay = floor((15 + $_ChatFloodRate) * $nbplayers); 

		// a 10s grace time before round start if there are netlost players
		if($_MFCTransition['Event'] == 'BeforePlay' && isset($_NetworkStats['LostContacts']) && $_NetworkStats['LostContacts'] != '')
			$pdelay += $_NetLostGraceTime;

		if($_MFCTransition['Event'] == 'EndPodium' && !$_WarmUp && $_FWarmUp <= 0){
			// a minimum 12s time between EndRace and final EndPodium (if not warmup), to be sure to get podium scoreboard visible few seconds (20s if more than 3 players)
			$mindelay = $nbplayers > 3 ? 20000 : 12000;
			if($_currentTime - $_endrace_time < $mindelay)
				$pdelay += ($mindelay - $_currentTime + $_endrace_time);
		}

		if(!$_MFCTransition['Proceed'] || $_MFCTransition['DelayTime'] + $pdelay <= $_currentTime){

			if($_MFCTransition['Event'] != ''){
				// call event to handle transition and eventual delay...
				// send delay=0 for a new event transition, and real delay for delayed event
				$delay = $_MFCTransition['Proceed'] ? $_currentTime - $_MFCTransition['Time'] : 0;

				// directly call event functions...
				if($_debug>5) console("handleManualFlowControlEvent:: call {$_MFCTransition['Transition']}/{$_MFCTransition['Event']} event ({$delay},{$pdelay}) !");
				callFuncsArray(array($_MFCTransition['Event'],$delay,$_MFCTransition['Time']));
				callMulti();
			}
			$_MFCTransition['Proceed'] = true;
		}

		$delay = $_currentTime - $_MFCTransition['Time'];
		if($_MFCTransition['DelayTime'] + $pdelay > $_currentTime){
			// delayed transition !
			if($_debug>6) console("handleManualFlowControlProceed:: {$_MFCTransition['Transition']}/{$_MFCTransition['Event']} delayed ({$delay},{$pdelay}) !...");
			$_MFCTransitionGet = '';

		}else{
			// have to proceed the manualflowcontrol

			// after EndPodium event and before proceed is last before race change : handle FGameMode changes
			if($_MFCTransition['Event'] == 'EndPodium'){
				if($_debug>0) console("handleManualFlowControlEvent:: call {$_MFCTransition['Transition']}/{$_MFCTransition['Event']} event (final:{$delay},{$pdelay}) !");
				changeFGameMode(false);
			}else{
				if($_debug>4) console("handleManualFlowControlEvent:: call {$_MFCTransition['Transition']}/{$_MFCTransition['Event']} event (final:{$delay},{$pdelay}) !");
			}

			// send final transition event with delay -1
			callFuncsArray(array($_MFCTransition['Event'],-1,$_MFCTransition['Time']));

			// handle pending transition calls results
			callMulti();

			// do a sync query to be "sure" to have pending things finished before proceeding flow control
			$_client->query('GetServerName'); // sync query (using any quick answered method)

			if(!$_client->query('ManualFlowControlProceed')){
				// it's a proceed error, don't send event again for it, will just try again next time
				if($_debug>0) console("handleManualFlowControlProceed:: TransitionProceed({$_MFCTransition['Transition']}/{$_MFCTransition['Event']}) failed: [".$_client->getErrorCode().'] '.$_client->getErrorMessage());
				$_MFCTransition['Event'] = '';
				
			}else{
				// transition proceeded !
				if($_debug>1) console("handleManualFlowControlProceed:: Proceed Transition={$_MFCTransition['Transition']}/{$_MFCTransition['Event']} ({$_MFCTransition['Time']})");
				$_MFCTransitionGet = '';
				$_MFCTransition['Transition'] = '';
				$_MFCTransition['Event'] = '';
				$_MFCTransition['Proceed'] = false;
			}
		}
	}
}


//------------------------------------------
// manage delayed actions
//------------------------------------------
// CALLED BY: manageEvents
//------------------------------------------
function manageDelayedActions(){
	global $_debug,$_delay_actions,$_currentTime;
	
	// manage delayed actions and add them in current if needed
	while(count($_delay_actions)>0 && $_delay_actions[0][0]<$_currentTime){
		$delay_action = array_shift($_delay_actions);

		if($_debug>6) console("manageDelayedActions - $delay_action[0]"); 
		if($_debug>6) debugPrint("manageDelayedActions - delay_action",$delay_action); 

		// single Event
		if(@is_array($delay_action['Event']))
			addEventArray($delay_action['Event']);

		// multi Events with possible delays
		if(isset($delay_action['Events']) && @is_array($delay_action['Events'])){
			$delay = 0;
			foreach($delay_action['Events'] as &$val){
				if(is_int($val))
					$delay = $val;
				elseif(is_array($val))
					addEventDelayArray($delay,$val);
			}
		}

		if(isset($delay_action['Call'][1]) && @array_key_exists(0,$delay_action['Call'])){
			// single Call
			if($_debug>9) console("manageDelayedActions:: Call, addCall: {$delay_action['Call'][0]},".print_r($delay_action['Call'][1],true));
			addCallArray($delay_action['Call'][0],$delay_action['Call'][1]);
	
		}elseif(isset($delay_action['CallAsync'][1]) && @array_key_exists(0,$delay_action['CallAsync'])){
			// single Call Async
			if($_debug>9) console("manageDelayedActions:: CallAsync, addCallAsync: {$delay_action['Call'][0]},".print_r($delay_action['Call'][1],true));
			addCallArray($delay_action['CallAsync'][0],$delay_action['CallAsync'][1],true);
		}

		if(isset($delay_action['Calls'])){
			// multi Calls with possible delays
			$delay = 0;
			foreach($delay_action['Calls'] as &$val){
				if(is_int($val))
					$delay = $val;
				elseif(isset($val[1]) && @array_key_exists(0,$val)){
					if($_debug>0) console("manageDelayedActions:: Calls, addCallDelay: {$delay},{$val[0]},".print_r($val[1],true));
					addCallDelayArray($delay,$val[0],$val[1]);
				}
			}

		}elseif(isset($delay_action['CallsAsync'])){
			// multi Calls Async with possible delays
			$delay = 0;
			foreach($delay_action['CallsAsync'] as &$val){
				if(is_int($val))
					$delay = $val;
				elseif(isset($val[1]) && @array_key_exists(0,$val))
					addCallDelayArray($delay,$val[0],$val[1],true);
			}
		}
	}
}


//------------------------------------------
// Handle main changes for FGameMode when the race change
// called with $beginrace=true at BeginRace and $beginrace=false at EndRace/EndPodium, don't call from anywhere !
// can be call with 'setstring' to just set $_GameModeString value.
//------------------------------------------
function changeFGameMode($beginrace=true){
	global $_debug,$_GameInfos,$_NextGameInfos,$_FGameMode,$_NextFGameMode,$_PrevFGameMode,$_FGameModes,$_roundspoints_rule,$_GameModeString,$_modelist,$_ChallengeInfo,$_NextChallengeInfo,$_NextFGameModeFails,$_players_round_restarting;
	if($beginrace === true){
		// BegingRace

		// set back the $_players_round_restarting used for the failed set GameMode restart
		if($_NextFGameModeFails && $_players_round_restarting)
			$_players_round_restarting = false;

		if($_NextFGameMode === '' || !isset($_FGameModes[$_NextFGameMode])){
			// no fgamemode
			$_PrevFGameMode = $_FGameMode;
			$_NextFGameMode = '';
			$_FGameMode = '';
			$_NextFGameModeFails = 0;

		}else{
			if(!isset($_FGameModes[$_NextFGameMode]['GameInfos']['GameMode'])){
				// no gamemode info, disable its use
				if($_debug>1) console("changeFGameMode(Begin):: FGameMode {$_NextFGameMode} miss GameMode infos, keep current FGameMode ({$_FGameMode}) !");
				$_NextFGameMode = $_FGameMode;
				$_NextFGameModeFails = 0;
				
			}else{
				$fgmok = true;

				// check if GameMode is ok (it can fail in some strange cases)
				if($_FGameModes[$_NextFGameMode]['GameInfos']['GameMode'] != $_GameInfos['GameMode'] && $_NextFGameModeFails <= 0){
					if($_debug>0) console("changeFGameMode(Begin):: bad GameMode ({$_GameInfos['GameMode']}) for FGameMode ({$_NextFGameMode}), try to set it and restart !");
					// bad gamemode, try to set it again
					addCall(null,'SetGameMode',$_FGameModes[$_NextFGameMode]['GameInfos']['GameMode']+0);
					callMulti();
					// and make a round_restarting restart
					$_NextFGameModeFails++;
					$_players_round_restarting = true;
					addCall(true,'ChallengeRestart');
					callMulti();
					return;
				}

				// check if NextFGameMode ['GameInfos'] values are ok
				foreach($_FGameModes[$_NextFGameMode]['GameInfos'] as $ginfo => $val){
					if(isset($_GameInfos[$ginfo]) && $_GameInfos[$ginfo] != $val){
						if($_debug>0) console("changeFGameMode(Begin):: FGameMode({$_NextFGameMode}) need {$ginfo}={$val} (current {$ginfo} is {$_GameInfos[$ginfo]}) !");
						$fgmok = false;
					}
				}
				if(!$fgmok){
					// some bad gameinfos values, restart challenge
					if($_debug>1) console("changeFGameMode(Begin):: bad values for FGameMode {$_NextFGameMode}, disable FGameMode !");
					//addCall(null,'RestartChallenge');
					$_PrevFGameMode = $_FGameMode;
					$_NextFGameMode = $_FGameMode;
					if($_FGameMode != '')
						addEvent('FGameModeChange',$_FGameMode,'');
					$_FGameMode = '';
					$_NextFGameModeFails = 0;
					
				}else{
					// gamemode is ok, set current fgamemode
					$_NextFGameModeFails = 0;
					if(isset($_FGameModes[$_NextFGameMode]['Next']))
						$_FGameModes[$_NextFGameMode]['Current'] = $_FGameModes[$_NextFGameMode]['Next'];
					$_PrevFGameMode = $_FGameMode;
					if($_FGameMode != $_NextFGameMode)
						addEvent('FGameModeChange',$_FGameMode,$_NextFGameMode);
					$_FGameMode = $_NextFGameMode;
					if($_debug>1) console("changeFGameMode(Begin):: FGameMode is {$_FGameMode} !");

					// set new custom points if needed (should have been set at EndRace/EndPodium)
					if(isset($_FGameModes[$_FGameMode]['RoundCustomPointsRule'])){
						if($_FGameModes[$_FGameMode]['RoundCustomPointsRule'] != $_roundspoints_rule){
							$_FGameModes[$_FGameMode]['RoundCustomPointsRuleOld'] = $_roundspoints_rule;
							setCustomPoints($_FGameModes[$_FGameMode]['RoundCustomPointsRule']);
						}
					}
				}
			}
		}

	}elseif($beginrace === false){
		// EndRace

		if($_NextFGameMode === '' || !isset($_FGameModes[$_NextFGameMode])){
			if($_FGameMode !== ''){
				// there were a FGameMode, so leave it...

				// set back custom points if needed
				if(isset($_FGameModes[$_FGameMode]['RoundCustomPointsRuleOld'])){
					if($_FGameModes[$_FGameMode]['RoundCustomPointsRuleOld'] != $_roundspoints_rule){
						setCustomPoints($_FGameModes[$_FGameMode]['RoundCustomPointsRuleOld']);
						unset($_FGameModes[$_FGameMode]['RoundCustomPointsRuleOld']);
					}
				}

			}
			// no next fgamemode
			$_NextFGameMode = '';
			$_NextFGameModeFails = 0;

		}else{
			if(!isset($_FGameModes[$_NextFGameMode]['GameInfos']['GameMode'])){
				// no gamemode info in next, keep current (should not happen)
				if($_debug>1) console("changeFGameMode(End):: FGameMode {$_NextFGameMode} miss GameMode infos, keep current FGameMode ({$_FGameMode}) !");
				$_NextFGameMode = $_FGameMode;
				$_NextFGameModeFails = 0;
				
			}else{
				// first, if needed then try to set GameMode only
				callMulti();
				if($_FGameModes[$_NextFGameMode]['GameInfos']['GameMode'] != $_NextGameInfos['GameMode']){
					addCall(null,'SetGameMode',$_FGameModes[$_NextFGameMode]['GameInfos']['GameMode']+0);
					callMulti();
					if($_FGameModes[$_NextFGameMode]['GameInfos']['GameMode'] != $_NextGameInfos['GameMode']){
						if($_debug>0) console("changeFGameMode(End):: failed to set GameMode {$_FGameModes[$_NextFGameMode]['GameInfos']['GameMode']} ({$_NextGameInfos['GameMode']}) !");
					}
				}
				// check needed NextFGameMode ['GameInfos'] values to set them
				$gameinfos = $_NextGameInfos;
				$fgmok = true;
				foreach($_FGameModes[$_NextFGameMode]['GameInfos'] as $ginfo => $val){
					if(isset($gameinfos[$ginfo]) && $gameinfos[$ginfo] != $val){
						if($_debug>1) console("changeFGameMode(End):: SetGameInfos -> {$ginfo}={$val}");
						$gameinfos[$ginfo] = $val;
						$fgmok = false;
					}
				}
				if(!$fgmok){
					// set gameinfos values
					if($_debug>3) debugPrint("changeFGameMode(End):: SetGameInfos: ",$gameinfos);
					addCall(null,'SetGameInfos',$gameinfos);
					callMulti();

					// check it
					if($_FGameModes[$_NextFGameMode]['GameInfos']['GameMode'] != $_NextGameInfos['GameMode']){
						// still bad gamemode !  we can just hope that it will work with a restart at next BeginRace as workaround ! :(
						console("changeFGameMode:: failed to set next GameMode to {$_FGameModes[$_NextFGameMode]['GameInfos']['GameMode']} (still {$_NextGameInfos['GameMode']}) !");
					}
				}

				// set back old custom points if needed
				if($_FGameMode != $_NextFGameMode && isset($_FGameModes[$_FGameMode]['RoundCustomPointsRuleOld'])){
					if($_FGameModes[$_FGameMode]['RoundCustomPointsRuleOld'] != $_roundspoints_rule){
						setCustomPoints($_FGameModes[$_FGameMode]['RoundCustomPointsRuleOld']);
						unset($_FGameModes[$_FGameMode]['RoundCustomPointsRuleOld']);
					}
				}
				// set new custom points if needed
				if(isset($_FGameModes[$_NextFGameMode]['RoundCustomPointsRule'])){
					if($_FGameModes[$_NextFGameMode]['RoundCustomPointsRule'] != $_roundspoints_rule){
						$_FGameModes[$_NextFGameMode]['RoundCustomPointsRuleOld'] = $_roundspoints_rule;
						setCustomPoints($_FGameModes[$_NextFGameMode]['RoundCustomPointsRule']);
					}
				}

			}
		}
	}

	// set $_GameModeString value
	if($_FGameMode != '')
		$_GameModeString = $_FGameMode;
	else if(isset($_modelist[$_GameInfos['GameMode']]))
		$_GameModeString = $_modelist[$_GameInfos['GameMode']];
	else
		$_GameModeString = "GameMode {$_GameInfos['GameMode']}";
}


//------------------------------------------
// Make events from callback events, ask for more info if needed
//------------------------------------------
// CALLED BY: manageCallbacks() after getting callback
//------------------------------------------
function callbackEvents(){
	global $_debug,$_dedebug,$_cbTime,$_playerlistTime,$_currentTime,$_ServerId,$_client,$_WarmUp,$_FWarmUp,$_NextFWarmUp;
	global $_old_Status,$_Status,$_StatusCode,$_StatusList,$_ChallengeInfo,$_PrevChallengeInfo,$_Ranking,$_BeginRaceSeen,$_GameInfos,$_methods_list,$_bills,$_CurrentChallengeIndex,$_NextChallengeIndex,$_NetworkStats,$_Game,$_ml_act_rev,$_is_ingame,$_relays,$_is_relay,$_master,$_MFCTransition,$_use_flowcontrol,$_transition_events,$_fastecho,$_EndMatchCondition,$_players,$_MaxMaps,$_NeedGetRankings,$_ChatFloodRate,$_PrevGameInfos,$_PrevFGameMode,$_FGameMode,$_beginrace_time,$_beginround_time,$_endround_time,$_endrace_time,$_sleep_short,$_remote_controller_chat_is_admin;
	// 'StatusChanged','BeginRound','BeginRace','EndRace','ServerStart','ServerStop',        
	// 'PlayerConnect','PlayerDisconnect','PlayerCheckpoint','PlayerFinish','PlayerChat',

	$callback_responses = $_client->getCBResponses();
	if(!is_array($callback_responses))
		return;

	foreach($callback_responses as $cb){
		$_cbTime = $_currentTime;
		//if($_debug>8) console("*** store time for keepalive ($_cbTime)");

		if($_dedebug>7){
			console2("***TMcallback: ".$cb[0]);
			if($_dedebug>8)
				showIt($cb[0],$cb[1]);
		}

		switch($cb[0]){
			
			case 'TrackMania.PlayerChat': //$cb[1]:[0]=pluid, [1]=login, [2]=message, [3]=is command
				$login = ''.$cb[1][1];
				if(isset($_relays[$login]) && !isset($_relays[$login]['Master']))
					$_relays[$login]['Master'] = true;
				
				//console("TrackMania.PlayerChat({$cb[1][0]},{$cb[1][1]}):: {$cb[1][2]}");

				if($_is_relay && $cb[1][0] == $_master['PlayerId'] && strncmp($cb[1][2],'::torelay::',11)==0){
					// received a message from master ! use chat because TunnelSendData has too limited bandwidth !
					// note: actually use TunnelSendData which has been relaxed between master and relays
					$data = unserialize(gzinflate(base64_decode(substr($cb[1][2],11))));
					if($_dedebug>2) console("TrackMania.PlayerChat({$cb[1][0]},{$cb[1][1]})::(".strlen($cb[1][2]).") master data for relay !...");
					if($_dedebug>8) console("TrackMania.PlayerChat({$cb[1][0]},{$cb[1][1]})::(".strlen($cb[1][2]).") master data for relay : ".print_r($data,true));
					addEvent('DatasFromMaster',$data);
					// if sub-relays then re-send to them
					if(count($_relays)>0){
						foreach($_relays as $relay){
							if(!isset($relay['Master'])){
								if($_dedebug>2) console("TrackMania.PlayerChat({$cb[1][0]},{$cb[1][1]})::(".strlen($cb[1][2]).") resend master data to sub-relay.");
								addCall(null,'ChatSendServerMessageToId',$cb[1][2],$relay['PlayerId']);
							}
						}
					}

				}else{
					// normal chat message
					if($_dedebug>5) console("TrackMania.PlayerChat({$cb[1][0]},{$cb[1][1]})::(".strlen($cb[1][2]).")");
					$_ChatFloodRate++;
					if(isset($_players[$login]['Active']) && $_players[$login]['Active']){
						resetPlayerNetworkStats($login);
						$chat = trim($cb[1][2]);
						$iscommand = (substr($chat, 0, 1) == '/');
						addEvent('PlayerChat',$login,$chat,$iscommand); // addEvent('PlayerChat',login,message,iscommand)

					}elseif($_remote_controller_chat_is_admin && $cb[1][0] == $_ServerId){
						// message from server (should be builtin dedicated chat or remote controller chat)
						$chat = trim($cb[1][2]);
						$iscommand = (substr($chat, 0, 1) == '/');

						if(!$iscommand && strlen($chat) > 7 && substr($chat, 0, 1) == '$'){
							// check for $./ , $.../ and $.$.../ cases (servermania send the 3rd)
							if(substr($chat, 2, 1) == '/'){
								$chat = substr($chat, 2);
								$iscommand = true;
							}elseif(substr($chat, 4, 1) == '/'){
								$chat = substr($chat, 4);
								$iscommand = true;
							}elseif(substr($chat, 6, 1) == '/'){
								$chat = substr($chat, 6);
								$iscommand = true;
							}
						}

						if($iscommand){
							if($_dedebug>1) console("TrackMania.PlayerChat({$cb[1][0]},{$cb[1][1]}):: remote controller command ! ($login,$chat,$iscommand)");
							addEvent('PlayerChat',$login,$chat,$iscommand); // addEvent('PlayerChat',login,message,iscommand)
						}
					}
				}
				break;
				
			case 'TrackMania.PlayerManialinkPageAnswer': //$cb[1]:[0]=pluid, [1]=login, [2]=Answer
				if($_dedebug>4) console("TrackMania.PlayerManialinkPageAnswer({$cb[1][1]},{$cb[1][2]})::");
				resetPlayerNetworkStats(''.$cb[1][1]);
				$action = isset($_ml_act_rev[$cb[1][2]]) ? $_ml_act_rev[$cb[1][2]] : '';
				addEvent('PlayerManialinkPageAnswer',''.$cb[1][1],$cb[1][2]+0,$action); // addEvent('PlayerManialinkPageAnswer',login,answer,action)
				break;

			case 'TrackMania.PlayerCheckpoint': //$cb[1]:[0]=pluid, [1]=login, [2]=time/score, [3]=lap, [4]=checkpt num
				$login = ''.$cb[1][1];
				if($_dedebug>2) console("TrackMania.PlayerCheckpoint({$cb[1][1]},{$cb[1][2]},{$cb[1][3]},{$cb[1][4]})::");
				if(isset($_players[$login]['Active']) && $_players[$login]['Active']){
					resetPlayerNetworkStats(''.$cb[1][1]);
					addEvent('PlayerCheckpoint',''.$cb[1][1],$cb[1][2],$cb[1][3],$cb[1][4],false); // addEvent('PlayerCheckPoint',login,time,lap,checkpt)
				}
				break;

			case 'TrackMania.PlayerInfoChanged': //$cb[1]:[0]=PlayerInfo
				if($_dedebug>4) console("TrackMania.PlayerInfoChanged([".@implode(',',$cb[1][0])."])::");
				if(isset($cb[1][0]['Login'])){
					$login = ''.$cb[1][0]['Login'];
					//debugPrint("callbackEvents - PlayerInfoChanged - ",$cb[1][0]);
					if(isset($_players[$login]['Active']) && $_players[$login]['Active']){
						addEvent('PlayerInfoChanged',$login,$cb[1][0]); // addEvent('PlayerInfoChanged',Login,PlayerInfo)
						managePlayer($login,$cb[1][0]);

						// this callback means that there is a player : update NetworkStats if empty
						if(!isset($_NetworkStats['PlayerNetInfos']) || count($_NetworkStats['PlayerNetInfos']) <= 0)
							addCall(true,'GetNetworkStats');
					}
				}
				break;

			case 'TrackMania.PlayerFinish': //$cb[1]:[0]=pluid, [1]=login, [2]=time
				if($_StatusCode >= 3){
					$login = ''.$cb[1][1];
					if($cb[1][0] == $_ServerId && !$_is_ingame){ 
						if($_dedebug>3) console("TrackMania.PlayerFinish({$cb[1][1]},{$cb[1][2]})::(server id,{$cb[1][0]})");
						// it's the server tm account, not a player...
						//addCall(true,'GetPlayerList',260,0,1);

					}elseif(isset($_players[$login]['Active']) && $_players[$login]['Active']){
						// player
						if($_dedebug>1) console("TrackMania.PlayerFinish({$cb[1][1]},{$cb[1][2]})::");

						if($cb[1][2] > 0 && $_currentTime - $_beginround_time > 2000){
							addCall(true,'GetDetailedPlayerInfo',$cb[1][1]);
							// if GetCurrentRankingForLogin is available then use it directly
							if(isset($_methods_list['GetCurrentRankingForLogin'])){
								// ****** call it and remove the $_NeedGetRankings = true once tested and ok *****0
								//addCall(true,'GetCurrentRankingForLogin',$cb[1][1]);
								$_NeedGetRankings = true;
							}else{
								$_NeedGetRankings = true;
							}
						}
						addEvent('PlayerFinish',''.$cb[1][1],$cb[1][2],null); // addEvent('PlayerFinish',login,time,checkpts)
					}
				}else{
					if($_dedebug>3) console("TrackMania.PlayerFinish({$cb[1][1]},{$cb[1][2]})::(status={$_StatusCode})");
				}
				break;

			case 'TrackMania.PlayerConnect': //$cb[1]:[0]=login, [1]=is spec
				$login = ''.$cb[1][0];
				if($_dedebug>2) console("TrackMania.PlayerConnect({$login})::");
				playerConnect($login,'CB');
				break;

			case 'TrackMania.PlayerDisconnect': //$cb[1]:[0]=login
				$login = ''.$cb[1][0];
				if($_dedebug>2)	console("TrackMania.PlayerDisconnect({$login})::");
				playerDisconnect($login,'CB');
				break;

			case 'TrackMania.StatusChanged': //$cb[1]:[0]=statuscode, [1]=statusname
				if($_dedebug>4)	console("TrackMania.StatusChanged({$cb[1][0]},{$cb[1][1]})::");
				if($cb[1][0] != $_StatusCode){
					$_old_Status = $_Status;
					$_Status['Code'] = $cb[1][0];
					$_Status['Name'] = $cb[1][1];
					$_StatusCode = $cb[1][0];
					addEvent('StatusChanged',$_Status,$_StatusCode);
				}

				if(!$_use_flowcontrol && $_old_Status['Code'] == 5 && $_StatusCode != 5){
					// should happen for relay only, simulate EndPodium
					addEvent('EndPodium',0);
					addEvent('EndPodium',-1);
				}

				if($_StatusCode != $_old_Status['Code']){ // Status changed !
				
					// 3 (Synchronization) or 4 (Play) but no BeginRace before : first or restart
					if(($_StatusCode==3 || $_StatusCode==4) && $_BeginRaceSeen==false){
						$_PrevChallengeInfo = $_ChallengeInfo;
						$_PrevGameInfos = $_GameInfos;
						$_PrevFGameMode = $_FGameMode;
						forceDelayedEvent('BeforeEndRound');
						forceDelayedEvent('EndRound');
						forceDelayedEvent('EndRace');
						forceDelayedEvent('EndResult');
						forceDelayedEvent('EndPodium');
						addCall(array('CB'=>array('setWarmUpState',array(0,true,'StatusChanged,NoBeginRaceSeen'),0)),'GetWarmUp');
						addCall(true,'GetServerOptions',1);
						addCall(true,'GetGameInfos',1);
						addCall(true,'GetRoundCustomPoints');
						addCall(true,'GetForcedMods');
						addCall(true,'GetForcedMusic');
						addCall(true,'GetServerPackMask');
						addCall(true,'GetPlayerList',260,0,1);
						addCall(true,'GetCurrentRanking',260,0);
						//addCall(true,'GetChallengeList',$_CurrentChallengeIndex+$_MaxMaps,0);
						//$_NextChallengeIndex = -1;
						addCall(true,'GetCurrentChallengeIndex');
						addCall(true,'GetBanList',260,0);
						addCall(true,'GetBlackList',260,0);
						addCall(true,'GetGuestList',260,0);
						addCall(true,'GetIgnoreList',260,0);
						addCall(true,'GetCallVoteRatios');
						$_playerlistTime = $_currentTime;
						callMulti();
						changeFGameMode();
						$_beginrace_time = $_currentTime;
						addEventDelay(2,'BeginRace',$_GameInfos,$_ChallengeInfo,$_EndMatchCondition=='Finished',$_WarmUp,$_NextFWarmUp); // addEvent('BeginRace',GameInfos,ChallengeInfo,NewCup,WarmUp,FWarmUp)
						addEventDelay(2,'BeginChallenge',$_ChallengeInfo,$_GameInfos); // addEvent('BeginChallenge',ChallengeInfo,GameInfos)
						$_BeginRaceSeen = true;
					}
					/*
					// was 4 (Play) and now 3 (Synchronization) -> it's the end of round
					if($_old_Status['Code']==4 && $_StatusCode==3){
				  callMulti();
					addEventDelay(3,'EndRound',$_Ranking,$_ChallengeInfo,$_GameInfos,$_players_round_restarting); // addEvent('EndRound',Ranking,ChallengeInfo,GameInfos,$SpecialRestarting)
					addCall(true,'GetPlayerList',260,0,1);
					addCall(true,'GetCurrentRanking',260,0);
					}
					*/
				}
				break;

			case 'TrackMania.ManualFlowControlTransition': //$cb[1]:[0]=Transition
				if($_dedebug>1)	console("TrackMania.ManualFlowControlTransition({$cb[1][0]})::");
				if($_use_flowcontrol){
					//   'Synchro -> Play' : before BeginRound and StatusChanged 3->4, seconds after StatusChanged 2->3 or 4->3 and EndRound
					//   'Play -> Synchro' : before StatusChanged 4->3 and EndRound, after all PlayerFinish
					//   'Play -> Podium' : before StatusChanged 4->5 and EndRound and EndRace, 
					//   'Podium -> Synchro' : before StatusChanged 5->2 and BeginRace, seconds after EndRace
					$event = isset($_transition_events[$cb[1][0]]) ? $_transition_events[$cb[1][0]] : '';
					$_MFCTransition = array('Time'=>$_currentTime,'Transition'=>$cb[1][0],'Event'=>$event,'DelayTime'=>$_currentTime,'Proceed'=>false);
					if($event=='BeforeEndRound'){
						addCall(true,'GetGameInfos',1); // want to have chattime for sure to check against value 0 !
						addCall(true,'GetServerOptions',1);
						addCall(true,'GetCurrentRanking',260,0);
					}elseif($event=='EndPodium'){
						forceDelayedEvent('BeforeEndRound');
						forceDelayedEvent('EndRound');
						forceDelayedEvent('EndRace');
						forceDelayedEvent('EndResult');
						forceDelayedEvent('EndRound');
					}
					//addCall(array('CB'=>array('setWarmUpState',array(0,false,$cb[1][0]),0)),'GetWarmUp'); // always false at end of race, so useless
					if($_dedebug>1){
						if($cb[1][0] == 'Play -> Podium') console("TrackMania.ManualFlowControlTransition({$cb[1][0]})::");
						if($cb[1][0] != 'Play -> Podium' && $cb[1][0] != 'Synchro -> Play' && $cb[1][0] != 'Play -> Synchro' && $cb[1][0] != 'Podium -> Synchro') console("TrackMania.ManualFlowControlTransition({$cb[1][0]}):: unknown transition !!!");
					}
				}
				break;

			case 'TrackMania.BeginRound': //$cb[1]:empty
				if($_dedebug>0)	console("TrackMania.BeginRound()::");
				$_beginround_time = $_currentTime;
				if($_StatusCode != 4){
					$_StatusCode = 4;
					$_old_Status = $_Status;
					$_Status = $_StatusList[$_StatusCode];
					addEvent('StatusChanged',$_Status,$_StatusCode);
				}
				if($_BeginRaceSeen == false){ // for some restart case, particulary restart without player
					$_PrevChallengeInfo = $_ChallengeInfo;
					$_PrevGameInfos = $_GameInfos;
					$_PrevFGameMode = $_FGameMode;
					forceDelayedEvent('BeforeEndRound');
					forceDelayedEvent('EndRound');
					forceDelayedEvent('EndRace');
					forceDelayedEvent('EndResult');
					forceDelayedEvent('EndPodium');
					$_BeginRaceSeen = true;
					addCall(array('CB'=>array('setWarmUpState',array(0,true,'BeginRound,NoBeginRaceSeen'),0)),'GetWarmUp');
					if(isset($_methods_list['CheckEndMatchCondition']))
						addCall(null,'CheckEndMatchCondition');	
					addCall(true,'GetGameInfos',1);
					addCall(true,'GetServerOptions',1);
					addCall(true,'GetPlayerList',260,0,1);
					addCall(true,'GetCurrentRanking',260,0);
					addCall(true,'GetRoundCustomPoints');
					$_playerlistTime = $_currentTime;
					//addCall(true,'GetChallengeList',$_CurrentChallengeIndex+$_MaxMaps,0);
					//$_NextChallengeIndex = -1;
					//addCall(true,'GetCurrentChallengeIndex');
					addCall(true,'GetBanList',260,0);
					addCall(true,'GetBlackList',260,0);
					addCall(true,'GetGuestList',260,0);
					addCall(true,'GetIgnoreList',260,0);
					addCall(true,'GetForcedMods');
					addCall(true,'GetForcedMusic');
					addCall(true,'GetServerPackMask');
					callMulti();
					changeFGameMode();
					$_beginrace_time = $_currentTime;
					addEventDelay(1,'BeginRace',$_GameInfos,$_ChallengeInfo,$_EndMatchCondition=='Finished',$_WarmUp,$_NextFWarmUp); // addEvent('BeginRace',GameInfos,ChallengeInfo,NewCup,WarmUp,FWarmUp)
					addEventDelay(1,'BeginChallenge',$_ChallengeInfo,$_GameInfos); // addEvent('BeginChallenge',ChallengeInfo,GameInfos)
					if(!$_use_flowcontrol){
						// should happen for relay only, simulate EndPodium
						addEventDelay(2,'BeforePlay',0);
						addEventDelay(2,'BeforePlay',-1);
					}
					addEventDelay(2,'BeginRound');
				}else{
					addCall(array('CB'=>array('setWarmUpState',array(0,true,'BeginRound'),0)),'GetWarmUp');
					if(isset($_methods_list['CheckEndMatchCondition']))
						addCall(null,'CheckEndMatchCondition');	
					addCall(true,'GetGameInfos',1);
					addCall(true,'GetServerOptions',1);
					addCall(true,'GetPlayerList',260,0,1);
					addCall(true,'GetCurrentRanking',260,0);
					addCall(true,'GetRoundCustomPoints');
					$_playerlistTime = $_currentTime;
					callMulti();
					if(!$_use_flowcontrol){
						// should happen for relay only, simulate EndPodium
						addEvent('BeforePlay',0);
						addEvent('BeforePlay',-1);
					}
					addEvent('BeginRound');
				}
				//if($_dedebug>8) console("TrackMania.BeginRound():: *** store time for getplayerlist ($_playerlistTime)");
				break;

			case 'TrackMania.EndRound': //$cb[1]:empty
				if($_dedebug>0)	console("TrackMania.EndRound()::");
				$_endround_time = $_currentTime;
				if($_StatusCode != 3){
					$_StatusCode = 3;
					$_old_Status = $_Status;
					$_Status = $_StatusList[$_StatusCode];
					addEvent('StatusChanged',$_Status,$_StatusCode);
				}
				//addCall(array('CB'=>array('setWarmUpState',array(0,false,'EndRound'),0)),'GetWarmUp'); // always false at end of race, so useless
				addCall(true,'GetGameInfos',1); // want to have chattime for sure to check against value 0 !
				addCall(true,'GetServerOptions',1);
				addCall(true,'GetPlayerList',260,0,1);
				addCall(true,'GetCurrentRanking',260,0);
				if(!isset($_players_round_restarting))
					$_players_round_restarting = false;
				callMulti();
				// call EndRound with a delay, to avoid having some PlayerFinish(0) come after !
				if(!$_use_flowcontrol){
					// should happen for relay only, simulate EndPodium
					addEvent('BeforeEndRound',0);
					addEvent('BeforeEndRound',-1);
				}
				addEventDelay(5,'EndRound',$_Ranking,$_ChallengeInfo,$_GameInfos,$_players_round_restarting); // addEvent('EndRound',Ranking,ChallengeInfo,GameInfos,$SpecialRestarting)
				break;

			case 'TrackMania.BeginRace': //$cb[1]:[0]=ChallengeInfo array
				if($_dedebug>0) console("TrackMania.BeginRace(chalinfo)::");
				$_sleep_short = false;
				$_beginrace_time = $_currentTime;
				if($_StatusCode != 3){
					$_StatusCode = 3;
					$_old_Status = $_Status;
					$_Status = $_StatusList[$_StatusCode];
					addEvent('StatusChanged',$_Status,$_StatusCode);
				}
				$_PrevChallengeInfo = $_ChallengeInfo;
				$_PrevGameInfos = $_GameInfos;
				$_PrevFGameMode = $_FGameMode;
				$_ChallengeInfo = $cb[1][0];
				if(!isset($_ChallengeInfo['NbCheckpoints']))
					$_ChallengeInfo['NbCheckpoints'] = -1;
				if(!isset($_ChallengeInfo['NbLaps']))
					$_ChallengeInfo['NbLaps'] = -1;
				else if(isset($_ChallengeInfo['LapRace']) && !$_ChallengeInfo['LapRace'])
					$_ChallengeInfo['NbLaps'] = 0;
				if($_ChallengeInfo['NbLaps'] > 0)
					$_ChallengeInfo['LapAuthorTime'] = (int) floor($_ChallengeInfo['AuthorTime'] / $_ChallengeInfo['NbLaps']);
				else
					$_ChallengeInfo['LapAuthorTime'] = $_ChallengeInfo['AuthorTime'];
				forceDelayedEvent('EndResult');
				if($_StatusCode==5){
					// quick restart where StatusCode==2 is missing : add it
					$_old_Status = $_Status;
					$_Status['Code'] = 2;
					$_Status['Name'] = 'Launching';
					$_StatusCode = 2;
					addEvent('StatusChanged',$_Status,$_StatusCode);
				}
				$_BeginRaceSeen = true;

				addCall(array('CB'=>array('setWarmUpState',array(0,true,'BeginRace'),0)),'GetWarmUp');
				addCall(true,'GetServerOptions',1);
				addCall(true,'GetGameInfos',1);
				addCall(true,'GetChallengeList',$_CurrentChallengeIndex+$_MaxMaps,0);
				//$_NextChallengeIndex = -1;
				addCall(true,'GetCurrentChallengeIndex');
				addCall(true,'GetNextChallengeIndex');
				addCall(true,'GetNextChallengeInfo');
				addCall(true,'GetPlayerList',260,0,1);
				addCall(true,'GetCurrentRanking',260,0);
				addCall(true,'GetBanList',260,0);
				addCall(true,'GetBlackList',260,0);
				addCall(true,'GetGuestList',260,0);
				addCall(true,'GetIgnoreList',260,0);
				addCall(true,'GetRoundCustomPoints');
				addCall(true,'GetForcedMods');
				addCall(true,'GetForcedMusic');
				addCall(true,'GetServerPackMask');
				addCall(true,'GetCallVoteRatios');
				callMulti();
				changeFGameMode();
				addEvent('BeginRace',$_GameInfos,$_ChallengeInfo,$_EndMatchCondition=='Finished',$_WarmUp,$_NextFWarmUp); // addEvent('BeginRace',GameInfos,ChallengeInfo,NewCup,WarmUp,FWarmUp)
				addEvent('BeginChallenge',$_ChallengeInfo,$_GameInfos); // addEvent('BeginChallenge',ChallengeInfo,GameInfos)
				break;

			case 'TrackMania.EndRace': //$cb[1]:[0]=CurrentRanking array,[1]=ChallengeInfo array
				if($_dedebug>0) console("TrackMania.EndRace(ranking,chalinfo)::");
				$_endrace_time = $_currentTime;
				if($_StatusCode != 5){
					$_StatusCode = 5;
					$_old_Status = $_Status;
					$_Status = $_StatusList[$_StatusCode];
					addEvent('StatusChanged',$_Status,$_StatusCode);
				}
				//addCall(array('CB'=>array('setWarmUpState',array(0,false,'EndRace'),0)),'GetWarmUp'); // always false at end of race, so useless
				$_Ranking = $cb[1][0];
				$_ChallengeInfo = $cb[1][1];
				if(!isset($_ChallengeInfo['NbCheckpoints']))
					$_ChallengeInfo['NbCheckpoints'] = -1;
				if(!isset($_ChallengeInfo['NbLaps']))
					$_ChallengeInfo['NbLaps'] = -1;
				else if(isset($_ChallengeInfo['LapRace']) && !$_ChallengeInfo['LapRace'])
					$_ChallengeInfo['NbLaps'] = 0;
				if($_ChallengeInfo['NbLaps'] > 0)
					$_ChallengeInfo['LapAuthorTime'] = (int) floor($_ChallengeInfo['AuthorTime'] / $_ChallengeInfo['NbLaps']);
				else
					$_ChallengeInfo['LapAuthorTime'] = $_ChallengeInfo['AuthorTime'];
				manageRanking();
				addCall(true,'GetNextGameInfo',1);

				$_BeginRaceSeen=false;
				if(isset($_methods_list['CheckEndMatchCondition']))
					addCall(null,'CheckEndMatchCondition');
				else{
					// old dedicated : should test scores and set $_EndMatchCondition to 'Finished' or 'ChangeMap' ....
				}
				callMulti();
				// call EndRace with a delay, to be sure to be after EndRound
				addEventDelay(10,'EndRace',$_Ranking,$_ChallengeInfo,$_GameInfos,$_EndMatchCondition != 'Finished',$_WarmUp,$_FWarmUp); // addEvent('EndRace',Ranking,ChallengeInfo,GameInfos,ContinueCup,WarmUp,FWarmUp)
				// send EndResult event
				if($_GameInfos['ChatTime'] <= 0){
					$delay = 11; // to be sure to have EndResult after EndRace
				}else{
					$delay = 3000;
					if($_GameInfos['ChatTime'] / 2 + 2000 < $delay)
						$delay = floor($_GameInfos['ChatTime'] / 2 + 2000);
				}
				addEventDelay($delay,'EndResult');
				changeFGameMode(false);
				break;

			case 'TrackMania.Echo': //$cb[1]:[0]=internal, [1]=public
				if($_dedebug>4)	console("TrackMania.Echo({$cb[1][0]},{$cb[1][1]})::");
				addEvent('Echo',$cb[1][1],$cb[1][0]); // addEvent('Echo',public,internal)
				//console("TrackMania.Echo::{$cb[1][1]},{$cb[1][0]}  ($_fastecho)");
				if($cb[1][1]=='Fast.running' && $cb[1][0] != $_fastecho){
					// another Fast script announced itself
					$echoval = $cb[1][0]+0;
					if($echoval > 0 && $echoval < $_fastecho+0.0){
						// the other is older : quit
						console("\n\n************************************************************\nOther Fast is running for more time : quit this one ! ({$echoval},{$_fastecho})\n************************************************************\n\n");
						exit();

					}elseif($echoval > $_fastecho){
						// the other is newer, make own announce so the other will close
						console("Other Fast is running for less time : announce it to quit ! ({$echoval},{$_fastecho})");
						addCall(false,'Echo','Fast.running',"{$_fastecho}");
					}
				}
				break;

			case 'TrackMania.BeginChallenge': //$cb[1]:[0]=ChallengeInfo array, [1]=WarmUp bool, [2]=MatchContinuation bool
				if($_dedebug>0)	console("TrackMania.BeginChallenge(chalinfo,{$cb[1][1]},{$cb[1][2]})::");
				// called before TrackMania.BeginRace (there can be several TrackMania.BeginRace because of warmups)
				if($_dedebug>0) console("TrackMania.BeginChallenge({$cb[1][1]},{$cb[1][2]})::");
				$_beginrace_time = $_currentTime;
				setWarmUpState($cb[1][1],true,'BeginChallenge');
				break;
				
			case 'TrackMania.EndChallenge': //$cb[1]:[0]=CurrentRanking array,[1]=ChallengeInfo array, [2]=WasWarmUp bool, [3]=MatchContinuesOnNextChallenge bool,[4]=RestartChallenge bool
				if($_dedebug>0) console("TrackMania.EndChallenge(ranking,chalinfo,{$cb[1][2]},{$cb[1][3]},{$cb[1][4]})::");
				// called before EndRace (there can be several EndRace because of warmups)
				$_endrace_time = $_currentTime;
				break;
				
			case 'TrackMania.ChallengeListModified': //$cb[1]:[0]=CurChallengeIndex, [1]=NextChallengeIndex, [2]=IsListModified
				if($_dedebug>2) console("TrackMania.ChallengeListModified({$cb[1][0]},{$cb[1][1]},{$cb[1][2]})::");
				if($cb[1][0]>=-1 && $_CurrentChallengeIndex != $cb[1][0]){
					$_CurrentChallengeIndex = $cb[1][0];
					// don't do GetCurrentChallengeInfo
				}
				if($cb[1][1]>=0 && $_NextChallengeIndex != $cb[1][1]){
					$_NextChallengeIndex = $cb[1][1];
					addCall(true,'GetNextChallengeInfo');
				}
				if($cb[1][2]){
					// get new challenge list before sending event
					addCall(array('Event'=>array('ChallengeListModified',$cb[1][0],$cb[1][1],$cb[1][2])),
									'GetChallengeList',$_CurrentChallengeIndex+$_MaxMaps,0);
				}else{
					addEvent('ChallengeListModified',$cb[1][0],$cb[1][1],$cb[1][2]); // addEvent('ChallengeListModified',curchalindex,nextchalindex,islistmodified)
				}
				break;
			
			case 'TrackMania.TunnelDataReceived': //$cb[1]:[0]=PlayerUid, [1]=Login, [2]=Data
				if($_dedebug>4) console("TrackMania.ChallengeListModified({$cb[1][1]},datas)::");
				//console("TunnelDataReceived from {$cb[1][1]},{$cb[1][0]} !");
				addEvent('TunnelDataReceived',''.$cb[1][1],$cb[1][2]); // addEvent('TunnelDataReceived',login,data)
				break;

			case 'TrackMania.BillUpdated': //$cb[1]:[0]=BillId, [1]=State, [2]=StateName, [3]=TransactionId
				if($_dedebug>2) console("TrackMania.BillUpdated({$cb[1][0]},{$cb[1][1]},{$cb[1][2]},{$cb[1][3]})::");
				$billid = $cb[1][0];
				if(!isset($_bills[$billid]))
					$_bills[$billid] = array('From'=>'?','To'=>'?','Coppers'=>-1,'Comment'=>'');
				$bill = &$_bills[$billid];
				$bill['State'] = $cb[1][1];
				$bill['StateName'] = $cb[1][2];
				$bill['TransactionId'] = $cb[1][3];
				// correction for known bugged server bill result
				if($bill['From']==='' && $bill['State']==5 && $bill['StateName']=='Refused'){
					$bill['State'] = 4;
					$bill['StateName'] = 'Payed';
				}
				$msg = 'Bill.'.$billid.'('.$bill['From'].','.$bill['To'].','.$bill['Coppers']
				.')=>('.$bill['State'].','.$bill['StateName'].','.$bill['TransactionId'].')';
				console("TrackMania.BillUpdated()::".$msg);
				addEvent('BillUpdated',$billid,$bill); // addEvent('BillUpdated',billid,bill)
				break;

			case 'TrackMania.PlayerIncoherence': //$cb[1]:[0]=pluid, [1]=login
				if($_dedebug>0) console("TrackMania.PlayerIncoherence({$cb[1][1]})::");
				$login = ''.$cb[1][1];
				if(isset($_players[$login]['Active']) && $_players[$login]['Active']){
					addEvent('PlayerIncoherence',''.$cb[1][1]); // addEvent('PlayerIncoherence',login)
				}
				break;

			case 'TrackMania.VoteUpdated': //$cb[1]:[0]=statename, [1]=login, [2]=cmdname, [3]=cmdparam
				// (StateName: 'NewVote', 'VoteCancelled', 'VotePassed' or 'VoteFailed')
				if($_dedebug>0) console("TrackMania.VoteUpdated({$cb[1][0]},{$cb[1][1]},{$cb[1][2]},{$cb[1][3]})::");
				addEvent('VoteUpdated',$cb[1][0],''.$cb[1][1],$cb[1][2],$cb[1][3]); // addEvent('VoteUpdated',$StateName,$Login,$CmdName,$CmdParam)
				break;

			case 'TrackMania.ServerStart': //$cb[1]:empty
				if($_dedebug>0) console("TrackMania.ServerStart()::");
				addEvent('ServerStart');
				break;

			case 'TrackMania.ServerStop': //$cb[1]:empty
				if($_dedebug>0) console("TrackMania.ServerStart()::");
				addEvent('ServerStop');
				break;

			default:
				if($_dedebug>0) console("{$cb[0]}():: (unmanaged)");
		}
	}
}


//------------------------------------------
// should be called only for specific cases
function setWarmUpState($warmup,$setiffalse=false,$caller=''){
	global $_debug,$_WarmUp,$_FWarmUp,$_NextFWarmUp;
	//if($_debug>6) console("*** setWarmUpState - WarmUp: {$_WarmUp} -> {$warmup} ???  ({$caller})");
	if(is_bool($warmup) && $warmup != $_WarmUp){
		if($warmup || $setiffalse){
			if($_debug>2) console("* setWarmUpState - WarmUp: {$_WarmUp} -> {$warmup}  ({$caller})");
			$_WarmUp = $warmup;
			if($_WarmUp && $_FWarmUp > 0 && $caller != 'SpecialRoundRestart'){
				$_FWarmUp = 0;
				$_FNextWarmUp = 0;
				addEvent('FWarmUpChange',$_FWarmUp); // addEvent('FWarmUpChange',status)
			}
			addEvent('WarmUpChange',$_WarmUp); // addEvent('WarmUpChange',status)
		}
	}
}


//------------------------------------------
// auto store main known infos of multicall response
//------------------------------------------
// CALLED BY: callMulti() after TM server response to query
//------------------------------------------
function multicallAutoStoreInfos() {
	global $_debug,$_multicall_response,$_response,$_response_error,$_multidest_logins;
	global $_old_Status,$_Status,$_StatusCode,$_PlayerList,$_Ranking,$_ChallengeInfo,$_GameInfos,$_NextGameInfos;
	global $_ServerOptions,$_PlayerInfo,$_Version,$_ChallengeList,$_NextChallengeInfo,$_RankingTime,$_NeedGetRankings;
	global $_CurrentChallengeIndex,$_NextChallengeIndex,$_methods_related_to_call;
	global $_GuestList,$_IgnoreList,$_BanList,$_BlackList,$_RoundCustomPoints;
	global $_guest_list,$_ignore_list,$_ban_list,$_black_list,$_MaxMaps;
	global $_CallVoteRatios,$_ForcedMods,$_ForcedMusic,$_ServerPackMask;
	global $_playernetstatTime,$_playerlistTime,$_currentTime,$_ServerCoppers;
	global $_EndMatchCondition,$_LadderServerLimits,$_use_flowcontrol,$_MFCTransitionGet,$_MFCTransition;

	if(!isset($_multicall_response['multicall']))
		return;

	foreach($_multicall_response['multicall'] as $index => $call){
		if(!isset($_multicall_response[$index]['faultCode']) && isset($_multicall_response[$index][0])){
			// handle actions on call responses
			$method = $call['methodName'];
			$response = $_multicall_response[$index][0];
			
			// for methods which set something, call the method which will get and update new values
			if(isset($_methods_related_to_call[$method]) && is_array($_methods_related_to_call[$method])){
				if(@is_array($_methods_related_to_call[$method][0])){
					foreach($_methods_related_to_call[$method] as $related){
						if($_debug>3) console("Related method: $method -> ".$related[0]);
						addCallArray(true,$related);
						//addCallDelayArray(1000,true,$related);
					}
				}else{
					if($_debug>3) console("Related method: $method -> ".$_methods_related_to_call[$method][0]);
					addCallArray(true,$_methods_related_to_call[$method]);
					//addCallDelayArray(1000,true,$_methods_related_to_call[$method]);
				}

			}elseif($method == 'ForceSpectator'){
				// special case
				$login = ''.$call['params'][0];
				if($_debug>3) console("Related method: $method -> GetPlayerInfo({$login})");
				addCall(true,'GetPlayerInfo',$login,1);
			}
			
			// handle specific actions on call responses
			switch($method){
				case 'GetStatus':
					if(isset($response['Code'])){
						if($response['Code']==5 && $_StatusCode==2)
							break; // special case : quick restart where status=2 is missing
						$_old_Status = $_Status;
						$_Status = $response;
						$_StatusCode = $response['Code'];
					}
					break;
				case 'ManualFlowControlGetCurTransition':
					if($_use_flowcontrol){
						if($_MFCTransition['Transition']=='' &&
							 $_MFCTransitionGet!='' && $_MFCTransitionGet==$response){
							$event = isset($_transition_events[$response]) ? $_transition_events[$response] : '';
							$_MFCTransition = array('Time'=>$_currentTime,'Transition'=>$response,'Event'=>$event,'DelayTime'=>$_currentTime,'Proceed'=>false);
							if($event=='BeforeEndRound'){
								addCall(true,'GetGameInfos',1); // want to have chattime for sure to check against value 0 !
								addCall(true,'GetServerOptions',1);
								addCall(true,'GetCurrentRanking',260,0);
							}
							if($_debug>0) console("multicallAutoStoreInfos:: add FlowControlTransition {$response},{$event} to avoid lockup !");
						}
						$_MFCTransitionGet = $response;
					}
					break;
				case 'GetPlayerList':
					if(isset($response[0]['Flags'])){
						loginToString($response,1);
						$_PlayerList = $response;
						$_playerlistTime = $_currentTime;
						managePlayerList();
					}elseif(count($response)==0){
						$_PlayerList = array();
						$_playerlistTime = $_currentTime;
						managePlayerList();
					}
					break;
				case 'GetCurrentRankingForLogin':
					if(isset($response[0]['Rank'])){
						loginToString($response,1);
						manageRankingForLogins($response);
					}
					break;
				case 'GetCurrentRanking':
					$_NeedGetRankings = false;
					if(isset($response[0]['Rank'])){
						$_RankingTime = $_currentTime;
						loginToString($response,1);
						$_Ranking = $response;
						manageRanking();
					}elseif(count($response)==0){
						$_RankingTime = $_currentTime;
						$_Ranking = array();
						manageRanking();
					}
					break;
				case 'GetPlayerInfo':
					if(isset($response['Login'])){
						loginToString($response,0);
						managePlayer($response['Login'],$response);
					}
					break;
				case 'GetDetailedPlayerInfo':
					//if($_debug>1) debugPrint("multicallAutoStoreInfos - GetDetailedPlayerInfo",$response['Login']);
					if(isset($response['Login'])){
						loginToString($response,0);
						managePlayerInfo($response['Login'],$response);
					}
					//debugPrint('multicallAutoStoreInfos - GetDetailedPlayerInfo - response',$response);
					break;
				case 'GetGameInfos':
					if(isset($response['CurrentGameInfos']['DisableRespawn']))
						$_GameInfos = $response['CurrentGameInfos'];
					if(isset($response['NextGameInfos']['DisableRespawn']))
						$_NextGameInfos = $response['NextGameInfos'];
					break;
				case 'GetCurrentGameInfo':
					if(isset($response['DisableRespawn']))
						$_GameInfos = $response;
					break;
				case 'GetNextGameInfo':
					if(isset($response['DisableRespawn']))
						$_NextGameInfos = $response;
					break;
				case 'GetServerOptions':
					if(isset($response['HideServer']))
						$_ServerOptions = $response;
					break;
				case 'GetWarmUp':
					// value handled in setWarmUpState() callback (for core GetWarmUp calls) !!!
					//if($_debug>8) console("GetWarmUp:: {$response}");
					break;
				case 'CheckEndMatchCondition':
					$_EndMatchCondition = $response;
					break;
				case 'GetNetworkStats':
					if(is_array($response)){
						loginToString($response['PlayerNetInfos'],1);
						$_playernetstatTime = $_currentTime;
						updateNetworkStats($response);
					}
					break;
				case 'GetCurrentChallengeInfo':
					// store info only at script start, else rely on callbacks infos
					if(isset($response['FileName']) && !isset($_ChallengeInfo['FileName'])){
						//debugPrint('multicallAutoStoreInfos - GetCurrentChallengeInfo - FileName ok - response',$response);
						$_ChallengeInfo = $response;
						if(!isset($_ChallengeInfo['NbCheckpoints'])){
							$_ChallengeInfo['NbCheckpoints'] = -1;
							$_multidest_logins = false; // does not support comma list of logins in ChatSendToLogin etc.
						}else{
							$_multidest_logins = true; // supports comma list of logins in ChatSendToLogin etc.
							// (it's a reasonable approximation: the multidest feature really came in dedicated 2009-04-08 or 2009-05-04)
						}
						if(!isset($_ChallengeInfo['NbLaps']))
							$_ChallengeInfo['NbLaps'] = -1;
						else if(isset($_ChallengeInfo['LapRace']) && !$_ChallengeInfo['LapRace'])
							$_ChallengeInfo['NbLaps'] = 0;
						if($_ChallengeInfo['NbLaps'] > 0)
							$_ChallengeInfo['LapAuthorTime'] = (int) floor($_ChallengeInfo['AuthorTime'] / $_ChallengeInfo['NbLaps']);
						else
							$_ChallengeInfo['LapAuthorTime'] = $_ChallengeInfo['AuthorTime'];
					}
					break;
				case 'GetCurrentChallengeIndex':
					//debugPrint("GetCurrentChallengeIndex",$response,true);
					if($_CurrentChallengeIndex != $response+0){
						$_CurrentChallengeIndex = $response+0;

						if($_CurrentChallengeIndex>=count($_ChallengeList)){
							addCall(true,'GetChallengeList',$_CurrentChallengeIndex+$_MaxMaps,0);
							addCall(null,'GetCurrentChallengeIndex');
						}
					}
					break;
				case 'GetNextChallengeInfo':
					if(isset($response['UId']) && 
						 (!isset($_NextChallengeInfo['UId']) || $_NextChallengeInfo['UId']!=$response['UId'])){
						//debugPrint('multicallAutoStoreInfos - GetNextChallengeInfo - response',$response);
						$_NextChallengeInfo = $response;
						if(!isset($_NextChallengeInfo['NbCheckpoints']))
							$_NextChallengeInfo['NbCheckpoints'] = -1;
						if(!isset($_ChallengeInfo['NbLaps']))
							$_NextChallengeInfo['NbLaps'] = -1;
						else if(isset($_NextChallengeInfo['LapRace']) && !$_NextChallengeInfo['LapRace'])
							$_NextChallengeInfo['NbLaps'] = 0;
					}
					break;
				case 'GetNextChallengeIndex':
					//debugPrint("GetCurrentChallengeIndex",$response,true);
					if($_NextChallengeIndex != $response+0){
						$_NextChallengeIndex = $response+0;
					}
					break;
				case 'GetChallengeList':
					if(isset($response[0]['UId'])){
						if($_ChallengeList!=$response)
							addEvent('ChallengeListChange',$response); // addEvent('ChallengeListChange')
						$_ChallengeList = $response;
					}
					break;
				case 'GetChallengeInfo':
					if(isset($response['UId']) && isset($_ChallengeList[$_NextChallengeIndex]['UId']) && 
						 $response['UId'] == $_ChallengeList[$_NextChallengeIndex]['UId']){
						$_NextChallengeInfo = $response;
						if(!isset($_NextChallengeInfo['NbCheckpoints']))
							$_NextChallengeInfo['NbCheckpoints'] = -1;
						if(!isset($_ChallengeInfo['NbLaps']))
							$_NextChallengeInfo['NbLaps'] = -1;
						else if(isset($_NextChallengeInfo['LapRace']) && !$_NextChallengeInfo['LapRace'])
							$_NextChallengeInfo['NbLaps'] = 0;
						//if($_debug>2) debugPrint("multicallAutoStoreInfos - GetChallengeInfo - next challenge infos ",$response);
					}
					break;
				case 'GetChatTime':
					if(isset($response['CurrentValue'])){
						$_GameInfos['ChatTime'] = $response['CurrentValue'];
						$_NextGameInfos['ChatTime'] = $response['NextValue'];
					}
					break;
				case 'LoadMatchSettings':
					// special case : CurrentChallengeIndex is always false
					$_CurrentChallengeIndex = -1;
					$_NextChallengeIndex = 0;
					break;
				case 'GetGuestList':
					if($_GuestList!=$response)
						addEvent('GuestListChange',$response); // addEvent('GuestListChange',guestlist)
					$_GuestList = $response;
					$_guest_list = array();
					foreach($_GuestList as $entry)
					$_guest_list[$entry['Login']] = $entry;
					break;
				case 'GetIgnoreList':
					if($_IgnoreList!=$response)
						addEvent('IgnoreListChange',$response); // addEvent('IgnoreListChange',ignorelist)
					$_IgnoreList = $response;
					$_ignore_list = array();
					foreach($_IgnoreList as $entry){
						$_ignore_list[$entry['Login']] = $entry;
					}
					break;
				case 'GetBanList':
					if($_BanList!=$response)
						addEvent('BanListChange',$response); // addEvent('BanListChange',banlist)
					$_BanList = $response;
					$_ban_list = array();
					foreach($_BanList as $entry)
					$_ban_list[$entry['Login']] = $entry;
					break;
				case 'GetBlackList':
					if($_BlackList!=$response)
						addEvent('BlackListChange',$response); // addEvent('BlackListChange',blacklist)
					$_BlackList = $response;
					$_black_list = array();					
					foreach($_BlackList as $entry)
					$_black_list[$entry['Login']] = $entry;
					break;
				case 'GetRoundCustomPoints':
					if(is_array($response) && $response!=$_RoundCustomPoints){
						//console("multicallAutoStoreInfos - _RoundCustomPoints: ".implode(',',$_RoundCustomPoints)." -> ".implode(',',$response));
						$_RoundCustomPoints = $response;
						addEvent('RoundCustomPointsChange',$_RoundCustomPoints); // addEvent('RoundCustomPointsChange',custompoints)
					}
					break;
				case 'GetServerCoppers':
					$_ServerCoppers = $response;
					break;
				case 'GetCallVoteRatios':
					$_CallVoteRatios = $response;
					break;
				case 'GetForcedMods':
					$_ForcedMods = $response;
					break;
				case 'GetForcedMusic':
					$_ForcedMusic = $response;
					break;
				case 'GetServerPackMask':
					$_ServerPackMask = $response;
					break;
				case 'GetValidationReplay':
					break;
				case 'GetLadderServerLimits':
					$_LadderServerLimits = $response;
					break;
				case 'ChatEnableManualRouting':
					var_dump($response);
					break;
			}
		}
	}
}


//------------------------------------------
function playerDisconnect($login,$caller=''){
	global $_debug,$_old_PlayerList,$_PlayerList,$_PlayerInfo,$_players,$_relays,$_client;
	//if($_debug>1) console("playerDisconnect:{$caller}: {$login} ...");

	if(isset($_PlayerInfo[$login]['Login']))
		unset($_PlayerInfo[$login]);

	if(isset($_relays[$login])){
		// relay server disconnected
		unset($_relays[$login]);

	}else{
		resetPlayerNetworkStats($login);

		if(isset($_players[$login]['Active']) && $_players[$login]['Active']){
			addCall(true,'GetBanList',260,0);

			if($_debug>1) console("playerDisconnect:{$caller}: {$login}");

			// direct call of PlayerDisconnect event
			$event = array('PlayerDisconnect',$login); // addEvent('PlayerDisconnect',login)
			callFuncsArray($event);
		}else{
			if($_debug>3) console("playerDisconnect:{$caller}: {$login} already disconnected");
		}

		// remove from player list
		//addCall(true,'GetPlayerList',260,0,1);
		for($i = 0; $i < sizeof($_PlayerList); $i++){
			if($login == $_PlayerList[$i]['Login']){
				// found : remove it
				unset($_PlayerList[$i]);
				$_PlayerList = array_values($_PlayerList);
				break;
			}
		}
		$_old_PlayerList = $_PlayerList;

		//if($_client->query('GetPlayerList',260,0,1)) debugPrint("playerDisconnect:{$caller}: GetPlayerList ",$_client->getResponse());
	}
}


//------------------------------------------
function playerConnect($login,$caller='',$playerinfo=null){
	global $_debug,$_SystemInfo,$_old_PlayerList,$_PlayerList,$_PlayerInfo,$_GameInfos,$_Ranking,$_players,$_relays,$_client,$_starting,$_RankingTime,$_currentTime;

	if(isset($_players[$login]['Active']) && $_players[$login]['Active']){
		if($_debug>3) console("playerConnect:{$caller}: {$login} already connected");
		return; // already connected
	}

	resetPlayerNetworkStats($login);

	if($playerinfo === null || !isset($playerinfo['Login']) || !isset($playerinfo['PlayerId'])){
		// get PlayerInfo
		if($_client->query('GetPlayerInfo',$login,1))
			$playerinfo = $_client->getResponse();
		if(!isset($playerinfo['Login']) || $playerinfo['Login'] != $login){
			// failed to get infos, bad playerinfo
			if($_debug>0) console("playerConnect:: {$login} : bad playerinfo");
			return;
		}
		$playerinfo['Login'] = ''.$playerinfo['Login'];
	}
	if($_debug>3) debugPrint("playerConnect:{$caller}: {$login} : playerinfo ",$playerinfo);

	// is it a (relay) server ?
	if(isset($playerinfo['Flags']) && (floor($playerinfo['Flags']/100000) % 10) > 0){
		// server connected : add to relays list
		if($login != $_SystemInfo['ServerLogin']){
			if(!isset($_relays[$login]))
				console("playerConnect:{$caller}: {$login} (relay server)");
			elseif(isset($_relays[$login]['Master']))
				$playerinfo['Master'] = true;
			$_relays[$login] = $playerinfo;
		}
		return;
	}

	// get PlayerDetailedInfo
	$playerdetailedinfo = null;
	if($_client->query('GetDetailedPlayerInfo',$login))
		$playerdetailedinfo = $_client->getResponse();
	if(!isset($playerdetailedinfo['Login']) || $playerdetailedinfo['Login'] != $login){
		// failed to get infos, bad playerdetailedinfo
		if($_debug>0) console("playerConnect:{$caller}: {$login} : bad playerdetailedinfo");
		return;
	}
	if($_debug>3) debugPrint("playerConnect:{$caller}: {$login} : playerdetailedinfo ",$playerdetailedinfo);

	// update/add PlayerList
	$found = false;
	for($i = 0; $i < sizeof($_PlayerList); $i++){
		if($login == $_PlayerList[$i]['Login']){
			$found = true;
			// set playerinfo
			$_PlayerList[$i] = $playerinfo;
			break;
		}
	}
	if(!$found){
		// add playerinfo
		$_PlayerList[] = $playerinfo;
	}
	$_old_PlayerList = $_PlayerList;

	// update/add in PlayerInfo
	$_PlayerInfo[$login] = $playerdetailedinfo;

	// if not Team then search player ranking
	$do_manageranking = true;
	$playerranking = array();
	if($_GameInfos['GameMode'] != TEAM){ 
		// (in Team Mode, only teams blue/red are listed in ranking)
		$found = false;
		for($i = 0; $i < sizeof($_Ranking); $i++){
			if($login == $_Ranking[$i]['Login']){
				$found = true;
				$playerranking = $_Ranking[$i];
				break;
			}
		}
		if(!$found && !$_starting && ($_currentTime - $_RankingTime) > 10000){
			// no player ranking found and ranking not get for 10s or more : get Ranking
			if($_debug>2) console("playerConnect:{$caller}: {$login} : get rankings");
			if($_client->query('GetCurrentRanking',260,0)){
				$response = $_client->getResponse();
				if(isset($response[0]['Rank'])){
					loginToString($response,1);
					$do_manageranking = true;
					$_Ranking = $response;
					$_RankingTime = $_currentTime;

					// serach in new Ranking
					$found = false;
					for($i = 0; $i < sizeof($_Ranking); $i++){
						if($login == $_Ranking[$i]['Login']){
							$found = true;
							$playerranking = $_Ranking[$i];
							break;
						}
					}

				}elseif(count($response)==0){
					$do_manageranking = true;
					$_Ranking = array();
					$_RankingTime = $_currentTime;
				}
				
			}else{
				addCall(true,'GetCurrentRanking',260,0);
			}
		}
	}
	if($_debug>3) debugPrint("playerConnect:{$caller}: {$login} : playerranking ",$playerranking);


	//if($_client->query('GetPlayerList',260,0,1)) debugPrint("playerConnect:{$caller}: GetPlayerList ",$_client->getResponse());

	// player connected !
	if($_debug>1) console("playerConnect:{$caller}: {$login}");
	// direct call of PlayerConnect event
	$event = array('PlayerConnect',$login,$playerinfo,$playerdetailedinfo,$playerranking); // addEvent('PlayerConnect',login,info,detailedinfo,playerranking)
	callFuncsArray($event);

	if($do_manageranking)
		manageRanking();
}


//------------------------------------------
// look for connect/deconnect players when no server callback
//   call Event('PlayerUpdate',login,player) events if needed
//------------------------------------------
// CALLED BY: multicallAutoStoreInfos(), GetPlayerInfo response, TrackMania.PlayerInfoChanged callback
//------------------------------------------
function managePlayer($login,$playerinfo) {
	global $_debug,$_old_PlayerList,$_PlayerList;

	//debugPrint("managePlayer - playerinfo",$playerinfo);
	$playerinfo['Login'] = ''.$playerinfo['Login'];
	$login = $playerinfo['Login'];

	// is it a (relay) server ?
	if(isset($playerinfo['Flags']) && (floor($playerinfo['Flags']/100000) % 10) > 0){
		// server connected : add to relays list
		if($login!=$_SystemInfo['ServerLogin']){
			if(!isset($_relays[$login]))
				console("managePlayer:: {$login} (relay server)");
			elseif(isset($_relays[$login]['Master']))
				$playerinfo['Master'] = true;
			$_relays[$login] = $playerinfo;
		}
		return;
	}

	$found = false;
	for($n = 0; $n < sizeof($_PlayerList); $n++){
		if($_PlayerList[$n]['Login'] == $login){
				$found = true;
				break;
		}
	}
	if($found){
		// player update ?
		if($_PlayerList[$n] != $playerinfo){
			$player = array();
			foreach($playerinfo as $key => $val){
				if(!isset($_PlayerList[$n][$key]) ||
					 $_PlayerList[$n][$key] != $playerinfo[$key])
					$player[$key] = $playerinfo[$key];
			}
			//debugPrint("managePlayer - PlayerList[$n]",$_PlayerList[$n]);
			//debugPrint("managePlayer - $login - player",$player);
			$_PlayerList[$n] = $playerinfo;
			addEvent('PlayerUpdate',$login,$player); // addEvent('PlayerUpdate',login,player)
		}
	}
	$_old_PlayerList = $_PlayerList;
}


//------------------------------------------
// 
//   call Event('PlayerUpdate',login,player) events if needed
//------------------------------------------
// CALLED BY: multicallAutoStoreInfos() on GetDetailedPlayerInfo response
//------------------------------------------
function managePlayerInfo($login,$playerinfo) {
	global $_debug,$_PlayerInfo;
	if(isset($playerinfo['Login']) && 
		 (!isset($_PlayerInfo[$login]['Login']) || ($_PlayerInfo[$login] != $playerinfo))){
		$_PlayerInfo[$login] = $playerinfo;
		addEvent('PlayerUpdate',''.$login,array('PlayerInfo'=>$playerinfo)); // addEvent('PlayerUpdate',login,player)
	}
}


//------------------------------------------
// look for connect/deconnect players when no server callback
//   call Event('PlayerUpdate',login,player) events if needed
//------------------------------------------
// CALLED BY: multicallAutoStoreInfos() on GetPlayerList response
//------------------------------------------
function managePlayerList() {
	global $_debug,$_use_cb,$_old_PlayerList,$_PlayerList,$_PlayerInfo,$_SystemInfo,$_relays,$_master,$_is_relay;

	//debugPrint("managePlayerList - old_PlayerList",$_old_PlayerList);
	//debugPrint("managePlayerList - PlayerList",$_PlayerList);

	$addplayers = array();
	$renumber = false;
	// test players status changes 
	for($i = 0; $i < sizeof($_PlayerList); $i++){
		if(isset($_PlayerList[$i]['Flags']) && 
			 (floor($_PlayerList[$i]['Flags']/100000) % 10) > 0){
			// server : remove entry
			unset($_PlayerList[$i]);
			$renumber = true;
			continue;
		}

		$found = false;
		$_PlayerList[$i]['Login'] = ''.$_PlayerList[$i]['Login'];
		$login = ''.$_PlayerList[$i]['Login'];
		for($n = 0; $n < sizeof($_old_PlayerList); $n++){
			if($login == $_old_PlayerList[$n]['Login']){
				$found = true;
				break;
			}
		}
		if($found){
			// player update ?
			if($_PlayerList[$i] != $_old_PlayerList[$n]){
				$player = array();
				foreach($_PlayerList[$i] as $key => $val){
					if(!isset($_old_PlayerList[$n][$key]) || 
						 $_PlayerList[$i][$key] != $_old_PlayerList[$n][$key])
						$player[$key] = $_PlayerList[$i][$key];
				}
				//debugPrint("managePlayerList - old_PlayerList[$n]",$_old_PlayerList[$n]);
				//debugPrint("managePlayerList - PlayerList[$i]",$_PlayerList[$i]);
				//debugPrint("managePlayerList - $login - player",$player);
				addEvent('PlayerUpdate',$login,$player); // addEvent('PlayerUpdate',login,player)
			}

		}else{
			// new player
			$addplayers[$login] = $_PlayerList[$i];
		}
	}
	if($renumber)
		$_PlayerList = array_values($_PlayerList);

	// player disconnect ?
	$remplayers = array();
	$renumber = false;
	for($i = 0; $i < sizeof($_old_PlayerList); $i++){
		$login = ''.$_old_PlayerList[$i]['Login'];
		$found = false;
		for($n = 0; $n < sizeof($_PlayerList); $n++){
			if($login == $_PlayerList[$n]['Login']){
				$found = true;
				break;
			}
		}
		if(!$found){
			if($_debug>2) console('managePlayerListChanges - PlayerDisconnect - '.$_old_PlayerList[$i]['Login']);
			$remplayers[] = $login;
		}
	}
	$_old_PlayerList = $_PlayerList;

	// connect new player
	foreach($addplayers as $login => $playerinfo){
		if(isset($_PlayerInfo[$login]))
			unset($_PlayerInfo[$login]);
		if($_debug>2) console('managePlayerList - PlayerConnect - '.$login);
		playerConnect(''.$login,'List',$playerinfo);

		if(count($addplayers) > 10){
			// mostly for startup with many players, avoid to stress too much the dedicated connection
			callMulti();
			usleep(2000);
		}
	}
	// disconnect old players
	foreach($remplayers as $login){
		if($_debug>2) console('managePlayerList - PlayerDisconnect - '.$login);
		playerDisconnect($login,'List');
	}
}


//------------------------------------------
// look for ranking changes
//   call Event('PlayerUpdate',login,player) events if needed
//------------------------------------------
// CALLED BY: multicallAutoStoreInfos() on GetCurrentRanking response
//------------------------------------------
function manageRanking($whilestarting=false) {
	global $_debug,$_old_Ranking,$_Ranking,$_GameInfos,$_team_id,$_ChallengeInfo,$_PlayerList,$_starting;
	if(!$whilestarting && $_starting)
		return;

	// complete ranking infos in team mode
	if($_GameInfos['GameMode'] == TEAM){ // Team Mode, only teams blue/red are listed in ranking
		// if PlayerId is present, replace with TeamId and set PlayerId to -1
		if(isset($_Ranking[0]['PlayerId'])){
			$_Ranking[0]['TeamId'] = $_Ranking[0]['PlayerId'];
			$_Ranking[1]['TeamId'] = $_Ranking[1]['PlayerId'];
			$_Ranking[0]['PlayerId'] = -1;
			$_Ranking[1]['PlayerId'] = -1;
		}
		// make TeamId info if not present
		if(!isset($_Ranking[0]['TeamId']) && isset($_Ranking[0]['NickName'])){
			if(isset($_team_id[$_Ranking[0]['NickName']])){
				$_Ranking[0]['TeamId'] = $_team_id[$_Ranking[0]['NickName']];
				$_Ranking[1]['TeamId'] = $_team_id[$_Ranking[1]['NickName']];
			}elseif($_Ranking[0]['Score']==0 && $_Ranking[1]['Score']==0){
				$_team_id[$_Ranking[0]['NickName']] = 0;
				$_Ranking[0]['TeamId'] = 0;
				$_team_id[$_Ranking[1]['NickName']] = 1;
				$_Ranking[1]['TeamId'] = 1;
			}
		}
		// set login to TeamId value
		if(isset($_Ranking[0]['TeamId'])){
			$_Ranking[0]['Login'] = $_Ranking[0]['TeamId'];
			$_Ranking[1]['Login'] = $_Ranking[1]['TeamId'];
		}
		else
			return; // can't set TeamId to team, so stop
	}

	// player ranking change ?
	for($i = 0; $i < sizeof($_Ranking); $i++){
		$found = false;
		$_Ranking[$i]['Login'] = ''.$_Ranking[$i]['Login'];
		$login = $_Ranking[$i]['Login'];
		for($n = 0; $n < sizeof($_old_Ranking); $n++){
			if($login == $_old_Ranking[$n]['Login']){
				$found = true;
				break;
			}
		}
		if($found){
			// player ranking has changed ?
			if($_Ranking[$i] != $_old_Ranking[$n]){
				$player = array();
				foreach($_Ranking[$i] as $key => $val){
					if(!isset($_old_Ranking[$n][$key]) || $_Ranking[$i][$key] != $_old_Ranking[$n][$key]){
						$player[$key] = $_Ranking[$i][$key];
					}
				}
				if($_debug>5) debugPrint("manageRanking - $login - player",$player);
				// direct call of PlayerDisconnect event
				$event = array('PlayerUpdate',$login,$player); // addEvent('PlayerUpdate',login,player)
				callFuncsArray($event);
				//addEvent('PlayerUpdate',$login,$player); // addEvent('PlayerUpdate',login,player)
			}
		}else{
			// first ranking for the player
			$player = $_Ranking[$i];
			//unset($player['Login']);
			//unset($player['NickName']);
			if($_debug>5) debugPrint("manageRanking - 2 - $login - player",$player);
			addEvent('PlayerUpdate',$login,$player); // addEvent('PlayerUpdate',login,player)
		}
	}
	$_old_Ranking = $_Ranking;
}


//------------------------------------------
// handle new rankings from GetCurrentRankingForLogin
//   call Event('PlayerUpdate',login,player) events if needed
//------------------------------------------
// CALLED BY: multicallAutoStoreInfos() on GetCurrentRankingForLogin response
//------------------------------------------
function manageRankingForLogins($rankings) {
	global $_debug,$_old_Ranking,$_Ranking,$_GameInfos,$_team_id,$_ChallengeInfo,$_PlayerList,$_starting;
	if($_starting)
		return;

	// complete ranking infos if not team mode
	if($_GameInfos['GameMode'] != TEAM){ // not Team Mode, players are listed
		// player ranking change ?
		for($i = 0; $i < sizeof($rankings); $i++){
			$found = false;
			$rankings[$i]['Login'] = ''.$rankings[$i]['Login'];
			$login = $rankings[$i]['Login'];
			for($n = 0; $n < sizeof($_Ranking); $n++){
				if($login == $_Ranking[$n]['Login']){
					$found = true;
					break;
				}
			}
			if($found){
				// player ranking has changed ?
				if($rankings[$i] != $_Ranking[$n]){
					$player = array();
					foreach($rankings[$i] as $key => $val){
						if(!isset($_Ranking[$n][$key]) || $rankings[$i][$key] != $_Ranking[$n][$key]){
							$player[$key] = $rankings[$i][$key];
						}
					}
					if($_debug>5) debugPrint("manageRankingForLogins - $login - player",$player);
					// direct call of PlayerDisconnect event
					$event = array('PlayerUpdate',$login,$player); // addEvent('PlayerUpdate',login,player)
					callFuncsArray($event);
					//addEvent('PlayerUpdate',$login,$player); // addEvent('PlayerUpdate',login,player)

					// update _Ranking value
					$_Ranking[$n] = $rankings[$i];
				}
			}else{
				// first ranking for the player
				$player = $rankings[$i];
				//unset($player['Login']);
				//unset($player['NickName']);
				if($_debug>5) debugPrint("manageRankingForLogins - 2 - $login - player",$player);
				addEvent('PlayerUpdate',$login,$player); // addEvent('PlayerUpdate',login,player)

				// add _Ranking value
				$_Ranking[] = $rankings[$i];
			}
		}
	}
	$_old_Ranking = $_Ranking;
}


//------------------------------------------
// look for NetworkStats infos
//------------------------------------------
// CALLED BY: multicallAutoStoreInfos() on GetNetworkStats response
//------------------------------------------
function updateNetworkStats(&$netstats){
	global $_debug,$_currentTime,$_NetworkStats,$_StatusCode,$_NetStatsColors,$_GameInfos,$_players,$_netstatRunDelay,$_netstatRunDelay1,$_netstatRunDelay2,$_netlost_limit;

	if($_StatusCode < 3 || $_StatusCode > 4)
		return;

	$netstats['Time'] = $_currentTime;
	$netstats['LostContacts'] = '';

	if(count($netstats['PlayerNetInfos']) > 0){
		// sort on LatestNetworkActivity
		usort($netstats['PlayerNetInfos'],'netInfosCompare');
		
		// set logins keys
		$pnetinfos = array();
		$sep = '';
		foreach($netstats['PlayerNetInfos'] as &$netstat){
			$login = $netstat['Login'];
			$pnetinfos[$login] = $netstat;

			if($_StatusCode >= 3){
				addEvent('PlayerNetInfos',$login,$netstat); // addEvent('PlayerNetInfos',login,netinfos)
				if(isset($_players[$login]['LatestNetworkActivity']))
					$_players[$login]['LatestNetworkActivity'] = $netstat['LatestNetworkActivity'];

				if($netstat['LatestNetworkActivity']>$_netlost_limit){
					$val = (int)($netstat['LatestNetworkActivity'] / 5000);
					if($val >= count($_NetStatsColors))
						$netstats['LostContacts'] .= end($_NetStatsColors);
					else
						$netstats['LostContacts'] .= $_NetStatsColors[$val];
					$netstats['LostContacts'] .= $sep.sprintf("%s(%d)",$netstat['Login'],(int)($netstat['LatestNetworkActivity']/1000));
					$sep = ', ';
				}
			}else{
				if(isset($_players[$login]['LatestNetworkActivity']))
					$_players[$login]['LatestNetworkActivity'] = -1;
			}
		}
		$netstats['PlayerNetInfos'] = $pnetinfos;
	}

	// send null NetInfos event to players not in NetInfos
	if($_StatusCode >= 3 && count($_players) > 0){
		foreach($_players as $login => &$pl){
			if(!is_string($login))
				$login = ''.$login;
			if(!isset($netstats['PlayerNetInfos'][$login])){
				$pl['LatestNetworkActivity'] = -1;
				addEvent('PlayerNetInfos',$login,null); // addEvent('PlayerNetInfos',login,netinfos)	
			}
		}
	}

	// reduce GetNetworkStats delay if some client seem disconnect to get more accurate infos
	if($netstats['LostContacts'] != '')
		$_netstatRunDelay = $_netstatRunDelay2;
	else
		$_netstatRunDelay = $_netstatRunDelay1;

	// copy datas to global
	$_NetworkStats = $netstats;
}


//------------------------------------------
// compare function for usort, return -1 if $a should be before $b
//------------------------------------------
function netInfosCompare(&$a,&$b){
	// if one has LastTransfer=-1 then go first
	//if($a['LastTransferTime']==-1 && $b['LastTransferTime']!=-1)
	//return -1;
	//elseif($b['LastTransferTime']==-1)
	//return 1;

	if($a['LatestNetworkActivity']<2000 && $b['LatestNetworkActivity']<2000){
		// no stall, sort on login
		return strcmp($a['Login'],$b['Login']);
	}
	// older stall first
	if($a['LatestNetworkActivity']>$b['LatestNetworkActivity'])
		return -1;
	elseif($a['LatestNetworkActivity']<$b['LatestNetworkActivity'])
		return 1;
}


//------------------------------------------
// reset player NetworkStats values
//------------------------------------------
// CALLED BY: player callbacks
//------------------------------------------
function resetPlayerNetworkStats($login){
	global $_NetworkStats,$_players,$_currentTime;
	if(isset($_players[$login]['PlayerActionTime']))
		$_players[$login]['PlayerActionTime'] = $_currentTime;
	//if(!isset($_NetworkStats['PlayerNetInfos']))
	if(isset($_players[$login]['LatestNetworkActivity']))
		$_players[$login]['LatestNetworkActivity'] = -1;
}


//------------------------------------------
// do the multicall and get responses
//------------------------------------------
// CALLED BY: manageCallbacks()
//------------------------------------------
function callMulti() {
	global $_debug,$_memdebug,$_memdebugs,$_memdebugmode,$_loadsimul,$_client,$_multicall,$_multicall_action,$_multicall_response,$_multicall_response_action,$_multicall_async,$_multicall_async_action,$_multicall_async_list,$_CallTimes,$_CallTimeTmp,$_CallNumTmp,$_CallAsyncTimes,$_CallAsyncTime,$_CallAsyncTimeTmp,$_CallAsyncNumbers,$_CallAsyncNumTmp,$_methods_textparam,$_async_delay,$_sleep_time;

	if(count($_multicall) + count($_multicall_async) <= 0)
		return false;

	// sync multicall
	$_multicall_response = array();
	$retval = false;
	$reconnect = false;

	if($_memdebug>1) $_memdebugs['callMulti-sync'] -= memory_get_usage($_memdebugmode);

	// loadsimul : add fake sync calls ?
	if($_loadsimul>0 && count($_multicall)>0){
		$text = "<?xml version='1.0' encoding='utf-8' ?><manialink><type>default</type><line><cell><text>";
		while(strlen($text)<2000)
			$text .= ' Bonjour tout le monde !';
		for($i=0; $i<$_loadsimul; $i++)
			$_multicall[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text."{$i}</text></cell></line></manialink>",0,false));
		//$_multicall[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(194)."{$i}</text></cell></line></manialink>",0,false)); // error: -503, UTF-8 sequence too short
	}

	// send sync calls
	$numcalls = count($_multicall);
	if($numcalls > 0){
		$start = 0;
		$bTime = floor(microtime(true)*1000);

		// for strange cases debuging
		//if($_debug>6) debugPrint("callMulti - _multicall",$_multicall);

		// split the multicall in several smaller if too many requests
		while($start < $numcalls){
			$size = 0;
			$num = 0;
			$end = $start;

			// compute approximate max size, limit number to avoid risks of too big response !...
			while($end < $numcalls && $num < 60){
				$size += 500; // for security it is more than real average size
				$methodname = $_multicall[$end]['methodName'];
				if(isset($_methods_textparam[$methodname])){
					$param = $_methods_textparam[$methodname];
					$size += strlen($_multicall[$end]['params'][$param]);
				}
				if($_debug>6) console("callMulti::sync[{$num}]: {$methodname}(".@implode(',',$_multicall[$end]['params']).')');
				if($size > 300000 && $end > $start) // real max size of packed xml is 512000
					break;
				$end++;
				$num++;
			}

			$multicall = array_slice($_multicall,$start,$end-$start);
			$multicall_response = false;

			if($start>0 && $_debug>0) console("callMulti Sync ({$start} - {$end} / {$numcalls})");
				
			//$datas = serialize($multicall);
			//file_put_contents('fastog/BadXmlrpcCall.last.sync.phpser',$datas);

			if(!@$_client->query('system.multicall', $multicall)){
				// if error test if connection error
				if($_client->isError()){
					$errmsg = $_client->getErrorMessage();
					$errcode = $_client->getErrorCode();
					Error("callMulti SyncResponse ({$start} - {$end} / {$numcalls}) - system.multicall - {$errmsg}", $errcode);
					if($_debug>1){
						// store bad multicall array for debugging purpose
						$datas = serialize($multicall);
						$file = "fastlog/BadXmlrpcCall.".rand(100,999).".phpser";
						file_put_contents($file,$datas);
						console("callMulti:: bad multicall contents saved in: {$file}");
					}
					if($_debug>0) debugPrint("callMulti SyncResponse ({$start} - {$end} / {$numcalls}) - system.multicall - _multicall",$multicall);
					if(($errcode==-32300) || ($errcode==-32700))
						$reconnect = true;

					if($errcode==-503){
						// utf8 error : filter utf8 then resend it (sync)
						learnUtf8(); // set in learn mode if utf8 list was loaded
						filterUtf8($multicall);
						if(@$_client->query('system.multicall', $multicall)){
							console("callMulti:: filtered utf8, then resent request succeeded!");
							$multicall_response = $_client->getResponse();
							$retval = true;
						}else{
							console("callMulti:: tried to filter utf8 then resend, but failed !");
						}
					}
				}
			}else{
				$multicall_response = $_client->getResponse();
				$retval = true;
			}
			if($multicall_response===false && $end < $numcalls){
				// query failed and other part is remaining : fill response with needed number of fake responses
				$multicall_response = array_fill(0,$end-$start,false);
			}
			$_multicall_response = array_merge($_multicall_response,$multicall_response);

			$start = $end;
		}
		$_multicall_response['multicall'] = $_multicall;
		$_multicall_response_action = $_multicall_action;

		//debugPrint("callMulti:: Sync response: ",$_multicall_response);

		// used for some stats on dedicated query/reply delays
		$dTime = floor(microtime(true)*1000) - $bTime;
		if($_debug>0 && $dTime > $_sleep_time/2)console("(callMulti Sync: {$numcalls} methods, {$dTime}ms)");
		$_CallTimeTmp += $dTime;
		$_CallNumTmp += $numcalls;
		$_CallTimes[] = $dTime / $numcalls;
		while(count($_CallTimes)>100)
			array_shift($_CallTimes);
	}
	$_multicall = array();
	$_multicall_action = array();

	if($_memdebug>1) $_memdebugs['callMulti-sync'] += memory_get_usage($_memdebugmode);
	//debugPrint("callMulti:: Sync: ",$_multicall_response);

	if($reconnect){
		reconnectTM();
		$retval = false;
	}

	// auto store infos
	multicallAutoStoreInfos();

	// call addCall() callbacks
	callMulticallCBFuncs();







	// async multicall
	if($_memdebug>1) $_memdebugs['callMulti-async'] -= memory_get_usage($_memdebugmode);
	$retval = false;
	$reconnect = false;

	// loadsimul : add fake async calls
	if($_loadsimul>0 && count($_multicall_async)>0){
		$text = "<?xml version='1.0' encoding='utf-8' ?><manialink><type>default</type><line><cell><text>.\n\n";
		while(strlen($text)<2000)
			$text .= '$aafBonsoir tout le monde ! ';
		for($i=0; $i<$_loadsimul*10; $i++)
			$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text."{$i}</text></cell></line></manialink>",0,false));
		//$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(194)."{$i}</text></cell></line></manialink>",0,false)); // error: -503, UTF-8 sequence too short
		//$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(192).'bibi',0,false)); // error: -503, UTF-8 sequence too short
		//$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(131).'bibi',0,false)); // error: -503, Invalid UTF-8 initial byte
		//$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(245).chr(131).chr(131).chr(131).'bibi',0,false)); // error: -503, UCS-4 characters not supported
		//$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(240).chr(131).chr(131).chr(131).'bibi',0,false)); // error: -503, UCS-4 characters not supported
		//$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(224).chr(131).'bibi',0,false)); // error: -503, UTF-8 sequence too short
		//$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(224).chr(131).chr(131).'bibi',0,false)); // error: -503, Overlong UTF-8 sequence not allowed
		//$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(0xE2).chr(0x82).chr(0xAC).'bibi',0,false)); // ok
		//$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(194).'bibi',0,false)); // error: -503, UTF-8 sequence too short
		//$_multicall_async[] = array('methodName'=>'SendDisplayManialinkPage','params'=>array($text.chr(194).chr(131).'bibi',0,false)); // ok
	}

	// send async calls
	$numcalls = count($_multicall_async);
	if($numcalls > 0){
		$start = 0;
		$bTime = floor(microtime(true)*1000);
		
		// for strange cases debuging
		//if($_debug>6) debugPrint("callMulti - _multicall_async",$_multicall_async);
		
		// split the multicall in several smaller if too many requests
		while($start < $numcalls){
			$size = 0;
			$num = 0;
			$end = $start;

			// compute approximate max size
			while($end < $numcalls && $num < 200){
				$size += 500; // for security it is more than real average size
				$methodname = $_multicall_async[$end]['methodName'];
				if(isset($_methods_textparam[$methodname])){
					$param = $_methods_textparam[$methodname];
					$size += strlen($_multicall_async[$end]['params'][$param]);
				}
				if($_debug>6) console("callMulti::async[{$num}]: {$methodname}(".@implode(',',$_multicall[$end]['params']).')');
				if($size > 300000 && $end > $start) // real max size of packed xml is 512000
					break;
				$end++;
				$num++;
			}
			$multicall = array_slice($_multicall_async,$start,$end-$start);
			$action = array_slice($_multicall_async_action,$start,$end-$start);
			
			if($start>0 && $_debug>0) console("callMulti Async ({$start} - {$end} / {$numcalls})");

			//$datas = serialize($multicall);
			//file_put_contents('BadXmlrpcCall.last.async.phpser',$datas);
			
			$reqhandles = @$_client->queryAsync('system.multicall', $multicall);
			if($reqhandles===false){
				// if error test if connection error
				if($_client->isError()){
					Error("callMulti Async ({$start} - {$end} / {$numcalls}) - system.multicall - ".$_client->getErrorMessage(), $_client->getErrorCode());
					if($_debug>0) debugPrint("callMulti Async ({$start} - {$end} / {$numcalls}) - system.multicall - _multicall_async",$multicall);
					if(($_client->getErrorCode()==-32300) || ($_client->getErrorCode()==-32700))
						$reconnect = true;
				}
				
			}elseif($reqhandles!==true){
				// (it is true if gbxremote don't store async responses, so in that case don't store here either)
				if(is_array($reqhandles)){
					foreach($reqhandles as $reqhandle){
						$_multicall_async_list[$reqhandle] = array('Time'=>$bTime,'Multicall'=>$multicall,'Action'=>$action); 
					}
				}else{
					$_multicall_async_list[$reqhandles] = array('Time'=>$bTime,'Multicall'=>$multicall,'Action'=>$action); 
				}
				$retval = true;
			}
			$start = $end;
		}
		
		// used for some stats on dedicated query/reply delays
		$dTime = floor(microtime(true)*1000) - $bTime;
		if($_debug>0 && $dTime > $_sleep_time/2)console("(callMulti Async: {$numcalls} methods, {$dTime}ms)");
		$_CallAsyncTimeTmp += $dTime;
		$_CallAsyncNumTmp += $numcalls;
		$_CallAsyncTimes[] = $dTime / $numcalls;
		while(count($_CallAsyncTimes) > 100)
			array_shift($_CallAsyncTimes);
	}
	$_multicall_async = array();
	$_multicall_async_action = array();

	if($_memdebug>1) $_memdebugs['callMulti-async'] += memory_get_usage($_memdebugmode);
	
	if($reconnect){
		reconnectTM();
		$retval = false;
	}
	


	// handle async responses
	$_multicall_response = array();
	if($_memdebug>1) $_memdebugs['callMulti-async-resp'] -= memory_get_usage($_memdebugmode);
	$async_responses = $_client->getAsyncResponses();

	if(is_array($async_responses) && count($async_responses)>0){
		$rTime = floor(microtime(true)*1000);
		$_async_delay = -1;
		foreach($async_responses as $reqhandle => $response){
			if(isset($_multicall_async_list[$reqhandle]['Multicall'])){
				$_multicall_response = false;
				// async response : $reqhandle
				if (is_object($response) && is_a($response, 'IXR_Error')) {
					// handle async response error
					$errmsg = $response->message;
					$errcode = $response->code;
					Error("callMulti AsyncResponse - {$errmsg}", $errcode);
					if($_debug>1){
						// store bad multicall array for debugging purpose
						$datas = serialize($_multicall_async_list[$reqhandle]['Multicall']);
						$file = "fastlog/BadXmlrpcCall.".rand(100,999).".phpser";
						file_put_contents($file,$datas);
						console("callMulti:: bad multicall contents saved in: {$file}");
					}
					if($_debug>0) debugPrint("callMulti AsyncResponse - _multicall",$_multicall_async_list[$reqhandle]['Multicall']);

					if($errcode==-503){
						// utf8 error : filter utf8 then resend it (sync)
						learnUtf8(); // set in learn mode if utf8 list was loaded
						filterUtf8($_multicall_async_list[$reqhandle]['Multicall']);
						if(@$_client->query('system.multicall', $_multicall_async_list[$reqhandle]['Multicall'])){
							console("callMulti Async:: filtered utf8, then resent request succeeded!");
							$multicall_response = $_client->getResponse();
							$retval = true;
						}else{
							console("callMulti Async:: tried to filter utf8 then resend, but failed !");
						}
					}

				}else{
					// handle async response
					$_multicall_response = $response;
				}
				if($_multicall_response!==false){
					$_multicall_response['multicall'] = $_multicall_async_list[$reqhandle]['Multicall'];
					
					//debugPrint("callMulti:: Async response: ",$_multicall_response);
					
					$_multicall_response_action = $_multicall_async_list[$reqhandle]['Action'];
					$dTime = $rTime - $_multicall_async_list[$reqhandle]['Time'];
					if($_async_delay < 0 || $_async_delay > $dTime)
						$_async_delay = $dTime;
					unset($_multicall_async_list[$reqhandle]);
					if($_debug>2 && $dTime > 50)console("(callMulti Async Response : {$dTime}ms)");
					// auto store infos
					multicallAutoStoreInfos();
					// call addCall() callbacks
					callMulticallCBFuncs();
				}
			}else{
				if($_debug>0)console("callMulti Async Response : unknown reqhandle ({$reqhandle}) !");
			}
		}

		// remove old async calls infos (more than 2 min)
		if(count($_multicall_async_list) > 0){
			$nb = 0;
			foreach($_multicall_async_list as $reqhandle => $asyncall){
				$dTime = $rTime - $asyncall['Time'];
				if($dTime > 60000){
					unset($_multicall_async_list[$reqhandle]);
					if($_debug>3)console("callMulti Async : remove old call infos ({$reqhandle},{$dTime}ms)");
				}else
					$nb++;
			}
			if($nb <= 0) // real full clean of list
				$_multicall_async_list = array();
		}
		if($_debug>3 && $_async_delay > $_sleep_time+1000) console("callMulti:: delay to get async response: {$_async_delay}");
	}
	$_multicall_response = array();
	if($_memdebug>1) $_memdebugs['callMulti-async-resp'] += memory_get_usage($_memdebugmode);

	return $retval;
}


//------------------------------------------
// call callback functions set by all addCall(), after getting response
//------------------------------------------
// CALLED BY: callMulti()
//------------------------------------------
function callMulticallCBFuncs() {
	global $_debug,$_multicall_response,$_multicall_response_action,$_response,$_response_error,$_bills,$_is_relay;

	foreach($_multicall_response_action as $index => $action){

		if(isset($_multicall_response[$index]['faultCode'])){
			$_response = NULL;
			$_response_error = $_multicall_response[$index];
			// handle error response
			$sep = '';
			$err_call_params = '';
			foreach($_multicall_response['multicall'][$index]['params'] as $param){
				$err_call_params .= $sep.print_r($param,true);
				$sep = ', ';
			}
			$methodName = $_multicall_response['multicall'][$index]['methodName'];
			$errmsg = $methodName.'('.$err_call_params.'): '.$_response_error['faultString'].' ('.$_response_error['faultCode'].')';
			if($_response_error['faultCode']==-1000){
				// handle -1000 errors
				if($_response_error['faultCode']==-1000 && $_response_error['faultString']=='Change in progress.' && 
					 ($methodName=='ChallengeRestart' || $methodName=='RestartChallenge' || $methodName=='NextChallenge')){
					// Change in progress -1000 on ChallengeRestart/NextChallenge : probably synchro so restart/next failed -> resend it 1s later
					if($_debug>1) console("callMulticallCBFuncs:: {$methodName} failed: resend it !...");
					if(isset($_multicall_response['multicall'][$index]['params'][0]) && $_multicall_response['multicall'][$index]['params'][0])
						addCallDelay(1000,true,$methodName,true);
					else
						addCallDelay(1000,true,$methodName);
					$_response_error = NULL;

				}elseif($_response_error['faultCode']==-1000 && $_response_error['faultString']=='Internal error.' && $methodName=='GetNetworkStats'){
					// Internal error. -1000 on GetNetworkStats, or Not a server. -1000 -> not a server but a game client !!!
					console('******************************************************');
					console('Error-cMCBF: GetNetworkStats returned -1000 (Internal error.)');
					console('Fast is not connected to a TM server but a TM game, exiting...');
					console('******************************************************');
					exit;

				}elseif($_response_error['faultCode']==-1000 && $_response_error['faultString']=='Not a server.'){
					// not a server !
					console('******************************************************');
					console('Error-cMCBF: '.$_multicall_response['multicall'][$index]['methodName'].' returned -1000 (Not a server.)');
					console('Fast is not connected to a TM server but a TM game, exiting...');
					console('******************************************************');
					exit;

				}elseif($_response_error['faultCode']==-1000 && $_response_error['faultString']=='No next track currently defined.' && $_is_relay){
					// GetNextChallengeInfo on relay !
					if($_debug>5) console2("Error-cMCBF[".$_response_error['faultCode'].','.$_response_error['faultString'].'] '.str_replace("\n",' ',str_replace('  ',' ',$errmsg)));
					
				}elseif($_response_error['faultCode']==-1000 && $_response_error['faultString']=='Player not managed by this server.'){
					// ToLogin to a remote client !
					if($_debug>3) console2("Error-cMCBF[".$_response_error['faultCode'].','.$_response_error['faultString'].'] '.str_replace("\n",' ',str_replace('  ',' ',$errmsg)));
					
				}elseif($_response_error['faultCode']==-1000  && $_response_error['faultString']=='Login unknown.' && //!isset($action['Login']) &&
								($methodName=='ChatSendToLogin' || $methodName=='ChatSendServerMessageToLogin' || $methodName=='SendDisplayManialinkPageToLogin' || 
								 $methodName=='SendHideManialinkPageToLogin' || $methodName=='Kick' || $methodName=='Ban' || $methodName=='SendNoticeToLogin')){
					// some ToLogin with unkown login !
					if($_debug>3) console2("Error-cMCBF[".$_response_error['faultCode'].','.$_response_error['faultString'].'] '.str_replace("\n",' ',str_replace('  ',' ',$errmsg)));

				}else{
				// other -1000 error !
					console2("Error-cMCBF[".$_response_error['faultCode'].','.$_response_error['faultString'].'] '.str_replace("\n",' ',str_replace('  ',' ',$errmsg)));
				}

			}else{
				// other error than -1000 !
				console2("Error-cMCBF[".$_response_error['faultCode'].','.$_response_error['faultString'].'] '.str_replace("\n",' ',str_replace('  ',' ',$errmsg)));
			}
		}elseif(isset($_multicall_response[$index][0])){
			$_response = $_multicall_response[$index][0];
			$_response_error = NULL;
		}else{
			$_response = NULL;
			$_response_error = NULL;
		}
		if($_response!==NULL || $_response_error!==NULL){
			// special response handle to store infos about Pay and SendBill methods
			if($_response && $_multicall_response['multicall'][$index]['methodName']=='SendBill' && is_int($_response)){
				$params = $_multicall_response['multicall'][$index]['params']; // login,cost,comment,dest
				$_bills[$_response] = array('From'=>$params[0],'To'=>$params[3],'Coppers'=>$params[1],'Comment'=>$params[2]);
			}elseif($_response && $_multicall_response['multicall'][$index]['methodName']=='Pay' && is_int($_response)){
				$params = $_multicall_response['multicall'][$index]['params']; // dest,cost,comment
				$_bills[$_response] = array('From'=>'','To'=>$params[0],'Coppers'=>$params[1],'Comment'=>$params[2]);
			}
			// method response action
			if(is_array($action)){
				if($_debug>8) debugPrint("callMulticallCBFuncs - action",$action);
				
				// call CallBack
				if(isset($action['CB'])){
					//if($_debug>3) debugPrint("callMulticallCBFuncs - ".$_multicall_response['multicall'][$index]['methodName']."() - CB - response",$_response);
					if($_debug>2) console("CB({$index})={$action['CB'][0]}(".sizeof($action['CB'][1]).")");
					if(@array_key_exists(2,$action['CB']))
						$action['CB'][1][$action['CB'][2]] = $_response;
					call_user_func_array($action['CB'][0],$action['CB'][1]);
				}

				// send to Login
				if(isset($action['Login']) && $_response_error){
					if($_debug>3) debugPrint("callMulticallCBFuncs - ".$_multicall_response['multicall'][$index]['methodName']."() - Login - response",$_response);
					addCall(null,'ChatSendToLogin', $errmsg, $action['Login']);
				}

				// single Event
				if(isset($action['Event']) && is_array($action['Event']))
					addEventArray($action['Event']);
				
				// multi Events with possible delays
				if(isset($action['Events'])){
					$delay = 0;
					foreach($action['Events'] as &$val){
						if(is_int($val))
							$delay = $val;
						elseif(is_array($val))
							addEventDelayArray($delay,$val);
					}
				}
				
				if(isset($action['Call'][1]) && @array_key_exists(0,$action['Call'])){
					// single Call
					if($_debug>9) console("callMulticallCBFuncs:: Call: {$action['Call'][0]},".print_r($action['Call'][1],true));
					addCallArray($action['Call'][0],$action['Call'][1]);

				}elseif(isset($action['CallAsync'][1]) && @array_key_exists(0,$action['CallAsync'])){
					// single Call Async
					if($_debug>9) console("callMulticallCBFuncs:: CallAsync: {$action['Call'][0]},".print_r($action['Call'][1],true));
					addCallArray($action['CallAsync'][0],$action['CallAsync'][1],true);
				}
				
				if(isset($action['Calls'])){
					// multi Calls with possible delays
					if($_debug>9) console("callMulticallCBFuncs:: Calls: ".print_r($action,true));
					$delay = 0;
					foreach($action['Calls'] as &$val){
						if(is_int($val))
							$delay = $val;
						elseif(isset($val[1]) && @array_key_exists(0,$val)){
							if($_debug>9) console("callMulticallCBFuncs:: Calls: {$delay},{$val[0]},".print_r($val[1],true));
							addCallDelayArray($delay,$val[0],$val[1]);
						}
					}

				}elseif(isset($action['CallsAsync'])){
					// multi Calls Asyncwith possible delays
					if($_debug>9) console("callMulticallCBFuncs:: CallsAsync: ".print_r($action,true));
					$delay = 0;
					foreach($action['CallsAsync'] as &$val){
						if(is_int($val))
							$delay = $val;
						elseif(isset($val[1]) && @array_key_exists(0,$val)){
							if($_debug>9) console("callMulticallCBFuncs:: CallsAsync: {$delay},{$val[0]},".print_r($val[1],true));
							addCallDelayArray($delay,$val[0],$val[1],true);
						}
					}
				}
			}
		}
	}
	$_response = NULL;
	$_response_error = NULL;
}



//----------------------------------------------------------------
//
$_auto_get_challenge_list_infos = array(array('GetChallengeList',$_MaxMaps,0),
																				array('GetCurrentChallengeIndex'),
																				array('GetNextChallengeIndex'),
																				array('GetNextChallengeInfo'),
																				array('GetGameInfos',1));


//----------------------------------------------------------------
// Related methods used to update infos after a change using a addCall() and begin
// sent when the reply come
$_methods_related_to_call = 
array(
			'system.listMethods'=>false,
			'system.methodSignature'=>false,
			'system.methodHelp'=>false,
			'system.multicall'=>false,
			'Authenticate'=>false,
			'ChangeAuthPassword'=>false,
			'EnableCallbacks'=>false,
			'GetVersion'=>false,
			'CallVote'=>false,
			'CallVoteEx'=>false,
			'InternalCallVote'=>false,
			'CancelVote'=>false,
			'ChatSendServerMessage'=>false,
			'ChatSendServerMessageToLanguage'=>false,
			'ChatSendServerMessageToId'=>false,
			'ChatSendServerMessageToLogin'=>false,
			'ChatSend'=>false,
			'ChatSendToLanguage'=>false,
			'ChatSendToLogin'=>false,
			'ChatSendToId'=>false,
			'GetChatLines'=>false,
			'SendNotice'=>false,
			'SendNoticeToId'=>false,
			'SendNoticeToLogin'=>false,
			'SendDisplayManialinkPage'=>false,
			'SendDisplayManialinkPageToId'=>false,
			'SendDisplayManialinkPageToLogin'=>false,
			'SendHideManialinkPage'=>false,
			'SendHideManialinkPageToId'=>false,
			'SendHideManialinkPageToLogin'=>false,
			'GetManialinkPageAnswers'=>false,
			'Kick'=>array(array('GetPlayerList',260,0,1)),
			'KickId'=>array(array('GetPlayerList',260,0,1)),
			'Ban'=>array(array('GetBanList',260,0)),
			'BanId'=>array(array('GetBanList',260,0)),
			'UnBan'=>array(array('GetBanList',260,0)),
			'CleanBanList'=>array(array('GetBanList',260,0)),
			'GetBanList'=>false,
			'BlackList'=>array(array('GetBlackList',260,0)),
			'BlackListId'=>array(array('GetBlackList',260,0)),
			'UnBlackList'=>array(array('GetBlackList',260,0)),
			'CleanBlackList'=>array(array('GetBlackList',260,0)),
			'GetBlackList'=>false,
			'BanAndBlackList'=>array(array('GetBanList',260,0),array('GetBlackList',260,0)),
			'AddGuest'=>array(array('GetGuestList',260,0)),
			'AddGuestId'=>array(array('GetGuestList',260,0)),
			'RemoveGuest'=>array(array('GetGuestList',260,0)),
			'RemoveGuestId'=>array(array('GetGuestList',260,0)),
			'CleanGuestList'=>array(array('GetGuestList',260,0)),
			'GetGuestList'=>false,
			'WriteFile'=>false,
			'Echo'=>false,
			'Ignore'=>array(array('GetIgnoreList',260,0)),
			'IgnoreId'=>array(array('GetIgnoreList',260,0)),
			'UnIgnore'=>array(array('GetIgnoreList',260,0)),
			'UnIgnoreId'=>array(array('GetIgnoreList',260,0)),
			'CleanIgnoreList'=>array(array('GetIgnoreList',260,0)),
			'GetIgnoreList'=>false,
			'Pay'=>array('GetServerCoppers'),
			'SendBill'=>array('GetServerCoppers'),
			'GetBillState'=>false,
			'GetServerCoppers'=>false,
			'GetSystemInfo'=>false,
			'SetServerName'=>array(array('GetServerOptions',1)),
			'GetServerName'=>false,
			'SetServerComment'=>array(array('GetServerOptions',1)),
			'GetServerComment'=>false,
			'SetServerPassword'=>array(array('GetServerOptions',1)),
			'GetServerPassword'=>false,
			'SetServerPasswordForSpectator'=>array(array('GetServerOptions',1)),
			'GetServerPasswordForSpectator'=>false,
			'SetMaxPlayers'=>array(array('GetServerOptions',1)),
			'GetMaxPlayers'=>false,
			'SetMaxSpectators'=>array(array('GetServerOptions',1)),
			'GetMaxSpectators'=>false,
			'EnableP2PUpload'=>array(array('GetServerOptions',1)),
			'IsP2PUpload'=>false,
			'EnableP2PDownload'=>array(array('GetServerOptions',1)),
			'IsP2PDownload'=>false,
			'AllowChallengeDownload'=>array(array('GetServerOptions',1)),
			'IsChallengeDownloadAllowed'=>false,
			'AutoSaveValidationReplays'=>array(array('GetServerOptions',1)),
			'IsAutoSaveValidationReplaysEnabled'=>false,
			'AutoSaveReplays'=>array(array('GetServerOptions',1)),
			'IsAutoSaveReplaysEnabled'=>false,
			'SaveCurrentReplay'=>false,
			'SetLadderMode'=>array(array('GetServerOptions',1)),
			'GetLadderMode'=>false,
			'SetVehicleNetQuality'=>array(array('GetServerOptions',1)),
			'GetVehicleNetQuality'=>false,
			'SetCallVoteTimeOut'=>array(array('GetServerOptions',1)),
			'GetCallVoteTimeOut'=>false,
			'SetCallVoteRatio'=>array(array('GetServerOptions',1)),
			'GetCallVoteRatio'=>false,
			'SetServerOptions'=>array(array('GetServerOptions',1)),
			'GetServerOptions'=>false,
			'SetHideServer'=>array(array('GetServerOptions',1)),
			'GetHideServer'=>false,
			'SetRefereeMode'=>array(array('GetServerOptions',1)),
			'GetRefereeMode'=>false,
			'SetRefereePassword'=>array(array('GetServerOptions',1)),
			'GetRefereePassword'=>false,
			'SetUseChangingValidationSeed'=>array(array('GetServerOptions',1)),
			'GetUseChangingValidationSeed'=>false,
			'LoadBlackList'=>false,
			'SaveBlackList'=>false,
			'LoadGuestList'=>false,
			'SaveGuestList'=>false,
			'GetLastConnectionErrorMessage'=>false,
			'ChallengeRestart'=>false,
			'RestartChallenge'=>false,
			'NextChallenge'=>false,
			'StopServer'=>false,
			'ForceEndRound'=>false,
			'SetGameInfos'=>array(array('GetGameInfos',1)),
			'GetCurrentGameInfo'=>false,
			'GetNextGameInfo'=>false,
			'GetGameInfos'=>false,
			'SetChatTime'=>array(array('GetGameInfos',1)),
			'GetChatTime'=>false,
			'SetFinishTimeout'=>array(array('GetGameInfos',1)),
			'GetFinishTimeout'=>false,
			'SetGameMode'=>array(array('GetGameInfos',1)),
			'GetGameMode'=>false,
			'SetTimeAttackLimit'=>array(array('GetGameInfos',1)),
			'GetTimeAttackLimit'=>false,
			'SetTimeAttackSynchStartPeriod'=>array(array('GetGameInfos',1)),
			'GetTimeAttackSynchStartPeriod'=>false,
			'SetLapsTimeLimit'=>array(array('GetGameInfos',1)),
			'GetLapsTimeLimit'=>false,
			'SetNbLaps'=>array(array('GetGameInfos',1)),
			'GetNbLaps'=>false,
			'SetRoundForcedLaps'=>array(array('GetGameInfos',1)),
			'GetRoundForcedLaps'=>false,
			'SetRoundPointsLimit'=>array(array('GetGameInfos',1)),
			'GetRoundPointsLimit'=>false,
			'SetUseNewRulesRound'=>array(array('GetGameInfos',1)),
			'GetUseNewRulesRound'=>false,
			'SetTeamPointsLimit'=>array(array('GetGameInfos',1)),
			'GetTeamPointsLimit'=>false,
			'SetMaxPointsTeam'=>array(array('GetGameInfos',1)),
			'GetMaxPointsTeam'=>false,
			'SetUseNewRulesTeam'=>array(array('GetGameInfos',1)),
			'GetUseNewRulesTeam'=>false,
			'SetCupPointsLimit'=>array(array('GetGameInfos',1)),
			'GetCupPointsLimit'=>false,
			'SetCupRoundsPerChallenge'=>array(array('GetGameInfos',1)),
			'GetCupRoundsPerChallenge'=>false,
			'SetCupNbWinners'=>array(array('GetGameInfos',1)),
			'GetCupNbWinners'=>false,
			'SetCupWarmUpDuration'=>array(array('GetGameInfos',1)),
			'GetCupWarmUpDuration'=>false,
			'SetAllWarmUpDuration'=>array(array('GetGameInfos',1)),
			'GetAllWarmUpDuration'=>false,
			'SetDisableRespawn'=>array(array('GetGameInfos',1)),
			'GetDisableRespawn'=>false,
			'SetForceShowAllOpponents'=>array(array('GetGameInfos',1)),
			'GetForceShowAllOpponents'=>false,
			'SetRoundCustomPoints'=>array('GetRoundCustomPoints'),
			'GetRoundCustomPoints'=>false,
			'GetCurrentChallengeIndex'=>false,
			'GetCurrentChallengeInfo'=>false,
			'GetNextChallengeIndex'=>false,
			'GetNextChallengeInfo'=>false,
			'GetChallengeInfo'=>false,
			'GetChallengeList'=>false,
			'SetNextChallengeIndex'=>array(array('GetNextChallengeIndex'),array('GetNextChallengeInfo')),
			'AddChallenge'=>$_auto_get_challenge_list_infos,
			'AddChallengeList'=>$_auto_get_challenge_list_infos,
			'RemoveChallenge'=>$_auto_get_challenge_list_infos,
			'RemoveChallengeList'=>$_auto_get_challenge_list_infos,
			'InsertChallenge'=>$_auto_get_challenge_list_infos,
			'InsertChallengeList'=>$_auto_get_challenge_list_infos,
			'ChooseNextChallenge'=>$_auto_get_challenge_list_infos,
			'ChooseNextChallengeList'=>$_auto_get_challenge_list_infos,
			'LoadMatchSettings'=>$_auto_get_challenge_list_infos,
			'AppendPlaylistFromMatchSettings'=>$_auto_get_challenge_list_infos,
			'SaveMatchSettings'=>false,
			'InsertPlaylistFromMatchSettings'=>$_auto_get_challenge_list_infos,
			'GetPlayerList'=>false,
			'GetPlayerInfo'=>false,
			'GetDetailedPlayerInfo'=>false,
			'GetCurrentRanking'=>false,
			'ForceScores'=>array('GetCurrentRanking',260,0),
			'ForcePlayerTeam'=>array(array('GetPlayerList',260,0,1),array('GetCurrentRanking',260,0)),
			'ForcePlayerTeamId'=>array(array('GetPlayerList',260,0,1),array('GetCurrentRanking',260,0)),
			'ForceSpectator'=>false, //array(array('GetPlayerList',260,0,1)), // add GetPlayerInfo in multicallAutoStoreInfos() to be faster than getting full list
			'ForceSpectatorId'=>array(array('GetPlayerList',260,0,1)), // rarely used
			'GetNetworkStats'=>false,
			'GetValidationReplay'=>false,
			'StartServerLan'=>false,
			'StartServerInternet'=>false,
			'GetStatus'=>false,
			'QuitGame'=>false,
			'GameDataDirectory'=>false,
			'GetTracksDirectory'=>false,
			'GetSkinsDirectory'=>false,

			'ForceSpectatorTarget'=>false, // will trigger PlayerInfoModified callback
			'ForceSpectatorTargetId'=>false, // will trigger PlayerInfoModified callback

			'SetWarmUp'=>false, // GetWarmUp is called at BeginRace/BeginRound, no more is needed
			'GetWarmUp'=>false,

			'SetCallVoteRatios'=>array('GetCallVoteRatios'),
			'GetCallVoteRatios'=>false,
			'GetCurrentCallVote'=>false,
			'SetForcedMods'=>array('GetForcedMods'),
			'GetForcedMods'=>false,
			'SetForcedMusic'=>array('GetForcedMusic'),
			'GetForcedMusic'=>false,
			'SetServerPackMask'=>array('GetServerPackMask'),
			'GetServerPackMask'=>false,

			'SetForcedSkins'=>false,
			'GetForcedSkins'=>false,

			'CheckEndMatchCondition'=>false,

			'ChatEnableManualRouting'=>false,
			'ChatForwardToLogin'=>false,

			'ManualFlowControlEnable'=>false,
			'ManualFlowControlProceed'=>false,
			'ManualFlowControlIsEnabled'=>false,
			'ManualFlowControlGetCurTransition'=>false,

			'TunnelSendDataToId'=>false,
			'TunnelSendDataToLogin'=>false,

			'SaveBestGhostsReplay'=>false,

			'CheckChallengeForCurrentServerParams'=>false,
			'GetMainServerPlayerInfo'=>false,
			'SetBuddyNotification'=>false,
			'GetBuddyNotification'=>false,
			'IsRelayServer'=>false,
			'GetLadderServerLimits'=>false,
			);
																	
//----------------------------------------------------------------
//   'Synchro -> Play' (BeforePlay) : before BeginRound and StatusChanged 3->4, seconds after StatusChanged 2->3 or 4->3 and EndRound
//   'Play -> Synchro' (BeforeEndRound) : before StatusChanged 4->3 and EndRound, after all PlayerFinish
//   'Play -> Podium' (BeforeEndRound) : before StatusChanged 4->5 and EndRound and EndRace, 
//   'Podium -> Synchro' (EndPodium) : before StatusChanged 5->2 and BeginRace, seconds after EndRace
$_transition_events = array(
	'Play -> Synchro' => 'BeforeEndRound',
	'Play -> Podium' => 'BeforeEndRound',
	'Synchro -> Play' => 'BeforePlay',
	'Podium -> Synchro' => 'EndPodium'
);


?>
