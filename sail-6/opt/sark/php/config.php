<?php

/**
 * System
 */

define('EXEC_DB_RELOAD',				'sh /opt/sark/scripts/srkV4reloader.sh');

define('SNAPSHOTS', 					'/opt/sark/snap/');
define('BACKUPS', 						'/opt/sark/bkup/');


define('MONITOR',						'/var/spool/asterisk/monitor/');
define('MONOUT',						'/var/spool/asterisk/monout/');
define('MONSTAGE',						'/var/spool/asterisk/monstage/');

define('SARK_DB',						'/opt/sark/db/sark.db'); 
define('SARK_READONLY_DB',				'/opt/sark/db/sark.rdonly.db');
define('SARK_PATH',                     '/opt/sark');
define('ROOTDIR',                       '/opt/sark/db');


/**
 * PJSIP
 */

define('PJSIP', 						'/etc/asterisk/sark_pjsip_');
define('PJSIP_ANONYMOUS',				'anonymous.conf');
define('PJSIP_GLOBALS', 				'globals.conf');

define('PJSIP_PHONE',					'phone.conf');
define('PJSIP_PHONE_TEMPLATE',          'phone.tmpl');
define('PJSIP_READY_PHONES',            'ready_phones.conf');

define('PJSIP_WEBRTC',					'webrtc.conf');
define('PJSIP_WEBRTC_TEMPLATE',         'webrtc.tmpl');
define('PJSIP_READY_WEBRTC',            'ready_webrtc.conf');
	      
define('PJSIP_TRANSPORT',				'transport.conf');	
define('PJSIP_TRANSPORT_TEMPLATE',		'transport.tmpl');

define('PJSIP_TRUNK',					'trunk.conf');
define('PJSIP_TRUNK_SNDREG_TEMPLATE',	'trunk_sndreg.tmpl');
define('PJSIP_TRUNK_RCVREG_TEMPLATE',	'trunk_rcvreg.tmpl');
define('PJSIP_TRUNK_TRUSTED_TEMPLATE',	'trunk_trusted.tmpl');
define('PJSIP_READY_TRUNKS',            'ready_trunks.conf');
