<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      13.06.2010
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// needed plugins: vote
// recommanded pugins: chat.vote
//
if(function_exists('voteInit') && !$_is_relay) 
		 registerPlugin('ml_vote',95,1.0);

// minimum chattime value to have vote appear automatically. You can set that value in fast.php
// note: it will appear only if player have finished the map and have not already voted
// $_ml_vote_minauto = 15000; 

//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function ml_voteInit($event){
	global $_mldebug,$_vote_challenge,$_vote_list,$_ml_vote_ask,$_ml_vote_begintime,$_ml_act,$_vote_first_round,$_currentTime,$_ml_vote_minauto;
	if($_mldebug>3) console("ml_vote.Event[$event]");

	$_vote_first_round = false;

	if(!isset($_ml_vote_ask))
		$_ml_vote_ask = false;

	if(!isset($_ml_vote_minauto))
		$_ml_vote_minauto = 15000;

	$_ml_vote_begintime = $_currentTime;

	foreach($_vote_list[0] as $key => $val)
		manialinksAddAction('ml_vote.'.$key);
	manialinksAddAction('ml_vote.quit');
	manialinksAddAction('ml_vote.open');

	manialinksAddId('ml_vote');

	ml_mainAddEntry($_ml_act['ml_vote.open'],'ml_vote.entry');
	ml_voteInitXmlStrings();

	//manialinksAddManialink(false,'ml_vote','ml_voteBuildXml',null,2);
}


function ml_voteBeginRace($event,$GameInfos){
	global $_mldebug,$_vote_first_round,$_currentTime,$_ml_vote_begintime;

	$_vote_first_round = true;
	$_ml_vote_begintime = $_currentTime;
}


function ml_voteBeginChallenge($event,$ChallengeInfo,$GameInfos){
	global $_mldebug,$_Game,$_ml_act,$_vote_first_round;

	$vote = voteGetChallengeVoteValue($ChallengeInfo['UId'],$GameInfos['GameMode']);
	if($vote!='')
		ml_mainAddEntry($_ml_act['ml_vote.open'],array('ml_vote.entry.value',$vote));
	else
		ml_mainAddEntry($_ml_act['ml_vote.open'],'ml_vote.entry');
}


function ml_voteBeginRound($event){
	global $_mldebug,$_Game,$_GameInfos,$_vote_first_round,$_players,$_currentTime,$_ml_vote_begintime;
	
	// if begin of first round then close vote panel for players
	if($_vote_first_round){
		$_ml_vote_begintime = $_currentTime;
		foreach($_players as $login => &$player){
			if(!is_string($login))
				$login = ''.$login;
			if($player['Active'] && !$player['IsSpectator'])
				manialinksHide($login,'ml_vote');
		}
	}
	$_vote_first_round = false;
}


// 
//function ml_voteEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
function ml_voteEndResult($event){
	global $_mldebug,$_Game,$_ml_vote_ask,$_GameInfos,$_autorestart_map,$_autorestart_newmap,$_ml_vote_minauto;
	
	// don't ask if Team mode or if _autorestart_map is on, only if ChatTime>=$_ml_vote_minauto (default: 15s)
	if($_ml_vote_ask && $_GameInfos['GameMode']!=2 && $_GameInfos['ChatTime']>=$_ml_vote_minauto &&
		 (!isset($_autorestart_map) || !$_autorestart_map) &&
		 (!isset($_autorestart_newmap) || !$_autorestart_newmap)){
		console("ml_vote.Event[$event]");
		ml_voteAskVoteToAll();
	}
}


//
function ml_votePlayerFinish($event,$login,$time){
	global $_mldebug,$_Game,$_players,$_currentTime,$_GameInfos,$_ml_vote_begintime,$_BestChecks,$_ml_vote_ask;

	if(!$_ml_vote_ask)
		return;

	if($_GameInfos['GameMode']==1 && isset($_players[$login]['BestTime'])){
		$delta = $_GameInfos['TimeAttackLimit'] - ($_currentTime - $_ml_vote_begintime);
		if(isset($_BestChecks)){
			$time2 = end($_BestChecks);
			if($_players[$login]['BestTime'] < $time2)
				$time2 = $_players[$login]['BestTime'];
		}else
			$time2 = $_players[$login]['BestTime'];

		//console("Limit=".$_GameInfos['TimeAttackLimit']." delta=".$delta." time2=".$time2." (".$_players[$login]['BestTime'].",".end($_BestChecks).")");

		if($time2>1000 && $time2>$delta+6000)
			ml_voteAskVote($login,false);
	}
	else if($_GameInfos['GameMode']==3 && $time>0)
		ml_voteAskVote($login,false);
}


// 
function ml_voteAskVoteToAll($ask_having_voted=false){
	global $_mldebug,$_Game,$_players,$_ml_vote_ask;

	if(!$_ml_vote_ask)
		return;

	foreach($_players as $login => &$player){
		if(!is_string($login))
			$login = ''.$login;
		if($player['Active'] && !$player['IsSpectator'])
			ml_voteAskVote($login,$ask_having_voted);
	}
}


// 
function ml_voteAskVote($login,$ask_having_voted=true){
	global $_mldebug,$_Game,$_players,$_ChallengeInfo,$_GameInfos;
	if(!isset($_players[$login]['ML']))
		return false;
  if(!is_string($login))
    $login = ''.$login;
	$pml = &$_players[$login]['ML'];

	// if not ask_having_voted and if player already voted or if did not finish, then do nothing
	if(!$ask_having_voted){
		if($_players[$login]['FinalTime']<=0)
			return true;
		$vote = voteGetPlayerVote($login,$_ChallengeInfo['UId'],$_GameInfos['GameMode']);
		if($vote!==false)
			return true;
	}

	$pml['ml_vote.uid'] = $_ChallengeInfo['UId'];
	$pml['ml_vote.mode'] = $_GameInfos['GameMode'];
	ml_voteUpdateXml($login);
	return true;
}


