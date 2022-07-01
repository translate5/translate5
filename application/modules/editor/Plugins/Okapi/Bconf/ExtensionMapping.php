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
 */
class editor_Plugins_Okapi_Bconf_ExtensionMapping {

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
     * @var array
     */
    private array $map = [];

    /**
     * @var string
     */
    private string $contentToPack;

    /**
     * When packing a bconf we add the okapi-defaults as real FPRMs. These will be cached here
     * @var array
     */
    private array $fprmsToPack;

    /**
     * @var editor_Plugins_Okapi_Models_Bconf
     */
    private editor_Plugins_Okapi_Models_Bconf $bconf;

    /**
     * @param string $path
     * @param editor_Plugins_Okapi_Models_Bconf $bconf
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function __construct(string $path, editor_Plugins_Okapi_Models_Bconf $bconf){
        $this->path = $path;
        $this->bconf = $bconf;
        $content = NULL;
        if(file_exists($path) && is_writable($path)){
            $content = file_get_contents($path);
        }
        if(empty($content)){
            throw new ZfExtended_Exception('editor_Plugins_Okapi_Bconf_ExtensionMapping can only be instantiated for an existing extension-mapping file ('.$path.')');
        }
        $lines = explode('\n', $content);
        foreach($lines as $line){
            $parts = preg_split("/\s+/", trim($line));
            if(count($parts) === 2){
                $this->map[ltrim($parts[0], '.')] = $parts[1];
            }
        }
        if(!$this->hasEntries()){
            throw new editor_Plugins_Okapi_Exception('E4402', ['bconf' => $bconf->getName(), 'bconfId' => $bconf->getId()]);
        }
    }

    /**
     * @return string
     */
    public function getPath() : string {
        return $this->path;
    }

    /**
     * @return bool
     */
    public function hasEntries() : bool {
        return (count($this->map) > 0);
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
     * Retrieves the mapping to write to a packed bconf
     * @param array $addedCustomIdentifiers: these are the identifiers that have already been added to the packed bconf (just the identifiers, not the pathes)
     * @return string
     */
    public function getContentForPacking(array $addedCustomIdentifiers) : string {
        $this->preparePacking($addedCustomIdentifiers);
        return $this->contentToPack;
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
     * Generates the file-contents
     * @param array $map
     * @return string
     */
    private function createContent(array $map) {
        $content = [];
        foreach($map as $extension => $identifier){
            $content .= '.'.$extension . self::SEPERATOR . $identifier . self::LINEFEED;
        }
        return rtrim($content, self::LINEFEED);
    }
    /**
     * @param array $addedCustomIdentifiers
     */
    private function preparePacking(array $addedCustomIdentifiers){
        if(empty($this->contentToPack)){
            $this->fprmsToPack = [];
            $packMap = [];
            foreach($this->map as $extension => $identifier){
                if(in_array($identifier, $addedCustomIdentifiers)){
                    // either the identifier is a custom filter and therefore must be part of the already added custom filters
                    $packMap[$extension] = $identifier;
                } else if(editor_Plugins_Okapi_Bconf_Filters::isOkapiDefaultIdentifier($identifier)){
                    // or it is a okapi default identifier which then needs to be added as explicit fprm
                    $path = editor_Plugins_Okapi_Bconf_Filters::instance()->getOkapiDefaultFilterPathById($identifier);
                    if(empty($path)){
                        throw new editor_Plugins_Okapi_Exception('E4403', ['bconf' => $this->bconf->getName(), 'bconfId' => $this->bconf->getId(), 'identifier' => $identifier]);
                    } else {
                        $this->fprmsToPack[] = $path;
                        $packMap[$extension] = basename($path);
                    }
                } else {
                    // otherwise the entry must be a translate5 asjusted bconf or invalid
                    $idata = editor_Plugins_Okapi_Bconf_Filters::parseIdentifier($identifier);
                    $path = editor_Plugins_Okapi_Bconf_Filters::instance()->getTranslate5FilterPath($idata->type, $idata->id);
                    if(empty($path)){
                        throw new editor_Plugins_Okapi_Exception('E4403', ['bconf' => $this->bconf->getName(), 'bconfId' => $this->bconf->getId(), 'identifier' => $identifier]);
                    } else {
                        $this->fprmsToPack[] = $path;
                        $packMap[$extension] = basename($path);
                    }
                }
            }
        }
    }
}
