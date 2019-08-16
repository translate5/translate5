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
 * FIXME WARNING: MimeTypes ware not needed anymore, since check was deactivated in UploadProcessor
 * but since there is currently no time to refactor the stuff, we leave it as it is and refactor it later
 * 
 * Helper Class which provides information about the allowed file types in translate5
 * Instanced once, the class tracks internally (static) the allowed values.
 */
class editor_Models_Import_SupportedFileTypes {
    protected static $extensionMimeMap;
    protected static $extensionParserMap = [];
    
    public function __construct() {
        if(empty(self::$extensionParserMap)){
            $this->init();
        }
    }

    /**
     * reads all supported file types from the core fileparsers and stores them staticly in the class
     */
    protected function init() {
        self::$extensionParserMap = editor_Models_Import_FileParser::getAllFileParsersMap();
        foreach(self::$extensionParserMap as $ext => $parser) {
            $this->register($ext, $parser::getValidMimeTypes());
        }
        //ZIP is not provided by a specific fileparser, but is supported by the core as container format
        $this->register('zip', []);
    }
    
    /**
     * FIXME WARNING: MimeTypes ware not needed anymore, since check was deactivated in UploadProcessor
     * but since there is currently no time to refactor the stuff, we leave it as it is and refactor it later
     * 
     * Registers the given file type to be handleable by translate5, 
     * due multiple pre processing steps, this is independant of the FileParsers
     * 
     * @param string $extension
     * @param array $mimetypes list of matching mimetypes
     */
    public function register($extension, array $mimetypes) {
        $map = self::$extensionMimeMap[$extension] ?? [];
        self::$extensionMimeMap[$extension] = array_merge($map, $mimetypes);
    }
        
    /**
     * Register additional fileParsers, if a plugin adds a new FileParser for example.
     * Overwrites existing extensions
     * @param string $extension
     * @param string $importFileParserClass
     */
    public function registerFileParser(string $extension, string $importFileParserClass) {
        $this->register($extension, []);
        self::$extensionParserMap[$extension] = $importFileParserClass;
    }
    
    /**
     * FIXME WARNING: MimeTypes ware not needed anymore, since check was deactivated in UploadProcessor
     * but since there is currently no time to refactor the stuff, we leave it as it is and refactor it later
     * 
     * returns a map of supported file extensions to the corresponding mime types
     * @return array
     */
    public function getSupportedTypes() {
        return self::$extensionMimeMap;
    }
    
    /**
     * returns a map of supported file extensions to the corresponding mime types
     * @param string $ext
     * @throws Zend_Exception
     * @return string parser class name
     */
    public function getParser($ext) {
        if(empty(self::$extensionParserMap[$ext])) {
            //'For the given fileextension no parser is registered.'
            throw new editor_Models_Import_FileParser_NoParserException('E1060', [
                'extension' => $ext,
                'availableParsers' => print_r(self::$extensionParserMap,1),
            ]);
        }
        return self::$extensionParserMap[$ext];
    }
    
    /**
     * returns true if file extension is supported natively by a fileparser (no pre conversion like Okapi is needed for that file).
     * @param string $ext
     * @return bool
     */
    public function hasParser(string $ext) : bool {
        return !empty(self::$extensionParserMap[$ext]);
    }
}