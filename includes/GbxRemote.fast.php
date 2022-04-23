<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/* ¤
	IXR - The Incutio XML-RPC Library - (c) Incutio Ltd 2002
	Version 1.61 - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
	Site:   http://scripts.incutio.com/xmlrpc/
	Manual: http://scripts.incutio.com/xmlrpc/manual.php
	Made available under the Artistic License: http://www.opensource.org/licenses/artistic-license.php

	Modified to support protocol 'GbxRemote 2' ('GbxRemote 1')

	Release 2007-09-22 - Slig:
		Modified to support >256KB received data (and now >2MB data produce a specific error message)
		Modified readCB() to wait the initial timeout only before first read packet
		Modified readCB() to return true if there is data to get with getCBResponses()
		Modified to support amd64 (for $recvhandle)
		Modified IXR_ClientMulticall_Gbx->addCall() to fit Aseco 0.6.1
		Added IXR_Client_Gbx->bytes_sent & bytes_received counters
		Fix for a changed feature since php5.1.1 about reference parameter assignment (was used in stream_select)
		Workaround for stream_select return value bug with amd64

	Release 2008-01-20 - Slig / Xymph / Assembler Maniac:
		Workaround for fread delay bug in some cases
		Added resetError method (by Xymph)
		Some comments and strings code cleanup (by Xymph)
		Fix stream_set_timeout($this->socket,...) (thx to CavalierDeVache)
		Added a default timeout value to function readCB($timeout)
		Changed calls with timeout on a stream to use microseconds instead of seconds (by AM)
		Removed IXR_Client_Gbx->bytes_sent & bytes_received counters - not used (by AM)

	Release 2008-02-05 - Slig:
		Changed some socket read/write timeouts back to seconds to avoid 'transport error'
		Changed max data received from 2MB to 4MB

	Release 2008-05-20 - Xymph:
		Prevented unpack() warnings in query method when the connection dies
		Changed resetError method to assign 'false' for correct isError method
		Tweaked some 'transport error' messages

	Release 2008-12-11 - Slig:
		Merged little endian and big endian versions
		Added asynchronous query and get async response
		Fixed few minor changes

	Release 2009-01-25 - Slig:
		Add some precisions in connection interrupted messages
		Accept more timeout when reading callback/async response first bytes

	Release 2009-02-02 - Slig:
		Fixed set usefull IXR_Message infos when parse() return false
*/

if (!defined('LF')) {
	define('LF', "\n");
}

class IXR_Value {
	var $data;
	var $type;

	function IXR_Value ($data, $type = false) {
		$this->data = $data;
		if (!$type) {
			$type = $this->calculateType();
		}
		$this->type = $type;
		if ($type == 'struct') {
			// warning : in some case changing directly the array values modify also the last entry of original array !!! so build a new array...
			$this->data = array();
			// Turn all the values in the array into new IXR_Value objects
			foreach ($data as $key => $value) {
				$this->data[$key] = new IXR_Value($value);
			}
		}
		if ($type == 'array') {
			// warning : in some case changing directly the array values modify also the last entry of original array !!! so build a new array...
			$this->data = array();
			for ($i = 0, $j = count($data); $i < $j; $i++) {
				$this->data[$i] = new IXR_Value($data[$i]);
			}
		}
	}

	function calculateType() {
		if ($this->data === true || $this->data === false) {
			return 'boolean';
		}
		if (is_integer($this->data)) {
			return 'int';
		}
		if (is_double($this->data)) {
			return 'double';
		}
		// Deal with IXR object types base64 and date
		if (is_object($this->data) && is_a($this->data, 'IXR_Date')) {
			return 'date';
		}
		if (is_object($this->data) && is_a($this->data, 'IXR_Base64')) {
			return 'base64';
		}
		// If it is a normal PHP object convert it into a struct
		if (is_object($this->data)) {
			$this->data = get_object_vars($this->data);
			return 'struct';
		}
		if (!is_array($this->data)) {
			return 'string';
		}
		// We have an array - is it an array or a struct?
		if ($this->isStruct($this->data)) {
			return 'struct';
		} else {
			return 'array';
		}
	}

