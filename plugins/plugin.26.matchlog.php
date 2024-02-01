<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      12.04.2023
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST3.2 Matchlog plugin
// 
//
if(!$_is_relay) registerPlugin('matchlog',26,1.0);

global $do_match_log,$matchfilename;


$matchfilename = "fastlog/matchlog.txt";  // real value is in Init
$do_match_log = true;


//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function matchlogInit(){
	global $_debug,$do_match_log,$matchfilename,$htmlmatchfilename,$matchfile,$_Game,$_DedConfig,$_lapspoints_points,$_lapspoints_finishbonus,$_lapspoints_notfinishmultiplier,$_lapspoints_rule;

	$matchfilename = "fastlog/matchlog.".strtolower($_Game).".".$_DedConfig['login'].".txt";
	$htmlmatchfilename = "matchlog.".strtolower($_Game).".".$_DedConfig['login'].".html";

	if(!isset($_lapspoints_rule))
		$_lapspoints_rule = 'fet3';

	$_lapspoints_points['fet2'] = array(15,12,11,10,9,8,7,6,6,5,5,4,4,3,3,3,2,2,2,2,1,1,0);
	$_lapspoints_finishbonus['fet2'] = array(100=>5,80=>4,60=>3,40=>2,20=>1); // if >= index% of race, then add indicated bonus, must be in decreasing order !!

  $_lapspoints_points['fet3'] = array(25,22,20,19,18,17,16,15,14,13,12,11,10,10,9,9,8,8,7,7,6,6,5,5,4,4,3,3,2,2,1); // FET6 style points
	$_lapspoints_finishbonus['fet3'] = array(100=>0); // if >= index% of race, then add indicated bonus, must be in decreasing order !!
	$_lapspoints_notfinishmultiplier['fet3'] = 0.5; // coef (round up) for players who did not finish


	// open log file
	if($do_match_log){
		$matchfile = fopen($matchfilename,"ab");
		if($matchfile === false)
			$do_match_log = false;
	}
}


//--------------------------------------------------------------
// BeginRace :
//--------------------------------------------------------------
function matchlogBeginRace($event,$GameInfos){
	global $_debug,$_matchlog_Ranking,$do_match_log,$matchfile,$matchfilename;
	$_matchlog_Ranking[0]['Score'] = -1;
	$_matchlog_Ranking[1]['Score'] = -1;

	// re-open log file (sometimes usefull if the file was modified externally after fast init)
	if($do_match_log){
		if($matchfile !== false)
			@fclose($matchfile);
		$matchfile = fopen($matchfilename,'ab');
	}
}


//------------------------------------------
// BeginRound : 
//------------------------------------------
function matchlogBeginRound(){
	global $_debug,$_PlayerList,$_Ranking,$_matchlog_Ranking,$_GameInfos,$_Status,$_team_color,$_teamcolor,$do_match_log,$_WarmUp,$_FWarmUp;
	if(!$do_match_log || $_WarmUp || $_FWarmUp > 0)
		return;

	if ($_GameInfos['GameMode'] == TEAM){ // team
		if ($_Status['Code']>=3){

			// the score has changed
			if($_Ranking[0]['Score']!=$_matchlog_Ranking[0]['Score'] || $_Ranking[1]['Score']!=$_matchlog_Ranking[1]['Score']){
				$tnick0 = stripColors(''.$_Ranking[0]['NickName']);
				$tnick1 = stripColors(''.$_Ranking[1]['NickName']);

				$msg = '$z $ddd* Score: '.$_teamcolor[0].$tnick0.' '.$_Ranking[0]['Score'];
				$msg .= '$ddd - '.$_teamcolor[1].$_Ranking[1]['Score'].' '.$tnick1.'$z';

				addCall(null,'ChatSendServerMessage', $msg);
				console('Score - '.stripColors($msg));
				$msg = 'Score: '.$tnick0.' '.$_Ranking[0]['Score'].' - '.$_Ranking[1]['Score'].' '.$tnick1."\n";
				matchlog($msg);
			}
		}
		$_matchlog_Ranking = $_Ranking;
	}
	
}


