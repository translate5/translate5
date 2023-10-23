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

use editor_Models_Task as Task;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\Service\T5Memory;

/**
 * T5memory / OpenTM2 Connector
 *
 * IMPORTANT: see the doc/comments in MittagQI\Translate5\Service\T5Memory
 */
class editor_Services_OpenTM2_Connector extends editor_Services_Connector_FilebasedAbstract {

    /**
     * Connector
     * @var editor_Services_OpenTM2_HttpApi
     */
    protected $api;
    
    /**
     * Using Xliff based tag handler here
     * @var string
     */
    protected $tagHandlerClass = 'editor_Services_Connector_TagHandler_OpenTM2Xliff';
    
    /**
     * Just overwrite the class var hint here
     * @var editor_Services_Connector_TagHandler_Xliff
     */
    protected $tagHandler;

    /**
     *  Is the connector generally able to support internal Tags for the translate-API
     * @var bool
     */
    protected $internalTagSupport = true;

    /**
     * Holds the parent API in case of an fuzzy connector
     * @var editor_Services_OpenTM2_HttpApi|null
     */
    private ?editor_Services_OpenTM2_HttpApi $parentApi = null;

    /**
     * marks an fuzzy connector as reorganizing the TM, holds the beginning timestamp
     * @var int
     */
    private int $fuzzyReorganize = -1;
    
    public function __construct() {
        editor_Services_Connector_Exception::addCodes([
            'E1314' => 'The queried OpenTM2 TM "{tm}" is corrupt and must be reorganized before usage!',
            'E1333' => 'The queried OpenTM2 server has to many open TMs!',
        ]);
        
        //ZfExtended_Logger::addDuplicatesByMessage('E1314');
        ZfExtended_Logger::addDuplicatesByEcode('E1333', 'E1306', 'E1314');
        
        parent::__construct();
    }
    
    /**
     * {@inheritDoc}
     */
    public function connectTo(
        editor_Models_LanguageResources_LanguageResource $languageResource,
        $sourceLang,
        $targetLang
    ): void {
        parent::connectTo($languageResource, $sourceLang, $targetLang);
        $this->api = ZfExtended_Factory::get('editor_Services_OpenTM2_HttpApi');
        $this->api->setLanguageResource($languageResource);

        // TODO T5MEMORY: remove when OpenTM2 is out of production
        // t5 memory is not needing the OpenTM2 specific Xliff TagHandler, the default XLIFF TagHandler is sufficient
        if (!$this->api->isOpenTM2()
            && $this->tagHandler instanceof editor_Services_Connector_TagHandler_OpenTM2Xliff) {
            $this->tagHandler = ZfExtended_Factory::get(
                'editor_Services_Connector_TagHandler_Xliff',
                [['gTagPairing' => false]]
            );
        }
    }

    /**
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see editor_Services_Connector_FilebasedAbstract::addTm()
     */
    public function addTm(array $fileinfo = null,array $params=null) {
        $sourceLang = $this->languageResource->getSourceLangCode();

        //to ensure that we get unique TMs Names although of the above stripped content,
        // we add the LanguageResource ID and a prefix which can be configured per each translate5 instance
        $name = 'ID'.$this->languageResource->getId().'-'.$this->filterName($this->languageResource->getName());
        
        $this->languageResource->addSpecificData('fileName',$name);
        $this->languageResource->save();
        
        // If we are adding a TMX file as LanguageResource, we must create an empty memory first.
        $validFileTypes = $this->getValidFiletypes();
        if(empty($validFileTypes['TMX'])){
            throw new ZfExtended_NotFoundException('OpenTM2: Cannot addTm for TMX-file; valid file types are missing.');
        }
        $noFile = empty($fileinfo);
        $tmxUpload = !$noFile && in_array($fileinfo['type'], $validFileTypes['TMX']) && preg_match('/\.tmx$/', $fileinfo['name']);
        if($noFile || $tmxUpload) {
            if($this->api->createEmptyMemory($name, $sourceLang)){
                $this->api->updateFilenameFromResult();
                //if initial upload is a TMX file, we have to import it.
                if($tmxUpload) {
                    return $this->addAdditionalTm($fileinfo);
                }
                return true;
            }
            $this->logger->error('E1305', 'OpenTM2: could not create TM', [
                'languageResource' => $this->languageResource,
                'apiError' => $this->api->getError(),
            ]);
            return false;
        }
        
        //initial upload is a TM file
        if($this->api->createMemory($name, $sourceLang, file_get_contents($fileinfo['tmp_name']))){
            $this->api->updateFilenameFromResult();
            return true;
        }
        $this->logger->error('E1304', 'OpenTM2: could not create prefilled TM', [
            'languageResource' => $this->languageResource,
            'apiError' => $this->api->getError(),
        ]);
        return false;
        
    }
    