function ml_votePlayerMenuBuild($event,$login){
	global $_mldebug;
	
	ml_menusAddItem($login, 'menu.main', 'menu.vote', 
									array('Name'=>localeText($login,'ml_vote.entry'),
												'Type'=>'item'));
}


function ml_votePlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_Game,$_players,$_ml_act,$_vote_list,$_vote_list_default,$_ChallengeInfo,$_GameInfos;
	if(!isset($_players[$login]['ML']))
		return;
	$pml = &$_players[$login]['ML'];

	if($action=='menu.vote'){
		if(isset($pml['ml_vote.uid']) && $pml['ml_vote.uid']!=''){
			manialinksRemove($login,'ml_vote');
			unset($pml['ml_vote.uid']);
			unset($pml['ml_vote.mode']);

		}elseif(!ml_voteAskVote($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').localeText($login,'vote.notnow');
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
	}
}


// 
function ml_votePlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_mldebug,$_Game,$_players,$_ml_act,$_vote_list,$_vote_list_default,$_ChallengeInfo,$_GameInfos;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['ML']))
		return;
	$pml = &$_players[$login]['ML'];
	
	$msg = localeText(null,'server_message').localeText(null,'interact');

	if($action=='ml_vote.open' || $action=='ml_vote.quit'){
		if(isset($pml['ml_vote.uid']) && $pml['ml_vote.uid']!=''){
			manialinksRemove($login,'ml_vote');
			unset($pml['ml_vote.uid']);
			unset($pml['ml_vote.mode']);

		}elseif(!ml_voteAskVote($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').localeText($login,'vote.notnow');
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

	}else{
		foreach($_vote_list[0] as $key => $val){

			if($action=='ml_vote.'.$key && isset($pml['ml_vote.mode']) &&
				 isset($pml['ml_vote.uid']) && $pml['ml_vote.uid']!=''){
				console("ml_vote.Event[$event]($login,$answer)->ml_vote.$key");
				voteSetPlayerVote($login,$pml['ml_vote.uid'],$pml['ml_vote.mode'],$key);

				if($pml['ml_vote.uid']==$_ChallengeInfo['UId'] && $pml['ml_vote.mode']==$_GameInfos['GameMode']){
					$vote = voteGetChallengeVoteValue($_ChallengeInfo['UId'],$_GameInfos['GameMode']);
					if($vote!='')
						ml_mainAddEntry($_ml_act['ml_vote.open'],array('ml_vote.entry.value',$vote));
					else
						ml_mainAddEntry($_ml_act['ml_vote.open'],'ml_vote.entry');
				}

				$pml['ml_vote.uid'] = '';
				$pml['ml_vote.mode'] = '';
				manialinksRemove($login,'ml_vote');
			}
		}
	}

	//if($_mldebug>9) debugPrint("ml_votePlayerManialinkPageAnswer - _players[$login]['ML']",$pml);
}




function	ml_voteInitXmlStrings(){
	global $_ml_act,$_ml_id,$_ml;

	$_ml['ml_vote_head'] = '<format textsize=\'2\'></format>'
		.'<background bgcolor=\'000d\' bgborderx=\'0.02\' bgbordery=\'0.02\'></background>'
		.'<line><cell width=\'0.90\'><text halign=\'center\' textsize=\'4\'>$z$s$f0f%s$z</text></cell></line>'
		.'<line><cell width=\'0.90\'><text halign=\'center\' textsize=\'3\'>$z$s%s$z</text></cell></line>'
		.'<line>';
	$_ml['ml_vote_cell'] = '<cell width=\'%f\'><text halign=\'center\' action=\'%d\'>$o$ff0%s$z</text></cell>';
	$_ml['ml_vote_end'] = '</line><line><cell width=\'0.90\'><text halign=\'center\' action=\'%d\'>$z%s</text></cell></line>';

}


function ml_voteUpdateXml($login){
	global $_mldebug,$_players,$_ml,$_ml_act,$_vote_list,$_vote_list_default;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['ML']['ShowML']) || $_players[$login]['ML']['ShowML']<=0)
		return;
	$pml = &$_players[$login]['ML'];

	$xml = sprintf($_ml['ml_vote_head'],localeText($login,'ml_vote.query'),voteGetChallengeVoteInfos($pml['ml_vote.uid'],$pml['ml_vote.mode']));

	$width = 0.80/count($_vote_list[$_vote_list_default]);

	$vote = voteGetPlayerVote($login,$pml['ml_vote.uid'],$pml['ml_vote.mode']);
	//debugPrint("ml_voteBuildXml - $login - $vote",$vote);
	if($vote!==false)
		$color = '$f20';
	else
		$color = '';
	foreach($_vote_list[$_vote_list_default] as $key => $val){
		if(is_numeric($val))
			$value = ''.$val;
		else
			$value = localeText($login,$val);
		if($vote===$key)
			$xml .= sprintf($_ml['ml_vote_cell'],$width,$_ml_act['ml_vote.'.$key],'$2f0'.$value);
		else
			$xml .= sprintf($_ml['ml_vote_cell'],$width,$_ml_act['ml_vote.'.$key],$color.$value);
	}
	$xml .= sprintf($_ml['ml_vote_end'],$_ml_act['ml_vote.quit'],localeText($login,'ml.quit'));

	manialinksShow($login,'ml_vote',$xml,0.45,0.56);
}

?>
