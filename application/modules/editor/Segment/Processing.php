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
 * Provides  Methods & Flags to Orchestrate the Segment tags Processing
 */
class editor_Segment_Processing {
    
    /**
     * Used to indicate an import process
     * @var string
     */
    const IMPORT = 'import';
    /**
     * Used to indicate an editing process
     * @var string
     */
    const EDIT = 'edit';
    /**
     * #Used to indicate an alike segment copying process
     * @var string
     */
    const ALIKE = 'alike';
    /**
     * Used to indicate an retagging process (rebuilding qualities)
     * @var string
     */
    const RETAG = 'retag';
    /**
     * Used to indicate a match analysis retagging
     * @var string
     */
    const ANALYSIS = 'analysis';
    /**
     * Used to indicate a TermTagging Only Operation
     * @var string
     */
    const TAGTERMS = 'tagterms';
    
    /**
     * Evaluates if we have a operational processing mode
     * operational means, that we have workers, who save their results to LEK_segment_tags instead of back to the segments directly
     * this requires a finising worker calling editor_Segment_Processing::finishOperation 
     * @param string $type
     * @return bool
     */
    public static function isOperation(string $type) : bool {
        return ($type == self::IMPORT || $type == self::ANALYSIS || $type == self::RETAG);
    }
}
