<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Okapi Bconf Filter Entity Object
 *
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method integer getBconfId() getBconfId()
 * @method void setBconfId() setBconfId(int $bconfId)
 * @method string getOkapiType() getOkapiType()
 * @method void setOkapiType() setOkapiType(string $okapiType)
 * @method string getOkapiId() getOkapiId()
 * @method void setOkapiId() setOkapiId(string $okapiId)
 * @method string getMimeType() getMimeType()
 * @method void setMimeType() setMimeType(string $mimeType)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * @method string getDescription() getDescription()
 * @method void setDescription() setDescription(string $description)
 * @method string getExtensions() getExtensions()
 * @method void setExtensions() setExtensions(string $extensions)
 */
class editor_Plugins_Okapi_Models_BconfFilter extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Plugins_Okapi_Models_Db_BconfFilter';
    protected $validatorInstanceClass = 'editor_Plugins_Okapi_Models_Validator_BconfFilter';

    /**
     * @return array
     */
    public function getFileExtensions() : array {
        return explode(',', $this->getExtensions());
    }

    /**
     * @param string[] $extensions
     * @return array
     */
    public function setFileExtensions(array $extensions) {
        $this->setExtensions(implode(',', $extensions));
    }

    /**
     * find all customized filters for a bconf
     * @param int $bconfId
     * @return array
     */
    public function getByBconfId(int $bconfId) : array {
         $select = $this->db->select()
              ->where('bconfId = ?', $bconfId);
         return $this->loadFilterdCustom($select);
    }

    /**
     * Retrieves all customized filters having one of the passed extensions
     * @param array $extensions
     * @return array
     */
    public function getByExtensions(array $extensions) : array {
        if(count($extensions) === 0){
            return [];
        }
        $select = $this->db->select();
        foreach($extensions as $extension){
            $select
                ->orWhere('extensions = ?', $extension)
                ->orWhere('extensions LIKE ?', $extension.',%')
                ->orWhere('extensions LIKE ?', '%,'.$extension)
                ->orWhere('extensions LIKE ?', '%,'.$extension.',%');
        }
        return $this->loadFilterdCustom($select);
    }

    /**
     * Removes the passed Extension from the entry.
     * If there are no extensions left then, it will be removed (return-value: false) or saved (return-value: true)
     * @param string[] $extensionsToRemove
     * @return bool
     */
    public function removeExtensions(array $extensionsToRemove) : bool {
        if(count($extensionsToRemove) === 0){
            return true;
        }
        $newExtensions = [];
        foreach($this->getFileExtensions() as $extension){
            if(!in_array($extension, $extensionsToRemove)){
                $newExtensions[] = $extension;
            }
        }
        if(count($newExtensions) === 0){
            $this->delete();
            return false;
        } else {
            $this->setExtensions(implode(',', $newExtensions));
            $this->save();
            return true;
        }
    }
}
