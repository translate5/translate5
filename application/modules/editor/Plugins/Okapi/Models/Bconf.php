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
 * @method setIsDefault(int $int)
 * @method setDescription(string $string)
 * @method setCustomerId(mixed $customerId)
 * @method setVersionIdx(int $versionIdx)
 */
class editor_Plugins_Okapi_Models_Bconf extends ZfExtended_Models_Entity_Abstract {

    const SYSTEM_BCONF_VERSION = 1;
    const SYSTEM_BCONF_NAME = 'Translate5-Standard';

    public editor_Plugins_Okapi_Bconf_File $file;
    public function setFile(editor_Plugins_Okapi_Bconf_File $file): void {
        $this->file = $file;
    }

    protected $dbInstanceClass = 'editor_Plugins_Okapi_Models_Db_Bconf';
    protected $validatorInstanceClass = 'editor_Plugins_Okapi_Models_Validator_Bconf';

    /**
     * Creates new bconf record in DB and directory on disk
     * @param ?array $postFile - see https://www.php.net/manual/features.file-upload.post-method.php
     * @param array $data - data to initialize the record, usually the POST params
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function __construct(array $postFile = null, array $data = [])
    {
        parent::__construct();
        if($postFile){ // create new entity from file
            if(empty($data['name'])){
                $data['name'] = pathinfo($postFile['name'])['filename']; // strip '.bconf'
            }
            unset($data['id']); // auto generated
            if(!$data['versionIdx']){
                $data['versionIdx'] = self::SYSTEM_BCONF_VERSION;
            }
            $this->init($data);
            $this->getDataDirectory(1); // Ensures directory is valid QUIRK: id 1 is an assumption. Creates empty dir if AUTO_INCREMENT != 1
            $this->save();

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
        // TODO: check wether system bconf has been updated and if so import updated one
        if ($skipCheck || $this->getTotalCount() === 0) {
            $defaultImportBconf = editor_Plugins_Okapi_Init::getOkapiDataFilePath() . 'okapi_default_import.bconf';
            $bconf = new editor_Plugins_Okapi_Models_Bconf(['tmp_name'=>$defaultImportBconf, 'name'=>'Translate5-Standard.bconf']);
            $bconf->setIsDefault(1);
            $bconf->setDescription("The default .bconf used for file imports unless another one is configured");
            $bconf->save();
            return true;
        }
        return false;
    }

    /**
     * @param $id
     * @return Zend_Db_Table_Row_Abstract|null
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function load($id) {
        $ret = parent::load($id);
        $this->file = new editor_Plugins_Okapi_Bconf_File($this);
        return $ret;
    }

    /**
     * Returns the data directory of the given bconfId/loaded entity without trailing slash, creating it if neccessary
     * @param string|null $id
     * @return string
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function getDataDirectory(?string $id = null) : string {
        !$id && $id = $this->getId();
        !$id && $id = (int) $_REQUEST['id'];
        !$id && throw new ZfExtended_UnprocessableEntity('E1025', ['errors' => [["No 'id' parameter given."]]]);

        /** @var Zend_Config $config */
        $config = Zend_Registry::get('config');
        $dataDir = $config->runtimeOptions->plugins->Okapi->dataDir;
        $this->checkDirectory($dataDir);

        $okapiBconfDir = realpath($dataDir) .'/'.$id;
        $this->checkDirectory($okapiBconfDir);
        return $okapiBconfDir;
    }

    /**
     * @param $dir
     * @throws editor_Plugins_Okapi_Exception
     */
    public function checkDirectory($dir){
        $errorMsg = '';
        if(!is_dir($dir)){
            if(is_file($dir)){
                $errorMsg = "'$dir' is actually a file!";
            } else if(!mkdir($dir, 0755, true)){
                $errorMsg = "Could not create directory '$dir'!";
            }
        } else if(!is_writable($dir)){
            $errorMsg = $dir;
        }
        $errorMsg && throw new editor_Plugins_Okapi_Exception('E1057', ['okapiDataDir' => $errorMsg]);
    }

    /**
     * @param $id
     * @return string path - the absolute path of the bconf
     * @throws Zend_Exception|editor_Plugins_Okapi_Exception
     */
    public function getFilePath(string $id = null): string {
        $dataDirectory = $this->getDataDirectory($id);
        !$id && $id = $this->getId();
        return $dataDirectory.DIRECTORY_SEPARATOR.'bconf-'.$id.'.bconf';

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
