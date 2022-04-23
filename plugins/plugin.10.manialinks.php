<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      15.12.2010
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
registerPlugin('manialinks',10,1.0);

// $_ml_default = true;

// $_players[$login]['ML']['ShowML']



//--------------------------------------------------------------
// get the action value of named one (for action='xx' in manialinks)
//--------------------------------------------------------------
function manialinksGetAction($name){
	global $_ml_act;

	if(!isset($_ml_act[$name]))
		return false;
	return $_ml_act[$name];
}


//--------------------------------------------------------------
// add a general action name and get its value (for action='xx' in manialinks)
//--------------------------------------------------------------
function manialinksAddAction($name){
	global $_ml_act,$_ml_act_rev,$_manialinks_action;

	if(strlen($name) <= 0)
		return false;
	if(!isset($_ml_act[$name])){
		$_ml_act_rev[$_manialinks_action] = $name;
		$_ml_act[$name] = $_manialinks_action++;
	}
	return $_ml_act[$name];
}


//--------------------------------------------------------------
// remove a general action name and value
//--------------------------------------------------------------
function manialinksRemoveAction($name){
	global $_ml_act,$_ml_act_rev;

	if(isset($_ml_act[$name])){
		if(isset($_ml_act_rev[$_ml_act[$name]]))
			unset($_ml_act_rev[$_ml_act[$name]]);
		unset($_ml_act[$name]);
	}
}


//--------------------------------------------------------------
// Get an base value of the specified size for manialink action='xx'
// (and so avoid having 2 plugins using the same values).
// If login is specified then get it specifically for a user.
// Player action values start at 20000, so if you get a general
// value >20000 then it means that some plugin was too hungry :p
//--------------------------------------------------------------
function manialinksGetActionBase($login=null,$size=100){
	global $_manialinks_actionbase;
  if($login !== null && !is_string($login))
    $login = ''.$login;

	if($login === null){
		$_manialinks_actionbase += $size;
		return $_manialinks_actionbase-$size;

	}elseif(isset($_players[$login]['ML']['ActionBase'])){
		$_players[$login]['ML']['ActionBase'] += $size;
		return $_players[$login]['ML']['ActionBase']-$size;
	}
	return false;
}







//--------------------------------------------------------------
// show/add a manialink to draw
// $login:true, apply to all current users
// $login:string, apply to specified user
// $id_name:true, apply on all manialink
// $id_name:string, name id of manialink (see manialinksAddId)
//
// $xml:string, xml manialink data
// $x:float, optional x position (null: unchanged/default-0)
// $y:float, optional y position (null: unchanged/default-0)
// $duration:int, optional duration before hide (ms) (null: unchanged/default-0)
// $autohide:bool, optional hide on action (null: unchanged/default-false)
// Note: X and Y are probably useless for manialinks using frames
//--------------------------------------------------------------
function manialinksShow($login,$id_name,&$xml,$x=null,$y=null,$duration=null,$autohide=null){
	manialinksSet($login,$id_name,'show',$xml,$x,$y,$duration,$autohide);
}


//--------------------------------------------------------------
// show/add a manialink to draw, even if player disabled manialinks !
// (see manialinksShow for parameters)
// Only special cases please ! if the player disabled, it's not to get them...
//--------------------------------------------------------------
function manialinksShowForce($login,$id_name,&$xml=null,$x=null,$y=null,$duration=null,$autohide=null){
	manialinksSet($login,$id_name,'showforce',$xml,$x,$y,$duration,$autohide);
}


//--------------------------------------------------------------
// hide a manialink
// $login:true, apply to all current users
// $login:string, apply to specified user
// $id_name:true, apply on all manialink
// $id_name:string, name id of manialink
//--------------------------------------------------------------
function manialinksHide($login,$id_name){
	manialinksSet($login,$id_name,'hide');
}


//--------------------------------------------------------------
// remove a manialink
// $login:true, apply to all current users
// $login:string, apply to specified user
// $id_name:true, apply on all manialink
// $id_name:string, name id of manialink
//--------------------------------------------------------------
function manialinksRemove($login,$id_name){
	manialinksSet($login,$id_name,'remove');
}


//--------------------------------------------------------------
// true if the asked manialink is opened
//--------------------------------------------------------------
function manialinksIsOpened($login,$id_name){
	global $_mldebug,$_players,$_ml_list;
	if(!isset($_ml_list[$login][$id_name]['Hide']) || !isset($_players[$login]['ML']['ShowML']))
		return false;
	return !$_ml_list[$login][$id_name]['Hide'] && 
		($_players[$login]['ML']['ShowML'] > 0 || $_ml_list[$login][$id_name]['Force']);
}




//--------------------------------------------------------------
// optional $actions is an array of $action_name=>$action_type
function manialinksShowOnRelay($id_name,&$xml,$x=null,$y=null,$actions=null){
	manialinksOnRelay($id_name,'show',false,$xml,$x,$y,$actions);
}

//--------------------------------------------------------------
// optional $actions is an array of $action_name=>$action_type
function manialinksShowForceOnRelay($id_name,&$xml,$x=null,$y=null,$actions=null){
	manialinksOnRelay($id_name,'show',true,$xml,$x,$y,$actions);
}

//--------------------------------------------------------------
// optional $actions is an array of $action_name=>$action_type
function manialinksUpdateOnRelay($id_name,&$xml=null,$x=null,$y=null,$actions=null){
	manialinksOnRelay($id_name,'update',null,$xml,$x,$y,$actions);
}

//--------------------------------------------------------------
// optional $actions is an array of $action_name=>$action_type
function manialinksHideOnRelay($id_name,&$xml=null,$x=null,$y=null,$actions=null){
	manialinksOnRelay($id_name,'hide',false,$xml,$x,$y,$actions);
}

//--------------------------------------------------------------
// optional $actions is an array of $action_name=>$action_type
function manialinksHideForceOnRelay($id_name,&$xml=null,$x=null,$y=null,$actions=null){
	manialinksOnRelay($id_name,'hide',true,$xml,$x,$y,$actions);
}

//--------------------------------------------------------------
function manialinksRemoveOnRelay($id_name){
	manialinksOnRelay($id_name,'remove');
}


//--------------------------------------------------------------
// $action_type can be: 'show','force','hide','showhide','forcehide'
function manialinksAddActionOnRelay($action_name,$id_name,$action_type){
	manialinksActionOnRelay('add',$action_name,$id_name,$action_type);
}

