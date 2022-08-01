<?php

$target = "/opt/sark/db/sark.db";

if (!empty ($argv[1])) {
	$target = $argv[1];
}

try {
  $db = new PDO("sqlite:$target");
} catch (PDOException $e) {
  die("Failed to get DB handle: " . $e->getMessage() . "\n");
}
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
        $sql = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");
        $sql->execute();
} catch (Exception $e) {
                logIt("retrieval failed");
                exit;
}

$date = new DateTime();
$now = $date->getTimestamp();

$fileList = array();
$myDir = "mkdir -p /tmp/dbdumps/$now/rawdbdump/";
`$myDir`;
While ($thisRow = $sql->fetchObject()) {
        $dump = "sqlite3 /opt/sark/db/sark.db '.dump " .  $thisRow->name . "' > /tmp/dbdumps/$now/rawdbdump/" . $thisRow->name;
        echo $dump . "\n";
        `$dump`;
}
$myNewDir = "mkdir -p /tmp/dbdumps/$now/insertdump/";
`$myNewDir`;

$sysArray = array("Carrier","Device","Greeting","Panel","PanelGroup","PanelGroupPanel","UserPanel","globals","IPphone_FKEY","master_audit","master_xref","mfgmac","page","tt_help_core");


if ($handle = opendir("/tmp/dbdumps/$now/rawdbdump")) {
		while (false !== ($entry = readdir($handle))) {
			if ($entry != '.' && $entry != '..') {
				if (in_array($entry,$sysArray)) {
					continue;
				}

				$stream = fopen("/tmp/dbdumps/$now/rawdbdump/$entry",'r');
				while(($line=fgets($stream))!==false) {
					if (preg_match('/^INSERT/',$line)) {
						echo $line;
					} 					 
				}
				fclose($stream);
			}
		}	
		closedir($handle);
	}

exit;

$timestamp = NULL;
foreach ($sysloghd as $row ) {

		preg_match ('/^(\w+\s*\d+\s*\d{2}:\d{2}:\d{2})/',$row,$matches);
		$timestamp = $matches[1];
		if ($timestamp <= $lastScanDate) {
			continue;
		}
				
		$lastrec = $row;
		$tuple = array(
			"pkey" => NULL,
			"asn" => 'unknown',
			"firstseen" => 'never',
			"hits" => '1',
			"isp" => 'unknown',
			"lastseen" => 'never',		
			"loc" => 'unknown'
		);
		
		if (!preg_match('/Shorewall/',$row)) {
			continue;
		}
		
		preg_match ('/SRC=(\d+\.\d+\.\d+\.\d+)/',$row,$matches);
		$ipAddr = $matches[1];
		
		
		try {
			$sql = $db->prepare("SELECT pkey,hits,lastseen FROM threat WHERE  pkey = '" . $ipAddr . "'");
			$sql->execute();
		} catch (Exception $e) {
			logIt("retrieval failed");
		}
		
		$thisRow = $sql->fetchObject();
		$hits=1;
		if (!empty($thisRow->pkey)) {
			if (isset($thisRow->hits)) {
				$hits = $thisRow->hits;
				$hits++;
			}
			
			$sql = $db->prepare("UPDATE threat SET hits=?,lastseen=? WHERE pkey = ?"); 
			$sql->execute(array($hits,$timestamp,$ipAddr));	
			continue;
		}
		
		
		$url = "ipinfo.io/" . $ipAddr ;
  		$ch = curl_init();
  		curl_setopt($ch, CURLOPT_URL, $url);
  		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  		$json = curl_exec($ch);
  		$jobj = json_decode($json);
  		if (isset($jobj->org)) {
  			$asnarray = explode (" ", $jobj->org,2);
			$tuple['asn'] = $asnarray[0];
			$tuple['isp'] = $asnarray[1];
		}
		if (isset($jobj->country)) {
			$tuple['loc'] = $jobj->country;
		}
		$tuple['lastseen'] = $timestamp;
		$tuple['firstseen'] = $timestamp;
		$tuple['pkey'] = $ipAddr;
		
		try {
			$sql = $db->prepare("INSERT INTO threat (pkey,asn,firstseen, hits,isp,lastseen,loc) VALUES (?,?,?,?,?,?,?)");
			$sql->execute (array($tuple['pkey'], $tuple['asn'], $tuple['lastseen'], $tuple['hits'], $tuple['isp'],$tuple['lastseen'],$tuple['loc']));
		} catch (Exception $e) {
			logIt("INSERT of $ipAddr into table threat failed");
			die ("INSERT of $ipAddr into table threat failed for $e");
		}
		
		sleep(1);
		
	}
	echo "updating timestamp -> $timestamp\n";
 	`echo $timestamp > /opt/sark/cache/last_syslog`;
exit;

function logIt($someText) {
  if (isset($_SERVER["REMOTE_ADDR"])) {
  	syslog(LOG_WARNING, $_SERVER["REMOTE_ADDR"] . " " . $someText . "\n");
  }
  else {
  	syslog(LOG_WARNING, "BATCH LOGSCAN " . $someText . "\n");
  }	
}
