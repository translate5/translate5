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
 * @method string getIsDefault()
 * @method setIsDefault(int $int)
 * @method string getDescription()
 * @method setDescription(string $string)
 * @method string getCustomerId()
 * @method setCustomerId(mixed $customerId)
 * @method string getVersionIdx()
 * @method setVersionIdx(int $versionIdx)
 */
class editor_Plugins_Okapi_Models_Bconf extends ZfExtended_Models_Entity_Abstract {

    const SYSTEM_BCONF_VERSION = 9;

    private static ?string $bconfRootDir = null;

    public static function getBconfRootDir(): string {
        if(!static::$bconfRootDir){
            try {
                $errorMsg = '';
                /** @var Zend_Config $config */
                $config = Zend_Registry::get('config');
                $bconfRootDir = $config->runtimeOptions->plugins->Okapi->dataDir;
                // if the directory does not exist, we create it
                if(!is_dir($bconfRootDir)){
                    @mkdir($bconfRootDir, 0777, true);
                }
                $errorMsg = self::checkDirectory($bconfRootDir);
                if(!$errorMsg && $bconfRootDir){
                    $bconfRootDir = realpath($bconfRootDir);
                }
            } catch(Exception $e){
                $errorMsg = $e->__toString();
            } finally {
                if($errorMsg || empty($bconfRootDir)){
                    throw new editor_Plugins_Okapi_Exception('E1057',
                        ['okapiDataDir' => $errorMsg . "\n(checking runtimeOptions.plugins.Okapi.dataDir)"]);
                } else {
                    self::$bconfRootDir = $bconfRootDir;
                }
            }
        }
        return self::$bconfRootDir;
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
    public $file = NULL;
    /**
     * @var string
     */
    private string $dir = '';
    /**
     * @var bool
     */
    private bool $isNewRecord = false; // flag for newly created entities

    protected $dbInstanceClass = 'editor_Plugins_Okapi_Models_Db_Bconf';
    protected $validatorInstanceClass = 'editor_Plugins_Okapi_Models_Validator_Bconf';

    /**
     * Creates new bconf record in DB and directory on disk
     * @param ?array $postFile - see https://www.php.net/manual/features.file-upload.post-method.php
     * @param array $data - data to initialize the record, usually the POST params
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Plugins_Okapi_Exception
     */
    public function __construct(array $postFile = null, array $data = []) {
        parent::__construct();
        if($postFile && self::getBconfRootDir()){ // create new entity from file

            // init data
            $bconfData = [];
            $bconfData['name'] = (array_key_exists('name', $data) && !empty($data['name'])) ? $data['name'] : pathinfo($postFile['name'])['filename'];
            $bconfData['description'] = (array_key_exists('description', $data) && !empty($data['description'])) ? $data['description'] : '';
            $bconfData['customerId'] = (array_key_exists('customerId', $data) && $data['customerId'] != NULL) ? intval($data['customerId']) : NULL;
            $bconfData['versionIdx'] = editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX;
            $bconfData['isDefault'] = 0;

            $this->isNewRecord = true;
            $this->init($bconfData, false);
            $this->save(); // Generates id needed for directory
            $dir = $this->getDir();
            if(self::checkDirectory($dir) && !mkdir($dir, 0755, true)){
                $this->delete();
                $errorMsg = "Could not create directory for bconf (in runtimeOptions.plugins.Okapi.dataDir)";
                throw new editor_Plugins_Okapi_Exception('E1057', ['okapiDataDir' => $errorMsg]);
            }
            $this->getFile()->unpack($postFile['tmp_name']);
            $this->getFile()->pack();
            $this->isNewRecord = false;
        }
    }

    /**
     * @return bool
     */
    public function isNewRecord(): bool {
        return $this->isNewRecord;
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
     */
    public function repackIfOutdated() {
        // TODO MILESTONE 2: We then need to re-pack every outdated bconf when accessing it -> remove sys-default check, change mechanic
        if($this->isSystemDefault() && $this->isOutdated()){
            $t5ProvidedImportBconf = editor_Plugins_Okapi_Init::getBconfStaticDataDir() . editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT;
            $this->getFile()->unpack($t5ProvidedImportBconf);
            $this->getFile()->pack();
            $this->setVersionIdx(editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
            $this->save();
        }
    }

    /**
     * Lazy accessor for our file wrapper
     * @return editor_Plugins_Okapi_Bconf_File
     */
    private function getFile(): editor_Plugins_Okapi_Bconf_File {
        if($this->file == NULL){
            $this->file = new editor_Plugins_Okapi_Bconf_File($this);
        }
        return $this->file;
    }

    /**
     * @return editor_Plugins_Okapi_Models_Bconf|null
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function importDefaultWhenNeeded() {
        $sysBconfRow = $this->db->fetchRow($this->db->select()->where('name = ?', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME));
        // when the system default bconf does not exist we have to generate it
        if($sysBconfRow == NULL){
            $t5ProvidedImportBconf = editor_Plugins_Okapi_Init::getBconfStaticDataDir() . editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT;
            $sysBconf = new self(['tmp_name' => $t5ProvidedImportBconf, 'name' => 'Translate5-Standard.bconf']);
            $sysBconf->setDescription("The default .bconf used for file imports unless another one is configured");
            $sysBconf->setVersionIdx(editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
            if(!$this->db->fetchRow(['isDefault = 1'])){
                $sysBconf->setIsDefault(1);
            }
            $sysBconf->save();
            return $sysBconf;
        }
        return NULL;
    }
    /**
     * @param string $id If given, gets the directory without loaded entity
     * @return string
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getDir(string $id = ''): string {
        return match ((bool)$id) {
            true => self::getBconfRootDir() . DIRECTORY_SEPARATOR . $id,
            false => $this->dir ?: $this->dir = self::getBconfRootDir() . DIRECTORY_SEPARATOR . $this->getId(),
        };
    }

    /**
     * @param string $id
     * @param string $fileName Appended to the bconf's data directory, defaults to the bconf file itself
     * @return string path The absolute path of the bconf
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getFilePath(string $id = '', string $fileName = ''): string {
        if(!$fileName){
            $fileName = 'bconf-' . ($id ?: $this->getId()) . '.bconf';
        }
        return $this->getDir($id) . DIRECTORY_SEPARATOR . $fileName;
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
                return $customerMeta->getDefaultBconfId();
            } catch(ZfExtended_Models_Entity_NotFoundException){
            }
        }
        // try to load system default bconf
        try {
            $this->loadRow('name = ? ', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);
            return $this->getId();
        } catch(ZfExtended_Models_Entity_NotFoundException){
        }
        // if not found, generate it
        return $this->importDefaultWhenNeeded()->getId();
    }
    /**
     * @param string $bconfId
     * @return void
     * @throws Zend_Exception
     * @throws ZfExtended_NoAccessException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function deleteDirectory(string $bconfId): void {
        $bconfId = (int)$bconfId;  // directory traversal mitigation
        $systemBconf_row = $this->db->fetchRow(['name = ?' => editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME]);
        if($bconfId == $systemBconf_row['id'] && !$this->isNewRecord){
            throw new ZfExtended_NoAccessException();
        }
        chdir(self::getBconfRootDir());
        if(is_dir($bconfId)){ // just to be safe
            $this->dir = ""; // remove cached valid dir
            /** @var ZfExtended_Controller_Helper_Recursivedircleaner $cleaner */
            $cleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('Recursivedircleaner');
            $cleaner->delete($bconfId);
        }
    }

    /**
     * Reads the name of one contained srx file from the pipeline
     * @param string $purpose Which srx name to extract, one of 'source' or 'target'
     * @return string
     * @throws Zend_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function srxNameFor(string $purpose): string {
        $purpose .= 'SrxPath';
        $descFile = $this->getFilePath(fileName: editor_Plugins_Okapi_Bconf_File::DESCRIPTION_FILE);
        $content = json_decode(file_get_contents($descFile), true);

        $srxFileName = $content['refs'][$purpose] ?? '';
        !$srxFileName && throw new ZfExtended_Exception("Corrupt bconf record: Could not get '$purpose' from '$descFile'.");
        return $srxFileName;
    }

}
