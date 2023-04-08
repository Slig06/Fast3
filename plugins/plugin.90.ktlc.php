<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 / 4.0 (First Automatic Server for Trackmania)
// Web:       
// Date:      02.06.2021
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// KTLC is a survival mode, basically every round 1, 2 or 3 players are eliminated at the end of the map
// 
if(!$_is_relay) registerPlugin('ktlc',90,1.0);


// Can be use to have automatic copy of ktlc result file at end of ktlc
// Be very carefull : it will stop other fast stuff while copying,
// so use remote copy for special case only !
//           Set it in fast.php, not here !
//$_ktlc_result_copy = "/var/www/ktlc/";
//$_ktlc_result_copy = "ftp://xxxx:yyy@ftpperso.free.fr/ktlc/";
//$_ktlc_result_url = "http://xxxx.free.fr/ktlc/" 


// $_ktlc_state (info for dev)
// -1: off
// 0: prep
// 1: in waiting map, WAITING
// 2: waiting map, WAITING
// 3: play round, PLAY
// 4: restart before real play round, WAIT/READY
// 5: first play round, GO


// --- other variables ---
function ktlcInit($event){
  global $_debug,$_DedConfig,$_Game,$_ktlc_state,$_ktlc_res,$_ktlc_round,$_ktlc_round_wait,$_ktlc_GameInfos,$_ktlc_ServerOptions,$ktlcfilename,$ktlcfile,$ktlcresultfile,$ktlclog_roundnum,$_ktlc_final_limit,$_ktlc_dualkill_limit,$_ktlc_triplekill_limit,$_ktlc_oldnb,$_ktlc_auto_spec,$_ktlc_auto_wnext,$_ktlc_ftimeout,$_ktlc_ftimeouts,$_ktlc_vote_ask;
	if($_debug>3) console("ktlc.Event[$event]");

	$_ktlc_ftimeouts = array(50000,35000,30000,25000);

	$_ktlc_auto_spec = true;
	$_ktlc_auto_wnext = true;

	$_ktlc_final_limit = 3;
	$_ktlc_dualkill_limit = 8;
	$_ktlc_triplekill_limit = 3*$_ktlc_dualkill_limit - 2*$_ktlc_final_limit; // ie same number of rounds for dual and single

  $_ktlc_state = -1;
  $_ktlc_GameInfos = array();
  $_ktlc_ServerOptions = array();

	$_ktlc_res = array();
	$_ktlc_round = 0;
	$_ktlc_round_wait = 0;
	$_ktlc_oldnb = 1000;

	$_ktlc_ftimeout = end($_ktlc_ftimeouts);
	
	$_ktlc_vote_ask = false;

	$ktlcfilename = 'fastlog/ktlclog.'.strtolower($_Game).'.'.$_DedConfig['login'].'.txt';
	$ktlcfile = false;
	$ktlcresultfile =false;

	registerCommand('ktlc','/ktlc prep, on, off, wnext, next, specforce login, spec login, play login, free login',true);
}


function ktlcPrep($login){
	global $_debug,$_ktlc_state,$_ktlc_GameInfos,$_ktlc_ServerOptions,$_ktlc_vote_ask,$_GameInfos,$_ServerOptions,$_ml_vote_ask,$_ktlc_oldnb;
	if(!is_string($login))
		$login = ''.$login;

	if($_ktlc_state == -1){
		$_ktlc_GameInfos = $_GameInfos;
		$_ktlc_ServerOptions = $_ServerOptions;
		if(stripos($_ServerOptions['Name'],'ktlc') === false)
			addCall($login,'SetServerName','KTLC');
		addCall($login,'SetServerComment','$l[http://slig.free.fr/TM/KTLC/ktlc.html]KTLC$l'
						.' - Kill The Last Cup: an online rounds survival game. :)');
		addCall($login,'SetLadderMode',0);
		addCall($login,'SetCallVoteTimeOut',0);
		$_ktlc_state = 0;
		$_ktlc_oldnb = 1000;

		if(isset($_ml_vote_ask)){
			$_ktlc_vote_ask = $_ml_vote_ask;
			$_ml_vote_ask = false;
		}

		$msg = localeText(null,'server_message').$login.localeText(null,'interact')." (admin) KTLC mode prepared ! :)\nDon't forget to set a password !";
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);
	}
}


function ktlcOn($login){
	global $_debug,$_StatusCode,$_ktlc_state,$_ml_vote_ask,$_ktlc_vote_ask,$_map_control,$_ktlc_oldnb,$_ktlc_ftimeouts;
  if(!is_string($login))
    $login = ''.$login;

	ktlcPrep($login);

	if($_ktlc_state == 0){
		// delay ktlcOn if in low status
		if($_StatusCode <= 3){
			if($_debug>1) console("Delay ktlcOn while StatusCode<=3 ...");
			addEventDelay(300,'Function','ktlcOn',$login);
			return;
		}

		ktlclog_on();
		$_map_control = false;
		ml_timesAddTimesMod('ktlc','ktlcGetTimesArray',0);

		addCall($login,'SaveMatchSettings','MatchSettings/KTLC_tmp.txt');
		addCall($login,'LoadMatchSettings','KTLC.txt');
		addCall($login,'SetLadderMode',0);
		addCall($login,'SetCallVoteTimeOut',0);
		addCall($login,'SetGameMode',ROUNDS);
		addCall($login,'SetChatTime',2000);
		addCall($login,'SetUseNewRulesRound',false);
		addCall($login,'SetRoundPointsLimit',1);
		addCall($login,'SetRoundForcedLaps',0);
		addCall($login,'SetFinishTimeout',$_ktlc_ftimeouts[1]);
		$_ktlc_state = 2;
		$_ktlc_oldnb = 1000;

		// disable auto ml votes
		if(isset($_ml_vote_ask) && $_ml_vote_ask){
			$_ktlc_vote_ask = $_ml_vote_ask;
			$_ml_vote_ask = false;
		}

		addCallDelay(200,$login,'SetFinishTimeout',$_ktlc_ftimeouts[1]);
		addCall($login,'NextChallenge');

		$msg = localeText(null,'server_message').$login.localeText(null,'interact').' (admin) KTLC mode ON ! :)';
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);

	}else{
		$msg = localeText(null,'server_message').$login.localeText(null,'interact').' KTLC is already ON !!!';
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}


