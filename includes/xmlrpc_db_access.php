<?php
////////////////////////////////////////////////////////////////
//¤
// File:      XMLRPC DB ACCESS 2.1
// Date:      17.07.2011
// Author:    Gilles Masson
//
////////////////////////////////////////////////////////////////

//require_once('includes/web_access.php');

class XmlrpcDB{
  
  //-----------------------------
  // Fields
  //-----------------------------
  
	var $_webaccess;
  var $_url;
	var $_server;
	var $_requests;
	var $_callbacks;
  var $_auth_cb;
	var $_bad;
	var $_bad_time;

  //-----------------------------
  // Methods
  //-----------------------------
  
  function XmlrpcDB($webaccess, $url, $game, $login, $password, $tool, $version, $nation, $packmask=''){
		$this->_webaccess = $webaccess;
		$this->_url = $url;
		$this->_server = array('Game'=>$game,'Login'=>$login,'Password'=>$password,'Tool'=>$tool,'Version'=>$version,'Nation'=>$nation,'Packmask'=>$packmask,'PlayersGame'=>true);
		$this->_auth_cb = array('xmlrpc_auth_cb');

		$this->_bad = false;
		$this->_bad_time = -1;
		// in case webaccess url connection was previously in error, ask to retry
		$this->_webaccess->retry($this->_url);

		// prepare to add requests
		$this->_initRequest();
	}

	// change the packmask value
	function setPackmask($packmask=''){
		$this->_server['Packmask'] = $packmask;
	}

	// is the connection in recurrent error ?
	function isBad(){
		return $this->_bad;
	}

	// get time since the error state was set
	function badTime(){
		return (time()-$this->_bad_time);
	}

	// stop the bad state : will try again at next RequestWait(), sendRequestsWait() or sendRequests()
	function retry(){
		$this->_bad = false;
		$this->_bad_time = -1;
		// set webaccess object to retry on that url too
		$this->_webaccess->retry($this->_url);
	}

	// clear all requests, and get them if asked
	function clearRequests($get_requests=false){
		if($get_requests){
			$return = array($_requests,$_callbacks);
			$this->_initRequest();
			return $return;
		}
		$this->_initRequest();
	}

	// add a request
	function addRequest($callback, $method) {
		$args = func_get_args();
		$callback = array_shift($args);
		$method = array_shift($args);
		return $this->addRequestArray($callback,$method,$args);
	}

	// add a request
	function addRequestArray($callback, $method, $args){
		$this->_callbacks[] = $callback;
		$this->_requests[] = array('methodName' => $method, 'params' => $args);
		return count($this->_requests)-1;
	}

	// send added requests, callbacks will be called when response come 
  function sendRequests(){
		if(count($this->_callbacks)>1){
			$this->addRequest(null, 'dedimania.WarningsAndTTR');
			$webdatas = $this->_makeXMLdatas();
			$response = $this->_webaccess->request($this->_url, array( array($this,'_callCB'),$this->_callbacks,$this->_requests), $webdatas, true);
			$this->_initRequest();
			if($response===false){
				if(!$this->_bad){
					$this->_bad = true;
					$this->_bad_time = time();
				}
				return false;
			}
		}
		return true;
	}

	// send added requests, wait response, then call callbacks
  function sendRequestsWait(){
		if(count($this->_callbacks)>1){
			$this->addRequest(null, 'dedimania.WarningsAndTTR');
			$webdatas = $this->_makeXMLdatas();
			$response = $this->_webaccess->request($this->_url, null, $webdatas, true);
			if($response===false){
				if(!$this->_bad){
					$this->_bad = true;
					$this->_bad_time = time();
				}
				$this->_initRequest();
				return false;
			}else{
				$this->_callCB($response,$this->_callbacks,$this->_requests);
				$this->_initRequest();
			}
		}
		return true;
	}

	// send a request, wait response, and return the response
	function RequestWait($method) {
		$args = func_get_args();
		$method = array_shift($args);
		return $this->RequestWaitArray($method,$args);
	}

