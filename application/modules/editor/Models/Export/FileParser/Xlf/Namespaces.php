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
 * XLF Namespace Handler
 */
class editor_Models_Export_FileParser_Xlf_Namespaces extends editor_Models_Export_FileParser_Xlf_Namespaces_Abstract {
    protected $namespaces = [];

    public function __construct($xliff) {
        //TODO this code could be improved by moving the following checks into each namespace class and loop through the existing classes
        // instead of hardcoding the checks here
        if (strpos($xliff, editor_Models_Import_FileParser_Xlf_Namespaces_Tmgr::IBM_XLIFF_NAMESPACE) !== false) {
            $this->namespaces['ibm'] = ZfExtended_Factory::get('editor_Models_Export_FileParser_Xlf_Namespaces_Tmgr');
        }
        if (strpos($xliff, editor_Models_Import_FileParser_Xlf_Namespaces_Translate5::TRANSLATE5_XLIFF_NAMESPACE) !== false) {
            $this->namespaces['translate5'] = ZfExtended_Factory::get('editor_Models_Export_FileParser_Xlf_Namespaces_Translate5');
        }
        if (strpos($xliff, editor_Models_Import_FileParser_Xlf_Namespaces_Across::ACROSS_XLIFF_NAMESPACE) !== false) {
            $this->namespaces['across'] = ZfExtended_Factory::get('editor_Models_Export_FileParser_Xlf_Namespaces_Across');
        }
        if (strpos($xliff, editor_Models_Import_FileParser_Xlf_Namespaces_MemoQ::MEMOQ_XLIFF_NAMESPACE) !== false) {
            $this->namespaces['memoq'] = ZfExtended_Factory::get('editor_Models_Export_FileParser_Xlf_Namespaces_MemoQ');
        }
    }

    /**
     * {@inheritDoc}
     * @see editor_Models_Export_FileParser_Xlf_Namespaces_Abstract::registerParserHandler()
     */
    public function registerParserHandler(editor_Models_Import_FileParser_XmlParser $xmlparser) {
        $this->call(__FUNCTION__, func_get_args());
    }

    protected function call($function, $arguments) {
        $result = false;
        foreach ($this->namespaces as $namespace){
            $result = call_user_func_array([$namespace, $function], $arguments);
            //if one of the callen namespace handlers produces a result, we return this and end the loop
            if(!empty($result)) {
                return $result;
            }
        }
        return $result; //for falsy values we return the last value only
    }
}
