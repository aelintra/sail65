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


Class sark3pcerts {

        protected $message;
        protected $head = "Certificates";
        protected $certFile = "/etc/ssl/3pcerts/3pcerts.pem";

        protected $myPanel;
        protected $helper;
        protected $validator;
        protected $invalidForm;
        protected $error_hash = array();

public function showForm() {

        $this->myPanel = new page;
        $this->helper = new helper;

        $this->myPanel->pagename = '3pcert';

        if (isset($_POST['remove']) || isset($_POST['endremove'])) {
                $this->message = $this->remCert();
        }

        if (isset($_POST['save']) || isset($_POST['endsave'])) {
                $this->message = $this->addCert();
        }

        $this->showMain();

        return;

}
 
private function showMain() {

        if (isset($this->message)) {
                $this->myPanel->msg = $this->message;
        }
        $buttonArray=array();
        if (file_exists($this->certFile)) {
            $buttonArray['remove'] = "w3-text-white";
            $buttonArray['save'] = "w3-text-white";

        }
        else {
             $buttonArray['save'] = "w3-text-white";
        }

        $this->myPanel->actionBar($buttonArray,"sark3pcertForm",false,false);
        $this->myPanel->Heading($this->head,$this->message);
        $this->myPanel->responsiveSetup(2);

        echo '<form id="sark3pcertForm" action="' . $_SERVER['PHP_SELF'] . '" method="post">' . PHP_EOL;

/*
 *  Certificates
 */
        if (file_exists($this->certFile)) {
            $file = file_get_contents($this->certFile) or die("Could not read file $pkey !");
			$this->myPanel->internalEditBoxStart();
        }
        $this->myPanel->subjectBar("Third Party Certificates");
        echo '<div class="w3-margin-bottom w3-text-blue-grey w3-small">';
        echo "<p>";
        echo "If you plan to provision remote endpoints then it is highly recommended you use certificate checking to prevent a potential hacker from stealing phone SIP acccount and login passwords. To implement this you will need to obtain the correct certificates from each of your phone vendors and paste them here.  You can concatenate as many certificates as you need and they will be saved as a bundled .pem file. Paste each new certificate at the end of the file (on a new line) and press Save.  You will also need to update the provisioning URL in each phone to use HTTPS and port 41363;- e.g. https://mypbx:41363/provisioning";
        echo '</p>';
        echo "<p>";
        echo "N.B. - don't forget to open (and forward) port 41363(TCP) in your PBX and external firewalls";
        echo '</p>';        
        echo "";
        echo '</p>';        
        echo '</div>';

		echo '<div class="w3-margin-bottom w3-text-blue-grey w3-small">';

		echo '</div>';
		echo '<p><textarea class="w3-padding w3-margin-bottom w3-tiny w3-card-4 longdatabox" style="height:400px; width:500px"';
		echo ' name="cert" id="cert" >' . $file . '</textarea></p>' . PHP_EOL;

		echo '<div class="w3-container w3-padding w3-margin-top">' . PHP_EOL;
        echo '<button class="w3-button w3-blue w3-small w3-round-xxlarge w3-padding w3-right" type="submit" name="endremove">Remove</button>';
        echo '<span class="w3-padding w3-right">&nbsp</span>';
		echo '<button class="w3-button w3-blue w3-small w3-round-xxlarge w3-padding w3-right" type="submit" name="endsave">Save</button>';
        

		echo '</div>' . PHP_EOL;
 
        echo '</div>' . PHP_EOL;        
        echo '</form>';
        $this->myPanel->responsiveClose();
}

private function addcert()
{
    	if (empty($_POST['cert'])) {
    		return "Certificates are empty, not saved!";
    	}

        $cert =  $_POST['cert'];

        `echo "$cert" > /tmp/certFile`;
        `sudo mv /tmp/certFile $this->certFile`;
        `sudo chown root:root $this->certFile`;
        `sudo chmod 644 $this->certFile`;

        if (file_exists("/etc/apache2/sites-enabled/sark-prov-ssl.conf")) {
            return("updated Certificates");
        }
        

        `sudo a2ensite sark-prov-ssl.conf`;

        return("Saved Certificates - reboot to action");
}


private function remcert() {

        `sudo rm -rf $this->certFile`;
 
        `sudo a2dissite sark-prov-ssl.conf`;

        return("Deleted Certificates - reboot required");

}
}