	function getXml() {
		// Return XML for this value
		switch ($this->type) {
			case 'boolean':
				return '<boolean>' . ($this->data ? '1' : '0') . '</boolean>';
				break;
			case 'int':
				return '<int>' . $this->data . '</int>';
				break;
			case 'double':
				return '<double>' . $this->data . '</double>';
				break;
			case 'string':
				return '<string>' . htmlspecialchars($this->data) . '</string>';
				break;
			case 'array':
				$xml = '';
				foreach ($this->data as $item) {
					$xml .= '<value>' . $item->getXml() . '</value>';
				}
				return '<array><data>' . $xml . '</data></array>';
				break;
			case 'struct':
				$xml = '';
				foreach ($this->data as $name => $value) {
					$xml .= '<member><name>' . $name . '</name><value>' . $value->getXml() . '</value></member>';
				}
				return '<struct>' . $xml . '</struct>';
				break;
			case 'date':
			case 'base64':
				return $this->data->getXml();
				break;
		}
		return false;
	}

	function isStruct($array) {
		// Nasty function to check if an array is a struct or not
		$expected = 0;
		foreach ($array as $key => $value) {
			if ((string)$key != (string)$expected) {
				return true;
			}
			$expected++;
		}
		return false;
	}
}


class IXR_Message {
	var $message;
	var $messageType;  // methodCall / methodResponse / fault
	var $faultCode;
	var $faultString;
	var $methodName;
	var $params;
	// Current variable stacks
	var $_arraystructs = array();  // Stack to keep track of the current array/struct
	var $_arraystructstypes = array();  // Stack to keep track of whether things are structs or array
	var $_currentStructName = array();  // A stack as well
	var $_param;
	var $_value;
	var $_currentTag;
	var $_currentTagContents;
	// The XML parser
	var $_parser;

	function IXR_Message ($message) {
		$this->message = $message;
	}

	function parse() {
		// first remove the XML declaration
		$this->message = preg_replace('/<\?xml(.*)?\?'.'>/', '', $this->message);
		if (trim($this->message) == '') {
			return false;
		}
		$this->_parser = xml_parser_create('UTF-8');
		// Set XML parser to take the case of tags into account
		xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
		// Set XML parser callback functions
		xml_set_object($this->_parser, $this);
		xml_set_element_handler($this->_parser, 'tag_open', 'tag_close');
		xml_set_character_data_handler($this->_parser, 'cdata');
		if (!xml_parse($this->_parser, $this->message)) {
			$this->messageType = 'fault';
			$this->faultCode = xml_get_error_code($this->_parser);
			$this->faultString = sprintf('XML error: %s at line %d',
																	 xml_error_string(xml_get_error_code($this->_parser)),
																	 xml_get_current_line_number($this->_parser));
			return false;
		}
		xml_parser_free($this->_parser);
		// Grab the error messages, if any
		if ($this->messageType == 'fault') {
			$this->faultCode = $this->params[0]['faultCode'];
			$this->faultString = $this->params[0]['faultString'];
		}
		return true;
	}

	function tag_open($parser, $tag, $attr) {
		$this->currentTag = $tag;
		switch ($tag) {
			case 'methodCall':
			case 'methodResponse':
			case 'fault':
				$this->messageType = $tag;
				break;
			// Deal with stacks of arrays and structs
			case 'data':  // data is to all intents and purposes more interesting than array
				$this->_arraystructstypes[] = 'array';
				$this->_arraystructs[] = array();
				break;
			case 'struct':
				$this->_arraystructstypes[] = 'struct';
				$this->_arraystructs[] = array();
				break;
		}
	}

	function cdata($parser, $cdata) {
		$this->_currentTagContents .= $cdata;
	}

