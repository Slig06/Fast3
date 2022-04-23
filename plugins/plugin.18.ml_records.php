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
// 
// dependences: need manialinks and ml_times plugins
//
registerPlugin('ml_records',18);



function ml_recordsInit($event){
	global $_debug,$_tm_db;
	if($_debug>0) console("ml_records.Event[".$event."]");
	ml_recordsAddTimesMod();
}


function ml_recordsStartRace($event,$tm_db_n,$chalinfo,$ChallengeInfo){
	global $_debug;
	if($_debug>0) console("ml_records.Event[$event]");
	ml_recordsAddTimesMod();
	ml_timesRefresh();

}


function ml_recordsPlayerConnect($event,$login){
	global $_mldebug,$_tm_db,$_ml_times_mods,$_players;
  if(!is_string($login))
    $login = ''.$login;
	//console("ml_records.Event[$event]('$login')");

	if(!isset($_players[$login]['ML']['Show.records.0']))
		$_players[$login]['ML']['Show.records.0'] = true;

	if(!isset($_players[$login]['ML']['Show.records.1']))
		$_players[$login]['ML']['Show.records.1'] = true;
}


function ml_recordsPlayerMenuBuild($event,$login){
	global $_mldebug,$_tm_db,$_ml_times_mods,$_players;
	//console("ml_records.Event[$event]('$login')");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if(isset($_tm_db[1]['XmlrpcDB']))
		ml_menusAddItem($login, 'menu.hud.times.menu', 'menu.hud.times.records.1', 
										array('Name'=>stripColors($_tm_db[1]['Name']),'Type'=>'bool'));
	if(isset($_tm_db[0]['XmlrpcDB']))
		ml_menusAddItem($login, 'menu.hud.times.menu', 'menu.hud.times.records.0', 
										array('Name'=>stripColors($_tm_db[0]['Name']),'Type'=>'bool'));
}


function ml_recordsPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_players;
	//if($_mldebug>6) console("ml_records.Event[$event]('$login',$action,$state)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if($action == 'menu.hud.times.records.0'){
		$_players[$login]['ML']['Show.records.0'] = $state;
		ml_timesRefresh($login);

	}else if($action == 'menu.hud.times.records.1'){
		$_players[$login]['ML']['Show.records.1'] = $state;
		ml_timesRefresh($login);
	}

}

	
function ml_recordsPlayerRecord($event,$tm_db_n,$login,$time,$rank,$old_time,$old_rank,$ChallengeInfo){
	ml_timesRefresh();
}


function ml_recordsAddTimesMod(){
	global $_mldebug,$_tm_db,$_ml_times_mods;
	if(isset($_tm_db[1]['XmlrpcDB']) && !isset($_ml_times_mods['db1']))
		ml_timesAddTimesMod('db1','ml_recordsGetTimesArray',1,-1);
	if(isset($_tm_db[0]['XmlrpcDB']) && !isset($_ml_times_mods['db0']))
		ml_timesAddTimesMod('db0','ml_recordsGetTimesArray',0,0);
	//ml_timesAddTimesMod('dbp','ml_recordsGetTimesArray',0,-p);
}


