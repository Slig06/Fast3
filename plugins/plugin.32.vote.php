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
registerPlugin('vote',32,1.0);


//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function voteInit($event){
	global $_debug,$_vote_challenge,$_vote_list,$_vote_list_default,$_vote_xmlfilename;
	if($_debug>3) console("vote.Event[$event]");

	if(!isset($_vote_xmlfilename))
		$_vote_xmlfilename = 'votes.xml.txt';

	if(!isset($_vote_list_default))
		$_vote_list_default = 1;

	$_vote_list = array( // array 0 must have all possible vote values, don't change it. Notes are from 0 to 10.
											0 => array(0,1,2,3,4,5,6,7,8,9,10),
											1 => array(0=>0,2=>1,4=>2,6=>3,8=>4,10=>5),
											2 => array(0=>'vote.bad',5=>'vote.maybe',10=>'vote.good'),
											3 => array(0=>'vote.trashit',10=>'vote.keepit'),
											4 => array(0=>'vote.bad',10=>'vote.good'),
											5 => array(0=>'vote.no',10=>'vote.yes'),
											);
	
	$_vote_challenge = array();
	voteSaveChallengeVote();

	registerCommand('vote','/vote number : vote a mark for the challenge (0 to 10)');
}


//
function voteGetChallengeVoteInfos($uid,$mode){
	global $_vote_challenge;

	if(!voteAddChallengeInfos($uid,$mode)){
		console("voteGetChallengeVoteInfos($uid,$mode): map uid unknown !");
		return '';
	}

	if($_vote_challenge['Uid.'.$uid]['Mode.'.$mode]['VoteCount']>0){
		$msg = sprintf('$z$i$s$08f%s$z :  $o$0ff%0.1f $o$i$s%%   $0cf(%d/%d)',
									 $_vote_challenge['Uid.'.$uid]['Name'],
									 $_vote_challenge['Uid.'.$uid]['Mode.'.$mode]['VoteValue']*10,
									 $_vote_challenge['Uid.'.$uid]['Mode.'.$mode]['VoteTotal'],
									 $_vote_challenge['Uid.'.$uid]['Mode.'.$mode]['VoteCount']*10);
	}else{
		$msg = sprintf('$z$i$s$08f%s$z :  $o$0ff??? $o$i$s   $0cf(0/0)',$_vote_challenge['Uid.'.$uid]['Name']);
	}
	return $msg;
}


//
function voteGetChallengeVoteValue($uid,$mode){
	global $_vote_challenge;

	if(!voteAddChallengeInfos($uid,$mode)){
		console("voteGetChallengeVoteInfos($uid,$mode): map uid unknown !");
		return '';
	}

	if($_vote_challenge['Uid.'.$uid]['Mode.'.$mode]['VoteCount']>2)
		return sprintf("%0.0f%%",($_vote_challenge['Uid.'.$uid]['Mode.'.$mode]['VoteValue']*10));

	return '';
}


// 
function voteSetPlayerVote($login,$uid,$mode,$vote){
	global $_debug,$_vote_list,$_vote_challenge,$_vote_list,$_vote_list_default;

	if(!voteAddChallengeInfos($uid,$mode)){
		//console("voteSetPlayerVote($login,$uid,$mode,$vote): map uid unknown !");
		return;
	}
	if(isset($_vote_list[$_vote_list_default][$vote])){
		if($_debug>2) console("voteSetVote($login,$uid,$vote)");
		if(!isset($_vote_challenge['Uid.'.$uid]['Mode.'.$mode]['Votes']['L.'.$login]) || $vote!=$_vote_challenge['Uid.'.$uid]['Mode.'.$mode]['Votes']['L.'.$login]){
			$voteinfo = array('Uid.'.$uid=>array('Mode.'.$mode=>array('Votes'=>array('L.'.$login=>$vote))));
			voteSaveChallengeVote($voteinfo);

		}
		$msg = localeText(null,'server_message').localeText(null,'interact').voteGetChallengeVoteInfos($uid,$mode);
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);

	}else{
		console("voteSetPlayerVote($login,$uid,$mode,$vote): bad vote value !");
		$msg = localeText(null,'server_message').localeText(null,'interact').localeText($login,'vote.syntax').' '.implode(',',array_keys($_vote_list[$_vote_list_default]));
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}


