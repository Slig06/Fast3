<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      13.04.2023
// Author:    Gilles Masson
// 
// These are public functions usable in plugins
//
////////////////////////////////////////////////////////////////


//------------------------------------------
// Send a message from login, usually used in case of ChatEnableManualRouting to show players chat
// notes: 
// - now the chat plugin will send player chat if ChatEnableManualRouting to be on.
//   that means that plugins which send chats themselves should call dropEvent() to stop event propagation
// - addCall(null,'ChatEnableManualRouting',true) will set $_chatmanualrouting to true,
//   which can be tested to know if the chats have to be sent or not.
// - never set ChatEnableManualRouting another way than using addCall(), else $_chatmanualrouting
//   won't get the right value !
// - be carefull if you set ChatEnableManualRouting,false that perhaps another plugin still need
//   it to true !...
// - if manualrouting is on, a xxxPlayerChat plugin function can just dropEvent() to drop a message,
//   or modify the message which will be visible by changing the $_callFuncsArgs[2] value
//------------------------------------------
function sendPlayerChat($login,$msg){
	global $_players;
	if(isset($_players[$login]['NickName'])){
		addCall(null,'ChatSendServerMessage',authorChat($login,$_players[$login]['NickName']).$msg);

		// ChatForwardToLogin don't allow to change the line header.
		//addCall(null,'ChatForwardToLogin',$msg,$login,'');
	}
}


//------------------------------------------
// Player name in chat, ie "[nickname] "
// Will use locale 'author_chat' or 'author_chat.relay' depending of the player
// being a relayed player or not (use .relay version if is spec on the relay server)
//------------------------------------------
function authorChat($login,$author=''){
	global $_is_relay,$_players;
	if($author == '')
		$autor = isset($_players[$login]['NickName']) ? $_players[$login]['NickName'] : $login;
	if($_is_relay && (!isset($_players[$login]['Relayed']) || !$_players[$login]['Relayed'])){
		return localeText(null,'author_chat.relay',$author);
	}else{
		return localeText(null,'author_chat',$author); //.localeText(null,'server_message');
	}
}

//------------------------------------------
// Player name in /me chat, ie ">> nickname "
// Will use locale 'me_chat' or 'me_chat.relay' depending of the player
// being a relayed player or not (use .relay version if is spec on the relay server)
//------------------------------------------
function authorMeChat($login,$author=''){
	global $_is_relay,$_players;
	if($author == '')
		$autor = isset($_players[$login]['NickName']) ? $_players[$login]['NickName'] : $login;
	if($_is_relay && (!isset($_players[$login]['Relayed']) || !$_players[$login]['Relayed'])){
		return localeText(null,'me_chat.relay',$author);
	}else{
		return localeText(null,'me_chat',$author); //.localeText(null,'server_message');
	}
}





// Fast teams infos, local teams for use by special script modes (nothing to do with the game Team mode)
// see $_fteams_rules and $_fteams details in plugin.01.players.php
//--------------------------------------------------------------
// change team name
//--------------------------------------------------------------
function fteamsSetName($fteamid,$name){
	global $_debug,$_fteams,$_fteams_changes;
	if(isset($_fteams[$fteamid]['Name'])){
		$name = tm_substr(str_replace(',','',$name),0,22);
		if($name != $_fteams[$fteamid]['Name']){
			$_fteams[$fteamid]['Name'] = $name;
			$_fteams[$fteamid]['NameDraw'] = htmlspecialchars($_fteams[$fteamid]['Name'],ENT_QUOTES,'UTF-8');
			$_fteams[$fteamid]['Changed'] = true;
			$_fteams_changes = true;
			//console("fteamsSetName:: $fteamid,$name");
		}
	}
}

//--------------------------------------------------------------
// change team name
//--------------------------------------------------------------
function fteamsSetExtId($fteamid,$extid){
	global $_debug,$_fteams,$_fteams_changes;
	if(isset($_fteams[$fteamid]['ExtId'])){
		if($extid != $_fteams[$fteamid]['ExtId']){
			$_fteams[$fteamid]['ExtId'] = $extid;
			$_fteams[$fteamid]['Changed'] = true;
			$_fteams_changes = true;
		}
	}
}

//--------------------------------------------------------------
// set team lock (player level)
//--------------------------------------------------------------
function fteamsSetLock($fteamid,$lock){
	global $_debug,$_fteams,$_fteams_changes;

	if(isset($_fteams[$fteamid]['Lock'])){
		if($lock && !$_fteams[$fteamid]['Lock']){
			$_fteams[$fteamid]['Lock'] = true;
			$_fteams[$fteamid]['Changed'] = true;
			$_fteams_changes = true;
			//console("fteamsSetLock:: lock $fteamid");

		}elseif(!$lock && $_fteams[$fteamid]['Lock']){
			$_fteams[$fteamid]['Lock'] = false;
			$_fteams[$fteamid]['Changed'] = true;
			$_fteams_changes = true;
			//console("fteamsSetLock:: unlock $fteamid");
		}
	}
}

//--------------------------------------------------------------
// add player to fteam (name or id, or -extid)
// return teamid 
// (-1 if already in another team and force=false)
// (-2 if no free fteam)
// set force=true to add player if was already in another team
//--------------------------------------------------------------
function fteamsAddPlayer($ftnid,$login,$force=false){
	global $_debug,$_fteams,$_fteams_rules,$_fteams_max,$_fteams_changes,$_players;
	$extid = -1;

	if(is_int($ftnid)){
		if($ftnid >= 0 && $ftnid < $_fteams_max){
			$ftid = $ftnid;
		}else{
			$ftid = fteamsGetTeamId($ftnid);
			$extid = -$ftnid;
		}
	}else{
		$ftid = fteamsGetTeamId($ftnid);
	}
	console("fteamsAddPlayer:: $ftnid,$login => $ftid");

	// search free team
	if($ftid < 0){
		for($ftid = 0; $ftid < $_fteams_max && $_fteams[$ftid]['Active']; $ftid++)
			;
		if($ftid >= $_fteams_max){
			// no free team
			return -2;
		}
	}

	$oldfteamid = fteamsGetPlayerTeamId($login);
	if($oldfteamid >= 0){
		// already in a team
		if($oldfteamid == $ftid)
			return $ftid; // return actual team id if already in it
		if(!$force)
			return -1; // return -1 is not force
		console("fteamsAddPlayer:: RemPlayer({$oldfteamid},{$login})");
		fteamsRemPlayer($oldfteamid,$login,false); // else remove player
	}

	if(!$_fteams[$ftid]['Active']){
		// new team
		if(is_string($ftnid) && $ftnid != ''){
			$_fteams[$ftid]['Name'] = tm_substr($ftnid,0,22);
			$_fteams[$ftid]['NameDraw'] = htmlspecialchars($_fteams[$ftid]['Name'],ENT_QUOTES,'UTF-8');
		}
		$_fteams[$ftid]['Active'] = true;
		$_fteams[$ftid]['ExtId'] = $extid;
		$_fteams[$ftid]['Players'] = array();
		$_fteams[$ftid]['AllPlayers'] = array();
		$_fteams[$ftid]['Lock'] = false;
		$_fteams[$ftid]['Score'] = 0;
		$_fteams[$ftid]['Time'] = -1;
		$_fteams[$ftid]['MatchScore'] = 0;
		$_fteams[$ftid]['MatchTime'] = -1;
	}

	// add player in team
	$_fteams[$ftid]['Players'][$login] = count($_fteams[$ftid]['Players']) + 1;
	$_fteams[$ftid]['Changed'] = true;
	$_fteams_changes = true;
	//console("fteamsAddPlayer:: add $login , $ftnid");
	if(isset($_players[$login]['FTeamId']))
		$_players[$login]['FTeamId'] = $ftid;

	// change spec state of player depending on $_fteams_rules['JoinSpecMode']
	if($_players[$login]['Active']){
		if($_fteams_rules['JoinSpecMode'] == 'Free' && count($_fteams[$ftid]['Players']) <= $_fteams_rules['MaxPlaying'])
			addCall(null,'ForceSpectator',''.$login,2);
		else if($_fteams_rules['JoinSpecMode'] == 'PlayFree' || $_fteams_rules['JoinSpecMode'] == 'PlayForce')
			addCall(null,'ForceSpectator',''.$login,2);
		else if($_fteams_rules['JoinSpecMode'] == 'SpecFree' || $_fteams_rules['JoinSpecMode'] == 'SpecForce')
			addCall(null,'ForceSpectator',''.$login,1);
		
		if($_fteams_rules['JoinSpecMode'] == 'PlayFree' || $_fteams_rules['JoinSpecMode'] == 'SpecFree' || $_fteams_rules['JoinSpecMode'] == 'Free')
			addCall(null,'ForceSpectator',''.$login,0);
	}

	return $ftid;
}


//--------------------------------------------------------------
// remove player from fteam (name or id)
// give empty fteam '' to remove player whatever fteam he is in
// return true (removed) or false (not in that fteam)
//--------------------------------------------------------------
function fteamsRemPlayer($ftnid,$login,$forcespec=true){
	global $_debug,$_fteams,$_fteams_max,$_fteams_changes,$_players,$_players_old;

	if($ftnid === '')
		$ftid = fteamsGetPlayerTeamId($login);
	else
		$ftid = fteamsGetTeamId($ftnid);
	console("fteamsRemPlayer:: $ftnid,$login => $ftid");

	if($ftid >= 0){
		// force player as spectator
		if($_players[$login]['Active'] && $forcespec)
			addCall(null,'ForceSpectator',''.$login,1);

		// remove player
		if(isset($_players[$login]['FTeamId']))
			$_players[$login]['FTeamId'] = -1;
		if(isset($_players_old[$login]['FTeamId']))
			$_players_old[$login]['FTeamId'] = -1;
		if(isset($_fteams[$ftid]['Players'][$login])){
			unset($_fteams[$ftid]['Players'][$login]);
			// unlock team if was last player
			if(count($_fteams[$ftid]['Players']) <= 0)
				$_fteams[$ftid]['Lock'] = false;

			if(count($_fteams[$ftid]['Players']) <= 0){
				$_fteams[$ftid]['Active'] = false;
			}else{
				// renumber players in list
				$num = 1;
				foreach($_fteams[$ftid]['Players'] as $ftlogin => $order){
					$_fteams[$ftid]['Players'][$ftlogin] = $num++;
				}
			}
			$_fteams[$ftid]['Changed'] = true;
			$_fteams_changes = true;
			//console("fteamsRemPlayer:: rem $login, $ftid");
			return true;
		}
	}
	return false;
}


//--------------------------------------------------------------
// get fteam id
// return fteam id (or -1)
//--------------------------------------------------------------
function fteamsGetTeamId($ftnid){
	global $_debug,$_fteams,$_fteams_max;

	$ftid = -1;

	if(is_int($ftnid)){
		if($ftnid >= 0){
			if(isset($_fteams[$ftnid]['Name']))
				return $ftnid;

		}else{
			// search extid
			for($ftid = 0; $ftid < $_fteams_max; $ftid++){
				if($_fteams[$ftid]['Active'] && $_fteams[$ftid]['ExtId'] == -$ftnid ){
					return $ftid;
				}
			}
		}

	}elseif($ftnid != ''){
		for($ftid = 0; $ftid < $_fteams_max; $ftid++){
			if($_fteams[$ftid]['Active'] && $_fteams[$ftid]['Name'] == $ftnid ){
				return $ftid;
			}
		}
	}
	return -1;
}


//--------------------------------------------------------------
// get fteam id of a player
// return fteam id (or -1)
//--------------------------------------------------------------
function fteamsGetPlayerTeamId($login){
	global $_debug,$_fteams,$_fteams_max;

	for($ftid = 0; $ftid < $_fteams_max; $ftid++){
		if($_fteams[$ftid]['Active'] && isset($_fteams[$ftid]['Players'][$login]) ){
			return $ftid;
		}
	}
	return -1;
}


//--------------------------------------------------------------
// get fteam ext id of a player
// return fteam id (or -1)
//--------------------------------------------------------------
function fteamsGetPlayerTeamExtId($login){
	global $_debug,$_fteams,$_fteams_max;

	for($ftid = 0; $ftid < $_fteams_max; $ftid++){
		if($_fteams[$ftid]['Active'] && isset($_fteams[$ftid]['Players'][$login]) ){
			return $_fteams[$ftid]['ExtId'];
		}
	}
	return -1;
}


//--------------------------------------------------------------
// check if team lock should be removed (if no more players are active in team)
//--------------------------------------------------------------
function fteamsCheckLock($fteamOrLogin){
	global $_fteams,$_players,$_fteams_changes;
	$ftid = is_int($fteamOrLogin) ? $fteamOrLogin : fteamsGetPlayerTeamId($fteamOrLogin);

	if(isset($_fteams[$ftid]['Lock']) && $_fteams[$ftid]['Lock']){
		if(!$_fteams[$ftid]['Active']){
			$_fteams[$ftid]['Players'] = array();
			$_fteams[$ftid]['Lock'] = false;

		}else{
			$actives = 0;
			foreach($_fteams[$ftid]['Players'] as $login => $order){
				if(isset($_players[$login]['Active']) && $_players[$login]['Active'] && $_players[$login]['FTeamId'] == $ftid)
					$actives++;
			}
			if($actives <= 0){
				$_fteams[$ftid]['Lock'] = false;
				$_fteams[$ftid]['Changed'] = true;
				$_fteams_changes = true;
				//console("fteamsCheckLock:: no active -> unlock");
			}
		}
	}
}


//--------------------------------------------------------------
//--------------------------------------------------------------
function fteamsClearAllTeams(){
	global $_fteams,$_players,$_players_old,$_fteams_changes,$_fteams_rules,$_fteams_max;
	for($ftid = 0; $ftid < $_fteams_max; $ftid++){
		$_fteams[$ftid]['Active'] = false;
		$_fteams[$ftid]['Name'] = '';
		$_fteams[$ftid]['NameDraw'] = '';
		$_fteams[$ftid]['Players'] = array();
		$_fteams[$ftid]['AllPlayers'] = array();
		$_fteams[$ftid]['Lock'] = false;
		$_fteams[$ftid]['Changed'] = true;
		$_fteams_changes = true;
	}
	foreach($_players as $login => &$pl){
		$pl['FTeamId'] = -1;
	}
	fteamsUpdateTeampanelXml(true,'refresh');
	fteamsBuildScore(true);
	fgmodesUpdateScoretableXml(true,'refresh');
}


//--------------------------------------------------------------
// check if team have inactive players that should be removed
// event can be 'BeginRace', 'PlayerDisconnect', or 'Force'
//--------------------------------------------------------------
function fteamsCheckInactive($fteamOrLogin,$event){
	global $_fteams,$_players,$_players_old,$_fteams_changes,$_fteams_rules;
	if($event == 'PlayerDisconnect' && $_fteams_rules['AutoLeave'] != 'PlayerDisconnect')
		return;
	if($event != 'Force' && $_fteams_rules['AutoLeave'] == 'ForceOnly')
		return;

	if($fteamOrLogin === true){
		foreach($_fteams as $ftid => &$fteam){
			fteamsCheckInactive($ftid,$event);
		}
		return;
	}

	$ftid = is_int($fteamOrLogin) ? $fteamOrLogin : fteamsGetPlayerTeamId($fteamOrLogin);

	if(!$_fteams[$ftid]['Active']){
		$_fteams[$ftid]['Players'] = array();
		$_fteams[$ftid]['Lock'] = false;

	}else{
		$removed = 0;
		$actives = 0;
		foreach($_fteams[$ftid]['Players'] as $login => $order){
			if(!isset($_players[$login]['Active']) || !$_players[$login]['Active']){
				unset($_fteams[$ftid]['Players'][$login]);
				if(isset($_players[$login]['FTeamId']))
					$_players[$login]['FTeamId'] = -1;
				if(isset($_players_old[$login]['FTeamId']))
					$_players_old[$login]['FTeamId'] = -1;
				$removed++;
			}else{
				$actives++;
			}
		}
		if($actives <= 0){
			// desactivate team
			$_fteams[$ftid]['Active'] = false;
			$_fteams[$ftid]['Players'] = array();
			$_fteams[$ftid]['Lock'] = false;
			$_fteams[$ftid]['Changed'] = true;
			$_fteams_changes = true;
			console("fteamsCheckInactive:: desactivate team $ftid");

		}elseif($removed > 0){
			$_fteams_changes = true;
			$_fteams[$ftid]['Changed'] = true;
			$_fteams_changes = true;
			console("fteamsCheckInactive:: removed $removed from team $ftid");
		}
	}
}


//--------------------------------------------------------------
// clear fteams scores & times 
// if not in match or asked then clear match score & times, else update them
// used by playersBeginRace()
//--------------------------------------------------------------
function fteamsClearRanks($clearmatch=false){
	global $_match_map,$_fteams;
	foreach($_fteams as &$fteam){
		if($clearmatch || !isset($_match_map) || $_match_map < 0){
			$fteam['MatchScore'] = 0;
			$fteam['MatchCPs'] = 0;
			$fteam['MatchTime'] = 0;
		}else{
			$fteam['MatchScore'] += $fteam['Score'];
			$fteam['MatchCPs'] += $fteam['CPs'];
			$fteam['MatchTime'] += $fteam['Time'];
		}
		$fteam['Score'] = 0;
		$fteam['CPs'] = 0;
		$fteam['Time'] = 0;
		$fteam['Rank'] = 0;
	}
	fteamsClearRoundRanks(true);

	$_fteams_changes = true;
	//console("fteamsClearRanks::");
	fteamsSortRanks();
}


//--------------------------------------------------------------
// clear fteams round scores & times 
// if not in match or asked then clear match score & times, else update them
// used by playersBeginRound() and fteamsClearRanks()
//--------------------------------------------------------------
function fteamsClearRoundRanks($mapstart=false){
	global $_fteams_round;
	foreach($_fteams_round as &$fteamr){
		$fteamr['RoundScore'] = 0;
		$fteamr['RoundPoints'] = 0;
		$fteamr['RoundCPs'] = 0;
		$fteamr['RoundTime'] = 0;
		$fteamr['NbPlaying'] = 0;
		$fteamr['CP0Players'] = array();
		if($mapstart){
			$fteamr['RoundRank'] = 1000;
			$fteamr['RoundSortRank'] = 1000;
		}
	}
	$_fteams_changes = true;
	//console("fteamsClearRoundRanks::");
}


//--------------------------------------------------------------
function fteamsUpdateMapScores(){
	global $_fteams,$_fteams_round,$_fteams_mapscoresrule,$_fteams_changes;
	foreach($_fteams as $ftid => &$fteam){
		if($_fteams_mapscoresrule === 'Sum'){
			if($_fteams_round[$ftid]['RoundScore'] != 0 || 
				 $_fteams_round[$ftid]['RoundCPs'] != 0 || 
				 $_fteams_round[$ftid]['RoundTime'] != 0){
				//console("fteamsUpdateMapScores:: fteams_changes sum scores !");
				$_fteams_changes = true;
			}
			$fteam['Score'] += $_fteams_round[$ftid]['RoundScore'];
			$fteam['CPs'] += $_fteams_round[$ftid]['RoundCPs'];
			$fteam['Time'] += $_fteams_round[$ftid]['RoundTime'];

		}else{
			if($_fteams_round[$ftid]['RoundScore'] != $fteam['Score'] || 
				 $_fteams_round[$ftid]['RoundCPs'] != $fteam['CPs'] || 
				 $_fteams_round[$ftid]['RoundTime'] != $fteam['Time']){
				//console("fteamsUpdateMapScores:: fteams_changes copy scores !");
				$_fteams_changes = true;
			}
			$fteam['Score'] = $_fteams_round[$ftid]['RoundScore'];
			$fteam['CPs'] = $_fteams_round[$ftid]['RoundCPs'];
			$fteam['Time'] = $_fteams_round[$ftid]['RoundTime'];
		}
	}
	fteamsSortRanks();
}


