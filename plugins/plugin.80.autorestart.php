<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      16.12.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// function registerPlugin($plugin,$priority=50)
// 
//
if(!$_is_relay) registerPlugin('autorestart',80,1.0);


// -----------------------------------------------------------
// Customize these values in fast.php !
// -----------------------------------------------------------

// Map autorestart , will autorestart automatically the map except
// if using /adm next (or a nextchallenge vote success while the podium)

// $_autorestart_map = false;


// Map autorestart , will autorestart automatically before playing
// when the map just changed.
// It can be used also to restart the new map later : set a numeric int value for
// a delay in seconds, 'checkpoint' to restart at first passed checkpoint, 'finish'
// to restart at first finish, 'round' to restart at end of first round, or 'on'
// for immediate.

// $_autorestart_newmap = false;




// --- other variables ---
function autorestartInit(){
	global $_autorestart_map,$_autorestart_newmap,$_autorestart_no,$_autorestart_uid;
	if(!isset($_autorestart_map))
		$_autorestart_map = false;
	if(!isset($_autorestart_newmap))
		$_autorestart_newmap = false;
	$_autorestart_no = false;
	$_autorestart_uid = '';
}


//------------------------------------------
function autorestartEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_PlayerList,$_autorestart_map,$_autorestart_no,$_autorestart_uid;

	$_autorestart_uid = $ChallengeInfo['UId'];
	if($_autorestart_map && !$_autorestart_no && count($_PlayerList)>0){
		addCall(null,'ChallengeRestart');
		$msg = localeText(null,'server_message').localeText(null,'interact').' Auto restarting the challenge !...';
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);
	}
	$_autorestart_no = false;
}


//------------------------------------------
// autorestart at begining of round
//------------------------------------------
function autorestartBeginChallenge($event,$ChallengeInfo,$GameInfos){
	global $_debug,$_autorestart_newmap,$_autorestart_no,$_autorestart_uid,$_players_actives;
	
	if($_autorestart_uid!='' && $_autorestart_uid!=$ChallengeInfo['UId']){
		$msg = '';

		if($_autorestart_newmap!==false && $_players_actives<=0){
			$_autorestart_uid = $ChallengeInfo['UId'];
			$msg = localeText(null,'server_message').localeText(null,'interact').' No player active: new map auto restart is disabled !';			
			return;
		}

		if($_autorestart_newmap===true || $_autorestart_newmap=='on'){
			$_autorestart_uid = $ChallengeInfo['UId'];
			$_autorestart_no = true;
			addCall(null,'ChallengeRestart');
			$msg = localeText(null,'server_message').localeText(null,'interact').' Auto restarting the challenge before real start !...';
		}elseif(is_numeric($_autorestart_newmap)){
			$_autorestart_newmap = $_autorestart_newmap+0;
			$msg = localeText(null,'server_message').localeText(null,'interact').' Will auto restart after '.$_autorestart_newmap.'s !';
		}elseif($_autorestart_newmap=='checkpoint' || $_autorestart_newmap=='check'){
			$msg = localeText(null,'server_message').localeText(null,'interact').' Will auto restart at first passed checkpoint !';
		}elseif($_autorestart_newmap=='finish'){
			$msg = localeText(null,'server_message').localeText(null,'interact').' Will auto restart at first finish !';
		}elseif($_autorestart_newmap!==false && $_autorestart_newmap!='no'){
			$msg = localeText(null,'server_message').localeText(null,'interact').' Will auto restart after first round !';
		}
		if($msg!=''){
			// send message in offical chat
			addCall(null,'ChatSendServerMessage', $msg);
		}
	}
}


//------------------------------------------
// autorestart after xx seconds
//------------------------------------------
function autorestartEverysecond($event,$seconds){
	global $_debug,$_autorestart_newmap,$_autorestart_no,$_autorestart_uid,$_players_round_time,$_currentTime,$_ChallengeInfo;

	if(is_numeric($_autorestart_newmap) && (($_currentTime-$_players_round_time)/1000 > $_autorestart_newmap+0) && 
		 $_autorestart_uid!='' && $_autorestart_uid!=$_ChallengeInfo['UId']){

		$_autorestart_uid = $_ChallengeInfo['UId'];
		$_autorestart_no = true;
		addCall(null,'ChallengeRestart');
		$msg = localeText(null,'server_message').localeText(null,'interact').' Auto restarting the challenge ('.$_autorestart_newmap.'s) !...';
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);
	}
}


//------------------------------------------
// autorestart at first passed checkpoint
//------------------------------------------
function autorestartPlayerCheckpoint($event,$login,$time,$lap,$checkpt){
	global $_debug,$_autorestart_newmap,$_autorestart_no,$_autorestart_uid,$_ChallengeInfo;

	if(($_autorestart_newmap=='checkpoint' || $_autorestart_newmap=='check') && $_autorestart_uid!='' && $_autorestart_uid!=$_ChallengeInfo['UId']){
		$_autorestart_uid = $_ChallengeInfo['UId'];
		$_autorestart_no = true;
		addCall(null,'ChallengeRestart');
		$msg = localeText(null,'server_message').localeText(null,'interact').' Auto restarting new challenge (player checkpoint) !...';
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);
	}
}


//------------------------------------------
// autorestart at first finish
//------------------------------------------
function autorestartPlayerFinish($event,$login,$time){
	global $_debug,$_autorestart_newmap,$_autorestart_no,$_autorestart_uid,$_ChallengeInfo;

	if($_autorestart_newmap=='finish' && ($time+0 > 0) &&
		 $_autorestart_uid!='' && $_autorestart_uid!=$_ChallengeInfo['UId']){
		$_autorestart_uid = $_ChallengeInfo['UId'];
		$_autorestart_no = true;
		addCall(null,'ChallengeRestart');
		$msg = localeText(null,'server_message').localeText(null,'interact').' Auto restarting new challenge (player finish) !...';
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);
	}
}


//------------------------------------------
// autorestart after first round
//------------------------------------------
function autorestartEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_debug,$_autorestart_newmap,$_autorestart_no,$_autorestart_uid;
	if($SpecialRestarting)
		return;

	if($_autorestart_newmap!==false && $_autorestart_newmap!=='no' && $_autorestart_uid!='' && $_autorestart_uid!=$ChallengeInfo['UId']){
		$_autorestart_uid = $ChallengeInfo['UId'];
		$_autorestart_no = true;
		addCall(null,'ChallengeRestart');
		$msg = localeText(null,'server_message').localeText(null,'interact').' Auto restarting new challenge (end of round) !...';
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);
	}
}

?>
