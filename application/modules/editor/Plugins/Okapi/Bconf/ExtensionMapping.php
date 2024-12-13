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

use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\FilterEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\Fprm;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\OkapiFilterInventory;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\T5FilterInventory;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Parser\ExtensionMappingParser;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Debug;
use ZfExtended_Exception;
use ZfExtended_Logger;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * Class representing the Extension mapping of an Bconf
 * It is able to parse the extension-mapping from file, build an internal model, manipulate it and write it back to file
 * Also the mapping for the actual bconf-file can be retrieved where the okapi-default entries are
 * actually mapped to the mapping files. The processing when unpacking bconfs is also implemented here,
 * including writing the extracted custom fprms to the DB and file-system.
 * The extension mapping is always stored as a filein a BCONFs file-repository with the name "extensions-mapping.txt"
 *
 * There are 4 types of filter-identifiers in a bconf:
 * OKAPI defaults:
 *     Identifiers like "okf_xml-AndroidStrings"; These names represent bconf-id's as used in Rainbow and Longhorn
 * OKAPI embedded defaults:
 *     Identifiers like "okf_xml@okf_xml-AndroidStrings"; These names have the formal bconf-type @ bconf-id.
 *     The id's are the ones used as OKAPI defaults.
 * translate5 adjusted defaults:
 *     Identifiers like "okf_xml@translate5-AndroidStrings"; Here the okapi-id always is "translate5"
 *     or srtarts with "translate5-"
 * User customized filters:
 *     Identifiers like "okf_xml@worldtranslation-my_special_setting"; Here the okapi-id has a special
 *     format: [ customerName or domain ] + "-" + [ websafe bconf name ]
 *
 * When packing BCONFs, the Okapi default filters will all be embedded into the BCONF to ensure maximal compatibility
 * over time and between different Okapi-Versions. When unpacking BCONFs, that have embedded OKAPI defaults,
 * the OKAPI embedded default identifiers will be reverted to simple OKAPI defaults. Only user customized filters will
 * actually have FPRM-files in the BCONF's file store, all other types will always be taken from the git-based stores
 * in translate5/application/modules/editor/Plugins/Okapi/data/fprm/
 */
final class ExtensionMapping extends ExtensionMappingParser
{
    /**
     * The filename an extension mapping generally has
     * @var string
     */
    public const FILE = 'extensions-mapping.txt';

    private static ?ZfExtended_Logger $logger = null;

