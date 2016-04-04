<?php
/**
 * File:
 * config.plugin.php
 *
 * @package Plugins
 * @subpackage Newsletter
 * @version $Rev$
 * @since 2.0
 * @author Ortwin Pinke <o.pinke@conlite.org>
 * @copyright 2012 CL-Team
 * @link http://www.conlite.org
 *
 * $Id$
 */

// security check
defined('CON_FRAMEWORK') or die('Illegal call');

# 2015-12-05 :: Create DB tables if nessecary -->
function pinlCreateDbTables($db, $cfg) {
    $sql = 'CREATE TABLE `' . $cfg['sql']['sqlprefix'] . '_news` (
                `idnews` int(10) NOT NULL DEFAULT "0",
                `idclient` int(10) NOT NULL DEFAULT "0",
                `idlang` int(10) NOT NULL DEFAULT "0",
                `idart` int(10) NOT NULL DEFAULT "0",
                `template_idart` int(10) NOT NULL DEFAULT "0",
                `type` varchar(10) NOT NULL DEFAULT "text",
                `name` varchar(255) NOT NULL,
                `subject` text,
                `message` longtext,
                `newsfrom` varchar(255) NOT NULL,
                `newsfromname` varchar(255) DEFAULT NULL,
                `newsdate` datetime DEFAULT NULL,
                `welcome` tinyint(1) NOT NULL DEFAULT "0",
                `use_cronjob` tinyint(1) NOT NULL DEFAULT "0",
                `send_to` varchar(32) NOT NULL DEFAULT "all",
                `send_ids` text,
                `dispatch` tinyint(1) NOT NULL DEFAULT "0",
                `dispatch_count` int(5) NOT NULL DEFAULT "50",
                `dispatch_delay` int(5) NOT NULL DEFAULT "5",
                `author` varchar(32) NOT NULL,
                `created` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                `modified` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                `modifiedby` varchar(32) NOT NULL,
                PRIMARY KEY (`idnews`)
            )';
    $db->query($sql);
    $sql = 'CREATE TABLE `' . $cfg['sql']['sqlprefix'] . '_news_groupmembers` (
                `idnewsgroupmember` int(10) NOT NULL DEFAULT "0",
                `idnewsgroup` int(10) NOT NULL DEFAULT "0",
                `idnewsrcp` int(10) NOT NULL DEFAULT "0",
                PRIMARY KEY (`idnewsgroupmember`)
            )';
    $db->query($sql);
    $sql = 'CREATE TABLE `' . $cfg['sql']['sqlprefix'] . '_news_groups` (
                `idnewsgroup` int(10) NOT NULL DEFAULT "0",
                `idclient` int(10) NOT NULL DEFAULT "0",
                `idlang` int(10) NOT NULL DEFAULT "0",
                `groupname` varchar(32) NOT NULL,
                `defaultgroup` tinyint(1) NOT NULL DEFAULT "0",
                PRIMARY KEY (`idnewsgroup`)
            )';
    $db->query($sql);
    $sql = 'CREATE TABLE `' . $cfg['sql']['sqlprefix'] . '_news_jobs` (
                `idnewsjob` int(10) NOT NULL DEFAULT "0",
                `idclient` int(10) NOT NULL DEFAULT "0",
                `idlang` int(10) NOT NULL DEFAULT "0",
                `idnews` int(10) NOT NULL DEFAULT "0",
                `status` tinyint(1) NOT NULL DEFAULT "0",
                `use_cronjob` tinyint(1) NOT NULL DEFAULT "0",
                `started` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                `finished` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                `name` varchar(255) NOT NULL,
                `type` varchar(10) NOT NULL DEFAULT "text",
                `encoding` varchar(32) NOT NULL DEFAULT "iso-8859-1",
                `newsfrom` varchar(255) NOT NULL,
                `newsfromname` varchar(255) NOT NULL,
                `newsdate` datetime DEFAULT "0000-00-00 00:00:00",
                `subject` text,
                `idart` int(10) NOT NULL DEFAULT "0",
                `message_text` longtext NOT NULL,
                `message_html` longtext,
                `send_to` text NOT NULL,
                `dispatch` tinyint(1) NOT NULL DEFAULT "0",
                `dispatch_count` int(5) NOT NULL DEFAULT "50",
                `dispatch_delay` int(5) NOT NULL DEFAULT "5",
                `author` varchar(32) NOT NULL,
                `authorname` varchar(32) NOT NULL,
                `rcpcount` int(10) NOT NULL DEFAULT "0",
                `sendcount` int(10) NOT NULL DEFAULT "0",
                `created` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                `modified` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                `modifiedby` varchar(32) NOT NULL,
                PRIMARY KEY (`idnewsjob`)
            )';
    $db->query($sql);
    $sql = 'CREATE TABLE `' . $cfg['sql']['sqlprefix'] . '_news_log` (
                `idnewslog` int(10) NOT NULL DEFAULT "0",
                `idnewsjob` int(10) NOT NULL DEFAULT "0",
                `idnewsrcp` int(10) NOT NULL DEFAULT "0",
                `rcpname` varchar(255) NOT NULL,
                `rcpemail` varchar(255) NOT NULL,
                `rcphash` varchar(32) NOT NULL,
                `rcpnewstype` tinyint(1) NOT NULL DEFAULT "0",
                `status` varchar(255) NOT NULL,
                `sent` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                `created` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                PRIMARY KEY (`idnewslog`)
            )';
    $db->query($sql);
    $sql = 'CREATE TABLE `' . $cfg['sql']['sqlprefix'] . '_news_rcp` (
                `idnewsrcp` int(10) NOT NULL DEFAULT "0",
                `idclient` int(10) NOT NULL DEFAULT "0",
                `idlang` int(10) NOT NULL DEFAULT "0",
                `email` varchar(255) DEFAULT NULL,
                `confirmed` tinyint(1) NOT NULL DEFAULT "0",
                `confirmeddate` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                `lastaction` varchar(32) DEFAULT NULL,
                `name` varchar(255) DEFAULT NULL,
                `hash` varchar(32) NOT NULL,
                `deactivated` tinyint(1) NOT NULL DEFAULT "0",
                `news_type` tinyint(1) NOT NULL DEFAULT "0",
                `author` varchar(32) NOT NULL,
                `created` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                `lastmodified` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
                `modifiedby` varchar(32) NOT NULL,
                PRIMARY KEY (`idnewsrcp`)
            )';
    $db->query($sql);
}
if (!$db) {
    $db = new DB_Contenido();
}
#$sql = 'SELECT idnews
#        FROM ' . $cfg['sql']['sqlprefix'] . '_news
#        LIMIT 0, 1';
#if (!$db->query($sql)) {
$sql = 'SHOW TABLES LIKE "' . $cfg['sql']['sqlprefix'] . '_news"';
$db->query($sql);
if (!$db->num_rows()) {
    pinlCreateDbTables($db, $cfg);
}

?>