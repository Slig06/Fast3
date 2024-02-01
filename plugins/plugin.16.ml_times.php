<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 / 4.0 (First Automatic Server for Trackmania)
// Web:       
// Date:      09.04.2023
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// dependences: need manialinks plugin
//
// defined menus:  'menu.hud.times.menu'
// 
// 
// $_times_default = true; // times panel (contain records etc.)
// $_players[$login]['ML']['Show.times']

registerPlugin('ml_times',16);



//--------------------------------------------------------------
// ask refresh of times panel for all players 
//--------------------------------------------------------------
function ml_timesRefresh($login=true){
	global $_players;
	if($login === true){
		foreach($_players as $login => &$pl){
			if(!is_string($login))
				$login = ''.$login;
			if($pl['ML']['Show.times'] && $pl['ML']['Show.ml_times'] > 0){
				if($pl['Status2'] < 2)
					ml_timesUpdateXml1($login,'refresh');
				else
					ml_timesUpdateXmlF($login,'refresh');
			}
		}
	}else if(isset($_players[$login]['ML']['Show.times']) && $_players[$login]['ML']['Show.times'] && $_players[$login]['ML']['Show.ml_times'] > 0){
		if($_players[$login]['Status2'] < 2)
			ml_timesUpdateXml1($login,'refresh');
		else
			ml_timesUpdateXmlF($login,'refresh');
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function ml_timesAddTimesMod($name,$hook,$data,$priority=10){
	global $_ml_times_mods,$_ml_times_mods_list,$_ml_times_default_mod,$_players;
	if(function_exists($hook) && !isset($_ml_times_mods[$name])){
		$_ml_times_mods[$name] = array('Hook'=>$hook,'Data'=>$data,'Priority'=>$priority);
		uasort($_ml_times_mods,'ml_timesModPosCompare');
		$_ml_times_mods_list = array_reverse(array_keys($_ml_times_mods));

		$_ml_times_default_mod = reset($_ml_times_mods_list);
		foreach($_players as $login => &$player)
			$player['ML']['ml_times.mod'] = $_ml_times_default_mod;
		//debugPrint("ml_timesAddTimesMod ($name,$hook) - default=$_ml_times_default_mod - _ml_times_mods",$_ml_times_mods);

		ml_timesRefresh();
	}
}


//--------------------------------------------------------------
// 
//--------------------------------------------------------------
function ml_timesRemoveTimesMod($name){
	global $_ml_times_mods,$_ml_times_mods_list,$_ml_times_default_mod,$_players;
	if(isset($_ml_times_mods[$name])){
		unset($_ml_times_mods[$name]);
		uasort($_ml_times_mods,'ml_timesModPosCompare');
		$_ml_times_mods_list = array_reverse(array_keys($_ml_times_mods));
		
		$_ml_times_default_mod = reset($_ml_times_mods_list);
		foreach($_players as $login => &$player)
			$player['ML']['ml_times.mod'] = $_ml_times_default_mod;
		//debugPrint("ml_timesRemoveTimesMod ($name) - default=$_ml_times_default_mod - _ml_times_mods",$_ml_times_mods);

		ml_timesRefresh();
	}
}


// -----------------------------------
// compare function for uasort, return -1 if $a should be before $b
function ml_timesModPosCompare($a, $b){
	if($a['Priority']<=$b['Priority'])
		return -1;
	return 1;
}



//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function ml_timesInit($event){
	global $_mldebug,$_ml_times_mods,$_ml_times_mods_list,$_ml_times_default_mod,$_times_default;
	if($_mldebug>3) console("ml_times.Event[$event]");

	$_ml_times_mods = array();
	$_ml_times_mods_list = array();
	$_ml_times_default_mod = '';

	if(!isset($_times_default))
		$_times_default = true;

	for($m = 0; $m < 6; $m++){
		manialinksAddAction('ml_times.1s'.$m);
		manialinksAddAction('ml_times.F'.$m);
	}
	manialinksAddAction('ml_times.1l'); // more cols
	manialinksAddAction('ml_times.1r'); // less colls
	manialinksAddAction('ml_times.1s'); // standard: 3 cols

	manialinksAddAction('ml_times.open');

	manialinksAddId('ml_times.1');
	manialinksAddId('ml_times.3');
	manialinksAddId('ml_times.F');
	manialinksAddId('ml_times.F2');

	ml_timesInitXmlStrings();

	//debugPrint("ml_timesInit - default=$_ml_times_default_mod - _ml_times_mods",$_ml_times_mods);
}


function ml_timesPlayerConnect($event,$login){
	global $_Game,$_players,$_ml_times_default_mod,$_GameInfos,$_times_default;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	//console("ml_times.Event[$event]('$login')");
	$pml = &$_players[$login]['ML'];

	$pml['ml_times.mod'] = $_ml_times_default_mod;

	if(!isset($pml['Show.times']))
		$pml['Show.times'] = $_times_default;

	if(!isset($pml['Show.ml_times']))
		$pml['Show.ml_times'] = 1;

	if(!isset($pml['Show.ml_times.1']))
		$pml['Show.ml_times.1'] = 1;

	if(!isset($pml['Show.ml_times.1.cols'][0]))
		$pml['Show.ml_times.1.cols'][0] = 1;
	if(!isset($pml['Show.ml_times.1.cols'][1]))
		$pml['Show.ml_times.1.cols'][1] = 3;
	
	// if team mode then hide times panel while playing
	if($_GameInfos['GameMode'] == TEAM && $pml['Show.ml_times.1.cols'][0] >= 0)
		$pml['Show.ml_times.1.cols'][0] = -1 - $pml['Show.ml_times.1.cols'][0];


	ml_timesPlayerStatus2Change('PlayerStatus2Change',$login,$_players[$login]['Status2']);
}


function ml_timesPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if($ShowML && $_players[$login]['ML']['Show.times']){
		if($_players[$login]['Status2']<2){
			ml_timesUpdateXmlF($login,'hide');
			ml_timesUpdateXml1($login,'show');
		}else{
			ml_timesUpdateXml1($login,'hide');
			ml_timesUpdateXmlF($login,'show');
		}
	}else{
		ml_timesUpdateXml1($login,'hide');
		ml_timesUpdateXmlF($login,'hide');
	}
}


