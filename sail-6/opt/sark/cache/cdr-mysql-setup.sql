CONNECT mysql;

CREATE DATABASE IF NOT EXISTS asterisk; 

CREATE USER 'asterisk'@'localhost' IDENTIFIED BY 'aster1sk';

GRANT ALL ON asterisk.* TO asterisk@localhost; 

USE asterisk; 

CREATE TABLE IF NOT EXISTS `cdr` ( 
`calldate` datetime NOT NULL DEFAULT (CURRENT_DATE), 
`clid` varchar(80) NOT NULL default '', 
`src` varchar(80) NOT NULL default '', 
`dst` varchar(80) NOT NULL default '', 
`dcontext` varchar(80) NOT NULL default '',  
`channel` varchar(80) NOT NULL default '', 
`dstchannel` varchar(80) NOT NULL default '', 
`lastapp` varchar(80) NOT NULL default '', 
`lastdata` varchar(80) NOT NULL default '', 
`duration` int(11) NOT NULL default '0', 
`billsec` int(11) NOT NULL default '0', 
`disposition` varchar(45) NOT NULL default '',  
`amaflags` int(11) NOT NULL default '0', 
`accountcode` varchar(20) NOT NULL default '', 
`userfield` varchar(255) NOT NULL default '' 
); 

ALTER TABLE `cdr` ADD INDEX ( `calldate` ); 
ALTER TABLE `cdr` ADD INDEX ( `dst` ); 
ALTER TABLE `cdr` ADD INDEX ( `accountcode` ); 

FLUSH PRIVILEGES;
