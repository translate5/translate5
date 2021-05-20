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
 * TODO: currently we process only QM qualities for Export (and MQM, which are exported on tag-level). When other qualities are included, the consuming code needs to be adjusted and preferrably the generation of tags shoul be moved into this class
 */
class editor_Models_Quality_XliffExport extends editor_Models_Quality_AbstractData {
    
    private static $qmNames = null;    
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
    /**
     * We want the untranslated names
     * @var boolean
     */
    protected $addTranslations = false;
    
    protected function applyDefaults(){
        $this->types = self::INCLUDED_TYPES;
        $this->excludeFalsePositives = !self::INCLUDE_FALSEPOSITIVES;
        // we need the categoryIndex which holds the qm index
        $this->columnsToFetch[] = 'categoryIndex';
        // the untranslated qm props. They are static to avoid unneccessary instantiation during an export
        if(static::$qmNames == null){
            $config = Zend_Registry::get('config');
            static::$qmNames = $config->runtimeOptions->segments->qualityFlags->toArray();
        }
    }
    /**
     * We only need the translated name of the Quality
     * {@inheritDoc}
     * @see editor_Models_Quality_AbstractData::transformRow()
     */
    protected function transformRow(array $qualityData){
        $qmIndex = intval($qualityData['categoryIndex']);
        $qualityData['text'] = (isset(static::$qmNames[$qmIndex])) ? static::$qmNames[$qmIndex] : 'Unknown Qm Id '.$qmIndex;
        return $qualityData;
    }
}
