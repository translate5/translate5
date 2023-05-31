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
 * Class representing the Extension mapping of an Bconf
 * It is able to parse the extension-mapping from file, build an internal model, manipulate it and write it back to file
 * Also the mapping for the actual bconf-file can be retrieved where the okapi-default entries are actually mapped to the mapping files
 * The processing when unpacking bconfs is also implemented here, including writing the extracted custom fprms to the DB and file-system
 * The extension mapping is always stored as a filein a BCONFs file-repository with the name "extensions-mapping.txt"
 *
 * There are 4 types of filter-identifiers in a bconf:
 * OKAPI defaults: Identifiers like "okf_xml-AndroidStrings"; These names represent bconf-id's as used in Rainbow and Longhorn
 * OKAPI embedded defaults: Identifiers like "okf_xml@okf_xml-AndroidStrings"; These names have the formal bconf-type @ bconf-id. The id's are the ones used as OKAPI defaults.
 * translate5 adjusted defaults: Identifiers like "okf_xml@translate5-AndroidStrings"; Here the okapi-id always is "translate5" or srtarts with "translate5-"
 * User customized filters: Identifiers like "okf_xml@worldtranslation-my_special_setting"; Here the okapi-id has a special format: [ customerName or domain ] + "-" + [ websafe bconf name ]
 *
 * When packing BCONFs, the Okapi default filters will all be embedded into the BCONF to ensure maximal compatibility over time and between different Okapi-Versions
 * When unpacking BCONFs, that have embedded OKAPI defaults, the OKAPI embedded default identifiers will be reverted to simple OKAPI defaults
 * Only user customized filters will actually have FPRM-files in the BCONF's file store, all other types will always be taken from the git-based stores in translate5/application/modules/editor/Plugins/Okapi/data/fprm/
 */
class editor_Plugins_Okapi_Bconf_ExtensionMapping {

    /**
     * The filename an extension mapping generally has
     * @var string
     */
    const FILE = 'extensions-mapping.txt';

    /**
     * @var string
     */
    const INVALID_IDENTIFIER = 'INVALID';

    /**
     * @var ZfExtended_Logger|null
     */
    private static ?ZfExtended_Logger $logger = NULL;

