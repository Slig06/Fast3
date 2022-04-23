<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// needed plugins: manialinks , ml_menus
//
// plugin to remove scorepanels between rounds
// 
// General config :
// $_scorepanel_hide = 1;        // 0=normal, 1=hide between round, 2=hide always
// $_scorepanel_round_hide = 1;  // 0=normal, 1=hide between round, 2=hide always
//
// Will override general config if set, with hide always effect (ie the gamemode plugin show is own)
// $_FGameModes[$_FGameMode]['Podium'] = true
// $_FGameModes[$_FGameMode]['RoundPanel'] = true
//
// Player config for both panels
// $_players[$login]['ML']['Hide.scorepanel']  // 0=normal, 1=hide between round, 2=hide always
// $_players[$login]['ML']['Hide.roundscore']  // 0=normal, 1=hide until finish, 2=hide always


registerPlugin('ml_scorepanel',14,1.0);


//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function ml_scorepanelInit($event){
	global $_mldebug,$_scorepanel_hide,$_scorepanel_round_hide;
	if($_mldebug>4) console("ml_scorepanel.Event[$event]");

	if(!isset($_scorepanel_hide))
		$_scorepanel_hide = 1;
	$_scorepanel_hide = $_scorepanel_hide+0;

	if(!isset($_scorepanel_round_hide))
		$_scorepanel_round_hide = $_scorepanel_hide;
	else
		$_scorepanel_round_hide = $_scorepanel_round_hide+0;
	
	manialinksAddId('ml_scorepanel');

	manialinksGetHudPartControl('ml_scorepanel','scoretable');
	manialinksGetHudPartControl('ml_scorepanel','round_scores');

	registerCommand('scorepanel','/scorepanel [global|round|all] [on|off]: show or not global and round score panels at end of rounds.',true);
}


//--------------------------------------------------------------
// PlayerConnect : hide scoretable if not while end of race
//--------------------------------------------------------------
function ml_scorepanelPlayerConnect($event,$login){
	global $_mldebug,$_StatusCode,$_scorepanel_hide,$_scorepanel_round_hide,$_players,$_FGameModes,$_FGameMode;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	$pml = &$_players[$login]['ML'];

	if(isset($_FGameModes[$_FGameMode]['Podium']) && $_FGameModes[$_FGameMode]['Podium'] !== false)
		$pml['Hide.scorepanel'] = 2;
	else if(!isset($pml['Hide.scorepanel']))
		$pml['Hide.scorepanel'] = $_scorepanel_hide;

	if(isset($_FGameModes[$_FGameMode]['RoundPanel']) && $_FGameModes[$_FGameMode]['RoundPanel'] !== false)
		$pml['Hide.roundscore'] = 2;
	else if(!isset($pml['Hide.roundscore']))
		$pml['Hide.roundscore'] = $_scorepanel_round_hide;

	if(($pml['Hide.scorepanel'] > 0 && $_StatusCode < 5) || $pml['Hide.scorepanel'] > 1)
		manialinksHideHudPart('ml_scorepanel','scoretable',$login);

	if($pml['Hide.roundscore'] > 0)
		manialinksHideHudPart('ml_scorepanel','round_scores',$login);
}


//--------------------------------------------------------------
// PlayerMenuBuild
//--------------------------------------------------------------
function ml_scorepanelPlayerMenuBuild($event,$login){
	global $_mldebug,$_StatusCode,$_scorepanel_hide,$_scorepanel_round_hide,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	ml_menusAddItem($login, 'menu.config', 'menu.config.hidescorepanel',
									array('Name'=>array(localeText($login,'menu.config.hidescorepanel.off'),
																			localeText($login,'menu.config.hidescorepanel.on')),
												'Type'=>'bool',
												'State'=>($_scorepanel_hide == true)));
	ml_menusAddItem($login, 'menu.config', 'menu.config.hideroundpanel', 
									array('Name'=>array(localeText($login,'menu.config.hideroundpanel.off'),
																			localeText($login,'menu.config.hideroundpanel.on')),
												'Type'=>'bool',
												'State'=>($_scorepanel_round_hide == true)));
}


function ml_scorepanelPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_scorepanel_hide,$_scorepanel_round_hide,$_StatusCode,$_players;
	// verify if author is in admin list
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!verifyAdmin($login))
		return;
	//if($_mldebug>6) console("ml_team.Event[$event]('$login',$action,$state)");
	$msg = localeText(null,'server_message').localeText(null,'interact');

	if($action == 'menu.config.hidescorepanel'){
		$_scorepanel_hide = $state;
		if(!$state)
			manialinksShowHudPart('ml_scorepanel','scoretable',true);
		elseif($_StatusCode < 5)
			manialinksHideHudPart('ml_scorepanel','scoretable',true);
		else
			manialinksShowHudPart('ml_scorepanel','scoretable',true);
		if($state)
			$msg .= localeText($login,'chat.config.hidescorepanel.off');
		else
			$msg .= localeText($login,'chat.config.hidescorepanel.on');
		addCall(null,'ChatSendToLogin', $msg, $login);
		ml_menusSetItem(true, 'menu.config.hidescorepanel', array('State'=>$state));

	}elseif($action == 'menu.config.hideroundpanel'){
		$_scorepanel_round_hide = $state;
		if(!$state)
			manialinksShowHudPart('ml_scorepanel','round_scores',true);
		if($state)
			$msg .= localeText($login,'chat.config.hideroundpanel.off');
		else
			$msg .= localeText($login,'chat.config.hideroundpanel.on');
		addCall(null,'ChatSendToLogin', $msg, $login);
		ml_menusSetItem(true, 'menu.config.hideroundpanel', array('State'=>$state));
	}
}