//--------------------------------------------------------------
// sort fteams, based on scores or times depending on $_fteams_ranksrule (including match ones)
// used by fteamsClearRanks() and playersBeforeEndRound()
//--------------------------------------------------------------
function fteamsSortRanks(){
	global $_fteams,$_fteams_changes;
	
	// sort teams based on score etc.
	uasort($_fteams,'fteamsSortTeams');

	$rank = 1;
	foreach($_fteams as &$fteam){
		if($fteam['Rank'] != $rank){
			$_fteams_changes = true;
			//console("fteamsSortRanks:: fteams_changes");
		}
		$fteam['Rank'] = $rank++;
	}
}


// -----------------------------------
// compare function for uasort, return -1 if $a should be before $b
// used by fteamsSortRanks()
// -----------------------------------
function fteamsSortTeams($a, $b){
	global $_fteams_ranksrule,$_match_map;
	// active first
	if($a['Active'] && !$b['Active'])
		return -1;
	else if(!$a['Active'] && $b['Active'])
		return 1;
	else if($a['Active'] && $b['Active']){
		if($_fteams_ranksrule != 'CPTime'){
			// compare scores
			if($a['Score']+$a['MatchScore'] > $b['Score']+$b['MatchScore'])
				return -1;
			else if($a['Score']+$a['MatchScore'] < $b['Score']+$b['MatchScore'])
				return 1;

		}else{
			// compare CPs
			if($a['CPs']+$a['MatchCPs'] > $b['CPs']+$b['MatchCPs'])
				return -1;
			else if($a['CPs']+$a['MatchCPs'] < $b['CPs']+$b['MatchCPs'])
				return 1;
			// same CPs, compare times
			if($a['Time']+$a['MatchTime'] < $b['Time']+$b['MatchTime'])
				return -1;
			else if($a['Time']+$a['MatchTime'] > $b['Time']+$b['MatchTime'])
				return 1;
		}
		// same scores or cps/times, compare previous rank
		if($a['Rank'] > 0 && $b['Rank'] > 0){
			if($a['Rank'] < $b['Rank'])
				return -1;
			else if($a['Rank'] < $b['Rank'])
				return 1;
		}
	}
	// same all or not active, compare by teamid
	if($a['Tid'] < $b['Tid'])
		return -1;
	return 1;
}














//------------------------------------------
// write in match log with time
//------------------------------------------
function chatlog($text){
	global $_chatfile,$_do_chat_log;
	if($_do_chat_log){
		fwrite($_chatfile,'['.date('m/d,H:i:s')."] {$text}\n");
		fflush($_chatfile);
	}
}


//------------------------------------------
// write in console without time
//------------------------------------------
function console2($text){
	global $_logfile;
	echo "{$text}\n";
	flush();
	fwrite($_logfile,"{$text}\n");
	fflush($_logfile);
}


//------------------------------------------
// write in console with time
//------------------------------------------
function console($text){
	global $_currentTime;
	$time = (int)floor($_currentTime / 1000);
	$ctime = floor(($_currentTime - $time * 1000) / 10);
	console2(sprintf('[%s.%02d] %s',date('m/d,H:i:s',$time),$ctime,$text));
}


//------------------------------------------
// write error in console with time
//------------------------------------------
function Error($errstr, $errline){
  if($errline != -1000){
		console("Error: $errstr [$errline]");
  }
}


//------------------------------------------
// debug print
//------------------------------------------
function debugPrint($text,&$var,$vardump=false){
	$msg = "[".date("m/d,H:i:s")."]\n*** (".$text.") ***\n";
	if($vardump){
		ob_start();
		var_dump($var);
		$msg .= ob_get_contents();
		ob_end_clean();
	}
	else{
		if(is_bool($var))
			$msg .= ($var?'true':'false');
		else
			$msg .= print_r($var,true);
	}
	$msg .= "\n*******************\n";
	console2($msg);
}


//------------------------------------------
// add chat command and store help msg for it
//------------------------------------------
function registerCommand($command,$helpmsg, $admonly = false){
	global $_HelpCmd,$_HelpAdmCmd;
	if(isset($_HelpCmd[$command]) || isset($_HelpAdmCmd[$command])) {
		console('Chat command \''.$command.'\' already registered!');
		return false;
	}else{
		if($admonly)
			$_HelpAdmCmd[$command] = $helpmsg;
		else
			$_HelpCmd[$command] = $helpmsg;
	}
	return true;
}


//------------------------------------------
// ChatSendServerMessage to all admins
//------------------------------------------
function ChatSendServerMessageToAdmins($msg){
	global $_players;
	foreach($_players as $login => &$pl){
		if($pl['Active'] && verifyAdmin(''.$login))
			addCall(true,'ChatSendServerMessageToLogin', $msg, $login);
	}
}


//------------------------------------------
// get localized string using login language
//   localeText($login,$tag,...)
// set login to null if not related to a player
// tag is the searched tag in the locale file
// other params are sprintf like params 
//------------------------------------------
function localeText($login,$string){
	global $_debug,$_players,$_locale,$_locale_default;
	if(is_array($string)){
		return localeTextArray($login,$string);

	}else{
		$arg = func_get_args();
		$login = array_shift($arg);
		
		return localeTextArray($login,$arg);
	}
}


//------------------------------------------
// get localized string using login language
//   localeTextArray($login,array($tag,...))
// set login to null if not related to a player
// tag is the searched tag in the locale file
// other params in the array are sprintf like params 
//------------------------------------------
function localeTextArray($login,$string_tag_array){
	global $_debug,$_players,$_locale,$_locale_default;
	$arg = $string_tag_array;
	$string = array_shift($arg);

	if($login !== null && isset($_players[$login]['Language']))
		$lang = $_players[$login]['Language'];
	elseif($login !== null && isset($_locale[$login]))
		$lang = $login;
	else
		$lang = $_locale_default;

	//if($_debug>9)	debugPrint("localeTextArray($login,$string) Language:$lang",$arg);

	if(isset($_locale[$lang]) && array_key_exists($string,$_locale[$lang])){
		if(!is_string($_locale[$lang][$string]))
			return " !?$lang:$string?! ";
		return stripLinks(vsprintf($_locale[$lang][$string],$arg));

	}elseif($lang != 'en' && array_key_exists($string,$_locale['en'])){
		if(!is_string($_locale['en'][$string]))
			return " !?$lang:$string?! ";
		return stripLinks(vsprintf($_locale['en'][$string],$arg));
	}
	return " !!$lang:$string!! ";
}


//------------------------------------------
// get localized array for ChatSendToLanguage TM method for all used languages
//   multiLocaleText(mixed,mixed,...)
// all mixed are concatenated, each mixed can be :
// - an array($tag,...) , where tag is the searched tag in the locale file, and
//   other params in the array are sprintf like params 
// - any other value will just be concatenated
//------------------------------------------
function multiLocaleText($string_tag_array){
	global $_debug,$_players,$_locale,$_locale_default,$_used_languages;
	if(count($_used_languages)<=0){
		if(!isset($_locale[$_locale_default]))
			$_locale_default = 'en';
		$_used_languages[$_locale_default] = 0;
	}

	$multitext = array();
	$args = func_get_args();

	// add all but default language
	foreach($_used_languages as $lang => $num){
		$text = '';

		if(!isset($_locale[$lang]) || $lang == $_locale_default)
			continue;

		foreach($args as $arg){

			if(is_array($arg)){
				$string = array_shift($arg);
				
				if(array_key_exists($string,$_locale[$lang])){
					if(!is_string($_locale[$lang][$string]))
						$text .= " !?$lang:$string?! ";
					else
						$text .= stripLinks(vsprintf($_locale[$lang][$string],$arg));

				}elseif(array_key_exists($string,$_locale[$_locale_default])){
					if(!is_string($_locale[$_locale_default][$string]))
						$text .= " !?$_locale_default:$string?! ";
					else
						$text .= stripLinks(vsprintf($_locale[$_locale_default][$string],$arg));

				}else{
					$text .= " !!$lang:$string!! ";
				}

			}else{
				$text .= $arg;
			}
		}
		$multitext[] = array('Lang'=>$lang,'Text'=>$text);
	}
	
	// add default language
	$lang = $_locale_default;
	foreach($args as $arg){

		if(is_array($arg)){
			$string = array_shift($arg);
			
			if(array_key_exists($string,$_locale[$lang])){
				if(!is_string($_locale[$lang][$string]))
					$text .= " !?$lang:$string?! ";
				else
					$text .= stripLinks(vsprintf($_locale[$lang][$string],$arg));
				
			}elseif(array_key_exists($string,$_locale[$_locale_default])){
				if(!is_string($_locale[$_locale_default][$string]))
					$text .= " !?$_locale_default:$string?! ";
				else
					$text .= stripLinks(vsprintf($_locale[$_locale_default][$string],$arg));
				
			}else{
				$text .= " !!$lang:$string!! ";
			}
			
		}else{
			$text .= $arg;
		}
	}
	$multitext[] = array('Lang'=>$lang,'Text'=>$text);

	return $multitext;
}


//------------------------------------------
// Load and add a localization file
//------------------------------------------
function loadLocaleFile($file){
	global $_debug,$_locale,$_locale_default;
	if(file_exists($file)){
		$locale = xml_parse_file($file);
		if(isset($locale['fast']['locale']) && is_array($locale['fast']['locale'])){
			convertSpecialChars($locale['fast']['locale']);
			array_merge_deep($_locale,$locale['fast']['locale']);
		}
		if($_debug>4) debugPrint("loadLocaleFile - $file - locale",$locale);
		return $locale;
	}
	return false;
}


//------------------------------------------
// deep merge data2 array into data1 array
//------------------------------------------
function array_merge_deep(&$data1,&$data2,$nullnotover=false){
	if(is_array($data1) && is_array($data2)){
		foreach($data2 as $key => &$val){
			if(is_array($val) && isset($data1[$key]) && is_array($data1[$key]))
				array_merge_deep($data1[$key],$data2[$key]);
			elseif(!$nullnotover || $val !== null || !isset($data1[$key]))
				$data1[$key] = $val;
		}
	}
}


//------------------------------------------
// Make a new array with $basevalues keys/values, then update values of keys 
// which are present in $newvalues, then $newvalues2, trying also to prefix indexes
// Types of basevalues are preserved
//------------------------------------------
function array_update($basevalues,$newvalues,$newvalues2=null,$prefix=null){
	$res = $basevalues;
	foreach($res as $key => $val){
		$newval = $val;
		if(array_key_exists($key,$newvalues))
			$newval = $newvalues[$key];
		else if($prefix !== null && array_key_exists($prefix.$key,$newvalues))
			$newval = $newvalues[$prefix.$key];
		if($newvalues2 !== null){
			if(array_key_exists($key,$newvalues2))
				$newval = $newvalues2[$key];
			else if($prefix !== null && array_key_exists($prefix.$key,$newvalues2))
				$newval = $newvalues2[$prefix.$key];
		}
		settype($newval,gettype($val));
		$res[$key] = $newval;
	}
	return $res;
}


//------------------------------------------
// formats a time: 122010 -> 02:02.01 , 12010 -> 00:12.01
//   if msec=true: 122010 -> 02:02 , 12010 -> 00:12
//------------------------------------------
function MwTimeToString($MwTime,$msec=true){
	if ($MwTime == -1) {
		return "???";
	} else {
		$minutes = floor($MwTime/60000);
		$seconds = ($MwTime%60000)/1000;
		if($msec)
			return sprintf('%02d:%05.2f', $minutes, $seconds); // 1/100s
		return sprintf('%02d:%02.0f', $minutes, $seconds);
	}
}


//------------------------------------------
// formats a time: 122010 -> 2:02.01 , 12010 -> 12.01
//   if msec=true: 122010 -> 2:02 , 12010 -> 12
//------------------------------------------
function MwTimeToString2($MwTime,$msec=true){
	if (!is_numeric($MwTime)) {
		return $MwTime;
	} else if ($MwTime == -1) {
		return "???";
	} else {
		$minutes = floor($MwTime/60000);
		$seconds = ($MwTime%60000)/1000;
		if($msec){
			if($minutes > 0)
				return sprintf('%d:%05.2f', $minutes, $seconds); // 1/100s
			return sprintf('%0.2f', $seconds); // 1/100s

		}else{
			if($minutes > 0)
				return sprintf('%d:%02.0f', $minutes, $seconds);
			return sprintf('%0.0f', $seconds);
		}
	}
}


//------------------------------------------
// formats a diff time: -12010 -> -12.01 , 122010 -> +2:02.01
//------------------------------------------
function MwDiffTimeToString($DiffTime){
	$sign = "+";
	if($DiffTime < 0){
		$DiffTime = -$DiffTime;
		$sign = "-";
	}
	$minutes = floor($DiffTime/60000);
	$seconds = ($DiffTime%60000)/1000;
	if($minutes > 0)
		return sprintf('%s%d:%05.2f', $sign, $minutes, $seconds); // 1/100s
	return sprintf('%s%0.2f', $sign, $seconds); // 1/100s
}


//------------------------------------------
// Remove colors from strings : $s$fffhello -> hello
// source: http://www.tm-forum.com/viewtopic.php?p=183637#p183637
//------------------------------------------
function stripColors($str,$for_tm_drawing=true,$strip_newlines=false){
	if($strip_newlines)
		$str = strip_newlines($str);
	// (regex modifiers: /i for case insensitive, /u for utf8, /x to ignore whitespace data characters in the pattern
	//  see: http://php.net/manual/en/reference.pcre.pattern.modifiers.php )
	return str_replace("\0", ($for_tm_drawing ? '$$' : '$'),
										 preg_replace('/\\$[hlp](.*?)(?:\\[.*?\\](.*?))*(?:\\$[hlp]|$)/ixu', '$1$2',
																	preg_replace('/\\$(?:[0-9a-f][^$][^$]|[0-9a-f][^$]|[^][hlp]|(?=[][])|$)/ixu', '',
																							 str_replace('$$', "\0", tm_substr($str))
																	)
										 )
	);
}


//------------------------------------------
// remove newlines chars
//------------------------------------------
function stripNewLines($str){
	return str_replace(array("\n\n", "\r", "\n"),
										 array(' ', '', ''), $str);
}


//------------------------------------------
// Remove tm text enlarge codes from strings : $w$ohello -> hello
//------------------------------------------
function stripEnlarge($str,$codes='ow'){
  // replace $ with \001 (unused char in nicks), because ereg don't like to work with $
  // note: first strip all \001 and \002 because they'll be made to $ or badused if they weren't removed...
  // add a char at begining because ereg rule will use the char before found "$"...
  // ereg_replace is deprecated ! $str2 = str_replace("$", "\001", ereg_replace("[\001\002]","","a".$str) );
  $str2 = str_replace('$', "\001", preg_replace("/[\001\002]/","",'a'.$str) );
  // replace back double dollars with 1 or 2 dollar (keep 2 for drawing in TM)
	$str2 = str_replace("\001\001","$$", $str2);
  // remove $o and $w
  // ereg_replace is deprecated ! $str2 = ereg_replace("\001([$codes])","",$str2);
  $str2 = preg_replace("/\001([$codes])/iu",'',$str2);
  // remove first char, and back to $
	$str2 = str_replace("\001",'$', substr($str2,1) );
  return tm_substr($str2);
}


//------------------------------------------
// Remove links $h $l $p from strings
//------------------------------------------
function stripLinks($str,$force=false){
	global $_Game;
	// if not force the remove links only if game is known and not TMU/TMF (ie which don't support the links)
	if(!$force && ($_Game == 'TMU' || $_Game == 'TMF' || $_Game == 'TMUF' || $_Game == 'TMNF' || $_Game == 'tmx'))
		return $str;

	return str_replace("\0", '$$',
										 preg_replace('/\\$[hlp](.*?)(?:\\[.*?\\](.*?))*(?:\\$[hlp]|$)/iu', '$1$2',
																	str_replace('$$', "\0", tm_substr($str))
										 )
	);
}


//------------------------------------------
// Convert array strings \r\n, \r, and \n, string links $h $l if requested, in whole array
//------------------------------------------
function convertSpecialChars(&$data){
	if(is_array($data)){
		foreach($data as $key => $data2){
			if(is_array($data2))
				convertSpecialChars($data[$key]);
			else{
				$data[$key] = str_replace('\r\n', "\n", $data[$key]);
				$data[$key] = str_replace('\r', "\n", $data[$key]);
				$data[$key] = str_replace('\n', "\n", $data[$key]);
				if(function_exists('stripLinks'))
					$data[$key] = stripLinks($data[$key]);
			}
		}
	}else{
		$data = str_replace('\r\n', "\n", $data);
		$data = str_replace('\r', "\n", $data);
		$data = str_replace('\n', "\n", $data);
		if(function_exists('stripLinks'))
			$data[$key] = stripLinks($data[$key]);
	}
}


function asciiString($str){
	$res = '';
	$s = strlen($str);
	for($pos=0;$pos<$s;$pos++){
		$ch = $str[$pos];
		$co = ord($ch);
		if($co>=32 && $co < 128 && $ch != '<' && $ch != '>' && $ch != '&'){
			$res .= $ch;
		}
	}
	return $res;
}


//------------------------------------------
// strip $h and $l in whole array
//------------------------------------------
function stripLinksFromArray(&$data){
	if(is_array($data)){
		foreach($data as $key => $data2){
			if(is_array($data2))
				stripLinksFromArray($data[$key]);
			else{
				$data[$key] = stripLinks($data[$key]);
			}
		}
	}else{
		$data[$key] = stripLinks($data[$key]);
	}
}


//------------------------------------------
// Test if a login is a LAN one, ie finishing with /xxx.xxx.xxx.xxx:xxx
// (or _xxx.xxx.xxx.xxx_xxx for 2009-04-08 dedicated)
//------------------------------------------
function is_LAN_login($login){
	$num = '(25[0-5]|2[0-4]\d|[01]?\d\d|\d)';
	if(preg_match("/(\/{$num}\\.{$num}\\.{$num}\\.{$num}:\d+)$/", $login) > 0)
		return true;
	if(preg_match("/(_{$num}\\.{$num}\\.{$num}\\.{$num}_\d+)$/", $login) > 0)
		return true;
	return false;
}


//------------------------------------------
// Get base part of a LAN login, ie finishing with /xxx.xxx.xxx.xxx:xxx
// (or _xxx.xxx.xxx.xxx_xxx for 2009-04-08 dedicated)
//------------------------------------------
function get_LAN_baselogin($login){
	$num = '(25[0-5]|2[0-4]\d|[01]?\d\d|\d)';
	return preg_replace("/(\/{$num}\\.{$num}\\.{$num}\\.{$num}:\d+)$/",
											'',
											preg_replace("/(_{$num}\\.{$num}\\.{$num}\\.{$num}_\d+)$/",'',$login)
	);
}