    /**
     * Processes an embedded filter (fprm) when unpacking a bconf. The following cases can occur / are processed:
     * - an okapi-default filter is embedded => stays as it is
     * - an invalid okapi-default filter is embedded: simply is removed & warning written
     * - an embedded okapi-default filter is imported (e.g. by reimporting a bconf generated with T5): will be rewritten to a non-embedded default filter
     * - an embedded translate5 adjusted filter is imported: will be part of the extension-mapping but not saved to the file-system
     * - a custom embedded filter is imported: Will be saved to the file-system & written as custom filter to the DB. Existing custom filters are searched by hash for a name & description
     * if true is returned, the fprm needs to be saved to the bconfs data dir
     *
     * @param editor_Plugins_Okapi_Bconf_Entity $bconf
     * @param string $identifier
     * @param string $unpackedContent
     * @param array $replacementMap
     * @param array $customFilters
     * @return bool
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     */
    public static function processUnpackedFilter(editor_Plugins_Okapi_Bconf_Entity $bconf, string $identifier, string $unpackedContent, array &$replacementMap, array &$customFilters) : bool {
        $doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiExtensionMapping');
        // default-identifiers do not need to be embedded, we just check them
        if(editor_Plugins_Okapi_Bconf_Filters::isOkapiDefaultIdentifier($identifier)){
            // a non-embedded default-identifier that does not point to a valid OKAPI default filter will be removed & a warning written
            if(!editor_Plugins_Okapi_Bconf_Filters::instance()->isValidOkapiDefaultFilter($identifier)){
                $replacementMap[$identifier] = self::INVALID_IDENTIFIER;
                static::getLogger()->warn(
                    'E1407',
                    'Okapi Plug-In: The extension mapping of the bconf "{bconf}" contains an invalid filter identifier "{identifier}" which has been removed',
                    ['bconf' => $bconf->getName(), 'bconfId' => $bconf->getId(), 'identifier' => $identifier]);
                // DEBUG
                if($doDebug){ error_log('ExtensionMapping processUnpackedFilter: okapi default identifier '.$identifier.' is invalid'); }
            }
        } else {
            // process all identifiers pointing to a file
            $idata = editor_Plugins_Okapi_Bconf_Filters::parseIdentifier($identifier);
            if(editor_Plugins_Okapi_Bconf_Filters::instance()->isEmbeddedDefaultFilter($idata->type, $idata->id)){
                // when we import a embedded okapi default filter we map it to a non-embedded okapi default filter
                if(editor_Plugins_Okapi_Bconf_Filters::instance()->isEmbeddedOkapiDefaultFilter($idata->type, $idata->id)){
                    $replacementMap[$identifier] = $idata->id;
                    // DEBUG
                    if($doDebug){ error_log('ExtensionMapping processUnpackedFilter: replace '.$identifier.' with '.$idata->id); }
                }
            } else {
                // "real" custom filters, check if they have a supported type
                if(editor_Plugins_Okapi_Bconf_Filter_Okapi::isValidType($idata->type)){
                    // DEBUG
                    if($doDebug){ error_log('ExtensionMapping processUnpackedFilter: custom filter with identifier '.$identifier.' will be embedded'); }
                    // add a custom filter to the filesys & map (that later is flushed to the DB)
                    $fprmPath = $bconf->createPath(editor_Plugins_Okapi_Bconf_Filter_Entity::createFileFromIdentifier($identifier));
                    $fprm = new editor_Plugins_Okapi_Bconf_Filter_Fprm($fprmPath, $unpackedContent);
                    if($fprm->validate(true)){
                        $fprm->flush();
                        $customFilters[$identifier] = $fprm->getHash();
                        return true;
                    } else {
                        throw new ZfExtended_Exception($fprm->getValidationError());
                    }
                } else {
                    $replacementMap[$identifier] = self::INVALID_IDENTIFIER;
                    static::getLogger()->warn(
                        'E1407',
                        'Okapi Plug-In: The extension mapping of the bconf "{bconf}" contains an invalid filter identifier "{identifier}" which has been removed',
                        ['bconf' => $bconf->getName(), 'bconfId' => $bconf->getId(), 'identifier' => $identifier]);
                    // DEBUG
                    if($doDebug){ error_log('ExtensionMapping processUnpackedFilter: custom filter with identifier '.$identifier.' is invalid'); }
                }
            }
        }
        return false;
    }

    /**
     * @return ZfExtended_Logger
     * @throws Zend_Exception
     */
    private static function getLogger(){
        if(static::$logger == NULL){
            static::$logger = Zend_Registry::get('logger')->cloneMe('editor.okapi.bconf.filter');
        }
        return static::$logger;
    }

    /**
     * @var string
     */
    const LINEFEED = "\n";

    /**
     * @var string
     */
    const SEPERATOR = "\t";

    /**
     * @var string
     */
    const FILENAME = 'extensions-mapping.txt';

    /**
     * @var string
     */
    private string $path;

    /**
     * @var string
     */
    private string $dir;

    /**
     * @var array
     */
    private array $map = [];

    /**
     * @var array
     */
    private ?array $mapToPack = NULL;

    /**
     * When packing a bconf we add the okapi-defaults as real FPRMs. These will be cached here
     * @var array
     */
    private array $fprmsToPack;

    /**
     * Captures validatiopn errors during validation
     * @var array
     */
    private array $validationErrors;

    /**
     * @var bool
     */
    private bool $doDebug;

    /**
     * @var editor_Plugins_Okapi_Bconf_Entity
     */
    private editor_Plugins_Okapi_Bconf_Entity $bconf;

