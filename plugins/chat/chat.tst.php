<?php
//¤
//registerCommand('tst','/tst xx');


// EMOTIC: away from keyboard
function chat_tst($author, $login, $params){
	$msg2  = 'A123456789,';
	$msg2 .= 'B123456789,';
	$msg2 .= 'C123456789,';
	$msg2 .= 'D123456789,';
	$msg2 .= 'E123456789,';
	$msg2 .= 'F123456789,';
	$msg2 .= 'G123456789,';
	$msg2 .= 'H123456789,';
	$msg2 .= 'I123456789,';
	$msg2 .= 'J123456789,';
	$msg2 .= 'K123456789,';
	$msg2 .= 'L123456789,';
	$msg2 .= 'M123456789,';
	$msg2 .= 'N123456789,';
	$msg2 .= 'O123456789,';
	$msg2 .= 'P123456789,';
	$msg2 .= 'Q123456789,';
	$msg2 .= 'R123456789,';
	$msg2 .= 'S123456789,';
	$msg2 .= 'T123456789,';
	$msg2 .= 'U123456789,';
	$msg2 .= 'V123456789,';
	$msg2 .= 'W123456789,';
	$msg2 = 'X123456789,';
	$msg2 .= 'Y123456789,';
	$msg2 .= 'Z123456789,';
	$msg = authorChat($login,$author).localeText(null,'emotic').'$800'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$b00'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$f00'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);

	$msg = authorChat($login,$author).localeText(null,'emotic').'$880'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$bb0'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$ff0'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);

	$msg = authorChat($login,$author).localeText(null,'emotic').'$080'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$0b0'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$0f0'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);

	$msg = authorChat($login,$author).localeText(null,'emotic').'$088'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$0bb'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$0ff'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);

	$msg = authorChat($login,$author).localeText(null,'emotic').'$008'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$00b'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$00f'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);

	$msg = authorChat($login,$author).localeText(null,'emotic').'$808'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$b0b'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	$msg = authorChat($login,$author).localeText(null,'emotic').'$f0f'.$msg2;
	addCall(null,'ChatSendToLogin', $msg, $login);
	//addCall(null,'ChatSendServerMessage', $msg);
	//addCall(null,'ChatSendToLogin', $msg, $login);
}
?>