function ml_timesPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_mldebug,$_Game,$_players,$_ml,$_ml_act,$_ml_times_mods_list;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_players[$login]['ML']))
		return;
	$pml = &$_players[$login]['ML'];
	if($_mldebug>6) console("ml_times.Event[$event]('$login',$answer,$action)");
	$msg = localeText(null,'server_message').localeText(null,'interact');
	$state = ($_players[$login]['Status2'] < 1) ? 0 : 1;

	if($action == 'Show.ml_times'){
		$pml['Show.ml_times'] = ($pml['Show.ml_times'] > 0) ? 0 : 1;
		ml_timesUpdateXml1($login,'hide');
		if($pml['Show.ml_times'] > 0){
			if($pml['Show.ml_times.1'] >= 0){
				ml_timesUpdateXml1($login,'show');
			}
		}
		$msg .= localeText($login,'ml_times.'.(($pml['Show.ml_times']>0)?'show':'hide'));
		addCall(null,'ChatSendToLogin', $msg, $login);
		ml_mainRefresh($login);

	}elseif($action == 'ml_times.1l'){
		if($pml['Show.ml_times.1.cols'][$state] < 6){
			$pml['Show.ml_times.1.cols'][$state]++;
			ml_timesUpdateXml1($login,'refresh');
		}

	}elseif($action == 'ml_times.1r'){
		if($pml['Show.ml_times.1.cols'][$state] > 0){
			$pml['Show.ml_times.1.cols'][$state]--;
			ml_timesUpdateXml1($login,'refresh');
		}

	}elseif($action == 'ml_times.1s'){
		$defaultcols = ($state > 0) ? 3 : 1; // spec: 3, play: 1
		if($pml['Show.ml_times.1.cols'][$state] != $defaultcols){
			$pml['Show.ml_times.1.cols'][$state] = $defaultcols;
			ml_timesUpdateXml1($login,'refresh');
		}

	}elseif($action == 'ml_times.open'){
		$pml['Show.ml_times.1.cols'][$state] = -1 - $pml['Show.ml_times.1.cols'][$state];
		ml_timesUpdateXml1($login,'refresh');

	}else{
		foreach($_ml_times_mods_list as $m => $modname){
			if($action == 'ml_times.1s'.$m){ // select
				$pml['ml_times.mod'] = $modname;
				ml_timesUpdateXml1($login,'refresh');

			}elseif($action == 'ml_times.F'.$m){ // select result table
				$pml['ml_times.mod'] = $modname;
				ml_timesUpdateXmlF($login,'refresh');
			}
		}
	}
}


