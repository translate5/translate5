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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

class editor_Models_Excel_ExImportException extends ZfExtended_ErrorCodeException {
        /**
         * @var string
         */
        protected $domain = 'editor.task.exceleximport';
        
        static protected $localErrorCodes = [
            // Export:
            'E1137' => 'Task can not be exported as Excel-file.',
            
            // Reimport:
            'E1138' => 'Excel Reimport: Formal check failed: task-guid differs in task compared to the excel.',
            'E1139' => 'Excel Reimport: Formal check failed: number of segments differ in task compared to the excel.',
            'E1140' => 'Excel Reimport: Formal check failed: segment #{segmentNr} is empty in excel while there was content in the the original task.',
            'E1141' => 'Excel Reimport: upload failed.',
        ];
}