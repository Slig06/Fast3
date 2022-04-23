<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      21.06.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// needed plugins: manialinks
//
// menus handling
// 

registerPlugin('ml_menus',11,1.0);


// $_mlmenus[$login][idname] = menu
// menu = array('Show'=>true,'X'=>0,'Y'=>0,'Width'=>15, 'DefaultStyles'=>true,
//              'Style'=>'','StyleOn'=>'','StyleOff'=>'','StyleMenu'=>'','StyleSel'=>'',
//              'Color'=>'','ColorOn'=>'','ColorOff'=>'','ColorMenu'=>'','ColorSel'=>'',
//              'Pos'=>'top'/'center'/'bottom','SubPos'=>'left'/'right',
//              'Items'=>array())
// items[actname] = array('Show'=>true,'Name'=>''/array,'Type'=>'item'/'hide'/'bool'/'multi'/'menu',
//                        'multioffval'=>num,'State'=>true/false/num,'Menu'=>menu)


//--------------------------------------------------------------
// new menu, need a ShowMenu after building it !
//--------------------------------------------------------------
// menu_name is also the manialink id_name of the menu
function ml_menusNewMenu($login,$menu_name,$menu){
	global $_mldebug,$_mlmenus;
	$_mlmenus[$login][$menu_name] = $menu;
	$_mlmenus[$login][$menu_name]['Show'] = false;
	ml_menusMenuVerifyRec($_mlmenus[$login][$menu_name]);
	ml_menusBuildXml($login,$menu_name);
}


//--------------------------------------------------------------
// show menu(s)/submenu(s)
//--------------------------------------------------------------
// usage: ml_menusShowMenu($login, $mname1,...)
//   mname1, nname2, etc. are the names of menus/submenus to show
function ml_menusShowMenu($login, $mname1){
	$args = func_get_args();
	array_shift($args);
	$actions = array();
	foreach($args as $mname)
		$actions[] = array($mname,'menu.show');
	ml_menusSet($login,$actions);
}


//--------------------------------------------------------------
// hide menu(s)/submenu(s)
//--------------------------------------------------------------
// usage: ml_menusHideMenu($login, $mname1, ...)
//   mname1, nname2, etc. are the names of menus/submenus to hide
function ml_menusHideMenu($login, $mname1){
	$args = func_get_args();
	array_shift($args);
	$actions = array();
	foreach($args as $mname)
		$actions[] = array($mname,'menu.hide');
	ml_menusSet($login,$actions);
}


//--------------------------------------------------------------
// set menu infos
//--------------------------------------------------------------
// usage: ml_menusSetMenu($login, $m1infos, ...)
//   m1infos, m2infos, etc. are the infos to change menus
//   m1infos is array($mname,$menu)
//     $mname is the item name (and also the action name for item)
//     $menu is an array of menu infos
function ml_menusSetMenu($login, $m1infos){
	$args = func_get_args();
	array_shift($args);
	$actions = array();
	foreach($args as $minfos)
		$actions[] = array($minfos[0],'menu.set',$minfos[1]);
	ml_menusSet($login,$actions);
}


//--------------------------------------------------------------
// add item(s) in menu
//--------------------------------------------------------------
// usage: ml_menusAddItem($login, $mname, $iname1,$item1, ...)
//   mname is the menu/submenu name where to add the item(s)
//   iname1 is the item name (and also the action name for item)
//   item1 is an array of item infos
function ml_menusAddItem($login, $mname, $iname1,$item1){
	$args = func_get_args();
	array_shift($args);
	array_shift($args);
	$actions = array();
	while(isset($args[0]) && isset($args[1])){
		$args[1]['ActionName'] = $args[0];
		$actions[] = array($mname,'menu.add.item',$args[1]);
		array_shift($args);
		array_shift($args);
	}
	ml_menusSet($login,$actions);
}


//--------------------------------------------------------------
// remove item(s) from menu
//--------------------------------------------------------------
// usage: ml_menusRemoveItem($login, $mname, $iname1, ...)
//   mname is the menu/submenu name where is(are) the item's) to remove
//   iname1 is the item name (and also the action name for item)
function ml_menusRemoveItem($login, $mname, $iname1){
	$args = func_get_args();
	array_shift($args);
	array_shift($args);
	$actions = array();
	foreach($args as $iname)
		$actions[] = array($mname,'menu.remove.item',$iname);
	ml_menusSet($login,$actions);
}


