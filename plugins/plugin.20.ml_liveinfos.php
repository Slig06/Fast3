<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
// needed plugins: manialinks,ml_menus
//
// plugin to show checkpoints times
// 
// 
//
// $_live_position_default = true;
// $_players[$login]['ML']['Show.position']
//
// $_live_checkpoints_default = true;
// $_players[$login]['ML']['Show.cpgaps']
//
// $_live_top_default = true;
// $_players[$login]['ML']['Show.topgaps']
//
// $_live_players_default = 0;
// $_players[$login]['ML']['Show.liveplayers']

registerPlugin('ml_liveinfos',20,1.0);


//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function ml_liveinfosInit($event){
	global $_mldebug,$_live_checkpoints_default,$_live_default,$_live_top_default,$_live_position_default,$_live_players_default,$_live_players_bg_xml,$_live_players_bg2_xml,$live_team0_score,$live_team1_score,$_is_relay;
	if($_mldebug>4) console("ml_liveinfos.Event[$event]");

	if(!isset($_live_checkpoints_default))
		$_live_checkpoints_default = !$_is_relay;

	if(!isset($_live_top_default))
		$_live_top_default = !$_is_relay;

	if(!isset($_live_position_default))
		$_live_position_default = !$_is_relay;

	if(!isset($_live_players_default))
		$_live_players_default = !$_is_relay;
	$_live_players_default = $_live_players_default+0;

	$_live_players_bg_xml =
		'<quad sizen="25.5 2.4" posn="-68.2 -16.65 -44" style="BgsPlayerCard" substyle="BgPlayerCardBig"/>'; // "BgPlayerName" , "BgCard" , "BgPlayerCardBig"
	$_live_players_bg2_xml =
		'<quad sizen="23.5 2.4" posn="-36.2 30.65 -61" style="BgsPlayerCard" substyle="BgPlayerCardBig"/>'; // "BgPlayerName" , "BgCard" , "BgPlayerCardBig"
	
	$live_team0_score = -1;
	$live_team1_score = -1;

	//manialinksGetHudPartControl('ml_liveinfos','checkpoint_list');
}


function ml_liveinfosPlayerConnect($event,$login){
	global $_mldebug,$_players,$_live_checkpoints_default,$_live_top_default,$_live_position_default,$_live_players_default;
  if(!is_string($login))
    $login = ''.$login;
	//console("ml_team.Event[$event]('$login')");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if(!isset($_players[$login]['ML']['Show.position']))
		$_players[$login]['ML']['Show.position'] = $_live_position_default;
	if($_players[$login]['ML']['Show.position'] && $_players[$login]['Status2']<2)
		ml_liveinfosUpdatePlayerPositionXml($login,'show');

	if(!isset($_players[$login]['ML']['Show.cpgaps']))
		$_players[$login]['ML']['Show.cpgaps'] = $_live_checkpoints_default;
	if($_players[$login]['ML']['Show.cpgaps'] && $_players[$login]['Status2']<2)
		ml_liveinfosUpdatePlayerCPGapsXml($login,'show');

	if(!isset($_players[$login]['ML']['Show.topgaps']))
		$_players[$login]['ML']['Show.topgaps'] = $_live_top_default;
	if($_players[$login]['ML']['Show.topgaps'] && $_players[$login]['Status2']<2)
		ml_liveinfosUpdatePlayerTopGapsXml($login,'show');

	if(!isset($_players[$login]['ML']['Show.liveplayers']))
		$_players[$login]['ML']['Show.liveplayers'] = $_live_players_default;
	if($_players[$login]['ML']['Show.liveplayers'] && $_players[$login]['Status2']<1)
		ml_liveinfosUpdatePlayerLivePlayersXml($login,'show');
}


function ml_liveinfosPlayerMenuBuild($event,$login){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	ml_menusAddItem($login, 'menu.hud.live.menu', 'menu.hud.position', 
									array('Name'=>array(localeText($login,'menu.hud.position.on'),
																			localeText($login,'menu.hud.position.off')),
									'Type'=>'bool',
												'State'=>$_players[$login]['ML']['Show.position']));

	ml_menusAddItem($login, 'menu.hud.live.menu', 'menu.hud.showcp', 
									array('Name'=>array(localeText($login,'menu.hud.showcp.on'),
																			localeText($login,'menu.hud.showcp.off')),
												'Type'=>'bool',
												'State'=>$_players[$login]['ML']['Show.cpgaps']));

	ml_menusAddItem($login, 'menu.hud.live.menu', 'menu.hud.showtop', 
									array('Name'=>array(localeText($login,'menu.hud.showtop.on'),
																			localeText($login,'menu.hud.showtop.off')),
												'Type'=>'bool',
												'State'=>$_players[$login]['ML']['Show.topgaps']));

	ml_menusAddItem($login, 'menu.hud.live.menu', 'menu.hud.liveplayers', 
									array('Name'=>array(localeText($login,'menu.hud.liveplayers.off'),
																			localeText($login,'menu.hud.liveplayers.on'),
																			localeText($login,'menu.hud.liveplayers.high')),
												'Type'=>'multi','multioffval'=>0,
												'State'=>$_players[$login]['ML']['Show.liveplayers']));
}


