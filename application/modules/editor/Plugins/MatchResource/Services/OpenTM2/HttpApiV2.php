<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * OpenTM2 HTTP Connection API V2 (more REST as V1)
 * FIXME if API V2 is completly working, merge classes back to one file/class
 */
class editor_Plugins_MatchResource_Services_OpenTM2_HttpApiV2 extends editor_Plugins_MatchResource_Services_OpenTM2_HttpApi {
    /**
     * This method deletes a memory.
     */
    public function delete() {
        $http = $this->getHttpWithMemory();
        return $this->processResponse($http->request('DELETE'));
    }
    
    /**
     * This method searches the given search string in the proposals contained in a memory. The function returns one proposal per request. The caller has to provide the search position returned by a previous call or an empty search position to start the search at the begin of the memory.
     * Note: Provide the returned search position NewSearchPosition as SearchPosition on subsequenet calls to do a sequential search of the memory.
     */
    public function search($queryString, $field, $searchPosition = null) {
        $data = new stdClass();
        $data->searchString = $queryString;
        $data->searchType = $field;
        $data->searchPosition = $searchPosition;
        $data->numResults = 5;
        $data->msSearchAfterNumResults = 100;
        $http = $this->getHttpWithMemory('concordancesearch');
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        return $this->processResponse($http->request('POST'));
    }

    /**
     * This method updates (or adds) a memory proposal in the memory.
     * Note: This method updates an existing proposal when a proposal with the same key information (source text, language, segment number, and document name) exists.
     * 
     * @param editor_Models_Segment $segment
     * @return boolean
     */
    public function update(string $source, string $target, editor_Models_Segment $segment, $filename) {
        /* 
         * In:{ "Method":"update", "Memory": "TestMemory", "Proposal": {
         *  "Source": "This is the source text", 
         *  "Target": "This is the translated text", 
         *  "Segment":231,
         *  "DocumentName":"Anothertest.txt", 
         *  "SourceLanguage":"en-US", 
         *  "TargetLanguage":"de-de", 
         *  "Type":"Manual", 
         *  "Author":"A.Nonymous", 
         *  "DateTime":"20161013T152948Z", 
         *  "Markup":"EQFHTML3", 
         *  "Context":"", 
         *  "AddInfo":"" }  } 
         */
        //Out: { "ReturnValue":0, "ErrorMsg":"" }
        $json = $this->json(__FUNCTION__);
        
        $json->source = $source;
        $json->target = $target;

        //$json->segmentNumber = $segment->getSegmentNrInTask(); FIXME TRANSLATE-793 must be implemented first, since this is not segment in task, but segment in file
        $json->documentName = $filename;
        $json->author = $segment->getUserName();
        $json->timeStamp = $this->nowDate();
        $json->context = $segment->getMid();
        
        $json->type = "Manual";
        $json->markupTable = "OTMXUXLF"; //fixed markup table for our XLIFF subset
        
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        
        $lang->load($this->tmmt->getSourceLang());
        $json->sourceLang = $lang->getRfc5646();
        
        $lang->load($this->tmmt->getTargetLang());
        $json->targetLang = $lang->getRfc5646();
        
        $http = $this->getHttpWithMemory('entry');
        $http->setRawData(json_encode($json), 'application/json; charset=utf-8');
        return $this->processResponse($http->request('POST'));
    }
    
    public function lookup(editor_Models_Segment $segment, string $queryString, string $filename) {
        $json = new stdClass();
        $json->sourceLang = $this->tmmt->getSourceLangRfc5646();
        $json->targetLang = $this->tmmt->getTargetLangRfc5646();
        $json->source = $queryString;
        $json->documentName = $filename;
        $json->segmentNumber = ''; //FIXME can be used after implementing TRANSLATE-793
        $json->markupTable = 'OTMXUXLF';
        $json->context = $segment->getMid(); // hier MID (Context war gedacht für die Keys (Dialog Nummer) bei übersetzbaren strings in Software)
        
        $http = $this->getHttpWithMemory('fuzzysearch');
        $http->setRawData(json_encode($json), 'application/json; charset=utf-8');
        return $this->processResponse($http->request('POST'));
    }
}