//--------------------------------------------------------------
// show item(s)
//-------------------------------------------------------------
// usage: ml_menusShowItem($login, $iname1, ...)
//   iname1 is the item name (and also the action name for item)
function ml_menusShowItem($login, $iname1){
	$args = func_get_args();
	array_shift($args);
	$actions = array();
	foreach($args as $iname)
		$actions[] = array($iname,'show');
	ml_menusSet($login,$actions);
}


//--------------------------------------------------------------
// hide item(s)
//-------------------------------------------------------------
// usage: ml_menusHideItem($login, $iname1, ...)
//   iname1 is the item name (and also the action name for item)
function ml_menusHideItem($login, $iname1){
	$args = func_get_args();
	array_shift($args);
	$actions = array();
	foreach($args as $iname)
		$actions[] = array($iname,'hide');
	ml_menusSet($login,$actions);
}


//--------------------------------------------------------------
// set item infos
//-------------------------------------------------------------
// usage: ml_menusSetItem($login, $iname1,$item1, ...)
//   iname1 is the item name (and also the action name for item)
//   item1 is an array of item infos
function ml_menusSetItem($login, $iname1,$item1){
	$args = func_get_args();
	array_shift($args);
	$actions = array();
	while(isset($args[0]) && isset($args[1])){
		$actions[] = array($args[0],'set',$args[1]);
		array_shift($args);
		array_shift($args);
	}
	ml_menusSet($login,$actions);
}












//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function ml_menusInit($event){
	global $_mldebug,$_mlmenus,$_mlmenus_act,$_mlmenus_defaultstyles,$_mlmenus_defaultpos;
	if($_mldebug>4) console("ml_menus.Event[$event]");

	$_mlmenus = array();
	$_mlmenus_act = array();

	$_mlmenus_defaultstyles = array('Style'=>'style="Bgs1" substyle="BgCardPlayer"',
																	'StyleOn'=>'style="Bgs1" substyle="BgCardZone"',
																	'StyleOff'=>'style="Bgs1" substyle="BgCardFolder"',
																	'StyleMenu'=>'style="Bgs1" substyle="BgCardChallenge"',
																	'StyleSel'=>'style="Bgs1" substyle="BgCardBuddy"',
																	'Color'=>'$011',
																	'ColorOn'=>'$006',
																	'ColorOff'=>'$555$i',
																	'ColorMenu'=>'$310',
																	'ColorSel'=>'$520$i');
	$_mlmenus_defaultpos = array('Width'=>10,
															 'Pos'=>'left',
															 'SubPos'=>'top');

	manialinksAddId('ml_menus');
}


//--------------------------------------------------------------
// PlayerConnect : 
//--------------------------------------------------------------
function ml_menusPlayerConnect($event,$login){
	global $_mldebug,$_mlmenus,$_players;
	if($_mldebug>6) console("ml_menus.Event[$event]('$login')");
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;

	$_mlmenus[$login] = array();
	insertEvent('PlayerMenuBuild',''.$login);
}


//--------------------------------------------------------------
// PlayerMenuBuild : 
//--------------------------------------------------------------
function ml_menusPlayerMenuBuild($event,$login){
	global $_mldebug,$_mlmenus,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_players[$login]['ML']))
		dropEvent();
	if($_mldebug>6) console("ml_menus.Event[$event]('$login')");

	$_mlmenus[$login] = array();
}


//--------------------------------------------------------------
// PlayerAdminChange : 
//--------------------------------------------------------------
function ml_menusPlayerAdminChange($event,$login,$isadmin){
	global $_mldebug,$_mlmenus,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if($_mldebug>6) console("ml_menus.Event[$event]('$login')");
	insertEvent('PlayerMenuBuild',''.$login);
}


//--------------------------------------------------------------
// PlayerDisconnect : remove player menu infos
//--------------------------------------------------------------
function ml_menusPlayerDisconnect_Post($event,$login){
	global $_mldebug,$_mlmenus;
	if($_mldebug>6) console("ml_menus.Event[$event]('$login')");
	if(isset($_mlmenus[$login]))
		unset($_mlmenus[$login]);
}


