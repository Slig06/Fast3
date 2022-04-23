<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// Will show in top of screen the 12 best times done on the current map,
// and who done them.
//
// $_bests_default = 1;  // 0=off, 1=when not playing, 2=on
// $_players[$login]['ML']['Show.bests']

registerPlugin('ml_bests',15,1.0);



//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function ml_bestsInit($event){
	global $_mldebug,$_ml_act,$_bests_MapTimes,$_bests_MaxTimes,$_bests_default,$_bests_xml,$_bests_bg_xml;
	if($_mldebug>3) console("ml_bests.Event[$event]");

	if(!isset($_bests_default))
		$_bests_default = 1;

	$_bests_MapTimes = array();
	$_bests_MaxTimes = 12;
	$_bests_xml = '';
	$_bests_bg_xml = '';

	ml_bestsBuildBgXml();
}


function ml_bestsPlayerConnect($event,$login){
	global $_mldebug,$_players,$_bests_default;
  if(!is_string($login))
    $login = ''.$login;
	//console("ml_bests.Event[$event]('$login')");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if(!isset($_players[$login]['ML']['Show.bests']))
		$_players[$login]['ML']['Show.bests'] = $_bests_default;

	if($_players[$login]['ML']['ShowML'] && $_players[$login]['ML']['Show.bests'] > 0){
		ml_bestsUpdateXml($login,'show');
	}
}


function ml_bestsPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if($ShowML && $_players[$login]['ML']['Show.bests'] > 0){
		ml_bestsUpdateXml($login,'show');

	}else{
		ml_bestsUpdateXml($login,'hide');
	}
}


function ml_bestsPlayerMenuBuild($event,$login){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	ml_menusAddItem($login, 'menu.hud', 'menu.hud.showbests', 
									array('Name'=>array(localeText($login,'menu.hud.bests.off'),
																			localeText($login,'menu.hud.bests.noplay'),
																			localeText($login,'menu.hud.bests.on')),
												'Type'=>'multi','multioffval'=>0,
												'State'=>$_players[$login]['ML']['Show.bests']));
}


function ml_bestsPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_players;
	if($_mldebug>6) console("ml_bests.Event[$event]('$login',$action,$state)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	$msg = localeText(null,'server_message').localeText(null,'interact');
	if($action=='menu.hud.showbests'){
		$msg = localeText(null,'server_message').localeText(null,'interact');
		$_players[$login]['ML']['Show.bests'] = $state;
		if($state==0){
			ml_bestsUpdateXml($login,'hide');
			$msg .= localeText($login,'chat.hud.bests.off');
		}elseif($state==1){
			if($_players[$login]['ML']['ShowML']){
				ml_bestsUpdateXml($login,'show');
			}
			$msg .= localeText($login,'chat.hud.bests.noplay');

		}else{
			ml_bestsUpdateXml($login,'show');
			$msg .= localeText($login,'chat.hud.bests.on');
		}
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}

	
function ml_bestsBeginRace($event){
	global $_mldebug,$_players,$_bests_MapTimes,$_bests_xml,$_ChallengeInfo,$_PrevChallengeInfo,$_GameInfos,$_PrevGameInfos,$_FGameMode,$_PrevFGameMode;
	//if($_mldebug>9) console("ml_bests.Event[$event]");
	if($_ChallengeInfo['UId'] == $_PrevChallengeInfo['UId'] && $_ChallengeInfo['FileName'] == $_PrevChallengeInfo['FileName'] &&
		 $_GameInfos['GameMode'] == $_PrevGameInfos['GameMode'] && $_FGameMode == $_PrevFGameMode){
		// same map (restart), keep bests !
		ml_bestsBuildBgXml();
	}else{
		$_bests_MapTimes = array();
		$_bests_xml = '';
	}

	foreach($_players as $login => &$pl){
		if($pl['Active'] && $pl['ML']['ShowML'] && $pl['ML']['Show.bests'] > 0)
			ml_bestsUpdateXml($login,'show');
	}
}


function ml_bestsPlayerStatus2Change($event,$login,$status2){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!$_players[$login]['ML']['ShowML'] || $_players[$login]['ML']['Show.bests']!=1)
		return;
	if($status2 > 0)
		ml_bestsUpdateXml($login,'show');
	else
		ml_bestsUpdateXml($login,'hide');
}


function ml_bestsPlayerFinish($event,$login,$time){
	global $_mldebug,$_players,$_GameInfos,$_bests_MapTimes,$_bests_MaxTimes;
	if(!is_string($login))
		$login = ''.$login;
	//if($_mldebug>2) console("ml_bests.Event[$event]('$login',$time)");

	if($_GameInfos['GameMode'] != LAPS){
		// build array of best times in current map
		if($time > 0 && (!isset($_bests_MapTimes[$_bests_MaxTimes]['Time']) || $time < $_bests_MapTimes[$_bests_MaxTimes]['Time'])){

			$_bests_MapTimes[$_bests_MaxTimes] = array('Login'=>$login,'Time'=>$time,'NickDraw'=>$_players[$login]['NickDraw'],'Pos'=>99);
			usort($_bests_MapTimes,'ml_bestsMapTimesCompare');
			foreach($_bests_MapTimes as $num => &$maptime)
				$maptime['Pos'] = $num+1;

			ml_bestsBuildXml();
		}
	}
}


