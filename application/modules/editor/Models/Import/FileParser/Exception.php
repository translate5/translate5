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

/**
 *
 */
class editor_Models_Import_FileParser_Exception extends editor_Models_Import_Exception {
    /**
     * @var string
     */
    protected $domain = 'editor.import.fileparser';
    
    static protected $localErrorCodes = [
        'E1083' => 'The encoding of the file "{fileName}" is none of the encodings utf-8, iso-8859-1 and win-1252.',
        'E1084' => 'Given MID was to long (max 1000 chars), MID: "{mid}".',
        'E1325' => 'Something went wrong when loading task config template with name: {filename}. The error was:{errorMessage}',
        'E1327' => 'The config value {name} given in the task-config.ini does not exist in the main configuration and is ignored therefore.',
    ];
}