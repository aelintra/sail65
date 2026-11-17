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
	protected $customFullchain = "/opt/sark/etc/ssl/custom/fullchain.pem";
	protected $customPrivkey = "/opt/sark/etc/ssl/custom/privkey.pem";
	protected $leDomainFile = "/opt/sark/etc/identity/le-domain";
	protected $tlsActiveJson = "/opt/sark/etc/identity/tls-active.json";
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

	if (isset($_POST['endLeSetup'])) {
		$this->message = $this->leSetup();
	}

	if (isset($_POST['endLeRenew'])) {
		$this->message = $this->leRenew();
	}

	if (isset($_POST['endLeRemove'])) {
		$this->message = $this->leRemove();
	}

	$this->showMain();

	return;

}

private function activeInfo() {
	$info = array(
		'source' => 'snakeoil',
		'domain' => null,
		'expires_at' => null,
		'issuer' => null,
		'cert_sans' => array(),
	);
	if (is_readable($this->tlsActiveJson)) {
		$j = json_decode(file_get_contents($this->tlsActiveJson), true);
		if (is_array($j)) {
			$info = array_merge($info, $j);
		}
	}
	return $info;
}

private function showMain() {

	if (isset($this->message)) {
		$this->myPanel->msg = $this->message;
	}

	$info = $this->activeInfo();
	$source = isset($info['source']) ? $info['source'] : 'snakeoil';
	$hasCustomer = (file_exists($this->certFile) || file_exists($this->customFullchain) || $source === 'letsencrypt' || $source === 'custom');

	$buttonArray = array();
	if ($hasCustomer && $source !== 'snakeoil') {
		$buttonArray['remove'] = "w3-text-white";
	} else {
		$buttonArray['install'] = "w3-text-white";
	}

	$this->myPanel->actionBar($buttonArray, "sarkcertForm", false, false);
	$this->myPanel->Heading($this->head, $this->message);
	$this->myPanel->responsiveSetup(2);

	echo '<form id="sarkcertForm" action="' . $_SERVER['PHP_SELF'] . '" method="post">' . PHP_EOL;

	$this->myPanel->internalEditBoxStart();

	$label = 'Self-signed (snakeoil)';
	if ($source === 'letsencrypt') {
		$label = 'Let\'s Encrypt';
	} elseif ($source === 'custom') {
		$label = 'Custom / commercial';
	}
	$this->myPanel->subjectBar("Active: " . $label);

	echo '<div class="w3-margin-bottom w3-text-blue-grey w3-small">' . PHP_EOL;
	if (!empty($info['domain'])) {
		echo '<p>Domain: ' . htmlspecialchars($info['domain']) . '</p>' . PHP_EOL;
	}
	if (!empty($info['expires_at'])) {
		echo '<p>Expires: ' . htmlspecialchars($info['expires_at']) . '</p>' . PHP_EOL;
	}
	if (!empty($info['issuer'])) {
		echo '<p>Issuer: ' . htmlspecialchars($info['issuer']) . '</p>' . PHP_EOL;
	}
	if (!empty($info['cert_sans']) && is_array($info['cert_sans'])) {
		echo '<p>SANs: ' . htmlspecialchars(implode(', ', $info['cert_sans'])) . '</p>' . PHP_EOL;
	}
	if ($source === 'snakeoil') {
		echo '<p>Browsers will warn until you install Let\'s Encrypt or a commercial certificate. WebRTC (WSS :8089) uses the same active certificate.</p>' . PHP_EOL;
	}
	echo '</div>' . PHP_EOL;

	if ($source === 'letsencrypt' || $source === 'custom') {
		echo '<div class="w3-container w3-padding w3-margin-top">' . PHP_EOL;
		echo '<button class="w3-button w3-blue w3-small w3-round-xxlarge w3-padding w3-right" type="submit" name="endRemove" onclick="return confirmOK(\'Remove active certificate and revert to self-signed?\')">Remove Certificate</button>';
		echo '</div>' . PHP_EOL;
	}

	echo '</div>' . PHP_EOL;

	/*
	 * Let's Encrypt
	 */
	$this->myPanel->internalEditBoxStart();
	$this->myPanel->subjectBar("Let's Encrypt");
	echo '<div class="w3-margin-bottom w3-text-blue-grey w3-small">' . PHP_EOL;
	echo '<p>Issues a trusted certificate for one public FQDN via HTTP-01 (port 80 must reach this PBX). Apache serves <code>/.well-known/acme-challenge/</code>; the same cert is applied to Apache HTTPS and Asterisk WSS (:8089).</p>' . PHP_EOL;
	echo '<p>Prerequisites: DNS A/AAAA for the FQDN → this host; Shorewall allows tcp/80 (default).</p>' . PHP_EOL;
	echo '</div>' . PHP_EOL;

	if ($source === 'letsencrypt') {
		echo '<div class="w3-container w3-padding">' . PHP_EOL;
		echo '<button class="w3-button w3-blue w3-small w3-round-xxlarge w3-padding" type="submit" name="endLeRenew">Renew now</button> ';
		echo '<button class="w3-button w3-blue w3-small w3-round-xxlarge w3-padding" type="submit" name="endLeRemove" onclick="return confirmOK(\'Stop using Let\\\'s Encrypt?\')">Remove Let\'s Encrypt</button>';
		echo '</div>' . PHP_EOL;
	} elseif ($source === 'custom') {
		echo '<p class="w3-text-blue-grey w3-small">Remove the custom certificate first before requesting Let\'s Encrypt.</p>' . PHP_EOL;
	} else {
		$fqdnDefault = '';
		$dbh = DB::getInstance();
		$res = $dbh->query("SELECT FQDN FROM globals LIMIT 1");
		if ($res) {
			$row = $res->fetch(PDO::FETCH_ASSOC);
			if (!empty($row['FQDN']) && strcasecmp($row['FQDN'], 'None') !== 0) {
				$fqdnDefault = $row['FQDN'];
			}
		}
		echo '<p><label class="w3-text-blue-grey w3-small">FQDN</label><br>' . PHP_EOL;
		echo '<input class="w3-input w3-border w3-round" style="max-width:420px" type="text" name="le_fqdn" id="le_fqdn" value="' . htmlspecialchars($fqdnDefault) . '" placeholder="pbx.example.com"></p>' . PHP_EOL;
		echo '<p><label class="w3-text-blue-grey w3-small">ACME contact email</label><br>' . PHP_EOL;
		echo '<input class="w3-input w3-border w3-round" style="max-width:420px" type="email" name="le_email" id="le_email" value="" placeholder="admin@example.com"></p>' . PHP_EOL;
		echo '<div class="w3-container w3-padding w3-margin-top">' . PHP_EOL;
		echo '<button class="w3-button w3-blue w3-small w3-round-xxlarge w3-padding w3-right" type="submit" name="endLeSetup">Get Let\'s Encrypt certificate</button>';
		echo '</div>' . PHP_EOL;
	}
	echo '</div>' . PHP_EOL;

	/*
	 * Custom / commercial paste
	 */
	if ($source === 'snakeoil') {
		$this->myPanel->internalEditBoxStart();
		$this->myPanel->subjectBar("Load a commercial certificate");
		echo '<div class="w3-margin-bottom w3-text-blue-grey w3-small">' . PHP_EOL;
		echo '<p>Paste the PEM certificate (full chain preferred) and matching private key. Custom certificates take precedence over Let\'s Encrypt.</p>' . PHP_EOL;
		echo '</div>' . PHP_EOL;

		echo '<p><label class="w3-text-blue-grey w3-small">Certificate (.crt / fullchain PEM)</label></p>' . PHP_EOL;
		echo '<p><textarea class="w3-padding w3-margin-bottom w3-tiny w3-card-4 longdatabox" style="height:120px; width:500px"';
		echo ' name="cert" id="cert" ></textarea></p>' . PHP_EOL;

		echo '<p><label class="w3-text-blue-grey w3-small">Private key (.key PEM)</label></p>' . PHP_EOL;
		echo '<p><textarea class="w3-padding w3-margin-bottom w3-tiny w3-card-4 longdatabox" style="height:120px; width:500px"';
		echo ' name="csrkey" id="csrkey" ></textarea></p>' . PHP_EOL;

		echo '<div class="w3-container w3-padding w3-margin-top">' . PHP_EOL;
		echo '<button class="w3-button w3-blue w3-small w3-round-xxlarge w3-padding w3-right" type="submit" name="endInstall">Install</button>';
		echo '</div>' . PHP_EOL;
		echo '</div>' . PHP_EOL;
	}

	echo '</form>';
	$this->myPanel->responsiveClose();
}