	function tag_close($parser, $tag) {
		$valueFlag = false;
		switch ($tag) {
			case 'int':
			case 'i4':
				$value = (int)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'double':
				$value = (double)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'string':
				$value = (string)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'dateTime.iso8601':
				$value = new IXR_Date(trim($this->_currentTagContents));
				// $value = $iso->getTimestamp();
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'value':
				// If no type is indicated, the type is string
				if (trim($this->_currentTagContents) != '') {
					$value = (string)$this->_currentTagContents;
					$this->_currentTagContents = '';
					$valueFlag = true;
				}
				break;
			case 'boolean':
				$value = (boolean)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'base64':
				$value = base64_decode($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
				// Deal with stacks of arrays and structs
			case 'data':
			case 'struct':
				$value = array_pop($this->_arraystructs);
				array_pop($this->_arraystructstypes);
				$valueFlag = true;
				break;
			case 'member':
				array_pop($this->_currentStructName);
				break;
			case 'name':
				$this->_currentStructName[] = trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				break;
			case 'methodName':
				$this->methodName = trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				break;
		}

		if ($valueFlag) {
			/*
			if (!is_array($value) && !is_object($value)) {
				$value = trim($value);
			}
			*/
			if (count($this->_arraystructs) > 0) {
				// Add value to struct or array
				if ($this->_arraystructstypes[count($this->_arraystructstypes)-1] == 'struct') {
					// Add to struct
					$this->_arraystructs[count($this->_arraystructs)-1][$this->_currentStructName[count($this->_currentStructName)-1]] = $value;
				} else {
					// Add to array
					$this->_arraystructs[count($this->_arraystructs)-1][] = $value;
				}
			} else {
				// Just add as a paramater
				$this->params[] = $value;
			}
		}
	}
}


class IXR_Request {
	var $method;
	var $args;
	var $xml;

	function IXR_Request($method, $args) {
		$this->method = $method;
		$this->args = $args;
		$xml = '';
		foreach ($this->args as $arg) {
			$v = new IXR_Value($arg);
			$itemxml = $v->getXml();
			$xml .= '<param><value>'.$itemxml.'</value></param>' . LF;
		}
		$this->xml = '<?xml version="1.0" encoding="utf-8" ?><methodCall><methodName>' . $this->method . '</methodName><params>' . LF . $xml . '</params></methodCall>';
	}

	function getLength() {
		return strlen($this->xml);
	}

	function getXml() {
		return $this->xml;
	}
}


class IXR_Error {
	var $code;
	var $message;

	function IXR_Error($code, $message) {
		$this->code = $code;
		$this->message = $message;
	}

	function getXml() {
		$xml = <<<EOD
<methodResponse>
	<fault>
		<value>
			<struct>
				<member>
					<name>faultCode</name>
					<value><int>{$this->code}</int></value>
				</member>
				<member>
					<name>faultString</name>
					<value><string>{$this->message}</string></value>
				</member>
			</struct>
		</value>
	</fault>
</methodResponse>
EOD;
		return $xml;
	}
}


class IXR_Date {
	var $year;
	var $month;
	var $day;
	var $hour;
	var $minute;
	var $second;

	function IXR_Date($time) {
		// $time can be a PHP timestamp or an ISO one
		if (is_numeric($time)) {
			$this->parseTimestamp($time);
		} else {
			$this->parseIso($time);
		}
	}

	function parseTimestamp($timestamp) {
		$this->year = date('Y', $timestamp);
		$this->month = date('Y', $timestamp);
		$this->day = date('Y', $timestamp);
		$this->hour = date('H', $timestamp);
		$this->minute = date('i', $timestamp);
		$this->second = date('s', $timestamp);
	}

	function parseIso($iso) {
		$this->year = substr($iso, 0, 4);
		$this->month = substr($iso, 4, 2);
		$this->day = substr($iso, 6, 2);
		$this->hour = substr($iso, 9, 2);
		$this->minute = substr($iso, 12, 2);
		$this->second = substr($iso, 15, 2);
	}