//--------------------------------------------------------------
function manialinksRemoveActionOnRelay($action_name,$id_name){
	manialinksActionOnRelay('remove',$action_name,$id_name);
}


//--------------------------------------------------------------
// Hide hud part (need to have control of it using manialinksGetHudPartControl)
// $plugin:string, name of plugin having control
// $hudpart:string, which can be:
//		'notice', notices
//		'challenge_info', upper right challenge info
//		'chat', chat box
//		'checkpoint_list', bottom right checkpoint list (of first 6 players)
//		'round_scores', no right round score panel at the end of rounds
//		'scoretable', no auto score tables at end of rounds
//		'global', all
//--------------------------------------------------------------
function manialinksHideHudPart($plugin,$hudpart,$login){
	manialinksSetHudPart($plugin,$hudpart,$login,true);
}


//--------------------------------------------------------------
// Show hud part (need to have control of it using manialinksGetHudPartControl)
// (see manialinksHideHudPart for parameters)
//--------------------------------------------------------------
function manialinksShowHudPart($plugin,$hudpart,$login){
	manialinksSetHudPart($plugin,$hudpart,$login,false);
}


//--------------------------------------------------------------
// Get control on hud part, set plugin=true to release control
// (see manialinksHideHudPart for parameters)
//--------------------------------------------------------------
function manialinksGetHudPartControl($plugin,$hudpart){
	global $_mldebug,$_HudControl;
	if(isset($_HudControl[$hudpart])){
		if($_mldebug>1) console("Hud $hudpart controlled by $plugin (was {$_HudControl[$hudpart]})."); 
		$_HudControl[$hudpart] = $plugin;
		if($plugin === true)
			manialinksShowHudPart(true,$hudpart,true);
		else
			manialinksHideHudPart(true,$hudpart,true);

	}else{
		if($_mldebug>0) console("Hud $hudpart unknown !"); 
	}
}


//--------------------------------------------------------------
// Get hud part controller name
// (see manialinksHideHudPart for parameter)
//--------------------------------------------------------------
function manialinksHudPartController($hudpart){
	global $_mldebug,$_HudControl;
	if(isset($_HudControl[$hudpart]))
		return $_HudControl[$hudpart];
	return false;
}








//--------------------------------------------------------------
// add a single manialink id and get its value
// note: each manialink part need an unic id number
//--------------------------------------------------------------
function manialinksAddId($idname){
  global $_ml_id,$_manialinks_id;

  if(!is_string($idname) || strlen($idname) <= 0)
    return false;
  if(!isset($_ml_id[$idname]))
    $_ml_id[$idname] = $_manialinks_id++;
  return $_ml_id[$idname];
}


//--------------------------------------------------------------
// remove a single manialink id name and value
//--------------------------------------------------------------
function manialinksRemoveId($idname){
	global $_ml_id;

	if(isset($_ml_id[$idname]))
		unset($_ml_id[$idname]);
}


//--------------------------------------------------------------
// get a single manialink id number
//--------------------------------------------------------------
function manialinksGetId($idname){
	global $_ml_id;

	if(!isset($_ml_id[$idname]))
		return false;
	return $_ml_id[$idname];
}











//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function manialinksInit($event){
	global $_debug,$_mldebug,$_is_relay,$_mlonrelays,$_actiononrelays,$_ml_list,$_ml_hud,$_ml_spool,$_ml_spool_rate,$_ml_act,$_ml_act_rev,$_ml_id,$_manialinks_actionbase,$_manialinks_action,$_manialinks_id,$_ml_is_on,$_Hud,$_HudControl,$_ml,$_ml_default,$_ServerInfos;

	if($_mldebug>3) console("ml_manialinks.Event[$event]");
	if(!isset($_mldebug))
		$_mldebug = $_debug;
	if(!isset($_ml_is_on))
		$_ml_is_on = true;
	if(!isset($_ml_default))
		$_ml_default = true;

	$_ml_list = array();
	$_ml_hud = array();
	$_ml_spool = array();

	$_mlonrelays = array();
	$_actiononrelays = array();

	// get different id on play server and relay, so play server id as free on relay...
	$_manialinks_action = $_is_relay ? 501 : 101;
	$_manialinks_actionbase = $_is_relay ? 5000 : 1000;
	$_manialinks_id = $_is_relay ? 5501 : 501;
	$_ml_id = array();
	$_ml_act = array();
	$_ml_act_rev = array();  // reverse table for $_ml_act
	manialinksAddAction('ShowML');

	registerCommand('ml','/ml : active manialink infos');

	// set rate value from server upload/download rates ($rate is byte/s)
	$rate = 8000000;
	if($_ServerInfos['DownloadRate']<$rate)
		$rate = $_ServerInfos['DownloadRate'];
	if($_ServerInfos['UploadRate']<$rate)
		$rate = $_ServerInfos['UploadRate'];
	if($rate < 4000)
		$rate = 4000;
	// ~80% of smallest server rate, on 1/5s ticks => max bytes to send at each tick
	$_ml_spool_rate = (int)($rate * 0.80 / 5);

	// store which plugin has control on a hud part
	$_Hud = array('notice'=>true,	// notices
								'challenge_info'=>true,	// upper right challenge info
								'net_infos'=>true, 	// upper left player num ???
								'chat'=>true,	// chat box
								'checkpoint_list'=>true,	 // bottom right checkpoint list (of first 6 players)
								'round_scores'=>true,	// no right round score panel at the end of rounds
								'scoretable'=>true,	// no auto score tables at end of rounds
								'global'=>true,	// all
								);
	$_HudControl = $_Hud;

	$_ml = array(); // used to store various xml strings used in manialinks
}



// -----------------------------------
function manialinksServerStart(){
	//sendToRelay(true,array('MLxml'=>array('Init'=>true)));
}


//--------------------------------------------------------------
// Show master to relay manialinks to login (relay only function !)
//--------------------------------------------------------------
function manialinksShowMLFromMaster($login){
	global $_mldebug,$_is_relay,$_mlonrelays;
	if(!$_is_relay)
		return;
	foreach($_mlonrelays as $id_name => $mlinfo){
		$action = ($_mlonrelays[$id_name]['def'] == 'show' && $_mlonrelays[$id_name]['force']) ? 'showforce' : $_mlonrelays[$id_name]['def'];
		if($_mldebug>5) console("manialinksShowMLFromMaster:: {$login},{$id_name},{$action}");
		manialinksSet($login,$id_name,$action,$_mlonrelays[$id_name]['xml'],$_mlonrelays[$id_name]['x'],$_mlonrelays[$id_name]['y']);
	}
}