function ml_liveinfosPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_players;
	//if($_mldebug>6) console("ml_liveinfos.Event[$event]('$login',$action,$state)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	$msg = localeText(null,'server_message').localeText(null,'interact');
	if($action=='menu.hud.position'){
		$_players[$login]['ML']['Show.position'] = $state;
		if($state && $_players[$login]['ML']['ShowML'] && $_players[$login]['ML']['Show.live'] && $_players[$login]['Status2']<2)
			ml_liveinfosUpdatePlayerPositionXml($login,'show');
		else
			ml_liveinfosUpdatePlayerPositionXml($login,'hide');
		if($state)
			$msg .= localeText($login,'chat.hud.position.on');
		else
			$msg .= localeText($login,'chat.hud.position.off');
		addCall(null,'ChatSendToLogin', $msg, $login);

		if($_players[$login]['ML']['Show.liveplayers']>=2 && $_players[$login]['ML']['Show.live'] && $_players[$login]['Status2']<2)
			ml_liveinfosUpdatePlayerLivePlayersXml($login,'show');

	}elseif($action=='menu.hud.showcp'){
		$_players[$login]['ML']['Show.cpgaps'] = $state;
		if($state && $_players[$login]['ML']['ShowML'] && $_players[$login]['ML']['Show.live'] && $_players[$login]['Status2']<2)
			ml_liveinfosUpdatePlayerCPGapsXml($login,'show');
		else
			ml_liveinfosUpdatePlayerCPGapsXml($login,'hide');
		if($state)
			$msg .= localeText($login,'chat.hud.showcp.on');
		else
			$msg .= localeText($login,'chat.hud.showcp.off');
		addCall(null,'ChatSendToLogin', $msg, $login);

	}elseif($action=='menu.hud.showtop'){
		$_players[$login]['ML']['Show.topgaps'] = $state;
		if($state && $_players[$login]['ML']['ShowML'] && $_players[$login]['ML']['Show.live'] && $_players[$login]['Status2']<2)
			ml_liveinfosUpdatePlayerTopGapsXml($login,'show');
		else
			ml_liveinfosUpdatePlayerTopGapsXml($login,'hide');
		if($state)
			$msg .= localeText($login,'chat.hud.showtop.on');
		else
			$msg .= localeText($login,'chat.hud.showtop.off');
		addCall(null,'ChatSendToLogin', $msg, $login);

	}elseif($action=='menu.hud.liveplayers'){
		if($_players[$login]['ML']['Show.liveplayers']!=$state)
			ml_liveinfosUpdatePlayerLivePlayersXml($login,'hide');
		$_players[$login]['ML']['Show.liveplayers'] = $state;
		if($state && $_players[$login]['ML']['ShowML'] && 
			 $_players[$login]['ML']['Show.live'] && $_players[$login]['Status2']<1)
			ml_liveinfosUpdatePlayerLivePlayersXml($login,'show');
		else
			ml_liveinfosUpdatePlayerLivePlayersXml($login,'hide');
		if($state==0)
			$msg .= localeText($login,'chat.hud.liveplayers.off');
		elseif($state==1)
			$msg .= localeText($login,'chat.hud.liveplayers.on');
		else
			$msg .= localeText($login,'chat.hud.liveplayers.high');
		addCall(null,'ChatSendToLogin', $msg, $login);

	}elseif($action=='menu.hud.live'){
		// state set in ml_main
		if($state && $_players[$login]['ML']['ShowML'] && $_players[$login]['Status2']<2){
			if($_players[$login]['ML']['Show.position'])
				ml_liveinfosUpdatePlayerPositionXml($login,'show');
			if($_players[$login]['ML']['Show.cpgaps'])
				ml_liveinfosUpdatePlayerCPGapsXml($login,'show');
			if($_players[$login]['ML']['Show.topgaps'])
				ml_liveinfosUpdatePlayerTopGapsXml($login,'show');
			if($_players[$login]['ML']['Show.liveplayers']>0)
				ml_liveinfosUpdatePlayerLivePlayersXml($login,'show');
			
		}else{
			ml_liveinfosUpdatePlayerPositionXml($login,'hide');
			ml_liveinfosUpdatePlayerCPGapsXml($login,'hide');
			ml_liveinfosUpdatePlayerTopGapsXml($login,'hide');
			ml_liveinfosUpdatePlayerLivePlayersXml($login,'hide');
		}

	}
}


function ml_liveinfosPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if($ShowML && $_players[$login]['ML']['Show.live'] && $_players[$login]['Status2']<2){
		if($_players[$login]['ML']['Show.position'])
			ml_liveinfosUpdatePlayerPositionXml($login,'show');
		if($_players[$login]['ML']['Show.cpgaps'])
			ml_liveinfosUpdatePlayerCPGapsXml($login,'show');
		if($_players[$login]['ML']['Show.topgaps'])
			ml_liveinfosUpdatePlayerTopGapsXml($login,'show');
		if($_players[$login]['ML']['Show.liveplayers']>0)
			ml_liveinfosUpdatePlayerLivePlayersXml($login,'show');
	}else{
		ml_liveinfosUpdatePlayerPositionXml($login,'hide');
		ml_liveinfosUpdatePlayerCPGapsXml($login,'hide');
		ml_liveinfosUpdatePlayerTopGapsXml($login,'hide');
		ml_liveinfosUpdatePlayerLivePlayersXml($login,'hide');
	}
}


