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
 * Provides the data for the qualities filter panel in the frontend
 */
class editor_Models_Quality_Notifications extends editor_Models_Quality_AbstractData {
    
    /**
     * Defines if we add false positive qualities
     * @var boolean
     */
    const INCLUDE_FALSEPOSITIVES = true;
    /**
     * Defines what kind of qualities we add (keep in mind this should only be qualities that have no representation as tags)
     * @var array
     */
    const INCLUDED_TYPES = [ 'qm' ];
    
    protected $addTranslations = true;    
    
    protected function applyDefaults(){
        $this->types = self::INCLUDED_TYPES;
        $this->excludeFalsePositives = !self::INCLUDE_FALSEPOSITIVES;
    }
    /**
     * We only need the translated name of the Quality
     * {@inheritDoc}
     * @see editor_Models_Quality_AbstractData::transformRow()
     */
    protected function transformRow(array $qualityData){
        return $qualityData['text'];
    }
    /**
     * ... and we want the output sorted (eng & ger only currently so natsort is sufficient)
     * {@inheritDoc}
     * @see editor_Models_Quality_AbstractData::transformData()
     */
    protected function transformData(array $segmentData) : array {
        $segmentData = array_unique($segmentData, SORT_STRING);
        natsort ($segmentData);
        return $segmentData;
    }
}
