<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      09.04.2023
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
registerPlugin('ml_main',13,1.0);
//
//
// defined menus:  'menu.main' , 'menu.fast' , 'menu.config' , 'menu.hud'
//
// 'menu.hud.live'
// $_liveinfos_default = true;
// $_players[$login]['ML']['Show.live']

// $_chatpanel_default = 2; // 0=off, 1=when not playing, 2=on
// $_players[$login]['ML']['Show.chat']
//
// $_playernumber_default = true;
// $_players[$login]['ML']['Show.plnum']
//
// $_netlost_admin_default = true;
// $_players[$login]['ML']['Show.netlost']
// 
// $_buddy_notify_default
// $_players[$login]['ML']['Show.buddynotify']

// $_playernumber_showforce = false;


//--------------------------------------------------------------
// show/hide an automatic timer
// set a time (ms) in future to show it, set -1 to hide it
// will be hidden automatically after 0 if not hidden before
//--------------------------------------------------------------
function ml_mainSetTimer($timer_ms=-1){
	global $_ml_main_timer,$_lastEverysecond;
	$timersec = floor($timer_ms / 1000);
	if($timersec > $_lastEverysecond + 2){
		$_ml_main_timer = $timersec;
		ml_mainUpdateTimerXml($_ml_main_timer);

	}elseif($_ml_main_timer > -1){
		$_ml_main_timer = -1;
		ml_mainUpdateTimerXml(-1);
	}
}


//--------------------------------------------------------------
// obsolete, use menus instead
//--------------------------------------------------------------
function ml_mainAddEntry($action,$locale_name){
}
//--------------------------------------------------------------
// obsolete, use menus instead
//--------------------------------------------------------------
function ml_mainRemoveEntry($action){
}
//--------------------------------------------------------------
// obsolete, use menus instead
//--------------------------------------------------------------
function ml_mainRefresh($login){
}