function ktlcOff($login){
	global $_debug,$_ktlc_state,$_ktlc_GameInfos,$_ktlc_ServerOptions,$_ml_vote_ask,$_ktlc_vote_ask,$_map_control,$_players;
  if(!is_string($login))
    $login = ''.$login;

	if($_ktlc_state > 0){
		$_map_control = true;
		ml_timesRemoveTimesMod('ktlc');

		$_ktlc_state = -1;
		addCall($login,'LoadMatchSettings','MatchSettings/KTLC_tmp.txt');
		addCall($login,'SetServerName',$_ktlc_ServerOptions['Name']);
		addCall($login,'SetServerComment',$_ktlc_ServerOptions['Comment']);
		addCall($login,'SetLadderMode',$_ktlc_ServerOptions['CurrentLadderMode']);
		addCall($login,'SetCallVoteTimeOut',$_ktlc_ServerOptions['CurrentCallVoteTimeOut']);
		addCall($login,'SetGameMode',$_ktlc_GameInfos['GameMode']);
		addCall($login,'SetChatTime',$_ktlc_GameInfos['ChatTime']);
		addCall($login,'SetUseNewRulesRound',$_ktlc_GameInfos['RoundsUseNewRules']);
		addCall($login,'SetRoundPointsLimit',$_ktlc_GameInfos['RoundsPointsLimit']);
		addCall($login,'SetRoundForcedLaps',$_ktlc_GameInfos['RoundsForcedLaps']);
		addCall($login,'SetFinishTimeout',$_ktlc_GameInfos['FinishTimeout']);
		addCall($login,'NextChallenge');

		if(isset($_ml_vote_ask)){
			$_ml_vote_ask = $_ktlc_vote_ask;
			$_ktlc_vote_ask = false;
		}

		$msg = localeText(null,'server_message').$login.localeText(null,'interact').' (admin) KTLC mode OFF ! cya :)';
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);

		ktlclog_off();
	}else{
		$msg = localeText(null,'server_message').$login.localeText(null,'interact').' KTLC is already OFF !!!';
		addCall(null,'ChatSendToLogin', $msg, $login);
	}

	// set all as free player
	foreach($_players as $login => $pl){
		if($pl['Active']){
			if($pl['IsSpectator'])
				addCall(null,'ForceSpectator',''.$kpl['Login'],1);
			addCall(null,'ForceSpectator',$pl['Login'],2);
			addCall(null,'ForceSpectator',$pl['Login'],0);
		}
	}
}


function ktlcBeginChallenge($event,$ChallengeInfo,$GameInfos){
  global $_ktlc_state,$_ktlc_res,$_players;
  //console("ktlc.Event[$event]");

  if($_ktlc_state == 5){
    $msg = localeText(null,'server_message').localeText(null,'interact').' KTLC:  $z$s$i$f88!!! $c33GO$d44OOO$e55OOO$f66OOOO $f88!!!';
    // send message in offical chat
    addCall(null,'ChatSendServerMessage', $msg);
		
  }elseif($_ktlc_state == 4){
    $msg = localeText(null,'server_message').localeText(null,'interact').' KTLC:  $z$w$i$s$f00!!! $c00DO$d11N\'T $e22MO$f33VE $f00!!!';
    // send message in offical chat
    addCall(null,'ChatSendServerMessage', $msg);
  }

	// set spec/play if needed, just in case...
	if($_ktlc_state == 3 || $_ktlc_state == 5){
		// playing round : set (again) loosers as spec, set others as player
		foreach($_ktlc_res as $login => $kpl){
			if($kpl['Loose'] > 0){
				if(isset($_players[$login]['Active']) && $_players[$login]['Active'] && !$_players[$login]['IsSpectator']){
					addCall(null,'ForceSpectator',''.$kpl['Login'],2);
					addCall(null,'ForceSpectator',''.$kpl['Login'],1);
				}
			}else{
				if(isset($_players[$login]['Active']) && $_players[$login]['Active'] && $_players[$login]['IsSpectator']){
					addCall(null,'ForceSpectator',''.$kpl['Login'],2);
					addCall(null,'ForceSpectator',''.$kpl['Login'],0);
				}
			}
		}

	}elseif($_ktlc_state == 1 || $_ktlc_state == 2 || $_ktlc_state == 4){
		// first map restart and waiting map : set all as players
		foreach($_players as $login => $pl){
			if($pl['Active']){
				if($pl['IsSpectator'])
					addCall(null,'ForceSpectator',''.$pl['Login'],1);
				addCall(null,'ForceSpectator',$pl['Login'],2);
				addCall(null,'ForceSpectator',$pl['Login'],0);
			}
		}
	}
}


