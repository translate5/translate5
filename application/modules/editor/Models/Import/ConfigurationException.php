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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 */
class editor_Models_Import_ConfigurationException extends ZfExtended_ErrorCodeException {
    /**
     * @var string
     */
    protected $domain = 'editor.import.configuration';
    
    static protected $localErrorCodes = [
        'E1032' => 'The passed source language "{language}" is not valid.',
        'E1033' => 'The passed target language "{language}" is not valid.',
        'E1035' => 'The given taskGuid "{taskGuid}" was not valid GUID.',
        'E1036' => 'The given userGuid "{userGuid}" was not valid GUID.',
        'E1037' => 'The given userName "{userName}" was not valid user name.',
        'E1038' => 'The import root folder does not exist. Path "{folder}".',
        'E1039' => 'The imported package did not contain a valid "{review}" folder.',
        'E1040' => 'The imported package did not contain any files in the "{review}" folder.',
        'E1338' => 'IMPORTANT: The "proofRead" folder in the zip import package is deprecated from now on. In the future please always use the new folder "workfiles" instead. All files that need to be reviewed or translated will have to be placed in the new folder "workfiles" from now on. In some future version of translate5 the support for "proofRead" folder will be completely removed. Currently it still is supported, but will write a "deprecated" message to the php error-log.'
    ];
}