    /**
     * Processes an embedded filter (fprm) when unpacking a bconf. The following cases can occur / are processed:
     * - an okapi-default filter is embedded => stays as it is
     * - an invalid okapi-default filter is embedded: simply is removed & warning written
     * - an embedded okapi-default filter is imported (e.g. by reimporting a bconf generated with T5):
     *   will be rewritten to a non-embedded default filter
     * - an embedded translate5 adjusted filter is imported: will be part of the extension-mapping
     *   but not saved to the file-system
     * - a custom embedded filter is imported: Will be saved to the file-system & written as custom filter to the DB.
     *   Existing custom filters are searched by hash for a name & description
     * if true is returned, the fprm needs to be saved to the bconfs data dir
     *
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     */
    public static function processUnpackedFilter(
        BconfEntity $bconf,
        string $identifier,
        string $unpackedContent,
        array &$replacementMap,
        array &$customFilters,
    ): bool {
        $doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiExtensionMapping');
        // default-identifiers do not need to be embedded, we just check them
        if (Filters::isOkapiDefaultIdentifier($identifier)) {
            // a non-embedded default-identifier that does not point to a valid OKAPI default filter will be removed
            // and a warning written
            if (! Filters::instance()->isValidOkapiDefaultFilter($identifier)) {
                $replacementMap[$identifier] = self::INVALID_IDENTIFIER;
                self::getLogger()->warn(
                    'E1407',
                    'Okapi Plug-In: The extension mapping of the bconf "{bconf}" contains an invalid filter'
                    . ' identifier "{identifier}" which has been removed',
                    [
                        'bconf' => $bconf->getName(),
                        'bconfId' => $bconf->getId(),
                        'identifier' => $identifier,
                    ]
                );

                if ($doDebug) {
                    error_log('ExtensionMapping processUnpackedFilter: okapi default identifier '
                        . $identifier . ' is invalid');
                }
            }
        } else {
            // process all identifiers pointing to a file
            $idata = Filters::parseIdentifier($identifier);
            if (Filters::instance()->isEmbeddedDefaultFilter($idata->type, $idata->id)) {
                // when we import a embedded okapi default filter we map it to a non-embedded okapi default filter
                if (Filters::instance()->isEmbeddedOkapiDefaultFilter($idata->type, $idata->id)) {
                    $replacementMap[$identifier] = $idata->id;

                    if ($doDebug) {
                        error_log('ExtensionMapping processUnpackedFilter: replace ' . $identifier
                            . ' with ' . $idata->id);
                    }
                }
            } else {
                // "real" custom filters, check if they have a supported type
                if (OkapiFilterInventory::isValidType($idata->type)) {
                    if ($doDebug) {
                        error_log('ExtensionMapping processUnpackedFilter: custom filter with identifier '
                            . $identifier . ' will be embedded');
                    }
                    // add a custom filter to the filesys & map (that later is flushed to the DB)
                    $fprmPath = $bconf->createPath(FilterEntity::createFileFromIdentifier($identifier));
                    $fprm = new Fprm($fprmPath, $unpackedContent);
                    if ($fprm->validate(true)) {
                        $fprm->flush();
                        $customFilters[$identifier] = $fprm->getHash();

                        return true;
                    } else {
                        throw new ZfExtended_Exception($fprm->getValidationError());
                    }
                } else {
                    $replacementMap[$identifier] = self::INVALID_IDENTIFIER;
                    self::getLogger()->warn(
                        'E1407',
                        'Okapi Plug-In: The extension mapping of the bconf "{bconf}" contains an invalid filter'
                        . ' identifier "{identifier}" which has been removed',
                        [
                            'bconf' => $bconf->getName(),
                            'bconfId' => $bconf->getId(),
                            'identifier' => $identifier,
                        ]
                    );

                    if ($doDebug) {
                        error_log('ExtensionMapping processUnpackedFilter: custom filter with identifier '
                            . $identifier . ' is invalid');
                    }
                }
            }
        }

        return false;
    }

    /**
     * @throws Zend_Exception
     */
    private static function getLogger(): ZfExtended_Logger
    {
        if (self::$logger == null) {
            self::$logger = Zend_Registry::get('logger')->cloneMe('editor.okapi.bconf.filter');
        }

        return self::$logger;
    }

    /**
     * @var string
     */
    public const LINEFEED = "\n";

    /**
     * @var string
     */
    public const SEPERATOR = "\t";

    private string $path;

    private string $dir;

    private ?array $mapToPack = null;

    /**
     * When packing a bconf we add the okapi-defaults as real FPRMs. These will be cached here
     */
    private array $fprmsToPack;

    /**
     * Captures validatiopn errors during validation
     */
    private array $validationErrors;

    private bool $doDebug;

    private BconfEntity $bconf;

    /**
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function __construct(BconfEntity $bconf, ?array $unpackedLines = null, ?array $replacementMap = null)
    {
        $this->path = $bconf->getExtensionMappingPath();
        $this->dir = rtrim(dirname($this->path), '/');
        $this->bconf = $bconf;
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiExtensionMapping');

        if (is_array($unpackedLines) && is_array($replacementMap)) {
            if ($this->doDebug) {
                error_log('ExtensionMapping construct by content:' . "\n" . print_r($unpackedLines, 1)
                    . "\n" . ' replacementMap:' . "\n" . print_r($replacementMap, 1));
            }
            // importing an unpacked bconf
            $this->unpackContent($unpackedLines, $replacementMap);
        } else {
            if ($this->doDebug) {
                error_log('ExtensionMapping construct by path: ' . $this->path);
            }
            // opening a mapping from the file-system
            $content = null;
            if (file_exists($this->path)) { //&& is_writable($this->path)
                $content = file_get_contents($this->path);
            }
            if (empty($content)) {
                throw new ZfExtended_Exception(
                    'ExtensionMapping can only be instantiated for an existing extension-mapping file (' . $this->path . ')'
                );
            }
            $this->parseContent($content);
            if (! $this->hasEntries()) {
                throw new OkapiException('E1405', [
                    'bconf' => $bconf->getName(),
                    'bconfId' => $bconf->getId(),
                ]);
            }
        }
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Retrieves the related bconf id
     */
    public function getBconfId(): int
    {
        return (int) $this->bconf->getId();
    }

