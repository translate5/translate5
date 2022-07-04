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
 *
 * Types of filter-identifiers in a bconf
 */
class editor_Plugins_Okapi_Bconf_ExtensionMapping {

    /**
     * The filename an extension mapping generaly has
     * @var sring
     */
    const FILE = 'extensions-mapping.txt';

    /**
     * @var sring
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
        // default-identifiers do not need to be embedded, we just check them
        if(editor_Plugins_Okapi_Bconf_Filters::isOkapiDefaultIdentifier($identifier)){
            // a non-embedded default-identifier that does not point to a valid OKAPI default filter will be removed & a warning written
            if(!editor_Plugins_Okapi_Bconf_Filters::instance()->isValidOkapiDefaultFilter($identifier)){
                $replacementMap[$identifier] = self::INVALID_IDENTIFIER;
                static::getLogger()->warn(
                    'E1407',
                    'Okapi Plug-In: The extension mapping of the bconf "{bconf}" contains an invalid filter identifier "{identifier}" which has been removed',
                    ['bconf' => $bconf->getName(), 'bconfId' => $bconf->getId(), 'identifier' => $identifier]);
            }
        } else {
            // process all identifiers pointing to a file
            $idata = editor_Plugins_Okapi_Bconf_Filters::parseIdentifier($identifier);
            if(editor_Plugins_Okapi_Bconf_Filters::instance()->isEmbeddedDefaultFilter($idata->type, $idata->id)){
                // when we import a embedded okapi default filter we map it to a non-embedded okapi default filter
                if(editor_Plugins_Okapi_Bconf_Filters::instance()->isEmbeddedOkapiDefaultFilter($idata->type, $idata->id)){
                    $replacementMap[$identifier] = $idata->id;
                }
            } else {
                // "real" custom filters, check if they have a supported type
                if(editor_Plugins_Okapi_Bconf_Filter_Okapi::isValidType($idata->type)){
                    // add a custom filter to the filesys & map (that later is flushed to the DB)
                    $customFilters[$identifier] = md5($unpackedContent);
                    return true;
                } else {
                    $replacementMap[$identifier] = self::INVALID_IDENTIFIER;
                    static::getLogger()->warn(
                        'E1407',
                        'Okapi Plug-In: The extension mapping of the bconf "{bconf}" contains an invalid filter identifier "{identifier}" which has been removed',
                        ['bconf' => $bconf->getName(), 'bconfId' => $bconf->getId(), 'identifier' => $identifier]);
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

        if(is_array($unpackedLines) && is_array($replacementMap)){
            // importing an unpacked bconf
            $this->unpackContent($unpackedLines, $replacementMap);

        } else {
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
     * Updates a mapping with an adjusted content
     * @param string $content
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function updateByContent(string $content) {
        $this->parseContent($content);
        if(!$this->hasEntries()){
            throw new editor_Plugins_Okapi_Exception('E1405', ['bconf' => $this->bconf->getName(), 'bconfId' => $this->bconf->getId()]);
        }
        $this->flush();
    }

    /**
     * @return bool
     */
    public function hasEntries() : bool {
        return (count($this->map) > 0);
    }

    /**
     * Retrieves the extensions a filter has
     * @param string $identifier
     * @return string[]
     */
    public function findExtensionsForFilter(string $identifier) : array {
        $extensions = [];
        foreach($this->map as $extension => $fi){
            if($fi === $identifier){
                $extensions[] = $extension;
            }
        }
        return $extensions;
    }

    /**
     * Removes a filter from the mapping and deletes the corresponding fprm-file
     * @param string $identifier
     * @return bool
     */
    public function removeFilter(string $identifier) : bool {
        $map = [];
        $removed = false;
        foreach($this->map as $extension => $fi){
            if($fi === $identifier) {
                $removed = true;
            } else {
                $map[$extension] = $fi;
            }
        }
        if($removed){
            $this->map = $map;
            if(file_exists($this->dir.'/'.$identifier.'.'.editor_Plugins_Okapi_Bconf_Filter_Entity::EXTENSION)){
                @unlink($this->dir.'/'.$identifier.'.'.editor_Plugins_Okapi_Bconf_Filter_Entity::EXTENSION);
            }
        }
        return $removed;
    }

    /**
     * writes back an extension-mapping to the filesystem
     */
    public function flush() {
        if(empty($this->path)){
            throw new ZfExtended_Exception('editor_Plugins_Okapi_Bconf_ExtensionMapping::flush can not be called for packing clones');
        }
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
        // evaluate existing filters from db
        $bconfFilter = new editor_Plugins_Okapi_Bconf_Filter_Entity();
        $existingFilters = [];
        $existingNames = [];
        $filesysFilters = [];
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
            if(!array_key_exists($identifier, $existingFilters)){
                $hash = md5(file_get_contents($dir.'/'.$filterFile));
                $this->flushFilterToDatabase($identifier, $hash, $existingNames);
            }
            $filesysFilters[$identifier] = $identifier;
        }
        // remove the filters that exist in the database but have no corresponding file
        foreach($existingFilters as $identifier => $id){
            if(!array_key_exists($identifier, $filesysFilters)){
                $bconfFilter->load($filesysFilters[$identifier]);
                $bconfFilter->delete();
            }
        }
    }

    /**
     * Adds afilter to the database
     * @param string $identifier
     * @param string $hash
     * @param array $usedNames
     * @return int
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
            $name = ucfirst(str_replace('_', ' ', rtrim($idata->id, '1234567890-_')));
            $description = '';
        }
        // we must guarantee unique names
        $baseName = $name;
        $count = 0;
        while(in_array($name, $usedNames)){
            $count++;
            $name = $baseName.' '.$count;
        }
        $extensions = $this->findExtensionsForFilter($identifier);
        $this->bconf->addCustomFilterEntry(
            $idata->type,
            $idata->id,
            $name,
            $description,
            $extensions,
            $hash
        );
        $usedNames[] = $name;
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
                    } else {
                        $identifier = editor_Plugins_Okapi_Bconf_Filters::createIdentifierFromPath($path);
                        $this->fprmsToPack[$identifier] = $path;
                        $this->mapToPack[] = [ '.'.$extension, $identifier ];
                    }
                } else {
                    if(in_array($identifier, $addedCustomIdentifiers)){
                        // either the identifier is a custom filter and therefore must be part of the already added custom filters
                        $this->mapToPack[] = [ '.'.$extension, $identifier ];
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
                        }
                    }
                }
            }
        }
    }
}
