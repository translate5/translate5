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
 * Okapi Bconf Entity Object
 *
 * @method integer getId()
 * @method void setId(int $id)
 * @method string getName()
 * @method void setName(string $name)
 * @method int getIsDefault()
 * @method setIsDefault(int $int)
 * @method string getDescription()
 * @method setDescription(string $string)
 * @method int getCustomerId()
 * @method setCustomerId(mixed $customerId)
 * @method int getVersionIdx()
 * @method setVersionIdx(int $versionIdx)
 */
class editor_Plugins_Okapi_Bconf_Entity extends ZfExtended_Models_Entity_Abstract {

    /**
     * @var string
     */
    const EXTENSION = 'bconf';

    /**
     * The general version of bconfs we support
     */
    const VERSION = 2;

    /**
     * The signature written int the bconf-files
     * @var string
     */
    const SIGNATURE = 'batchConf';

    /**
     * Momentary there is no support for plugins in our bconfs
     * @var int
     */
    const NUM_PLUGINS = 0;

    /**
     * @var string|null
     */
    private static ?string $userDataDir = null;

    public static function getUserDataDir(): string {
        if(!static::$userDataDir){
            try {
                $errorMsg = '';
                /** @var Zend_Config $config */
                $config = Zend_Registry::get('config');
                $userDataDir = $config->runtimeOptions->plugins->Okapi->dataDir;
                // if the directory does not exist, we create it
                if(!is_dir($userDataDir)){
                    @mkdir($userDataDir, 0777, true);
                }
                $errorMsg = self::checkDirectory($userDataDir);
                if(!$errorMsg && $userDataDir){
                    $userDataDir = realpath($userDataDir);
                }
            } catch(Exception $e){
                $errorMsg = $e->__toString();
            } finally {
                if($errorMsg || empty($userDataDir)){
                    throw new editor_Plugins_Okapi_Exception('E1057',
                        ['okapiDataDir' => $errorMsg . "\n(checking runtimeOptions.plugins.Okapi.dataDir)"]);
                } else {
                    self::$userDataDir = $userDataDir;
                }
            }
        }
        return self::$userDataDir;
    }

    /**
     * Checks if a directory exists and is writable
     * @param string $dir The directory path to check
     */
    public static function checkDirectory(string $dir): string {
        if(!is_dir($dir)){
            if(is_file($dir)){
                return "The directory is a file!";
            } else {
                return "The directory is missing!";
            }
        } else {
            $permissions = fileperms($dir);
            $rwx = 7;
            $user = 6; // number of bytes to shift
            if($permissions >> $user !== $rwx && !chmod($dir, $permissions | $rwx << $user)){
                return "The directory has wrong permissions";
            }
        }
        return '';
    }

    /**
     * @var editor_Plugins_Okapi_Bconf_File
     */
    private $file = NULL;

    /**
     * @var string
     */
    private string $dir = '';

    /**
     * @var bool
     */
    private bool $doDebug;

    /**
     * @var editor_Models_Customer_Customer
     */
    private ?editor_Models_Customer_Customer $customer = NULL;

    protected $dbInstanceClass = 'editor_Plugins_Okapi_Db_Bconf';
    protected $validatorInstanceClass = 'editor_Plugins_Okapi_Db_Validator_Bconf';

    public function __construct(){
        parent::__construct();
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfProcessing');
    }

