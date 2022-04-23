<?php
//=============================================
//¤ XML PARSER 1.0
// (c) 2006 by Gilles Masson
//=============================================


// parse xml string to assossiative array
// (array built with attrmix mode will not be able to be build in the same xml)
function xml_parse_string($xmlstr,$attrmix=false){
	global $_xml_parser_values;
	$res = array();

	if(strlen($xmlstr)>5){
		$parser = xml_parser_create('UTF-8');
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
		xml_parser_set_option($parser,XML_OPTION_TARGET_ENCODING,'UTF-8');
		xml_parse_into_struct($parser,$xmlstr,$_xml_parser_values,$tags);
		xml_parser_free($parser);
		//debugPrint("xml_parse_string - _xml_parser_values",$_xml_parser_values);
		//debugPrint("xml_parse_string - tags",$tags);
		$res = xml_parser_buildarray(reset($_xml_parser_values),$attrmix);
	}
	return $res;
}

// parse xml file to assossiative array
// (array built with attrmix mode will not be able to be build in the same xml)
function xml_parse_file($xmlfile,$attrmix=false,$errormsg=false){	
	$res = array();
	if(file_exists($xmlfile)){
		$xmlstr = file_get_contents($xmlfile);
		$res = xml_parse_string($xmlstr,$attrmix);
	}else{
		echo "xml_parse_file: can't find file $xmlfile.\n";
		return false;
	}
	return $res;
}


// make a xml string from an associative array
function xml_build($datas){
	global $_xml_parser_tabs;
	$_xml_parser_tabs = "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";

	$res = '<?xml version="1.0" encoding="UTF-8" ?>';
	$res .= xml_parser_buildxml($datas,0);

	return $res;
}


// build xml string from an associative array and save to file
function xml_build_to_file($datas,$xmlfile){
	return file_put_contents($xmlfile,xml_build($datas));
}


//
function xml_parser_buildarray($tag,$attrmix=false){
	global $_xml_parser_values;
	$res = array();
	$tsub = array();

	for(;$tag!==false && $tag['type']!='close'; $tag=next($_xml_parser_values)){

		$tagval = '';
		$tagattr = null;
		$tname = $tag['tag'];

		if($tag['type']=='complete' || $tag['type']=='open'){
			if(isset($tag['attributes'])){
				if($attrmix)
					// attribute in attrmix mode : the key take the attr array
					$tagval = $tag['attributes'];
				else
					// attribute in normal mode : add a special attr key
					$tagattr = $tag['attributes'];
			}
			
			if($tag['type']=='complete'){
				// final value
				if(isset($tag['value'])){
					if($attrmix && isset($tag['attributes']))
						// final key with value and attr in attrmix mode
						$tagval['value'] = $tag['value'];
					else
						// final key without attrmix mode
						$tagval = $tag['value'];
				}
				
			}elseif($tag['type']=='open'){
				// array key
				$tagval = xml_parser_buildarray(next($_xml_parser_values),$attrmix);
			}
			
			// put $tagval and $tagattr in array...
			
			if(isset($res[$tname])){
				if($attrmix && is_array($res[$tname])){
					// key already exist, is an array, and attrmix mode : just  complete the array
					$res[$tname] = array_merge($res[$tname],$tagval);
					
				}else{
					// key already exist : make multi same key array (add '.xml_parser_built.'=>true in array to remind it)
					
					if(!isset($tsub[$tname])){
						// first time : make the array
						$tsub[$tname] = 0;
						if(isset($res['.attr.'.$tname]))
							$res['.attr.'.$tname] = array($res['.attr.'.$tname]);
						$tmp = $res[$tname];
						$res[$tname] = array('.multi_same_tag.'=>true);
						$res[$tname][$tsub[$tname]++] = $tmp;
					}
					if($tagattr!==null){
						if(!isset($res['.attr.'.$tname])){
							$res['.attr.'.$tname] = array();
							$tmp = $res[$tname];
							unset($res[$tname]);
							$res[$tname] = $tmp;
						}
						$res['.attr.'.$tname][$tsub[$tname]] = $tagattr;
					}
					$res[$tname][$tsub[$tname]++] = $tagval;
				}
				
			}else{
				// put normal result in the given array
				if($tagattr!==null)
					$res['.attr.'.$tname] = $tagattr;
				$res[$tname] = $tagval;
			}
		}

	}
	return $res;
}


// 
function xml_parser_buildxml(&$tval,$level){
	global $_xml_parser_tabs,$_xml_parser_sep;
	$res = '';
	if(is_array($tval) && count($tval)>0){

		foreach($tval as $tag => $val){

			if(strncmp($tag,'.attr.',6)==0){
				// attribute: do nothing

			}elseif(is_array($val) && isset($val['.multi_same_tag.']) && $val['.multi_same_tag.']){
				// multi same key array
				for($i=0; $i<count($val)-1; $i++){
					if(isset($val[$i])){
						$tattrs = xml_parser_buildxml_tagattrs($tval,$tag,$i);
						$res2 = xml_parser_buildxml($val[$i],$level+1);
						if($res2=='' && $tattrs!='') // if empty with attrs then make auto close tag
							$res .= "\n".substr($_xml_parser_tabs,0,$level).'<'.$tag.$tattrs.' />';
						else
							$res .= "\n".substr($_xml_parser_tabs,0,$level).'<'.$tag.$tattrs.'>'.$res2."</$tag>";
					}
				}
				
			}else{
				// classig tag
				$tattrs = xml_parser_buildxml_tagattrs($tval,$tag,false);
				$res2 = xml_parser_buildxml($val,$level+1);
				if($res2=='' && $tattrs!='') // if empty with attrs then make auto close tag
					$res .= "\n".substr($_xml_parser_tabs,0,$level).'<'.$tag.$tattrs.' />';
				else
					$res .= "\n".substr($_xml_parser_tabs,0,$level).'<'.$tag.$tattrs.'>'.$res2."</$tag>";
			}
		}
		$res .= "\n".substr($_xml_parser_tabs,0,$level-1);

	}else{
		$res .= $tval;
	}
	return $res;
}


function xml_parser_buildxml_tagattrs(&$attrs,$tag,$index=false){
	$res = '';
	if($index===false){
		if(isset($attrs['.attr.'.$tag]) && is_array($attrs['.attr.'.$tag])){
			foreach($attrs['.attr.'.$tag] as $key => $val)
				$res .= " $key=\"$val\"";
		}
	}else{
		if(isset($attrs['.attr.'.$tag][$index]) && is_array($attrs['.attr.'.$tag][$index])){
			foreach($attrs['.attr.'.$tag][$index] as $key => $val)
				$res .= " $key=\"$val\"";
		}
	}
	return $res;
}

?>