//------------------------------------------
// Verify than 'Login' in given array is really of type string
// because there are problems with pure numeric logins seen as int
//------------------------------------------
function loginToString(&$response,$level){
	global $_debug;
	if($level == 0){
		if(isset($response['Login']) && !is_string($response['Login'])){
			if($_debug>2) debugPrint("loginToString - response",$response,true);
			$response['Login'] = ''.$response['Login'];
		}

	}elseif($level == 1){
		foreach($response as $key => &$res){
			if(isset($res['Login']) && !is_string($res['Login'])){
				if($_debug>2) debugPrint("loginToString - response[$key]",$res,true);
				$res['Login'] = ''.$res['Login'];
			}
		}

	}elseif($level > 1){
		foreach($response as &$res)
			loginToString($res,$level-1);
	}
}


//------------------------------------------
// return string with challenge and mode infos
//------------------------------------------
function stringInfos($gameinfos,$challenge,$fgamemode=''){
	global $_modelist,$_roundslimit_rule,$_teamroundslimit_rule,$_cup_autoadjust,$_FWarmUpDuration,$_teamgap_rule,$_team_playersperteam;

	$gmode = $gameinfos['GameMode'];
	if($challenge !== null && isset($challenge['Name'])){
		$msg = localeText(null,'server_message').localeText(null,'highlight').stripColors($challenge['Name']);
		$msg .= localeText(null,'track_round').'$n('.$challenge['Environnement'].'/'.$challenge['Mood'].'): ';
	}else{
		$msg = '';
	}
	$msg .= localeText(null,'highlight').($fgamemode != '' ? $fgamemode : $_modelist[$gmode]);

	if ($fgamemode == 'TeamRelay'){ // TeamRelay

	}elseif ($fgamemode == 'TeamLaps'){ // TeamLaps

	}elseif ($fgamemode == 'TeamRounds'){ // TeamRounds

	}elseif ($gmode == ROUNDS){ // rounds
		if($gameinfos['RoundsUseNewRules'])
			$msg .= localeText(null,'track_round').',$nPointsLimit='.localeText(null,'highlight').$gameinfos['RoundsPointsLimitNewRules'];
		else
			$msg .= localeText(null,'track_round').',$nPointsLimit='.localeText(null,'highlight').$gameinfos['RoundsPointsLimit'];
		if(isset($_roundslimit_rule) && $_roundslimit_rule > 0)
			$msg .= "({$_roundslimit_rule}r)";
		$msg .= localeText(null,'track_round').',$nNewRules='.localeText(null,'highlight').($gameinfos['RoundsUseNewRules']?'true':'false');
		$msg .= localeText(null,'track_round').',$nFinishTimeout='.localeText(null,'highlight').($gameinfos['FinishTimeout'] > 9 ? MwTimeToString($gameinfos['FinishTimeout'],false) : $gameinfos['FinishTimeout']);
		$msg .= localeText(null,'track_round').',$nWarmUpDuration='.localeText(null,'highlight').($gameinfos['AllWarmUpDuration'] > 0 || $_FWarmUpDuration <= 0 ? $gameinfos['AllWarmUpDuration'] : $_FWarmUpDuration.'(F)');

	}elseif($gmode == TA){ // TimeAttack
		$msg .= localeText(null,'track_round').',$nTimeLimit='.localeText(null,'highlight').MwTimeToString($gameinfos['TimeAttackLimit'],false);
		if(isset($gameinfos['TimeAttackSynchStartPeriod']))
			$msg .= localeText(null,'track_round').',$nSynchStart='.localeText(null,'highlight').MwTimeToString($gameinfos['TimeAttackSynchStartPeriod'],false);
		$msg .= localeText(null,'track_round').',$nWarmUpDuration='.localeText(null,'highlight').($gameinfos['AllWarmUpDuration'] > 0 || $_FWarmUpDuration <= 0 ? $gameinfos['AllWarmUpDuration'] : $_FWarmUpDuration.'(F)');

	}elseif($gmode == TEAM){ // Team
		if($gameinfos['TeamUseNewRules']){
			if(isset($_teamgap_rule) && $_teamgap_rule > 1)
				$msg .= localeText(null,'track_round').',$nPointsLimit='.localeText(null,'highlight').$_teamgap_rule.' (min-gap=2)';
			else
				$msg .= localeText(null,'track_round').',$nPointsLimit='.localeText(null,'highlight').$gameinfos['TeamPointsLimitNewRules'];
			if($gameinfos['TeamMaxPoints'] == 0)
				$msg .= localeText(null,'track_round').',$nPPT='.localeText(null,'highlight').$_team_playersperteam;
		}else
			$msg .= localeText(null,'track_round').',$nPointsLimit='.localeText(null,'highlight').$gameinfos['TeamPointsLimit'];
		if(isset($_teamroundslimit_rule) && $_teamroundslimit_rule > 0)
			$msg .= "({$_teamroundslimit_rule}r)";
		$msg .= localeText(null,'track_round').',$nNewRules='.localeText(null,'highlight').($gameinfos['TeamUseNewRules']?'true':'false');
		$msg .= localeText(null,'track_round').',$nMaxPoints='.localeText(null,'highlight').$gameinfos['TeamMaxPoints'];
		$msg .= localeText(null,'track_round').',$nFinishTimeout='.localeText(null,'highlight').($gameinfos['FinishTimeout'] > 9 ? MwTimeToString($gameinfos['FinishTimeout'],false) : $gameinfos['FinishTimeout']);
		$msg .= localeText(null,'track_round').',$nWarmUpDuration='.localeText(null,'highlight').($gameinfos['AllWarmUpDuration'] > 0 || $_FWarmUpDuration <= 0 ? $gameinfos['AllWarmUpDuration'] : $_FWarmUpDuration.'(F)');

	}elseif($gmode == LAPS){ // Laps
		$msg .= localeText(null,'track_round').',$nNbLaps='.localeText(null,'highlight').$gameinfos['LapsNbLaps'];
		$msg .= localeText(null,'track_round').',$nTimeLimit='.localeText(null,'highlight').MwTimeToString($gameinfos['LapsTimeLimit']);
		$msg .= localeText(null,'track_round').',$nFinishTimeout='.localeText(null,'highlight').($gameinfos['FinishTimeout'] > 9 ? MwTimeToString($gameinfos['FinishTimeout'],false) : $gameinfos['FinishTimeout']);
		$msg .= localeText(null,'track_round').',$nWarmUpDuration='.localeText(null,'highlight').($gameinfos['AllWarmUpDuration'] > 0 || $_FWarmUpDuration <= 0 ? $gameinfos['AllWarmUpDuration'] : $_FWarmUpDuration.'(F)');

	}elseif($gmode == STUNTS){ // Stunts
		$msg .= localeText(null,'track_round').',$nTimeLimit='.localeText(null,'highlight').MwTimeToString($gameinfos['TimeAttackLimit'],false); // ??? supposition

	}elseif($gmode == CUP){ // cup
		$msg .= localeText(null,'track_round').',$nPointsLimit='.localeText(null,'highlight').$gameinfos['CupPointsLimit'];
		$msg .= localeText(null,'track_round').',$nRoundsPerMap='.localeText(null,'highlight').$gameinfos['CupRoundsPerChallenge'];
		$msg .= localeText(null,'track_round').',$nNbWinners='.localeText(null,'highlight').$gameinfos['CupNbWinners'];
		$msg .= localeText(null,'track_round').',$nFinishTimeout='.localeText(null,'highlight').($gameinfos['FinishTimeout'] > 9 ? MwTimeToString($gameinfos['FinishTimeout'],false) : $gameinfos['FinishTimeout']);
		$msg .= localeText(null,'track_round').',$nWarmUpDuration='.localeText(null,'highlight').($gameinfos['CupWarmUpDuration'] > 0 || $_FWarmUpDuration <= 0 ? $gameinfos['CupWarmUpDuration'] : $_FWarmUpDuration.'(F)');
		if(isset($_cup_autoadjust) && $_cup_autoadjust > 0)
			$msg .= localeText(null,'track_round').',$n'.localeText(null,'highlight')."autoadjust({$_cup_autoadjust})";
	}
	$msg .= localeText(null,'track_round').',$nChatTime='.localeText(null,'highlight').MwTimeToString($gameinfos['ChatTime'],false);
	$msg .= localeText(null,'track_round').',$nForceShowOpponents='.localeText(null,'highlight').($gameinfos['ForceShowAllOpponents']? $gameinfos['ForceShowAllOpponents']:'false');
	if($gameinfos['DisableRespawn'])
		$msg .= localeText(null,'track_round').',$n'.localeText(null,'highlight').'norespawn';

	return $msg;
}


//------------------------------------------
function stringMapList($maxi=3){
	global $_ChallengeList,$_NextChallengeIndex,$_CurrentChallengeIndex;

	$clsize = count($_ChallengeList);
	if($maxi<13)
		$sep = "\n ";
	else
		$sep = '   ';
	if($maxi>$clsize)
		$maxi = $clsize;
	if($maxi>80)
		$maxi = 80;
	$msg = '';
	$sep .= localeText(null,'server_message').'%s>>%s '.localeText(null,'interact');
	for($index = $_NextChallengeIndex; $index < $_NextChallengeIndex+$maxi; $index++){
		$ind = $index % $clsize;
		$color = ($ind == $_CurrentChallengeIndex)? '$f00' : (($ind == $_NextChallengeIndex)? '$00f' : '');
		$msg .= sprintf($sep,$color,'+'.($index+1-$_NextChallengeIndex).'-').stripColors($_ChallengeList[$ind]['Name']).' $n$ffa, '.$_ChallengeList[$ind]['Environnement'].' $cec, '.$_ChallengeList[$ind]['Author'];
	}
	return $msg;
}


//------------------------------------------
// return challenge list for ChooseNextChallengeList
//------------------------------------------
// $direction can be 'next','prev','previous'
// $arg can be a number of maps to go to, or envir name, or partial map name, or map uid
// &$num will be modified with the number the map to go
// will return the list for ChooseNextChallengeList, or false
function buildMapList($direction,$arg,&$num){
	global $_debug,$_NextChallengeIndex,$_ChallengeList,$_envirs;

	$num = false;
	$clsize = count($_ChallengeList);
  if($clsize<1)
    return false;

	if($arg !== false && !is_numeric($arg)){
		if(isset($_envirs[strtolower($arg)]))
			$arg = $_envirs[strtolower($arg)];
		else{
			$name = trim($arg);
			$arg = 'Name';
		}
	}

	if(is_numeric($arg)){
		$num = $arg+0;
		if($direction == 'prev' || $direction == 'previous')
			$num = -$num;
		
	}else{
		if($direction == 'prev' || $direction == 'previous'){
			for($index = $_NextChallengeIndex-2+$clsize*2; $index > $_NextChallengeIndex+$clsize-2; $index--){
				$ind = $index % $clsize;
				if($arg == 'Name'){
					if($_ChallengeList[$ind]['UId'] == $name ||
						 stristr(stripColors($_ChallengeList[$ind]['Name']),$name) !== false){
						$num = $index - $_NextChallengeIndex + 1;
						break;
					}
					
				}elseif($_ChallengeList[$ind]['Environnement'] == $arg){
					$num = $index - $_NextChallengeIndex + 1;
					break;
				}
			}
			
		}else{
			for($index = $_NextChallengeIndex; $index < $_NextChallengeIndex+$clsize; $index++){
				$ind = $index % $clsize;
				if($arg == 'Name'){
					if($_ChallengeList[$ind]['UId'] == $name ||
						 stristr(stripColors($_ChallengeList[$ind]['Name']),$name) !== false){
						$num = $index - $_NextChallengeIndex + 1;
						break;
					}
					
				}elseif($_ChallengeList[$ind]['Environnement'] == $arg){
					$num = $index - $_NextChallengeIndex + 1;
					break;
				}
			}
		}
	}
		
	if($num === false){
		$arg = false;
		return false;

	}else{
		// go to any other than pure next... build list
		$start = $_NextChallengeIndex-1+$clsize+$num;
		$clist = array();
		for($index = $start; $index < $start+$clsize; $index++){
			$ind = $index % $clsize;
			if($ind < 0 || !isset($_ChallengeList[$ind]['FileName']))
				return false;
			$clist[] = $_ChallengeList[$ind]['FileName'];
		}
		return $clist;
	}
}


//------------------------------------------
// shuffle map list
//------------------------------------------
function mapsShuffle($list,$type=3){
	global $_debug,$_envir_order;
	$lists = array();
	// build lists by envir
	foreach($list as $challenge){
		$envir = $challenge['Environnement'];
		$challenge['Rand'] = rand();
		if(!isset($lists[$envir])){
			$lists[$envir]['Envir'] = $envir;
			$lists[$envir]['Maps'] = array($challenge);
			$lists[$envir]['MapsNum'] = 1;
			$lists[$envir]['MapsAdded'] = 0;
			$lists[$envir]['Ratio'] = 0;
			$lists[$envir]['Order'] = 0;
			if(isset($_envir_order[$envir]))
				$lists[$envir]['Order'] = $_envir_order[$envir];
 		}else{
			$lists[$envir]['Maps'][] = $challenge;
			$lists[$envir]['MapsNum']++;
		}
	}

	// sort envirs by reverse number of maps in list (and make numeric indexes)
	//usort($lists,'mapsShuffleSortByMapsNum');

	// sort by envirs (and make numeric indexes)
	usort($lists,'mapsShuffleSortByEnvirs');

	// get the max num of maps per envir to get at least $min_envir mix
	if($type <= 1)
		$min_envir = 1;
	else
		$min_envir = $type;
	$min_envir--;
	while(!isset($lists[$min_envir]))
		$min_envir--;
	$maxmaps = $lists[$min_envir]['MapsNum'];
	
	$msg = 'Shuffle ';
	$sep = '';
	// sort envir lists in random order
	foreach($lists as &$listenvir){
		if($listenvir['MapsNum'] > $maxmaps)
			$listenvir['MapsNum'] = $maxmaps;
		$msg .= $sep.$listenvir['MapsNum'].' '.$listenvir['Envir']; $sep = ', ';
		usort($listenvir['Maps'],'mapsShuffleSortByRand');
	}

	// add maps (filename) in list
	$maps = array();
	if($type == 0){
		// one map of each envir while remaing ones
		$added = true;
		while($added){
			$added = false;
			foreach($lists as &$listenvir){
				$ind = $listenvir['MapsAdded'];
				if($ind < $listenvir['MapsNum']){
					$maps[] = $listenvir['Maps'][$ind]['FileName'];
					$listenvir['MapsAdded']++;
					$added = true;
					if($_debug>4) console('Map '.$listenvir['Envir'].' ('.$listenvir['MapsAdded'].'/'.$listenvir['MapsNum'].')');
				}
			}
		}

	}else{
		$added = true;
		while($added){
			$added = false;

			// compute ratios and keep the worst one
			$envirnum = -1;
			$ratio = 2;
			foreach($lists as $envnum => &$listenvir){
				$listenvir['Ratio'] = $listenvir['MapsAdded'] / $listenvir['MapsNum'];
				if($listenvir['Ratio'] < $ratio && $listenvir['MapsAdded'] < $listenvir['MapsNum']){
					$envirnum = $envnum;
					$ratio = $listenvir['Ratio'];
				}
			}
			if($envirnum != -1){
				$ind = $lists[$envirnum]['MapsAdded'];
				$maps[] = $lists[$envirnum]['Maps'][$ind]['FileName'];
				$lists[$envirnum]['MapsAdded']++;
				$added = true;
				if($_debug>4) console('Map '.$lists[$envirnum]['Envir'].' ('.$lists[$envirnum]['Ratio'].' , '.$lists[$envirnum]['MapsAdded'].'/'.$lists[$envirnum]['MapsNum'].')');
			}
		}
	}

	$msg .= ' ('.count($maps).'/'.count($list).')';
	if($_debug>0) console($msg);

	//print_r($maps);
	return $maps;
}


// sort function for shuffle
function mapsShuffleSortByRand($a,$b){
	if($a['Rand'] <= $b['Rand'])
		return -1;
	return 1;
}


// sort function for shuffle
function mapsShuffleSortByMapsNum($a,$b){
	if($a['MapsNum'] >= $b['MapsNum'])
		return -1;
	return 1;
}


// sort function for shuffle
function mapsShuffleSortByEnvirs($a,$b){
	if($a['Order'] < $b['Order'])
		return -1;
	return 1;
}


//------------------------------------------
// quick restart with end of warmup message (using WarmUp true then false)
function mapQuickRestart($login=null,$setwuafter=false){
	global $_debug,$_StatusCode,$_GameInfos;
	if($_StatusCode < 4){
		// changing map, or synchro : wait
		if($_debug>9) console("restartQuick:: not playing, delay it...");
		addEventDelay(500, 'Function', 'mapQuickRestart',$login,$setwuafter);

	}elseif($_StatusCode > 4){
		// podium : simple restart (set warmup if asked)
		$action2 = $setwuafter ? array('Calls'=>array(3000,array(null,array('SetWarmUp',true)))) : null;
		if($_GameInfos['GameMode'] == CUP)
			addCall($action2, 'ChallengeRestart', true);
		else
			addCall($action2, 'ChallengeRestart');

	}else{
		// playing : set warmup, the unset
		$action2 = $setwuafter ? array('Calls'=>array(3200,array(null,array('SetWarmUp',true)))) : $login;
		//$action = array('Calls'=>array(200,array($action2,array('SetWarmUp',false))));
		$action = array('Call'=>array($action2,array('SetWarmUp',false)));
		addCall($action, 'SetWarmUp', true);
	}
}


//------------------------------------------
// quick restart without message or podium (using ChatTime,0)
// note: restart with ChatTime=0 will work only if the restart if asked
//       before the last EndRound and EndRace (ie before the server take
//       decision that the map is finished)
function mapQuickRestartNP($login=null,$setwuafter=false){
	global $_debug,$_StatusCode,$_GameInfos;
	if($_StatusCode < 4){
		// changing map, or synchro : wait
		if($_debug>9) console("restartQuickNP:: not playing, delay it...");
		addEventDelay(500, 'Function', 'mapQuickRestartNP',$login,$setwuafter);

	}elseif($_StatusCode > 4){
		// podium : simple restart (set warmup if asked)
		$action2 = $setwuafter ? array('Calls'=>array(3000, array(null, array('SetWarmUp',true)) )) : null;
		if($_GameInfos['GameMode'] == CUP)
			addCall($action2, 'ChallengeRestart', true);
		else
			addCall($action2, 'ChallengeRestart');

	}else{
		// playing : set ChatTime,0 then simple restart and set back ChatTime (set warmup if asked)
		$action2 = array('Calls'=>array(3000, array(null, array('SetChatTime', $_GameInfos['ChatTime'])) ));
		if($setwuafter)
			$action2['Calls'][] = array(null, array('SetWarmUp',true));
		addCall(null, 'SetChatTime', 0);
		if($_GameInfos['GameMode'] == CUP)
			addCall($action2, 'ChallengeRestart', true);
		else
			addCall($action2, 'ChallengeRestart');
	}
}


