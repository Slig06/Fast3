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
// FAST3.2 plugin to handle script specific game modes (FGameModes), for special script game modes
// 
if(!$_is_relay) registerPlugin('fgmodes',17,1.0);


// FGameMode main handling is include in Fast core
// FGameMode graphics handling are done in ml_main, ml_scorepanel, and this plugin (and plugins which use it)
//
// Any plugin can add its own FGameMode by adding an entry in $_FGameModes, then handing specific datas and graphics
//
// $_NextFGameMode is the next FGameMode (name), keep '' for classic gamemodes, use setNextFGameMode() to change it !
// $_FGameMode is the current FGameMode (name), keep '' for classic gamemodes, never change it directly !
//
// $_FGameModes is an array( 'fgamemodename' => array() , ... )
//	$_FGameModes['fgamemodename']['Aliases'] = array('trounds','tro','tr'); // mode name is not case sensitive, but aliases are.
//	$_FGameModes['fgamemodename']['Podium'] = true; // false=classic podium, true=FGameMode podium  
//                                                     using fgmodesSetScoretable(), else just hide classic podium
//	$_FGameModes['fgamemodename']['RoundPanel'] = true; // false=classic round panel, true=FGameMode roundpanel
//                                                     using fgmodesSetRoundScore(), else just hide classic round panel
//	$_FGameModes['fgamemodename']['FTeam'] = true; // use fteams (and should use fteamsSetScores() instead of fgmodesSetScoretable() )
//	$_FGameModes['fgamemodename']['GameInfos'] = array('GameMode'=>ROUNDS); // set dedicated GameInfos config for the FGameMode
//	$_FGameModes['fgamemodename']['RoundCustomPointsRule'] = 'none';  // set dedicated RoundCustomPoints config for the FGameMode
//
//	$_FGameModes['fgamemodename']['MatchLogEndRound'] = 'callback'; // if set, callback called by matchlog plugin at EndRound
//                                                                     should call matchlog() to write wanted infos
//	$_FGameModes['fgamemodename']['MatchLogEndRace'] = 'callback';  // if set, callback called by matchlog plugin at EndRace
//                                                                     should call matchlog() to write wanted infos
//
//	$_FGameModes['fgamemodename']['MatchEndRound'] = 'callback'; // if set, callback called by match plugin at EndRound
//	$_FGameModes['fgamemodename']['MatchEndRace'] = 'callback';  // if set, callback called by match plugin at EndRace
//
//  $_FGameModes['fgamemodename'][confname] for which $_fteams_rules[confname] exists will be updated to $_fteams_rules at BeginRound


//--------------------------------------------------------------
// set next FGameMode (name) to use (after next or restart)
//--------------------------------------------------------------
function setNextFGameMode($fgmode){
	global $_debug,$_NextFGameMode,$_FGameModes,$_NextFGameModeFails;
	$_NextFGameModeFails = 0;
	$_NextFGameMode = $fgmode;

	if($_debug>0) console("setNextFGameMode:: {$_FGameModes} -> {$_NextFGameMode}"); 
	// help the game mode to be changed
	if(isset($_FGameModes[$_NextFGameMode]['GameInfos']['GameMode']))
		addCall(null,'SetGameMode',$_FGameModes[$_NextFGameMode]['GameInfos']['GameMode']+0);
}