//--------------------------------------------------------------
// PlayerRemove : remove player menu infos
//--------------------------------------------------------------
function ml_menusPlayerRemove_Post($event,$login){
	global $_mldebug,$_mlmenus;
	if(isset($_mlmenus[$login]))
		unset($_mlmenus[$login]);
}


//--------------------------------------------------------------
// PlayerShowML : redraw
//--------------------------------------------------------------
function ml_menusPlayerShowML($event,$login,$ShowML){
	global $_mldebug,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if($ShowML){
		insertEvent('PlayerMenuBuild',''.$login);
		ml_menusUpdateXml($login,'show');
	}
}


//--------------------------------------------------------------
// PlayerManialinkPageAnswer : (event from server callback)
//--------------------------------------------------------------
function ml_menusPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_mldebug,$_mlmenus_act,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_mlmenus_act[$action]))
		return;
	//if($_mldebug>6) console("ml_menus.Event[$event]('$login',$answer,$action)");
	dropEvent();
	ml_menusSet($login,array($action,'clic'));
}


//--------------------------------------------------------------
// PlayerMenuAction : (event from ml_menus plugin)
//--------------------------------------------------------------
function ml_menusPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug,$_players,$_mlmenus,$_players;
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_players[$login]['ML']) || !isset($_mlmenus[$login]))
		dropEvent();
	//if($_mldebug>6) console("ml_menus.Event[$event]('$login',$action,$state)");
}






//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide'
//--------------------------------------------------------------
function ml_menusUpdateXml($login,$action='show'){
	global $_mldebug,$_players,$_mlmenus;
	// if the players disabled manialinks then do nothing
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	if(!isset($_players[$login]['ML']['ShowML']) || $_players[$login]['ML']['ShowML']<=0)
		return;
	if(!isset($_mlmenus[$login]) || count($_mlmenus[$login])<=0)
		return;

	if($_mldebug>3) console("ml_menusUpdateXml('$login')");

	if($action=='hide'){
		// hide manialink
		foreach($_mlmenus[$login] as $idname => &$menu){
			manialinksHide($login,$idname);
		}
		return;
	}

	// show/refresh
	foreach($_mlmenus[$login] as $idname => &$menu){
		ml_menusBuildXml($login,$idname);
	}
}








//--------------------------------------------------------------
// modify menu
//--------------------------------------------------------------
// $actions is array of array($name,$action,$tags)
function ml_menusSet($login,$actions){
	global $_mldebug,$_mlmenus,$_players;
	if($login===true){
		foreach($_mlmenus as $plogin => &$mlm)
			ml_menusSet($plogin,$actions);
		return;
	}
	if(!isset($_players[$login]['Relayed']) || $_players[$login]['Relayed'])
		return;
	//if($_mldebug>0) debugPrint("ml_menusSet($login) - 1 - actions",$actions);
	if(!isset($_mlmenus[$login]) || count($_mlmenus[$login])<=0 || !isset($actions[0]))
		return null;
	if(!is_array($actions[0])){
		$actions = array($actions);
		//debugPrint("ml_menusSet($login) - 2 - actions",$actions);
	}
	//if($_mldebug>0) console("ml_menusSet($login) - {$actions[0][0]},{$actions[0][1]}");
	//if($_mldebug>0) debugPrint("ml_menusSet($login) - 2 - actions",$actions);

	$res = array();
	foreach(array_keys($_mlmenus[$login]) as $idname){
		$res['Redraw'] = null;
		$res['Hide'] = false;

		foreach($actions as $natag){
			if(isset($natag[0]) && isset($natag[1])){
				if(!isset($natag[2]))
					$natag[2] = null;
				//debugPrint("ml_menusSet($login) - natag",$natag);
				$res = ml_menusSetRec($_mlmenus[$login][$idname],$res,$login,$idname,$natag[0],$natag[1],$natag[2]);
				//debugPrint("ml_menusSet - $idname - MenuEvent",$res['MenuEvent']);
			}
		}
		//debugPrint("ml_menusSet - $res - _mlmenus[$login][$idname]",$_mlmenus[$login]);

		if($res['Redraw']!==null)
			ml_menusBuildXml($login,$idname);
	}

	if(isset($res['MenuEvent']) && count($res['MenuEvent'])>0){
		//debugPrint("ml_menusSet($login) - res",$res);
		foreach($res['MenuEvent'] as $actname => $state){
			insertEvent('PlayerMenuAction',$login,$actname,$state);
		}
	}
}