function ml_recordsGetTimesArray($login,$data,$num,$min){
	global $_mldebug,$_Game,$_players,$_tm_db,$_ChallengeInfo,$_players_positions,$_StatusCode;
  if(!is_string($login))
    $login = ''.$login;

	if(!isset($_players[$login]['ML']['Show.records.'.$data]) || !$_players[$login]['ML']['Show.records.'.$data])
		return false;

	$times = array();
	$tmdb =& $_tm_db[$data];

	if(!isset($tmdb['Challenge']['Uid']) || $tmdb['Challenge']['Uid']!=$_ChallengeInfo['UId'] || !isset($tmdb['Challenge']['ServerMaxRecords'])){
		//if($_mldebug>2) console("ml_recordsGetTimesArray - unsure: return false");
		return false;
	}

	$uid = $_ChallengeInfo['UId'];
	if(isset($tmdb['Results']['Uid']) && $tmdb['Results']['Uid']==$uid && count($tmdb['Results']['Records'])>0)
		$records = &$tmdb['Results']['Records'];
	elseif(count($tmdb['Challenge']['Records'])>0)
		$records = &$tmdb['Challenge']['Records'];
	else{
		$times['Name'] = htmlspecialchars(sprintf($tmdb['NameForTrack'],$_Game,$uid),ENT_QUOTES,'UTF-8');
		return $times;
	}

	//if($_mldebug>2) debugPrint("ml_recordsGetTimesArray - $num - $min - records",$records);

	if($min > 0){
		$max = count($records);
		if($max > $tmdb['Challenge']['ServerMaxRecords'])
			$max = $tmdb['Challenge']['ServerMaxRecords'];
		if($min > $num)
			$min = $num;
	
		//if($_mldebug>1) debugPrint("ml_recordsGetTimesArray - state=$_StatusCode - max=$max - min=$min - num=$num - _tm_db[$data]['Results']",$tmdb['Results']);
		//if($_mldebug>1) debugPrint("ml_recordsGetTimesArray - state=$_StatusCode - max=$max - min=$min - num=$num - _tm_db[$data]['Challenge']",$tmdb['Challenge']);

		// fill table with first times
		$own = -1;
		for($i = 0; $i < $num && $i < $max; $i++){
			if($records[$i]['Login']==$login)
				$own = $i;
			$times[$i] = ml_recordsGetPlayerTime($records[$i],$login);
		}

		//if($_mldebug>2) debugPrint("ml_recordsGetTimesArray A - times",$times);

		if($i<$min){
			// complete list with empty entries...
			for( ;$i<$min;$i++)
				$times[$i] = array('Pos'=>'','Name'=>'','Time'=>'');
			
		}elseif($i>1){ 
			// list is full 

			// search login
			for($j = $i; $own < 0 && $j < $max; $j++){
				if($records[$j]['Login']==$login)
					$own = $j;
			}

			if($own>=0){
				if($i<=2){
					// small list : put login in 2nd pos, else put max
					if($own>=$i)
						$times[$i-1] = ml_recordsGetPlayerTime($records[$own],$login);
					elseif($own< 1 && $max>$i)
						$times[$i-1] = ml_recordsGetPlayerTime($records[$max-1],$login);
					
				}elseif($i<4){
					// medium list should not happen, just put login in last pos
					if($own>=$i)
						$times[$i-1] = ml_recordsGetPlayerTime($records[$own],$login);

				}else{
					// classic sized list

					if($own<$i-1){
						// login is before last pos, just put max in last pos
						if($max>$i)
							$times[$i-1] = ml_recordsGetPlayerTime($records[$max-1],$login);

					}else{
						if($own>=$max-1){
							// login is max, just put it last
							$times[$i-1] = ml_recordsGetPlayerTime($records[$own],$login);

						}else{
							$times[$i-3] = ml_recordsGetPlayerTime($records[$own-1],$login);
							$times[$i-2] = ml_recordsGetPlayerTime($records[$own],$login);
							$times[$i-1] = ml_recordsGetPlayerTime($records[$max-1],$login);
						}
					}
				}

			}elseif($i>1 && $max>$i){
				// no login, put max in last pos
				$times[$i-1] = ml_recordsGetPlayerTime($records[$max-1],$login);
			}
		}
	}
	$times['Name'] = htmlspecialchars(sprintf($tmdb['NameForTrack'],$_Game,$uid),ENT_QUOTES,'UTF-8');

	//if($_mldebug>2) debugPrint("ml_recordsGetTimesArray - times",$times);
	//if($_mldebug>1) debugPrint("ml_recordsGetTimesArray - _tm_db[$data]['Challenge']",$tmdb['Challenge']);
	return $times;
}


function ml_recordsGetPlayerTime(&$rec,$login){
	global $_players;
  if(!is_string($login))
    $login = ''.$login;
	$color = '';
	if($rec['Login']==$login){
		$own = true;
		if(isset($rec['NewBest']) && $rec['NewBest'])
			$color = '$f33';
		else
			$color = '$f88';
	}elseif(isset($rec['NewBest']) && $rec['NewBest'])
		 $color = '$07f';
	elseif(isset($_players[$rec['Login']]))
		$color = '$7cf';
	$topcol = (count($rec['Checks'])<=0 || $rec['Best']!=end($rec['Checks'])) ? '$aaa' : '';
	return array('Pos'=>($topcol.'Top$ff0'.$rec['Rank']),
							 'Name'=>'$s'.$color.$rec['NickDraw'],
							 'Time'=>$color.MwTimeToString($rec['Best']));
}


?>
