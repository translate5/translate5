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

    const SYSTEM_BCONF_VERSION = 9;
    const SYSTEM_BCONF_NAME = 'Translate5-Standard';
    const SYSTEM_BCONF_IMPORTFILE = 'okapi_default_import.bconf';

    public editor_Plugins_Okapi_Bconf_File $file;

    private string $dir = ''; // for caching the results of expensive filesystem checks
    private bool $isNewRecord = false; //flag for newly created entities

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
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Plugins_Okapi_Exception
     */
    public function __construct(array $postFile = null, array $data = []) {
        parent::__construct();
        if($postFile){ // create new entity from file
            if(empty($data['name'])){
                $data['name'] = pathinfo($postFile['name'])['filename']; // strip '.bconf'
            }
            unset($data['id']); // auto generated
            if(!$data['versionIdx']){
                $data['versionIdx'] = self::SYSTEM_BCONF_VERSION;
            }
            $this->isNewRecord = true;
            $this->init($data);
            $this->save(); // Generates id needed for directory

            $this->getDataDirectory(); // Creates directory

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
     * @throws editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function importDefaultWhenNeeded(int $totalCount = -1): bool {
        $t5ProvidedImportBconf = editor_Plugins_Okapi_Init::getOkapiDataFilePath() . $this::SYSTEM_BCONF_IMPORTFILE;
        $updateNeeded = false;
        $insertNeeded = $totalCount === 0 || !$totalCount === -1 && $this->getTotalCount() === 0;
        if(!$insertNeeded){
            $s = $this->db->select();
            $versionSelect = $s->from($s->getTable(), ['id', 'versionIdx'])->where('name = ?', $this::SYSTEM_BCONF_NAME);
            [$id, $systemVersion] = $versionSelect->limit(1)->query()->fetch(PDO::FETCH_NUM);

            $insertNeeded = !$systemVersion; // there are bconfs, but not the t5 provided one
            $updateNeeded = !$insertNeeded && $systemVersion < $this::SYSTEM_BCONF_VERSION;
            if($updateNeeded){
                $bconf = new self();
                $bconf->load($id);
                $bconf->file->unpack($t5ProvidedImportBconf);
                $bconf->file->pack();
            }
        }
        if(!$insertNeeded && !$updateNeeded){
            return false;
        }
        if($insertNeeded){
            $bconf = new self(['tmp_name' => $t5ProvidedImportBconf, 'name' => 'Translate5-Standard.bconf']);
            $bconf->setIsDefault(1);
            $bconf->setDescription("The default .bconf used for file imports unless another one is configured");
        }
        $bconf->setVersionIdx($bconf::SYSTEM_BCONF_VERSION);

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
     * Returns the data directory of the given bconfId/loaded entity without trailing slash, creating it if neccessary
     * @param string $id If not given, retrieved from entity and request parameters
     * @param bool $check can be used to disable file system checks
     * @return string
     * @throws ZfExtended_UnprocessableEntity|editor_Plugins_Okapi_Exception|Zend_Exception
     */
    public function getDataDirectory(string $id = '', bool $check = true): string {
        !$id && $id = $this->getId();
        !$id && $id = (int)$_REQUEST['id'];
        !$id && throw new ZfExtended_UnprocessableEntity('E1025', ['errors' => [["No 'id' parameter given."]]]);
        if(str_ends_with($this->dir, $id)){
            return $this->dir; // return cached result
        }
        /** @var Zend_Config $config */
        $config = Zend_Registry::get('config');
        $okapiDataDir = $config->runtimeOptions->plugins->Okapi->dataDir;
        $check && $this->checkDirectory($okapiDataDir);

        $this->dir = $bconfDir = realpath($okapiDataDir) . DIRECTORY_SEPARATOR . $id; // cache result
        $check && $this->checkDirectory($bconfDir);
        return $bconfDir;
    }

    /**
     * Checks if a directory exists and is writable
     * @param string $dir The directory path to check
     * @throws editor_Plugins_Okapi_Exception
     */
    public function checkDirectory(string $dir) {
        $errorMsg = '';
        if(!is_dir($dir)){
            if(is_file($dir)){
                $errorMsg = "'$dir' is a file!";
            } else if(!$this->isNewRecord){
                $errorMsg = "Directory '$dir' is missing";
            } else if(!mkdir($dir, 0755, true)){ // new record
                $errorMsg = "Could not create directory '$dir'";
            }
        } else {
            $permissions = fileperms($dir);
            $rwx = 7;
            $user = 6; // number of bytes to shift
            if($permissions >> $user !== $rwx && !chmod($dir, $permissions | $rwx << $user)){
                $errorMsg = $dir;
            }
        }
        if($errorMsg){
            if($this->isNewRecord){ // new bconf or clone
                $this->delete();
            }
            throw new editor_Plugins_Okapi_Exception('E1057', ['okapiDataDir' => $errorMsg]);
        }
    }

    /**
     * @param string $id
     * @param string $fileName Appended to the bconf's data directory, defaults to the bconf file itself
     * @return string path The absolute path of the bconf
     * @throws Zend_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getFilePath(string $id = '', string $fileName = ''): string {
        $dataDirectory = $this->getDataDirectory($id);
        !$id && $id = $this->getId();
        !$fileName && ($fileName = 'bconf-' . $id . '.bconf');
        return $dataDirectory . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @param null $customerId
     * @return int $defaultBconfId
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception|ZfExtended_Models_Entity_NotFoundException|Zend_Exception
     */
    public function getDefaultBconfId($customerId = null): int {
        $this->importDefaultWhenNeeded();

        $defaultBconfId = 0;
        if($customerId){
            $customerMeta = new editor_Models_Customer_Meta();
            $customerMeta->loadByCustomerId($customerId);
            $defaultBconfId = $customerMeta->getDefaultBconfId();
        }
        if(!$defaultBconfId){
            $this->loadRow('isDefault = 1');
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
        $systemBconf_row = $this->db->fetchRow(['name = ?' => $this::SYSTEM_BCONF_NAME]);
        if($bconfId == $systemBconf_row['id'] && !$this->isNewRecord){
            throw new ZfExtended_NoAccessException();
        }
        chdir(Zend_Registry::get('config')->runtimeOptions->plugins->Okapi->dataDir);
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
    public function srxNameFromPipeline(string $purpose): string {
        $purpose .= 'SrxPath';
        $pipelineFile = $this->getFilePath(fileName: $this->file::PIPELINE_FILE);

        $pipeline = new editor_Utils_Dom();
        $xmlErrors = $pipeline->load($pipelineFile) ? $pipeline->getErrorMsg('', true) : '';
        $step = $pipeline->query('/*/step[@class="net.sf.okapi.steps.segmentation.SegmentationStep"]')->item(0);
        preg_match("/^$purpose=(.*)$/m", $step?->nodeValue ?? '', $matches);

        $srxFileName = $matches[1] ?? '';
        if(!$srxFileName){
            $xmlErrors .= "\nNo SegmentationStep with attribute $purpose in " . $this->file::PIPELINE_FILE . "on server";
        }

        $xmlErrors && throw new ZfExtended_UnprocessableEntity('E1026',
            ['errors' => [[$xmlErrors]]]
        );
        return $srxFileName;
    }

}
