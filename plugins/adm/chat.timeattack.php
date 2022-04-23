<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      08.04.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('ta','/ta timelimit [number ms] , sync [number ms] , warmupduration [number], fwarmupduration [number]',true);
registerCommand('timeattack','/ta timelimit [number ms] , sync [number ms] , warmupduration [number], fwarmupduration [number]',true);


//------------------------------------------
// TimeAttack Commands
//------------------------------------------
function chat_timeattack($author, $login, $params){
	chat_ta($author, $login, $params);
}

//------------------------------------------
// TimeAttack Commands
//------------------------------------------
function chat_ta($author, $login, $params){
	global $_GameInfos,$_NextGameInfos,$_FWarmUpDuration;
	
	// verify if author is in admin list
	if(!verifyAdmin($login))
		return;
	
	// TimeAttackLimit
	if(isset($params[0]) && ($params[0]=='timelimit' || $params[0]=='time' || $params[0]=='limit')){
		if(isset($params[1])) {
			$time = $params[1]+0;
			if($time<10000)
				$time *= 1000;
			addCall($login,'SetTimeAttackLimit',$time);

			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting TA TimeLimit to '.localeText(null,'highlight').$time.' (ms) !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['TimeAttackLimit'];
			$val2 = $_NextGameInfos['TimeAttackLimit'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'TA TimeLimit: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
		// TimeAttackSynchStartPeriod
	}elseif(isset($params[0]) && ($params[0]=='sync' || $params[0]=='syncperiod')){
		if(isset($params[1])) {
			$time = $params[1]+0;
			if($time<500)
				$time *= 1000;
			addCall($login,'SetTimeAttackSynchStartPeriod',$time);

			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting TA SynchStartPeriod to '.localeText(null,'highlight').$time.' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['TimeAttackSynchStartPeriod'];
			$val2 = $_NextGameInfos['TimeAttackSynchStartPeriod'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'TA SynchStartPeriod: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

    // warmup duration
  }elseif(isset($params[0]) && ($params[0]=='warmupduration' || $params[0]=='wuduration' || $params[0]=='wud' || $params[0]=='wu')){
    if(isset($params[1])) {
      addCall($login,'SetAllWarmUpDuration',$params[1]+0);

      // send success message
      $msg = localeText(null,'server_message') . localeText(null,'interact').'Setting All WarmUpDuration to '.localeText(null,'highlight').$params[1].' !';
      // send message to user who wrote command
      addCall(null,'ChatSendToLogin', $msg, $login);

    }else{
      $val = $_GameInfos['AllWarmUpDuration'];
      $val2 = $_NextGameInfos['AllWarmUpDuration'];
      $msg = localeText(null,'server_message') . localeText(null,'interact').'WarmUpDuration: '.$val.' next: '.$val2;
      // send message to user who wrote command
      addCall(null,'ChatSendToLogin', $msg, $login);
    }

    // fwarmup duration
  }elseif(isset($params[0]) && ($params[0]=='fwarmupduration' || $params[0]=='fwuduration' || $params[0]=='fwud' || $params[0]=='fwu')){
    if(isset($params[1])) {
			$_FWarmUpDuration = $params[1]+0;
      addCall($login,'SetAllWarmUpDuration',0);

      // send success message
      $msg = localeText(null,'server_message') . localeText(null,'interact').'Setting FWarmUpDuration to '.localeText(null,'highlight').$params[1].' !';
      // send message to user who wrote command
      addCall(null,'ChatSendToLogin', $msg, $login);

    }else{
      $val = $_FWarmUpDuration;
      $msg = localeText(null,'server_message') . localeText(null,'interact').'FWarmUpDuration: '.$val;
      // send message to user who wrote command
      addCall(null,'ChatSendToLogin', $msg, $login);
    }

		// help
	}else{
		$msg = localeText(null,'server_message') . localeText(null,'interact').'/ta timelimit [number ms] , sync [number ms] , warmupduration [number] , fwarmupduration [number]';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}

?>
