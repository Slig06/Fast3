<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      25.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// needed plugins: manialinks
//
registerPlugin('ml_players',91,1.0);


//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function ml_playersInit($event){
	global $_debug,$_ml_players_begintime,$_ml_act,$_currentTime,$_ml_players_samedtimelimit1,$_ml_players_samedtimelimit2,$_ml_players_actions;
	if($_debug>3) console("ml_players.Event[$event]");

	$_ml_players_samedtimelimit1 = 14000;
	$_ml_players_samedtimelimit2 = 45000;

	manialinksAddAction('ml_players.open');
	manialinksAddAction('ml_players.quit');
	manialinksAddAction('ml_players.confirm');
	manialinksAddAction('ml_players.play');
	manialinksAddAction('ml_players.blue');
	manialinksAddAction('ml_players.red');
	manialinksAddAction('ml_players.spec');
	manialinksAddAction('ml_players.specforce');
	manialinksAddAction('ml_players.kick');
	manialinksAddAction('ml_players.ignore');
	manialinksAddAction('ml_players.unignore');
	manialinksAddAction('ml_players.ban');
	manialinksAddAction('ml_players.unban');
	manialinksAddAction('ml_players.black');
	manialinksAddAction('ml_players.unblack');
	manialinksAddAction('ml_players.nickcolor');
	manialinksAddAction('ml_players.sort.nickdown');
	manialinksAddAction('ml_players.sort.nickup');
	manialinksAddAction('ml_players.sort.logindown');
	manialinksAddAction('ml_players.sort.loginup');

	for($key=0; $key < 260; $key++)
		manialinksAddAction('ml_players.'.$key);

	manialinksAddAction('ml_players.refresh');

	$_ml_players_actions = array('ml_players.kick'=>'Kick',
															 'ml_players.ignore'=>'Ignore',
															 'ml_players.unignore'=>'UnIgnore',
															 'ml_players.ban'=>'Ban',
															 'ml_players.unban'=>'UnBan',
															 );

	manialinksAddId('ml_players');
	manialinksAddId('ml_players2');
	manialinksAddId('ml_players_preload');

	ml_mainAddEntry($_ml_act['ml_players.open'],'ml_players.entry');
	ml_playersInitXmlStrings();
}


