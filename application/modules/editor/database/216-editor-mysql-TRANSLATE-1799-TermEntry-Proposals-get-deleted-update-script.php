<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
  README:
    update the term entry isProposal flag when in the term entry are only terms with proces status unprocessed
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '216-editor-mysql-TRANSLATE-1799-TermEntry-Proposals-get-deleted-update-script.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

//find all term entries,and the terms count in it with terms with processStatus unprocessed
$db = Zend_Db_Table::getDefaultAdapter();
$sql= 'select te.id as id,count(*) as total from LEK_terms t
        inner join LEK_term_entry te on t.termEntryId=te.id
        where t.processStatus="unprocessed"
        group by te.id';
$res = $db->query($sql, $SCRIPT_IDENTIFIER);
$allProposalsInEntry = $res->fetchAll();
//foreach term entries which are containing terms with process status unporocessed, check if all of the terms in this term entry
//are with proces status unprocessed, if yes that meens that this term entry is proposed
foreach ($allProposalsInEntry as $p) {
    $sql='update LEK_term_entry set isProposal=(if((select count(*) from LEK_terms where LEK_terms.termEntryId='.$p['id'].')='.$p['total'].',1,0)) where LEK_term_entry.id='.$p['id'].';';
    $db->query($sql, $SCRIPT_IDENTIFIER);
}
