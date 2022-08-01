<?php
//
// Developed by CoCo
// Copyright (C) 2018 CoCoSoft
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


Class sarkreception {
	
	protected $message=NULL;
	protected $head="Welcome"; 
	protected $myPanel;
	protected $dbh;
	protected $helper;
	protected $validator;
	protected $invalidForm;
	protected $error_hash = array();
	protected $productImage = "/sark-common/AELI006-Product_600x600.png";
	protected $HA;


	
public function showForm() {

	$this->myPanel = new page;

	$this->helper = new helper;
			
	$this->myPanel->pagename = 'Home';
	
	if (isset($_POST['commit']) || isset($_POST['commitClick'])) { 
		$this->helper->sysCommit();
		$this->message = "Committed!";			
	}


			
	$this->showMain();
	
	$this->dbh = NULL;
	return;
	
}
	
private function showMain() { 

	if (isset($this->message)) {
		$this->myPanel->msg = $this->message;
	}
	$buttonArray=array();
  	$this->myPanel->actionBar($buttonArray,"sarkForm",false,false);

	if ($this->invalidForm) {
		$this->myPanel->showErrors($this->error_hash);
	}
	$this->myPanel->Heading($this->head,$this->message);

	$this->myPanel->responsiveSetup(2);

	echo '<div>';
	echo '<img src="' . $this->productImage . '" style="display:block;margin-left:auto;margin-right:auto;width:40%;"></img>' . PHP_EOL;
	echo '</div>';

	if ( $_SESSION['user']['pkey'] == 'admin' ) {
		$this->myPanel->printSysNotes();
	}
	
	echo '</div>';
    $this->myPanel->responsiveClose();
    
}
 
}
