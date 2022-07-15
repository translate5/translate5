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
 * Class representing the Filters a bconf can have
 * These consist of okapi default-filters, translate5-adjusted filters and the user customized filters from the database
 */
class editor_Plugins_Okapi_Bconf_Filters {

    /**
     * General seperator in the OKAPI filter naming scheme
     * @var string
     */
    const IDENTIFIER_SEPERATOR = '@';

    /**
     * All Filters that have a GUI must be defined here
     * Each filter must define extensions that can be tested alongside the filter. These files must exist in /application/modules/editor/Plugins/Okapi/data/$self::TESTFILE_FOLDER just as described with TESTABLE_EXTENSIONS
     * @var string[]
     */
    const GUIS = [
        'okf_html' => ['html'],
        'okf_xml' => ['xml'],
        'okf_xmlstream' => ['xml'],
        /*
        'okf_icml' => [], // TODO: a testfile is required for any filter-GUI
        'okf_idml' => ['idml'],
        */
        'okf_openxml' => ['docx', 'pptx', 'xlsx']
    ];
    /**
     * A list of file-extensions, that validation files exist for.
     * This files exist in /application/modules/editor/Plugins/Okapi/data/$self::TESTFILE_FOLDER and are all called "test.$EXTENSION"
     * For each extension here a file must exist, the language must be english / en-GB
     */
    const TESTABLE_EXTENSIONS = ['txt', 'xml', 'strings', 'csv', 'htm', 'html', 'sdlxliff', 'docx', 'odp', 'ods', 'odt', 'pptx', 'tbx', 'xlsx', 'idml'];

    /**
     *
     * @var string
     */
    const TESTFILE_FOLDER = 'testfiles';

    /**
     * Used for testing/validating bconfs
     * English / en-GB
     * @var int
     */
    const SOURCE_LANGUAGE = 5;

    /**
     * sed for testing/validating bconfs
     * German / de-DE
     * @var int
     */
    const TARGET_LANGUAGE = 4;

    /**
     * @var editor_Plugins_Okapi_Bconf_Filters|null
     */
    private static ?editor_Plugins_Okapi_Bconf_Filters $_instance = NULL;

    /**
     * Evaluates if a filter has aExtJS GUI to be edited with
     * @param string $filterType
     * @return bool
     */
    public static function hasGui(string $filterType) : bool {
        return array_key_exists($filterType, self::GUIS);
    }

    /**
     * Small helper to create the filter-gui classname out of the filter type
     * Optionally defines the full ExtJS path
     * Returns an empty string when no Gui defined
     * @param string $filterType
     * @return string
     */
    public static function getGuiClass(string $filterType, bool $fullPath=true) : string {
        if(!self::hasGui($filterType)){
            return '';
        }
        $name = ucfirst(substr($filterType, 4));
        if($fullPath){
            return 'Editor.plugins.Okapi.view.fprm.'.$name;
        }
        return $name;
    }

    /**
     * Evaluates, if the identifier represents an okapi default identifier (an identier that does not point to a fprm embedded in the bconf)
     * @param string $identifier
     * @return bool
     */
    public static function isOkapiDefaultIdentifier(string $identifier) : bool {
        return !str_contains($identifier, self::IDENTIFIER_SEPERATOR);
    }

    /**
     * Retrieves the non-embedded counterpart for an embedded okapi-default identifier, eg. "okf_plaintext_regex_paragraphs" for "okf_plaintext@okf_plaintext_regex_paragraphs"
     * @param string $identifier
     * @return string|null
     * @throws ZfExtended_Exception
     */
    public static function createOkapiDefaultIdentifier(string $identifier) : ?string {
        if(!self::isOkapiDefaultIdentifier($identifier)){
            $idata = self::parseIdentifier($identifier);
            if(self::instance()->isEmbeddedOkapiDefaultFilter($idata->type, $idata->id)){
                return $idata->id;
            }
        }
        return NULL;
    }
    /**
     * Parses an identifier that is part of the bconf file
     * @param string $identifier
     * @return stdClass
     * @throws ZfExtended_Exception
     */
    public static function parseIdentifier(string $identifier) : stdClass {
        $parts = explode('@', $identifier);
        if(count($parts) !== 2 || substr($parts[0], 0, 4) !== 'okf_') {
            throw new ZfExtended_Exception('OKAPI FPRM identifier '.$identifier.' is not valid');
        }
        $result = new stdClass();
        $result->type = $parts[0];
        $result->id = $parts[1];
        return $result;
    }

    /**
     * Creates an identifier out of okapiType and okapiId
     * @param string $okapiType
     * @param string $okapiId
     * @return string
     */
    public static function createIdentifier(string $okapiType, string $okapiId) : string {
        return $okapiType.self::IDENTIFIER_SEPERATOR.$okapiId;
    }

    /**
     * Creates an identifier out of a path to a fprm file
     * @param string $fprmPath
     * @return string
     */
    public static function createIdentifierFromPath(string $fprmPath) : string {
        return pathinfo($fprmPath, PATHINFO_FILENAME);
    }

    /**
     * Creates the path of a testfile in the testfile-folder
     * @param string $testFile
     * @return string
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public static function createTestfilePath(string $testFile) : string {
        return editor_Plugins_Okapi_Init::getDataDir().self::TESTFILE_FOLDER.'/'.$testFile;
    }

    /**
     * @return editor_Plugins_Okapi_Bconf_Filters
     */
    public static function instance() : editor_Plugins_Okapi_Bconf_Filters {
        if(self::$_instance == NULL){
            self::$_instance = new editor_Plugins_Okapi_Bconf_Filters();
        }
        return self::$_instance;
    }