//--------------------------------------------------------------
// set scoretable entries contents. use it to set scoretable contents if $_FGameModes['fgamemodename']['Podium'] !== false
// 16 entries max
// title and title2 must be xml compliant
// array( array(0=>keynum,1=>'bgcolor',2=>'teamname',3=>'score',4=>'status',5=>'players') ,... )
//   where keynum is 0 to 16, 'fgmodes.xx' action will be called when the corresponding entry is clicked
//   (set '' for 3,4,5,6,7 if empty !)
//   if unknown 'fgmodes' will be used, which is also called on round panel background
// refresh=true to ask refresh for all, or refresh='login' for immediate refresh for player only
// Should be called in BeginRace of plugins which use it, else will get default empty panel
// Plugins with $_FGameModes['fgamemodename']['FTeams'] = true should use fteamsSetScores() from fteams plugin instead
//
// Note: to avoid overhead, the scoretable is the same for all players, so do not build it with anything specific for a player !
//--------------------------------------------------------------
function fgmodesSetScoretable($scoretable,$title='Scores',$title2='',$refresh=false){
	global $_debug,$_ml_act,$_fgmodes_scoretable,$_FGameMode,$_fgmodes_scoretable_bg_xml,$_fgmodes_scoretable_xml,$_fgmodes_keymax,$_is_relay;
	if($_is_relay)
		return;

	$_fgmodes_scoretable = $scoretable;

	$nmax = count($_fgmodes_scoretable);

	$entries_xml = '';
	if($nmax <= 4){
		$yt = 24;
		$scale = 1.0;
	}elseif($nmax >= 8){
		$yt = 8 * 12 / 2;
		$scale = 4 / 8;
	}else{
		$yt = $nmax * 12 / 2;
		$scale = 4 / $nmax;
	}
	$n = 1;
	foreach($_fgmodes_scoretable as $score){ // width: 78, left: -39 to -33, right: 29 to 39
		$x = ($nmax < 8) ? 0 : ( ($n <= 8) ? -39 : 39 );
		$y = ($n <= 8) ? $yt - 12 * ($n - 1) : $yt - 12 * ($n - 9);
		$key = $score[0]+0;
		if($key < 0 || $key > $_fgmodes_keymax)
			$key = 0;
		$num = ($score[2] !== '') ? "{$n}" : '';
		$entries_xml .= sprintf('<label sizen="78 12" posn="%0.1F %0.1F 0.1" halign="center" text=" " action="'. $_ml_act['fgmodes.'.$key] .'"/>' // selectable
														.'<quad sizen="6 12" posn="%0.1F %0.1F 0" bgcolor="%sd"/>' // colored square
														.'<label posn="%0.1F %0.1F 0.2" halign="center" valign="center2" textsize="6" textcolor="ffff" text="%s"/>' // rank
														.'<label sizen="30 2" posn="%0.1F %0.1F 0.2" valign="center2" textsize="6" textcolor="ffff" text="%s"/>', // team
														$x,$y,   $x-39,$y,$score[1],   $x-36,$y-6,$num,  	$x-32,$y-4.5,$score[2]);
		if($score[3] !== ''){
			$entries_xml .= sprintf('<label sizen="14 4" posn="%0.1F %0.1F 0.2" halign="right" valign="center2" textsize="6" textcolor="ffff" text="%s"/>', // score
															$x+37.5,$y-4.5,$score[3]);
		}
		if($score[4] !== ''){
			$size = is_numeric($score[4]) ? 5 : 3;
			$entries_xml .= sprintf('<label sizen="9 3" posn="%0.1F %0.1F 0.2" halign="right" valign="center2" textsize="%d" textcolor="ddbf" text="%s"/>', // status
															$x+37.5,$y-9.5,$size,$score[4]);
		}
		if($score[5] !== ''){
			$entries_xml .= sprintf('<label sizen="58 3" posn="%0.1F %0.1F 0.2" valign="center2" textsize="4" textcolor="fffc" text="%s"/>', // players
															$x-30,$y-9.5,$score[5]);
		}
		$n++;
	}

	// default value
	$_fgmodes_scoretable_xml = sprintf($_fgmodes_scoretable_bg_xml,$_FGameMode,$title,$title2,$scale,$entries_xml);
	//console("fgmodesSetScoretable:: => {$_fgmodes_scoretable_xml}");

	if($refresh === true)
		fgmodesUpdateScoretableXml(true,'refresh');
	else if($refresh !== false)
		fgmodesUpdateScoretableXml($refresh,'refresh');
}