function ktlcBeginRound($event){
  global $_ktlc_state,$_ktlc_res,$_ktlc_round,$_ktlc_round_wait,$_StatusCode,$_ktlc_final_limit,$_ktlc_dualkill_limit,$_ktlc_triplekill_limit,$_ktlc_oldnb,$_players,$_players_actives,$_players_spec,$_ktlc_ftimeout,$_ktlc_ftimeouts,$_GameInfos;
 	//console("ktlc.Event[$event]");

	$ftimeout = ' $n$bbb(Finish: '.floor($_GameInfos['FinishTimeout']/1000).'s)';

	// auto restart new map
  if($_ktlc_state == 4){
		$_ktlc_state = 5;
		$_ktlc_res = array();
		$_ktlc_round = 0;
		console("ktlcBeginRound - _ktlc_state=4 - ChallengeRestart - Status=$_StatusCode");
    addCall(null,'ChallengeRestart');
    $msg = localeText(null,'server_message').localeText(null,'interact').' KTLC:  restart ! $z$w$i$s$f00??? $b00R$c11E$d22A$e33D$f44Y $f00???';
    // send message in offical chat
    addCall(null,'ChatSendServerMessage', $msg);
		$_ktlc_ftimeout = $_ktlc_ftimeouts[$_ktlc_round];

		// Goooo Goooo message (first race round of map)
  }elseif($_ktlc_state == 5){
		$_ktlc_state = 3;
		$_ktlc_res = array();
		$_ktlc_round = 0;
		$_ktlc_oldnb = $_players_actives;

    $msg = localeText(null,'server_message').'$z$s$i$faa !!! $c88Goo $d44Goooo $e55Goooo$f66OOOO $f88!!!';
		if($_players_actives-$_players_spec > $_ktlc_triplekill_limit)
			$msg .= ' $ff0(Triple Kill supposed)'.$ftimeout;
		else if($_players_actives-$_players_spec > $_ktlc_dualkill_limit)
			$msg .= ' $ff0(Dual Kill supposed)'.$ftimeout;
		else
			$msg .= ' $ff0(Simple Kill supposed)'.$ftimeout;
    // send message in offical chat
    addCall(null,'ChatSendServerMessage', $msg);
		$_ktlc_ftimeout = $_ktlc_ftimeouts[$_ktlc_round];

		// ktlc round
  }elseif($_ktlc_state == 3){
    $msg = localeText(null,'server_message').localeText(null,'interact').($_ktlc_round+1).' $d44Goooo !';
		if($_ktlc_oldnb > $_ktlc_triplekill_limit)
			$msg .= '  $o$ff0Triple Kill ! '.$_ktlc_oldnb.' -> '.($_ktlc_oldnb-3).$ftimeout;
		elseif($_ktlc_oldnb > $_ktlc_dualkill_limit)
			$msg .= '  $o$ff0Dual Kill ! '.$_ktlc_oldnb.' -> '.($_ktlc_oldnb-2).$ftimeout;
		elseif($_ktlc_oldnb > $_ktlc_final_limit)
			$msg .= '  $o$ff0Simple Kill ! '.$_ktlc_oldnb.' -> '.($_ktlc_oldnb-1).$ftimeout;
		else
			$msg .= ' $o$ff0!!! Final !!!'.$ftimeout;
    // send message in offical chat
    addCall(null,'ChatSendServerMessage', $msg);
		if(isset($_ktlc_ftimeouts[$_ktlc_round]))
			$_ktlc_ftimeout = $_ktlc_ftimeouts[$_ktlc_round];
		else
			$_ktlc_ftimeout = end($_ktlc_ftimeouts);

		// waiting round
	}elseif($_ktlc_state == 2 || $_ktlc_state == 1){
		if($_ktlc_state == 2){
			$_ktlc_round_wait = 0;
			$_ktlc_state = 1;
		}
		$_ktlc_round = 0;
		$_ktlc_res = array();
    $msg = localeText(null,'server_message').localeText(null,'interact').' KTLC: simple round on WAITING MAP ! ['
			.($_ktlc_round_wait+1).']'.$ftimeout;
    // send message in offical chat
    addCall(null,'ChatSendServerMessage', $msg);
		$_ktlc_ftimeout = end($_ktlc_ftimeouts);
  }
	ml_timesRefresh();
}


function ktlcEndRound($event){
	global $_ktlc_state,$_ktlc_ftimeout,$_ktlc_ftimeouts,$_ktlc_round_wait,$_players_actives,$_StatusCode;
	//console("ktlc.Event[$event]");

}


function ktlcEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_ktlc_state,$_ktlc_ftimeout,$_ktlc_ftimeouts,$_ktlc_round_wait,$_players_actives,$_players,$_StatusCode;
	//console("ktlc.Event[$event]");

	if($_ktlc_state == 1 || $_ktlc_state == 2){
		if($_ktlc_state == 1)
			$_ktlc_round_wait++;
		else
			$_ktlc_round_wait = 0;

		if(isset($_ktlc_ftimeouts[$_ktlc_round_wait+1]))
			$_ktlc_ftimeout = $_ktlc_ftimeouts[$_ktlc_round_wait+1];
		else
			$_ktlc_ftimeout = end($_ktlc_ftimeouts);
	}

	if($_ktlc_state > 0)
		addCall(null,'SetFinishTimeout',$_ktlc_ftimeout);

	if($_players_actives > 0 && ($_ktlc_state == 1 || $_ktlc_state == 3)){
		console("ktlcEndRace - _ktlc_state=$_ktlc_state - ChallengeRestart (short) - Status=$_StatusCode");
		//addCall(null,'SetChatTime',500);
		addCall(null,'ChallengeRestart');

		if(!ktlcLogRace($Ranking,$ChallengeInfo,$GameInfos)){
			$msg = localeText(null,'server_message').localeText(null,'interact').' KTLC:  restarting the challenge !...';
			// send message in offical chat
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			// map finished: set all as player
			foreach($_players as $login => $pl){
				if($pl['Active']){
					if($pl['IsSpectator'])
						addCall(null,'ForceSpectator',''.$pl['Login'],1);
					addCall(null,'ForceSpectator',$pl['Login'],2);
					addCall(null,'ForceSpectator',$pl['Login'],0);
				}
			}
		}
	}

	ml_timesRefresh();

}


function ktlcPlayerFinish($event,$login,$time){
	global $_Game,$_players,$_ktlc_state;
  if(!is_string($login))
    $login = ''.$login;
	//console("ktlc.Event[$event]('$login',$time)");
	if($time > 0 && $_ktlc_state >= 3){
		ktlcBuildResArray();
		ml_timesRefresh();
	}
}


