<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renaming etc.
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '481-TRANSLATE-4733-no-analysis-entries-saved.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();
$new = json_decode('[
        {
            "unicode": "U+00AB",
            "visualized": "«"
        },
        {
            "unicode": "U+00BB",
            "visualized": "»"
        }
]', true);

$config = Zend_Registry::get('config');
$specialCharacters = json_decode($config->runtimeOptions->editor->segments->editorSpecialCharacters, true);
if (! isset($specialCharacters['ru'])) {
    $specialCharacters['ru'] = [];
}
$specialCharacters['ru'] = array_merge($specialCharacters['ru'], $new);
$specialCharactersJson = json_encode(
    $specialCharacters,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$db->query(
    'UPDATE `Zf_configuration` SET `value` = :value WHERE `name` = "runtimeOptions.editor.segments.editorSpecialCharacters"',
    [
        'value' => $specialCharactersJson,
    ]
);