//--------------------------------------------------------------
// PlayerShowML : hide scoretable
//--------------------------------------------------------------
function ml_scorepanelPlayerShowML_Post($event,$login,$ShowML){
	global $_mldebug,$_StatusCode,$_scorepanel_hide,$_players,$_FGameModes,$_FGameMode;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	$pml = &$_players[$login]['ML'];

	if(isset($_FGameModes[$_FGameMode]['Podium']) && $_FGameModes[$_FGameMode]['Podium'] !== false)
		$pml['Hide.scorepanel'] = 2;
	else if(!isset($pml['Hide.scorepanel']))
		$pml['Hide.scorepanel'] = $_scorepanel_hide;

	if(($pml['Hide.scorepanel'] > 0 && $_StatusCode < 5) || $pml['Hide.scorepanel'] > 1)
		manialinksHideHudPart('ml_scorepanel','scoretable',$login);
	else
		manialinksShowHudPart('ml_scorepanel','scoretable',$login);
}


//--------------------------------------------------------------
// BeginRace : hide scoretable
//--------------------------------------------------------------
function ml_scorepanelBeginRace($event){
	global $_mldebug,$_scorepanel_hide,$_players,$_FGameModes,$_FGameMode;

	$sphide = $_scorepanel_hide;
	if(isset($_FGameModes[$_FGameMode]['Podium']) && $_FGameModes[$_FGameMode]['Podium'] !== false)
		$sphide = 2;

	foreach($_players as $login => &$pl){

		if(!isset($pl['ML']['Hide.scorepanel']) || $pl['ML']['Hide.scorepanel'] != $sphide){
			$pl['ML']['Hide.scorepanel'] = $sphide;
			if($sphide > 0)
				manialinksHideHudPart('ml_scorepanel','scoretable',''.$login);
			else
				manialinksShowHudPart('ml_scorepanel','scoretable',''.$login);

		}else if($pl['ML']['Hide.scorepanel'] > 0)
			manialinksHideHudPart('ml_scorepanel','scoretable',''.$login);
	}	
}


//--------------------------------------------------------------
// EndRace : show scoretable
// eventually a custom scoretable could be made, setable individually by user 
//--------------------------------------------------------------
function ml_scorepanelEndRace($event){
	global $_mldebug,$_players,$_FGameModes,$_FGameMode,$_WarmUp,$_FWarmUp,$_players;

	if(!$_WarmUp && $_FWarmUp <= 0 && 
		 (!isset($_FGameModes[$_FGameMode]['Podium']) || $_FGameModes[$_FGameMode]['Podium'] === false)){

		foreach($_players as $login => &$pl){

			if(isset($pl['ML']['Hide.scorepanel']) && $pl['ML']['Hide.scorepanel'] == 1)
				manialinksShowHudPart('ml_scorepanel','scoretable',''.$login);

		}	
	}
}


//--------------------------------------------------------------
// BeginRound : hide round_scores
//--------------------------------------------------------------
function ml_scorepanelBeginRound($event){
	global $_mldebug,$_scorepanel_round_hide,$_FGameModes,$_FGameMode,$_players;

	$rphide = $_scorepanel_round_hide;
	if(isset($_FGameModes[$_FGameMode]['RoundPanel']) && $_FGameModes[$_FGameMode]['RoundPanel'] !== false)
		$rphide = 2;

	if($rphide > 0){

		foreach($_players as $login => &$pl){

			if(!isset($pl['ML']['Hide.roundscore']) || $pl['ML']['Hide.roundscore'] != $rphide){
				$pl['ML']['Hide.roundscore'] = $rphide;
			
				if($rphide > 0)
					manialinksHideHudPart('ml_scorepanel','round_scores',''.$login);
				else
					manialinksShowHudPart('ml_scorepanel','round_scores',''.$login);
				
			}else if($pl['ML']['Hide.roundscore'] > 0)
				manialinksHideHudPart('ml_scorepanel','round_scores',''.$login);
		}	

	}
}


//--------------------------------------------------------------
// EndRound : show round_scores
//--------------------------------------------------------------
function ml_scorepanelEndRound($event){
	global $_mldebug,$_scorepanel_round_hide,$_WarmUp,$_FWarmUp,$_FGameModes,$_FGameMode;

	if($_scorepanel_round_hide == 1 &&
		 (!isset($_FGameModes[$_FGameMode]['RoundPanel']) || $_FGameModes[$_FGameMode]['RoundPanel'] === false)){
		if(!$_WarmUp && $_FWarmUp <= 0)
			manialinksShowHudPart('ml_scorepanel','round_scores',true);
	}
}


