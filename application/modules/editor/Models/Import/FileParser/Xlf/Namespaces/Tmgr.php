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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */


/**
 * XLF Fileparser Add On to parse IBM XLF specific stuff
 */
class editor_Models_Import_FileParser_Xlf_Namespaces_Tmgr extends editor_Models_Import_FileParser_Xlf_Namespaces_Abstract{
    const IBM_XLIFF_NAMESPACE = 'xmlns:tmgr="http://www.ibm.com"';

    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::transunitAttributes()
     */
    public function transunitAttributes(array $attributes, editor_Models_Import_FileParser_SegmentAttributes $segmentAttributes) {
        //FIXME add match rate infos into our matchRateType field!
        settype($attributes['tmgr:matchratio'], 'integer');
        $segmentAttributes->matchRate = $attributes['tmgr:matchratio'];
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::useTagContentOnly()
     */
    public function useTagContentOnly() {
        return null; //For OpenTM2 we can calculate this value depending on the tag
    }
}