	function getIso() {
		return $this->year.$this->month.$this->day.'T'.$this->hour.':'.$this->minute.':'.$this->second;
	}

	function getXml() {
		return '<dateTime.iso8601>'.$this->getIso().'</dateTime.iso8601>';
	}

	function getTimestamp() {
		return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
	}
}


class IXR_Base64 {
	var $data;

	function IXR_Base64($data) {
		$this->data = $data;
	}

	function getXml() {
		return '<base64>'.base64_encode($this->data).'</base64>';
	}
}


//////////////////////////////////////////////////////////
// Nadeo modifications                                  //
//  (many thanks to slig for adding callback support)   //
//////////////////////////////////////////////////////////
class IXR_Client_Gbx {
	var $socket;
	var $message = false;
	var $cb_message = array();
	var $async_storeresp = array();
	var $async_responses = array();
	var $reqhandle;
	var $protocol = 0;
	// Storage place for an error message
	var $error = false;

	function IXR_Client_Gbx() {
		$this->socket = false;
		$this->reqhandle = 0x80000000;
	}

	function InitWithIp($ip, $port, $opentimeout=20) {

		big_endian_test();

		// open connection
		$this->socket = @fsockopen($ip, $port, $errno, $errstr, $opentimeout);
		if (!$this->socket) {
			$this->error = new IXR_Error(-32300, "transport error - could not open socket (error: $errno, $errstr)");
			return false;
		}
		@stream_set_timeout($this->_socket, 0, 100000 * $opentimeout);

		// handshake
		$array_result = multi_endian_unpack('Vsize', fread($this->socket, 4));
		$size = $array_result['size'];
		if ($size > 64) {
			$this->error = new IXR_Error(-32300, 'transport error - wrong lowlevel protocol header');
			return false;
		}
		$handshake = fread($this->socket, $size);
		if ($handshake == 'GBXRemote 1') {
			$this->protocol = 1;
		} else if ($handshake == 'GBXRemote 2') {
			$this->protocol = 2;
		} else {
			$this->error = new IXR_Error(-32300, 'transport error - wrong lowlevel protocol version');
			return false;
		}
		return true;
	}

	function Init($port) {
		return $this->InitWithIp('localhost', $port);
	}

	function Terminate() {
		if ($this->socket) {
			fclose($this->socket);
			$this->socket = false;
		}
	}

