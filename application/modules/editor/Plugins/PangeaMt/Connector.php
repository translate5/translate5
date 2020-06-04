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
 * TODO: most of the following code is the same for each language-resource...
 */
class editor_Plugins_PangeaMt_Connector extends editor_Services_Connector_Abstract {

    /**
     * @var editor_Plugins_PangeaMt_HttpApi
     */
    protected $api;
    
    /**
     * @var boolean
     */
    protected $isInstantTranslate;

    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::__construct()
     */
    public function __construct() {
        parent::__construct();
        $this->api = ZfExtended_Factory::get('editor_Plugins_PangeaMt_HttpApi');
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $this->defaultMatchRate = $config->runtimeOptions->plugins->PangeaMt->matchrate;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $this->isInstantTranslate = false;
        
        // For matches and pretranslation, we use PangeaMt with tag_handling = "xml".
        // In order to handle proper xml, we use xliff and implement a similar procedure as in OpenTM2:
        
        $queryString = $this->getQueryString($segment);
        
        //Although we take the source fields from the LanguageResource answer below
        // we have to set the default source here to fill the be added internal tags
        $this->resultList->setDefaultSource($queryString);
        
        $queryString = $this->restoreWhitespaceForQuery($queryString);
        
        //$map is set by reference
        $map = [];
        $queryString = $this->internalTag->toXliffPaired($queryString, true, $map);
        $mapCount = count($map);
        
        //we have to use the XML parser to restore whitespace, otherwise protectWhitespace would destroy the tags
        $xmlParser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlParser editor_Models_Import_FileParser_XmlParser */
        
        $this->shortTagIdent = $mapCount + 1;
        $xmlParser->registerOther(function($textNode, $key) use ($xmlParser){
            //for communication with the LanguageResource we assume that the segment content is XML/XLIFF therefore we assume xmlBased here
            $textNode = $this->whitespaceHelper->protectWhitespace($textNode, true);
            $textNode = $this->whitespaceTagReplacer($textNode);
            $xmlParser->replaceChunk($key, $textNode);
        });
        
        if ($this->queryPangeaMtApi($queryString)) {
            $found = $this->api->getResult();
            //since protectWhitespace should run on plain text nodes we have to call it before the internal tags are reapplied,
            // since then the text contains xliff tags and the xliff tags should not contain affected whitespace
            $target = $xmlParser->parse($found->text);
            $target = $this->internalTag->reapply2dMap($target, $map);
            $this->resultList->addResult($target, $this->defaultMatchRate);
            
        }
        return $this->resultList;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        throw new BadMethodCallException("The PangeaMt Translation Connector does not support search requests");
    }
    
    /***
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        $this->isInstantTranslate = true;
        if ($this->queryPangeaMtApi($searchString)){
            $result = $this->api->getResult();
            $translation = $result->text ?? "";
            $this->resultList->addResult($translation, $this->defaultMatchRate);
        }
        return $this->resultList;
    }
    
    /***
     * Query the PangeaMt cloud api for the search string
     * @param string $searchString
     * @param bool $reimportWhitespace optional, if true converts whitespace into translate5 capable internal tag
     * @return boolean
     */
    protected function queryPangeaMtApi($searchString, $reimportWhitespace = false){
        return false;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        if($this->api->getStatus()){
            return self::STATUS_AVAILABLE;
        }
        return self::STATUS_NOCONNECTION;
    }
}