    /**
     * {@inheritDoc}
     */
    public function addAdditionalTm(array $fileinfo = null, array $params = null): bool
    {
        try {
            $successful = $this->api->importMemory(file_get_contents($fileinfo['tmp_name']));

            if (!$successful && $this->needsReorganizing($this->api->getError())) {
                $this->addReorganizeWarning();
                $this->reorganizeTm();
            }

            return $successful;
        } catch (editor_Models_Import_FileParser_InvalidXMLException $e) {
            $e->addExtraData([
                'languageResource' => $this->languageResource,
            ]);
            $this->logger->exception($e);
        }

        $this->logger->error('E1303', 'OpenTM2: could not add TMX data to TM', [
            'languageResource' => $this->languageResource,
            'apiError' => $this->api->getError(),
        ]);

        return false;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypes()
     */
    public function getValidFiletypes() {
        return [
            'TM' => ['application/zip'],
            'TMX' => ['application/xml','text/xml'],
        ];
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypeForExport()
     */
    public function getValidExportTypes() {
        return [
            'TM' => 'application/zip',
            'TMX' => 'application/xml',
        ];
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::getTm()
     */
    public function getTm($mime) {
        if($this->api->get($mime)) {
            return $this->api->getResult();
        }
        $this->throwBadGateway();
    }

    public function update(editor_Models_Segment $segment): void
    {
        if ($this->isReorganizingAtTheMoment()) {
            throw new editor_Services_Connector_Exception('E1512', [
                'service' => $this->getResource()->getName(),
                'languageResource' => $this->languageResource,
            ]);
        }

        $fileName = $this->getFileName($segment);
        $source = $this->tagHandler->prepareQuery($this->getQueryString($segment));
        $target = $this->tagHandler->prepareQuery($segment->getTargetEdit());

        $successful = $this->api->update($source, $target, $segment, $fileName);

        if ($successful) {
            return;
        }

        if ($this->needsReorganizing($this->api->getError())) {
            $this->addReorganizeWarning($segment->getTask());
            $this->reorganizeTm();

            $successful = $this->api->update($source, $target, $segment, $fileName);

            if ($successful) {
                return;
            }
        }
        
        $error = $this->api->getError();

        // send the error to the frontend
        editor_Services_Manager::reportTMUpdateError($error);

        $this->logger->error('E1306', 'OpenTM2: could not save segment to TM', [
            'languageResource' => $this->languageResource,
            'segment' => $segment,
            'apiError' => $error
        ]);
    }

    public function updateTranslation(string $source, string $target){
        $this->api->updateText($source,$target);
    }
    
    /**
     * Fuzzy search
     *
     * {@inheritDoc}
     */
    public function query(editor_Models_Segment $segment): editor_Services_ServiceResult
    {
        $fileName = $this->getFileName($segment);
        $queryString = $this->getQueryString($segment);
        
        //if source is empty, OpenTM2 will return an error, therefore we just return an empty list
        if (empty($queryString) && $queryString !== '0') {
            return $this->resultList;
        }
        
        //Although we take the source fields from the OpenTM2 answer below
        // we have to set the default source here to fill the be added internal tags
        $this->resultList->setDefaultSource($queryString);
        $query = $this->tagHandler->prepareQuery($queryString);
        $successful = $this->api->lookup($segment, $query, $fileName);

        if (!$successful && $this->needsReorganizing($this->api->getError())) {
            $this->addReorganizeWarning($segment->getTask());
            $this->reorganizeTm();
            $successful = $this->api->lookup($segment, $query, $fileName);
        }

        if (!$successful) {
            $this->throwBadGateway();
        }

        $result = $this->api->getResult();

        if ((int)$result->NumOfFoundProposals === 0) {
            return $this->resultList;
        }

        foreach ($result->results as $found) {
            $target = $this->tagHandler->restoreInResult($found->target);
            $hasTargetErrors = $this->tagHandler->hasRestoreErrors();

            $source = $this->tagHandler->restoreInResult($found->source);
            $hasSourceErrors = $this->tagHandler->hasRestoreErrors();

            if ($hasTargetErrors || $hasSourceErrors) {
                //the source has invalid xml -> remove all tags from the result, and reduce the matchrate by 2%
                $found->matchRate = $this->reduceMatchrate($found->matchRate, 2);
            }

            $matchrate = $this->calculateMatchRate(
                $found->matchRate,
                $this->getMetaData($found),
                $segment,
                $fileName
            );
            $this->resultList->addResult($target, $matchrate, $this->getMetaData($found));
            $this->resultList->setSource($source);
        }

        return $this->getResultListGrouped();
    }

    /**
     * returns the filename to a segment
     * @param editor_Models_Segment $segment
     * @return string
     */
    protected function getFileName(editor_Models_Segment $segment): string {
        $file = editor_ModelInstances::file($segment->getFileId());
        return $file->getFileName();
    }
    
    /**
     * Helper function to get the metadata which should be shown in the GUI out of a single result
     * @param stdClass $found
     * @return stdClass
     */
    protected function getMetaData($found) {
        $nameToShow = [
            "documentName",
            "matchType",
            "author",
            "timestamp",
            "context",
            "additionalInfo",
        ];
        $result = [];
        foreach($nameToShow as $name) {
            if(property_exists($found, $name)) {
                $item = new stdClass();
                $item->name = $name;
                $item->value = $found->{$name};
                if($name == 'timestamp') {
                    $item->value = date('Y-m-d H:i:s T', strtotime($item->value));
                }
                $result[] = $item;
            }
        }
        return $result;
    }
    
    /**
     * Concordance search
     *
     * {@inheritDoc}
     */
    public function search(string $searchString, $field = 'source', $offset = null): editor_Services_ServiceResult
    {
        $successful = $this->api->search($searchString, $field, $offset);

        if (!$successful && $this->needsReorganizing($this->api->getError())) {
            $this->addReorganizeWarning();
            $this->reorganizeTm();
            $successful = $this->api->search($searchString, $field, $offset);
        }

        if (!$successful) {
            $this->throwBadGateway();
        }

        $result = $this->api->getResult();

        if (empty($result) || empty($result->results)) {
            $this->resultList->setNextOffset(null);

            return $this->resultList;
        }

        $this->resultList->setNextOffset($result->NewSearchPosition);
        $results = $result->results;

        //$found->{$field}
        //[NextSearchPosition] =>
        foreach ($results as $result) {
            $this->resultList->addResult($this->highlight(
                $searchString,
                $this->tagHandler->restoreInResult($result->target),
                $field === 'target'
            ));
            $this->resultList->setSource($this->highlight(
                $searchString,
                $this->tagHandler->restoreInResult($result->source),
                $field === 'source')
            );
        }

        return $this->resultList;
    }
    
    /***
     * Search the resource for available translation. Where the source text is in
     * resource source language and the received results are in the resource target language
     *
     * {@inheritDoc}
     */
    public function translate(string $searchString)
    {
        //return empty result when no search string
        if (empty($searchString) && $searchString !== "0") {
            return $this->resultList;
        }

        $this->resultList->setDefaultSource($searchString);

        //create dummy segment so we can use the lookup
        $dummySegment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $dummySegment editor_Models_Segment */
        $dummySegment->init();

        $query = $this->tagHandler->prepareQuery($searchString);

        $successful = $this->api->lookup($dummySegment, $query, 'source');

        if (!$successful && $this->needsReorganizing($this->api->getError())) {
            $this->addReorganizeWarning();
            $this->reorganizeTm();
            $successful = $this->api->lookup($dummySegment, $query, 'source');
        }

        if ($successful) {
            $result = $this->api->getResult();

            if ((int)$result->NumOfFoundProposals === 0) {
                return $this->resultList;
            }

            foreach ($result->results as $found) {
                $found->target = $this->tagHandler->restoreInResult($found->target);
                $hasTargetErrors = $this->tagHandler->hasRestoreErrors();
                $found->source = $this->tagHandler->restoreInResult($found->source);
                $hasSourceErrors = $this->tagHandler->hasRestoreErrors();

                if ($hasTargetErrors || $hasSourceErrors) {
                    //the source has invalid xml -> remove all tags from the result, and reduce the matchrate by 2%
                    $found->matchRate = $this->reduceMatchrate($found->matchRate, 2);
                }

                $calcMatchRate = $this->calculateMatchRate($found->matchRate, $this->getMetaData($found), $dummySegment, 'InstantTranslate');

                $this->resultList->addResult($found->target, $calcMatchRate, $this->getMetaData($found));
                $this->resultList->setSource($found->source);
            }

            return $this->resultList;
        }

        $this->throwBadGateway();
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::delete()
     */
    public function delete() {
        if(!$this->api->delete()) {
            $resp = $this->api->getResponse();
            if($resp->getStatus() == 404) {
                // if the result was a 404, then there is nothing to delete,
                // so throw no error then and delete just locally
                return;
            }
            $this->throwBadGateway();
        }
    }
    
    /**
     * Throws a service connector exception
     * @throws editor_Services_Connector_Exception
     */
    protected function throwBadGateway() {
        $ecode = 'E1313';
        $error = $this->api->getError();
        $data = [
            'service' => $this->getResource()->getName(),
            'languageResource' => $this->languageResource ?? '',
            'error' => $error,
        ];
        if(strpos($error->error ?? '', 'needs to be organized') !== false) {
            $ecode = 'E1314';
            $data['tm'] = $this->languageResource->getName();
        }
        
        if(strpos($error->error ?? '', 'too many open translation memory databases') !== false) {
            $ecode = 'E1333';
        }
        
        throw new editor_Services_Connector_Exception($ecode, $data);
    }
    
    /**
     * Replaces not allowed characters with "_" in memory names
     * @param string $name
     * @return string
     */
    protected function filterName($name){
        //since we are getting Problems on the OpenTM2 side with non ascii characters in the filenames,
        // we strip them all. See also OPENTM2-13.
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        return preg_replace('/[^a-zA-Z0-9 _-]/', '_', $name);
        //original not allowed string list:
        //return str_replace("\\/:?*|<>", '_', $name);
    }

    /**
     * {@inheritDoc}
     * @throws editor_Services_Exceptions_InvalidResponse
     */
    public function getStatus(
        editor_Models_LanguageResources_Resource $resource,
        editor_Models_LanguageResources_LanguageResource $languageResource = null
    ): string
    {
        $this->lastStatusInfo = '';

        // is may injected with the call
        if(!empty($languageResource)){
            $this->languageResource = $languageResource;
        }

        // for the rare cases where no language-resource is present
        if (!isset($this->languageResource)) {
            //ping call
            $this->api = ZfExtended_Factory::get(editor_Services_OpenTM2_HttpApi::class);
            $this->api->setResource($resource);

            return $this->api->status() ? LanguageResourceStatus::AVAILABLE : LanguageResourceStatus::ERROR;
        }
        
        $name = $this->languageResource->getSpecificData('fileName');

        if (empty($name)) {
            $this->lastStatusInfo = 'The internal stored filename is invalid';

            return LanguageResourceStatus::NOCONNECTION;
        }

        // let's check the internal state before calling API for status as import worker may be running
        $status = $this->languageResource->getStatus();

        if ($status === LanguageResourceStatus::IMPORT) {
            $this->lastStatusInfo = 'TM wird noch importiert und ist daher auch noch nicht nutzbar.';
            // FIXME thats not 100% correct here, since when it was crashed while the import it may stay on status import
            // need to add status reset if it hanged up
            return LanguageResourceStatus::IMPORT;
        }

        // TODO remove after reorganize status is implemented in status query on t5memory side
        if ($this->isReorganizingAtTheMoment()) {
            // Status import to prevent any other queries to TM to be performed
            return LanguageResourceStatus::IMPORT;
        }

        if ($this->api->status()) {
            $result = $this->api->getResult();

            return $this->processImportStatus(is_object($result) ? $result : null);
        }
        //down here the result contained an error, the json was invalid or HTTP Status was not 20X

        //Warning: this evaluates to "available" in the GUI, see the following explanation:
        //a 404 response from the status call means:
        // - OpenTM2 is online
        // - the requested TM is currently not loaded, so there is no info about the existence
        // - So we display the STATUS_NOT_LOADED instead
        if ($this->api->getResponse()->getStatus() === 404) {
            if ($status === LanguageResourceStatus::ERROR) {
                $this->lastStatusInfo = 'Es gab einen Fehler beim Import, bitte prüfen Sie das Fehlerlog.';

                return LanguageResourceStatus::ERROR;
            }

            $this->lastStatusInfo = 'Die Ressource ist generell verfügbar, '
                . 'stellt aber keine Informationen über das angefragte TM bereit, da dies nicht geladen ist.';

            // This will be not needed after migration to t5memory completed
            return LanguageResourceStatus::NOT_LOADED;
        }
        
        $error = $this->api->getError();

        if (empty($error->type)) {
            $this->lastStatusInfo = $error->error;
        } else {
            $this->lastStatusInfo = $error->type . ': ' . $error->error;
        }

        return LanguageResourceStatus::ERROR;
    }

    /**
     * processes the import state
     * Please note, method made public for testing purposes only,
     * should be changed to private after the class is refactored
     *
     * @param stdClass|null $apiResponse
     *
     * @return string
     */
    public function processImportStatus(?stdClass $apiResponse): string
    {
        $status = $apiResponse ? ($apiResponse->status ?? '') : '';
        $tmxImportStatus = $apiResponse ? ($apiResponse->tmxImportStatus ?? '') : '';

        $lastStatusInfo = '';
        $result = LanguageResourceStatus::UNKNOWN;

        switch ($status) {
            // TM not found at all
            case 'not found':
                // We have no status 'not found' at the moment, so we use 'error' instead
                $result = LanguageResourceStatus::ERROR;

                break;

            // TM exists on a disk, but not loaded into memory
            case 'available':
                $result = LanguageResourceStatus::AVAILABLE;
                // TODO change this to STATUS_NOT_LOADED after discussed with the team
//                $result = self::STATUS_NOT_LOADED;
                break;

            // TM exists and is loaded into memory
            case 'open':

                switch ($tmxImportStatus) {
                    case 'available':
                        if (isset($apiResponse->importTime) && $apiResponse->importTime === 'not finished') {
                            $result = LanguageResourceStatus::IMPORT;

                            break;
                        }

                        $result = LanguageResourceStatus::AVAILABLE;

                        break;

                    case 'import':
                        $lastStatusInfo = 'TMX wird importiert, TM kann trotzdem benutzt werden';
                        $result = LanguageResourceStatus::IMPORT;

                        break;

                    case 'error':
                    case 'failed':
                        $lastStatusInfo = $apiResponse->ErrorMsg;
                        $result = LanguageResourceStatus::ERROR;

                        break;

                    default:
                        break;
                }

                break;

            default:
                break;
        }

        $this->lastStatusInfo = $lastStatusInfo !== '' ? $lastStatusInfo : 'original OpenTM2 status ' . $status;

        return $result;
    }
    
    /***
     * Calculate the new matchrate value.
     * Check if the current match is of type context-match or exact-exact match
     *
     * @param int $matchRate
     * @param array $metaData
     * @param editor_Models_Segment $segment
     * @param string $filename
     *
     * @return integer
     */
    protected function calculateMatchRate($matchRate,$metaData,$segment,$filename){
        
        if($matchRate<100){
            return $matchRate;
        }
        
        $isExacExac=false;
        $isContext=false;
        foreach ($metaData as $data){
            
            //exact-exact match
            if($data->name=="documentName" && $data->value==$filename){
                $isExacExac=true;
            }

            //context metch
            if($data->name=="context" && $data->value==$segment->getMid()){
                $isContext=true;
            }
        }
        
        if($isExacExac && $isContext){
            return self::CONTEXT_MATCH_VALUE;
        }
        
        if($isExacExac){
            return self::EXACT_EXACT_MATCH_VALUE;
        }
        
        return $matchRate;
    }
    
    /***
     * Download and save the existing tm with "fuzzy" name. The new fuzzy connector will be returned.
     * @param int $analysisId
     * @throws ZfExtended_NotFoundException
     * @return editor_Services_Connector_Abstract
     */
    public function initForFuzzyAnalysis($analysisId)
    {
        $mime = 'TM';
        // TODO FIXME: This brings the "Mother-TM" into fuzzy-mode, why is this done ? Maybe a historic artefact due to the ugly "clone" in the base-implementation ??
        $this->isInternalFuzzy = true;
        $validExportTypes = $this->getValidExportTypes();

        if (empty($validExportTypes[$mime])) {
            throw new ZfExtended_NotFoundException('Can not download in format ' . $mime);
        }

        $fuzzyFileName = $this->renderFuzzyLanguageResourceName($this->languageResource->getSpecificData('fileName'), $analysisId);
        $this->api->setResource($this->languageResource->getResource());

        // TODO T5MEMORY: remove when OpenTM2 is out of production
        if ($this->api->isOpenTM2()) {
            $data = $this->getTm($validExportTypes[$mime]);
            $this->api->createMemory($fuzzyFileName, $this->languageResource->getSourceLangCode(), $data);
        } else {
            // HOTFIX for t5memory BUG: After a clone call the clone might is corrupt, if the cloned TM has (recent) updates
            // an export of the cloned memory before seems to heal that (either as TM or TMX)
            $this->getTm($validExportTypes[$mime]);
            sleep(1);
            $this->api->cloneMemory($fuzzyFileName);
            sleep(1);
        }

        $fuzzyLanguageResource = clone $this->languageResource;

        //visualized name:
        $fuzzyLanguageResourceName = $this->renderFuzzyLanguageResourceName($this->languageResource->getName(), $analysisId);
        $fuzzyLanguageResource->setName($fuzzyLanguageResourceName);
        $fuzzyLanguageResource->addSpecificData('fileName', $fuzzyFileName);
        //INFO: The resources logging requires resource with valid id.
        //$fuzzyLanguageResource->setId(null);

        $connector = ZfExtended_Factory::get(self::class);
        $connector->connectTo($fuzzyLanguageResource, $this->languageResource->getSourceLang(), $this->languageResource->getTargetLang());
        // copy the current config (for task specific config)
        $connector->setConfig($this->getConfig());
        // copy the worker user guid
        $connector->setWorkerUserGuid($this->getWorkerUserGuid());
        $connector->isInternalFuzzy = true;
        $connector->parentApi = $this->api; // needed by the fuzzy connector to reorganize the parent TM if neccessary

        return $connector;
    }
    
    /***
     * Get the result list where the >=100 matches with the same target are grouped as 1 match.
     * @return editor_Services_ServiceResult|number
     */
    public function getResultListGrouped() {
        $allResults=$this->resultList->getResult();
        if(empty($allResults)){
            return $this->resultList;
        }
        
        $showMultiple100PercentMatches = $this->config->runtimeOptions->LanguageResources->opentm2->showMultiple100PercentMatches;
        
        $other=array();
        $differentTargetResult=array();
        $document=array();
        $target=null;
        $resultlist=$this->resultList;
        //filter and collect the results
        //all 100>= matches with same target will be collected
        //all <100 mathes will be collected
        //all documentName and documentShortName will be collected from matches >=100
        $filterArray = array_filter($allResults, function ($var) use(&$other,&$document,&$target,&$differentTargetResult,$resultlist,$showMultiple100PercentMatches) {
            //collect lower then 100 matches to separate array
            if($var->matchrate<100){
                $other[]=$var;
                return false;
            }
            //set the compare target
            if(!isset($target)){
                $target=$var->target;
            }
            
            //is with same target or show multiple id disabled collect >=100 match for later sorting
            if($var->target==$target || !$showMultiple100PercentMatches){
                $document[]=array(
                    'documentName'=>$resultlist->getMetaValue($var->metaData, 'documentName'),
                    'documentShortName'=>$resultlist->getMetaValue($var->metaData, 'documentShortName'),
                );
                return true;
            }
            //collect different target result
            $differentTargetResult[]=$var;
            return false;
        });
        
        //sort by highes matchrate from the >=100 match results, when same matchrate sort by timestamp
        usort($filterArray,function($item1,$item2) use($resultlist){
            //FIXME UGLY UGLY
            // the whole existing code of reducing double 100% matches (getResultListGrouped) must be moved to the processing of the search results for the UI usage of matches
            // this is nothing which should be handled so deep inside of the connector
            // the connector should not make any decision about sorting or so, this is business logic on a higher level, a connector should be only about connecting...
            // if this is moved, there is no need to contain the isFuzzy check anymore since there is then no fuzzy usage anymore.
            $item1IsFuzzy = preg_match('#^translate5-unique-id\[[^\]]+\]$#', $item1->target);
            $item2IsFuzzy = preg_match('#^translate5-unique-id\[[^\]]+\]$#', $item2->target);
            if($item1IsFuzzy && !$item2IsFuzzy) {
                return 1;
            }
            if(!$item1IsFuzzy && $item2IsFuzzy) {
                return -1;
            }
            if ($item1->matchrate == $item2->matchrate){
                return date($resultlist->getMetaValue($item1->metaData, 'timestamp'))<date($resultlist->getMetaValue($item2->metaData, 'timestamp')) ? 1 : -1;
            }
            return ($item1->matchrate < $item2->matchrate) ? 1 : -1;
        });
        
        if(!empty($filterArray)){
            //get the highest >=100 match, and apply the documentName and documentShrotName from all >=100 matches
            $filterArray=$filterArray[0];
            foreach ($filterArray->metaData as $md) {
                if($md->name=='documentName'){
                    $md->value=implode(';',array_column($document, 'documentName'));
                }
                if($md->name=='documentShortName'){
                    $md->value=implode(';',array_column($document, 'documentShortName'));
                }
            }
        }

        //if it is single result, init it as array
        if(!is_array($filterArray)){
            $filterArray=[$filterArray];
        }
        
        //merge all available results
        $result=array_merge($filterArray,$differentTargetResult);
        $result=array_merge($result,$other);
        
        $this->resultList->resetResult();
        $this->resultList->setResults($result);
        return $this->resultList;
    }
    
    /***
     * Reduce the given matchrate to given percent.
     * It is used when unsupported tags are found in the response result, and those tags are removed.
     * @param integer $matchrate
     * @param integer $reducePercent
     * @return number
     */
    protected function reduceMatchrate($matchrate,$reducePercent) {
        //reset higher matches than 100% to 100% match
        //if the matchrate is higher than 0, reduce it by $reducePercent %
        return max(0, min($matchrate, 100) - $reducePercent);
    }

    #region Reorganize TM
    // Need to move this region to a dedicated class while refactoring connector
    private const REORGANIZE_STARTED_AT = 'reorganize_started_at';
    private const MAX_REORGANIZE_TIME_MINUTES = 30;

    private function needsReorganizing(stdClass $error): bool
    {
        if ($this->api->isOpentm2()) {
            return false;
        }

        $errorCodes = explode(
            ',',
            $this->config->runtimeOptions->LanguageResources->t5memory->reorganizeErrorCodes
        );

        $errorSupposesReorganizing = (isset($error->code)
                && str_replace($errorCodes, '', $error->code) !== $error->code
            )
            || (isset($error->error) && $error->error === 500);

        // Check if error codes contains any of the values
        return $errorSupposesReorganizing
            && !$this->isReorganizingAtTheMoment()
            && !$this->isReorganizeFailed();
    }

    public function reorganizeTm(): bool
    {
        if (!$this->isInternalFuzzy()) {
            // TODO In editor_Services_Manager::visitAllAssociatedTms language resource is initialized
            // without refreshing from DB, which leads th that here it is tried to be inserted as new one
            // so refreshing it here. Need to check if we can do this in editor_Services_Manager::visitAllAssociatedTms
            $this->languageResource->refresh();
            $this->languageResource->setStatus(LanguageResourceStatus::REORGANIZE_IN_PROGRESS);
            $this->languageResource->addSpecificData(
                self::REORGANIZE_STARTED_AT,
                date(DateTimeInterface::RFC3339)
            );
            $this->languageResource->save();
        }

        // HOTFIX for t5memory BUG: It seems a reorganize may deletes recently updated segments
        // an export of the cloned memory before seems to heal that
        $validExportTypes = $this->getValidExportTypes();
        $this->getTm($validExportTypes['TM']);
        sleep(1);
        $reorganized = $this->api->reorganizeTm();

        if($this->isInternalFuzzy()){

            $this->fuzzyReorganize = time();
            $this->waitForReorganization();
            return true;

        } else {

            $this->languageResource->setStatus(
                $reorganized ? LanguageResourceStatus::AVAILABLE : LanguageResourceStatus::REORGANIZE_FAILED
            );
            $this->languageResource->save();
        }

        return $reorganized;
    }

    public function isReorganizingAtTheMoment(): bool
    {
        if($this->fuzzyReorganize > 0){
            return true;
        }
        $this->resetReorganizingIfNeeded();

        return $this->languageResource->getStatus() === LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
    }

    public function isReorganizeFailed(): bool
    {
        return $this->languageResource->getStatus() === LanguageResourceStatus::REORGANIZE_FAILED;
    }

    private function addReorganizeWarning(Task $task = null): void
    {
        $params = [
            'apiError' => $this->api->getError(),
        ];

        if (null !== $task) {
            $params['task'] = $task;
        }

        $this->logger->warn(
            'E1314',
            'The queried TM returned error which is configured for automatic TM reorganization',
            $params
        );
    }

    private function resetReorganizingIfNeeded(): void
    {
        $reorganizeStartedAt = $this->languageResource->getSpecificData(self::REORGANIZE_STARTED_AT);

        if (null === $reorganizeStartedAt || $this->isInternalFuzzy()) {
            return;
        }

        if ((new DateTimeImmutable($reorganizeStartedAt))
                ->modify(sprintf('+%d minutes', self::MAX_REORGANIZE_TIME_MINUTES)) < new DateTimeImmutable()
        ) {
            // TODO In editor_Services_Manager::visitAllAssociatedTms language resource is initialized
            // without refreshing from DB, which leads th that here it is tried to be inserted as new one
            // so refreshing it here. Need to check if we can do this in editor_Services_Manager::visitAllAssociatedTms
            $this->languageResource->refresh();
            $this->languageResource->removeSpecificData(self::REORGANIZE_STARTED_AT);
            $this->languageResource->setStatus(LanguageResourceStatus::AVAILABLE);
            $this->languageResource->save();
        }
    }
    #endregion Reorganize TM

    /**
     * This is forced to be public, because part of its functionality is used outside of this class
     * Needs to be removed when refactoring connector
     *
     * @return editor_Services_OpenTM2_HttpApi
     */
    public function getApi(): editor_Services_OpenTM2_HttpApi
    {
        return $this->api;
    }

    /**
     * Helper to wait for a internal reorganization
     * @throws editor_Services_Connector_Exception
     * @throws editor_Services_Exceptions_InvalidResponse
     */
    private function waitForReorganization()
    {
        while($this->fuzzyReorganize > 0){
            // if reorganize takes too long we end with exception
            if((time() - $this->fuzzyReorganize) > T5Memory::REQUEST_TIMEOUT){
                throw new editor_Services_Connector_Exception('E1512');
            }
            // wait 10 sec
            sleep(10);
            // if TM is answering, we assume reorganize succeeded
            if($this->api->isRequestable()){
                $this->fuzzyReorganize = -1;
                return;
            }
        }
    }
}
