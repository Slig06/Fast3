<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      30.07.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST3.2 plugin for multi round maps match with cumulative points
// 
//
if(!$_is_relay) registerPlugin('match',85,1.0);

// $_match_mode = 'eswc';
// $_match_show_freeplay = false;

// Can be use to have automatic copy of matchlog file at end of map
// Be very carefull : it will stop other fast stuff while copying,
// so use remote copy for special case only !
//$_match_copy = "/var/www/matchlogs/";
//$_match_copy = "ftp://xxxx:yyy@ftpperso.free.fr/match/";
//$_match_url = "http://xxxx.free.fr/match/" 

// name of the mysql database table to store match results in
//$_match_db_table = 'tmmatch';


//--------------------------------------------------------------
// Init :
// $_match_map : -1=no match, 0=prepare, 1=number of current match map (incremented in EndRace), -2=no match but end of match podium
//

// $_match_conf['Ident'] : name of the match config
// $_match_conf['Title'] : match title
// $_match_conf['DbTitle'] : match title for database
// $_match_conf['ReplayName'] : name of the replay, else don't save it
// $_match_conf['MatchMaxTime'] : if set, maximum time for the match (seconds)
// $_match_conf['MatchMinPlayers'] : need also MatchMinTime
// $_match_conf['MatchMinTime'] : will end match if there is no more than MatchMinPlayers after MatchMinTime (seconds)
// $_match_conf['MatchMinRounds'] : will end match if there is no more than MatchMinPlayers after MatchMinRounds rounds
// $_match_conf['ImgUrl'] : image url
// $_match_conf['LocaleInfo'] : locale code for an event text info
// $_match_conf['SrvName'] : used to setup easy server name (using  /match name xxx)
// $_match_conf['GlobalScore'] : if true then make an independant global cumulated scores, summing each map scores (unused in Cup mode)
// $_match_conf['ReportedScore'] : if true then report previous map scores on next map, for Rounds mode only ! (need RoundsRoundsLimit/TeamRoundsLimit)
// $_match_conf['ShowScore'] : if true then show the special score panel (unused in Cup mode, usually when GlobalScore)
// $_match_conf['MatchSettings'] : can set a matchsettings which will be loaded to get maps in list
// $_match_conf['ShowNextMaps'] : show next maps in chat at each new map
// $_match_conf['MapFalseStart'] : number of false start or disconnection accepted (by player) at beginning to restart (0 to disable)
// $_match_conf['MatchFalseStart'] : number of false start or disconnection accepted (by player) at beginning to restart (0 to disable)
// $_match_conf['NumberOfMaps'] : number of finished map to play in match (For Cup mode: 'CupEnd' or empty)
// $_match_conf['Maps'] : array of structs, each need 'Ident' which can be 'shuffle', 'current', 'next', map uid, map envir, map name, or index in maplist
//                        and any ServerOptions or GameInfo settings which is wanted for the concerned map.
//                        the order will be used to make the map list for match. In case of 'shuffle' it should be alone
// $_match_conf['CustomPoints'] : custom points in Rounds/Cup (like $_roundspoints_rule value)
// $_match_conf['MapScoresMode'] : how map scores are computed and sorted :
//                    'Scores': player/team Score, sorted by score/bestime/rank (default for Rounds/Cup/Team/Stunts/FTeam derived)
//                    'Times': decreasing points, sorted by time/rank (default for TA/Laps)
//                    'Checkpoints': (besttime) num of checkpts, sorted by nb_cps/time/rank (for Laps using timelimit)
// $_match_conf['MapScoresList'] : custom scores which will be set to players at end of map
//                                 depending of their map rank after being sorted by MapScoresMode
//                                 (array of values, or string with comma separated values)
//                                 use it only if you want to replace MapScoresMode scores depending of resulting ranks!
// $_match_conf['MapRestarts'] : in TA, Stunts or Laps, will (quick) restart each map MapRestarts times, to simulate rounds
// $_match_conf['MapRestartsWu'] : in TA, Stunts or Laps, number of warmups when MapRestarts is used
// $_match_conf['MapRSscoremode'] : if MapRS, 'addscores','bestscore','addtimes','besttime','addpoints','bestpoints'
// $_match_conf['RoundsRoundsLimit'] : Rounds map limit based on number of played rounds
// $_match_conf['TeamRoundsLimit'] : Team map limit based on number of played rounds
// $_match_conf['CupAutoAdjust'] : Cup rounds points adjusted. 1=reduced when finalists, 2=limited depending on finalist position
// $_match_conf['BreakChatTime'] : break duration (seconds), that time will be added to the usual chattime at podium
// $_match_conf['BreakWarmUp'] : break warmup duration, the given value will be added to the usual warmup
// $_match_conf['BreakBeforeMaps'] : array of maps num (num compared to $_match_map) before which there will a break. For xml config several entries can be set with one value in each.
// $_match_conf['FWarmUpDuration'] : number of Rounds/Laps (1st player) of warmup. Can replace real WU : don't set real WU at same time !
// $_match_conf['EndCB'] : Call back which will be called when finish or stopped. $_match_conf['EndCB'](maps,rounds,maxscore,scores)
//                         maxscore will be 'EndMatch' if case of special end (MatchMinTime,MatchMinRounds)


// $_match_conf['GameMode'] : wanted gamemode int value (needed)
// $_match_conf['FGameMode'] : fgamemode value (optional: if not ''), if set then GameMode should correspond
// $_match_conf['Xxx'] : any Xxx where SetXxx exists in dedicated methods (GameMode have to be indicated)
// $_match_conf['Xxx'] : if FGameMode is used, any Xxx which is usable in $_FGameModes[FGameMode]



// added by script
// $_match_conf['MapsMode'] : copy of ['Maps'], or value given when starting match
// $_match_conf['MapsConf'] : array of 'uid'=>array(specific conf)
// $_match_conf['Finished'] : 0/1/2
// $_match_conf['LimitText'] : ' /limitnum' or empty
// $_match_conf['EndMatch'] : false/true

//--------------------------------------------------------------
// tag names for options in match.configs.xml.txt
// see also $_ConvGameInfos at the end of fast_general.php (same tags as in matchsettings)
// see also $_ConvFTeamInfos at beginning of fteams plugin
// see also $_FGameModes['fgamemodename']['XmlConv'] in fgmodes/fteams sub-plugins
//--------------------------------------------------------------
global $_ConvMatchConfig;
$_ConvMatchConfig = array(
	'ident' => 'Ident',
	'title' => 'Title',
	'dbtitle' => 'DbTitle',
	'replay_name' => 'ReplayName',
	'match_maxtime' => 'MatchMaxTime',
	'match_minplayers' => 'MatchMinPlayers',
	'match_mintime' => 'MatchMinTime',
	'imgurl' => 'ImgUrl',
	'locale_info' => 'LocaleInfo',
	'srvname' => 'SrvName',
	'global_score' => 'GlobalScore',
	'reported_score' => 'ReportedScore',
	'score_panel' => 'ShowScore',
	'matchsettings' => 'MatchSettings',
	'show_nextmaps' => 'ShowNextMaps',
	'map_falsestart' => 'MapFalseStart',
	'match_falsestart' => 'MatchFalseStart',
	'number_of_maps' => 'NumberOfMaps',
	'map' => 'Maps',
	'break_chattime' => 'BreakChatTime',
	'break_warmup' => 'BreakWarmUp',
	'break_before_map' => 'BreakBeforeMaps',
	'fwarmupduration' => 'FWarmUpDuration',
	'custom_points' => 'CustomPoints',
	'map_scores_list' => 'MapScoresList',
	'map_scores_mode' => 'MapScoresMode',
	'map_restarts' => 'MapRestarts',
	'map_restarts_wu' => 'MapRestartsWu',
	'map_rs_scoremode' => 'MapRSscoremode',
	'rounds_roundslimit' => 'RoundsRoundsLimit',
	'team_roundslimit' => 'TeamRoundsLimit',
	'cup_autoadjust' => 'CupAutoAdjust',
	'fgamemode' => 'FGameMode',
	'freeplay' => 'FreePlay',
	);



//--------------------------------------------------------------
function matchInit($event){
	global $_debug,$_match_mode,$_match_startit,$_match_config,$_match_conf,$match_file,$_match_map,$_match_map_rs,$_match_map_rswu,$_match_scores,$_match_scores_xml,$_match_scores_xml2,$_match_scores_bg_xml,$_match_rounds_player_bonus,$_players_roundplayed_current,$_roundslimit_rule,$_match_show_freeplay,$_match_state_bg_xml,$_match_logo_xml,$_match_rounds,$_match_starttime,$_lastEverysecond,$_match_scores_upd;
	if($_debug>0) console("match.Event[$event]");

	if(!isset($_match_mode))
		$_match_mode = '';

	if(!isset($_match_show_freeplay))
		$_match_show_freeplay = false;

	if(!file_exists('custom/match.configs.custom.xml.txt') && file_exists('plugins/match.configs.template.xml.txt'))
		copy('plugins/match.configs.template.xml.txt','custom/match.configs.custom.xml.txt');

	$_match_config = array();
	matchReadConfigs('plugins/match.configs.xml.txt');
	matchReadConfigs('custom/match.configs.custom.xml.txt');

	manialinksAddAction('match.infos');

	if(isset($_match_config[$_match_mode]))
		$_match_conf = $_match_config[$_match_mode];
	else{
		$_match_conf = reset($_match_config);
		$_match_mode = key($_match_config);
	}
	$_match_rounds = 0;
	$_match_starttime = $_lastEverysecond;

	$_match_conf['Finished'] = 2;
	$_match_conf['EndMatch'] = false;

	if(!isset($_match_mapstart))
		$_match_mapstart = 0;
	//$_match_mapstart = 'stadium';
	
	if(!isset($_match_rounds_player_bonus))
		$_match_rounds_player_bonus = 0;

	$match_file = false;
	$_match_startit = false;
	$_match_map = -1;
	$_match_map_rs = 1; // from 1 to $_match_conf['MapRestarts']
	$_match_map_rswu = 0; // from 0 to $_match_conf['MapRestartsWu']-1
	$_match_scores_upd = false;
	$_match_scores = array();
	$_match_scores_xml = '';
	$_match_scores_xml2 = '';

	$_match_scores_bg_xml = '<quad sizen="28 %0.2F" posn="-68.2 %0.2F -40" style="BgsPlayerCard" substyle="BgPlayerCardBig"/>'
	.'<quad sizen="28 3.2" posn="-68.2 %0.2F -40" style="BgsPlayerCard" substyle="BgPlayerCardBig"/>';
	
	$_match_logo_xml = '';

	//manialinksGetHudPartControl('match','net_infos');
	
	registerCommand('match','/match start, stop, maps <num>, bonus <login> <value>, screen',false);
	
	// in case roundslimit plugin is not defined, set them to avoid warnings and bugs
	if(!isset($_roundslimit_rule)){
		$_roundslimit_rule = -1; // standard possible values: -1 or positive number of rounds
	}

	$_match_state_bg_xml =
	'<quad sizen="25.5 2.4" posn="-68.2 -30.85 -44" style="BgsPlayerCard" substyle="BgPlayerCardBig"/>'; // "BgPlayerName" , "BgCard" , "BgPlayerCardBig"


	// database related
	matchDbInit();
}


// -----------------------------------
function matchServerStart(){
	matchBuildStateXml(true);
}


// -----------------------------------
function matchBuildStateXml($remove=false){
	global $_mldebug,$_match_state_xml,$_match_map,$_match_conf,$_WarmUp,$_FWarmUp,$_match_show_freeplay,$_GameInfos,$_match_map_rswu;

	if($_match_map < 0 && !$_match_show_freeplay){
		$_match_state_xml = '';
		if($remove)
			matchUpdateStateXml(true,'remove');
		return;
	}

	if($_match_map >= 0){
		if($_WarmUp || $_FWarmUp > 0){
			$text = sprintf('$ddd%s:$z$s$f70  Warm-up',$_match_conf['Title']);

		}elseif($_match_conf['MapRestartsWu'] > 0 && $_match_map_rswu < $_match_conf['MapRestartsWu']){
			$text = sprintf('$ddd%s:$z$s$f70  Warm-up',$_match_conf['Title']);

		}else{
			$text = sprintf('$ddd%s:$z$s$7f7  Race',$_match_conf['Title']);
			if(isset($_match_conf['NumberOfMaps']) && is_int($_match_conf['NumberOfMaps']) && $_match_conf['NumberOfMaps'] > 1)
				$text .= sprintf('$z$n$ddd  (map %d / %d)',$_match_map,$_match_conf['NumberOfMaps']);
		}
	}else
		$text = '$o$s$ddd   Free Play !';

	$_match_state_xml = sprintf('<label sizen="20.5 2" posn="-63.8 -31 -35.19" textsize="2" text="%s"/>',$text);

	matchUpdateStateXml(true);
}


//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove'
//--------------------------------------------------------------
function matchUpdateStateXml($login,$action='show'){
	global $_mldebug,$_players,$_StatusCode,$_GameInfos,$_match_state_xml,$_match_state_bg_xml;
	// to all users
	if($login === true){
		foreach($_players as $login => &$pl){
			if($pl['Active'])
				matchUpdateStateXml($login,$action);
		}
		// for relays...
		if($action == 'show'){
			manialinksShowForceOnRelay('match.state.bg.relay',$_match_state_bg_xml);
			manialinksShowForceOnRelay('match.state.relay',$_match_state_xml);
		}elseif($action == 'hide'){
			manialinksHideForceOnRelay('match.state.bg.relay',$_match_state_bg_xml);
			manialinksHideForceOnRelay('match.state.relay',$_match_state_xml);
		}else{ 
			manialinksRemoveOnRelay('match.state.bg.relay');
			manialinksRemoveOnRelay('match.state.relay');
		}
		return;
	}

	if(!isset($_players[$login]['Status2']))
		return;
	//console("matchUpdateStateXml - $login");
	// if the players disabled manialinks then do nothing
	if($action == 'remove'){
		// remove manialink
		manialinksRemove($login,'match.state');
		manialinksRemove($login,'match.state.bg');
		return;
	}elseif($action == 'hide'){
		// hide manialink
		manialinksHide($login,'match.state');
		manialinksHide($login,'match.state.bg');
		return;
	}
	// show/refresh
	// none to show and opened : hide it
	if(($_match_state_xml == '' || $_players[$login]['Status2'] >= 2) && manialinksIsOpened($login,'match.state.bg')){
		manialinksHide($login,'match.state');
		manialinksHide($login,'match.state.bg');
	}

	// show
	if(!manialinksIsOpened($login,'match.state.bg')){
		// show bg
		manialinksShowForce($login,'match.state.bg',$_match_state_bg_xml);
	}

	//console("matchUpdateStateXml - $login - $_match_state_xml");
	manialinksShowForce($login,'match.state',$_match_state_xml);
}


// -----------------------------------
function matchShowScores($inchat=true){
	global $_debug,$_match_conf,$_players,$_match_map,$_match_map_rs,$_match_scores,$_GameInfos,$_ServerOptions,$_WarmUp,$_FWarmUp,$_players_roundplayed_current,$_players_round_current,$_match_scores_xml,$_match_scores_xml2,$_match_scores_bg_xml,$_roundslimit_rule;

	//if($_debug>=0) debugPrint("matchShowScores::",$_match_scores);

	if($inchat && $_match_conf['GlobalScore']){
		$msg = localeText(null,'server_message').localeText(null,'interact');
		if($_roundslimit_rule > 0)
			$msg .= "Challenge {$_match_map}{$_match_conf['LimitText']}";
		else if($_match_conf['MapRestarts'] < 1)
			$msg .= "Round {$_players_roundplayed_current} (challenge {$_match_map}{$_match_conf['LimitText']})";
		else
			$msg .= "Round {$_match_map_rs} (challenge {$_match_map}{$_match_conf['LimitText']})";
		$sep = ":\n";
		if(count($_match_scores) > 0){
			$i = 0;
			foreach($_match_scores as &$pls){
				$bcol = $pls['Bonus'] > 0 ? '$f00':'';
				$msg .= $sep.'$ff0 '.$pls['Rank'].$bcol.'.$eee'.stripColors($pls['NickName'])
				.' $ccc('.$pls['MapScore'].',$fff'.($pls['FullScore']+$pls['Bonus']+$pls['MapScore']).'$ccc)';
				$sep = ', ';
				if($i++ > 9){
					$msg .= '...';
					break;
				}
			}
		}
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);
	}

	if(!$_match_conf['GlobalScore'] || !$_match_conf['ShowScore'])
		return;

	// build scores manialink
	if($_match_map >= 0){
		$hline = 2.5;
		$lines = count($_match_scores) + 1;
		if($lines < 2)
			$lines = 2;
		if($lines > 25)
			$lines = 25;
		$h = $hline * $lines + 1.3;
		$xml = sprintf($_match_scores_bg_xml,$h,28.5,28.5);
		
		$max = 20 - count($_match_scores);
		if($max < 3)
			$max = 3;
		$lines2 = ($lines > $max)? $max : $lines;
		$h2 = $hline * $lines2 + 1.3;
		$xml2 = sprintf($_match_scores_bg_xml,$h2,28.5,28.5);
		
		$titlexml = sprintf('<label sizen="17 2" posn="-64 %0.2F" text="%s"/>'
												.'<label sizen="6.1 2" posn="-43.7 %0.2F" halign="center" text="%s"/>',
												28.1,tm_substr($_ServerOptions['Name'],0,24),
												28.1,"{$_match_map}{$_match_conf['LimitText']}");
		
		$xml .= $titlexml;
		$xml2 .= $titlexml;
		
		if($lines > 1){
			$y = 27.6 - $hline;
			$line = 1;
			foreach($_match_scores as &$pls){
				if($line < $lines){
					$bcol = $pls['Bonus'] > 0 ? '$f00':'';
					$pad = $pls['Rank'] < 10 ? ' ':''; 
					$linexml = sprintf('<label sizen="14 2" posn="-64 %0.2F" textsize="2" text="%s"/>'
														 .'<label sizen="4.0 2" posn="-47.8 %0.2F" halign="center" text="%s"/>'
														 .'<label sizen="5.1 2" posn="-43.1 %0.2F" halign="center" text="%s"/>',
														 $y-0.1,' $ff0'.$pad.$pls['Rank'].$bcol.'.$fff '.$pls['NickDraw2'],
														 $y,'$ccc'.((int)$pls['MapScore']),
														 $y,'$fff'.((int)($pls['FullScore']+$pls['Bonus']+$pls['MapScore'])));
					$xml .= $linexml;
					if($line < $lines2)
						$xml2 .= $linexml;
				}
				$y -= $hline;
				$line++;
			}
		}
	}elseif($_match_map < -1){
		// between match end and next map begin : keep previous values
		$xml = $_match_scores_xml;
		$xml2 = $_match_scores_xml2;
		
	}else{
		// no match : empty manialink
		$xml = '';
		$xml2 = '';
	}

	if(!$inchat || $xml != $_match_scores_xml){
		$_match_scores_xml = $xml;
		$_match_scores_xml2 = $xml2;
		// send manialink
		matchScoreManialink();
	}
}