    /**
     * Generates the frontend-model for the extension mapping, where identifier => extensions
     * The non-embedded default identifiers like 'okf_xml-AndroidStrings' will be turned to embedded ones like
     * 'okf_xml@okf_xml-AndroidStrings'
     */
    public function getIdentifierMap(): array
    {
        $items = [];
        // map extensions to filters
        foreach ($this->map as $extension => $identifier) {
            if (! array_key_exists($identifier, $items)) {
                $items[$identifier] = [];
            }
            $items[$identifier][] = $extension;
        }
        // generate Frontend data model
        $jsonData = [];
        foreach ($items as $identifier => $extensions) {
            if (Filters::isOkapiDefaultIdentifier($identifier)) {
                $okapiType = Filters::instance()->getOkapiDefaultFilterTypeById($identifier);
                if ($okapiType === null) {
                    // theoretically this can not happen
                    error_log('INVALID non-embedded okapi default filter entry in extension mapping '
                        . $this->path . ' for bconf ' . $this->bconf->getId());
                } else {
                    $jsonData[Filters::createIdentifier($okapiType, $identifier)] = $extensions;
                }
            } else {
                $jsonData[$identifier] = $extensions;
            }
        }

        if ($this->doDebug) {
            error_log('ExtensionMapping toJSON: ' . "\n" . print_r($jsonData, 1));
        }

        return $jsonData;
    }

    /**
     * Retrieves the extensions a filter has
     * @return string[]
     * @throws ZfExtended_Exception
     */
    public function findExtensionsForFilter(string $identifier): array
    {
        // this api is agnostic to passed embedded okapi default filters
        $defaultIdentifier = Filters::createOkapiDefaultIdentifier($identifier);
        $extensions = [];
        foreach ($this->map as $extension => $filter) {
            if ($filter === $identifier || ($defaultIdentifier !== null && $filter === $defaultIdentifier)) {
                $extensions[] = $extension;
            }
        }

        return $extensions;
    }

    /**
     * @throws ZfExtended_Exception
     */
    public function findCustomIdentifiers(): array
    {
        $customIdentifiers = [];
        foreach ($this->map as $extension => $filter) {
            if (Filters::instance()->isCustomFilter($filter)) {
                $customIdentifiers[$filter] = true;
            }
        }
        $identifiers = array_keys($customIdentifiers);
        sort($identifiers);

        return $identifiers;
    }