//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function ml_mainInit($event){
	global $_mldebug,$_ml_main_entries,$_ml_act,$_ml_main_xml,$_ml_main_gamemode_base_xml,$_ml_main_gamemode_base2_xml,$_ml_main_gamemode_base3_xml,$_ml_main_gamemode_xml,$_ml_main_chat_xml,$_ml_main_plnum_bg_xml,$_ml_main_plnum_xml,$_mlmain_players_actives,$_mlmain_players_spec,$_liveinfos_default,$_netlost_admin_default,$_netlost_string,$_netlost_xml,$_NetworkStats,$_mlmain_team0_num,$_mlmain_team1_num,$_chatpanel_default,$_playernumber_default,$_is_relay,$_playernumber_show,$_playernumber_showforce,$_ml_main_fwarmup_basexml,$_ml_FWarmUp_xml,$_ml_FWarmUp_show,$_ml_main_timer_bg_xml,$_ml_main_timer_basexml,$_ml_main_timer;
	if($_mldebug>3) console("ml_main.Event[$event]");
	$_ml_main_entries = array();
	$_mlmain_players_actives = 0;
	$_mlmain_players_spec = 0;
	$_mlmain_team0_num = 0;
	$_mlmain_team1_num = 0;
	$_ml_FWarmUp_xml = '';

	if(!isset($_liveinfos_default))
		$_liveinfos_default = !$_is_relay;

	if(!isset($_chatpanel_default))
		$_chatpanel_default = 2;

	if(!isset($_playernumber_default))
		$_playernumber_default = true;

	if(!isset($_netlost_admin_default))
		$_netlost_admin_default = !$_is_relay;

	if(!isset($_playernumber_showforce))
		$_playernumber_showforce = false;
	if($_playernumber_showforce)
		$_playernumber_show = 'showforce';
	else
		$_playernumber_show = 'show';

	$_NetworkStats['LostContacts'] = '';
	$_netlost_string = '';
	$_netlost_xml = '';

	$_ml_FWarmUp_show = false;

	$_ml_main_timer = -1;

	manialinksAddAction('ml_main.cross');
	manialinksAddAction('ml_main.gamemode');
	manialinksAddAction('ml_main.fteam');
	$_ml_main_xml = 
	'<quad sizen="16 2.2" posn="-65.0 48.4 -0.9" style="BgsPlayerCard" substyle="BgPlayerCardSmall" action='.$_ml_act['ml_main.gamemode'].' actionkey="2"/>'
	//.'<label sizen="12.4 1" posn="-62 48 -0.8" textsize="1" textcolor="dddd" text="$oTeamRounds"/>'
	.'<quad sizen="1.6 1.6" posn="-64.0 48.0 -0.8" style="BgsPlayerCard" substyle="BgPlayerCardSmall"/>'
	.'<quad sizen="1.4 1.4" posn="-63.9 47.9 -0.7" style="Icons64x64_1" substyle="Close" action="'.$_ml_act['ml_main.cross'].'" actionkey="1"/>';

	$_ml_main_gamemode_base_xml = 
	'<label sizen="12.4 1" posn="-62 48 -0.8" textsize="1" textcolor="dddd" text="$o%s"/>';

	$_ml_main_gamemode_base2_xml = 
	'<label sizen="11.4 1" posn="-62 48 -0.8" textsize="1" textcolor="dddd" text="$o%s"/>'
	.'<quad sizen="2.3 2.3" posn="-51.25 48.35 -0.7" style="Icons64x64_1" substyle="IconLeaguesLadder"/>'
	.'<quad sizen="2.3 2.3" posn="10 -46 -0.7" style="Icons64x64_1" substyle="IconLeaguesLadder" action='.$_ml_act['ml_main.gamemode'].'/>';

	$_ml_main_gamemode_base3_xml = 
	'<label sizen="11.4 1" posn="-62 48 -0.8" textsize="1" textcolor="dddd" text="$o%s"/>'
	.'<quad sizen="2.3 2.3" posn="-51.25 48.35 -0.7" style="Icons64x64_1" substyle="IconLeaguesLadder"/>'
	.'<quad sizen="2.3 2.3" posn="9.6 -46 -0.7" style="Icons64x64_1" substyle="IconLeaguesLadder" action='.$_ml_act['ml_main.gamemode'].'/>'
	.'<quad sizen="2.3 2.3" posn="9.6 -43.7 -0.7" style="Icons64x64_1" substyle="IconPlayers" action='.$_ml_act['ml_main.fteam'].'/>'; // Buddy or IconPlayers

	$_ml_main_gamemode_xml = $_ml_main_gamemode_base_xml;
	// $_ml_main_gamemode_xml = sprintf($_ml_main_gamemode_base_xml,$_GameModeString)
	// manialinksShowForce($login,'ml_main.gamemode',$_ml_main_gamemode_xml);

	manialinksAddAction('ml_main.chat.cross');
	$_ml_main_chat_xml = 
	'<quad sizen="1.6 1.6" posn="-63.9 -33.4 -0.8" style="BgsPlayerCard" substyle="BgPlayerCardSmall"/>'
	.'<quad sizen="1.4 1.4" posn="-63.8 -33.5 -0.7" style="Icons64x64_1" substyle="Close" action="'.$_ml_act['ml_main.chat.cross'].'"/>';

	$_ml_main_plnum_bg_xml = 
	'<quad sizen="7.6 2.2" posn="-65 31 -35.2" style="BgsPlayerCard" substyle="BgPlayerCardSmall"/>'; // "BgPlayerCardSmall"

	$_ml_main_fwarmup_basexml = 
	'<frame posn="48.4 17.7 -40">'
	.'<quad sizen="19 7.4" posn="0 0 -40" style="BgsPlayerCard" substyle="BgPlayerCard"/>'
	.'<label sizen="0 0" posn="11.1 -1.5 0" halign="right" style="TextRaceValueSmall">$f70$s%s</label>'
	.'<label sizen="0 0" posn="11.1 -4.3 0" halign="right" style="TextRaceValueSmall">$f70$s%s</label>'
	.'<quad sizen="4 4" posn="11.6 -1.5 0" style="%s" substyle="%s"/></frame>';

	$_ml_main_timer_bg_xml = 
	'<frame posn="50.0 22.2 -40">'
	.'<quad sizen="27 7.5" posn="0 0 -40" scale="0.6" style="BgsPlayerCard" substyle="BgPlayerCard"/>'
	.'<quad sizen="4 4" posn="10 -0.2 0" style="BgRaceScore2" substyle="SandTimer"/></frame>';

	$_ml_main_timer_basexml = 
	'<frame posn="50.0 22.2 -40">'
	.'<label sizen="13.2 0" posn="10.2 -0.5 0.1" scale="0.7" halign="right" style="TextRaceChrono">$s%s</label></frame>';

	manialinksAddAction('ml_main.quit');
	manialinksAddAction('ml_main.showml');

	manialinksGetHudPartControl('ml_main','chat');
}


function ml_mainServerStart($event){
	global $_is_relay,$_ml_main_xml,$_ml_main_gamemode_xml,$_ml_main_chat_xml;

	changeFGameMode('setstring');
	ml_mainBuildPlNumXml();
	ml_mainBuildFWarmUpXml();
	manialinksShowForce(true,'ml_main.cross',$_ml_main_xml);
	if(!$_is_relay){
		manialinksShowForce(true,'ml_main.gamemode',$_ml_main_gamemode_xml);
		manialinksShowForceOnRelay('ml_main.gamemode.relay',$_ml_main_gamemode_xml);
	}
	manialinksShowForce(true,'ml_main.chat.cross',$_ml_main_chat_xml);
}


