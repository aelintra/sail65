<?php
// +-----------------------------------------------------------------------+
// |  Copyright (c) KoKoSoft 2024                                  |
// +-----------------------------------------------------------------------+
// | This file is free software; you can redistribute it and/or modify     |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation; either version 2 of the License, or     |
// | (at your option) any later version.                                   |
// | This file is distributed in the hope that it will be useful           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of        |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the          |
// | GNU General Public License for more details.                          |
// +-----------------------------------------------------------------------+
// | Author: KoKo                                                      |
// +-----------------------------------------------------------------------+
// 

include("localvars.php");

$sysTables = array(
	"Carrier"  			=> true,			
	"mfgmac"  			=> true,
	"Panel"  			=> true,
	"PanelGroup"  		=> true,
	"PanelGroupPanel"  	=> true
);

$ignoreTables = array(
/**
 * Ignore No longer used tables which may be present in an upgrade
 */
	"undolog"  			=> true,
	"tt_help_user"  	=> true,
	"vendorxref"  		=> true,
	"device_atl"  		=> true,
	"master_xref"  		=> true,
	"master_audit"  	=> true,
	"mcast" 		 	=> true,
	"shorewall_blacklist" 		 	=> true,
	"shorewall_whitelist" 		 	=> true,
	"tt_help_core"  	=> true,
	"undolog"  			=> true,
);
$laravelTables = array(
/**
 * Laravel ephemeral stuff.  Create but don't populate
 */
	"cache"  			=> true,
	"cache_locks"  		=> true,
	"job_batches"  		=> true,
	"jobs"  			=> true,
	"password_reset_tokens"  	=> true,
	"personal_access_tokens"  	=> true,
	"sessions"  		=> true
);
     
$prefix='/last_'; 

if (isset ($argv[1])) {
	$rootdir = $argv[1];
}

if (isset ($argv[2])) {
	$sarkdb = $argv[2];
}

	$tables=array();
	$colrows=array();
	$datarows=array();
	$cfgfilename=$rootdir . $prefix . 'create.sql';
	$datafilename=$rootdir . $prefix . 'data.sql';
	$devfilename=$rootdir . $prefix . 'device.sql';
	$custdevfilename=$rootdir . $prefix . 'custdevice.sql';
	$sysfilename=$rootdir . $prefix . 'system.sql';	
	$tablesdirectory=$rootdir . $prefix . 'tabledumps';
		
    /*** connect to SQLite database ***/
    try {
		$dbh = new PDO($sarkdb);
	}
	catch (Exception $e) {
		echo "Oops failed to open DB $sarkdb" . " $e\n";
		exit(4);
	}

    /*** set the error reporting attribute ***/
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
 	$COLDATA 	= NULL;
	$VALDATA 	= NULL;
	$CREATE 	= "BEGIN TRANSACTION;\n";
	$INSERT 	= "BEGIN TRANSACTION;\n";
	$TABLEINSERT = "BEGIN TRANSACTION;\n";
	$SYSINSERT 	= "BEGIN TRANSACTION;\n";
	$DEVINSERT 	= "BEGIN TRANSACTION;\n";
	$CUSTDEVINSERT 	= "BEGIN TRANSACTION;\n";

// create the teblesdirectory if it does not exist
	if (is_dir($tablesdirectory)) {
		`rm -rf $tablesdirectory`;
	}
	`mkdir -p $tablesdirectory`; 

/*
 * get a list of tables
 */
    try { 
		$tables = $dbh->query("select name from sqlite_master where type='table'")->fetchall();
	}
	catch (Exception $e) {
		echo "Oops on table list fetch " . " $e\n";
		exit(8);
	}