// 
function ml_playersPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_debug,$_Game,$_players,$_ml_act,$_players,$_ChallengeInfo,$_GameInfos,$_ml_players_actions;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['ML']))
		return;
	// keep only ml_players actions
	if($answer < $_ml_act['ml_players.open'] || $answer > $_ml_act['ml_players.refresh'])
		return;
	//if($_debug>3) console("ml_players.Event[$event]('$login',$answer,$action)");

	$pml = &$_players[$login]['ML'];
	$nickname = $_players[$login]['NickName'];

	$msg = localeText(null,'server_message').localeText(null,'interact');

	switch($action){
	case 'ml_players.open':
	case 'ml_players.quit':
		if(!isset($pml['ml_players'])){
			$pml['ml_players'] = array();
			// remove preload image manialink
			manialinksRemove($login,'ml_players_preload');
		}
		$pml['ml_players']['Action'] = '';
		$pml['ml_players']['Login'] = '';
		$pml['ml_players']['Color'] = true;
		$pml['ml_players']['Sort'] = 'ml_playersCompareNickDown';
		
		if(isset($pml['ml_players']['List'])){
			ml_playersUpdateXml($login,'remove');
			unset($pml['ml_players']['List']);
			
		}else{
			if(verifyAdmin($login)){
				addCall(true,'GetBanList',200,0);
				addCall(true,'GetBlackList',200,0);
				addCall(true,'GetGuestList',200,0);
				addCall(true,'GetIgnoreList',200,0);
			}
			$pml['ml_players']['List'] = array();
			ml_playersUpdateXml($login,'show');
		}
		break;

	case 'ml_players.confirm':
		if($pml['ml_players']['Action']!='' && $pml['ml_players']['Login']!=''){

			if(isset($_ml_players_actions[$pml['ml_players']['Action']])){
				if($_debug>0) console("$login(admin): ".$_ml_players_actions[$pml['ml_players']['Action']].' ('.$pml['ml_players']['Login'].')');
				addCall($login,$_ml_players_actions[$pml['ml_players']['Action']],$pml['ml_players']['Login']);
				
				if(isset($_players[$pml['ml_players']['Login']])){
					$msg = localeText(null,'server_message').$_players[$login]['NickName'].localeText(null,'interact')
						." (admin) made ".$_ml_players_actions[$pml['ml_players']['Action']]." on ".stripColors($pml['ml_players']['Login'])." !";
					addCall(null,'ChatSendServerMessage', $msg);
				}

			}elseif($pml['ml_players']['Action']=='ml_players.black'){
				if($_debug>0) console("$login(admin): BlackList (".$pml['ml_players']['Login'].')');
				addCall($login,'BlackList',$pml['ml_players']['Login']);

			}elseif($pml['ml_players']['Action']=='ml_players.unblack'){
				if($_debug>0) console("$login(admin): UnBlackList (".$pml['ml_players']['Login'].')');
				addCall($login,'UnBlackList',$pml['ml_players']['Login']);

			}elseif(isset($_players[$pml['ml_players']['Login']])){

				if($pml['ml_players']['Action']=='ml_players.spec'){
					if($_debug>0) console("$login(admin): make ".$pml['ml_players']['Login'].' spec.');
					addCall($login,'ForceSpectator',$pml['ml_players']['Login'],1);
					addCall($login,'ForceSpectator',$pml['ml_players']['Login'],0);
				
					$msg = localeText(null,'server_message').$_players[$login]['NickName'].localeText(null,'interact')
						." (admin) made ".stripColors($pml['ml_players']['Login'])." to spec !";
					addCall(null,'ChatSendServerMessage', $msg);

				}elseif($pml['ml_players']['Action']=='ml_players.specforce'){
					if($_debug>0) console("$login(admin): force ".$pml['ml_players']['Login'].' spec.');
					addCall($login,'ForceSpectator',$pml['ml_players']['Login'],1);
					$_players[$pml['ml_players']['Login']]['ForcedByHimself'] = false;
					
					$msg = localeText(null,'server_message').$_players[$login]['NickName'].localeText(null,'interact')
						." (admin) forced ".stripColors($pml['ml_players']['Login'])." to spec !";
					addCall(null,'ChatSendServerMessage', $msg);
				
				}elseif($pml['ml_players']['Action']=='ml_players.play'){
					if($_debug>0) console("$login(admin): make ".$pml['ml_players']['Login'].' play.');
					addCall($login,'ForceSpectator',$pml['ml_players']['Login'],2);
					addCall($login,'ForceSpectator',$pml['ml_players']['Login'],0);

					$msg = localeText(null,'server_message').$_players[$login]['NickName'].localeText(null,'interact')
						." (admin) made ".stripColors($pml['ml_players']['Login'])." to play !";
					addCall(null,'ChatSendServerMessage', $msg);
				
				}elseif($pml['ml_players']['Action']=='ml_players.blue'){
					if($_debug>0) console("$login(admin): make ".$pml['ml_players']['Login'].' play blue.');
					addCall($login,'ForcePlayerTeam',$pml['ml_players']['Login'],0);
					addCall($login,'ForceSpectator',$pml['ml_players']['Login'],2);
					addCall($login,'ForceSpectator',$pml['ml_players']['Login'],0);
				
					$msg = localeText(null,'server_message').$_players[$login]['NickName'].localeText(null,'interact')
						." (admin) put ".stripColors($pml['ml_players']['Login'])." in blue team !";
					addCall(null,'ChatSendServerMessage', $msg);

				}elseif($pml['ml_players']['Action']=='ml_players.red'){
					if($_debug>0) console("$login(admin): make ".$pml['ml_players']['Login'].' play red.');
					addCall($login,'ForcePlayerTeam',$pml['ml_players']['Login'],1);
					addCall($login,'ForceSpectator',$pml['ml_players']['Login'],2);
					addCall($login,'ForceSpectator',$pml['ml_players']['Login'],0);
				
					$msg = localeText(null,'server_message').$_players[$login]['NickName'].localeText(null,'interact')
						." (admin) put ".stripColors($pml['ml_players']['Login'])." in red team !";
					addCall(null,'ChatSendServerMessage', $msg);
				}
			}
			$pml['ml_players']['Action'] = '';
		}
		ml_playersUpdateXml($login,'refresh');
		break;

	case 'ml_players.play':
	case 'ml_players.blue':
	case 'ml_players.red':
	case 'ml_players.spec':
	case 'ml_players.specforce':
	case 'ml_players.kick':
	case 'ml_players.ignore':
	case 'ml_players.unignore':
	case 'ml_players.ban':
	case 'ml_players.unban':
	case 'ml_players.black':
	case 'ml_players.unblack':
		$pml['ml_players']['Action'] = $action;
		//echo "Click: ".$pml['ml_players']['Login']." -> ".$pml['ml_players']['Action']."\n";
		ml_playersUpdateXml($login,'refresh');
		break;

	case 'ml_players.nickcolor':
		$pml['ml_players']['Color'] = !$pml['ml_players']['Color'];
		ml_playersUpdateXml($login,'refresh');
		break;

	case 'ml_players.sort.nickdown':
		$pml['ml_players']['Sort'] = 'ml_playersCompareNickDown';
		ml_playersUpdateXml($login,'refresh');
		//echo "Action: $action\n";
		break;

	case 'ml_players.sort.nickup':
		$pml['ml_players']['Sort'] = 'ml_playersCompareNickUp';
		ml_playersUpdateXml($login,'refresh');
		//echo "Action: $action\n";
		break;

	case 'ml_players.sort.logindown':
		$pml['ml_players']['Sort'] = 'ml_playersCompareLoginDown';
		ml_playersUpdateXml($login,'refresh');
		//echo "Action: $action\n";
		break;

	case 'ml_players.sort.loginup':
		$pml['ml_players']['Sort'] = 'ml_playersCompareLoginUp';
		ml_playersUpdateXml($login,'refresh');
		//echo "Action: $action\n";
		break;

	case 'ml_players.refresh':
		if(verifyAdmin($login)){
			addCall(true,'GetBanList',200,0);
			addCall(true,'GetBlackList',200,0);
			addCall(true,'GetGuestList',200,0);
			addCall(true,'GetIgnoreList',200,0);
		}
		ml_playersUpdateXml($login,'refresh');
		//echo "Click: refresh\n";
		break;

	default:
		$num = $answer - $_ml_act['ml_players.0'];
		//console("click: $answer -> $num");
		//debugPrint("ml_playersPlayerManialinkPageAnswer($login) - [ML][ml_players]",$_players[$login]['ml_players']);
		//print_r($pml['ml_players']);
		
		if(isset($pml['ml_players']['List'][$num])){
			$pml['ml_players']['Login'] = ''.$pml['ml_players']['List'][$num];
			$pml['ml_players']['Action'] = '';
			//console("Click: $num -> ".$pml['ml_players']['Login']);
			ml_playersUpdateXml($login,'refresh');
		}
	}

	//if($_debug>9) debugPrint("ml_playersPlayerManialinkPageAnswer - _players[$login]['ML']",$pml);
}