//--------------------------------------------------------------
// Fast Infos from master to relay (relay only function !)
//--------------------------------------------------------------
function manialinksDatasFromMaster($event,$data){
	global $_mldebug,$_is_relay,$_mlonrelays,$_actiononrelays,$_ml_act,$_ml_act_rev,$_ml_id;
	if(!$_is_relay)
		return;

	if(isset($data['MLxml']) && count($data['MLxml']) > 0){
		// $data['MLxml'][$id_name] = array('id'=>$id,'def'=>$action,'force'=>$force,'x'=>$x+0,'y'=>$y+0,'xml'=>$xml);

		if($_mldebug>7) console("manialinksDatasFromMaster:: MLxml: ".print_r($data['MLxml'],true));

		if(isset($data['MLxml']['Init'])){
			// init from master : remove all existing
			if($_mldebug>0) console("manialinksDatasFromMaster:: init!");
			foreach($_mlonrelays as $id_name => $mlinfo){
				manialinksSet(true,$id_name,'remove');
			}
			$_mlonrelays = array();
			$_actiononrelays = array();
		}

		foreach($data['MLxml'] as $id_name => $mlinfo){
			if($_mldebug>6) console("manialinksDatasFromMaster:: data[MLxml][{$id_name}]: ".print_r($mlinfo,true));
			$change = false;

			if(!isset($_mlonrelays[$id_name]['id'])){
				// new ml
				if(isset($mlinfo['id']) && isset($mlinfo['def']) && isset($mlinfo['xml'])){
					if($_mldebug>4) console("manialinksDatasFromMaster:: MLxml[{$id_name}]: ".print_r($mlinfo,true));
					if(!isset($mlinfo['force']))
						$mlinfo['force'] = false;
					if(!isset($mlinfo['x']))
						$mlinfo['x'] = 0;
					if(!isset($mlinfo['y']))
						$mlinfo['y'] = 0;
					$_mlonrelays[$id_name] = $mlinfo;
					$_ml_id[$id_name] = $mlinfo['id'];
					$change = true;
				}

			}else{
				if(isset($mlinfo['def']) && $mlinfo['def'] == 'remove'){
					// remove existing
					unset($_mlonrelays[$id_name]);
					manialinksRemove(true,$id_name);
					// remove actions on that ml
					foreach($_actiononrelays as $actname => $actions){
						if(isset($actions[$id_name])){
							unset($_actiononrelays[$actname][$id_name]);
							if(count($_actiononrelays[$actname]) <= 0)
								unset($_actiononrelays[$actname]);
						}
					}

				}else{
					// update existing
					foreach($mlinfo as $tag => $val){
						if(isset($_mlonrelays[$id_name][$tag]) && $_mlonrelays[$id_name][$tag] !== $val){
							$_mlonrelays[$id_name][$tag] = $val;
							$change = true;
						}
					}
				}
			}
			if($change){
				$action = ($_mlonrelays[$id_name]['def'] == 'show' && $_mlonrelays[$id_name]['force']) ? 'showforce' : $_mlonrelays[$id_name]['def'];
				if($_mldebug>6) console("manialinksDatasFromMaster:: ALL,{$id_name},{$action}");
				manialinksSet(true,$id_name,$action,$_mlonrelays[$id_name]['xml'],$_mlonrelays[$id_name]['x'],$_mlonrelays[$id_name]['y']);
			}
		}

	}
	
	if(isset($data['MLact']) && count($data['MLact']) > 0){
		// $data['MLact'][$actname][$mlname] = array('actid'=>$actid,'mlid'=>$mlid,'actiontype'=>$action_type);
		// $action_type is 'show','showforce','hide','hideforce','showhide' (+ 'remove' to remove action)

		if($_mldebug>7) console("manialinksDatasFromMaster:: MLact: ".print_r($data['MLact'],true));

		foreach($data['MLact'] as $actname => $actions){
			if(count($actions) > 0){
				foreach($actions as $mlname => $mlaction){
					if($mlaction['actiontype'] == 'remove'){
						// remove action
						if(isset($_actiononrelays[$actname][$mlname]['actid'])){
							unset($_actiononrelays[$actname][$mlname]);
							if(count($_actiononrelays[$actname]) <= 0)
								unset($_actiononrelays[$actname]);
						}
					}elseif(isset($mlaction['actid'])){
						// add/update action
						if($_mldebug>6) console("manialinksDatasFromMaster:: add action: {$actname}:{$mlaction['actid']} -> {$mlname}:{$mlaction['actiontype']} ".print_r($mlaction,true));
						$_ml_act[$actname] = $mlaction['actid'];
						$_ml_act_rev[$mlaction['actid']] = $actname;
						if(isset($_mlonrelays[$mlname]))
							$_actiononrelays[$actname][$mlname] = $mlaction;
					}
				}
			}
		}
	}
}


//--------------------------------------------------------------
// Get Fast infos for relays (master only function)
//--------------------------------------------------------------
function manialinksGetInfosForRelay($event,$relaylogin,$state){
	global $_debug,$_is_relay,$_RelayInfos,$_mlonrelays,$_actiononrelays;
	if($state == 'init'){
		if(count($_mlonrelays) > 0){
			if($_debug>0) console("manialinksGetInfosForRelay:: give manialinks for relay ({$relaylogin})");
			$_RelayInfos['MLxml'] = $_mlonrelays;
		}
		if(count($_actiononrelays) > 0){
			if($_debug>0) console("manialinksGetInfosForRelay:: give manialinks actions for relay ({$relaylogin})");
			$_RelayInfos['MLact'] = $_actiononrelays;
		}
	}
}


