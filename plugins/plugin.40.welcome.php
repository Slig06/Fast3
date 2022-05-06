<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      04.11.2010
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// function registerPlugin($plugin,$priority=50)
// 
//
registerPlugin('welcome',40);



// -----------------------------------------------------------
// Customize these values in fast.php !
// -----------------------------------------------------------

// The 'Connection: ...' message will be display if less players
// than this value are on the server (set 0 to disable message)
// $_welcome_connect = 50;

// The 'Disconnection: ...' message will be display if less players
// than this value are on the server (set 0 to disable message)
// $_welcome_disconnect = 50;



//------------------------------------------
// Init
//------------------------------------------
function welcomeInit($event){
	global $_welcome_connect,$_welcome_disconnect;

	if(!isset($_welcome_connect))
		$_welcome_connect = 50;
	if(!isset($_welcome_disconnect))
		$_welcome_disconnect = 50;
}


//------------------------------------------
// PlayerConnect : generic welcome message
//------------------------------------------
function welcomePlayerConnect_Reverse($event,$login){
	global $_debug,$_players,$_FASTver,$_players_actives,$_players_spec,$_welcome_connect,$_init,$_is_relay;
	$msg = '';
	if(isset($_players[$login]['Country'])) $msg .= "{$_players[$login]['Country']},";
	if(isset($_players[$login]['Language'])) $msg .= "{$_players[$login]['Language']},";
	if(isset($_players[$login]['PlayerInfo']['IPAddress'])) $msg .= "{$_players[$login]['PlayerInfo']['IPAddress']},";
	if(isset($_players[$login]['PlayerInfo']['ClientVersion'])) $msg .= "{$_players[$login]['PlayerInfo']['ClientVersion']}";
	console("welcomePlayerConnect({$login}):: {$msg}");
	//debugPrint("welcomePlayerConnect({$login}):: ",$_players[$login]);
	if($_init)
		return;
	if(@$_players[$login]['Quiet'])
		return;

	// send welcome message to player
	$msg = localeText(null,'server_message').localeText($login,'welcome.message',$_FASTver,$_players[$login]['NickName']);
	addCall(null,'ChatSendServerMessageToLogin', $msg, $login);

	if($_players_actives+$_players_spec < $_welcome_connect){
		// send connect message to all
		
		$path_string = '';
		if(isset($_players[$login]['Path'])){
			$path_array = explode("|",$_players[$login]['Path']);
			if(count($path_array)>1){
				$path_string = ', $n'.$path_array[1];
				if(isset($path_array[2]))
					$path_string .= '|'.$path_array[2];
				if(isset($path_array[3]))
					$path_string .= '|'.$path_array[3];
				$path_string .= '$m';
			}
		}elseif(isset($_players[$login]['Nation'])){
			$path_string = ', $n'.$_players[$login]['Nation'].'$m';
		}
		if(!$_is_relay || $_players[$login]['Relayed']){
			$msgarray = multiLocaleText(localeText(null,'server_message').localeText(null,'interact'),
																	array('welcome.connection'),
																	stripColors($_players[$login]['NickName']).' ('.stripColors($login).$path_string.')');
		}else{
			$msgarray = multiLocaleText(localeText(null,'server_message').localeText(null,'interact'),
																	array('welcome.relay.connection'),
																	stripColors($_players[$login]['NickName']).' ('.stripColors($login).$path_string.')');
		}
		addCall(null,'ChatSendServerMessageToLanguage',$msgarray);
		if($_debug>5) debugPrint("welcomePlayerConnect({$login}):: msgarray",$msgarray);
		//if($_debug>0) debugPrint("welcomePlayerConnect - _players[$login]",$_players[$login]);
	}
}


//------------------------------------------
// PlayerDisconnect : generic bye message
//------------------------------------------
function welcomePlayerDisconnect($event,$login){
	global $_debug,$_players,$_FASTver,$_players_actives,$_players_spec,$_welcome_disconnect,$_currentTime,$_init,$_is_relay;
	if(!isset($_players[$login]['Login']))
		return;

	console("welcomePlayerDisconnect({$login}):: ");
	if($_init)
		return;
	if(@$_players[$login]['Quiet']){
		console("welcomePlayerDisconnect({$login}):: {$login} is quiet");
		return;
	}

	// Disconnection (if ['ConnectionTime']==$_currentTime then was already inactive before event)
	//if((!isset($_players[$login]['ConnectionTime']) || $_players[$login]['ConnectionTime']==$_currentTime) &&
	if($_players_actives+$_players_spec < $_welcome_disconnect){

		if($_debug>1) console("welcomePlayerDisconnect({$login}):: Active={$_players[$login]['Active']}, {$_players[$login]['ConnectionTime']}, {$_currentTime}");
		// send disconnect message to all
		$path_string = '';
		if(isset($_players[$login]['Path'])){
			$path_array = explode("|",$_players[$login]['Path']);
			if(count($path_array)>1){
				$path_string = ', $n'.$path_array[1];
				if(isset($path_array[2]))
					$path_string .= '|'.$path_array[2];
				if(isset($path_array[3]))
					$path_string .= '|'.$path_array[3];
				$path_string .= '$m';
			}
		}elseif(isset($_players[$login]['Nation'])){
			$path_string = ', $n'.$_players[$login]['Nation'].'$m';
		}
		if(!$_is_relay || $_players[$login]['Relayed']){
			$msgarray = multiLocaleText(localeText(null,'server_message').localeText(null,'interact'),
																	array('welcome.disconnection'),
																	stripColors($_players[$login]['NickName']).' ('.stripColors($login).$path_string.')');
		}else{
			$msgarray = multiLocaleText(localeText(null,'server_message').localeText(null,'interact'),
																	array('welcome.relay.disconnection'),
																	stripColors($_players[$login]['NickName']).' ('.stripColors($login).$path_string.')');
		}
		addCall(null,'ChatSendServerMessageToLanguage',$msgarray);
		//if($_debug>5) debugPrint("welcomePlayerDisconnect - msgarray",$msgarray);
		//if($_debug>0) debugPrint("welcomePlayerConnect - _players[$login]",$_players[$login]);
	}
}


//------------------------------------------
// PlayerArrive : database configured welcome message
//------------------------------------------
function welcomePlayerArrive($event,$tm_db_n,$login){
	global $_tm_db,$_players;
  if(!is_string($login))
    $login = ''.$login;

	// send welcome message to player
	if(isset($_tm_db[$tm_db_n]['Welcome'])){
		$msg = localeText(null,'server_message').localeText($login,$_tm_db[$tm_db_n]['WelcomeTag']);
		addCall(null,'ChatSendServerMessageToLogin', $msg, $login);
	}
}


?>