function fgmodesRestoreInfos_Reverse($event,$restoretype,$liveage,$playerschanged,$rankingchanged){
	global $_is_relay,$_ml_main_xml,$_ml_main_gamemode_xml,$_ml_main_chat_xml;
	if(!$_is_relay){
		manialinksShowForce(true,'ml_main.gamemode',$_ml_main_gamemode_xml);
		manialinksShowForceOnRelay('ml_main.gamemode.relay',$_ml_main_gamemode_xml);
	}
}


function ml_mainBeginRace_Reverse($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
	global $_is_relay,$_ml_main_gamemode_xml,$_ml_main_gamemode_base_xml,$_ml_main_gamemode_base2_xml,$_ml_main_gamemode_base3_xml,$_GameModeString,$_FGameModes,$_is_relay;
	if($_is_relay)
		return;

	if(isset($_FGameModes[$_GameModeString]['Podium']) && $_FGameModes[$_GameModeString]['Podium']){
		if(isset($_FGameModes[$_GameModeString]['FTeams']) && $_FGameModes[$_GameModeString]['FTeams'])
			$_ml_main_gamemode_xml = sprintf($_ml_main_gamemode_base3_xml,$_GameModeString);
		else
			$_ml_main_gamemode_xml = sprintf($_ml_main_gamemode_base2_xml,$_GameModeString);
	}else{
		$_ml_main_gamemode_xml = sprintf($_ml_main_gamemode_base_xml,$_GameModeString);
	}

	if(!$_is_relay){
		manialinksShowForce(true,'ml_main.gamemode',$_ml_main_gamemode_xml);
		manialinksShowForceOnRelay('ml_main.gamemode.relay',$_ml_main_gamemode_xml);
	}
}


//--------------------------------------------------------------
// Player connect
//--------------------------------------------------------------
function ml_mainPlayerConnect($event,$login){
	global $_mldebug,$_is_relay,$_players,$_ml_main_xml,$_ml_main_gamemode_xml,$_ml_main_chat_xml,$_ml_main_plnum_bg_xml,$_ml_main_plnum_xml,$_liveinfos_default,$_netlost_admin_default,$_buddy_notify_default,$_chatpanel_default,$_playernumber_default,$_playernumber_show,$_playernumber_showforce,$_NextFWarmUp,$_ml_FWarmUp_xml,$_ml_FWarmUp_show;
  if(!is_string($login))
    $login = ''.$login;
	//console("ml_main.Event[$event]('$login')");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if($_NextFWarmUp > 0){
		if($_mldebug>3) console("ml_mainPlayerConnect({$login}):: show warmup to {$login} (NextFWarmUp={$_NextFWarmUp})");
		manialinksShowForce($login,'ml_main.fwarmup',$_ml_FWarmUp_xml);

	}elseif($_ml_FWarmUp_show != false){
		if($_mldebug>3) console("ml_mainPlayerConnect({$login}):: show warmup to {$login}. (FWarmUp_show={$_ml_FWarmUp_show})");
		
		ml_mainFWarmUpShow($_ml_FWarmUp_show,$login);
	}

	manialinksShowForce($login,'ml_main.cross',$_ml_main_xml);
	if(!$_is_relay)
		manialinksShowForce($login,'ml_main.gamemode',$_ml_main_gamemode_xml);
	manialinksShowForce($login,'ml_main.chat.cross',$_ml_main_chat_xml);
	manialinksSetHudPart(null,true,$login);

	if(!isset($_players[$login]['ML']['Show.live']))
		$_players[$login]['ML']['Show.live'] = $_liveinfos_default;

	if(!isset($_players[$login]['ML']['Show.netlost']))
		$_players[$login]['ML']['Show.netlost'] = verifyAdmin($login) ? $_netlost_admin_default : false;

	if($_buddy_notify_default!==null){
		if(!isset($_players[$login]['ML']['Show.buddynotify']))
			$_players[$login]['ML']['Show.buddynotify'] = ($_buddy_notify_default==true);
		addCall(true,'SetBuddyNotification',$login,$_players[$login]['ML']['Show.buddynotify']);
	}

	if(!isset($_players[$login]['ML']['Show.chat']))
		$_players[$login]['ML']['Show.chat'] = $_chatpanel_default;
	if($_players[$login]['ML']['Show.chat']==0)
		manialinksHideHudPart('ml_main','chat',$login);

	if(!isset($_players[$login]['ML']['Show.plnum']))
		$_players[$login]['ML']['Show.plnum'] = $_playernumber_default;
	if(($_players[$login]['ML']['ShowML'] || $_playernumber_showforce) && $_players[$login]['ML']['Show.plnum']){
		manialinksSet($login,'ml_main.plnum.bg',$_playernumber_show,$_ml_main_plnum_bg_xml);
		manialinksSet($login,'ml_main.plnum',$_playernumber_show,$_ml_main_plnum_xml);
	}

}


