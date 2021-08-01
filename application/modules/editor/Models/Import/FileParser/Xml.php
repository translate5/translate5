<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */


/**
 * Fileparser for importing XLIFF 1.1 / 1.2 (like defined in Xlf Fileparser) named as .xml file
 */
class editor_Models_Import_FileParser_Xml extends editor_Models_Import_FileParser_Xlf {
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions() {
        return ['xml'];
    }
    
    /**
     * returns true if the given file is XLF and parsable by this parser
     *
     * @param string $fileHead the first 512 bytes of the file to be imported
     * @param string $errorMsg returning by reference a reason why its not parsable
     * @return boolean
     */
    public static function isParsable(string $fileHead, string &$errorMsg): bool {
        $errorMsg = '';
        //remove the bom from the text chunk. If it is not removed, the regex check below can fail
        $fileHead=ZfExtended_Utils::remove_utf8_bom($fileHead);
        // check here the loaded XML content, if it is XLF everything is ok, since we extend the Xlf parser
        // if it is another (not supported) XML type we throw an exception
        $validVersions = ['1.1', '1.2'];
        $validXmlns = ['urn:oasis:names:tc:xliff:document:1.1', 'urn:oasis:names:tc:xliff:document:1.2'];
        $isXliff = preg_match('/^(<[^>]+>[^<]+)?\s*<xliff([^>]+)>/s', $fileHead, $match);
        if($isXliff && preg_match_all('/([^\s]+)="([^"]*)"/', $match[2], $matches)){
            $infoData = array_combine($matches[1], $matches[2]);
            settype($infoData['xmlns'], 'string');
            settype($infoData['version'], 'string');
            settype($infoData['xsi:schemaLocation'], 'string');
            $validVersion = in_array($infoData['version'], $validVersions);
            $validXmlns = in_array($infoData['xmlns'], $validXmlns);
            $validSchema = strpos($infoData['xsi:schemaLocation'], 'urn:oasis:names:tc:xliff:document:') === 0;
            if($validVersion && ($validXmlns || $validSchema)) {
                //for example check here additionaly for SDL markers then create the SDLXLIFF parser here and return it instead of $this
                return true;
            }
        }
        $errorMsg = 'File has no xliff tag or is no xliff version 1.1 or 1.2!';
        return false;
    }
}