//------------------------------------------
// EndRound : 
//------------------------------------------
function matchlogEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_debug,$_players,$_GameInfos,$_team_color,$do_match_log,$_players_round_current,$_Game,$_teams,$_WarmUp,$_FWarmUp,$_FGameModes,$_FGameMode;
	if($SpecialRestarting || !$do_match_log || $_WarmUp || $_FWarmUp > 0)
		return;

	if(isset($_FGameModes[$_FGameMode]['MatchLogEndRound']) && $_FGameModes[$_FGameMode]['MatchLogEndRound'] != '' &&
		 function_exists($_FGameModes[$_FGameMode]['MatchLogEndRound'])){
		// call FGameMode matchlog callback if exists
		call_user_func($_FGameModes[$_FGameMode]['MatchLogEndRound'],$event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting);

	}elseif($_GameInfos['GameMode'] == TEAM || $_GameInfos['GameMode'] == ROUNDS || $_GameInfos['GameMode'] == CUP){ // team or rounds or cup
		$times = array();
		foreach($_players as $login => &$pl){
			if(!is_string($login))
				$login = ''.$login;
			if($pl['FinalTime']>0){
				//debugPrint('matchlogEndRound - players[$login]',$_players[$login]);
				$times[] = array('Login'=>$pl['Login'],
												 'NickName'=>$pl['NickName'],
												 'FinalTime'=>$pl['FinalTime'],
												 'TeamId'=>$pl['TeamId']);
			}
		}
		if($_debug>1) debugPrint('matchlogEndRound - times',$times);
		if(count($times)>0){
			usort($times,'matchlogRecCompare');
			$best = $times[0]['FinalTime'];
 			$msg = '$i$s$n$dfdR-'.$_players_round_current.'$cc0>> ';
			$msg2 = 'Round-'.$_players_round_current;
			$sep = '';
			$sep2 = ':';
			for($i=0;$i<count($times);$i++){
				if($i==0){
					if($_GameInfos['GameMode'] == TEAM)
						$msg2 .= '(B='.$_teams[0]['Score'].',R='.$_teams[1]['Score'].')';
					else
						$msg2 .= '('.MwTimeToString($times[$i]['FinalTime']).')';
				}
				$msg .= $sep.'$0f0 '.($i+1).'.'.$_team_color[$times[$i]['TeamId']].stripColors($times[$i]['NickName']);
				$msg2 .= $sep2.stripColors($times[$i]['NickName']);
				if($i<3)
					$msg .= ' $8b8('.MwTimeToString($times[$i]['FinalTime']).')';
				$sep = ', ';
				$sep2 = ',';
			}
			$sep2 = "\nTimes: ";
			for($i=0;$i<count($times);$i++){
				$msg2 .= $sep2.stripColors($times[$i]['Login']).'('.MwTimeToString($times[$i]['FinalTime']).')';
				$sep2 = ', ';
			}
			matchlog($msg2);
			// don't show in chat for TMU dedicated (visible in manialinks)
			//addCall(null,'ChatSendServerMessage', $msg);
		}
	}
}

// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function matchlogRecCompare($a, $b){
  if($a['FinalTime']<=0 && $b['FinalTime']<=0)
    return strcmp($a['NickName'],$b['NickName']);
  elseif($b['FinalTime']<=0)
    return -1;
  elseif($a['FinalTime']<=0)
    return 1;
  // both best ok, so...
  elseif($a['FinalTime']<$b['FinalTime'])
    return -1;
  elseif($a['FinalTime']>$b['FinalTime'])
    return 1;
  return -1;
  // same best, so...
  //elseif(isset($a['NewBest']) && isset($a['NewBest']))
  //return ($a['NewBest']<$b['NewBest'])? -1 : 1;
  //else
  //return ($a['Rank']<$b['Rank'])? -1 : 1;
}



