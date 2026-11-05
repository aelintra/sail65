<?php
//
// Fail2Ban control panel — status, unban, whitelist, ban-email on/off
//
Class sarkfail2ban {

	protected $message;
	protected $myPanel;
	protected $dbh;
	protected $helper;
	protected $head = "Fail2Ban";
	protected $myBooleans = array('f2bmail');
	protected $ctl = '/opt/sark/scripts/srkFail2banCtl.sh';

public function showForm() {

	$this->myPanel = new page;
	$this->helper = new helper;
	$this->dbh = DB::getInstance();

	if (isset($_POST['f2bunban'])) {
		$this->f2bUnban();
	}
	if (isset($_POST['f2bwladd'])) {
		$this->f2bWhitelistAdd();
	}
	if (isset($_GET['f2bwldel'])) {
		$this->f2bWhitelistDel();
	}
	if (isset($_POST['save']) || isset($_POST['endsave']) || isset($_POST['f2bmailsave'])) {
		$this->saveMailPref();
	}

	$this->showMain();
	return;
}

private function showMain() {

	$buttonArray = array();
	$this->myPanel->actionBar($buttonArray, "sarkfail2banForm", false, false);
	$this->myPanel->Heading($this->head, $this->message);
	$this->myPanel->responsiveSetup(2);

	if (!file_exists($this->ctl)) {
		echo '<div class="w3-panel w3-yellow"><p>Fail2Ban control script not installed.</p></div>';
		$this->myPanel->responsiveClose();
		return;
	}

	# --- Ban email ---
	$this->myPanel->internalEditBoxStart();
	$this->myPanel->subjectBar('Ban email');
	echo '<form id="sarkfail2banMailForm" action="' . $_SERVER['PHP_SELF'] . '" method="post">' . PHP_EOL;
	$f2bmail = 'YES';
	$prefFile = '/opt/sark/cache/f2b-mail.pref';
	if (file_exists($prefFile)) {
		$pref = strtolower(trim(@file_get_contents($prefFile)));
		if ($pref === 'off' || $pref === 'no') {
			$f2bmail = 'NO';
		}
	}
	$this->myPanel->displayBooleanFor('f2bmail', $f2bmail);
	$mailActive = file_exists('/etc/fail2ban/jail.d/sail-mail.conf');
	echo '<p class="w3-small w3-text-grey">Requires Network SMTP (ssmtp) and Globals supervisor email. ';
	echo 'Currently: <strong>' . ($mailActive ? 'mailing bans' : 'not mailing') . '</strong>.</p>';
	echo '<button class="w3-button w3-blue" type="submit" name="f2bmailsave" value="1">Save email setting</button>';
	echo '</form>' . PHP_EOL;
	echo '</div>';

	# --- Status / unban / whitelist ---
	$this->showStatusUnbanWhitelist();

	$this->myPanel->responsiveClose();
}

private function showStatusUnbanWhitelist() {

	$this->myPanel->internalEditBoxStart();
	$this->myPanel->subjectBar('Jails &amp; bans');

	$statusRaw = '';
	$statusFile = '/opt/sark/cache/f2b-status.txt';
	if (is_executable($this->ctl)) {
		$this->helper->request_syscmd($this->ctl . ' status');
		if (file_exists($statusFile)) {
			$statusRaw = (string)@file_get_contents($statusFile);
		}
	}

	$jails = array();
	$banned = array();
	$curJail = null;
	foreach (preg_split("/\r\n|\n|\r/", (string)$statusRaw) as $line) {
		if (preg_match('/^### jail:(\S+)/', $line, $m)) {
			$curJail = $m[1];
			if (!isset($jails[$curJail])) {
				$jails[$curJail] = array('failed' => 0, 'banned' => 0);
			}
			continue;
		}
		if ($curJail === null) {
			continue;
		}
		if (preg_match('/Currently failed:\s*(\d+)/', $line, $m)) {
			$jails[$curJail]['failed'] = (int)$m[1];
		}
		if (preg_match('/Currently banned:\s*(\d+)/', $line, $m)) {
			$jails[$curJail]['banned'] = (int)$m[1];
		}
		if (preg_match('/Banned IP list:\s*(.*)$/', $line, $m)) {
			$list = trim($m[1]);
			if ($list !== '') {
				foreach (preg_split('/\s+/', $list) as $ip) {
					if ($ip === '') {
						continue;
					}
					if (!isset($banned[$ip])) {
						$banned[$ip] = array();
					}
					$banned[$ip][] = $curJail;
				}
			}
		}
	}

	echo '<div class="w3-margin-bottom w3-small">';
	echo '<table class="w3-table w3-bordered w3-white"><thead><tr>';
	echo '<th>Jail</th><th>Failed</th><th>Banned</th></tr></thead><tbody>';
	if (empty($jails)) {
		echo '<tr><td colspan="3">Fail2Ban not running or no jails</td></tr>';
	} else {
		foreach ($jails as $name => $st) {
			echo '<tr><td>' . htmlspecialchars($name) . '</td>';
			echo '<td>' . (int)$st['failed'] . '</td>';
			echo '<td>' . (int)$st['banned'] . '</td></tr>';
		}
	}
	echo '</tbody></table></div>';

	echo '<form id="sarkf2bunbanForm" action="' . $_SERVER['PHP_SELF'] . '" method="post">' . PHP_EOL;
	$this->myPanel->aLabelFor('f2bunban');
	echo '<div class="w3-margin-bottom">';
	if (!empty($banned)) {
		echo '<table class="w3-table w3-bordered w3-white w3-small"><thead><tr>';
		echo '<th>IP</th><th>Jails</th><th></th></tr></thead><tbody>';
		foreach ($banned as $ip => $jailList) {
			echo '<tr><td>' . htmlspecialchars($ip) . '</td>';
			echo '<td>' . htmlspecialchars(implode(', ', $jailList)) . '</td>';
			echo '<td><button class="w3-button w3-tiny w3-red" type="submit" name="f2bunban" value="' .
				htmlspecialchars($ip) . '">Unban</button></td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p class="w3-text-grey">No banned IPs</p>';
	}
	echo '<div class="w3-row w3-margin-top">';
	echo '<div class="w3-col m8">';
	echo '<input class="w3-input w3-border" type="text" name="f2bunbanip" placeholder="IP to unban" />';
	echo '</div><div class="w3-col m4">';
	echo '<button class="w3-button w3-blue w3-block" type="submit" name="f2bunban" value="manual">Unban</button>';
	echo '</div></div></div>';
	echo '</form>' . PHP_EOL;
	echo '</div>';

	# Whitelist
	$this->myPanel->internalEditBoxStart();
	$this->myPanel->subjectBar('Whitelist');

	$extras = array();
	$extraFile = '/opt/sark/cache/f2b-ignoreip.extra';
	if (file_exists($extraFile)) {
		foreach (file($extraFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
			$line = trim($line);
			if ($line !== '' && preg_match('/^[0-9]/', $line)) {
				$extras[] = $line;
			}
		}
	}
	echo '<form id="sarkf2bwlForm" action="' . $_SERVER['PHP_SELF'] . '" method="post">' . PHP_EOL;
	$this->myPanel->aLabelFor('f2bwhitelist');
	echo '<div class="w3-margin-bottom">';
	echo '<p class="w3-small w3-text-grey">LAN and localhost are always ignored. Add office NATs, monitors, or trusted peers here.</p>';
	if (!empty($extras)) {
		echo '<ul class="w3-ul w3-border w3-white w3-small">';
		foreach ($extras as $e) {
			$get = '?f2bwldel=yes&amp;ip=' . rawurlencode($e);
			echo '<li>' . htmlspecialchars($e) . ' ';
			echo '<a class="w3-right w3-text-red" href="' . $_SERVER['PHP_SELF'] . $get . '">remove</a></li>';
		}
		echo '</ul>';
	}
	echo '<div class="w3-row w3-margin-top">';
	echo '<div class="w3-col m8">';
	echo '<input class="w3-input w3-border" type="text" name="f2bwlip" placeholder="IP or CIDR e.g. 203.0.113.10 or 10.0.0.0/8" />';
	echo '</div><div class="w3-col m4">';
	echo '<button class="w3-button w3-green w3-block" type="submit" name="f2bwladd" value="1">Add</button>';
	echo '</div></div></div>';
	echo '</form>' . PHP_EOL;
	echo '</div>';
}

private function saveMailPref() {
	$this->myPanel->xlateBooleans($this->myBooleans);
	$f2bmail = isset($_POST['f2bmail']) ? strip_tags($_POST['f2bmail']) : 'NO';
	if (!is_executable($this->ctl)) {
		$this->message = 'Fail2Ban control script missing';
		return;
	}
	$pref = ($f2bmail === 'YES') ? 'on' : 'off';
	$this->helper->request_syscmd($this->ctl . ' mail-pref ' . $pref);
	$this->message = ($pref === 'on') ? 'Ban email enabled (if SMTP + supervisor email ready)' : 'Ban email disabled';
}

private function f2bUnban() {
	if (!is_executable($this->ctl)) {
		$this->message = 'Fail2Ban control script missing';
		return;
	}
	$ip = '';
	if (isset($_POST['f2bunban']) && $_POST['f2bunban'] !== 'manual') {
		$ip = strip_tags($_POST['f2bunban']);
	} elseif (!empty($_POST['f2bunbanip'])) {
		$ip = strip_tags($_POST['f2bunbanip']);
	}
	$ip = trim($ip);
	if ($ip === '' || !preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $ip)) {
		$this->message = 'Invalid IP to unban';
		return;
	}
	$this->helper->request_syscmd($this->ctl . ' unban ' . escapeshellarg($ip));
	$this->message = 'Unbanned ' . $ip;
}

private function f2bWhitelistAdd() {
	if (!is_executable($this->ctl)) {
		$this->message = 'Fail2Ban control script missing';
		return;
	}
	$ip = isset($_POST['f2bwlip']) ? trim(strip_tags($_POST['f2bwlip'])) : '';
	if ($ip === '' || !preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}(\/([0-9]|[12][0-9]|3[0-2]))?$/', $ip)) {
		$this->message = 'Invalid whitelist IP/CIDR';
		return;
	}
	$this->helper->request_syscmd($this->ctl . ' whitelist-add ' . escapeshellarg($ip));
	$this->message = 'Whitelisted ' . $ip;
}

private function f2bWhitelistDel() {
	if (!is_executable($this->ctl)) {
		$this->message = 'Fail2Ban control script missing';
		return;
	}
	$ip = isset($_GET['ip']) ? trim(strip_tags($_GET['ip'])) : '';
	if ($ip === '' || !preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}(\/([0-9]|[12][0-9]|3[0-2]))?$/', $ip)) {
		$this->message = 'Invalid whitelist IP/CIDR';
		return;
	}
	$this->helper->request_syscmd($this->ctl . ' whitelist-del ' . escapeshellarg($ip));
	$this->message = 'Removed whitelist ' . $ip;
}

}
?>