    /**
     * @var editor_Plugins_Okapi_Bconf_Filter_Okapi
     */
    private editor_Plugins_Okapi_Bconf_Filter_Okapi $okapiFilters;

    /**
     * @var editor_Plugins_Okapi_Bconf_Filter_Translate5
     */
    private editor_Plugins_Okapi_Bconf_Filter_Translate5 $translate5Filters;

    protected function __construct(){
        $this->okapiFilters = editor_Plugins_Okapi_Bconf_Filter_Okapi::instance();
        $this->translate5Filters = editor_Plugins_Okapi_Bconf_Filter_Translate5::instance();
    }

    /**
     * @param string $type
     * @param string $id
     * @return bool
     */
    public function isValidOkapiDefaultFilter(string $identifier) : bool {
        if(str_contains($identifier, self::IDENTIFIER_SEPERATOR)){
            return false;
        }
        return (count($this->okapiFilters->findFilter(NULL, $identifier)) === 1);
    }

    /**
     * Checks, whether the $identifier is a default identifier, either OKAPI default, OKAPI embedded default or translate5 adjusted default
     * @param string $type
     * @param string $id
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function isEmbeddedDefaultFilter(string $type, string $id) : bool {
        if(editor_Plugins_Okapi_Bconf_Filter_Translate5::isTranslate5Id($id)){
            return $this->isEmbeddedTranslate5Filter($type, $id);
        } else {
            return $this->isEmbeddedOkapiDefaultFilter($type, $id);
        }
    }

    /**
     * @param string $type
     * @param string $id
     * @return bool
     */
    public function isEmbeddedOkapiDefaultFilter(string $type, string $id) : bool {
        $filters = $this->okapiFilters->findFilter($type, $id);
        return(count($filters) > 0);
    }

    /**
     * @param string $type
     * @param string $id
     * @return bool
     */
    public function isEmbeddedTranslate5Filter(string $type, string $id) : bool {
        $filters = $this->translate5Filters->findFilter($type, $id);
        return(count($filters) > 0);
    }

    /**
     * Finds the fprm path for an OKAPI default filter
     * Note that this might actually return a tranlate5 adjusted filter in case it is a replacing filter
     * This API will return NULL for a filter that could not be found and false for filters that do not support a settings file
     * @param $filterId
     * @return string|null|bool
     * @throws ZfExtended_Exception
     */
    public function getOkapiDefaultFilterPathById($filterId) {
        // first, search if there is a replacing filter
        $filters = $this->translate5Filters->findOkapiDefaultReplacingFilter($filterId);
        if(count($filters) > 1){
            throw new ZfExtended_Exception('translate5 replacing filter id '.$filterId.' is ambigous!');
        } else if(count($filters) === 1){
            return $this->translate5Filters->createFprmPath($filters[0]);
        }
        // search the OKAPI filters
        $filters = $this->okapiFilters->findFilter(NULL, $filterId);
        if(count($filters) > 1){
            throw new ZfExtended_Exception('OKAPI filter id '.$filterId.' is ambigous!');
        } else if(count($filters) === 1){
            // we may have filters without settings !
            if(!$filters[0]->settings){
                return false;
            }
            return $this->okapiFilters->createFprmPath($filters[0]);
        }
        return NULL;
    }

    /**
     * @param string $type
     * @param string $id
     * @return string|null
     * @throws ZfExtended_Exception
     */
    public function getTranslate5FilterPath(string $type, string $id) : ?string {
        $filters = $this->translate5Filters->findFilter($type, $id);
        if(count($filters) > 1){
            throw new ZfExtended_Exception('Translate5 filter identifier '.$type.self::IDENTIFIER_SEPERATOR.$id.' is ambigous!');
        } else if(count($filters) === 1){
            return $this->translate5Filters->createFprmPath($filters[0]);
        }
        return NULL;
    }

    /**
     * Retrieves the filter-type for an (non-embedded) okapi default filter
     * @param string $id
     * @return string|null
     */
    public function getOkapiDefaultFilterTypeById(string $id) : ?string {
        $filters = $this->okapiFilters->findFilter(NULL, $id);
        if(count($filters) === 1){
            return $filters[0]->type;
        }
        return NULL;
    }

    /**
     * Checks the existence of all the testfiles linked in our constants
     * @return bool
     */
    public function validate() : bool {
        $valid = true;
        $extensions = self::TESTABLE_EXTENSIONS;
        foreach(self::GUIS as $type => $guiExtensions){
            foreach($guiExtensions as $guiExtension){
                if(!in_array($guiExtension, $extensions)){
                    $extensions[] = $guiExtension;
                }
            }
        }
        $folder = editor_Plugins_Okapi_Init::getDataDir().self::TESTFILE_FOLDER;
        foreach($extensions as $extension){
            if(!file_exists($folder.'/test.'.$extension)){
                error_log('Okapi Filter Testfile '.get_class($this).': Missing filter testfile test.'.$extension.' in '.$folder);
                $valid = false;
            }
        }
        // TODO BCONF: add GUI-datafiles to this validation
        return $valid;
    }
}
