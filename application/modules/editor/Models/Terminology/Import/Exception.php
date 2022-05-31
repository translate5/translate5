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
class editor_Models_Terminology_Import_Exception extends ZfExtended_ErrorCodeException {
    /**
     * @var string
     */
    protected $domain = 'editor.terminology.import';

    protected static $localErrorCodes = [
        'E1353' => 'TBX Import: Folder to save images does not exist or is not writable!',
        'E1354' => 'TBX Import: Folder to save termcollection images could not be created!',
        'E1356' => 'TBX Import: Import error - {msg}',
        'E1357' => 'TBX Import: Could not import due unknown attribute level',
        'E1393' => 'TBX Import: The XML structure of the TBX file is invalid: {message}',
    ];
}