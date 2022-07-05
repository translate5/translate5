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
 * @method string getHash() getHash()
 * @method void setHash() setHash(string $hash)
 */
class editor_Plugins_Okapi_Bconf_Filter_Entity extends ZfExtended_Models_Entity_Abstract {

    /**
     * @var string
     */
    const EXTENSION = 'fprm';
    /**
     * @var int
     */
    const MAX_IDENTIFIER_LENGTH = 128;

    /**
     * Generates the okapi-id for a new custom filter
     * @param editor_Plugins_Okapi_Bconf_Entity $bconf
     * @param string $name
     * @param string $okapiType
     * @return string
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public static function createOkapiId(editor_Plugins_Okapi_Bconf_Entity $bconf, string $name, string $okapiType) : string {
        $baseId = $bconf->getCustomFilterProviderId().'-'.editor_Utils::filenameFromUserText($name, false);
        if(strlen($baseId) > (self::MAX_IDENTIFIER_LENGTH - 2)){
            $baseId = substr($baseId, 0, (self::MAX_IDENTIFIER_LENGTH - 2));
        }
        $okapiId = $baseId;
        $dir = $bconf->getDataDirectory().'/';
        $count = 0;
        while(file_exists($dir.editor_Plugins_Okapi_Bconf_Filters::createIdentifier($okapiType, $okapiId).'.'.self::EXTENSION)){
            $count++;
            $okapiId = $baseId.'-'.$count;
        }
        return $okapiId;
    }

    protected $dbInstanceClass = 'editor_Plugins_Okapi_Db_BconfFilter';
    protected $validatorInstanceClass = 'editor_Plugins_Okapi_Db_Validator_BconfFilter';

    /**
     * @var editor_Plugins_Okapi_Bconf_Entity|null
     */
    private ?editor_Plugins_Okapi_Bconf_Entity $bconf = NULL;


    /**
     * @return editor_Plugins_Okapi_Bconf_Entity
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getRelatedBconf() : editor_Plugins_Okapi_Bconf_Entity {
        if($this->bconf === NULL){
            $this->bconf = new editor_Plugins_Okapi_Bconf_Entity();
            $this->bconf->load($this->getBconfId());
        }
        return $this->bconf;
    }

    /**
     * Retrieves the full filename of the related fprm file
     * @return string
     */
    public function getFile() : string {
        return editor_Plugins_Okapi_Bconf_Filters::createIdentifier($this->getOkapiType(), $this->getOkapiId()).'.'.self::EXTENSION;
    }

    /**
     * Retrieves the server-path to our related fprm
     * @return string
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getPath() : string {
        return $this->getRelatedBconf()->createPath($this->getFile());
    }

    /**
     * @return editor_Plugins_Okapi_Bconf_Filter_Fprm
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getFprm() : editor_Plugins_Okapi_Bconf_Filter_Fprm {
        return new editor_Plugins_Okapi_Bconf_Filter_Fprm($this->getPath());
    }

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
    public function getRowsByBconfId(int $bconfId) : array {
         $select = $this->db->select()
              ->where('bconfId = ?', $bconfId);
         return $this->loadFilterdCustom($select);
    }

    /**
     * Retrieves the data for the frontend grid
     * @param int $bconfId
     * @return array
     */
    public function getGridRowsByBconfId(int $bconfId) : array {
        $rows = [];
        foreach($this->getRowsByBconfId($bconfId) as $row){
            unset($row['extensions']);
            unset($row['hash']);
            $row['editable'] = true;
            $row['clonable'] = true;
            $row['isCustom'] = true;
            $row['guiClass'] = editor_Plugins_Okapi_Bconf_Filters::getGuiName($row['okapiId'], true);
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Retrieves all customized filters having one of the passed extensions
     * @param array $extensions
     * @return editor_Plugins_Okapi_Bconf_Filter_Entity[]
     */
    public function getAllByExtensions(array $extensions) : array {
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
        $entities = [];
        foreach($this->loadFilterdCustom($select) as $data){
            $entity = new editor_Plugins_Okapi_Bconf_Filter_Entity();
            $entity->init($data, true);
            $entities[] = $entity;
        }
        return $entities;
    }

    /**
     * @param string $okapiType
     * @param string $hash
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByTypeAndHash(string $okapiType, string $hash) {
        $select = $this->db->select()
            ->where('okapiType = ?', $okapiType)
            ->where('hash = ?', $hash);
        $this->loadRowBySelect($select);
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

    /**
     * Retrieves the highest auto-increment id
     * @return int
     * @throws Zend_Db_Table_Exception
     */
    public function getHighestId() : int {
        return intval($this->db->getAdapter()->fetchOne('SELECT MAX(id) FROM '.$this->db->info(Zend_Db_Table_Abstract::NAME)));
    }
}