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

class editor_Plugins_NecTm_Worker extends ZfExtended_Worker_Abstract {
    
    /**
     * @var ZfExtended_Logger
     */
    protected $logger;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $this->logger = Zend_Registry::get('logger')->cloneMe('plugin.necTm');
        $this->synchronizeNecTmCategories();
    }

    /**
     * Queries NEC TM for all categories that can be accessed with the system credentials in NEC TM.
     * The existing categories are saved in the translate5 DB. Categories that already exist in translate5 DB,
     * but do not exist any more in NEC TM, are removed from the DB and from all language resource associations.
     */
    protected function synchronizeNecTmCategories() {
        try {
            $sync = ZfExtended_Factory::get('editor_Plugins_NecTm_SyncCategories');
            /* @var $sync editor_Plugins_NecTm_SyncCategories */
            $sync->synchronize(false); //without mutex, since we call it explicitly via parameter
        } catch (Exception $e){
            $this->handleException($e);
        }
    }
    
    /**
     * Logs the occured exception
     * @param Exception $e
     */
    protected function handleException(Exception $e) {
        $this->logger->exception($e, [
            'level' => ZfExtended_Logger::LEVEL_WARN,
        ]);
        
        $code = 'E1181';
        $msg = 'NecTm Plug-In: Synchronize of NEC-TM-Tags with our categories failed.';
        
        // in case of an exception we log it, but proceed with translate5
        $this->logger->warn($code, $msg, [
            'message' => get_class($e).': '.$e->getMessage()
        ]);
    }
}
