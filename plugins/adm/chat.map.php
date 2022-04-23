<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 & 4.0 (First Automatic Server for Trackmania)
// Web:       
// Date:      22.09.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

registerCommand('map','/map loadms <matchsettings_path>, addms <matchsettings_path>, insertms <matchsettings_path>, add <map_path>, insert <map_path>, addmx <id>, insertmx <id>, addurl <mapurl>, inserturl <mapurl>, rem <mapname> (file path need the path under GameData/Tracks/ or UserData/Maps/).',true);


//------------------------------------------
// Laps Commands
//------------------------------------------
function chat_map($author, $login, $params, $params2){
	global $_debug,$_GameInfos,$_NextGameInfos,$doInfosNext,$_ServerOptions,$_ChallengeList,$_NextChallengeIndex,$_mxsites,$_ServerPackMask,$_Version;

	// verify if author is in admin list
	if(!verifyAdmin($login))
		return;

	if(!isset($params2[0]))
		$params2[0] = '';
	if(!isset($params2[1]))
		$params2[1] = '';
	if(!isset($params2[2]))
		$params2[2] = '';
	$help = true;

	// download and add challenge from MX id
	if($params2[0] == 'addmx'){
		if($params2[1] != ''){
			$mxid = $params2[1]+0;
			if($mxid > 0){
				$help = false;
				if(isset($_mxsites[$_Version['Name']][$_ServerPackMask]))
					$mxsite = $_mxsites[$_Version['Name']][$_ServerPackMask];
				else if(isset($_mxsites[$_Version['Name']]['Default']))
					$mxsite = $_mxsites[$_Version['Name']]['Default'];
				else
					$mxsite = null;

				if($mxsite !== null){
					$msg = localeText(null,'server_message').localeText(null,'interact')
					."Try to load then add MX map #{$mxid} from {$mxsite['Name']}...";
					addCall(null,'ChatSendToLogin', $msg, $login);
					
					mxGetMap($mxsite,$mxid,'',$login,'AddChallenge','map added');

				}else{
					$msg = localeText(null,'server_message').localeText(null,'interact')
					."No MX infos known for {$_ServerPackMask} or {$_Version['Name']} !";
					addCall(null,'ChatSendToLogin', $msg, $login);
				}
			}
		}

	// download and insert challenge from MX id
	}elseif($params2[0] == 'insertmx'){
		if($params2[1] != ''){
			$mxid = $params2[1]+0;
			if($mxid > 0){
				$help = false;
				if(isset($_mxsites[$_Version['Name']][$_ServerPackMask]))
					$mxsite = $_mxsites[$_Version['Name']][$_ServerPackMask];
				else if(isset($_mxsites[$_Version['Name']]['Default']))
					$mxsite = $_mxsites[$_Version['Name']]['Default'];
				else
					$mxsite = null;

				if($mxsite !== null){
					$msg = localeText(null,'server_message').localeText(null,'interact')
					."Try to load then insert MX map #{$mxid} from {$mxsite['Name']}...";
					addCall(null,'ChatSendToLogin', $msg, $login);
					
					mxGetMap($mxsite,$mxid,'',$login,'InsertChallenge','map inserted');

				}else{
					$msg = localeText(null,'server_message').localeText(null,'interact')
					."No MX infos known for {$_ServerPackMask} or {$_Version['Name']} !";
					addCall(null,'ChatSendToLogin', $msg, $login);
				}
			}
		}

	// download and add challenge from url
	}elseif($params2[0] == 'addurl'){
		if($params2[1] != ''){
			$mapurl = $params2[1];
			$help = false;
			
			$msg = localeText(null,'server_message').localeText(null,'interact')
			."Try to load then add map from {$mapurl}...";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
			mxGetMap(null,0,$mapurl,$login,'AddChallenge','map added');
		}

	// download and insert challenge from url
	}elseif($params2[0] == 'inserturl'){
		if($params2[1] != ''){
			$mapurl = $params2[1];
			$help = false;
			
			$msg = localeText(null,'server_message').localeText(null,'interact')
			."Try to load then insert map from {$mapurl}...";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
			mxGetMap(null,0,$mapurl,$login,'InsertChallenge','map inserted');
		}

	// remove challenge with part of mapname
	}elseif($params2[0] == 'rem' || $params2[0] == 'remove'){
		if(strlen($params2[1]) > 2){
			$help = false;
			$mapname = $params2[1];
			$num_maps = count($_ChallengeList);
			$found = false;
			for($n = 0; $n < $num_maps; $n++){
				$num = ($_NextChallengeIndex + $n) % $num_maps;
				if(isset($_ChallengeList[$num]['Name']) && stripos(stripColors($_ChallengeList[$num]['Name']),$mapname)){
					// found the name
					$found = true;
					$mapfile = $_ChallengeList[$num]['FileName'];
					$msg = localeText(null,'server_message').localeText(null,'interact')
					."Try to remove map {$_ChallengeList[$num]['Name']}\$z ({$mapfile}) ...";
					addCall(null,'ChatSendToLogin', $msg, $login);
					
					$action = array('CB'=>array('chatMapResponse',array($author,$login,"map removed: {$mapfile}")),'Login'=>$login);
					addCall($action,'RemoveChallenge', $mapfile);
					break;
				}
			}
			if(!$found){
				$msg = localeText(null,'server_message').localeText(null,'interact')
				."'{$mapname}' was not found in current maps list !";
				addCall(null,'ChatSendToLogin', $msg, $login);
			}
		}

	// add challenge with path
	}elseif($params2[0] == 'add'){
		if(stripos($params2[1],'.gbx') !== false){
			$help = false;
			$mapfile = $params2[1];

			$msg = localeText(null,'server_message').localeText(null,'interact')
			."Try to add map {$mapfile} ...";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
			$action = array('CB'=>array('chatMapResponse',array($author,$login,'map added from '.$mapfile)),'Login'=>$login);
			addCall($action,'AddChallenge', $mapfile);
		}

	// insert challenge with path
	}elseif($params2[0] == 'insert'){
		if(stripos($params2[1],'.gbx') !== false){
			$help = false;
			$mapfile = $params2[1];

			$msg = localeText(null,'server_message').localeText(null,'interact')
			."Try to insert map {$mapfile} ...";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
			$action = array('CB'=>array('chatMapResponse',array($author,$login,'map inserted from '.$mapfile)),'Login'=>$login);
			addCall($action,'InsertChallenge', $mapfile);
		}

	// add challenges list from matchsettings
	}elseif($params2[0] == 'addms'){
		if($params2[1] != ''){
			$help = false;
			$matchsettings = $params2[1];
			if(stripos($matchsettings,'.txt') === false)
				$matchsettings .= '.txt';

			$msg = localeText(null,'server_message').localeText(null,'interact')
			."Try to add maps from {$matchsettings} ...";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
			$action = array('CB'=>array('chatMapResponse',array($author,$login,'maps added from '.$matchsettings)),'Login'=>$login);
			addCall($action,'AppendPlaylistFromMatchSettings', $matchsettings);
		}

	// insert challenges list from matchsettings
	}elseif($params2[0] == 'insertms'){
		if($params2[1] != ''){
			$help = false;
			$matchsettings = $params2[1];
			if(stripos($matchsettings,'.txt') === false)
				$matchsettings .= '.txt';

			$msg = localeText(null,'server_message').localeText(null,'interact')
			."Try to insert maps from {$matchsettings} ...";
			addCall(null,'ChatSendToLogin', $msg, $login);
			
			$action = array('CB'=>array('chatMapResponse',array($author,$login,'maps inserted from '.$matchsettings)),'Login'=>$login);
			addCall($action,'InsertPlaylistFromMatchSettings', $matchsettings);
		}

	// load matchsettings
	}elseif(($params2[0] == 'loadms' && $params2[1] != '') || $params2[0] != ''){
		$help = false;
		$matchsettings = ($params2[0] == 'load') ? $params2[1] : $params2[0];
		if(stripos($matchsettings,'.txt') === false)
			$matchsettings .= '.txt';

		$msg = localeText(null,'server_message').localeText(null,'interact')
		."Try to load matchsettings {$matchsettings} ...";
		addCall(null,'ChatSendToLogin', $msg, $login);
		
		$action = array('CB'=>array('chatMapResponse',array($author,$login,'maps loaded from '.$matchsettings)),'Login'=>$login);
		addCall($action,'LoadMatchSettings', $matchsettings);
	}

	// help
	if($help){
		$msg = localeText(null,'server_message') . localeText(null,'interact')
		.'/map loadms <matchsettings_path>, addms <matchsettings_path>, insertms <matchsettings_path>, add <map_path>, insert <map_path>, addmx <id>, insertmx <id>, addurl <mapurl>, inserturl <mapurl>, rem <mapname> (file path need the path under GameData/Tracks/ or UserData/Maps/).';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}


// Handle AddChallenge/InsertChallenge/RemoveChallenge/AppendPlaylistFromMatchSettings/AppendPlaylistFromMatchSettings/LoadMatchSettings response
function chatMapResponse($author, $login, $msg){
	global $_debug,$_GameInfos,$_NextGameInfos,$doInfosNext,$_ServerOptions;
	global $_response,$_response_error;
	global $_ChallengeList;

	if($_response_error !== NULL)
		$msg = localeText(null,'server_message')."Error(".$_response_error['faultCode']."): ".localeText(null,'interact').$_response_error['faultString']." !";
	else{
		$msg = '';
		if(is_array($_response)){
			$sep = '';
			foreach($_response as $key => $val){
				$msg .= $sep.$key.'=';
				if(is_array($val)){
					$sep2 = '{';
					foreach($val as $key2 => $val2){
						$msg .= $sep2.$key2.'='.$val2;
						$sep2 = ',';
					}
					$msg .= '}';
				}else
					$msg .= $val;
				$sep = ',';
			}
		}else
			$msg .= $_response;
		$msg = localeText(null,'server_message')."Result: ".localeText(null,'interact').stripColors($msg);
		if(is_numeric($_response) && ($_response+0)>0)
			$msg .= " {$msg}.";
	}
	addCall(null,'ChatSendToLogin', $msg, $login);
}


// get a map from TMX/MX
// if not used or known, set:  $mxsite=null, $mxid=0, $url=''
function mxGetMap($mxsite,$mxid,$url,$login,$method,$msg){
	global $_webaccess;
	if($mxsite && $mxid > 0){
		$url = sprintf($mxsite['GetMapUrl'],$mxid);
		console("mxGetMap($mxsite,$mxid,$url,$login,$method,$msg):: getting MX #{$mxid} map ...");
		$_webaccess->request($url, array('mxReceiveMap',$mxsite,$mxid,$url,$login,$method,$msg),$url,false);

	}else if($url != ''){
		console("mxGetMap($mxsite,$mxid,$url,$login,$method,$msg):: getting map from url ...");
		$_webaccess->request($url, array('mxReceiveMap',null,0,$url,$login,$method,$msg),$url,false);

	}else{
		console("mxGetMap($mxsite,$mxid,$url,$login,$method,$msg):: bad request!");
	}
}


// Handle the map downloaded/received
function mxReceiveMap($datas,$mxsite,$mxid,$url,$login,$method,$msg){
	global $_TracksDirectory,$_Version;
	if(isset($datas['Message'])){
		$rep = 'DL/';
		$trackdir = $_TracksDirectory.$rep;
		$map = $datas['Message'];
		$size = strlen($map);

		if($mxid > 0 && isset($mxsite['MapName']))
			$filename = $rep.sprintf($mxsite['MapName'],$mxid);
		else{
			$name = basename($url);
			if(strrpos($name,'=') > 0)
				$name = substr($name,strrpos($name,'=')+1);
			if(strrpos($name,'#') > 0)
				$name = substr($name,0,strrpos($name,'#'));
			$name = preg_replace("/[^0-9a-zA-Z]/ixu", '', $name);
			$filename = $rep.$name;
		}

		$extension = ($_Version['Name'] == 'ManiaPlanet') ? '.Map.Gbx' : '.Challenge.Gbx';
		if(stripos($filename,$extension) === false)
			$filename .= $extension;

		$dest = $_TracksDirectory.$filename;
		if(!file_exists($trackdir) && !mkdir($trackdir)){
			// can't reach or create directory !
			console("mxReceiveMap($mxsite,$mxid,$url,$login,$method):: can't create folder {$trackdir} !");
			$msg = localeText(null,'server_message')."Failed to create folder {$trackdir} !";
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$size2 = file_put_contents($dest,$map);
			if($size2 != $size){
				console("mxReceiveMap($mxsite,$mxid,$url,$login,$method):: write on {$dest} failed ! ({$size2}/{$size})");
				$msg = localeText(null,'server_message')."Failed to write {$dest} ({$size2}/{$size}) !";
				addCall(null,'ChatSendToLogin', $msg, $login);

			}else{
				console("mxReceiveMap($mxsite,$mxid,$url,$login,$method):: {$filename} ({$size}) written => {$method} {$filename}");
				
				$msg = localeText(null,'server_message')."{$method} {$filename} !...";
				addCall(null,'ChatSendToLogin', $msg, $login);

				$action = array('CB'=>array('chatMapResponse',array($login,$login,$msg.": ".$filename)),'Login'=>$login);
				addCall($action, $method, $filename);
			}
		}
	}else{
		console("mxReceiveMap($mxsite,$mxid,$url,$login,$method):: failed. .".print_r($datas,true));
		$msg = localeText(null,'server_message')."Bad reply from {$url} !";
		addCall(null,'ChatSendToLogin', $msg, $login);
	}
}


// search a map or maplist from TMX/MX
function mxSearch($mxsite,$method,$msg){
	console("mxSearch($mxid,$mxsite,$method)::  todo...");
}


// Handle ManiaPlanet MX response/results
// if not used or known, set:  $mxid=0, $uid='', $author='', $trackname=''
function mxResultXmlConvert($datas,$mxsite,$mxid,$uid,$author,$trackname,$url,$login,$method,$msg){
	$maps = array();
	if(isset($datas['Message']) && strlen($datas['Message']) > 0){
		$xml = $datas['Message'];
		$infos = xml_parse_string($xml);

		if(isset($infos['ArrayOfTrackInfo']['TrackInfo']['.multi_same_tag.'])){
			unset($infos['ArrayOfTrackInfo']['TrackInfo']['.multi_same_tag.']);
			$maps = $infos['ArrayOfTrackInfo']['TrackInfo'];

		}else if(isset($infos['ArrayOfTrackInfo']['TrackInfo'])){
			$maps = array($infos['ArrayOfTrackInfo']['TrackInfo']);
			
		}else if(isset($infos['TrackInfo'])){
			$maps = array($infos['TrackInfo']);
			
		}else{
			console("mxResultXmlConvert($mxid,$uid,$author,$trackname,$login,$method):: {$url}\nraw xml??: ".print_r($xml,true));
		}

		if(count($maps) > 0){
			if(count($maps) > 1)
				$uid = '';

			foreach($maps as &$map){
				$map['UID'] = $uid;
				$map['UpdatedAt'] = '';
				$map['Comments'] = str_replace("\n","  ",tm_substr($map['Comments'],0,120));
			}

			// $map is an array of 1 or more maps infos
			console("mxResultXmlConvert($mxid,$uid,$author,$trackname,$login,$method):: {$url}\nMaps infos: ".print_r($maps,true));
			
		}
	}
}


// Handle TMF TMX response/results (mimic MX array values)
// if not used or known, set:  $mxid=0, $uid='', $author='', $trackname=''
// example:  2625091	MandraxTown	22033	DelNuelo	Race	Bay	Day	Normal	Single	5m	Intermediate	1703	2	1	False	TMUnitedForever	2814524	0	1		0		2010-03-1518:23:10	2010-03-1518:23:10	<BR>
function mxResultRawConvert($datas,$mxsite,$mxid,$uid,$author,$trackname,$url,$login,$method,$msg){
	$maps = array();
	if(isset($datas['Message']) && strlen($datas['Message']) > 0){
		$lines = explode('<BR>', $datas['Message']);
		foreach($lines as $line){
			$infos = explode("\t",$lines[$i]);

			$maps[] = array('TrackID' => $infos[0],
											'UserID' => $infos[2],
											'UploadedAt' => $infos[20],
											'Name' => $infos[1],
											'TypeName' => $infos[4],
											'StyleName' => $infos[7],
											'Mood' => $infos[6],
											'DisplayCost' => '?',
											'Lightmap' => '?',
											'ExeVersion' => '?',
											'ExeBuild' => '?',
											'EnvironmentName' => $infos[5],
											'RouteName' => $infos[8],
											'LengthName' => $infos[9],
											'Laps' => '?',
											'DifficultyName' => $infos[10],
											'ReplayTypeName' => '?',
											'ReplayWRID' => $infos[16],
											'ReplayCount' => 0,
											'Comments' => str_replace("\n","  ",tm_substr($infos[13],0,120)),
											'AwardCount' => $infos[12],
											'CommentCount' => 0,

											'UpdatedAt' => $infos[21],
											'UID' => '');
		}

		if(count($maps) > 0){
			if(count($maps) == 1)
				$maps[0]['UID'] = $uid;
			
			// $map is an array of 1 or more maps infos
			console("mxResultRawConvert($mxid,$uid,$author,$trackname,$login,$method):: {$url}\nMaps infos: ".print_r($maps,true));
			
		}
	}
}




global $_mxsites;
// TMF TMX infos
$_mxsites['TmForever']['Default'] = 
array('Name'=>'united.tm-exchange',
			'InfoConvertCB'=>'mxResultRawConvert',
			'MapName'=>'TMX%d.Challenge.Gbx',
			'SearchUrl'=>'http://united.tm-exchange.com/get.aspx?action=apisearch&author=%s&track=%s', // &page=
			'InfoUrl'=>'http://united.tm-exchange.com/get.aspx?action=apisearch&trackid=%d',
			'GetMapUrl'=>'http://united.tm-exchange.com/get.aspx?action=trackgbx&id=%d');

$_mxsites['TmForever']['United'] = $_mxsites['TmForever']['Default'];

$_mxsites['TmForever']['Stadium'] = 
array('Name'=>'tmnforever.tm-exchange',
			'InfoConvertCB'=>'mxResultRawConvert',
			'MapName'=>'TMX%d.Challenge.Gbx',
			'SearchUrl'=>'http://tmnforever.tm-exchange.com/get.aspx?action=apisearch&author=%s&track=%s', // &page=
			'InfoUrl'=>'http://tmnforever.tm-exchange.com/get.aspx?action=apisearch&trackid=%d',
			'GetMapUrl'=>'http://tmnforever.tm-exchange.com/get.aspx?action=trackgbx&id=%d');

// TM²/ManiaPlanet MX infos
$_mxsites['ManiaPlanet']['Default'] = 
array('Name'=>'tm.mania-exchange',
			'InfoConvertCB'=>'mxResultXmlConvert',
			'MapName'=>'MX%d.Map.Gbx',
			'SearchUrl'=>'http://tm.mania-exchange.com/tracksearch?api=on&format=xml&mode=0&vm=0&priord=2&author=%s&trackname=%s', // &page=
			'InfoUrl'=>'http://tm.mania-exchange.com/api/tracks/get_track_info/id/%d?format=xml',
			'GetMapUrl'=>'http://tm.mania-exchange.com/tracks/download/%d');

$_mxsites['ManiaPlanet']['Canyon'] = $_mxsites['ManiaPlanet']['Default'];



?>