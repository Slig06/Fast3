<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      12.04.2023
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
// needed plugins: manialinks,ml_menus
//
// plugin to show spectator infos
// 
// 
//
// $_spec_players_default = true;
// $_players[$login]['ML']['Show.specplayers']
//
// $_spec_lapinfo_default = true;
// $_players[$login]['ML']['Show.lapinfo']

registerPlugin('ml_specinfos',21,1.0);


//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function ml_specinfosInit($event){
	global $_mldebug,$_spec_players_default,$_spec_players_bg_xml,$_spec_lapinfo_default,$_spec_lapinfo_bg_xml,$_spec_lapinfo_xml,$_ml_specinfos_updatespecplayers;
	if($_mldebug>4) console("ml_specinfos.Event[$event]");

	if(!isset($_spec_players_default))
		$_spec_players_default = true;

	if(!isset($_spec_lapinfo_default))
		$_spec_lapinfo_default = true;

	$_spec_players_bg_xml = 
		'<quad sizen="30 %0.2F" posn="-68.2 %0.2F -40" style="BgsPlayerCard" substyle="BgPlayerCardBig"/>'; // "BgPlayerName" , "BgCard" , "BgPlayerCardBig"
	//'<quad sizen="30 31.6" posn="-68.2 8.1 -40" style="BgsPlayerCard" substyle="BgPlayerCardBig"/>'; // "BgPlayerName" , "BgCard" , "BgPlayerCardBig"

	$_spec_lapinfo_bg_xml =
		'<quad sizen="15 4.5" posn="52 32.2 -40" style="BgsPlayerCard" substyle="BgPlayerCardBig"/>'; // "BgPlayerName" , "BgCard" , "BgPlayerCardBig"
	$_spec_lapinfo_xml = '';
	$_ml_specinfos_updatespecplayers = false;
}


function ml_specinfosPlayerConnect($event,$login){
	global $_mldebug,$_players,$_spec_players_default,$_spec_lapinfo_default;
  if(!is_string($login))
    $login = ''.$login;
	//console("ml_team.Event[$event]('$login')");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if(!isset($_players[$login]['ML']['Show.specplayers']))
		$_players[$login]['ML']['Show.specplayers'] = $_spec_players_default;
	if($_players[$login]['ML']['Show.specplayers'] && $_players[$login]['Status2']==1)
		ml_specinfosUpdateSpecPlayersXml($login,'show');

	if(!isset($_players[$login]['ML']['Show.lapinfo']))
		$_players[$login]['ML']['Show.lapinfo'] = $_spec_lapinfo_default;
	if($_players[$login]['ML']['Show.lapinfo'] && $_players[$login]['Status2']==1)
		ml_specinfosUpdateLapInfoXml($login,'show');
}


function ml_specinfosPlayerMenuBuild($event,$login){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	
	ml_menusAddItem($login, 'menu.hud', 'menu.hud.specplayers', 
									array('Name'=>array(localeText($login,'menu.hud.specplayers.on'),
																			localeText($login,'menu.hud.specplayers.off')),
												'Type'=>'bool',
												'State'=>$_players[$login]['ML']['Show.specplayers']));
	
	ml_menusAddItem($login, 'menu.hud', 'menu.hud.lapinfo', 
									array('Name'=>array(localeText($login,'menu.hud.lapinfo.on'),
																			localeText($login,'menu.hud.lapinfo.off')),
												'Type'=>'bool',
												'State'=>$_players[$login]['ML']['Show.lapinfo']));
}


function ml_specinfosPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_players,$_StatusCode;
	//if($_mldebug>6) console("ml_specinfos.Event[$event]('$login',$action,$state)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	
	$msg = localeText(null,'server_message').localeText(null,'interact');
	if($action=='menu.hud.specplayers'){
		$_players[$login]['ML']['Show.specplayers'] = $state;
		if($state && $_players[$login]['ML']['ShowML'] & $_players[$login]['Status2']==1)
			ml_specinfosUpdateSpecPlayersXml($login,'show');
		else
			ml_specinfosUpdateSpecPlayersXml($login,'hide');
		if($state)
			$msg .= localeText($login,'chat.hud.specplayers.on');
		else
			$msg .= localeText($login,'chat.hud.specplayers.off');
		addCall(null,'ChatSendToLogin', $msg, $login);
			
	}elseif($action=='menu.hud.lapinfo'){
		$_players[$login]['ML']['Show.lapinfo'] = $state;
		if($state && $_players[$login]['ML']['ShowML'] && $_players[$login]['Status2']==1)
			ml_specinfosUpdateLapInfoXml($login,'show');
		else
			ml_specinfosUpdateLapInfoXml($login,'hide');
		if($state)
			$msg .= localeText($login,'chat.hud.lapinfo.on');
		else
			$msg .= localeText($login,'chat.hud.lapinfo.off');
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}


