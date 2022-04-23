<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      26.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// needed plugins: manialinks
//
// draw next map info
// 
// $_mapinfo_default = 0;
// $_players[$login]['ML']['Show.mapinfo']

registerPlugin('ml_mapinfo',14,1.0);


//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function ml_mapinfoInit($event){
	global $_mldebug,$_mapinfo_xml,$_mapinfo_default,$_is_relay;
	if($_mldebug>4) console("ml_mapinfo.Event[$event]");

	if(!isset($_mapinfo_default))
		$_mapinfo_default = !$_is_relay;
	$_mapinfo_default = $_mapinfo_default+0;

	$_mapinfo_xml = '';
	
	manialinksAddId('ml_mapinfo');
	manialinksGetHudPartControl('ml_mapinfo','challenge_info');
}


//--------------------------------------------------------------
// PlayerConnect : (event from server callback)
//--------------------------------------------------------------
function ml_mapinfoPlayerConnect($event,$login){
	global $_mldebug,$_Game,$_players,$_mapinfo_default;
	if($_mldebug>4) console("ml_mapinfo.Event[$event]('$login')");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	
	if(!isset($_players[$login]['ML']['Show.mapinfo']))
		$_players[$login]['ML']['Show.mapinfo'] = $_mapinfo_default;

	if($_players[$login]['ML']['ShowML']){
		if($_players[$login]['ML']['Show.mapinfo']>0)
			manialinksHideHudPart('ml_mapinfo','challenge_info',$login);
		if($_players[$login]['ML']['Show.mapinfo']==1)
			ml_mapinfoUpdateXml($login,'show');
	}
}


//--------------------------------------------------------------
// PlayerShowML : redraw
//--------------------------------------------------------------
function ml_mapinfoPlayerShowML_Post($event,$login,$ShowML){
	global $_mldebug,$_players;
	if($_mldebug>5) console("ml_mapinfo.Event[$event]($login,$ShowML)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if($_players[$login]['ML']['Show.mapinfo']>0)
		manialinksHideHudPart('ml_mapinfo','challenge_info',$login);
	else
		manialinksShowHudPart('ml_mapinfo','challenge_info',$login);

	if($ShowML && $_players[$login]['ML']['Show.mapinfo']==1){
		ml_mapinfoUpdateXml($login,'show');
	}else{
		ml_mapinfoUpdateXml($login,'hide');
	}
}


//--------------------------------------------------------------
// PlayerMenuBuild : (event from server callback)
//--------------------------------------------------------------
function ml_mapinfoPlayerMenuBuild($event,$login){
	global $_mldebug,$_Game,$_players;
		if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	ml_menusAddItem($login, 'menu.hud', 'menu.hud.mapinfo',
									array('Name'=>array(localeText($login,'menu.hud.mapinfo.off'),
																			localeText($login,'menu.hud.mapinfo.on'),
																			localeText($login,'menu.hud.mapinfo.none')),
									'Type'=>'multi','multioffval'=>0,
									'State'=>$_players[$login]['ML']['Show.mapinfo']));
}


//--------------------------------------------------------------
// PlayerMenuBuild : (event from server callback)
//--------------------------------------------------------------
function ml_mapinfoPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_players[$login]['ML']))
		return;

	$msg = localeText(null,'server_message').localeText(null,'interact');
	if($action=='menu.hud.mapinfo'){
		$_players[$login]['ML']['Show.mapinfo'] = $state;
		if($state==2){
			if($_players[$login]['ML']['ShowML']){
				manialinksHideHudPart('ml_mapinfo','challenge_info',$login);
				ml_mapinfoUpdateXml($login,'hide');
			}
			$msg .= localeText($login,'chat.hud.mapinfo.none');

		}elseif($state==1){
			if($_players[$login]['ML']['ShowML']){
				manialinksHideHudPart('ml_mapinfo','challenge_info',$login);
				ml_mapinfoUpdateXml($login,'show');
			}
			$msg .= localeText($login,'chat.hud.mapinfo.on');

		}else{
			ml_mapinfoUpdateXml($login,'hide');
			manialinksShowHudPart('ml_mapinfo','challenge_info',$login);
			$msg .= localeText($login,'chat.hud.mapinfo.off');
		}
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}


