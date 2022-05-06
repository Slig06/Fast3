<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      01.06.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// needed plugins: manialinks
//
// This is a simple plugin showing how manialinks can be used with Fast.
// ml_mapinfo plugin is another rather simple example.
// See dev documentation for more infos.
//
// Just uncomment the registerPlugin() function to make it active

//registerPlugin('ml_howto',98,1.0);


//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function ml_howtoInit($event){
	global $_mldebug,$_ml_howto_force;
	if($_mldebug>0) console("ml_howto.Event[$event]");

	$_ml_howto_force = false;

	// will set a unique action='' value in $_ml_act['ml_howto.quit']
	// see function description in manialinks plugin
	manialinksAddAction('ml_howto.quit');
	manialinksAddAction('ml_howto.force');

	// get a unique manialink id. Use the same name as for 
	// manialinksAddManialink(). It will add its value automatically in <manialink id='xx'>
	manialinksAddId('ml_howto');
}


//--------------------------------------------------------------
// PlayerConnect : (event from server callback)
//--------------------------------------------------------------
function ml_howtoPlayerConnect($event,$login){
	global $_mldebug,$_Game,$_players;
	if($_mldebug>0) console("ml_howto.Event[$event]('$login')");

	ml_howtoUpdateXml($login,'show');
}


//--------------------------------------------------------------
// PlayerShowML : (event from manialink plugin when the player set it on/off)
//--------------------------------------------------------------
function ml_howtoPlayerShowML($event,$login,$ShowML){
	global $_mldebug;
	if($_mldebug>0) console("ml_howto.Event[$event]('$login',$ShowML)");

	if($ShowML>0)
		ml_howtoUpdateXml($login,'show');
}


//--------------------------------------------------------------
// PlayerManialinkPageAnswer : (event from server callback)
//--------------------------------------------------------------
function ml_howtoPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_mldebug,$_ml_howto_force;
	if($_mldebug>0) console("ml_howto.Event[$event]('$login',$answer,$action)");

	if($action=='ml_howto.quit'){
		ml_howtoUpdateXml($login,'remove');

	}elseif($action=='ml_howto.force'){
		$_ml_howto_force = !$_ml_howto_force;
		ml_howtoUpdateXml($login,'show');
	}
}


//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'refresh', 'hide', 'remove'
//--------------------------------------------------------------
function ml_howtoUpdateXml($login,$action='show'){
	global $_mldebug,$_ml_act,$_players,$_ml_howto_force;
	// if the players disabled manialinks then do nothing
	if(!isset($_players[$login]['ML']['ShowML']) || $_players[$login]['ML']['ShowML']<=0)
		return;
	if($_mldebug>0) console("ml_howtoUpdateXml('$login',$action)");

	if($action=='remove'){
		// remove manialink
		manialinksRemove($login,'ml_howto');
		return;

	}elseif($action=='hide'){
		// hide manialink
		manialinksHide($login,'ml_howto');
		return;

	}elseif($action=='refresh' && !manialinksIsOpened($login,'ml_howto')){
		// refresh but not opened: do nothing
		return;
	}

	// show/refresh
	$xml = sprintf('<frame posn="0 10 5">'
								 .'<quad  sizen="60 30" posn="0 0 0" halign="center" valign="center" style="Bgs1" substyle="BgWindow2"/>'
								 .'<quad  sizen="57 5" posn="0 11 0.1" halign="center" valign="center" style="Bgs1" substyle="BgTitle2"/>'
								 .'<label sizen="50 3" posn="0 11 0.11" halign="center" valign="center" style="TextTitle2" text="Example"/>'
								 .'<label sizen="50 3" posn="0 0 0.1" halign="center" style="TextRaceMessage" text="Hello !  :)"/>'
								 .'<label posn="-2 -9 0.1" halign="right" style="CardButtonMedium" action="%d" text="%s"/>'
								 .'<label posn="2 -9 0.1" halign="left" style="CardButtonMedium" action="%d" text="Quit"/>'
								 .'</frame>',
								 $_ml_act['ml_howto.force'],
								 ($_ml_howto_force ? 'Unforce' : 'Force'),
								 $_ml_act['ml_howto.quit']);
	// show manialink
	if($_ml_howto_force)
		manialinksShowForce($login,'ml_howto',$xml);
	else
		manialinksShow($login,'ml_howto',$xml);
}






