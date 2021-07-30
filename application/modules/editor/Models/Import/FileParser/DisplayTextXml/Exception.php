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
 * Should be used for errors in the context of DisplayTextXml import processing
 */
class editor_Models_Import_FileParser_DisplayTextXml_Exception extends editor_Models_Import_FileParser_Exception {
    /**
     * @var string
     */
    protected $domain = 'editor.import.fileparser.displaytextxml';
    
    static protected $localErrorCodes = [
        'E1273' => 'The XML of the DisplayText XML file "{fileName} (id {fileId})" is invalid!',
        'E1274' => 'The DisplayText XML file "{fileName} (id {fileId})" does not contain any translation relevant segments.',
        'E1275' => 'Element "Inset" with ID {id} has the invalid type {type}, only type "pixel" is supported!',
        'E1276' => 'Element "Len" with ID {id} has the invalid type {type}, only type "pixel" is supported!',
    ];
}