//--------------------------------------------------------------
// Called by playersPlayerChat if a 'CMD:manialink,mlname,xml'
// was received from master (obsolete)
//--------------------------------------------------------------
function manialinksChatMasterManialink($message){
	global $_debug,$_is_relay,$_players;
	if(!$_is_relay)
		return;
	//if($_debug>0) console("match.Event[$event]($login,$data)");
	$datas = explode(',',$message,3);
	//debugPrint("manialinksChatMasterManialink - datas",$datas);
	foreach($_players as $login => &$pl){
		if($pl['Active'] && $pl['IsSpectator'] && !$pl['Relayed']){
			if($datas[2] != '')
				manialinksShow(''.$login,$datas[1],$datas[2]);
			else
				manialinksHide(''.$login,$datas[1]);
		}
	}
}


//--------------------------------------------------------------
function manialinksPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_mldebug,$_Game,$_players,$_ml_act,$_ml_is_on,$_Quiet,$_is_relay,$_actiononrelays,$_mlonrelays;
  if(!is_string($login))
    $login = ''.$login;

	if($answer <= 1 || !isset($_players[$login]['ML']) || !$_ml_is_on || ($_Quiet && !verifyAdmin($login))){
		dropEvent();
		return;
	}

	if($action == 'ShowML'){
		dropEvent();
		chat_ml($login,$login,array());
	}

	if($_mldebug>3) console("manialinksPlayerManialinkPageAnswer({$login},{$answer},{$action})");
	if($_is_relay && isset($_actiononrelays[$action])){
		// relay actions on than ml answer...
		if($_mldebug>8) console("manialinksPlayerManialinkPageAnswer({$login},{$answer},{$action}): relay action !");
		foreach($_actiononrelays[$action] as $mlname => $mlaction){
			if(!isset($_mlonrelays[$mlname]['xml'])){
				// remove action
				unset($_actiononrelays[$action][$mlname]);
				if(count($_actiononrelays[$action]) <= 0){
					unset($_actiononrelays[$action]);
					break;
				}
				continue;
			}
			// $mlaction = array('actid'=>$actid,'mlid'=>$mlid,'actiontype'=>$action_type);
			// $action_type is 'show','showforce','hide','hideforce','showhide'
			$actiontype = $mlaction['actiontype'];
			if($actiontype == 'show'){
				$_mlonrelays[$mlname]['force'] = false;
				manialinksSet($login,$mlname,'show',$_mlonrelays[$mlname]['xml'],$_mlonrelays[$mlname]['x'],$_mlonrelays[$mlname]['y']);
			}elseif($actiontype == 'showforce'){
				$_mlonrelays[$mlname]['force'] = true;
				manialinksSet($login,$mlname,'showforce',$_mlonrelays[$mlname]['xml'],$_mlonrelays[$mlname]['x'],$_mlonrelays[$mlname]['y']);
			}elseif($actiontype == 'hide'){
				$_mlonrelays[$mlname]['hide'] = false;
				manialinksSet($login,$mlname,'hide',$_mlonrelays[$mlname]['xml'],$_mlonrelays[$mlname]['x'],$_mlonrelays[$mlname]['y']);
			}elseif($actiontype == 'hideforce'){
				$_mlonrelays[$mlname]['force'] = true;
				manialinksSet($login,$mlname,'hide',$_mlonrelays[$mlname]['xml'],$_mlonrelays[$mlname]['x'],$_mlonrelays[$mlname]['y']);
			}elseif($actiontype == 'showhide'){
				$act = manialinksIsOpened($login,$mlname) ? 'hide' : ($_mlonrelays[$mlname]['force'] ? 'showforce' : 'show');
				manialinksSet($login,$mlname,$act,$_mlonrelays[$mlname]['xml'],$_mlonrelays[$mlname]['x'],$_mlonrelays[$mlname]['y']);
			}
		}
	}
	//if($_mldebug>3) console("manialinks.Event[$event]('$login',$answer,'$action')");
}


function manialinksPlayerConnect($event,$login){
	global $_mldebug,$_Game,$_players,$_ml_is_on,$_ml_list,$_Hud,$_ml_default;
	if(!$_ml_is_on)
		return;
  if(!is_string($login))
    $login = ''.$login;
	//if($_mldebug>3) console("manialinks.Event[$event]('$login')");

	if(!isset($_players[$login]['ML'])){
		$_players[$login]['ML'] = array('ActionBase'=>20000,'ShowML'=>$_ml_default);
	}
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed']){
		$_players[$login]['ML']['ShowML'] = false;
		return;
	}

	$_players[$login]['ML']['Hud'] = $_Hud;

	$_ml_list[$login] = array();

	// can make problems with multiple scripts... :(
	addCallAsync($login,'SendHideManialinkPageToLogin',$login);
	// delay a complete hud part send, not sure it's usefull...
	addEventDelay(10000,'Function','manialinksSetHudPart',null,true,$login);

	manialinksShowMLFromMaster($login);
}


function manialinksPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'] || !isset($_players[$login]['ML'])){
		dropEvent();
		return;
	}
	if($_mldebug>5) console("manialinks.Event[$event]($login,$ShowML)(".isInteractEvent().')');

	$_players[$login]['ML']['ShowML'] = $ShowML;
	if($ShowML){
		addCallAsync($login,'SendHideManialinkPageToLogin',$login);
		manialinksSet($login,true,'forget');
		manialinksShowMLFromMaster($login);

	}else{
		manialinksSet($login,true,'hidesoft');
	}

	if($event){
		$msg = localeText(null,'server_message').localeText(null,'interact');
		//$msg1 = $msg.localeText($login,'ml_main.'.(($pml['Show.notices']>0)?'show_notices':'hide_notices'));
		//addCallAsync(null,'ChatSendToLogin', $msg1, $login);
		$msg .= localeText($login,'ml_main.'.(($ShowML > 0)?'show_ml':'hide_ml'));
		addCallAsync(null,'ChatSendToLogin', $msg, $login);
	}
}


function manialinksPlayerShowML_Post($event,$login,$ShowML){
	global $_mldebug,$_players,$_Hud;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	//if($_mldebug>0) console("manialinks.Event_Post[$event]($login,$ShowML)");
	// can't send it just after SendHideManialinkPageToLogin because it has no effect on empty manialink page
	if($ShowML)
		manialinksSetHudPart(null,true,$login);
}


function manialinksPlayerDisconnect($event,$login){
	global $_mldebug,$_ml_list,$_players;
	if(!isset($_players[$login]['Login']))
		return;
	
	if(isset($_players[$login]['Relayed']) && $_players[$login]['Relayed'])
		unset($_players[$login]['ML']);
	if(isset($_ml_list[$login]))
		unset($_ml_list[$login]);
}



