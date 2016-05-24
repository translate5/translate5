<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * Moses Connector
 */
class editor_Plugins_TmMtIntegration_Services_Moses_Connector extends editor_Plugins_TmMtIntegration_Connector_Abstract {
    public function __construct(stdClass $config) {
        $this->name = "Moses MT - ".$config->url;
    }
    
    /**
     * returns a list with connector instances, one per resource
     * @return [editor_Plugins_TmMtIntegration_Connector_Moses]
     */
    public static function createForAllResources() {
        //FIXME let me come from config:
        $config = '[{
                "url": "http://www.translate5.net:8124"
        }]';
        $config = json_decode($config);
        $result = array();
        foreach($config as $one) {
            $result[] = ZfExtended_Factory::get(__CLASS__, array($one));
        }
        return $result;
    }
    
    /**
     * returns a list with connector instances, one per resource
     * @return [editor_Plugins_TmMtIntegration_Connector_Moses]
     */
    public static function createForResource(string $resourceId) {
        //FIXME load me come from config according to the resourceId
        $config = '[{
                "url": "http://www.translate5.net:8124"
        }]';
        $config = json_decode($config);
        return ZfExtended_Factory::get(__CLASS__, array($config[0]));
    }
    
    public function synchronizeTmList() {
        //for Moses do currently nothing
    }
    
    public function open(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt) {
        error_log("Opened Tmmt ".$tmmt->getName().' - '.$tmmt->getResourceName());
    }
    
    public function translate(string $toTranslate) {
        $rpc = new Zend_XmlRpc_Client("http://www.translate5.net:8124/RPC2");
        try {
            
            $rpc->call('translate', array('text' => 'es ist ein kleines haus'));
        }
        catch(Exception $e) {
            error_log($e);
        }
        //error_log("Translate ".$toTranslate);
        //error_log("Translated ".$toTranslate);
    }
}