//--------------------------------------------------------------
// Player ShowML
//--------------------------------------------------------------
function ml_mainPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_is_relay,$_players,$_ml_main_xml,$_ml_main_gamemode_xml,$_ml_main_plnum_bg_xml,$_ml_main_plnum_xml,$_ml_main_chat_xml,$_netlost_xml,$_netlost_string,$_playernumber_show,$_playernumber_showforce,$_NextFWarmUp,$_ml_FWarmUp_xml,$_ml_FWarmUp_show;
	if(isset($_players[$login]['Relayed']) && $_players[$login]['Relayed'])
		return;

  if($_mldebug>5) console("ml_main.Event[$event]($login,$ShowML)(".isInteractEvent().')');

	if($_NextFWarmUp > 0){
		if($_mldebug>3) console("ml_mainPlayerShowML:: show warmup to {$login} (NextFWarmUp={$_NextFWarmUp})");
		manialinksShowForce($login,'ml_main.fwarmup',$_ml_FWarmUp_xml);

	}elseif($_ml_FWarmUp_show != false){
		if($_mldebug>3) console("ml_mainPlayerShowML:: show warmup to {$login}. (FWarmUp_show={$_ml_FWarmUp_show})");
		ml_mainFWarmUpShow($_ml_FWarmUp_show,$login);
	}

	if($ShowML){
		manialinksShowForce($login,'ml_main.cross',$_ml_main_xml);
		if(!$_is_relay)
			manialinksShowForce($login,'ml_main.gamemode',$_ml_main_gamemode_xml);
		manialinksShowForce($login,'ml_main.chat.cross',$_ml_main_chat_xml);
	}

	if($_players[$login]['ML']['Show.chat']==0){
		manialinksHideHudPart('ml_main','chat',$login);
		
	}else{
		manialinksShowHudPart('ml_main','chat',$login);
	}
	
	if($ShowML && $_players[$login]['ML']['Show.netlost'] && $_netlost_string!=''){
		manialinksShow($login,'ml_main.netlost',$_netlost_xml);
	}else{
		manialinksHide($login,'ml_main.netlost');
	}
	
	if(($ShowML || $_playernumber_showforce) && $_players[$login]['ML']['Show.plnum']){
		manialinksSet($login,'ml_main.plnum.bg',$_playernumber_show,$_ml_main_plnum_bg_xml);
		manialinksSet($login,'ml_main.plnum',$_playernumber_show,$_ml_main_plnum_xml);

	}else{
		manialinksHide($login,'ml_main.plnum');
		manialinksHide($login,'ml_main.plnum.bg');
	}
}