function ml_timesPlayerMenuBuild($event,$login){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	
	ml_menusAddItem($login, 'menu.hud', 'menu.hud.times', 
									array('Name'=>array(localeText($login,'menu.hud.times.on'),
																			localeText($login,'menu.hud.times.off')),
												'Type'=>'bool',
												'State'=>$_players[$login]['ML']['Show.times']));
	ml_menusAddItem($login, 'menu.hud', 'menu.hud.times.menu', 
									array('Name'=>localeText($login,'menu.hud.times'),
												'Menu'=>array('DefaultStyles'=>true,'Width'=>13,'Items'=>array()),
												'Show'=>$_players[$login]['ML']['Show.times']));
}


function ml_timesPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_players;
	//if($_mldebug>6) console("ml_times.Event[$event]('$login',$action,$state)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	
	$msg = localeText(null,'server_message').localeText(null,'interact');
	if($action=='menu.hud.times'){
		$_players[$login]['ML']['Show.times'] = $state;
		if($state){
			ml_menusShowItem($login, 'menu.hud.times.menu');
			if($_players[$login]['Status2']<2)
				ml_timesUpdateXml1($login,'show');
			else
				ml_timesUpdateXmlF($login,'show');
			$msg .= localeText($login,'chat.hud.times.on');
		}else{
			ml_menusHideItem($login, 'menu.hud.times.menu');
			ml_timesUpdateXml1($login,'hide');
			ml_timesUpdateXmlF($login,'hide');
			$msg .= localeText($login,'chat.hud.times.off');
		}
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}


function ml_timesPlayerStatus2Change($event,$login,$status2){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!$_players[$login]['ML']['ShowML'] || !$_players[$login]['ML']['Show.times'])
		return;
	if($_mldebug>4) console("ml_times.Event[$event]($login,$status2)");
	if($status2<2){
		ml_timesUpdateXmlF($login,'hide');
		ml_timesUpdateXml1($login,'show');
	}else{
		ml_timesUpdateXml1($login,'hide');
		ml_timesUpdateXmlF($login,'show');
	}
}