function ktlcPlayerConnect($event,$login){
	global $_debug,$_ktlc_state,$_ktlc_res,$_ktlc_round,$_players;
	// if in ktlc race, not 1st round, and not in previous result or already loosed, then force spec
	if($_ktlc_state == 3){
		$msg = localeText(null,'server_message').localeText(null,'interact');
		if($_ktlc_round > 0){
			if(!isset($_ktlc_res[$login])){
				console("ktlcPlayerConnect:: {$login} => spec (not in list)");
				addCall(null,'ForceSpectator',''.$login,2);
				addCallDelay(5000,null,'ForceSpectator',''.$login,1);
				addCallDelay(10000,null,'ForceSpectator',''.$login,1);
				addCallDelay(20000,null,'ForceSpectator',''.$login,1);
				$msg .= 'Sorry, already started ! please wait as spectator...';

			}elseif($_ktlc_res[$login]['Loose'] == 1){
				console("ktlcPlayerConnect:: {$login} => spec (have lost)");
				addCall(null,'ForceSpectator',''.$login,2);
				addCallDelay(5000,null,'ForceSpectator',''.$login,1);
				addCallDelay(10000,null,'ForceSpectator',''.$login,1);
				addCallDelay(20000,null,'ForceSpectator',''.$login,1);
				$msg .= 'Sorry, you already lost : please wait as spectator...';

			}else{
				console("ktlcPlayerConnect:: {$login} => play (still in game)");
				addCall(null,'ForceSpectator',''.$login,2);
				addCall(null,'ForceSpectator',''.$login,0);
				$msg .= '$d44Goooo ! Round is started : try to finish !';
			}

		}else{
			console("ktlcPlayerConnect:: {$login} => play (first round)");
			addCall(null,'ForceSpectator',''.$login,2);
			addCall(null,'ForceSpectator',''.$login,0);
			$msg .= '$d44Goooo ! First round is started : try to finish !';
		}

		addCall(null,'ChatSendToLogin', $msg, ''.$login);
	}
}


