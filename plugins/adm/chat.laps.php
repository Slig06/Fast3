<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      18.05.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('laps','/laps nblaps [number] , timelimit [number ms] , warmupduration [number] , fwarmupduration [number] , ftimeout [number]',true);

//------------------------------------------
// Laps Commands
//------------------------------------------
function chat_laps($author, $login, $params){
	global $_GameInfos,$_NextGameInfos,$_FWarmUpDuration;

	// verify if author is in admin list
	if(!verifyAdmin($login))
		return;

	// NbLaps
	if(isset($params[0]) && ($params[0]=='nblaps' || $params[0]=='nb' || $params[0]=='laps')){
		if(isset($params[1])) {
			addCall($login,'SetNbLaps',$params[1]+0);

			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Laps NbLaps to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['LapsNbLaps'];
			$val2 = $_NextGameInfos['LapsNbLaps'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'NbLaps: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
			
		// 
	}elseif(isset($params[0]) && ($params[0]=='timelimit' || $params[0]=='limit' || $params[0]=='time')){
		if(isset($params[1])) {
			$time = $params[1]+0;
			if($time<10000)
				$time *= 1000;
			addCall($login,'SetLapsTimeLimit',$time);

			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Laps TimeLimit to '.localeText(null,'highlight').$time.' (ms) !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['LapsTimeLimit'];
			$val2 = $_NextGameInfos['LapsTimeLimit'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'TimeLimit: '.$val.' next: '.$val2;
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

		// FinishTimeout
	}elseif(isset($params[0]) && ($params[0]=='ftimeout' || $params[0]=='fto' || $params[0]=='finishtimeout')){
		if(isset($params[1]) && $params[1]!='') {
			$ftimeout = $params[1]+0;
			if($ftimeout>1 && $ftimeout<5000)
				$ftimeout *= 1000;
			addCall($login,'SetFinishTimeout',$ftimeout);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting FinishTimeout to '.localeText(null,'highlight').$ftimeout.' (ms) !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_GameInfos['FinishTimeout'];
			$val2 = $_NextGameInfos['FinishTimeout'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'FinishTimeout: '.$val.' next: '.$val2.' [timeout to finish after first (ms), 0=default, 1=adaptative]';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

	// help
	}else{
		$msg = localeText(null,'server_message') . localeText(null,'interact').'/laps nblaps [number] , timelimit [number ms] , warmupduration [number] , fwarmupduration [number] , ftimeout [number]';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}

?>