function ml_playersRefreshPlayersPages(){
	global $_debug,$_players;

	// refresh all opened player list pages
	foreach($_players as $login => &$pl){
		ml_playersUpdateXml(''.$login,'refresh');
	}
}


function ml_playersPlayerConnect($event,$login){
	global $_debug;
	if($_debug>3) console("ml_players.Event[$event]('$login')");
	ml_playersRefreshPlayersPages();
	// preload images manialink

	ml_playersPreloadXml($login);
}


function ml_playersPlayerMenuBuild($event,$login){
	global $_mldebug;
	
	ml_menusAddItem($login, 'menu.main', 'menu.players', 
									array('Name'=>localeText($login,'ml_players.entry'),
												'Type'=>'item'));
}


function ml_playersPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_Game,$_players,$_ml_act,$_players,$_ChallengeInfo,$_GameInfos,$_ml_players_actions;
	if(!isset($_players[$login]['ML']))
		return;
	//if($_mldebug>6) console("ml_team.Event[$event]('$login',$action,$state)");
	$pml = &$_players[$login]['ML'];

	if($action=='menu.players'){
		$msg = localeText(null,'server_message').localeText(null,'interact');
		if(!isset($pml['ml_players'])){
			$pml['ml_players'] = array();
			// remove preload image manialink
			manialinksRemove($login,'ml_players_preload');
		}
		$pml['ml_players']['Action'] = '';
		$pml['ml_players']['Login'] = '';
		$pml['ml_players']['Color'] = true;
		$pml['ml_players']['Sort'] = 'ml_playersCompareNickDown';
		
		if(isset($pml['ml_players']['List'])){
			ml_playersUpdateXml($login,'remove');
			unset($pml['ml_players']['List']);
			
		}else{
			if(verifyAdmin($login)){
				addCall(true,'GetBanList',200,0);
				addCall(true,'GetBlackList',200,0);
				addCall(true,'GetGuestList',200,0);
				addCall(true,'GetIgnoreList',200,0);
			}
			$pml['ml_players']['List'] = array();
			ml_playersUpdateXml($login,'show');
		}
	}
}

	
function ml_playersPlayerShowML($event,$login,$ShowML){
	if($ShowML)
		ml_playersUpdateXml(''.$login,'refresh');
}


function ml_playersPlayerDisconnect($event,$login){
	global $_debug,$_players;
	if(!isset($_players[$login]['Login']))
		return;

	if($_debug>3) console("ml_players.Event[$event]('$login')");
	ml_playersRefreshPlayersPages();
}


function ml_playersPlayerSpecChange($event,$login,$isspec){
	global $_debug;
	if($_debug>3) console("ml_players.Event[$event]('$login',".($isspec?'true':'false').")");
	ml_playersRefreshPlayersPages();
}


function ml_playersPlayerTeamChange($event,$login,$teamid){
	global $_debug;
	if($_debug>3) console("ml_players.Event[$event]('$login',$teamid)");
	ml_playersRefreshPlayersPages();
}


function ml_playersIgnoreListChange($event,$ignorelist){
	global $_debug;
	if($_debug>3) console("ml_players.Event[$event]");
	ml_playersRefreshPlayersPages();
}

    
function ml_playersBanListChange($event,$banlist){
	global $_debug;
	if($_debug>3) console("ml_players.Event[$event]");
	ml_playersRefreshPlayersPages();
}

    
function ml_playersBlackListChange($event,$blacklist){
	global $_debug;
	if($_debug>3) console("ml_players.Event[$event]");
	ml_playersRefreshPlayersPages();
}