// 
function voteGetPlayerVote($login,$uid,$mode){
	global $_debug,$_vote_challenge;
	if(!voteAddChallengeInfos($uid,$mode)){
		//console("voteGetPlayerVote($login,$uid): map uid unknown !");
		return;
	}
  if(!is_string($login))
    $login = ''.$login;
	if(isset($_vote_challenge['Uid.'.$uid]['Mode.'.$mode]['Votes']['L.'.$login]))
		return $_vote_challenge['Uid.'.$uid]['Mode.'.$mode]['Votes']['L.'.$login];
	return false;
}



//
function voteAddChallengeInfos($uid,$mode){
	global $_ChallengeList,$_vote_challenge;
	if(!isset($_vote_challenge['Uid.'.$uid])){
		foreach($_ChallengeList as $index => &$challenge){
			if($uid==$challenge['UId']){
				$_vote_challenge['Uid.'.$uid] = array('Name'=>asciiString(stripColors($challenge['Name'])),
																							'Name64'=>base64_encode($challenge['Name']),
																							'Environnement'=>$challenge['Environnement'],
																							'Author'=>$challenge['Author']
																			 );
				$_vote_challenge['Uid.'.$uid]['Mode.'.$mode] = array('VoteValue'=>0.0,'VoteTotal'=>0,'VoteCount'=>0,'Votes'=>array());
				return true;
			}
		}
		return false;

	}elseif(!isset($_vote_challenge['Uid.'.$uid]['Name64'])){
		foreach($_ChallengeList as $index => &$challenge){
			if($uid==$challenge['UId']){
				$_vote_challenge['Uid.'.$uid]['Name'] = asciiString(stripColors($challenge['Name']));
				$_vote_challenge['Uid.'.$uid]['Name64'] = base64_encode($challenge['Name']);
				$_vote_challenge['Uid.'.$uid]['Environnement'] = $challenge['Environnement'];
				$_vote_challenge['Uid.'.$uid]['Author'] = $challenge['Author'];
				break;
			}
		}
	}

	if(!isset($_vote_challenge['Uid.'.$uid]['Mode.'.$mode]))
		$_vote_challenge['Uid.'.$uid]['Mode.'.$mode] = array('VoteValue'=>0.0,'VoteTotal'=>0,'VoteCount'=>0,'Votes'=>array());
	return true;
}


// 
function voteSaveChallengeVote($voteinfo=null){
	global $_vote_xmlfilename;
	locked_fileaccess($_vote_xmlfilename, 'voteSaveChallengeVoteHook',$voteinfo);
}


