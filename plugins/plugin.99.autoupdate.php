<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      12.04.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////

if(!function_exists('zip_open')){
	console2("# Autoupdate plugin inactive: the module php_zip is missing !");

}else{

	registerPlugin('autoupdate',100);
}


function autoupdateInit($event){
	global $_debug;
	//if($_debug>2) console("autoupdate.Event[$event]");

	fastAutoupdate();
}


function autoupdatePlayerDisconnect($event,$login){
	global $_debug,$_PlayerList;
	if(count($_PlayerList)<=0){
		//if($_debug>2) console("autoupdate.Event[$event]('$login')");
		fastAutoupdate();
	}
}


function fastAutoupdate(){
	global $_debug,$_FASTver,$_autoupdateStop10 ;
	$updateurl = 'http://slig.info/fast3.2/update/fastupdate.version.txt';

	$updatexmlinfos = @file_get_contents($updateurl);
	if($updatexmlinfos===false){
		console2("\n#######################################\n# Autoupdate: update infos couldn't be read...\n");

	}else{
		console2("\n#######################################\n# Autoupdate: reading infos...\n");

		$updateinfos = xml_parse_string($updatexmlinfos);
		//debugPrint('fastAutoupdate - updateinfos',$updateinfos);

		if(isset($updateinfos['updates']['fast']['version']) && 
			 $updateinfos['updates']['fast']['version']>$_FASTver &&
			 isset($updateinfos['updates']['fast']['update_url'])){

			$updateinfos['updates']['fast']['file'] = basename($updateinfos['updates']['fast']['update_url']);
			if(fastAutoExtractFile($updateinfos['updates']['fast'])){

				if($_autoupdateStop10){ 
					console2("#####################################################\n"
									 ."# Update done: script is now stopped with errorlevel 10\n"
									 ."#  to indicate that a restart is needed.\n"
									 ."#####################################################\n");
					sleep(4);
					exit(10);

				}else{
					console2("#####################################################\n"
									 ."# Update done: restart Fast script please.\n"
									 ."#####################################################\n");
					sleep(4);
				}
			}
		}
	}
}


// return true if extraction was done successfully
// do nothing if file already exist
function fastAutoExtractFile($updateinfo){
	if(file_exists($updateinfo['file']))
		return false;
	
	console2("########\n# Autoupdate: get ".$updateinfo['file']);
	if(!copy($updateinfo['update_url'],$updateinfo['file'])){
		console2("# Failed to load file ".$updateinfo['file']);
		return false;
		
	}elseif(!unpackZip($updateinfo['file'])){
		console2("# Failed to unzip file ".$updateinfo['file']);
		return false;
		
	}
	console2("# Extraction done: ".$updateinfo['file']);
	return true;
}

?>