//------------------------------------------
// real restart, with wu if wanted (using a maplist rebuild then next)
function mapRealRestart($login=null,$resetcup=false){
	global $_ChallengeList,$_NextChallengeIndex,$_GameInfos,$_methods_list;
	$clsize = count($_ChallengeList);
	$num = 0;
	$start = $_NextChallengeIndex-1+$clsize+$num;
	$clist = array();
	for($index = $start; $index < $start+$clsize; $index++){
		$ind = $index % $clsize;
		$clist[] = $_ChallengeList[$ind]['FileName'];
	}
	addCall($login,'ChooseNextChallengeList',$clist);
	callMulti();
	if(isset($_methods_list['CheckEndMatchCondition']) && $_GameInfos['GameMode'] == CUP && !$resetcup){
		addCall($login,'NextChallenge',true);
		//addCallDelay(1000,$login,'NextChallenge',true);
	}else{
		addCall($login,'NextChallenge');
		//addCallDelay(1000,$login,'NextChallenge');
	}
}


//------------------------------------------
// add single query to $_multicall array
//
// usage: addCall(action,'TMServerMethod',...)
//   action is null, true, login string, or action array
//    - null -> just log errors
//    - true -> reject duplicate addCall method with same arguments
//    - login string -> login of player who will get the error message if any
//    - action array -> List of action which will be done when the response is received:
//                      'Event'=>event_array
//                      'Events'=>array(mixed delay_int and event_array)
//                      'Call'=>array(action,call_array)
//                      'CallAsync'=>array(action,call_array)
//                      'Calls'=>array(mixed delay int and array(action,call_array))
//                      'CallsAsync'=>array(mixed delay int and array(action,call_array))
//                      'CB'=>callback: array(function_name, args_array [,num of arg to replace with response])
//                      'Login'=>login string : same as action=login string
//                      'DropDuplicate'=>boolean : if true then same as action=true
//   second param is the tm server method, next args are needed parameters for the method
//------------------------------------------
function addCall() {
	$args = func_get_args();
	$action = array_shift($args);
	return addCallArray($action,$args);
}


//------------------------------------------
// same as addCall, but for async call (ie for which the response will not be waited after sending the request)
//------------------------------------------
function addCallAsync() {
	$args = func_get_args();
	$action = array_shift($args);
	return addCallArray($action,$args,true);
}


//------------------------------------------
// add single query to $_multicall array
//
// usage: addCallArray(action,addcall_array,async)
//   addcall_array is array('TMServerMethod',...)
//      first is the tm server method, next args are needed parameters for the method
//   action done when the response is received, can be :
//    - null -> just log errors
//    - true -> reject duplicate addCall method with same arguments
//    - login string -> login of player who will get the error message if any
//    - action array -> List of action which will be done when the response is received:
//                      'Event'=>event_array
//                      'Events'=>array(mixed delay_int and event_array)
//                      'Call'=>array(action,call_array)
//                      'CallAsync'=>array(action,call_array)
//                      'Calls'=>array(mixed delay int and array(action,call_array))
//                      'CallsAsync'=>array(mixed delay int and array(action,call_array))
//                      'CB'=>callback: array(function_name, args_array [,num of arg to replace with response])
//                      'Login'=>login string : same as action=login string
//                      'DropDuplicate'=>boolean : if true then same as action=true
//   async is true to make an async call (ie for which the response will not be waited after sending the request)
//------------------------------------------
function addCallArray($action,$addCall_array,$async=false) {
	global $_debug,$_cdebug,$_multicall,$_multicall_action,$_multicall_async,$_multicall_async_action,$_methods_list,$_StatusCode,$_delay_actions,$_players,$_players_checkchange,$_Quiet,$_ServerChatName,$_ServerCoppers,$_use_flowcontrol,$_players_actives,$_players_spec,$_ServerOptions,$_LadderServerLimits,$_control_maxplayers,$_players_maxlist,$_guest_list,$_ladderserver_guestlimit,$_async_forced_methods,$_is_relay,$_toplayer_methods,$_multidest_logins,$_chatmanualrouting;

	// debug for $_cdebug specified cases...
	if(isset($_cdebug[$addCall_array[0]]) && $_debug > $_cdebug[$addCall_array[0]]){
		console("BACKTRACE addCallArray({$action},array(".@implode(',',$addCall_array)."),{$async}): ".get_backtrace());
	}

	// special case: if Status is 'Running - Synchronization' and next or restart it will fail, so delay it !
	if($_StatusCode <= 3 &&
		 ($addCall_array[0] == 'NextChallenge' || $addCall_array[0] == 'ChallengeRestart' || $addCall_array[0] == 'RestartChallenge')){
		if($_debug>1 && $action !== true) console($addCall_array[0].' while synchro (Status='.$_StatusCode.'), delay it...');
		addCallDelayArray(400,true,$addCall_array);
		//debugPrint("addCallArray - action=$action - ".$addCall_array[0]." - _delay_actions",$_delay_actions);
		return;
	}

	// get method name
	$methodName = array_shift($addCall_array);

	// if action is string then build action array with 'Login' entry
	if(is_string($action)){
		$action = array('Login'=>$action);

	// if action is boolean then build action array with 'DropDuplicate' entry
	}elseif(is_bool($action)){
		if($action)
			$action =  array('DropDuplicate'=>true);
		else
			$action = null;

	// if action is array and action[0] exist the action is an old style callback : transform it
	}elseif(isset($action[0]) && @is_string($action[0])){
		$action = array('CB'=>$action);
	}

	// verify if 'CB' is an existing function, else remove CB info
	if(isset($action['CB'])){
		if(isset($action['CB'][0]) && is_string($action['CB'][0])){
			if(!function_exists($action['CB'][0])){
				if($_debug>0) console2("Inexistent action callback function : ".$action['CB'][0]);
				unset($action['CB']);

			}else{
				if(!isset($action['CB'][1]))
					$action['CB'][1] = array();
			}
		}else{
			if($_debug>0) console2("No callback function specified in action['CB'] !");
			unset($action['CB']);
		}
	}

	// verify if method exist !
	if(!is_string($methodName) || !isset($_methods_list[$methodName])){
		if($_debug>1){
			console("Warning: {$methodName}: method don't exist on this TM server !\n");
			if($_debug>3 || $methodName == ''){
				$backtrace = debug_backtrace();
				debugPrint('addCallArray - method dont exist - debug_backtrace',$backtrace);
			}
		}
		if(isset($action['Login']))
			addCall(null,'ChatSendToLogin', $methodName.": method don't exist on this TM server !", $action['Login']);
		return -1;
	}

	// forbid to send some methods to inactive or relayed players !
	if(array_key_exists($methodName,$_toplayer_methods)){
		$pindex = $_toplayer_methods[$methodName];
		if($pindex > 0){
			$plogin = $addCall_array[$pindex-1];
			if($_multidest_logins){
				// if comma list then don't check login validity
				if(strpos($plogin,',') !== false)
					$plogin = true;
				else if(!isset($_players[$plogin]['Active']))
					$plogin = false;
			}else if(!isset($_players[$plogin]['Active']))
				$plogin = false;

			if($methodName == 'ChatEnableManualRouting' && $plogin === '')
				$plogin = true;

		}else{
			$plid = $addCall_array[-$pindex-1];
			$plogin = false;
			foreach($_players as &$pl){
				if($pl['PlayerId'] == $plid){
					$plogin = $pl['Login'];
					break;
				}
			}
		}
		if($plogin !== true){
			if($plogin === false || !isset($_players[$plogin]['Active']) || !isset($_players[$plogin]['Quiet']) || !isset($_players[$plogin]['Relayed'])){
				if($_debug>4) console("Warning: {$methodName}({$plogin}) forbidden for an unknown player !");
				return -1;
			}elseif(!$_players[$plogin]['Active']){
				if($_debug>4) console("Warning: {$methodName}({$plogin}) forbidden for an inactive player !");
				return -1;
			}elseif($_players[$plogin]['Quiet']){
				if($_debug>4) console("Warning: {$methodName}({$plogin}) forbidden for a quiet (going to disconnect) player !");
				return -1;
			}elseif($_is_relay && $_players[$plogin]['Relayed']){
				if($_debug>2) console("Warning: {$methodName}({$plogin}) forbidden for a relayed player !");
				return -1;
			}
		}
	}

	// special cases
	if($methodName == 'ManualFlowControlProceed'){
		console("Warning: {$methodName} have to use a direct call, don't use addCall for it !\n");
		return;

	}elseif($methodName == 'ForceSpectator' || $methodName == 'ForceSpectatorId'){
		// get login
		$login = $addCall_array[0];
		if($methodName == 'ForceSpectatorId'){
			foreach($_players as &$pl){
				if($pl['PlayerId'] == $login){
					$login = $pl['Login'];
					break;
				}
			}
		}
		// if not exist then drop method
		if(!isset($_players[$login]['Active'])){
			console("Warning: {$methodName}({$login},{$addCall_array[1]}): player not in list !");
			return -1;
		}
		// if inactive then just drop method
		if(!$_players[$login]['Active']){
			console("Warning: {$methodName}({$login},{$addCall_array[1]}): player not in active list !");
			return -1;
		}

		/* // debug ForceSpectator,*,1
		if($addCall_array[1] == 1){
			$backtrace = debug_backtrace();
			debugPrint('addCallArray - ForceSpectator - debug_backtrace',$backtrace);
		}
		*/

		// control max players (see also playersControlMaxPlayers() )
		if($_control_maxplayers && $_ServerOptions['Password'] == ''){
			if($addCall_array[1] == 2 && !isset($_players_maxlist[$login])){
				// put player ?

				if(!isset($_guest_list[$login]) || 
					 (isset($_LadderServerLimits['LadderServerLimitMax']) && $_LadderServerLimits['LadderServerLimitMax'] >= $_ladderserver_guestlimit)	){
					// player not in guest list, or ladderserver>=60K (on which guests are forbidden) : test number

					$cur = count($_players_maxlist);
					$max = $_ServerOptions['CurrentMaxPlayers'];
					if($cur >= $max){
						// already max players, refuse to set one more as player !
						console2("addCallArray::{$methodName} {$addCall_array[0]} refused: too many players ({$cur}/{$max}) !");
						if(isset($action['Login']))
							addCall(null,'ChatSendToLogin', "{$methodName} refused : too many players ({$cur}/{$max}) !", $action['Login']);
						return -1;

					}else{
						// put player: add in maxlist
						$_players_maxlist[$login] = $login;
					}
				}
				
			}elseif($addCall_array[1] == 1 && isset($_players_maxlist[$login])){
				// put spec: remove from maxlist
				unset($_players_maxlist[$login]);
			}
		}
		// store info if spec state was forced or not
		if($addCall_array[1]>0){
			$_players_checkchange |= !$_players[$login]['ForcedOld'];
			$_players[$login]['Forced'] = true;
		}else{
			$_players_checkchange |= $_players[$login]['ForcedOld'];
			$_players[$login]['Forced'] = false;
			$_players[$login]['ForcedByHimself'] = false;
		}

	}elseif($methodName == 'Ban' || $methodName == 'Kick' || $methodName == 'BlackList' || $methodName == 'BanAndBlackList'){
		$plogin = $addCall_array[0];
		if(isset($_players[$plogin]['Active']) && $_players[$plogin]['Active'] && !$_players[$plogin]['Quiet'])
			$_players[$plogin]['Quiet'] = true;

	}elseif($methodName == 'BanId' || $methodName == 'KickId' || $methodName == 'BlackListId'){
		$plid = $addCall_array[0];
		$plogin = false;
		foreach($_players as &$pl){
			if($pl['PlayerId'] == $plid){
				$plogin = $pl['Login'];
				break;
			}
		}
		if($plogin !== false && $_players[$plogin]['Active'] && !$_players[$plogin]['Quiet'])
			$_players[$plogin]['Quiet'] = true;

	}elseif($methodName == 'Pay' || $methodName == 'SendBill'){
		if($_ServerCoppers < 2){
			if(isset($action['Login']))
				if($_debug>0) console("$methodName: not enougth coppers on server account for transaction ($_ServerCoppers)!");
				addCall(null,'ChatSendToLogin', "Sorry, the server don't own enough coppers to do transactions, send coppers to the server login using the game internal message system !", $action['Login']);
		}

	}elseif($methodName == 'ChatEnableManualRouting'){
		$_chatmanualrouting = $addCall_array[0];
	}

	// ChatSendXXX special stuff...
	if(strncmp($methodName,'ChatSend',8) == 0){
		// if $_ServerChatName is set then replace ChatSend by ChatSendServerMessage with $_ServerChatName as server name
		// do it also if $_chatmanualrouting, because in such mode the ChatSendXxx() show nothing !!!
		if($_ServerChatName != '' || $_chatmanualrouting){
			$srvchatname = ($_ServerChatName != '') ? $_ServerChatName : $_ServerOptions['Name'];
			if($methodName == 'ChatSend'){
				$methodName = 'ChatSendServerMessage';
				$addCall_array[0] = '$z['.$srvchatname.'$z] '.$addCall_array[0];
			}elseif($methodName == 'ChatSendToLogin'){
				$methodName = 'ChatSendServerMessageToLogin';
				$addCall_array[0] = '$z<'.$srvchatname.'$z> '.$addCall_array[0];
			}elseif($methodName == 'ChatSendToId'){
				$methodName = 'ChatSendServerMessageToId';
				$addCall_array[0] = '$z<'.$srvchatname.'$z> '.$addCall_array[0];
			}elseif($methodName == 'ChatSendToLanguage'){
				$methodName = 'ChatSendServerMessageToLanguage';
				foreach($addCall_array[0] as $lang => $text)
					$addCall_array[0][$lang] = '$z['.$srvchatname.'$z] '.$text;
			}
		}

		// ChatSend in $_Quiet mode ?   if Quiet mode then send to admins only
		if($_Quiet){
			if($methodName == 'ChatSendServerMessageToLogin' || $methodName == 'ChatSendToLogin'){
				if(!verifyAdmin($addCall_array[1]))
					return;
			}elseif($methodName == 'ChatSendServerMessageToId' || $methodName == 'ChatSendToId'){
				if(!verifyAdmin($addCall_array[1],true))
					return;
			}elseif($methodName == 'ChatSendServerMessage'){
				foreach($_players as &$pl){
					if(verifyAdmin($pl['Login'])){
						addCall($action,'ChatSendServerMessageToLogin',$addCall_array[0],$pl['Login']);
						$action = null;
					}
				}
				return;
			}elseif($methodName == 'ChatSend'){
				foreach($_players as &$pl){
					if(verifyAdmin($pl['Login'])){
						addCall($action,'ChatSendToLogin',$addCall_array[0],$pl['Login']);
						$action = null;
					}
				}
				return;
			}elseif($methodName == 'ChatSendServerMessageToLanguage'){
				if(count($addCall_array[0])>=1){
					$methodName = 'ChatSendServerMessage';
					$deflang = end($addCall_array[0]);
					$addCall_array[0] = $deflang['Text'];
					foreach($_players as &$pl){
						if(verifyAdmin($pl['Login'])){
							addCall($action,'ChatSendServerMessageToLogin',$addCall_array[0],$pl['Login']);
							$action = null;
						}
					}
				}
				return;
			}elseif($methodName == 'ChatSendToLanguage'){
				if(count($addCall_array[0])>=1){
					$methodName = 'ChatSendServerMessage';
					$deflang = end($addCall_array[0]);
					$addCall_array[0] = $deflang['Text'];
					foreach($_players as &$pl){
						if(verifyAdmin($pl['Login'])){
							addCall($action,'ChatSendToLogin',$addCall_array[0],$pl['Login']);
							$action = null;
						}
					}
				}
				return;
			}
		}

		if($methodName != 'ChatSendToLanguage' && $methodName != 'ChatSendServerMessageToLanguage'){
			// forbid to ChatSendXX a message with an empty line : it kills game clients in some cases !!!
			// verify empty lines
			$lines = explode("\n",stripColors($addCall_array[0]));
			foreach($lines as $line){
				if(strlen(trim($line)) == 0){
					$addCall_array[0] = str_replace("\n", "\n.", $addCall_array[0]);
					debugPrint("** Tried to ChatSend an empty line ! add . after each newlines",$addCall_array[0]);
					//if($_debug>1)	debugPrint('addCallArray - empty line - addCall_array[0]',$addCall_array[0]);
					if($_debug>1){
						$backtrace = debug_backtrace();
						debugPrint('addCallArray - empty line - debug_backtrace',$backtrace);
					}
					break;
				}
			}
		}
	}

	$call = array('methodName' => $methodName,'params' => $addCall_array);

	// force some kind of method to async !
	if(!$async && array_search($methodName,$_async_forced_methods) !==  false)
		$async = true;

	if($async){
		// if action is true then duplicate addCall of the method is avoided
		// (index of existing on is then retruned)
		$index = count($_multicall_async);
		if(isset($action['DropDuplicate']) && $action['DropDuplicate']){
			for($i=0; $i < $index; $i++){
				if(isset($_multicall_async_action[$i]['DropDuplicate']) && $_multicall_async_action[$i]['DropDuplicate'] &&
					 ($_multicall_async[$i] == $call)){
					// perhaps should consider the old actions having DropDuplicate too, and add new actions at the existing action ?
					
					
					// if method is Get... then remove the old one, else keep the old one !
					if(strncmp($methodName,'Get',3) == 0){
						if($_debug>3) console2("duplicate addCall(".$methodName.") in [".$i."]->".$_multicall_async[$i]['methodName']. '(keep new)');
						array_splice($_multicall_async,$i,1);
						array_splice($_multicall_async_action,$i,1);
						break;
						
					}else{
						if($_debug>3) console2("duplicate addCall(".$methodName.") in [".$i."]->".$_multicall_async[$i]['methodName']. '(keep old)');
						return $i;
					}
				}
			}
		}
		
		// add call and action
		$index = count($_multicall_async);
		$_multicall_async[$index] = $call;
		$_multicall_async_action[$index] = $action;

		return $index;

	}else{
		// if action is true then duplicate addCall of the method is avoided
		// (index of existing on is then retruned)
		$index = count($_multicall);
		if(isset($action['DropDuplicate']) && $action['DropDuplicate']){
			for($i=0; $i<$index; $i++){
				if(isset($_multicall_action[$i]['DropDuplicate']) && $_multicall_action[$i]['DropDuplicate'] &&
					 ($_multicall[$i] == $call)){
					// perhaps should consider the old actions having DropDuplicate too, and add new actions at the existing action ?
					
					
					// if method is Get... then remove the old one, else keep the old one !
					if(strncmp($methodName,'Get',3) == 0){
						if($_debug>3) console2("duplicate addCall(".$methodName.") in [".$i."]->".$_multicall[$i]['methodName']. '(keep new)');
						array_splice($_multicall,$i,1);
						array_splice($_multicall_action,$i,1);
						break;
						
					}else{
						if($_debug>3) console2("duplicate addCall(".$methodName.") in [".$i."]->".$_multicall[$i]['methodName']. '(keep old)');
						return $i;
					}
				}
			}
		}
		
		// add call and action
		$index = count($_multicall);
		$_multicall[$index] = $call;
		$_multicall_action[$index] = $action;

		return $index;
	}
}


//------------------------------------------
// add single delayed Call to $_delay_actions array
// Call will be sent after delay milliseconds
//
// usage: addCallDelay(delay,action,'TMServerMethod',...)
//------------------------------------------
function addCallDelay() {
	$call = func_get_args();
	$delay = array_shift($call);
	$action = array_shift($call);
	addCallDelayArray($delay,$action,$call);
}