function manialinksPlayerRemove($event,$login){
	global $_mldebug,$_ml_list;
	if(isset($_ml_list[$login]))
		unset($_ml_list[$login]);
}


//--------------------------------------------------------------
// Everytime : we use a spool/queue to avoid sending too many manialinks at the same time
// $_ml_spool : array of array(0=>time,1=>xmlsize,2=>'login.mlid',3=>addcall_array('SendDisplayManialinkPageToLogin','login','xml',0,false),4=>mlid)
// In case of multidest, 'login.mlid' can be replaced by an array('login.mlid','login.mlid',...)
//--------------------------------------------------------------
function manialinksEverytime_Post($event){
	global $_mldebug,$_Game,$_GameInfos,$_currentTime,$_players,$_ml_hud,$_ml_spool,$_ml_spool_rate,$_ml_is_on,$_multidest_logins;
	if(!$_ml_is_on)
		return;

	// send hud manialinks !...
	while(count($_ml_hud) > 0){
		$data = array_shift($_ml_hud); // login=>array(login,xml)
		$nmerge = 0;
		foreach($_ml_hud as $k => $data2){
			if($data2[1] === $data[1]){
				$nmerge++;
				$data[0] .= ','.$data2[0];
				unset($_ml_hud[$k]);
			}
		}
		if($nmerge > 0){
			if($_mldebug>3) console("manialinksEverytime:: merged ".($nmerge+1)." huds -> {$data[0]}");
		}
		addCallAsync(null,'SendDisplayManialinkPageToLogin',$data[0],$data[1],0,false);
	}
	$_ml_hud = array();


	// send manialinks !...
	$remain = count($_ml_spool);
	if($remain > 0){
		$sizeall = 0;
		for($n=0; $n < $remain; $n++)
			$sizeall += $_ml_spool[$n][1];
		$sizemax = $sizeall / 10 + $_ml_spool_rate;
		
		$nmerge = 0;
		$num = 0;
		$size = 0;
		do{
			$data = array_shift($_ml_spool); // array(time,size,xml)
			// $data : array(0=>time,1=>xmlsize,2=>'login.mlid',3=>addcall_array('SendDisplayManialinkPageToLogin','login','xml',0,false),4=>mlid
			// send
			$num++;
			$size += $data[1];

			if($_multidest_logins){
				// multidest : merge other destinations with same manialink !
				$nmerge = 0;
				$remain = count($_ml_spool);
				for($n=0; $n < $remain; $n++){
					if($_ml_spool[$n][4] == $data[4] && $_ml_spool[$n][1] == $data[1] && $_ml_spool[$n][3][2] === $data[3][2] && 
						 $_ml_spool[$n][3][3] === $data[3][3] && $_ml_spool[$n][3][4] === $data[3][4] ){
						// same manialink to another dest : add him to current
						$nmerge++;
						$data[3][1] .= ','.$_ml_spool[$n][3][1];
						unset($_ml_spool[$n]);
					}
				}
				if($nmerge > 0){
					$remain -= $nmerge;
					if($_mldebug>3) console("manialinksEverytime:: merged ".($nmerge+1)." id:{$data[4]} -> {$data[3][1]}");
				}
			}

			if($_mldebug>6) console("manialinks: {$data[2]} ({$data[1]})");
			addCallArray(null,$data[3],true);
			$data[2] = null;

			$dtime = isset($_ml_spool[0][0]) ? $_currentTime - $_ml_spool[0][0] : 0;
		}while($remain > 0 && ($dtime > 500 || $size < $sizemax));

		if($nmerge > 0){
			// if last on was merged, then need to remake indexes (else array_shift() have already done it)
			// note: I hope that it won't make some memory leaks. If it does, then try to disable merged entries rather than unset...
			$_ml_spool = array_values($_ml_spool);
		}
		
		if($_mldebug>0 && $size > $_ml_spool_rate*2) console("manialinksEverytime - sent: $num,$size(".($size*100/$sizeall)."%),$sizemax");
	}
}


//--------------------------------------------------------------
// $_ml_spool : array of array(0=>time,1=>xmlsize,2=>'login.mlid',3=>addcall_array('SendDisplayManialinkPageToLogin','login','xml',0,false)
// In case of multidest, 'login.mlid' can be replaced by an array('login.mlid'=>'login.mlid','login.mlid'=>'login.mlid',...)
//--------------------------------------------------------------
function manialinksFindSpooled($lid){
	global $_mldebug,$_ml_spool;
	$remain = count($_ml_spool);
	for($n=0; $n < $remain; $n++){
		if($_ml_spool[$n][2] == $lid)
			return $n;
	}
	return false;
}




