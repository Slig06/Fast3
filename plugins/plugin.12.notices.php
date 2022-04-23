<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      20.07.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// replaced by manialinks...

registerPlugin('notices',12,1.0);


//--------------------------------------------------------------
// Add a login in the spool/queue to send him the notice
//--------------------------------------------------------------
function noticesNoticePlayer($login,$delay=0){
	global $_debug,$_Game,$_notices_spool,$_currentTime,$_notices_is_on,$_players,$_Quiet;
	if(!$_notices_is_on || ($_Quiet && !verifyAdmin($login)))
		return;
	if(!is_string($login))
		$login = ''.$login;

	if(!isset($_notices_spool[$login]) && isset($_players[$login]['Relayed']) && !$_players[$login]['Relayed'])
		$_notices_spool[$login] = $_players[$login]['NoticeTime']+$delay;
} 


//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function noticesInit($event){
	global $_debug,$_Game,$_notices_spool,$_old_notices_default;
	if($_debug>3) console("notices.Event[$event]");
	$_notices_spool = array();
	if(!isset($_old_notices_default))
		$_old_notices_default = false;
}


//--------------------------------------------------------------
// Player Connect
//--------------------------------------------------------------
function noticesPlayerConnect($event,$login){
	global $_debug,$_players,$_old_notices_default;
	if(!isset($_players[$login]['ML']['Show.notices']) && 
		 isset($_players[$login]['Relayed']) && !$_players[$login]['Relayed'])
		$_players[$login]['ML']['Show.notices'] = $_old_notices_default;
}


//--------------------------------------------------------------
// MenuBuild (_Reverse because have to be done after ml_main PlayerMenuBuild)
//--------------------------------------------------------------
function noticesPlayerMenuBuild_Reverse($event,$login){
	global $_Game,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	ml_menusAddItem($login, 'menu.hud', 'menu.hud.notices', 
									array('Name'=>array(localeText($login,'menu.hud.notices.on'),
																			localeText($login,'menu.hud.notices.off')),
												'Type'=>'bool',
												'State'=>$_players[$login]['ML']['Show.notices']));
}


