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
 * trait for handling SpecificData in tables
 * 
 */
trait editor_Models_Entity_SpecificDataTrait {
    
    /***
     * Set the specificData field. The given value will be json encoded.
     * @param string $value
     */
    public function setSpecificData($value){
        $this->__call('setSpecificData', array(
            json_encode($value)
        ));
    }
    
    /***
     * Get specificData field value. The returned value will be json decoded.
     * If $propertyName is provided, only the value for this field will be returned if exisit.
     * @param string $propertyName
     * @return mixed|NULL
     */
    public function getSpecificData($propertyName=null){
        $specificData=$this->__call('getSpecificData', array());
        
        if(empty($specificData)){
            return null;
        }
        //try to decode the data
        try {
            $specificData=json_decode($specificData);
            
            //return the property name value if exist
            if(isset($propertyName)){
                return $specificData->$propertyName ?? null;
            }
            return $specificData;
        } catch (Exception $e) {
            
        }
        return null;
    }
    
    /***
     * Add specific data by property name and value. The result will be encoded back to json
     * @param string $propertyName
     * @param mixed $value
     * @return boolean
     */
    public function addSpecificData($propertyName,$value) {
        $specificData=$this->getSpecificData();
        if(empty($specificData)){
            $this->setSpecificData(array($propertyName=>$value));
            return true;
        }
        //set the property name into the specific data
        $specificData->$propertyName=$value;
        $this->setSpecificData($specificData);
        return true;
    }

    /***
     * @param string $propertyName
     * @return void
     */
    public function removeSpecificData(string $propertyName): void
    {
        $specificData=$this->getSpecificData() ?? new stdClass();
        if(property_exists($specificData,$propertyName)){
            unset($specificData->$propertyName);
        }
        $this->setSpecificData($specificData);
    }
   
}
