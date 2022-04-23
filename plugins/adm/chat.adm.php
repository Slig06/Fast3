<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      25.08.2011
// Author:    Gilles Masson
// Contributors: >LM<bibi for /adm map , Alex Granvaud for many original changes
//
////////////////////////////////////////////////////////////////

registerCommand('adm','please use /adm help',true);



//------------------------------------------
// Adm Commands
//------------------------------------------
function chat_adm($author, $login, $params, $params2){
	global $_modelist,$_ladderlist,$_laddermode,$_GameInfos,$_NextGameInfos,$_ServerOptions,$_players,$_autorestart_map,$_autorestart_newmap,$_autorestart_no,$_ChallengeList,$_CurrentChallengeIndex,$_NextChallengeIndex,$_map_control,$_ignore_list,$_ban_list,$_black_list,$_guest_list,$_Quiet,$_CallVoteTimeOut,$_DedConfig,$_StatusCode,$_ml_vote_ask,$_methods_list,$_LadderServerLimits,$_FGameModes,$_FGameMode,$_NextFGameMode;

	// verify if author is in admin list
	if(!verifyAdmin($login)){
		loadAdmins();
		if(!verifyAdmin($login))
			return;
	}

	// blacklist name
	if(isset($_DedConfig['blacklist_filename'])){
		$blacklist = trim($_DedConfig['blacklist_filename']);
		if(strlen($blacklist)<5)
			$blacklist = 'blacklist.txt';
	}
	else
		$blacklist = 'blacklist.txt';

	// guestlist name
	if(isset($_DedConfig['guestlist_filename'])){
		$guestlist = trim($_DedConfig['guestlist_filename']);
		if(strlen($guestlist)<5)
			$guestlist = 'guestlist.txt';
	}
	else
		$guestlist = 'guestlist.txt';

	//
	if(!isset($params[0]))
		$params[0] = 'help';
	if(!isset($params[1]))
		$params[1] = '';
	if(!isset($params2[1]))
		$params2[1] = '';

	// help
	if($params[0] == 'help'){
		$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm restart|rs [wu|np|q|quick|reset],next [#|envir|reset],prev [#|envir|reset],map [#],setnext [#|envir],setprev [#|envir],setmap [#],endround,kick,ban,unban,black,unblack,ignore,unignore,guest,mode,name,comment,srvpass,spectpass,pass,ftimeout,chattime,maxplayers,maxspec,opponents,ladder,voteratio,votetimeout,cancel,specforce,spec,play,free,autorestart,noautorestart,autonewrestart,noautonewrestart,replay,respawn,askvote';
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);


		// kick sucker
	}elseif($params[0] == 'kick'){
		
		if($params[1] == ''){
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm kick [login] : kicks user with login';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			addCall($login,'Kick',$params[1]);
		}

	
		// ban motherfucker
	}elseif($params[0] == 'ban'){
		
		if($params[1] == 'list'){
			$msg = localeText(null,'server_message') . localeText(null,'interact').'BanList : ';
			$sep = '';
			foreach($_ban_list as $blogin){
				$msg .= $sep.$blogin['Login'];
				$sep = ',';
			}
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg.'.', $login);

		}elseif($params[1] == '' || !isset($_players[$params[1]])){
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm ban [login] : bans user with login.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{ 
			addCall($login,'Ban',$params[1]);
		}

		// unban
	}elseif($params[0] == 'unban'){
		
		if($params[1] == 'cleanlist'){
			addCall($login,'CleanBanList');
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Ban list is now empty.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif($params[1] == '' || !isset($_ban_list[$params[1]])){
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm unban [login] : unbans user with login, /adm unban cleanlist : clean the ban list.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			addCall($login,'UnBan',$params[1]);
		}
		
		// blackist motherfucker
	}elseif($params[0] == 'black'){
		
		if($params[1] == ''){
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm black [login] : permanent bans user with login.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			if($params[1] == 'list'){
				$msg = localeText(null,'server_message') . localeText(null,'interact').'BlackList : ';
				$sep = '';
				foreach($_black_list as $blogin){
					$msg .= $sep.$blogin['Login'];
					$sep = ',';
				}
				// send message to user who wrote command
				addCall(null,'ChatSendToLogin', $msg.'.', $login);

			}else{
				addCall($login,'BlackList',$params[1]);
				addCall($login,'SaveBlackList',$blacklist);
			}
		}

		
		// blackist motherfucker
	}elseif($params[0] == 'unblack'){
		
		if($params[1] == 'cleanlist'){
			addCall($login,'CleanBlackList');
			addCall($login,'SaveBlackList','blacklist.txt');
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Black list is now empty.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif($params[1] == '' || !isset($_black_list[$params[1]])){
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm unblack [login] : remove user from blacklist, /adm unblack cleanlist : clean the black list.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			addCall($login,'UnBlackList',$params[1]);
			addCall($login,'SaveBlackList','blacklist.txt');
		}
		

		// ignore the boulet
	}elseif($params[0] == 'ignore'){
		
		if($params[1] == '' || !isset($_players[$params[1]])){
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm ignore [login] : ignore user with login.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			if($params[1] == 'list'){
				$msg = localeText(null,'server_message') . localeText(null,'interact').'IgnoreList : ';
				$sep = '';
				foreach($_ignore_list as $blogin){
					$msg .= $sep.$blogin['Login'];
					$sep = ',';
				}
				// send message to user who wrote command
				addCall(null,'ChatSendToLogin', $msg.'.', $login);

			}else{
				addCall($login,'Ignore',$params[1]);
			}
		}

		
		// ignore the boulet
	}elseif($params[0] == 'unignore'){
		
		if($params[1] == 'cleanlist'){
			addCall($login,'CleanIgnoreList');
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Ignore list is now empty.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif($params[1] == '' || !isset($_ignore_list[$params[1]])){
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm unignore [login] : unignore user with login, /adm unignore cleanlist : clean the ignore list.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			addCall($login,'UnIgnore',$params[1]);
		}

		
		// add guest user (who can enter on server when full)
	}elseif($params[0] == 'guest'){
		
		if($params[1] == ''){
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm guest [login] : add guest player (who can enter on server when full)';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
			
		}else{
			if($params[1] == 'list'){
				$msg = localeText(null,'server_message') . localeText(null,'interact').'GuestList : ';
				$sep = '';
				foreach($_guest_list as $blogin){
					$msg .= $sep.$blogin['Login'];
					$sep = ',';
				}
				// send message to user who wrote command
				addCall(null,'ChatSendToLogin', $msg.'.', $login);

			}else{
				addCall($login,'AddGuest',$params[1]);
				addCall($login,'SaveGuestList',$guestlist);
				$msg = localeText(null,'server_message') . localeText(null,'interact')."{$params[1]} added to guestlist !";
				addCall(null,'ChatSendToLogin', $msg, $login);
			}
		}
		

		// restart track
	}elseif($params[0] == 'restart' || $params[0] == 'rs'){
		if($_map_control == false){
			$msg = localeText(null,'server_message').localeText(null,'interact').'This command is currently disabled.';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif($params2[1] == 'wu' || $params2[1] == 'warmup' || $params2[1] == 'reset'){
			// build list to have current as next
			mapRealRestart($login, ($params2[1] == 'reset') );
			
			if($params2[1] == 'reset' && isset($_methods_list['CheckEndMatchCondition']) && $_GameInfos['GameMode'] == 5)
				$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) restart challenge (with score reset) !";
			else
				$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) restart challenge (with warmup if active) !";
			// send message in offical chat
			addCall(null,'ChatSendServerMessage', $msg);
			
		}elseif(($params2[1] == 'quick' || $params2[1] == 'q') && $_StatusCode <= 4){
			mapQuickRestart($login);
			
			$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) quick restart ! (like a warmup end)";
			// send message in offical chat
			addCall(null,'ChatSendServerMessage', $msg);

		}elseif(($params2[1] == 'nopodium' || $params2[1] == 'np' || $params2[1] == 'quick2' || $params2[1] == 'q2') && $_StatusCode <= 4){
			mapQuickRestartNP($login);
			
			$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) quick restart ! (without podium)";
			// send message in offical chat
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			$_autorestart_no = true;
			if(isset($_methods_list['CheckEndMatchCondition']) && $_GameInfos['GameMode'] == 5)
				addCall($login,'ChallengeRestart',true);
			else
				addCall($login,'ChallengeRestart');
			$msg = localeText(null,'server_message').$author.localeText(null,'interact').' (admin) forced a restart !';
			// send message in offical chat
			addCall(null,'ChatSendServerMessage', $msg);
		}


		// next / previous track
	}elseif($params[0] == 'next' || $params[0] == 'prev' || $params[0] == 'previous' ||
					$params[0] == 'setnext' || $params[0] == 'setprev' || $params[0] == 'setprevious'){
		if($_map_control == false){
			$msg = localeText(null,'server_message').localeText(null,'interact').'This command is currently disabled.';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif($params[0] == 'next' && ($params2[1] == '' || $params2[1] == 'reset')){
			$_autorestart_no = true;
			// basic next : just do a next challenge
			if(isset($_methods_list['CheckEndMatchCondition']) && $_GameInfos['GameMode'] == 5 && $params2[1] != 'reset')
				addCall($login,'NextChallenge',true);
			else
				addCall($login,'NextChallenge');
			$msg = localeText(null,'server_message').$author.localeText(null,'interact').' (admin) forced next challenge !';
			// send message in offical chat
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			$donext = true;
			if($params[0] == 'setnext'){
				$params[0] = 'next';
				$donext = false;
			}elseif($params[0] == 'setprev' | $params[0] == 'setprevious'){
				$params[0] = 'prev';
				$donext = false;
			}
			$_autorestart_no = true;
			if($params2[1] == ''){
				$arg = 1;
			}elseif($params2[1] == 'reset'){
				$arg = 1;
				$params2[2] = 'reset';
			}else
				$arg = $params2[1];

			$num = false;
			$clist = buildMapList($params[0],$arg,$num);

			if($clist === false){
				$msg = localeText(null,'server_message').$author.localeText(null,'interact')." '{$params2[1]}' is not a environment or a part of challenge name ! "
					.'usage: /adm next [reset], /adm next # [reset], /adm next <envir> [reset], /adm next <mapname part> [reset], /adm prev [reset], /adm prev # [reset], /adm prev <envir> [reset], /adm prev <mapname part> [reset] (to modify map order use: /adm map #[,#...]), /adm setnext #, /adm setprev #';
				// send message to user who wrote command
				addCall(null,'ChatSendToLogin', $msg, $login);
					
			}else{
				addCall($login,'ChooseNextChallengeList',$clist);
				if($donext){
					if(isset($_methods_list['CheckEndMatchCondition']) && $_GameInfos['GameMode'] == 5 && (!isset($params2[2]) || $params2[2] != 'reset'))
						addCallDelay(1000,$login,'NextChallenge',true);
					else
						addCallDelay(1000,$login,'NextChallenge');
				}
				$next = ($num>=0)? 'next '.($num) : ( ($num < -1)? 'previous '.(-$num) : 'previous' );
				if($donext)
					$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) forced $next challenge !";
				else
					$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) selected $next challenge !";
				// send message in offical chat
				addCall(null,'ChatSendServerMessage', $msg);
			}
		}


		// choose next map(s) -          thx to >LM<bibi for first version  :)
	}elseif(isset($params[0]) && ($params[0] == 'map' || $params[0] == 'setmap')){
		if($_map_control == false){
			$msg = localeText(null,'server_message').localeText(null,'interact').'This command is currently disabled.';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$clsize = count($_ChallengeList);
			$donext = true;
			if($params[0] == 'setmap'){
				$params[0] = 'map';
				$donext = false;
			}
			
			if($params[1] != '' && is_numeric($params[1]) && $clsize > 0){
				// choose 1 next challenge
				
				$num = ($params[1]+0) % $clsize;
				$_autorestart_no = true;			
				addCall($login,'ChooseNextChallenge',$_ChallengeList[$num]['FileName']);
				if($donext)
					addCallDelay(1000,$login,'NextChallenge');
				
				if($donext)
					$msg = localeText(null,'server_message').$author.localeText(null,'interact').' (admin) forced challenge : '.stripColors($_ChallengeList[$num]['Name']).' $n$ffa, '.$_ChallengeList[$num]['Environnement'].' $cec, '.$_ChallengeList[$num]['Author'].' !';
				else
					$msg = localeText(null,'server_message').$author.localeText(null,'interact').' (admin) selected challenge : '.stripColors($_ChallengeList[$num]['Name']).' $n$ffa, '.$_ChallengeList[$num]['Environnement'].' $cec, '.$_ChallengeList[$num]['Author'].' !';
				// send message in offical chat
				addCall(null,'ChatSendServerMessage', $msg);
				
			}elseif($params[1] != '' && $params[1] != 'help' && $clsize > 0){
				// choose multi next challenges : build list from comma separated map nums list
				
				$mlist = array_unique(explode(',',$params[1]));
				$clist = array();
				foreach($mlist as $mapnum){
					$ind = (trim($mapnum)+0) % $clsize;
					$clist[] = $_ChallengeList[$ind]['FileName'];
				}
				$clist = array_values(array_unique($clist));
				
				if(count($clist)>0){
					addCall($login,'ChooseNextChallengeList',$clist);
					if($donext)
						addCallDelay(1000,$login,'NextChallenge');
					
					if($donext)
						$msg = localeText(null,'server_message').$author.localeText(null,'interact').' (admin) forced a new challenges list !';
					else
						$msg = localeText(null,'server_message').$author.localeText(null,'interact').' (admin) selected a new challenges list !';
					// send message in offical chat
					addCall(null,'ChatSendServerMessage', $msg);
					
				}else{
					$msg = localeText(null,'server_message').localeText(null,'interact').'Bad usage or no map on server (/adm map #[,#...])';
					// send message to user who wrote command
					addCall(null,'ChatSendToLogin', $msg, $login);
				}
				
			}else{
				$player = array();
				$msg = localeText(null,'server_message').localeText(null,'interact').'Choose next challenge(s) using /adm map #[,#...] :';// ('.$_CurrentChallengeIndex.','.$_NextChallengeIndex.')';
				if(is_array($_ChallengeList) && count($_ChallengeList)>0){
					$maxi = $clsize;
					if($maxi<=12)
						$sep = "\n ";
					else
						$sep = '   ';				
					if($maxi > 50)
						$maxi = 50;
					$sep .= localeText(null,'server_message').'%s>>%s '.localeText(null,'interact');
					for($index = $_NextChallengeIndex; $index < $_NextChallengeIndex+$maxi; $index++){
						$ind = $index % $clsize;
						$color = ($ind == $_CurrentChallengeIndex)? '$f00' : (($ind == $_NextChallengeIndex)? '$00f' : '');
						$msg .= sprintf($sep,$color,$ind.'-').stripColors($_ChallengeList[$ind]['Name']).' $n$ffa, '
							.$_ChallengeList[$ind]['Environnement'].' $cec, '.$_ChallengeList[$ind]['Author'];
					}
				}
				// send message to user who wrote command
				addCall(null,'ChatSendToLogin', $msg, $login);
			}
		}


		// shuffle maps
	}elseif($params[0] == 'shuffle'){
		if($_map_control == false){
			$msg = localeText(null,'server_message').localeText(null,'interact').'This command is currently disabled.';
			addCall(null,'ChatSendToLogin', $msg, $login);

		}elseif($params[1] != ''){
			$_autorestart_no = true;
			
			$arg = $params[1]+0;
			$clist = mapsShuffle($_ChallengeList,$arg);

			addCall($login,'ChooseNextChallengeList',$clist);
			addCallDelay(1000,$login,'NextChallenge');
					
			$msg = localeText(null,'server_message').$author.localeText(null,'interact')." (admin) made a challenges shuffle (type $arg) on ".count($clist).' / '.count($_ChallengeList).' maps !';
			// send message in offical chat
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			$msg = localeText(null,'server_message').localeText(null,'interact').'usage: /adm shuffle <type> : suffle maps with envir repartition and random order in each envir. <type> 0 use all existing envirs in the list from begining as possible. <type> from 1 to 7 will do a full repartition, but limiting the map number by envir to the <type>th bigger list. Using 1, 2 or 3 is probably the best.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// force end of round
	}elseif($params[0] == 'endround' || $params[0] == 'end'){
		$msg = localeText(null,'server_message').">>\n".$author.localeText(null,'interact')." (admin) forced end of round !\nNew round !\n"
			.'$o$0ffGooooo !!! $ff0Gooooo !!! $f00Gooooo !!!';
		// send message in offical chat
		addCall(null,'ChatSendServerMessage', $msg);
		addCallDelay(20,$login,'ForceEndRound');


		// game mode
	}elseif($params[0] == 'mode'){
		$fgmode = '';

		if($params[1] != ''){
			for($gmode = 0; $gmode < count($_modelist); $gmode++){
				if(strcasecmp($_modelist[$gmode],$params[1]) == 0)
					break;
			}
			if($params[1] == 'round') // special case for 'round'
				$gmode = ROUNDS;
			if($params[1] == 'ta') // special case for 'ta'
				$gmode = TA;
			if($params[1] == 'lap') // special case for 'lap'
				$gmode = LAPS;
			if($params[1] == 'stunt') // special case for 'stunt'
				$gmode = STUNTS;
			if($gmode >= count($_modelist) && is_numeric($params[1]))
				$gmode = $params[1]+0;

			if(!isset($_modelist[$gmode])){
				// test FastGameModes
				foreach($_FGameModes as $fgmodename => &$fgamemode){
					if(strcasecmp($fgmodename,$params[1]) == 0 || 
						 (isset($fgamemode['Aliases']) && array_search($params[1],$fgamemode['Aliases']) !== false)){
						$fgmode = $fgmodename;
						break;
					}
				}
			}

			if(isset($_modelist[$gmode])){
				// classic gamemode
				setNextFGameMode('');
				addCall($login,'SetGameMode',$gmode);
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Mode to '.localeText(null,'highlight').$_modelist[$gmode].' ('.$gmode.') ! (need a restart)';

			}elseif($fgmode != '' && isset($_FGameModes[$fgmode]['GameInfos']['GameMode'])){
				// fgamemode
				setNextFGameMode($fgmode);
				//addCall($login,'SetGameMode',$_FGameModes[$fgmode]['GameInfos']['GameMode']);
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting FGameMode to '.localeText(null,'highlight').$_NextFGameMode.' ! (need a restart)';

			}else{
				$fgmodes = implode(',',array_keys($_FGameModes));
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Bad mode: '.$params[1]. " [Rounds(0), TimeAttack(1), Team(2), Laps(3), Stunts(4), Cup(5), {$fgmodes}]";
			}
		}else{
			$val = ($_FGameMode != '') ? $_FGameMode : $_modelist[$_GameInfos['GameMode']];
			$val2 = ($_NextFGameMode != '') ? $_NextFGameMode : $_modelist[$_NextGameInfos['GameMode']];
			$fgmodes = implode(',',array_keys($_FGameModes));
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Current GameMode: '.$val.', Next GameMode: '.$val2."  [Rounds(0), TimeAttack(1), Team(2), Laps(3), Stunts(4), Cup(5), {$fgmodes}]";
		}
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);


		// ladder mode
	}elseif($params[0] == 'ladder'){
		if($params[1] != ''){
			$lmode = strtolower($params[1]);
			if(isset($_laddermode[$lmode]))
				$lmode = $_laddermode[$lmode];
			else
				$lmode = $params[1]+0;
			if(isset($_ladderlist[$lmode])){
				addCall($login,'SetLadderMode',$lmode);
				// send success message
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting LadderMode to '.localeText(null,'highlight').$_ladderlist[$lmode].' ('.$lmode.') ! (need a restart)';
			}else{
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Bad LadderMode: '.$params[1]. ' ['.implode(',',$_ladderlist).']';
			}
		}else{
			$val = $_ladderlist[$_ServerOptions['CurrentLadderMode']];
			$val2 = $_ladderlist[$_ServerOptions['NextLadderMode']];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Current LadderMode: '.$val.', Next: '.$val2;
		}
		// send message to user who wrote command
		addCall(null,'ChatSendToLogin', $msg, $login);


		// srvname
	}elseif($params[0] == 'srvname' || $params[0] == 'name'){
		if($params[1] != '') {
			addCall($login,'SetServerName',$params[1]);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting ServerName to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_ServerOptions['Name'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'ServerName: '.$val;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// comment
	}elseif($params[0] == 'comment'){
		if($params[1] != '') {
			addCall($login,'SetServerComment',$params[1]);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting ServerComment to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_ServerOptions['Comment'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'ServerComment: '.$val;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// server+spectator password
	}elseif($params[0] == 'pass'){
		if(isset($_LadderServerLimits['LadderServerLimitMax']) &&
			 $_LadderServerLimits['LadderServerLimitMax'] > 50000){

			$msg = localeText(null,'server_message').localeText(null,'interact').'Ladder servers have to be public ! Setting a password is forbidden';
			addCall(null,'ChatSendToLogin', $msg, $login);
			addCall(null,'SetServerPassword','');
			addCall(null,'SetServerPasswordForSpectator','');

		}elseif($params[1] != ''){
			$srvpass = $params[1];
			if($srvpass ==  'none')
				$srvpass = '';
			addCall($login,'SetServerPassword',$srvpass);
			addCall($login,'SetServerPasswordForSpectator',$srvpass);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting Spectator and ServerPassword to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_ServerOptions['Password'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'ServerPassword: '.$val.'  SpectPass: '.$_ServerOptions['PasswordForSpectator']." (set 'none' to remove)";
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// server password
	}elseif($params[0] == 'srvpass'){
		if(isset($_LadderServerLimits['LadderServerLimitMax']) &&
			 $_LadderServerLimits['LadderServerLimitMax'] > 50000){

			$msg = localeText(null,'server_message').localeText(null,'interact').'Ladder servers have to be public ! Setting a password is forbidden';
			addCall(null,'ChatSendToLogin', $msg, $login);
			addCall(null,'SetServerPassword','');
			addCall(null,'SetServerPasswordForSpectator','');

		}elseif($params[1] != '') {
			$srvpass = $params[1];
			if($srvpass == 'none')
				$srvpass = '';
			addCall($login,'SetServerPassword',$srvpass);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting ServerPassword to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_ServerOptions['Password'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'ServerPassword: '.$val." (set 'none' to remove)";
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// server password
	}elseif($params[0] == 'spectpass'){
		if(isset($_LadderServerLimits['LadderServerLimitMax']) &&
			 $_LadderServerLimits['LadderServerLimitMax'] > 50000){

			$msg = localeText(null,'server_message').localeText(null,'interact').'Ladder servers have to be public ! Setting a password is forbidden';
			addCall(null,'ChatSendToLogin', $msg, $login);
			addCall(null,'SetServerPassword','');
			addCall(null,'SetServerPasswordForSpectator','');

		}elseif($params[1] != '') {
			$srvpass = $params[1];
			if($srvpass == 'none')
				$srvpass = '';
			addCall($login,'SetServerPasswordForSpectator',$srvpass);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting SpectatorPassword to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_ServerOptions['Password'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'SpectPass: '.$_ServerOptions['PasswordForSpectator']." (set 'none' to remove)";
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// max players
	}elseif($params[0] == 'maxplayers' || $params[0] == 'max'){
		if($params[1] != '') {
			addCall($login,'SetMaxPlayers',$params[1]+0);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting MaxPlayers to '.localeText(null,'highlight').$params[1].' !';
			if(isset($_LadderServerLimits['LadderServerLimitMax']) &&
			 $_LadderServerLimits['LadderServerLimitMax'] >= 60000)
				$msg .= "\nThis is a ladder server, be sure to set appropriate maxplayers value !";
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_ServerOptions['CurrentMaxPlayers'];
			$val2 = $_ServerOptions['NextMaxPlayers'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'MaxPlayers: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// max specs
	}elseif($params[0] == 'maxspec'){
		if($params[1] != '') {
			addCall($login,'SetMaxSpectators',$params[1]+0);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting MaxSpectators to '.localeText(null,'highlight').$params[1].' !';
			if(isset($_LadderServerLimits['LadderServerLimitMax']) &&
			 $_LadderServerLimits['LadderServerLimitMax'] >= 60000)
				$msg .= "\nThis is a ladder server, be sure to set appropriate maxspec value !";
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_ServerOptions['CurrentMaxSpectators'];
			$val2 = $_ServerOptions['NextMaxSpectators'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'MaxSpectators: '.$val.' next: '.$val2;
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// FinishTimeout
	}elseif($params[0] == 'ftimeout' || $params[0] == 'fto' || $params[0] == 'finishtimeout'){
		if($params[1] != '') {
			$ftimeout = $params[1]+0;
			if($ftimeout>2 && $ftimeout<1000)
				$ftimeout *= 1000;
			addCall($login,'SetFinishTimeout',$ftimeout);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting FinishTimeout to '.localeText(null,'highlight').$ftimeout.' (ms) !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_GameInfos['FinishTimeout'];
			$val2 = $_NextGameInfos['FinishTimeout'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'FinishTimeout: '.$val.' next: '.$val2.' [timeout to finish after first (ms), 0=default, 1=adaptative]';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// ChatTime
	}elseif($params[0] == 'ctime' || $params[0] == 'chattime'){
		if($params[1] != '') {
			$ctime = $params[1]+0;
			if($ctime<1)
				$ctime = 1000;
			elseif($ctime<1000)
				$ctime *= 1000;
			addCall($login,'SetChatTime',$ctime);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting ChatTime to '.localeText(null,'highlight').$ctime.' (ms) !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_GameInfos['ChatTime'];
			$val2 = $_NextGameInfos['ChatTime'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'ChatTime: '.$val.' next: '.$val2.' [time to chat at end of track (ms)]';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// ShowAllOpponents
	}elseif($params[0] == 'opp' || $params[0] == 'opponents' || $params[0] == 'showopponents'){
		if($params[1] != ''){
			if(strcasecmp($params[1],'true') == 0)
				$opp = 4;
			elseif(strcasecmp($params[1],'false') == 0 || $params[1]+0 <= 0)
				$opp = 0;
			else
				$opp = $params[1]+0;
			addCall($login,'SetForceShowAllOpponents',$opp);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting ForceShowAllOpponents to '.localeText(null,'highlight').$opp.($opp == 0 ? ' (off)': ($opp == 1 ? ' (all)': '(min opps)')).' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_GameInfos['ForceShowAllOpponents'];
			$val2 = $_NextGameInfos['ForceShowAllOpponents'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'ForceShowAllOpponents: '.$val.' next: '.$val2.' [0=off/1=all/value=min opps]';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// vote ratio
	}elseif($params[0] == 'voteratio' || $params[0] == 'vratio'){
		if($params[1] != '') {
			addCall($login,'SetCallVoteRatio',$params[1]+0.0);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting CallVoteRatio to '.localeText(null,'highlight').$params[1].' !';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_ServerOptions['CallVoteRatio'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'CallVoteRatio: '.$val.' [vote ratio for success, 0 to 1]';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// vote timeout
	}elseif($params[0] == 'votetimeout' || $params[0] == 'vtimeout' || $params[0] == 'vto'){
		if($params[1] != '') {
			$vtime = $params[1]+0;
			if($vtime>-1000 && $vtime<1000)
				$vtime *= 1000;
			if(isset($_CallVoteTimeOut))
				$_CallVoteTimeOut = $vtime;
			if($vtime<0)
				$vtime = 0;
			addCall($login,'SetCallVoteTimeOut',$vtime);
			// send success message
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Setting CallVoteTimeOut to '.localeText(null,'highlight').$vtime.' (ms) !'.($_CallVoteTimeOut<0 ? ' (automatic)' : '');
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);

		}else{
			$val = $_ServerOptions['CurrentCallVoteTimeOut'];
			$val2 = $_ServerOptions['NextCallVoteTimeOut'];
			$msg = localeText(null,'server_message') . localeText(null,'interact').'CallVoteTimeOut: '.$val.' next: '.$val2.' [time to vote (ms)], set negative value to enable automatic feature when admin are present.';
			// send message to user who wrote command
			addCall(null,'ChatSendToLogin', $msg, $login);
		}


		// cancel vote
	}elseif($params[0] == 'cancel' || $params[0] == 'cancelvote'){
		$msg = localeText(null,'server_message').">>\n".$author.localeText(null,'interact').' current vote cancelled.';
		// send message
		addCall(null,'ChatSendServerMessageToLogin', $msg, $login);
		//addCall(null,'ChatSendServerMessage', $msg);
		addCall(null,'CancelVote');

		// force spec
	}elseif(isset($params[0]) && $params[0] == 'specforce'){
		if($params[1] != '' && isset($_players[$params[1]])){
			addCall($login,'ForceSpectator',$params[1],1);
			addCall(null,'GetPlayerInfo',$params[1]);
			if(isset($_players[$params[1]]))
				$_players[$params[1]]['ForcedByHimself'] = false;

			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
				.' (admin) forced '.stripColors($_players[$params[1]]['Login']).' to spec !';
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm specforce login : force to spec';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}
		
		// spec
	}elseif(isset($params[0]) && $params[0] == 'spec'){
		if($params[1] != '' && isset($_players[$params[1]])){
			addCall($login,'ForceSpectator',$params[1],1);
			addCall($login,'ForceSpectator',$params[1],0);
			addCall(null,'GetPlayerInfo',$params[1]);

			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
				.' (admin) made '.stripColors($_players[$params[1]]['Login']).' to spec !';
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm spec login : make player to spec';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		
		// free spec/play
	}elseif(isset($params[0]) && $params[0] == 'free'){
		if($params[1] != '' && isset($_players[$params[1]])){
			addCall($login,'ForceSpectator',$params[1],0);

			$msg = localeText(null,'server_message').$author.localeText(null,'interact')
				.' (admin) released '.stripColors($_players[$params[1]]['Login']).' !';
			addCall(null,'ChatSendServerMessage', $msg);

		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm free login : release player from forced spec or play state';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		
		// play
	}elseif(isset($params[0]) && $params[0] == 'play'){
		if($params[1] != '' && isset($_players[$params[1]])){
			if(addCall($login,'ForceSpectator',$params[1],2) !== -1){
				addCall($login,'ForceSpectator',$params[1],0);
				addCall(null,'GetPlayerInfo',$params[1]);
				
				$msg = localeText(null,'server_message').$author.localeText(null,'interact')
					.' (admin) made '.stripColors($_players[$params[1]]['Login']).' to play !';
				addCall(null,'ChatSendServerMessage', $msg);

			}else{
				$msg = localeText(null,'server_message') . localeText(null,'interact').'refused : too many players !';
				addCall(null,'ChatSendToLogin', $msg, $login);
			}

		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm play login : make player to play';
			addCall(null,'ChatSendToLogin', $msg, $login);
		}

		
		// autorestart
	}elseif(isset($params[0]) && $params[0] == 'autorestart'){
		$_autorestart_map = true;
		$msg = localeText(null,'server_message') . localeText(null,'interact').'Map autorestart is now ON !';
		addCall(null,'ChatSendToLogin', $msg, $login);

		
		// noautorestart
	}elseif(isset($params[0]) && $params[0] == 'noautorestart'){
		$_autorestart_map = false;
		$msg = localeText(null,'server_message') . localeText(null,'interact').'Map autorestart is now OFF !';
		addCall(null,'ChatSendToLogin', $msg, $login);


		// autonewrestart
	}elseif(isset($params[0]) && $params[0] == 'autonewrestart'){
		if($params[1] != '')
			$_autorestart_newmap = $params[1];
		$msg = localeText(null,'server_message') . localeText(null,'interact').'New map autorestart is '.$_autorestart_newmap.' ! (on|check|finish|round|off)';
		addCall(null,'ChatSendToLogin', $msg, $login);

		
		// noautonewrestart
	}elseif(isset($params[0]) && $params[0] == 'noautonewrestart'){
		$_autorestart_newmap = false;
		$msg = localeText(null,'server_message') . localeText(null,'interact').'New map autorestart is now OFF !';
		addCall(null,'ChatSendToLogin', $msg, $login);

		// quiet
	}elseif(isset($params[0]) && $params[0] == 'quiet'){
		if($params[1] == 'on'){
			if(!$_Quiet){
				$_Quiet = true;
				addCall(null,'SendHideManialinkPage');
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Quiet mode is now active !';
			}else
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Quiet mode already active !';

		}elseif($params[1] == 'off'){
			if($_Quiet){
				$_Quiet = false;
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Quiet mode is now inactive !';
			}else
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Quiet mode already inactive !';

		}else{
			if($_Quiet)
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Quiet mode is active !';
			else
				$msg = localeText(null,'server_message') . localeText(null,'interact').'Quiet mode is inactive !';
		}
		addCall(null,'ChatSendToLogin', $msg, $login);
		
		// auto replays
	}elseif(isset($params[0]) && $params[0] == 'replay'){
		if($params[1] == 'off'){
			addCall($login,'AutoSaveReplays',false);
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Unactivating AutoSaveReplays !...';

		}elseif($params[1] == 'on'){
			addCall($login,'AutoSaveReplays',true);
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Activating AutoSaveReplays !...';

		}elseif($params[1] == 'save'){
			addCall($login,'SaveCurrentReplay','');
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Saving current replay !...';

		}else{
			addCall($login,'IsAutoSaveReplaysEnabled');
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm replay on|off : for AutoSaveReplays, /adm replay save : to save occasional replay !';
		}
		addCall(null,'ChatSendToLogin', $msg, $login);
		
		// respawn
	}elseif(isset($params[0]) && $params[0] == 'respawn'){
		if($params[1] == 'off'){
			addCall($login,'SetDisableRespawn',true);
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Respawn disabled !...';

		}elseif($params[1] == 'on'){
			addCall($login,'SetDisableRespawn',false);
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Respawn enabled !...';

		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm respawn on|off : enable/disable respawn !';
		}
		addCall(null,'ChatSendToLogin', $msg, $login);
		
		// map vote ask
	}elseif(isset($params[0]) && $params[0] == 'askvote'){
		if($params[1] == 'off'){
			$_ml_vote_ask = false;
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Map vote ask disabled !...';

		}elseif($params[1] == 'on'){
			$_ml_vote_ask = true;
			$msg = localeText(null,'server_message') . localeText(null,'interact').'Map vote ask  enabled !...';

		}else{
			$msg = localeText(null,'server_message') . localeText(null,'interact').'/adm vote on|off : enable/disable map vote ask !';
		}
		addCall(null,'ChatSendToLogin', $msg, $login);
		
	}

}

?>


