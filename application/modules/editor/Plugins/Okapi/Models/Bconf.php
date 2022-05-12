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

    public editor_Plugins_Okapi_Bconf_File $file;
    private string $dir = '';
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
            $this->file = new editor_Plugins_Okapi_Bconf_File($this);
            $this->file->unpack($postFile['tmp_name']);
            $this->file->pack();
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
     * @param editor_Plugins_Okapi_Bconf_File $file
     */
    public function setFile(editor_Plugins_Okapi_Bconf_File $file): void {
        $this->file = $file;
    }

    /**
     * @return bool - true when system bconf was imported
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function importDefaultWhenNeeded(int $totalCount = -1): bool {
        if($totalCount === -1){
            $totalCount = $this->getTotalCount();
        }
        $t5ProvidedImportBconf = editor_Plugins_Okapi_Init::getBconfStaticDataDir() . editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT;
        $updateNeeded = false;
        $insertNeeded = $totalCount === 0;
        $bconf = null;
        $id = 0;
        if(!$insertNeeded){
            $s = $this->db->select();
            $versionSelect = $s->from($s->getTable(), ['id', 'versionIdx'])->where('name = ?', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);
            [$id, $systemVersion] = $versionSelect->limit(1)->query()->fetch(PDO::FETCH_NUM);
            $insertNeeded = !$id; // there are bconfs, but not the t5 provided one
            $updateNeeded = !$insertNeeded && $systemVersion < editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX;
        }
        if($updateNeeded){
            $bconf = new self();
            $bconf->load($id);
            $bconf->file->unpack($t5ProvidedImportBconf);
            $bconf->file->pack();
        } else if($insertNeeded){
            $bconf = new self(['tmp_name' => $t5ProvidedImportBconf, 'name' => 'Translate5-Standard.bconf']);
            if(!$this->db->fetchRow(['isDefault = 1'])){
                $bconf->setIsDefault(1);
            }
            $bconf->setDescription("The default .bconf used for file imports unless another one is configured");
        } else {
            return false;
        }
        $bconf->setVersionIdx(editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
        $bconf->save();
        return true;
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
     * @param null $customerId
     * @return int $defaultBconfId
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function getDefaultBconfId($customerId = null): int {
        $this->importDefaultWhenNeeded();

        $defaultBconfId = 0;
        if($customerId){
            $customerMeta = new editor_Models_Customer_Meta();
            try {
                $customerMeta->loadByCustomerId($customerId);
                $defaultBconfId = $customerMeta->getDefaultBconfId();
            } catch(ZfExtended_Models_Entity_NotFoundException){
            }
        }
        if(!$defaultBconfId){
            $this->loadRow('name = ? ', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);
            $defaultBconfId = $this->getId();
        }
        return $defaultBconfId;
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
        $descFile = $this->getFilePath(fileName: $this->file::DESCRIPTION_FILE);
        $content = json_decode(file_get_contents($descFile), true);

        $srxFileName = $content['refs'][$purpose] ?? '';
        !$srxFileName && throw new ZfExtended_Exception("Corrupt bconf record: Could not get '$purpose' from '$descFile'.");
        return $srxFileName;
    }

}