    /**
     * Removes extensions from the mapping and flushes the map if changed
     * Also updates the related content-file
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function removeExtensions(array $extensions): bool
    {
        if ($this->doDebug) {
            error_log('ExtensionMapping removeExtensions: [ ' . implode(', ', $extensions) . ' ]');
        }

        $newMap = [];
        $removed = false;
        foreach ($this->map as $extension => $filter) {
            if (in_array($extension, $extensions)) {
                $removed = true;
            } else {
                $newMap[$extension] = $filter;
            }
        }
        if ($removed) {
            $this->map = $newMap;
            $this->flush();
            $this->updateBconfContent();

            if ($this->doDebug) {
                error_log('ExtensionMapping removeExtensions: [ ' . implode(', ', $extensions)
                    . ' ] have been removed' . "\n" . print_r($this->map, 1));
            }
        }

        return $removed;
    }

    /**
     * Removes a filter from the mapping and deletes the corresponding fprm-file anf flushes the map if changed
     * Also updates the related content-file
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function removeFilter(string $identifier): bool
    {
        // this api is agnostic to passed embedded okapi default filters
        $defaultIdentifier = Filters::createOkapiDefaultIdentifier($identifier);

        if ($this->doDebug) {
            error_log('ExtensionMapping removeFilter: ' . $identifier
                . ($defaultIdentifier ? ' || ' . $defaultIdentifier : ''));
        }

        $newMap = [];
        $removed = false;
        foreach ($this->map as $extension => $filter) {
            if ($filter === $identifier || ($defaultIdentifier !== null && $filter === $defaultIdentifier)) {
                $removed = true;
            } else {
                $newMap[$extension] = $filter;
            }
        }
        if ($removed) {
            $this->map = $newMap;
            if (file_exists($this->dir . '/' . $identifier . '.' . FilterEntity::EXTENSION)) {
                @unlink($this->dir . '/' . $identifier . '.' . FilterEntity::EXTENSION);
            }
            $this->flush();
            $this->updateBconfContent();

            if ($this->doDebug) {
                error_log('ExtensionMapping removeFilter: ' . $identifier . ' has been removed' . "\n"
                    . print_r($this->map, true));
            }
        }

        return $removed;
    }

    /**
     * Merges all Translate5 specific extensions in
     * Usually, this will only be applied to the T5 default bconf ...
     * @return bool: If the mapping was changed due to the sync
     * @throws ZfExtended_Exception
     */
    public function complementTranslate5Extensions(): bool
    {
        $translate5Mapping = T5FilterInventory::instance()->getExtensionMappingEntries();

        if ($this->doDebug) {
            error_log('ExtensionMapping syncTranslate5Extensions: ' . print_r($translate5Mapping, true));
        }

        $changed = false;
        foreach ($translate5Mapping as $extension => $identifier) {
            if (! empty($extension) &&
                (! array_key_exists($extension, $this->map) || $this->map[$extension] != $identifier)
            ) {
                $this->map[$extension] = $identifier;
                $changed = true;
            }
        }
        if ($changed) {
            $this->flush();
            $this->updateBconfContent();

            if ($this->doDebug) {
                error_log('ExtensionMapping syncTranslate5Extensions: mapping has been changed: ' . "\n"
                    . print_r($this->map, true));
            }
        }

        return $changed;
    }

    /**
     * Adds a filter with it's extensions
     * Also updates the related content-file
     * @throws ZfExtended_Exception
     */
    public function addFilter(string $identifier, array $extensions): bool
    {
        if ($this->doDebug) {
            error_log('ExtensionMapping addFilter: ' . $identifier . ', extensions: [ '
                . implode(', ', $extensions) . ' ]');
        }

        // we simply use the change API, but it seems resonable to have an explicit add function
        return $this->changeFilter($identifier, $extensions);
    }

    /**
     * Changes a filter with it's extensions
     * Also updates the related content-file
     * @throws ZfExtended_Exception
     */
    public function changeFilter(string $identifier, array $extensions): bool
    {
        // important: make sure, the filter does not contain empty extensions
        $extensions = array_values(array_filter($extensions));
        // this api is agnostic to passed embedded okapi default filters
        $defaultIdentifier = Filters::createOkapiDefaultIdentifier($identifier);

        if ($this->doDebug) {
            error_log('ExtensionMapping changeFilter: ' . $identifier
                . ($defaultIdentifier ? ' || ' . $defaultIdentifier : '')
                . ', extensions: [ ' . implode(', ', $extensions) . ' ]');
        }

        $newMap = [];
        $extToAdd = $extensions;
        $extBefore = [];
        $changed = false;
        foreach ($this->map as $extension => $filter) {
            if ($filter === $identifier || ($defaultIdentifier !== null && $filter === $defaultIdentifier)) {
                $extBefore[] = $extension;
                if (count($extToAdd) > 0) {
                    // add extension for filter & remove from list to add
                    $newExt = array_shift($extToAdd);
                    $newMap[$newExt] = $filter;
                } else {
                    $changed = true;
                }
            } elseif (in_array($extension, $extensions)) {
                $changed = true;
            } else {
                $newMap[$extension] = $filter;
            }
        }
        // append extensions that could not be distributed to existing entries for $identifier
        if (count($extToAdd) > 0) {
            // in the map on disk we always use default identifiers
            $filter = ($defaultIdentifier === null) ? $identifier : $defaultIdentifier;
            foreach ($extToAdd as $extension) {
                $newMap[$extension] = $filter;
            }
            $changed = true;
        }
        // we must capture the case where different extensions of the same amount have been set,
        // this cannot be evaluated with the logic above
        if (! $changed) {
            $changed = (count(array_diff($extBefore, $extensions)) !== 0);
        }
        if ($changed) {
            $this->map = $newMap;
            $this->flush();
            $this->updateBconfContent();

            if ($this->doDebug) {
                error_log('ExtensionMapping changeFilter: ' . $identifier . ' with extensions [ '
                    . implode(', ', $extensions) . ' ] has been changed: '
                    . "\n" . print_r($this->map, 1));
            }
        }

        return $changed;
    }

