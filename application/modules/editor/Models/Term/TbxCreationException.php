<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
			 https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Exception on TBX creation for term tagging
 */
class editor_Models_Term_TbxCreationException extends ZfExtended_ErrorCodeException {
    /**
     * @var string
     */
    protected $domain = 'editor.terminology';
    
    protected static $localErrorCodes = [
        'E1113' => 'No term collection assigned to task although tasks terminology flag is true.',
        'E1114' => 'The associated collections don\'t contain terms in the languages of the task.',
        'E1115' => 'collected terms could not be converted to XML.',
    ];
}