<?php
//
// Developed by CoCo
// Copyright (C) 2012 CoCoSoft
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//


Class sarkgreeting {
	
	protected $message;
	protected $head = "Greetings"; 
	protected $myPanel;
	protected $dbh;
	protected $helper;
	protected $validator;
	protected $invalidForm;
	protected $error_hash = array();
	protected $distro = array();
	protected $soundir = '/var/lib/asterisk/sounds'; // set for rhel (see below)

public function showForm() {
	
	$this->myPanel = new page;
	$this->dbh = DB::getInstance();
	$this->helper = new helper;
	$this->helper->qDistro($distro);
	
	if ( $distro['debian'] )  {
		$this->soundir = '/usr/share/asterisk/sounds';
	}	
	
	
	$this->myPanel->pagename = 'Greeting';
	
	if (isset($_POST['upimgclick'])) { 
		$filename = strip_tags($_FILES['file']['name']);
		if (preg_match (' /^(usergreeting\d{4})\.(mp3)$/ ', $filename, $matches) ) {
			if (glob('/usr/share/asterisk/sounds/' . $matches[1] . '.*')) {
				$this->message = $matches[1] . " already exists";
			}
			else {
				$tfile = strip_tags($_FILES['file']['tmp_name']);
				$this->helper->request_syscmd ("/bin/mv $tfile /usr/share/asterisk/sounds/$filename");
				$this->helper->request_syscmd ("/bin/chown asterisk:asterisk /usr/share/asterisk/sounds/$filename");
				$this->helper->request_syscmd ("/bin/chmod 664 /usr/share/asterisk/sounds/$filename");
				$this->message = "File $filename uploaded!";
			}
		}
		else {
			$this->message = "*ERROR* - File name must be usergreetingxxxx.mp3";
		}					
	}	
	
	if (isset($_POST['commit_x']) || isset($_POST['commitClick_x'])) { 
		$this->helper->sysCommit();
		$this->message = "Updates have been Committed";	
	}
		
	$this->showMain();
	
	$this->dbh = NULL;
	return;
	
}
	
private function showMain() {
	
	if (isset($this->message)) {
		$this->myPanel->msg = $this->message;
	} 
	
	$fgreeting = array();
	$tuple = array();	

/* 
 * start page output
 */
  	echo '<input type="file" id="file" name="file" style="display: none;" />'. PHP_EOL;
	echo '<input type="hidden" id="upimgclick" name="upimgclick" />'. PHP_EOL;

	$buttonArray=array();
	$this->myPanel->actionBar($buttonArray,"sarkgreetingForm",false,false);

	if ($this->invalidForm) {
		$this->myPanel->showErrors($this->error_hash);
	}
	$this->myPanel->Heading($this->head,$this->message);

	$this->myPanel->responsiveSetup(2);

	echo '<form id="sarkgreetingForm" action="' . $_SERVER['PHP_SELF'] . '" method="post" enctype="multipart/form-data">' . PHP_EOL;
	
	$this->myPanel->beginResponsiveTable('greetingtable',' w3-small');

	echo '<thead>' . PHP_EOL;	
	echo '<tr>' . PHP_EOL;
	

	$this->myPanel->aHeaderFor('greetingnum');
	$this->myPanel->aHeaderFor('cluster');
	$this->myPanel->aHeaderFor('description'); 	
	$this->myPanel->aHeaderFor('filesize');	
	$this->myPanel->aHeaderFor('filetype');
	$this->myPanel->aHeaderFor('D/L');
	$this->myPanel->aHeaderFor('play'); 
	$this->myPanel->aHeaderFor('del');
	 
	
	echo '</tr>' . PHP_EOL;
	echo '</thead>' . PHP_EOL;
	echo '<tbody>' . PHP_EOL;
	
/*
 * read the greeting files and create an array of filename=>filetype
 */ 

	if ( $handle = opendir($this->soundir) ) {
		while (false !== ($entry = readdir($handle))) {
			if (preg_match(' /(^usergreeting\d*)\.(wav|gsm|mp3)$/ ',$entry,$matches)) {
				$fgreeting[$matches[1]] = $matches[2];
			}
		}	
		closedir($handle);
	}


/*
 * If a table row exists without a corresponding sound file then delete it.
 */ 
	$rows = $this->helper->getTable("greeting");
	foreach ($rows as $row ) {
		$globarray = glob ($this->soundir . "/" . $row['pkey'] . '*');
		if (empty($globarray)) {
//			$tuple['pkey'] = $key;
			$ret = $this->helper->delTuple("greeting",$row['pkey']);
		}	
	}
/*
 * attempt to insert an entry for each sound file into the database
 */ 
	foreach ($fgreeting as $key=>$value) {
		$tuple['pkey'] = $key;
		$tuple['type'] = $value;
		$this->helper->createTuple("greeting",$tuple); 
    }
/*
 * read the table again - it should be consistent with the files now.
 */
  		
	$rows = $this->helper->getTable("greeting");	
	
/**** table rows ****/	
	foreach ($rows as $row ) {
		$file = glob( $this->soundir . "/" . $row['pkey'] . '*');
		$filesize = filesize($file[0]);
		echo '<tr id="' . $row['pkey'] . '">'. PHP_EOL; 
		echo '<td class="read_only">' . $row['pkey'] . '</td>' . PHP_EOL;	
		echo '<td >' . $row['cluster']  . '</td>' . PHP_EOL;		
		echo '<td >' . $row['desc']  . '</td>' . PHP_EOL;
		echo '<td >' . $filesize. '</td>' . PHP_EOL;
		echo '<td >' . $row['type']  . '</td>' . PHP_EOL;
		if (preg_match('/mp3$/',$file[0])) {
			echo '<td class="center"><a href="/php/downloadg.php?dtype=greet&dfile=' . $file[0] . '"><img src="/sark-common/icons/download.png" border=0 title = "Click to Download" ></a></td>' . PHP_EOL;
		}
		else {
			echo '<td class="center">N/A</td>' . PHP_EOL;
		}
									
		if (preg_match('(wav|mp3)',$row['type'])) {
			echo '<td ><a href="/server-sounds/' . $row['pkey'] . '.' .$row['type'] . '"><img src="/sark-common/icons/play.png" border=0 title = "Click to play" ></a></td>' . PHP_EOL; 
		}
		else {
			echo '<td title = "Only  files of type \'wav\' and \'mp3\'  may be played">N/A</td>' . PHP_EOL;
		}
		echo '<td ><a class="table-action-deletelink" href="/php/sarkgreeting/delete.php?id=' . $row['pkey'] . '"><img src="/sark-common/icons/delete.png" border=0 title = "Click to Delete" ></a></td>' . PHP_EOL;				

		echo '</td>' . PHP_EOL;
		echo '</tr>'. PHP_EOL;
	}

	echo '</tbody>' . PHP_EOL;
	$this->myPanel->endResponsiveTable();
	echo '</form>';
	$this->myPanel->responsiveClose();	
		
}

private function saveNew() {
// save the data away
	
	$tuple = array();

		$tuple['pkey'] 	= '0000' . rand(1000, 9999);

		$ret = $this->helper->createTuple("greeting",$tuple);
		if ($ret == 'OK') {
//			$this->helper->commitOn();	
			$this->message = "Saved new greeting " . $tuple['pkey'] . "!";
		}
		else {
			$this->invalidForm = True;
			$this->message = "<B>  --  Validation Errors!</B>";	
			$this->error_hash['exteninsert'] = $ret;	
		}
	
}

}
