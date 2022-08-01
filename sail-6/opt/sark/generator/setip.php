<?php
// +-----------------------------------------------------------------------+
// |  Copyright (c) CoCoSoft 2005-10                                  |
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
// | Author: CoCoSoft                                                           |
// +-----------------------------------------------------------------------+
//

include("/opt/sark/php/srkNetHelperClass");

$net = new nethelper();

$interface = $net->get_interfaceName();
$netaddress = $net->get_networkIPV4();
$cidr = $net->get_networkCIDR();
$msk = $net->get_netMask();
$ip = $net->get_localIPV4();
$staticIPV4 = $net->get_staticIPV4();

logit  ("Interface name on this node: $interface");
logit  ("IPV4: $ip");
logit  ("staticIPV4: $staticIPV4");
logit  ("Network address: $netaddress");
logit  ("netmask: $msk");
logit  ("CIDR: $cidr");

$Phonelist = array ('aastra'=>'aastra',
					'cisco'=>'cisco',
					'ciscospa'=>'ciscospa',
					'gigaset'=>'gigaset',
					'panasonic'=>'panasonic',
					'polycom'=>'polycom',
					'snom'=>'snom',
					'yealink'=>'yealink'
					);
					
$f2b_target = '/etc/fail2ban/jail.conf';

if ($netaddress == '0.0.0.0') {
	print "No IP from ifconfig  - got $netaddress \n";
}
else {
	if ($staticIPV4) {
		logit  ("Static Virt ip $staticIPV4");
		$net->set_staticIPV4(false,$staticIPV4);
	}
	if ( file_exists( "/etc/shorewall") ) {
		`echo LAN=$netaddress/$cidr > /etc/shorewall/local.lan`;
		`echo IF1=$interface > /etc/shorewall/local.if1`;
	}
	
	if ( file_exists( "/etc/fail2ban/jail.local")) {	
		`sed -i --follow-symlinks '/^ignoreip/c \ignoreip = 127.0.0.1 $netaddress\/$cidr 224.0.1.0\/24' /etc/fail2ban/jail.local`;
		`fail2ban-server reload > /dev/null`;
	}
		
	if ( file_exists( "/etc/asterisk")) {
		# set correct Asterisk dateformat in logger.conf
		`sed -i 's/^;dateformat=%F %T /dateformat=%F %T/' /etc/asterisk/logger.conf`;
		`sed -i '/^messages/c \messages => security,notice,warning,error' /etc/asterisk/logger.conf`;
		# set localnet values for Asterisk
		`asterisk -rx 'reload' > /dev/null`;
	}
	
	if ( file_exists( "/etc/dnsmasq.d" )) {
		`tail /etc/dnsmasq.d/sarkdhcp-opt66 > /etc/dnsmasq.d/sarkdhcp-opt66`;
		foreach ($Phonelist as $phone) {
			$query = '';
			if ($phone == 'snom') {
				$query = '?mac={mac}';
			}
			if ($phone == 'panasonic') {
				$query = '?mac={mac}';
			}					 
			if ($phone == 'ciscospa') {
				$query = '/\$MAU';
			}		
			`echo "dhcp-option=$phone,option:tftp-server,\"http://$ip/provisioning$query\"" >> /etc/dnsmasq.d/sarkdhcp-opt66`;
		}
	}
/*
        Set an IP in /etc/issue for CPE systems
 */
	$srkrlse = trim(`dpkg-query -W -f '\${version}\n' sail`);
	$osrelease = trim (`lsb_release -d --short`);
	`echo "$osrelease/SARK $srkrlse running at $ip/$cidr" > /etc/issue`;
}

function logit ($someText) {
	syslog(LOG_WARNING, "SRK setip $someText");	
}

?>		