	// Send query (method call) and wait response
	// Also read callbacks messages and async responses
	function query() {
		$args = func_get_args();
		$method = array_shift($args);

		$this->error = false;
		if (!$this->socket || $this->protocol == 0) {
			$this->error = new IXR_Error(-32300, 'transport error - Client not initialized');
			return false;
		}

		$request = new IXR_Request($method, $args);
		$xml = $request->getXml();

		@stream_set_timeout($this->socket, 30);  // timeout 30 s (to write the request)
		// send request
		$this->reqhandle ++;
		if($this->reqhandle > 0x8FFFFFF0)
			 $this->reqhandle = 0x80000000;
		$reqh = sprintf("RH%u",(int)$this->reqhandle);
		if ($this->protocol == 1) {
			$request = pack('Va*', strlen($xml), $xml);
		} else {
			$request = pack('VVa*', strlen($xml), $this->reqhandle, $xml);
		}
		$sent = fwrite($this->socket, $request);

		//echo "Gbx:: sent {$reqh} ($sent / ".strlen($request)." / ".strlen($xml).")\n";
		
		$contents = '';
		$contents_length = 0;
		do {
			$size = 0;
			$recvhandle = 0;
			@stream_set_timeout($this->socket, 30);  // timeout 30 s (to read the reply header)
			// Get result
			if ($this->protocol == 1) {
				$contents = fread($this->socket, 4);
				if (strlen($contents) == 0) {
					$this->error = new IXR_Error(-32700, 'transport error - connection interrupted (failed to read 4 first bytes of response)!');
					return false;
				}
				$array_result = multi_endian_unpack('Vsize', $contents);
				$size = $array_result['size'];
				$recvhandle = $this->reqhandle;
			} else {
				$contents = fread($this->socket, 8);
				if (strlen($contents) == 0) {
					$this->error = new IXR_Error(-32700, 'transport error - connection interrupted (failed to read 8 first bytes of response)!');
					return false;
				}
				$array_result = multi_endian_unpack('Vsize/Vhandle', $contents);
				$size = $array_result['size'];
				$recvhandle = $array_result['handle'];
				// -- amd64 support --
				$bits = sprintf('%b', $recvhandle);
				if (strlen($bits) == 64) {
					$recvhandle = bindec(substr($bits, 32));
				}
			}

			if ($recvhandle == 0 || $size == 0) {
				$this->error = new IXR_Error(-32700, 'transport error - connection interrupted (failed to read response datas)!');
				return false;
			}
			if ($size > 4096*1024) {
				$this->error = new IXR_Error(-32700, "transport error - answer too big ($size)");
				return false;
			}

			$contents = '';
			$contents_length = 0;
			@stream_set_timeout($this->socket, 0, 10000);  // timeout 10 ms (for successive reads until end)
			while ($contents_length < $size) {
				$contents .= fread($this->socket, $size-$contents_length);
				$contents_length = strlen($contents);
			}

			if (($recvhandle & 0x80000000) == 0) {
				// this is a callback, not our answer! handle= $recvhandle, xml-rpc= $contents
				// just add it to the cb message list for the user to read
				$new_cb_message = new IXR_Message($contents);
				if ($new_cb_message->parse() && $new_cb_message->messageType != 'fault') {
					array_push($this->cb_message, array($new_cb_message->methodName, $new_cb_message->params));
				}
			}elseif((int)$recvhandle != (int)$this->reqhandle){
				// this is a not callback, and not our answer! handle= $recvhandle, xml-rpc= $contents
				// just add it to the async message list for the user to read
				$rech = sprintf("RH%u",(int)$recvhandle);
				//echo "Gbx:: received async {$rech}\n";
				if(array_key_exists($rech,$this->async_storeresp)){
					unset($this->async_storeresp[$rech]);
					$new_async_response = new IXR_Message($contents);
					if (!$new_async_response->parse()){
						$this->async_responses[$rech] = new IXR_Error(-32700, 'parse error. not well formed');
					}elseif ($new_async_response->messageType == 'fault') {
						$this->async_responses[$rech] = new IXR_Error($new_async_response->faultCode, $new_async_response->faultString);;
					}else{
						$this->async_responses[$rech] = $new_async_response->params[0];
					}
				}
			}
		} while ((int)$recvhandle != (int)$this->reqhandle);

		$this->message = new IXR_Message($contents);
		if (!$this->message->parse()) {
			// XML error
			$this->error = new IXR_Error(-32700, 'parse error. not well formed');
			return false;
		}
		// Is the message a fault?
		if ($this->message->messageType == 'fault') {
			$this->error = new IXR_Error($this->message->faultCode, $this->message->faultString);
			return false;
		}
		// Message must be OK
		return true;
	}

	// send query(method call) without waiting response
	// return the req handle which will be given when reading async responses
	function queryAsync() {
		$args = func_get_args();
		$method = array_shift($args);

		$this->error = false;
		if (!$this->socket || $this->protocol == 0) {
			$this->error = new IXR_Error(-32300, 'transport error - Client not initialized');
			return false;
		}
		if ($this->protocol == 1) {
			$this->error = new IXR_Error(-32800, 'transport error - wrong lowlevel protocol version to use async calls');
			return false;
		}

		$request = new IXR_Request($method, $args);
		$xml = $request->getXml();

		@stream_set_timeout($this->socket, 30);  // timeout 30 s (to write the request)
		// send request
		$this->reqhandle ++;
		if($this->reqhandle > 0x8FFFFFF0)
			 $this->reqhandle = 0x80000000;
		$reqh = sprintf("RH%u",(int)$this->reqhandle);
		$request = pack('VVa*', strlen($xml), $this->reqhandle, $xml);
		$sent = fwrite($this->socket, $request);

		//echo "Gbx:: sent async {$reqh} ($sent / ".strlen($request)." / ".strlen($xml).")\n";

		// indicate to store response
		$time = time();
		$this->async_storeresp[$reqh] = $time;
		// remove old (>2min) to store indications (should never happen)
		if(count($this->async_storeresp) > 20){
			$time -= 120;
			foreach($this->async_storeresp as $rh => $rt){
				if($rt < $time)
					unset($this->async_storeresp[$rh]);
			}
		}
		return $reqh;
	}

