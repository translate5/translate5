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
 * Covers all errors in the task export
 */
class editor_Models_Export_FileParser_Exception extends ZfExtended_ErrorCodeException {
    /**
     * @var string
     */
    protected $domain = 'editor.export.fileparser';
    
    static protected $localErrorCodes = [
        'E1085' => 'this->_classNameDifftagger must be defined in the child class.',
        'E1086' => 'Error in Export-Fileparsing. instead of a id="INT" and a optional field="STRING" attribute the following content was extracted: "{content}"',
        //duplicates E1086 at different place
        'E1087' => 'Error in Export-Fileparsing. instead of a id="INT" and a optional field="STRING" attribute the following content was extracted: "{content}"',
        'E1088' => 'Error in diff tagging of export. For details see the previous exception.',
    ];
}