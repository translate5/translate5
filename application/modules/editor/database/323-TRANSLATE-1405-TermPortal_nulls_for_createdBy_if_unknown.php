<?php 
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * For tables having non-existent values in createdBy and/or updatedBy columns - set NULLs
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renaming etc...
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '323-TRANSLATE-1405-TermPortal_nulls_for_createdBy_if_unknown.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

// this is workaround just to be able to use the system user when we check the workflow bellow.
defined('ZFEXTENDED_IS_WORKER_THREAD') || define('ZFEXTENDED_IS_WORKER_THREAD', true);

/* @var $user ZfExtended_Models_User */
$user = ZfExtended_Factory::get('ZfExtended_Models_User');
if (empty($all = $user->loadAll())) {
    return;
}

// Get NOT IN (...) clause
$notIN = $user->db->getAdapter()->quoteInto('NOT IN (?)', array_column($all, 'id'));

// Run query for terms_attributes table
$user->db->getAdapter()->query('UPDATE `terms_attributes` SET `createdBy` = NULL WHERE `createdBy` ' . $notIN);
$user->db->getAdapter()->query('UPDATE `terms_attributes` SET `updatedBy` = NULL WHERE `updatedBy` ' . $notIN);

// Run queries for remaining tables
foreach (['terms_attributes_history', 'terms_term', 'terms_term_history'] as $table)
    $user->db->getAdapter()->query('UPDATE `' . $table . '` SET `updatedBy` = NULL WHERE `updatedBy` ' . $notIN);
