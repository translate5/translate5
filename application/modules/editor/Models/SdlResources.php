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
class editor_Models_SdlResources {
    
    /***
     * Get all available engines from the sdl language cloud.
     * Layout of the result array:
     * 
     *    customeEngineId -> internaly generated engine id
     *       name         -> engine name, engineType + from language + target language
     *       source       -> rfc5646 language code
     *       sourceIso    -> iso6393 language code
     *       target
     *       targetIso
     *       domainCode   -> unique engine code (only available for vertical engines)
     *       
     * @return array
     */
    public function getEngines(){
        $dummyTmmt=ZfExtended_Factory::get('editor_Models_TmMt');
        /* @var $dummyTmmt editor_Models_TmMt */
        
        $api=ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_HttpApi',[$dummyTmmt]);
        /* @var $api editor_Services_SDLLanguageCloud_HttpApi */
        
        
        $result=null;
        //load all available engines
        if($api->getEngines()){
            $result=$api->getResult();
        }
        
        $engines=array();
        
        if($result->totalCount<1){
            return $engines;
        }
            
        $engineCounter=1;
        foreach($result->translationEngines as $engine){
            $engines['mt'.$engineCounter]=array(
                'name' =>$engine->type.', ['.$engine->fromCulture.','.$engine->toCulture.']',
                'source' => $engine->fromCulture,
                'sourceIso' => $engine->from->code,
                'target' => $engine->toCulture,
                'targetIso' => $engine->to->code,
                'domainCode' => $engine->domainCode
            );
            $engineCounter++;
        }
        
        return $engines;
    }
}