	// Read callbacks messages and async responses
	function readAsync($timeout = 2000) {  // timeout 2 ms
		// same as readCB() except the returned value
		readCB($timeout);
		return count($this->async_responses) > 0;
	}

	// Read callbacks messages and async responses
	function readCB($timeout = 2000) {  // timeout 2 ms
		// read callbacks and pending responses
		$this->error = false;
		if (!$this->socket || $this->protocol == 0) {
			$this->error = new IXR_Error(-32300, 'transport error - Client not initialized');
			return false;
		}
		if ($this->protocol == 1)
			return false;

		$something_received = count($this->cb_message)>0;
		$contents = '';
		$contents_length = 0;

		@stream_set_timeout($this->socket, 0, 10000);  // timeout 10 ms (to read available data)
		// (assignment in arguments is forbidden since php 5.1.1)
		$read = array($this->socket);
		$write = NULL;
		$except = NULL;
		$nb = stream_select($read, $write, $except, 0, $timeout);
		// workaround for stream_select bug with amd64
		if ($nb !== false)
			$nb = count($read);

		while ($nb !== false && $nb > 0) {
			$timeout = 0;  // we don't want to wait for the full time again, just flush the available data

			$size = 0;
			$recvhandle = 0;
			// Get result
			$attempts = 0;
			do {
				$contents = fread($this->socket, 8);
			} while($attempts++ < 200 && strlen($contents) == 0);
			if (strlen($contents) == 0) {
				$this->error = new IXR_Error(-32700, 'transport error - connection interrupted (failed to read 8 first bytes of callback/async)!');
				return false;
			}
			$array_result = multi_endian_unpack('Vsize/Vhandle', $contents);
			$size = $array_result['size'];
			$recvhandle = $array_result['handle'];

			if ($recvhandle == 0 || $size == 0) {
				$this->error = new IXR_Error(-32700, 'transport error - connection interrupted (failed to read callback/async datas)!');
				return false;
			}
			if ($size > 4096*1024) {
				$this->error = new IXR_Error(-32700, "transport error - answer too big ($size)");
				return false;
			}

			$contents = '';
			$contents_length = 0;
			while ($contents_length < $size) {
				$contents .= fread($this->socket, $size-$contents_length);
				$contents_length = strlen($contents);
			}

			if (($recvhandle & 0x80000000) == 0) {
				// (note) this is a callback ! handle= $recvhandle, xml-rpc= $contents
				//echo 'CALLBACK('.$contents_length.')[ '.$contents.' ]' . LF;
				$new_cb_message = new IXR_Message($contents);
				if ($new_cb_message->parse() && $new_cb_message->messageType != 'fault') {
					array_push($this->cb_message, array($new_cb_message->methodName, $new_cb_message->params));
				}
				$something_received = true;

			}else{
				// this is a not callback, so it's an async response ! handle= $recvhandle, xml-rpc= $contents
				// just add it to the async message list for the user to read
				$rech = sprintf("RH%u",(int)$recvhandle);
				//echo "Gbx:: received async {$rech}\n";
				if(array_key_exists($rech,$this->async_storeresp)){
					unset($this->async_storeresp[$rech]);
					$new_async_response = new IXR_Message($contents);
					if (!$new_async_response->parse()){
						$this->async_responses[$rech] = new IXR_Error(-32700, 'parse error. not well formed');
					}elseif ($new_async_response->messageType == 'fault') {
						$this->async_responses[$rech] = new IXR_Error($new_async_response->faultCode, $new_async_response->faultString);;
					}else{
						$this->async_responses[$rech] = $new_async_response->params[0];
					}
				}
			}

			// (assignment in arguments is forbidden since php 5.1.1)
			$read = array($this->socket);
			$write = NULL;
			$except = NULL;
			$nb = stream_select($read, $write, $except, 0, $timeout);
			// workaround for stream_select bug with amd64
			if ($nb !== false)
				$nb = count($read);
		}
		return $something_received;
	}