function ml_specinfosPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_players,$_StatusCode;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if($ShowML && $_players[$login]['Status2']==1){
		if($_players[$login]['ML']['Show.specplayers'])
			ml_specinfosUpdateSpecPlayersXml($login,'show');

	}else{
		ml_specinfosUpdateSpecPlayersXml($login,'hide');
	}
	if($ShowML && $_players[$login]['Status2']==1){
		if($_players[$login]['ML']['Show.lapinfo'])
			ml_specinfosUpdateLapInfoXml($login,'show');

	}else{
		ml_specinfosUpdateLapInfoXml($login,'hide');
	}
}


//--------------------------------------------------------------
// PlayerPositionChange :
//--------------------------------------------------------------
//   1=position changed  (in TA: best rank changed)
//   2=checkpoint changed
//   4=diff with previous player changed  (not in TA)
//   8=diff with next player changed  (not in TA)
//  16=diff with first player changed  (in TA: best diff)
function ml_specinfosPlayerPositionChange($event,$login,$changes){
	global $_debug,$_players,$_players_positions,$_ml_specinfos_updatespecplayers,$_lastEverysecond;
  //console("ml_specinfos.Event[$event]('$login',$changes)");
	if($login===true){
		// some change in positions
		$_ml_specinfos_updatespecplayers = true;
		// show immediatly in not many spectators
		ml_specinfosEverysecond($event,$_lastEverysecond);

	}elseif(isset($_players_positions[0]['Login']) && $_players_positions[0]['Login']==$login){
		$cp = $_players[$_players_positions[0]['Login']]['CheckpointNumber']+1;
		$lap = isset($_players[$_players_positions[0]['Login']]['LapNumber'])? $_players[$_players_positions[0]['Login']]['LapNumber']+1 : 0;
		ml_specinfosUpdateLapInfoXml(true,'refresh',$cp,$lap);
	}
}


function ml_specinfosEverysecond($event,$sec){
	global $_players_spec,$_ml_specinfos_updatespecplayers;
	
	// really draw specplayers only every x seconds depending of spectators number
	if($_ml_specinfos_updatespecplayers){
		$showsec = (int)ceil((1 + $_players_spec) / 50); // from 1 to 5 depending of spectators number
		if( ($sec % $showsec)==0 ){
			//console("specinfosEverysecond[$event]:: {$sec} % {$showsec} ($_players_spec)");
			ml_specinfosUpdateSpecPlayersXml(true,'refresh');
			$_ml_specinfos_updatespecplayers = false;
		}
	}
}


function ml_specinfosPlayerTeamChange($event,$login,$teamid){
	ml_specinfosUpdateSpecPlayersXml(true,'refresh');
}


// round logical $status2: 0=playing, 1=spec, 2=race finished
function ml_specinfosPlayerStatus2Change($event,$login,$status2,$oldstatus2){
	global $_debug,$_players;
	//console("ml_specinfos.Event[$event]('$login',$status2)");

	if($_players[$login]['ML']['ShowML']){
		if($status2==1){
			if($_players[$login]['ML']['Show.specplayers'])
				ml_specinfosUpdateSpecPlayersXml($login,'show');
			if($_players[$login]['ML']['Show.lapinfo'])
					ml_specinfosUpdateLapInfoXml($login,'show');

		}elseif($oldstatus2==1){
			if($_players[$login]['ML']['Show.specplayers'])
				ml_specinfosUpdateSpecPlayersXml($login,'hide');
			if($_players[$login]['ML']['Show.lapinfo'])
					ml_specinfosUpdateLapInfoXml($login,'hide');
		}
	}
}