//------------------------------------------
// RaceFinish
//------------------------------------------
function matchlogEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	global $_debug,$_players,$_PlayerList,$_Ranking,$do_match_log,$_NumberOfChecks,$_players_round_current,$_lapspoints_points,$_lapspoints_finishbonus,$_lapspoints_notfinishmultiplier,$_lapspoints_rule,$_WarmUp,$_FWarmUp,$_GameInfos,$_players_round_time,$_currentTime,$_FGameModes,$_FGameMode;
	if(!$do_match_log || $_WarmUp || $_FWarmUp > 0)
		return;
	
	if(isset($_FGameModes[$_FGameMode]['MatchLogEndRace']) && $_FGameModes[$_FGameMode]['MatchLogEndRace'] != '' &&
		 function_exists($_FGameModes[$_FGameMode]['MatchLogEndRace'])){
		// call FGameMode matchlog callback if exists
		call_user_func($_FGameModes[$_FGameMode]['MatchLogEndRace'],$event,$Ranking,$ChallengeInfo,$GameInfos);

		// team match log
	}elseif($GameInfos['GameMode'] == TEAM){ // team

		$tnick0 = ''.$Ranking[0]['NickName'];
		$tnick1 = ''.$Ranking[1]['NickName'];

		$msg = 'Score: '.$tnick0.' '.$_Ranking[0]['Score'].' <> '.$_Ranking[1]['Score'].' '.$tnick1."\n";
		matchlog($msg);

		$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
		$msg1 = 'TEAM MATCH on ['.stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).') ['.$_players_round_current.'r]';
		$msg1 .= "\nFinal Score: ".$tnick0.' '.$Ranking[0]['Score'].' <> '.$Ranking[1]['Score'].' '.$tnick1;
		$sep = "\n* Blue players: ";
		for($i = 0; $i < sizeof($_PlayerList); $i++){
			if(isset($_PlayerList[$i]['TeamId']) && ($_PlayerList[$i]['TeamId']==0)){
				$msg1 .= $sep.stripColors($_PlayerList[$i]['Login']).'['.stripColors($_PlayerList[$i]['NickName']).']';
				$sep = ', ';
			}
		}
		$sep = "\n* Red players: ";
		for($i = 0; $i < sizeof($_PlayerList); $i++){
			if(isset($_PlayerList[$i]['TeamId']) && ($_PlayerList[$i]['TeamId']==1)){
				$msg1 .= $sep.stripColors($_PlayerList[$i]['Login']).'['.stripColors($_PlayerList[$i]['NickName']).']';
				$sep = ', ';
			}
		}
		$sep = "\n* Spectators: ";
		for($i = 0; $i < sizeof($_PlayerList); $i++){
			if(isset($_PlayerList[$i]['IsSpectator']) && ($_PlayerList[$i]['IsSpectator']==1)){
				$msg1 .= $sep.stripColors($_PlayerList[$i]['Login']).'['.stripColors($_PlayerList[$i]['NickName']).']';
				$sep = ', ';
			}
		}
		$sep = "\n* All players: ";  // server don't give player team info
		for($i = 0; $i < sizeof($_PlayerList); $i++){
			if(!isset($_PlayerList[$i]['TeamId'])){
				$msg1 .= $sep.stripColors($_PlayerList[$i]['Login']).'['.stripColors($_PlayerList[$i]['NickName']).']';
				$sep = ', ';
			}
		}
		$sep = "\n* Results: "; // pos in each round
		foreach($_players as $login => &$pl){
			if(!is_string($login))
				$login = ''.$login;
			if(count($pl['RoundsPos'])>0){
				$rend = endkey($pl['RoundsPos']);
				if($rend < $_players_round_current)
					$rend = $_players_round_current;

				$msg1 .= $sep.$login.'(';
				$sep2 = '';
				for($rnum=1; $rnum<=$rend; $rnum++){
					if(isset($pl['RoundsPos'][$rnum]))
						$msg1 .= $sep2.$pl['RoundsPos'][$rnum];
					else
						$msg1 .= $sep2.'*';
					$sep2 = ',';
				}
				$msg1 .= ')';
				$sep = ', ';
			}
		}
		matchlog($msg1."\n\n");

	
		// rounds match log
	}elseif($GameInfos['GameMode'] == ROUNDS || $GameInfos['GameMode'] == CUP){ // rounds

		$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
		$msg1 = 'ROUNDS MATCH on ['.stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).') ['.$_players_round_current.'r]';
		for($i = 0; $i < sizeof($Ranking); $i++){
			$msg1 .= "\n".$Ranking[$i]['Rank'].','.$Ranking[$i]['Score'].','.MwTimeToString($Ranking[$i]['BestTime']).','.stripColors($Ranking[$i]['Login']).','.stripColors($Ranking[$i]['NickName']);
		}
		$sep = "\n* Spectators: ";
		for($i = 0; $i < sizeof($_PlayerList); $i++){
			if(isset($_PlayerList[$i]['IsSpectator']) && ($_PlayerList[$i]['IsSpectator'] == 1)){
				$msg1 .= $sep.stripColors($_PlayerList[$i]['Login']).'['.stripColors($_PlayerList[$i]['NickName']).']';
				$sep = ', ';
			}
		}
		$sep = "\n* Results: "; // pos in each round
		foreach($_players as $login => &$pl){
			if(!is_string($login))
				$login = ''.$login;
			if(count($pl['RoundsPos'])>0){
				$rend = endkey($pl['RoundsPos']);
				if($rend < $_players_round_current)
					$rend = $_players_round_current;

				$msg1 .= $sep.$login.'(';
				$sep2 = '';
				for($rnum=1; $rnum<=$rend; $rnum++){
					if(isset($pl['RoundsPos'][$rnum]))
						$msg1 .= $sep2.$pl['RoundsPos'][$rnum];
					else
						$msg1 .= $sep2.'*';
					$sep2 = ',';
				}
				$msg1 .= ')';
				$sep = ', ';
			}
		}
		matchlog($msg1."\n\n");

		
		// timeattack match log
	}elseif($GameInfos['GameMode'] == TA){ // timeattack

		$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
		$msg1 = 'TIMEATTACK MATCH on ['.stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).')';
		for($i = 0; $i < sizeof($Ranking); $i++){
			$msg1 .= "\n".$Ranking[$i]['Rank'].','.MwTimeToString($Ranking[$i]['BestTime']).','.stripColors($Ranking[$i]['Login']).','.stripColors($Ranking[$i]['NickName']);
		}
		$sep = "\n* Spectators: ";
		for($i = 0; $i < sizeof($_PlayerList); $i++){
			if(isset($_PlayerList[$i]['IsSpectator']) && ($_PlayerList[$i]['IsSpectator'] == 1)){
				$msg1 .= $sep.stripColors($_PlayerList[$i]['Login']).'['.stripColors($_PlayerList[$i]['NickName']).']';
				$sep = ', ';
			}
		}
		matchlog($msg1."\n\n");

		
	}elseif($GameInfos['GameMode'] == STUNTS){ // stunts

		$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
		$msg1 = 'STUNTS MATCH on ['.stripColors($ChallengeInfo['Name']).'] ('.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).')';
		for($i = 0; $i < sizeof($Ranking); $i++){
			$msg1 .= "\n".$Ranking[$i]['Rank'].','.$Ranking[$i]['Score'].','.stripColors($Ranking[$i]['Login']).','.stripColors($Ranking[$i]['NickName']);
		}
		$sep = "\n* Spectators: ";
		for($i = 0; $i < sizeof($_PlayerList); $i++){
			if(isset($_PlayerList[$i]['IsSpectator']) && ($_PlayerList[$i]['IsSpectator'] == 1)){
				$msg1 .= $sep.stripColors($_PlayerList[$i]['Login']).'['.stripColors($_PlayerList[$i]['NickName']).']';
				$sep = ', ';
			}
		}
		matchlog($msg1."\n\n");

		
		// laps match log
	}elseif($GameInfos['GameMode'] == LAPS){
		if($_NumberOfChecks > 0){
			// make table
			$lasttime = 0;
			$times = array();
			$nbfinished = 0;
			$mincpdelay = 99999;
			foreach($_players as $login => &$pl){
				if(!is_string($login))
					$login = ''.$login;
				if($pl['CheckpointNumber'] > 0 && $pl['LastCpTime'] > 0 && $pl['LapNumber'] >= 0){
					//debugPrint('matchlogEndRace - players[$login]',$_players[$login]);
					if($pl['FinalTime'] > 0)
						$nbfinished++;
					$times[] = array('Login'=>$login,'NickName'=>$pl['NickName'],
													 'Check'=>$pl['CheckpointNumber']+1,
													 'Lap'=>$pl['LapNumber'],
													 'Time'=>$pl['LastCpTime'],
													 'BestLap'=>$pl['BestLapTime'],
													 'CPdelay'=>$pl['CPdelay']);
					if($pl['LastCpTime'] > $lasttime)
						$lasttime = $pl['LastCpTime'];
				}else{
					if($_debug>1) console("matchlogEndRace:: skipped: {$login},{$pl['Active']},{$pl['CheckpointNumber']},{$pl['Score']},{$pl['LapNumber']},{$pl['LastCpTime']},{$pl['BestLapTime']}");
				}
				if($pl['CPdelay'] > 0 && $pl['CPdelay'] < $mincpdelay)
					$mincpdelay = $pl['CPdelay'];
			}

			$timefinished = false;
			if($GameInfos['LapsTimeLimit'] > 0){
				if(($_currentTime - $_players_round_time) > $GameInfos['LapsTimeLimit']){
					console("matchlogEndRace::Laps race finished by timelimit (race time)");
					$timefinished = true;
				}
				if($lasttime + 10000 > $GameInfos['LapsTimeLimit']){
					console("matchlogEndRace::Laps race finished by timelimit (player time)");
					$timefinished = true;
				}
			}

			if(count($times) > 0 && ($nbfinished > 0 || $timefinished)){

				// sort laps times, then make log and message
				usort($times,'matchlogRecCompareLaps');
				// search bestlap player
				$bestlapi = 0;
				for($i = 0; $i < sizeof($times); $i++){
					if($times[$i]['BestLap']>0){
						if($times[$bestlapi]['BestLap']<=0 || $times[$bestlapi]['BestLap']>$times[$i]['BestLap'])
							$bestlapi = $i;
					}
				}
				
				//if($_debug>8) debugPrint("matchlogEndRace - Laps - times",$times);
				//
				$cuid = isset($ChallengeInfo['UId']) ? $ChallengeInfo['UId'] : 'UID';
				$msg = '$i$s$n$dfdRace$cc0>> ';
				$msg1 = 'LAPS MATCH on ['.stripColors($ChallengeInfo['Name']).'] ('
				.$ChallengeInfo['Environnement'].','.$cuid.','.stripColors($ChallengeInfo['Author']).')';
				$sep = '';
				for($i = 0; $i < sizeof($times); $i++){
					if($i==$bestlapi){
						$msg .= $sep.'$0f0 '.($i+1).'.$ecc'.stripColors($times[$i]['NickName'])
						.' $aaa('.$times[$i]['Check'].','.MwTimeToString($times[$i]['Time'])
						.',$ecc'.MwTimeToString($times[$i]['BestLap']).'$aaa)';
					}else{
						$msg .= $sep.'$0f0 '.($i+1).'.$ddd'.stripColors($times[$i]['NickName'])
						.' $aaa('.$times[$i]['Check'].','.MwTimeToString($times[$i]['Time']).')';
					}
					$msg1 .= "\n".($i+1).','.$times[$i]['Lap'].','.$times[$i]['Check'].','
					.MwTimeToString($times[$i]['Time']).','.MwTimeToString($times[$i]['BestLap']).','
					.(($times[$i]['CPdelay']-$mincpdelay)/1000).',';

					$lapspoints = 0;
					// main points
					if(isset($_lapspoints_points[$_lapspoints_rule][$i]))
						$lapspoints += $_lapspoints_points[$_lapspoints_rule][$i];
					else
						$lapspoints += end($_lapspoints_points[$_lapspoints_rule]);

					if($times[$i]['Check'] >= $times[0]['Check']){
						// add finish bonus
						if(isset($_lapspoints_finishbonus[$_lapspoints_rule]) && isset($_lapspoints_finishbonus[$_lapspoints_rule][100]))
							$lapspoints += $_lapspoints_finishbonus[$_lapspoints_rule][100];

					}else{
						// not finished
						if(isset($_lapspoints_notfinishmultiplier[$_lapspoints_rule])){
							// not finished multiplier
							$lapspoints = (int) ceil($lapspoints * $_lapspoints_notfinishmultiplier[$_lapspoints_rule]);

						}elseif(isset($_lapspoints_finishbonus[$_lapspoints_rule])){
							// or else partial race % bonuses
							$partial = (int) floor($times[$i]['Check'] * 100 / $times[0]['Check']);
							foreach($_lapspoints_finishbonus[$_lapspoints_rule] as $val => $bonus){
								if($partial >= $val){
									$lapspoints += $bonus;
									break;
								}
							}
						}
					}
					$msg1 .= $lapspoints.',';

					$msg1 .= stripColors($times[$i]['Login']).','.stripColors($times[$i]['NickName']);
					$sep = ', ';
				}
				$sep = "\n* Spectators: ";
				for($i = 0; $i < sizeof($_PlayerList); $i++){
					if(isset($_PlayerList[$i]['IsSpectator']) && ($_PlayerList[$i]['IsSpectator'] == 1)){
						$msg1 .= $sep.stripColors($_PlayerList[$i]['Login']).'['.stripColors($_PlayerList[$i]['NickName']).']';
						$sep = ', ';
					}
				}
				$msg1 .= "\n* BestLap: ".MwTimeToString($times[$bestlapi]['BestLap']).','
				.stripColors($times[$bestlapi]['Login']).','.stripColors($times[$bestlapi]['NickName']);
				addCall(null,'ChatSendServerMessage', $msg);
				matchlog($msg1."\n\n");
				console("to matchlog: ".$msg1);
			}else{
				if($_debug>1) console("matchlogEndRace:: Not sent to matchlog:  no times or none have finished !!!");
			}
		}else{
			if($_debug>1) console("matchlogEndRace:: Not sent to matchlog: _NumberOfChecks={$_NumberOfChecks} !!!");
		}
	}

}


