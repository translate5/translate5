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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */


/**
 * Fileparsing for import of IBM-XLIFF files
 */
class editor_Models_Import_FileParser_Xml extends editor_Models_Import_FileParser_Xlf {
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions() {
        return ['xml'];
    }
    
    public function getChainedParser() {
        // check here the loaded XML content, if it is XLF everything is ok, since we extend the Xlf parser
        // if it is another (not supported) XML type we throw an exception
        $attributes = [];
        $validVersions = ['1.1', '1.2'];
        $validXmlns = ['urn:oasis:names:tc:xliff:document:1.1', 'urn:oasis:names:tc:xliff:document:1.2'];
        $isXliff = preg_match('/^(<[^>]+>[^<]+)?\s*<xliff([^>]+)>/s', $this->_origFile, $match);
        if($isXliff && preg_match_all('/([^\s]+)="([^"]*)"/', $match[2], $matches)){
            $attributes = array_combine($matches[1], $matches[2]);
            settype($attributes['xmlns'], 'string');
            settype($attributes['version'], 'string');
            settype($attributes['xsi:schemaLocation'], 'string');
            $validVersion = in_array($attributes['version'], $validVersions);
            $validXmlns = in_array($attributes['xmlns'], $validXmlns);
            $validSchema = strpos($attributes['xsi:schemaLocation'], 'urn:oasis:names:tc:xliff:document:') === 0;
            if($validVersion && ($validXmlns || $validSchema)) {
                //for example check here additionaly for SDL markers then create the SDLXLIFF parser here and return it instead of $this
                $this->usedParser = 'editor_Models_Import_FileParser_Xlf';
                $this->updateFile();
                return $this; //since xml parser extends xlf, we can just return this here
            }
        }
        throw new ZfExtended_Exception('Content of given XML file is no valid XLF! File: '.$this->_fileName.'; xliff attributes: '.print_r($attributes,1));
    }
}