//--------------------------------------------------------------
// show/hide/remove a manialink
// $login:true, apply to all current users
// $login:string, apply to specified user
// $id_name:true, apply on all manialink
// $id_name:string, name id of manialink (see manialinksAddId)
//
// $action:'remove', remove the manialink (all other will add it if $xml is specified)
// $action:'hide', hide the manialink
// $action:'show', show the manialink
// $action:'update', update the manialink
// $action:'hidesoft', hide the manialink unless if was showforce
// $action:'showforce', show the manialink, even if the player disabled manialinks, only special cases ! 
// $action:'forget', forget the manialink drawn infos whithout hidding (for SendHideManialinkPage case)
//
// $xml:string, xml manialink data
// $x:float, optional x position (null: unchanged/default-0)
// $y:float, optional y position (null: unchanged/default-0)
// $duration:int, optional duration before hide (ms) (null: unchanged/default-0)
// $autohide:bool, optional hide on action (null: unchanged/default-false)
// Note: X and Y are probably useless for manialinks using frames
//--------------------------------------------------------------
function manialinksSet($login,$id_name,$action='show',&$xml=null,$x=null,$y=null,$duration=null,$autohide=null){
	global $_mldebug,$_players,$_ml_is_on,$_ml_spool,$_ml_list,$_currentTime;
	if(!$_ml_is_on)
		return;
  if(!is_string($login) && !is_bool($login))
    $login = ''.$login;
	$id = manialinksAddId($id_name);
	if($id_name !== true && $id === false)
		return;
	
	if(is_string($login) && isset($_players[$login]['Active']) && $_players[$login]['Active']){
		// to player $login
		if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
			return;

		// do nothing if not connected?
		if(!isset($_ml_list[$login]) || !isset($_players[$login]['ML']['ShowML']))
			return;
		if($_mldebug>8) console("manialinksSet - $login,$id_name,$action");
		
		if($id_name === true){
			// apply to all ml id
			foreach($_ml_list[$login] as $idname => &$mlinfo)
				manialinksSet($login,$idname,$action,$xml,$x,$y,$duration,$autohide);
			
		}elseif($id > 0){
			// id_name specified

			if($action == 'update'){
				$action = manialinksIsOpened($login,$id_name) ? ($_ml_list[$login][$id_name]['Force'] ? 'showforce' : 'show') : 'hide';
			}
			// not showforce
			if($action == 'show' && $_players[$login]['ML']['ShowML'] <= 0)
				return;
			$force = false;
			if($action == 'showforce'){
				// showforce
				$force = true;
				$action = 'show';
			}

			if($action == 'show' && is_string($xml)){
				// show
				if(!isset($_ml_list[$login][$id_name])){
					if($_mldebug>8) console("manialinksSet - $login,$id_name,$action,".strlen($xml));
					$_ml_list[$login][$id_name] = array('Id'=>$id,'Crc'=>0,'Size'=>0,'Hide'=>false,'Force'=>false,'X'=>0,'Y'=>0,'Duration'=>0,'AutoHide'=>false);
				}
				// update
				if($x !== null)
					$_ml_list[$login][$id_name]['X'] = $x+0;
				if($y !== null)
					$_ml_list[$login][$id_name]['Y'] = $y+0;
				if($duration !== null)
					$_ml_list[$login][$id_name]['Duration'] = $duration+0;
				if($autohide !== null)
					$_ml_list[$login][$id_name]['AutoHide'] = $autohide == true;
				$_ml_list[$login][$id_name]['Force'] = $force;
				if($_ml_list[$login][$id_name]['Duration'] > 0 || $_ml_list[$login][$id_name]['AutoHide'])
					$_ml_list[$login][$id_name]['Hide'] = true;

				$xml2 = manialinksMakeHeader($_ml_list[$login][$id_name]).$xml.'</manialink>';
				$xmls2 = strlen($xml2);
				$crc2 = crc32($xml2);

				if($_ml_list[$login][$id_name]['Size'] != $xmls2 || $_ml_list[$login][$id_name]['Crc'] != $crc2){
					// show
					if($_mldebug>4){
						if($_ml_list[$login][$id_name]['Size'] == 0)
							console("manialinksSet - $login,$id_name [show]:$id ($xmls2)");
						else
							console("manialinksSet - $login,$id_name [update]:$id ($xmls2)");
					}
					$_ml_list[$login][$id_name]['Size'] = $xmls2;
					$_ml_list[$login][$id_name]['Crc'] = $crc2;
					$_ml_list[$login][$id_name]['Hide'] = false;
					// spool show
					$mlid = $_ml_list[$login][$id_name]['Id'];
					$lid = $login.'.'.$mlid;


					// ***********
					$n = manialinksFindSpooled($lid);
					if($n !== false){
						$_ml_spool[$n][1] = $xmls2;
						$_ml_spool[$n][3] = array('SendDisplayManialinkPageToLogin',$login,$xml2,
																			$_ml_list[$login][$id_name]['Duration'],
																			$_ml_list[$login][$id_name]['AutoHide']);
					}elseif(isInteractEvent()){ // interact : put it first (send it now)
						addCallArray(null,array('SendDisplayManialinkPageToLogin',$login,$xml2,
																			$_ml_list[$login][$id_name]['Duration'],
																			$_ml_list[$login][$id_name]['AutoHide']),true);
					}else{
						$_ml_spool[] = array($_currentTime,$xmls2,$lid,
																 array('SendDisplayManialinkPageToLogin',$login,$xml2,
																			 $_ml_list[$login][$id_name]['Duration'],
																			 $_ml_list[$login][$id_name]['AutoHide']) ,$mlid);
					}
					// ***********



				}else{
					// don't show
					if($_mldebug>4)	console("manialinksSet - $login,$id_name [keep]:$id ($xmls2)");
				}
				
			}elseif(isset($_ml_list[$login][$id_name])){
				// hidesoft
				if($action == 'hidesoft'){
					if(!$_ml_list[$login][$id_name]['Force']) // not forced : hide it
						$action = 'hide';
					else // forced : don't hide but forget previous data
						$_ml_list[$login][$id_name]['Size'] = 0;
				}

				if($action == 'forget'){
					// forget
					$lid = $login.'.'.$_ml_list[$login][$id_name]['Id'];


					// ***********
					$n = manialinksFindSpooled($lid);
					if($n !== false){
						unset($_ml_spool[$n]);
						$_ml_spool = array_values($_ml_spool);
					}
					// ***********

					unset($_ml_list[$login][$id_name]);
				}
				if($action == 'hide'){
					// hide
					if(!$_ml_list[$login][$id_name]['Hide'] || 
						 $_ml_list[$login][$id_name]['AutoHide'] ||
						 $_ml_list[$login][$id_name]['Duration'] > 0){
						// spool hide
						$xml2 = "<manialink id='$id'/>";
						$xmls2 = strlen($xml2);
						if($_mldebug>4) console("manialinksSet - $login,$id_name [hide]:$id ($xmls2)");
						$mlid = $_ml_list[$login][$id_name]['Id'];
						$lid = $login.'.'.$mlid;


						// ***********
						$n = manialinksFindSpooled($lid);
						if($n !== false){
							$_ml_spool[$n][1] = $xmls2;
							$_ml_spool[$n][3] = array('SendDisplayManialinkPageToLogin',$login,$xml2,0,false);
						}elseif(isInteractEvent()){ // interact : put it first (send it now)
							addCallArray(null,array('SendDisplayManialinkPageToLogin',$login,$xml2,0,false),true);
						}else{
							$_ml_spool[] = array($_currentTime,$xmls2,$lid,
																	 array('SendDisplayManialinkPageToLogin',$login,$xml2,0,false) ,$mlid);
						}
						// ***********


						$_ml_list[$login][$id_name]['Hide'] = true;
						$_ml_list[$login][$id_name]['AutoHide'] = false;
						$_ml_list[$login][$id_name]['Duration'] = 0;
						$_ml_list[$login][$id_name]['Size'] = 0;
						$_ml_list[$login][$id_name]['Crc'] = 0;
					}

				}elseif($action == 'remove'){
					// remove
					if(!$_ml_list[$login][$id_name]['Hide'] || 
						 $_ml_list[$login][$id_name]['AutoHide'] || 
						 $_ml_list[$login][$id_name]['Duration'] > 0){
						// spool remove
						$xml2 = "<manialink id='$id'/>";
						$xmls2 = strlen($xml2);
						if($_mldebug>4) console("manialinksSet - $login,$id_name [erase]:$id ($xmls2)");

						$mlid = $_ml_list[$login][$id_name]['Id'];
						$lid = $login.'.'.$mlid;


						// ***********
						$n = manialinksFindSpooled($lid);
						if($n !== false){
							$_ml_spool[$n][1] = $xmls2;
							$_ml_spool[$n][3] = array('SendDisplayManialinkPageToLogin',$login,$xml2,0,false);
						}elseif(isInteractEvent()){ // interact : put it first (send it now)
							addCallArray(null,array('SendDisplayManialinkPageToLogin',$login,$xml2,0,false),true);
						}else{
							$_ml_spool[] = array($_currentTime,$xmls2,$lid,
																	 array('SendDisplayManialinkPageToLogin',$login,$xml2,0,false) ,$mlid);
						}
						// ***********



					}
					unset($_ml_list[$login][$id_name]);
				}
			}
		}

	}elseif($login === true){
		// to all players
		if($_mldebug>3) console("manialinksSet - ALL,$id_name,$action");
		
		// debug
		/*
		if($id_name == 'ml_main'){
			$backtrace = debug_backtrace();
			console("manialinksSet - ALL,$id_name,$action,$x,$y,$duration,$autohide,$xml");
			debugPrint("manialinksSet - ALL,$id_name,$action,$x,$y,$duration,$autohide,$xml - debug_backtrace",$backtrace);
		}
		*/
		foreach($_players as $login2 => &$player){
			if($player['Active'] && !$player['Relayed'])
				manialinksSet($login2,$id_name,$action,$xml,$x,$y,$duration,$autohide);
		}
	}
}