function	ml_timesInitXmlStrings(){
	global $_ml_act,$_ml_id,$_ml;

	$_ml['times_panel_header'] = "<frame posn='%0.3f %0.3f 10'><format textsize=1/>";
	$_ml['times_panel_bg'] = "<quad sizen='%0.3f %0.3f' posn='0.05 0' style='BgsPlayerCard' substyle='BgCardSystem'/>";
	$_ml['times_panel_open'] = "<quad sizen='2.5 2.5' posn='18.8 2.1 0' style='Icons64x64_1' substyle='Check' action='{$_ml_act['ml_times.open']}'/>";
	$_ml['times_title_sel'] = "<frame posn='0 %0.3f 0.1'><label sizen='12.5 1.7' posn='1.8 0' text='\$ff0\$o%s'/>"
	."<quad sizen='1.6 1.6' posn='0.1 -0.05 0' style='Icons64x64_1' substyle='Close' action='{$_ml_act['ml_times.open']}'/>"
	."<quad sizen='1.6 1.6' posn='16.5 -0.05 0' style='Icons64x64_1' substyle='ArrowPrev' action='{$_ml_act['ml_times.1l']}'/>"
	."<quad sizen='1.6 1.6' posn='19.4 -0.05 0' style='Icons64x64_1' substyle='ArrowNext' action='{$_ml_act['ml_times.1r']}'/>"
	."<label sizen='1.3 1.7' posn='18.8 0' halign='center' action='{$_ml_act['ml_times.1s']}' text='\$s%d'/>"
	."<quad sizen='1.6 1.6' posn='14.5 -0.05 0' style='Icons64x64_1' substyle='%s'/>"
	."</frame>";
	$_ml['times_title_sel2'] = "<frame posn='0 %0.3f 0.1'><label sizen='12.5 1.7' posn='1.8 0' text='\$ff0\$o%s'/>"
	."<quad sizen='1.6 1.6' posn='0.1 -0.05 0' style='Icons64x64_1' substyle='Close' action='{$_ml_act['ml_times.open']}'/>"
	."</frame>";
	$_ml['times_title'] = "<frame posn='0 %0.3f 0.1'><label sizen='12.5 1.7' posn='1.8 0' text='\$ff0\$o%s'/>"
	."<quad sizen='1.6 1.6' posn='0.1 -0.05 0' style='Icons64x64_1' substyle='Close' action='{$_ml_act['ml_times.open']}'/>"
	."<quad sizen='1.6 1.6' posn='19.4 -0.05 0' style='Icons64x64_1' substyle='Check' action='%d'/>"
	."</frame>";
	$_ml['times_cell'] = "<frame posn='%0.3f %0.3f 0.1'><label sizen='3.9 1.7' posn='0.1 0' text='%s'/><label sizen='10.7 1.7' posn='4.5 0' text='%s'/><label sizen='5.7 1.7' posn='20.9 0' halign='right' text='%s'/></frame>";
	$_ml['times_panel_end'] = "</frame>";

}


