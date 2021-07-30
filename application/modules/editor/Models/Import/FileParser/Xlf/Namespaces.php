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
class editor_Models_Import_FileParser_Xlf_Namespaces extends editor_Models_Import_FileParser_Xlf_Namespaces_Abstract {
    protected $namespaces = [];
    
    public function __construct($xliff) {
        //TODO this code could be improved by moving the following checks into each namespace class and loop through the existing classes
        // instead of hardcoding the checks here
        // Additionaly this simple String check fails if the strings are somewhere in the content
        // for a better implementation see Export Namespaces
        if (strpos($xliff, editor_Models_Import_FileParser_Xlf_Namespaces_Tmgr::IBM_XLIFF_NAMESPACE) !== false) {
            $this->namespaces['ibm'] = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_Namespaces_Tmgr');
        }
        if (strpos($xliff, editor_Models_Import_FileParser_Xlf_Namespaces_Translate5::TRANSLATE5_XLIFF_NAMESPACE) !== false) {
            $this->namespaces['translate5'] = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_Namespaces_Translate5');
        }
        if (strpos($xliff, editor_Models_Import_FileParser_Xlf_Namespaces_Across::ACROSS_XLIFF_NAMESPACE) !== false) {
            $this->namespaces['across'] = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_Namespaces_Across');
        }
        if (strpos($xliff, editor_Models_Import_FileParser_Xlf_Namespaces_MemoQ::MEMOQ_XLIFF_NAMESPACE) !== false) {
            $this->namespaces['memoq'] = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_Namespaces_MemoQ');
        }
    }
    
    /**
     * Adds manually a specific XLF namespace handler
     * @param string $key
     * @param editor_Models_Import_FileParser_Xlf_Namespaces_Abstract $namespace
     */
    public function addNamespace($key, editor_Models_Import_FileParser_Xlf_Namespaces_Abstract $namespace) {
        $this->namespaces[$key] = $namespace;
    }
    
    /**
     *
     * @param array $attributes
     * @param editor_Models_Import_FileParser_SegmentAttributes $segmentAttributes
     */
    public function transunitAttributes(array $attributes, editor_Models_Import_FileParser_SegmentAttributes $segmentAttributes) {
        $this->call(__FUNCTION__, func_get_args());
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::registerParserHandler()
     */
    public function registerParserHandler(editor_Models_Import_FileParser_XmlParser $xmlparser) {
        $this->call(__FUNCTION__, func_get_args());
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::getPairedTag()
     */
    public function getPairedTag($xlfBeginTag, $xlfEndTag){
        return $this->call(__FUNCTION__, func_get_args());
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::getSingleTag()
     */
    public function getSingleTag($xlfTag){
        return $this->call(__FUNCTION__, func_get_args());
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::useTagContentOnly()
     */
    public function useTagContentOnly(){
        //using null as default value should trigger further investigation if the tag content should be used or not (if the namespace did not provide information about it)
        return $this->call(__FUNCTION__, func_get_args(), null);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::getComments()
     */
    public function getComments() {
        return $this->call(__FUNCTION__, func_get_args());
    }
    
    /**
     * calls the function in each namespace object, passes the arguments
     * @param string $function
     * @param array $arguments
     * @param boolean $result optional, the default result if no namespace was found
     * @return string|mixed
     */
    protected function call(string $function, array $arguments, $result = false) {
        foreach ($this->namespaces as $namespace){
            $result = call_user_func_array([$namespace, $function], $arguments);
            //if one of the callen namespace handlers produces a result, we return this and end the loop
            if(!is_null($result)) {
                return $result;
            }
        }
        //if no namespace was defined, we return the default result, by default false
        return $result;
    }
}
