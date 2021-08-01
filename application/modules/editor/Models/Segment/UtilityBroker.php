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
 * Broker which provides various segment helper instances
 * instancing is done with lazy loading
 * @property-read editor_Models_Segment_TagProtection $tagProtection TagProtection instance
 * @property-read editor_Models_Segment_Whitespace $whitespace Whitespace instance
 * @property-read editor_Models_Segment_InternalTag $internalTag InternalTag Helper instance
 * @property-read editor_Models_Segment_TermTag $termTag TermTag Helper instance
 * @property-read editor_Models_Segment_TrackChangeTag $trackChangeTag TrackChangesTag Helper instance
 */
class editor_Models_Segment_UtilityBroker {
    /**
     * List of available segment content helpers
     * @var array
     */
    protected $utilities = [
        'tagProtection' => 'editor_Models_Segment_TagProtection',
        'whitespace' => 'editor_Models_Segment_Whitespace',
        'internalTag' => 'editor_Models_Segment_InternalTag',
        'termTag' => 'editor_Models_Segment_TermTag',
        'trackChangeTag' => 'editor_Models_Segment_TrackChangeTag',
    ];
    
    /**
     * Helper instances
     * @var array
     */
    protected $utilityInstances = [];
    
    /**
     * @param string $utility
     * @return NULL|object
     */
    public function __get(string $utility) {
        if(!empty($this->utilityInstances[$utility])) {
            return $this->utilityInstances[$utility];
        }
        if(empty($this->utilities[$utility])) {
            return null;
        }
        return $this->utilityInstances[$utility] = ZfExtended_Factory::get($this->utilities[$utility]);
    }
}