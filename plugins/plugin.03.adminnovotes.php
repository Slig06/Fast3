<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      21.06.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// Automatically disable callvotes (kick/ban/next/restart) when some admin is here
// Test is done at begin round, player connect and player disconnect
// if no more admin or if no admin active for long time, then active votes again

registerPlugin('adminnovotes',4);


// Don't change values here ! Do it in fast.php !

// Automatic callvote enable/disable when admins are here or not
// The vote timeout value is in milliseconds.
// set a negative value to activate automatic feature
// To change value in game, use : /adm votetimeout xxx
//$_CallVoteTimeOut = -60000;

// Delay of no admin active to activate votes (default 10minutes)
//$_adminnovotes_delay = 600000;



//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function adminnovotesInit($event){
	global $_debug,$_CallVoteTimeOut,$_adminnovotes_delay;
	
	if(!isset($_CallVoteTimeOut))
		$_CallVoteTimeOut = -60000;

	else{
		$vtime = $_CallVoteTimeOut<0 ? 0 : $_CallVoteTimeOut;
		addCall(null,'SetCallVoteTimeOut',$vtime);
	}

	if(!isset($_adminnovotes_delay))
		$_adminnovotes_delay = 600000; // 10min
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function adminnovotesVerifyAdmins(){
	global $_debug,$_players,$_CallVoteTimeOut,$_adminnovotes_delay,$_ServerOptions,$_currentTime;

	if($_ServerOptions['NextCallVoteTimeOut']>0){
		// verify if some admin, if yes disable votes
		foreach($_players as $login => &$pl){
			if($pl['Active'] && !$pl['Relayed'] && verifyAdmin(''.$login) && ($_currentTime - $pl['PlayerActionTime'] < $_adminnovotes_delay/2)){
				addCall(null,'SetCallVoteTimeOut',0);
				console("Active admin : disable callvotes !");
				ChatSendServerMessageToAdmins(localeText(null,'server_message').'CallVotes are now disabled (active admins).');
				break;
			}
		}
		
	}else{
		// verify if no admin active, else enable votes
		$nbadmin = 0;
		foreach($_players as $login => &$pl){
			if($pl['Active'] && !$pl['Relayed'] && verifyAdmin(''.$login) && ($_currentTime - $pl['PlayerActionTime'] < $_adminnovotes_delay))
				$nbadmin++;
		}
		if($nbadmin<=0){
			addCall(null,'SetCallVoteTimeOut',-$_CallVoteTimeOut);
			console("No active admin : enable callvotes !");
			ChatSendServerMessageToAdmins(localeText(null,'server_message').'CallVotes are now enabled (no active admin).');
		}
	}
}


//--------------------------------------------------------------
// PlayerConnect :
//--------------------------------------------------------------
function adminnovotesPlayerConnect($event,$login){
	global $_debug,$_CallVoteTimeOut,$_ServerOptions;

	if($_CallVoteTimeOut<0 && verifyAdmin(''.$login)){
		if($_ServerOptions['NextCallVoteTimeOut']>0){
			addCall(null,'SetCallVoteTimeOut',0);
			ChatSendServerMessageToAdmins(localeText(null,'server_message').'CallVotes are now disabled (active admin).');
		}
	}
}


//--------------------------------------------------------------
// PlayerConnect :
//--------------------------------------------------------------
function adminnovotesPlayerDisconnect($event,$login){
	global $_debug,$_CallVoteTimeOut,$_ServerOptions;

	if($_CallVoteTimeOut<0 && verifyAdmin($login)){
		if($_ServerOptions['NextCallVoteTimeOut']<=0){
			adminnovotesVerifyAdmins();
		}
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function adminnovotesBeginRound($event){
	global $_debug,$_CallVoteTimeOut;
	
	if($_CallVoteTimeOut<0)
		adminnovotesVerifyAdmins();
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function adminnovotesEvery5seconds($event){
	global $_debug,$_CallVoteTimeOut;
	
	if($_CallVoteTimeOut<0)
		adminnovotesVerifyAdmins();
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function adminnovotesEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_CallVoteTimeOut;
	
	if($_CallVoteTimeOut<0)
		adminnovotesVerifyAdmins();
}

?>