	// Get response of query()
	function getResponse() {
		// methodResponses can only have one param - return that
		return $this->message->params[0];
	}

	// Get callbacks messages
	// Callbacks are received in query(), readCB() and readAsync()
	// (look at the end of basic.php for an example)
	function getCBResponses() {
		$messages = $this->cb_message;
		$this->cb_message = array();
		return $messages;
	}

	// Get responses of async queries. 
	// Responses are received in query(), readCB() and readAsync()
	// Return an array of reqhandle=>response
	// For each response, in error case it will be an IXR_Error object
	// Use $reqhandle=false to get all received async responses and clear list
	function getAsyncResponses($reqhandle=false){
		if($reqhandle===false){
			$messages = $this->async_responses;
			$this->async_responses = array();
			return $messages;
		}elseif(isset($this->async_responses[$reqhandle])){
			$messages = array($reqhandle=>$this->async_responses[$reqhandle]);
			$this->async_responses[$reqhandle] = false;
			unset($this->async_responses[$reqhandle]);
		}
		return array();
	}

	function isError() {
		return is_object($this->error);
	}

	function resetError() {
		$this->error = false;
	}

	function getErrorCode() {
		return $this->error->code;
	}

	function getErrorMessage() {
		return $this->error->message;
	}
}




class IXR_ClientMulticall_Gbx extends IXR_Client_Gbx {
	var $calls = array();

	function addCall($methodName, $args) {
		$struct = array('methodName' => $methodName, 'params' => $args);
		$this->calls[] = $struct;

		return (count($this->calls) - 1);
	}

	function multiquery() {
		// Prepare multicall, then call the parent::query() method
		$result = parent::query('system.multicall', $this->calls);
		$this->calls = array();  // reset for next calls
		return $result;
	}
}




// Test if little endian or big endian
function big_endian_test(){
	global $is_little_endian;
	if(!isset($is_little_endian)){
		list($endiantest) = array_values(unpack('L1L', pack('V', 1)));
		if($endiantest == 1)
			$is_little_endian = true;
		else
			$is_little_endian = false;
	}
}

// this function include the workaround for unpack() in case of big endian
// big_endian_test() have to be called previously
function multi_endian_unpack($format, $data){
	global $is_little_endian;
	if($is_little_endian){
		return unpack($format, $data);
	}else{
		// The following code is a workaround for php's unpack function which
		// does not have the capability of unpacking double precision floats
		// that were packed in the opposite byte order of the current machine.
		$ar = unpack($format, $data);
		$vals = array_values($ar);
		$f = explode('/', $format);
		$i = 0;
		foreach ($f as $f_k => $f_v) {
			$repeater = intval(substr($f_v, 1));
			if ($repeater == 0)
				$repeater = 1;
			if ($f_v{1} == '*') {
				$repeater = count($ar) - $i;
			}
			if ($f_v{0} != 'd') {
				$i += $repeater;
				continue;
			}
			$j = $i + $repeater;
			for ($a = $i; $a < $j; ++$a) {
				$p = pack('d', $vals[$i]);
				$p = strrev($p);
				list($vals[$i]) = array_values(unpack('d1d', $p));
				++$i;
			}
		}
		$a = 0;
		foreach ($ar as $ar_k => $ar_v) {
			$ar[$ar_k] = $vals[$a];
			++$a;
		}
		return $ar;
	}
}
?>
