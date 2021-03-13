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
 * 
 */
class editor_Models_Quality_SegmentView extends editor_Models_Quality_AbstractView {
    
    /**
     * Seperates the type name from the category name
     * @var string
     */
    const SEPERATOR = ' > ';
    /**
     * Overrides to only supply the qualities for a single segment
     * {@inheritDoc}
     * @see editor_Models_Quality_AbstractView::create()
     */
    protected function create(){
        foreach($this->dbRows as $dbRow){
            /* @var $dbRow editor_Models_Db_SegmentQualityRow */
            $row = new stdClass();
            $row->id = $dbRow->id;
            $row->type = $dbRow->type;
            $row->segmentId = $dbRow->segmentId;
            $row->falsePositive = $dbRow->falsePositive;
            $row->title = $this->manager->translateQualityType($dbRow->type).self::SEPERATOR.$this->manager->translateQualityCategory($dbRow->type, $dbRow->category, $this->task);
            $row->fields = $dbRow->getFields();
            $this->rows[] = $row;
        }
        usort($this->rows, 'editor_Models_Quality_AbstractView::compareByTitle');
    }
}