// main times panel
// action can be 'show', 'refresh', 'hide'
function ml_timesUpdateXml1($login,$action='show'){
	global $_mldebug,$_players,$_ml_act,$_ml,$_ml_times_mods,$_ml_times_mods_list,$_ChallengeInfo;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_players[$login]['ML']))
		return;
	$pml = &$_players[$login]['ML'];
	$state = $_players[$login]['Status2'];

	if($_mldebug>6) console("ml_timesUpdateXml1({$login},{$action}):: state={$state}");

	// hide
	if($action=='hide' || $state>1){
		manialinksHide($login,'ml_times.1');
		manialinksHide($login,'ml_times.3');
		return;
	}
	// refresh only if opened
	if($action == 'refresh' && !manialinksIsOpened($login,'ml_times.1')){
		if($_mldebug>8) console("ml_timesUpdateXml1({$login},{$action}):: ml_times.1 is not opened !");
		return;
	}
	if(!$_players[$login]['ML']['Show.times']){
		if(manialinksIsOpened($login,'ml_times.1')){
			manialinksHide($login,'ml_times.1');
			manialinksHide($login,'ml_times.3');
		}
		return;
	}

	//debugPrint("ml_timesBuildXml1 A- pml",$pml);

	$cols = $pml['Show.ml_times.1.cols'][$state];
	$lines = 0;
	$mdef = -1;
	$times3 = array();

	//debugPrint("ml_timesBuildXml1 - login=$login - state=$state",$state);

	if($cols >= 0){
		// get times part values in $_players[$login]['ML']['ml_times.1'][] arrays
		$times1 = array();
		foreach($_ml_times_mods_list as $m => $modname){
			if($pml['ml_times.mod'] != $modname){
				// add not selected part : 2 lines
				$mod = &$_ml_times_mods[$modname];
				$times1[$m] = call_user_func($mod['Hook'],$login,$mod['Data'],2,2);
				if($times1[$m] === false)
					unset($times1[$m]);
				else{
					$lines += count($times1[$m]);
					$times1[$m]['sel'] = false; // not the selected one
				}
				
			}else{
				// don't add selected part now : will add after
				$mdef = $m;
			}
		}
		if($mdef >= 0){
			// add default/selected part last
			$mod = &$_ml_times_mods[$pml['ml_times.mod']];
			if($pml['Show.ml_times.1.cols'][$state] <= 0){
				// selected is reduced : 2 lines
				$times1[$mdef] = call_user_func($mod['Hook'],$login,$mod['Data'],2,2);
				if($times1[$mdef] === false)
					unset($times1[$mdef]);
				else{
					$lines += count($times1[$mdef]);
					$times1[$mdef]['sel'] = true; // the selected one
				}
				$cols = 0;
				
			}else{
				// selected is not reduced : 6 lines
				// get times part values in $_players[$login]['ML']['ml_times.3'] array
				$times3 = call_user_func($mod['Hook'],$login,$mod['Data'],6*$cols,6);
				
				if($times3 !== false && isset($times3['Name'])){
					// copy last 6 to $times1[$mdef]
					$cols = floor((count($times3)-2)/6);
					for($i = 0; $i < 6; $i++){
						if(isset($times3[$cols*6+$i]))
							$times1[$mdef][$i] = $times3[$cols*6+$i];
						else
							$times1[$mdef][$i] = array('Pos'=>'','Name'=>'','Time'=>'');
					}
					$times1[$mdef]['Name'] = $times3['Name'];
					$lines += count($times1[$mdef]);
					$times1[$mdef]['sel'] = true; // the selected one
					
				}else
					$cols = 0;
			}
		}
	}

	$celly = 0.0;
	$cellh = 1.7;
	$xml = sprintf($_ml['times_panel_header'],43.0,-33.8 + $lines*$cellh);

	//print_r($times1);

	if($lines > 0){
		$xml .= sprintf($_ml['times_panel_bg'],21,$lines*$cellh+0.1);
		foreach($times1 as $m => &$time1){
			if($time1['sel']){
				// selected part
				$xml .= sprintf($_ml['times_title_sel'],$celly,$time1['Name'],
												$pml['Show.ml_times.1.cols'][$state], // num of cols
												(($state > 0) ? 'CameraLocal' : 'IconPlayers'));
			}else{
				// not selected part
				$xml .= sprintf($_ml['times_title'],$celly,$time1['Name'],$_ml_act['ml_times.1s'.$m]);
			}
			$celly -= $cellh;

			for($i = 0; isset($time1[$i]); $i++){
				$xml .= sprintf($_ml['times_cell'],0,$celly,$time1[$i]['Pos'],$time1[$i]['Name'],$time1[$i]['Time']);
				$celly -= $cellh;
			}
		}
	}

	if($cols < 0){
		// panel not visible (no column) : show open button !
		$xml .= $_ml['times_panel_open'];
	}
	$xml .= $_ml['times_panel_end'];

	//debugPrint("ml_timesBuildXml1 B- pml",$pml);
	//debugPrint("ml_timesBuildXml1 - times1",$times1);
	//console($xml);
	
	// main (lower right) panel
	manialinksShow($login,'ml_times.1',$xml);

	// panels on left of main (lower right)
	if($cols > 0 && $times3 !== false)
		ml_timesUpdateXml1times3($login,$times3,$cols);
	else
		manialinksHide($login,'ml_times.3');

	ml_timesUpdateXmlF($login,$action);
}


// call by ml_timesUpdateXml1 if needed for lateral of main panel
function ml_timesUpdateXml1times3($login,&$times3,$cols){
	global $_mldebug,$_ml;
	if($cols<=0 || !isset($times3[0]))
		return ' ';

	$cellh = 1.7;
	$xml = sprintf($_ml['times_panel_header'],43 - 21.3*$cols,-33.8 + 6*$cellh);
	$xml .= sprintf($_ml['times_panel_bg'],21.3*$cols,6*$cellh+0.1);

	for($col = 0; $col < $cols; $col++){
		$celly = 0.0;
		for($i = $col*6; ($i < $col*6+6) && isset($times3[$i]); $i++){
			$xml .= sprintf($_ml['times_cell'],21.3*$col,$celly,$times3[$i]['Pos'],$times3[$i]['Name'],$times3[$i]['Time']);
			$celly -= $cellh;
		}
	}
	$xml .= $_ml['times_panel_end'];

	//debugPrint("ml_timesBuildXml1 - cols=$cols - xml",$xml);
	//debugPrint("ml_timesBuildXml1 - cols=$cols - times3",$times3);
	//console($xml);

	// panels on left of main (lower right)
	manialinksShow($login,'ml_times.3',$xml);
}