//--------------------------------------------------------------
// Player MenuBuild
//--------------------------------------------------------------
function ml_mainPlayerMenuBuild($event,$login){
	global $_Game,$_players,$_GameInfos,$_FASTver,$_buddy_notify_default;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	// main menu
	$menu = array('Show'=>true,'X'=>-65.4,'Y'=>46.1,
								'Style'=>'style="BgsPlayerCard" substyle="BgPlayerCardSmall"',
								'StyleOn'=>'style="BgsPlayerCard" substyle="BgPlayerCardSmall"',
								'StyleOff'=>'style="BgsPlayerCard" substyle="BgPlayerCardSmall"',
								'StyleMenu'=>'style="BgsPlayerCard" substyle="BgPlayerCardSmall"',
								'StyleSel'=>'style="BgsPlayerCard" substyle="BgPlayerCardSmall"',
								'Color'=>'$eee',
								'ColorOn'=>'$aaf',
								'ColorOff'=>'$bbb$i',
								'ColorMenu'=>'$edd',
								'ColorSel'=>'$f99$i',
								'Items'=>array());
	ml_menusNewMenu($login,'menu.main',$menu);
	// ml_menusShowMenu($login,'menu.main') is called in _Post event to permit 
	// to other plugins to add items without redrawing

	// fast submenu
	$menu_fast = array('Name'=>'Fast '.$_FASTver.' ...','Menu'=>array('DefaultStyles'=>true,'Width'=>13,'Items'=>array()));
	$menu_fast['Menu']['Items']['menu.fast.info'] = array('Name'=>'$l[http://dedimania.net/tmstats/?do=donation]$sFast '.$_FASTver.'$l','Type'=>'hide');

	if(verifyAdmin($login)){
		// server config submenu
		$menu_fast['Menu']['Items']['menu.config'] = array('Name'=>localeText($login,'menu.config'),
																											 'Menu'=>array('Width'=>15,'Items'=>array()));
	}
	ml_menusAddItem($login, 'menu.main', 'menu.fast', $menu_fast);

	// config menu -> buddy_notify (serveur default)
	if($_buddy_notify_default!==null){
		ml_menusAddItem($login, 'menu.config', 'menu.config.buddynotify',
										array('Name'=>array(localeText($login,'menu.config.buddynotify.on'),
																				localeText($login,'menu.config.buddynotify.off')),
													'Type'=>'bool',
													'State'=>($_buddy_notify_default==true)));
	}

	// hud: live & live submenu
	$menu_hud = array('Name'=>localeText($login,'menu.hud'),
										'Menu'=>array('DefaultStyles'=>true,'Width'=>15,'Items'=>array()));
	$menu_hud['Menu']['Items']['menu.hud.live'] = array('Name'=>array(localeText($login,'menu.hud.live.on'),
																																		localeText($login,'menu.hud.live.off')),
																											'Type'=>'bool',
																											'State'=>$_players[$login]['ML']['Show.live']);
	$menu_hud['Menu']['Items']['menu.hud.live.menu'] = array('Name'=>localeText($login,'menu.hud.live'),
																													 'Menu'=>array('Items'=>array()),
																													 'Show'=>$_players[$login]['ML']['Show.live']);
	ml_menusAddItem($login, 'menu.main', 'menu.hud', $menu_hud);

	// hud: netlost
	ml_menusAddItem($login, 'menu.hud','menu.hud.netlost',
									array('Name'=>array(localeText($login,'menu.hud.netlost.on'),
																			localeText($login,'menu.hud.netlost.off')),
												'Type'=>'bool',
												'State'=>$_players[$login]['ML']['Show.netlost']));

	// hud: buddy_notify (player)
	if($_buddy_notify_default!==null){
		ml_menusAddItem($login, 'menu.hud','menu.hud.buddynotify',
										array('Name'=>array(localeText($login,'menu.hud.buddynotify.on'),
																				localeText($login,'menu.hud.buddynotify.off')),
													'Type'=>'bool',
													'State'=>$_players[$login]['ML']['Show.buddynotify']));
	}

	// num players
	ml_menusAddItem($login, 'menu.hud','menu.hud.plnum',
									array('Name'=>array(localeText($login,'menu.hud.plnum.on'),
																			localeText($login,'menu.hud.plnum.off')),
												'Type'=>'bool',
												'State'=>$_players[$login]['ML']['Show.plnum']));

	// hud: chat
	ml_menusAddItem($login, 'menu.hud','menu.hud.chat',
									array('Name'=>array(localeText($login,'menu.hud.chat.off'),
																			localeText($login,'menu.hud.chat.noplay'),
																			localeText($login,'menu.hud.chat.on')),
												'Type'=>'multi','multioffval'=>0,
												'State'=>$_players[$login]['ML']['Show.chat']));
}


//--------------------------------------------------------------
// PlayerMenuBuild : 
//--------------------------------------------------------------
function ml_mainPlayerMenuBuild_Post($event,$login){
	global $_mldebug,$_players;
	if($_mldebug>6) console("ml_main.Event_Post[$event]('$login')");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	ml_menusShowMenu($login,'menu.main');
}


