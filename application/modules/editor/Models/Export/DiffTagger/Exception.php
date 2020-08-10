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
 * Covers all errors in the diff tagging
 */
class editor_Models_Export_DiffTagger_Exception extends ZfExtended_ErrorCodeException {
    /**
     * @var string
     */
    protected $domain = 'editor.export.difftagger';
    
    static protected $localErrorCodes = [
        'E1089' => 'Tag syntax error in the segment content. No diff export is possible. The segment had been: "{segment}"',
        'E1090' => 'The number of opening and closing g-Tags had not been the same! The Segment had been: "{segment}"',
        //duplicated error:
        'E1091' => 'Tag syntax error in the segment content. No diff export is possible. The segment had been: "{segment}"',
        //duplicated errors:
        'E1092' => 'The number of opening and closing g-Tags had not been the same! The Segment had been: "{segment}"',
        'E1093' => 'The number of opening and closing g-Tags had not been the same! The Segment had been: "{segment}"',
    ];
}