//--------------------------------------------------------------
// modify menu 
//--------------------------------------------------------------
// mname: id (for 1st level) or action name of the menu
// idname: id (for 1st level) or action name of the item/menu to deal with
// action: 'set'/'show'/'hide' / 'menu.set'/'menu.show'/'menu.hide'/'menu.add.item'/'menu.remove.item' / 'clic'
// tags: array 
function ml_menusSetRec(&$menu,$res,$login,&$mname,&$idname,&$action,&$tags){
	global $_mldebug;

	if(!isset($res['Redraw']))
		$res['Redraw'] = null;
	if(!isset($res['Hide']))
		$res['Hide'] = false;
	$redraw = $res['Redraw'];

	if($mname==$idname){
		// found : menu

		if($action=='menu.show'){
			// show menu
			if(!$menu['Show']){
				$menu['Show'] = true;
				$redraw = $mname;
			}

		}elseif($action=='menu.hide'){
			// hide menu
			if($menu['Show']){
				$menu['Show'] = false;
				$redraw = $mname;
			}

		}elseif($action=='menu.add.item' && isset($tags['ActionName'])){
			// add item
			//if($_mldebug>0) console("ml_menusSetRec - $login,$mname,$action,{$tags['ActionName']}");
			$actname = $tags['ActionName'];
			$menu['Items'][$actname] = $tags;
			ml_menusMenuVerifyRec($menu);
			$redraw = $mname;
		
		}elseif($action=='menu.remove.item' && is_string($tags) && isset($menu['Items'][$tags])){
			// remove item
			unset($menu['Items'][$tags]);
			$redraw = $mname;
		
		}elseif($action=='menu.set'){
			// update menu values
			if(isset($tags['Show']) && $menu['Show']!=$tags['Show']){
				$menu['Show'] = $tags['Show'];
				$redraw = $mname;
			}
			if(isset($tags['X']) && $menu['X']!=$tags['X']){
				$menu['X'] = $tags['X'];
				$redraw = $mname;
			}
			if(isset($tags['Y']) && $menu['Y']!=$tags['Y']){
				$menu['Y'] = $tags['Y'];
				$redraw = $mname;
			}
			if(isset($tags['Width']) && $menu['Width']!=$tags['Width']){
				$menu['Width'] = $tags['Width'];
				$redraw = $mname;
			}
			if(isset($tags['Pos']) && $menu['Pos']!=$tags['Pos']){
				$menu['Pos'] = $tags['Pos'];
				$redraw = $mname;
			}
			if(isset($tags['SubPos']) && $menu['SubPos']!=$tags['SubPos']){
				$menu['SubPos'] = $tags['SubPos'];
				$redraw = $mname;
			}
			if(isset($tags['Style']) && $menu['Style']!=$tags['Style']){
				$menu['Style'] = $tags['Style'];
				$redraw = $mname;
			}
			if(isset($tags['StyleOn']) && $menu['StyleOn']!=$tags['StyleOn']){
				$menu['StyleOn'] = $tags['StyleOn'];
				$redraw = $mname;
			}
			if(isset($tags['StyleOff']) && $menu['StyleOff']!=$tags['StyleOff']){
				$menu['StyleOff'] = $tags['StyleOff'];
				$redraw = $mname;
			}
			if(isset($tags['StyleMenu']) && $menu['StyleMenu']!=$tags['StyleMenu']){
				$menu['StyleMenu'] = $tags['StyleMenu'];
				$redraw = $mname;
			}
			if(isset($tags['StyleSel']) && $menu['StyleSel']!=$tags['StyleSel']){
				$menu['StyleSel'] = $tags['StyleSel'];
				$redraw = $mname;
			}
			if(isset($tags['Color']) && $menu['Color']!=$tags['Color']){
				$menu['Color'] = $tags['Color'];
				$redraw = $mname;
			}
			if(isset($tags['ColorOn']) && $menu['ColorOn']!=$tags['ColorOn']){
				$menu['ColorOn'] = $tags['ColorOn'];
				$redraw = $mname;
			}
			if(isset($tags['ColorOff']) && $menu['ColorOff']!=$tags['ColorOff']){
				$menu['ColorOff'] = $tags['ColorOff'];
				$redraw = $mname;
			}
			if(isset($tags['ColorMenu']) && $menu['ColorMenu']!=$tags['ColorMenu']){
				$menu['ColorMenu'] = $tags['ColorMenu'];
				$redraw = $mname;
			}
			if(isset($tags['ColorSel']) && $menu['ColorSel']!=$tags['ColorSel']){
				$menu['ColorSel'] = $tags['ColorSel'];
				$redraw = $mname;
			}
			if(isset($tags['Items'])){
				$menu['Items'] = $tags['Items'];
				ml_menusMenuVerifyRec($menu);
				$redraw = $mname;
			}
		}
	}

	if(count($menu['Items'])>0){
		$clicmenu = null;

		// search in menu items
		foreach($menu['Items'] as $actname => &$item){
			$rec = true;

			if($actname==$idname){
				// found : item

				if($action=='clic'){
					// clic on item
					if($item['Type']=='menu'){
						if(isset($res['MenuEvent'][$actname]))
							$state = (bool)$res['MenuEvent'][$actname];
						else
							$state = (bool)!$item['Menu']['Show'];
						$item['Menu']['Show'] = $state;
						$item['State'] = $state;
						if($state){
							$res['Menu'][$actname] = true;
						}
						$clicmenu = $actname;
						$redraw = $mname;
						$res['MenuEvent'][$actname] = $state;

					}elseif($item['Type']=='bool'){
						if(isset($res['MenuEvent'][$actname]))
							$state = (bool)$res['MenuEvent'][$actname];
						else
							$state = (bool)!$item['State'];
						$item['State'] = $state;
						$clicmenu = $mname;
						$redraw = $mname;
						$res['MenuEvent'][$actname] = $state;

					}elseif($item['Type']=='multi'){
						if(isset($res['MenuEvent'][$actname]))
							$state = $res['MenuEvent'][$actname];
						else
							$state = ($item['State']+1) % count($item['Name']);
						$item['State'] = $state;
						$clicmenu = $mname;
						$redraw = $mname;
						$res['MenuEvent'][$actname] = $state;

					}elseif($item['Type']=='item'){
						if(isset($res['MenuEvent'][$actname]))
							$state = $res['MenuEvent'][$actname];
						else
							$state = $item['State'];
						$item['State'] = $state;
						$clicmenu = $mname;
						$res['MenuEvent'][$actname] = $state;

					}else{
						if(isset($res['MenuEvent'][$actname]))
							$state = $res['MenuEvent'][$actname];
						else
							$state = $item['State'];
						$item['State'] = $state;
						$clicmenu = $mname;
						$res['MenuEvent'][$actname] = $state;
						$res['Hide'] = true;
					}
					
				}elseif($action=='show'){
					// show item
					if(!$item['Show']){
						$item['Show'] = true;
						$redraw = $mname;
					}
					
				}elseif($action=='hide'){
					// hide item
					if($item['Show']){
						$item['Show'] = false;
						$redraw = $mname;
					}
					
				}elseif($action=='menu.show' || ($action=='menu.set' && isset($tags['Show']) && $tags['Show'])){
					// highlight item
					if(!$item['State'] && $item['Type']=='menu'){
						$item['State'] = true;
						$redraw = $mname;
					}
					
				}elseif($action=='menu.hide' || ($action=='menu.set' && isset($tags['Show']) && !$tags['Show'])){
					// un-highlight item
					if($item['State'] && $item['Type']=='menu'){
						$item['State'] = false;
						$redraw = $mname;
					}
					
				}elseif($action=='set'){
					// update item value(s)
					if(isset($tags['Show']) && $item['Show']!=$tags['Show']){
						$item['Show'] = $tags['Show'];
						$redraw = $mname;
					}
					if(isset($tags['Name']) && $item['Name']!=$tags['Name']){
						$item['Name'] = $tags['Name'];
						$redraw = $mname;
					}
					if(isset($tags['Type']) && $item['Type']!=$tags['Type']){
						if($tags['Type']!='menu' || isset($tags['Menu'])){
							$item['Type'] = $tags['Type'];
							$redraw = $mname;
							if($item['Type']!='menu' && isset($item['Menu']))
								unset($item['Menu']);
						}
					}
					if(isset($tags['State']) && $item['State']!=$tags['State'] && $item['Type']!='menu'){
						$item['State'] = $tags['State'];
						$redraw = $mname;
					}
					if(is_array($item['Name']) && $item['Type']!='bool'){
						$redraw = $mname;
						$item['Type'] = 'multi';
						if(count($item['Name'])<=0)
							$item['Name'][0] = '-empty-';
						if(!isset($item['State']))
							$item['State'] = 0;
						else{
							$item['State'] += 0;
							if($item['State']<0 || $item['State']>=count($item['Name']))
								$item['State'] = 0;
						}
					}elseif($item['Type']=='multi'){
						$redraw = $mname;
						$item['Type'] = 'item';
					}
					if(isset($tags['Menu'])){
						$item['Type'] = 'menu';
						$item['Menu'] = $tags['Menu'];
						ml_menusMenuVerifyRec($item['Menu']);
						$rec = false;
						$redraw = $mname;
					}
				}
			}

			if($item['Type']=='menu' && $rec){
				// go in submenu
				if($res['Redraw']!==null)
					$redraw = $res['Redraw'];
				$res['Redraw'] = null;
				$res = ml_menusSetRec($item['Menu'],$res,$login,$actname,$idname,$action,$tags);
				//debugPrint("ml_menusSetRec - $actname,$idname - MenuEvent",$res['MenuEvent']);

				if($action=='menu.show' || ($action=='menu.set' && isset($tags['Show']) && $tags['Show'])){
					if($res['Redraw']!==null){
						if($item['State']===false){
							$item['State'] = true;
							$item['Menu']['Show'] = true;
							$redraw = $mname;
						}
					}else{
						if($item['State']!==false){
							$item['State'] = false;
							$item['Menu']['Show'] = false;
							$redraw = $mname;
						}
					}
				}
			}
		}

		// close other menus
		if($clicmenu!==null || $res['Hide']){
			$res['Menu'][$mname] = true;
			foreach($menu['Items'] as $actname => &$item){
				if($item['Type']=='menu'){
					if((($actname!=$clicmenu && !isset($res['Menu'][$actname])) || $res['Hide']) && $item['State']!=false){
						//console("menu: $actname!=$idname , {$res2['Redraw']} , {$item['State']} -> $mname");
						$item['State'] = false;
						$item['Menu']['Show'] = false;
						$redraw = $mname;
					}
				}
			}

		}
	}
	if(!$menu['Show'])
		$res['Redraw'] = null;
	elseif($redraw!==null)
		$res['Redraw'] = $redraw;
	return $res;
}


