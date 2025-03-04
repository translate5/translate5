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
use editor_Plugins_Okapi_Init;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\OkapiFilterInventory;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\T5FilterInventory;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use stdClass;
use ZfExtended_Exception;

/**
 * Class representing the Filters a bconf can have
 * These consist of okapi default-filters, translate5-adjusted filters and the user customized filters from the database
 *
 * There are 3 different sources for FPRMs in a BCONF:
 * - User customized FPRM: will have a database-entry (for name, description & mime) and also exist as file in the
 *   BCONFs component folder
 * - translate5 adjusted FPRM: Will exist in the folder .../Plugins/Okapi/data/fprm/translate5
 *   with the inventory-file .../Plugins/Okapi/data/fprm/translate5-filters.json
 * - OKAPI default adjusted FPRM: Will exist in the folder .../Plugins/Okapi/data/fprm/okapi
 *   with the inventory-file .../Plugins/Okapi/data/fprm/okapi-filters.json
 * When compiling/packing a bconf, the customized, translate5 adjusted and okapi-default FPRMs will all be embedded
 * into the packed BCONF-file depending on which filters are referenced in the BCONFs extension-mapping
 * On unpacking, the okapi-default files will be reverted to non-embedded, see ExtensionMapping
 *
 * This class also defines the filters/FPRMs, the frontend has a editing-GUI for, ::GUIS
 * Generally, an editor relates to an OKAPI-type. The OKAPI-ids like "okf_xml-AndroidStrings" reference the
 * base type "okf_xml" with certain settings. Each frontend-editable okapi-type must have an entry here;
 * all non x-properties types must also have one or more extensions defined,
 * which relate to a testfile in .../Plugins/Okapi/data/testfiles/
 * The referenced Class must exist in the ExtJS FPRM-editor directory .../Plugins/Okapi/public/js/view/fprm/
 * Note, that non X-Properties FPRMs cannot be validated other than testing it with a concrete testfile and therefore
 * all non X-Prperties editors must have a testfile.
 * Note, that multiple filter-types may have the same Frontend
 *
 * see also Filter\Fprm for more documentation
 */
class Filters
{
    /**
     * General seperator in the OKAPI filter naming scheme
     * @var string
     */
    public const IDENTIFIER_SEPERATOR = '@';

    /**
     * All Filters that have a GUI must be defined here
     * Each filter must define extensions that can be tested alongside the filter.
     * These files must exist in .../Plugins/Okapi/data/$self::TESTFILE_FOLDER just as described with TESTABLE_EXTENSIONS
     * @var array
     */
    public const GUIS = [
        'okf_html' => [
            'class' => 'Yaml',
            'extensions' => ['html'],
        ],
        'okf_icml' => [
            'class' => 'Icml',
            'extensions' => [],
        ],
        'okf_idml' => [
            'class' => 'Idml',
            'extensions' => ['idml'],
        ],
        'okf_itshtml5' => [
            'class' => 'Xml',
            'extensions' => ['html'],
        ],
        'okf_openxml' => [
            'class' => 'Openxml',
            'extensions' => ['docx', 'pptx', 'xlsx'],
        ],
        'okf_ttx' => [
            'class' => 'Ttx',
            'extensions' => [],
        ],
        'okf_xliff' => [
            'class' => 'Xliff',
            'extensions' => ['xlf', 'xlif', 'xliff', 'mxliff', 'mqxliff'],
        ],
        'okf_xliff2' => [
            'class' => 'Xliff2',
            'extensions' => ['xlf2', 'xliff2'],
        ],
        'okf_xml' => [
            'class' => 'Xml',
            'extensions' => ['xml'],
        ],
        'okf_xmlstream' => [
            'class' => 'Yaml',
            'extensions' => ['xml'],
        ],
    ];

