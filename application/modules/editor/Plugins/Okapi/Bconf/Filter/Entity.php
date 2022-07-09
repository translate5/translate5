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
     * Creates new identifier and copies the referenced FPRM for a ne bconf-filter
     * @param int $bconfId
     * @param string $okapiType
     * @param string $okapiId
     * @param string $filterName
     * @return string
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public static function preProcessNewEntry(int $bconfId, string $okapiType, string $okapiId, string $filterName) : string {
        $bconf = new editor_Plugins_Okapi_Bconf_Entity();
        $bconf->load($bconfId);
        // we need the old identifier to copy the fprm
        $oldIdentifier = editor_Plugins_Okapi_Bconf_Filters::createIdentifier($okapiType, $okapiId);
        $newOkapiId = editor_Plugins_Okapi_Bconf_Filter_Entity::createOkapiId($bconf, $filterName, $okapiType);
        $newIdentifier = editor_Plugins_Okapi_Bconf_Filters::createIdentifier($okapiType, $newOkapiId);
        // retrieves the filepath of the fprm to copy
        $sourcePath = $bconf->createPath(self::createFileFromIdentifier($oldIdentifier));
        if(editor_Plugins_Okapi_Bconf_Filters::instance()->isEmbeddedOkapiDefaultFilter($okapiType, $okapiId)){
            $sourcePath = editor_Plugins_Okapi_Bconf_Filters::instance()->getOkapiDefaultFilterPathById($okapiId);
        } else if(editor_Plugins_Okapi_Bconf_Filters::instance()->isEmbeddedTranslate5Filter($okapiType, $okapiId)){
            $sourcePath = editor_Plugins_Okapi_Bconf_Filters::instance()->getTranslate5FilterPath($okapiType, $okapiId);
        } else if(!file_exists($sourcePath)){
            throw new editor_Plugins_Okapi_Exception('E1409', ['filterfile' => $sourcePath, 'details' => 'The file was not found in '.ltrim($bconf->createPath(''), '/')]);
        }
        copy($sourcePath, $bconf->createPath(self::createFileFromIdentifier($newIdentifier)));
        // DEBUG
        if(ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfProcessing')){ error_log('BCONF FILTER: created new identifier "'.$newIdentifier.'" and copied FPRM-file for bconf-filter "'.$filterName.'" for bconf '.$bconf->getId()); }

        return $newOkapiId;
    }

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

    /**
     * @param string $identifier
     * @return string
     */
    public static function createFileFromIdentifier(string $identifier) : string {
        return $identifier.'.'.self::EXTENSION;
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
        // use cached bconf only with identical ID
        if($this->bconf === NULL || $this->bconf->getId() != $this->getBconfId()){
            $this->bconf = new editor_Plugins_Okapi_Bconf_Entity();
            $this->bconf->load($this->getBconfId());
        }
        return $this->bconf;
    }

    /**
     * retrieves our identifier as it can be found in the extension mapping
     * @return string
     */
    public function getIdentifier() : string {
        return editor_Plugins_Okapi_Bconf_Filters::createIdentifier($this->getOkapiType(), $this->getOkapiId());
    }

    /**
     * Retrieves the full filename of the related fprm file
     * @return string
     */
    public function getFile() : string {
        return self::createFileFromIdentifier($this->getIdentifier());
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
     * Retrieves our related file-extensions
     * Note, that this fetches the related bconf from DB, reda the extensions-mapping & parses it
     * @return array
     */
    public function getMappedExtensions() : array {
        return $this->getRelatedBconf()->getExtensionMapping()->findExtensionsForFilter($this->getIdentifier());
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
            unset($row['hash']);
            // the identifier can act as a unique ID in the frontend, akapiType and okapiId are not unique
            $row['identifier'] = editor_Plugins_Okapi_Bconf_Filters::createIdentifier($row['okapiType'], $row['okapiId']);
            $row['editable'] = true;
            $row['clonable'] = true;
            $row['isCustom'] = true;
            $row['guiClass'] = editor_Plugins_Okapi_Bconf_Filters::getGuiClass($row['okapiType']);
            $rows[] = $row;
        }
        return $rows;
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
     * @param string $okapiType
     * @param string $okapiId
     * @param int $bconfId
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByTypeAndIdForBconf(string $okapiType, string $okapiId, int $bconfId) {
        $select = $this->db->select()
            ->where('bconfId = ?', $bconfId)
            ->where('okapiType = ?', $okapiType)
            ->where('okapiId = ?', $okapiId);
        $this->loadRowBySelect($select);
    }

    /**
     * Retrieves the highest auto-increment id
     * @return int
     * @throws Zend_Db_Table_Exception
     */
    public function getHighestId() : int {
        return intval($this->db->getAdapter()->fetchOne('SELECT MAX(id) FROM '.$this->db->info(Zend_Db_Table_Abstract::NAME)));
    }

    /**
     * Retrieves the custom filter identifiers for the given bconf
     * @param int $bconfId
     * @return string[]
     */
    public function getIdentifiersForBconf(int $bconfId) : array {
        $identifiers = [];
        foreach($this->getRowsByBconfId($bconfId) as $rowData){
            $identifiers[] = editor_Plugins_Okapi_Bconf_Filters::createIdentifier($rowData['okapiType'], $rowData['okapiId']);
        }
        return $identifiers;
    }
}