//--------------------------------------------------------------
// PlayerMenuAction : (event from ml_menus plugin)
//--------------------------------------------------------------
function ml_mainPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_players,$_ml_main_plnum_bg_xml,$_ml_main_plnum_xml,$_netlost_xml,$_buddy_notify_default,$_playernumber_show,$_playernumber_showforce;
	//if($_mldebug>6) console("ml_main.Event[$event]('$login',$action,$state)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	
	$msg = localeText(null,'server_message').localeText(null,'interact');
	if($action=='menu.hud.live'){
		$_players[$login]['ML']['Show.live'] = $state;
		if($state){
			ml_menusShowItem($login, 'menu.hud.live.menu');
			$msg .= localeText($login,'chat.hud.live.on');
		}else{
			ml_menusHideItem($login, 'menu.hud.live.menu');
			$msg .= localeText($login,'chat.hud.live.off');
		}
		addCall(null,'ChatSendToLogin', $msg, $login);

	}elseif($action=='menu.hud.chat'){
		$_players[$login]['ML']['Show.chat'] = $state;
		if($state==0){
			manialinksHideHudPart('ml_main','chat',$login);
			$msg .= localeText($login,'chat.hud.chat.off');

		}elseif($state==1){
			manialinksShowHudPart('ml_main','chat',$login);
			$msg .= localeText($login,'chat.hud.chat.noplay');

		}else{
			manialinksShowHudPart('ml_main','chat',$login);
			$msg .= localeText($login,'chat.hud.chat.on');
		}
		addCall(null,'ChatSendToLogin', $msg, $login);

	}elseif($action=='menu.hud.plnum'){
		$_players[$login]['ML']['Show.plnum'] = $state;
		if($state){
			if($_players[$login]['ML']['ShowML'] || $_playernumber_showforce){
				manialinksSet($login,'ml_main.plnum.bg',$_playernumber_show,$_ml_main_plnum_bg_xml);
				manialinksSet($login,'ml_main.plnum',$_playernumber_show,$_ml_main_plnum_xml);
			}
			$msg .= localeText($login,'chat.hud.plnum.on');

		}else{
			manialinksHide($login,'ml_main.plnum');
			manialinksHide($login,'ml_main.plnum.bg');
			$msg .= localeText($login,'chat.hud.plnum.off');
		}
		addCall(null,'ChatSendToLogin', $msg, $login);

	}elseif($action=='menu.hud.netlost'){
		$_players[$login]['ML']['Show.netlost'] = $state;
		if($state){
			if($_players[$login]['ML']['ShowML']){
				manialinksShow($login,'ml_main.netlost',$_netlost_xml);
			}
			$msg .= localeText($login,'chat.hud.netlost.on');

		}else{
			manialinksHide($login,'ml_main.netlost');
			$msg .= localeText($login,'chat.hud.netlost.off');
		}
		addCall(null,'ChatSendToLogin', $msg, $login);

	}elseif($action=='menu.config.buddynotify'){
		$_buddy_notify_default = $state;
		if($state)
			$msg .= localeText($login,'chat.config.buddynotify.on');
		else
			$msg .= localeText($login,'chat.config.buddynotify.off');
		addCall(true,'SetBuddyNotification','',$_buddy_notify_default);
		addCall(null,'ChatSendToLogin', $msg, $login);

	}elseif($action=='menu.hud.buddynotify'){
		$_players[$login]['ML']['Show.buddynotify'] = $state;
		if($state)
			$msg .= localeText($login,'chat.hud.buddynotify.on');
		else
			$msg .= localeText($login,'chat.hud.buddynotify.off');
		addCall(true,'SetBuddyNotification',$login,$_players[$login]['ML']['Show.buddynotify']);
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}


function ml_mainPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_mldebug,$_players;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_players[$login]['ML']))
		return;
	$pml = &$_players[$login]['ML'];

	if($action=='ml_main.cross'){
		dropEvent();
		chat_ml($login, $login, null);
		//ml_mainDialogXml($login);

	}elseif($action=='ml_main.chat.cross'){
		dropEvent();
		ml_menusSet($login,array('menu.hud.chat','clic'));

	}elseif($action=='ml_main.showml'){
		dropEvent();
		ml_mainDialogXml($login);
		chat_ml($login, $login, null);
		
	}elseif($action=='ml_main.quit'){
		dropEvent();
		ml_mainDialogXml($login);
		addCall(true,'Kick',$login,"\n\n      \$w\$00fYou asked to quit the server !\$z\n\n");
	}
}


function ml_mainEverysecond($event,$seconds){
	//console("howto.Event[$event]($seconds)");
	global $_mldebug,$_players,$_players_actives,$_players_spec,$_mlmain_players_actives,$_mlmain_players_spec,$_ml_main_plnum_xml,$_netlost_xml,$_netlost_string,$_NetworkStats,$_StatusCode,$_mlmain_team0_num,$_mlmain_team1_num,$_teams,$_playernumber_show,$_playernumber_showforce,$_ml_main_timer,$_currentTime,$_lastEverysecond;

	if($_ml_main_timer > -1){
		if($_ml_main_timer < $_lastEverysecond)
			$_ml_main_timer = -1;
		ml_mainUpdateTimerXml($_ml_main_timer);
	}

	if($_mlmain_players_actives!=$_players_actives || $_mlmain_players_spec!=$_players_spec ||
		 $_mlmain_team0_num!=$_teams[0]['Num'] ||$_mlmain_team1_num!=$_teams[1]['Num']){
		// update player numbers
		if($_mldebug>8) console("ml_main.Event[$event]('$seconds') $_players_actives:$_mlmain_players_actives , $_players_spec:$_mlmain_players_spec");
		$_mlmain_players_actives = $_players_actives;
		$_mlmain_players_spec = $_players_spec;
		$_mlmain_team0_num = $_teams[0]['Num'];
		$_mlmain_team1_num = $_teams[1]['Num'];
		ml_mainBuildPlNumXml();
		foreach($_players as $login => &$pl){
			if($pl['Active'] && !$pl['Relayed'] && ($pl['ML']['ShowML'] || $_playernumber_showforce) && $pl['ML']['Show.plnum']){
				//console("update num players ($login) !");
				manialinksSet($login,'ml_main.plnum',$_playernumber_show,$_ml_main_plnum_xml);
			}
		}
	}

	if($_StatusCode>=3 && $_StatusCode<=4 && $_netlost_string!=$_NetworkStats['LostContacts']){
		$_netlost_string = $_NetworkStats['LostContacts'];
		$_netlost_xml = '<label posn="-61 -29 -40" textsize="1" text="$ccc$sNetLost: '.$_netlost_string.'"/>';
		foreach($_players as $login => &$pl){
			if($pl['Active'] && !$pl['Relayed'] && $pl['ML']['ShowML'] && $pl['ML']['Show.netlost']){
				if($_netlost_string!='')
					manialinksShow($login,'ml_main.netlost',$_netlost_xml);
				else
					manialinksHide($login,'ml_main.netlost');
			}
		}
	}
}


