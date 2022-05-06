<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      21.11.2010
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// dependences: need database.plugin.php 
//
if(!$_is_relay) registerPlugin('records',31);


global $record,$record_start;

//--------------------------------------------------------------
// PlayerInit :
//--------------------------------------------------------------
function recordsInit($event){

	registerCommand('record','/record : show the current track record');
	registerCommand('rec','/rec : show the current track record');
	registerCommand('r','/r : show the current track record');
}


//--------------------------------------------------------------
// PlayerRecord :
//--------------------------------------------------------------
function recordsPlayerRecord($event,$tm_db_n,$login,$time,$rank,$old_time,$old_rank,$ChallengeInfo){
	global $_debug,$_tm_db,$_players,$_used_languages;
  if(!is_string($login))
    $login = ''.$login;
	if($_debug>0) console("records.Event[$event]('$login',$time,$rank)");
	$tmdb =& $_tm_db[$tm_db_n];

	if($tmdb['ShowRecords']>0 && $rank <= $tmdb['Challenge']['ServerMaxRecords']){
		$dest = (($rank<=$tmdb['ShowNewRecordToAll']) && (count($_used_languages)>1))?  null : $login;

		$msg = localeText($dest,'record.top_prefix',$tmdb['Tag'])
			.localeText($dest,'record.top',$_players[$login]['NickName'],$rank,MwTimeToString($time));
		if($old_rank>0 && $old_time>0)
			$msg .= localeText($dest,'record.top_oldinfo',$old_rank,(($time-$old_time)/1000));

		if($rank<=$tmdb['ShowNewRecordToAll'] && $rank<=count($_tm_db[$tm_db_n]['Challenge']['Records'])*$tmdb['ShowNewRecordToAllRatio'])
			addCall(null,'ChatSendServerMessage', $msg);
		elseif($rank<=$tmdb['ShowNewRecordToPlayer'])
			addCall(null,'ChatSendServerMessageToLogin', $msg, $login);
	}
}


//--------------------------------------------------------------
function getRecords($tm_db_n,$ChallengeInfo,$login=''){
	global $_debug,$_tm_db,$_players,$_used_languages;
  if(!is_string($login))
    $login = ''.$login;
	
	$tmdb =& $_tm_db[$tm_db_n];
	
	$msg = '';
	if($tmdb['ShowRecords']>0 && isset($ChallengeInfo['UId']) && isset($tmdb['Challenge']['Uid']) &&
		 $ChallengeInfo['UId']==$tmdb['Challenge']['Uid']){
		//if($_debug>2) debugPrint("getRecords - results - $tm_db_n",$results);
		$records = &$tmdb['Challenge']['Records'];
		
		$nb = 0;
		$nba = 0;
		$nbn = 0;
		if(count($records)>0){
			$sep = "\n";
			for($i=0;$i<count($records);$i++){

				$login2 = $records[$i]['Login'];
				if($nb<$tmdb['ShowRecords'] || ($login!='' && $login2==$login) ||
					 ($nbn<$tmdb['ShowRecords'] && isset($records[$i]['NewBest']) && $records[$i]['NewBest']) ||
					 ($nba<$tmdb['ShowRecords'] && isset($_players[$login2]['Active']) && $_players[$login2]['Active'])){
					if(isset($records[$i]['NewBest']) && $records[$i]['NewBest']){
						$color = '$07f';
						$nbn++;
						$nba++;
					}elseif(isset($_players[$login2]['Active']) && $_players[$login2]['Active']){
						$color = '$7cf';
						$nba++;
					}else{
						$color = '$bcd';
					}
					$msg .= $sep.' $ff0'.$records[$i]['Rank'].'.'.$color.stripColors($records[$i]['NickName']).' $99b('.MwTimeToString($records[$i]['Best']).')';
					$sep = ', ';
					if($nb<$tmdb['ShowRecords'])
						$nba = $nbn = 0;
					$nb++;
				}
			}
		}

		//if($_debug>2) debugPrint("getRecords - _tm_db[$tm_db_n]['Challenge']",$_tm_db[$tm_db_n]['Challenge']);

		if(count($_used_languages)==1){
			reset($_used_languages);
			$dest = key($_used_languages);
		}else
			$dest = null;

		if($nb>0)
			$msg = localeText($dest,'record.recs',$tmdb['Tag'],$ChallengeInfo['Name']).$msg;
		else
			$msg = localeText($dest,'record.recs_none',$tmdb['Tag'],$ChallengeInfo['Name']);
	}
	return $msg;
}

//--------------------------------------------------------------
function getAllRecords($login=''){
	global $_debug,$_tm_db,$_ChallengeInfo;
  if(!is_string($login))
    $login = ''.$login;
	
	$msg = '';
	$sep = '';
	for($i=0; $i < count($_tm_db); $i++){
		$tmdb =& $_tm_db[$i];
		// if database if ok
		if(isset($tmdb['XmlrpcDB'])){
			$msg2 = getRecords($i,$_ChallengeInfo,$login);
			if($msg2!=''){
				$msg .= $sep.$msg2;
				$sep = "\n";
			}
		}
	}
	return $msg;
}




// -----------------------------------------------------------
// -------------------- CHAT COMMAND -------------------------
// -----------------------------------------------------------

// user requests current record
function chat_rec($author, $author_login, $params){
	chat_record($author, $author_login, $params);
}

// user requests current record
function chat_r($author, $author_login, $params){
	chat_record($author, $author_login, $params);
}

// user requests current record
function chat_record($author, $author_login, $params){
	global $record;
	$msg = getAllRecords($author_login);
	if($msg=='')
		$msg = '$ff0>$eee No record avaible, sorry.';
	// send message to user who wrote command
	addCall(null,'ChatSendServerMessageToLogin', $msg, $author_login);
}

?>
