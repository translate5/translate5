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
 */
class editor_Plugins_PangeaMt_Service extends editor_Services_ServiceAbstract {
    
    const DEFAULT_COLOR = '#f5741d';
    
    /**
     * URL to confluence-page
     * @var string
     */
    protected static $helpPage = "https://confluence.translate5.net/display/CON/PangeaMT";
    
    protected $resourceClass = 'editor_Plugins_PangeaMt_Resource';
    
    /**
     * {@inheritDoc}
     * @see editor_Services_ServiceAbstract::isConfigured()
     */
    public function isConfigured() {
        if (!isset($this->config->runtimeOptions->plugins->PangeaMt->server)) {
            return false;
        }
        if (!isset($this->config->runtimeOptions->plugins->PangeaMt->apikey)) {
            return false;
        }
        if (!isset($this->config->runtimeOptions->plugins->PangeaMt->matchrate)) {
            return false;
        }
        // server and apikey must be not just configured, but configured correctly!
        // getEngines() will already need them. If they are NOT set correctly,
        // PangeaMT must not be offered when adding LanguageResources.
        $urls = $this->config->runtimeOptions->plugins->PangeaMt->server->toArray() ?? null;
        if (empty($urls) || empty($urls[0])) {
            return false;
        }
        $apiKey = $this->config->runtimeOptions->plugins->PangeaMt->apikey ?? null ;
        if (empty($apiKey)) {
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
        $urls = $this->config->runtimeOptions->plugins->PangeaMt->server;
        $this->addResourceForeachUrl($this->getName(), $urls->toArray());
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_ServiceAbstract::getName()
     */
    public function getName() {
        return "PangeaMT";
    }
}