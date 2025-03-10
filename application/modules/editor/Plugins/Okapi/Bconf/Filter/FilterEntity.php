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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf\Filter;

use editor_Utils;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filters;
use MittagQI\Translate5\Plugins\Okapi\Db\BconfFilterTable;
use MittagQI\Translate5\Plugins\Okapi\Db\Validator\BconfFilterValidator;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use stdClass;
use Zend_Db_Table_Abstract;
use Zend_Db_Table_Exception;
use Zend_Exception;
use ZfExtended_Debug;
use ZfExtended_Exception;
use ZfExtended_Models_Entity_Abstract;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * Okapi Bconf Filter Entity Object
 *
 * Represent a single customized FPRM file that has been customized by the user.
 * see Filters and Fprm for more documentation
 *
 * @method string getId()
 * @method void setId(int $id)
 * @method string getBconfId()
 * @method void setBconfId(int $bconfId)
 * @method string getOkapiType()
 * @method void setOkapiType(string $okapiType)
 * @method string getOkapiId()
 * @method void setOkapiId(string $okapiId)
 * @method string getMimeType()
 * @method void setMimeType(string $mimeType)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getHash()
 * @method void setHash(string $hash)
 */
final class FilterEntity extends ZfExtended_Models_Entity_Abstract
{
    /**
     * @var string
     */
    public const EXTENSION = 'fprm';

    /**
     * @var int
     */
    public const MAX_IDENTIFIER_LENGTH = 128;

    /**
     * Creates new identifier and copies the referenced FPRM for a ne bconf-filter
     * @return stdClass: Object with properties okapiId | identifier | path | hash
     *
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \ReflectionException
     * @throws OkapiException
     */
    public static function preProcessNewEntry(
        int $bconfId,
        string $okapiType,
        string $okapiId,
        string $filterName,
    ): stdClass {
        $bconf = new BconfEntity();
        $bconf->load($bconfId);
        // we need the old identifier to copy the fprm
        $oldIdentifier = Filters::createIdentifier($okapiType, $okapiId);
        $newOkapiId = FilterEntity::createOkapiId($bconf, $filterName, $okapiType);
        $newIdentifier = Filters::createIdentifier($okapiType, $newOkapiId);
        // retrieves the filepath of the fprm to copy
        $sourcePath = $bconf->createPath(self::createFileFromIdentifier($oldIdentifier));
        if (Filters::instance()->isEmbeddedOkapiDefaultFilter($okapiType, $okapiId)) {
            $sourcePath = Filters::instance()->getOkapiDefaultFilterPathById($okapiId);
        } elseif (Filters::instance()->isEmbeddedTranslate5Filter($okapiType, $okapiId)) {
            $sourcePath = Filters::instance()->getTranslate5FilterPath($okapiType, $okapiId);
        } elseif (! file_exists($sourcePath)) {
            throw new OkapiException('E1409', [
                'filterfile' => $sourcePath,
                'details' => 'The file was not found in ' . ltrim($bconf->createPath(''), '/'),
            ]);
        }
        $targetPath = $bconf->createPath(self::createFileFromIdentifier($newIdentifier));
        copy($sourcePath, $targetPath);
        $fprm = new Fprm($targetPath);
        // DEBUG
        if (ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfProcessing')) {
            error_log('BCONF FILTER: created new identifier "' . $newIdentifier
                . '" and copied FPRM-file for bconf-filter "' . $filterName . '" for bconf ' . $bconf->getId());
        }
        // generate return data
        $newData = new stdClass();
        $newData->okapiId = $newOkapiId;
        $newData->identifier = $newIdentifier;
        $newData->path = $targetPath;
        $newData->hash = $fprm->getHash();

        return $newData;
    }

    /**
     * Generates the okapi-id for a new custom filter
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \ReflectionException
     * @throws OkapiException
     */
    public static function createOkapiId(BconfEntity $bconf, string $name, string $okapiType): string
    {
        $baseId = $bconf->getCustomFilterProviderId() . '-' . editor_Utils::filenameFromUserText($name, false);
        if (strlen($baseId) > (self::MAX_IDENTIFIER_LENGTH - 2)) {
            $baseId = substr($baseId, 0, (self::MAX_IDENTIFIER_LENGTH - 2));
        }
        $okapiId = $baseId;
        $dir = $bconf->getDataDirectory() . '/';
        $count = 0;
        while (file_exists($dir . Filters::createIdentifier($okapiType, $okapiId) . '.' . self::EXTENSION)) {
            $count++;
            $okapiId = $baseId . '-' . $count;
        }

        return $okapiId;
    }

