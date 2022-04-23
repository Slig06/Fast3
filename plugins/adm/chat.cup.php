<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      07.04.2010
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('cup','/cup pointslimit [number] , roundspermap [number], nbwinners [0/1], warmupduration [number] , fwarmupduration [number] , autoadjust [0|1|2] , ftimeout [number] , custom [mode] , reset , score login=# {login=#}|back [#]',true);

//------------------------------------------
// Laps Commands
//------------------------------------------
function chat_cup($author, $login, $params, $params2){
	global $_GameInfos,$_NextGameInfos,$_players,$_Ranking,$_cup_autoadjust,$_FWarmUpDuration;

	// verify if author is in admin list
	if(!verifyAdmin($login))
		return;

		// CupPointsLimit
	if(isset($params[0]) && ($params[0]=='pointslimit' || $params[0]=='limit')){
		if(isset($params[1])) {
			addCall($login,'SetCupPointsLimit',$params[1]+0);

			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Cup PointsLimit to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['CupPointsLimit'];
			$val2 = $_NextGameInfos['CupPointsLimit'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'PointsLimit: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
			
		// rounds per map
	}elseif(isset($params[0]) && ($params[0]=='roundspermap' || $params[0]=='rounds' || $params[0]=='rpm' || $params[0]=='rpc')){
		if(isset($params[1])) {
			addCall($login,'SetCupRoundsPerChallenge',$params[1]+0);

			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Cup RoundsPerChallenge to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['CupRoundsPerChallenge'];
			$val2 = $_NextGameInfos['CupRoundsPerChallenge'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'RoundsPerChallenge: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// nb winners
	}elseif(isset($params[0]) && ($params[0]=='nbwinners' || $params[0]=='nbwin' || $params[0]=='nbw' || $params[0]=='nb')){
		if(isset($params[1])) {
			addCall($login,'SetCupNbWinners',$params[1]+0);

			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Cup NbWinners to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['CupNbWinners'];
			$val2 = $_NextGameInfos['CupNbWinners'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'NbWinners: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// warmup duration
	}elseif(isset($params[0]) && ($params[0]=='warmupduration' || $params[0]=='wuduration' || $params[0]=='wud' || $params[0]=='wu')){
		if(isset($params[1])) {
			addCall($login,'SetCupWarmUpDuration',$params[1]+0);

			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Cup WarmUpDuration to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['CupWarmUpDuration.'];
			$val2 = $_NextGameInfos['CupWarmUpDuration.'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'WarmUpDuration: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// fwarmup duration
	}elseif(isset($params[0]) && ($params[0]=='fwarmupduration' || $params[0]=='fwuduration' || $params[0]=='fwud' || $params[0]=='fwu')){
		if(isset($params[1])) {
			$_FWarmUpDuration = $params[1]+0;
			addCall($login,'SetCupWarmUpDuration',0);
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

		// autoadjust rounds points when there is finalists/winners
	}elseif(isset($params[0]) && ($params[0]=='autoadjust' || $params[0]=='auto')){
		if(isset($params[1])) {
			if($params[1]=='on' || $params[1]=='true' || $params[1]=='2'){
				setCupAutoAdjust(2);
			}elseif($params[1]=='1'){
				setCupAutoAdjust(1);
			}elseif($params[1]=='off' || $params[1]=='false' || $params[1]=='0'){
				setCupAutoAdjust(0);
			}
		}
		$msg = localeText(null,'server_message') . localeText(null,'interact')."Cup AutoAdjust: {$_cup_autoadjust}";
		addCall(null,'ChatSendToLogin', $msg, $login);

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
			chat_rpoints($author, $login, $params,'/cup custom');
		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Sorry, this function is not available without the rpoints plugin !';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// Reset cup scores
	}elseif(isset($params[0]) && $params[0]=='reset'){
		$val = null;
		$msg = '';
		$sep = '';
		foreach($_players as &$pl){
			if($pl['Active']){
				$val[] = array('PlayerId'=>$pl['PlayerId'],'Score'=>0);
				$msg .= $sep.$pl['Login'].'='.$pl['Score'];
				$sep = ',';
			}
		}
		if($val != null){
			console("Reset Scores ! ({$msg})");
			addCall($login,'ForceScores',$val,true);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact')."Resetting scores (was: {$msg}) !";
			// send message to user who wrote command
			//addCall(null,'ChatSendToLogin', $msg, $login);
			addCall(null,'ChatSendServerMessage', $msg);
			
		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/cup reset';
			// send message to user who wrote command
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
				$msg = localeText(null,'server_message') . localeText(null,'interact').'/cup score login=val {login=val} , /rounds score back [#]';
				// send message to user who wrote command
				addCall(null,'ChatSendToLogin', $msg, $login);
			}
		}

	// help
	}else{
		$msg = localeText(null,'server_message') . localeText(null,'interact').'/cup pointslimit [number] , roundspermap [number], nbwinners [0/1], warmupduration [number] , fwarmupduration [number] , autoadjust [0|1|2]  , ftimeout [number] , custom [mode] , reset , score login=# {login=#}|back [#]';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}

?>


