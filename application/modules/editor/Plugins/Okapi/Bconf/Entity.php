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

use MittagQI\ZfExtended\MismatchException;

/**
 * Okapi Bconf Entity Object
 *
 * A OKAPI Batch Configuration aka "BCONF" is a Bitstream-based file that bundles several components:
 * 2 SRX files (source & target, may be equal), Pipeline, FPRM files (0-n) and the Extension-Mapping
 * Generally, a BCONF is represented by it's parts in the Filesystem and a corresponding database-entry
 * The filesystem-parts are stored in a configurable base-directory (usually /data/editorOkapiBconf/) in a folder with the database-id as name
 * In this folder the parts are stored in another file "content.json", which is an inventory of the parts and contains the steps found in the pipeline
 * The packing/unpacking of the parts is implemented in the Packer/Unpacker class
 * When a bconf is packed, the embedded FPRM and SRX components are updated to the current state/revision of the git-based files.
 * The Revision is hold in editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX, every time the revision is increased in the code, all existing bconfs will be repacked with updated FPRMs/SRXs
 * All File-based parts of a BCONF generally have a corresponding clas, that is able to validate the file
 *
 * see editor_Plugins_Okapi_Bconf_Filters, editor_Plugins_Okapi_Bconf_ExtensionMapping, editor_Plugins_Okapi_Bconf_Filter_Fprm and editor_Plugins_Okapi_Bconf_Segmentation for more documentation
 *
 * @method string getId()
 * @method void setId(int $id)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getIsDefault()
 * @method void setIsDefault(int $int)
 * @method string getDescription()
 * @method void setDescription(string $string)
 * @method string getCustomerId()
 * @method void setCustomerId(mixed $customerId)
 * @method string getVersionIdx()
 * @method void setVersionIdx(int $versionIdx)
 */
class editor_Plugins_Okapi_Bconf_Entity extends ZfExtended_Models_Entity_Abstract
{
    /**
     * @var string
     */
    public const EXTENSION = 'bconf';

    /**
     * The general version of bconfs we support
     */
    public const VERSION = 2;

    /**
     * The signature written int the bconf-files
     * @var string
     */
    public const SIGNATURE = 'batchConf';

    /**
     * Momentary there is no support for plugins in our bconfs
     * @var int
     */
    public const NUM_PLUGINS = 0;

    private static ?string $userDataDir = null;

