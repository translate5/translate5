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
$SCRIPT_IDENTIFIER = '444-TRANSLATE-4008-set-no-reply-in-installation-script.php';

$iniFile = APPLICATION_PATH . '/config/installation.ini';
$backup = APPLICATION_ROOT . "/data/backup/installation_backup-" . date('Y-m-d-h-i-s') . '.ini';

// Make backup.
copy($iniFile, $backup);

// Read the file into an array, with each line as an array element
$lines = file($iniFile);

foreach ($lines as $index => $line) {
    if (str_starts_with($line, 'resources.mail.defaultFrom.email')) {
        $lines[$index] = str_replace('support@translate5.net', 'noreply@translate5.net', $line);
    }
}

file_put_contents($iniFile, implode('', $lines));
