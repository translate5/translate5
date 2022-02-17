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
 * Handle engines of PangeaMT.
 * TODO: DRY, see editor_Models_LanguageResources_SdlResources
 */
class editor_Plugins_PangeaMt_Models_Engines {
    
    /***
     * Get all available engines
     * 
     * @return array
     */
    public function getAllEngines(){
        $api = ZfExtended_Factory::get('editor_Plugins_PangeaMt_HttpApi');
        /* @var $api editor_Plugins_PangeaMt_HttpApi */
        
        $result=null;
        //load all available engines
        if($api->getEngines()){
            $result=$api->getResult();
        }
        
        $engines=array();
        
        //check if results are found
        if(empty($result) || count($result)<1){
            return $engines;
        }
        return $this->mergeEngineData($result,false);
    }
    
    /***
     * Merge engine data required for the frontend layout
     * @param array $engines
     * @return array[]
     */
    public function mergeEngineData($engines) {
        
        $config = Zend_Registry::get('config');
        
        $engineCharacterLimit=null;
        //set the character limit as languageResourceId as key an characterlimit as value so easy can be accesed
        if(isset($config->runtimeOptions->LanguageResources->searchCharacterLimit)){
            $charLimit=$config->runtimeOptions->LanguageResources->searchCharacterLimit->toArray();
            foreach ($charLimit as $limit){
                $limit = json_decode(json_encode($limit),true);
                foreach ($limit as $key=>$value) {
                    $engineCharacterLimit[$key]=$value;
                }
            }
        }
        
        //get the maximum allowed characters for the engine
        //always the lowest config will be valid
        $getCharacterLimit=function($numbers){
            return min($numbers);
        };
        
        $service = ZfExtended_Factory::get('editor_Plugins_PangeaMt_Service');
        /* @var $service editor_Plugins_PangeaMt_Service */
        
        $customer=ZfExtended_Factory::get('editor_Models_Customer_Customer');
        /* @var $customer editor_Models_Customer_Customer */
        $customerLimit=$customer->getMinCharactersByUser();
        //if the customer config is not defined, set the customer limit to max int, so it is compared against engine limit
        if($customerLimit < 1){
            $customerLimit=PHP_INT_MAX;
        }
        
        $result=[];
        foreach($engines as $engine){
            $id = $engine->id;
            $engineLimit = $engineCharacterLimit[$id] ?? PHP_INT_MAX;
            $data=array(
                'id'=> $id,
                'name' => $engine->descr,
                'source' =>  $engine->src,
                'target' =>  $engine->tgt,
                'engineId' => $engine->id,
                'domainCode' => $engine->domain,
                'characterLimit' => $getCharacterLimit([$customerLimit,$engineLimit]),
                'fileUpload'=> false,
                'serviceName'=> $service->getName()
            );
            $result[]=$data;
        }
        return $result;
    }
}