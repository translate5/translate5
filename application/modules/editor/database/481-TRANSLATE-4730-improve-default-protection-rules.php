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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

use MittagQI\Translate5\ContentProtection\T5memory\RecalculateRulesHashWorker;

set_time_limit(0);

/* @var $this ZfExtended_Models_Installer_DbUpdater */

//$this->doNotSavePhpForDebugging = false;

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();

$db->query(
    <<<SQL
UPDATE `LEK_content_protection_content_recognition`
SET `regex` = '/(\\\s|^|\\\()([±\\\-+]?[1-9]\\\d{0,2}((·)(\\\d{3}·)*\\\d{3})?)(([\\\.,;:?!](\\\s|$))|\\\s|$|\\\))/u'
WHERE type = 'integer' and name = 'default generic with Middle dot separator';
SQL
)->execute();
$db->query(
    <<<SQL
UPDATE `LEK_content_protection_content_recognition`
SET `regex` = '/(\\\s|^|\\\()([±\\\-+]?[1-9]\\\d{0,2}((,)(\\\d{3},)*\\\d{3})?)(([\\\.,;:?!](\\\s|$))|\\\s|$|\\\))/u'
WHERE type = 'integer' and name = 'default generic with comma separator';
SQL
)->execute();
$db->query(
    <<<SQL
UPDATE `LEK_content_protection_content_recognition`
SET `regex` = '/(\\\s|^|\\\()([±\\\-+]?[1-9]\\\d{0,2}((\\\.)(\\\d{3}\\\.)*\\\d{3})?)(([\\\.,;:?!](\\\s|$))|\\\s|$|\\\))/u'
WHERE type = 'integer' and name = 'default generic with dot';
SQL
)->execute();
$db->query(
    <<<SQL
UPDATE `LEK_content_protection_content_recognition`
SET `regex` = '/(\\\s|^|\\\()([±\\\-+]?[1-9]\\\d{0,2}((˙)(\\\d{3}˙)*\\\d{3})?)(([\\\.,;:?!](\\\s|$))|\\\s|$|\\\))/u'
WHERE type = 'integer' and name = 'default generic with dot above separator';
SQL
)->execute();
$db->query(
    <<<SQL
UPDATE `LEK_content_protection_content_recognition`
SET `regex` = '/(\\\s|^|\\\()([±\\\-+]?[1-9]\\\d{0,2}((\')(\\\d{3}\')*\\\d{3})?)(([\\\.,;:?!](\\\s|$))|\\\s|$|\\\))/u'
WHERE type = 'integer' and name = 'default generic with apostrophe separator';
SQL
)->execute();
$db->query(
    <<<SQL
UPDATE `LEK_content_protection_content_recognition`
SET `regex` = '/(\\\s|^|\\\()([±\\\-+]?[1-9]\\\d{0,2}((\\\x{2009})(\\\d{3}\\\x{2009})*\\\d{3})?)(([\\\.,;:?!](\\\s|$))|\\\s|$|\\\))/u'
WHERE type = 'integer' and name = 'default generic with thin space separator';
SQL
)->execute();

$db->query(
    <<<SQL
UPDATE `LEK_content_protection_content_recognition`
SET `regex` = '/(\\\s|^|\\\()([±\\\-+]?[1-9]\\\d{0,2}((\\\x{202F})(\\\d{3}\\\x{202F})*\\\d{3})?)(([\\\.,;:?!](\\\s|$))|\\\s|$|\\\))/u'
WHERE type = 'integer' and name = 'default generic with NNBSP separator';
SQL
)->execute();

$ruleIds = $db->fetchCol('SELECT id FROM `LEK_content_protection_content_recognition` WHERE type = "integer" and isDefault = 1');

foreach ($ruleIds as $ruleId) {
    $worker = new RecalculateRulesHashWorker();
    $worker->init(parameters: [
        'recognitionId' => (int) $ruleId,
    ]);
    $worker->queue();
}