//--------------------------------------------------------------
// update menu and submenus defaults
//--------------------------------------------------------------
function ml_menusMenuVerifyRec(&$menu){
	global $_mldebug,$_mlmenus_act,$_mlmenus_defaultstyles;
	if(!isset($menu['Show']))
		$menu['Show'] = false;
	if(!isset($menu['X']))
		$menu['X'] = 0;
	if(!isset($menu['Y']))
		$menu['Y'] = 0;
	if(!isset($menu['Items']))
		$menu['Items'] = array();
	if(isset($menu['DefaultStyles']) && $menu['DefaultStyles']){
		foreach($_mlmenus_defaultstyles as $tag => $val){
			if(!isset($menu[$tag]))
				$menu[$tag] = $val;
		}
	}

	if(count($menu['Items'])>0){
		// search in menu items
		foreach($menu['Items'] as $actname => &$item){
			if(is_string($actname) && strlen($actname)>0)
				$_mlmenus_act[$actname] = manialinksAddAction($actname);
			if(!isset($item['Show']))
				$item['Show'] = true;
			if(!isset($item['Name']))
				$item['Name'] = '-empty-';
			if(isset($item['Menu']))
				$item['Type'] = 'menu';
			if(!isset($item['Type']))
				$item['Type'] = 'item';

			if(is_array($item['Name']) && $item['Type']!='bool'){
				$item['Type'] = 'multi';
				if(count($item['Name'])<=0)
					$item['Name'][0] = '-empty-';
				if(!isset($item['State']))
					$item['State'] = 0;
				else{
					$item['State'] += 0;
					if($item['State']<0 || $item['State']>=count($item['Name']))
						$item['State'] = 0;
				}
			}elseif($item['Type']=='multi'){
				$item['Type'] = 'item';
			}

			if($item['Type']=='menu'){
				$item['State'] = false;
				$item['Menu']['Show'] = false;
				ml_menusMenuVerifyRec($item['Menu']);

			}elseif(!isset($item['State']))
				$item['State'] = true;
		}
	}
}