//--------------------------------------------------------------
// set round score panel entries contents. 
// use it to set round score panel contents if $_FGameModes['fgamemodename']['RoundPanel'] !== false
// 16 entries max
// array( array(0=>action,1=>'bgcolor',2=>'points',3=>'score',4=>'name',5=>'name2'[,6=>type]) ,... )
//   (set '' if empty for 3,4,5,6 !)
//   where action is a manialink action name, usually 'fgmodes.xx', 'fgmodes2.xx' or 'fgmodes3.xx' (xx=0 to 16)
//   action will be called when the corresponding entry is clicked
//   (for fteams modes 'fgmodes.xx' opens the team xx panel, set '' for no action)
// refresh=true to ask refresh for all, or refresh='login' for immediate refresh for player only
// Should be called in PlayerPositionChange($event,true) and EndRound() of plugins which use it, else will get default empty panel at round beginning
//
// type indicate the width of entry parts: array(colored square width,colored square height,column 2 (points) width,column 3 (score) width)
//                                         col 2 & 3 are centered, col 4 & 5 are left aligned
//                                         if 4 has no width then take remaining (and 5 not shown), if 5 has no width then take remaining
// type can be an array or one of these (defaults 0 if unkown). 
// type 0: array(1.1,1.95,3.0,3.2)
// type 1: array(1.0,1.0,2.6,5.8)
// type 2: array(1.1,1.95,2.0,6.2,6.3,6)
// if unset then it keeps the previous entry value, type can be globally set or per entry.
//
// Note: to avoid overhead, the round score panel is the same for all players, so do not build it with anything specific for a player !
//--------------------------------------------------------------
function fgmodesSetRoundScore($roundscoretable,$roundscoretable2,$type1=0,$type2=-1,$refresh=false){
	global $_debug,$_ml_act,$_fgmodes_roundscoretable,$_fgmodes_roundscoretable2,$_FGameMode,$_fgmodes_roundscoretable_base_xml,$_fgmodes_roundscoretable_xml,$_fgmodes_keymax,$_roundscores_types,$_is_relay;
	if($_is_relay)
		return;

	// set $type1 type if valid, else type 0
	if(is_int($type1) && isset($_roundscores_types[$type1])){
		$type = $_roundscores_types[$type1];
	}else if(is_array($type1) && isset($_roundscores_types[$type1][2])){
		$type = $type1;
	}else{
		$type = $_roundscores_types[0];
	}

	$_fgmodes_roundscoretable = $roundscoretable;
	$_fgmodes_roundscoretable2 = $roundscoretable2;

	$nmax = count($_fgmodes_roundscoretable);
	$nmax2 = count($_fgmodes_roundscoretable2);
	if($nmax > 16)
		$nmax = 16;
	if($nmax + $nmax2 > 16)
		$nmax2 = 16 - $nmax;
	$entries_xml = '';
	$yt = 0;

	$w = 21.6;
	$n = 0;
	foreach($_fgmodes_roundscoretable as $score){
		if($n >= $nmax)
			break;
		if($score[4] !== ''){
			$y = $yt - $n * 2;
			$action = ($score[0] === '') ? '' : (isset($_ml_act[$score[0]]) ? ' action="'.$_ml_act[$score[0]].'"' : ' action="fgmodes"');

			if(isset($score[6])){
				if(is_array($score[6]))
					$type = $score[6];
				else if(isset($_roundscores_types[$score[6]]))
					$type = $_roundscores_types[$score[6]];
			}

			$x = -$w / 2;

			$entries_xml .= sprintf('<label sizen="%0.2F 1.95" posn="%0.2F %0.1F 0" text=" "'.$action.'/>' // selectable
															.'<quad sizen="%0.2F %0.2F" posn="%0.2F %0.1F 0.1" bgcolor="%sd"/>', // colored square
															$w,$x,$y,   $type[0],$type[1],$x,$y-(1.95-$type[1])/2,$score[1]);
			$x +=  $type[0] + 0.2;

			if($score[2] !== ''){
				$entries_xml .= sprintf('<label sizen="%0.2F 2" posn="%0.2F %0.1F 0.2" halign="center" valign="center2" textsize="1" textcolor="dddf" text="$s%s"/>', // points
																$type[2],$x+$type[2]/2,$y-1.1,$score[2]);
			}
			$x +=  $type[2] + 0.2;

			if($score[3] !== ''){
				$entries_xml .= sprintf('<label sizen="%0.2F 2" posn="%0.2F %0.1F 0.2" halign="center" valign="center2" textsize="1" textcolor="080f" text="$s%s"/>', // score
																$type[3],$x+$type[3]/2,$y-1.1,$score[3]);
			}
			$x +=  $type[3] + 0.2;

			$width = isset($type[4]) ? $type[4] : $w/2-$x;
			if($score[4] !== ''){
				$entries_xml .= sprintf('<label sizen="%0.2F 2" posn="%0.2F %0.1F 0.2" valign="center2" textsize="1" textcolor="ffff" text="%s"/>', // name
																$width,$x,$y-1.1,$score[4]);
			}
			$x +=  isset($type[4]) ? $type[4] + 0.2 : 0;

			$width = isset($type[5]) ? $type[5] : $w/2-$x;
			if(isset($score[5]) && $score[5] !== '' && $width > 0.5){
				$entries_xml .= sprintf('<label sizen="%0.2F 2" posn="%0.2F %0.1F 0.2" valign="center2" textsize="1" textcolor="ffff" text="%s"/>', // name
																$width,$x,$y-1.1,$score[5]);
			}

		}
		$n++;
	}

	// set $type2 type if valid
	if(is_int($type2) && isset($_roundscores_types[$type2])){
		$type = $_roundscores_types[$type2];
	}else if(is_array($type2) && isset($_roundscores_types[$type2][2])){
		$type = $type2;
	}

	$n = 16 - $nmax2;
	foreach($_fgmodes_roundscoretable2 as $score){
		if($n >= 16)
			break;
		if($score[4] !== ''){
			$y = $yt - $n * 2;
			$action = ($score[0] === '') ? '' : (isset($_ml_act[$score[0]]) ? ' action="'.$_ml_act[$score[0]].'"' : ' action="fgmodes"');

			if(isset($score[6])){
				if(is_array($score[6]))
					$type = $score[6];
				else if(isset($_roundscores_types[$score[6]]))
					$type = $_roundscores_types[$score[6]];
			}

			$x = -$w / 2;

			$entries_xml .= sprintf('<label sizen="%0.2F 1.95" posn="%0.2F %0.1F 0" text=" "'.$action.'/>' // selectable
															.'<quad sizen="%0.2F %0.2F" posn="%0.2F %0.1F 0.1" bgcolor="%sd"/>', // colored square
															$w,$x,$y,   $type[0],$type[1],$x,$y-(1.95-$type[1])/2,$score[1]);
			$x +=  $type[0] + 0.2;

			if($score[2] !== ''){
				$entries_xml .= sprintf('<label sizen="%0.2F 2" posn="%0.2F %0.1F 0.2" halign="center" valign="center2" textsize="1" textcolor="dddf" text="$s%s"/>', // points
																$type[2],$x+$type[2]/2,$y-1.1,$score[2]);
			}
			$x +=  $type[2] + 0.2;

			if($score[3] !== ''){
				$entries_xml .= sprintf('<label sizen="%0.2F 2" posn="%0.2F %0.1F 0.2" halign="center" valign="center2" textsize="1" textcolor="080f" text="$s%s"/>', // score
																$type[3],$x+$type[3]/2,$y-1.1,$score[3]);
			}
			$x +=  $type[3] + 0.2;

			$width = isset($type[4]) ? $type[4] : $w/2-$x;
			if($score[4] !== ''){
				$entries_xml .= sprintf('<label sizen="%0.2F 2" posn="%0.2F %0.1F 0.2" valign="center2" textsize="1" textcolor="ffff" text="%s"/>', // name
																$width,$x,$y-1.1,$score[4]);
			}
			$x +=  isset($type[4]) ? $type[4] + 0.2 : 0;

			$width = isset($type[5]) ? $type[5] : $w/2-$x;
			if(isset($score[5]) && $score[5] !== '' && $width > 0.5){
				$entries_xml .= sprintf('<label sizen="%0.2F 2" posn="%0.2F %0.1F 0.2" valign="center2" textsize="1" textcolor="ffff" text="%s"/>', // name
																$width,$x,$y-1.1,$score[5]);
			}
		}
		$n++;
	}

	// default value
	$_fgmodes_roundscoretable_xml = sprintf($_fgmodes_roundscoretable_base_xml,$entries_xml);
	//console("fgmodesSetRoundScore:: => {$_fgmodes_roundscoretable_xml}");
	//console("fgmodesSetRoundScore:: => {$refresh}");

	if($refresh === true)
		fgmodesUpdateRoundScoreXml(true,'refresh');
	else if($refresh !== false)
		fgmodesUpdateRoundScoreXml($refresh,'refresh');
}