	// send a request, wait response, and return the response
  function RequestWaitArray($method, $args){
		if($this->sendRequestsWait()===false){
			if(!$this->_bad){
				$this->_bad = true;
				$this->_bad_time = time();
			}
			return false;
		}
		
		$reqnum = $this->addRequestArray(null, $method, $args);

		$this->addRequest(null, 'dedimania.WarningsAndTTR');
		$webdatas = $this->_makeXMLdatas();
		$response = $this->_webaccess->request($this->_url, null, $webdatas, true);
		if(isset($response['Message']) && is_string($response['Message'])){
			//debugPrint("XmlrpcDB->RequestWaitArray() - response['Message']",$response['Message']);

			$xmlrpc_message = new IXR_Message($response['Message']);
			if ($xmlrpc_message->parse() && $xmlrpc_message->messageType != 'fault') {
				//debugPrint("XmlrpcDB->RequestWaitArray() - message",$xmlrpc_message->message);
				//debugPrint("XmlrpcDB->RequestWaitArray() - params",$xmlrpc_message->params);

				//$datas = array('methodName' => $xmlrpc_message->methodName,'params' => $xmlrpc_message->params);
				$datas = $this->_makeResponseDatas($xmlrpc_message->methodName,$xmlrpc_message->params,$this->_requests);

			}else{
				debugPrint("XmlrpcDB->RequestWaitArray() - message fault",$xmlrpc_message->message);
				$datas = array();

			}
		}else{
			$datas = array();
		}

		//debugPrint("XmlrpcDB->RequestWaitArray() - datas",$datas);
		//var_dump($response['Message']);
		//var_dump($datas);
		if(isset($datas['params']) && isset($datas['params'][$reqnum])){
			$response['Data'] = $datas['params'][$reqnum];
			$param_end = end($datas['params']);
			if(isset($param_end['globalTTR']) && !isset($response['Data']['globalTTR']))
				$response['Data']['globalTTR'] = $param_end['globalTTR'];
		}else{
			$response['Data'] = $datas;
		}
		//debugPrint("XmlrpcDB->RequestWaitArray() - response['Data']",$response['Data']);

		$this->_initRequest();
		return $response;
	}

	// init the request, and callback array
	function _initRequest(){
		$this->_requests = array();
		$this->_callbacks = array();
		$this->addRequest($this->_auth_cb, 'dedimania.Authenticate', $this->_server);
	}

	// make the xmlrpc string, encode it in base64, and pass it as value to xmlrpc url post parameter
	function _makeXMLdatas(){
		$xmlrpc_request = new IXR_RequestStd('system.multicall', $this->_requests);
		//debugPrint("XmlrpcDB->_makeXMLdatas() - getXml()",$xmlrpc_request->getXml());
		return $xmlrpc_request->getXml();
	}

	function _callCB($response,$callbacks,$requests){
		$globalTTR = 0;
		if(isset($response['Message']) && is_string($response['Message'])){
			$xmlrpc_message = new IXR_Message($response['Message']);
			if ($xmlrpc_message->parse() && $xmlrpc_message->messageType != 'fault') {
				//debugPrint("XmlrpcDB->_callCB() - message",$xmlrpc_message->message);

				//$datas = array('methodName' => $xmlrpc_message->methodName,'params' => $xmlrpc_message->params);
				$datas = $this->_makeResponseDatas($xmlrpc_message->methodName,$xmlrpc_message->params,$requests);

        if(!isset($datas['params']) || !is_array($datas['params'])){
					// store message serialized for debugging purpose
					global $_debug;
					if($_debug>0){
						$infos = array('Url'=>$this->_url,'Requests'=>$requests,'Callbacks'=>$callbacks,'Response'=>$response,'XmlrpcMessage'=>$xmlrpc_message->message);
						$serinfos = serialize($infos);
						$file = 'fastlog/BadCallCB.'.rand(100,999).'.phpser';
						file_put_contents($file,$serinfos);
						debugPrint("XmlrpcDB::callCB:: bad reply form (serialized infos saved in: {$file}) : ",$serinfos);
					}else{
						console("XmlrpcDB->_callCB() - message fault");
					}
					$datas = array();
        }else{
					$param_end = end($datas['params']);
					if(isset($param_end['globalTTR']))
						$globalTTR = $param_end['globalTTR'];
				}
			}else{
				global $_debug;
				if($_debug>0){
					$infos = array('Url'=>$this->_url,'Requests'=>$requests,'Callbacks'=>$callbacks,'Response'=>$response,'XmlrpcMessage'=>$xmlrpc_message->message);
					$serinfos = serialize($infos);
					$file = 'fastlog/BadCallCB.'.rand(100,999).'.phpser';
					file_put_contents($file,$serinfos);
					debugPrint("XmlrpcDB->_callCB() - message fault (serialized infos saved in: {$file}) : ",$serinfos);
				}else{
					console("XmlrpcDB->_callCB() - message fault");
				}
				$datas = array();
			}
		}else{
			if(!isset($response['Error']) || (strpos($response['Error'],'connection failed')===false && strpos($response['Error'],'Read timeout')===false)){
				$infos = array('Url'=>$this->_url,'Requests'=>$requests,'Callbacks'=>$callbacks,'Response'=>$response);
				$serinfos = serialize($infos);
				debugPrint("XmlrpcDB->_callCB() - no response message (usually server not responding, harmless if occasional) : ",$serinfos);
			}
			$datas = array();
		}
		for($i=0; $i<count($callbacks); $i++){
			if($callbacks[$i] != null){
				$callback = $callbacks[$i][0];
				if(isset($datas['params']) && isset($datas['params'][$i])){
					$response['Data'] = $datas['params'][$i];
					if(!isset($response['Data']['globalTTR']))
						$response['Data']['globalTTR'] = $globalTTR;
				}else{
					$response['Data'] = $datas;
				}
				$callbacks[$i][0] = $response;
				call_user_func_array($callback,$callbacks[$i]);
			}
		}
	}