//--------------------------------------------------------------
// PlayerPositionChange :
//--------------------------------------------------------------
// $changes value :
//   1=position changed  (in TA: best rank changed)
//   2=checkpoint changed
//   4=diff with previous player changed  (not in TA)
//   8=diff with next player changed  (not in TA)
//  16=diff with first player changed  (in TA: best diff)
//  32=diff with previous2 player changed  (not in TA)
//  64=diff with next2 player changed  (not in TA)
function ml_liveinfosPlayerPositionChange($event,$login,$changes){
	global $_mldebug,$_players;
	if($login===true){
		//if($_mldebug>5) console("ml_liveinfos.Event[$event](true,$changes)");

		// some change in positions
		ml_liveinfosUpdatePlayerPositionXml(true,'refresh');
		//ml_liveinfosUpdatePlayerLivePlayersXml(true,'refresh');

	}elseif(is_string($login)){
		//if($_mldebug>5) console("ml_liveinfos.Event[$event]($login,$changes)");
		if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
			return;

		// position infos to login when he passed a check (not in timeattack)
		if($_players[$login]['ML']['ShowML'] && $_players[$login]['ML']['Show.position']){
			if(($changes&2)==2){
				ml_liveinfosUpdatePlayerPositionXml($login,'show');
			}
		}
		if($_players[$login]['ML']['ShowML'] && $_players[$login]['ML']['Show.liveplayers']>0){
			ml_liveinfosUpdatePlayerLivePlayersXml($login,$changes);
		}
		
	}
}


function ml_liveinfosPlayerTeamChange($event,$login,$teamid){
	ml_liveinfosUpdatePlayerLivePlayersXml(true,'refresh');
}


function ml_liveinfosEverysecond($event,$sec){
	global $_mldebug,$_GameInfos,$_players,$_teams,$live_team0_score,$live_team1_score;

	if($_GameInfos['GameMode'] == TEAM){ // team
		if($live_team0_score!=$_teams[0]['Score'] || $live_team1_score!=$_teams[1]['Score']){
			$live_team0_score = $_teams[0]['Score'];
			$live_team1_score = $_teams[1]['Score'];
			ml_liveinfosUpdatePlayerLivePlayersXml(true,128);
		}
	}
}


function ml_liveinfosPlayerCheckpoint($event,$login,$time,$lapnum,$checkpt){
	global $_mldebug,$_players,$_checkpoints_hide,$_players_playing;
	if($_mldebug>6) console("ml_liveinfos.Event[$event]('$login',$time,$lapnum,$checkpt)");

	if($_players[$login]['ML']['ShowML'] && !$_players[$login]['Relayed'] && $_players[$login]['ML']['Show.live'] && $_players[$login]['Status2']<2){
		if($_players[$login]['ML']['Show.cpgaps'])
			ml_liveinfosUpdatePlayerCPGapsXml($login,'show');
		if($_players[$login]['ML']['Show.topgaps'])
			ml_liveinfosUpdatePlayerTopGapsXml($login,'show');
	}

	// send check to targetted specs
	$pid = $_players[$login]['PlayerId'];
	foreach($_players as $speclogin => &$pl){
		if($pl['Status']==1 && $pl['FinalTime']<=0 && ($pl['CurrentTargetId']==$pid || $_players_playing==1) && 
			 $pl['ML']['ShowML'] && $pl['ML']['Show.live']){
			if($pl['ML']['Show.cpgaps'])
				ml_liveinfosUpdatePlayerCPGapsXml(''.$speclogin,'show',$login);
			if($pl['ML']['Show.topgaps'])
				ml_liveinfosUpdatePlayerTopGapsXml(''.$speclogin,'show',$login);
		}
	}
}


function ml_liveinfosPlayerSpecChange($event,$login,$isspec,$specstatus,$oldspecstatus){
	global $_mldebug,$_players;
	//if($_mldebug>0) console("ml_liveinfos.Event[$event]('$login',".($isspec?'true':'false').",$specstatus,$oldspecstatus)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if($specstatus>0 && (((int)floor($oldspecstatus/10000))!=255 || $_players[$login]['IsAutoTarget'])){
		if($_players[$login]['ML']['Show.cpgaps'])
			ml_liveinfosUpdatePlayerCPGapsXml($login,'hide');
		if($_players[$login]['ML']['Show.topgaps'])
			ml_liveinfosUpdatePlayerTopGapsXml($login,'hide');
	}
}


// round logical $status2: 0=playing, 1=spec, 2=race finished
function ml_liveinfosPlayerStatus2Change($event,$login,$status2,$oldstatus2){
	global $_debug,$_players;
	//console("ml_liveinfos.Event[$event]('$login',$status2)");

	if($_players[$login]['ML']['ShowML'] && !$_players[$login]['Relayed']){
		if($status2==0){
			if($_players[$login]['ML']['Show.liveplayers']>0)
				ml_liveinfosUpdatePlayerLivePlayersXml($login,'show');

		}elseif($oldstatus2==0){
			if($_players[$login]['ML']['Show.liveplayers']>0)
				ml_liveinfosUpdatePlayerLivePlayersXml($login,'hide');
		}
	}
}