//--------------------------------------------------------------
//--------------------------------------------------------------
function fgmodesInit_Reverse($event){
	global $_mldebug,$_ml_act,$_fgmodes_scoretable_bg_xml,$_fgmodes_scoretable_xml,$_fgmodes_roundscoretable_bg_xml,$_fgmodes_roundscoretable_base_xml,$_fgmodes_roundscoretable_xml,$_fgmodes_hide,$_FGameModes,$_fgmodes_keymax,$_roundscores_types;

	$_fgmodes_keymax = 16;

	// complete FGameModes with default values (in _Reverse so they are already added)
	foreach($_FGameModes as $fgmode => $gm){
		if(!isset($_FGameModes[$fgmode]['Teams']))
			$_FGameModes[$fgmode]['Teams'] = false;
		
		if(!isset($_FGameModes[$fgmode]['Podium']))
			$_FGameModes[$fgmode]['Podium'] = false;
		
		if(!isset($_FGameModes[$fgmode]['RoundPanel']))
			$_FGameModes[$fgmode]['RoundPanel'] = false;
		
		if(!isset($_FGameModes[$fgmode]['Aliases']))
			$_FGameModes[$fgmode]['Aliases'] = array();

		if(!isset($_FGameModes[$fgmode]['XmlConv']))
			$_FGameModes[$fgmode]['XmlConv'] = array();
	}

	manialinksAddAction('fgmodes.roundscore');
	for($key = 0; $key <= $_fgmodes_keymax; $key++)
		manialinksAddAction('fgmodes.'.$key); // 'fgmodes.0' to 'fgmodes.16'
	for($key = 0; $key <= $_fgmodes_keymax; $key++)
		manialinksAddAction('fgmodes2.'.$key); // 'fgmodes2.0' to 'fgmodes2.16'
	for($key = 0; $key <= $_fgmodes_keymax; $key++)
		manialinksAddAction('fgmodes3.'.$key); // 'fgmodes3.0' to 'fgmodes3.16'

	$_fgmodes_hide = true;

	$_fgmodes_scoretable_bg_xml = 
	'<frame posn="0 29 -60">'
	.'<quad sizen="80 55" posn="0 1 0" halign="center" style="Bgs1InRace" substyle="BgList"/>'
	.'<quad sizen="78 4" posn="0 0 0.1" halign="center" style="Bgs1InRace" substyle="BgTitle3"/>'
	.'<quad sizen="3 3" posn="-38 -0.5 0.2" style="Icons64x64_1" substyle="Close" action="'.$_ml_act['ml_main.gamemode'].'"/>'
	.'<label sizen="41 3" posn="-34 -2 0.2" valign="center2" textsize="3" textcolor="ffff" text="$o%s - %s"/>'
	.'<label sizen="30 3" posn="38 -2 0.2" halign="right" valign="center2" textsize="2" textcolor="cccf" text="%s"/>'
	.'<frame posn="0 -29 0.1" scale="%0.2F">%s</frame>'
	.'</frame>';
	// set default panel
	$_fgmodes_scoretable_xml = sprintf($_fgmodes_scoretable_bg_xml,'','Scores','',1.0,'');

	$_fgmodes_roundscoretable_bg_xml = 
	'<frame posn="53.5 11 -40.1">'
	.'<quad sizen="22 34" posn="0 1 0" halign="center" style="Bgs1InRace" substyle="BgList" action="'.$_ml_act['fgmodes.roundscore'].'"/>'
	.'</frame>';

	$_fgmodes_roundscoretable_base_xml = 
	'<frame posn="53.5 11 -40">%s</frame>';
	// set default panel
	$_fgmodes_roundscoretable_xml = sprintf($_fgmodes_roundscoretable_base_xml,'');

	// width of round score panel entries
	$_roundscores_types = array(0=>array(1.1,1.95,3.0,3.2),
															1=>array(1.1,1.1,2.6,5.8),
															2=>array(1.1,1.95,2.0,6.2,6.3,6));
}