function ml_bestsPlayerLap($event,$login,$laptime,$lapnum,$checkpt){
	global $_mldebug,$_players,$_GameInfos,$_bests_MapTimes,$_bests_MaxTimes;
	if(!is_string($login))
		$login = ''.$login;
	//if($_mldebug>2) console("ml_bests.Event[$event]('$login',$time,$lapnum,$checkpt)");

	if($_GameInfos['GameMode'] == LAPS){
		// build array of best times in current map
		if($laptime>0 && (!isset($_bests_MapTimes[$_bests_MaxTimes]['Time']) || $laptime < $_bests_MapTimes[$_bests_MaxTimes]['Time'])){

			$_bests_MapTimes[$_bests_MaxTimes] = array('Login'=>$login,'Time'=>$laptime,'NickDraw'=>$_players[$login]['NickDraw'],'Pos'=>99);
			usort($_bests_MapTimes,'ml_bestsMapTimesCompare');
			foreach($_bests_MapTimes as $num => &$maptime)
				$maptime['Pos'] = $num+1;

			ml_bestsBuildXml();
		}
	}
}


// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function ml_bestsMapTimesCompare($a,$b){
	if($a['Time'] < $b['Time'])
		return -1;
	elseif($a['Time'] > $b['Time'])
		return 1;
	elseif($a['Pos'] <= $b['Pos'])
		return -1;
	return 1;
}


function ml_bestsBuildBgXml(){
	global $_mldebug,$_bests_bg_xml,$_bests_MaxTimes;

	// build xml
	$max = floor($_bests_MaxTimes / 2);
	$_bests_bg_xml = '<frame posn="-48 48.4 0">';
	for($i=0; $i < $_bests_MaxTimes; $i++){
		$y = ($i < $max) ? 0 : -2.1;
		$_bests_bg_xml .= 
			sprintf('<quad sizen="15 2.2" posn="%0.2F %0.1F -40" style="BgsPlayerCard" substyle="BgPlayerCardSmall"/>', // "BgPlayerScore"
							($i%$max)*15,$y);
	}
	$_bests_bg_xml .= '</frame>';
}


function ml_bestsBuildXml(){
	global $_mldebug,$_players,$_bests_MapTimes,$_bests_xml,$_bests_MaxTimes;

	if(count($_bests_MapTimes) > 0){
		// build xml
		$max = floor($_bests_MaxTimes / 2);
		$_bests_xml = '<frame posn="-48 48 -39"><format textsize="1" textcolor="eeee"/>';
		for($i=0; $i < $_bests_MaxTimes; $i++){
			if(isset($_bests_MapTimes[$i]['Time'])){
				$y = ($i < $max) ? 0 : -2.1;
				$_bests_xml .= 
					sprintf('<label sizen="8.9 1" posn="%0.2F %0.1F 0" text="$ff0%s$fff. %s"/>'
									.'<label posn="%0.2F %0.1F 0" halign="right" text="%s"/>',
									(($i%$max)*15)+0.6,$y,''.$_bests_MapTimes[$i]['Pos'],$_bests_MapTimes[$i]['NickDraw'],
									(($i%$max)*15)+14.4,$y,MwTimeToString($_bests_MapTimes[$i]['Time']));
			}
		}
		$_bests_xml .= '</frame>';
	}else
		$_bests_xml = '';
	

	// update to all
	foreach($_players as $login => &$pl){
		if($pl['Active'] && $pl['ML']['ShowML'] && $pl['ML']['Show.bests'] > 0)
			ml_bestsUpdateXml($login,'refresh');
	}
}


// action='show'/'hide'/'refresh'
function ml_bestsUpdateXml($login,$action='show'){
	global $_mldebug,$_players,$_bests_MapTimes,$_ml,$_GameInfos,$_bests_xml,$_bests_bg_xml;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if($_mldebug>8) console("ml_bestsUpdateXml($login,$action)");
	$pml = &$_players[$login]['ML'];
	if($action=='hide'){
		// hide manialink
		manialinksHide($login,'ml_bests');
		manialinksHide($login,'ml_bests.bg');
		return;
	}
	if(!$pml['ShowML'] || $pml['Show.bests'] == 0 || $_GameInfos['GameMode'] == STUNTS)
		return;

	if($pml['Show.bests'] < 2 && $_players[$login]['Status2'] < 1)
		return;

	// show
	if($action=='show'){
		// show bg
		manialinksShow($login,'ml_bests.bg',$_bests_bg_xml);
	}

	// show/refresh
	if($_bests_xml!=''){
		manialinksShow($login,'ml_bests',$_bests_xml);
	}
}


?>
