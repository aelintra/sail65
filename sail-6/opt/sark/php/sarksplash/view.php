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


Class sarksplash {
	
	protected $message=NULL;
	protected $head="System Overview"; 
	protected $myPanel;
	protected $dbh;
	protected $helper;
	protected $validator;
	protected $invalidForm;
	protected $error_hash = array(); 
	protected $HA;


	
public function showForm() {

	$this->myPanel = new page;
	$this->dbh = DB::getInstance();
	$this->helper = new helper;
			
	$this->myPanel->pagename = 'System';
 
//	Debugging		
//	$this->helper->logit(print_r($_POST, TRUE));
	
	
	if (isset($_POST['commit']) || isset($_POST['commitClick'])) { 
		$this->saveEdit();
		if ($this->invalidForm) {
			$this->showMain();
			return;
		}
		else {
			$this->helper->sysCommit();
			$this->message = "Committed!";	
		}		
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
  	$this->myPanel->actionBar($buttonArray,"sarksplashForm",false,false);

	if ($this->invalidForm) {
		$this->myPanel->showErrors($this->error_hash);
	}
	$this->myPanel->Heading($this->head,$this->message);

	$boxcolor=" w3-white";



	echo '<div class="w3-row-padding ' . $this->myPanel->bgColorClass . '">';

	echo '<div class="w3-col w3-display-container  m6 l6" style="height:140px;">';	
  	echo '<div id="ldavg_div" class="w3-container w3-display-middle"></div>';
  	echo '</div>';
  	echo '<div class="w3-col w3-display-container  m6 l6" style="height:140px;">';
  	echo '<div id="sys_div" class="w3-container w3-display-middle"></div>';
  	echo '</div>';
  	echo '</div>';

	echo '<div class="w3-container w3-padding ' . $this->myPanel->bgColorClass . '" >';
  	echo '<div class="w3-col w3-quarter">';
	echo '<div class="w3-col w3-quarter">&nbsp;</div>';
	echo '<div class="w3-display-container w3-card-4 w3-half w3-round-large' . $boxcolor . '" style="height:6em">';
  	echo '<div class="w3-display-topmiddle w3-padding w3-small">UpCalls</div>';
  	echo '<div id="upcalls" class="w3-display-middle w3-xlarge">000</div>';
  	echo '<div class="w3-display-bottommiddle w3-padding w3-small">Now</div>';
  	echo '</div>';
	echo '<div class="w3-col w3-quarter">&nbsp;</div>';
  	echo '</div>';

  	echo '<div class="w3-col w3-quarter">';
	echo '<div class="w3-col w3-quarter">&nbsp;</div>';
	echo '<div class="w3-display-container w3-card-4 w3-half w3-round-large' . $boxcolor . '" style="height:6em">';
  	echo '<div class="w3-display-topmiddle w3-padding w3-small">Inbound</div>';
  	echo '<div id="inbound" class="w3-display-middle w3-xlarge">000</div>';
  	echo '<div class="w3-display-bottommiddle w3-padding w3-small">Today</div>';
  	echo '</div>';
	echo '<div class="w3-col w3-quarter">&nbsp;</div>';
  	echo '</div>';
 
  	echo '<div class="w3-col w3-quarter">';
	echo '<div class="w3-col w3-quarter">&nbsp;</div>';
	echo '<div class="w3-display-container w3-card-4 w3-half w3-round-large' . $boxcolor . '" style="height:6em">';
  	echo '<div class="w3-display-topmiddle w3-padding w3-small">Outbound</div>';
  	echo '<div id="outbound" class="w3-display-middle w3-xlarge">000</div>';
  	echo '<div class="w3-display-bottommiddle w3-padding w3-small">Today</div>';
  	echo '</div>';
	echo '<div class="w3-col w3-quarter">&nbsp;</div>';
  	echo '</div>';

  	echo '<div class="w3-col w3-quarter">';
	echo '<div class="w3-col w3-quarter">&nbsp;</div>';
	echo '<div class="w3-display-container w3-card-4 w3-half w3-round-large' . $boxcolor . '" style="height:6em">';
  	echo '<div class="w3-display-topmiddle w3-padding w3-small">Internal</div>';
  	echo '<div id="internal" class="w3-display-middle w3-xlarge">000</div>';
  	echo '<div class="w3-display-bottommiddle w3-padding w3-small">Today</div>';
  	echo '</div>';
	echo '<div class="w3-col w3-quarter">&nbsp;</div>';
  	echo '</div>';

	echo '</div>';
	

  	echo '<div class="w3-row-padding w3-light-gray" style="min-height:99em;">' . PHP_EOL;
	echo '<div class="w3-col w3-container w3-hide-small m1 l2">&nbsp;</div>' . PHP_EOL;
    echo '<div class="w3-col w3-container m10 l8">' . PHP_EOL;
    echo '<br/>';
	$this->myPanel->aLabelFor("Live Calls...");
	echo '<br/>';
	echo '<table class="' . $this->myPanel->tableClass . ' w3-white w3-small" id="chantable"></table>';
	echo '<br/>';
	echo '</div>';
  	echo '</div>'; 

	echo '</div>';


  	echo '</div>';
  	echo '</div>'; 
  	echo '</div>'; 
  	echo '</div>'; 

    $this->myPanel->responsiveClose();
    
}




}