function ml_liveinfosBeginRound($event){
	global $_mldebug,$_players,$live_team0_score,$live_team1_score;
	
	$live_team0_score = -1;
	$live_team1_score = -1;

	foreach($_players as $login => &$pl){
		if(!is_string($login))
			$login = ''.$login;
		if($_players[$login]['ML']['ShowML'] && !$_players[$login]['Relayed'] && $_players[$login]['ML']['Show.live']){
			if($_players[$login]['ML']['Show.cpgaps'])
				ml_liveinfosUpdatePlayerCPGapsXml($login,'show');
			if($_players[$login]['ML']['Show.topgaps'])
				ml_liveinfosUpdatePlayerTopGapsXml($login,'hide');
			if($_players[$login]['ML']['Show.position'])
				ml_liveinfosUpdatePlayerPositionXml($login,'hide');
			if($_players[$login]['ML']['Show.liveplayers']>0)
				ml_liveinfosUpdatePlayerLivePlayersXml($login,'show');
		}
	}
}


function ml_liveinfosEndRace($event){
	global $_mldebug,$_players;
	
	foreach($_players as $login => &$pl){
		if(!is_string($login))
			$login = ''.$login;
		if($_players[$login]['ML']['ShowML'] && !$_players[$login]['Relayed'] && $_players[$login]['ML']['Show.live']){
			if($_players[$login]['ML']['Show.cpgaps'])
				ml_liveinfosUpdatePlayerCPGapsXml($login,'hide');
			if($_players[$login]['ML']['Show.topgaps'])
				ml_liveinfosUpdatePlayerTopGapsXml($login,'hide');
			if($_players[$login]['ML']['Show.position'])
				ml_liveinfosUpdatePlayerPositionXml($login,'hide');
			if($_players[$login]['ML']['Show.liveplayers']>0)
				ml_liveinfosUpdatePlayerLivePlayersXml($login,'hide');
		}
	}
}



//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove'
//--------------------------------------------------------------
function ml_liveinfosUpdatePlayerCPGapsXml($login,$action='show',$speclogin=''){
	global $_mldebug,$_players,$_StatusCode,$_BestPlayersChecks,$_GameInfos,$_BestChecks,$_IdealChecks,$_BestChecksName;
	// if the players disabled manialinks then do nothing
	if(!$_players[$login]['ML']['ShowML'])
		return;
	if($_mldebug>3) console("ml_liveinfosUpdatePlayerCPGapsXml('$login',$action) - ".$_players[$login]['Status']);

	if($action=='remove'){
		// remove manialink
		manialinksRemove($login,'ml_liveinfos.cp');
		return;
	}elseif($action=='hide'){
		// hide manialink
		manialinksHide($login,'ml_liveinfos.cp');
		return;
	}
	// show/refresh
	if(!$_players[$login]['ML']['Show.live'] || !$_players[$login]['ML']['Show.cpgaps'] || 
		 ($_players[$login]['IsSpectator'] && $speclogin=='')){
		// none to show and opened : hide it
		if(manialinksIsOpened($login,'ml_liveinfos.cp'))
			manialinksHide($login,'ml_liveinfos.cp');
		return;
	}

	// show 
	$showlogin = $login;
	$msg = '';
	// show spectated login infos
	if($speclogin!=''){
		$login = $speclogin;
		$msg = '$s$i'.$_players[$login]['NickDraw'].':  ';
	}

	// player gaps
	if($_GameInfos['GameMode'] == LAPS && isset($_players[$login]['LapCheckpoints'])){
		$Checkpoints = 'LapCheckpoints';
		$BestCheckpoints = 'BestLapCheckpoints';
	}else{
		$Checkpoints = 'Checkpoints';
		$BestCheckpoints = 'BestCheckpoints';
	}
	$time = end($_players[$login][$Checkpoints]);
	$key = key($_players[$login][$Checkpoints]);

	if($_players[$login]['FinalTime']>=0){
		// final time
		$msg .= '$z$s$w'.MwTimeToString($_players[$login]['FinalTime']).'  ';
		
	}else{
		// check number
		$lap = $_players[$login]['LapNumber']+1;
		$msg .= '$z$s$n'.($lap>1 ? '$555'.$lap.'..$888':'$888').($key+1);
		// check time
		if(isset($_BestPlayersChecks[$login][$key]) && count($_BestPlayersChecks[$login][$key])>0){
			$diff = $time - $_BestPlayersChecks[$login][$key];
			if($diff>0)
				$msg .= '$f00.  $z$s$w$f22'.MwDiffTimeToString($diff);
			elseif($diff<0 || $_players[$login]['FinalTime']<=0)
				$msg .= '$f00.  $z$s$w$22f'.MwDiffTimeToString($diff);
			else
				$msg .= '$f00.  $z$s$i$22fRecord';
			
		}elseif(isset($_players[$login][$BestCheckpoints][$key]) && $_players[$login][$BestCheckpoints][$key]>0){
			$diff = $time - $_players[$login][$BestCheckpoints][$key];
			if($diff>0)
				$msg .= '.  $z$s$w$f22'.MwDiffTimeToString($diff);
			elseif($diff<0 || $_players[$login]['FinalTime']<=0)
				$msg .= '.  $z$s$w$22f'.MwDiffTimeToString($diff);
			else
				$msg .= '$f00.  $z$s$i$22fBest';
			
		}elseif($_players[$login]['FinalTime']<0){
			$msg .= '.  $z$s$w$ddd'.MwTimeToString($time);
		}
	}
	if($msg!=''){
		$xml = '<label posn="0 30.7 -60" halign="center" textsize="3" text="'.$msg.'"/>';
		//console("ml_liveinfosUpdatePlayerCPGapsXml - $showlogin - $xml");
		manialinksShow($showlogin,'ml_liveinfos.cp',$xml);
	}
}