//------------------------------------------
// add single delayed CallAsync to $_delay_actions array
// Call will be sent after delay milliseconds
//
// usage: addCallAsyncDelay(delay,action,'TMServerMethod',...)
//------------------------------------------
function addCallAsyncDelay() {
	$call = func_get_args();
	$delay = array_shift($call);
	$action = array_shift($call);
	addCallDelayArray($delay,$action,$call,true);
}


//------------------------------------------
// add single delayed Call to $_delay_actions array
// Call will be sent after delay milliseconds
//
// usage: addCallDelayArray(delay,action,addcall_array)
//------------------------------------------
function addCallDelayArray($delay,$action,$call,$async=false) {
	global $_debug,$_cdebug,$_delay_actions,$_func_list,$_currentTime;
	if($delay<=0){
		addCallArray($action,$call,$async);
		
	}else{
		// debug for $_cdebug specified cases...
		if(isset($_cdebug[$call[0]]) && $_debug > $_cdebug[$call[0]]){
			console("BACKTRACE addCallDelayArray({$delay},{$action},array(".@implode(',',$call)."),{$async}): ".get_backtrace());
		}

		if($_debug>8) debugPrint("addCallDelayArray - $_currentTime+$delay, call",$call);
		$delay += $_currentTime;
		if($async)
			$_delay_actions[] = array(0=>$delay,'CallAsync'=>array($action,$call));
		else
			$_delay_actions[] = array(0=>$delay,'Call'=>array($action,$call));
		usort($_delay_actions,'actionCompare');
	}
}


//------------------------------------------
// add single event to $_events array
//
// usage: addEvent('EventName',...)
//------------------------------------------
function addEvent() {
	addEventArray(func_get_args());
}


//------------------------------------------
// add single event to $_events array
//
// note: if the event array begin with a int delay, then set it to 0
//       else add the 0 delay at beginning of array
//
// usage: addEventArray( array('EventName',...) )
//------------------------------------------
function addEventArray($event) {
	global $_debug,$_events,$_func_list,$_edebug;
	//debugPrint('addEventArray - event',$event);
	if(isset($_func_list[$event[0]])){

		if($_debug>8) debugPrint('addEventArray - event',$event);
		// debug for $_edebug specified cases...
		if(isset($_edebug[$event[0]]) && $_debug > $_edebug[$event[0]]){
			console("BACKTRACE addEventArray(array(".@implode(',',$event).")): ".get_backtrace());
		}

		$_events[] = $event;

	}else{
		console('** Unknown event : '.$event[0]);
		if($_debug>2) debugPrint('addEventArray - unknown event',$event);
	}
}


//------------------------------------------
// insert single event at beginning of $_events array
//
// usage: insertEvent('EventName',...)
//------------------------------------------
function insertEvent() {
	insertEventArray(func_get_args());
}


//------------------------------------------
// insert single event at beginning of $_events array
//
// note: if the event array begin with a int delay, then set it to 0
//       else add the 0 delay at beginning of array
//
// usage: insertEventArray( array('EventName',...) )
//------------------------------------------
function insertEventArray($event) {
	global $_debug,$_events,$_func_list,$_edebug;
	//debugPrint('addEventArray - event',$event);
	if(isset($_func_list[$event[0]])){

		if($_debug>8) debugPrint('insertEventArray - event',$event);
		// debug for $_edebug specified cases...
		if(isset($_edebug[$event[0]]) && $_debug > $_edebug[$event[0]]){
			console("BACKTRACE addEventArray(array(".@implode(',',$event).")): ".get_backtrace());
		}

		array_unshift($_events,$event);

	}else{
		console('** Unknown event : '.$event[0]);
		if($_debug>2) debugPrint('insertEventArray - unknown event',$event);
	}
}


//------------------------------------------
// add single delayed event to $_delay_actions array
// event will be sent after delay milliseconds
//
// usage: addEventDelay(delay,'EventName',...)
//------------------------------------------
function addEventDelay() {
	$event = func_get_args();
	$delay = array_shift($event);
	addEventDelayArray($delay,$event);
}


//------------------------------------------
// add single delayed event to $_delay_actions array
// event will be sent after delay milliseconds
//
// usage: addEventDelayArray(delay,array('EventName',...))
//------------------------------------------
function addEventDelayArray($delay,$event) {
	global $_debug,$_delay_actions,$_func_list,$_events_end,$_currentTime,$_edebug;

	if(isset($_func_list[$event[0]])){

		if($delay<=0){

			if($_debug>8) debugPrint('addEventArray_end - event',$event);
			// debug for $_edebug specified cases...
			if(isset($_edebug[$event[0]]) && $_debug > $_edebug[$event[0]]){
				console("BACKTRACE addEventDelayArray({$delay},array(".@implode(',',$event).")): ".get_backtrace());
			}

			//addEventArray($event);
			$_events_end[] = $event;

		}else{
			if($_debug>8) debugPrint("addEventDelayArray - $_currentTime+$delay, event",$event);
			// debug for $_edebug specified cases...
			if(isset($_edebug[$event[0]]) && $_debug > $_edebug[$event[0]]){
				console("BACKTRACE addEventDelayArray({$delay},array(".@implode(',',$event).")): ".get_backtrace());
			}

			$delay += $_currentTime;
			$_delay_actions[] = array(0=>$delay,'Event'=>$event);
			usort($_delay_actions,'actionCompare');
		}

	}else{
		console('** Unknown event : '.$event[0]);
		if($_debug>2) debugPrint('addEventDelayArray - unknown event - event',$event);
	}
}


//------------------------------------------
// force delayed event named $eventname to come now, return false if none was found
//------------------------------------------
function forceDelayedEvent($eventname,$firstonly=true){
	global $_debug,$_delay_actions,$_func_list,$_currentTime;
	$found = false;
	foreach($_delay_actions as &$delay_action){
		if(isset($delay_action['Event'][0]) && $delay_action['Event'][0] == $eventname){
			//if($_debug>9) debugPrint('forceDelayedEvent - event $eventname',$delay_action['Event']);
			addEventArray($delay_action['Event']);
			unset($delay_action['Event']);
			if($firstonly)
				return true;
			$found = true;
		}
	}
	return $found;
}


// compare function for usort, return -1 if $a should be before $b
function actionCompare($a, $b){
	if($a[0]<=$b[0])
		return -1;
	else
		return 1;
}



//------------------------------------------
// Load Plugins and Register Commands...
//------------------------------------------
// CALLED BY: main program
//------------------------------------------
function loadPlugins($dir, $chkstr, $begin=true){
	global $_debug,$_Game,$_is_relay,$_use_cb;
	if($dir_handler = opendir($dir)){
		while($file = readdir($dir_handler)){
			$pos = strpos($file, $chkstr);
			$pos2 = strpos($file, '.php');
			if($pos !== false && (!$begin || $pos == 0) && ($pos2 == strlen($file)-4)){
				if($_debug>2) console2("###################\n# Loading $dir/$file");
				include_once($dir.'/'.$file);
			}
		}
		closedir($dir_handler);
	}
}


//------------------------------------------
// register plugin and its functions
//------------------------------------------
// CALLED BY: main program
//------------------------------------------
function registerPlugin($plugin,$priority=50,$version=1.0,$dependance=null){
	global $_plugin_funclist,$_plugin_list,$_funcs_plugin,$_event_list,$_needenable,$_DisabledPlugins,$_EnabledPlugins,$_debug;
	if(isset($_DisabledPlugins[$plugin])){
		if($_debug>1) console2("## Disabled plugin: ".$plugin);
		return;
	}
	if($_needenable && !isset($_EnabledPlugins[$plugin]) && !isset($_EnabledPlugins['custom'])){
		if($_debug>1) console2("## Not enabled plugin: ".$plugin);
		return;
	}

	if($_debug>3) console2("## Register plugin: ".$plugin);

	if(isset($_plugin_list[$plugin])){
		console2("*** Error: plugin ".$plugin." already registered !");
	}else{
		$_plugin_list[$plugin] = array('Active'=>true,'Version'=>$version,'Dependance'=>$dependance,'Priority'=>$priority);
	}

	foreach($_event_list as $func){
		if(function_exists($plugin.$func)){
			// add plugin event func
			$_funcs_plugin[$plugin.$func] = $plugin;
			$_plugin_funclist[$func][$plugin.$func] = $priority;
			if($_debug>4) console2("### Register plugin function: ".$plugin.$func);
			// sort event func list
			asort($_plugin_funclist[$func]);
		}
		if(function_exists($plugin.$func.'_Reverse')){
			// add plugin event func
			$_funcs_plugin[$plugin.$func.'_Reverse'] = $plugin;
			$_plugin_funclist[$func][$plugin.$func.'_Reverse'] = (1000-$priority)*1000+$priority;
			if($_debug>4) console2("### Register reverse plugin function: ".$plugin.$func.'_Reverse');
			// sort event post func list
			asort($_plugin_funclist[$func]);
		}
		if(function_exists($plugin.$func.'_Post')){
			// add plugin event func
			$_funcs_plugin[$plugin.$func.'_Post'] = $plugin;
			$_plugin_funclist[$func][$plugin.$func.'_Post'] = (1000+$priority)*1000+$priority;
			if($_debug>4) console2("### Register post plugin function: ".$plugin.$func.'_Post');
			// sort event post func list
			asort($_plugin_funclist[$func]);
		}
	}
}


//------------------------------------------
// call func for all plugins which implement it
//------------------------------------------
// CALLED BY: 
//------------------------------------------
function callFuncs(){
	callFuncsArray(func_get_args());
}


//------------------------------------------
// call func for all plugins which implement it
//------------------------------------------
// CALLED BY: callFuncs()
//------------------------------------------
// the called function can :
// - call dropEvent() or return 'DropEvent', to drop current event (will not be propagate to lesser priority plugins)
// - change global $_callFuncsArgs, to modify args for next called event functions (for current event)
//------------------------------------------
function callFuncsArray($args){
	global $_debug,$_mldebug,$_pdebug,$_memdebug,$_memdebugs,$_memdebugmode,$_plugin_funclist,$_funcs_plugin,$_response,$_response_error,$_drop_current_event,$_functype,$_callFuncsArgs;
	$_functype = $args[0];
	if($_debug>11 && $_functype != 'Everytime') console2("# Exist plugin function ? ".$_functype);
	if($_functype == 'Function'){
		// special function event
		if(isset($args[1]) && function_exists($args[1])){
			$_response = NULL;
			$_response_error = NULL;
			call_user_func_array($args[1],array_slice($args,2));
		}

	}else{
		// call event funcs
		if(isset($_plugin_funclist[$_functype])){
			if($_debug>10 && $_functype != 'Everytime') console2("# Call plugins function: ".$_functype);
			$debug = $_debug;
			$mldebug = $_mldebug;

			$_callFuncsArgs = $args;
			$drop_level = 1000;
			$_drop_current_event = false;
			foreach($_plugin_funclist[$_functype] as $func => $priority){
				if($priority%1000 >= $drop_level){
					if($_debug>8 && $_functype != 'Everytime') console2("#D Call plugin function dropped: ".$func." [".$priority."]");
					continue;
				}
				if($_debug>9 && $_functype != 'Everytime') console2("# Call plugin function: ".$func." [".$priority."]");
				$_response = NULL;
				$_response_error = NULL;

				// set plugin specific debug values
				if(isset($_funcs_plugin[$func])){
					$plugin = $_funcs_plugin[$func];
					if(isset($_pdebug[$plugin][$_functype]['debug']))
						$_debug = $_pdebug[$plugin][$_functype]['debug'];
					else if(isset($_pdebug['ALL'][$_functype]['debug']))
						$_debug = $_pdebug['ALL'][$_functype]['debug'];
					else if(isset($_pdebug[$plugin]['debug']))
						$_debug = $_pdebug[$plugin]['debug'];
					if(isset($_pdebug[$plugin][$_functype]['mldebug']))
						$_mldebug = $_pdebug[$plugin][$_functype]['mldebug'];
					else if(isset($_pdebug['ALL'][$_functype]['mldebug']))
						$_mldebug = $_pdebug['ALL'][$_functype]['mldebug'];
					else if(isset($_pdebug[$plugin]['mldebug']))
						$_mldebug = $_pdebug[$plugin]['mldebug'];
				}

				if($_memdebug>3 && function_exists('memory_get_usage')){
					// debug: memory leak search
					if(!isset($_memdebugs[$func]))
						$_memdebugs[$func] = 0;
					$mem = memory_get_usage($_memdebugmode);
					$ret = call_user_func_array($func,$_callFuncsArgs);
					$dmem = memory_get_usage($_memdebugmode) - $mem;
					$_memdebugs[$func] += $dmem;
					if($mem>10000000 && ($dmem != 0 || $_memdebugs[$func] < -2000000 || $_memdebugs[$func] > 2000000)) console2("#mem: $mem # $dmem # {$_memdebugs[$func]} # $func(".@implode(',',$args).")");
				}else{
					// normal
					$ret = call_user_func_array($func,$_callFuncsArgs);
				}

				// restore general debug values
				$_debug = $debug;
				$_mldebug = $mldebug;

				// current event function asked to stop the event calls
				if($ret == 'DropEvent' || $_drop_current_event){
					$drop_level = ($priority%1000) - 0.1;
					if($_debug>3) console("# Event $_functype dropped above $priority by $func.");
				}
			}
		}
	}
}


//------------------------------------------
// stop propagation of event on plugins having a bigger priority value
//------------------------------------------
// CALLED BY: event function, to stop event propagation
//------------------------------------------
function dropEvent(){
	global $_drop_current_event;
	$_drop_current_event = true;
}


//------------------------------------------
// true is current event is an player interaction
//------------------------------------------
function isInteractEvent(){
	global $_functype;
	if($_functype == 'PlayerManialinkPageAnswer' || $_functype == 'PlayerMenuAction')
		return true;
	return false;
}


//------------------------------------------
// delay a FlowControlTransition Proceed.
// Can be called from events BeforeEndRound, BeforePlay and EndPodium
// the event will be called again after the delay and before proceeding the transition.
//   'Synchro -> Play' (BeforePlay) : before BeginRound and StatusChanged 3->4, seconds after StatusChanged 2->3 or 4->3 and EndRound
//   'Play -> Synchro' (BeforeEndRound) : before StatusChanged 4->3 and EndRound, after all PlayerFinish
//   'Play -> Podium' (BeforeEndRound) : before StatusChanged 4->5 and EndRound and EndRace, 
//   'Podium -> Synchro' (EndPodium) : before StatusChanged 5->2 and BeginRace, seconds after EndRace
function delayTransition($delay){
	global $_debug,$_use_flowcontrol,$_MFCTransition,$_currentTime;
	if($_use_flowcontrol && $_MFCTransition['Transition'] != '' && $_MFCTransition['Event'] != '' && $delay > 0){
		dropEvent();
		$delaytime = $_currentTime + $delay;
		$fulldelay = $delaytime - $_MFCTransition['Time'];
		if($delay > 60000){
			if($_debug>1) console("delayTransition:: a transition delay above 1min is forbidden ({$delay} ms) !");

		}elseif($fulldelay > 300000){
			if($_debug>1) console("delayTransition:: delaying a transition more than 5min is forbidden ({$delay}->{$fulldelay} ms) !");

		}elseif($_MFCTransition['DelayTime'] < $delaytime){
			if($_debug>1) console("delayTransition:: transition {$_MFCTransition['Transition']}/{$_MFCTransition['Event']} delayed ({$delay}->{$fulldelay} ms)");
			$_MFCTransition['DelayTime'] = $delaytime;
		}
	}
}


//------------------------------------------
function enableFlowControl($val=true){
	global $_debug,$_client,$_methods_list,$_use_flowcontrol,$_want_flowcontrol,$_need_flowcontrol,$_is_relay;
	// no flow control for relay ! (it's the master which control the relay)
	if($_is_relay)
		return;
	
	if(($_want_flowcontrol && $_use_flowcontrol != $val) ||
		 (!$_want_flowcontrol && !$val && $_use_flowcontrol)){
		// set manual flow control

		if(!$_client->query('ManualFlowControlEnable',$val)){
			$_use_flowcontrol = false;
			console('##############################################################');
			console('Error ! ManualFlowControl call failed');
			console('##############################################################');
		}else{
			$_use_flowcontrol = $val;
		}
		if($_client->query('ManualFlowControlIsEnabled')){
			$mfc = $_client->getResponse();
			if($mfc == 1){
				console("*** FlowControl: activated");
				$_use_flowcontrol = true;
			}elseif($mfc>1){
				console("*** FlowControl: by another script !");
				$_use_flowcontrol = false;
			}else{
				console("*** FlowControl: not activated !!!");
				$_use_flowcontrol = false;
			}
		}else{
			if($_use_flowcontrol)
				console('*** FlowControl: enabled ! (ManualFlowControlIsEnabled failed)');
			else
				console('*** FlowControl: disabled ! (ManualFlowControlIsEnabled failed)');
		}
	}
	if($_need_flowcontrol && $_want_flowcontrol && !$_use_flowcontrol){
		console('*** FlowControl: fail to get ManualFlowControl and need it : quit !');
		exit();
	}
}


//------------------------------------------
// send php data to master (it will be serialize and compressed)
// note: TunnelSendData has a very very limited average bandwidth !
function sendToMaster($data){
	global $_debug,$_master,$_is_relay,$_client;
	if(!$_is_relay || !isset($_master['PlayerId']))
		return false;
	$data64 = new IXR_Base64(gzdeflate(serialize($data)));
	if($_debug>5) console("sendToMaster({$_master['PlayerId']}) : ".print_r($data64,true));

	// using TunnelSendDataTo in multicall seems to make problems, so send directly
	if(!$_client->query('TunnelSendDataToId', $_master['PlayerId'], $data64)){
		if($_debug>0) console("sendToMaster:: Error query failed : ".$_client->getErrorCode().': '.$_client->getErrorMessage());
		return false;
	}else{
		$sent = $_client->getResponse();
		if(!$sent){
			if($_debug>0) console("sendToMaster:: Error could not be sent : ".$_client->getErrorCode().': '.$_client->getErrorMessage());
		}
	}
	return true;
}


