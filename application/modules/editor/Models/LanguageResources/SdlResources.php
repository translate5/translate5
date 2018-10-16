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
 * 
 */
class editor_Models_LanguageResources_SdlResources {
    
    /***
     * Get all available engines
     * 
     * @return array
     */
    public function getAllEngines(){
        $dummy=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $dummy editor_Models_LanguageResources_LanguageResource */
        
        $api=ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_HttpApi',[$dummy]);
        /* @var $api editor_Services_SDLLanguageCloud_HttpApi */
        
        
        $result=null;
        //load all available engines
        if($api->getEngines()){
            $result=$api->getResult();
        }
        
        $engines=array();
        
        //check if results are found
        if(empty($result) || $result->totalCount<1){
            return $engines;
        }
        return $this->mergeEngineData($result->translationEngines,false);
    }
    
    /***
     * Merge engine data required for the frontend layout
     * @param array $engines
     * @param boolean $addArrayId : if true(default true), the array key will be the language resource id
     * @return array[]
     */
    public function mergeEngineData($engines,$addArrayId=true) {
        //load all languages (sdl api use iso6393 langage shortcuts)
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $lngs=$langModel->loadAllKeyValueCustom('id','iso6393');
        
        $config = Zend_Registry::get('config');
        $engineCharacterLimit=$config->runtimeOptions->LanguageResources->searchCharacterLimit->toArray();
        
        //get the maximum allowed characters for the engine
        //always the lowest config will be valid
        $getCharacterLimit=function($numbers){
            return min($numbers);
        };
        
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $customers=$userModel->getUserCustomersFromSession();
        
        $customerLimit=PHP_INT_MAX;
        
        //get the minimum search character allowed from all user assoc customers of a user
        foreach ($customers as $customer){
            $cm=ZfExtended_Factory::get('editor_Models_Customer');
            /* @var $cm editor_Models_Customer */
            $cm->load($customer);
            $customerLimit=min([$cm->getSearchCharacterLimit(),$customerLimit]);
        }
        
        $result=[];
        //NOTE: the id is generated, since the sdl language cloud does not provide one
        $engineCounter=1;
        foreach($engines as $engine){
            $id=is_array($engine) ? $engine['id'] :'mt'.$engineCounter;
            //get character limit per engine (if configured)
            $engineLimit=isset($engineCharacterLimit[$id]) ? $engineCharacterLimit[$id] : PHP_INT_MAX;
            
            $data=array(
                'id'=>is_array($engine) ? $engine['id'] :'mt'.$engineCounter,
                'name' =>is_array($engine) ? $engine['serviceName'] : $engine->type.', ['.$engine->fromCulture.','.$engine->toCulture.']',
                'source' => is_array($engine) ? $engine['sourceLangRfc5646'] : $engine->fromCulture,
                'sourceIso' => is_array($engine) ? $lngs[$engine['sourceLang']] : $engine->from->code,
                'target' => is_array($engine) ? $engine['targetLangRfc5646']: $engine->toCulture,
                'targetIso' => is_array($engine) ?$lngs[$engine['targetLang']]:$engine->to->code,
                'domainCode' => is_array($engine) ? $engine['fileName']:$engine->domainCode,
                'characterLimit' => $getCharacterLimit([$customerLimit,$engineLimit]),
            );
            
            if($addArrayId){
                $result[is_array($engine) ? $engine['id']:'mt'.$engineCounter]=$data;
            }else{
                $result[]=$data;
            }
            $engineCounter++;
        }
        return $result;
    }
}