// -----------------------------------
// $login : true=all
function matchScoreManialink($login=true){
	global $_debug,$_players,$_is_relay,$_relays,$_match_map,$_match_scores_xml,$_match_scores_xml2,$_match_scores,$_StatusCode;
	//if($_debug>0) console("matchScoreManialink");
	
	if($_match_map < 0 && $_match_map > -2){
		// not match, and not end of match podium : hide
		manialinksHide($login,'match.scores');
		if($login === true)
			manialinksRemoveOnRelay('match.scores.relay');

	}elseif($_StatusCode >= 5){
		// podium : show to all
		manialinksShowForce($login,'match.scores',$_match_scores_xml);
		// for relays...
		if($login === true)
			manialinksShowForceOnRelay('match.scores.relay',$_match_scores_xml);
		
	}else{
		// during map...
		if($login === true){
			foreach($_players as $login => &$pl){
				if($pl['Active'] && !$pl['Relayed'])
					matchScoreManialink($login);
			}
			// for relays...
			manialinksShowForceOnRelay('match.scores.relay',$_match_scores_xml);

		}elseif(isset($_players[$login]['Active']) && $_players[$login]['Active']){
			if($_players[$login]['IsSpectator']){
				// spec
				if(!$_players[$login]['ML']['ShowML'] || 
					 !isset($_players[$login]['ML']['Show.specplayers']) || 
					 !$_players[$login]['ML']['Show.specplayers']){
					// spec without specplayer visible
					manialinksShowForce($login,'match.scores',$_match_scores_xml);
				}else{
					// normal spectator
					manialinksShowForce($login,'match.scores',$_match_scores_xml2);
				}
				
			}else{
				// player : hide
				manialinksHide($login,'match.scores');
			}
		}
		if($login === true)
			manialinksShowForceOnRelay('match.scores.relay',$_match_scores_xml);
	}
	// send to relays
	//if($login === true)
	//matchSendRelaysScoreManialink();
}


// -----------------------------------
function matchSendRelaysScoreManialink(){
	global $_debug,$_is_relay,$_relays,$_match_map,$_match_scores_xml,$_match_scores_xml2,$_StatusCode;
	
	// send manialink to relays
	if(!$_is_relay && count($_relays) > 0){
		if($_match_map < 0 && $_match_map > -2)
			$xml = '';
		else if($_StatusCode >= 5)
			$xml = $_match_scores_xml;
		else
			$xml = $_match_scores_xml2;
		if($_StatusCode >= 5)
			$xml = $_match_scores_xml;
		//$data = new IXR_Base64('manialink,match.scores,'.$_match_scores_xml);
		$data = 'CMD:manialink,match.scores,'.$xml;
		foreach($_relays as &$relay){
			if(!isset($relay['Master'])){
				//if($_debug>0) console("matchScoreManialink - SendDataToId({$relay['PlayerId']},datas)");
				//addCall(true,'TunnelSendDataToId',$relay['PlayerId'],$data);
				addCall(true,'ChatSendServerMessageToId',$data,$relay['PlayerId']);
			}
		}
	}
}




//--------------------------------------------------------------
function matchPlayerConnect($event,$login){
	global $_debug,$_match_map,$_match_conf,$_StatusCode;

	matchUpdateStateXml($login);

	if($_match_map < 0 && $_match_map > -2)
		return;

	// update playerid in $_match_scores !!!
	if(isset($_match_scores[$login]['PlayerId']) && $_match_scores[$login]['PlayerId'] != $_players[$login]['PlayerId'])
		$_match_scores[$login]['PlayerId'] = $_players[$login]['PlayerId'];

	if($_match_conf['ReportedScore'])
		if($_debug>1) console("matchPlayerConnect({$login}):: matchUpdateScore ! (ReportedScore) ".print_r($_match_scores,true));
	// report previous player score anyway
	matchUpdateScore($login,true);
	
	matchScoreManialink($login);

	if(isset($_players[$login]['ML']['Show.times']) &&
		 $_players[$login]['ML']['Show.times']){
		$_players[$login]['ML']['Show.times'] = false;
		ml_menusHideItem($login, 'menu.hud.times.menu');
		manialinksHide($login,'ml_times.1');
		manialinksHide($login,'ml_times.3');
		manialinksHide($login,'ml_times.F');
		manialinksHide($login,'ml_times.F2');
	}

	matchUpdateLogoXml($login,'show');
	//matchUpdateInfosXml($login,'show');
}


//--------------------------------------------------------------
function matchPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_players,$_StatusCode,$_match_map;

	matchUpdateStateXml($login);

	if($_match_map < 0 && $_match_map > -2)
		return;

	matchScoreManialink($login);

	//if($_players[$login]['ML']['Hide.scorepanel'] > 1)
	//manialinksHideHudPart('match','net_infos',$login);
	//else
	//manialinksShowHudPart('match','net_infos',$login);

	matchUpdateLogoXml($login,'show');
}


//--------------------------------------------------------------
function matchPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_debug,$_players,$_ml_act;

	if(!isset($_players[$login]['ML']))
		return;
	$pml = &$_players[$login]['ML'];
	//console("lapcup.Event[".$event."]('".$login."',".$answer.")");

	$msg = localeText(null,'server_message').localeText(null,'interact');

	if($action == 'match.infos'){
		if(manialinksIsOpened($login,'match.infos')){
			matchUpdateInfosXml($login,'hide');
			console("match: hide infos ($login)");
		}else{
			matchUpdateInfosXml($login,'show');
			console("match: show infos ($login)");
		}
	}
	//if($_debug>9) debugPrint("lapcupPlayerManialinkPageAnswer - _players[$login]['ML']",$pml);
}


//--------------------------------------------------------------
function matchPlayerSpecChange($event,$login,$isspec,$specstatus,$oldspecstatus){
	global $_debug,$_match_map;
	if($_match_map < 0 && $_match_map > -2)
		return;

	matchScoreManialink($login);
}


//--------------------------------------------------------------
// round logical $status2: 0=playing, 1=spec, 2=race finished
function matchPlayerStatus2Change($event,$login,$status2,$oldstatus2){
	global $_debug,$_players;
	//console("ml_specinfos.Event[$event]('$login',$status2)");

	if($status2 == 2){
		matchUpdateStateXml($login,'hide');
	}
}


//------------------------------------------
function matchEverysecond($event,$seconds){
	global $_debug,$_StatusCode,$_players,$_match_conf,$_match_map,$_match_map_rs,$_match_map_rswu,$_match_roundmaxtime,$_GameInfos,$_WarmUp,$_FWarmUp,$_currentTime,$_players_round_time,$_players_round_finished,$SpecialRestarting;
	if($_StatusCode != 4 || $SpecialRestarting || $_match_map < 1)
		return;

	if($_WarmUp || $_FWarmUp > 0){
		if(($seconds % 5) != 0 &&
			 $_players_round_time > 0 && $_players_round_finished <= 0 && 
			 ($_GameInfos['GameMode'] == 0 || $_GameInfos['GameMode'] == 5 || $_GameInfos['GameMode'] == 3)){
			// round or laps mode, warmup round not finished, test time in round
			$dtime = $_currentTime - $_players_round_time;

			if($dtime > $_match_roundmaxtime){
				// max time for wu round elapsed : end round
				console("matchEverysecond({$seconds}):: $dtime elapsed ({ $_match_roundmaxtime}) -> end round !");
				$msg = localeText(null,'server_message').localeText(null,'interact')."\$o Max round time reached, force end of round !";
				addCall(null,'ChatSendServerMessage', $msg);
				addCall(true,'ForceEndRound');
			}
		}

	}
}


