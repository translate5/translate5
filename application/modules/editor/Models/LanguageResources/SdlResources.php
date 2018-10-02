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
     * Get all available sdl cloud language resources for customers of loged user
     * @param boolean $addArrayId : if true(default true), the array key will be generated in format: 'mt'+autoincrement number       
     * @return array
     */
    public function getEngines($addArrayId=true){
        
        $model=ZfExtended_Factory::get('editor_Models_TmMt');
        /* @var $model editor_Models_TmMt */
        
        $service=ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_Service');
        /* @var $service editor_Services_SDLLanguageCloud_Service */
        $engines=$model->loadByUserCustomerAssocs($service->getName());
        
        //check if results are found
        if(empty($engines)){
            return $engines;
        }
        //load all languages (sdl api use iso6393 langage shortcuts)
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $lngs=$langModel->loadAllKeyValueCustom('id','iso6393');
        
        $result=[];
        foreach($engines as $engine){
            $data=array(
                'id'=>$engine['id'],
                'name' =>$engine['labelText'],
                'source' => $engine['sourceLangRfc5646'],
                'sourceIso' => $lngs[$engine['sourceLang']],
                'target' => $engine['targetLangRfc5646'],
                'targetIso' => $lngs[$engine['targetLang']],
                'domainCode' => $engine['fileName']
            );
            if($addArrayId){
                $result[$engine['id']]=$data;
            }else{
                $result[]=$data;
            }
        }
        return $result;
    }
}