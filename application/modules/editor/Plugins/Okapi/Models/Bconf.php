<?php
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Okapi Bconf Entity Object
 *
 * @method integer getId()
 * @method void setId(int $id)
 * @method void setName(string $name)
 * @method string getName()
 * @method setDefault(int $int)
 * @method setDescription(string $string)
 * @method setCustomer_id(mixed $customer_id)
 */
class editor_Plugins_Okapi_Models_Bconf extends ZfExtended_Models_Entity_Abstract {
    
    public editor_Plugins_Okapi_Bconf_File $file;

    /**
     * @param editor_Plugins_Okapi_Bconf_File $file
     */
    public function setFile(editor_Plugins_Okapi_Bconf_File $file): void {
        $this->file = $file;
    }

    protected $dbInstanceClass = 'editor_Plugins_Okapi_Models_Db_Bconf';
    protected $validatorInstanceClass = 'editor_Plugins_Okapi_Models_Validator_Bconf';

    /**
     * Creates new Bconf_Model instance
     * @param ?array $postFile - see https://www.php.net/manual/features.file-upload.post-method.php
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function __construct(array $postFile = null)
    {
        parent::__construct();
        if($postFile){ // create new entity from file
            $this->setName($postFile['name']);
            $this->save(); // generates id for Bconf_File

            $this->file = new editor_Plugins_Okapi_Bconf_File($this);
            $this->file->unpack($postFile['tmp_name']);
            $this->file->pack();
        }
    }

    /**
     * @return bool - true when system bconf was imported
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function importDefaultWhenNeeded(bool $skipCheck = false): bool
    {
        if ($skipCheck || $this->getTotalCount() === 0) {
            $defaultImportBconf = editor_Plugins_Okapi_Init::getOkapiDataFilePath() . 'okapi_default_import.bconf';
            $bconf = new editor_Plugins_Okapi_Models_Bconf(['tmp_name'=>$defaultImportBconf, 'name'=>'Translate5-Standard.bconf']);
            $bconf->setDefault(1);
            $bconf->setDescription("The .bconf used for file imports unless another one is configured");
            $bconf->save();
            return true;
        }
        return false;
    }

    public function load($id): ?Zend_Db_Table_Row_Abstract {
        $ret = parent::load($id);
        $this->file = new editor_Plugins_Okapi_Bconf_File($this);
        return $ret;
    }

    /**
     * Returns the data directory of the given bconfId/loaded entity without trailing slash
     * @param string|null $id
     * @return string
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function getDataDirectory(?string $id = null) : string {
        $id = $id ?: $this->getId();
        /** @var Zend_Config $config */
        $config = Zend_Registry::get('config');
        $okapiBconfDir = "{$config->runtimeOptions->plugins->Okapi->dataDir}/$id/";
        if(!is_dir($okapiBconfDir) && !mkdir($okapiBconfDir, 0755, true)){
            // TODO OKAPI: define proper Event Code
            throw new editor_Plugins_Okapi_Exception('E9999', ['reason' => 'Could not create Okapi Bconf directory: "'.$okapiBconfDir.'".']);
        }
        $okapiBconfDir = new SplFileInfo(realpath($okapiBconfDir));
        if(!$okapiBconfDir->isDir()) {
            // TODO OKAPI: define proper Event Code
            throw new editor_Plugins_Okapi_Exception('E9999', ['reason' => 'Okapi Bconf directory does not exist: "'.$okapiBconfDir->getPathname().'".']);
        }
        if(!$okapiBconfDir->isWritable()) {
            // TODO OKAPI: define proper Event Code
            throw new editor_Plugins_Okapi_Exception('E9999', ['reason' => 'Okapi Bconf directory is not writeable: "'.$okapiBconfDir->getPathname().'".']);
        }
        return (string) $okapiBconfDir;
    }

    /**
     * @return string
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function getFilePath(): string
    {
        return $this->getDataDirectory() . '/export.bconf';
    }

    /**
     * @param null $customerId
     * @return int $defaultBconfId
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception|ZfExtended_Models_Entity_NotFoundException
     */
    public function getDefaultBconfId($customerId = null): int {
        $this->importDefaultWhenNeeded();

        $defaultBconfId = 0;
        if ($customerId) {
            $customerMeta = new editor_Models_Customer_Meta();
            $customerMeta->loadByCustomerId($customerId);
            $defaultBconfId = $customerMeta->getDefaultBconfId();
        }
        if (!$defaultBconfId) {
            $this->loadRow('default = 1');
            $defaultBconfId = $this->getId();
        }
        return $defaultBconfId;
    }

}