//------------------------------------------
function matchEveryminute($event,$minutes,$is2min,$is5min){
	global $_match_map,$_match_conf,$SpecialRestarting,$_WarmUp,$_FWarmUp,$_lastEverysecond,$_match_starttime,$_match_scores;
	if($SpecialRestarting || $_match_map < 1 || !isset($_match_conf['EndMatch']))
		return;

	$mtime = $_lastEverysecond - $_match_starttime;
	//console("matchEveryminute:: {$_match_conf['MatchMinTime']},$mtime");
	if(!$_match_conf['EndMatch'] && isset($_match_conf['MatchMinPlayers']) && $_match_conf['MatchMinPlayers'] > 0 &&
		 isset($_match_conf['MatchMinTime']) && $_match_conf['MatchMinTime'] > 100 && $mtime > $_match_conf['MatchMinTime']){
		$nbplayers = 0;
		foreach($_match_scores as &$pls){
			//if($pls['MapScore'] > 0 && $pls['BestTime'] > 0)
			if($pls['MapScore'] > 0)
				$nbplayers++;
		}
		console("matchEveryminute:: {$_match_conf['MatchMinTime']},{$mtime} ; {$_match_conf['MatchMinPlayers']},{$nbplayers}");
		if($nbplayers <= $_match_conf['MatchMinPlayers']){
			// reach the min time with less than qualified players : match end
			console("matchEveryminute:: end match: MinPlayers({$nbplayers},{$_match_conf['MatchMinPlayers']}) + MinTime({$mtime},{$_match_conf['MatchMinTime']})");
			$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$oMatch finished ! ({$_match_conf['MatchMinPlayers']} or less players after ".MwTimeToString($_match_conf['MatchMinTime']*1000).')';
			addCall(null,'ChatSendServerMessage', $msg);
			matchEnd();

		}elseif($nbplayers <= 0 && $mtime > ($_match_conf['MatchMinTime'] + 300) * 1.2){
			// reach xx min time without player : match end
			console("matchEveryminute:: end match: No player and MinTime({$mtime},{$_match_conf['MatchMinTime']})");
			$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$oMatch finished ! (time limit without player after ".MwTimeToString($mtime).')';
			addCall(null,'ChatSendServerMessage', $msg);
			matchEnd();

		}elseif($mtime > ($_match_conf['MatchMinTime'] + 300) * 1.4){
			// reach xx min time : match end
			console("matchEveryminute:: end match: MinTime({$mtime},{$_match_conf['MatchMinTime']})");
			$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$oMatch finished ! (time limit after ".MwTimeToString($mtime).')';
			addCall(null,'ChatSendServerMessage', $msg);
			matchEnd();
		}
	}
}


//--------------------------------------------------------------
// BeginRace :
//--------------------------------------------------------------
function matchBeginRace($event,$GameInfos){
	global $_debug,$_match_startit,$_match_map,$_match_map_rs,$_match_map_rswu,$_match_conf,$_match_scores,$_WarmUp,$_FWarmUp,$_players,$_match_roundmaxtime,$_match_scores_upd;
	if($_match_startit !== false){
		if($_debug>0) console("matchBeginRace:: delayed match start to change mode.");
		matchStart($_match_startit[0],$_match_startit[1]);
	}

	if($_match_scores_upd){
		$_match_scores_upd = false;

		if(count($_match_scores) > 0){
			foreach($_match_scores as &$pls){
				if($_match_conf['GlobalScore']){
					$pls['FullScore'] += $pls['MapScore'];
					$pls['FullTime'] += $pls['BestTime'];
					$pls['FullCPs'] += $pls['CPs'];
					$pls['MapScore'] = 0;
				}else if($_match_conf['ReportedScore'])
					$pls['MapScore'] = $pls['Score'];
				else
					$pls['MapScore'] = 0;
				$pls['BestTime'] = -1;
				$pls['CPs'] = 0;
			}
			console("matchBeginRace:: added full scores: ".print_r($_match_scores,true));
		}
	}

	$_match_roundmaxtime = 10000000;

	matchBuildStateXml();
	
	if($_match_map < -1){
		// remove scores and set real no-match
		$_match_map = -1;
		matchShowScores(false);
	}

	if($_match_map < 0)
		return;

	$chattime = isset($_match_conf['ChatTime']) ? $_match_conf['ChatTime']+0 : -1;

	if($_match_map == 0){
		// real start of match
		$_match_map = 1;
		$_match_map_rs = 1;
		$_match_map_rswu = 0;
		// build match log filename
		matchInitLog();
		// update scores (for bonus set before the initial map change)
	}

	if($_match_map <= 1 && $_match_map_rs <= 1 && $_match_map_rswu <= 0){
		// first map : update scores (for bonus set before the initial map change)
		if($_debug>1) console("matchBeginRace:: matchUpdateScore ! (1st map) ".print_r($_match_scores,true));
		matchUpdateScore(true,true);

	}elseif($_match_conf['ReportedScore'] && $_match_map_rs <= 1 && $_match_map_rswu <= 0){
		// reported scores !
		if($_debug>1) console("matchBeginRace:: matchUpdateScore ! (ReportedScore) ".print_r($_match_scores,true));
		matchUpdateScore(true,true);
	}

	if($_match_conf['Finished'] == 1){
		// end of match
		matchStop();

	}elseif($_match_map > 0){
		// start of map...
		if($GameInfos['GameMode'] != 5 && !$_match_conf['ReportedScore']){
			// init scores if not ReportedScore and not Cup mode
			foreach($_match_scores as &$pls){
				$pls['Score'] = 0;
			}
		}
		foreach($_match_scores as &$pls){
			$pls['Times'] = array();
			$pls['BestTime'] = -1;
			$pls['MapScore'] = 0;
			$pls['CPs'] = 0;
			$pls['MapRank'] = 999;
			if($_match_conf['ReportedScore'])
				$pls['MapScore'] = $pls['Score'];
			else
				$pls['MapScore'] = 0;
		}

		if(!$_WarmUp && $_FWarmUp <= 0){
			// no warmup : start of map

			$rs = '';
			if($_match_conf['MapRestarts'] > 0){
				// MapRestarts : make short restarts

				if($_match_conf['MapRestartsWu'] > 0 && $_match_map_rswu < $_match_conf['MapRestartsWu']){
					// special map restart rounds wu
					$rs = ", warmup ".($_match_map_rswu+1)." /{$_match_conf['MapRestartsWu']}";
					$chattime = 100; // small chattime for fast restart
					ml_mainFWarmUpShow();

					// warmup : show maps list
					matchShowMaplist();

				}else{
					$rs = ", round {$_match_map_rs} /{$_match_conf['MapRestarts']}";
					if($_match_map_rs < $_match_conf['MapRestarts'])
						$chattime = 100; // small chattime for fast restart
					ml_mainFWarmUpHide();
				}
			}

			$msg = localeText(null,'server_message').localeText(null,'interact')."Start of challenge {$_match_map}{$_match_conf['LimitText']}{$rs}, \$f00Goooo !";
			// send message in offical chat
			addCall(null,'ChatSendServerMessage', $msg);
		}else{
			// warmup : show maps list
			matchShowMaplist();
		}
		
		// hide panel to players
		foreach($_players as $login => &$pl){
			if($pl['Active'] && !$pl['IsSpectator'])
				manialinksHide(''.$login,'match.scores');
		}
		matchShowScores(false);
	}

	// hide scoretable for "screen" players
	foreach($_players as $login => &$pl){
		if($pl['Active'] && $pl['ML']['Hide.scorepanel'] > 1 && isset($pl['screen']) && $pl['screen'])
			manialinksHide(''.$login,'match.scoretable');
	}

	// for MapRestart modes, make a restart without podium by setting ChatTime,0 , else standard value
	if($chattime >= 0)
		addCall(null,'SetChatTime',$chattime);
}


//------------------------------------------
// BeginRound : 
//------------------------------------------
function matchBeginRound(){
	global $_debug,$_match_map,$_match_map_rs,$_match_map_rswu,$_match_conf,$_match_scores,$_ChallengeInfo,$_WarmUp,$_FWarmUp,$_players,$_players_round_current,$_GameInfos,$_match_roundmaxtime;

	if($_match_map < 1)
		return;
	if($_debug>2) console("matchBeginRound:: _match_map={$_match_map}, rswu={$_match_map_rswu}, rs={$_match_map_rs}, round_current={$_players_round_current}");

	if($_players_round_current <= 1){
		// first round of map : do some init that can't be done in BeginRace !...
	}

	matchBuildStateXml();

	matchScoreManialink();
	//matchSendRelaysScoreManialink();
	
	if($_WarmUp || $_FWarmUp > 0){
		$msg = localeText(null,'server_message').localeText(null,'interact');
		$msg .= "Warm-up {$_players_round_current} (challenge {$_match_map}{$_match_conf['LimitText']})";
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);
	}

	// hide scoretable for "screen" players
	foreach($_players as $login => &$pl){
		if($pl['Active'] && $pl['ML']['Hide.scorepanel'] > 1 && isset($pl['screen']) && $pl['screen'])
			manialinksHide(''.$login,'match.scoretable');
	}

	if($_match_map >= 1 && $_match_conf['EndMatch']){
		// match finished, finish map
		if($_debug>2) console("matchBeginRound:: EndMatch is set, nextchallenge");
		addCall(true,'NextChallenge');
	}

	if($_GameInfos['GameMode'] == 0 || $_GameInfos['GameMode'] == 5 || $_GameInfos['GameMode'] == 3){
		// compute wu round maxtime
		$_match_roundmaxtime = $_ChallengeInfo['AuthorTime'];
		//console("matchBeginRound:1: match_roundmaxtime={$_match_roundmaxtime}");

		if($_ChallengeInfo['LapRace'] && ($_GameInfos['RoundsForcedLaps'] > 0 || $_GameInfos['GameMode'] == 3)){
			// specified number of laps : consider 1 lap time * number of laps
			if(isset($_ChallengeInfo['NbLaps']) && $_ChallengeInfo['NbLaps'] > 1)
				$_match_roundmaxtime = (int) floor($_match_roundmaxtime / $_ChallengeInfo['NbLaps']);
			//console("matchBeginRound:2: match_roundmaxtime={$_match_roundmaxtime}");

			if($_GameInfos['GameMode'] == 3){
				if($_FWarmUp > 0){
					$_match_roundmaxtime *= $_FWarmUp;
				}else{
					$_match_roundmaxtime *= $_GameInfos['AllWarmUpDuration']; // $_GameInfos['LapsNbLaps'];
				}
				//console("matchBeginRound:3: match_roundmaxtime={$_match_roundmaxtime} ({$_FWarmUp},{$_GameInfos['AllWarmUpDuration']})");
			}else{
				$_match_roundmaxtime *= $_GameInfos['RoundsForcedLaps'];
			}

			// if not warmup, mini roundmaxtime is 4 * FinishTimeout for sepcial cases ! (ie plateforme)
			if(!$_WarmUp && $_FWarmUp <= 0 && $_GameInfos['FinishTimeout'] * 4 > $_match_roundmaxtime)
				$_match_roundmaxtime = $_GameInfos['FinishTimeout'] * 4;
		}
		// warmup : short limit
		$_match_roundmaxtime *= 1.3;
		$_match_roundmaxtime += 10000;
		console("matchBeginRound:: match_roundmaxtime={$_match_roundmaxtime}");
	}
}


//------------------------------------------
// PlayerFinish : store finish times to permit time stats 
//------------------------------------------
function matchPlayerFinish($event,$login,$time,$checkpts){
	global $_debug,$_players,$_match_map,$_match_conf,$_match_scores,$_match_map_rs,$_match_map_rswu,$_WarmUp,$_FWarmUp,$_match_rounds,$_NextGameInfos,$_fteams,$_FGameModes,$_FGameMode;

	if($time <= 0 || $_match_map < 1 || $_WarmUp || $_FWarmUp > 0 || !isset($_players[$login]['PlayerId']))
		return;
	if($_match_conf['MapRestartsWu'] > 0 && $_match_map_rswu < $_match_conf['MapRestartsWu'])
		return;
	if($_debug>4) console("matchPlayerFinish({$login},{$time}):: CPs: ".print_r($checkpts,true));


	if(isset($_FGameModes[$_FGameMode]['FTeams']) && $_FGameModes[$_FGameMode]['FTeams']){
		// it's a fteam mode
		$tid = $_players[$login]['FTeamId'];
		if($tid >= 0 && isset($_fteams[$tid]['Active']) && $_fteams[$tid]['Active'] &&
			 ($_fteams[$tid]['Score'] > 0 || $_fteams[$tid]['Rank'] > 0)){
			if(!isset($_match_scores[$tid])){
				matchAddPlayer($tid,$tid,$_fteams[$tid]['Name'],$_fteams[$tid]['Score'],$_fteams[$tid]['Time']);
				
			}else{
				if($_fteams[$tid]['Score'] >= $_match_scores[$tid]['Score']){
					$_match_scores[$tid]['Score'] = $_fteams[$tid]['Score'];
					$_match_scores[$tid]['BestTime'] = $_fteams[$tid]['Time'];
					$_match_scores[$tid]['CPs'] = $_fteams[$tid]['CPs'];
				}
			}
		}
		
	}else{
		// not fteam mode
		if(!isset($_match_scores[$login]))
			matchAddPlayer($login,$_players[$login]['PlayerId'],$_players[$login]['NickName'],0,$time);
		if(isset($_match_scores[$login])){
			$_match_scores[$login]['Times'][] = $time;
			if($_match_scores[$login]['BestTime'] <= 0 || ($time > 0 && $time < $_match_scores[$login]['BestTime'])){
				$_match_scores[$login]['BestTime'] = $time;
				$_match_scores[$login]['CPs'] += count($checkpts) - 1;
			}
		}
	}

	if($_match_map > 0 && !$_WarmUp && $_FWarmUp <= 0 &&
		 ($_match_conf['MapRestarts'] <= 0 || $_match_map_rs >= $_match_conf['MapRestarts']) &&
		 isset($_match_conf['BreakChatTime']) && $_match_conf['BreakChatTime'] > 0 &&
		 isset($_match_conf['BreakBeforeMaps']) && 
		 array_search($_match_map+1,$_match_conf['BreakBeforeMaps']) !== false){
		// if needed then set chattime for break
		$chattime = $_match_conf['ChatTime'] + $_match_conf['BreakChatTime'];
		if($_NextGameInfos['ChatTime'] < $chattime){
			addCall(null,'SetChatTime',$chattime);
		}
	}
}


//------------------------------------------
function matchBeforeEndRound($event,$delay,$time){
	global $_debug,$SpecialRestarting,$_match_map,$_match_map_rs,$_match_map_rswu,$_match_conf;
	if($delay < 0){ // only in last call for transition
		if($SpecialRestarting || $_match_map < 1 || !isset($_match_conf['EndMatch']) || $_match_conf['MapRestarts'] <= 0)
			return;
		// try a restart without podium in MapRestarts case: it has to be done before EndRound and EndRace !
		if($_match_map_rswu < $_match_conf['MapRestartsWu'] || $_match_map_rs < $_match_conf['MapRestarts'])
			mapQuickRestartNP();
	}
}


//------------------------------------------
// EndRound : 
//------------------------------------------
function matchEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_debug,$_players,$_match_map,$_match_map_rs,$_match_map_rswu,$_match_conf,$_match_scores,$_GameInfos,$_WarmUp,$_FWarmUp,$_match_rounds,$_match_starttime,$_lastEverysecond,$_players_round_current,$_fteams_round,$_fteams,$_FGameModes,$_FGameMode,$_currentTime;

	if($SpecialRestarting || $_match_map < 1 || $_WarmUp || $_FWarmUp > 0 || !isset($_match_conf['EndMatch']))
		return;
	if($_match_conf['MapRestartsWu'] > 0 && $_match_map_rswu < $_match_conf['MapRestartsWu'])
		return;
	console("matchEndRound:: no MapRestartsWu: {$_match_map_rswu},{$_match_conf['MapRestartsWu']}");

	if(isset($_FGameModes[$_FGameMode]['FTeams']) && $_FGameModes[$_FGameMode]['FTeams']){
		// fteams scores
		foreach($_fteams as $tid => &$fteam){
			if($fteam['Active'] && ($fteam['Score'] > 0 || $fteam['Rank'] > 0)){
				debugPrint("matchEndRound:: _fteams_round[{$tid}]",$_fteams_round[$tid]);
				debugPrint("matchEndRound:: _fteams[{$tid}]",$_fteams[$tid]);
				if(!isset($_match_scores[$tid])){
					matchAddPlayer($tid,$tid,$fteam['Name'],$fteam['Score'],$fteam['Time']);
				
				}else{
					if($fteam['Score'] >= $_match_scores[$tid]['Score']){
						$_match_scores[$tid]['Score'] = $fteam['Score'];
						$_match_scores[$tid]['BestTime'] = $fteam['Time'];
						$_match_scores[$tid]['CPs'] = $fteam['CPs'];
					}
				}
			}
		}

	}else{
		if($_debug>1) debugPrint("matchEndRound:: Ranking",$Ranking);

		// in Stunts the ranks are sometimes wrong ??? recompute them with playerfinish time of best score as secondary sort key !
		// note: seems to happens only when trying to use quick restart !
		if($GameInfos['GameMode'] == 4){
			$nb = count($Ranking);
			for($n=0; $n < $nb; $n++){
				$login = $Ranking[$n]['Login'];
				if($Ranking[$n]['Score'] > 0)
					$Ranking[$n]['BestTime'] = isset($_players[$login]['BestDate']) ? $_players[$login]['BestDate'] : $_currentTime;
			}
			usort($Ranking,'rankingsStuntsCompare');
			$nb = count($Ranking);
			for($n=0; $n < $nb; $n++){
				if($Ranking[$n]['Rank'] != $n+1) console("matchEndRound:: stunts wrong rank: {$Ranking[$n]['Rank']}");
				$Ranking[$n]['Rank'] = $n+1;
			}
			if($_debug>0) debugPrint("matchEndRound:stunts:scores",$Ranking);
		}

		// update scores from ranking
		foreach($Ranking as &$prk){
			$login = $prk['Login'];
			if(isset($_players[$login]['Active']) && ($prk['Score'] > 0 || $prk['BestTime'] > 0)){

				if($GameInfos['GameMode'] == 4 && $prk['Score'] > 0){
					// add a time of best in stunts case which does not indicate a bestscore time
					debugPrint("matchEndRound:stunts:ranking({$login}): ",$prk);
					$prk['BestTime'] = isset($_players[$login]['BestDate']) ? $_players[$login]['BestDate'] : $_currentTime;
				}

				if(!isset($_match_scores[$login])){
					matchAddPlayer($prk['Login'],$prk['PlayerId'],$prk['NickName'],$prk['Score'],$prk['BestTime']);
					
				}else{
					if($prk['Score'] >= $_match_scores[$login]['Score']){
						$_match_scores[$login]['Score'] = $prk['Score'];
						$_match_scores[$login]['BestTime'] = $prk['BestTime'];
					}
				}
			}
		}
		
		// if in Cup mode : make score corrections for winners (rankings scores are sometimes broken because of disconnected winners)
		if($GameInfos['GameMode'] == 5){
			$maxscore = $GameInfos['CupPointsLimit'] + count($_match_scores);
			foreach($_match_scores as &$pls){
				if($pls['Score'] > $GameInfos['CupPointsLimit'])
					$pls['Score'] = $maxscore--;
			}
		}

		// test anticipated match end because of time or rounds when less players than qualified
		$nbplayers = 0; // nb players active/playing
		$nbwinners = 0; // nb Cup winners
		$maxscore_notplaying = 0; // max score of not playing players (dosconnected ?)
		$minscore_playing = $GameInfos['CupPointsLimit']; // min score of playing players
		foreach($_match_scores as &$pls){
			$login = $pls['Login'];

			if($GameInfos['GameMode'] == 5 && $pls['Score'] > $GameInfos['CupPointsLimit']) {
				// is cup winner
				$nbwinners++;

			}elseif($pls['Score'] > 0){
				// has played (but not cup winner)
				if($pls['BestTime'] > 0 || (isset($_players[$login]['Active']) && $_players[$login]['Active'] && !$_players[$login]['IsSpectator'])){
					// is connected/playing
					$nbplayers++;
					if($pls['Score'] < $minscore_playing)
						$minscore_playing = $pls['Score'];

				}else{
					if($pls['Score'] > $maxscore_notplaying)
						$maxscore_notplaying = $pls['Score'];
				}
			}
		}
	}

	//if($_debug>3) debugPrint("matchEndRound - _match_scores",$_match_scores);


	// sort match scores array
	if(count($_match_scores) > 0){

		// sort by map result
		if($_match_conf['MapScoresMode'] == 'Checkpoints'){
			console("matchEndRound:: matchMapCheckpointsCompare");
			uasort($_match_scores,'matchMapCheckpointsCompare');
		}else if($_match_conf['MapScoresMode'] == 'Times'){
			console("matchEndRound:: matchMapTimeCompare");
			uasort($_match_scores,'matchMapTimeCompare');
		}else{ // 'Scores'
			console("matchEndRound:: matchMapScoreCompare");
			uasort($_match_scores,'matchMapScoreCompare');
		}

		// compute map ranks and MapScore
		$rank = 1;
		$maxscore = count($_match_scores);
		foreach($_match_scores as &$pls){
			if($pls['BestTime'] > 0 || $pls['Score'] > 0)
				$pls['MapRank'] = $rank++;
			else if($pls['CPs'] > 1 && $_match_conf['MapScoresMode'] == 'Checkpoints')
				$pls['MapRank'] = $rank++;
			else
				$pls['MapRank'] = 999;
		}
		$maxscore = $rank;

		foreach($_match_scores as &$pls){

			// compute player/team MapScore (from MapScoresList or depending of MapScoresMode) :
			if($pls['MapRank'] < 999){
				if(isset($_match_conf['MapScoresList'][0])){
					$pls['MapScore'] = isset($_match_conf['MapScoresList'][$pls['MapRank']-1]) ? $_match_conf['MapScoresList'][$pls['MapRank']-1] : last($_match_conf['MapScoresList']);
				}else if($_match_conf['MapScoresMode'] == 'Checkpoints'){
					$pls['MapScore'] = $pls['CPs'];
				}else if($_match_conf['MapScoresMode'] == 'Times'){
					$pls['MapScore'] = $maxscore - $pls['MapRank'];
				}else{ // 'Scores'
					$pls['MapScore'] = $pls['Score'];
				}
			}else{
				$pls['MapScore'] = 0;
			}
			$pls['RealMapScore'] = $pls['MapScore'] - $pls['PrevMapScore'];
		}
		if($_debug>=0) debugPrint("matchEndRound:: map sorted _match_scores ",$_match_scores);

		// sort by match scores
		matchSortMatchScores();
		if($_debug>=0) debugPrint("matchEndRound:: match sorted _match_scores ",$_match_scores);
	}


	matchShowScores();

	$mtime = $_lastEverysecond - $_match_starttime;
	if(!$_match_conf['EndMatch'] && isset($_match_conf['MatchMaxTime']) && $mtime > $_match_conf['MatchMaxTime']){
		// reach the max time
		console("matchEndRound:: end match: MaxTime({$mtime},{$_match_conf['MatchMinTime']})");
		$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$oMatch finished ! (Max match time: ".MwTimeToString($_match_conf['MatchMaxTime']*1000);
		addCall(null,'ChatSendServerMessage', $msg);
		matchEnd();
		
	}elseif(!$_match_conf['EndMatch'] && isset($_match_conf['MatchMinPlayers']) && $_match_conf['MatchMinPlayers'] > 0 && 
					$_players_round_current > 1 && $maxscore_notplaying + 10 < $minscore_playing &&
					(($nbplayers + $nbwinners) <= $_match_conf['MatchMinPlayers'] || $nbplayers <= 0)){
		// not enough players (including cup winners, and after the 1 round of current map, and playing players having better score than disconnected ones)
		if(isset($_match_conf['MatchMinRounds']) && $_match_rounds >= $_match_conf['MatchMinRounds']){
			// reach the min rounds with less than qualified players
			console("matchEndRound:: end match: MinPlayers({$nbwinners}+{$nbplayers},{$_match_conf['MatchMinPlayers']}) + MinRounds({$_match_rounds},{$_match_conf['MatchMinRounds']})");
			$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$oMatch finished ! ({$_match_conf['MatchMinPlayers']} or less players after {$_match_conf['MatchMinRounds']} rounds)";
			addCall(null,'ChatSendServerMessage', $msg);
			matchEnd();

		}elseif(isset($_match_conf['MatchMinTime']) && $mtime > $_match_conf['MatchMinTime']){
			// reach the min time with less than qualified players
			console("matchEndRound:: end match: MinPlayers({$nbwinners}+{$nbplayers},{$_match_conf['MatchMinPlayers']}) + MinTime({$mtime},{$_match_conf['MatchMinTime']})");
			$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$oMatch finished ! ({$_match_conf['MatchMinPlayers']} or less players after ".MwTimeToString($_match_conf['MatchMinTime']*1000).')';
			addCall(null,'ChatSendServerMessage', $msg);
			matchEnd();
		}
	}
	$_match_rounds++;
}


function matchSortMatchScores(){
	global $_match_scores;

	if(count($_match_scores) > 0){
		// sort by match scores
		uasort($_match_scores,'matchFullScoreCompare');
		// compute match ranks
		$rank = 1;
		foreach($_match_scores as &$pls){
			$pls['Rank'] = $rank++;
		}
	}
}


// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function matchFullScoreCompare($a, $b){
	if($a['FullScore']+$a['Bonus']+$a['MapScore'] > $b['FullScore']+$b['Bonus']+$b['MapScore'])
		return -1;
	else if($a['FullScore']+$a['Bonus']+$a['MapScore'] < $b['FullScore']+$b['Bonus']+$b['MapScore'])
		return 1;
	// same scores, use previous rank
	else if($a['Rank'] > $b['Rank'])
		return 1;
	return -1;
}


// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function matchMapScoreCompare($a, $b){
	if($a['Score'] > $b['Score'])
		return -1;
	else if($a['Score'] < $b['Score'])
		return 1;
	if($a['BestTime'] > 0 && $b['BestTime'] > 0){
		if($a['BestTime'] < $b['BestTime'])
			return -1;
		else if($a['BestTime'] > $b['BestTime'])
			return 1;
	}else if($a['BestTime'] > 0)
		return -1;
	else if($b['BestTime'] > 0)
		return 1;
	// no time, or same time : use previous rank or no care
	if($a['MapRank'] < $b['MapRank'])
		return -1;
	else if($a['MapRank'] > $b['MapRank'])
		return 1;
	else if($a['Rank'] > $b['Rank'])
		return 1;
	return -1;
}



// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function matchMapTimeCompare($a, $b){
	if($a['BestTime'] > 0 && $b['BestTime'] > 0){
		if($a['BestTime'] < $b['BestTime'])
			return -1;
		else if($a['BestTime'] > $b['BestTime'])
			return 1;
		// same time, use previous rank or no care
		if($a['MapRank'] < $b['MapRank'])
			return -1;
		else if($a['MapRank'] > $b['MapRank'])
			return 1;
		else if($a['Rank'] > $b['Rank'])
			return 1;
	}else if($a['BestTime'] > 0)
		return -1;
	else if($b['BestTime'] > 0)
		return 1;
	// no time, or same time : use previous rank or no care
	if($a['MapRank'] < $b['MapRank'])
		return -1;
	else if($a['MapRank'] > $b['MapRank'])
		return 1;
	else if($a['Rank'] > $b['Rank'])
		return 1;
	return -1;
}



// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function matchMapCheckpointsCompare($a, $b){
	if($a['CPs'] > $b['CPs'])
		return -1;
	else if($a['CPs'] < $b['CPs'])
		return 1;
	if($a['BestTime'] > 0 && $b['BestTime'] > 0){
		if($a['BestTime'] < $b['BestTime'])
			return -1;
		else if($a['BestTime'] > $b['BestTime'])
			return 1;
	}else if($a['BestTime'] > 0)
		return -1;
	else if($b['BestTime'] > 0)
		return 1;
	// no time, or same time : use previous rank or no care
	if($a['MapRank'] < $b['MapRank'])
		return -1;
	else if($a['MapRank'] > $b['MapRank'])
		return 1;
	else if($a['Rank'] > $b['Rank'])
		return 1;
	return -1;
}


// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function rankingsStuntsCompare($a, $b){
	if($a['Score'] > $b['Score'])
		return -1;
	else if($a['Score'] < $b['Score'])
		return 1;
	if($a['BestTime'] < $b['BestTime'])
		return -1;
	else if($a['BestTime'] > $b['BestTime'])
		return 1;
	if($a['Rank'] < $b['Rank'])
		return -1;
	return 1;
}




//------------------------------------------
// RaceFinish
//------------------------------------------
function matchEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup){
	global $_debug,$_match_conf,$_players,$_match_map,$_match_map_rs,$_match_map_rswu,$_match_scores,$_GameInfos,$_WarmUp,$_FWarmUp,$_players_round_current,$_players_roundplayed_current,$_roundslimit_rule,$_EndMatchCondition,$_currentTime,$_players_round_time,$_FGameModes,$_FGameMode,$_match_scores_upd;

	if($_match_map < 1)
		return;
	$map_rs_next = false;

	matchBuildStateXml();

	// show panel to players
	matchScoreManialink();

	// MapRestarts WarmUp case
	if($_match_conf['MapRestartsWu'] > 0){

		if($_match_map_rswu < $_match_conf['MapRestartsWu']){
			console("matchEndRace:: increase match_map_rswu: {$_match_map_rswu} -> ".($_match_map_rswu+1));
			$_match_map_rswu++;
			ml_mainFWarmUpShow();
			addCall(null,'ChallengeRestart');
			return;

		}else{
			ml_mainFWarmUpHide();
		}
	}
	
	// classic Warmup case
	if($_WarmUp || $_FWarmUp > 0 || count($_match_scores) <= 0){
		if(isset($_match_conf['EndMatch']) && $_match_conf['EndMatch']){
			if($_debug>0) console("matchEndRace:: End by no scores... match_map={$_match_map}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
			matchEnd();
		}else{
			if($_debug>0) console("matchEndRace:: not end... match_map={$_match_map}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
		}
		return;
	}

			
	// get score max and time min
	$scoremax = 0;
	$timemin = -1;
	foreach($_match_scores as &$pls){
		if($scoremax < $pls['Score'])
			$scoremax = $pls['Score'];
		if($pls['BestTime'] > 0 && ($timemin <= 0 || $timemin < $pls['BestTime']))
			$timemin = $pls['BestTime'];
	}

	if($_debug>0) console("matchEndRace:: (state) match_map={$_match_map}, rswu={$_match_map_rswu}, rs={$_match_map_rs}, WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatchCondition={$_EndMatchCondition}, {$scoremax} >= {$_GameInfos['RoundsPointsLimit']}, {$_players_roundplayed_current}>{$_roundslimit_rule} , ".($_currentTime - $_players_round_time));

	if(isset($_FGameModes[$_FGameMode]['MatchEndRace']) && function_exists($_FGameModes[$_FGameMode]['MatchEndRace'])){
		// -------------------------------------------------------------------------
		// FGameMode with set MatchEndRace function
		// -------------------------------------------------------------------------
		$map_rs_next = call_user_func($_FGameModes[$_FGameMode]['MatchEndRace'],$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup,$scoremax,$timemin);

	}elseif($GameInfos['GameMode'] == 0){
		// -------------------------------------------------------------------------
		// general Rounds mode
		// -------------------------------------------------------------------------
		if($_match_conf['EndMatch'] || ($scoremax >= $_GameInfos['RoundsPointsLimit'] && $_GameInfos['RoundsPointsLimit'] > 0) ||
			 ($_roundslimit_rule > 0 && $_players_roundplayed_current > $_roundslimit_rule)){
			// Rounds, score reach pointslimit
			
			$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
			$msg1 = "MULTIMAP ROUNDS MATCH [{$_match_map}/{$_match_conf['NumberOfMaps']}] on [".stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).') ['.$_players_round_current.'r]';
			
			foreach($_match_scores as &$pls){
				// compute player time average
				$tottime = 0;
				foreach($pls['Times'] as $time){
					$tottime += $time;
				}
				$averagetime = (count($pls['Times']) > 0) ? (int)floor($tottime / count($pls['Times'])) : $tottime;
				$pls['RealMapScore'] = $pls['MapScore'] - $pls['PrevMapScore'];
				// write to log
				$msg1 .= "\n".$pls['Rank'].','.($pls['FullScore']+$pls['Bonus']+$pls['MapScore']).','.$pls['MapScore'].','.MwTimeToString($pls['BestTime']).','.MwTimeToString($averagetime).','.stripColors($pls['Login']).','.stripColors($pls['NickName']);
			}
			match_log($msg1."\n\n");
			
			// store in database (if available)
			matchDbStore($Ranking,$ChallengeInfo,$GameInfos);
			
			console("matchEndRace:(3): scores: ".print_r($_match_scores,true));

			// next BeginRace will compute GlobalScore/FullScore/FullTime/FullCPs and reset MapScore/BestTime/CPs
			$_match_scores_upd = true;


			if($_match_map < $_match_conf['NumberOfMaps']){
				$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$o Challenge {$_match_map}/{$_match_conf['NumberOfMaps']} finished !";
				addCall(null,'ChatSendServerMessage', $msg);
			}
			
			// prepare next match map
			$_match_map++;
			$map_rs_next = true;

			// end of match
			if($_match_conf['EndMatch'] || $_match_map > $_match_conf['NumberOfMaps']){
				if($_debug>0) console("matchEndRace:: Rounds, all maps played... match_map={$_match_map}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
				matchEnd();
			}
			
			// copy log
			match_log_copy();

		}else{
			// map not really done, back score if needed !
		}

	}elseif($GameInfos['GameMode'] == 5){
		// -------------------------------------------------------------------------
		// general Cup mode
		// -------------------------------------------------------------------------
		if($_match_conf['EndMatch'] || $_EndMatchCondition == 'Finished' || $_EndMatchCondition == 'ChangeMap'){
			// Cup, finished or real map change (ie not a map changed before the end)
			
			$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
			$msg1 = "CUP MATCH [{$_match_map}] on [".stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).') ['.$_players_round_current.'r]';
			
			foreach($_match_scores as &$pls){
				// compute player time average
				$tottime = 0;
				foreach($pls['Times'] as $time){
					$tottime += $time;
				}
				$averagetime = (int)floor($tottime / count($pls['Times']));
				$pls['RealMapScore'] = $pls['MapScore'] - $pls['PrevMapScore'];
				// write to log
				//$msg1 .= "\n".$pls['Rank'].','.$pls['MapScore'].','.$pls['ScoreBonus'].','.MwTimeToString($pls['BestTime']).','.MwTimeToString($averagetime).','.stripColors($pls['Login']).','.stripColors($pls['NickName']);
				$msg1 .= "\n".$pls['Rank'].','.$pls['MapScore'].','.$pls['ScoreBonus'].','.$pls['RealMapScore'].','.MwTimeToString($pls['BestTime']).','.MwTimeToString($averagetime).','.stripColors($pls['Login']).','.stripColors($pls['NickName']);
				$pls['PrevMapScore'] = $pls['MapScore'];
			}
			match_log($msg1."\n\n");

			if($_EndMatchCondition == 'Finished'){
				// store in database (if available)
				matchDbStore($Ranking,$ChallengeInfo,$GameInfos);
			}

			console("matchEndRace:(3): scores: ".print_r($_match_scores,true));
			
			// prepare next match map
			$_match_map++;
			$map_rs_next = true;
			
			// end of match
			if($_match_conf['EndMatch'] || $_EndMatchCondition == 'Finished'){
				if($_debug>1) console("matchEndRace:: cup finished... match_map={$_match_map}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
				if($_match_conf['EndMatch'])
					addCall(true,'NextChallenge');
				$_EndMatchCondition = 'Finished';
				matchEnd();
			}
			
			// copy log
			match_log_copy();
		}

	}elseif($GameInfos['GameMode'] == 1){
		// -------------------------------------------------------------------------
		// general Time Attack mode
		// -------------------------------------------------------------------------
		if($_match_conf['EndMatch'] || $_currentTime - $_players_round_time > $_GameInfos['TimeAttackLimit'] - 10000){
			// TA, time > limit - 10s
			
			$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
			$msg1 = "MULTIMAP TIME ATTACK MATCH [{$_match_map}/{$_match_conf['NumberOfMaps']}] on [".stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).')';
			
			foreach($_match_scores as &$pls){
				$msg1 .= "\n".$pls['Rank'].','.MwTimeToString($pls['FullTime']+$pls['BestTime']).','.MwTimeToString($pls['BestTime']).','.$pls['FullScore']+$pls['MapScore'].','.$pls['MapScore'].','.stripColors($pls['Login']).','.stripColors($pls['NickName']);
			}
			match_log($msg1."\n\n");
			
			// store in database (if available)
			matchDbStore($Ranking,$ChallengeInfo,$GameInfos);

			console("matchEndRace:(3): scores: ".print_r($_match_scores,true));

			// next BeginRace will compute GlobalScore/FullScore/FullTime/FullCPs and reset MapScore/BestTime/CPs
			$_match_scores_upd = true;

			
			if($_match_conf['MapRestarts'] > 1 && $_match_map_rs < $_match_conf['MapRestarts']){

				if($_debug>1) console("matchEndRace:: TA, map restart... match_map={$_match_map}, rswu={$_match_map_rswu}, rs={$_match_map_rs}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
				$_match_map_rs++;
				addCall(null,'ChallengeRestart');

			}else{
			
				if($_match_map < $_match_conf['NumberOfMaps']){
					$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$o Challenge {$_match_map}/{$_match_conf['NumberOfMaps']} finished !";
					addCall(null,'ChatSendServerMessage', $msg);
				}

				// prepare next match map
				$_match_map++;
				$map_rs_next = true;

				// end of match
				if($_match_conf['EndMatch'] || $_match_map > $_match_conf['NumberOfMaps']){
					if($_debug>1) console("matchEndRace:: TA, all maps played... match_map={$_match_map}, rswu={$_match_map_rswu}, rs={$_match_map_rs}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
					matchEnd();
				}
				
				// copy log
				match_log_copy();
			}
		}

	}elseif($GameInfos['GameMode'] == 4){
		// -------------------------------------------------------------------------
		// general Stunts mode
		// -------------------------------------------------------------------------
		if($_match_conf['EndMatch'] || $_currentTime - $_players_round_time > $_GameInfos['TimeAttackLimit'] - 10000){
			// Stunts, time > limit - 10s
			
			$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
			$msg1 = "MULTIMAP STUNTS MATCH [{$_match_map}/{$_match_conf['NumberOfMaps']}] on [".stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).')';
			
			foreach($_match_scores as &$pls){
				$msg1 .= "\n".$pls['Rank'].','.($pls['FullScore']+$pls['Bonus']+$pls['MapScore']).','.$pls['MapScore'].','.stripColors($pls['Login']).','.stripColors($pls['NickName']);
			}
			match_log($msg1."\n\n");
			
			// store in database (if available)
			matchDbStore($Ranking,$ChallengeInfo,$GameInfos);

			console("matchEndRace:(4): scores: ".print_r($_match_scores,true));

			// next BeginRace will compute GlobalScore/FullScore/FullTime/FullCPs and reset MapScore/BestTime/CPs
			$_match_scores_upd = true;


			if($_match_conf['MapRestarts'] > 1 && $_match_map_rs < $_match_conf['MapRestarts']){

				if($_debug>0) console("matchEndRace:: Stunts, map restart... match_map={$_match_map}, rswu={$_match_map_rswu}, rs={$_match_map_rs}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
				$_match_map_rs++;
				addCall(null,'ChallengeRestart');

			}else{
			
				if($_match_map < $_match_conf['NumberOfMaps']){
					$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$o Challenge {$_match_map}/{$_match_conf['NumberOfMaps']} finished !";
					addCall(null,'ChatSendServerMessage', $msg);
				}
				
				// prepare next match map
				$_match_map++;
				$map_rs_next = true;

				// end of match
				if($_match_conf['EndMatch'] || $_match_map > $_match_conf['NumberOfMaps']){
					if($_debug>0) console("matchEndRace:: Stunts, all maps played... match_map={$_match_map}, rswu={$_match_map_rswu}, rs={$_match_map_rs}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
					matchEnd();
				}else{
					if($_debug>0) console("matchEndRace:: Stunts, play next map... match_map={$_match_map}, rswu={$_match_map_rswu}, rs={$_match_map_rs}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
				}
	
				// copy log
				match_log_copy();
			}
		}

	}elseif($GameInfos['GameMode'] == 3){
		// -------------------------------------------------------------------------
		// general Laps mode
		// -------------------------------------------------------------------------
		if($_match_conf['EndMatch'] || $timemin > 0){
			// Laps, a player have finished
			
			$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
			$msg1 = "MULTIMAP LAPS MATCH [{$_match_map}/{$_match_conf['NumberOfMaps']}] on [".stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).')';
			
			foreach($_match_scores as &$pls){
				$msg1 .= "\n".$pls['Rank'].','.($pls['FullScore']+$pls['Bonus']+$pls['MapScore']).','.MwTimeToString($pls['BestTime']).','.$pls['MapScore'].','.stripColors($pls['Login']).','.stripColors($pls['NickName']);
			}
			match_log($msg1."\n\n");
			
			// store in database (if available)
			matchDbStore($Ranking,$ChallengeInfo,$GameInfos);
			
			if($_match_map < $_match_conf['NumberOfMaps']){
				$msg = localeText(null,'server_message').localeText(null,'interact')."\$f00\$o Challenge {$_match_map}/{$_match_conf['NumberOfMaps']} finished !";
				addCall(null,'ChatSendServerMessage', $msg);
			}

			// prepare next match map
			$_match_map++;
			$map_rs_next = true;

			console("matchEndRace:(5): scores: ".print_r($_match_scores,true));

			// next BeginRace will compute GlobalScore/FullScore/FullTime/FullCPs and reset MapScore/BestTime/CPs
			$_match_scores_upd = true;

			
			// end of match
			if($_match_conf['EndMatch'] || $_match_map > $_match_conf['NumberOfMaps']){
				if($_debug>1) console("matchEndRace:: Laps, all maps played... match_map={$_match_map}, nbscores=".count($_match_scores).", WarmUp={$_WarmUp}, FWarmUp={$_FWarmUp}, EndMatch={$_match_conf['EndMatch']}, EndMatchCondition={$_EndMatchCondition}");
				matchEnd();
			}
			
			// copy log
			match_log_copy();
		}
	}

	// ---------------------------------------------------------------------------
	// set server setup (for next map)
	if($_match_map >= 1 && $_match_conf['Finished'] <= 0){
		if($_match_conf['MapRestarts'] > 0 && $_match_map_rs < $_match_conf['MapRestarts']){
			if($_debug>0) console("matchEndRace:: map not finished, restart map {$_match_map_rs},{$_match_conf['MapRestarts']}");
			addCall(true,'ChallengeRestart'); // should not be needed, but at least avoid matchServerSetup()

		}else{
			matchServerSetup();

			if($map_rs_next){
				if($_debug>0) console("matchEndRace:: map finished, prepare next map {$_match_map_rs},{$_match_conf['MapRestarts']} -> 1 & rswu=0");
				$_match_map_rs = 1;
				$_match_map_rswu = 0;
			}
		}
	}
}


//------------------------------------------
function matchChallengeListModified($event,$curchalindex,$nextchalindex,$islistmodified){
	global $_debug,$_StatusCode,$_match_map,$_match_conf;
	if($_match_map < 0 || $_match_conf['Finished'] <= 0)
		return;

	// if map changed after EndRace then redo setup !
	if($_StatusCode >= 5){
		if($_debug>1) console("matchChallengeListModified:: call matchServerSetup() !");
		matchServerSetup();
	}
}






//--------------------------------------------------------------
function matchBuildLogoXml(){
	global $_match_conf,$_ml_act,$_match_logo_xml;

	$_match_logo_xml = '';

	$max = 10;
	if(isset($_match_conf['ImgUrl']) && $_match_conf['ImgUrl'] != ''){
		$_match_logo_xml = 
			sprintf('<quad sizen="%0.2F %0.2F" posn="43.6 %0.2F 0" halign="right" image="%s" action="'.$_ml_act['match.infos'].'" actionkey="2"/>',
							$max,$max,46.2,htmlspecialchars($_match_conf['ImgUrl']));
	}
}


//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove'
//--------------------------------------------------------------
function matchUpdateLogoXml($login,$action='show'){
	global $_debug,$_players,$_match_logo_xml;

	if($login === true){
		foreach($_players as $login => &$pl){
			if(isset($pl['ML']))
				matchUpdateLogoXml($login,$action);
		}
		if($_match_logo_xml == '' || $action == 'hide')
			manialinksRemoveOnRelay('match.logo.relay');
		else
			manialinksShowForceOnRelay('match.logo.relay',$_match_logo_xml);
		return;
	}
	if($_debug>3) console("matchUpdateLogoXml('$login',$action) - ".$_players[$login]['Status']);

	if($action == 'remove'){
		// remove manialink
		manialinksRemove($login,'match.logo');
		//console("LOGO: off ($login) 1");
		return;
	}elseif($action == 'hide' || $_match_logo_xml == ''){
		// hide manialink
		manialinksHide($login,'match.logo');
		//console("LOGO: off ($login) 2");
		return;
	}
	// show/refresh
	if(false && $_players[$login]['Status2'] > 1){
		// none to show and opened : hide it
		if(manialinksIsOpened($login,'match.logo'))
			manialinksHide($login,'match.logo');
		//console("LOGO: off ($login) 3");
		return;
	}
	//console("LOGO: on ($login)");
	manialinksShowForce($login,'match.logo',$_match_logo_xml);
}




//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'update'
//--------------------------------------------------------------
function matchUpdateInfosXml($login,$action='show'){
	global $_debug,$_players,$_ml_act,$_match_conf,$_ChallengeInfo;

	if(!isset($_match_conf['LocaleInfo']) || $_match_conf['LocaleInfo'] == '')
		return;

	if($login === true){
		foreach($_players as $login => &$pl){
			if(isset($pl['ML']) && $pl['Active'])
				matchUpdateInfosXml($login,$action);
		}
		return;
	}
	//if($_debug>3) console("matchUpdateInfosXml('$login',$action) - ".$_players[$login]['Status']);

	if($action == 'update' && !manialinksIsOpened($login,'match.infos')){
		return;

	}elseif($action == 'hide'){
		// hide manialink
		manialinksHide($login,'match.infos');
		return;
	}

	$title = $_match_conf['Title'];
	$_match_info_text = htmlspecialchars(localeText($login,$_match_conf['LocaleInfo']));

	$xml = "<frame posn='0 8 16'>"
		."<quad  sizen='88 56' posn='0 0 0' halign='center' valign='center' style='Bgs1' substyle='BgWindow1' action='1'/>"
		."<quad  sizen='88 56' posn='0 0 0' halign='center' valign='center' style='Bgs1' substyle='BgWindow1'/>"
		."<quad  sizen='86 4' posn='0 25 0.1' halign='center' valign='center' style='Bgs1' substyle='BgTitle3_4'/>"
		//."<label sizen='2 3' posn='-45 25 0.2' halign='center' valign='center' styletextsize='2' textcolor='ffff' text='.'/>"
		."<label sizen='78 3' posn='0 25 0.2' halign='center' valign='center' styletextsize='2' textcolor='ffff' text='{$title}'/>"
		."<label posn='0 -25 0' halign='center' valign='center' style='CardButtonMedium' text='OK' action='".$_ml_act['match.infos']."'/>";
	$xml .= "<label sizen='80 35' posn='-40 0 0.2' halign='left' valign='center2' styletextsize='2' textcolor='ffff' autonewline='1'>{$_match_info_text}</label>";
	$xml .= "</frame>";
	//console("match infos($login): $xml");
	manialinksShowForce($login,'match.infos',$xml);
}



//------------------------------------------
function matchInitLog(){
	global $_ServerOptions,$_match_conf,$match_filename,$htmlmatch_filename,$_DedConfig;

	$title = stripColors($_match_conf['Title']);
	// keep only alphanum chars
	for($i=0; $i < strlen($title); $i++){
		if(!ctype_alnum($title[$i]))
			$title[$i] = '_';
	}

	$name = stripColors($_ServerOptions['Name']);
	// keep only alphanum chars
	for($i=0; $i < strlen($name); $i++){
		if(!ctype_alnum($name[$i]))
			$name[$i] = '_';
	}
	
	if(!file_exists('matchlog'))
		mkdir('matchlog');
	$date = date('md.Hi');
	$match_filename = "matchlog/{$title}.{$date}.{$name}.{$_DedConfig['login']}.txt";
	$htmlmatch_filename = "{$title}.{$date}.{$name}.{$_DedConfig['login']}.html";
}


//------------------------------------------
// write in match log with time
//------------------------------------------
function match_log($text){
	global $match_filename;

	$match_file = fopen($match_filename,'ab');
	fwrite($match_file,"[".date("m/d,H:i:s")."] $text\n");
	fflush($match_file);
	fclose($match_file);
}


//--------------------------------------------------------------
// copy html match log
//--------------------------------------------------------------
function match_log_copy(){
	global $_debug,$_match_copy,$_match_url,$match_filename,$htmlmatch_filename;
	
	//$_match_copy = "/var/www/matchlogs/";
	//$_match_copy = "ftp://xxxx:yyy@ftpperso.free.fr/match/";
	//$_match_url = "http://xxxx.free.fr/match/" 
	if(isset($_match_copy)){
		
		// make html matchlog
		$datas = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>TM Match</title></head><body><pre>';
		$datas .= htmlspecialchars(file_get_contents($match_filename),ENT_QUOTES,'UTF-8');
		$datas .= '</pre></body></html>';
		$nb = file_put_contents('matchlog/'.$htmlmatch_filename,$datas);

		if($nb > 100){
			// copy html matchlog
			if($_debug>0) console("Copy matchlog/{$htmlmatch_filename} ({$nb}/".strlen($datas).")...");

			if(isset($_match_url))
				$addcall = array(null,'ChatSendServerMessage', 
												 localeText(null,'server_message').'$l['.$_match_url.$htmlmatch_filename.']matchlog copied.');
			else
				$addcall = null;

			file_copy('matchlog/'.$htmlmatch_filename,$_match_copy.$htmlmatch_filename,$addcall);
		}
	}
}


//------------------------------------------
// add player or team in match scores table
// for team: $login = $playerid = fteam id !
function matchAddPlayer($login,$playerid,$nickname,$score=0,$besttime=0,$mapscore=0){
	global $_match_scores,$_FGameModes,$_FGameMode,$_fteams;
	if(!isset($_match_scores[$login])){
		$_match_scores[$login] = array('Login'=>$login,
																	 'PlayerId'=>$playerid,
																	 'NickName'=>tm_substr($nickname,0,24),
																	 'NickDraw'=>htmlspecialchars(tm_substr($nickname,0,24)),
																	 'NickDraw2'=>htmlspecialchars(tm_substr(stripColors($nickname),0,24)),
																	 'Score'=>$score, // player server score
																	 'BestTime'=>$besttime, // player server best time
																	 'CPs'=>0, // map cumulated CPs
																	 'MapScore'=>$mapscore, // player map score computed by the match plugin
																	 'MapRank'=>999,
																	 'FullTime'=>0, // player server cumulated best time
																	 'FullCPs'=>0, // match cumulated CPs
																	 'FullScore'=>0, // full score for multimap Rounds (unused in Cup)
																	 'Rank'=>999,
																	 'ScoreBonus'=>0, // bonus added to server score
																	 'Bonus'=>0, // bonus, add it to FullScore (unused in Cup)
																	 'Times'=>array());

		// set initial match score if any
		if(isset($_FGameModes[$_FGameMode]['FTeams']) && $_FGameModes[$_FGameMode]['FTeams']){
			// it's a fteam mode
			$tid = $login;
			if($tid === $playerid && isset($_fteams[$tid]['MatchPrevScore']) && $_fteams[$tid]['MatchPrevScore'] > 0){
				if($_debug>0) console("matchAddPlayer:: fteam mode[{$tid}] : add init score : {$_fteams[$tid]['MatchPrevScore']}");
				$_match_scores[$tid]['FullScore'] = $_fteams[$tid]['MatchPrevScore'];
				$_fteams[$tid]['MatchScore'] = $_fteams[$tid]['MatchPrevScore'];
			}
		}

	}
}


//--------------------------------------------------------------
// update game scores from $_match_scores scores
// use login=true for all
function matchUpdateScore($login,$force=true){
	global $_debug,$_match_map,$_match_scores,$_players,$_match_conf,$_GameInfos;

	if($_match_map < 0 || $_match_conf['MapScoresMode'] !== 'Scores' || isset($_match_conf['MapScoresList'][0]) || 
		 ($_GameInfos['GameMode'] != 0 && $_GameInfos['GameMode'] != 5 && $_GameInfos['GameMode'] != 2))
		return;

	if($login === true){
		// score to all players
		if(count($_match_scores) > 0){
			//if($_debug>6) console("matchUpdateScore(true):: _match_scores: ".print_r($_match_scores,true));
			$scores = array();
			foreach($_match_scores as &$pls){
				$login = $pls['Login'];
				if(isset($_players[$login]['Active']) && $_players[$login]['Active'] && isset($_players[$login]['Score']) && 
					 ($force || $pls['Score'] != $_players[$login]['Score'])){
					$pls['PlayerId'] = $_players[$login]['PlayerId']; // update player id !!!
					$scores[] = array('PlayerId'=>$pls['PlayerId'],
														'Score'=>$pls['Score']);
				}
			}
			if(count($scores) > 0){
				if($_debug>2) console("matchUpdateScore(true):: scores: ".print_r($scores,true));
				addCall(null,'ForceScores',$scores,true);
			}
		}
		
	}elseif(isset($_players[$login]['PlayerId']) && isset($_match_scores[$login]['Score']) && $_match_scores[$login]['Score'] > 0){
		$_match_scores[$login]['PlayerId'] = $_players[$login]['PlayerId']; // update player id !!!
		if($_debug>0) console("matchUpdateScore({$login},{$force}):: ".print_r($_players[$login],true));

		if($force || (isset($_players[$login]['Score']) && $_match_scores[$login]['Score'] != $_players[$login]['Score'])){
			// score to login
			$scores = array(array('PlayerId'=>$_match_scores[$login]['PlayerId'],
														'Score'=>$_match_scores[$login]['Score']));
			if($_debug>2) console("matchUpdateScore({$login}):: scores: ".print_r($scores,true));
			addCall(true,'ForceScores',$scores,true);
		}
	}
}


//--------------------------------------------------------------
function matchSetBonus($admlogin, $playerlogin, $bonus){
	global $_debug,$_match_scores,$_match_conf,$_match_map;
	
	if($_match_map < 0 || !isset($_match_scores[$playerlogin]) || $bonus < 0)
		return;

	// set bonus
	console("matchSetBonus: set score to {$playerlogin} ({$bonus})");
	if($_match_conf['GameMode'] == 5){
		// cup
		debugPrint("matchSetBonus:: $bonus : match_scores[$playerlogin]",$_match_scores[$playerlogin]);
		$diff = $bonus - $_match_scores[$playerlogin]['ScoreBonus'];
		$_match_scores[$playerlogin]['ScoreBonus'] = $bonus;
		$_match_scores[$playerlogin]['Score'] += $diff;
		if($_match_scores[$playerlogin]['Score'] < $bonus)
			$_match_scores[$playerlogin]['Score'] = $bonus;

		if($_match_map > 0){
			$scores = array(array('PlayerId'=>$_match_scores[$playerlogin]['PlayerId'],
														'Score'=>$_match_scores[$playerlogin]['Score']));
			addCall($admlogin,'ForceScores',$scores,true);
			if($_debug>1) debugPrint("matchSetBonus::scores",$scores);
		}
		if($_debug>1) debugPrint("matchSetBonus::match_scores[$playerlogin]",$_match_scores[$playerlogin]);

	}else{
		// rounds (or ... ?)
		$_match_scores[$playerlogin]['Bonus'] = $bonus;
	}

	// sort list
	matchSortMatchScores();
	
	$nick = $_match_scores[$playerlogin]['NickDraw2'];
	$msg = localeText(null,'server_message').localeText(null,'interact')." \$o\$44fBonus for {$nick} : {$bonus}";
	// send message in offical chat
	addCall(null,'ChatSendServerMessage', $msg);

	//debugPrint("matchSetBonus - _match_scores",$_match_scores);
}


//--------------------------------------------------------------
// set game scores from $_match_scores scores
function matchSetScore($admlogin, $playerlogin, $score){
	global $_debug,$_match_scores,$_match_conf,$_match_map;
	
	if($_match_map < 1 || !isset($_match_scores[$playerlogin]) || $score < 0 || $score < $_match_scores[$playerlogin]['ScoreBonus'])
		return;

	// set score
	console("matchSetScore: set score to {$playerlogin} ({$score})");
	$_match_scores[$playerlogin]['Score'] = $score;
	$scores = array(array('PlayerId'=>$_match_scores[$playerlogin]['PlayerId'],
												'Score'=>$_match_scores[$playerlogin]['Score']));
	addCall($admlogin,'ForceScores',$scores,true);
	debugPrint("matchSetScore::scores",$scores);
	
	// sort scores list
	matchSortMatchScores();
	
	$nick = $_match_scores[$playerlogin]['NickDraw2'];
	$msg = localeText(null,'server_message').localeText(null,'interact')." \$o\$88fScore for {$nick} : {$score}";
	// send message in offical chat
	addCall(null,'ChatSendServerMessage', $msg);

	//debugPrint("matchSetScore - _match_scores",$_match_scores);
}


// -----------------------------------
// show next maps infos in chat
function matchShowMaplist(){
	global $_debug,$_match_conf,$_match_map,$_ChallengeList;
	
	$maxi = $_match_conf['ShowNextMaps'] - $_match_map + 1;
	if($maxi < 1)
		return;

	$msg = localeText(null,'server_message').'Next challenges: ';
	$msg .= stringMapList($maxi);
	
	// send message in offical chat
	addCall(null,'ChatSendServerMessage', $msg);
}


// -----------------------------------
// if startmatch is true then setup the map list and next challenge
function matchServerSetup($login=true,$startmatch=false,$startdelay=0){
	global $_debug,$_match_conf,$_CurrentChallengeIndex,$_NextChallengeIndex,$_ChallengeList,$_NextChallengeInfo,$_envirs,$_methods_list,$_ServerInfos,$_match_OldServerOptions,$_ServerOptions,$_match_OldGameInfos,$_NextGameInfos,$_match_OldCallVoteTimeOut,$_CallVoteTimeOut,$_match_Oldroundslimit_rule,$_roundslimit_rule,$_match_Oldteamroundslimit_rule,$_match_OldFGameModes,$_match_OldFGameMode,$_teamroundslimit_rule,$_match_Oldroundspoints_rule,$_roundspoints_rule,$_cup_autoadjust,$_match_Oldcup_autoadjust,$_SetServerOptions,$_SetGameInfos,$_MapFalsestart,$_match_OldMapFalsestart,$_match_map,$_FGameModes,$_FGameMode;

	$author = ($login === true) ? $_ServerInfos['Login'] : $login;

	$chattime = $_match_conf['ChatTime'];

	// starting match : store current config and set maps list
	if($startmatch){
		$_match_conf['MapsConf'] = array();

		// save old values
		$_match_Oldroundspoints_rule = $_roundspoints_rule;
		$_match_Oldroundslimit_rule = $_roundslimit_rule;
		$_match_Oldteamroundslimit_rule = $_teamroundslimit_rule;
		$_match_Oldcup_autoadjust = $_cup_autoadjust;
		$_match_OldCallVoteTimeOut = $_CallVoteTimeOut;
		$_match_OldServerOptions = $_ServerOptions;
		$_match_OldGameInfos = $_NextGameInfos;
		$_match_OldMapFalsestart = $_MapFalsestart;

		$_match_OldFGameModes = $_FGameModes;
		$_match_OldFGameMode = $_FGameMode;

		// disable users votes
		$_CallVoteTimeOut = 0;
		addCall($login,'SetCallVoteTimeOut',0);

		// map list
		if(count($_match_conf['MapsMode']) == 1){
			$ident = $_match_conf['MapsMode'][0]['Ident'];
			console("MatchMode ident: {$ident}");

			if($ident === 'current'){
				$ident = $_CurrentChallengeIndex;
			}elseif($ident === 'next'){
				$ident = $_NextChallengeIndex;
			}

			if($ident === 'shuffle'){
				// MapMode is 'shuffle' : shuffle maps
				$clist = mapsShuffle($_ChallengeList,1);
				addCall($login,'ChooseNextChallengeList',$clist);
				console("matchServerSetup:: started the match ! (challenges shuffled)");
				$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) started {$_match_conf['Title']} match ! (challenges shuffled)";
				
			}elseif(is_numeric($ident) && $ident < 2000){
				// MapsMode is numeric : go to that map index
				if($ident == $_CurrentChallengeIndex){
					mapRealRestart();
				}else{
					addCall($login,'SetNextChallengeIndex',$ident+0);
				}
				console("matchServerSetup:: started the match ! (map index: {$ident})");
				$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) started {$_match_conf['Title']} match !... (map index: {$ident})";
					
			}elseif(is_string($ident) && $ident != ''){
				// MapsMode is string : go to next map with that envir/name/uid
				$num = false;
				$clist = buildMapList('next',$ident,$num);
				if($clist !== false){
					console("matchServerSetup:: started the match ! ({$ident})");
					$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) started {$_match_conf['Title']} match ! ({$ident})";
					addCall($login,'ChooseNextChallengeList',$clist);
				}else{
					console("matchServerSetup:: started the match ! (buildMapList returned false!)");
					$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) started {$_match_conf['Title']} match !... (failed to start on specified map)";
				}
			}

		}elseif(count($_match_conf['MapsMode']) > 0){
			// make map list
			$max = count($_ChallengeList);
			$ind = $_NextChallengeIndex;
			$clist = array();
			$list = array();
			foreach($_match_conf['MapsMode'] as $map){
				$envir = false;
				$ident = $map['Ident'];
				if(isset($_envirs[strtolower($ident)])){
					$envir = true;
					$ident = $_envirs[strtolower($ident)];
					$map['Ident'] = $ident;
				}

				// search map in list
				for($ind2=$ind; $ind2 < $ind+$max; $ind2++){
					$ind3 = $ind2 % $max;
					if(!isset($list[$ind3])){
						if(($envir && $_ChallengeList[$ind3]['Environnement'] == $ident) ||
							 (!$envir && $_ChallengeList[$ind3]['UId'] == $ident) ||
							 (!$envir && stristr(stripColors($_ChallengeList[$ind3]['Name']),$ident) !== false)){
							$clist[] = $_ChallengeList[$ind3]['FileName'];
							$list[$ind3] = $ind3;
							if(count($map) > 1)
								$_match_conf['MapsConf'][$map['UId']] = $map;
							break;
						}
					}
				}
			}
			// set list
			if(count($clist) > 0){
				console("matchServerSetup:: started the match ! (".implode(',',$clist).")");
				$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) started {$_match_conf['Title']}  match ! (".count($clist)." maps)";
				addCall($login,'ChooseNextChallengeList',$clist);
			}else{
				console("matchServerSetup:: started the match ! (building list failed !)");
				$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) started {$_match_conf['Title']} match !... (building list failed ?!)";
			}

		}else{
			// just start match on next map
			console("matchServerSetup:: started the match ! (on next map)");
			$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) started {$_match_conf['Title']} match !";
		}

		if($_debug>0 && count($_match_conf['MapsConf'])) debugPrint("matchServerSetup:: MapsConf ",$_match_conf['MapsConf']);

		// match start: set short chattime unless startdelay
		$chattime = 100;
		if($startdelay > 0){
			// start of match : set chattime as start delay, $startdelay is supposed to be in seconds
			$chattime = (int)(($startdelay + 4) * 1000);
		}


		addCallDelay(1000,$login,'NextChallenge');
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);
	}


	// -------------------------
	// set config (for next map)
	// -------------------------

	// set map specific conf in general conf
	if(isset($_NextChallengeInfo['UId']) && isset($_match_conf['MapsConf'][$_NextChallengeInfo['UId']])){
		foreach($_match_conf['MapsConf'][$_NextChallengeInfo['UId']] as $conf => $value){
			if($conf !== 0 && !isset($_match_conf[$conf]) || $_match_conf[$conf] !== $value){
				if($_debug>0) console("matchServerSetup:: {$_NextChallengeInfo['UId']} specific: {$conf} => {$value}");
				$_match_conf[$conf] = $value;
			}
		}
	}

	// ---------------------------
	// set server config : options
	matchConvSetServerOptions($_match_conf);
	$serveroptions = array_update($_SetServerOptions,$_ServerOptions,$_match_conf,'Next');
	if($_debug>1) debugPrint("matchServerSetup:: serveroptions",$serveroptions);
	addCall(true,'SetServerOptions',$serveroptions);

	// -----------------------------
	// set server config : gameinfos
	$gameinfos = array_update($_SetGameInfos,$_NextGameInfos,$_match_conf);

	// increase warmup if needed for BreakBeforeMaps
	if($_match_map > 0 && isset($_match_conf['BreakWarmUp']) && $_match_conf['BreakWarmUp'] > 0 &&
		 isset($_match_conf['BreakBeforeMaps']) && 
		 array_search($_match_map,$_match_conf['BreakBeforeMaps']) !== false){
		if($_debug>0) console("matchServerSetup:: set warmup for break : {$gameinfos['AllWarmUpDuration']} + {$_match_conf['BreakWarmUp']}) ({$_match_map} in ".implode(',',$_match_conf['BreakBeforeMaps']).")");
		$gameinfos['AllWarmUpDuration'] += $_match_conf['BreakWarmUp'];
		$gameinfos['CupWarmUpDuration'] += $_match_conf['BreakWarmUp'];
	}
	if($_debug>1) debugPrint("matchServerSetup:: gameinfos",$gameinfos);

	addCall(true,'SetGameInfos',$gameinfos);

	// ----------------------------------------
	// set special values for RoundsRoundsLimit
	if(isset($_match_conf['RoundsRoundsLimit']) && $_match_conf['RoundsRoundsLimit'] > 0 && ($_match_conf['GameMode'] == 0)){
		$_roundslimit_rule = $_match_conf['RoundsRoundsLimit'];
	}else{
		$_roundslimit_rule = -1;
	}

	// set special values for TeamRoundsLimit
	if(isset($_match_conf['TeamRoundsLimit']) && $_match_conf['TeamRoundsLimit'] > 0 && ($_match_conf['GameMode'] == 2)){
		$_teamroundslimit_rule = $_match_conf['TeamRoundsLimit'];
	}else{
		$_teamroundslimit_rule = -1;
	}

	// set special values for CupAutoAdjust
	if(isset($_match_conf['CupAutoAdjust']) && $_match_conf['CupAutoAdjust'] > 0 && ($_match_conf['GameMode'] == 5)){
		$_cup_autoadjust = $_match_conf['CupAutoAdjust'];
	}else{
		$_cup_autoadjust = 0;
	}

	// set special values for CustomPoints
	if($_match_conf['GameMode'] == 0 || $_match_conf['GameMode'] == 5){
		if(isset($_match_conf['CustomPoints']) && $_match_conf['CustomPoints'] != '')
			setCustomPoints($_match_conf['CustomPoints']);
		else
			setCustomPoints();
	}

	// set false start
	$_MapFalsestart = $_match_conf['MapFalseStart'];
	falsestartStartMatch($_match_conf['MatchFalseStart']);

	// ----------------------------------------
	// set special values for FGameModes
	if(isset($_match_conf['FGameMode']) && $_match_conf['FGameMode'] != ''){
		$fgamemode = $_match_conf['FGameMode'];
		console("matchServerSetup:: set FGameMode to {$fgamemode} and associated values !");
		setNextFGameMode($fgamemode);

		foreach($_match_conf as $conf => $val){
			if(isset($_FGameModes[$fgamemode][$conf]))
				$_FGameModes[$fgamemode][$conf] = $val;
		}
	}

	addCall(null,'SetChatTime',$chattime);
}