function ml_playersPlayerNetInfos($event,$login,$netinfos){
	global $_debug,$_ml_players_samedtimelimit1,$_ml_players_samedtimelimit2;
	return;
	if(($netinfos['SameDTime']>$_ml_players_samedtimelimit1 && $netinfos['SameDTimeOld']<=$_ml_players_samedtimelimit1) ||
		 ($netinfos['SameDTime']>$_ml_players_samedtimelimit2 && $netinfos['SameDTimeOld']<=$_ml_players_samedtimelimit2) ||
		 ($netinfos['SameDTime']<$netinfos['SameDTimeOld'])){
		if($_debug>1) console("ml_players.Event[$event]('$login') ".$netinfos['SameDTime']);
		ml_playersRefreshPlayersPages();
	}
}


function ml_playersPlayerFinish($event,$login,$time){
	// remove preload image manialink
	manialinksRemove($login,'ml_players_preload');
}


function ml_playersPreloadXml($login){
	global $_ml_id;
	// this manialink is use for each player to preload images and
	// is at a not visible position
	// will be removed at first player finish or first player list panel use
	$xml = '<format textsize=1 textcolor=\'0000\'></format>'
		.'<quad pos="0.00 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_play.dds"/>'
		.'<quad pos="0.05 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_playR.dds"/>'
		.'<quad pos="0.10 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_spec.dds"/>'
		.'<quad pos="0.15 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_specforce.dds"/>'
		.'<quad pos="0.20 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_net1.dds"/>'
		.'<quad pos="0.25 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_net2.dds"/>'
		.'<quad pos="0.30 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_noplay.dds"/>'
		.'<quad pos="0.35 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_noplayR.dds"/>'
		.'<quad pos="0.40 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_nospec.dds"/>'
		.'<quad pos="0.45 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_ignore.dds"/>'
		.'<quad pos="0.50 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_kick.dds"/>'
		.'<quad pos="0.55 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_ban.dds"/>'
		.'<quad pos="0.60 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_black.dds"/>'
		.'<quad pos="0.65 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_unignore.dds"/>'
		.'<quad pos="0.70 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_unban.dds"/>'
		.'<quad pos="0.75 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_unblack.dds"/>'
		.'<quad pos="0.80 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_up.dds"/>'
		.'<quad pos="0.85 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_down.dds"/>'
		.'<quad pos="0.90 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_upG.dds"/>'
		.'<quad pos="0.95 0 0" size="0.03 0.03" image="http://slig.free.fr/img/fast_downG.dds"/>';
	manialinksShow($login,'ml_players_preload',$xml,1.0,1.0);
}


function ml_playersInitXmlStrings(){
	global $_ml_act,$_ml_id,$_ml;

	$_ml['ml_players_head'] = '<format textsize=1 textcolor=\'ffdf\'/>'
	.'<frame pos=\'0 0 -0.01\'>'
	.'<quad pos=\'0.01 0 0\' size=\'%0.3f %0.3f\'  style=\'Bgs1\' substyle=\'BgList\'/>';

	$_ml['ml_players_end'] = "\n</frame>";
}


