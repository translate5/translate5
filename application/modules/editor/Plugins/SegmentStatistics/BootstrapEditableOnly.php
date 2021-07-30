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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Plugin Bootstrap for Segment Statistics Plugin
 * Creates Statistics for editable segments only!
 * 
 * WARNING: statistics of non editable segments are deleted!!!!
 */
class editor_Plugins_SegmentStatistics_BootstrapEditableOnly extends editor_Plugins_SegmentStatistics_Bootstrap {
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_SegmentStatistics_Bootstrap::init()
     */
    public function init() {
        $this->blocks('editor_Plugins_SegmentStatistics_Bootstrap');
        $this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterImportCreateStat'), -90);
        $this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterImportCleanup'), -11000);
        $this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleImportWriteStat'), -12000);
        $this->eventManager->attach('editor_Models_Export', 'afterExport', array($this, 'handleAfterExport'), -10000);
        $this->eventManager->attach('editor_Models_Export', 'afterExport', array($this, 'handleAfterExportCleanup'), -11000);
        $this->eventManager->attach('editor_Models_Export', 'afterExport', array($this, 'handleExportWriteStat'), -12000);
    }
    
    /**
     * handler for event: editor_Models_Import#afterImport
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterImportCleanup(Zend_EventManager_Event $event) {
        $this->callWorker($event->getParam('task'), 'editor_Plugins_SegmentStatistics_CleanUpWorker', editor_Plugins_SegmentStatistics_CleanUpWorker::TYPE_IMPORT);
    }
    
    /**
     * handler for event: editor_Models_Export#afterExport
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterExportCleanup(Zend_EventManager_Event $event) {
        $this->callWorker($event->getParam('task'), 'editor_Plugins_SegmentStatistics_CleanUpWorker', editor_Plugins_SegmentStatistics_CleanUpWorker::TYPE_EXPORT);
    }
}