    /**
     * @param string $tmpPath
     * @param string $name
     * @param string $description
     * @param int|null $customerId
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function import(string $tmpPath, string $name, string $description, int $customerId=NULL){

        // DEBUG
        if($this->doDebug){ error_log('Import BCONF '.$name.' for customer '.($customerId?$customerId:'NULL')); }

        $bconfData = [
            'name' => $name,
            'description' => $description,
            'customerId' => $customerId,
            'versionIdx' => editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX,
            'isDefault' => 0
        ];
        $this->init($bconfData, false);
        $this->save(); // Generates id needed for directory
        $dir = $this->getDataDirectory();
        if(self::checkDirectory($dir) && !mkdir($dir, 0755, true)){
            $this->delete();
            $errorMsg = "Could not create directory for bconf (in runtimeOptions.plugins.Okapi.dataDir)";
            throw new editor_Plugins_Okapi_Exception('E1057', ['okapiDataDir' => $errorMsg]);
        }
        $bconfFile = new editor_Plugins_Okapi_Bconf_File($this, true);
        // parses the
        $bconfFile->unpack($tmpPath);
        $bconfFile->pack();

        // final step: validate the bconf
        $validation = new editor_Plugins_Okapi_Bconf_Validation($this);
        if($validation->validate()){
            if(!$validation->wasTestable()){
                // we generate a warning when a bconf could not be validated properly (what rarely can happen)
                $logger = Zend_Registry::get('logger')->cloneMe('editor.okapi.bconf');
                $logger->warn(
                    'E1408',
                    'Okapi Plug-In: The bconf "{bconf}" to import is not valid ({details})',
                    ['bconf' => $this->getName(), 'bconfId' => $this->getId(), 'details' => $validation->getValidationError()]);
            }
        } else {
            $name = $this->getName();
            $this->delete();
            throw new editor_Plugins_Okapi_Exception('E1408', ['bconf' => $name, 'details' => $validation->getValidationError()]);
        }
    }

    /**
     * Validates the bconf. Returns NULL, if the bconf is valid, otherwise an error why it is invalid
     * @return string|null
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function validate() : ?string {
        $validation = new editor_Plugins_Okapi_Bconf_Validation($this);
        if($validation->validate()){
            return NULL;
        }
        return $validation->getValidationError();
    }

    /**
     * Retrieves if the bconf is the system default bconf
     * @return bool
     */
    public function isSystemDefault(): bool {
        return ($this->getName() === editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);
    }