function ml_mainStatusChanged($event,$Status,$StatusCode){
	global $_mldebug,$_players,$_netlost_string;
	if($StatusCode==5){
		foreach($_players as $login => &$pl){
			if($pl['Active'] && !$pl['Relayed'] && $pl['ML']['ShowML'] && $pl['ML']['Show.netlost'] && $_netlost_string!='')
				manialinksHide($login,'ml_main.netlost');
		}
	}
}


function ml_mainFWarmUpShow($wuinfo='',$login=true){
	global $_mldebug,$_ml_FWarmUp_xml,$_ml_FWarmUp_show,$_is_relay;
	if($_is_relay)
		return;
	ml_mainBuildFWarmUpXml($wuinfo);
	if($_mldebug>3) console("ml_mainFWarmUpShow({$wuinfo},{$login}):: show FWarmUp");
	manialinksShowForce($login,'ml_main.fwarmup',$_ml_FWarmUp_xml);
	if($login === true){
		$_ml_FWarmUp_show = $wuinfo;
		manialinksShowForceOnRelay('ml_main.fwarmup.relay',$_ml_FWarmUp_xml);
	}
}


function ml_mainFWarmUpHide($login=true){
	global $_mldebug,$_ml_FWarmUp_show,$_is_relay;
	if($_is_relay)
		return;
	if($_mldebug>3) console("ml_mainFWarmUpShow({$login}):: hide FWarmUp");
	manialinksHide($login,'ml_main.fwarmup');
	if($login === true){
		$_ml_FWarmUp_show = false;
		manialinksHideForceOnRelay('ml_main.fwarmup.relay');
	}
}


function ml_mainFWarmUpChange($event,$status){
	global $_mldebug,$_NextFWarmUp,$_ml_FWarmUp_xml,$_is_relay;
	if($_is_relay)
		return;
	//console("ml_main.Event[$event]($status)");
	if($_NextFWarmUp > 0){
		ml_mainBuildFWarmUpXml();
		if($_mldebug>3) console("ml_mainFWarmUpChange:: show FWarmUp (NextFWarmUp={$_NextFWarmUp})");
		manialinksShowForce(true,'ml_main.fwarmup',$_ml_FWarmUp_xml);
		manialinksShowForceOnRelay('ml_main.fwarmup.relay',$_ml_FWarmUp_xml);
	}else{
		if($_mldebug>3) console("ml_mainFWarmUpChange:: hide FWarmUp (NextFWarmUp={$_NextFWarmUp})");
		manialinksHide(true,'ml_main.fwarmup');
		manialinksHideForceOnRelay('ml_main.fwarmup.relay');
	}
}


function ml_mainBuildFWarmUpXml($wuinfo=null){
	global $_mldebug,$_FWarmUpState,$_FWarmUp,$_NextFWarmUp,$_ml_main_fwarmup_basexml,$_ml_FWarmUp_xml;
	// build xml
	if($wuinfo === null){
		$fwuduration = $_FWarmUp > 0 ? $_FWarmUp : $_NextFWarmUp;
		$_ml_FWarmUp_xml = sprintf($_ml_main_fwarmup_basexml,'Warm-up',"{$_FWarmUpState} / {$fwuduration}",'BgRaceScore2','Warmup');
	}else{
		$_ml_FWarmUp_xml = sprintf($_ml_main_fwarmup_basexml,'Warm-up',$wuinfo,'BgRaceScore2','Warmup');
	}
	// 'BgRaceScore2','Warmup'
	// 'Icons64x64_1','RestartRace'
}


function ml_mainBeginRound($event){
	global $_mlmain_players_actives;
	$_mlmain_players_actives = -1;
	ml_mainEverysecond($event,-1);
}