//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove'
//--------------------------------------------------------------
function ml_liveinfosUpdatePlayerTopGapsXml($login,$action='show',$speclogin=''){
	global $_mldebug,$_players,$_StatusCode,$_BestPlayersChecks,$_GameInfos,$_BestChecks,$_IdealChecks,$_BestChecksName;
	// if the players disabled manialinks then do nothing
	if(!$_players[$login]['ML']['ShowML'])
		return;
	if($_mldebug>3) console("ml_liveinfosUpdatePlayerCPGapsXml('$login',$action) - ".$_players[$login]['Status']);

	if($action=='remove'){
		// remove manialink
		manialinksRemove($login,'ml_liveinfos.tops');
		return;
	}elseif($action=='hide'){
		// hide manialink
		manialinksHide($login,'ml_liveinfos.tops');
		return;
	}
	// show/refresh
	if(!$_players[$login]['ML']['Show.live'] || !$_players[$login]['ML']['Show.topgaps'] || 
		 ($_players[$login]['IsSpectator'] && $speclogin=='')){
		// none to show and opened : hide it
		if(manialinksIsOpened($login,'ml_liveinfos.tops'))
			manialinksHide($login,'ml_liveinfos.tops');
		return;
	}

	// show 
	$showlogin = $login;
	$msg = '';
	// show spectated login infos
	if($speclogin!='')
		$login = $speclogin;

	// Best Top gap
	if($_GameInfos['GameMode'] == LAPS && isset($_players[$login]['LapCheckpoints'])){
		$Checkpoints = 'LapCheckpoints';
		$BestCheckpoints = 'BestLapCheckpoints';
	}else{
		$Checkpoints = 'Checkpoints';
		$BestCheckpoints = 'BestCheckpoints';
	}
	$time = end($_players[$login][$Checkpoints]);
	$key = key($_players[$login][$Checkpoints]);

	if($_players[$login]['ChecksGaps']=='best'){
		if(isset($_BestChecks[$key]) && $_BestChecks[$key]>0){
			$msg .= '$n$s$bbb'.$_BestChecksName.'$z: ';
			$diff = $time - $_BestChecks[$key];
			if($diff>0)
				$msg .= '$z$w$s$b11'.MwDiffTimeToString($diff);
			else
				$msg .= '$z$w$s$11b'.MwDiffTimeToString($diff);
		}
		
	}elseif($_players[$login]['ChecksGaps']=='ideal'){
		if(isset($_IdealChecks[$key][$key]) && $_IdealChecks[$key][$key]>0){
			$msg .= '$n$s$bbbIdeal$z: ';
			$diff = $time - $_IdealChecks[$key][$key];
			if($diff>0)
				$msg .= '$z$w$s$b11'.MwDiffTimeToString($diff);
			else
				$msg .= '$z$w$s$11b'.MwDiffTimeToString($diff);
		}
		
	}elseif(isset($_players[$login]['ChecksGaps']['Name'])){
		if(isset($_players[$login]['ChecksGaps'][$key]) && $_players[$login]['ChecksGaps'][$key]>0){
			$msg .= '$n$s$bbb'.$_players[$login]['ChecksGaps']['Name'].'$z: ';
			$diff = $time - $_players[$login]['ChecksGaps'][$key];
			if($diff>0)
				$msg .= '$z$w$s$b11'.MwDiffTimeToString($diff);
			else
				$msg .= '$z$w$s$11b'.MwDiffTimeToString($diff);
		}
	}
	if($msg!=''){
		$xml = '<label posn="25 30.7 -60" textsize="3" text="'.$msg.'"/>';
		//console("ml_liveinfosUpdatePlayerTopGapsXml - $showlogin - $xml");
		manialinksShow($showlogin,'ml_liveinfos.tops',$xml);
	}
}


//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove'
//--------------------------------------------------------------
function ml_liveinfosUpdatePlayerPositionXml($login,$action='show'){
	global $_mldebug,$_players,$_StatusCode,$_BestPlayersChecks,$_GameInfos,$_BestChecks,$_IdealChecks,$_BestChecksName,$_players_positions,$_players_giveup,$_players_giveup2;
	if($login===true){
		foreach($_players as $login => &$pl){
			if(isset($pl['ML']) && $pl['Status2']<2 && $pl['ML']['ShowML'] && $pl['ML']['Show.live'])
				ml_liveinfosUpdatePlayerPositionXml($login,'refresh');
		}
		return;
	}
	// if the players disabled manialinks then do nothing
	if(!$_players[$login]['ML']['ShowML'])
		return;

	if($action=='remove'){
		// remove manialink
		manialinksRemove($login,'ml_liveinfos.pos');
		return;
	}elseif($action=='hide'){
		// hide manialink
		manialinksHide($login,'ml_liveinfos.pos');
		return;
	}
	// show/refresh
	if(!$_players[$login]['ML']['Show.live'] || !$_players[$login]['ML']['Show.position'] ||
		 $_GameInfos['GameMode'] == TA || $_GameInfos['GameMode'] == STUNTS){
		// none to show and opened : hide it
		if(manialinksIsOpened($login,'ml_liveinfos.pos'))
			manialinksHide($login,'ml_liveinfos.pos');
		return;
	}

	// Position
	$msg = '$w$s$fff'.($_players[$login]['Position']['Pos']+1).' $ccc/ '.(count($_players_positions)-$_players_giveup);
	if($_players_giveup2>0)
		$msg .= '$777+'.$_players_giveup2;
	$xml = '<label posn="-27.5 30.7 -60" textsize="3" halign="center" text="'.$msg.'"/>';
	//console("ml_liveinfosUpdatePlayerPositionXml - $login - $xml");
	manialinksShow($login,'ml_liveinfos.pos',$xml);
}