//--------------------------------------------------------------
function matchServerRestore(){
	global $_debug,$_match_conf,$_methods_list,$_match_OldServerOptions,$_match_OldGameInfos,$_match_OldCallVoteTimeOut,$_CallVoteTimeOut,$_match_Oldroundslimit_rule,$_roundslimit_rule,$_match_Oldteamroundslimit_rule,$_match_OldFGameModes,$_match_OldFGameMode,$_teamroundslimit_rule,$_match_Oldroundspoints_rule,$_cup_autoadjust,$_match_Oldcup_autoadjust,$_ServerOptions,$_SetServerOptions,$_NextGameInfos,$_SetGameInfos,$_MapFalsestart,$_match_OldMapFalsestart,$_FGameModes,$_FGameMode;

	if($_debug>0) console("matchServerRestore() ...");
	// restore special Fast values
	setCustomPoints($_match_Oldroundspoints_rule);
	$_roundslimit_rule = $_match_Oldroundslimit_rule;
	$_teamroundslimit_rule = $_match_Oldteamroundslimit_rule;
	$_cup_autoadjust = $_match_Oldcup_autoadjust;
	$_CallVoteTimeOut = $_match_OldCallVoteTimeOut;

	$_FGameModes = $_match_OldFGameModes;
	$_FGameMode = $_match_OldFGameMode;

	// restore dedicated values
	$serveroptions = array_update($_SetServerOptions,$_ServerOptions,$_match_OldServerOptions,'Next');
	debugPrint("matchServerRestore:: serveroptions",$serveroptions);
	addCall(true,'SetServerOptions',$serveroptions);

	$gameinfos = array_update($_SetGameInfos,$_NextGameInfos,$_match_OldGameInfos);
	debugPrint("matchServerRestore:: $gameinfos",$gameinfos);
	addCall(true,'SetGameInfos',$gameinfos);

	falsestartEndMatch();
	$_MapFalsestart = $_match_OldMapFalsestart;
}


