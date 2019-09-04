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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * NEC-TM Service Base Class
 */
class editor_Plugins_NecTm_Service extends editor_Services_ServiceAbstract {
    const DEFAULT_COLOR = '#61BDAA';
    
    const TAG_ORIGIN = 'NEC';
    
    public function __construct() {
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $urls = $config->runtimeOptions->plugins->NecTm->server->toArray();
        $this->addResourceForeachUrl($this->getName(), $urls);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_ServiceAbstract::getName()
     */
    public function getName() {
        return "NEC-TM";
    }
    
    /**
     * Get the DEFAULT_COLOR
     */
    public function getDefaultColor() {
        return self::DEFAULT_COLOR;
    }
    
    /**
     * Get the tag's origin.
     */
    public function getTagOrigin() {
        return self::TAG_ORIGIN;
    }
    
    /**
     * Returns the "top-level-tags" as configured.
     * @return array
     */
    public function getTopLevelTagIds() {
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        return $config->runtimeOptions->plugins->NecTm->topLevelTagIds->toArray();
    }
}