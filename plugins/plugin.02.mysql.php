<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      17.02.2009
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// FAST3.2 plugin with open local database and keep it alive
// 
//
registerPlugin('dbmysql',02,1.0);

// Configure those values in fast.php if you want Fast to be able to use a locale mysql database !
// You also need your php to have mysql support, and of course a working mysql server !
//
// $_DBserver = 'localhost';
// $_DBbase = 'mysql database name';
// $_DBuser = 'mysql login';
// $_DBpassword = 'mysql pass';

// Don't set it unless if you know that your mysql need less than 15 min.
// $_DBkeepalive = 15; // send a keepalive query every $_DBkeepalive minutes.


//--------------------------------------------------------------
// use it instead of mysql_query !
// this function will show error message if any, try database reconnection if needed,
// keep queries if database connection is tempory lost, then send them at reconnection.
// if global $_DB is false then don't use database !
//--------------------------------------------------------------
function dbmysql_query($query){
	global $_debug,$_DB,$_DBkeepalive,$_DBretry,$_DBretry_queries;
	// if connection was previously lost : try to reconnect
	if($_DB===false && $_DBretry){
		dbmysql_connection();
	}

	// if DB available then send query
	if($_DB!==false){
		$result = @mysql_query($query);
		if($result === false){
			$errno = mysql_errno();
			$errstr = mysql_error();
			// connection lost ? try to reconnect, then resend.
			if($errno==2006 || $errno==2013){ // http://dev.mysql.com/doc/refman/5.1/en/error-handling.html
				// it seems that keepalive was too big, reduce it
				$_DBkeepalive = (int) ceil($_DBkeepalive / 2);
				// reconnect
				if($_debug>0) console("dbmysql_query:: mysql connection lost ! try to reconnect...");
				dbmysql_connection();
				if($_DB!==false){
					// resend
					$result = @mysql_query($query);
					if($result === false){
						$errno = mysql_errno();
						$errstr = mysql_error();
					}

				}else{
					$_DBretry = true;
					if($_debug>0) console("dbmysql_query:: failed to reconnect to mysql db, will retry later...");
					// can't reconnect to database : keep query for later send !
					if(count($_DBretry_queries) < 3000){
						if($_debug>1) console("dbmysql_query:: database not available, keep query for later send.");
						$_DBretry_queries[] = $query;
					}else{
						if($_debug>1) console("dbmysql_query:: database not available, too many kept queries, drop it.");
					}
				}
			}
			if($result === false){
				console("dbmysql_query::Query failure({$errno},{$errstr}): $query");
				$backtrace = debug_backtrace();
				while(count($backtrace) > 3)
					array_pop($backtrace);
				debugPrint('dbmysql_query:: debug_backtrace',$backtrace);
				return false;
			}
		}
		return $result;

	}elseif($_DBretry){
		// can't reconnect to database : keep query for later send !
		if(count($_DBretry_queries) < 3000){
			if($_debug>1) console("dbmysql_query:: database not available, keep query for later send.");
			$_DBretry_queries[] = $query;
		}else{
			if($_debug>1) console("dbmysql_query:: database not available, too many kept queries, drop it.");
		}

	}else{
		// no database
		if($_debug>1) console("dbmysql_query:: database not available, can't do query.");
	}
	return false;
}


//--------------------------------------------------------------
// init mysql connection
// if global $_DB is false then don't use database !
//--------------------------------------------------------------
function dbmysqlInit($event){
	global $_debug,$_DB,$_DBretry,$_DBretry_queries,$_DBkeepalive;
	if($_debug>0) console("dbmysql.Event[$event]");

	if(!isset($_DBkeepalive))
		$_DBkeepalive = 15;
	if($_DBkeepalive < 1)
		$_DBkeepalive = 1;

	$_DB = false;
	$_DBretry = false;
	$_DBretry_queries = array();
	dbmysql_connection();
	if($_DB!==false) console("dbmysql:: connected to mysql database !");
}


//--------------------------------------------------------------
// keep alive mysql connection
//--------------------------------------------------------------
//function dbmysqlEverysecond($event){
function dbmysqlEveryminute($event,$minutes){
	global $_debug,$_DB,$_DBretry,$_DBkeepalive,$_DBkeepalive_time;
	if($_DB!==false || $_DBretry){
		$time = time();
		if($_DBkeepalive_time + $_DBkeepalive*60 < $time){
			// do keep alive
			$_DBkeepalive_time = $time;
			$result = dbmysql_query('show tables;');
			if($result !== false && $_DBkeepalive < 15)
				$_DBkeepalive++;
			console("dbmysqlEveryminute:: mysql keepalive ! ($_DBkeepalive)");
		}
	}
}


//--------------------------------------------------------------
// open connection to database, if failed then global $_DB is false
// --------------------------------------------------------------
function dbmysql_connection(){
	global $_debug,$_DB,$_DBserver,$_DBuser,$_DBpassword,$_DBbase,$_DBretry,$_DBretry_queries,$_DBkeepalive_time;
	$_DBkeepalive_time = time();
	if(!function_exists('mysql_pconnect')){
		console("Mysql is not supported by your php setup.");
		$_DB===false;
		return;
	}
	if($_DB!==false)
		dbmysql_close();

	if(isset($_DBserver) && $_DBserver!='' &&
		 isset($_DBuser) && $_DBuser!='' &&
		 isset($_DBpassword) && $_DBpassword!='' &&
		 isset($_DBbase) && $_DBbase!=''){

		if(@mysql_pconnect($_DBserver,$_DBuser,$_DBpassword) !== false){
			$_DB = @mysql_select_db($_DBbase);

			if($_DB !== false){
				$_DBretry = false;
				// set database charset
				@mysql_query("SET NAMES 'utf8'");
				// set database timezone for datetime entries (+0:00 = GMT)
				@mysql_query("SET time_zone = '+0:00';");

				// succeeded to connect : send pending queries (if any)
				if(count($_DBretry_queries) > 0){
					if($_debug>0) console("dbmysql_connection:: send pending queries (".count($_DBretry_queries).')');
					$queries = $_DBretry_queries;
					$_DBretry_queries = array();
					foreach($queries as $query)
						dbmysql_query($query);
				}
			}else{
				console("Failed to select base '$_DBbase' !");
			}
		}else{
			console("Failed to connect to mysql '$_DBserver' as user '$_DBuser' !");
		}
	}else{
		console("Mysql database not configured : will not use it.");
	}
	return $_DB;
}


// --------------------------------------------------------------
// close connection to database
// --------------------------------------------------------------
function dbmysql_close(){
	global $_DB;
	if($_DB!==false && function_exists('mysql_close'))
		@mysql_close();
	$_DB = false;
}


?>
