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
 * OpenTM2 Service Base Class
 */
class editor_Services_OpenTM2_Service extends editor_Services_ServiceAbstract {
    
    const DEFAULT_COLOR = 'aaff7f';
    
    /**
     * URL to confluence-page
     * @var string
     */
    protected static $helpPage = "https://confluence.translate5.net/display/BUS/OpenTM2";
    
    /**
     * {@inheritDoc}
     * @see editor_Services_ServiceAbstract::isConfigured()
     */
    public function isConfigured() {
        if (!isset($this->config->runtimeOptions->LanguageResources->opentm2->server) || empty($this->config->runtimeOptions->LanguageResources->opentm2->server)) {
            return false;
        }
        //since tmprefix and showMultiple100PercentMatches have workable defaults (which evaulates to empty) there is no need to test them
        return true;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see editor_Services_ServiceAbstract::embedService()
     */
    protected function embedService() {
        $urls = $this->config->runtimeOptions->LanguageResources->opentm2->server;
        $this->addResourceForeachUrl($this->getName(), $urls->toArray());
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_ServiceAbstract::getName()
     */
    public function getName() {
        return "OpenTM2";
    }
}