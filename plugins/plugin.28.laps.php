<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      20.07.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST2 Laps plugin : show lap & gaps infos in chat
// 
//
if(!$_is_relay) registerPlugin('laps',28,1.0);


//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function lapsInit(){
	global $_laps_bestlap_login,$_laps_bestlap_time;
	$_laps_bestlap_login = -1;
	$_laps_bestlap_time = -1;
}


//--------------------------------------------------------------
// BeginRace :
//--------------------------------------------------------------
function lapsBeginRace($event,$GameInfos){
	global $_laps_bestlap_login,$_laps_bestlap_time;

	$_laps_bestlap_login = -1;
	$_laps_bestlap_time = -1;
}


//--------------------------------------------------------------
// PlayerLap :
//--------------------------------------------------------------
function lapsPlayerLap($event,$login,$time,$lapnum,$checkpt){
	global $_debug,$_Game,$_laps_bestlap_login,$_laps_bestlap_time,$_players;
  if(!is_string($login))
    $login = ''.$login;
	//console("laps.Event[$event]('$login',$time,$lapnum,$checkpt)");
	if($_debug>4) debugPrint("lapsPlayerLap - _players[$login]",$_players[$login]);

	// best lap
	if($_laps_bestlap_time<0 || $time<$_laps_bestlap_time){
		$_laps_bestlap_login = $login;
		$_laps_bestlap_time = $time;
		$msg = '$z$i$s$dfd$nBest Lap$cc0> $fff$z'.$_players[$login]['NickName']
			.'$z$i$s$fff$n : '.MwTimeToString($time);
		addCall(null,'ChatSendServerMessage', $msg);
	}

}





?>