//--------------------------------------------------------------
// Build menu 
//--------------------------------------------------------------
function ml_menusBuildXml($login,$idname){
	global $_mldebug,$_mlmenus,$_ml_act;
	//console("ml_menusBuildXml($login,$idname) - A");

	if(!isset($_mlmenus[$login][$idname]['Show']))
		return;
	//console("ml_menusBuildXml($login,$idname) - B");
	if(!$_mlmenus[$login][$idname]['Show']){
		manialinksHide($login,$idname);
		return;
	}
	//console("ml_menusBuildXml($login,$idname) - C");

	// -35 is the min value to have action working in top left zone...
	$xml = '<format textsize="1"/>'.ml_menusBuildMenuRec($_mlmenus[$login][$idname],false);

	//debugPrint("ml_menusBuildXml($login,$idname) - xml",$xml);
	manialinksShow($login,$idname,$xml);
}


//--------------------------------------------------------------
// Build menu 
//--------------------------------------------------------------
// $_mlmenus[$login][idname] = menu
// menu = array('Show'=>true,'X'=>0,'Y'=>0,'Width'=>15, 'DefaultStyles'=>true,
//              'Style'=>'','StyleOn'=>'','StyleOff'=>'','StyleMenu'=>'','StyleSel'=>'',
//              'Color'=>'','ColorOn'=>'','ColorOff'=>'','ColorMenu'=>'','ColorSel'=>'',
//              'Pos'=>'top'/'center'/'bottom','SubPos'=>'left'/'right',
//              'Items'=>array())
// items[actname] = array('Show'=>true,'Name'=>''/array,'Type'=>'item'/'hide'/'bool'/'multi'/'menu',
//                        'multioffval'=>num,'State'=>true/false/num,'Menu'=>menu)
function ml_menusBuildMenuRec(&$menu,$nz=false,$heritstyles=null){
	global $_mldebug,$_ml_act,$_mlmenus_defaultstyles,$_mlmenus_defaultpos;
	$first = $nz===false;
	if($nz===false)
		$nz = 5;
	if($heritstyles===null)
		$heritstyles = array_merge($_mlmenus_defaultstyles,$_mlmenus_defaultpos);

	$heritstyles['Style'] = isset($menu['Style']) ? $menu['Style'] : $heritstyles['Style'];
	$heritstyles['StyleOn'] = isset($menu['StyleOn']) ? $menu['StyleOn'] : $heritstyles['StyleOn'];
	$heritstyles['StyleOff'] = isset($menu['StyleOff']) ? $menu['StyleOff'] : $heritstyles['StyleOff'];
	$heritstyles['StyleMenu'] = isset($menu['StyleMenu']) ? $menu['StyleMenu'] : $heritstyles['StyleMenu'];
	$heritstyles['StyleSel'] = isset($menu['StyleSel']) ? $menu['StyleSel'] : $heritstyles['StyleSel'];
	$heritstyles['Color'] = isset($menu['Color']) ? $menu['Color'] : $heritstyles['Color'];
	$heritstyles['ColorOn'] = isset($menu['ColorOn']) ? $menu['ColorOn'] : $heritstyles['ColorOn'];
	$heritstyles['ColorOff'] = isset($menu['ColorOff']) ? $menu['ColorOff'] : $heritstyles['ColorOff'];
	$heritstyles['ColorMenu'] = isset($menu['ColorMenu']) ? $menu['ColorMenu'] : $heritstyles['ColorMenu'];
	$heritstyles['ColorSel'] = isset($menu['ColorSel']) ? $menu['ColorSel'] : $heritstyles['ColorSel'];

	$heritstyles['Width'] = isset($menu['Width']) ? $menu['Width'] : $heritstyles['Width'];
	$heritstyles['Pos'] = isset($menu['Pos']) ? $menu['Pos'] : $heritstyles['Pos'];
	$heritstyles['SubPos'] = isset($menu['SubPos']) ? $menu['SubPos'] : $heritstyles['SubPos'];

	$y = 0;
	$h = 1.85;

	$xml = '';
	foreach($menu['Items'] as $act => &$item){
		if($item['Show']){

			if(!is_array($item['Name']))
				$name = $item['Name'];
			elseif(isset($item['Name'][0]))
				$name = $item['Name'][0];
			else
				$name = '!bad value!';

			$double = false;
			if($item['Type']=='menu'){
				if($item['State']!==false){ // menu opened
					$style = $heritstyles['StyleMenu'];
					$double = $heritstyles['StyleSel'];
					$color = $heritstyles['ColorSel'];
				}else{ // menu closed
					$style = $heritstyles['StyleMenu'];
					$color = $heritstyles['ColorMenu'];
				}

			}elseif($item['Type']=='bool'){
				if($item['State']!==false){ // bool: on
					if($heritstyles['StyleOn']==''){
						$style = $heritstyles['Style'];
					}else
						$style = $heritstyles['StyleOn'];
					$color = $heritstyles['ColorOn'];

				}else{ // bool: off
					if(is_array($item['Name']) && isset($item['Name'][1]))
						$name = $item['Name'][1];
					if($heritstyles['StyleOff']==''){
						$style = $heritstyles['Style'];
						$double = $heritstyles['Style'];
					}else
						$style = $heritstyles['StyleOff'];
					$color = $heritstyles['ColorOff'];
				}

			}elseif($item['Type']=='multi'){
				if(!isset($item['Name'][$item['State']]))
					$name = '!bad value!';
				else
					$name = $item['Name'][$item['State']];
				if(isset($item['multioffval']) && $item['multioffval']==$item['State']){
					// multi value is off
					if($heritstyles['StyleOff']==''){
						$style = $heritstyles['Style'];
						$double = $heritstyles['Style'];
					}else
						$style = $heritstyles['StyleOff'];
					$color = $heritstyles['ColorOff'];
					
				}else{
					if($heritstyles['StyleOn']==''){
						$style = $heritstyles['Style'];
					}else
						$style = $heritstyles['StyleOn'];
					$color = $heritstyles['ColorOn'];
				}

			}else{
				$style = $heritstyles['Style'];
				if($item['State']!==false){ // entry enabled
					$color = $heritstyles['Color'];
				}else{ // 
					$color = $heritstyles['ColorOff'].'$z';
				}
			}

			$action = (is_string($act)&&isset($_ml_act[$act])) ? ' action="'.$_ml_act[$act].'"' : '';

			$xml .= sprintf('<quad sizen="%0.2f 2" posn="0 %0.3f 0" %s%s/>',
											$heritstyles['Width'],$y,$style,$action);
			if($double!==false){
				$xml .= sprintf('<quad sizen="%0.2f 2" posn="0 %0.3f 0.01" %s/>',
												$heritstyles['Width'],$y,$double);
			}
			$xml .= sprintf('<label sizen="%0.2f 0" posn="1.6 %0.3f 0.02" text="%s%s"/>',
											$heritstyles['Width']-2,$y-0.26,$color,$name);

			if($item['Type']=='menu' && $item['State'] && isset($item['Menu']['Show'])){
				if($heritstyles['SubPos']=='right')
					$item['Menu']['X'] = 0.4;
				else
					$item['Menu']['X'] = $heritstyles['Width']-1.05;
				if($heritstyles['Pos']=='bottom')
					$item['Menu']['Y'] = $y+0.3;
				else
					$item['Menu']['Y'] = $y-0.2;
				$xml .= ml_menusBuildMenuRec($item['Menu'],$nz+0.05,$heritstyles);
			}

			$y -= $h;
		}
	}
	
	$x = $menu['X'] - ($heritstyles['SubPos']=='right' ? $heritstyles['Width'] : 0);
	if($heritstyles['Pos']=='bottom')
		$y = $menu['Y']-$y-($first?0:$h);
	elseif($heritstyles['Pos']=='center')
		$y = $menu['Y']-$y/2-($first?0:$h/2);
	else
		$y = $menu['Y'];
	return sprintf('<frame posn="%0.3f %0.3f %0.3f">',$x,$y,$nz+0.1).$xml.'</frame>';
}


?>