private function runRootScript($cmd) {
	// srk-ua-helper runs as root on 127.0.0.1:7601
	return $this->helper->request_syscmd($cmd);
}

private function addCert()
{
	if (empty($_POST['cert']) || empty($_POST['csrkey'])) {
		return "Both Cert and Key MUST be filled out!";
	}

	if (!openssl_x509_check_private_key($_POST['cert'], $_POST['csrkey'])) {
		return "Key does not match Certificate!";
	}

	$cert = $_POST['cert'];
	$key = $_POST['csrkey'];

	$certTmp = tempnam('/tmp', 'sailcert');
	$keyTmp = tempnam('/tmp', 'sailkey');
	if ($certTmp === false || $keyTmp === false) {
		return "Could not create temp files";
	}
	file_put_contents($certTmp, $cert);
	file_put_contents($keyTmp, $key);

	$cmd = sprintf(
		'/bin/bash -c %s',
		escapeshellarg(
			'mkdir -p /opt/sark/etc/ssl/custom && ' .
			'cp ' . escapeshellarg($certTmp) . ' ' . escapeshellarg($this->customFullchain) . ' && ' .
			'cp ' . escapeshellarg($keyTmp) . ' ' . escapeshellarg($this->customPrivkey) . ' && ' .
			'chown root:ssl-cert ' . escapeshellarg($this->customFullchain) . ' ' . escapeshellarg($this->customPrivkey) . ' && ' .
			'chmod 644 ' . escapeshellarg($this->customFullchain) . ' && ' .
			'chmod 640 ' . escapeshellarg($this->customPrivkey) . ' && ' .
			'rm -f ' . escapeshellarg($this->leDomainFile) . ' && ' .
			'/bin/bash /opt/sark/scripts/apply-active-cert.sh'
		)
	);
	$out = $this->runRootScript($cmd);
	@unlink($certTmp);
	@unlink($keyTmp);

	if (strpos((string)$out, 'source=custom') === false && strpos((string)$out, 'apply-active-cert:') === false) {
		return "Install attempted; check helper log. Output: " . trim((string)$out);
	}
	return "Custom certificate installed and applied (Apache + Asterisk).";
}