    /**
     * re-scans an existing bconf and brings the database in-sync with the filesystem
     * This is to support the initial rollout
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \Zend_Db_Statement_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws OkapiException
     */
    public function rescanFilters(): void
    {
        if ($this->doDebug) {
            error_log('ExtensionMapping rescanFilters');
        }
        // evaluate existing filters from db
        $bconfFilter = new FilterEntity();
        $existingFilters = [];
        $existingNames = [];
        $allFilters = [];
        foreach ($bconfFilter->getRowsByBconfId((int) $this->bconf->getId()) as $filterData) {
            $identifier = Filters::createIdentifier($filterData['okapiType'], $filterData['okapiId']);
            $existingFilters[$identifier] = $filterData['id'];
            $existingNames[] = $filterData['name'];
        }
        // evaluate filters in filesystem & add those not present in the database
        $dir = $this->bconf->getDataDirectory();
        $filterFiles = glob($dir . '/*.fprm');
        foreach ($filterFiles as $filterFile) {
            $identifier = Filters::createIdentifierFromPath($filterFile);
            $idata = Filters::parseIdentifier($identifier);
            if (Filters::instance()->isEmbeddedDefaultFilter($idata->type, $idata->id)) {
                // translate5 and okapi filters must not be in the custom filter dir
                @unlink($filterFile);

                if ($this->doDebug) {
                    error_log('ExtensionMapping rescanFilters: deleted embedded default filter ' . $identifier);
                }
            } else {
                // if a filter is not in the DB we must do so
                if (! array_key_exists($identifier, $existingFilters)) {
                    $fprm = new Fprm($filterFile);
                    $this->flushFilterToDatabase($identifier, $fprm->getHash(), $existingNames);

                    if ($this->doDebug) {
                        error_log('ExtensionMapping rescanFilters: added custom filter to DB ' . $identifier);
                    }
                }
                $allFilters[] = $identifier;
            }
        }
        // remove the filters that exist in the database but have no corresponding file
        foreach ($existingFilters as $identifier => $id) {
            if (! in_array($identifier, $allFilters)) {
                $bconfFilter->load($id);
                $bconfFilter->delete();

                if ($this->doDebug) {
                    error_log('ExtensionMapping rescanFilters: deleted non-existing filter '
                        . $identifier . ' from database');
                }
            }
        }
        // finally: adjust the content.json
        $content = $this->bconf->getContent();
        $content->setFilters($allFilters);
        $content->flush();

        if ($this->doDebug) {
            error_log('ExtensionMapping rescanFilters: The bconf "' . $this->bconf->getName() . '" has '
                . count($allFilters) . ' custom filters: [ ' . implode(', ', $allFilters) . ' ]');
        }
    }

