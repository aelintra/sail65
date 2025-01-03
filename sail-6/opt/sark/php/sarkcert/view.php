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


Class sarkcert {

        protected $message = "";
        protected $head = "Certificates";
        protected $certFile = "/etc/ssl/certs/ssl-cert-sark-customer.pem";
        protected $keyFile = "/etc/ssl/private/ssl-cert-sark-customer.key";
        protected $myPanel;
        protected $helper;
        protected $validator;
        protected $invalidForm;
        protected $error_hash = array();

public function showForm() {

        $this->myPanel = new page;
        $this->helper = new helper;

        $this->myPanel->pagename = 'Cert';

        if (isset($_POST['remove']) || isset($_POST['endRemove'])) {
                $this->message = $this->remCert();
        }

        if (isset($_POST['install']) || isset($_POST['endInstall'])) {
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
        }
        else {
             $buttonArray['install'] = "w3-text-white";
        }

        $this->myPanel->actionBar($buttonArray,"sarkcertForm",false,false);
        $this->myPanel->Heading($this->head,$this->message);
        $this->myPanel->responsiveSetup(2);

        echo '<form id="sarkcertForm" action="' . $_SERVER['PHP_SELF'] . '" method="post">' . PHP_EOL;

/*
 *  Certificates
 */
        if (file_exists($this->certFile)) {
            $cnString = `sudo openssl x509 -noout -subject -in $this->certFile`;
            $cnArray = explode('=',$cnString);
            $CN = $cnArray[2];
            $this->myPanel->internalEditBoxStart();
            $this->myPanel->subjectBar($CN);

			if (file_exists($this->certFile)) {
				echo '<p>Certificate loaded</p>' . PHP_EOL;
			}

			if (file_exists($this->keyFile)) {
				echo '<p>CSR Key loaded</p>' . PHP_EOL;
			}

			echo '<div class="w3-container w3-padding w3-margin-top">' . PHP_EOL;
			echo '<button class="w3-button w3-blue w3-small w3-round-xxlarge w3-padding w3-right" type="submit" name="endRemove" onclick="return confirmOK(\'Delete? - Confirm?\'">Remove Certificate</button>';
			echo '</div>' . PHP_EOL;
        }
        else {
			$this->myPanel->internalEditBoxStart();

            $this->myPanel->subjectBar("SSL Certificate is self signed");
            echo '<div class="w3-margin-bottom w3-text-blue-grey w3-small">';
            echo "<p>";
            echo "Your system's browser application is currently running a self-signed certificate.  You can load a commercial certificate below.  Simply copy and paste the  contents of the .crt file you received from your vendor together with the contents of the CSR private key file you provided when you purchased your certificate into the boxes below. The CSR key will be checked against the certificate to ensure they match.  Once loaded you must restart your PBX to finalise the install.";
            echo '</p>';
            echo '</div>';

			$this->myPanel->subjectBar("Load a Certificate");
			echo '<div class="w3-margin-bottom w3-text-blue-grey w3-small">';
			echo "<p>";
			echo "<label> Copy and paste your .crt file contents into the box below </label>";
			echo '</p>';
			echo '</div>';
			echo '<p><textarea class="w3-padding w3-margin-bottom w3-tiny w3-card-4 longdatabox" style="height:120px; width:500px"';
			echo ' name="cert" id="cert" ></textarea></p>' . PHP_EOL;

			$this->myPanel->subjectBar("Load a CSR Key");
			echo '<div class="w3-margin-bottom w3-text-blue-grey w3-small">';
			echo "<p>";
			echo "<label> Copy and paste your CSR .key file contents into the box below </label>";
			echo '</div>';
			echo '<p><textarea class="w3-padding w3-margin-bottom w3-tiny w3-card-4 longdatabox" style="height:120px; width:500px"';
			echo ' name="csrkey" id="csrkey" ></textarea></p>' . PHP_EOL;
			echo '</div>';

			echo '<div class="w3-container w3-padding w3-margin-top">' . PHP_EOL;
			echo '<button class="w3-button w3-blue w3-small w3-round-xxlarge w3-padding w3-right" type="submit" name="endInstall">Install</button>';
			echo '</div>' . PHP_EOL;
        }
        echo '</div>' . PHP_EOL;        
        echo '</form>';
        $this->myPanel->responsiveClose();
}

private function addcert()
{
    	if (empty($_POST['cert']) || empty($_POST['csrkey'])) {
    		return "Both Cert and Key MUST be filled out!";
    	}

        if ( !openssl_x509_check_private_key ( $_POST['cert'], $_POST['csrkey'] )) {
            return "Key does not match Certificate!";
        }

        $cert =  $_POST['cert'];
        $key = $_POST['csrkey'];

        `echo "$cert" > /tmp/certFile`;
        `sudo mv /tmp/certFile $this->certFile`;
        `sudo chown root:root $this->certFile`;
        `sudo chmod 644 $this->certFile`;

        `echo "$key" > /tmp/keyFile`;
        `sudo mv /tmp/keyFile $this->keyFile`;
        `sudo chown root:ssl-cert $this->keyFile`;
        `sudo chmod 640 $this->keyFile`;

        `sudo a2dissite sark-default-ssl.conf`;
        `sudo a2ensite sark-certs.conf`;

        return("Added Certificates - reboot to action");
}


private function remcert() {

        `sudo rm -rf $this->certFile`;
        `sudo rm -rf $this->keyFile`;
        `sudo a2dissite sark-certs.conf`;
        `sudo a2ensite sark-default-ssl.conf`;

        return("Deleted Certificate - reboot required");

}
}