//--------------------------------------------------------------
// MenuAction (_Reverse because have to be done after ml_main PlayerMenuAction)
//--------------------------------------------------------------
function noticesPlayerMenuAction_Reverse($event,$login,$action,$state){
	global $_mldebug,$_players;
	//if($_mldebug>6) console("notices.Event[$event]('$login',$action,$state)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	$msg = localeText(null,'server_message').localeText(null,'interact');
	if($action=='menu.hud.notices'){
		$_players[$login]['ML']['Show.notices'] = $state;
		if($state)
			$msg .= localeText($login,'chat.hud.notices.on');
		else
			$msg .= localeText($login,'chat.hud.notices.off');
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}


//--------------------------------------------------------------
// PlayerPositionChange :
//--------------------------------------------------------------
function noticesPlayerPositionChange($event,$login,$changes){
	global $_debug,$_players,$_Quiet;
	if($login===true)
		return;
	if($_Quiet && !verifyAdmin($login))
		return;
	if(!is_string($login))
		$login = ''.$login;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if($_players[$login]['ML']['Show.notices']){
		//console("notices.Event[$event]('$login',$changes)");
		// notice infos to login when he passed a check (not in timeattack)
		if(($changes&2)==2)
			noticesNoticePlayer($login);
	}
}


//--------------------------------------------------------------
// Everytime :
//--------------------------------------------------------------
function noticesEverytime($event){
	global $_debug,$_currentTime,$_players,$_notices_spool;

	// don't send notice to players noticed sooner than that
	$maxtime = $_currentTime-1000;

	// add logins needing a notice refresh
	foreach($_players as $login => &$pl){
		if(!isset($pl['ML']['Show.notices']) || !$pl['Active'] || !isset($pl['Relayed']) || $pl['Relayed'])
			continue;
		if(!is_string($login))
			$login = ''.$login;
		if(!isset($_notices_spool[$login]) && $pl['ML']['Show.notices'] &&
			 ($_currentTime-$pl['NoticeTime'])>3200)  // 3000ms max to keep Notice visible!
			noticesNoticePlayer($login);
	}
	
	// send notices to first spooled logins, only to those older than $maxtime
	if(count($_notices_spool)>0){
		asort($_notices_spool);
		$noticespool_logins = array_keys($_notices_spool);
		for($n=floor(count($_notices_spool)/2)+2;	$n>=0 && count($_notices_spool)>0 && reset($_notices_spool)<$maxtime;	$n--){
			$login = ''.array_shift($noticespool_logins);
			//array_shift($_notices_spool); // bad for numeric logins
			unset($_notices_spool[$login]);
			noticesNoticeTimeToPlayer($login);
		}
	}
}


//--------------------------------------------------------------
// send notice to login. please use noticesNoticePlayer($login) for all non interactive stuffs
//--------------------------------------------------------------
function noticesNoticeTimeToPlayer($login,$force=false){
	global $_debug,$_Game,$_GameInfos,$_currentTime,$_players,$_players_positions,$_players_actives,$_players_spec,$_BestChecks,$_BestChecksName,$_IdealChecks,$_BestPlayersChecks,$_Status,$_old_Status,$_teams,$_team_color,$_NetworkStats,$_Quiet;
	if(!is_string($login))
		$login = ''.$login;
	if($_old_Status['Code']==2 || !isset($_players[$login]['Active']) || 
		 !$_players[$login]['Active'] || !isset($_players[$login]['ML']['Show.notices']) || 
		 $_players[$login]['Relayed'] ||
		 !$_players[$login]['ML']['Show.notices'] || ($_Quiet && !verifyAdmin($login))){
		$_players[$login]['NoticeTime'] = $_currentTime;
		return;
	}
	// don't send notices to disconnected player
	if($_players[$login]['LatestNetworkActivity']>3000){
		return;
	}

	if(isset($_players[$login]['NickName']) && isset($_players[$login]['Checkpoints'])){

		if($_GameInfos['GameMode']==3 && isset($_players[$login]['LapCheckpoints'])){
			$Checkpoints = 'LapCheckpoints';
			$BestCheckpoints = 'BestLapCheckpoints';
		}else{
			$Checkpoints = 'Checkpoints';
			$BestCheckpoints = 'BestCheckpoints';
		}

		// active gap and bars by default in old notices
		$_players[$login]['ML']['Show.notices.posbars'] = 1;
		$_players[$login]['ML']['Show.notices.gap'] = 1;

		// get user config
		if($_GameInfos['GameMode']!=1 &&
			 isset($_players[$login]['Status']) &&  $_players[$login]['Status']<2 &&
			 isset($_players[$login]['ML']['Show.notices.posbars']) && $_players[$login]['ML']['Show.notices.posbars']>0)
			$showpos = true;
		else
			$showpos = false;

		if($_GameInfos['GameMode']!=1 && $_players[$login]['FinalTime']<0 && $_Status['Code']==4 && 
			 !$_players[$login]['IsSpectator'] &&
			 isset($_players[$login]['Status']) &&  $_players[$login]['Status']<1 &&
			 isset($_players[$login]['ML']['Show.notices.gap']) && $_players[$login]['ML']['Show.notices.gap']>0)
			$showgap = true;
		else
			$showgap = false;

		if(isset($_players[$login]['Status']) &&  $_players[$login]['Status']<2 &&
			 isset($_players[$login]['ML']['Show.notices.spec']) && $_players[$login]['ML']['Show.notices.spec']>0 &&
			 $_GameInfos['GameMode']!=1 && $_Status['Code']==4 && 
			 ($_players[$login]['IsSpectator'] || $_players[$login]['FinalTime']>=0))
			$showspec = true;
		else
			$showspec = false;

		//if($_debug>1) console("noticesNoticeTimeToPlayer($login)");

		$position = &$_players[$login]['Position'];

		$msg = '';
		
		// quiet mode
		if($_Quiet)
			$msg .= '$n$811(Quiet mode)  $z';

		// lap/check number
		if($showspec && isset($_players_positions[0]['Login'])){
			$msg .= '$z$s$n$aaaCP: $m'.($_players[$_players_positions[0]['Login']]['CheckpointNumber']+1);
			if(isset($_players[$_players_positions[0]['Login']]['LapNumber']) && 
				 $_players[$_players_positions[0]['Login']]['LapNumber']>0){
				$msg .= '$n / Lap: $m'.$_players[$_players_positions[0]['Login']]['LapNumber'];
			}
		}

		$msg .= "\n\$z";

		if(verifyAdmin($login) && isset($_NetworkStats['LostContacts']) && strlen($_NetworkStats['LostContacts'])>0)
			$msg .= '$n$sNetLost: '.$_NetworkStats['LostContacts'];

		$msg .= "\n\$z";

		if($showpos){
			// positions bars
			$blanks = str_pad('',200,' ');
			$msg .= '$z$n$s$fee';
			$nt = 0;
			foreach($_players_positions as &$pl){
				if($nt<200){
					$n=floor($pl['PrevDiffTime']/1000);
					if($n+$nt>=200)
						$n = 200-$nt;
					if($n>0){
						$msg .= substr($blanks,0,$n);
						$nt += $n;
					}
				}
				if($_players[$pl['Login']]['TeamId']==0){ // blue
					if($pl['FinalTime']>0) // finished
						$msg .= '$33f';
					elseif($pl['FinalTime']==0) // gave up
						$msg .= '$338';
					else
						$msg .= '$00f';
					if($pl['Login']==$login) // login
						$msg .= '|';
					elseif($pl['FinalTime']>0) // finished
						$msg .= '!';
					elseif($pl['FinalTime']==0) // gave up
						$msg .= '\'';
					else
						$msg .= 'l';

				}elseif($_players[$pl['Login']]['TeamId']==1){ // red
					if($pl['FinalTime']>0) // finished
						$msg .= '$f33';
					elseif($pl['FinalTime']==0) // gave up
						$msg .= '$833';
					else
						$msg .= '$f00';
					if($pl['Login']==$login) // login
						$msg .= '|';
					elseif($pl['FinalTime']>0) // finished
						$msg .= '!';
					elseif($pl['FinalTime']==0) // gave up
						$msg .= '\'';
					else
						$msg .= 'l';

				}else{ // no team
					if($pl['Login']==$login) // login
						$msg .= '$0f0|$fee';
					elseif($pl['FinalTime']>0) // finished
						$msg .= '$bdb!$fee';
					elseif($pl['FinalTime']==0) // gave up
						$msg .= '$383\'$fee';
					else
						$msg .= 'l';
				}
			}
		}
		$msg .= "\$z\n";

		// number of players on server
		$msg .= '$s$888'.($_players_actives-$_players_spec).' $n+'.$_players_spec."\$z";

		//if(!$player['IsSpectator'] && ($player['FinalTime']>=0 && $_GameInfos['GameMode']!=1))


		if(!$_players[$login]['IsSpectator']){
			// position and number of players playing
			$msg .= '                                        ';
			if($_GameInfos['GameMode']!=1)
				$msg .= '$w$s$fff'.($position['Pos']+1).' $ddd/ '.count($_players_positions).'     ';
			else
				$msg .= '       ';
			
			if($_players[$login]['FinalTime']>=0 || $_Status['Code']!=4){
				// final time
				$msg .= '$z$s$w'.MwTimeToString($_players[$login]['FinalTime']).'  ';
			}
			if($_players[$login]['FinalTime']!=0){
				// check time
				$msg .= '$z$s$n$888'.($position['Check']+1);

				$time = end($_players[$login][$Checkpoints]);
				$key = key($_players[$login][$Checkpoints]);

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
			}else
				$time = 0;
		}
		$msg .= "\$z\n";

		// team players and scores
		if(($showgap || $showspec) && $_GameInfos['GameMode']==2){
			$msg .= '$s'.$_team_color[1].$_teams[1]['Num'].'$aaa $n<>$m '.$_team_color[0].$_teams[0]['Num']
				.'                                                         $w$e22'
				.$_teams[1]['Score'].'$fff$n <> $w$22e'.$_teams[0]['Score'].'$z';
		}

		$msg .= "\$z\n";

		// ahead player
		if($showgap){
			if($position['PrevLogin']!=''){
				$msg .= '               $s$n$ebb'.stripColors($_players[$position['PrevLogin']]['NickName']).'$z:  ';
				$msg .= '$s$e55'.MwDiffTimeToString($position['PrevDiffTime']).'$z';
				if($position['PrevDiffCheck'])
					$msg .= ' $s$n$e55/ '.$position['PrevDiffCheck'].' cp$z';
			}
			//else
			//$msg .= '               $s$n$ebb'.stripColors($_players[$login]['NickName']).'$z:  $s$e55'.MwTimeToString($time).'$z';
		}
		
		if(!$showspec || !isset($_players_positions[0]['Login']))
			$msg .= "\$z\n\n\n\n\n\n\n\n\n\n\n\n";
		else{
			// spec infos : show 1st player gap with top1
			$pmax = count($_players_positions);
			$lines = 11;
			$lmax = $lines - 3;
			if($pmax>$lines)
				$pdiff = $pmax - $lines;
			else
				$pdiff = 0;
			for($line=0;$line<$lines;$line++){
				$msg .= "\n";
				$pos = ($line < $lmax)? $line : $line+$pdiff;;

				if(isset($_players_positions[$pos])){
					$pl2 = &$_players_positions[$pos];
					$plogin = $pl2['Login'];
					$nick = $pl2['NickName'];
					if($pos>0){
						// not first player and not finished : diff with first player
						if($pl2['FinalTime']==0)
							$strtime2 = MwTimeToString(0);
						else{
							$time2 = $pl2['Time'];
							$dtime2 = $pl2['FirstDiffTime'];
							if($pl2['FinalTime']>0){
								$strtime2 = MwTimeToString($time2).'  '.MwDiffTimeToString($dtime2);
							}else{
								$strtime2 = MwDiffTimeToString($dtime2);
								if($pl2['FirstDiffCheck']>1)
									$strtime2 .= ' $n/ -'.$pl2['FirstDiffCheck'].' cp';
							}
						}
					}else{
						// first player
						$time2 = $pl2['Time'];
						$strtime2 = MwTimeToString($time2);
					}
					// color
					if($_players[$pl2['Login']]['TeamId']==0){ // blue
						if($pl2['Login']==$login) // login
							$color = '$0be';
						elseif($pl2['FinalTime']>0) // finished
							$color = '$33e';
						elseif($pl2['FinalTime']==0) // gave up
							$color = '$337';
						else
							$color = '$00e';
						
					}elseif($_players[$pl2['Login']]['TeamId']==1){ // red
						if($pl2['Login']==$login) // login
							$color = '$eb0';
						elseif($pl2['FinalTime']>0) // finished
							$color = '$f33';
						elseif($pl2['FinalTime']==0) // gave up
							$color = '$733';
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
					if($pl2['FinalTime']>0) // finished
						$color .= '$i';

					$msg .= '$z$s$n$ff0'.($pos+1).'. '.$color.stripColors($nick).' : $m'.$strtime2;

					if($pos==0){
						// first player : add diff against best top
						$time2 = end($_players[$plogin][$Checkpoints]);
						$key2 = key($_players[$plogin][$Checkpoints]);
						if(isset($_BestChecks[$key2]) && $_BestChecks[$key2]>0){
							$msg .= ' $bbb>$n'.$_BestChecksName.'$m: ';
							$diff = $time2 - $_BestChecks[$key2];
							if($diff>0)
								$msg .= '$b11'.MwDiffTimeToString($diff);
							else
								$msg .= '$11b'.MwDiffTimeToString($diff);
						}
					}
				}
			}
			$msg .= "\$z\n";
		}
		
		// behind player
		if($showgap){
			if($position['NextLogin']!=''){
				$msg .= '               $s$n$bbe'.stripColors($_players[$position['NextLogin']]['NickName']).'$z:  ';	
				$msg .= '$s$55e'.MwDiffTimeToString($position['NextDiffTime']).'$z';
				if($position['NextDiffCheck'])
					$msg .= ' $s$n$55e/ +'.$position['NextDiffCheck'].' cp$z';
			}
			//else
			//$msg .= '               $s$n$bbe'.stripColors($_players[$login]['NickName']).'$z:  $s$55e'.MwTimeToString($time).'$z';
		}

		$msg .= "\$z\n\n";

		// Best Top gap
		if(isset($_players[$login]['Status']) &&  $_players[$login]['Status']<2 &&
			 isset($key) && isset($time)){

			if($_players[$login]['ChecksGaps']=='best'){
				if(isset($_BestChecks[$key]) && $_BestChecks[$key]>0){
					$msg .= '               $n$s$bbb'.$_BestChecksName.'$z: ';
					$diff = $time - $_BestChecks[$key];
					if($diff>0)
						$msg .= '$z$w$s$b11'.MwDiffTimeToString($diff);
					else
						$msg .= '$z$w$s$11b'.MwDiffTimeToString($diff);
				}
			}elseif($_players[$login]['ChecksGaps']=='ideal'){
				if(isset($_IdealChecks[$key][$key]) && $_IdealChecks[$key][$key]>0){
					$msg .= '               $n$s$bbbIdeal$z: ';
					$diff = $time - $_IdealChecks[$key][$key];
					if($diff>0)
						$msg .= '$z$w$s$b11'.MwDiffTimeToString($diff);
					else
						$msg .= '$z$w$s$11b'.MwDiffTimeToString($diff);
				}
			}elseif(isset($_players[$login]['ChecksGaps']['Name'])){
				if(isset($_players[$login]['ChecksGaps'][$key]) && $_players[$login]['ChecksGaps'][$key]>0){
					$msg .= '               $n$s$bbb'.$_players[$login]['ChecksGaps']['Name'].'$z: ';
					$diff = $time - $_players[$login]['ChecksGaps'][$key];
					if($diff>0)
						$msg .= '$z$w$s$b11'.MwDiffTimeToString($diff);
					else
						$msg .= '$z$w$s$11b'.MwDiffTimeToString($diff);
				}
			}
		}

		//if($_debug>2) console("NoticeTime ($login,".($_currentTime-$_players[$login]['NoticeTime']).")");
		$_players[$login]['NoticeTime'] = $_currentTime;
		addCall(null,'SendNoticeToLogin', $login, $msg, '',6);

	}elseif($force && isset($_players[$login]['ML']['Show.notices']) && 
					$_players[$login]['ML']['Show.notices']<=0){
		console("Notices off for $login !");
		addCall(null,'SendNoticeToLogin', $login, 'Off...', '');
	}
		 
}

?>