//------------------------------------------
// send php data to a relay (it will be serialize and compressed, 65500 bytes max)
// chat is used because TunnelSendData has too limited bandwidth !
// if $id === true send send to all relays
function sendToRelay($id,$data,$fastonly=false){
	global $_debug,$_relays,$_master,$_is_relay,$_client;
	if($_is_relay || count($_relays) <= 0)
		return false;
	$datacomp = gzdeflate(serialize( $data ));
	$datab64 = new IXR_Base64($datacomp);
	$data64 = base64_encode($datacomp);
	$data64len = strlen($data64);
	//if($data64len > 65500){ // using ChatSendServerMessageToId
		//console("sendToRelay:: Error, data too big: encoded/compressed data exceed 65500 bytes ($data64len) !");
	if($data64len > 5450){ // using TunnelSendDataToId
		console("sendToRelay:: Error, data too big: encoded/compressed data exceed 5450 bytes ($data64len) !");
		return false;
	}
	if($id === true){
		//$backtrace = debug_backtrace(); debugPrint('sendToRelay - debug_backtrace',$backtrace);
		if($_debug>6) console("sendToRelay(all) : ".print_r($data64,true));
		if($_debug>6) console("sendToRelay(all) , _relays: ".print_r($_relays,true));
		foreach($_relays as $relay){
			if(!isset($relay['Master']) && (!$fastonly || isset($relay['FastRelay']))){
				//if($_debug>0) console(" sendToRelay({$relay['Login']},{$relay['PlayerId']}) : ".print_r($data64,true));
				//addCall(null,'ChatSendServerMessageToId','::torelay::'.$data64,$relay['PlayerId']);

				// using TunnelSendDataTo in multicall seems to make problems, so send directly
				//addCall(null,'TunnelSendDataToId',$relay['PlayerId'],$datab64);
				if(!$_client->query('TunnelSendDataToId',$relay['PlayerId'],$datab64)){
					if($_debug>0) console(" sendToRelay({$relay['Login']},{$relay['PlayerId']}) : Error query failed : ".$_client->getErrorCode().': '.$_client->getErrorMessage());
				}else{
					$sent = $_client->getResponse();
					if(!$sent){
						if($_debug>0) console(" sendToRelay({$relay['Login']},{$relay['PlayerId']}) : Error could not be sent : ".$_client->getErrorCode().': '.$_client->getErrorMessage());
					}
				}
			}
		}
	}else{
		//if($_debug>0) console("sendToRelay({$id}) : ".print_r($data64,true));
		//addCall(null,'ChatSendServerMessageToId','::torelay::'.$data64,$id);
		
		// using TunnelSendDataTo in multicall seems to make problems, so send directly
		//addCall(null,'TunnelSendDataToId',$id,$datab64);
		if(!$_client->query('TunnelSendDataToId',$id,$datab64)){
			if($_debug>0) console(" sendToRelay({$id}) : Error query failed : ".$_client->getErrorCode().': '.$_client->getErrorMessage());
		}else{
			$sent = $_client->getResponse();
			if(!$sent){
				if($_debug>0) console(" sendToRelay({$id}) : Error could not be sent : ".$_client->getErrorCode().': '.$_client->getErrorMessage());
			}
		}

	}
	return true;
}


//------------------------------------------
// get Fast infos usefull for relay(s), then send to them/it
function getFastInfosForRelay($relaylogin,$state='race'){
	global $_debug,$_relays;
	if(count($_relays) > 0 && ($relaylogin === true || isset($_relays[$relaylogin]['PlayerId']))){
		if($_debug>3) console("getFastInfosForRelay:: call GetInfosForRelay then send to relay({$relaylogin},{$state})");
		addEvent('GetInfosForRelay',$relaylogin,$state);
	}
}


//------------------------------------------
// Connect to TM server
//------------------------------------------
// CALLED BY: main program
//------------------------------------------
function connectTM(){
	global $_DedConfig,$_client,$_methods_list,$_islogedin,$_use_cb,$_is_relay,$_Game,$_Version,$_SystemInfo,$_ServerInfos,$_ServerPackMask,$_game_name,$_TracksDirectory,$_FAST_tool,$_FASTver,$_Status,$_StatusCode,$_ServerId,$_master,$_relays,$_use_flowcontrol,$_want_flowcontrol,$_need_flowcontrol,$_LadderServerLimits,$_reconnect_times,$_connect_time,$_currentTime,$_fast_id,$_fastecho;

	$_ServerId = 0;
	$_islogedin = false;
	$_use_cb = false;
	$_is_relay = false;
	$_FAST_tool = 'Fast';
	$_master = array();
	$_relays = array();
	$_use_flowcontrol = false;

	if($_client->socket !== false){
		console('Terminate connection with TM server...');
		$_client->Terminate();
	}
	
	sleep(1);
	console('');
	console('##############################################################');
	console('Connection to TM server: '.$_DedConfig['server_ip'].':'.$_DedConfig['xmlrpc_port']);

	if (!$_client->InitWithIp($_DedConfig['server_ip'],$_DedConfig['xmlrpc_port'])) {
		console('##############################################################');
		console('An error occurred - '.$_client->getErrorCode().':'.$_client->getErrorMessage());
		console('');
		console("Can't connect to the TM server on xmlrpc port ! Is it started ?");
		console('');
		console('Are you sure your server xmlrpc is on '.$_DedConfig['server_ip'].':'.$_DedConfig['xmlrpc_port'].' ?');
		console('##############################################################');
		return false;

	}elseif (!$_client->query('Authenticate', 'SuperAdmin', $_DedConfig['super_admin'])) {
		console('##############################################################');
		console('Login failed !  Error '.$_client->getErrorCode().': '.$_client->getErrorMessage());
		console('');
		console('Are you sure you set the right password for your server SuperAdmin login ?');
		console('##############################################################');
		return false;

	}elseif (!$_client->query('GetVersion')) {
		console('##############################################################');
		console('Error '.$_client->getErrorCode().': '.$_client->getErrorMessage());
		console('Could not get TM server version and game type !');
		console('##############################################################');
		exit(0);
	}
	$_Version = $_client->getResponse();

	console('##############################################################');

	// wait the server is started is needed...
	$try = 2;
	do{
		if(!$_client->query('GetStatus')){
			console('##############################################################');
			console('Error ! Failed to get server Status !');
			console('##############################################################');
			exit(0);
		}
		$_Status = $_client->getResponse();
		$_StatusCode = $_Status['Code'];
		if($_StatusCode < 3){
			if($try > 16){
				console('##############################################################');
				console('Server is still not in started state, existing...');
				console('##############################################################');
				exit(0);
			}
			console('');
			console("#** Wait server start... (status=$_StatusCode)");
			sleep($try++);
		}
	}while($_StatusCode < 3);


	// test version
	console('');
	console('*********************************************************');
	if(isset($_game_name[$_Version['Name']]) && isset($_Version['Version'])){
		$_Game = $_game_name[$_Version['Name']];
		$_FAST_tool .= $_Game[2];
	}else{
		$_FAST_tool .= 'X';
		console('##############################################################');
		console("*** Unknown game name: {$_Version['Name']}  {$_Version['Version']},{$_Version['Build']}");
		console('##############################################################');
		exit(0);
	}
	console("*** Server: $_Game/{$_Version['Name']}, {$_Version['Version']},{$_Version['Build']}");
	$ver = explode('.',$_Version['Version']);
	if(count($ver)<3 || ($ver[0]+0) < 2 || ($ver[1]+0) < 11 || ($ver[2]+0) < 11 ){
		console('##############################################################');
		console('Error ! need TM Forever server version 2.11.11 or later !');
		console('##############################################################');
		exit(0);
	}
	if(isset($_Version['Build']) && strlen($_Version['Build'])>0){
		$_FAST_tool .= 'd';
		
	}else{
		$_FAST_tool .= 'i';
	}

	// Tracks directory
	if(!$_client->query('GetTracksDirectory')) {
		console('##############################################################');
		console('Error '.$_client->getErrorCode().': '.$_client->getErrorMessage());
		console('Could not get TM server Tracks Directory !');
		console('##############################################################');
		exit(0);
	}
	$_TracksDirectory = $_client->getResponse();
	// windows or linux
	if($_TracksDirectory[0] == '/')
		$_FAST_tool .= 'l';
	else
		$_FAST_tool .= 'w';
	
	// SystemInfo
	if(!$_client->query('GetSystemInfo')) {
		console('##############################################################');
		console('Error '.$_client->getErrorCode().': '.$_client->getErrorMessage());
		console('Could not get SystemInfo !');
		console('##############################################################');
		exit(0);
	}
	$_SystemInfo = $_client->getResponse();
	// system infos
	console("*** Server: IP: {$_SystemInfo['PublishedIp']}:{$_SystemInfo['Port']}, P2P: {$_SystemInfo['P2PPort']}");
	if($_SystemInfo['ServerLogin'] == ''){
		console("*** Server: LAN");
		// set default values...
		$_ServerInfos = array('Login'=>$_SystemInfo['ServerLogin'],'NickName'=>$_SystemInfo['ServerLogin'],
													'PlayerId'=>0,'TeamId'=>-1,'Path'=>'','Language'=>'','IPAddress'=>'',
													'DownloadRate'=>1048576,'UploadRate'=>65536,'IsSpectator'=>1,'IsInOfficialMode'=>false,
													'IsReferee'=>false,'HoursSinceZoneInscription'=>-1,'OnlineRights'=>3);

	}elseif(is_LAN_login($_SystemInfo['ServerLogin'])){
		console("*** Server: LAN (account: {$_SystemInfo['ServerLogin']} => ".get_LAN_baselogin($_SystemInfo['ServerLogin']).')');
		if(!$_client->query('GetDetailedPlayerInfo',$_SystemInfo['ServerLogin'])){
			// set default values...
			$_ServerInfos = array('Login'=>$_SystemInfo['ServerLogin'],'NickName'=>$_SystemInfo['ServerLogin'],
														'PlayerId'=>0,'TeamId'=>-1,'Path'=>'','Language'=>'','IPAddress'=>'',
														'DownloadRate'=>1048576,'UploadRate'=>65536,'IsSpectator'=>1,'IsInOfficialMode'=>false,
														'IsReferee'=>false,'HoursSinceZoneInscription'=>-1,'OnlineRights'=>3);
		}else{
			// store server values...
			$_ServerInfos = $_client->getResponse();
		}

	}elseif(strcasecmp($_SystemInfo['ServerLogin'],$_DedConfig['login']) != 0){
		console('##############################################################');
		console("Server account: {$_SystemInfo['ServerLogin']} , Fast account: {$_DedConfig['login']}");
		console('Error ! Account configured in Fast is not the same as server login !');
		console('##############################################################');
		exit(0);
	}else{
		console("*** Server: Internet (account: {$_SystemInfo['ServerLogin']})");

		$try = 2;
		do{
			// get server account infos...
			if(!$_client->query('GetDetailedPlayerInfo',$_SystemInfo['ServerLogin'])){
				// set default values...
				$_ServerInfos = array('Login'=>$_SystemInfo['ServerLogin'],'NickName'=>$_SystemInfo['ServerLogin'],
															'PlayerId'=>0,'TeamId'=>-1,'Path'=>'','Language'=>'','IPAddress'=>'',
															'DownloadRate'=>1048576,'UploadRate'=>65536,'IsSpectator'=>1,'IsInOfficialMode'=>false,
															'IsReferee'=>false,'HoursSinceZoneInscription'=>-1,'OnlineRights'=>3);
				if(isset($_DedConfig['connection_downloadrate']))
					$_ServerInfos['DownloadRate'] = (int)($_DedConfig['connection_downloadrate']*1024/8);
				elseif(isset($_DedConfig['connection_donwloadrate']))
					$_ServerInfos['DownloadRate'] = (int)($_DedConfig['connection_donwloadrate']*1024/8);
				if(isset($_DedConfig['connection_uploadrate']))
					$_ServerInfos['UploadRate'] = (int)($_DedConfig['connection_uploadrate']*1024/8);
				// old dedicated: will never get real infos...
				console("*** Server: Path: unavailable ! (old dedicated ?)");
				break;

			}else{
				// store server values...
				$_ServerInfos = $_client->getResponse();

				if(strlen($_ServerInfos['Path'])>=5){
					// server account account infos are available: ok
					console("*** Server: Path: {$_ServerInfos['Path']}");
					break;

				}else{
					// Path not available: wait
					if($try > 11){
						console('### Failed to get server account infos: continue without it !');
						break;
					}
					console("#** Wait server account infos...");
					sleep($try++);
				}
			}
		}while(true);
	}
	$_ServerId = $_ServerInfos['PlayerId'];

	// ServerPackMask
	if(!$_client->query('GetServerPackMask')) {
		console('##############################################################');
		console('Error '.$_client->getErrorCode().': '.$_client->getErrorMessage());
		console('Could not get TMF Server PackMask !');
		console('##############################################################');
		exit(0);
	}
	$_ServerPackMask = $_client->getResponse();
	// packmask
	if($_ServerPackMask == '')
		console("*** Server: type: Forever United (all environments able)");
	else
		console("*** Server: type: Forever $_ServerPackMask only");

	// get methods list
	if(!$_client->query('system.listMethods')) {
		console('##############################################################');
		console('Error '.$_client->getErrorCode().': '.$_client->getErrorMessage());
		console('Could not get TM server Methods list !');
		console('##############################################################');
		exit(0);
	}
	$res = $_client->getResponse();
	foreach($res as $val){
		$_methods_list[$val] = $val;
	}

	// is it a relay server ?
	if(isset($_methods_list['IsRelayServer']) && $_client->query('IsRelayServer')) {
		$res = $_client->getResponse();
		if($res){
			$_is_relay = true;
			console("*** Server: ManiaChannel relay server !");
			if(isset($_methods_list['GetMainServerPlayerInfo']) && $_client->query('GetMainServerPlayerInfo',1)){
				$res = $_client->getResponse();
				if(isset($res['Login'])){
					$_master = $res;
					console("*** Master server: {$_master['Login']}");
				}
			}
		}
		// server list
		if($_client->query('GetPlayerList',260,0,2)){
			$list = $_client->getResponse();
			if(count($list)>0){
				foreach($list as $srv){
					if((floor($srv['Flags']/100000) % 10) > 0){
						// server
						$_relays[$srv['Login']] = $srv;
					}
				}
			}
		}
		// remove current server
		if(isset($_relays[$_SystemInfo['ServerLogin']])){
			unset($_relays[$_SystemInfo['ServerLogin']]);
		}
		if($_is_relay && isset($_master['Login']) && isset($_relays[$_master['Login']])){
			// master server
			$_relays[$_master['Login']]['Master'] = true;
		}
		// connected relay servers
		if(count($_relays)>0){
			foreach($_relays as $srv){
				if(!isset($srv['Master']))
					console("*** Connected relay server:  {$srv['Login']}");
			}
		}
	}

	// activate callbacks
	if(!$_client->query('EnableCallbacks',true)){
		console('##############################################################');
		console('Error ! Callbacks activation failed, basic mode not supported');
		console('need a callbacks enabled server !');
		console('##############################################################');
		exit(0);
	}

	$_islogedin = true;
	$_use_cb = true;

	// Flow Control setup
	if($_is_relay){
		$_want_flowcontrol = false;
		$_need_flowcontrol = false;
	}
	if($_want_flowcontrol){
		if(!isset($_methods_list['ManualFlowControlIsEnabled'])){
			$_want_flowcontrol = false;
			console('*** Flow Control : disabled (unknown method ManualFlowControlIsEnabled, update your dedicated server !)');
			if($_need_flowcontrol){
				console('*** Flow Control : not able to get ManualFlowControlIsEnabled and need it : quit !');
				exit();
			}
		}else{
			enableFlowControl(true);
		}
	}else{
		console('*** Flow Control : disabled');
	}

	if(isset($_methods_list['GetLadderServerLimits']) && $_client->query('GetLadderServerLimits')) {
		$_LadderServerLimits = $_client->getResponse();
		// if ladder server then empty passwords !
		if(isset($_LadderServerLimits['LadderServerLimitMax']) &&
			 $_LadderServerLimits['LadderServerLimitMax'] > 50000){
			$_client->query('SetServerPassword','');
			$_client->query('SetServerPasswordForSpectator','');
		}
	}

	// keep time at connection
	$_currentTime = floor(microtime(true)*1000);
	$_connect_time = time();
	$_reconnect_times[] = $_connect_time;
	$_fastecho = "{$_connect_time}.{$_fast_id}";
	// annonouce itself
	$_client->query('Echo','Fast.running',"{$_fastecho}");

	console('*********************************************************');
	console("*** Fast: $_FAST_tool $_FASTver (account: {$_DedConfig['login']}) [{$_fastecho}]");
	if(isset($_SERVER["PWD"]) && isset($_SERVER["_"]))
		console('*** Fast: php '.phpversion()." (".$_SERVER["_"]." from ".$_SERVER["PWD"].")");
	else
		console('*** Fast: php '.phpversion());
	console('*********************************************************');
	console('');
	console('');	

	return true;
}


//------------------------------------------
// Try to reconnect to TM server
//------------------------------------------
// CALLED BY: main loop
//------------------------------------------
function reconnectTM(){
	global $_debug,$_events,$_delay_actions,$_currentTime,$_oldTime,$_ml_is_on,$_notices_is_on;

	// try immediate reconnect
	if(connectTM() === true){
		console('Connection with server lost, but connected again successfully...');
		return;
	}
	// try immediate reconnect a second time
	sleep(1);
	if(connectTM() === true){
		console('Connection with server lost, but connected again successfully at second try...');
		return;
	}
	// try immediate reconnect a 3rd time
	sleep(5);
	if(connectTM() === true){
		console('Connection with server lost, but connected again successfully at 3rd try...');
		return;
	}
	// try immediate reconnect a 4th time
	sleep(15);
	if(connectTM() === true){
		console('Connection with server lost, but connected again successfully at 4th try...');
		return;
	}
	// try immediate reconnect a 5th time
	sleep(30);
	if(connectTM() === true){
		console('Connection with server lost, but connected again successfully at 5th try...');
		return;
	}
	// else try long (1 min) reconnect until success
	do{
		set_time_limit(600);
		console('Error in connection with server, wait 1 minute then retry...');
		if($_debug>0) console('Empty events and delay spools.');

		// empty events and delay actions
		$_events = array();
		$_delay_actions = array();

		// put players in old list
		if(function_exists('playersReconnectTM'))
			playersReconnectTM();

		// wait 1 min
		sleep(60);
		// get time
		$_oldTime = $_currentTime;
		$_currentTime = floor(microtime(true)*1000);

	}while(connectTM() === false);

	// get all infos from TM server again (like at startup)
	manageInit();
}


//------------------------------------------
// gets user login by nickname
//------------------------------------------
// CALLED BY: 
//------------------------------------------
function getUserLogin($nick){
	global $_PlayerList;
	$nick = trim($nick);
	for($i = 0; $i < sizeof($_PlayerList); $i++){
		if(trim($_PlayerList[$i]['NickName']) == $nick)
			return $_PlayerList[$i]['Login'];
	}
	// it seems that when special chars are in the name, 
	// there are 3 strange chars in front of Nickname !
	// don't know if it's always the same, need more investigation...
	for($i = 0; $i < sizeof($_PlayerList); $i++){
		if (trim(substr($_PlayerList[$i]['NickName'],3)) == $nick)
			return $_PlayerList[$i]['Login'];
	}
	return false;
}


//------------------------------------------
// gets user nickname by login
//------------------------------------------
// CALLED BY: 
//------------------------------------------
function getUserNickName($login){
	global $_PlayerList,$_players;
	if(isset($_players[$login]['NickName']))
		return $_players[$login]['NickName'];

	//for($i = 0; $i < sizeof($_PlayerList); $i++){
	//if($_PlayerList[$i]['Login'] == $login)
	//return $_PlayerList[$i]['NickName'];
	//}
	return $login;
}


//------------------------------------------
// gets user login by nickname
//------------------------------------------
// CALLED BY: 
//------------------------------------------
function getIdLogin($id){
	global $_PlayerList;
	for($i = 0; $i < sizeof($_PlayerList); $i++){
		if($_PlayerList[$i]['PlayerId'] == $id)
			return $_PlayerList[$i]['Login'];
	}
	return false;
}


