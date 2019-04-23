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
 */
class editor_Models_Import_FileParser_Sdlxliff_Exception extends editor_Models_Import_FileParser_Exception {
    /**
     * @var string
     */
    protected $domain = 'editor.import.fileparser.sdlxliff';
    
    static protected $localErrorCodes = [
        'E1000' => 'The file "{filename}" contains SDL comments which are currently not supported!',
        'E1001' => 'The opening tag "{tagName}" contains the tagId "{tagId}" which is no valid SDLXLIFF!',
        'E1002' => 'Found a closing tag without an opening one. Segment MID: "{mid}".',
        'E1003' => 'There are change Markers in the sdlxliff-file "{filename}"! Please clear them first and then try to check in the file again.',
        'E1004' => 'Locked-tag-content was requested but tag does not contain a xid attribute.',
        'E1005' => '<sdl:seg-defs was not found in the current transunit: "{transunit}"',
        'E1006' => 'Loading the tag information from the SDLXLIFF header has failed!',
        'E1007' => 'The tag "{tagname}" is not defined in the "_tagDefMapping" list.',
        'E1008' => 'The tag ID "{tagId}" contains a dash "-" which is not allowed!',
        'E1009' => 'The source and target segment count does not match in transunit: "{transunit}".', 
        'E1010' => 'The tag "{tagname}" was used in the segment but is not defined in the "_tagDefMapping" list!',
    ];
}