function ktlcBuildResArray($endround=false){
	global $_debug,$_ktlc_state,$_ktlc_res,$_ktlc_round,$_players,$_players_positions,$_PlayerList,$_ktlc_oldnb,$_ktlc_final_limit,$_ktlc_dualkill_limit,$_ktlc_triplekill_limit,$_ktlc_auto_spec,$_ktlc_auto_wnext;
	
	$retval = false; // true if was last round of map

	// at least 1 player should have finished when $endround is true, filtered by ktlcLogRace()
	if($_ktlc_state != 3)
		return $retval;

	$res = array();
	$pos = 1;
	$nbp = 0;
	$nbfinished = 0;
	foreach($_players_positions as &$plp){
		$login = $plp['Login'];
		$nickname = $plp['NickName'];
		$nickdraw = $plp['NickDraw'];

		if(!isset($_ktlc_res[$login]['Loose'])){
			// not in list
			if($_ktlc_round <= 1){
				// new player, else doon't add in list
				$res[$login] = array('Login'=>$login,'NickName'=>$nickname,'NickDraw'=>$nickdraw,'Rank'=>$pos++,'FinalTime'=>$plp['FinalTime'],'Check'=>$plp['Check'],'Time'=>$plp['Time'],'Round'=>$_ktlc_round,'Spec'=>false,'Loose'=>0);
				$nbp++;
				if($plp['FinalTime'] > 0)
					$nbfinished++;
			}

		}elseif($_ktlc_res[$login]['Loose'] > 0){
			// already looser, keep previous info and set spec (again)
			$res[$login] = $_ktlc_res[$login];
			console("ktlcBuildResArray:: {$login} ==> spec (played while had lost !)");
			addCall(null,'ForceSpectator',''.$login,1);
			addCallDelay(1000,null,'ForceSpectator',''.$login,1);

		}else{
			// still player, use new round infos
			$res[$login] = array('Login'=>$login,'NickName'=>$nickname,'NickDraw'=>$nickdraw,'Rank'=>$pos++,'FinalTime'=>$plp['FinalTime'],'Check'=>$plp['Check'],'Time'=>$plp['Time'],'Round'=>$_ktlc_round,'Spec'=>false,'Loose'=>0);
			$nbp++;
			if($plp['FinalTime'] > 0)
				$nbfinished++;
		}
		if(isset($_ktlc_res[$login]))
			unset($_ktlc_res[$login]);
	}

	// keep players who have not play this round in list
	foreach($_ktlc_res as &$plk){
		$login = $plk['Login'];
		$res[$login] = $plk;
	}

	if($endround){

		$msg = localeText(null,'server_message').localeText(null,'interact').'Go to spec: $n$444';
		$sep = '';

		// round 1 : original number of players is the number who played it
		if($_ktlc_round <= 1){
			$_ktlc_oldnb = $nbp;
			if($_ktlc_auto_spec){
				// force spec all not playing
				foreach($_players as $login => &$pl){
					if($pl['Active'] && !isset($res[$login])){
						console("ktlcBuildResArray:: {$login} => spec (not in list)");
						addCall(null,'ForceSpectator',''.$login,1);
						addCallDelay(1000,null,'ForceSpectator',''.$login,1);
						$msg .= $sep.'['.stripColors($pl['NickName']).']'; $sep = ', ';
					}
				}
			}
		}
		$msg .= '$o$f22';

		$keys = array_keys($res);
		if($_ktlc_oldnb > $_ktlc_final_limit){
			// end of round: compute who loose
			//debugPrint("ktlcBuildResArray - $nbp - res",$res);
			//debugPrint("ktlcBuildResArray - $nbp - keys",$keys);

			// minimum number of player who loose depend of number of supposed playing players
			$nbloose = ($_ktlc_oldnb > $_ktlc_triplekill_limit) ? 3 : (($_ktlc_oldnb > $_ktlc_dualkill_limit) ? 2 : 1);
			console("ktlcBuildResArray:: {$_ktlc_oldnb}, {$_ktlc_dualkill_limit}, {$_ktlc_triplekill_limit} : {$nbloose} loosers");

			$nb = $_ktlc_oldnb - 1;
			$nbl = -1;
			
			console("ktlcBuildResArray:1: nb=$nb , nbloose=$nbloose");
			// all who did not played or not finished loose
			while($nb > 0 && (($nb >= $nbp) || ($res[$keys[$nb]]['FinalTime'] <= 0))){
				console("ktlcBuildResArray:: 1.make loose: $nb ({$res[$keys[$nb]]['Login']} ,  not finished)");
				if($_ktlc_auto_spec){
					console("ktlcBuildResArray:: {$res[$keys[$nb]]['Login']} => spec (have lost, not finished)");
					addCall(null,'ForceSpectator',''.$res[$keys[$nb]]['Login'],1);
					addCallDelay(1000,null,'ForceSpectator',''.$res[$keys[$nb]]['Login'],1);
				}
				$msg .= $sep.stripColors($res[$keys[$nb]]['NickName']); $sep = ', ';
				$res[$keys[$nb]]['Round'] = $_ktlc_round;
				if($res[$keys[$nb]]['FinalTime'] < 0){ // did not played, need to set values
					$res[$keys[$nb]]['Rank'] = $nb;
					$res[$keys[$nb]]['FinalTime'] = 0;
					$res[$keys[$nb]]['Check'] = 0;
					$res[$keys[$nb]]['Time'] = 0;
				}
				$nbl = $nb;
				$res[$keys[$nb--]]['Loose'] = 1;
				$nbloose--;
			}

			console("ktlcBuildResArray:2: nb=$nb , nbloose=$nbloose");
			// make loose all (in case of multikill) after (in list) the best looser
			while($nb > 0 && $nbloose > 0){
				console("ktlcBuildResArray:: 2.make loose: $nb ({$res[$keys[$nb]]['Login']} ,  looser #{$nbloose})");
				if($_ktlc_auto_spec){
					console("ktlcBuildResArray:: {$res[$keys[$nb]]['Login']} => spec (have lost, looser #{$nbloose})");
					addCall(null,'ForceSpectator',''.$res[$keys[$nb]]['Login'],1);
					addCallDelay(1000,null,'ForceSpectator',''.$res[$keys[$nb]]['Login'],1);
				}
				$msg .= $sep.stripColors($res[$keys[$nb]]['NickName']); $sep = ', ';
				$nbl = $nb;
				$res[$keys[$nb--]]['Loose'] = 1;
				$nbloose--;
			}

			console("ktlcBuildResArray:3: nb={$nb} , nbl={$nbl} , nbloose={$nbloose}");
			// make loose nb (the best looser) and all previous with same final time
			if($nbl > 0 && $nbloose >= 0){
  			console("ktlcBuildResArray:4: {nb=$nb} , nbl={$nbl} , {nbloose=$nbloose}");
				while($nb >= 0 && $res[$keys[$nb]]['FinalTime'] == $res[$keys[$nbl]]['FinalTime']){
					console("ktlcBuildResArray:5: 3.make loose: $nb ({$res[$keys[$nb]]['Login']} ,  same time as {$nbl})");
					if($_ktlc_auto_spec){
						console("ktlcBuildResArray:: {$res[$keys[$nb]]['Login']} => spec (have lost, same time as first looser)");
						addCall(null,'ForceSpectator',''.$res[$keys[$nb]]['Login'],1);
						addCallDelay(1000,null,'ForceSpectator',''.$res[$keys[$nb]]['Login'],1);
					}
					$msg .= $sep.stripColors($res[$keys[$nb]]['NickName']); $sep = ', ';
					$res[$keys[$nb--]]['Loose'] = 1;
					$nbloose--;
				}
			}

			// new old number of remaining players
			$_ktlc_oldnb = $nb + 1;
			$msg .= "\n\$bbbremain({$_ktlc_oldnb})";
			$sep = ': $z';
			foreach($res as $login => $kpl){
				if($kpl['Loose'] <= 0){
					$msg .= $sep.$kpl['NickName'];
					$sep = ', $z';
					if(isset($_players[$login]['Active']) && $_players[$login]['Active'] && $_players[$login]['IsSpectator']){
						console("ktlcBuildResArray:: {$login} ==> play (still player but was spec)");
						addCall(null,'ForceSpectator',''.$kpl['Login'],1);
						addCall(null,'ForceSpectator',''.$kpl['Login'],2);
						addCall(null,'ForceSpectator',''.$kpl['Login'],0);
					}
				}else{
					if(isset($_players[$login]['Active']) && $_players[$login]['Active'] && !$_players[$login]['IsSpectator']){
						console("ktlcBuildResArray:: {$login} ==> spec (looser but not in spec)");
						addCall(null,'ForceSpectator',''.$kpl['Login'],2);
						addCall(null,'ForceSpectator',''.$kpl['Login'],1);
					}
				}
			}

			// Special victory : only one is remaining !
			if($nb <= 0){
				console("ktlcBuildResArray:: 5.make win: $nb  ({$res[$keys[0]]['Login']},  alone)");
				$msg .= "\n\$ff0\$oSpecial winner :  \$2f2".stripColors($res[$keys[0]]['NickName']);
				$res[$keys[0]]['Loose'] = 2;

				$retval = true;
				if($_ktlc_auto_wnext)
					ktlcWNext();
			}

		}else{
			// Final result !
			$msg2 = '';
			$sep = '';
			$nb = $_ktlc_oldnb - 1;
			while($nb >= 0){
				console("ktlcBuildResArray:: 6.make win: $nb  ({$res[$keys[$nb]]['Login']},  finalist)");
				$msg2 = stripColors($res[$keys[$nb]]['NickName']).$sep.$msg2; $sep = ' , ';
				$res[$keys[$nb--]]['Loose'] = 2;
			}
			$msg .= "\n\$ff0\$oFinal winners :  \$2f2".$msg2;

			$retval = true;
			if($_ktlc_auto_wnext)
				ktlcWNext();
		}
		addCall(null,'ChatSendServerMessage', $msg);
	}
	$_ktlc_res = $res;
	return $retval;
}


//------------------------------------------
//
//------------------------------------------
function ktlcLogRace($Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_ktlc_state,$_ktlc_res,$_ktlc_round,$_players,$_players_positions,$_PlayerList;
	$lastround = false;

	//debugPrint("ktlcLogRace - _players",$_players);
	if($_debug>2) debugPrint("ktlcLogRace - $_ktlc_state - $_ktlc_round - _players_positions",$_players_positions);
	
	// not in ktlc race or nobody finished the round, don't kill, don't log
	if($_ktlc_state != 3 || !isset($_players_positions[0]['FinalTime']) || $_players_positions[0]['FinalTime'] <= 0)
		return $lastround;

	// build new round results
	$_ktlc_round++;
	$lastround = ktlcBuildResArray(true);

	// don't log waiting rounds !
	if($_ktlc_round < 1)
		return $lastround;

	//debugPrint("ktlcLogRace - _ktlc_res ($_ktlc_round)",$_ktlc_res);

	$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID' ;
	$msg1 = "\nKTLC Round $_ktlc_round on [".stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).')';
	foreach($_ktlc_res as &$plk){
		$msg1 .= "\n".$plk['Rank'].','.$plk['Round'].','.MwTimeToString($plk['FinalTime']).','.$plk['Check'].','.$plk['Time'].','.stripColors($plk['Login']).','.htmlspecialchars(stripColors($plk['NickName']));
	}
	$sep = "\n* Spectators: ";
	for($i = 0; $i < sizeof($_PlayerList); $i++){
		if(isset($_PlayerList[$i]['IsSpectator']) && ($_PlayerList[$i]['IsSpectator'] == 1)){
			$msg1 .= $sep.stripColors($_PlayerList[$i]['Login']).'['.htmlspecialchars(stripColors($_PlayerList[$i]['NickName'])).']';
			$sep = ', ';
		}
	}

	ktlclog($msg1."\n",$lastround);

	return $lastround;
}


