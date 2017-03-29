<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * abstract base class to create QM SubSegment Images
 */
abstract class editor_ImageTag_QmSubSegment extends editor_ImageTag {
    public function __construct() {
        parent::__construct();
        $this->_tagDef = new Zend_Config(array(), 1);
        //preset the internal config with the default values
        $this->_tagDef->merge($this->_config->runtimeOptions->imageTag);
        //override the custimized values in the clone
        $this->_tagDef->merge($this->_config->runtimeOptions->imageTags->qmSubSegment);
    }
}