    public static function getUserDataDir(): string
    {
        if (! static::$userDataDir) {
            try {
                $errorMsg = '';
                /** @var Zend_Config $config */
                $config = Zend_Registry::get('config');
                $userDataDir = $config->runtimeOptions->plugins->Okapi->dataDir;
                // if the directory does not exist, we create it
                if (! is_dir($userDataDir)) {
                    @mkdir($userDataDir, 0777, true);
                }
                $errorMsg = self::checkDirectory($userDataDir);
                if (! $errorMsg && $userDataDir) {
                    $userDataDir = realpath($userDataDir);
                }
            } catch (Exception $e) {
                $errorMsg = $e->__toString();
            } finally {
                if ($errorMsg || empty($userDataDir)) {
                    $okapiDataDir = empty($userDataDir) ?
                        'runtimeOptions.plugins.Okapi.dataDir NOT CONFIGURED'
                        : ($errorMsg ? $userDataDir . ' (' . $errorMsg . ')' : $userDataDir);

                    throw new editor_Plugins_Okapi_Exception('E1057', [
                        'okapiDataDir' => $okapiDataDir,
                    ]);
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
    public static function checkDirectory(string $dir): string
    {
        if (! is_dir($dir)) {
            if (is_file($dir)) {
                return "The directory is a file!";
            } else {
                return "The directory is missing!";
            }
        } elseif (! is_writable($dir)) {
            return "The directory is not writable!";
        }

        return '';
    }

    /**
     * Loads the system default bconf
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public static function getSystemDefaultBconf(): editor_Plugins_Okapi_Bconf_Entity
    {
        $bconf = new editor_Plugins_Okapi_Bconf_Entity();

        try {
            $bconf->loadRow('name = ?', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);

            return $bconf;
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            return $bconf->importSystemDefault();
        }
    }

    /**
     * Cache for our extension mapping
     */
    private ?editor_Plugins_Okapi_Bconf_ExtensionMapping $extensionMapping = null;

    /**
     * Cache for our pipeline
     */
    private ?editor_Plugins_Okapi_Bconf_Pipeline $pipeline = null;

    /**
     * Cache for our content/TOC
     */
    private ?editor_Plugins_Okapi_Bconf_Content $content = null;

    /**
     * Cache for our related customer
     */
    private ?editor_Models_Customer_Customer $customer = null;

    /**
     * The isNew state is only set during the import: after the bconf is saved to DB but before all filebased operations/validations are finished, a bconf is regarded as "new"
     * When Exceptions occurs while packing/unpacking, new bconfs will be deleted from DB
     */
    private bool $isNew = false;

    private bool $doDebug;

    protected $dbInstanceClass = 'editor_Plugins_Okapi_Db_Bconf';

    protected $validatorInstanceClass = 'editor_Plugins_Okapi_Db_Validator_Bconf';

    public function __construct()
    {
        parent::__construct();
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfProcessing');
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function import(string $tmpPath, string $name, string $description, int $customerId = null)
    {
        // DEBUG
        if ($this->doDebug) {
            error_log('Import BCONF ' . $name . ' for customer ' . ($customerId ? $customerId : 'NULL'));
        }

        $bconfData = [
            'name' => $name,
            'description' => $description,
            'customerId' => $customerId,
            'versionIdx' => editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX,
            'isDefault' => 0,
        ];
        $this->init($bconfData, false);
        $this->save(); // Generates id needed for directory
        $dir = $this->getDataDirectory();
        if (self::checkDirectory($dir) != '' && ! mkdir($dir, 0777, true)) {
            $this->delete();

            throw new editor_Plugins_Okapi_Exception('E1057', [
                'okapiDataDir' => $dir,
            ]);
        }
        // when exceptions occur during unpacking/packing this flag ensures, the entity is removed from DB
        $this->isNew = true;
        // unpacks the imported file & saves the parts to filesys/DB
        $this->unpack($tmpPath);
        // packs a bconf from it that can be used for okapi-projects from now on
        $this->pack();

        // final step: validate the bconf - if not the sys-default bconf
        if (! $this->isSystemDefault()) {
            $validation = new editor_Plugins_Okapi_Bconf_Validation($this);
            if ($validation->validate()) {
                if (! $validation->wasTestable()) {
                    // we generate a warning when a bconf could not be validated properly (what rarely can happen)
                    $logger = Zend_Registry::get('logger')->cloneMe('editor.okapi.bconf');
                    $logger->warn(
                        'E1408',
                        'Okapi Plug-In: The bconf "{bconf}" to import is not valid ({details})',
                        [
                            'bconf' => $this->getName(),
                            'bconfId' => $this->getId(),
                            'details' => $validation->getValidationError(),
                        ]
                    );
                }
            } else {
                $name = $this->getName();
                $this->delete();

                throw new editor_Plugins_Okapi_Exception('E1408', [
                    'bconf' => $name,
                    'details' => $validation->getValidationError(),
                ]);
            }
        }
        // after a successful unpack/pack, we're not new anymore
        $this->isNew = false;
    }

    /**
     * Validates the bconf. Returns NULL, if the bconf is valid, otherwise an error why it is invalid
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function validate(): ?string
    {
        $validation = new editor_Plugins_Okapi_Bconf_Validation($this);
        if ($validation->validate()) {
            return null;
        }

        return $validation->getValidationError();
    }

    /**
     * Retrieves if the bconf is the system default bconf
     */
    public function isSystemDefault(): bool
    {
        return ($this->getName() === editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);
    }

    /**
     * Retrieves, if a bconf is outdated and needs to be recompiled
     */
    public function isOutdated(): bool
    {
        return ($this->getVersionIdx() < editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
    }

    /**
     * Updates a bconf if the version-index is outdated with potentially changed default settings
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function repackIfOutdated(bool $force = false)
    {
        if ($this->isOutdated() || $force) {
            // DEBUG
            if ($this->doDebug) {
                error_log('Re-pack BCONF ' . $this->getName() . ' to Version ' . editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
            }

            $this->pack(true);
            $this->setVersionIdx(editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
            $this->save();
        }
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function importDefaultWhenNeeded(): int
    {
        $sysBconfRow = $this->db->fetchRow($this->db->select()->where('name = ?', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME));
        // when the system default bconf does not exist we have to generate it
        if ($sysBconfRow === null) {
            $sysBconf = $this->importSystemDefault();

            return $sysBconf->getId();
        }

        return $sysBconfRow->id;
    }

    /**
     * Imports the system default bconf
     * It must be certain, it does not already exists
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    private function importSystemDefault(): editor_Plugins_Okapi_Bconf_Entity
    {
        $sysBconfPath = editor_Plugins_Okapi_Init::getDataDir() . editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT;
        $sysBconfName = editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME;
        $sysBconfDescription = 'The default set of file filters. Copy to customize filters. Or go to "Clients" and customize filters there.';
        $sysBconf = new editor_Plugins_Okapi_Bconf_Entity();
        $sysBconf->import($sysBconfPath, $sysBconfName, $sysBconfDescription, null);
        $sysBconf->setVersionIdx(editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
        if (! $this->db->fetchRow(['isDefault = 1'])) {
            $sysBconf->setIsDefault(1);
        }
        $sysBconf->save();
        // DEBUG
        if ($this->doDebug) {
            error_log('BCONF: Imported sys default bconf: ' . $sysBconfName);
        }

        return $sysBconf;
    }

    /**
     * Retrieves our data-directory
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getDataDirectory(): string
    {
        return self::getUserDataDir() . '/' . $this->getId();
    }

    /**
     * Creates the path for the bconf itself which follllows a fixed naming-schema
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getPath(): string
    {
        return $this->createPath($this->getFile());
    }

    /**
     * Generates the file-name in our data-dir
     */
    public function getFile(): string
    {
        return 'bconf-' . $this->getId() . '.' . static::EXTENSION;
    }

    /**
     * Creates the path for a file inside the bconf's data-directory
     * @throws editor_Plugins_Okapi_Exception
     */
    public function createPath(string $fileName): string
    {
        return $this->getDataDirectory() . '/' . $fileName;
    }

    /**
     * Retrieves the download filename with extension
     */
    public function getDownloadFilename(): string
    {
        $filename = editor_Utils::secureFilename($this->getName(), false);
        $filename = ($filename == '') ? 'bconf-' . $this->getId() : $filename;

        return $filename . '.' . static::EXTENSION;
    }

    /**
     * Retrieves the default bconf for a customer
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getDefaultBconf(int $customerId = null): editor_Plugins_Okapi_Bconf_Entity
    {
        // if customer given, try to load customer-specific default bconf
        if ($customerId != null) {
            $customerMeta = new editor_Models_Customer_Meta();

            try {
                $customerMeta->loadByCustomerId($customerId);
                // return the customers default only, if it is set!
                // API-based import's may have a customer set that not neccessarily must have a default-bconf
                if (! empty($customerMeta->getDefaultBconfId())) {
                    $this->load($customerMeta->getDefaultBconfId());

                    return $this;
                }
            } catch (ZfExtended_Models_Entity_NotFoundException) {
            }
        }

        try {
            $this->loadRow('isDefault = ? AND `customerId` IS NULL', 1);

            return $this;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
        }

        // try to load system default bconf
        try {
            $this->loadRow('name = ?', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);

            return $this;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
        }

        // if not found, generate it
        return $this->importSystemDefault();
    }

    /**
     * Retrieves the default bconf-id for a customer
     * @return int $defaultBconfId
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getDefaultBconfId(int $customerId = null): int
    {
        return $this->getDefaultBconf($customerId)->getId();
    }

    /**
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getSrxNameFor(string $field): string
    {
        if ($field !== 'source' && $field !== 'target') {
            throw new MismatchException('E2004', [$field, 'field']);
        }

        return $this->getContent()->getSrxFile($field);
    }

    /**
     * Retrieves the SRX as file-object, either "source" or "target"
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getSrx(string $field): editor_Plugins_Okapi_Bconf_Segmentation_Srx
    {
        if ($field !== 'source' && $field !== 'target') {
            throw new MismatchException('E2004', [$field, 'field']);
        }
        $path = $this->createPath($this->getSrxNameFor($field));

        return new editor_Plugins_Okapi_Bconf_Segmentation_Srx($path);
    }

    /**
     * Retrieves the bound customers name (cached)
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getCustomerName(): ?string
    {
        if (empty($this->getCustomerId())) {
            return null;
        }
        if ($this->customer == null || $this->customer->getId() != $this->getCustomerId()) {
            $this->customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
            $this->customer->load($this->getCustomerId());
        }

        return $this->customer->getName();
    }

    /**
     * Retrieves the server path to the extension-mapping file of a bconf
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getExtensionMappingPath(): string
    {
        return $this->createPath(editor_Plugins_Okapi_Bconf_ExtensionMapping::FILE);
    }

    /**
     * Retrieves the extension-mapping object for this bconf
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getExtensionMapping(): editor_Plugins_Okapi_Bconf_ExtensionMapping
    {
        if ($this->extensionMapping == null || $this->extensionMapping->getBconfId() !== $this->getId()) {
            $this->extensionMapping = new editor_Plugins_Okapi_Bconf_ExtensionMapping($this);
        }

        return $this->extensionMapping;
    }

    /**
     * Retrieves the server path to the pipeline-file of a bconf
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getPipelinePath(): string
    {
        return $this->createPath(editor_Plugins_Okapi_Bconf_Pipeline::FILE);
    }

    /**
     * Returns a pipline-object for our pipeline-file
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getPipeline(): editor_Plugins_Okapi_Bconf_Pipeline
    {
        if ($this->pipeline == null || $this->pipeline->getBconfId() !== $this->getId()) {
            $this->pipeline = new editor_Plugins_Okapi_Bconf_Pipeline($this->getPipelinePath(), null, $this->getId());
        }

        return $this->pipeline;
    }

    /**
     * Retrieves the server path to the content/TOC of a bconf
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getContentPath(): string
    {
        return $this->createPath(editor_Plugins_Okapi_Bconf_Content::FILE);
    }

    /**
     * Returns a content-object for our content-file
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getContent(): editor_Plugins_Okapi_Bconf_Content
    {
        if ($this->content == null || $this->content->getBconfId() !== $this->getId()) {
            $this->content = new editor_Plugins_Okapi_Bconf_Content($this->getContentPath(), null, $this->getId());
        }

        return $this->content;
    }

    /**
     * All the file-extensions we support
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getSupportedExtensions(): array
    {
        return $this->getExtensionMapping()->getAllExtensions();
    }

    /**
     * Checks whether the given extension is supported
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function hasSupportFor(string $extension): bool
    {
        return $this->getExtensionMapping()->hasExtension($extension);
    }

    /**
     * Returns the custom (database based) filters for the bconf
     * @return array
     */
    public function getCustomFilterData()
    {
        $filters = new editor_Plugins_Okapi_Bconf_Filter_Entity();

        return $filters->getRowsByBconfId($this->getId());
    }

    /**
     * Returns the custom (database based) filters for the frontend
     * @return array
     */
    public function getCustomFilterGridData()
    {
        $filters = new editor_Plugins_Okapi_Bconf_Filter_Entity();

        return $filters->getGridRowsByBconfId($this->getId());
    }

    /**
     * Retrieves the provider-prefix to be used for custom filters ( $okapiType@$provider-$specialization )
     * Keep in mind that this string may not neccessarily be unique for a customer
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getCustomFilterProviderId(): string
    {
        if (empty($this->getCustomerId())) {
            $config = Zend_Registry::get('config');
            $name = editor_Utils::filenameFromUserText($config->runtimeOptions->server->name, false);
        } else {
            $name = editor_Utils::filenameFromUserText($this->getCustomerName(), false);
            if (empty($name)) {
                $name = 'customer' . $this->getCustomerId();
            }
        }
        // we must not create okapi-id's that start with or contain "translate5"
        if (str_contains($name, 'translate5')) {
            $name = str_replace(['_translate5_', '_translate5', 'translate5_', 'translate5'], ['', '', '', ''], $name);
            if (empty($name)) {
                $name = 'customized';
            }
        }
        if (strlen($name) > 50) {
            return substr($name, 0, 50);
        }

        return $name;
    }

    /**
     * API to make a bconf the base (non-customer) default bconf.
     * Will reset any other non-customer default bconf
     * Returns the ID of the former default (if any)
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function setAsDefaultBconf(): int
    {
        if ($this->getCustomerId() !== null) {
            throw new ZfExtended_Exception('Only bconfs not bound to a customer can be set as default bconf');
        }
        $oldDefaultId = -1;
        $oldDefaultRow = $this->db->fetchRow($this->db->select()->where('customerId IS NULL AND isDefault = 1'));
        if ($oldDefaultRow != null) {
            $oldDefaultId = $oldDefaultRow->id;
            $oldDefaultRow->isDefault = 0;
            $oldDefaultRow->save();
        }
        $this->db->update([
            'isDefault' => 0,
        ], 'customerId IS NULL AND isDefault = 1');
        $this->setIsDefault(1);
        $this->save();

        return $oldDefaultId;
    }

    /**
     * Adds a Bconf Filter to the DB
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function addCustomFilterEntry(string $okapiType, string $okapiId, string $name, string $description, string $hash, string $mimeType = null): editor_Plugins_Okapi_Bconf_Filter_Entity
    {
        if ($mimeType === null) {
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
        if ($this->doDebug) {
            error_log('BCONF: Added custom filter entry "' . $name . '" ' . $okapiType . '@' . $okapiId . ' to bconf ' . $this->getId());
        }

        return $filterEntity;
    }

    /**
     * Finds a custom bconf filter entity
     */
    public function findCustomFilterEntry(string $okapiType, string $okapiId): ?editor_Plugins_Okapi_Bconf_Filter_Entity
    {
        try {
            $filterEntity = new editor_Plugins_Okapi_Bconf_Filter_Entity();
            $filterEntity->loadByTypeAndIdForBconf($okapiType, $okapiId, $this->getId());

            return $filterEntity;
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            return null;
        }
    }

    /**
     * Retrieves a list with the custom filter identifiers that are related
     * @return string[]
     */
    public function findCustomFilterIdentifiers(): array
    {
        $filterEntity = new editor_Plugins_Okapi_Bconf_Filter_Entity();

        return $filterEntity->getIdentifiersForBconf($this->getId());
    }

    /**
     * Retrieves the extensions of our related custom filter entries
     */
    public function findCustomFilterExtensions(): array
    {
        $extensions = [];
        foreach ($this->findCustomFilterIdentifiers() as $identifier) {
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
    public function delete()
    {
        if ($this->isSystemDefault()) {
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
        if ($this->doDebug) {
            error_log('Deleted BCONF ' . $id);
        }
    }

    /**
     * Removes the related files of an bconf entity
     * Must only be called via ::delete
     * @throws editor_Plugins_Okapi_Exception
     */
    private function removeFiles(int $id)
    {
        $dir = self::getUserDataDir() . '/' . $id;
        if (is_dir($dir)) { // just to be safe
            ZfExtended_Utils::recursiveDelete($dir);
        }
    }

    /**
     * Retrieves the list to feed the bconf's grid view
     * Adds the custom extensions to each row
     */
    public function getGridRows(): array
    {
        $data = [];
        foreach ($this->loadAllEntities() as $bconfEntity) { /* @var editor_Plugins_Okapi_Bconf_Entity $bconfEntity */
            $bconfData = $bconfEntity->toArray();
            $bconfData['customExtensions'] = $bconfEntity->findCustomFilterExtensions();
            $data[] = $bconfData;
        }

        return $data;
    }

    /**
     * Disassembles an uploaded bconf into it's parts & flushes the parts into the file-system & DB
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function unpack(string $pathToParse): void
    {
        try {
            $unpacker = new editor_Plugins_Okapi_Bconf_Unpacker($this);
            $unpacker->process($pathToParse);
        } catch (editor_Plugins_Okapi_Bconf_InvalidException $e) {
            // in case of a editor_Plugins_Okapi_Bconf_InvalidException, the exception came from the Unpacker
            $name = $this->getName();
            error_log('UNPACK EXCEPTION for bconf "' . $name . '": ' . $e->getMessage());
            $this->invalidateNew();

            throw new editor_Plugins_Okapi_Exception('E1415', [
                'bconf' => $name,
                'details' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            // if an other exception than the explicitly thrown via invalidate occur we do a passthrough to be able to identify the origin
            error_log('UNKNOWN UNPACK EXCEPTION: ' . $e->__toString());
            $this->invalidateNew();

            throw $e;
        }
    }

    /**
     * Packs a bconf out of it parts (filters, srx, ...) to an assembled bconf
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function pack(bool $isOutdatedRepack = false): void
    {
        try {
            $packer = new editor_Plugins_Okapi_Bconf_Packer($this);
            $packer->process($isOutdatedRepack, $this->isSystemDefault());
        } catch (editor_Plugins_Okapi_Bconf_InvalidException $e) {
            // in case of a editor_Plugins_Okapi_Bconf_InvalidException, the exception came from the packer
            $name = $this->getName();
            error_log('PACK EXCEPTION for bconf "' . $name . '": ' . $e->getMessage());
            $this->invalidateNew();

            throw new editor_Plugins_Okapi_Exception('E1416', [
                'bconf' => $name,
                'details' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            // if an other exception than the explicitly thrown via invalidate occur we do a passthrough to be able to identify the origin
            error_log('UNKNOWN PACK EXCEPTION: ' . $e->__toString());
            $this->invalidateNew();

            throw $e;
        }
    }

    /**
     * Handles deleting new records when an exception ocurred in the import
     */
    protected function invalidateNew(): void
    {
        if ($this->isNew) {
            try {
                $this->delete();
            } catch (Exception $e) {
                error_log('PROBLEMS DELETING BCONF: ' . strval($e));
            }
            $this->isNew = false;
        }
    }

    /**
     * Invalidates all cached dependant objects
     */
    public function invalidateCaches()
    {
        $this->pipeline = null;
        $this->content = null;
        $this->extensionMapping = null;
        $this->customer = null;
    }
}
