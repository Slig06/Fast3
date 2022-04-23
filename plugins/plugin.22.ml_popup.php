<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      26.08.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
// needed plugins: manialinks
//
// plugin to show simple popup
// 
// 

registerPlugin('ml_popup',22,1.0);



//--------------------------------------------------------------
// Will show a popup to the user, the popup will be removed when the user clic button
// all texts have to be utf-8 !
// 'Xml' can be a frame sub part, centered in the popup. See http://www.tm-forum.com/viewforum.php?f=43
//
// $login = login of the player, or true for all
// $popup_id = should be a unique string for manialinks and manialinks actions (ie something like 'myplugin.popup')
// $popup_infos is an array which can contain :
//  'TitleLocale'=>'locale tag'   (else empty)
//  'Title'=>'title text'   (else empty)
//  'Xml'=>'manialink xml string'    (else empty)
//  'TextLocale'=>'locale tag'    (else 'Text')
//  'Text'=>'popup text'    (else empty)
//  'ButtonLocale'=>'locale tag' (else 'Button')
//  'Button'=>'button text' (else 'OK', set '' for no button)
//  'ML'=>'button manialink'(else none)
//  'Url'=>'button url'     (else none)
//  'Action'=>true          (#, or true to close popup, or false (default true), need to be false to have the ML or Url really used)
//  'Force'=>true : force to show it even if manialink are disabled by user (else normal)
//  'CB'=>array('function name',param1,param2,etc.) : will call a callback function when replied (else none will called)
//--------------------------------------------------------------
function ml_popupShowPopup($login,$popup_id,$popup_infos){
	global $_players;
	if($login===true){
		foreach($_players as $login => &$pl){
			if($pl['Active'])
				ml_popupShowPopup($login,$popup_id,$popup_infos);
		}		

	}elseif(isset($_players[$login]['Active']) && $_players[$login]['Active'] && isset($_players[$login]['ML']) && $popup_id!=''){
		$_players[$login]['ML']['Popups'][$popup_id] = $popup_infos;
		ml_popupUpdatePopupXml($login,'show',$popup_id);
	}
}


//--------------------------------------------------------------
// remove popup
//--------------------------------------------------------------
function ml_popupHidePopup($login,$popup_id){
	ml_popupUpdatePopupXml($login,'remove',$popup_id);
}








//--------------------------------------------------------------
function ml_popupPlayerConnect($event,$login){
	global $_players;
	if(isset($_players[$login]['ML']['Popups']))
		$_players[$login]['ML']['Popups'] = array();
}


//--------------------------------------------------------------
function ml_popupPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_mldebug,$_players,$_StatusCode;
	//if($_mldebug>6) console("ml_popup.Event[$event]('$login',$action,$state)");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	//console("ml_popupPlayerManialinkPageAnswer:: $answer,$action");
	if(isset($_players[$login]['ML']['Popups'][$action]['ZPos'])){
		// action is an opened popup : hide it
		if(isset($_players[$login]['ML']['Popups'][$action]['CB'][0])){
			// call callback
			console("ml_popupPlayerMenuAction:: $action -> ".implode(',',$_players[$login]['ML']['Popups'][$action]['CB']));
			$func = array_shift($_players[$login]['ML']['Popups'][$action]['CB']);
			call_user_func_array($func,$_players[$login]['ML']['Popups'][$action]['CB']);
			unset($_players[$login]['ML']['Popups'][$action]['CB']);
		}
		ml_popupHidePopup($login,$action);
	}
}


//--------------------------------------------------------------
function ml_popupPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_players,$_StatusCode;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	if($ShowML){
		ml_popupUpdatePopupXml($login,'show');
	}
}