//--------------------------------------------------------------
// make the xml header: <manialink posx='..' posy='..' id='..'>
//--------------------------------------------------------------
function manialinksMakeHeader(&$mlinfo){
	$xml = '<manialink';
	if(isset($mlinfo['X']) && $mlinfo['X'] != 0)
		$xml .= " posx='{$mlinfo['X']}'";
	if(isset($mlinfo['Y']) && $mlinfo['Y'] != 0)
		$xml .= " posy='{$mlinfo['Y']}'";
	$xml .= " id='{$mlinfo['Id']}'>"; // <type>default</type>
	return $xml;
}




//--------------------------------------------------------------
// $action can be: 'show','update','hide','remove'
// optional $actions is an array of $action_name=>$action_type
function manialinksOnRelay($id_name,$action,$force=false,&$xml=null,$x=null,$y=null,$actions=null){
	global $_mldebug,$_mlonrelays,$_actiononrelays,$_ml_act,$_currentTime;
	$mlinfo = array();
	if(isset($_mlonrelays[$id_name]['id'])){
		if($action == 'remove'){
			$mlinfo['MLxml'][$id_name] = array('def'=>$action);

		}else{
			if($_mlonrelays[$id_name]['def'] !== $action){
				$_mlonrelays[$id_name]['def'] = $action;
				$mlinfo['MLxml'][$id_name]['def'] = $_mlonrelays[$id_name]['def'];
			}
			if($force !== null && $_mlonrelays[$id_name]['force'] !== $force){
				$_mlonrelays[$id_name]['force'] = $force;
				$mlinfo['MLxml'][$id_name]['force'] = $_mlonrelays[$id_name]['force'];
			}
			if($x !== null && $_mlonrelays[$id_name]['x'] !== $x){
				$_mlonrelays[$id_name]['x'] = $x+0;
				$mlinfo['MLxml'][$id_name]['x'] = $_mlonrelays[$id_name]['x'];
			}
			if($y !== null && $_mlonrelays[$id_name]['y'] !== $y){
				$_mlonrelays[$id_name]['y'] = $y+0;
				$mlinfo['MLxml'][$id_name]['y'] = $_mlonrelays[$id_name]['y'];
			}
			if($xml !== null && $_mlonrelays[$id_name]['xml'] !== $xml){
				$_mlonrelays[$id_name]['xml'] = $xml;
				$mlinfo['MLxml'][$id_name]['xml'] = $_mlonrelays[$id_name]['xml'];
			}

			if(count($mlinfo) > 0 && ($action == 'show' || $action == 'update') && $_currentTime - $_mlonrelays[$id_name]['time'] > 300000){
				$_mlonrelays[$id_name]['time'] = $_currentTime;
				$mlinfo['MLxml'][$id_name] = $_mlonrelays[$id_name];
			}

			if($actions !== null && count($actions) > 0){
				// actions on ml
				foreach($actions as $actname => $action_type){
					if(isset($_ml_act[$actname])){
						if($action_type == 'remove'){
							if(isset($_actiononrelays[$actname][$id_name]['actid'])){
								$mlinfo['MLact'][$actname][$id_name] = array('actid'=>$_ml_act[$actname],'mlid'=>$_mlonrelays[$id_name]['id'],'actiontype'=>$action_type);
								unset($_actiononrelays[$actname][$id_name]);
								if(count($_actiononrelays[$actname]) <= 0)
									unset($_actiononrelays[$actname]);
							}
						}else{
							if(!isset($_actiononrelays[$actname][$id_name]['actid']) || $_actiononrelays[$actname][$id_name]['actiontype'] != $action_type){
								$_actiononrelays[$actname][$id_name] = array('actid'=>$_ml_act[$actname],'mlid'=>$_mlonrelays[$id_name]['id'],'actiontype'=>$action_type);
								$mlinfo['MLact'][$actname][$id_name] = $_actiononrelays[$actname][$id_name];
							}
						}
					}
				}
			}
		}

	}else{
		if($action == 'show' || $action == 'hide'){
			$id = manialinksAddId($id_name);
			if($force === null)
				$force = false;
			$_mlonrelays[$id_name] = array('id'=>$id,'def'=>$action,'force'=>$force,'x'=>$x+0,'y'=>$y+0,'xml'=>$xml,'time'=>0);
			$mlinfo['MLxml'][$id_name] = $_mlonrelays[$id_name];

			if($actions !== null && count($actions) > 0){
				// actions on ml
				foreach($actions as $actname => $action_type){
					if(isset($_ml_act[$actname])){
						if($action_type == 'remove'){
							if(isset($_actiononrelays[$actname][$id_name]['actid'])){
								$mlinfo['MLact'][$actname][$id_name] = array('actid'=>$_ml_act[$actname],'mlid'=>$_mlonrelays[$id_name]['id'],'actiontype'=>$action_type);
								unset($_actiononrelays[$actname][$id_name]);
								if(count($_actiononrelays[$actname]) <= 0)
									unset($_actiononrelays[$actname]);
							}
						}else{
							if(!isset($_actiononrelays[$actname][$id_name]['actid']) || $_actiononrelays[$actname][$id_name]['actiontype'] != $action_type){
								$_actiononrelays[$actname][$id_name] = array('actid'=>$_ml_act[$actname],'mlid'=>$_mlonrelays[$id_name]['id'],'actiontype'=>$action_type);
								$mlinfo['MLact'][$actname][$id_name] = $_actiononrelays[$actname][$id_name];
							}
						}
					}
				}
			}
		}
	}
	if(count($mlinfo) > 0){
		if($_mldebug>9) console("manialinksOnRelay({$id_name},{$action}):: mlinfo: ".print_r($mlinfo,true));
		sendToRelay(true,$mlinfo);
	}
}