function ml_playersInitXmlPlayerInfo($isadmin,$lw,$lh,$size,$act,$num,$login,&$pl,&$pml){
	global $_ignore_list,$_ban_list,$_black_list,$_ml_players_samedtimelimit1,$_ml_players_samedtimelimit2,$_is_relay;
	$action = ($isadmin && !$_is_relay && !$pl['Relayed']) ? " action=$act" : '';
	$admincolor = verifyAdmin($login) ? ' textcolor=\'afaf\'' : '';
	//$center = ($size-$lh>0.005) ? ' halign=center' : '';
	$back = ($pml['ml_players']['Login']==$login)? " bgcolor='332e'" :'';
	$nimg = 2;
	$iconw = $size + 0.005;

	// compute play/spec icons
	if(!$pl['IsSpectator'] && $pl['Active']){
		if($pl['TeamId']==1)
			$url1 = 'http://slig.free.fr/img/fast_playR.dds';
		else
			$url1 = 'http://slig.free.fr/img/fast_play.dds';
	}else
		$url1 = null;
	if($pl['Active']){
		if(isset($pl['NetInfos']['SameDTime']) && $pl['NetInfos']['SameDTime'] > $_ml_players_samedtimelimit2)
			$url2 = 'http://slig.free.fr/img/fast_net2.dds';
		elseif(isset($pl['NetInfos']['SameDTime']) && $pl['NetInfos']['SameDTime'] > $_ml_players_samedtimelimit1)
			$url2 = 'http://slig.free.fr/img/fast_net1.dds';
		elseif($pl['IsSpectator'] && $pl['Forced'])
			$url2 = 'http://slig.free.fr/img/fast_specforce.dds';
		elseif($pl['IsSpectator'])
			$url2 = 'http://slig.free.fr/img/fast_spec.dds';
		else
			$url2 = null;
	}else{
		if($pl['IsSpectator'])
			$url2 = 'http://slig.free.fr/img/fast_nospec.dds';
		elseif($pl['TeamId']==1)
		 $url2 = 'http://slig.free.fr/img/fast_noplayR.dds';
		else
		 $url2 = 'http://slig.free.fr/img/fast_noplay.dds';
	}

	$pos = 0;
	$ln = 0.024;

	// player background & action
	$info = sprintf("\n <quad pos='0 0.01 0' size='%0.03f %0.3f'%s  style='BgsPlayerCard' substyle='BgCard'/>",$lw,$lh,$action);

	// player num
	$numcolor = ($pl['Relayed']) ? " textcolor='c6f7'" : " textcolor='6f07'";
	$pos += $ln + 0.01;
	$info .= sprintf("\n <label pos='-%0.3f 0 -0.02' size='%0.03f %0.3f'%s halign='center' text='%s'/>",$pos/2+0.003,$ln,$lh,$numcolor,($num%100));

	// play icon
	if($url1)
		$info .= sprintf("\n <quad pos='-%0.3f 0.008 -0.01' size='%0.3f %0.3f' image='%s'/>",$pos,$size,$size,$url1);
	$pos += $iconw;

	// spec icon
	if($url2)
		$info .= sprintf("\n <quad pos='-%0.3f 0.008 -0.01' size='%0.3f %0.3f' image='%s'/>",$pos,$size,$size,$url2);
	$pos += $iconw;

	// special icons
	if(isset($_ignore_list[$login])){
		$info .= sprintf("\n <quad pos='-%0.3f 0.008 -0.01' size='%0.3f %0.3f' image='http://slig.free.fr/img/fast_ignore.dds'/>",$pos,$size,$size);
		$pos += $iconw;
	}
	if(isset($_ban_list[$login])){
		$info .= sprintf("\n <quad pos='-%0.3f 0.008 -0.01' size='%0.3f %0.3f' image='http://slig.free.fr/img/fast_ban.dds'/>",$pos,$size,$size);
		$pos += $iconw;
	}
	if(isset($_black_list[$login])){
		$info .= sprintf("\n <quad pos='-%0.3f 0.008 -0.01' size='%0.3f %0.3f' image='http://slig.free.fr/img/fast_black.dds'/>",$pos,$size,$size);
		$pos += $iconw;
	}

	// player nickname
	if(!isset($pml['ml_players']['Color']) || $pml['ml_players']['Color'])
		$nick = $pl['NickDraw3'];
	else
		$nick = $pl['NickDraw2'];
	$info .= sprintf("\n <label pos='-%0.3f 0 -0.02' size='%0.03f %0.3f' textcolor='ceff' text='%s'/>",$pos,$lw*0.65-$pos,$lh,$nick);

	// player login
	$info .= sprintf("\n <label pos='-%0.3f 0 -0.03' size='%0.03f %0.3f' halign='right'%s text='%s'/>",$lw-0.005,$lw*0.35-0.1,$lh,$admincolor,$pl['Login']);

	return $info;
}


function ml_playersIconXml(&$posx,$size,$action,$image,$text){
	$xml = sprintf("\n <quad pos='-%0.3f 0.008 -0.02' size='%0.3f %0.3f' action='%d' image='%s'/>"
								 ."\n <label pos='-%0.3f 0 -0.03' size='%0.3f 0.026' action='%d' text='\$s %s '/>",
								 $posx,$size,$size,$action,$image,
								 $posx+$size,0.076,$action,$text);
	$posx += $size+0.085;
	return $xml;
}