//------------------------------------------
// Gets exclusive MD5 of a track
//------------------------------------------
// CALLED BY: obsolete
//------------------------------------------
function getMd5(){
	global $_ChallengeInfo;
	return md5($_ChallengeInfo['Author'].$_ChallengeInfo['Name'].$_ChallengeInfo['Environnement'].$_ChallengeInfo['Mood'].$_ChallengeInfo['CopperPrice']);
}




//------------------------------------------
// wait milliseconds
//------------------------------------------
// CALLED BY: simulateCallbacks()
//------------------------------------------
function doSleep($msec){
	if($msec>0)
		usleep($msec*1000);
}


//------------------------------------------
// Show an array and sub-arrays contents
//------------------------------------------
// CALLED BY: 
//------------------------------------------
function showIt($key,$val,$indent=""){
	if (is_array($val)){
		print $indent."*".$key." :\n";
		foreach ($val as $key2 => $val2) {
			showIt($key2,$val2,"  ".$indent);
		}
	}else{
		if(is_bool($val)){
			if($val)
				$val = '[BOOL]true';
			else
				$val = '[BOOL]false';
		}
		print $indent.$key.' = '.$val."\n";
	}
}
 


//------------------------------------------
// verify if login is in admin list
//------------------------------------------
function verifyAdmin($login,$isId=false){
	global $_AdminList,$_PlayerList,$_SystemInfo,$_remote_controller_chat_is_admin;

	if($login == $_SystemInfo['ServerLogin'])
		return $_remote_controller_chat_is_admin;

	// if no admin then the first passing an admin command or help will be
	if(!$isId && count($_AdminList) == 0){
		addAdmin($login);
	}
	// if id then get login
	if($isId){
		$login = getIdLogin($login);
		if($login === false)
			return false;
	}
	if(isset($login) && is_string($login)){
		$login = strtolower(stripColors($login));
		if(is_LAN_login($login))
			$login = get_LAN_baselogin($login);
		//$login = substr($login,0,strrpos($login,'/')+1);
		// verify if admin
		if(isset($login) && (array_search($login,$_AdminList) !== false)){
			return true;
		}
	}
	return false;
}


//------------------------------------------
// add login in admin list
//------------------------------------------
// CALLED BY: 
//------------------------------------------
function addAdmin($login){
	global $_AdminList;
	$login = strtolower(stripColors($login));
	if(is_LAN_login($login))
		$login = get_LAN_baselogin($login);
	//$login = substr($login,0,strrpos($login,'/')+1);
	if(array_search($login,$_AdminList) === false){
		$_AdminList[] = $login;
		addEvent('AdminChange',$login,true);
		saveAdmins();
		return true;
	}
	return false;
}


//------------------------------------------
// remove login in admin list
//------------------------------------------
// CALLED BY: 
//------------------------------------------
function removeAdmin($login){
	global $_AdminList;
	$login = strtolower(stripColors($login));
	if(is_LAN_login($login))
		$login = get_LAN_baselogin($login);
	//$login = substr($login,0,strrpos($login,'/')+1);
	$pos = array_search($login,$_AdminList);
	if($pos !== false){
		unset($_AdminList[$pos]);
		addEvent('AdminChange',$login,false);
		saveAdmins();
		return true;
	}
	return false;
}


//------------------------------------------
// Load Admin list from xml file
//------------------------------------------
// CALLED BY: 
//------------------------------------------
function loadAdmins($send_event=true){
	global $_debug,$_AdminList,$_adminfile,$_adminfile_date,$_DedConfig;

	if(!isset($_AdminList) || !is_array($_AdminList))
		$_AdminList = array();

	if(!file_exists($_adminfile)){
		saveAdmins();
	}

	// test if admin file has changed
	$adminstat = @stat($_adminfile);
	if($adminstat !== false){
		if($adminstat['mtime'] <= $_adminfile_date)
			return; // not changed : return
		$_adminfile_date = $adminstat['mtime'];
	}else{
		$_adminfile_date = time();
	}

	$oldadmins = $_AdminList;

	$admin = xml_parse_file($_adminfile);
	if(isset($admin['adminlist']['player']['login'])){
		if(trim($admin['adminlist']['player']['login']) != ''){
			$login = strtolower(stripColors(''.$admin['adminlist']['player']['login']));
			if(is_LAN_login($login))
				$login = get_LAN_baselogin($login);
			//$login = substr($login,0,strrpos($login,'/')+1);
			if(array_search($login,$_AdminList) === false)
				$_AdminList[] = $login;
		}

	}elseif(isset($admin['adminlist']['player'][0])){
		for($i=0; isset($admin['adminlist']['player'][$i]['login']); $i++){
			if(trim($admin['adminlist']['player'][$i]['login']) != ''){
				$login = strtolower(stripColors(''.$admin['adminlist']['player'][$i]['login']));
				if(is_LAN_login($login))
					$login = get_LAN_baselogin($login);
				//$login = substr($login,0,strrpos($login,'/')+1);
				if(array_search($login,$_AdminList) === false)
					$_AdminList[] = $login;
			}
		}
	}
	if($send_event){
		if(count($_AdminList)>0){
			foreach($_AdminList as $login){
				$pos = array_search($login,$oldadmins);
				if($pos === false)
					addEvent('AdminChange',$login,true);
				else
					unset($oldadmins[$pos]);
			}
		}
		if(count($oldadmins)>0){
			foreach($oldadmins as $login)
				addEvent('AdminChange',$login,false);
		}
		//debugPrint("** Loaded admins - _AdminList",$_AdminList);
		//debugPrint("** Loaded admins - oldadmins",$oldadmins);
	}else{
		if($_debug>5) debugPrint("** Loaded admins from file $_adminfile : ",$_AdminList);
	}
}


//------------------------------------------
// Save Admin list to xml file
//------------------------------------------
// CALLED BY: 
//------------------------------------------
function saveAdmins(){
	global $_debug,$_AdminList,$_adminfile;

	$admins = array('adminlist'=>array('player'=>array('.multi_same_tag.'=>true)));
	foreach($_AdminList as $admin)
		$admins['adminlist']['player'][] = array('login'=>$admin);
	$admins['adminlist']['player'][] =  array('login'=>'');
	xml_build_to_file($admins,$_adminfile);
	if($_debug>1) debugPrint("** Saved admins to file $_adminfile : ",$_AdminList);
}


//------------------------------------------
// go in stunts gamemode, if not already stunts
// to do such, a complete stunts matchsettings have to be loaded
//------------------------------------------
function goStunts(){
	global $_debug,$_client,$_NextGameInfos;
	if($_NextGameInfos['GameMode'] != STUNTS){
		if(!@$_client->query('LoadMatchSettings','fast-stunts.txt') || !$_client->getResponse()){
			// failed : try to copy needed files
			console('goStunts:: Error'.$_client->getErrorCode().': '.$_client->getErrorMessage());

			if($_client->getErrorCode() == -1000){
				// no matchsettings, or no map : copy them
				copyFileToDedicated('includes/fast-stunts.txt','fast-stunts.txt');
				copyFileToDedicated('includes/fast-stunts.Challenge.Gbx','Challenges/fast-stunts.Challenge.Gbx');
			}

			// try again
			if(!@$_client->query('LoadMatchSettings','fast-stunts.txt') || !$_client->getResponse()){
				console('goStunts:: failed to loadmatchsettings fast-stunts.txt...  Error'.$_client->getErrorCode().': '.$_client->getErrorMessage());
				return false;
			}
		}
		if($_debug>0) console('goStunts:: loadmatchsettings... !');
		addCall(true,'GetGameInfos',1);
		return true;
	}
	if($_debug>0) console('goStunts:: already stunts for next !');
	return true;
}


//------------------------------------------
// go in Laps gamemode, if is currently stunts
// to do such, a complete not stunts matchsettings have to be loaded
//------------------------------------------
function goNotStunts(){
	global $_debug,$_client,$_NextGameInfos;
	if($_NextGameInfos['GameMode'] == STUNTS){
		if(!@$_client->query('LoadMatchSettings','fast-laps.txt') || !$_client->getResponse()){
			// failed : try to copy needed files
			console('goNotStunts:: Error'.$_client->getErrorCode().': '.$_client->getErrorMessage());

			if($_client->getErrorCode() == -1000){
				// no matchsettings, or no map : copy them
				copyFileToDedicated('includes/fast-laps.txt','fast-laps.txt');
				copyFileToDedicated('includes/fast-laps.Challenge.Gbx','Challenges/fast-laps.Challenge.Gbx');
			}

			// try again
			if(!@$_client->query('LoadMatchSettings','fast-laps.txt') || !$_client->getResponse()){
				console('goNotStunts:: failed to loadmatchsettings fast-laps.txt...  Error'.$_client->getErrorCode().': '.$_client->getErrorMessage());
				return false;
			}
		}
		if($_debug>0) console('goNotStunts:: loadmatchsettings... !');
		addCall(true,'GetGameInfos',1);
		return true;
	}
	if($_debug>0) console('goStunts:: already not stunts for next !');
	return true;
}


//------------------------------------------
// copy a file from Fast path to dedicated GameData/Tracks/ path
// $filesrc is relative to Fast dir, $filedest is relative to dedicated GameData/Tracks/
//------------------------------------------
function copyFileToDedicated($filesrc,$filedest){
	global $_debug,$_client;
	$data = @file_get_contents($filesrc);
	if($data === false){
		console("copyFileToDedicated:: failed to read {$filesrc}");
		return false;
	}
	$data64 = new IXR_Base64($data);
	if(!@$_client->query('WriteFile',$filedest,$data64) || !$_client->getResponse()){
		console('copyFileToDedicated:: Error '.$_client->getErrorCode().' while writing to GameData/Tracks/{$filedest}: '.$_client->getErrorMessage());
		return false;
	}
	console("copyFileToDedicated:: Fast/{$filesrc} copied to GameData/Tracks/{$filedest}");
	return true;
}


//------------------------------------------
// safe faile access, call a hook giving file content, then write the replied data
//------------------------------------------
function locked_fileaccess($filename, $hook) {
	$args = func_get_args();
	$filename = array_shift($args);
	$hook = array_shift($args);
	
	$abort_state = ignore_user_abort(1);
	$lockfile = $filename.'.lock';
	
	// if a lockfile already exists, but it is more than 5 seconds old,
	// we assume the last process failed to delete it
	// this would be very rare, but possible and must be accounted for
	if (file_exists($lockfile)) {
		if (time() - filemtime($lockfile) > 5) unlink($lockfile);
	}

	$lock_ex = @fopen($lockfile, 'x');
	for ($i=0; ($lock_ex === false) && ($i < 20); $i++) {
		clearstatcache();
		usleep(rand(100, 999));
		$lock_ex = @fopen($lockfile, 'x');
	}

	$success = false;
	if ($lock_ex !== false) {

		$data = @file_get_contents($filename);
		if($data === false)
			$data = '';

		$args[] = $data;
		$data = call_user_func_array($hook,$args);

		if($data === false)
			$success = true;
		elseif(file_put_contents($filename,$data) !== false)
			$success = true;

		fclose($lock_ex);
		unlink($lockfile);
	}

	ignore_user_abort($abort_state);
  return $success;
}


//------------------------------------------
// get array end key, use end() !
//------------------------------------------
if(!function_exists('endkey()')){

	function endkey($thearray){
		end($thearray);
		return key($thearray);
	}
}


//------------------------------------------
// mostly for debugging purpose, return an array with first and last elements
//------------------------------------------
function minmaxArray(&$src){
	if(count($src)>1){
		$dst = array($src[0]);
		$val = end($src);
		$dst[key($src)] = $val;
		return $dst;
	}
	return $src;
}


//------------------------------------------
// get backtrace array with simple func string, or complete simple string
//------------------------------------------
function get_backtrace($getstring=true,$backmax=4){
	$backtrace = debug_backtrace();
	foreach($backtrace as $i => &$trace){
		$file = isset($trace['file']) ? $trace['file'] : (isset($backtrace[$i+1]['file']) ? $backtrace[$i+1]['file'] : '???');
		$line = isset($trace['line']) ? $trace['line'] : (isset($backtrace[$i+1]['line']) ? $backtrace[$i+1]['line'] : '???');
		$func = $trace['function'];
		if(isset($backtrace[$i+1]['function']) && $backtrace[$i+1]['function'] == 'call_user_func')
			$func .= ' [call_user_func]';
		else if(isset($backtrace[$i+1]['function']) && $backtrace[$i+1]['function'] == 'call_user_func_array')
			$func .= ' [call_user_func_array]';
		$trace['func'] = $func.' ('.basename($file).','.$line.')';

		if($trace['function'] == 'call_user_func' || $trace['function'] == 'call_user_func_array')
			unset($backtrace[$i]);
	}
	array_shift($backtrace); // skip get_backtrace() function
	//print_r($backtrace);

	if(!$getstring)
		return $backtrace;
	
	$backtracestr = '';
	$sep = '';
	for($i = 0; $i < $backmax && isset($backtrace[$i]['func']); $i++){
		$backtracestr .= $sep.$backtrace[$i]['func'];
		$sep = ', ';
	}
	return $backtracestr;
}


//------------------------------------------
// filter all utf8 strings in array and sub-arrays
//------------------------------------------
function filterUtf8(&$data){
	if(is_string($data)){
		$data = tm_substr($data);
	}elseif(is_array($data)){
		foreach(array_keys($data) as $key){
			filterUtf8($data[$key]);
		}
	}
}


//------------------------------------------
global $_utf8_BOM;
// utf8 BOM is sometimes used to indicate that a string is utf8, the sequence is EFBBBF (239,187,191)
// ( see http://en.wikipedia.org/wiki/Byte-order_mark )
$_utf8_BOM = chr(0xEF).chr(0xBB).chr(0xBF);
	
//------------------------------------------
// utf-8 tm substr function (only for $start>=0)
// Multibyte and tm colors substring, can also bu used as an utf8 filter.
// This function will remove bad utf8 characters (replacing them with spaces)
// Use it for all user created strings (mainly nickname and maps name) when you
// use them in requests to server (and Dedimania)
//------------------------------------------
// For 3 bytes utf8 chars it will be asked to isValidUTF8() if TM accept it
//------------------------------------------
// set $TMcodes to false to not count TM colors sequences
// (anyway $TMcodes will default to false if no substring is wanted)
// set $stripBOM to true to remove the eventual utf8 BOM
//------------------------------------------
// Written by Slig
//------------------------------------------
function tm_substr($str, $start=0, $length=10000000, $TMcodes=true, $stripBOM=false){
	if(!is_string($str))
		$str .= ''; // convert to string
	if($stripBOM){
		global $_utf8_BOM;
		$str = str_replace($_utf8_BOM,'',$str); // remove utf8 BOM if any
	}
	if($start == 0 && $length == 10000000)
		$TMcodes = false; // if don't ask substring then do not count TM non visible codes

	$s = strlen($str); // byte string length
	$st = -1; // at which pos start the substring
	$len = 0; // current real length of the substring
	$n = 0; // current real char pos in string
	$pos = 0; // current byte pos in string
	$dolln = 0; // how many chars which should not be counted because special TM not visibles
		
	while($pos < $s && $len < $length){
		$c = $str[$pos];
		$co = ord($c);
			
		// if not start pos and should be, then set it
		if($st < 0 && $n >= $start)
			$st = $pos;
			
		if($TMcodes && $c == '$'){
			// After '$' : TM special char
			////echo "'$' at $pos (".$str[$pos+1].','.ord($str[$pos+1]).")\n";
			$dolln = 0;
			$pos++;

			if($pos < $s){
				$c = $str[$pos];
					
				if($c == '$'){
					// '$$'
					$pos++;
					// it was 1 character, increase counters
					$n++;
					if($st >= 0)
						$len++;
						
				}elseif(($c >= '0' && $c <= '9') || ($c >= 'A' && $c <= 'F') || ($c >= 'a' && $c <= 'f')){
					// 1st hex num in TM color, no visible char
					////echo "'\$$c' num at $pos\n";
					$pos++;
					$dolln = 2; // 2 next chars will not be counted
						
				}else{
					// whatever other char it is, it is a TM not visible char
					$dolln = 1; // next char will not be counted
				}
			}
				
			/* 			// replaced to fit real dedicated utf8 support ! (see below) */
			/* 		}elseif($co >= 245){  */
			/* 			// (dedicated xmlrpc error: -503, UCS-4 characters not supported) */
			/* 			// bad 1st multibyte char value (11111xxx), or non utf8 extended ascii value */
			/* 			// or restricted 4,5 or 6 bytes sequence : not supported in TM ! */
			/* 			$str[$pos] = ' '; // replace bad value with space */
			/* 			$pos++; */
			/* 			// it was 1 character, increase counters */
			/* 			$n++; */
			/* 			if($st >= 0) */
			/* 				$len++; */
				
			/* 			// replaced to fit real dedicated utf8 support ! (see below) */
			/* 		}elseif($co >= 240){ // 4 bytes utf8 => 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx */
			/* 			// (dedicated xmlrpc error: -503, UCS-4 characters not supported in TM) */
			/* 			if(($pos+3 < $s ) && */
			/* 				 (ord($str[$pos+1]) >= 128) && (ord($str[$pos+1]) < 192) && */
			/* 				 (ord($str[$pos+2]) >= 128) && (ord($str[$pos+2]) < 192) && */
			/* 				 (ord($str[$pos+3]) >= 128) && (ord($str[$pos+3]) < 192)){ */
			/* 				// ok, it was 1 character, increase counters */
			/* 				$pos += 4; */
			/* 			}else{ */
			/* 				// bad multibyte char */
			/* 				$str[$pos] = ' '; */
			/* 				$pos++; */
			/* 			} */
			/* 			// it was 1 character, increase counters */
			/* 			$n++; */
			/* 			if($st >= 0) */
			/* 				$len++; */
				
		}elseif($co >= 240){
			// (dedicated xmlrpc error for 240/253: -503, UCS-4 characters not supported in TM)
			// (dedicated xmlrpc error for 254: -503, Invalid UTF-8 initial byte)
			$str[$pos] = ' '; // replace bad value with space
			$pos++;
			// it was 1 character, increase counters
			if($dolln <= 0){
				$n++;
				if($st >= 0)
					$len++;
			}else{
				$dolln--;
			}
				
		}elseif($co >= 224){ // 3 bytes utf8 => 1110xxxx 10xxxxxx 10xxxxxx
			if(($pos+2 < $s ) &&
				 (ord($str[$pos+1]) >= 128) && (ord($str[$pos+1]) < 192) &&
				 (ord($str[$pos+2]) >= 128) && (ord($str[$pos+2]) < 192)){
				// (dedicated xmlrpc error when not supported: -503, Overlong UTF-8 sequence not allowed in TM)
				// ask to dedicated or tm_utf8.phpser file values :
				if(isValidUTF8($c.$str[$pos+1].$str[$pos+2])){
					// ok, it was 1 character, increase counters
					$pos += 3;
				}else{
					// bad multibyte char
					$str[$pos] = ' ';
					$pos++;
				}
			}else{
				// bad multibyte char (dedicated xmlrpc error: -503, UTF-8 sequence too short)
				$str[$pos] = ' ';
				$pos++;
			}
			// it was 1 character, increase counters
			if($dolln <= 0){
				$n++;
				if($st >= 0)
					$len++;
			}else{
				$dolln--;
			}
				
		}elseif($co >= 194){ // 2 bytes utf8 => 110xxxxx 10xxxxxx
			// (all 2 bytes utf-8 are supported by dedicated)
			if(($pos+1 < $s ) &&
				 (ord($str[$pos+1]) >= 128) && (ord($str[$pos+1]) < 192)){
				$pos += 2;
			}else{
				// bad multibyte char 
				// (dedicated xmlrpc error: -503, UTF-8 sequence too short)
				$str[$pos] = ' ';
				$pos++;
			}
			// it was 1 character, increase counters
			if($dolln <= 0){
				$n++;
				if($st >= 0)
					$len++;
			}else{
				$dolln--;
			}
				
		}elseif($co >= 192){ // overlong 2 bytes encoding : unsupported
			// (dedicated xmlrpc error: -503, Overlong UTF-8 sequence not allowed)
			// bad 1st multibyte char value (1100000x)
			$str[$pos] = ' '; // replace bad value with space
			$pos++;
			// it was 1 character, increase counters
			if($dolln <= 0){
				$n++;
				if($st >= 0)
					$len++;
			}else{
				$dolln--;
			}
				
		}else{
			// ascii char or erroneus middle multibyte char
			if($co >=128 || $co == 0) // (dedicated xmlrpc error: -503, Invalid UTF-8 initial byte)
				// (can also happen because of a previously removed bad byte)
				$str[$pos] = ' '; // replace bad value with space
			$pos++;
				
			// it was 1 character, increase counters
			if($dolln <= 0){
				$n++;
				if($st >= 0)
					$len++;
			}else{
				$dolln--;
			}
		}
	}
	if($st >= 0)
		return substr($str,$st,$pos-$st);
	return '';
}