//--------------------------------------------------------------
// $action can be: 'add','remove'
// $action_type can be: 'show','showforce','hide','hideforce','showhide'
// $act_name and $id_name have to already exist !
function manialinksActionOnRelay($action,$act_name,$id_name,$action_type='hide'){
	global $_mldebug,$_mlonrelays,$_actiononrelays,$_ml_act,$_ml_id;
	if(!isset($_ml_act[$act_name]) || !isset($_ml_id[$id_name]) || !isset($_mlonrelays[$id_name]))
		return false;
	if($action == 'add' && $action_type != 'remove'){
		$_actiononrelays[$act_name][$id_name] = array('actid'=>$_ml_act[$act_name],'mlid'=>$_ml_id[$id_name],'actiontype'=>$action_type);
		sendToRelay(true,array('MLact'=>array($act_name=>array($id_name=>$_actiononrelays[$act_name][$id_name]))));

	}elseif(isset($_actiononrelays[$act_name][$id_name]['actid'])){
		$_actiononrelays[$act_name][$id_name]['actiontype'] = 'remove';
		sendToRelay(true,array('MLact'=>array($act_name=>array($id_name=>$_actiononrelays[$act_name][$id_name]))));
		unset($_actiononrelays[$act_name][$id_name]);
		if(count($_actiononrelays[$act_name]) <= 0)
			unset($_actiononrelays[$act_name]);
	}
}


//--------------------------------------------------------------
// Set hud part value (hide/show)
// $plugin:string, name of plugin having control
// $hudpart:string, which can be:
//		'notice', notices
//		'challenge_info', upper right challenge info
//		'chat', chat box
//		'checkpoint_list', bottom right checkpoint list (of first 6 players)
//		'round_scores', no right round score panel at the end of rounds
//		'scoretable', no auto score tables at end of rounds
//		'global', all
//--------------------------------------------------------------
function manialinksSetHudPart($plugin,$hudpart,$login,$hide=false){
	global $_mldebug,$_Hud,$_HudControl,$_players,$_ml_hud;
	if($hudpart !== true && (!isset($_HudControl[$hudpart]) || $_HudControl[$hudpart] != $plugin))
		return;
	if(is_string($login) && isset($_players[$login]['Active']) && $_players[$login]['Active']){
		// specified player
		if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'] || !isset($_players[$login]['ML']))
			return;
		$pml = &$_players[$login]['ML'];
		
		if($hudpart !== true && !isset($pml['Hud'][$hudpart]))
			$pml['Hud'] = $_Hud;
		
		$hud = $pml['Hud'];

		if($hudpart === true){
			// refresh custom_ui
		}elseif(!$hide && !$pml['Hud'][$hudpart]){
			// show hud 
			$pml['Hud'][$hudpart] = true;
			if($_mldebug>5) console("manialinksSetHudPart - $hudpart - show - $login");
			
		}elseif($hide && $pml['Hud'][$hudpart]){
			// hide hud part
			$pml['Hud'][$hudpart] = false;
			if($_mldebug>5) console("manialinksSetHudPart - $hudpart - hide - $login");
			
		}else
			return;

		// make xml with all not visible and the one which change, in original order of tags...
		$xml = '';
		foreach($pml['Hud'] as $part => $visible){
			if(!$visible)
				$xml .= "<{$part} visible='false'/>";
			elseif($hudpart === true || $part == $hudpart)
				$xml .= "<{$part} visible='true'/>";
		}
		if($_mldebug>3) console("manialinksSetHudPart({$hudpart},{$login},{$hide}):: ");
		//console("manialinksSetHudPart({$hudpart},{$login},{$hide}):: BACKTRACE: ".get_backtrace());
		if($_mldebug>6) debugPrint("manialinksSetHudPart - $login - <custom_ui>",$xml);
		//addCallAsync(null,'SendDisplayManialinkPageToLogin',$login,'<custom_ui>'.$xml.'</custom_ui>',0,false);
		$_ml_hud[$login] = array($login,'<custom_ui>'.$xml.'</custom_ui>');

	}elseif($login === true){
		// all players
		if($_mldebug>5) console("manialinksSetHudPart - $hudpart - ".($hide?'hide':'show')." - all");
		foreach($_players as $login2 => &$player){
			manialinksSetHudPart($plugin,$hudpart,$login2,$hide);
		}	
	}
}
	




//---------------------------------------------------
// ml chat command
//---------------------------------------------------
function chat_ml($author, $login, $params){
	global $_mldebug,$_players;
	
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'] || !isset($_players[$login]['ML']['ShowML']))
		return;

	$_players[$login]['ML']['ShowML'] = ($_players[$login]['ML']['ShowML'] > 0)?0:1;
	addEvent('PlayerShowML',$login,$_players[$login]['ML']['ShowML']);
}


?>