    /**
     * Retrieves, if a bconf is outdated and needs to be recompiled
     * @return bool
     */
    public function isOutdated(): bool {
        return ($this->getVersionIdx() < editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
    }

    /**
     * Updates a bconf if the version-index is outdated with potentially changed default settings
     * @param bool $force: if set, it will be repacked even if not outdated
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function repackIfOutdated(bool $force=false) {
        if($this->isOutdated() || $force){
            // DEBUG
            if($this->doDebug){ error_log('Re-pack BCONF '.$this->getName().' to Version '.editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX); }

            $this->getFile()->pack();
            $this->setVersionIdx(editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
            $this->save();
        }
    }

    /**
     * Lazy accessor for our file wrapper
     * @return editor_Plugins_Okapi_Bconf_File
     */
    public function getFile(): editor_Plugins_Okapi_Bconf_File {
        // use cached file only with identical ID
        if($this->file === NULL || $this->file->getBconfId() != $this->getId()){
            $this->file = new editor_Plugins_Okapi_Bconf_File($this);
        }
        return $this->file;
    }

    /**
     * @return int
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function importDefaultWhenNeeded() : int {
        $sysBconfRow = $this->db->fetchRow($this->db->select()->where('name = ?', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME));
        // when the system default bconf does not exist we have to generate it
        if($sysBconfRow == NULL){

            $sysBconfPath = editor_Plugins_Okapi_Init::getDataDir() . editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT;
            $sysBconfName = editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME . '.' .  static::EXTENSION;
            $sysBconfDescription = 'The default .bconf used for file imports unless another one is configured';
            $sysBconf = new editor_Plugins_Okapi_Bconf_Entity();
            $sysBconf->import($sysBconfPath, $sysBconfName, $sysBconfDescription, NULL);
            $sysBconf->setVersionIdx(editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
            if(!$this->db->fetchRow(['isDefault = 1'])){
                $sysBconf->setIsDefault(1);
            }
            $sysBconf->save();
            // DEBUG
            if($this->doDebug){ error_log('BCONF: Imported sys default bconf '.$sysBconfName.' when needed'); }

            return $sysBconf->getId();
        }
        return $sysBconfRow->id;
    }

    /**
     * Retrieves our data-directory
     * @return string
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getDataDirectory() : string {
        return self::getUserDataDir().'/'.$this->getId();
    }

    /**
     * Creates the path for the bconf itself which follllows a fixed naming-schema
     * @return string
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getPath() : string {
        return $this->createPath('bconf-'.$this->getId().'.'.static::EXTENSION);
    }

    /**
     * Creates the path for a file inside the bconf's data-directory
     * @param string $fileName
     * @return string
     * @throws editor_Plugins_Okapi_Exception
     */
    public function createPath(string $fileName) : string {
        return $this->getDataDirectory().'/'.$fileName;
    }

    /**
     * Retrieves the download filename with extension
     * @return string
     */
    public function getDownloadFilename() : string {
        $filename = editor_Utils::secureFilename($this->getName(), false);
        $filename = ($filename == '') ? 'bconf-'.$this->getId() : $filename;
        return $filename.'.'.static::EXTENSION;
    }

    /**
     * @param int $customerId
     * @return int $defaultBconfId
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function getDefaultBconfId($customerId = null): int {
        // if customer given, try to load customer-specific default bconf
        if($customerId){
            $customerMeta = new editor_Models_Customer_Meta();
            try {
                $customerMeta->loadByCustomerId($customerId);
                // return the customers default only, if it is set!
                // API-based import's may have a customer set that not neccessarily must have a default-bconf
                if(!empty($customerMeta->getDefaultBconfId())){
                    return $customerMeta->getDefaultBconfId();
                }
            } catch(ZfExtended_Models_Entity_NotFoundException){
            }
        }
        try {
            $this->loadRow('isDefault = ? ', 1);
            return $this->getId();
        } catch(ZfExtended_Models_Entity_NotFoundException){
        }
        // try to load system default bconf
        try {
            $this->loadRow('name = ? ', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);
            return $this->getId();
        } catch(ZfExtended_Models_Entity_NotFoundException){
        }
        // if not found, generate it and return it's id
        return $this->importDefaultWhenNeeded();
    }

    /**
     * Reads the name of one contained srx file from the pipeline
     * @param string $purpose Which srx name to extract, one of 'source' or 'target'
     * @return string
     * @throws Zend_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getSrxNameFor(string $purpose): string {
        $purpose .= 'SrxPath';
        $descFile = $this->createPath(editor_Plugins_Okapi_Bconf_File::DESCRIPTION_FILE);
        $content = json_decode(file_get_contents($descFile), true);

        $srxFileName = $content['refs'][$purpose] ?? '';
        !$srxFileName && throw new ZfExtended_Exception("Corrupt bconf record: Could not get '$purpose' from '$descFile'.");
        return $srxFileName;
    }

    /**
     * Retrieves the SRX as file-object, either "source" or "target"
     * @param string $purpose
     * @return editor_Plugins_Okapi_Bconf_Srx
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getSrx(string $purpose) : editor_Plugins_Okapi_Bconf_Srx {
        $path = $this->createPath($this->getSrxNameFor($purpose));
        return new editor_Plugins_Okapi_Bconf_Srx($path);
    }

    /**
     * Retrieves the bound customers name (cached)
     * @return string|null
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getCustomerName() : ?string {
        if(empty($this->getCustomerId())){
            return NULL;
        }
        if($this->customer == NULL || $this->customer->getId() != $this->getCustomerId()){
            $this->customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
            $this->customer->load($this->getCustomerId());
        }
        return $this->customer->getName();
    }

    /**
     * Retrieves the server path to the extension mapping to a bconf
     * @return string
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getExtensionMappingPath() : string {
        return $this->createPath(editor_Plugins_Okapi_Bconf_ExtensionMapping::FILE);
    }

    /**
     * Retrieves the extension-mapping object for this bconf
     * @return editor_Plugins_Okapi_Bconf_ExtensionMapping
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getExtensionMapping() : editor_Plugins_Okapi_Bconf_ExtensionMapping {
        return new editor_Plugins_Okapi_Bconf_ExtensionMapping($this);
    }

    /**
     * All the file-extensions we support
     * @return array
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getSupportedExtensions() : array {
        return $this->getExtensionMapping()->getAllExtensions();
    }

    /**
     * Checks whether the given extension is supported
     * @param string $extension
     * @return bool
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function hasSupportFor(string $extension) : bool {
        return $this->getExtensionMapping()->hasExtension($extension);
    }

    /**
     * Returns the custom (database based) filters for the bconf
     * @return array
     */
    public function getCustomFilterData(){
        $filters = new editor_Plugins_Okapi_Bconf_Filter_Entity();
        return $filters->getRowsByBconfId($this->getId());
    }

    /**
     * Returns the custom (database based) filters for the frontend
     * @return array
     */
    public function getCustomFilterGridData(){
        $filters = new editor_Plugins_Okapi_Bconf_Filter_Entity();
        return $filters->getGridRowsByBconfId($this->getId());
    }

    /**
     * Retrieves the provider-prefix to be used for custom filters ( $okapiType@$provider-$specialization )
     * Keep in mind that this string may not neccessarily be unique for a customer
     * @return string
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getCustomFilterProviderId() : string {
        if(empty($this->getCustomerId())){
            $config = Zend_Registry::get('config');
            return editor_Utils::filenameFromUserText($config->runtimeOptions->server->name, false);
        }
        $name = editor_Utils::filenameFromUserText($this->getCustomerName(), false);
        if(empty($name)){
            $name = 'customer'.$this->getCustomerId();
        }
        if(strlen($name) > 50){
            return substr($name, 0, 50);
        }
        return $name;
    }

    /**
     * Adds a Bconf Filter to the DB
     * @param string $okapiType
     * @param string $okapiId
     * @param string $name
     * @param string $description
     * @param array $extensions
     * @param string $hash
     * @param string|null $mimeType
     * @return editor_Plugins_Okapi_Bconf_Filter_Entity
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_BadMethodCallException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function addCustomFilterEntry(string $okapiType, string $okapiId, string $name, string $description, array $extensions, string $hash, string $mimeType=NULL) : editor_Plugins_Okapi_Bconf_Filter_Entity {
        if(empty($extensions)){
            throw new ZfExtended_BadMethodCallException('A Okapi Bconf custom filter can not be added without related file extensions');
        }
        if($mimeType === NULL){
            $mimeType = editor_Plugins_Okapi_Bconf_Filter_Okapi::findMimeType($okapiType);
        }
        $filterEntity = new editor_Plugins_Okapi_Bconf_Filter_Entity();
        $filterEntity->setBconfId($this->getId());
        $filterEntity->setOkapiType($okapiType);
        $filterEntity->setOkapiId($okapiId);
        $filterEntity->setMimeType($mimeType);
        $filterEntity->setName($name);
        $filterEntity->setDescription($description);
        $filterEntity->setFileExtensions($extensions);
        $filterEntity->setHash($hash);
        $filterEntity->save();

        // DEBUG
        if($this->doDebug){ error_log('BCONF: Added custom filter entry "'.$name.'" '.$okapiType.'@'.$okapiId.' to bconf '.$this->getId()); }

        return $filterEntity;
    }

    /**
     * Finds a custom bconf filter entity
     * @param string $okapiType
     * @param string $okapiId
     * @return editor_Plugins_Okapi_Bconf_Filter_Entity|null
     */
    public function findCustomFilterEntry(string $okapiType, string $okapiId) : ?editor_Plugins_Okapi_Bconf_Filter_Entity {
        try {
            $filterEntity = new editor_Plugins_Okapi_Bconf_Filter_Entity();
            $filterEntity->loadByTypeAndIdForBconf($okapiType, $okapiId, $this->getId());
            return $filterEntity;
        } catch(ZfExtended_Models_Entity_NotFoundException $e){
            return NULL;
        }
    }

    /**
     * Retrieves a list with the custom filter identifiers that are related
     * @return string[]
     */
    public function findCustomFilterIdentifiers() : array {
        $filterEntity = new editor_Plugins_Okapi_Bconf_Filter_Entity();
        return $filterEntity->getIdentifiersForBconf($this->getId());
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function delete(){
        if($this->isSystemDefault()){
            throw new ZfExtended_NoAccessException('You can not delete the system default bconf.');
        }
        try {
            // first try to delete record to avoid records without files in any case
            $id = $this->row->id;
            $this->row->delete();
            $this->removeFiles($id);
        } catch (Zend_Db_Statement_Exception $e) {
            $this->handleIntegrityConstraintException($e);
        }
        // DEBUG
        if($this->doDebug){ error_log('Deleted BCONF '.$id); }
    }

    /**
     * Removes the related files of an bconf entity
     * Must only be called via ::delete
     * @param int $id
     * @throws editor_Plugins_Okapi_Exception
     */
    private function removeFiles(int $id){
        $dir = self::getUserDataDir().DIRECTORY_SEPARATOR.strval($id);
        if(is_dir($dir)){ // just to be safe
            $this->dir = ''; // remove cached valid dir
            /** @var ZfExtended_Controller_Helper_Recursivedircleaner $cleaner */
            $cleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('Recursivedircleaner');
            $cleaner->delete($dir);
        }
    }
}