/*
 * get a column list for each table
 */		
	foreach ($tables as $table) {
/**
 * Ignore tables in the ignore list
 */
		if (isset($ignoreTables[$table['name']])) {			
			continue;
		} 	  	

//  get the create sql from sqlite3
		try {
		 $sql = $dbh->query("select sql from sqlite_master WHERE name='" . $table['name'] . "' AND type='table'" )->fetchColumn();
		}
		catch (Exception $e) {
			echo "Oops on sql retrieve for " . $table['name'] . " $e\n";
			exit(12);
		}
		
//  make it idempotent
		if (!preg_match ('/^CREATE TABLE IF NOT EXISTS/', $sql)) {
			$sql = preg_replace ( '/^CREATE TABLE /','CREATE TABLE IF NOT EXISTS ', $sql );
		}
		$CREATE .= $sql; 
		$CREATE .= ";\n\n";

//	ignore laravel ephemeral data		
		if (isset($laravelTables[$table['name']])) {			
			continue;
		} 	
		
//  ignore empty tables
		$res = $dbh->query('select count(*) from ' . $table['name'] )->fetchColumn();
		if ( $res == 0 ) {
			$someText = "TABLE " . $table['name'] . " IS EMPTY";
			syslog(LOG_WARNING, date("M j H:i:s") . ": SRKDUMPER -> " . $someText . "\n");
			continue;
		}
//   Create a file in /opt/sark/db/tabledumps



//  get column metadata and table data 	
		
		$orderby = "";
		$colrows = $dbh->query( "PRAGMA table_info(" . $table['name'] . ")" )->fetchall();
		if (isset($table['pkey'])) {
			$orderby = " ORDER BY pkey";
		}
		try {			
			$rows = $dbh->query( "SELECT * from " . $table['name'] . $orderby )->fetchall();
		}
		catch (Exception $e) {
			echo "Oops on select from " . $table['name'] . " $e\n";
			exit(16);
		}
			
			
		
// Build the dump string 	
		foreach ($rows as $row) {				
			foreach ($colrows as $col) {
				$myData = $row[$col['name']];
				if ($myData) {
// don't carry forward the create/update time stamps.  They'll cause interlocks on the DB.
					if ( !preg_match (" /^z_/", $col['name'] )) {
						$COLDATA .= $col['name'] . ",";
						$VALDATA .= "'" . $myData . "',";
					}
				}
			}
			$COLDATA = rtrim($COLDATA, ',');
			$VALDATA = rtrim($VALDATA, ',');
			
// ignore system tables
			if (isset($sysTables[$table['name']])) {			
				$SYSINSERT .= "INSERT OR IGNORE INTO " . $table['name'] . "(" . $COLDATA . ") values (" . $VALDATA . ");\n";
			}
// dump the device table into a separate file
			elseif ($table['name'] == 'Device') {
/*
 * suggested fix for cust data persistence (and DROP device_atl)
 */
 				if (isset($row['owner'])) {
  					if ($row['owner'] == "cust") {
  						$CUSTDEVINSERT .= "INSERT OR IGNORE INTO " . $table['name'] . "(" . $COLDATA . ") values (" . $VALDATA . ");\n";
  					}
  					else { 
						$DEVINSERT .= "INSERT OR IGNORE INTO " . $table['name'] . "(" . $COLDATA . ") values (" . $VALDATA . ");\n";
					}
  				}
			}
// dump the customer data 
			else {
				$INSERT .= "INSERT OR IGNORE INTO " . $table['name'] . "(" . $COLDATA . ") values (" . $VALDATA . ");\n";
				$TABLEINSERT .= "INSERT OR IGNORE INTO " . $table['name'] . "(" . $COLDATA . ") values (" . $VALDATA . ");\n";
			}	
			$COLDATA = NULL;
			$VALDATA = NULL;			
		}	
		$TABLEINSERT 	.= "COMMIT;\n";
		$tname = $tablesdirectory . '/' . $table['name'];
		$fh = fopen($tname, 'w') or die('Could not open $tname!');
		fwrite($fh,$TABLEINSERT) or die('Could not write to $tname');
		fclose($fh);	
		`dos2unix $tname >/dev/null 2>&1`;
		$TABLEINSERT = "BEGIN TRANSACTION;\n";
	}

	$CREATE 	.= "COMMIT;\n";
	$INSERT 	.= "COMMIT;\n";
	$SYSINSERT 	.= "COMMIT;\n";
	$DEVINSERT 	.= "COMMIT;\n";	
	$CUSTDEVINSERT 	.= "COMMIT;\n";
	
	$fh = fopen($cfgfilename, 'w') or die('Could not open file!');
	fwrite($fh,$CREATE) or die('Could not write to cfg file');
	fclose($fh);

	$fh = fopen($datafilename, 'w') or die('Could not open file!');
	fwrite($fh,$INSERT) or die('Could not write to insert file');
	fclose($fh);	

	$fh = fopen($sysfilename, 'w') or die('Could not open file!');
	fwrite($fh,$SYSINSERT) or die('Could not write sysinsert to file');
	fclose($fh);
	
	$fh = fopen($devfilename, 'w') or die('Could not open file!');
	fwrite($fh,$DEVINSERT) or die('Could not write devinsert to file');
	fclose($fh);
	
	$fh = fopen($custdevfilename, 'w') or die('Could not open file!');
	fwrite($fh,$CUSTDEVINSERT) or die('Could not write custdevinsert to file');
	fclose($fh);
	
// clear any junk left by windows-based data changes 
	`dos2unix $cfgfilename >/dev/null 2>&1`;
	`dos2unix $datafilename >/dev/null 2>&1`;
	`dos2unix $devfilename >/dev/null 2>&1`;
	`dos2unix $custdevfilename >/dev/null 2>&1`;	
	`dos2unix $sysfilename >/dev/null 2>&1`;
		
/*
}

catch(PDOException $e)
    {
    echo "fail on try " .  $e->getMessage();
    }
*/    
?>		