    public static function createFileFromIdentifier(string $identifier): string
    {
        return $identifier . '.' . self::EXTENSION;
    }

    protected $dbInstanceClass = BconfFilterTable::class;

    protected $validatorInstanceClass = BconfFilterValidator::class;

    private ?BconfEntity $bconf = null;

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getRelatedBconf(): BconfEntity
    {
        // use cached bconf only with identical ID
        if ($this->bconf === null || $this->bconf->getId() != $this->getBconfId()) {
            $this->bconf = new BconfEntity();
            $this->bconf->load((int) $this->getBconfId());
        }

        return $this->bconf;
    }

    /**
     * retrieves our identifier as it can be found in the extension mapping
     */
    public function getIdentifier(): string
    {
        return Filters::createIdentifier($this->getOkapiType(), $this->getOkapiId());
    }

    /**
     * Retrieves the full filename of the related fprm file
     */
    public function getFile(): string
    {
        return self::createFileFromIdentifier($this->getIdentifier());
    }

    /**
     * Retrieves the server-path to our related fprm
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    public function getPath(): string
    {
        return $this->getRelatedBconf()->createPath($this->getFile());
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    public function getFprm(): Fprm
    {
        return new Fprm($this->getPath());
    }

    /**
     * Retrieves our related file-extensions
     * Note, that this fetches the related bconf from DB, reda the extensions-mapping & parses it
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    public function getMappedExtensions(): array
    {
        return $this->getRelatedBconf()->getExtensionMapping()->findExtensionsForFilter($this->getIdentifier());
    }

    /**
     * find all customized filters for a bconf
     */
    public function getRowsByBconfId(int $bconfId): array
    {
        $select = $this->db->select()
            ->where('bconfId = ?', $bconfId);

        return $this->loadFilterdCustom($select);
    }

    /**
     * Retrieves the data for the frontend grid
     */
    public function getGridRowsByBconfId(int $bconfId): array
    {
        $rows = [];
        foreach ($this->getRowsByBconfId($bconfId) as $row) {
            unset($row['hash']);
            // the identifier can act as a unique ID in the frontend, akapiType and okapiId are not unique
            $row['identifier'] = Filters::createIdentifier($row['okapiType'], $row['okapiId']);
            $row['editable'] = Filters::hasGui($row['okapiType']);
            $row['isCustom'] = true;
            $row['guiClass'] = Filters::getGuiClass($row['okapiType']);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByTypeAndHash(string $okapiType, string $hash)
    {
        $select = $this->db->select()
            ->where('okapiType = ?', $okapiType)
            ->where('hash = ?', $hash);
        $this->loadRowBySelect($select);
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByTypeAndIdForBconf(string $okapiType, string $okapiId, int $bconfId)
    {
        $select = $this->db->select()
            ->where('bconfId = ?', $bconfId)
            ->where('okapiType = ?', $okapiType)
            ->where('okapiId = ?', $okapiId);
        $this->loadRowBySelect($select);
    }

    /**
     * Retrieves the highest auto-increment id
     * @throws Zend_Db_Table_Exception
     */
    public function getHighestId(): int
    {
        return intval($this->db->getAdapter()->fetchOne(
            'SELECT MAX(id) FROM ' . $this->db->info(Zend_Db_Table_Abstract::NAME)
        ));
    }

    /**
     * Retrieves the custom filter identifiers for the given bconf
     * @return string[]
     */
    public function getIdentifiersForBconf(int $bconfId): array
    {
        $identifiers = [];
        foreach ($this->getRowsByBconfId($bconfId) as $rowData) {
            $identifiers[] = Filters::createIdentifier($rowData['okapiType'], $rowData['okapiId']);
        }

        return $identifiers;
    }
}