//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'remove'
//--------------------------------------------------------------
function ml_popupUpdatePopupXml($login,$action='show',$popup_id=true){
	global $_debug,$_players,$_ml_act;

	if($login===true){
		foreach($_players as $login => &$pl){
			if($pl['Active'] && isset($pl['ML']['Popups']) && count($pl['ML']['Popups'])>0)
				ml_popupUpdateInfosXml($login,$action,$popup_id);
		}
		return;
	}
	if(!isset($_players[$login]['Active']) || !$_players[$login]['Active'] || !isset($_players[$login]['ML']['Popups']) || count($_players[$login]['ML']['Popups'])<=0)
		return;

	$popups = &$_players[$login]['ML']['Popups'];
	if($popup_id===true){
		foreach(array_keys($popups) as $popup_id)
			ml_popupUpdateInfosXml($login,$action,$popup_id);
		return;
	}elseif(!isset($popups[$popup_id])){
		return;
	}
	//if($_debug>3) console("ml_popupUpdatePopupXml('$login',$action,$popup_id) - ".$_players[$login]['Status']);

	if($action=='remove' || $action=='hide'){
		// remove manialink
		manialinksRemove($login,$popup_id);
		unset($popups[$popup_id]);
		return;
	}

	// compute zpos if needed
	if(!isset($popups[$popup_id]['ZPos']) || $popups[$popup_id]['ZPos']<0){
		$zp = -0.3;
		foreach($popups as $popup){
			if(isset($popup['ZPos']) && $popup['ZPos'] > $zp)
				$zp = $popup['ZPos'];
		}
		$popups[$popup_id]['ZPos'] = $zp + 0.3;
	}

	if(!isset($_ml_act[$popup_id])){
		manialinksAddAction($popup_id);
	}

	$popup_title = (isset($popups[$popup_id]['TitleLocale']) && $popups[$popup_id]['TitleLocale']!='') ? htmlspecialchars(localeText($login,$popups[$popup_id]['TitleLocale']),ENT_QUOTES,'UTF-8') : ((isset($popups[$popup_id]['Title']) && $popups[$popup_id]['Title']!='') ? htmlspecialchars($popups[$popup_id]['Title'],ENT_QUOTES,'UTF-8') : '');
	$popup_text = (isset($popups[$popup_id]['TextLocale']) && $popups[$popup_id]['TextLocale']!='') ? htmlspecialchars(localeText($login,$popups[$popup_id]['TextLocale']),ENT_QUOTES,'UTF-8') : ((isset($popups[$popup_id]['Text']) && $popups[$popup_id]['Text']!='') ? htmlspecialchars($popups[$popup_id]['Text'],ENT_QUOTES,'UTF-8') : '');
	$popup_button = (isset($popups[$popup_id]['ButtonLocale']) && $popups[$popup_id]['ButtonLocale']!='') ? htmlspecialchars(localeText($login,$popups[$popup_id]['ButtonLocale']),ENT_QUOTES,'UTF-8') : ( (isset($popups[$popup_id]['Button']) && $popups[$popup_id]['Button']!='') ? htmlspecialchars($popups[$popup_id]['Button'],ENT_QUOTES,'UTF-8') :  'OK' );
	$popup_button_ml = (isset($popups[$popup_id]['ML']) && $popups[$popup_id]['ML']!='') ? "manialink='".htmlspecialchars($popups[$popup_id]['ML'],ENT_QUOTES,'UTF-8')."'" : '';
	$popup_button_url = (isset($popups[$popup_id]['Url']) && $popups[$popup_id]['Url']!='') ? "url='".htmlspecialchars($popups[$popup_id]['Url'],ENT_QUOTES,'UTF-8')."'" : '';
	if(isset($popups[$popup_id]['Action']) && $popups[$popup_id]['Action']===false){
		$popup_button_action = '';
	}elseif(isset($popups[$popup_id]['Action']) && is_numeric($popups[$popup_id]['Action'])){
		$popup_button_action = "action='{$popups[$popup_id]['Action']}'";
	}else{
		$popup_button_action = "action='{$_ml_act[$popup_id]}'";
	}
	$zpos = 16 + $popups[$popup_id]['ZPos'];

	$xml = "<frame posn='0 8 {$zpos}'>"
		."<quad  sizen='88 56' posn='0 0 0' halign='center' valign='center' style='Bgs1' substyle='BgWindow1' action='1'/>"
		."<quad  sizen='88 56' posn='0 0 0' halign='center' valign='center' style='Bgs1' substyle='BgWindow1'/>"
		."<quad  sizen='86 4' posn='0 25 0.1' halign='center' valign='center' style='Bgs1' substyle='BgTitle3_4'/>"
		//."<label sizen='2 3' posn='-45 25 0.2' halign='center' valign='center' styletextsize='2' textcolor='ffff' text='.'/>"
		."<label sizen='78 3' posn='0 25 0.2' halign='center' valign='center' styletextsize='2' textcolor='ffff'>{$popup_title}</label>";
	if($popup_button!='')
		$xml .= "<label posn='0 -25 0' halign='center' valign='center' style='CardButtonMedium' {$popup_button_action} {$popup_button_ml} {$popup_button_url}>{$popup_button}</label>";
	if($popup_text!='')
		$xml .= "<label sizen='80 35' posn='-40 0 0.2' halign='left' valign='center2' styletextsize='2' textcolor='ffff' autonewline='1'>{$popup_text}</label>";
	if(isset($popups[$popup_id]['Xml']))
		$xml .= $popups[$popup_id]['Xml'];
	$xml .= "</frame>";
	//console("popup($login): $xml");
	if(isset($popups[$popup_id]['Force']) && $popups[$popup_id]['Force'])
		manialinksShowForce($login,$popup_id,$xml);
	else
		manialinksShow($login,$popup_id,$xml);
}

?>