    /**
     * Adds afilter to the database
     * @throws ZfExtended_Exception
     * @throws \Zend_Db_Statement_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function flushFilterToDatabase(string $identifier, string $hash, array &$usedNames): void
    {
        // evaluate name & description from a filter that already exists in the DB
        $idata = Filters::parseIdentifier($identifier);
        $hashedEntity = new FilterEntity();

        try {
            $hashedEntity->loadByTypeAndHash($idata->type, $hash);
            $name = $hashedEntity->getName();
            $description = $hashedEntity->getDescription();
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            // revert secure filename to a somewhat human-readable name, remove trailing counters like '-4'
            $name = preg_replace('/\-[0-9]+$/', '', $idata->id);
            // revert secure filename to a somewhat human-readable name
            $name = ucfirst(str_replace('_', ' ', $name));
            if ($name == '') { // just to be sure ...
                $name = $idata->id;
            }
            $description = '';
        }
        // we must guarantee unique names
        $baseName = $name;
        $count = 0;
        while (in_array($name, $usedNames)) {
            $count++;
            $name = $baseName . ' ' . $count;
        }
        $this->bconf->addCustomFilterEntry(
            $idata->type,
            $idata->id,
            $name,
            $description,
            $hash
        );
        $usedNames[] = $name;

        if ($this->doDebug) {
            error_log('ExtensionMapping flushFilterToDatabase: added ' . $identifier . ', "'
                . $name . '" to database');
        }
    }

    /**
     * writes back an extension-mapping to the filesystem
     */
    public function flush()
    {
        file_put_contents($this->path, $this->createContent($this->map));
    }

    /**
     * saves the passed filters ('identifier' => 'hash') to the database
     * This is the follow-up to processUnpackedFilter, in a bconf, the filters are encoded before the mapping-file
     * so the processing is "in the wrong direction"
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \Zend_Db_Statement_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function flushUnpacked(array $customFilters): void
    {
        $usedNames = [];
        foreach ($customFilters as $identifier => $hash) {
            $this->flushFilterToDatabase($identifier, $hash, $usedNames);
        }
        $this->flush();
    }

    /**
     * Retrieves the mapping to write to a packed bconf
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function getMapForPacking(array $addedCustomIdentifiers): array
    {
        $this->preparePacking($addedCustomIdentifiers);

        return $this->mapToPack;
    }

    /**
     * Retrieves the additional fprm files that need to be additionally injected (apart from the custom ones) into a packed BCONF
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function getOkapiDefaultFprmsForPacking(array $addedCustomIdentifiers): array
    {
        $this->preparePacking($addedCustomIdentifiers);

        return $this->fprmsToPack;
    }

    /**
     * Validates the extenion-mapping. Checks, there are only valid identifiers
     * and the custom identifiers exist in the DB and the filesystem
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    public function validate(): bool
    {
        $this->validationErrors = [];
        $filters = Filters::instance();
        $customFiltersFromDB = $this->bconf->findCustomFilterIdentifiers(); // thr custom identifiers in the DB
        foreach ($this->getAllFilters() as $identifier) {
            if (Filters::isOkapiDefaultIdentifier($identifier)) {
                if (! $filters->isValidOkapiDefaultFilter($identifier)) {
                    $this->validationErrors[] = 'Invalid OKAPI default filter "' . $identifier . '"';
                }
            } else {
                $idata = Filters::parseIdentifier($identifier);
                if ($filters->isEmbeddedOkapiDefaultFilter($idata->type, $idata->id)) {
                    $this->validationErrors[] =
                        'Embedded OKAPI default filter "' . $identifier . '" not allowed in extension-mapping';
                } elseif (! $filters->isEmbeddedTranslate5Filter($idata->type, $idata->id)) {
                    // custom filter, must exist in database & file-system
                    if (! in_array($identifier, $customFiltersFromDB)) {
                        $this->validationErrors[] =
                            'Embedded custom filter "' . $identifier . '" not found in the database';
                    }
                    $fprmPath = $this->bconf->createPath(FilterEntity::createFileFromIdentifier($identifier));
                    if (! file_exists($fprmPath)) {
                        $this->validationErrors[] =
                            'Embedded custom filter "' . $identifier . '" not found in the bconfs files';
                    }
                }
            }
        }

        return (count($this->validationErrors) === 0);
    }

    /**
     * Retrieves the error that caused the extension-mapping to be invalid
     */
    public function getValidationError(): string
    {
        return implode("\n", $this->validationErrors);
    }