	// build the datas array from fast3 or dedimania server
	//   remove the first array level into params if needed
	//   add methodResponse name if needed
	//   rename sub responses params array from [0] to ['params'] if needed
	function _makeResponseDatas($methodname,$params,$requests){
		
		//debugPrint("mlrpcDB->_makeResponseDatas() - params",$params);
		if(is_array($params) && count($params)==1 && is_array($params[0]))
			$params = $params[0];
		//debugPrint("XmlrpcDB->_makeResponseDatas() - params",$params);
		
		if(is_array($params) && is_array($params[0]) && !isset($params[0]['methodResponse'])){
			$params2 = array();
			foreach($params as $key => $param){
				
				$errors = null;
				if(isset($param['faultCode'])){
					$errors[] = array('Code'=>$param['faultCode'],'Message'=>$param['faultString']);
				}
				
				if(isset($requests[$key]['methodName']))
					$methodresponse = $requests[$key]['methodName'];
				else
					$methodresponse = "Unknown";
				
				$ttr = 0.000001;
				
				if(isset($param[0]))
					$param = $param[0];
				else
					$param = array();
				
				$params2[$key] = array('methodResponse'=>$methodresponse,'params'=>$param,'errors'=>$errors,'TTR'=>$ttr,'globalTTR'=>$ttr);

				if($methodresponse=='dedimania.WarningsAndTTR'){
					//debugPrint("XmlrpcDB->_makeResponseDatas() - param",$param);
					//debugPrint("XmlrpcDB->_makeResponseDatas() - params2",$params2);
					$globalTTR = $param['globalTTR'];
					$key2 = -1;
					foreach($param['methods'] as $key3 => $param3){
						$key2++;
						while($key2<count($params2) && $params2[$key2]['methodResponse']!=$param3['methodName']){
							$params2[$key2]['globalTTR'] = $globalTTR;
							$key2++;
						}
						//debugPrint("XmlrpcDB->_makeResponseDatas() - key2=$key2 - key3=$key3 - param3",$param3);
						if($key2<count($params2)){
							$params2[$key2]['errors'] = $param3['errors'];
							$params2[$key2]['TTR'] = $param3['TTR'];
							$params2[$key2]['globalTTR'] = $globalTTR;
						}
					}
				}
			}
			//debugPrint("XmlrpcDB->_makeResponseDatas() - params",$params);
			return array('methodName' => $methodname,'params' => $params2);
			
		}else{
			return array('methodName' => $methodname,'params' => $params);
		}
	}

}


// Dedimania.Authenticate callback used to catch errors
function xmlrpc_auth_cb($response){
	if(isset($response['Data']['errors']) && $response['Data']['errors']!=''){
		if(is_array($response['Data']['errors']))
			@debugPrint("xmlrpc_auth_cb() - error: response['Data']['errors']",$response['Data']['errors']);
		else
			@debugPrint("xmlrpc_auth_cb() - error: response",$response);
	}
}

?>
