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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf;

use editor_Models_ConfigException;
use editor_Models_Customer_Customer;
use editor_Models_Customer_Meta;
use editor_Plugins_Okapi_Init;
use editor_Utils;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\FilterEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\OkapiFilterInventory;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation\Srx;
use MittagQI\Translate5\Plugins\Okapi\Db\BconfTable;
use MittagQI\Translate5\Plugins\Okapi\Db\Validator\BconfValidator;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use MittagQI\ZfExtended\MismatchException;
use ReflectionException;
use Throwable;
use Zend_Config;
use Zend_Db_Statement_Exception;
use Zend_Db_Table_Row_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Debug;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Abstract;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_NoAccessException;
use ZfExtended_UnprocessableEntity;
use ZfExtended_Utils;

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
 * see Filters, ExtensionMapping, Filter_Fprm and Segmentation for more documentation
 *
 * @method string getId()
 * @method void setId(int $id)
 * @method string|null getName()
 * @method void setName(string $name)
 * @method string getIsDefault()
 * @method void setIsDefault(int $int)
 * @method string|null getDescription()
 * @method void setDescription(string $string)
 * @method string|null getCustomerId()
 * @method void setCustomerId(mixed $customerId)
 * @method string getVersionIdx()
 * @method void setVersionIdx(int $versionIdx)
 */