function ktlcGetTimesArray($login,$data,$num,$min){
	global $_debug,$_ktlc_res,$_ktlc_round,$_players,$_players_positions;
  if(!is_string($login))
    $login = ''.$login;

	$times = array();
	if($min > 0){
		$i = 0;
		foreach($_ktlc_res as $login => &$plk){
			if(!is_string($login))
				$login = ''.$login;
			if($i >= $num)
				break;

			if($plk['Round'] >= $_ktlc_round){ // current round

				if($plk['Loose'] == 1) // marked loose
					$times[$i] = array('Pos'=>$plk['Rank'],'Name'=>'$f22'.$plk['NickDraw'],'Time'=>'$f22'.MwTimeToString($plk['FinalTime']));
				elseif($plk['Loose'] == 2 && $plk['FinalTime']>0) // marked loose == 2 for last 3 who drove the final (and finished)
					$times[$i] = array('Pos'=>$plk['Rank'],'Name'=>'$04f'.$plk['NickDraw'],'Time'=>'$04f'.MwTimeToString($plk['FinalTime']));
				elseif($plk['Loose'] == 2) // marked loose == 2 for last 3 who drove the final (and did not finished!)
					$times[$i] = array('Pos'=>$plk['Rank'],'Name'=>'$04f'.$plk['NickDraw'],'Time'=>'$adf'.MwTimeToString($plk['FinalTime']));
				elseif($plk['FinalTime']>0) // finished
					$times[$i] = array('Pos'=>$plk['Rank'],'Name'=>'$7cf'.$plk['NickDraw'],'Time'=>'$7cf'.MwTimeToString($plk['FinalTime']));
				else // not finished
					$times[$i] = array('Pos'=>$plk['Rank'],'Name'=>$plk['NickDraw'],'Time'=>$plk['Round']);

			}else // lost in previous round
				$times[$i] = array('Pos'=>$plk['Rank'],'Name'=>'$aaa'.$plk['NickDraw'],'Time'=>'$aaa'.$plk['Round']);

			$i++;
		}
		for(; $i < $min; $i++)
			$times[$i] = array('Pos'=>'','Name'=>'','Time'=>'');

		if($_debug>3) debugPrint("ktlcGetTimesArray - times",$times);
	}
	$times['Name'] = '$l[http://slig.free.fr/TM/KTLC/ktlc.html]KTLC$l';
	return $times;
}


//------------------------------------------
// write in ktlc log with time, optionaly in ktlc_result too
//------------------------------------------
function ktlclog($text,$logmatch=false){
	global $ktlcfile,$ktlcresultfile;
	$msg = '['.date('m/d,H:i:s')."] $text\n";
	if($ktlcfile !== false){
		if($logmatch)
			fwrite($ktlcfile,$msg."\n\n\n\n\n\n\n\n\n");
		else
			fwrite($ktlcfile,$msg);
		fflush($ktlcfile);
	}
	if($logmatch && $ktlcresultfile !== false){
		fwrite($ktlcresultfile,$msg."\n\n");
		fflush($ktlcresultfile);
	}
}


//------------------------------------------
// 
//------------------------------------------
function ktlclog_on(){
	global $ktlcfile,$ktlcfilename,$ktlcresultfile,$ktlcresultfilename,$_Game,$_DedConfig;
	if($ktlcfile === false)
		$ktlcfile = fopen($ktlcfilename,'ab');
	if($ktlcfile !== false)
		ktlclog('KTLC ON.');

	if($ktlcresultfile === false){
		$ktlcresultfilename = 'ktlc.'.strtolower($_Game).'.'.$_DedConfig['login'].'.'.date('ymd');
		$ktlcresultfile = fopen('fastlog/ktlc/'.$ktlcresultfilename.'.txt','ab');
	}
}


//------------------------------------------
// 
//------------------------------------------
function ktlclog_off(){
	global $ktlcfile,$ktlcresultfile;
	if($ktlcfile !== false){
		ktlclog('KTLC OFF.');
		@fclose($ktlcfile);
		$ktlcfile = false;
	}

	if($ktlcresultfile !== false){
		@fclose($ktlcresultfile);
		$ktlcresultfile = false;
		ktlclogResultCopy();
	}
}

//------------------------------------------
// 
//------------------------------------------
function ktlclogResultCopy(){
	global $_debug,$_ktlc_result_copy,$_ktlc_result_url,$ktlcresultfilename;

	// copy ktlc_result
	if(isset($_ktlc_result_copy) && file_exists('fastlog/'.$ktlcresultfilename.'.txt')){

		// make html ktlc_result
		$datas = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>TM Match</title></head><body><pre>';
		$datas .= file_get_contents('fastlog/ktlc/'.$ktlcresultfilename.'.txt');
		$datas .= '</pre></body></html>';
		$nb = file_put_contents('fastlog/'.$ktlcresultfilename.'.html',$datas);

		if($nb > 100){
			// copy html ktlc_result
			console("Copy fastlog/$ktlcresultfilename.html ($nb/".strlen($datas).")...");

			if(isset($_ktlc_result_url))
				$addcall = array(null,'ChatSendServerMessage', 
												 localeText(null,'server_message').'$l['.$_ktlc_result_url.$ktlcresultfilename.'html]ktlc result copied.');
			else
				$addcall = null;

			file_copy('fastlog/'.$ktlcresultfilename.'.html',$_ktlc_result_copy.$ktlcresultfilename.'.html',$addcall);
		}
	}
}




