<?php
//¤
// this is the easiest way to add custom messages on your server !

// just add a line with those infos :
//
//     registerEasyChat('command_name','message','help message for command');
//
// where:
//     message can contain {{xxx}}, which will be replaced by the chat command
//     arguments, or the xxx part is there were no argument.
//
//     optionnal 4th param : true  for admin only command (if not set then false)
// Do not modify this file : make a file 'easy_chat.custom.php',
// and put in it registerEasyChat() like previous ones



registerEasyChat('thx',' Thank you {{}} ! :)','/thx [name(s)] : show a thanks message');

registerEasyChat('stop',' STOOOOP {{ALL}} !!!','/stop [name(s)] : show a stooop message');

registerEasyChat('bb',' Bye {{}}','/bb [name(s)] : show a bye message');


?>