//--------------------------------------------------------------
function matchStart($login, $params, $startdelay=0){
	global $_debug,$_match_config,$_match_startit,$_match_conf,$_match_map,$_match_map_rswu,$_match_map_rs,$_match_mode,$_match_scores,$_is_relay,$_methods_list,$_NextGameInfos,$_GameInfos,$_ChallengeList,$_players,$_mapinfo_default,$_scorepanel_hide,$_ServerInfos,$_StatusCode,$_match_rounds,$_match_starttime,$_lastEverysecond,$_FGameModes,$_FGameMode,$_match_scores_upd;
	//if($_debug>0) console("matchStart($login)");

	$_match_startit = false;
	matchReadConfigs('plugins/match.configs.xml.txt');
	matchReadConfigs('custom/match.configs.custom.xml.txt');

	$author = ($login === true) ? $_ServerInfos['Login'] : $login;

	if($_match_map >= 0){
		$msg = localeText(null,'server_message').localeText(null,'interact').'match already started !';
		console("matchStart::bad config: ".stripColors($msg));
		addCall(null,'ChatSendToLogin', $msg, $login);
		
	}elseif(count($_ChallengeList) < 1){
		$msg = localeText(null,'server_message').localeText(null,'interact').'need at least one challenge in list !';
		console("matchStart::bad config: ".stripColors($msg));
		addCall(null,'ChatSendToLogin', $msg, $login);
			
	}elseif($_match_map >= 0){
		$msg = localeText(null,'server_message').localeText(null,'interact').'match already started !';
		console("matchStart::bad config: ".stripColors($msg));
		addCall(null,'ChatSendToLogin', $msg, $login);

	}elseif(isset($params[0]) && $params[0] != '' && !isset($_match_config[$params[0]]['GameMode'])){
		$modelist = implode(',',array_keys($_match_config));
		$msg = localeText(null,'server_message').localeText(null,'interact')."Match mode {$params[0]} unknown ! ({$modelist})";
		console("matchStart::bad config: ".stripColors($msg));
		addCall(null,'ChatSendToLogin', $msg, $login);
			
	}else{
		// set match mode ?
		$match_mode = isset($_match_mode) ? $_match_mode : '';
		if(isset($params[0]) && $params[0] != '' && isset($_match_config[$params[0]]['GameMode'])){
			$match_mode = $params[0];
			
		}elseif(!isset($_match_config[$match_mode]['GameMode'])){
			// no match mode is set !
			$msg = localeText(null,'server_message').localeText(null,'interact')."sorry, no match mode is set !";
			console("matchStart::bad config: ".stripColors($msg));
			addCall(null,'ChatSendToLogin', $msg, $login);
			return;
		}

		// add warmup info in match_config if not already set
		if(!isset($_match_config[$match_mode]['AllWarmUpDuration']))
			$_match_config[$match_mode]['AllWarmUpDuration'] = 0;
		if(!isset($_match_config[$match_mode]['CupWarmUpDuration']))
			$_match_config[$match_mode]['CupWarmUpDuration'] = 0;
		if($_match_config[$match_mode]['GameMode'] == 5)
			$_match_config[$match_mode]['AllWarmUpDuration'] = $_match_config[$match_mode]['CupWarmUpDuration'];
		else
			$_match_config[$match_mode]['CupWarmUpDuration'] = $_match_config[$match_mode]['AllWarmUpDuration'];

		// add chattime info in match_config if not already set
		if(!isset($_match_config[$match_mode]['ChatTime']))
			$_match_config[$match_mode]['ChatTime'] = $_NextGameInfos['ChatTime'];

		$match_gamemode = $_match_config[$match_mode]['GameMode'];
		$match_fgamemode = isset($_match_config[$match_mode]['FGameMode']) ? $_match_config[$match_mode]['FGameMode'] : '';
		$fteams = isset($_FGameModes[$match_fgamemode]['FTeams']) ? $_FGameModes[$match_fgamemode]['FTeams'] : false;

		if($_NextGameInfos['GameMode'] != 4 && $match_gamemode == 4){
			if(!goStunts()){ // need special trick to change to Stunts mode
				// can't change to Stunts
				$msg = localeText(null,'server_message').localeText(null,'interact')."Sorry, can't change to Stunts mode, you need to restart the server in Stunts mode !";
				console("matchStart::bad config: ".stripColors($msg));
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;
			}

		}elseif($_NextGameInfos['GameMode'] == 4 && $match_gamemode != 4){
			if(!goNotStunts()){ // need special trick to change from Stunts mode
				// can't change from Stunts
				$msg = localeText(null,'server_message').localeText(null,'interact')."Sorry, can't change from Stunts mode, you need to restart the server in other mode !";
				console("matchStart::bad config: ".stripColors($msg));
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;
			}

		}elseif($_NextGameInfos['GameMode'] == 3 && $match_gamemode != 3){
			// currently Laps mode and have to go to other : need to change the game mode before loading maps (dedicated "feature")
			addCall(true,'SetGameMode',$match_gamemode);
			if($_StatusCode < 5){
				mapQuickRestartNP();
				//addCall(true,'SetChatTime',0);
				//addCallDelay(10,$login,'ChallengeRestart');
				//addCallDelay(2000,true,$_NextGameInfos['ChatTime']);
			}
			// set $_match_startit to have matchStart() called after restart
			$_match_startit = array($login, $params);
			$msg = localeText(null,'server_message').localeText(null,'interact')."Need a first restart to change game mode...";
			console("matchStart::".stripColors($msg));
			addCall(null,'ChatSendToLogin', $msg, $login);
			return;
		}

		// -------- set match mode and state --------
		$_match_mode = $match_mode;
		console("matchStart:: set mode to {$_match_mode} !");
		$_match_conf = $_match_config[$_match_mode];
		$_match_conf['EndMatch'] = false; // used to stop the match at EndRace
		$_match_conf['Finished'] = 0;

		$_match_rounds = 0;
		$_match_starttime = $_lastEverysecond;

		if(!isset($_match_conf['Maps']) || !is_array($_match_conf['Maps']))
			$_match_conf['Maps'] = array();
		if(!isset($_match_conf['RoundsRoundsLimit']) || $_match_conf['RoundsRoundsLimit'] < 1)
			$_match_conf['RoundsRoundsLimit'] = -1;
		if(!isset($_match_conf['TeamRoundsLimit']) || $_match_conf['TeamRoundsLimit'] < 1)
			$_match_conf['TeamRoundsLimit'] = -1;
		if(!isset($_match_conf['ReportedScore']) || $_match_conf['ReportedScore'] !== true)
			$_match_conf['ReportedScore'] = false;
		if(!isset($_match_conf['GlobalScore']) || $_match_conf['GlobalScore'] !== false)
			$_match_conf['GlobalScore'] = true;
		if(!isset($_match_conf['ShowScore']) || $_match_conf['ShowScore'] !== true)
			$_match_conf['ShowScore'] = false;
		if(!isset($_match_conf['ShowNextMaps']) || $_match_conf['ShowNextMaps'] < 1)
			$_match_conf['ShowNextMaps'] = 0;
		if(!isset($_match_conf['MapFalseStart']) || $_match_conf['MapFalseStart'] < 1)
			$_match_conf['MapFalseStart'] = 0;
		if(!isset($_match_conf['MatchFalseStart']) || $_match_conf['MatchFalseStart'] < 1 ||
			 $_match_conf['MatchFalseStart'] < $_match_conf['MapFalseStart'])
			$_match_conf['MatchFalseStart'] = $_match_conf['MapFalseStart'];
		if(!isset($_match_conf['BreakTime']) || $_match_conf['BreakTime'] < 1)
			$_match_conf['BreakTime'] = 180;
		if(!isset($_match_conf['FWarmUpDuration']) || $_match_conf['FWarmUpDuration'] <= 0)
			$_match_conf['FWarmUpDuration'] = 0;
		if(!isset($_match_conf['MapRestarts']) || $_match_conf['MapRestarts'] <= 0)
			$_match_conf['MapRestarts'] = 0;
		if(!isset($_match_conf['MapRestartsWu']) || $_match_conf['MapRestartsWu'] <= 0)
			$_match_conf['MapRestartsWu'] = 0;

		// MapRestarts
		if($_match_conf['MapRestarts'] > 0 && $match_gamemode != 1 && $match_gamemode != 4 && $match_gamemode != 3){
			console("matchStart:: MapRestarts only in TA, Stunts and Laps, disable it !");
			$_match_conf['MapRestarts'] = 0;
		}

		// MapRestartsWu
		if($_match_conf['MapRestartsWu'] > 0 && $_match_conf['MapRestarts'] <= 0){
			$_match_conf['MapRestartsWu'] = 0;
		}

		// MapRSscoremode
		if(!isset($_match_conf['MapRSscoremode']) || 
			 ($_match_conf['MapRSscoremode'] != 'addscores' && $_match_conf['MapRSscoremode'] != 'bestscore' && 
				$_match_conf['MapRSscoremode'] != 'addtimes' && $_match_conf['MapRSscoremode'] != 'besttime' && 
				$_match_conf['MapRSscoremode'] != 'addpoints' && $_match_conf['MapRSscoremode'] != 'bestpoints'))
			$_match_conf['MapRSscoremode'] = 'addscores';

		if($_match_conf['ChatTime'] <= 0)
			$_match_conf['ChatTime'] = 1000;

		// check MapScoresMode
		if(!isset($_match_conf['MapScoresMode']) ||
			 ($_match_conf['MapScoresMode'] != 'Scores' && $_match_conf['MapScoresMode'] != 'Times' && $_match_conf['MapScoresMode'] != 'Checkpoints')){
			// default value
			if($match_gamemode == 0 || $match_gamemode == 5 || $match_gamemode == 2 || $match_gamemode == 4 || $fteams)
				$_match_conf['MapScoresMode'] = 'Scores';
			else
				$_match_conf['MapScoresMode'] = 'Times';

			console("matchStart:: bMapScoresMode={$_match_conf['MapScoresMode']} ({$match_gamemode},{$match_fgamemode},{$fteams}), set right value in config !");
		}

		if(isset($_match_conf['MapScoresList'])){
			if(!is_array($_match_conf['MapScoresList']))
				$_match_conf['MapScoresList'] = explode(',',$_match_conf['MapScoresList']);
			if(!isset($_match_conf['MapScoresList'][0])){
				unset($_match_conf['MapScoresList']);
				console("matchStart:: bad MapScoresList value !");
			}
		}

		// ReportedScore, GlobalScore, ShowScore
		if($_match_conf['ReportedScore']){
			$_match_conf['GlobalScore'] = false;
			$_match_conf['ShowScore'] = false;
			// only with limited number of rounds, in Rounds or Team
			if(!($match_gamemode == 0 && $_match_conf['RoundsRoundsLimit'] > 0) &&
				 !($match_gamemode == 2 && $_match_conf['TeamRoundsLimit'] > 0)){
				$_match_conf['ReportedScore'] = false;
				$_match_conf['GlobalScore'] = true;
				$_match_conf['ShowScore'] = true;
				$msg = localeText(null,'server_message').localeText(null,'interact')."Sorry, ReportedScore is only for Rounds/Team modes with RoundsRoundsLimit/TeamRoundsLimit !";
				console("matchStart::bad config: ".stripColors($msg));
				addCall(null,'ChatSendToLogin', $msg, $login);
				//return;
			}
		}
		if(isset($params[1]) && $params[1] != ''){
			$_match_conf['MapsMode'] = array();
			$list = explode(',',$params[1]);
			foreach($list as $ident){
				$_match_conf['MapsMode'][] = array('Ident'=>$ident);
			}
		}else{
			$_match_conf['MapsMode'] = $_match_conf['Maps'];
		}

		// verify if EndMatchCondition is needed and supported by dedicated
		if($_match_conf['GameMode'] == 5){
			$_match_conf['NumberOfMaps'] = 'CupEnd';
			$_match_conf['LimitText'] = '';

			if(!isset($_methods_list['CheckEndMatchCondition'])){
				console("matchStart:: NumberOfMaps=CupEnd, but old dedicated release !");
				$msg = localeText(null,'server_message').localeText(null,'interact').'Cup match not supported : update your dedicated server release !';
				console("matchStart::bad config: ".stripColors($msg));
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;
			}

		}else{
			$_match_conf['NumberOfMaps'] += 0;
			$_match_conf['LimitText'] = " /{$_match_conf['NumberOfMaps']}";

			if($_match_conf['NumberOfMaps'] < 1){
				console("matchStart:: bad NumberOfMaps : {$_match_conf['NumberOfMaps']}");
				$msg = localeText(null,'server_message').localeText(null,'interact').'NumberOfMaps is bad !';
				console("matchStart::bad config: ".stripColors($msg));
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;
			}
		}

		if(!isset($_match_conf['Title']) || $_match_conf['Title'] == ''){
			$msg = localeText(null,'server_message').localeText(null,'interact').'Missing Title in match config !';
				console("matchStart::bad config: ".stripColors($msg));
			addCall(null,'ChatSendToLogin', $msg, $login);
			return;
		}

		// match start: set short chattime
		addCall(null,'SetChatTime',100);

		// -------- start match --------
		$_match_map = 0;
		$_match_map_rs = 1;
		$_match_map_rswu = 0;
		$_match_scores_upd = false;
		$_match_scores = array();

		// show score panel
		matchShowScores(false);

		// hide times panel if here
		foreach($_players as $login2 => &$pl){
			if($pl['Active'] && isset($pl['ML']['Show.times']) && $pl['ML']['Show.times']){
				$_players[$login]['ML']['Show.times'] = false;
				ml_menusHideItem($login, 'menu.hud.times.menu');
				manialinksHide($login,'ml_times.1');
				manialinksHide($login,'ml_times.3');
				manialinksHide($login,'ml_times.F');
				manialinksHide($login,'ml_times.F2');
			}
		}

		console("matchStart:: start {$_match_conf['Title']} match !");
		if(isset($_match_conf['MatchSettings']) && $_match_conf['MatchSettings'] != ''){
			$action = array('CB'=>array('matchServerSetup',array($login,true)),'Login'=>$login);
			addCall($action,'LoadMatchSettings',$_match_conf['MatchSettings']);
		}else{
			matchServerSetup($login,true,$startdelay);
		}

		matchBuildLogoXml();
		matchUpdateLogoXml(true,'show');
		matchUpdateInfosXml(true,'show');
	}
}