// -----------------------------------
function matchlogEndResult($event){
	//console("matchlog.Event[$event]");
	global $_debug,$do_match_log,$_matchlog_copy,$_matchlog_url,$matchfilename,$htmlmatchfilename,$_WarmUp,$_FWarmUp;
	if(!$do_match_log || $_WarmUp || $_FWarmUp > 0)
		return;

	// copy matchlog
	if(isset($_matchlog_copy)){

		// make html matchlog
		$datas = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>TM Match</title></head><body><pre>';
		$datas .= htmlspecialchars(file_get_contents($matchfilename),ENT_QUOTES,'UTF-8');
		$datas .= '</pre></body></html>';
		$nb = file_put_contents('fastlog/'.$htmlmatchfilename,$datas);

		if($nb>100){
			// copy html matchlog
			console("Copy fastlog/$htmlmatchfilename ($nb/".strlen($datas).")...");

			if(isset($_matchlog_url))
				$addcall = array(null,'ChatSendServerMessage', 
											localeText(null,'server_message').'$l['.$_matchlog_url.$htmlmatchfilename.']matchlog copied.');
			else
				$addcall = null;

			file_copy('fastlog/'.$htmlmatchfilename,$_matchlog_copy.$htmlmatchfilename,$addcall);
		}
	}
}


// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function matchlogRecCompareLaps($a, $b)
{
	if($a['Check']>$b['Check'])
		return -1;
	elseif($a['Check']<$b['Check'])
		return 1;
	// same number of check, test times
	elseif($a['Time']<$b['Time'])
    return -1;
  elseif($a['Time']>$b['Time'])
    return 1;
	// same times, test bestlap times
  elseif($a['BestLap']<=0 && $b['BestLap']<=0)
		return -1;
	elseif($b['BestLap']<=0)
		return -1;
	elseif($a['BestLap']<=0)
		return 1;
	elseif($a['BestLap']<$b['BestLap'])
		return -1;
	elseif($a['BestLap']>$b['BestLap'])
		return 1;
	return -1;
}


//------------------------------------------
// write in match log with time
//------------------------------------------
function matchlog($text){
	global $matchfile,$do_match_log;
	if($do_match_log){
		fwrite($matchfile,"[".date("m/d,H:i:s")."] $text\n");
		fflush($matchfile);
	}
}



?>