//------------------------------------------
// 
//------------------------------------------
function ktlcWNext($login=null,$msg=' Next waiting challenge !',$change='next',$number=1){
	global $_ktlc_state,$_autorestart_no,$_players,$_ktlc_ftimeout,$_ktlc_ftimeouts;

	$_ktlc_state = 2;
	$_autorestart_no = true;
	if($change == 'restart'){
		// wrestart
		addCall($login,'ChallengeRestart');
	}elseif($change == 'prev'){
		// wprev
		$num = false;
		$clist = buildMapList('prev',$number,$num);
		if($clist === false){
			$msg = localeText(null,'server_message').$author.localeText(null,'interact').' Failed to do prev {$number}.';
			addCall(null,'ChatSendToLogin', $msg, $login);
			return;
		}else{
			addCall($login,'ChooseNextChallengeList',$clist);
			addCallDelay(1000,$login,'NextChallenge');
		}
	}else{
		// wnext
		if($number == 1){
			addCall($login,'NextChallenge');
		}else{
			$num = false;
			$clist = buildMapList('next',$number,$num);
			if($clist === false){
				$msg = localeText(null,'server_message').$author.localeText(null,'interact').' Failed to do next {$number}.';
				addCall(null,'ChatSendToLogin', $msg, $login);
				return;
			}else{
				addCall($login,'ChooseNextChallengeList',$clist);
				addCallDelay(1000,$login,'NextChallenge');
			}
		}
	}
	
	$_ktlc_ftimeout = $_ktlc_ftimeouts[1];
	addCall(null,'SetFinishTimeout',$_ktlc_ftimeout);
	
	if($login !== null){
		$msg2 = localeText(null,'server_message').$login.localeText(null,'interact').$msg;
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg2);
	}

	// put all as player
	foreach($_players as $login2 => &$pl){
		addCall($login,'ForceSpectator',''.$login2,2);
		addCall($login,'ForceSpectator',''.$login2,0);
	}	
	addCall(null,'GetPlayerList',260,0,1);
}


// -----------------------------------------------------------
// -------------------- CHAT COMMAND -------------------------
// -----------------------------------------------------------