//--------------------------------------------------------------
// End of match
function matchEnd($msg=null){
	global $_debug,$_ServerInfos,$_match_map,$_match_conf,$_match_scores,$_players_round_current,$_StatusCode;

	if(isset($_match_conf['Finished']) && $_match_conf['Finished'] > 0){
		// match already finished !
		console("matchEnd({$msg}):: already finished !...");
		return;
	}

	ml_mainFWarmUpHide();

	$_match_conf['EndMatch'] = true;

	// if not podium then next challenge with $_match_conf['EndMatch'] = true
	if($_StatusCode < 5){
		console("matchEnd({$msg}):: not podium, do nextchallenge !...");
		$_match_conf['EndMatch'] = true;
		addCall(true,'NextChallenge');

		if($msg !== null)
			addCall(null,'ChatSendServerMessage', localeText(null,'server_message').$msg);
		return;
	}

	// match end
	if($_debug>0) console("matchEnd({$msg})::(start)");

	// match done ?
	if($_match_map < 1 || ($_match_map == 1 && $_players_round_current <= 1)){
		console("matchEnd::(stop) match_map: {$_match_map} , {$_players_round_current}");
		matchStop();
		return;
	}
	// verify scores
	$maxscore = 0;
	foreach($_match_scores as &$pls){
		if($maxscore < $pls['FullScore']+$pls['Bonus']+$pls['MapScore'])
			$maxscore = $pls['FullScore']+$pls['Bonus']+$pls['MapScore'];
	}
	// if all scores are 0 then stop
	if($maxscore <= 0){
		if($_match_conf['EndMatch']){
			// except if match ended because of time or min rounds limit
			console("matchEnd::(EndMatch) maxscore: {$maxscore} -> EndMatch");
			$maxscore = 'EndMatch';

		}else{
			console("matchEnd::(stop) maxscore: {$maxscore} , {$_match_conf['EndMatch']}");
			matchStop();
			return;
		}
	}

	// set end of match
	$_match_conf['Finished'] = 2;
	$_match_conf['EndMatch'] = false; // used to stop the match at EndRace

	if(isset($_match_conf['ReplayName']) && $_match_conf['ReplayName'] != ''){
		// save replay
		addCall(null,'SaveCurrentReplay',$_match_conf['ReplayName']);
	}

	// call finished callback
	if(isset($_match_conf['EndCB']) && $_match_conf['EndCB'] != '' && function_exists($_match_conf['EndCB'])){
		// $_match_conf['EndCB'](maps,rounds,maxscore,scores)
		console("matchEnd::(callcb) {$_match_conf['EndCB']},{$_match_map},{$_players_round_current},{$maxscore}");
		if($_debug>1) debugPrint("matchEnd::(callcb) _match_scores ",$_match_scores);
		call_user_func($_match_conf['EndCB'],$_match_map,$_players_round_current,$maxscore,$_match_scores);

	}else{
		console("matchEnd::(nocb) {$_match_map},{$_players_round_current},{$maxscore}");
		if($_debug>1) debugPrint("matchEnd::(nocb) _match_scores ",$_match_scores);
	}

	// send event EndMatch($event,$match_map,$players_round_current,$maxscore,$match_scores,$match_config): called at end of match
	insertEvent('EndMatch',$_match_map,$_players_round_current,$maxscore,$_match_scores,$_match_conf);

	// hide logos & infos
	matchUpdateLogoXml(true,'hide');
	matchUpdateInfosXml(true,'hide');

	// restore server state
	matchServerRestore();

	matchScoreManialink();

	matchBuildStateXml(true);

	if($msg === null){
		console("matchEnd({$msg})::(Finished!!!)");
		$msg = localeText(null,'interact')."\$f00\$o Match finished !!!";
	}
	matchStop($msg);
	if($_debug>0) console("matchEnd({$msg})::(end)");
}


//--------------------------------------------------------------
// real stop match states
function matchStop($msg=null){
	global $_debug,$_match_conf,$_ServerInfos,$_match_map,$_match_map_rswu,$_match_map_rs,$_players_round_current,$_match_OldGameInfos,$_NextGameInfos,$_StatusCode;
	console("matchStop($msg)::");

	matchUpdateLogoXml(true,'hide');
	matchUpdateInfosXml(true,'hide');

	// restore server state if not done
	if($_match_conf['Finished'] <= 1){
		matchServerRestore();

		// call finished callback
		if(isset($_match_conf['EndCB']) && $_match_conf['EndCB'] != '' && function_exists($_match_conf['EndCB'])){
			// $_match_conf['EndCB'](maps,rounds,maxscore,scores)
			call_user_func($_match_conf['EndCB'],$_match_map,$_players_round_current,0,null);
		}
	}
	$_match_conf['Finished'] = 2;
	$_match_conf['EndMatch'] = false; // used to stop the match at EndRace

	// disable match state
	$_match_map = -2;
	$_match_map_rs = 1;
	$_match_map_rswu = 0;
	$_match_scores = array();

	matchScoreManialink();

	if($msg !== null)
		addCall(null,'ChatSendServerMessage', localeText(null,'server_message').$msg);

	matchBuildStateXml(true);

	if($_StatusCode < 5){
		if($_debug>0) console("matchStop:: not podium, do short maprestart !...");
		// short restart
		addCall(true,'SetChatTime',1000);
		addCallDelay(10,true,'ChallengeRestart');
		if(isset($_match_OldGameInfos['ChatTime']))
			addCallDelay(2000,true,$_match_OldGameInfos['ChatTime']);
		else
			addCallDelay(2000,true,$_NextGameInfos['ChatTime']);
	}
}


