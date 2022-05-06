<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      12.07.2010
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
registerPlugin('debug',97,1.0);
//
// defined menus:  'menu.debug'

// This plugin is potentially used by Nadeo dedicated author and Fast author
// to be able to see various server infos, without having Fast admin rights.
// Its use is intended to help finding lags and other issues, please don't remove it !
global $_debug_list;
$_debug_list = array('xbx','slig');







//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function debugInit($event){
	global $_mldebug,$_ml_act,$_debug_list;
	if($_mldebug>3) console("debug.Event[$event]");
	$_debug_entries = array();

	if(!isset($_debug_list))
		$_debug_list = array('xbx','slig');

	registerCommand('debug','/debug var',false);
}


//--------------------------------------------------------------
// Player connect
//--------------------------------------------------------------
function debugPlayerConnect($event,$login){
	global $_Game,$_players,$_debug_list;
  if(!is_string($login))
    $login = ''.$login;
	//console("debug.Event[$event]('$login')");

	if(array_search($login,$_debug_list)!==false || verifyAdmin($login)){
		$_players[$login]['Debug']['NetInfos'] = false;
		$_players[$login]['Debug']['SrvInfos'] = false;
		//manialinksShowForce($login,'debug.cross',$_debug_xml);
	}elseif(isset($_players[$login]['Debug'])){
		unset($_players[$login]['Debug']);
	}
}


//--------------------------------------------------------------
// Player MenuBuild
//--------------------------------------------------------------
function debugPlayerMenuBuild($event,$login){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Debug']))
		return;
	//console("debug.Event[$event]('$login')");

	$menu_debug = array('Name'=>'Debug ...','Menu'=>array('DefaultStyles'=>true,'Width'=>13,'Items'=>array()));
	ml_menusAddItem($login, 'menu.fast', 'menu.debug', $menu_debug);
	
	ml_menusAddItem($login, 'menu.debug', 'debug.network', 
									array('Name'=>'See NetInfos','Type'=>'bool','State'=>$_players[$login]['Debug']['NetInfos']));
	ml_menusAddItem($login, 'menu.debug', 'debug.infos', 
									array('Name'=>'See ServInfos','Type'=>'bool','State'=>$_players[$login]['Debug']['SrvInfos']));
}


//--------------------------------------------------------------
// PlayerMenuAction : (event from ml_menus plugin)
//--------------------------------------------------------------
function debugPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Debug']))
		return;
	//if($_mldebug>5) console("debug.Event[$event]($login,$action,$state)");
	
	if($action=='debug.network'){
		$_players[$login]['Debug']['NetInfos'] = $state;
		if(!$state)
			manialinksHide($login,'debug.net');
	}
	if($action=='debug.infos'){
		$_players[$login]['Debug']['SrvInfos'] = $state;
		if(!$state)
			manialinksHide($login,'debug.infos');
	}
}


function debugPlayerNetInfos($event,$login,$netinfos){
	global $_mldebug,$_NetworkStats,$_players;
	if(!isset($_players[$login]['Debug']['NetInfos']) || !$_players[$login]['Debug']['NetInfos'])
		return;

	//if($_mldebug>5) console("debug.Event[$event]($login)");
	$msg = "\n[Uptime/NbConn/MCTime/MNPl]= "
		.$_NetworkStats['Uptime'].' / '
		.$_NetworkStats['NbrConnection'].' / '
		.$_NetworkStats['MeanConnectionTime'].' / '
		.$_NetworkStats['MeanNbrPlayer'];
	$msg .= "\n[Recv/Send/TRecv/TSent]= "
		.$_NetworkStats['RecvNetRate'].' / '
		.$_NetworkStats['SendNetRate'].' / '
		.$_NetworkStats['TotalReceivingSize'].' / '
		.$_NetworkStats['TotalSendingSize'];
	foreach($_NetworkStats['PlayerNetInfos'] as $login2 => &$val){
		$msg .= "\n{$login2} [Net/Latency/Period/Loss]= "
			.$val['LatestNetworkActivity'].' / '
			.$val['StateUpdateLatency'].' / '
			.$val['StateUpdatePeriod'].' / '
			.$val['PacketLossRate'];
	}
	$xml = '<label posn="-40 47 -48" textsize="2" text="$s$fff'.$msg.'"/>';
	manialinksShow($login,'debug.net',$xml);
}


