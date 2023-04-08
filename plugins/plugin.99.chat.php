<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      12.09.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
global $do_chat_log,$chatfilename;
//
// Chat commands and aliases plugin
// 

registerPlugin('chat',99,1.0);




//--------------------------------------------------------------
// Init
//--------------------------------------------------------------
function chatInit(){
	global $_HelpCmd,$_HelpAdmCmd,$_EasyChat,$_DisabledChatCommands;

	$_EasyChat = array();

	// handle $_DisabledChatCommands list
	if(!isset($_DisabledChatCommands))
		$_DisabledChatCommands = array();
	else{
		$list = array();
		foreach($_DisabledChatCommands as $cmd)
			$list[$cmd] = $cmd;
		$_DisabledChatCommands = $list;
	}

	// Load chat plugins & Register Commands
	console('Register chat commands...');
	loadPlugins('./plugins/chat', 'chat.');
	loadPlugins('./plugins/cmd', 'chat.');
	loadPlugins('./plugins/adm', 'chat.');

	// load easychat messages
	include './plugins/chat/easy_chat.php';
	if(file_exists('./plugins/chat/easy_chat.custom.php'))
		include './plugins/chat/easy_chat.custom.php';

	// make the '/help' message for public and admin
	helpInit();
}


//------------------------------------------
// PlayerChat : Check Chat for Commands
//------------------------------------------
function chatPlayerChat($event,$login,$msg,$iscommand){
	global $_debug,$_PlayerList,$_EasyChat,$_HelpCmd,$_HelpAdmCmd,$_Quiet,$_players,$_DisabledChatCommands,$_ignore_list;
  if(!is_string($login))
    $login = ''.$login;
	if(isset($_players[$login]['Relayed']) && $_players[$login]['Relayed'])
		return;

	// log it
	$ncmsg = stripColors($msg);
	if($ncmsg!=$msg)
		chatlog("<{$login}> {$ncmsg}\n[colored] <{$login}> {$msg}");
	else
		chatlog("<{$login}> {$msg}");

	// quiet mode
	if($_Quiet && !verifyAdmin($login))
		return;

	// if not starting with / then it's not a command
	if(!$iscommand)
		return;

	// remove beginning slashes
	while(substr($msg, 0, 1) == '/')
		$msg = ltrim(substr($msg, 1));
	// command is at least 1 character long
	if(strlen($msg)<1)
		return;

	if($_debug>4) console("chatPlayerChat({$login}):: chat command: {$msg}");

	// replace multiple spaces by single one
	$msg = preg_replace('/[ ]+/s',' ',$msg);
	// separate words, only 1st 3 words including command
	$cmd = explode(' ',$msg,3);
	// first work is the command
	$command = array_shift($cmd);

	// remove spaces arround comma
	$msg = preg_replace('/\s*,\s*/s',',',$msg);
	// replace multiple spaces by single one
	$msg = preg_replace('/\s+/s',' ',$msg);
	// separate words, all words
	$cmd2 = explode(' ',$msg);
	array_shift($cmd2);

	// get author nickname
	$author = getUserNickName($login);

	if(isset($_DisabledChatCommands[$command])){
		// disabled command !

	}elseif(isset($_HelpCmd[$command]) || verifyAdmin($login)){
		// call function for specified command (ie.: "chat_afk(parameters)")
		// $cmd[0] is spaces free, but $cmd[1] can contain spaces.
		// $cmd2 is fully spaces exploded (with comma separated words in one word).
		if(function_exists('chat_'.$command)){
			if(verifyAdmin($login) || !isset($_players[$login]['ChatFloodRateIgnore']) || !$_players[$login]['ChatFloodRateIgnore']){
				call_user_func('chat_'.$command, $author, $login, $cmd, $cmd2);
				
				// log any command written (because they are hidden now)
				console("CHATCMD[{$login}] /".$command.' '.implode(' ',$cmd));
			}

		// else verify if it's in easychat list
		}elseif(isset($_EasyChat[$command])){
			if(!isset($_ignore_list[$login])){
				sendEasyChat($command, $author, $cmd, $login);
				console("CHAT[{$login}] /".$command.' '.implode(' ',$cmd));
			}
		}
	}
}


//------------------------------------------
// PlayerChat : Check Chat for Commands after all other plugins
// show chat text if chatmanualrouting is known to be on, using
// special relay color if it's a spec on relay
//------------------------------------------
function chatPlayerChat_Post($event,$login,$msg,$iscommand){
	global $_chatmanualrouting,$_ignore_list;
	//console("chatPlayerChat_Post:: $login,$msg,$iscommand,$_chatmanualrouting");
	if(!$iscommand && $_chatmanualrouting){
		if(!isset($_ignore_list[$login]))
			sendPlayerChat($login,$msg);
	}
}


//------------------------------------------
// add easychat command
//------------------------------------------
function registerEasyChat($command,$msg,$helpmsg, $admonly = false){
	global $_EasyChat;
	if (registerCommand($command,$helpmsg,$admonly))
		$_EasyChat[$command] = $msg;
}


//------------------------------------------
// send EasyChat message
//------------------------------------------
function sendEasyChat($command, $author, $params, $login){
	global $_EasyChat;
	$msg = authorChat($login,$author).localeText(null,'emotic');
	if(isset($params[0])){
		$repl = localeText(null,'highlight').$params[0];
		if (isset($params[1]))
			$repl .= ' '.$params[1];
		$repl .= localeText(null,'emotic');
		// ereg_replace is deprecated ! $msg .= ereg_replace("{{[^}]*}}",$repl,$_EasyChat[$command]);
		$msg .= preg_replace("/{{[^}]*}}/",$repl,$_EasyChat[$command]);
	}else{
		// ereg_replace is deprecated ! $msg .= ereg_replace("{{([^}]*)}}","\\1",$_EasyChat[$command]);
		$msg .= preg_replace("/{{([^}]*)}}/","\\1",$_EasyChat[$command]);
	}
	addCall(null,'ChatSendServerMessage', $msg);
}



?>