// receive data of locked file, merge with $_vote_challenge velues and recompute votes, then return values to save
function voteSaveChallengeVoteHook($voteinfo,$xmldata){
	global $_vote_challenge;
	if(strlen($xmldata)>0)
		$data = xml_parse_string($xmldata);
	if(!isset($data['votes']) || !is_array($data['votes']))
		$data = array('votes'=>array());
	//debugPrint("voteSaveChallengesVotesHook 0 - xmldata",$xmldata);
	//debugPrint("voteSaveChallengesVotesHook 1 - data",$data);
	//debugPrint("voteSaveChallengesVotesHook 2 - _vote_challenge",$_vote_challenge);
	//debugPrint("voteSaveChallengesVotesHook 3 - voteinfo",$voteinfo);

	// make votes as int values
	foreach($data['votes'] as $uid => &$challenge){
		for($mode=0;$mode<5;$mode++){
			if(isset($challenge['Mode.'.$mode]['Votes']) &&
				 is_array($challenge['Mode.'.$mode]['Votes'])){
				$challenge['Mode.'.$mode]['VoteValue'] += 0;
				$challenge['Mode.'.$mode]['VoteTotal'] += 0;
				$challenge['Mode.'.$mode]['VoteCount'] += 0;
				
				foreach($challenge['Mode.'.$mode]['Votes'] as $login => $lv){
					if(!is_string($login))
						$login = ''.$login;
					$challenge['Mode.'.$mode]['Votes'][$login] += 0;
				}
			}
		}
	}

	if($voteinfo && is_array($voteinfo)){
		foreach($voteinfo as $uid => $chal){
			if(!isset($data['votes'][$uid]) && isset($_vote_challenge[$uid]))
				$data['votes'][$uid] = $_vote_challenge[$uid];
			if(!isset($data['votes'][$uid]['Name']) && isset($_vote_challenge[$uid]['Name']))
				$data['votes'][$uid]['Name'] = $_vote_challenge[$uid]['Name'];
			if(!isset($data['votes'][$uid]['Name64']) && isset($_vote_challenge[$uid]['Name64']))
				$data['votes'][$uid]['Name64'] = $_vote_challenge[$uid]['Name64'];
			
			//debugPrint("voteSaveChallengesVotesHook 3a - $uid - challenge",$chal);
			foreach($chal as $mode => $cmode){
				if(!isset($data['votes'][$uid][$mode]))
					$data['votes'][$uid][$mode] = array('VoteValue'=>0,'VoteTotal'=>0,'VoteCount'=>0);

				//debugPrint("voteSaveChallengesVotesHook 3b - $mode - cmode",$cmode);

				foreach($cmode['Votes'] as $login => $vote){
					if(!is_string($login))
						$login = ''.$login;
					$data['votes'][$uid][$mode]['Votes'][$login] = $vote;
				}

				// recompute vote total for the mode
				$votetotal = 0;
				$votecount = 0;
				foreach($data['votes'][$uid][$mode]['Votes'] as $login => $vote){
					$votetotal += $vote;
					$votecount++;
				}
				$data['votes'][$uid][$mode]['VoteTotal'] = $votetotal;
				$data['votes'][$uid][$mode]['VoteCount'] = $votecount;
				$data['votes'][$uid][$mode]['VoteValue'] = $votetotal/$votecount;
				
			}
		}
	}
	// keep datas
	$_vote_challenge = $data['votes'];

	//debugPrint("voteSaveChallengesVotesHook 4 - data",$data);
	// retrun datas to save
	return xml_build($data);
}




// -----------------------------------------------------------
// -------------------- CHAT COMMAND -------------------------
// -----------------------------------------------------------

// Votes for a track
function chat_vote($author, $login, $params){
	global $_debug,$_Game,$_players,$_ChallengeInfo,$_GameInfos,$_vote_list,$_vote_list_default;
	
	// check if parameter is used correct
	if(!isset($params[0])){
		if(function_exists('ml_voteAskVote')){
			if(!ml_voteAskVote($login)){
				$msg = localeText(null,'server_message').localeText(null,'interact').localeText($login,'vote.notnow');
				addCall(null,'ChatSendToLogin', $msg, $login);
			}
			
		}else{
			$uid = $_ChallengeInfo['UId'];
			$mode = $_GameInfos['GameMode'];
			$msg = localeText(null,'server_message').localeText(null,'interact').voteGetChallengeVoteInfos($uid,$mode);
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

	}elseif(is_numeric($params[0])){
		$uid = $_ChallengeInfo['UId'];
		$mode = $_GameInfos['GameMode'];
		voteSetPlayerVote($login,$uid,$mode,$params[0]+0);
		
	}else{
		$msg = localeText(null,'server_message').localeText(null,'interact').localeText($login,'vote.syntax').' '.implode(',',array_keys($_vote_list[$_vote_list_default]));
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}


?>