function ml_mainBuildPlNumXml(){
	global $_mldebug,$_players,$_ml_main_plnum_xml,$_mlmain_players_actives,$_mlmain_players_spec,$_GameInfos,$_players_round_current,$_teams,$_Ranking;
	$_ml_main_plnum_xml = sprintf('<label posn="-63.8 30.8 -35.19" textsize="2" text="$fff$s%d $z$ccc+%d"/>',
																($_mlmain_players_actives-$_mlmain_players_spec),$_mlmain_players_spec);

	// if round mode then draw round number in bottom left
	if($_GameInfos['GameMode'] == ROUNDS || $_GameInfos['GameMode'] == TEAM || $_GameInfos['GameMode'] == CUP){
		$teamsg = '';
		if($_GameInfos['GameMode'] == TEAM && isset($_Ranking[1]['Score']))
			$teamsg = '$333/ $339'.$_teams[0]['Num'].'$n$aaa vs $m$933'.$_teams[1]['Num']
				.' $333/ $s$00f'.$_Ranking[0]['Score'].'$n$ddd <> $m$f00'.$_Ranking[1]['Score'];
		$_ml_main_plnum_xml .= sprintf('<label posn="-62.6 -46.4 10" textsize="1" text="$cccr:$fff %d %s"/>',
																	 $_players_round_current,$teamsg);
	}
}


function ml_mainDialogXml($login){
	global $_mldebug,$_players,$_ml_act;
  if(!is_string($login))
    $login = ''.$login;
	//if($_mldebug>5) console("ml_mainUpdateXml - $login");

	if(manialinksIsOpened($login,'ml_main.dialog')){
		manialinksHide($login,'ml_main.dialog');
		return;
	}
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_players[$login]['ML']['ShowML']))
		return;
	$showml = $_players[$login]['ML']['ShowML'];
	
	$xml = "<frame posn='-48 41 15.9'>"
		.'<quad sizen="30 12" posn="0 0 0.01" halign="center" valign="center" style="Bgs1" substyle="BgWindow1" action=0/>'
		.'<quad sizen="30 12" posn="0 0 0.01" halign="center" valign="center" style="Bgs1" substyle="BgWindow1"/>'
		.'<quad sizen="28 3" posn="0 4 0.02" halign="center" valign="center" style="Bgs1" substyle="BgTitle3_4"/>'
		.'<quad sizen="2 2" posn="-13 4 0.03" valign="center" style="Icons64x64_1" substyle="Close" action="'.$_ml_act['ml_main.cross'].'"/>'

		."<label posn='0 0 0.01' halign='center' valign='center' style='CardButtonSmall' action='"
		.$_ml_act['ml_main.quit']."' text='Quit server'/>"

		."<label posn='0 -3 0.01' halign='center' valign='center' style='CardButtonSmall' action='"
		.$_ml_act['ml_main.showml']."' text='".($showml?'Hide hud (manialinks)':'Show Hud (manialinks)')."'/>"

		."</frame>";
	manialinksShowForce($login,'ml_main.dialog',$xml);
}


function ml_mainUpdateTimerXml($timer_sec){
	global $_mldebug,$_players,$_ml_main_timer_bg_xml,$_ml_main_timer_basexml,$_currentTime,$_lastEverysecond;
	$timerval = (int) ($timer_sec - $_lastEverysecond);
	
	if($timerval < 0){
		// hide
		foreach($_players as $login => &$pl){
			$login = ''.$login;
			if($pl['Active'] &&  !$pl['Relayed'] && manialinksIsOpened($login,'ml_main.timer')){
				manialinksHide($login,'ml_main.timer');
				manialinksHide($login,'ml_main.timer.bg');
			}
		}
		return;
	}
	// show
	$xml = sprintf($_ml_main_timer_basexml,MwTimeToString2($timerval*1000,false));
	
	foreach($_players as $login => &$pl){
		$login = ''.$login;
		if($pl['Active'] &&  !$pl['Relayed']){
			if(!manialinksIsOpened($login,'ml_main.timer.bg'))
				manialinksShowForce($login,'ml_main.timer.bg',$_ml_main_timer_bg_xml);
			manialinksShowForce($login,'ml_main.timer',$xml);
		}
	}
}



function ml_mainPlayerCheckpoint($event,$login,$time,$lapnum,$checkpt){
	global $_mldebug,$_players,$_LastCheckNum;
	//console("ml_main.Event[$event]('$login',$time,$lapnum,$checkpt)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if($checkpt==0 && $_players[$login]['ML']['Show.chat']==1 &&
		 ($lapnum==0 || $checkpt < $_LastCheckNum)){
		manialinksHideHudPart('ml_main','chat',$login);
	}
}


function ml_mainPlayerStatusChange($event,$login,$status,$oldstatus){
	global $_mldebug,$_players;
	//console("ml_main.Event[$event]('$login',$status,$oldstatus)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if($oldstatus==0 && $_players[$login]['ML']['Show.chat']==1){
		manialinksShowHudPart('ml_main','chat',$login);
	}
}


function ml_mainPlayerFinish($event,$login,$time){
	global $_mldebug,$_players;
	//console("ml_main.Event[$event]('$login',$time)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if($time==0 && $_players[$login]['ML']['Show.chat']==1){
		manialinksShowHudPart('ml_main','chat',$login);
	}
}



?>