function ml_mapinfoBeginRace($event){
	global $_mldebug;
	//console("ml_mapinfo.Event[$event]");
	ml_mapinfoBuildXml(true);
}


function ml_mapinfoChallengeListModified($event,$curchalindex,$nextchalindex,$islistmodified){
	global $_mldebug;
	if($_mldebug>5) console("ml_mapinfo.Event[$event]($curchalindex,$nextchalindex,$islistmodified)");
	ml_mapinfoBuildXml(true);
}



//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'refresh', 'hide', 'remove'
//--------------------------------------------------------------
function ml_mapinfoUpdateXml($login,$action='show'){
	global $_mldebug,$_players,$_mapinfo_xml;
	if($login===true){
		foreach($_players as $login => &$pl){
			if($pl['Active'])
				ml_mapinfoUpdateXml($login);
		}
		return;
	}
	// if the players disabled manialinks then do nothing
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_players[$login]['ML']['ShowML']))
		return;
	if($_mldebug>6) console("ml_mapinfoUpdateXml({$login},{$action})");

	if($action=='remove'){
		// remove manialink
		manialinksRemove($login,'ml_mapinfo');
		return;

	}elseif($action=='hide'){
		// hide manialink
		manialinksHide($login,'ml_mapinfo');
		return;

	}elseif($_players[$login]['ML']['Show.mapinfo']!=1){
		// mapinfo disabled
		if(manialinksIsOpened($login,'ml_mapinfo'))
			manialinksHide($login,'ml_mapinfo');
		return;
	}
	if($action=='refresh' && !manialinksIsOpened($login,'ml_mapinfo')){
		// refresh but not opened: do nothing
		return;
	}

	// show/refresh
	manialinksShow($login,'ml_mapinfo',$_mapinfo_xml);
}


function ml_mapinfoBuildXml($refresh_all=false){
	global $_mldebug,$_mapinfo_xml,$_players,$_ml_act,$_ChallengeInfo,$_NextChallengeInfo;

	// make xml string
	$_mapinfo_xml = '<frame posn="64 48 -2"><format textsize="2"/>'
		.'<quad sizen="25 10" posn="-21 1.4 0" style="BgsPlayerCard" substyle="BgPlayerCard"/>';
		//.'<quad sizen="25 3.8" posn="-20.1 0.2 0.01" style="BgsPlayerCard" substyle="BgPlayerCardSmall"/>';

	if(isset($_NextChallengeInfo['Environnement']))
		$_mapinfo_xml .= sprintf('<label sizen="19.5 1.6" posn="-0.5 -0.1 0.02" textsize="1" halign="right" text="$bbbNext:  $ccc%s $aaaby $ccc%s"/>'
														 .'<label sizen="19.5 1.6" posn="-0.5 -1.8 0.02" textsize="1" halign="right" text="$eee%s"/>',
														 $_NextChallengeInfo['Environnement'],htmlspecialchars($_NextChallengeInfo['Author'],ENT_QUOTES,'UTF-8'),
														 htmlspecialchars(tm_substr(stripColors($_NextChallengeInfo['Name']),0,48),ENT_QUOTES,'UTF-8'));
	
	$_mapinfo_xml .= sprintf('<label sizen="19 2" posn="-0.5 -3.9 0.02" halign="right" text="$fff%s"/>'
													 .'<label sizen="18.3 2" posn="-0.5 -6.1 0.02" halign="right" text="$fff%s $ddd%s"/>',
													 htmlspecialchars(tm_substr($_ChallengeInfo['Name'],0,48),ENT_QUOTES,'UTF-8'),
													 htmlspecialchars($_ChallengeInfo['Author'],ENT_QUOTES,'UTF-8'),MwTimeToString($_ChallengeInfo['AuthorTime']))
		.'</frame>';

	if($refresh_all){
		ml_mapinfoUpdateXml(true,'refresh');

	}else{
		// update to specs
		foreach($_players as $login => &$pl){
			if($pl['Active'] && !$pl['Relayed'] && $pl['Status']>0)
				ml_mapinfoUpdateXml($login,'refresh');
		}
	}
}

?>
