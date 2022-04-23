<?php
//¤ special case for help cmd : the help message will overwritten by helpInit()


registerCommand('help','/help [cmd] : return cmd help message');


// shows all commands available and command help messages
function chat_help($author, $login, $params){
	global $_HelpCmd,$_HelpAdmCmd,$_DisabledChatCommands;

	// get cmd arg
	if(isset($params[0]))
		$cmd = $params[0];
	else
		$cmd = 'help';

	if(isset($_DisabledChatCommands[$cmd])){
		helpInit();
		console("help: {$cmd} disabled !");
		$msg = localeText(null,'server_message').localeText(null,'interact')."Command {$cmd} is disabled !";
		addCall(null,'ChatSendToLogin', localeText(null,'server_message').localeText(null,'interact').$msg, $login);

	}else{
		console('help: '.$cmd);
		// it's an admin help message and user is admin
		if(isset($_HelpAdmCmd[$cmd]) && verifyAdmin($login)){
			$msg = localeText(null,'server_message').localeText(null,'interact').$_HelpAdmCmd[$cmd];
			
			// or it's a public help message
		}elseif(isset($_HelpCmd[$cmd])){
			$msg = localeText(null,'server_message').localeText(null,'interact').$_HelpCmd[$cmd];
			
		}else{
			$msg = 'no help for '.$cmd.'command.';
		}
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', localeText(null,'server_message').localeText(null,'interact').$msg, $login);
	}
}


// call this function after loading all plugins !
function helpInit(){
	global $_HelpCmd,$_HelpAdmCmd,$_DisabledChatCommands;

	// make $_HelpCmd['help'] help message
	$msg = 'Commands: ';
	$sep = '/';
	foreach($_HelpCmd as $key => $val){
		if(!isset($_DisabledChatCommands[$key])){
			$msg .= $sep.$key;
			$sep = ', /';
		}
	}
	$_HelpCmd['help'] = $msg;

	// make $_HelpAdmCmd['help'] help message
	$msg = 'Commands: ';
	$sep = '/';
	foreach($_HelpCmd as $key => $val){
		if(!isset($_DisabledChatCommands[$key])){
			$msg .= $sep.$key;
			$sep = ', /';
		}
	}
	$msg .= '$fef';
	foreach($_HelpAdmCmd as $key => $val){
		if(!isset($_DisabledChatCommands[$key])){
			$msg .= $sep.$key;
			$sep = ', /';
		}
	}
	$_HelpAdmCmd['help'] = $msg;
}

?>
