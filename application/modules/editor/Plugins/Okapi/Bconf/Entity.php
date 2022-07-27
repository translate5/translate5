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
     * Cache for our extension mapping
     * @var editor_Plugins_Okapi_Bconf_ExtensionMapping
     */
    private ?editor_Plugins_Okapi_Bconf_ExtensionMapping $extensionMapping = NULL;

    /**
     * Cache for our pipeline
     * @var editor_Plugins_Okapi_Bconf_Pipeline
     */
    private ?editor_Plugins_Okapi_Bconf_Pipeline $pipeline = NULL;

    /**
     * Cache for our content/TOC
     * @var editor_Plugins_Okapi_Bconf_Content
     */
    private ?editor_Plugins_Okapi_Bconf_Content $content = NULL;

    /**
     * Cache for our related customer
     * @var editor_Models_Customer_Customer
     */
    private ?editor_Models_Customer_Customer $customer = NULL;

    /**
     * The isNew state is only set during the import: after the bconf is saved to DB but before all filebased operations/validations are finished, a bconf is regarded as "new"
     * When Exceptions occurs while packing/unpacking, new bconfs will be deleted from DB
     * @var bool
     */
    private bool $isNew = false;

    /**
     * @var bool
     */
    private bool $doDebug;

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
        // when exceptions occur during unpacking/packing this flag ensures, the entity is removed from DB
        $this->isNew = true;
        // unpacks the imported file & saves the parts to filesys/DB
        $this->unpack($tmpPath);
        // packs a bconf from it that can be used for okapi-projects from now on
        $this->pack();

        // final step: validate the bconf - if not the sys-default bconf
        if(!$this->isSystemDefault()){
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
        // after a successful unpack/pack, we're not new anymore
        $this->isNew = false;
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

            $this->pack(true);
            $this->setVersionIdx(editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
            $this->save();
        }
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
            $sysBconfName = editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME;
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
        return $this->createPath($this->getFile());
    }

    /**
     * Generates the file-name in our data-dir
     * @return string
     */
    public function getFile(): string {
        return 'bconf-'.$this->getId().'.'.static::EXTENSION;
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
     * @param string $field
     * @return string
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getSrxNameFor(string $field): string {
        if($field !== 'source' && $field !== 'target'){
            throw new ZfExtended_Mismatch('E2004', [ $field, 'field' ]);
        }
        return $this->getContent()->getSrxFile($field);
    }

    /**
     * Retrieves the SRX as file-object, either "source" or "target"
     * @param string $field: source|target
     * @return editor_Plugins_Okapi_Bconf_Segmentation_Srx
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getSrx(string $field) : editor_Plugins_Okapi_Bconf_Segmentation_Srx {
        if($field !== 'source' && $field !== 'target'){
            throw new ZfExtended_Mismatch('E2004', [ $field, 'field' ]);
        }
        $path = $this->createPath($this->getSrxNameFor($field));
        return new editor_Plugins_Okapi_Bconf_Segmentation_Srx($path);
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
     * Retrieves the server path to the extension-mapping file of a bconf
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
        if($this->extensionMapping == NULL || $this->extensionMapping->getBconfId() !== $this->getId()){
            $this->extensionMapping = new editor_Plugins_Okapi_Bconf_ExtensionMapping($this);
        }
        return $this->extensionMapping;
    }

    /**
     * Retrieves the server path to the pipeline-file of a bconf
     * @return string
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getPipelinePath() : string {
        return $this->createPath(editor_Plugins_Okapi_Bconf_Pipeline::FILE);
    }

    /**
     * Returns a pipline-object for our pipeline-file
     * @return editor_Plugins_Okapi_Bconf_Pipeline
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getPipeline() : editor_Plugins_Okapi_Bconf_Pipeline {
        if($this->pipeline == NULL || $this->pipeline->getBconfId() !== $this->getId()){
            $this->pipeline = new editor_Plugins_Okapi_Bconf_Pipeline($this->getPipelinePath(), NULL, $this->getId());
        }
        return $this->pipeline;
    }

    /**
     * Retrieves the server path to the content/TOC of a bconf
     * @return string
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getContentPath() : string {
        return $this->createPath(editor_Plugins_Okapi_Bconf_Content::FILE);
    }

    /**
     * Returns a content-object for our content-file
     * @return editor_Plugins_Okapi_Bconf_Content
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getContent() : editor_Plugins_Okapi_Bconf_Content {
        if($this->content == NULL || $this->content->getBconfId() !== $this->getId()){
            $this->content = new editor_Plugins_Okapi_Bconf_Content($this->getContentPath(), NULL, $this->getId());
        }
        return $this->content;
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
     * @param string $hash
     * @param string|null $mimeType
     * @return editor_Plugins_Okapi_Bconf_Filter_Entity
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function addCustomFilterEntry(string $okapiType, string $okapiId, string $name, string $description, string $hash, string $mimeType=NULL) : editor_Plugins_Okapi_Bconf_Filter_Entity {
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
     * Retrieves the extensions of our related custom filter entries
     * @return array
     */
    public function findCustomFilterExtensions() : array {
        $extensions = [];
        foreach($this->findCustomFilterIdentifiers() as $identifier){
            $extensions = array_merge($this->getExtensionMapping()->findExtensionsForFilter($identifier), $extensions);
        }
        $extensions = array_unique($extensions);
        sort($extensions);
        return $extensions;
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
        $dir = self::getUserDataDir().'/'.strval($id);
        if(is_dir($dir)){ // just to be safe
            /** @var ZfExtended_Controller_Helper_Recursivedircleaner $cleaner */
            $cleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('Recursivedircleaner');
            $cleaner->delete($dir);
        }
    }

    /**
     * Retrieves the list to feed the bconf's grid view
     * Adds the custom extensions to each row
     * @return array
     */
    public function getGridRows() : array {
        $data = [];
        foreach($this->loadAllEntities() as $bconfEntity){ /* @var editor_Plugins_Okapi_Bconf_Entity $bconfEntity */
            $bconfData = $bconfEntity->toArray();
            $bconfData['customExtensions'] = $bconfEntity->findCustomFilterExtensions();
            $data[] = $bconfData;
        }
        return $data;
    }

    /**
     * Disassembles an uploaded bconf into it's parts & flushes the parts into the file-system & DB
     * @param string $pathToParse
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function unpack(string $pathToParse): void {
        try {
            $unpacker = new editor_Plugins_Okapi_Bconf_Unpacker($this);
            $unpacker->process($pathToParse);
        } catch(editor_Plugins_Okapi_Bconf_InvalidException $e){
            // in case of a editor_Plugins_Okapi_Bconf_InvalidException, the exception came from the Unpacker
            $name = $this->getName();
            error_log('UNPACK EXCEPTION for bconf "'.$name.'": '.$e->getMessage());
            $this->invalidateNew();
            throw new editor_Plugins_Okapi_Exception('E1415', ['bconf' => $name, 'details' => $e->getMessage()]);
        } catch(Exception $e){
            // if an other exception than the explicitly thrown via invalidate occur we do a passthrough to be able to identify the origin
            error_log('UNKNOWN UNPACK EXCEPTION: '.$e->__toString());
            $this->invalidateNew();
            throw $e;
        }
    }

    /**
     * Packs a bconf out of it parts (filters, srx, ...) to an assembled bconf
     * @param bool $isOutdatedRepack
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function pack(bool $isOutdatedRepack=false): void {
        try {
            $packer = new editor_Plugins_Okapi_Bconf_Packer($this);
            $packer->process($isOutdatedRepack);
        } catch(editor_Plugins_Okapi_Bconf_InvalidException $e){
            // in case of a editor_Plugins_Okapi_Bconf_InvalidException, the exception came from the packer
            $name = $this->getName();
            error_log('PACK EXCEPTION for bconf "'.$name.'": '.$e->getMessage());
            $this->invalidateNew();
            throw new editor_Plugins_Okapi_Exception('E1416', ['bconf' => $name, 'details' => $e->getMessage()]);
        } catch(Exception $e){
            // if an other exception than the explicitly thrown via invalidate occur we do a passthrough to be able to identify the origin
            error_log('UNKNOWN PACK EXCEPTION: '.$e->__toString());
            $this->invalidateNew();
            throw $e;
        }
    }

    /**
     * Handles deleting new records when an exception ocurred in the import
     */
    protected function  invalidateNew() : void {
        if($this->isNew){
            try {
                $this->delete();
            } catch(Exception $e){
                error_log('PROBLEMS DELETING BCONF: '.strval($e));
            }
            $this->isNew = false;
        }
    }

    /**
     * Invalidates all cached dependant objects
     */
    public function invalidateCaches(){
        $this->pipeline = NULL;
        $this->content = NULL;
        $this->extensionMapping = NULL;
        $this->customer = NULL;
    }
}