//--------------------------------------------------------------
// chat command : /match
//--------------------------------------------------------------
function chat_match($author, $login, $params1, $params){
	global $_debug,$_match_conf,$_match_map,$_match_scores,$_is_relay,$_NextGameInfos,$_GameInfos,$_ChallengeList,$_players,$_mapinfo_default,$_scorepanel_hide,$_MapFalsestart;
	//if($_debug>0) console("chat_match($author, $login)");

	// verify if author is in admin list and not a relay server
	if($_is_relay)
		return;
	
	// match start
	if(isset($params[0]) && ($params[0] == 'start' || $params[0] == 'on')){
		if(!verifyAdmin($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').'need admin permissions !';
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			array_shift($params);
			matchStart($login,$params);
		}

		// match stop/off
	}elseif(isset($params[0]) && ($params[0] == 'stop' || $params[0] == 'off')){
		if(!verifyAdmin($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').'need admin permissions !';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			matchStop($login.localeText(null,'interact')." (admin) stopped the match !");

			//$msg = localeText(null,'server_message').localeText(null,'interact').'need a next or restart to have restored parameters !';
			//addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// match end/finish
	}elseif(isset($params[0]) && ($params[0] == 'end' || $params[0] == 'finish')){
		if(!verifyAdmin($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').'need admin permissions !';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$_match_conf['EndMatch'] = true;
			matchEnd($login.localeText(null,'interact')." (admin) finished the match !");
		}

		// set special match name
	}elseif(isset($params[0]) && ($params[0] == 'name')){
		if(!verifyAdmin($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').'need admin permissions !';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif(!isset($_match_conf['SrvName'])){
			$msg = localeText(null,'server_message').localeText(null,'interact').'option not supported with this match config !';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif(!isset($params[1]) && strpos($_match_conf['SrvName'],'%') !== false){
			$msg = localeText(null,'server_message').localeText(null,'interact')."need a parameter for '{$_match_conf['SrvName']}'";
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			if(isset($params[1]))
				$name = sprintf($_match_conf['SrvName'],$params[1]);
			else
				$name = $_match_conf['SrvName'];
			addCall($login,'SetServerName',$name);

			$msg = localeText(null,'server_message').localeText(null,'interact')."server name set to: '{$name}'";
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// set number of maps in match
	}elseif(isset($params[0]) && ($params[0] == 'nbmaps')){
		if(!verifyAdmin($login) || $_match_map < 0){
			$msg = localeText(null,'server_message').localeText(null,'interact').'need admin permissions and match started !';
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			if(isset($params[1]) && is_numeric($params[1]) && ($params[1]+0) > 0){
				$_match_conf['NumberOfMaps'] = $params[1]+0;
			}elseif($params[1] == 'CupEnd' && $_match_conf['NumberOfMaps'] == 5){
				$_match_conf['NumberOfMaps'] = 'CupEnd';
			}
			$msg = localeText(null,'server_message').localeText(null,'interact').'number of maps in match: '.$_match_conf['NumberOfMaps'];
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
		
		// set warmup value during match
	}elseif(isset($params[0]) && ($params[0] == 'warmup' || $params[0] == 'wu')){
		if(!verifyAdmin($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').'need admin permissions !';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			if(isset($params[1]) && $params[1]+0 >= 0){
				$wu = $params[1]+0;
				$_match_conf['AllWarmUpDuration'] = $wu;
				$_match_conf['CupWarmUpDuration'] = $wu;
				if(isset($_match_conf['FWarmUpDuration']) && $_match_conf['FWarmUpDuration'] > 0)
					$_match_conf['FWarmUpDuration'] = $wu;
				$msg = localeText(null,'server_message').localeText(null,'interact')."WarmUpDuration for next match map set to: {$wu}";

			}else{
				$wu = 0;
				if(isset($_match_conf['FWarmUpDuration']) && $_match_conf['FWarmUpDuration'] > 0)
					$wu = $_match_conf['FWarmUpDuration'];
				else if(isset($_match_conf['CupWarmUpDuration']) && $_match_conf['CupWarmUpDuration'] > 0)
					$wu = $_match_conf['CupWarmUpDuration'];
				else if(isset($_match_conf['AllWarmUpDuration']) && $_match_conf['AllWarmUpDuration'] > 0)
					$wu = $_match_conf['AllWarmUpDuration'];
				$msg = localeText(null,'server_message').localeText(null,'interact')."Current WarmUpDuration is: {$wu}";
			}

			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// set falsestart value during match
	}elseif(isset($params[0]) && ($params[0] == 'fs' || $params[0] == 'falsestart')){
		if(!verifyAdmin($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').'need admin permissions !';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			if(isset($params[1]) && $params[1]+0 >= 0){
				$fs = $params[1]+0;
				$_match_conf['MapFalseStart'] = $fs;
				$_MapFalsestart = $fs;
				$msg = localeText(null,'server_message').localeText(null,'interact')."Max FalseStart set to: {$fs}";

			}else{
				$msg = localeText(null,'server_message').localeText(null,'interact')."Current max FalseStart per player per map is: {$_MapFalsestart}";
			}

			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// add special bonus
	}elseif(isset($params[0]) && isset($params[1]) &&
					( (($params[0] == 'bonus') && isset($params[2])) ||
						(($params[0] == 'nobonus') && !isset($params[2])))){
		if(!verifyAdmin($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').'need admin permissions !';
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}elseif($_match_map < 0){
			$msg = localeText(null,'server_message').localeText(null,'interact')."match must be started before !";
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			if($params[0] == 'nobonus')
				$params[2] = 0;
			else
				$params[2] = $params[2]+0;
			
			$name = $params[1];
			$nlen = strlen($name);
			$found = false;
			if(isset($_match_scores[$name]['Login'])){
				$found = true;
				matchSetBonus($login, $name, $params[2]);
			}elseif(isset($_players[$name]['Active']) && $_players[$name]['Active']){
				$found = true;
				matchAddPlayer($_players[$name]['Login'],$_players[$name]['PlayerId'],$_players[$name]['NickName']);
				matchSetBonus($login, $name, $params[2]);
			}else{
				foreach($_match_scores as &$pls){
					if(strncmp($pls['Login'],$name,$nlen) == 0 || $pls['PlayerId'] == $name){
						$found = true;
						matchSetBonus($login, $pls['Login'], $params[2]);
						break;
					}
				}
			}
			if(!$found && $params[2] > 0){
				foreach($_players as &$pl){
					if($pl['Active'] && (strncmp($pl['Login'],$name,$nlen) == 0 || $pl['PlayerId'] == $name)){
						$found = true;
						matchAddPlayer($pl['Login'],$pl['PlayerId'],$pl['NickName']);
						matchSetBonus($login, $pl['Login'], $params[2]);
						break;
					}
				}
			}
			if(!$found){
				$msg = localeText(null,'server_message').localeText(null,'interact')."player '$name' not found !";
				addCall(null,'ChatSendToLogin', $msg, $login);
			}
		}

		// set score
	}elseif(isset($params[0]) && $params[0] == 'score' && isset($params[1]) && isset($params[2])){
		if(!verifyAdmin($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').'need admin permissions !';
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}elseif($_match_map < 1){
			$msg = localeText(null,'server_message').localeText(null,'interact')."match must be started before !";
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$params[2] = $params[2]+0;
			
			$name = $params[1];
			$nlen = strlen($name);
			$found = false;
			if(isset($_match_scores[$name]['Login'])){
				$found = true;
				matchSetScore($login, $name, $params[2]);
			}elseif(isset($_players[$name]['Active']) && $_players[$name]['Active']){
				$found = true;
				matchAddPlayer($_players[$name]['Login'],$_players[$name]['PlayerId'],$_players[$name]['NickName']);
				matchSetScore($login, $name, $params[2]);
			}else{
				foreach($_match_scores as &$pls){
					if(strncmp($pls['Login'],$name,$nlen) == 0 || $pls['PlayerId'] == $name){
						$found = true;
						matchSetScore($login, $pls['Login'], $params[2]);
						break;
					}
				}
			}
			if(!$found && $params[2] > 0){
				foreach($_players as &$pl){
					if($pl['Active'] && (strncmp($pl['Login'],$name,$nlen) == 0 || $pl['PlayerId'] == $name)){
						$found = true;
						matchAddPlayer($pl['Login'],$pl['PlayerId'],$pl['NickName']);
						matchSetScore($login, $pl['Login'], $params[2]);
						break;
					}
				}
			}
			if(!$found){
				$msg = localeText(null,'server_message').localeText(null,'interact')."player '$name' not found !";
				addCall(null,'ChatSendToLogin', $msg, $login);
			}
		}
		
		// set special display for big screen
	}elseif(isset($params[0]) && ($params[0] == 'screen')){
		if(isset($params[1]) && $params[1] == 'on'){
			$_players[$login]['screen'] = true;
			$_players[$login]['ML']['Show.mapinfo'] = 2;
			
			if(!isset($_match_conf['NumberOfMaps']) || $_match_conf['NumberOfMaps'] == 'CupEnd'){
				$_players[$login]['ML']['Hide.scorepanel'] = 0;
			}else{
				$_players[$login]['ML']['Hide.scorepanel'] = 2;
			}
			addEvent('PlayerShowML',$login,$_players[$login]['ML']['ShowML']);
			
		}elseif(isset($params[1]) && $params[1] == 'off'){
			$_players[$login]['screen'] = false;
			$_players[$login]['ML']['Show.mapinfo'] = $_mapinfo_default;
			$_players[$login]['ML']['Hide.scorepanel'] = $_scorepanel_hide;
			addEvent('PlayerShowML',$login,$_players[$login]['ML']['ShowML']);
			
		}else{
			$msg = localeText(null,'server_message').localeText(null,'interact').'/match screen on|off : set special hud display for big screen';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// help
	}else{
		$msg = localeText(null,'server_message') . localeText(null,'interact').'/match start [matchmode], stop, end, name <#>, nbmaps <num>|CupEnd, bonus <login> <value>, nobonus <login>, score <login> <value>, screen';
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}



//--------------------------------------------------------------
function matchReadConfigs($matchconfigfile){
	global $_debug,$_match_config,$_FGameModes;

	if(!file_exists($matchconfigfile))
		return;

	$datas = xml_parse_file($matchconfigfile);
	if(isset($datas['fast']['matchconfig']['.multi_same_tag.']))
		unset($datas['fast']['matchconfig']['.multi_same_tag.']);
	else
		$datas['fast']['matchconfig'] = array($datas['fast']['matchconfig']);
	if($_debug>6) debugPrint("matchReadConfigs($matchconfigfile):: ",$datas);

	foreach($datas['fast']['matchconfig'] as $matchconfig){
		$mconfig = matchConvConfig($matchconfig);

		$fgamemode = isset($mconfig['FGameMode']) ? $mconfig['FGameMode'] : '';
		$gmode = isset($_FGameModes[$fgamemode]['GameInfos']['GameMode']) ? $_FGameModes[$fgamemode]['GameInfos']['GameMode'] : -1;
		if(!isset($mconfig['GameMode']))
			$mconfig['GameMode'] = $gmode;
		$mconfig['FGameMode'] = $fgamemode;

		if(isset($mconfig['MapScoresList'])){
			if(!is_array($mconfig['MapScoresList']))
				$mconfig['MapScoresList'] = explode(',',$mconfig['MapScoresList']);
			if(!isset($mconfig['MapScoresList'][0]))
				unset($mconfig['MapScoresList']);
		}

		if(isset($mconfig['Ident']) && $mconfig['Ident'] != '' &&
			 $mconfig['GameMode'] >= 0 && $mconfig['GameMode'] <= 5 &&
			 ($gmode < 0 || $mconfig['GameMode'] == $gmode)){

			$ident = $mconfig['Ident'];
			// store match config '$ident'
			$_match_config[$ident] = $mconfig;

			if($_debug>5) debugPrint("matchReadConfigs:: _match_config[$ident]",$_match_config[$ident]);
		}else{
			debugPrint("matchReadConfigs:: BAD match_config",$mconfig);
		}
	}
}


//--------------------------------------------------------------
function matchConvConfig($matchconfig){
	global $_debug,$_ConvMatchConfig,$_ConvServerOptions,$_SetServerOptions,$_ConvGameInfos,$_ConvFTeamInfos,$_FGameModes,$_SetGameInfos;

	// need to check first an eventual 'fgamemode' value, to be able to test specific tags
	$fgamemode = isset($matchconfig['fgamemode']) ? $matchconfig['fgamemode'].'' : '';

	$config = array();
	foreach($matchconfig as $conf => $val){
		if($val === 'true')
			$val = true;
		else if($val === 'false')
			$val = false;
		else if(is_numeric($val))
			$val += 0;
		else if(is_string($val))
			$val = trim($val);

		if(isset($_FGameModes[$fgamemode]['XmlConv']) && isset($_FGameModes[$fgamemode]['XmlConv'][$conf])){
			$config[$_FGameModes[$fgamemode]['XmlConv'][$conf]] = $val;

		}elseif(isset($_ConvFTeamInfos[$conf])){
			$config[$_ConvFTeamInfos[$conf]] = $val;

		}elseif(isset($_ConvServerOptions[$conf])){
			if(isset($_SetServerOptions[$_ConvServerOptions[$conf]]))
				settype($val,gettype($_SetServerOptions[$_ConvServerOptions[$conf]]));
			elseif(isset($_SetServerOptions['Next'.$_ConvServerOptions[$conf]]))
				settype($val,gettype($_SetServerOptions['Next'.$_ConvServerOptions[$conf]]));
			$config[$_ConvServerOptions[$conf]] = $val;

		}elseif(isset($_ConvGameInfos[$conf]) && isset($_SetGameInfos[$_ConvGameInfos[$conf]])){
			settype($val,gettype($_SetGameInfos[$_ConvGameInfos[$conf]]));
			$config[$_ConvGameInfos[$conf]] = $val;

		}elseif(isset($_ConvMatchConfig[$conf])){
			if($val === '')
				continue;

			// special case: make these always an array
			if($conf == 'break_before_map' || $conf == 'map'){
				// handle multi same tags case as an array
				if(!is_array($val) || !isset($val['.multi_same_tag.']))
					$val = array($val);
				else
					unset($val['.multi_same_tag.']);
				
				// special case: maps description/order/specific config
				if($conf == 'map'){
					foreach($val as $map => $mapconf){
						if(is_array($mapconf))
							$val[$map] = matchConvConfig($mapconf);
						else
							$val[$map] = array('Ident'=>$mapconf);
					}
				}
			}elseif(is_array($val)){
				$val = matchConvConfig($val);
			}
			$config[$_ConvMatchConfig[$conf]] = $val;

		}else{
			if($_debug>0) console("matchConvConfig:: UNKNOWN TAG in '{$matchconfig['ident']}' : <{$conf}>");
		}
	}
	if($_debug>3) console("matchConvConfig:: config ".print_r($config,true));
	return $config;
}


//--------------------------------------------------------------
function matchConvSetServerOptions(&$config){
	global $_debug,$_SetServerOptions;

	foreach($config as $key => $val){
		$nkey = 'Next'.$key;
		if(array_key_exists($nkey,$_SetServerOptions) && !array_key_exists($nkey,$config))
			$config[$nkey] = $val;
	}
}


//--------------------------------------------------------------
// DB store :
//--------------------------------------------------------------
function matchDbStore($Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_DB,$_match_db_table,$_match_conf,$_players,$_SystemInfo,$_ServerOptions,$_FGameMode;
	if($_DB === false)
		return;

	$time = time();
	$match = isset($_match_conf['DbTitle']) ? mysql_real_escape_string($_match_conf['DbTitle']) : mysql_real_escape_string($_match_conf['Title']);
	$cuid = mysql_real_escape_string($ChallengeInfo['UId']);
	$cname = mysql_real_escape_string(tm_substr($ChallengeInfo['Name']));
	$cenv = mysql_real_escape_string($ChallengeInfo['Environnement']);
	$srvlogin = mysql_real_escape_string(tm_substr($_SystemInfo['ServerLogin']));
	$svrname = mysql_real_escape_string(tm_substr($_ServerOptions['Name']));
	
	foreach($Ranking as &$prk){
		$login = $prk['Login'];
		$query = sprintf("INSERT INTO `%s` (`id`,`name`,`map_uid`,`map_name`,`environment`,`mode`,`fmode`,`login`,`nickname`,`date`,`time`,`score`,`cp`,`points`,`rank`,`srvlogin`,`srvname`)"
										 ."VALUES (NULL,'%s','%s','%s','%s','%d','%s','%s','%s',FROM_UNIXTIME(%d),'%d','%d','%d','-1','-1','%s','%s');",
										 $_match_db_table,$match,$cuid,$cname,$cenv,$GameInfos['GameMode'],$_FGameMode,
										 mysql_real_escape_string(tm_substr($prk['Login'])),mysql_real_escape_string(tm_substr($prk['NickName'])),
										 $time,$prk['BestTime'],$prk['Score'],count($prk['BestCheckpoints']),
										 $srvlogin,$svrname);
		$result = @dbmysql_query($query);
	}
}


//--------------------------------------------------------------
// DB Init :
//--------------------------------------------------------------
function matchDbInit(){
	global $_debug,$_DB,$_match_db_table;
	if($_DB === false)
		return;

	if($_debug>2) console("matchDbInit");

	if(!isset($_match_db_table) || $_match_db_table == '')
		$_match_db_table = 'tmmatch';

	// ALTER TABLE `tmmatch` ADD `fmode` VARCHAR( 30 ) NOT NULL DEFAULT '' AFTER `mode` ;

	// create table if not exist !
	$query = "CREATE TABLE IF NOT EXISTS `tmmatch` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(60) NOT NULL,
  `map_uid` varchar(27) NOT NULL,
  `map_name` varchar(60) NOT NULL,
  `environment` varchar(15) NOT NULL,
  `mode` tinyint(4) NOT NULL,
  `fmode` varchar(30) NOT NULL DEFAULT '',
  `login` varchar(70) NOT NULL,
  `nickname` varchar(70) NOT NULL,
  `date` datetime NOT NULL,
  `time` int(11) NOT NULL default '-1',
  `score` int(11) NOT NULL default '-1',
  `cp` int(11) NOT NULL default '-1',
  `points` int(11) NOT NULL default '-1',
  `rank` int(11) NOT NULL default '-1',
  `srvlogin` varchar(60) NOT NULL,
  `srvname` varchar(60) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `match` (`name`),
  KEY `map_uid` (`map_uid`),
  KEY `mode` (`mode`),
  KEY `fmode` (`fmode`),
  KEY `login` (`login`),
  KEY `time` (`time`),
  KEY `cp` (`cp`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
	$result = @dbmysql_query($query);
}


//------------------------------------------
// if ctype_alnum does not exist then create a limited one for one char only
if(!function_exists('ctype_alnum')){
	function ctype_alnum($c){
		$n = ord($c);
		if($n >= ord('0') && $n <= ord('9'))
			return true;
		if($n >= ord('A') && $n <= ord('Z'))
			return true;
		if($n >= ord('a') && $n <= ord('z'))
			return true;
		return false;
	}
}




?>
