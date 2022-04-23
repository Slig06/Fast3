<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 / 4.0 (First Automatic Server for Trackmania)
// Web:       
// Date:      29.09.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
registerPlugin('ml_team',19,1.0);



// --- other variables ---
function ml_teamInit(){
  global $_ml_debug,$_ml_team_state,$_ml_team_teams,$_ml_team_players;
	if($_ml_debug>3) console("ml_team.Event[$event]");

	$_ml_team_state = 0;
	$_ml_team_teams = array();
	$_ml_team_players = array();
}


function ml_teamPlayerConnect($event,$login){
	global $_mldebug,$_GameInfos,$_players;
  if(!is_string($login))
    $login = ''.$login;
	//console("ml_team.Event[$event]('$login')");

	if(!isset($_players[$login]['ML']['Show.teamscores']))
		$_players[$login]['ML']['Show.teamscores'] = true;
}


function ml_teamPlayerMenuBuild($event,$login){
	global $_mldebug,$_GameInfos;

	ml_menusAddItem($login, 'menu.hud.times.menu', 'menu.hud.times.team', 
									array('Show'=>($_GameInfos['GameMode'] == TEAM),'Name'=>'Team scores','Type'=>'bool'));
}


function ml_teamPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_players;
	//if($_mldebug>6) console("ml_team.Event[$event]('$login',$action,$state)");

	if($action == 'menu.hud.times.team'){
		$_players[$login]['ML']['Show.teamscores'] = $state;
		ml_timesRefresh($login);
	}	
}

	
function ml_teamBeginRace($event,$GameInfos){
	global $_ml_debug,$_GameInfos,$_ml_team_state,$_ml_team_teams,$_ml_team_players;
  //console("ml_team.Event[$event]");
	$_ml_team_teams = array();
	$_ml_team_players = array();

	if($_GameInfos['GameMode'] == TEAM){
		console("ml_team.Event[$event]");
		if($_ml_team_state==0)
			ml_teamModeActivate();

	}else{
		if($_ml_team_state>0)
			ml_teamModeDeactivate();
	}
}


function ml_teamEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
 	global $_ml_debug,$_GameInfos,$_players_round_current,$_ml_team_state,$_teams,$_ml_team_teams,$_ml_team_players,$_Ranking,$_players,$_players_positions;
	if($SpecialRestarting)
		 return;

	if($_GameInfos['GameMode'] == TEAM){
		console("ml_team.Event[$event]");
		if($_ml_team_state==0)
			ml_teamModeActivate();

		$_ml_team_teams[$_players_round_current] = $_teams;
		$_ml_team_teams[$_players_round_current][0]['TScore'] = $_Ranking[0]['Score'];
		$_ml_team_teams[$_players_round_current][1]['TScore'] = $_Ranking[1]['Score'];

		$scores = array();
		foreach($_players as &$pl){
			if(isset($pl['TeamScores'][0]) && $pl['TeamScores'][0]>0){
				$scores[] = array('Score'=>$pl['TeamScores'][0],
													'TP'=>array('Pos'=>'$z $s$ff0'.$pl['TeamScores'][0],'Name'=>'$z'.$pl['NickDraw'],'Time'=>'$aaa'.MwTimeToString($pl['BestTime'])));
			}
		}
		usort($scores,'ml_teamScoresCompare');
		$_ml_team_players = array();
		foreach($scores as $score)
			$_ml_team_players[] = $score['TP'];

		ml_timesRefresh();
		
	}else{
		if($_ml_team_state>0)
			ml_teamModeDeactivate();
	}
}


function ml_teamModeActivate(){
	global $_ml_team_state,$_players;

	if($_ml_team_state==0){
		$_ml_team_state = 1;
		ml_timesAddTimesMod('ml_team','ml_teamGetTimesArray',0);

		foreach($_players as $login => &$player){
			if(isset($player['ML']['Show.ml_times.1.cols'][0])){
				if(!is_string($login))
					$login = ''.$login;
				$pml = &$_players[$login]['ML'];
				if($pml['Show.ml_times.1.cols'][0]>=0)
					$pml['Show.ml_times.1.cols'][0] = -1 - $pml['Show.ml_times.1.cols'][0];
			}
			ml_menusShowItem($login, 'menu.hud.times.team');
		}

	}
}


function ml_teamModeDeactivate(){
	global $_ml_team_state,$_ml_team_players,$_players;

	if($_ml_team_state>0){
		$_ml_team_state = 0;
		$_ml_team_players = array();
		ml_timesRemoveTimesMod('ml_team');

		foreach($_players as $login => &$player){
			if(isset($player['ML']['Show.ml_times.1.cols'][0])){
				if(!is_string($login))
					$login = ''.$login;
				$pml = &$_players[$login]['ML'];
				if($pml['Show.ml_times.1.cols'][0]<0)
					$pml['Show.ml_times.1.cols'][0] = -1 - $pml['Show.ml_times.1.cols'][0];
			}
			ml_menusHideItem($login, 'menu.hud.times.team');
		}

	}
}


function ml_teamGetTimesArray($login,$data,$num,$min){
	global $_ml_debug,$_ml_team_teams,$_players,$_players_positions,$_ml_team_players;
  if(!is_string($login))
    $login = ''.$login;
	$i = 0;
	$times = array();

	if(!$_players[$login]['ML']['Show.teamscores'])
		return false;

	// compute how many players infos and how many scores history
	$np = 0;
	if($num==$min || (count($_ml_team_players)+count($_ml_team_teams) <= $min)){
		if($min>2){
			$np = floor($num/2);
			if($np>count($_ml_team_players))
				$np = count($_ml_team_players);
		}
		$ns = $min - $np;

	}else{
		$ns = $min;
		while(($np < $num-$ns) && ($np < count($_ml_team_players)))
			$np += $min;
		while(($ns+$np < $num) && ($ns < count($_ml_team_teams)))
			$ns += $min;
	}

	// players points
	for($j=0;$j<$np;$j++){
		if(isset($_ml_team_players[$j]))
			$times[$i++] = $_ml_team_players[$j];
		else
			$times[$i++] = array('Pos'=>'','Name'=>'','Time'=>'');
	}

	// rounds scores
	$endk = endkey($_ml_team_teams)+1;
	for($j=$endk-$ns;$j<$endk;$j++){
		if(isset($_ml_team_teams[$j])){
			$times[$i++] = array('Pos'=>'$z r$ff0'.$j,'Name'=>'$888Points: $00b'.$_ml_team_teams[$j][0]['Score'].' $n$aaa&lt;&gt;$z $b00'.$_ml_team_teams[$j][1]['Score'],'Time'=>'$s$00f'.$_ml_team_teams[$j][0]['TScore'].' $n$ddd&lt;&gt;$z $s$f00'.$_ml_team_teams[$j][1]['TScore'].' ');
		}else{
			$times[$i++] = array('Pos'=>'','Name'=>'','Time'=>'');
		}
	}

	$times['Name'] = 'Team Scores';
	//if($_ml_debug>3) debugPrint("ml_teamGetTimesArray - $num,$min - times",$times);
	return $times;
}


// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function ml_teamScoresCompare($a, $b){
	if($a['Score'] > $b['Score'])
		return -1;
	return 1;
}

?>