function fgmodesStoreInfos($event){
	global $_debug,$_StoredInfos,$_FGameMode,$_NextFGameMode,$_FGameModes;

	if($_debug>3) console("fgmodesStoreInfos()");
	// build data to store
	$_StoredInfos['FGameMode'] = $_FGameMode;
	$_StoredInfos['NextFGameMode'] = $_NextFGameMode;
}


function fgmodesRestoreInfos($event,$restoretype,$liveage,$playerschanged,$rankingchanged){
	global $_debug,$_StoredInfos,$_FGameMode,$_NextFGameMode,$_FGameModes,$_StartFGameMode,$_NextFGameModeFails;
;

	if($restoretype == 'previous'){
		// restore previous server config (for case where the dedicated and Fast were restarted)
		if($_debug>1) console("fgmodesRestoreInfos($restoretype,$liveage,$playerschanged,$rankingchanged):: set previous FGameMode: {$_StoredInfos['FGameMode']}");
		$_FGameMode = '';
		$_NextFGameMode = isset($_StoredInfos['FGameMode']) ? $_StoredInfos['FGameMode'] : '';
		
		changeFGameMode(false);
		$_NextFGameModeFails = -1;
		changeFGameMode(true);
		$_NextFGameMode = isset($_StoredInfos['NextFGameMode']) ? $_StoredInfos['NextFGameMode'] : '';


	}elseif($restoretype == 'live'){
		// restore previous Fast config (for case where only Fast was restarted)
		if($_debug>1) console("fgmodesRestoreInfos($restoretype,$liveage,$playerschanged,$rankingchanged):: set live previous FGameMode: {$_StoredInfos['FGameMode']}");

		$_FGameMode = isset($_StoredInfos['FGameMode']) ? $_StoredInfos['FGameMode'] : '';
		$_NextFGameMode = isset($_StoredInfos['FGameMode']) ? $_StoredInfos['FGameMode'] : '';
		changeFGameMode(false);
		$_NextFGameModeFails = -1;
		changeFGameMode(true);
		$_NextFGameMode = isset($_StoredInfos['NextFGameMode']) ? $_StoredInfos['NextFGameMode'] : '';

		/*
		// check if $_FGameMode is compatible with current GameMode, else disable it
		if(!isset($_FGameModes[$_FGameMode]['GameInfos']['GameMode']) ||
		$_FGameModes[$_FGameMode]['GameInfos']['GameMode'] != $_GameInfos['GameMode'])
		$_FGameMode = '';
		*/

	}elseif($restoretype == 'start'){
		// normal start : set FGameMode to $_StartFGameMode if set
		$_FGameMode = '';
		$_NextFGameMode = '';
		if(isset($_StartFGameMode) && $_StartFGameMode != '' && isset($_FGameModes[$_StartFGameMode]['GameInfos']['GameMode'])){
			if($_debug>1) console("fgmodesRestoreInfos($restoretype):: set FGameMode to StartFGameMode: {$_StartFGameMode}");
			$_NextFGameMode = $_StartFGameMode;
			changeFGameMode(false);
			$_NextFGameModeFails = -1;
			changeFGameMode(true);

		}else{
			changeFGameMode('setstring');
		}
	}
}