private function remCert() {
	$cmd = '/bin/bash -c ' . escapeshellarg(
		'rm -f ' . escapeshellarg($this->customFullchain) . ' ' . escapeshellarg($this->customPrivkey) . ' ' .
		escapeshellarg($this->leDomainFile) . ' /opt/sark/etc/identity/le-email && ' .
		'/bin/bash /opt/sark/scripts/apply-active-cert.sh'
	);
	$this->runRootScript($cmd);
	return "Certificate removed; reverted to self-signed (Apache + Asterisk reloaded).";
}

private function leSetup() {
	$fqdn = isset($_POST['le_fqdn']) ? trim($_POST['le_fqdn']) : '';
	$email = isset($_POST['le_email']) ? trim($_POST['le_email']) : '';

	if ($fqdn === '' || $email === '') {
		return "FQDN and email are required for Let's Encrypt.";
	}
	if (!preg_match('/^[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $fqdn)) {
		return "FQDN looks invalid.";
	}
	if (!$this->helper->validEmail($email)) {
		return "Email looks invalid.";
	}

	$cmd = sprintf(
		'/bin/bash /opt/sark/scripts/le-first-cert.sh %s %s',
		escapeshellarg($fqdn),
		escapeshellarg($email)
	);
	$out = $this->runRootScript($cmd);
	$out = trim((string)$out);
	if (stripos($out, 'ERROR') !== false || stripos($out, 'failed') !== false) {
		return "Let's Encrypt failed: " . $out;
	}
	if (stripos($out, 'issued for') === false && stripos($out, 'source=letsencrypt') === false) {
		return "Let's Encrypt finished with: " . ($out !== '' ? $out : '(no output — check /var/log/srkhelper.log and /var/log/letsencrypt/)');
	}
	return "Let's Encrypt certificate issued for $fqdn and applied.";
}

private function leRenew() {
	$out = $this->runRootScript('/bin/bash /opt/sark/scripts/le-renew.sh');
	return "Renew finished: " . trim((string)$out);
}

private function leRemove() {
	$out = $this->runRootScript('/bin/bash /opt/sark/scripts/le-remove-cert.sh');
	return "Let's Encrypt removed: " . trim((string)$out);
}
}
