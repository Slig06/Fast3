<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      23.03.2010
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('team','/team pointslimit [number] , maxpoint [number] , newrules [0/1] , fixed [number] , gap [#|off] , warmupduration [number] , fwarmupduration [number] , ftimeout [number] , blue [login], red [login], score b=num|r=num|back [#]',true);

//------------------------------------------
// Team Commands
//------------------------------------------
function chat_team($author, $login, $params, $params2){
	global $_GameInfos,$_NextGameInfos,$_Game,$_is_dedicated,$_teamroundslimit_rule,$_players,$_FWarmUpDuration,$_teamgap_rule;

	// verify if author is in admin list
	if(!verifyAdmin($login))
		return;

	// TeamPointsLimit
	if(isset($params[0]) && ($params[0]=='pointslimit' || $params[0]=='limit')){
		if(isset($params[1])) {
			if(isset($_teamgap_rule) && $_teamgap_rule > 1){
				// handle teamgap case
				$_teamgap_rule = $params[1]+0;
				addCall($login,'SetTeamPointsLimit',$_teamgap_rule*10);

			}else{
				addCall($login,'SetTeamPointsLimit',$params[1]+0);
			}
			if(isset($_teamroundslimit_rule) && $params[1]+0 > 0)
				$_teamroundslimit_rule = 0; // remove fixed rounds limit if set

			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Team PointsLimit to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			if($_GameInfos['TeamUseNewRules'])
				$val = $_GameInfos['TeamPointsLimitNewRules'].' (newrules)';
			else
				$val = $_GameInfos['TeamPointsLimit'];

			if($_NextGameInfos['TeamUseNewRules'])
				$val2 = $_NextGameInfos['TeamPointsLimitNewRules'].' (newrules)';
			else
				$val2 = $_NextGameInfos['TeamPointsLimit'];

			if(isset($_teamgap_rule) && $_teamgap_rule > 1)
				$val2 .= ' (min-gap=2)';
			$msg = localeText(null,'server_message') . localeText(null,'interact').'PointsLimit: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
			
		// MaxPointsTeam
	}elseif(isset($params[0]) && ($params[0] == 'maxpoint' || $params[0] == 'max')){
		if(isset($params[1])) {
			addCall($login,'SetMaxPointsTeam',$params[1]+0);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Team MaxPoint to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['TeamMaxPoints'];
			$val2 = $_NextGameInfos['TeamMaxPoints'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'MaxPoints: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// UseNewRulesTeam
	}elseif(isset($params[0]) && $params[0] == 'newrules'){
		if(isset($params[1])) {
			if(strcasecmp($params[1],'true') == 0)
				$val = true;
			elseif((strcasecmp($params[1],'false') == 0) || $params[1]+0 == 0)
				$val = false;
			else
				$val = true;
			addCall($login,'SetUseNewRulesTeam',$val);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Team UseNewRules to '.localeText(null,'highlight').($val?'true':'false').' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$val = $_GameInfos['TeamUseNewRules'] ? 'true' : 'false';
			$val2 = $_NextGameInfos['TeamUseNewRules'] ? 'true' : 'false';
			$msg = localeText(null,'server_message') . localeText(null,'interact').'UseNewRules: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// 
	}elseif(isset($params[0]) && $params[0] == 'fixed'){
		if(!isset($_teamroundslimit_rule)){
			// send message to user who wrote command
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Fixed rounds mode is unavailable, sorry.';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif(isset($params[1])) {
			$_teamroundslimit_rule = $params[1]+0;

			addCall($login,'SetTeamPointsLimit',0);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Fixed rounds limit to '.localeText(null,'highlight').$_teamroundslimit_rule.' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Fixed rounds mode: '.$_teamroundslimit_rule;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
			
		// 
	}elseif(isset($params[0]) && $params[0] == 'gap'){
		if(!isset($_teamgap_rule)){
			// send message to user who wrote command
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Minimum gap mode is unavailable, sorry.';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif(isset($params[1]) && ($params[1] == 'off' || $params[1] == 'stop' || $params[1] == 'end' || $params[1] == '0')) {
			if($_teamgap_rule > 1)
				addCall($login,'SetTeamPointsLimit',$_teamgap_rule);
			$_teamgap_rule = 0;
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Minimum gap mode is OFF.';

		}elseif(isset($params[1]) && $params[1]+0 > 1){
			$_teamgap_rule = $params[1]+0;
			addCall($login,'SetTeamPointsLimit',$_teamgap_rule*10);

			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting minimum gap mode ON, using points limit: '.$_teamgap_rule.' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			if($_teamgap_rule > 1)
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Minimum gap mode is ON, with points limit: '.$_teamgap_rule;
			else
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Minimum gap mode is OFF.';
			$msg .= '  Usage: /team gap [#|off]';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
			
    // warmup duration
  }elseif(isset($params[0]) && ($params[0] == 'warmupduration' || $params[0] == 'wuduration' || $params[0] == 'wud' || $params[0] == 'wu')){
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
  }elseif(isset($params[0]) && ($params[0] == 'fwarmupduration' || $params[0] == 'fwuduration' || $params[0] == 'fwud' || $params[0] == 'fwu')){
    if(isset($params[1])) {
			$_FWarmUpDuration = $params[1]+0;
      addCall($login,'SetAllWarmUpDuration',0);

      // send success message
      $msg = localeText(null,'server_message') . localeText(null,'interact').'Setting FWarmUpDuration to '.localeText(null,'highlight').$params[1].' !';
      // send message to user who wrote command
      addCall(null,'ChatSendToLogin', $msg, $login);

    }else{
      $val = $_FWarmUpDuration;
      $msg = localeText(null,'server_message') . localeText(null,'interact').'WarmUpDuration: '.$val;
      // send message to user who wrote command
      addCall(null,'ChatSendToLogin', $msg, $login);
    }

		// Put player in blue team
	}elseif(isset($params[0]) && $params[0] == 'blue'){
		if(isset($params[1]) && isset($_players[''.$params[1]])) {
			addCall($login,'ForcePlayerTeam',''.$params[1],0);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting '.stripColors($params[1]).' to blue team !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
				.' (admin) put '.stripColors($params[1]).' in blue team !';
			addCall(null,'ChatSendServerMessage', $msg);
			
		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').' /team blue [login]';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// Put player in red team
	}elseif(isset($params[0]) && $params[0] == 'red'){
		if(isset($params[1]) && isset($_players[''.$params[1]])) {
			addCall($login,'ForcePlayerTeam',''.$params[1],1);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting '.stripColors($params[1]).' to red team !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
				.' (admin) put '.stripColors($params[1]).' in red team !';
			addCall(null,'ChatSendServerMessage', $msg);
			
		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').' /team red [login]';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// set scores
	}elseif(isset($params[0]) && $params[0] == 'score'){
		if(isset($params2[1]) && $params2[1] == 'back'){
			$round = false;
			if(isset($params2[2]))
				$round = $params2[2]+0;
			$res = playersSetScoresBack($round);
			if($res === false)
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Can\'t do that !';
			else
				$msg = localeText(null,'server_message') . localeText(null,'interact').$res;
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = null;
			$msg = '';
			if(isset($params[1])) {
				$scores = explode(' ',$params[1]);
				if(count($scores)>0){
					foreach($scores as $score){
						$sc = explode('=',$score);
						if(count($sc) == 2){
							$team = $sc[0];
							$score = $sc[1]+0;
							if($team == 'blue' || $team == 'b' || $team == '0'){
								$val[] = array('PlayerId'=>0,'Score'=>$score);
								$msg .= 'Blue score to '.$score.'... ';
							}elseif($team == 'red' || $team == 'r' || $team == '1'){
								$val[] = array('PlayerId'=>1,'Score'=>$score);
								$msg .= 'Red score to '.$score.'... ';
							}
						}
					}
				}
			}
			if($val != null){
				console('ForceScores: '.$msg);
				addCall($login,'ForceScores',$val,true);
				// send success message
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting '.localeText(null,'highlight').$msg.' !';
				// send message to user who wrote command
				//addCall(null,'ChatSendToLogin', $msg, $login);
				addCall(null,'ChatSendServerMessage', $msg);
			
			}else{
				$msg = localeText(null,'server_message') . localeText(null,'interact').'/team score blue=val , /team score red=val , /team score back [#]';
				// send message to user who wrote command
				addCall(null,'ChatSendToLogin', $msg, $login);
			}
		}

		// FinishTimeout
	}elseif(isset($params[0]) && ($params[0] == 'ftimeout' || $params[0] == 'fto' || $params[0] == 'finishtimeout')){
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
		$msg = localeText(null,'server_message') . localeText(null,'interact').'/team pointslimit [number] , maxpoint [number] , newrules [0/1] , fixed [number] , gap [#|off] , warmupduration [number] , fwarmupduration [number] , ftimeout [number] , blue [login], red [login], score b=num|r=num|back [#]';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}

?>