// times panel for podium
// action can be 'show', 'refresh', 'hide'
function ml_timesUpdateXmlF($login,$action='show'){
	global $_mldebug,$_players,$_ml_act,$_ml,$_ml_times_mods,$_ml_times_mods_list,$_ml_times_default_mod;;
  if(!is_string($login))
    $login = ''.$login;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_players[$login]['ML']['ShowML']) || !$_players[$login]['ML']['ShowML'])
		return;
	$pml = &$_players[$login]['ML'];
	$state = $_players[$login]['Status2'];

	if($_mldebug>6) console("ml_timesUpdateXmlF({$login},{$action}):: state={$state}");

	// hide
	if($action=='hide' || $state<2){
		manialinksHide($login,'ml_times.F');
		manialinksHide($login,'ml_times.F2');
		return;
	}
	// refresh only if opened
	if($action=='refresh' && !manialinksIsOpened($login,'ml_times.F')){
		if($_mldebug>8) console("ml_timesUpdateXmlF({$login},{$action}):: ml_times.F is not opened !");
		return;
	}
	if(!$_players[$login]['ML']['Show.times']){
		if(manialinksIsOpened($login,'ml_times.1')){
			manialinksHide($login,'ml_times.F');
			manialinksHide($login,'ml_times.F2');
		}
		return;
	}


	if(!isset($pml['ml_times.mod']))
		return '';
	$mod = $pml['ml_times.mod'];
	if(!isset($_ml_times_mods[$mod]['Hook']))
		return '';
	// get up part values in $_players[$login]['ML']['ml_times.F'] array
	$pml['ml_times.F'] = call_user_func($_ml_times_mods[$mod]['Hook'],$login,$_ml_times_mods[$mod]['Data'],30,30);
	if($pml['ml_times.F'] === false)
		return '';
	$timesf = &$pml['ml_times.F'];

	$cellh = 1.7;
	$celly = 0.0;
	$xml = sprintf($_ml['times_panel_header'],-64.2, 28.5);

	// build timesfinal cols
	for($n = 0; $n < 30 && isset($timesf[$n]['Time']); $n++){
		$xml .= sprintf($_ml['times_cell'],0,$celly,$timesf[$n]['Pos'],$timesf[$n]['Name'],$timesf[$n]['Time']);
		$celly -= $cellh;
	}
	$xml .= sprintf($_ml['times_panel_bg'],21.2,$n*$cellh+0.1);
	$xml .= $_ml['times_panel_end'];

	// build titles panel
	$xml2 = '';
	$celly = 0.0;
	$n2 = 0;
	foreach($_ml_times_mods_list as $m => $modname){
		if($pml['ml_times.mod'] != $modname){
			$ttmp = call_user_func($_ml_times_mods[$modname]['Hook'],$login,$_ml_times_mods[$modname]['Data'],0,0);
			$xml2 .= sprintf($_ml['times_title'],$celly,$ttmp['Name'],$_ml_act['ml_times.1s'.$m]);
		}else{
			$xml2 .= sprintf($_ml['times_title_sel2'],$celly,$timesf['Name']);
		}
		$celly -= $cellh;
		$n2++;
	}
	$xml .= sprintf($_ml['times_panel_header'],-57, 29+$n2*$cellh) . $xml2;
	$xml .= sprintf($_ml['times_panel_bg'],21,$n2*$cellh+0.1);
	$xml .= $_ml['times_panel_end'];
	
	//debugPrint("ml_timesBuildXmlF - lines=$n,$n2 - timesf",$timesf);
	//console($xml);

	// podium panel
	manialinksShow($login,'ml_times.F',$xml);
}




?>