//------------------------------------------
// if utf8 list was loaded, then empty list and put in learn mode
//------------------------------------------
function learnUtf8(){
	global $_tm_utf8,$_tm_utf8add;
	if(!$_tm_utf8add){
		$_tm_utf8add = true;
		$_tm_utf8 = array();
	}
}


//------------------------------------------
// used by tm_substr to test if 3 bytes utf8 chars are supported by the dedicated !
//------------------------------------------
// if the file tm_utf8.phpser is available then use it instead of testing by sending requests to dedicated
// note: tm_utf8.phpser have to contain all invalid 3 bytes combinaisons, and be an array('utf8:XXX:utf8'=>false , ... )
//------------------------------------------
function isValidUTF8($utf8char){
	global $_tm_utf8,$_tm_utf8add,$_client,$_debug;
	// init case
	if(!isset($_tm_utf8)){
		if(file_exists('tm_utf8.phpser')){
			$data = file_get_contents('tm_utf8.phpser');
			$_tm_utf8 = unserialize($data);
			if(!is_array($_tm_utf8))
				$_tm_utf8 = array();
			else
				console("isValidUTF8:: tm_utf8.phpser file was found : use it to consider valid utf8 chars !");
		}else{
			$_tm_utf8 = array();
		}
		$_tm_utf8add = count($_tm_utf8) <= 0;
	}

	// build key
	$key = "utf8:{$utf8char}:utf8";

	// use loaded compatibility list
	if(!$_tm_utf8add){
		if(array_key_exists($key,$_tm_utf8) && !$_tm_utf8[$key])
			return false;
		return true;
	}

	// else test and add when asked
	if(!array_key_exists($key,$_tm_utf8)){
		// unknown : send a request to dedicated to verify !
		if(@$_client->query('TestUtf8Char',$key)){
			// should never happen (unless TestUtf8Char method exists on dedicated...)
			$_tm_utf8[$key] = true;
			if($_debug>0) console("isValidUTF8:: utf8: {$utf8char} is accepted!?");
		}else{
			$errcode = $_client->getErrorCode();
			if($errcode == -506){
				// unknow method error code : the uft8 code was accepted !
				$_tm_utf8[$key] = true;
				if($_debug>0) console("isValidUTF8:: utf8: {$utf8char} is accepted.");
			}elseif($errcode == -503){
				// error message about utf8 : the uft8 code was rejected !
				$_tm_utf8[$key] = false;
				if($_debug>0) console("isValidUTF8:: utf8: {$utf8char} is rejected!");
			}else{
				// other error message (should not happen) : return false without storing it !
				if($_debug>0) console("isValidUTF8:: bad response: ".$_client->getErrorCode().", ".$_client->getErrorMessage());
				return false;
			}
		}
	}
	return $_tm_utf8[$key];
}


//------------------------------------------
// multibyte substring
// WARNING: don't use this function ! use tm_substr instead !!!
//------------------------------------------
if(function_exists('mb_substr')){
	// if multibyte functions are avaible then set default to utf-8
	mb_internal_encoding('UTF-8');

}else{
	// else define a replacement mb_substr

	// utf-8 substr function, only for $start>=0
	function mb_substr($str, $start, $length=100000){
		$s = strlen($str);
		$st = -1;
		$len = 0;
		$n = 0;
		for($pos=0;$pos<$s && $len<$length;$pos++){
			$co = ord($str[$pos]);
			if($co < 128 || $co >= 192){
				// it's a single byte char or first multibyte char
				if($st>=0){
					$len++;
					if($len>=$length)
						break;
				}elseif($n == $start)
				 $st = $pos;
				$n++;
			}
		}
		if($st>=0)
			return substr($str,$st,$pos-$st);
		return '';
	}

}


//------------------------------------------
// copy file to a file (possibly ftp url)
//------------------------------------------
function file_copy($sourcefilename,$destfilename,$addcall=null) {
	global $_debug;

	if(substr($destfilename,0,6) == 'ftp://'){
		//$f=@fopen($destfilename,"w",false,stream_context_create(array('ftp'=>array('overwrite'=>true))));
		$ftptab = explode('/',$destfilename,4);
		print_r($ftptab);
		if(count($ftptab)<4){
			console("file_write - bad ftp name : $destfilename");
			return false;
		}
		$ftptab2 = explode('@',$ftptab[2]);
		$ftp_file = $ftptab[3];
		print_r($ftptab2);
		if(count($ftptab2)<2){
			$ftp_host = $ftptab[2];
			$ftp_user = 'ftp';
			$ftp_pass = 'ftp@fast';
		}else{
			$ftp_host = $ftptab2[1];
			$ftptab3 = explode(':',$ftptab2[0]);
			print_r($ftptab3);
			if(count($ftptab3)<2){
				$ftp_user = $ftptab2[0];
				$ftp_pass = '';
			}else{
				$ftp_user = $ftptab3[0];
				$ftp_pass = $ftptab3[1];
			}
		}
		if($_debug>2) console("Try ftp connection as $ftp_user@$ftp_host");
		$ftp_id = @ftp_connect($ftp_host,21,10);
		if($ftp_id !== false){
			if(@ftp_login($ftp_id, $ftp_user, $ftp_pass)){
				if($_debug>2) console("Connected as $ftp_user@$ftp_host");
				if(ftp_put($ftp_id, $ftp_file, $sourcefilename, FTP_BINARY) !== false){
					
					// addcall
					if($addcall !== null){
						$action = array_shift($addcall);
						addCallArray($action,$addcall);
					}
					return true;
				}
			}
			ftp_close($ftp_id);
		}
		debugPrint("file_write - ftp($ftp_user@$ftp_host): error",error_get_last());
		return false;

	}else{
		$datas = file_get_contents($sourcefilename);
		if($datas !== false){
			$f=@fopen($destfilename,"wb");
			if ($f !== false){
				$len = strlen($datas);
				$nb = fwrite($f,$datas,$len);
				if($nb<$len)
					console("file_write - wrote $nb/$len !!!");
				fclose($f);

				// addcall
				if($addcall !== null){
					$action = array_shift($addcall);
					addCallArray($action,$addcall);
				}
				return $nb;
			}
		}
		debugPrint("file_write - error",error_get_last());
		return false;
	}
}


//------------------------------------------
// unpack a .zip file (in current dir)
//------------------------------------------
function unpackZip($file) {

	if(!function_exists('zip_open')){
		// on windows give up, on linux try using unzip command 
		if(!isset($_SERVER['windir'])){
			$output = array();
			$unzipcmd = "unzip -o $file";
			$res = @exec($unzipcmd.' 2>&1',$output,$retval);
			if($res !== false && $retval == 0){
				return true;
			}else{
				console("*** can't unzip: module php_zip missing and '$unzipcmd' failed ($retval) *******\n"
								.implode("\n",$output)
								."\n*******************************************************************************");
				return false;
			}

		}else{
			console("Can't unzip: the module php_zip is missing !");
			return false;
		}

	}elseif($zip = zip_open($file)) {
		while ($zip_entry = zip_read($zip)) {
			if (zip_entry_open($zip,$zip_entry,"r")) {
				$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
				$name = zip_entry_name($zip_entry);
				if($name[strlen($name)-1] != '/'){
					$dirname = dirname($name);
					if($dirname != '.' && !file_exists($dirname))
						@mkdir($dirname,0777,true);
					@file_put_contents($name,$buf);
				}
				zip_entry_close($zip_entry);

			}else{
				console('Error reading part of '.$file);
				zip_close($zip);
				return false;
			}
		}
		zip_close($zip);
		return true;
	}
	return false;
}


//------------------------------------------
// global vars for team infos and color
//------------------------------------------
global $_team_id,$_team_color, $_teamcolor;

// id for team nicknames, old dedicated and game server (game server names are localized !)
$_team_id[0] = 0;
$_team_id[1] = 1;
$_team_id['Blue'] = 0;
$_team_id['Red'] = 1;
$_team_id['Equipe Bleue'] = 0;
$_team_id['Equipe Rouge'] = 1;

// color used for teams
$_team_color[-1] = '$bcb';
$_team_color[0] = '$55e'; // blue
$_team_color[1] = '$e55'; // red
$_team_color[2] = '$5e5'; // green


// team colors (dedicated server names)
$_teamcolor[0] = '$00f'; // blue
$_teamcolor[1] = '$f00'; // red
$_teamcolor[2] = '$0f0'; // green
$_teamcolor[3] = '$0ff'; // cyan
$_teamcolor[4] = '$f0f'; // magenta
$_teamcolor[5] = '$ff0'; // yellow
$_teamcolor[6] = '$b50'; // orange
$_teamcolor[7] = '$5b0'; // green apple
$_teamcolor[8] = '$b05'; // pink
$_teamcolor[9] = '$50b'; // violet
$_teamcolor[10]= '$05b'; // blue cyan light
$_teamcolor[11]= '$0b5'; // green cyan light
$_teamcolor[12]= '$aaa'; // grey
$_teamcolor[13]= '$faa'; // red light
$_teamcolor[14]= '$afa'; // green light
$_teamcolor[15]= '$aaf'; // blue light
$_teamcolor[16]= '$800'; // red dark
$_teamcolor[17]= '$080'; // green dark
$_teamcolor[18]= '$008'; // blue dark
$_teamcolor[19]= '$666'; // grey dark
$_teamcolor[20]= '$888'; // grey dark
$_teamcolor[21]= '$aaa'; // grey dark
$_teamcolor[22]= '$777'; // grey dark
$_teamcolor[23]= '$999'; // grey dark
$_teamcolor[24]= '$bbb'; // grey dark
$_teamcolor['Blue'] = '$00f';
$_teamcolor['Red'] = '$f00';
// team colors (game server names are localized !)
$_teamcolor['Equipe Bleue'] = '$00f';
$_teamcolor['Equipe Rouge'] = '$f00';

foreach($_teamcolor as $n => $col){
	$_teambgcolor[$n] = substr($col,1);
}

// order of map envirs in shuffle
$_envir_order = array('Island'=>1,'Rally'=>2,'Bay'=>3,'Alpine'=>4,'Coast'=>5,'Stadium'=>6,'Speed'=>7);

$_envirs = array('Stadium'=>'Stadium',
								 'stadium'=>'Stadium',
								 'Coast'=>'Coast',
								 'coast'=>'Coast',
								 'Bay'=>'Bay',
								 'bay'=>'Bay',
								 'Island'=>'Island',
								 'island'=>'Island',
								 'Rally'=>'Rally',
								 'rally'=>'Rally',
								 'Desert'=>'Speed',
								 'desert'=>'Speed',
								 'Speed'=>'Speed',
								 'speed'=>'Speed',
								 'Snow'=>'Alpine',
								 'snow'=>'Alpine',
								 'Alpine'=>'Alpine',
								 'alpine'=>'Alpine');

// SetServerOptions: template and parametters types
$_SetServerOptions = array('Name'=>'-', 'Comment'=>'', 'Password'=>'', 'PasswordForSpectator'=>'', 'RefereePassword'=>'', 'NextMaxPlayers'=>1, 'NextMaxSpectators'=>1, 'IsP2PUpload'=>false, 'IsP2PDownload'=>false, 'NextLadderMode'=>0, 'NextVehicleNetQuality'=>0, 'NextCallVoteTimeOut'=>0, 'CallVoteRatio'=>0.0, 'AllowChallengeDownload'=>true, 'AutoSaveReplays'=>false, 'RefereeMode'=>0, 'AutoSaveValidationReplays'=>false, 'HideServer'=>false, 'UseChangingValidationSeed'=>false);

// SetGameInfos: template and parametters types
$_SetGameInfos = array('GameMode'=>0, 'ChatTime'=>0, 'RoundsPointsLimit'=>0, 'RoundsUseNewRules'=>false, 'RoundsForcedLaps'=>0, 'TimeAttackLimit'=>0, 'TimeAttackSynchStartPeriod'=>0, 'TeamPointsLimit'=>0, 'TeamMaxPoints'=>0, 'TeamUseNewRules'=>true, 'LapsNbLaps'=>1, 'LapsTimeLimit'=>0, 'FinishTimeout'=>0, 'AllWarmUpDuration'=>0, 'DisableRespawn'=>false, 'ForceShowAllOpponents'=>0, 'RoundsPointsLimitNewRules'=>0, 'TeamPointsLimitNewRules'=>0, 'CupPointsLimit'=>0, 'CupRoundsPerChallenge'=>0, 'CupNbWinners'=>0, 'CupWarmUpDuration'=>0);

// ServerOptions: xml tag name -> xmlrpc names
$_ConvServerOptions = array(
	'name' => 'Name',
	'comment' => 'Comment',
	'hide_server' => 'HideServer',
	'max_players' => 'MaxPlayers',
	'password' => 'Password',
	'max_spectators' => 'MaxSpectators',
	'password_spectator' => 'PasswordForSpectator',
	'ladder_mode' => 'LadderMode',
	'enable_p2p_upload' => 'IsP2PUpload',
	'enable_p2p_download' => 'IsP2PDownload',
	'callvote_timeout' => 'CallVoteTimeOut',
	'callvote_ratio' => 'CallVoteRatio',
	'allow_challenge_download' => 'AllowChallengeDownload',
	'autosave_replays' => 'AutoSaveReplays',
	'autosave_validation_replays' => 'AutoSaveValidationReplays',
	'referee_password' => 'RefereePassword',
	'referee_validation_mode' => 'RefereeMode',
	'use_changing_validation_seed' => 'UseChangingValidationSeed'
);

// GameInfos: xml tag name -> xmlrpc names
$_ConvGameInfos = array(
	'game_mode' => 'GameMode',
	'chat_time' => 'ChatTime',
	'finishtimeout' => 'FinishTimeout',
	'allwarmupduration' => 'AllWarmUpDuration',
	'disablerespawn' => 'DisableRespawn',
	'forceshowallopponents' => 'ForceShowAllOpponents',
	'rounds_pointslimit' => 'RoundsPointsLimit',
	'rounds_usenewrules' => 'RoundsUseNewRules',
	'rounds_forcedlaps' => 'RoundsForcedLaps',
	'rounds_pointslimitnewrules' => 'RoundsPointsLimitNewRules',
	'team_pointslimit' => 'TeamPointsLimit',
	'team_maxpoints' => 'TeamMaxPoints',
	'team_usenewrules' => 'TeamUseNewRules',
	'team_pointslimitnewrules' => 'TeamPointsLimitNewRules',
	'timeattack_limit' => 'TimeAttackLimit',
	'timeattack_synchstartperiod' => 'TimeAttackSynchStartPeriod',
	'laps_nblaps' => 'LapsNbLaps',
	'laps_timelimit' => 'LapsTimeLimit',
	'cup_pointslimit' => 'CupPointsLimit',
	'cup_roundsperchallenge' => 'CupRoundsPerChallenge',
	'cup_nbwinners' => 'CupNbWinners',
	'cup_warmupduration' => 'CupWarmUpDuration',
	);


$_async_forced_methods = array(
	'ChatSendServerMessage',
	'ChatSendServerMessageToLanguage',
	'ChatSendServerMessageToId',
	'ChatSendServerMessageToLogin',
	'ChatSend',
	'ChatSendToLanguage',
	'ChatSendToLogin',
	'ChatSendToId',
	'ChatForwardToLogin',
	'SendNotice',
	'SendNoticeToId',
	'SendNoticeToLogin',
	'SendDisplayManialinkPage',
	'SendDisplayManialinkPageToId',
	'SendDisplayManialinkPageToLogin',
	'SendHideManialinkPage',
	'SendHideManialinkPageToId',
	'SendHideManialinkPageToLogin',
	'WriteFile',
	'TunnelSendDataToId',
	'TunnelSendDataToLogin',
	'Echo',
	//'GetValidationReplay',
	);


// list of methods which send info to players or change their status
// which of course can't be done on relay servers for relayed players
// value is the player login/id argument number, >0 if login and <0 if id
// (1/-1 for 1st argument after the methodname)
$_toplayer_methods = array(
													 'ChatSendServerMessageToId' => -2,
													 'ChatSendServerMessageToLogin' => 2,
													 'ChatSendToLogin' => 2,
													 'ChatSendToId' => -2,
													 //'ChatForwardToLogin' => 3,
													 'SendNoticeToId' => -1,
													 'SendNoticeToLogin' => 1,
													 'SendDisplayManialinkPageToId' => -1,
													 'SendDisplayManialinkPageToLogin' => 1,
													 'SendHideManialinkPageToId' => -1,
													 'SendHideManialinkPageToLogin' => 1,
													 'SetBuddyNotification' => 1,
													 'GetBuddyNotification' => 1,
													 'ForcePlayerTeam' => 1,
													 'ForcePlayerTeamId' => -1,
													 'ForceSpectator' => 1,
													 'ForceSpectatorId' => -1,
													 'ForceSpectatorTarget' => 1,
													 'ForceSpectatorTargetId' => -1,
													 );
// Player specifi but usable on relayed players :
//'GetPlayerInfo'=>1,
//'GetDetailedPlayerInfo'=>1,


// method which use a text argument
// (0 for 1st argument after methodname)
$_methods_textparam = array(
	'ChatSendServerMessage' => 0,
	'ChatSendServerMessageToId' => 0,
	'ChatSendServerMessageToLogin' => 0,
	'ChatSend' => 0,
	'ChatSendToLogin' => 0,
	'ChatSendToId' => 0,
	'ChatForwardToLogin' => 0,
	'SendNotice' => 0,
	'SendNoticeToId' => 1,
	'SendNoticeToLogin' => 1,
	'SendDisplayManialinkPage' => 0,
	'SendDisplayManialinkPageToId' => 1,
	'SendDisplayManialinkPageToLogin' => 1,
	'WriteFile' => 1,
	'Echo' => 0,
);

?>
