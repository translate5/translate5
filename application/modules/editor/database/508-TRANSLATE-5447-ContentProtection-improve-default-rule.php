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

use MittagQI\Translate5\ContentProtection\Model\ContentRecognition;
use MittagQI\Translate5\ContentProtection\Model\Db\ContentRecognitionTable;
use MittagQI\Translate5\ContentProtection\T5memory\RecalculateRulesHashWorker;

$SCRIPT_IDENTIFIER = '508-TRANSLATE-5447-ContentProtection-improve-default-rule.php';

$db = Zend_Db_Table::getDefaultAdapter();

$names = [
    'default with "\'" separator' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2}\.){0,1}(\d{3}\.)*\d{3}'\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with dot thousand decimal comma' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2}\.){0,1}(\d{3}\.)*\d{3},\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with comma thousand decimal dot' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2},){0,1}(\d{3},)*\d{3}\.\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default chinese' => <<<REGEX
/(\s|^|\()([±\-+]?(\d{1,4},){0,1}(\d{4},)*\d{4}\.\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with comma thousand decimal middle dot' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2},){0,1}(\d{3},)*\d{3}·\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with whitespace thousand decimal dot' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2} ){0,1}(\d{3} )*\d{3}\.\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with [THSP] thousand decimal dot' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2}\x{2009}){0,1}(\d{3}\x{2009})*\d{3}\.\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with [NNBSP] thousand decimal dot' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2}\x{202F}){0,1}(\d{3}\x{202F})*\d{3}\.\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with "˙" thousand decimal dot' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2}˙){0,1}(\d{3}˙)*\d{3}\.\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with "\'" thousand decimal dot' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2}'){0,1}(\d{3}')*\d{3}\.\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with whitespace thousand decimal comma' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2} ){0,1}(\d{3} )*\d{3},\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with [THSP] thousand decimal comma' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2}\x{2009}){0,1}(\d{3}\x{2009})*\d{3},\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with [NNBSP] thousand decimal comma' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2}\x{202F}){0,1}(\d{3}\x{202F})*\d{3},\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with "˙" thousand decimal comma' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2}˙){0,1}(\d{3}˙)*\d{3},\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
    'default with "\'" thousand decimal comma' => <<<REGEX
/(\s|^|\()([±\-+]?([1-9]\d{0,2}'){0,1}(\d{3}')*\d{3},\d+)(([\.,;:?!](\s|$))|\s|$|\))/u
REGEX,
];

foreach ($names as $name => $regex) {
    $s = $db->select()
        ->from(ContentRecognitionTable::TABLE_NAME)
        ->where('type = ?', 'float')
        ->where('name = ?', $name);

    $row = $db->fetchRow($s);

    $recognition = new ContentRecognition();
    $recognition->init(
        new Zend_Db_Table_Row(
            [
                'table' => $recognition->db,
                'data' => $row,
                'stored' => true,
                'readOnly' => false,
            ]
        )
    );

    $recognition->setRegex($regex);
    $recognition->save();

    if ($recognition->getEnabled()) {
        $worker = new RecalculateRulesHashWorker();
        $worker->init(parameters: [
            'recognitionId' => $recognition->getId(),
        ]);
        $worker->queue();
    }
}