//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove'
//--------------------------------------------------------------
// other $action values :
//   1=position changed  (in TA: best rank changed)
//   2=checkpoint changed
//   4=diff with previous player changed  (not in TA)
//   8=diff with next player changed  (not in TA)
//  16=diff with first player changed  (in TA: best diff)
//  32=diff with previous2 player changed  (not in TA)
//  64=diff with next2 player changed  (not in TA)
// 128=team round scores changed
function ml_liveinfosUpdatePlayerLivePlayersXml($login,$action='show'){
	global $_mldebug,$_players,$_live_players_bg_xml,$_live_players_bg2_xml,$_players_positions,$_GameInfos,$_teams;
	if($login===true){
		foreach($_players as $login => &$pl){
			if(isset($pl['ML']) && $pl['Status2']<1 && $pl['ML']['ShowML'] && $pl['ML']['Show.live'])
				ml_liveinfosUpdatePlayerLivePlayersXml($login,$action);
		}
		return;
	}
	// if the players disabled manialinks then do nothing
	if(!$_players[$login]['ML']['ShowML'])
		return;
	//if($_mldebug>8) console("ml_liveinfosUpdatePlayerLivePlayersXml($login,$action)");

	if($action=='remove'){
		// remove manialink
		manialinksRemove($login,'ml_liveinfos.players.1');
		manialinksRemove($login,'ml_liveinfos.players.2');
		manialinksRemove($login,'ml_liveinfos.players.3');
		manialinksRemove($login,'ml_liveinfos.players.4');
		manialinksRemove($login,'ml_liveinfos.players.5');
		manialinksRemove($login,'ml_liveinfos.players.6');
		manialinksRemove($login,'ml_liveinfos.players.bg');
		return;
	}elseif($action=='hide'){
		// hide manialink
		manialinksHide($login,'ml_liveinfos.players.1');
		manialinksHide($login,'ml_liveinfos.players.2');
		manialinksHide($login,'ml_liveinfos.players.3');
		manialinksHide($login,'ml_liveinfos.players.4');
		manialinksHide($login,'ml_liveinfos.players.5');
		manialinksHide($login,'ml_liveinfos.players.6');
		manialinksHide($login,'ml_liveinfos.players.bg');
		return;
	}
	// show/refresh
	if(!$_players[$login]['ML']['Show.live'] || $_players[$login]['ML']['Show.liveplayers'] <= 0 ||
		 $_GameInfos['GameMode'] == TA || $_GameInfos['GameMode'] == STUNTS || $_players[$login]['Status2'] > 0){
		// none to show and opened : hide it
		if(manialinksIsOpened($login,'ml_liveinfos.players.bg')){
			manialinksHide($login,'ml_liveinfos.players.1');
			manialinksHide($login,'ml_liveinfos.players.2');
			manialinksHide($login,'ml_liveinfos.players.3');
			manialinksHide($login,'ml_liveinfos.players.4');
			manialinksHide($login,'ml_liveinfos.players.5');
			manialinksHide($login,'ml_liveinfos.players.6');
			manialinksHide($login,'ml_liveinfos.players.bg');
		}
		return;
	}
	$lip = $_players[$login]['ML']['Show.liveplayers'];
	// 2: +28 +77.3
	$dx = ($lip<2)? 0.0 : 28.0;
	$dy = ($lip<2)? 0.0 : 47.3;
	$dz = ($lip<2)? 0.0 : -16.0;

	// show bg if needed
	if(!manialinksIsOpened($login,'ml_liveinfos.players.bg')){
		if($lip<2)
			manialinksShow($login,'ml_liveinfos.players.bg',$_live_players_bg_xml);
		else
			manialinksShow($login,'ml_liveinfos.players.bg',$_live_players_bg2_xml);
	}

	// show parts
	if($action=='show' || $action=='refresh')
		$action = 255;

	// player
	$position = &$_players[$login]['Position'];
	$ppos = $position['Pos'];

	if($action & (1+128)){
		$pl = &$_players[$login];
		if($pl['TeamId']==0)     $color = $pl['FinalTime']<0 ? '$99f' : ($pl['FinalTime']>0 ? '$99f$i' : '$008' ); // blue
		elseif($pl['TeamId']==1) $color = $pl['FinalTime']<0 ? '$F99' : ($pl['FinalTime']>0 ? '$F99$i' : '$800' ); // red
		else                     $color = $pl['FinalTime']<0 ? '$fff' : ($pl['FinalTime']>0 ? '$fff$i' : '$888' ); // no team
		
		$msg = ($ppos<9 ? '$z$s$ff0 '.($ppos+1).'.  ' : '$z$s$ff0'.($ppos+1).'. ')
			.$color.$_players[$login]['NickDraw2'];

		if($lip>=2 && $_players[$login]['ML']['Show.position']){
			$xml = '';
		}else{
			$xml = sprintf('<label sizen="15 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>',
										 -64+$dx,-16.8+$dy,-44+$dz,$msg);
		}
		if($_GameInfos['GameMode'] == TEAM){
			$dx2 = ($lip<2)? 0.0 : 30.0;
			$msg2 = '$s$00f'.$_teams[0]['Score'].' $n$ddd<>$m $f00'.$_teams[1]['Score'];
			$xml .= sprintf('<label posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>',
											-49.5+$dx2,-16.8+$dy,-44+$dz,$msg2);
		}
		manialinksShow($login,'ml_liveinfos.players.4',$xml);
	}

	// opponents
	if($ppos>=0){
		/*
		$ppos=15;
		$action = 255;
		if($position['Next2Login']==''){
			$position['Next2Login'] = $login;
			$position['Next2DiffTime'] = 100560;
			$position['Next2DiffCheck'] = 5;
		}
		if($position['NextLogin']==''){
			$position['NextLogin'] = $login;
			$position['NextDiffTime'] = 5560;
			$position['NextDiffCheck'] = 2;
		}
		if($position['PrevLogin']==''){
			$position['PrevLogin'] = $login;
			$position['PrevDiffTime'] = -3200;
			$position['PrevDiffCheck'] = 0;
		}
		if($position['Prev2Login']==''){
			$position['Prev2Login'] = $login;
			$position['Prev2DiffTime'] = -4200;
			$position['Prev2DiffCheck'] = -1;
		}
		if($position['FirstLogin']==''){
			$position['FirstLogin'] = $login;
			$position['FirstDiffTime'] = -12400;
			$position['FirstDiffCheck'] = -10;
		}
		*/

		// first
		if($ppos>2 && $position['FirstLogin']!='' && isset($_players[$position['Prev2Login']])){
			if($action & 16){
				$pos = 0;
				$pl = &$_players[$position['FirstLogin']];
				// color
				if($pl['TeamId']==0)     $color = $pl['FinalTime']<0 ? '$66e' : ($pl['FinalTime']>0 ? '$66e$i' : '$006' ); // blue
				elseif($pl['TeamId']==1) $color = $pl['FinalTime']<0 ? '$E66' : ($pl['FinalTime']>0 ? '$E66$i' : '$600' ); // red
				else                     $color = $pl['FinalTime']<0 ? '$9aa' : ($pl['FinalTime']>0 ? '$ddd$i' : '$666' ); // no team

				$msg = ($pos<9 ? '$s$eee '.($pos+1).'.  ' : '$s$eee'.($pos+1).'. ')
					.$color.$pl['NickDraw2'];

				if($pl['FinalTime']==0)
					$msg2 = $color.'out';
				else{
					$msg2 = '$s'.$color.MwDiffTimeToString($position['FirstDiffTime']);
					if($position['FirstDiffCheck'])
						$msg2 .= '  '.$position['FirstDiffCheck'].'$n cp';
				}
				$xml = sprintf('<label sizen="14 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>'
											 .'<label sizen="12 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>',
											 -64+$dx,-9.6+$dy,-44+$dz,$msg,-49.5+$dx,-9.6+$dy,-44+$dz,$msg2);
				manialinksShow($login,'ml_liveinfos.players.1',$xml);
			}
		}elseif(manialinksIsOpened($login,'ml_liveinfos.players.1')){
			manialinksHide($login,'ml_liveinfos.players.1');
		}

		// prev2
		if($ppos>1 && $position['Prev2Login']!='' && isset($_players[$position['Prev2Login']])){
			if($action & 32){
				$pos = $ppos - 2;
				$pl = &$_players[$position['Prev2Login']];
				// color
				if($pl['TeamId']==0)     $color = $pl['FinalTime']<0 ? '$88f' : ($pl['FinalTime']>0 ? '$88f$i' : '$006' ); // blue
				elseif($pl['TeamId']==1) $color = $pl['FinalTime']<0 ? '$F88' : ($pl['FinalTime']>0 ? '$F88$i' : '$600' ); // red
				else                     $color = $pl['FinalTime']<0 ? '$edd' : ($pl['FinalTime']>0 ? '$fff$i' : '$666' ); // no team

				$msg = ($pos<9 ? '$z$s$eee '.($pos+1).'.  ' : '$z$s$eee'.($pos+1).'. ')
					.$color.$pl['NickDraw2'];

				if($pl['FinalTime']==0)
					$msg2 = $color.'out';
				else{
					$msg2 = '$s'.$color.MwDiffTimeToString($position['Prev2DiffTime']);
					if($position['Prev2DiffCheck'])
						$msg2 .= '  '.$position['Prev2DiffCheck'].'$n cp';
				}
				$xml = sprintf('<label sizen="14 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>'
											 .'<label sizen="12 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>',
											 -64+$dx,-12+$dy,-44+$dz,$msg,-49.5+$dx,-12+$dy,-44+$dz,$msg2);
				manialinksShow($login,'ml_liveinfos.players.2',$xml);
			}
		}elseif(manialinksIsOpened($login,'ml_liveinfos.players.2')){
			manialinksHide($login,'ml_liveinfos.players.2');
		}

		// prev
		if($ppos>0 && $position['PrevLogin']!='' && isset($_players[$position['PrevLogin']])){
			if($action & 4){
				$pos = $ppos - 1;
				$pl = &$_players[$position['PrevLogin']];
				// color
				if($pl['TeamId']==0)     $color = $pl['FinalTime']<0 ? '$88f' : ($pl['FinalTime']>0 ? '$88f$i' : '$006' ); // blue
				elseif($pl['TeamId']==1) $color = $pl['FinalTime']<0 ? '$F88' : ($pl['FinalTime']>0 ? '$F88$i' : '$600' ); // red
				else                     $color = $pl['FinalTime']<0 ? '$edd' : ($pl['FinalTime']>0 ? '$fff$i' : '$666' ); // no team

				$msg = ($pos<9 ? '$z$s$eee '.($pos+1).'.  ' : '$z$s$eee'.($pos+1).'. ')
					.$color.$pl['NickDraw2'];

				if($pl['FinalTime']==0)
					$msg2 = $color.'out';
				else{
					$msg2 = '$s'.$color.MwDiffTimeToString($position['PrevDiffTime']);
					if($position['PrevDiffCheck'])
						$msg2 .= '  '.$position['PrevDiffCheck'].'$n cp';
				}
				$xml = sprintf('<label sizen="14 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>'
											 .'<label sizen="12 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>',
											 -64+$dx,-14.4+$dy,-44+$dz,$msg,-49.5+$dx,-14.4+$dy,-44+$dz,$msg2);
				manialinksShow($login,'ml_liveinfos.players.3',$xml);
			}
		}else{
			manialinksHide($login,'ml_liveinfos.players.3');
		}

		// next
		if($position['NextLogin']!='' && isset($_players[$position['NextLogin']])){
			if($action & 8){
				$pos = $ppos + 1;
				$pl = &$_players[$position['NextLogin']];
				// color
				if($pl['TeamId']==0)     $color = $pl['FinalTime']<0 ? '$88f' : ($pl['FinalTime']>0 ? '$88f$i' : '$006' ); // blue
				elseif($pl['TeamId']==1) $color = $pl['FinalTime']<0 ? '$F88' : ($pl['FinalTime']>0 ? '$F88$i' : '$600' ); // red
				else                     $color = $pl['FinalTime']<0 ? '$edd' : ($pl['FinalTime']>0 ? '$fff$i' : '$666' ); // no team

				$msg = ($pos<9 ? '$z$s$eee '.($pos+1).'.  ' : '$z$s$eee'.($pos+1).'. ')
					.$color.$pl['NickDraw2'];

				if($pl['FinalTime']==0)
					$msg2 = $color.'out';
				else{
					$msg2 = '$s'.$color.MwDiffTimeToString($position['NextDiffTime']);
					if($position['NextDiffCheck'])
						$msg2 .= '  +'.$position['NextDiffCheck'].'$n cp';
				}
				$xml = sprintf('<label sizen="14 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>'
											 .'<label sizen="12 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>',
											 -64+$dx,-19.2+$dy,-44+$dz,$msg,-49.5+$dx,-19.2+$dy,-44+$dz,$msg2);
				manialinksShow($login,'ml_liveinfos.players.5',$xml);
			}
		}elseif(manialinksIsOpened($login,'ml_liveinfos.players.5')){
			manialinksHide($login,'ml_liveinfos.players.5');
		}

		// next2
		if($position['Next2Login']!='' && isset($_players[$position['Next2Login']])){
			if($action & 64){
				$pos = $ppos + 2;
				$pl = &$_players[$position['Next2Login']];
				// color
				if($pl['TeamId']==0)     $color = $pl['FinalTime']<0 ? '$88f' : ($pl['FinalTime']>0 ? '$88f$i' : '$006' ); // blue
				elseif($pl['TeamId']==1) $color = $pl['FinalTime']<0 ? '$F88' : ($pl['FinalTime']>0 ? '$F88$i' : '$600' ); // red
				else                     $color = $pl['FinalTime']<0 ? '$edd' : ($pl['FinalTime']>0 ? '$fff$i' : '$666' ); // no team

				$msg = ($pos<9 ? '$z$s$eee '.($pos+1).'.  ' : '$z$s$eee'.($pos+1).'. ')
					.$color.$pl['NickDraw2'];

				if($pl['FinalTime']==0)
					$msg2 = $color.'out';
				else{
					$msg2 = '$s'.$color.MwDiffTimeToString($position['Next2DiffTime']);
					if($position['Next2DiffCheck'])
						$msg2 .= '  +'.$position['Next2DiffCheck'].'$n cp';
				}
				$xml = sprintf('<label sizen="14 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>'
											 .'<label sizen="12 2" posn="%0.2F %0.2F %0.2F" textsize="2" text="%s"/>',
											 -64+$dx,-21.6+$dy,-44+$dz,$msg,-49.5+$dx,-21.6+$dy,-44+$dz,$msg2);
				manialinksShow($login,'ml_liveinfos.players.6',$xml);
			}
		}elseif(manialinksIsOpened($login,'ml_liveinfos.players.6')){
			manialinksHide($login,'ml_liveinfos.players.6');
		}

	}else{
		manialinksHide($login,'ml_liveinfos.players.1');
		manialinksHide($login,'ml_liveinfos.players.2');
		manialinksHide($login,'ml_liveinfos.players.3');
		manialinksHide($login,'ml_liveinfos.players.5');
		manialinksHide($login,'ml_liveinfos.players.6');
	}
	
	//manialinksHideHudPart('ml_liveinfos','checkpoint_list',$login);
}

?>
