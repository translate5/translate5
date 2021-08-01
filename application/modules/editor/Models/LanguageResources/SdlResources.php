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
 * TODO: change class-name (does not handle SDL-resources only, but engines of all types).
 */
class editor_Models_LanguageResources_SdlResources {
    
    /***
     * Get all available engines
     * 
     * @return array
     */
    public function getAllEngines(){
        $api = ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_HttpApi');
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
     * @param bool $addArrayId : if true(default true), the array key will be the language resource id
     * @return array[]
     */
    public function mergeEngineData($engines,$addArrayId=true) {
        //load all languages (sdl api use iso6393 langage shortcuts)
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $lngs=$langModel->loadAllKeyValueCustom('id','iso6393');
        
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
        
        //get the domain code from the specific data json
        $getDomainCode=function($specificData){
            try {
                $specificData=json_decode($specificData);
                return $specificData->domainCode ?? null;
            } catch (Exception $e) {
                return null;
            }
        };
        
        $getIsoLangs=function($languages) use($lngs){
          $ret=[];
          foreach ($languages as $l){
              $ret[] = $lngs[$l] ?? null;
          }
          return $ret;
        };
        
        
        $sdlService=ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_Service');
        /* @var $sdlService editor_Services_SDLLanguageCloud_Service */
        
        //check if the resource supports the file upload. (Current instruction: all MT resources.)
        $isFileUpload=function($engine){
            if(is_object($engine) && isset($engine->resourceType)){
                return $engine->resourceType == editor_Models_Segment_MatchRateType::TYPE_MT;
            }
            if(is_array($engine) && isset($engine['resourceType'])){
                return $engine['resourceType'] == editor_Models_Segment_MatchRateType::TYPE_MT;
            }
            
            return false;
        };
        
        $customer=ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customer editor_Models_Customer */
        $customerLimit=$customer->getMinCharactersByUser();
        
        //if the customer config is not defined, set the customer limit to max int, so it is compared against engine limit
        if($customerLimit < 1){
            $customerLimit=PHP_INT_MAX;
        }
        $result=[];
        //NOTE: the id is generated, since the sdl language cloud does not provide one
        $engineCounter=1;
        foreach($engines as $engine){
            $id=is_array($engine) ? $engine['id'] :'mt'.$engineCounter;
            //get character limit per engine (if configured)
            $engineLimit = $engineCharacterLimit[$id] ?? PHP_INT_MAX;
            
            //check if the engine support file uploads
            $fileUpload=$isFileUpload($engine);
            
            $data=array(
                'id'=>is_array($engine) ? $engine['id'] :'mt'.$engineCounter,
                'name' =>is_array($engine) ? $engine['name'] : $engine->type.', ['.$engine->fromCulture.','.$engine->toCulture.']',
                'source' => is_array($engine) ? $engine['sourceLangCode'] : $engine->fromCulture,
                'sourceIso' => is_array($engine) ? $getIsoLangs($engine['sourceLang']) : $engine->from->code,
                'target' => is_array($engine) ? $engine['targetLangCode']: $engine->toCulture,
                'targetIso' => is_array($engine) ? $getIsoLangs($engine['targetLang']):$engine->to->code,
                'domainCode' => is_array($engine) ? $getDomainCode($engine['specificData']):$engine->domainCode,
                'characterLimit' => $getCharacterLimit([$customerLimit,$engineLimit]),
                'fileUpload'=> $fileUpload,
                'serviceName'=>is_array($engine) ?  $engine['serviceName'] : $sdlService->getName()
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