function ml_specinfosEndRace($event){
	global $_mldebug,$_players;
	
	foreach($_players as $login => &$pl){
		if(!is_string($login))
			$login = ''.$login;
		if($_players[$login]['ML']['ShowML']){
			if($_players[$login]['ML']['Show.specplayers'])
				ml_specinfosUpdateSpecPlayersXml($login,'hide');
			if($_players[$login]['ML']['Show.lapinfo'])
				ml_specinfosUpdateLapInfoXml($login,'hide',0,0);
		}
	}
}


function ml_specinfosBeginRound($event){
	global $_mldebug,$_players;
	
	foreach($_players as $login => &$pl){
		if(!is_string($login))
			$login = ''.$login;
		if($_players[$login]['ML']['ShowML'] && $_players[$login]['Status2']==1){

			if($_players[$login]['ML']['Show.specplayers'])
				ml_specinfosUpdateSpecPlayersXml($login,'show');

			if($_players[$login]['ML']['Show.lapinfo'])
				ml_specinfosUpdateLapInfoXml($login,'show',0,0);
		}
	}
}


function ml_specinfosEndRound($event){
	global $_mldebug,$_players;

	ml_specinfosUpdateLapInfoXml(true,'refresh',0,0);
}





//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove'
//--------------------------------------------------------------
function ml_specinfosUpdateLapInfoXml($login,$action='show',$cp=-1,$lap=-1){
	global $_mldebug,$_players,$_StatusCode,$_GameInfos,$_BestChecks,$_spec_lapinfo_bg_xml,$_spec_lapinfo_xml,$_ChallengeInfo,$_LastCheckNum;

	if($_GameInfos['GameMode'] == TA || $_GameInfos['GameMode'] == STUNTS){
		$action = 'remove';
		$cp = -1;
	}
	
	// build xml
	if($cp >= 0){
		$msg = '';
		if($cp > 0)
			$msg .= '$ncp  '.$cp;
		if($_mldebug>1) console("ml_specinfosUpdateLapInfoXml:: $cp,$lap,$_LastCheckNum");
		if(($_GameInfos['GameMode'] == LAPS || $_ChallengeInfo['LapRace'] == true) && $lap > 1){
			if($_LastCheckNum < 0 || $cp <= $_LastCheckNum){
				$msg .= '$n / lap  '.$lap;
			}elseif($lap > 1){
				$msg .= '$n / '.($lap-1).' laps';
			}
		}
		$_spec_lapinfo_xml = sprintf('<label posn="63.4 31.2 -39" halign="right" text="%s"/>',$msg);
	}

	// to all users
	if($login === true){
		foreach($_players as $login => &$pl){
			if($pl['Status2'] == 1 && $pl['ML']['ShowML'])
				ml_specinfosUpdateLapInfoXml($login,'refresh');
		}
		return;
	}
	// if the players disabled manialinks then do nothing
	if(!$_players[$login]['ML']['ShowML'])
		return;

	if($action == 'remove'){
		// remove manialink
		manialinksRemove($login,'ml_specinfos.lapinfo');
		manialinksRemove($login,'ml_specinfos.lapinfo.bg');
		return;
	}elseif($action == 'hide'){
		// hide manialink
		manialinksHide($login,'ml_specinfos.lapinfo');
		manialinksHide($login,'ml_specinfos.lapinfo.bg');
		return;
	}
	// show/refresh
	if(!$_players[$login]['ML']['Show.lapinfo'] || $_players[$login]['Status2'] != 1){
		// none to show and opened : hide it
		if(manialinksIsOpened($login,'ml_specinfos.lapinfo.bg')){
			manialinksHide($login,'ml_specinfos.lapinfo');
			manialinksHide($login,'ml_specinfos.lapinfo.bg');
		}
		return;
	}

	// show
	if(!manialinksIsOpened($login,'ml_specinfos.lapinfo.bg')){
		// show bg
		manialinksShow($login,'ml_specinfos.lapinfo.bg',$_spec_lapinfo_bg_xml);
	}

	//console("ml_specinfosUpdateSpecPlayersXml - $login - $xml");
	manialinksShow($login,'ml_specinfos.lapinfo',$_spec_lapinfo_xml);
}




	
//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove'
//--------------------------------------------------------------
function ml_specinfosUpdateSpecPlayersXml($login,$action='show'){
	global $_mldebug,$_players,$_StatusCode,$_BestPlayersChecks,$_GameInfos,$_BestChecks,$_IdealChecks,$_BestChecksName,$_spec_players_bg_xml,$_players_positions,$_players_actives,$_players_spec,$_teams;
	if($login===true){
		foreach($_players as $login => &$pl){
			if(isset($pl['ML']) && $pl['Status2']==1 && $pl['ML']['ShowML'])
				ml_specinfosUpdateSpecPlayersXml($login,'refresh');
		}
		return;
	}
	// if the players disabled manialinks then do nothing
	if(!$_players[$login]['ML']['ShowML'])
		return;

	if($action=='remove'){
		// remove manialink
		manialinksRemove($login,'ml_specinfos.spec');
		manialinksRemove($login,'ml_specinfos.spec.bg');
		return;
	}elseif($action=='hide'){
		// hide manialink
		manialinksHide($login,'ml_specinfos.spec');
		manialinksHide($login,'ml_specinfos.spec.bg');
		return;
	}
	//console("ml_specinfosUpdateSpecPlayersXml({$login},{$action}):: {$_players[$login]['Status2']}");
	// show/refresh
	if(!$_players[$login]['ML']['Show.specplayers'] || $_players[$login]['Status2']!=1 ||
		 $_GameInfos['GameMode'] == TA || $_GameInfos['GameMode'] == STUNTS){
		// none to show and opened : hide it
		if(manialinksIsOpened($login,'ml_specinfos.spec.bg')){
			manialinksHide($login,'ml_specinfos.spec');
			manialinksHide($login,'ml_specinfos.spec.bg');
		}
		return;
	}

	// show
	$hline = 2.2;
	$lines = $_players_actives-$_players_spec;
	if($lines < 2)
		$lines = 2;
	if($lines > 22)
		$lines = 22;
	
	// show bg
	$h = $hline*$lines+1;
	if($_GameInfos['GameMode'] == TEAM) // 1 line more to show Team Score !
		$h += $hline;
	$xml = sprintf($_spec_players_bg_xml,$h,$h-23.6);
	manialinksShow($login,'ml_specinfos.spec.bg',$xml);

	// spec infos : show 1st player gap with top1
	if($_GameInfos['GameMode'] == LAPS && isset($_players[$login]['LapCheckpoints'])){
		$Checkpoints = 'LapCheckpoints';
		$BestCheckpoints = 'BestLapCheckpoints';
	}else{
		$Checkpoints = 'Checkpoints';
		$BestCheckpoints = 'BestCheckpoints';
	}
	$xml = '';
	$y = 0.0;
	$lmax = $lines - 5;
	$pmax = count($_players_positions);
	if($pmax>$lines)
		$pdiff = $pmax - $lines;
	else
		$pdiff = 0;

	$msg = '';
	for($line=0;$line<$lines;$line++){
		$pos = ($line < $lmax)? $line : $line+$pdiff;;
		$msg = '';
		$msg3 = '';

		if(isset($_players_positions[$pos])){
			$pl2 = &$_players_positions[$pos];
			$plogin = $pl2['Login'];

			// color
			if($_players[$pl2['Login']]['TeamId']==0){ // blue
				if($pl2['Login']==$login) // login
					$color = '$45e';
				elseif($pl2['FinalTime']>0) // finished
					$color = '$33e';
				elseif($pl2['FinalTime']==0) // gave up
					$color = '$338';
				else
					$color = '$00e';
				
			}elseif($_players[$pl2['Login']]['TeamId']==1){ // red
				if($pl2['Login']==$login) // login
					$color = '$e54';
				elseif($pl2['FinalTime']>0) // finished
					$color = '$f33';
				elseif($pl2['FinalTime']==0) // gave up
					$color = '$833';
				else
					$color = '$e00';
				
			}else{ // no team
				if($pl2['Login']==$login) // login
					$color = '$0e0';
				elseif($pl2['FinalTime']>0) // finished
					$color = '$bcb';
				elseif($pl2['FinalTime']==0) // gave up
					$color = '$373';
				else
					$color = '$edd';
			}
			$pcolor = $color;
			if($pl2['FinalTime']>0) // finished
				$pcolor .= '$i';


			// if Team then show player score (supposed) on left instead of position
			if($_players[$pl2['Login']]['TeamId'] >= 0){
				$tsc = ($_players[$pl2['Login']]['TeamScore'] > 0) ? $color.$_players[$pl2['Login']]['TeamScore'] : '$n . ';
				$tpos = $_players[$pl2['Login']]['Position']['TPos'];
				$msg = ($tsc<10 ? '$s$ddd$m $n<$m'.$tsc.'$ddd$n>$m  ' : '$s$ddd$n<$m'.$tsc.'$ddd$n>$m ').$pcolor.$_players[$plogin]['NickDraw2'];
			}
			else{
				$msg = ($pos<9 ? '$o$m$s$ff0 '.($pos+1).'.  ' : '$o$m$s$ff0'.($pos+1).'. ').$pcolor.$_players[$plogin]['NickDraw2'];
			}
			
			if($pos>0){
				// not first player and not finished : diff with first player
				if($pl2['FinalTime']==0)
					$strtime2 = 'out';
				else{
					$time2 = $pl2['Time'];
					$dtime2 = $pl2['FirstDiffTime'];
					if($pl2['FinalTime']>0){
						$strtime2 = MwTimeToString($time2).' $n '.MwDiffTimeToString($dtime2);
					}else{
						$strtime2 = MwDiffTimeToString($dtime2);
						if($pl2['FirstDiffCheck']>0)
							$strtime2 .= '  +'.$pl2['FirstDiffCheck'].'$n cp';
					}
				}
			}else{
				// first player
				if($pl2['FinalTime']==0)
					$strtime2 = 'out';
				else{
					$time2 = $pl2['Time'];
					$strtime2 = MwTimeToString($time2);

					// first player : add diff against best top
					if($_GameInfos['GameMode'] != LAPS || $_players[$plogin]['FinalTime'] < 0){
						$time2 = end($_players[$plogin][$Checkpoints]);
						$key2 = key($_players[$plogin][$Checkpoints]);
						if(isset($_BestChecks[$key2]) && $_BestChecks[$key2]>0){
							$msg3 = '$s$bbb>$n'.$_BestChecksName.'$m: ';
							$diff = $time2 - $_BestChecks[$key2];
							if($diff>0)
								$msg3 .= '$b11'.MwDiffTimeToString($diff);
							else
								$msg3 .= '$11b'.MwDiffTimeToString($diff);
						}
					}
				}
			}
			$msg2 = '$s'.$color.$strtime2;
		}
		if($msg!=''){
			$xml .= sprintf('<label sizen="14 2" posn="0 %0.2F" text="%s"/>'
											.'<label sizen="11.1 2" posn="14.3 %0.2F" text="%s"/>',
											$y,$msg,$y,$msg2);
			if($msg3!=''){
				$xml .= sprintf('<quad sizen="14 2.2" posn="25.6 %0.2F -20" style="BgsPlayerCard" substyle="BgPlayerCardSmall"/>'
												.'<label sizen="13 2" posn="26.2 %0.2F -20" text="%s"/>'
												,$y+0.1,$y,$msg3);
			}
		}
		$y -= $hline;
	}

	if($_GameInfos['GameMode'] == TEAM){
		// Teams points (supposed)
		$msg = '$z$s$eeeScore: $00f'.$_teams[0]['RaceScore'].'$n$eee - $m$f00'.$_teams[1]['RaceScore'].'$eee.';
		$msg .= ' Points$n(';
		if($_GameInfos['TeamMaxPoints'] == 0) // PPT Team points
			$msg .= $_teams[0]['Max'].' vs '.$_teams[0]['Max'];
		else  // standard Team
			$msg .= $_GameInfos['TeamMaxPoints'].' max';
		$msg .= ')$m:  $00f'.$_teams[0]['Score'].' $n$eee<>$m $f00'.$_teams[1]['Score'].'$eee.';
		$xml .= sprintf('<label sizen="25 2" posn="0 %0.2F" text="%s"/>',$y,$msg);
		$y -= $hline;
	}

	$xml = sprintf('<frame posn="-64 %0.2F -39.2"><format textsize="2"/>',(-23.2-$y)).$xml.'</frame>';
	//console("ml_specinfosUpdateSpecPlayersXml - $login - $xml");
	//console("ml_specinfosUpdateSpecPlayersXml - players \n".print_r($_players,true));
	manialinksShow($login,'ml_specinfos.spec',$xml);
}


?>