// action can be 'show', 'refresh', 'remove'
function ml_playersUpdateXml($login,$action='show'){
	global $_debug,$_ml,$_ml_act,$_players,$_GameInfos,$_ignore_list,$_ban_list,$_black_list,$_is_relay;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['ML']['ShowML']) || $_players[$login]['ML']['ShowML']<=0)
		return;
	$pml = &$_players[$login]['ML'];

	// refresh only if opened
	if($action=='refresh' && !manialinksIsOpened($login,'ml_players'))
		return;
	// remove
	if($action=='remove'){
		manialinksRemove($login,'ml_players');
		manialinksRemove($login,'ml_players2');
		return;
	}

	// verify if author is in admin list
	$isadmin = verifyAdmin($login);

	$list = array_keys($_players);

	if(!isset($pml['ml_players']['Sort']) || $pml['ml_players']['Sort']=='ml_playersCompareNickDown'){
		$url1 = 'http://slig.free.fr/img/fast_down.dds';
		$url2 = 'http://slig.free.fr/img/fast_downG.dds';
		$act1 = $_ml_act['ml_players.sort.nickup'];
		$act2 = $_ml_act['ml_players.sort.logindown'];
		usort($list,'ml_playersCompareNickDown');

	}elseif($pml['ml_players']['Sort']=='ml_playersCompareNickUp'){
		$url1 = 'http://slig.free.fr/img/fast_up.dds';
		$url2 = 'http://slig.free.fr/img/fast_upG.dds';
		$act1 = $_ml_act['ml_players.sort.nickdown'];
		$act2 = $_ml_act['ml_players.sort.loginup'];
		usort($list,'ml_playersCompareNickUp');

	}elseif($pml['ml_players']['Sort']=='ml_playersCompareLoginDown'){
		$url1 = 'http://slig.free.fr/img/fast_downG.dds';
		$url2 = 'http://slig.free.fr/img/fast_down.dds';
		$act1 = $_ml_act['ml_players.sort.nickdown'];
		$act2 = $_ml_act['ml_players.sort.loginup'];
		usort($list,'ml_playersCompareLoginDown');

	}else{ //if($pml['ml_players']['Sort']=='ml_playersCompareLoginUp'){
		$url1 = 'http://slig.free.fr/img/fast_upG.dds';
		$url2 = 'http://slig.free.fr/img/fast_up.dds';
		$act1 = $_ml_act['ml_players.sort.nickup'];
		$act2 = $_ml_act['ml_players.sort.logindown'];
		usort($list,'ml_playersCompareLoginUp');
	}

	// for tests
	foreach($_players as &$pl){
	for($i=0;$i<22;$i++)
		$list[] = $pl['Login'];
	}
	$p = end($list);
	while(count($list)<4)
		$list[] = $p;

	// adapatative display
	$lmax = count($list);
	$lw = 0.430;
	if($lmax<=32)    { $size = 0.042; $cmax = 2; $lmax = 16; }
	elseif($lmax<=52){ $size = 0.028; $cmax = 2; $lmax = 26; }
	elseif($lmax<=75){ $size = 0.028; $cmax = 3; $lmax = 26; }
	else{              $size = 0.028; $cmax = 4; $lmax = 26; $lw = 0.370; }

	$lh = $size+0.002;
	$lw += 2*$lh+0.024;
	// manialink header
	$xml = sprintf($_ml['ml_players_head'],$lw*$cmax+0.02,$lmax*$lh+0.2);

	// 1st line : title and other pages
	$xml .= sprintf("\n<label pos='0 -0.01 -0.01' size='%0.03f 0.4' textsize='2' text=' \$s\$ff0%s '/>",
									$lw*$cmax/2,localeText($login,'ml_players.title'));
	// action='{$_ml_act['ml_players.refresh']}'

	// 2nd line : sort and color buttons
	$size2 = 0.028;

	$xml .= sprintf("\n<quad pos='-%0.3f -0.06 -0.01' size='%0.3f %0.3f' action='%d' image='%s'/>",
									 3*$lh+0.010,$size2,$size2,$act1,$url1);
	$xml .= sprintf("\n<quad pos='-%0.3f -0.06 -0.01' size='%0.3f %0.3f' action='%d' image='%s'/>",
									 $lw-$lh,$size2,$size2,$act2,$url2);

	$colorstr = (!isset($pml['ml_players']['Color']) || $pml['ml_players']['Color'])? 'ml_players.nonickcolor' : 'ml_players.nickcolor';
	$xml .= sprintf("\n<label pos='-%0.3f -0.06 -0.01' size='0.2 0.03' halign='right' action='%d' textcolor='cc4f' text=' %s '/>",
									$lw*$cmax,$_ml_act['ml_players.nickcolor'],localeText($login,$colorstr));

	// players list
	for($i=0;$i<$lmax; $i++){

		for($j=0;$j<$cmax;$j++){
			$k = $i + $j*$lmax;
			if(isset($list[$k])){
				$act = $_ml_act['ml_players.'.$k];
				$xml .= sprintf("\n<frame pos='-%0.3f -%0.3f -0.01'>",$j*$lw,$lh*$i+0.1);
				$xml .= ml_playersInitXmlPlayerInfo($isadmin,$lw,$lh,$size,$act,$k+1,''.$list[$k],$_players[$list[$k]],$pml);
				$xml .= "\n</frame>";
			}
		}
	}

	// admin actions on selected player
	if(isset($pml['ml_players']['Login']) && isset($_players[$pml['ml_players']['Login']]) && !$_is_relay){
		//debugPrint("ml_playersBuildXml($login) - [ML][ml_players]",$_players[$login]['ML']['ml_players']);
		$slogin = $pml['ml_players']['Login'];
		$spl = &$_players[$slogin];
		$size2 = 0.042;


		if(!isset($pml['ml_players']['Action']) || $pml['ml_players']['Action']==''){
			// 1st line : select action msg
			$xml .= sprintf("\n<label pos='0 -%0.3f -0.01' size='%0.03f %0.3f' textsize='2' text='%s'/>",
											$lmax*$lh+0.10,$lw*$cmax,$size2,
											localeText($login,'ml_players.choose',$slogin,$spl['NickDraw2'],$spl['NickDraw3']));

		}else{
			// 1st line : confirm action msg and confirm button
			$xml .= sprintf("\n<label pos='0 -%0.3f -0.01' size='%0.03f %0.3f' textsize='2' text='%s'/>",
											$lmax*$lh+0.10,$lw*$cmax,$size2,
											localeText($login,'ml_players.confirm',$slogin,$spl['NickDraw2'],$spl['NickDraw3'],
																 localeText($login,$pml['ml_players']['Action'])));

			$xml .= sprintf("\n<quad pos='-%0.3f -%0.3f -0.05' size='0.20 0.042' halign='center' style='Bgs1' substyle='BgButtonSmall' action='%d'/>"
											."<label pos='-%0.3f -%0.3f -0.06' size='0.18 0.03' halign='center' textsize='1' text='\$333\$o%s'/>",
											$lw*$cmax-0.10,$lmax*$lh+0.11-0.008,$_ml_act['ml_players.confirm'],
											$lw*$cmax-0.10,$lmax*$lh+0.11-0.001,localeText($login,'ml.confirm'));
		}

		// 2nd line : show actions
		$xml .= sprintf("\n<frame pos='0 -%0.3f -0.01'>",$lmax*$lh+0.15);
		$pos = 0;

		if($spl['Active']){
			if($spl['IsSpectator']){
				if($_GameInfos['GameMode'] == 2){
					$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.blue'],'http://slig.free.fr/img/fast_play.dds',localeText($login,'ml_players.blue'));
					$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.red'],'http://slig.free.fr/img/fast_playR.dds',localeText($login,'ml_players.red'));
				}else
					$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.play'],'http://slig.free.fr/img/fast_play.dds',localeText($login,'ml_players.play'));

				if($spl['Forced'])
					$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.spec'],'http://slig.free.fr/img/fast_spec.dds',localeText($login,'ml_players.spec'));
				else
					$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.specforce'],'http://slig.free.fr/img/fast_specforce.dds',localeText($login,'ml_players.specforce'));

			}else{
				if($_GameInfos['GameMode'] == 2 && $spl['TeamId'] == 1)
					$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.blue'],'http://slig.free.fr/img/fast_play.dds',localeText($login,'ml_players.blue'));
				elseif($_GameInfos['GameMode'] == 2 && $spl['TeamId'] == 0)
					$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.red'],'http://slig.free.fr/img/fast_playR.dds',localeText($login,'ml_players.red'));

				$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.spec'],'http://slig.free.fr/img/fast_spec.dds',localeText($login,'ml_players.spec'));
				$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.specforce'],'http://slig.free.fr/img/fast_specforce.dds',localeText($login,'ml_players.specforce'));
			}
		}

		$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.kick'],'http://slig.free.fr/img/fast_kick.dds',localeText($login,'ml_players.kick'));

		if(isset($_ignore_list[$slogin]))
			$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.unignore'],'http://slig.free.fr/img/fast_unignore.dds',localeText($login,'ml_players.unignore'));
		else
			$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.ignore'],'http://slig.free.fr/img/fast_ignore.dds',localeText($login,'ml_players.ignore'));

		if(isset($_ban_list[$slogin]))
			$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.unban'],'http://slig.free.fr/img/fast_unban.dds',localeText($login,'ml_players.unban'));
		else
			$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.ban'],'http://slig.free.fr/img/fast_ban.dds',localeText($login,'ml_players.ban'));

		if(isset($_black_list[$slogin]))
			$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.unblack'],'http://slig.free.fr/img/fast_unblack.dds',localeText($login,'ml_players.unblack'));
		else
			$xml .= ml_playersIconXml($pos,$size2,$_ml_act['ml_players.black'],'http://slig.free.fr/img/fast_black.dds',localeText($login,'ml_players.black'));

		$xml .= sprintf("\n</frame>");


		// 2nd line : quit button
		$xml .= sprintf("\n<quad pos='-%0.3f -%0.3f -0.05' size='0.20 0.042' halign='center' style='Bgs1' substyle='BgButtonSmall' action='%d'/>"
										."<label pos='-%0.3f -%0.3f -0.06' size='0.18 0.03' halign='center' textsize='1' text='\$333\$o%s'/>",
										$lw*$cmax-0.10,$lmax*$lh+0.15-0.008,$_ml_act['ml_players.quit'],
										$lw*$cmax-0.10,$lmax*$lh+0.15-0.001,localeText($login,'ml.quit'));

		// manialink footer and quit
		$xml .= $_ml['ml_players_end'];

	}else{
		// manialink footer and quit
		$xml .= sprintf("\n<quad pos='-%0.3f -%0.3f -0.05' size='0.20 0.042' halign='center' style='Bgs1' substyle='BgButtonSmall' action='%d'/>"
										."<label pos='-%0.3f -%0.3f -0.06' size='0.18 0.03' halign='center' textsize='1' text='\$333\$o%s'/>",
										$lw*$cmax/2,$lmax*$lh+0.15-0.008,$_ml_act['ml_players.quit'],
										$lw*$cmax/2,$lmax*$lh+0.15-0.001,localeText($login,'ml.quit'));

		$xml .= $_ml['ml_players_end'];
	}

	$_players[$login]['ML']['ml_players']['List'] = $list;

	//debugPrint("ml_playersBuildXml($login) - [ML][ml_players]",$_players[$login]['ML']['ml_players']);
	//print_r($pml['ml_players']);

	//echo "PlayerList xml size: ".strlen($xml)."+".strlen($xml2)."\n";
	//echo "$xml\n";

	console("ml_playersUpdateXml:: xml={$xml}");
	manialinksShow($login,'ml_players',$xml,$lw*$cmax/2,0.635);
	//manialinksShow($login,'ml_players2',$xml2,$lw*$cmax/2,0.635);
}


// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function ml_playersCompareNickDown($a, $b){
	global $_players;
	if($_players[$a]['Active'] && !$_players[$b]['Active'])
		return -1;
	elseif(!$_players[$a]['Active'] && $_players[$b]['Active'])
		return 1;
	if(!$_players[$a]['IsSpectator'] && $_players[$b]['IsSpectator'])
		return -1;
	elseif($_players[$a]['IsSpectator'] && !$_players[$b]['IsSpectator'])
		return 1;
	elseif($_players[$a]['IsSpectator'] && $_players[$b]['IsSpectator']){
		if($_players[$a]['Forced'] && !$_players[$b]['Forced'])
			return -1;
		elseif(!$_players[$a]['Forced'] && $_players[$b]['Forced'])
			return 1;
	}
	if($_players[$a]['TeamId']!=$_players[$b]['TeamId'])
		return $_players[$a]['TeamId'] - $_players[$b]['TeamId'];
	return strcmp($_players[$a]['NickDraw2'],$_players[$b]['NickDraw2']);
}

// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function ml_playersCompareNickUp($a, $b){
	global $_players;
	if($_players[$a]['Active'] && !$_players[$b]['Active'])
		return -1;
	elseif(!$_players[$a]['Active'] && $_players[$b]['Active'])
		return 1;
	if(!$_players[$a]['IsSpectator'] && $_players[$b]['IsSpectator'])
		return -1;
	elseif($_players[$a]['IsSpectator'] && !$_players[$b]['IsSpectator'])
		return 1;
	elseif($_players[$a]['IsSpectator'] && $_players[$b]['IsSpectator']){
		if($_players[$a]['Forced'] && !$_players[$b]['Forced'])
			return -1;
		elseif(!$_players[$a]['Forced'] && $_players[$b]['Forced'])
			return 1;
	}
	if($_players[$a]['TeamId']!=$_players[$b]['TeamId'])
		return $_players[$a]['TeamId'] - $_players[$b]['TeamId'];
	return strcmp($_players[$b]['NickDraw2'],$_players[$a]['NickDraw2']);
}

// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function ml_playersCompareLoginDown($a, $b){
	global $_players;
	if($_players[$a]['Active'] && !$_players[$b]['Active'])
		return -1;
	elseif(!$_players[$a]['Active'] && $_players[$b]['Active'])
		return 1;
	if(!$_players[$a]['IsSpectator'] && $_players[$b]['IsSpectator'])
		return -1;
	elseif($_players[$a]['IsSpectator'] && !$_players[$b]['IsSpectator'])
		return 1;
	elseif($_players[$a]['IsSpectator'] && $_players[$b]['IsSpectator']){
		if($_players[$a]['Forced'] && !$_players[$b]['Forced'])
			return -1;
		elseif(!$_players[$a]['Forced'] && $_players[$b]['Forced'])
			return 1;
	}
	if($_players[$a]['TeamId']!=$_players[$b]['TeamId'])
		return $_players[$a]['TeamId'] - $_players[$b]['TeamId'];
	return strcmp(''.$a,''.$b);
}

// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function ml_playersCompareLoginUp($a, $b){
	global $_players;
	if($_players[$a]['Active'] && !$_players[$b]['Active'])
		return -1;
	elseif(!$_players[$a]['Active'] && $_players[$b]['Active'])
		return 1;
	if(!$_players[$a]['IsSpectator'] && $_players[$b]['IsSpectator'])
		return -1;
	elseif($_players[$a]['IsSpectator'] && !$_players[$b]['IsSpectator'])
		return 1;
	elseif($_players[$a]['IsSpectator'] && $_players[$b]['IsSpectator']){
		if($_players[$a]['Forced'] && !$_players[$b]['Forced'])
			return -1;
		elseif(!$_players[$a]['Forced'] && $_players[$b]['Forced'])
			return 1;
	}
	if($_players[$a]['TeamId']!=$_players[$b]['TeamId'])
		return $_players[$a]['TeamId'] - $_players[$b]['TeamId'];
	return strcmp(''.$b,''.$a);
}


// -----------------------------------
// permit to place an image at the asked xpos, but will increase line width, and a little line height
//function ml_playersInitXmlCellPosIcon($pos,$w,$h,$url){
//  if($pos>0){
//	$p = $pos+$w;
//	return "<cell valign=top width=$p><icon width=$w height=$h halign=right>$url</icon></cell>";
//}else
//	return "<cell valign=top><icon width=$w height=$h>$url</icon></cell>";
//}


?>