function ml_scorepanelPlayerFinish($event,$login,$time){
	global $_mldebug,$_scorepanel_round_hide,$_players,$_GameInfos;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if($_players[$login]['ML']['Hide.roundscore'] < 2 && $_GameInfos['GameMode'] != TA && $_GameInfos['GameMode'] != STUNTS){
		manialinksShowHudPart('ml_scorepanel','round_scores',$login);
	}
}


//------------------------------------------
// Scorepanel command
//------------------------------------------
function chat_scorepanel($author, $login, $params){
	global $_mldebug,$_scorepanel_hide,$_scorepanel_round_hide,$_StatusCode,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	
	// verify if author is in admin list
	if(!verifyAdmin($login))
		return;
	
	if(isset($params[0]) && $params[0]!='global' && $params[0]!='round' && $params[0]!='all'){
		$params[1] = $params[0];
		$params[0] = 'all';
	}
	if(isset($params[1])){
		if($params[1] == 'true' || $params[1] == 'on'  || $params[1] == 'ON' || $params[1] == '1'){
			if($params[0] == 'global'){
				if($_scorepanel_hide)
					ml_menusSetItem(true, 'menu.config.hidescorepanel', array('State'=>false));
				$_scorepanel_hide = false;
				manialinksShowHudPart('ml_scorepanel','scoretable',true);
				$msg = localeText(null,'server_message').localeText(null,'interact').'Global score panel is now ON !';
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;

			}elseif($params[0] == 'round'){
				if($_scorepanel_round_hide)
					ml_menusSetItem(true, 'menu.config.hideroundpanel', array('State'=>false));
				$_scorepanel_round_hide = false;
				manialinksShowHudPart('ml_scorepanel','round_scores',true);
				$msg = localeText(null,'server_message').localeText(null,'interact').'Round score panel is now ON !';
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;

			}elseif($params[0] == 'all'){
				if($_scorepanel_hide)
					ml_menusSetItem(true, 'menu.config.hidescorepanel', array('State'=>false));
				if($_scorepanel_round_hide)
					ml_menusSetItem(true, 'menu.config.hideroundpanel', array('State'=>false));
				$_scorepanel_hide = false;
				$_scorepanel_round_hide = false;
				manialinksShowHudPart('ml_scorepanel','round_scores',true);
				manialinksShowHudPart('ml_scorepanel','scoretable',true);
				$msg = localeText(null,'server_message').localeText(null,'interact').'Global and round score panels are now ON !';
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;
			}

		}elseif($params[1] == 'false' || $params[1] == 'off'  || $params[1] == 'OFF' || $params[1] == '0'){
			if($params[0] == 'global'){
				if(!$_scorepanel_hide)
					ml_menusSetItem(true, 'menu.config.hidescorepanel', array('State'=>true));
				$_scorepanel_hide = true;
				if($_StatusCode < 5)
					manialinksHideHudPart('ml_scorepanel','scoretable',true);
				else
					manialinksShowHudPart('ml_scorepanel','scoretable',true);
				$msg = localeText(null,'server_message').localeText(null,'interact').'Global score panel is now OFF !';
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;

			}elseif($params[0] == 'round'){
				if(!$_scorepanel_round_hide)
					ml_menusSetItem(true, 'menu.config.hideroundpanel', array('State'=>true));
				$_scorepanel_round_hide = true;
				$msg = localeText(null,'server_message').localeText(null,'interact').'Round score panel is now OFF !';
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;

			}elseif($params[0] == 'all'){
				if(!$_scorepanel_hide)
					ml_menusSetItem(true, 'menu.config.hidescorepanel', array('State'=>true));
				if(!$_scorepanel_round_hide)
					ml_menusSetItem(true, 'menu.config.hideroundpanel', array('State'=>true));
				$_scorepanel_hide = true;
				$_scorepanel_round_hide = true;
				if($_StatusCode < 5)
					manialinksHideHudPart('ml_scorepanel','scoretable',true);
				else
					manialinksShowHudPart('ml_scorepanel','scoretable',true);
				$msg = localeText(null,'server_message').localeText(null,'interact').'Global and round score panels are now OFF !';
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;
			}
		}
	}
	// other : show info
	$msg = localeText(null,'server_message') . localeText(null,'interact')
		.'/scorepanel [global|round|all] [on|off]: show or not global and round score panels at end of rounds.';
	if(!$_scorepanel_hide)
		$msg .= ' Global score panel is ON.';
	else
		$msg .= ' Global score panel is OFF.';
	if(!$_scorepanel_round_hide)
		$msg .= ' Round score panel is ON.';
	else
		$msg .= ' Round score panel is OFF.';
	// send message to user who wrote command
	addCall(null,'ChatSendToLogin', $msg, $login);
}

?>