    /**
     * @param editor_Plugins_Okapi_Bconf_Entity $bconf
     * @param string|null $unpackedContent: must be set when unpacking a bconf
     * @param array $replacementMap: must be set when unpacking a bconf, represents the filters that have been mapped to a different identifier (e.g. filebased okapi-default back to a default identifier or default identifier to a translate5 adjuated one etc.)
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function __construct(editor_Plugins_Okapi_Bconf_Entity $bconf, ?array $unpackedLines=NULL, ?array $replacementMap=NULL){
        $this->path = $bconf->getExtensionMappingPath();
        $this->dir = rtrim(dirname($this->path), '/');
        $this->bconf = $bconf;
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiExtensionMapping');

        if(is_array($unpackedLines) && is_array($replacementMap)){
            // DEBUG
            if($this->doDebug){ error_log('ExtensionMapping construct by content:'."\n".print_r($unpackedLines, 1)."\n".' replacementMap:'."\n".print_r($replacementMap, 1)); }
            // importing an unpacked bconf
            $this->unpackContent($unpackedLines, $replacementMap);

        } else {
            // DEBUG
            if($this->doDebug){ error_log('ExtensionMapping construct by path: '.$this->path); }
            // opening a mapping from the file-system
            $content = NULL;
            if(file_exists($this->path) && is_writable($this->path)){
                $content = file_get_contents($this->path);
            }
            if(empty($content)){
                throw new ZfExtended_Exception('editor_Plugins_Okapi_Bconf_ExtensionMapping can only be instantiated for an existing extension-mapping file ('.$this->path.')');
            }
            $this->parseContent($content);
            if(!$this->hasEntries()){
                throw new editor_Plugins_Okapi_Exception('E1405', ['bconf' => $bconf->getName(), 'bconfId' => $bconf->getId()]);
            }
        }
    }

    /**
     * @return string
     */
    public function getPath() : string {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getMap() : array {
        return $this->map;
    }

    /**
     * @return string[]
     */
    public function getAllFilters() : array {
        $filters = [];
        foreach($this->map as $extension => $identifier){
            $filters[$identifier] = true;
        }
        return array_keys($filters);
    }

    /**
     * @return array
     */
    public function getAllExtensions() : array {
        return array_keys($this->map);
    }

    /**
     * @param string $extension
     * @return bool
     */
    public function hasExtension(string $extension) : bool {
        return array_key_exists(ltrim($extension, '.'), $this->map);
    }

    /**
     * @return bool
     */
    public function hasEntries() : bool {
        return (count($this->map) > 0);
    }

    /**
     * Retrieves the related bconf id
     * @return int
     */
    public function getBconfId() : int {
        return $this->bconf->getId();
    }

    /**
     * Generates the frontend-model for the extension mapping, where identifier => extensions
     * The non-embedded default identifiers like 'okf_xml-AndroidStrings' will be turned to embedded ones like 'okf_xml@okf_xml-AndroidStrings'
     * @return array
     * @throws ZfExtended_Exception
     */
    public function getIdentifierMap() : array {
        $items = [];
        // map extensions to filters
        foreach($this->map as $extension => $identifier){
            if(!array_key_exists($identifier, $items)){
                $items[$identifier] = [];
            }
            $items[$identifier][] = $extension;
        }
        // generate Frontend data model
        $jsonData = [];
        foreach($items as $identifier => $extensions){
            if(editor_Plugins_Okapi_Bconf_Filters::isOkapiDefaultIdentifier($identifier)){
                $okapiType = editor_Plugins_Okapi_Bconf_Filters::instance()->getOkapiDefaultFilterTypeById($identifier);
                if($okapiType === NULL){
                    // theoretically this can not happen
                    error_log('INVALID non-embedded okapi default filter entry in extension mapping '.$this->path.' for bconf '.$this->bconf->getId());
                } else {
                    $jsonData[editor_Plugins_Okapi_Bconf_Filters::createIdentifier($okapiType, $identifier)] = $extensions;
                }
            } else {
                $jsonData[$identifier] = $extensions;
            }

        }
        // DEBUG
        if($this->doDebug){ error_log('ExtensionMapping toJSON: '."\n".print_r($jsonData, 1)); }

        return $jsonData;
    }

    /**
     * Retrieves the extensions a filter has
     * @param string $identifier
     * @return string[]
     */
    public function findExtensionsForFilter(string $identifier) : array {
        // this api is agnostic to passed embedded okapi default filters
        $defaultIdentifier = editor_Plugins_Okapi_Bconf_Filters::createOkapiDefaultIdentifier($identifier);
        $extensions = [];
        foreach($this->map as $extension => $filter){
            if($filter === $identifier || ($defaultIdentifier !== NULL && $filter === $defaultIdentifier)){
                $extensions[] = $extension;
            }
        }
        return $extensions;
    }

    /**
     * @return array
     * @throws ZfExtended_Exception
     */
    public function findCustomIdentifiers(){
        $customIdentifiers = [];
        foreach($this->map as $extension => $filter){
            if(editor_Plugins_Okapi_Bconf_Filters::instance()->isCustomFilter($filter)){
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
     * @param array $extensions
     * @return bool
     */
    public function removeExtensions(array $extensions) : bool {
        // DEBUG
        if($this->doDebug){ error_log('ExtensionMapping removeExtensions: [ '.implode(', ', $extensions).' ]'); }

        $newMap = [];
        $removed = false;
        foreach($this->map as $extension => $filter){
            if(in_array($extension, $extensions)){
                $removed = true;
            } else {
                $newMap[$extension] = $filter;
            }
        }
        if($removed){
            $this->map = $newMap;
            $this->flush();
            $this->updateBconfContent();
            // DEBUG
            if($this->doDebug){ error_log('ExtensionMapping removeExtensions: [ '.implode(', ', $extensions).' ] have been removed'."\n".print_r($this->map, 1)); }
        }
        return $removed;
    }

    /**
     * Removes a filter from the mapping and deletes the corresponding fprm-file anf flushes the map if changed
     * Also updates the related content-file
     * @param string $identifier
     * @return bool
     */
    public function removeFilter(string $identifier) : bool {
        // this api is agnostic to passed embedded okapi default filters
        $defaultIdentifier = editor_Plugins_Okapi_Bconf_Filters::createOkapiDefaultIdentifier($identifier);
        // DEBUG
        if($this->doDebug){ error_log('ExtensionMapping removeFilter: '.$identifier.($defaultIdentifier ? ' || '.$defaultIdentifier : '')); }

        $newMap = [];
        $removed = false;
        foreach($this->map as $extension => $filter){
            if($filter === $identifier || ($defaultIdentifier !== NULL && $filter === $defaultIdentifier)) {
                $removed = true;
            } else {
                $newMap[$extension] = $filter;
            }
        }
        if($removed){
            $this->map = $newMap;
            if(file_exists($this->dir.'/'.$identifier.'.'.editor_Plugins_Okapi_Bconf_Filter_Entity::EXTENSION)){
                @unlink($this->dir.'/'.$identifier.'.'.editor_Plugins_Okapi_Bconf_Filter_Entity::EXTENSION);
            }
            $this->flush();
            $this->updateBconfContent();
            // DEBUG
            if($this->doDebug){ error_log('ExtensionMapping removeFilter: '.$identifier.' has been removed'."\n".print_r($this->map, 1)); }
        }
        return $removed;
    }

    /**
     * Merges all Translate5 specific extensions in
     * Usually, this will only be applied to the T5 default bconf ...
     * @return bool: If the mapping was changed due to the sync
     * @throws ZfExtended_Exception
     */
    public function complementTranslate5Extensions() : bool
    {
        $translate5Mapping = editor_Plugins_Okapi_Bconf_Filter_Translate5::instance()->getExtensionMappingEntries();
        // DEBUG
        if($this->doDebug){ error_log('ExtensionMapping syncTranslate5Extensions: '.print_r($translate5Mapping, true)); }

        $changed = false;
        foreach($translate5Mapping as $extension => $identifier){
            if(!array_key_exists($extension, $this->map) || $this->map[$extension] != $identifier){
                $this->map[$extension] = $identifier;
                $changed = true;
            }
        }
        if($changed){
            $this->flush();
            $this->updateBconfContent();
            // DEBUG
            if($this->doDebug){ error_log('ExtensionMapping syncTranslate5Extensions: mapping has been changed: '."\n".print_r($this->map, 1)); }
        }
        return $changed;
    }

    /**
     * Adds a filter with it's extensions
     * Also updates the related content-file
     * @param string $identifier
     * @param array $extensions
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function addFilter(string $identifier, array $extensions) : bool {
        // DEBUG
        if($this->doDebug){ error_log('ExtensionMapping addFilter: '.$identifier.', extensions: [ '.implode(', ', $extensions).' ]'); }
        // we simply use the change API, but it seems resonable to have an explicit add function
        return $this->changeFilter($identifier, $extensions);
    }

    /**
     * Changes a filter with it's extensions
     * Also updates the related content-file
     * @param string $identifier
     * @param array $extensions
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function changeFilter(string $identifier, array $extensions) : bool {
        // this api is agnostic to passed embedded okapi default filters
        $defaultIdentifier = editor_Plugins_Okapi_Bconf_Filters::createOkapiDefaultIdentifier($identifier);
        // DEBUG
        if($this->doDebug){ error_log('ExtensionMapping changeFilter: '.$identifier.($defaultIdentifier ? ' || '.$defaultIdentifier : '').', extensions: [ '.implode(', ', $extensions).' ]'); }

        $newMap = [];
        $extToAdd = $extensions;
        $extBefore = [];
        $changed = false;
        foreach($this->map as $extension => $filter){
            if($filter === $identifier || ($defaultIdentifier !== NULL && $filter === $defaultIdentifier)){
                $extBefore[] = $extension;
                if(count($extToAdd) > 0){
                    $newMap[array_shift($extToAdd)] = $filter;
                } else {
                    $changed = true;
                }
            } else if(in_array($extension, $extensions)){
                $changed = true;
            } else {
                $newMap[$extension] = $filter;
            }
        }
        // append extensions that could not be distributed to existing entries for $identifier
        if(count($extToAdd) > 0){
            // in the map on disk we always use default identifiers
            $filter = ($defaultIdentifier === NULL) ? $identifier : $defaultIdentifier;
            foreach($extToAdd as $extension){
                $newMap[$extension] = $filter;
            }
            $changed = true;
        }
        // we must capture the case where different extensions of the same amount have been set, this cannot be evaluated with the logic above
        if(!$changed){
            $changed = (count(array_diff($extBefore, $extensions)) !== 0);
        }
        if($changed){
            $this->map = $newMap;
            $this->flush();
            $this->updateBconfContent();
            // DEBUG
            if($this->doDebug){ error_log('ExtensionMapping changeFilter: '.$identifier.' with extensions [ '.implode(', ', $extensions).' ] has been changed: '."\n".print_r($this->map, 1)); }
        }
        return $changed;
    }

    /**
     * re-scans an existing bconf and brings the database in-sync with the filesystem
     * This is to support the initial rollout
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_BadMethodCallException
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function rescanFilters(){
        // DEBUG
        if($this->doDebug){ error_log('ExtensionMapping rescanFilters'); }
        // evaluate existing filters from db
        $bconfFilter = new editor_Plugins_Okapi_Bconf_Filter_Entity();
        $existingFilters = [];
        $existingNames = [];
        $allFilters = [];
        foreach($bconfFilter->getRowsByBconfId($this->bconf->getId()) as $filterData){
            $identifier = editor_Plugins_Okapi_Bconf_Filters::createIdentifier($filterData['okapiType'], $filterData['okapiId']);
            $existingFilters[$identifier] = $filterData['id'];
            $existingNames[] = $filterData['name'];
        }
        // evaluate filters in filesystem & add those not present in the database
        $dir = $this->bconf->getDataDirectory();
        $filterFiles = glob($dir.'/*.fprm');
        foreach($filterFiles as $filterFile){
            $identifier = editor_Plugins_Okapi_Bconf_Filters::createIdentifierFromPath($filterFile);
            $idata = editor_Plugins_Okapi_Bconf_Filters::parseIdentifier($identifier);
            if(editor_Plugins_Okapi_Bconf_Filters::instance()->isEmbeddedDefaultFilter($idata->type, $idata->id)){
                // translate5 and okapi filters must not be in the custom filter dir
                @unlink($filterFile);
                // DEBUG
                if($this->doDebug){ error_log('ExtensionMapping rescanFilters: deleted embedded default filter '.$identifier); }
            } else {
                // if a filter is not in the DB we must do so
                if(!array_key_exists($identifier, $existingFilters)){
                    $fprm = new editor_Plugins_Okapi_Bconf_Filter_Fprm($filterFile);
                    $this->flushFilterToDatabase($identifier, $fprm->getHash(), $existingNames);
                    // DEBUG
                    if($this->doDebug){ error_log('ExtensionMapping rescanFilters: added custom filter to DB '.$identifier); }
                }
                $allFilters[] = $identifier;
            }
         }
        // remove the filters that exist in the database but have no corresponding file
        foreach($existingFilters as $identifier => $id){
            if(!in_array($identifier, $allFilters)){
                $bconfFilter->load($id);
                $bconfFilter->delete();
                // DEBUG
                if($this->doDebug){ error_log('ExtensionMapping rescanFilters: deleted non-existing filter '.$identifier.' from database'); }
            }
        }
        // finally: adjust the content.json
        $content = $this->bconf->getContent();
        $content->setFilters($allFilters);
        $content->flush();
        // DEBUG
        if($this->doDebug){ error_log('ExtensionMapping rescanFilters: The bconf "'.$this->bconf->getName().'" has '.count($allFilters).' custom filters: [ '.implode(', ', $allFilters).' ]'); }
    }

    /**
     * Adds afilter to the database
     * @param string $identifier
     * @param string $hash
     * @param array $usedNames
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_BadMethodCallException
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    private function flushFilterToDatabase(string $identifier, string $hash, array &$usedNames) {
        // evaluate name & description from a filter that already exists in the DB
        $idata = editor_Plugins_Okapi_Bconf_Filters::parseIdentifier($identifier);
        $hashedEntity = new editor_Plugins_Okapi_Bconf_Filter_Entity();
        try{
            $hashedEntity->loadByTypeAndHash($idata->type, $hash);
            $name = $hashedEntity->getName();
            $description = $hashedEntity->getDescription();
        } catch (ZfExtended_Models_Entity_NotFoundException $e){
            // revert secure filename to a somewhat human readable name, remove trailing counters like '-4'
            $name = preg_replace('/\-[0-9]+$/', '', $idata->id);
            // revert secure filename to a somewhat human readable name
            $name = ucfirst(str_replace('_', ' ', $name));
            if($name == ''){ // just to be sure ...
                $name = $idata->id;
            }
            $description = '';
        }
        // we must guarantee unique names
        $baseName = $name;
        $count = 0;
        while(in_array($name, $usedNames)){
            $count++;
            $name = $baseName.' '.$count;
        }
        $this->bconf->addCustomFilterEntry(
            $idata->type,
            $idata->id,
            $name,
            $description,
            $hash
        );
        $usedNames[] = $name;
        // DEBUG
        if($this->doDebug){ error_log('ExtensionMapping flushFilterToDatabase: added '.$identifier.', "'.$name.'" to database'); }
    }

    /**
     * writes back an extension-mapping to the filesystem
     */
    public function flush() {
        file_put_contents($this->path, $this->createContent($this->map));
    }

    /**
     * saves the passed filters ('identifier' => 'hash') to the database
     * This is the follow-up to processUnpackedFilter, in a bconf, the filters are encoded before the mapping-file so the processing is "in the wrong direction"
     * @param array $customFilters
     */
    public function flushUnpacked(array $customFilters){
        $usedNames = [];
        foreach($customFilters as $identifier => $hash){
            $this->flushFilterToDatabase($identifier, $hash, $usedNames);
        }
        $this->flush();
    }

    /**
     * Retrieves the mapping to write to a packed bconf
     * @param array $addedCustomIdentifiers: these are the identifiers that have already been added to the packed bconf (just the identifiers, not the pathes)
     * @return array
     */
    public function getMapForPacking(array $addedCustomIdentifiers) : array {
        $this->preparePacking($addedCustomIdentifiers);
        return $this->mapToPack;
    }

    /**
     * Retrieves the additional fprm files that need to be additionally injected (apart from the custom ones) into a packed BCONF
     * @param array $addedCustomIdentifiers: these are the identifiers that have already been added to the packed bconf (just the identifiers, not the pathes)
     * @return array
     */
    public function getOkapiDefaultFprmsForPacking(array $addedCustomIdentifiers) : array {
        $this->preparePacking($addedCustomIdentifiers);
        return $this->fprmsToPack;
    }

    /**
     * Validates the extenion-mapping. Checks, there are only valid identifiers and the custom identifiers exist in the DB and the filesystem
     * @return bool
     */
    public function validate() : bool {
        $this->validationErrors = [];
        $filters = editor_Plugins_Okapi_Bconf_Filters::instance();
        $customFiltersFromDB = $this->bconf->findCustomFilterIdentifiers(); // thr custom identifiers in the DB
        foreach($this->getAllFilters() as $identifier){
            if(editor_Plugins_Okapi_Bconf_Filters::isOkapiDefaultIdentifier($identifier)){
                if(!$filters->isValidOkapiDefaultFilter($identifier)){
                    $this->validationErrors[] = 'Invalid OKAPI default filter "'.$identifier.'"';
                }
            } else {
                $idata = editor_Plugins_Okapi_Bconf_Filters::parseIdentifier($identifier);
                if($filters->isEmbeddedOkapiDefaultFilter($idata->type, $idata->id)){
                    $this->validationErrors[] = 'Embedded OKAPI default filter "'.$identifier.'" not allowed in extension-mapping';
                } else if(!$filters->isEmbeddedTranslate5Filter($idata->type, $idata->id)){
                    // custom filter, must exist in database & file-system
                    if(!in_array($identifier, $customFiltersFromDB)){
                        $this->validationErrors[] = 'Embedded custom filter "'.$identifier.'" not found in the database';
                    }
                    $fprmPath = $this->bconf->createPath(editor_Plugins_Okapi_Bconf_Filter_Entity::createFileFromIdentifier($identifier));
                    if(!file_exists($fprmPath)){
                        $this->validationErrors[] = 'Embedded custom filter "'.$identifier.'" not found in the bconfs files';
                    }
                }
            }
        }
        return (count($this->validationErrors) === 0);
    }

    /**
     * Retrieves the error that caused the extension-mapping to be invalid
     * @return string
     */
    public function getValidationError() : string {
        return implode("\n", $this->validationErrors);
    }

    /**
     * Internal API to parse our contents from string (from filesystem)
     * @param string $content
     */
    private function parseContent(string $content){
        $lines = explode("\n", $content);
        foreach($lines as $line){
            $parts = explode("\t", trim($line));
            if(count($parts) === 2){
                $this->map[ltrim(trim($parts[0]), '.')] = trim($parts[1]);
            }
        }
    }

    /**
     * Internal API to parse our contents from unpacked bconf data (on import)
     * @param string $content
     */
    private function unpackContent(array $unpackedLines, array $replacementMap){
        foreach($unpackedLines as $line){
            $identifier = array_key_exists($line[1], $replacementMap) ? $replacementMap[$line[1]] : $line[1];
            if($identifier != self::INVALID_IDENTIFIER){
                $this->map[ltrim($line[0], '.')] = $identifier;
            }
        }
    }

    /**
     * Generates the file-contents
     * @param array $map
     * @return string
     */
    private function createContent(array $map) {
        $content = '';
        foreach($map as $extension => $identifier){
            $content .= '.'.$extension . self::SEPERATOR . $identifier . self::LINEFEED;
        }
        return rtrim($content, self::LINEFEED);
    }
    /**
     * Transforms our map to what really should be in an packed bconf
     * @param array $addedCustomIdentifiers
     */
    private function preparePacking(array $addedCustomIdentifiers){
        // DEBUG
        if($this->doDebug){ error_log('ExtensionMapping preparePacking: addedCustomIdentifiers: [ '.implode(', ', $addedCustomIdentifiers).' ]'); }

        if($this->mapToPack == NULL){
            $this->mapToPack = [];
            $this->fprmsToPack = [];
            foreach($this->map as $extension => $identifier){
                if(editor_Plugins_Okapi_Bconf_Filters::isOkapiDefaultIdentifier($identifier)){
                    // or it is a okapi default identifier which then needs to be added as explicit fprm
                    $path = editor_Plugins_Okapi_Bconf_Filters::instance()->getOkapiDefaultFilterPathById($identifier);
                    if($path === NULL) {
                        throw new editor_Plugins_Okapi_Exception('E1406', ['bconf' => $this->bconf->getName(), 'bconfId' => $this->bconf->getId(), 'identifier' => $identifier]);
                    } else if($path === false){
                        $this->mapToPack[] = [ '.'.$extension, $identifier ];
                        // DEBUG
                        if($this->doDebug){ error_log('ExtensionMapping preparePacking: add non embedded default filter '.$identifier.' for '.$extension); }
                    } else {
                        $identifier = editor_Plugins_Okapi_Bconf_Filters::createIdentifierFromPath($path);
                        $this->fprmsToPack[$identifier] = $path;
                        $this->mapToPack[] = [ '.'.$extension, $identifier ];
                        // DEBUG
                        if($this->doDebug){ error_log('ExtensionMapping preparePacking: add embedded default filter '.$identifier.' for '.$extension); }
                    }
                } else {
                    if(in_array($identifier, $addedCustomIdentifiers)){
                        // either the identifier is a custom filter and therefore must be part of the already added custom filters
                        $this->mapToPack[] = [ '.'.$extension, $identifier ];
                        // DEBUG
                        if($this->doDebug){ error_log('ExtensionMapping preparePacking: add custom filter '.$identifier.' for '.$extension); }
                    } else {
                        // otherwise the entry must be a translate5 asjusted bconf or invalid
                        $idata = editor_Plugins_Okapi_Bconf_Filters::parseIdentifier($identifier);
                        $path = editor_Plugins_Okapi_Bconf_Filters::instance()->getTranslate5FilterPath($idata->type, $idata->id);
                        if(empty($path)){
                            throw new editor_Plugins_Okapi_Exception('E1406', ['bconf' => $this->bconf->getName(), 'bconfId' => $this->bconf->getId(), 'identifier' => $identifier]);
                        } else {
                            $identifier = editor_Plugins_Okapi_Bconf_Filters::createIdentifierFromPath($path);
                            $this->fprmsToPack[$identifier] = $path;
                            $this->mapToPack[] = [ '.'.$extension, $identifier ];
                            // DEBUG
                            if($this->doDebug){ error_log('ExtensionMapping preparePacking: add embedded translate5 filter '.$identifier.' for '.$extension); }
                        }
                    }
                }
            }
        }
    }

    /**
     * Updates the content-inventory, which is always done alongside the extension-mapping
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    private function updateBconfContent(){
        $content =  $this->bconf->getContent();
        $content->setFilters($this->findCustomIdentifiers());
        $content->flush();
    }
}
