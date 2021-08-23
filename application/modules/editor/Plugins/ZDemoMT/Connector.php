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

/**
 * TODO: most of the following code is the same for each language-resource...
 */
class editor_Plugins_ZDemoMT_Connector extends editor_Services_Connector_Abstract {

    /**
     * Using Xliff based tag handler here
     * @var string
     */
    protected $tagHandlerClass = 'editor_Services_Connector_TagHandler_Xliff';
    
    /**
     * Just overwrite the class var hint here
     * @var editor_Services_Connector_TagHandler_Xliff
     */
    protected $tagHandler;
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::__construct()
     */
    public function __construct() {
        parent::__construct();
        $this->defaultMatchRate = 70;
    }
    
    /***
     *
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryStringAndSetAsDefault($segment);
        $queryString = $this->tagHandler->prepareQuery($queryString);
        $results = [$this->translateToRot13($queryString)];
        if(empty($results)) {
            return $this->resultList;
        }
        
        foreach ($results as $result) {
            $this->resultList->addResult($this->tagHandler->restoreInResult($result), $this->defaultMatchRate);
            $this->resultList->setSource($this->tagHandler->restoreInResult($queryString));
        }
        return $this->resultList;
    }
    
    /***
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        if(empty($searchString)) {
            return $this->resultList;
        }
        $allResults = [$this->translateToRot13($searchString)];
        if(empty($allResults)) {
            return $this->resultList;
        }
        
        foreach ($allResults as $result) {
            $this->resultList->addResult($result,$this->defaultMatchRate);
        }
        return $this->resultList;
    }
    
    /**
     * translates the given string to rot13.
     * @param string $searchString
     * @return string
     */
    public function translateToRot13(string $searchString): string {
        $tokens = preg_split('/(<[^>]+>)/i', $searchString, null, PREG_SPLIT_DELIM_CAPTURE);
        foreach($tokens as &$token) {
            if(strpos($token, '<') === 0) {
                continue;
            }
            $token = htmlentities(str_rot13(html_entity_decode($token)), ENT_XML1);
        }
        return join('', $tokens);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(editor_Models_LanguageResources_Resource $resource){
        return self::STATUS_AVAILABLE;
    }
}
