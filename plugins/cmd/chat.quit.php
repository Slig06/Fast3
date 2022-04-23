<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      10.04.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////


registerCommand('quit','/quit : ask to be kicked from server');

// kick player who asked to quit
function chat_quit($author, $login, $params){
	addCall(true,'Kick',$login,"\n\n      \$w\$00fYou asked to quit the server !\$z\n\n");
}
?>