    /**
     * A list of file-extensions, that validation files exist for.
     * These files reside in .../Plugins/Okapi/data/$self::TESTFILE_FOLDER and are all called "test.$EXTENSION"
     * For each extension here a file must exist, the language is expected to be be english / en
     *
     * TODO: a testfile for "icml" would be great
     */
    public const TESTABLE_EXTENSIONS = [
        'xml', // "xml" must go first for Bconf validation with Xslt step in pipeline to succeed
        'txt',
        'strings',
        'csv',
        'htm',
        'html',
        'sdlxliff',
        'docx',
        'odp',
        'ods',
        'odt',
        'po',
        'pptx',
        'tbx',
        'xlsx',
        'idml',
    ];

    /**
     * @var string
     */
    public const TESTFILE_FOLDER = 'testfiles';

    private static ?Filters $_instance = null;

    /**
     * Evaluates if a filter has aExtJS GUI to be edited with
     */
    public static function hasGui(string $filterType): bool
    {
        return array_key_exists($filterType, self::GUIS);
    }

    /**
     * Small helper to create the filter-gui classname out of the filter type
     * Optionally defines the full ExtJS path
     * Returns an empty string when no Gui defined
     */
    public static function getGuiClass(string $filterType, bool $fullPath = true): string
    {
        if (! self::hasGui($filterType)) {
            return '';
        }
        $className = self::GUIS[$filterType]['class'];
        if ($fullPath) {
            return 'Editor.plugins.Okapi.view.fprm.' . $className;
        }

        return $className;
    }

    /**
     * Evaluates, if the identifier represents an okapi default identifier
     * (an identier that does not point to a fprm embedded in the bconf)
     */
    public static function isOkapiDefaultIdentifier(string $identifier): bool
    {
        return ! str_contains($identifier, self::IDENTIFIER_SEPERATOR);
    }

    /**
     * Retrieves the non-embedded counterpart for an embedded okapi-default identifier,
     * eg. "okf_plaintext_regex_paragraphs" for "okf_plaintext@okf_plaintext_regex_paragraphs"
     * @throws ZfExtended_Exception
     */
    public static function createOkapiDefaultIdentifier(string $identifier): ?string
    {
        if (! self::isOkapiDefaultIdentifier($identifier)) {
            $idata = self::parseIdentifier($identifier);
            if (self::instance()->isEmbeddedOkapiDefaultFilter($idata->type, $idata->id)) {
                return $idata->id;
            }
        }

        return null;
    }

    /**
     * Parses an identifier that is part of the bconf file
     * @throws ZfExtended_Exception
     */
    public static function parseIdentifier(string $identifier): stdClass
    {
        $parts = explode('@', $identifier);
        if (count($parts) !== 2 || ! str_starts_with($parts[0], 'okf_')) {
            throw new ZfExtended_Exception('OKAPI FPRM identifier ' . $identifier . ' is not valid');
        }
        $result = new stdClass();
        $result->type = $parts[0];
        $result->id = $parts[1];

        return $result;
    }

    /**
     * Creates an identifier out of okapiType and okapiId
     */
    public static function createIdentifier(string $okapiType, string $okapiId): string
    {
        return $okapiType . self::IDENTIFIER_SEPERATOR . $okapiId;
    }

    /**
     * Creates an identifier out of a path to a fprm file
     */
    public static function createIdentifierFromPath(string $fprmPath): string
    {
        return pathinfo($fprmPath, PATHINFO_FILENAME);
    }

    /**
     * Creates the path of a testfile in the testfile-folder
     */
    public static function createTestfilePath(string $testFile): string
    {
        return editor_Plugins_Okapi_Init::getDataDir() . self::TESTFILE_FOLDER . '/' . $testFile;
    }

    public static function instance(): Filters
    {
        if (self::$_instance == null) {
            self::$_instance = new Filters();
        }

        return self::$_instance;
    }

    private OkapiFilterInventory $okapiFilters;

    private T5FilterInventory $translate5Filters;

    protected function __construct()
    {
        $this->okapiFilters = OkapiFilterInventory::instance();
        $this->translate5Filters = T5FilterInventory::instance();
    }

    public function isValidOkapiDefaultFilter(string $identifier): bool
    {
        if (str_contains($identifier, self::IDENTIFIER_SEPERATOR)) {
            return false;
        }

        return (count($this->okapiFilters->findFilter(null, $identifier)) === 1);
    }

