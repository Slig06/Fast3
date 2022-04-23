<?php
//¤
// alternative base64 url compatible decode and encode functions
// written by Ferdinand Dosser
//

// urlsafe base64 alternative encode
function urlsafe_base64_encode($string) {
   $data = base64_encode($string);
   $data = str_replace(array('+','/','='),array('-','_',''),$data);
   return $data;
}

// urlsafe base64 alternative decode
function urlsafe_base64_decode($string) {
   $data = str_replace(array('-','_'),array('+','/'),$string);
   $mod4 = strlen($data) % 4;
   if ($mod4) {
       $data .= substr('====', $mod4);
   }
   return base64_decode($data);
}

?>