final class BconfEntity extends ZfExtended_Models_Entity_Abstract
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

    /**
     * @throws OkapiException
     */
    public static function getUserDataDir(): string
    {
        if (empty(self::$userDataDir)) {
            $errorMsg = null;

            try {
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
            } catch (Throwable $e) {
                $errorMsg = $e->__toString();
            } finally {
                if ($errorMsg !== null || empty($userDataDir)) {
                    $okapiDataDir = empty($userDataDir) ?
                        'runtimeOptions.plugins.Okapi.dataDir NOT CONFIGURED'
                        : ($errorMsg ? $userDataDir . ' (' . $errorMsg . ')' : $userDataDir);

                    throw new OkapiException('E1057', [
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
    public static function checkDirectory(string $dir): ?string
    {
        if (! is_dir($dir)) {
            if (is_file($dir)) {
                return 'The directory is a file!';
            } else {
                return 'The directory is missing!';
            }
        } elseif (! is_writable($dir)) {
            return 'The directory is not writable!';
        }

        return null;
    }

    /**
     * Loads the system default bconf
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws OkapiException
     */
    public static function getSystemDefaultBconf(): BconfEntity
    {
        $bconf = new BconfEntity();

        try {
            $bconf->loadRow('name = ?', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);

            return $bconf;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return $bconf->importSystemDefault();
        }
    }

    /**
     * Cache for our extension mapping
     */
    private ?ExtensionMapping $extensionMapping = null;

    /**
     * Cache for our pipeline
     */
    private ?Pipeline $pipeline = null;

    /**
     * Cache for our content/TOC
     */
    private ?Content $content = null;

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

    protected $dbInstanceClass = BconfTable::class;

    protected $validatorInstanceClass = BconfValidator::class;

    public function __construct()
    {
        parent::__construct();
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfProcessing');
    }

    /**
     * @throws BconfInvalidException
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function import(string $tmpPath, string $name, string $description, int $customerId = null): void
    {
        // DEBUG
        if ($this->doDebug) {
            error_log('Import BCONF ' . $name . ' for customer ' . ($customerId ?: 'NULL'));
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
        if (self::checkDirectory($dir) != null && ! mkdir($dir, 0777, true)) {
            $this->delete();

            throw new OkapiException('E1057', [
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
            $validation = new BconfValidation($this);
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

                throw new OkapiException('E1408', [
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
     * @throws ReflectionException
     * @throws ZfExtended_Exception
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function validate(): ?string
    {
        $validation = new BconfValidation($this);
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
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_UnprocessableEntity
     * @throws OkapiException
     */
    public function repackIfOutdated(bool $force = false): void
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
     * @throws OkapiException
     */
    public function importDefaultWhenNeeded(): int
    {
        $sysBconfRow = $this->db->fetchRow($this->db->select()->where('name = ?', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME));
        // when the system default bconf does not exist we have to generate it
        if ($sysBconfRow === null) {
            $sysBconf = $this->importSystemDefault();

            return (int) $sysBconf->getId();
        }

        // @phpstan-ignore-next-line
        return (int) $sysBconfRow->id;
    }

    /**
     * Imports the system default bconf
     * @throws BconfInvalidException
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    private function importSystemDefault(): BconfEntity
    {
        $sysBconfPath = editor_Plugins_Okapi_Init::getDataDir() . editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT;
        $sysBconfName = editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME;
        $sysBconfDescription = 'The default set of file filters. Copy to customize filters. Or go to "Clients" and customize filters there.';
        $sysBconf = new BconfEntity();
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
     * @throws OkapiException
     */
    public function getDataDirectory(): string
    {
        return self::getUserDataDir() . '/' . $this->getId();
    }

    /**
     * Creates the path for the bconf itself which follllows a fixed naming-schema
     * @throws OkapiException
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
        return 'bconf-' . $this->getId() . '.' . self::EXTENSION;
    }

    /**
     * Creates the path for a file inside the bconf's data-directory
     * @throws OkapiException
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

        return $filename . '.' . self::EXTENSION;
    }

    /**
     * Retrieves the default bconf for a customer
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws OkapiException
     */
    public function getDefaultBconf(int $customerId = null): BconfEntity
    {
        // if customer given, try to load customer-specific default bconf
        if ($customerId != null) {
            $customerMeta = new editor_Models_Customer_Meta();

            try {
                $customerMeta->loadByCustomerId($customerId);
                // return the customers default only, if it is set!
                // API-based import's may have a customer set that not neccessarily must have a default-bconf
                if (! empty($customerMeta->getDefaultBconfId())) {
                    $this->load((int) $customerMeta->getDefaultBconfId());

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
     * @throws Zend_Db_Table_Row_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws OkapiException
     */
    public function getDefaultBconfId(int $customerId = null): int
    {
        return (int) $this->getDefaultBconf($customerId)->getId();
    }

    /**
     * @throws ZfExtended_Exception
     * @throws OkapiException
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
     * @throws OkapiException
     */
    public function getSrx(string $field): Srx
    {
        if ($field !== 'source' && $field !== 'target') {
            throw new MismatchException('E2004', [$field, 'field']);
        }
        $path = $this->createPath($this->getSrxNameFor($field));

        return new Srx($path);
    }

    /**
     * Retrieves the bound customers name (cached)
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ReflectionException
     */
    public function getCustomerName(): ?string
    {
        if (empty($this->getCustomerId())) {
            return null;
        }
        if ($this->customer == null || (int) $this->customer->getId() !== (int) $this->getCustomerId()) {
            $this->customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
            $this->customer->load((int) $this->getCustomerId());
        }

        return $this->customer->getName();
    }

    /**
     * Retrieves the server path to the extension-mapping file of a bconf
     * @throws OkapiException
     */
    public function getExtensionMappingPath(): string
    {
        return $this->createPath(ExtensionMapping::FILE);
    }

    /**
     * Retrieves the extension-mapping object for this bconf
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function getExtensionMapping(): ExtensionMapping
    {
        if ($this->extensionMapping == null || $this->extensionMapping->getBconfId() !== (int) $this->getId()) {
            $this->extensionMapping = new ExtensionMapping($this);
        }

        return $this->extensionMapping;
    }

    /**
     * Retrieves the server path to the pipeline-file of a bconf
     * @throws OkapiException
     */
    public function getPipelinePath(): string
    {
        return $this->createPath(Pipeline::FILE);
    }

    /**
     * Returns a pipline-object for our pipeline-file
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function getPipeline(): Pipeline
    {
        if ($this->pipeline == null || $this->pipeline->getBconfId() !== (int) $this->getId()) {
            $this->pipeline = new Pipeline($this->getPipelinePath(), null, (int) $this->getId());
        }

        return $this->pipeline;
    }

    /**
     * Retrieves the server path to the content/TOC of a bconf
     * @throws OkapiException
     */
    public function getContentPath(): string
    {
        return $this->createPath(Content::FILE);
    }

    /**
     * Returns a content-object for our content-file
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function getContent(): Content
    {
        if ($this->content == null || $this->content->getBconfId() !== (int) $this->getId()) {
            $this->content = new Content($this->getContentPath(), null, (int) $this->getId());
        }

        return $this->content;
    }

    /**
     * All the file-extensions we support
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function getSupportedExtensions(): array
    {
        return $this->getExtensionMapping()->getAllExtensions();
    }

    /**
     * Checks whether the given extension is supported
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function hasSupportFor(string $extension): bool
    {
        return $this->getExtensionMapping()->hasExtension($extension);
    }

    /**
     * Returns the custom (database based) filters for the bconf
     */
    public function getCustomFilterData(): array
    {
        $filters = new FilterEntity();

        return $filters->getRowsByBconfId((int) $this->getId());
    }

    /**
     * Returns the custom (database based) filters for the frontend
     */
    public function getCustomFilterGridData(): array
    {
        $filters = new FilterEntity();

        return $filters->getGridRowsByBconfId((int) $this->getId());
    }

    /**
     * Retrieves the provider-prefix to be used for custom filters ( $okapiType@$provider-$specialization )
     * Keep in mind that this string may not neccessarily be unique for a customer
     * @throws ReflectionException
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
     * @throws ZfExtended_Exception
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
            // @phpstan-ignore-next-line
            $oldDefaultId = $oldDefaultRow->id;
            // @phpstan-ignore-next-line
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
    public function addCustomFilterEntry(string $okapiType, string $okapiId, string $name, string $description, string $hash, string $mimeType = null): FilterEntity
    {
        if ($mimeType === null) {
            $mimeType = OkapiFilterInventory::findMimeType($okapiType);
        }
        $filterEntity = new FilterEntity();
        // @phpstan-ignore-next-line
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
    public function findCustomFilterEntry(string $okapiType, string $okapiId): ?FilterEntity
    {
        try {
            $filterEntity = new FilterEntity();
            $filterEntity->loadByTypeAndIdForBconf($okapiType, $okapiId, (int) $this->getId());

            return $filterEntity;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return null;
        }
    }

    /**
     * Retrieves a list with the custom filter identifiers that are related
     * @return string[]
     */
    public function findCustomFilterIdentifiers(): array
    {
        $filterEntity = new FilterEntity();

        return $filterEntity->getIdentifiersForBconf((int) $this->getId());
    }

    /**
     * Retrieves the extensions of our related custom filter entries
     * @throws ZfExtended_Exception
     * @throws OkapiException
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
     * @throws Zend_Db_Table_Row_Exception
     * @throws ZfExtended_NoAccessException
     * @throws OkapiException
     */
    public function delete()
    {
        if ($this->isSystemDefault()) {
            throw new ZfExtended_NoAccessException('You can not delete the system default bconf.');
        }

        // @phpstan-ignore-next-line
        $id = $this->row->id;
        $this->row->delete();
        $this->removeFiles($id);
        // DEBUG
        if ($this->doDebug) {
            error_log('Deleted BCONF ' . $id);
        }
    }

    /**
     * Removes the related files of an bconf entity
     * Must only be called via ::delete
     * @throws OkapiException
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
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function getGridRows(): array
    {
        $data = [];
        foreach ($this->loadAllEntities() as $bconfEntity) { /** @var BconfEntity $bconfEntity */
            $bconfData = $bconfEntity->toArray();
            $bconfData['customExtensions'] = $bconfEntity->findCustomFilterExtensions();
            $data[] = $bconfData;
        }

        return $data;
    }

    /**
     * Disassembles an uploaded bconf into it's parts & flushes the parts into the file-system & DB
     * @throws BconfInvalidException
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function unpack(string $pathToParse): void
    {
        try {
            $unpacker = new Unpacker($this);
            $unpacker->process($pathToParse);
        } catch (BconfInvalidException $e) {
            // in case of a BconfInvalidException, the exception came from the Unpacker
            $name = $this->getName();
            error_log('UNPACK EXCEPTION for bconf "' . $name . '": ' . $e->getMessage());
            $this->invalidateNew();

            throw new OkapiException('E1415', [
                'bconf' => $name,
                'details' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            // if a different exception than the explicitly thrown via invalidate occurs,
            // we do a passthrough to be able to identify the origin
            error_log('UNKNOWN UNPACK EXCEPTION: ' . $e->__toString());
            $this->invalidateNew();

            throw $e;
        }
    }

    /**
     * Packs a bconf out of it parts (filters, srx, ...) to an assembled bconf
     * @throws BconfInvalidException
     * @throws Throwable
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function pack(bool $isOutdatedRepack = false): void
    {
        try {
            $packer = new Packer($this);
            $packer->createExtraction($isOutdatedRepack, $this->isSystemDefault());
        } catch (BconfInvalidException $e) {
            // in case of a BconfInvalidException, the exception came from the packer
            $name = $this->getName();
            error_log('PACK EXCEPTION for bconf "' . $name . '": ' . $e->getMessage());
            $this->invalidateNew();

            throw new OkapiException('E1416', [
                'bconf' => $name,
                'details' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            // if a different exception than the explicitly thrown via invalidate occur we do a passthrough
            // to be able to identify the origin
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
            } catch (Throwable $e) {
                error_log('PROBLEMS DELETING BCONF: ' . $e->getMessage());
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
