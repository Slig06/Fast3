<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      12.09.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('custom','/custom tm_cmd arg,.. : send a specified command to server',true);

//------------------------------------------
// Custom Command
//------------------------------------------
function chat_custom($author, $login, $params){
	global $doInfosNext;
	global $_response,$_response_error;

	// verify if author is in admin list
	if(!verifyAdmin($login))
		return;

		// response
	if($_response !== NULL || $_response_error !== NULL) {
		console("chat_custom response received !");
		if($_response_error !== NULL)
			$msg = localeText(null,'server_message')."Error(".$_response_error['faultCode']."): ".localeText(null,'interact').$_response_error['faultString']." !";
		else{
			$msg = "";
			if(is_array($_response)){
				$sep = "";
				foreach($_response as $key => $val){
					$msg .= $sep.$key."=";
					if(is_array($val)){
						$sep2 = "{";
						foreach($val as $key2 => $val2){
							$msg .= $sep2.$key2."=".$val2;
							$sep2 = ",";
						}
						$msg .= "}";
					}else
						$msg .= $val;
					$sep = ",";
				}
			}else
				$msg .= $_response;
			$msg = localeText(null,'server_message')."Result: ".localeText(null,'interact').stripColors($msg);
		}
		addCall(null,'ChatSendToLogin', $msg, $login);
		
		// custom command
	}elseif(isset($params[0])) {
		if(isset($params[1])){
			$args = explode(';',$params[1]);
			if(count($args) <= 1)
				$args = explode(',',$params[1]);
			unset($params[1]); 
			for($i = 0; $i < sizeof($args); $i++){
				$arg = trim($args[$i]);
				if(strcasecmp($arg,'true')==0)
					$params[] = true;
				elseif(strcasecmp($arg,'false')==0)
					$params[] = false;
				elseif(is_numeric($arg))
					$params[] = 0+$arg;
				else
					$params[] = $arg;
			}
		}
		$action = array('CB'=>array("chat_custom",func_get_args()),'Login'=>$login);
		addCallArray($action,$params);
		//addCallArray($login,$params);

		$doInfosNext = true;
		// send success message
		$msg = localeText(null,'server_message') . localeText(null,'interact')."Sending command ".$params[0]." !";
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);

		// help
	}elseif(!isset($params[0]) || $params[0]=="help"){
		$msg = localeText(null,'server_message') . localeText(null,'interact')."/custom tm_cmd arg,... : send a specified command to server";
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);

	}

}
//   action done when the response is received, can be :
//    - null -> just log errors
//    - true -> reject duplicate addCall method with same arguments
//    - login string -> login of player who will get the error message if any
//    - action array -> List of action which will be done when the response is received:
//                      'Event'=>event_array
//                      'Events'=>array(mixed delay_int and event_array)
//                      'Call'=>array(action,call_array)
//                      'Calls'=>array(mixed delay int and array(action,call_array))
//                      'CB'=>callback: array(function_name, args_array [,num of arg to replace with response])
//                      'Login'=>login string : same as action=login string
//                      'DropDuplicate'=>boolean : if true then same as action=true

?>