function chat_ktlc($author, $login, $params, $params2=null){
	global $_GameInfos,$_NextGameInfos,$_ServerOptions,$_autorestart_no,$_StatusCode,$_players;
	global $_ktlc_state,$_ktlc_GameInfos,$_ktlc_ServerOptions,$_players,$_ktlc_final_limit,$_ktlc_dualkill_limit,$_ktlc_triplekill_limit,$_ktlc_auto_spec,$_ktlc_auto_wnext;

	// verify if author is in admin list
	if(!verifyAdmin($login))
		return;

	// if changing map or sync then delay the command !
	if($_StatusCode <= 3){
		addEventDelay(300,'Function','chat_ktlc',$author, $login, $params);
		return;
	}

	// prev waiting ktlc track
	if(isset($params[0]) && $params[0] == 'wprev'){
		if($_ktlc_state > 0){
			if(!isset($params[1]))
				$params[1] = 1;
			ktlcWNext($login,' (admin) forced prev waiting challenge !','prev',$params[1]);
			
		}else{
			$msg = localeText(null,'server_message').$author.localeText(null,'interact').' KTLC mode is not active ! put all players in play mode.';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
		// next waiting ktlc track
	}elseif(isset($params[0]) && $params[0] == 'wnext'){
		if($_ktlc_state > 0){
			if(!isset($params[1]))
				$params[1] = 1;
			ktlcWNext($login,' (admin) forced next waiting challenge !','next',$params[1]);
			
		}else{
			$msg = localeText(null,'server_message').$author.localeText(null,'interact').' KTLC mode is not active ! put all players in play mode.';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
		// restarting as ktlc track
	}elseif(isset($params[0]) && $params[0] == 'wrestart'){
		if($_ktlc_state > 0){
			ktlcWNext($login,' (admin) forced restart as waiting challenge !','restart');
			
		}else{
			$msg = localeText(null,'server_message').$author.localeText(null,'interact').' KTLC mode is not active ! put all players in play mode.';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
	}elseif(isset($params[0]) && $params[0] == 'next'){
		if($_ktlc_state > 0 && $_ktlc_state < 3){
			if($_StatusCode > 3){
				$_ktlc_state = 4;
				$_autorestart_no = true;
				addCall($login,'NextChallenge');
				$msg = localeText(null,'server_message').$author.localeText(null,'interact').' (admin) forced next KTLC challenge !';
				// send message in offical chat
				addCall(null,'ChatSendServerMessage', $msg);
				// put all as player
				foreach($_players as $login2 => &$pl){
					addCall($login,'ForceSpectator',''.$login2,2);
					addCall(null,'ForceSpectator',''.$login2,0);
				}	
				addCall(null,'GetPlayerList',260,0,1);
			}

		}elseif($_ktlc_state >= 3){
			$msg = localeText(null,'server_message').$author.localeText(null,'interact').' You are already on a ktlc map ! use /ktlc wnext to go to next waiting map !';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$msg = localeText(null,'server_message').$author.localeText(null,'interact').' KTLC mode is not active !';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
	}elseif(isset($params[0]) && $params[0] == 'restart'){
		if($_ktlc_state > 0){
			if($_StatusCode > 3){
				$_ktlc_state = 4;
				$_autorestart_no = true;
				addCall($login,'ChallengeRestart');
				$msg = localeText(null,'server_message').$author.localeText(null,'interact').' (admin) forced restart of Complete KTLC challenge !';
				// send message in offical chat
				addCall(null,'ChatSendServerMessage', $msg);
				// put all as player
				foreach($_players as $login2 => &$pl){
					addCall($login,'ForceSpectator',''.$login2,2);
					addCall(null,'ForceSpectator',''.$login2,0);
				}	
				addCall(null,'GetPlayerList',260,0,1);
			}

		}else{
			$msg = localeText(null,'server_message').$author.localeText(null,'interact').' KTLC mode is not active !';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
		// prepare ktlc mode
	}elseif(isset($params[0]) && $params[0] == 'prep'){
		ktlcPrep($login);

		// activate ktlc mode
	}elseif(isset($params[0]) && $params[0] == 'on'){
		ktlcOn($login);
		// set dualkill and triplekill limit values if specified

		if(isset($params2[1])){
			$num = $params2[1]+0;
			if($_ktlc_final_limit < $num){
				$_ktlc_dualkill_limit = $num;
				if(isset($params2[2])){
					$num2 = $params2[2]+0;
					if($_ktlc_dualkill_limit < $num2)
						$_ktlc_triplekill_limit = $num2;
					else
						$_ktlc_triplekill_limit = 3*$_ktlc_dualkill_limit - 2*$_ktlc_final_limit; // ie same number of rounds for dual and single
				}else
					$_ktlc_triplekill_limit = 3*$_ktlc_dualkill_limit - 2*$_ktlc_final_limit; // ie same number of rounds for dual and single

				if($_ktlc_dualkill_limit < $_ktlc_final_limit + 2)
					$_ktlc_dualkill_limit = $_ktlc_final_limit + 2;
				if($_ktlc_triplekill_limit < $_ktlc_final_limit + 3)
					$_ktlc_triplekill_limit = $_ktlc_final_limit + 3;
			}
		}
		$msg = localeText(null,'server_message').localeText(null,'interact')."KTLC Dualkill limit: {$_ktlc_dualkill_limit} , Triplekill limit: {$_ktlc_triplekill_limit}";
		addCall(null,'ChatSendServerMessage', $msg);

		// desactivate ktlc mode
	}elseif(isset($params[0]) && $params[0] == 'off'){
		// put all as player
		foreach($_players as $login2 => &$pl){
			addCall($login,'ForceSpectator',''.$login2,2);
			addCall(null,'ForceSpectator',''.$login2,0);
		}	
		addCall(null,'GetPlayerList',260,0,1);
		ktlcOff($login);
			

		// set dualkill limit
	}elseif(isset($params[0]) && $params[0] == 'limit'){
		if(isset($params2[1])){
			$num = $params2[1]+0;
			if($_ktlc_final_limit < $num){
				$_ktlc_dualkill_limit = $num;
				if(isset($params2[2])){
					$num2 = $params2[2]+0;
					if($_ktlc_dualkill_limit < $num2)
						$_ktlc_triplekill_limit = $num2;
					else
						$_ktlc_triplekill_limit = 3*$_ktlc_dualkill_limit - 2*$_ktlc_final_limit; // ie same number of rounds for dual and single
				}else
					$_ktlc_triplekill_limit = 3*$_ktlc_dualkill_limit - 2*$_ktlc_final_limit; // ie same number of rounds for dual and single

				if($_ktlc_dualkill_limit < $_ktlc_final_limit + 2)
					$_ktlc_dualkill_limit = $_ktlc_final_limit + 2;
				if($_ktlc_triplekill_limit < $_ktlc_final_limit + 3)
					$_ktlc_triplekill_limit = $_ktlc_final_limit + 3;
			}
		}
		$msg = localeText(null,'server_message').localeText(null,'interact')."KTLC Dualkill limit: {$_ktlc_dualkill_limit} , Triplekill limit: {$_ktlc_triplekill_limit}";
		addCall(null,'ChatSendServerMessage', $msg);


		// set auto spec and wnext
	}elseif(isset($params[0]) && $params[0] == 'auto'){
		$_ktlc_auto_spec = true;
		$_ktlc_auto_wnext = true;
		$msg = localeText(null,'server_message').localeText(null,'interact').'Activate auto spec and wnext features.';
		addCall(null,'ChatSendToLogin', $msg, $login);


		// set auto spec and wnext
	}elseif(isset($params[0]) && $params[0] == 'noauto'){
		$_ktlc_auto_spec = false;
		$_ktlc_auto_wnext = false;
		$msg = localeText(null,'server_message').localeText(null,'interact').'Desactivate auto spec and wnext features.';
		addCall(null,'ChatSendToLogin', $msg, $login);


		// players spec state commands...
	}elseif(isset($params[0]) && $params[0] == 'specforce'){
		if(isset($params[1]) && isset($_players[$params[1]])){
			addCall($login,'ForceSpectator',$params[1],1);
			addCall(null,'GetDetailedPlayerInfo',$params[1]);

			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
				.' (admin) forced '.stripColors($_players[$params[1]]['Login']).' to spec !';
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/ktlc specforce login : force to spec';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
	}elseif(isset($params[0]) && $params[0] == 'spec'){
		if(isset($params[1]) && isset($_players[$params[1]])){
			addCall($login,'ForceSpectator',$params[1],1);
			addCall($login,'ForceSpectator',$params[1],0);
			addCall(null,'GetDetailedPlayerInfo',$params[1]);

			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
				.' (admin) made '.stripColors($_players[$params[1]]['Login']).' to spec !';
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/ktlc spec login : make player to spec';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
	}elseif(isset($params[0]) && $params[0] == 'free'){
		if(isset($params[1]) && isset($_players[$params[1]])){
			addCall($login,'ForceSpectator',$params[1],0);

			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
				.' (admin) released '.stripColors($_players[$params[1]]['Login']).' !';
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/ktlc free login : release player from forced spec or play state';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
	}elseif(isset($params[0]) && $params[0] == 'play'){
		if(isset($params[1]) && isset($_players[$params[1]])){
			addCall($login,'ForceSpectator',$params[1],2);
			addCall($login,'ForceSpectator',$params[1],0);
			addCall(null,'GetDetailedPlayerInfo',$params[1]);

			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
				.' (admin) made '.stripColors($_players[$params[1]]['Login']).' to play !';
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/ktlc play login : make player to play';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		// help
	}else{
		$msg = localeText(null,'server_message') . localeText(null,'interact').'/ktlc prep, on [limit1] [limit2], off, wnext [#|envir], wprev [#|envir], wrestart, next, restart, limit [limit1] [limit2], specforce login, spec login, play login, free login';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}

}


?>
