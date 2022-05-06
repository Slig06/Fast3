<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      05.11.2010
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// function registerPlugin($plugin,$priority=50)
// 
//
registerPlugin('relaychat',39);



// -----------------------------------------------------------
// Change colors of chat for players on relay
// Limit the global chat flood on relay
// -----------------------------------------------------------
// this values can be overridden in fast.php
// $_relaychat_rate = 10; // max messages per seconds (admin messages always accepted)
// $_relaychat_maxlevel = 50; // above it messages are dropped
// $_relaychat_playerrate = 8; // max messages per seconds for relayed players (admin messages always accepted)
// $_relaychat_maxplayerlevel = 80; // above it messages are dropped


//------------------------------------------
// Init
//------------------------------------------
function relaychatInit($event){
	global $_debug,$_is_relay,$_relaychat_maxlevel,$_relaychat_maxplayerlevel,$_relaychat_level,$_relaychat_playerlevel,$_relaychat_rate,$_relaychat_playerrate;

	if(!isset($_relaychat_rate))
		$_relaychat_rate = 10; // max messages per seconds (admin messages always accepted)
	if(!isset($_relaychat_maxlevel))
		$_relaychat_maxlevel = 50; // above it messages are dropped

	if(!isset($_relaychat_playerrate))
		$_relaychat_playerrate = 8; // max messages per seconds for relayed players (admin messages always accepted)
	if(!isset($_relaychat_maxplayerlevel))
		$_relaychat_maxplayerlevel = 50; // above it messages are dropped

	$_relaychat_level = 0;
	$_relaychat_playerlevel = 0;

	if($_is_relay){
		// for relay set chatmanualrouting
		addCall(null,'ChatEnableManualRouting',true);
	}
}


// BeginRound($event)
function relaychatBeginRound($event){
	global $_debug,$_is_relay;

	if($_is_relay){
		// for relay set chatmanualrouting
		addCall(null,'ChatEnableManualRouting',true);
	}
}


// Everysecond($event,$seconds): called once every second, after all other events (before Everytime)
function relaychatEverysecond($event,$seconds){
	global $_debug,$_is_relay,$_relaychat_maxlevel,$_relaychat_maxplayerlevel,$_relaychat_level,$_relaychat_playerlevel,$_relaychat_rate,$_relaychat_playerrate;

	if($_is_relay){
		$_relaychat_level -= $_relaychat_rate;
		if($_relaychat_level < 0)
			$_relaychat_level = 0;
		$_relaychat_playerlevel -= $_relaychat_playerrate;
		if($_relaychat_playerlevel < 0)
			$_relaychat_playerlevel = 0;
	}
}


// PlayerChat($event,$login,$message): the player wrote a text in chat
// chat flooding is limited by $_relaychat_maxlevel,$_relaychat_maxplayerlevel,$_relaychat_rate,$_relaychat_playerrate
function relaychatPlayerChat($event,$login,$message,$iscommand){
	global $_debug,$_players,$_is_relay,$_relaychat_maxlevel,$_relaychat_maxplayerlevel,$_relaychat_level,$_relaychat_playerlevel,$_relaychat_rate,$_relaychat_playerrate,$_chatmanualrouting;
	//console("relaychatPlayerChat({$login},{$message},{$iscommand})::");

	if($_is_relay && !$iscommand && $_chatmanualrouting && isset($_players[$login]['NickName'])){
		//console("relaychat.Event[$event]('$login','$message') ({$_relaychat_level} / {$_relaychat_maxlevel} , {$_relaychat_playerlevel} / {$_relaychat_maxplayerlevel})");

		if(!isset($_players[$login]['Relayed']) || !$_players[$login]['Relayed']){
			// spec on relay

			if(!verifyAdmin($login)){
				// if not admin then check rate
				if($_relaychat_level > $_relaychat_maxlevel){
					console("relaychat.Event[$event]('$login','$message') dropped ({$_relaychat_level} / {$_relaychat_maxlevel})");
					dropEvent(); // drop event to not have it shown by some other plugin...
					return; // drop
				}
				$_relaychat_level++;
			}

		}else{
			// relayed player

			if(!verifyAdmin($login)){
				// if not admin then check rate
				if($_relaychat_playerlevel > $_relaychat_maxplayerlevel){
					console("relaychat.Event[$event]('$login','$message') dropped ({$_relaychat_playerlevel} / {$_relaychat_maxplayerlevel})");
					dropEvent(); // drop event to not have it shown by some other plugin...
					return; // drop
				}
				$_relaychat_level++;
			}
			$_relaychat_playerlevel++;
		}

		// chat color is handled by sendPlayerChat(), see plugin.01.players.php
		sendPlayerChat($login,$message);

		// drop event to not have it shown a second time by some other plugin...
		dropEvent();
	}
}



?>