//--------------------------------------------------------------
// PlayerMenuAction : (event from ml_menus plugin)
//--------------------------------------------------------------
function fgmodesPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_mldebug,$_ml_act,$_players,$_FGameMode,$_FGameModes,$_StatusCode,$_fgmodes_keymax;
  if(!is_string($login))
    $login = ''.$login;
	if($_FGameMode == '' || !isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'] || !isset($_players[$login]['ML']))
		return;
	//if($_mldebug>6) console("fgmodes.Event[$event]('$login',$action,$state)");
	
	//$msg = localeText(null,'server_message').localeText(null,'interact');
	if($action == 'ml_main.gamemode'){
		if($_StatusCode < 5 && isset($_FGameModes[$_FGameMode]['Podium']) && $_FGameModes[$_FGameMode]['Podium'] === true)
			fgmodesUpdateScoretableXml($login,'reverse');
	}

	if($answer >= $_ml_act['fgmodes.0'] && $answer <= $_ml_act['fgmodes.0']+$_fgmodes_keymax){
		//console("fgmodesPlayerManialinkPageAnswer:: {$action}");
	}
	if($answer >= $_ml_act['fgmodes2.0'] && $answer <= $_ml_act['fgmodes2.0']+$_fgmodes_keymax){
		//console("fgmodesPlayerManialinkPageAnswer:: {$action}");
	}
}


function fgmodesBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
	global $_mldebug,$_FGameMode,$_FGameModes,$_fgmodes_hide,$_fgmodes_scoretable_bg_xml,$_fgmodes_scoretable_xml,$_fgmodes_roundscoretable_bg_xml,$_fgmodes_roundscoretable_xml;
	
	if($_fgmodes_hide){
		if($_mldebug>6) console("fgmodesBeginRace:: hide scores");
		$_fgmodes_hide = false;
		//console("fgmodesBeginRace:: hide RoundScore to all");
		fgmodesUpdateScoretableXml(true,($_FGameMode == '') ? 'remove' : 'hide');
		fgmodesUpdateRoundScoreXml(true,($_FGameMode == '') ? 'remove' : 'hide');
	}

	if($_FGameMode == '')
		return;

	fgmodesUpdateScoretableXml(true,'refresh');

	// if Podium then reset podium to default contents : real podium contents should be set by sub plugins
	if(isset($_FGameModes[$_FGameMode]['Podium']) && $_FGameModes[$_FGameMode]['Podium'] === true){
		if($_mldebug>6) console("fgmodes.Event[$event]($newcup,$warmup,$fwarmup)");
	
		// set default panel
		$_fgmodes_scoretable_xml = sprintf($_fgmodes_scoretable_bg_xml,$_FGameMode,'Scores','',1.0,'');
		$_fgmodes_roundscoretable_xml = '';
	}
}

function fgmodesBeforePlay($event,$delay){
		global $_mldebug,$_players,$_FGameMode,$_FGameModes,$_fgmodes_roundscoretable_xml,$_fgmodes_roundscoretable_bg_xml;
	if($_FGameMode == '' || $delay >= 0)
		return;

}

function fgmodesBeginRound($event){
	global $_mldebug,$_players,$_FGameMode,$_FGameModes,$_fgmodes_roundscoretable_xml,$_fgmodes_roundscoretable_bg_xml;
	if($_FGameMode == '')
		return;

	// should have been done in fgmodesBeforePlay...
	if(isset($_FGameModes[$_FGameMode]['RoundPanel']) && $_FGameModes[$_FGameMode]['RoundPanel'] === true){
		// remove round panel to players not spec, show it to specs
		foreach($_players as $login => &$pl){
			if($pl['Active'] && !$pl['Relayed']){
				if(!$pl['IsSpectator']){
					//console("fgmodesBeginRound:: hide RoundScore to $login");
					fgmodesUpdateRoundScoreXml(''.$login,'hide');
				}else{
					//console("fgmodesBeginRound:: show RoundScore to $login");
					fgmodesUpdateRoundScoreXml(''.$login,'show');
				}
			}
		}
		// show round panel to relay
		fgmodesUpdateRoundScoreXml('RELAY','show');
		// update empty panel to all who see it
		fgmodesSetRoundScore(array(),array(),true);

		// tempo tests
		//fgmodesUpdateRoundScoreXml(true,'show');
	}
}


function fgmodesPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_FGameMode,$_FGameModes,$_StatusCode,$_players;
	if($_FGameMode == '' || !isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'] || !isset($_players[$login]['ML']))
		return;
	if($_mldebug>6) console("fgmodes.Event[$event]('$login')");
	
	if($_StatusCode == 5 && isset($_FGameModes[$_FGameMode]['Podium']) && $_FGameModes[$_FGameMode]['Podium'] === true){
		if($_mldebug>6) console("fgmodesPlayerConnect:: show scores to $login");
		fgmodesUpdateScoretableXml($login,'show');
	}

	if(isset($_FGameModes[$_FGameMode]['RoundPanel']) && $_FGameModes[$_FGameMode]['RoundPanel'] === true){
		if($_StatusCode < 4 || ($_StatusCode < 5 && $_players[$login]['IsSpectator'])){
			//console("fgmodesPlayerConnect_Reverse:: show RoundScore to $login");
			fgmodesUpdateRoundScoreXml($login,'show');
		}
	}
}