    /**
     * Internal API to parse our contents from string (from filesystem)
     */
    private function parseContent(string $content)
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $parts = explode("\t", trim($line));
            if (count($parts) === 2) {
                $extension = ltrim(trim($parts[0]), '.');
                if (! empty($extension)) {
                    $this->map[$extension] = trim($parts[1]);
                }
            }
        }
    }

    /**
     * Generates the file-contents
     */
    private function createContent(array $map): string
    {
        $content = '';
        foreach ($map as $extension => $identifier) {
            $content .= '.' . $extension . self::SEPERATOR . $identifier . self::LINEFEED;
        }

        return rtrim($content, self::LINEFEED);
    }

    /**
     * Transforms our map to what really should be in an packed bconf
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    private function preparePacking(array $addedCustomIdentifiers)
    {
        if ($this->doDebug) {
            error_log('ExtensionMapping preparePacking: addedCustomIdentifiers: [ '
                . implode(', ', $addedCustomIdentifiers) . ' ]');
        }

        if ($this->mapToPack == null) {
            $this->mapToPack = [];
            $this->fprmsToPack = [];
            foreach ($this->map as $extension => $identifier) {
                if (Filters::isOkapiDefaultIdentifier($identifier)) {
                    // or it is a okapi default identifier which then needs to be added as explicit fprm
                    $path = Filters::instance()->getOkapiDefaultFilterPathById($identifier);
                    if ($path === null) {
                        throw new OkapiException('E1406', [
                            'bconf' => $this->bconf->getName(),
                            'bconfId' => $this->bconf->getId(),
                            'identifier' => $identifier,
                        ]);
                    } elseif ($path === false) {
                        $this->mapToPack[] = ['.' . $extension, $identifier];

                        if ($this->doDebug) {
                            error_log('ExtensionMapping preparePacking: add non embedded default filter '
                                . $identifier . ' for ' . $extension);
                        }
                    } else {
                        $identifier = Filters::createIdentifierFromPath($path);
                        $this->fprmsToPack[$identifier] = $path;
                        $this->mapToPack[] = ['.' . $extension, $identifier];

                        if ($this->doDebug) {
                            error_log('ExtensionMapping preparePacking: add embedded default filter '
                                . $identifier . ' for ' . $extension);
                        }
                    }
                } else {
                    if (in_array($identifier, $addedCustomIdentifiers)) {
                        // either the identifier is a custom filter and therefore must be part
                        // of the already added custom filters
                        $this->mapToPack[] = ['.' . $extension, $identifier];

                        if ($this->doDebug) {
                            error_log('ExtensionMapping preparePacking: add custom filter '
                                . $identifier . ' for ' . $extension);
                        }
                    } else {
                        // otherwise the entry must be a translate5 asjusted bconf or invalid
                        $idata = Filters::parseIdentifier($identifier);
                        $path = Filters::instance()->getTranslate5FilterPath($idata->type, $idata->id);
                        if (empty($path)) {
                            throw new OkapiException('E1406', [
                                'bconf' => $this->bconf->getName(),
                                'bconfId' => $this->bconf->getId(),
                                'identifier' => $identifier,
                            ]);
                        } else {
                            $identifier = Filters::createIdentifierFromPath($path);
                            $this->fprmsToPack[$identifier] = $path;
                            $this->mapToPack[] = ['.' . $extension, $identifier];

                            if ($this->doDebug) {
                                error_log('ExtensionMapping preparePacking: add embedded translate5 filter '
                                    . $identifier . ' for ' . $extension);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Updates the content-inventory, which is always done alongside the extension-mapping
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    private function updateBconfContent()
    {
        $content = $this->bconf->getContent();
        $content->setFilters($this->findCustomIdentifiers());
        $content->flush();
    }
}