//--------------------------------------------------------------
// PlayerMenuBuild : (event from ml_menu plugin)
//--------------------------------------------------------------
function ml_howtoPlayerMenuBuild($event,$login){
	global $_mldebug;
	if($_mldebug>0) console("ml_howto.Event[$event]('$login')");

	// add a new menu 'menu.howto' in 'menu.main' :
	$menuitem = array('Name'=>'Howto ...','Menu'=>array('DefaultStyles'=>true,'Width'=>13,'Items'=>array()));
	$menuitem['Menu']['Items']['menu.howto.item1'] = array('Name'=>'Item1','Type'=>'item');
	$menuitem['Menu']['Items']['menu.howto.hide1'] = array('Name'=>'Hide1','Type'=>'hide');
	$menuitem['Menu']['Items']['menu.howto.bool1'] = array('Name'=>'Bool1','Type'=>'bool');
	$menuitem['Menu']['Items']['menu.howto.bool2'] = array('Name'=>array('Bool2:on','Bool2:off'),'Type'=>'bool','State'=>false);
	$menuitem['Menu']['Items']['menu.howto.multi1'] = array('Name'=>array('Multi:1','Multi:2','Multi:3'),'Type'=>'multi');
	$menuitem['Menu']['Items']['menu.howto.menu1'] = array('Name'=>'Menu1','Menu'=>array('Items'=>array()));
	ml_menusAddItem($login, 'menu.main', 'menu.howto', $menuitem);
	// add an item in submenu 'menu.howto.menu1' :
	ml_menusAddItem($login, 'menu.howto.menu1', 'menu.howto.item2', array('Name'=>'Item2','Type'=>'item'));
	ml_menusAddItem($login, 'menu.howto.menu1', 'menu.howto.hide2', array('Name'=>'Hide2','Type'=>'hide'));

	// add also 'menu.howto.bool1' in Hud :
	ml_menusAddItem($login, 'menu.hud', 'menu.howto.bool1', array('Name'=>'Bool1','Type'=>'bool'));



	// make a new menu elsewhere, at bottom right :
	$menu2 = array('Show'=>true,'X'=>64,'Y'=>-48,
								 'Pos'=>'bottom','SubPos'=>'right',
								 'DefaultStyles'=>true,'Width'=>13,'Items'=>array());
	$menu2['Items']['menu.htmenu.item2'] = array('Name'=>'Item2');
	$menu2['Items']['menu.htmenu.item3'] = array('Name'=>'Item3');
	ml_menusNewMenu($login,'menu.htmenu',$menu2);
	// add a submenu, items with same idname are shared ! submenus can't be shared, only simple items !!
	$menuitem2 = array('Name'=>'Howto2 ...','Menu'=>array('DefaultStyles'=>true,'Width'=>13,'Items'=>array()));
	$menuitem2['Menu']['Items']['menu.howto.item1'] = array('Name'=>'Item1','Type'=>'item');
	$menuitem2['Menu']['Items']['menu.howto.hide1'] = array('Name'=>'Hide1','Type'=>'hide');
	$menuitem2['Menu']['Items']['menu.howto2.hide2'] = array('Name'=>'Hide2','Type'=>'hide');
	$menuitem2['Menu']['Items']['menu.howto.bool1'] = array('Name'=>'Bool1','Type'=>'bool');
	$menuitem2['Menu']['Items']['menu.howto.bool2'] = array('Name'=>array('Bool2:on','Bool2:off'),'Type'=>'bool','State'=>false);
	$menuitem2['Menu']['Items']['menu.howto.multi1'] = array('Name'=>array('Multi:1','Multi:2','Multi:3'),'Type'=>'multi');
	$menuitem2['Menu']['Items']['menu.howto2.menu2'] = array('Name'=>'Menu2','Menu'=>array('Items'=>array()));
	$menuitem2['Menu']['Items']['menu.howto2.menu2']['Menu']['Items']['menu.howto2.menu2.item1'] = array('Name'=>'Item1','Type'=>'item');
	ml_menusAddItem($login, 'menu.htmenu', 'menu.howto2', $menuitem2);
	ml_menusAddItem($login, 'menu.howto2.menu2', 'menu.howto2.menu2.item2', array('Name'=>'Item2','Type'=>'item'));
	// show the new menu
	ml_menusShowMenu($login,'menu.htmenu');
}


//--------------------------------------------------------------
// PlayerMenuAction : (event from ml_menus plugin)
//--------------------------------------------------------------
function ml_howtoPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug;
	if($_mldebug>0) console("ml_howto.Event[$event]('$login',$action,$state)");

	if($action=='menu.howto.item1'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action  $state", $login);
	}elseif($action=='menu.howto.hide1'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
	}elseif($action=='menu.howto.bool1'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
	}elseif($action=='menu.howto.bool2'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
	}elseif($action=='menu.howto.multi1'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
	}elseif($action=='menu.howto.menu1'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
	}
}



?>