    /**
     * Retrieves, if a filter identifier represents a customized filter
     * @throws ZfExtended_Exception
     */
    public function isCustomFilter(string $identifier): bool
    {
        if (self::isOkapiDefaultIdentifier($identifier)) {
            return false;
        }
        $idata = self::parseIdentifier($identifier);

        return ! $this->isEmbeddedDefaultFilter($idata->type, $idata->id);
    }

    /**
     * Checks, whether the $identifier is a default identifier, either OKAPI default,
     * OKAPI embedded default or translate5 adjusted default
     */
    public function isEmbeddedDefaultFilter(string $type, string $id): bool
    {
        if (T5FilterInventory::isTranslate5Id($id)) {
            return $this->isEmbeddedTranslate5Filter($type, $id);
        } else {
            return $this->isEmbeddedOkapiDefaultFilter($type, $id);
        }
    }

    public function isEmbeddedOkapiDefaultFilter(string $type, string $id): bool
    {
        $filters = $this->okapiFilters->findFilter($type, $id);

        return (count($filters) > 0);
    }

    public function isEmbeddedTranslate5Filter(string $type, string $id): bool
    {
        $filters = $this->translate5Filters->findFilter($type, $id);

        return (count($filters) > 0);
    }

    /**
     * Finds the fprm path for an OKAPI default filter.
     * Note that this might actually return a tranlate5 adjusted filter in case it is a replacing filter.
     * This API will return NULL for a filter that could not be found and false for filters that do not support settings
     * @throws ZfExtended_Exception
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function getOkapiDefaultFilterPathById($filterId): string|null|bool
    {
        // first, search if there is a replacing filter
        $filters = $this->translate5Filters->findOkapiDefaultReplacingFilter($filterId);
        if (count($filters) > 1) {
            throw new ZfExtended_Exception('translate5 replacing filter id ' . $filterId . ' is ambigous!');
        } elseif (count($filters) === 1) {
            return $this->translate5Filters->createFprmPath($filters[0]);
        }
        // search the OKAPI filters
        $filters = $this->okapiFilters->findFilter(null, $filterId);
        if (count($filters) > 1) {
            throw new ZfExtended_Exception('OKAPI filter id ' . $filterId . ' is ambigous!');
        } elseif (count($filters) === 1) {
            // we may have filters without settings !
            if (! $filters[0]->settings) {
                return false;
            }

            return $this->okapiFilters->createFprmPath($filters[0]);
        }

        return null;
    }

    /**
     * @throws ZfExtended_Exception
     */
    public function getTranslate5FilterPath(string $type, string $id): ?string
    {
        $filters = $this->translate5Filters->findFilter($type, $id);
        if (count($filters) > 1) {
            throw new ZfExtended_Exception(
                'Translate5 filter identifier ' . $type . self::IDENTIFIER_SEPERATOR . $id . ' is ambigous!'
            );
        } elseif (count($filters) === 1) {
            return $this->translate5Filters->createFprmPath($filters[0]);
        }

        return null;
    }

    /**
     * Retrieves the filter-type for an (non-embedded) okapi default filter
     */
    public function getOkapiDefaultFilterTypeById(string $id): ?string
    {
        $filters = $this->okapiFilters->findFilter(null, $id);
        if (count($filters) === 1) {
            return $filters[0]->type;
        }

        return null;
    }

    /**
     * Checks the existence of all the testfiles linked in our constants
     */
    public function validate(): bool
    {
        $valid = true;
        $extensions = self::TESTABLE_EXTENSIONS;
        foreach (self::GUIS as $type => $data) {
            foreach ($data['extensions'] as $guiExtension) {
                if (! in_array($guiExtension, $extensions)) {
                    $extensions[] = $guiExtension;
                }
            }
        }
        $folder = editor_Plugins_Okapi_Init::getDataDir() . self::TESTFILE_FOLDER;
        foreach ($extensions as $extension) {
            if (! file_exists($folder . '/test.' . $extension)) {
                error_log('Okapi Filter Testfile ' . get_class($this) . ':'
                    . ' Missing filter testfile test.' . $extension . ' in ' . $folder);
                $valid = false;
            }
        }

        return $valid;
    }
}
