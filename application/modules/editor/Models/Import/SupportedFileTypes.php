<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Helper Class which provides information about the allowed file types in translate5
 * Instanced once, the class tracks internally (static) the allowed values.
 */
class editor_Models_Import_SupportedFileTypes {

    /**
     * The map of extensions mapped to their file parsers
     * @var array
     */
    protected static $extensionsWithParser = [];
    
    /**
     * The list of extensions which can be imported via preprocessors (which convert the file then to format known by file parsers)
     * @var array
     */
    protected static $extensionsSupported = [];
    
    /**
     * The list of extensions which should be ignored in import processing (and therefore do not produce a unprocessed warning) 
     *  in other words, file is ignored in default import process, but still is used as secondary input file for special fileparsers like transit  
     * @var array
     */
    protected static $extensionsIgnored = [];
    
    public function __construct() {
        if(empty(self::$extensionsWithParser)){
            $this->init();
        }
    }

    /**
     * reads all supported file types from the core fileparsers and stores them staticly in the class
     */
    protected function init() {
        self::$extensionsWithParser = editor_Models_Import_FileParser::getAllFileParsersMap();
        //ZIP is not provided by a specific fileparser, but is supported by the core as container format
        // same for testcases
        $this->register(editor_Models_Import_UploadProcessor::TYPE_ZIP);
    }
    
    /**
     * Registers the given file type to be handleable by translate5, but without a concrete parser
     *  due multiple pre-processing steps, this filetype is probably preprocessed and converted before giving finally to the FileParsers
     * @param string $extension
     */
    public function register($extension) {
        //only add if it does not already exist
        if(!in_array($extension, self::$extensionsSupported)) {
            self::$extensionsSupported[] = $extension;
        }
    }
    
    /**
     * Registers the given file type to be ignored by translate5, 
     *  useful if file is needed by the fileparser as additional data source, but should not be listed in file list
     * @param string $extension
     */
    public function registerIgnored($extension) {
        //only add if it does not already exist
        if(!in_array($extension, self::$extensionsIgnored)) {
            self::$extensionsIgnored[] = $extension;
        }
    }
        
    /**
     * Register additional fileParsers, if a plugin adds a new FileParser for example.
     * Overwrites existing extensions
     * @param string $extension
     * @param string $importFileParserClass
     */
    public function registerFileParser(string $extension, string $importFileParserClass) {
        self::$extensionsWithParser[$extension] = $importFileParserClass;
    }
    
    /**
     * returns a list of supported file extensions (extensions with parser + supported extensions)
     * @return array
     */
    public function getSupportedExtensions() {
        //array_values needed for later JSON encode (with array_unique there may be gaps in the index, which results in objects instead arrays 
        return array_values(array_unique(array_merge(array_keys(self::$extensionsWithParser), self::$extensionsSupported)));
    }
    
    /**
     * returns all registered extensions (the supported + the ignored)
     * @return array
     */
    public function getRegisteredExtensions() {
        //array_values needed for later JSON encode (with array_unique there may be gaps in the index, which results in objects instead arrays 
        return array_values(array_unique(array_merge($this->getSupportedExtensions(), self::$extensionsIgnored))); 
    }
    
    /**
     * returns the parser class name to the given extension
     * @param string $ext
     * @throws editor_Models_Import_FileParser_NoParserException
     * @return string parser class name
     */
    public function getParser(string $ext): string {
        if(empty(self::$extensionsWithParser[$ext])) {
            //'For the given fileextension no parser is registered.'
            throw new editor_Models_Import_FileParser_NoParserException('E1060', [
                'extension' => $ext,
                'availableParsers' => print_r(self::$extensionsWithParser,1),
            ]);
        }
        return self::$extensionsWithParser[$ext];
    }
    
    /**
     * returns true if file extension is supported natively by a fileparser (no pre conversion like Okapi is needed for that file).
     * @param string $ext
     * @return bool
     */
    public function hasParser(string $ext) : bool {
        return !empty(self::$extensionsWithParser[$ext]);
    }
    
    /**
     * returns true if extension as to be ignored by the directory parser at all
     * @param string $ext
     * @return boolean
     */
    public function isIgnored(string $ext): bool {
        return in_array($ext, self::$extensionsIgnored);
    }
}