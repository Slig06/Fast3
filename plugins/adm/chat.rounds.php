<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      19.05.2009
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('rounds','/rounds pointslimit [number] , newrules [0/1] , fixed [number] , warmupduration [number] , fwarmupduration [number] , ftimeout [number] , custom [mode] , score login=# {login=#}|back [#]',true);

//------------------------------------------
// Rounds Commands
//------------------------------------------
function chat_rounds($author, $login, $params, $params2){
	global $_GameInfos,$_NextGameInfos,$_roundslimit_rule,$_players,$_Ranking,$_FWarmUpDuration;

	// verify if author is in admin list
	if(!verifyAdmin($login))
		return;

	// RoundPointsLimit
	if(isset($params[0]) && ($params[0]=='pointslimit' || $params[0]=='limit')){
		if(isset($params[1])) {
			// if pointslimit is set then remove $_roundslimit_rule if exist
			if(isset($_roundslimit_rule))
				$_roundslimit_rule = -1;

			addCall($login,'SetRoundPointsLimit',$params[1]+0);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Rounds PointsLimit to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			//
      if($_GameInfos['RoundsUseNewRules'])
        $val = $_GameInfos['RoundsPointsLimitNewRules'].' (newrules)';
      else
        $val = $_GameInfos['RoundsPointsLimit'];

      if($_NextGameInfos['RoundsUseNewRules'])
        $val2 = $_NextGameInfos['RoundsPointsLimitNewRules'].' (newrules)';
      else
        $val2 = $_NextGameInfos['RoundsPointsLimit'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'PointsLimit: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
			
		// 
	}elseif(isset($params[0]) && $params[0]=='newrules'){
		if(isset($params[1])) {
			if(strcasecmp($params[1],'true')==0)
				$val = true;
			elseif((strcasecmp($params[1],'false')==0) || $params[1]+0==0)
				$val = false;
			else
				$val = true;
			addCall($login,'SetUseNewRulesRound',$val);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Rounds UseNewRules to '.localeText(null,'highlight').($val?'true':'false').' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['RoundsUseNewRules'] ? 'true' : 'false';
			$val2 = $_NextGameInfos['RoundsUseNewRules'] ? 'true' : 'false';
			$msg = localeText(null,'server_message') . localeText(null,'interact').'UseNewRules: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// 
	}elseif(isset($params[0]) && $params[0]=='fixed'){
		if(!isset($_roundslimit_rule)){
			// send message to user who wrote command
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Fixed rounds mode is unavailable, sorry.';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif(isset($params[1])) {
			$_roundslimit_rule = $params[1]+0;

			addCall($login,'SetRoundPointsLimit',0);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Fixed rounds limit to '.localeText(null,'highlight').$_roundslimit_rule.' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Fixed rounds mode: '.$_roundslimit_rule;
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

		// Custom points
	}elseif(isset($params[0]) && ($params[0]=='cust' || $params[0]=='custom' || $params[0]=='rpoints')){
		if(function_exists('chat_rpoints')){
			array_shift($params);
			chat_rpoints($author, $login, $params,'/rounds custom');
		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Sorry, this function is not available without the rpoints plugin !';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// set scores
	}elseif(isset($params[0]) && $params[0]=='score'){
		if(isset($params2[1]) && $params2[1]=='back'){
			$round = false;
			if(isset($params2[2]))
				$round = $params2[2]+0;
			$res = playersSetScoresBack($round);
			if($res===false)
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Can\'t do that !';
			else
				$msg = localeText(null,'server_message') . localeText(null,'interact').$res;
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = null;
			$msg = '';
			$sep = '';
			if(isset($params[1])) {
				$scores = explode(' ',$params[1]);
				if(count($scores)>0){
					foreach($scores as $score){
						$sc = explode('=',$score);
						if(count($sc)==2){
							$pid = -1;
							$score = $sc[1]+0;
							$plogin = $sc[0];
							if(isset($_players[$plogin]))
								$pid = $_players[$plogin]['PlayerId'];
							elseif(is_numeric($plogin))
								$pid = $plogin+0;
							if($pid>=0){
								foreach($_Ranking as &$rk){
									if($rk['PlayerId']==$pid){
										if($rk['Score']!=$score){
											$val[] = array('PlayerId'=>$pid,'Score'=>$score);
											$msg .= $sep.$rk['Login'].'='.$score;
											$sep = ',';
										}
										break;
									}
								}
							}
						}
					}
				}
			}
			if($val != null){
				console('ForceScores: '.$msg);
				addCall($login,'ForceScores',$val,true);
				// send success message
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting scores: '.localeText(null,'highlight').$msg.' !';
				// send message to user who wrote command
				//addCall(null,'ChatSendToLogin', $msg, $login);
				addCall(null,'ChatSendServerMessage', $msg);
				
			}else{
				$msg = localeText(null,'server_message') . localeText(null,'interact').'/rounds score login=val [login=val] , /rounds score back [#]';
				// send message to user who wrote command
				addCall(null,'ChatSendToLogin', $msg, $login);
			}
		}

	// help
	}else{
		$msg = localeText(null,'server_message') . localeText(null,'interact').'/rounds pointslimit [number] , newrules [0/1] , fixed [number] , warmupduration [number] , fwarmupduration [number] , ftimeout [number] , custom [mode] , score login=# {login=#}|back [#]';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}

?>