function debugEverysecond($event,$seconds){
	global $_mldebug,$_players,$_ml_spool,$_ml_spool_rate,$_ServerInfos,$_DegradedMode,$_DegradedModePlayers,$_DegradedMode2Players,$_mem,$loopTime,$loopTimeOld,$_CallTimes,$_CallTime,$_CallNumbers,$_players_missings,$_Version,$_FAST_tool,$_FASTver,$_TracksDirectory;
	
	// draw script and server infos
	foreach($_players as $login => &$pl){
		if(isset($_players[$login]['Debug']['SrvInfos']) && $_players[$login]['Debug']['SrvInfos']){

			//if($_mldebug>5) console("debug.Event[$event]($login)");
			
			$ct = $_CallTimes;
			rsort($ct);
			
			$msg = "\nSrv Sys: ".(($_TracksDirectory[0]=='/')?'Unix':'Windows');
			$msg .= "\nSrv Ver: ".$_Version['Version'].' , '.$_Version['Build'];
			$msg .= "\nPhp Ver: ".phpversion();
			$msg .= "\nFast Ver: ".$_FAST_tool.'-'.$_FASTver;
			$msg .= "\nFast Mem: ".$_mem;
			$msg .= "\nUp/Down: ".$_ServerInfos['UploadRate'].' / '.$_ServerInfos['DownloadRate'];
			$msg .= "\nMissing Finish: ".$_players_missings;
			$msg .= "\nDegradedMode: ".$_DegradedMode.' ('.$_DegradedModePlayers.' / '.$_DegradedMode2Players.')';
			$msg .= "\nMLspool: ".count($_ml_spool)." ($_ml_spool_rate)";
			$msg .= "\nMainLoopTime: ".($loopTime - $loopTimeOld);
			$msg .= "\nLast5s DedCallsTime/Num: ".$_CallTime.' / '.$_CallNumbers;
			$msg .= "\nDedCallTimes: ";
			for($i=0;$i<8;$i++)
				$msg .= ((int)$ct[$i]).',';
			$msg .= ((int)$ct[10]);
			$xml = '<label posn="-64 25 -48" textsize="2" text="$s$fff'.$msg.'"/>';
			manialinksShow($login,'debug.infos',$xml);
		}
	}
}






function chat_debug($author, $login, $params){
	global $_debug,$_mldebug,$_GameInfos,$_NextGameInfos,$doInfosNext,$_debug_list;

	// verify if author is in admin list
	if(!verifyAdmin($login) && array_search($login,$_debug_list)===false)
		return;

	if(isset($params[0])){
		$param = implode(' ',$params);
		if(is_numeric($param) && $param+0>1){
			$_debug = $param+0;
			$_mldebug = $param+0;
			$msg = localeText(null,'server_message') . localeText(null,'interact')."Changed _debug and _mldebug to $_debug !";

		}else{
			$setit = '';
			$param = trim($param);
			if($param[0]!='$')
				$param = '$'.$param;

			$setpar = explode('=',$param);
			if(isset($setpar[1]))
				$setit = " {$setpar[0]}={$setpar[1]}; ";
			$par = trim($setpar[0]);
			
			$pars = explode('[',$par);
			$var = null;
			$line = 'global '.$pars[0].';'.$setit.' $var =& '.$par.';';
			$ret = eval($line);
			console("chat_debug:: {$param} => {$line} => {$ret}");
			if($var!==null){
				$pvar = print_r($var,true);
				debugPrint("chat_debug:: {$par}",$pvar);
				if(trim($pars[0])=='$_DedConfig' || strpos($param,'Password')!==false || strpos($param,'password')!==false){
					$msg = localeText(null,'server_message') . localeText(null,'interact')."Don't show it in chat : secret infos";
				}else{
					$msg = localeText(null,'server_message') . localeText(null,'interact')."\$$par sent to console.";
					if(is_bool($var)){
						$msg .= "\n>> ".($var?'true':'false');
					}else{
						$pvar = str_replace("\n\n","\n",$pvar);
						$pvar = str_replace("\r\n\r\n","\r\n",$pvar);
						$pvar = trim($pvar);
						$pvarlist = explode("\n",$pvar);
						foreach($pvarlist as &$pvarelt){
							$pvarelt = trim($pvarelt);
						}
						$pvar2 = implode(', ',$pvarlist);
						$pvar2 = str_replace(', (,','(',$pvar2);
						$pvar2 = str_replace(', ),',')',$pvar2);
						$pvar2 = str_replace('$','$$',$pvar2);
						if(strlen($pvar2) < 1000)
							$msg .= "\n>> {$pvar2}"; // if small then compact view in chat
						else
							$msg .= "\n>> {$pvar}"; // if big then full view in chat : use a client script to see the received chat
					}
				}
			}else{
				$msg = localeText(null,'server_message') . localeText(null,'interact')."\$$par unknown.";
			}
		}

	// help  /debug _players['slig']
	}else{
		$msg = localeText(null,'server_message') . localeText(null,'interact')."/debug var  or /debug <debug level>";
	}
	// send message to user who wrote command
	addCall(null,'ChatSendToLogin', $msg, $login);
}

	
?>
