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
    
    /**
     * URL to confluence-page
     * @var string
     */
    protected static $helpPage = "https://confluence.translate5.net/display/CON/NEC-TM";
    
    /**
     * What translate5 uses as origin for storing categories from NEC-TM (there: "tags").
     * @var string
     */
    const CATEGORY_ORIGIN = 'NEC';
    
    /**
     * {@inheritDoc}
     * @see editor_Services_ServiceAbstract::isConfigured()
     */
    public function isConfigured() {
        if (!isset($this->config->runtimeOptions->plugins->NecTm->server) || empty($this->config->runtimeOptions->plugins->NecTm->server)) {
            return false;
        }
        if (!isset($this->config->runtimeOptions->plugins->NecTm->credentials) || empty($this->config->runtimeOptions->plugins->NecTm->credentials)) {
            return false;
        }
        if (!isset($this->config->runtimeOptions->plugins->NecTm->topLevelCategoriesIds) || empty($this->config->runtimeOptions->plugins->NecTm->topLevelCategoriesIds)) {
            return false;
        }
        return true;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see editor_Services_ServiceAbstract::embedService()
     */
    protected function embedService() {
        $urls = $this->config->runtimeOptions->plugins->NecTm->server;
        $this->addResourceForeachUrl($this->getName(), $urls->toArray());
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_ServiceAbstract::getName()
     */
    public function getName() {
        return "NEC-TM";
    }
    
    /**
     * Returns the "top-level-categories" as configured.
     * @return array
     */
    public function getTopLevelCategoriesIds() {
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        return $config->runtimeOptions->plugins->NecTm->topLevelCategoriesIds->toArray();
    }
    
    /**
     * For NEC-TMs: "We should always use tags in the data uploaded,
     * if not, the data can't be searched by users (only by admin)",
     * TMs cannot be updated, ...
     * @param string $categories
     *
     */
    public function validateCategories($categories) {
        if (empty($categories) && empty($this->getTopLevelCategoriesIds())) {
            throw new editor_Plugins_NecTm_Exception('E1256');
        }
    }
}