function fgmodesPlayerConnect_Reverse($event,$login){
	global $_mldebug,$_FGameMode,$_FGameModes,$_StatusCode,$_players;
	if($_FGameMode == '' || !isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'] || !isset($_players[$login]['ML']))
		return;
	if($_mldebug>6) console("fgmodes.Event[$event]('$login')");
	
	if($_StatusCode == 5 && isset($_FGameModes[$_FGameMode]['Podium']) && $_FGameModes[$_FGameMode]['Podium'] === true){
		if($_mldebug>6) console("fgmodesPlayerConnect:: show scores to $login");
		fgmodesUpdateScoretableXml($login,'show');
	}

	if(isset($_FGameModes[$_FGameMode]['RoundPanel']) && $_FGameModes[$_FGameMode]['RoundPanel'] === true){
		if($_StatusCode < 4 || ($_StatusCode < 5 && $_players[$login]['IsSpectator'])){
			//console("fgmodesPlayerConnect_Reverse:: show RoundScore to $login");
			fgmodesUpdateRoundScoreXml($login,'show');
		}
	}
}


function fgmodesPlayerFinish_Reverse($event,$login,$time,$checkpts){
	global $_mldebug,$_players,$_FGameMode,$_FGameModes,$_GameInfos;
	if($_FGameMode == '' || !isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if($_GameInfos['GameMode'] == TA || $_GameInfos['GameMode'] == STUNTS)
		return;

	if(isset($_FGameModes[$_FGameMode]['RoundPanel']) && $_FGameModes[$_FGameMode]['RoundPanel'] === true &&
		 isset($_players[$login]['ML']['Hide.roundscore']) && $_players[$login]['ML']['Hide.roundscore'] >= 2 &&
		 !manialinksIsOpened($login,'fgmodes.roundscore')){
		//console("fgmodesPlayerFinish_Reverse:: show RoundScore to $login");
		fgmodesUpdateRoundScoreXml($login,'show');
	}
}


function fgmodesPlayerStatusChange_Reverse($event,$login,$status,$oldstatus){
	global $_mldebug,$_players,$_FGameMode,$_FGameModes;
	if($_FGameMode == '' || !isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_FGameModes[$_FGameMode]['RoundPanel']) || $_FGameModes[$_FGameMode]['RoundPanel'] !== true)
		return;
	if(!isset($_players[$login]['ML']['Hide.roundscore']) || $_players[$login]['ML']['Hide.roundscore'] < 2)
		return;

	//console("fgmodesPlayerStatusChange($login,$status,$oldstatus)::");
	if($status == 1 && !manialinksIsOpened($login,'fgmodes.roundscore')){
		//console("fgmodesPlayerStatusChange_Reverse:: show RoundScore to $login");
		fgmodesUpdateRoundScoreXml($login,'show');

	}elseif($status == 0 && manialinksIsOpened($login,'fgmodes.roundscore')){
		//console("fgmodesPlayerStatusChange_Reverse:: hide RoundScore to $login");
		fgmodesUpdateRoundScoreXml($login,'hide');
	}
}


function fgmodesEndRound_Reverse($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_mldebug,$_FGameMode,$_NextFGameMode,$_FGameModes;
	if($_FGameMode == '')
		return;

	if(isset($_FGameModes[$_FGameMode]['RoundPanel']) && $_FGameModes[$_FGameMode]['RoundPanel'] === true){
		// show round panel for all
		//console("fgmodesEndRound_Reverse:: show RoundScore to all");
		fgmodesUpdateRoundScoreXml(true,'show');
		// show round panel to relay
		fgmodesUpdateRoundScoreXml('RELAY','show');
	}
}


function fgmodesEndResult_Reverse($event){
	global $_mldebug,$_FGameMode,$_NextFGameMode,$_FGameModes;
	if($_FGameMode == '')
		return;
	if($_mldebug>6) console("fgmodes.Event[$event]($continuecup,$warmup,$fwarmup)");
	
	
	if(isset($_FGameModes[$_FGameMode]['Podium']) && $_FGameModes[$_FGameMode]['Podium'] === true){
		//console("fgmodesEndRace:: show scores");
		fgmodesUpdateScoretableXml(true,'show');
	}
																																																
	if(isset($_FGameModes[$_FGameMode]['RoundPanel']) && $_FGameModes[$_FGameMode]['RoundPanel'] === true){
		// hide round panel for all
		//console("fgmodesEndResult_Reverse:: hide RoundScore to all");
		fgmodesUpdateRoundScoreXml(true, ($_NextFGameMode == '') ? 'remove' : 'hide' );
	}
}


function fgmodesUpdateScoretableXml($login,$action){
	global $_mldebug,$_players,$_StatusCode,$_GameInfos,$_BestChecks,$_spec_lapinfo_bg_xml,$_spec_lapinfo_xml,$_ChallengeInfo,$_LastCheckNum,$_fgmodes_hide,$_fgmodes_scoretable_xml;
	// to all users
	if($login === true){
		foreach($_players as $login => &$pl){
			if($pl['Active'] &&  !$pl['Relayed'])
				fgmodesUpdateScoretableXml(''.$login,$action);
		}
		fgmodesUpdateScoretableXml('RELAY',$action);
		return;
	}
	if($login === 'RELAY'){
		if($action == 'remove'){
			manialinksRemoveOnRelay('fgmodes.scoretable.relay',$_fgmodes_scoretable_xml,null,null,array('ml_main.fteam'=>'showhide','ml_main.gamemode'=>'showhide'));
		}elseif($action == 'hide'){
			manialinksHideForceOnRelay('fgmodes.scoretable.relay');
		}elseif($action == 'refresh'){
			manialinksUpdateOnRelay('fgmodes.scoretable.relay',$_fgmodes_scoretable_xml,null,null,array('ml_main.fteam'=>'showhide','ml_main.gamemode'=>'showhide'));
		}else{
			manialinksShowForceOnRelay('fgmodes.scoretable.relay',$_fgmodes_scoretable_xml,null,null,array('ml_main.fteam'=>'showhide','ml_main.gamemode'=>'showhide'));
		}
		return;
	}
	// nothing is player is not active
	if(!$_players[$login]['Active'] || $_players[$login]['Relayed'])
		return;
	if($action == 'reverse' && manialinksIsOpened($login,'fgmodes.scoretable')){
		// reverse to hide
		manialinksHide($login,'fgmodes.scoretable');
		return;
	}elseif($action == 'remove'){
		// remove manialink
		manialinksRemove($login,'fgmodes.scoretable');
		return;
	}elseif($action == 'hide'){
		// hide manialink
		manialinksHide($login,'fgmodes.scoretable');
		return;
	}elseif($action == 'refresh'){
		// refresh
		if(!manialinksIsOpened($login,'fgmodes.scoretable'))
			return;
	}

	//console("fgmodesUpdateScoretableXml - {$login} - {$_fgmodes_scoretable_xml}");
	$_fgmodes_hide = true;
	manialinksShowForce($login,'fgmodes.scoretable',$_fgmodes_scoretable_xml);
}



function fgmodesUpdateRoundScoreXml($login,$action){
	global $_mldebug,$_players,$_StatusCode,$_GameInfos,$_BestChecks,$_spec_lapinfo_bg_xml,$_spec_lapinfo_xml,$_ChallengeInfo,$_LastCheckNum,$_fgmodes_hide,$_fgmodes_roundscoretable_xml,$_fgmodes_roundscoretable_bg_xml;
	// to all users
	if($login === true){
		foreach($_players as $login => &$pl){
			if($pl['Active'] &&  !$pl['Relayed'])
				fgmodesUpdateRoundScoreXml(''.$login,$action);
		}
		fgmodesUpdateRoundScoreXml('RELAY',$action);
		return;
	}
	if($login === 'RELAY'){
		if($action == 'remove'){
			manialinksRemoveOnRelay('fgmodes.roundscore.relay');
		}elseif($action == 'hide'){
			manialinksHideOnRelay('fgmodes.roundscore.relay');
		}elseif($action == 'refresh'){
			$relayxml = $_fgmodes_roundscoretable_bg_xml.$_fgmodes_roundscoretable_xml;
			manialinksUpdateOnRelay('fgmodes.roundscore.relay',$relayxml);
		}else{
			$relayxml = $_fgmodes_roundscoretable_bg_xml.$_fgmodes_roundscoretable_xml;
			manialinksShowOnRelay('fgmodes.roundscore.relay',$relayxml);
		}
		return;
	}
	// nothing is player is not active
	if(!$_players[$login]['Active'] || $_players[$login]['Relayed'])
		return;
	if($action == 'reverse' && manialinksIsOpened($login,'fgmodes.roundscore.bg')){
		// reverse to hide
		manialinksHide($login,'fgmodes.roundscore');
		manialinksHide($login,'fgmodes.roundscore.bg');
		return;
	}elseif($action == 'remove'){
		// remove manialink
		manialinksRemove($login,'fgmodes.roundscore');
		manialinksRemove($login,'fgmodes.roundscore.bg');
		return;
	}elseif($action == 'hide'){
		// hide manialink
		manialinksHide($login,'fgmodes.roundscore');
		manialinksHide($login,'fgmodes.roundscore.bg');
		return;
	}elseif($action == 'refresh'){
		// refresh
		if(!manialinksIsOpened($login,'fgmodes.roundscore.bg'))
			return;
	}

	//console("fgmodesUpdateRoundScoreXml - {$login} - {$_fgmodes_roundscoretable_xml}");
	//console("fgmodesUpdateRoundScoreXml - {$login}");
	$_fgmodes_hide = true;
	if(!manialinksIsOpened($login,'fgmodes.roundscore.bg'))
		manialinksShowForce($login,'fgmodes.roundscore.bg',$_fgmodes_roundscoretable_bg_xml);
	manialinksShowForce($login,'fgmodes.roundscore',$_fgmodes_roundscoretable_xml);
}





?>
