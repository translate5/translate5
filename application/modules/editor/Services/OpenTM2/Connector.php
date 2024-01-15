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

use editor_Models_Segment_Whitespace as Whitespace;
use editor_Models_Task as Task;
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\T5memory\T5NTagSchemaFixFilter;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\ContentProtection\WhitespaceProtector;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\Service\T5Memory;

/**
 * T5memory / OpenTM2 Connector
 *
 * IMPORTANT: see the doc/comments in MittagQI\Translate5\Service\T5Memory
 */
class editor_Services_OpenTM2_Connector extends editor_Services_Connector_FilebasedAbstract implements UpdatableAdapterInterface
{
    private const CONCORDANCE_SEARCH_NUM_RESULTS = 20;
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

    private TmConversionService $conversionService;
    private ContentProtector $contentProtector;

    public function __construct()
    {
        editor_Services_Connector_Exception::addCodes([
            'E1314' => 'The queried OpenTM2 TM "{tm}" is corrupt and must be reorganized before usage!',
            'E1333' => 'The queried OpenTM2 server has to many open TMs!',
        ]);

        //ZfExtended_Logger::addDuplicatesByMessage('E1314');
        ZfExtended_Logger::addDuplicatesByEcode('E1333', 'E1306', 'E1314');

        $this->contentProtector = ContentProtector::create(ZfExtended_Factory::get(Whitespace::class));
        $this->conversionService = new TmConversionService(new ContentProtectionRepository(), $this->contentProtector);

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
        $this->api = ZfExtended_Factory::get('editor_Services_OpenTM2_HttpApi');
        $this->api->setLanguageResource($languageResource);

        // TODO T5MEMORY: remove when OpenTM2 is out of production
        // t5 memory is not needing the OpenTM2 specific Xliff TagHandler, the default XLIFF TagHandler is sufficient
        if (!$this->api->isOpenTM2()
            && $this->tagHandler instanceof editor_Services_Connector_TagHandler_OpenTM2Xliff) {
            $this->tagHandler = ZfExtended_Factory::get(
                editor_Services_Connector_TagHandler_T5MemoryXliff::class,
                [['gTagPairing' => false]]
            );
        }
        parent::connectTo($languageResource, $sourceLang, $targetLang);
    }

    /**
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see editor_Services_Connector_FilebasedAbstract::addTm()
     */
    public function addTm(array $fileinfo = null, array $params = null): bool
    {
        $sourceLang = $this->languageResource->getSourceLangCode();

        //to ensure that we get unique TMs Names although of the above stripped content,
        // we add the LanguageResource ID and a prefix which can be configured per each translate5 instance
        $name = 'ID' . $this->languageResource->getId() . '-' . $this->filterName($this->languageResource->getName());

        if (isset($params['createNewMemory'])) {
            $name = $this->generateNextMemoryName($this->languageResource);
        }

        // If we are adding a TMX file as LanguageResource, we must create an empty memory first.
        $validFileTypes = $this->getValidFiletypes();
        if (empty($validFileTypes['TMX'])) {
            throw new ZfExtended_NotFoundException('OpenTM2: Cannot addTm for TMX-file; valid file types are missing.');
        }

        $noFile = empty($fileinfo);
        $tmxUpload = !$noFile
            && in_array($fileinfo['type'], $validFileTypes['TMX'])
            && preg_match('/\.tmx$/', $fileinfo['name']);

        if ($noFile || $tmxUpload) {
            $tmName = $this->api->createEmptyMemory($name, $sourceLang);

            if (null !== $tmName) {
                $this->addMemoryToLanguageResource($tmName);

                //if initial upload is a TMX file, we have to import it.
                if ($tmxUpload) {
                    return $this->addAdditionalTm($fileinfo, ['tmName' => $tmName]);
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
        $tmName = $this->api->createMemory($name, $sourceLang, file_get_contents($fileinfo['tmp_name']));
        if ($tmName) {
            $this->addMemoryToLanguageResource($tmName);

            return true;
        }

        $this->logger->error('E1304', 'OpenTM2: could not create prefilled TM', [
            'languageResource' => $this->languageResource,
            'apiError' => $this->api->getError(),
        ]);

        return false;
    }

    /**
     * Updates the filename of the language resource instance with the filename coming from the TM system
     * @throws Zend_Exception
     */
    private function addMemoryToLanguageResource(string $tmName): void
    {
        $prefix = Zend_Registry::get('config')->runtimeOptions->LanguageResources->opentm2->tmprefix;
        if (!empty($prefix)) {
            //remove the prefix from being stored into the TM
            $tmName = str_replace('^' . $prefix . '-', '', '^' . $tmName);
        }

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        foreach ($memories as &$memory) {
            $memory['readonly'] = true;
        }

        $memories[] = ['id' => count($memories) + 1, 'filename' => $tmName, 'readonly' => false];

        $this->languageResource->addSpecificData('memories', $memories);
        //saving it here makes the TM available even when the TMX import was crashed
        $this->languageResource->save();
    }

    /**
     * {@inheritDoc}
     */
    public function addAdditionalTm(array $fileinfo = null, array $params = null): bool
    {
        try {
            $importFilename = $this->conversionService->convertTMXForImport(
                $fileinfo['tmp_name'],
                (int) $this->languageResource->getSourceLang(),
                (int) $this->languageResource->getTargetLang()
            );
        } catch (RuntimeException $e) {
            $this->logger->error(
                'E1590',
                'Conversion: Error in process of TMX file conversion',
                [
                    'reason' => $e->getMessage(),
                    'languageResource' => $this->languageResource
                ]
            );

            return false;
        }

        $result = $this->importTmxIntoMemory(
            file_get_contents($importFilename),
            $params['tmName'] ?? $this->getWritableMemory()
        );

        unlink($importFilename);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getValidFiletypes(): array
    {
        return [
            'TM' => ['application/zip'],
            'TMX' => ['application/xml', 'text/xml'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getValidExportTypes(): array
    {
        return [
            'TM' => 'application/zip',
            'TMX' => 'application/xml',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTm($mime, string $tmName = '')
    {
        if (empty($tmName)) {
            $tmName = $this->getWritableMemory();
        }

        if ($this->api->get($mime, $tmName)) {
            return $this->api->getResult();
        }

        $this->throwBadGateway();
    }

    public function update(editor_Models_Segment $segment, bool $recheckOnUpdate = self::DO_NOT_RECHECK_ON_UPDATE): void
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

        $tmName = $this->getWritableMemory();

        $successful = $this->api->update($source, $target, $segment, $fileName, $tmName, !$this->isInternalFuzzy);

        if ($successful) {
            $this->checkUpdatedSegment($segment, $recheckOnUpdate);

            return;
        }

        if ($this->needsReorganizing($this->api->getError())) {
            $this->addReorganizeWarning($segment->getTask());
            $this->reorganizeTm($tmName);

            $successful = $this->api->update($source, $target, $segment, $fileName, $tmName, !$this->isInternalFuzzy);

            if ($successful) {
                $this->checkUpdatedSegment($segment, $recheckOnUpdate);

                return;
            }
        } elseif ($this->isMemoryOverflown($this->api->getError())) {
            $newName = $this->generateNextMemoryName($this->languageResource);
            $newName = $this->api->createEmptyMemory($newName, $this->languageResource->getSourceLangCode());
            $this->addMemoryToLanguageResource($newName);

            $successful = $this->api->update($source, $target, $segment, $fileName, $tmName, !$this->isInternalFuzzy);

            if ($successful) {
                $this->checkUpdatedSegment($segment, $recheckOnUpdate);

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

    public function updateTranslation(string $source, string $target, string $tmName = '')
    {
        if (empty($tmName)) {
            $tmName = $this->getWritableMemory();
        }
        $this->api->updateText($source, $target, $tmName);
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
        $resultList = $this->queryTm($queryString, $segment, $fileName);

        if (empty($resultList->getResult())) {
            return $resultList;
        }

        return $this->getResultListGrouped($resultList);
    }

    /**
     * returns the filename to a segment
     * @param editor_Models_Segment $segment
     * @return string
     */
    protected function getFileName(editor_Models_Segment $segment): string
    {
        return editor_ModelInstances::file($segment->getFileId())->getFileName();
    }

    /**
     * Helper function to get the metadata which should be shown in the GUI out of a single result
     *
     * @return object[]
     */
    private function getMetaData(object $found): array
    {
        $nameToShow = [
            "documentName",
            "matchType",
            "author",
            "timestamp",
            "context",
            "additionalInfo",
        ];
        $result = [];

        foreach ($nameToShow as $name) {
            if (property_exists($found, $name)) {
                $item = new stdClass();
                $item->name = $name;
                $item->value = $found->{$name};
                if ($name == 'timestamp') {
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
        $offsetTmId = null;
        $tmOffset = null;

        if (null !== $offset) {
            @[$offsetTmId, $tmOffset] = explode(':', (string) $offset);
        }

        if ('' !== $offsetTmId && null === $tmOffset) {
            throw new editor_Services_Connector_Exception('E1565', compact('offset'));
        }

        if (null !== $tmOffset) {
            $tmOffset = (int) $tmOffset;
        }

        $isSource = $field === 'source';

        $searchString = $this->tagHandler->prepareQuery(
            $this->contentProtector->protect(
                $searchString,
                $isSource,
                (int) $this->languageResource->getSourceLang(),
                (int) $this->languageResource->getSourceLang(),
                ContentProtector::ENTITY_MODE_RESTORE,
                WhitespaceProtector::alias()
            )
        );

        $resultList = new editor_Services_ServiceResult();
        $resultList->setLanguageResource($this->languageResource);

        $results = [];
        $resultsCount = 0;

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        foreach ($memories as ['filename' => $tmName, 'id' => $id]) {
            // check if current memory was searched through in prev request
            if ('' !== $offsetTmId && $id < $offsetTmId) {
                continue;
            }

            $numResults = self::CONCORDANCE_SEARCH_NUM_RESULTS - $resultsCount;

            $successful = $this->api->search($searchString, $tmName, $field, $tmOffset, $numResults);

            if (!$successful && $this->needsReorganizing($this->api->getError())) {
                $this->addReorganizeWarning();
                $this->reorganizeTm($tmName);
                $successful = $this->api->search($searchString, $tmName, $field, $tmOffset, $numResults);
            }

            if (!$successful) {
                $this->logger->exception($this->getBadGatewayException($tmName));

                continue;
            }

            $result = $this->api->getResult();

            if (empty($result) || empty($result->results)) {
                continue;
            }

            $results[] = $result->results;
            $resultsCount += count($result->results);
            $resultList->setNextOffset($id . ':' . $result->NewSearchPosition);

            // if we get enough results then response them
            if (self::CONCORDANCE_SEARCH_NUM_RESULTS <= $resultsCount) {
                break;
            }
        }

        $results = array_merge(...$results);

        if (empty($results)) {
            $resultList->setNextOffset(null);

            return $resultList;
        }

        //$found->{$field}
        //[NextSearchPosition] =>
        foreach ($results as $result) {
            $searchString = $this->conversionService->convertT5MemoryTagToNumber($searchString);
            $resultList->addResult($this->highlight(
                $searchString,
                $this->tagHandler->restoreInResult($result->target, $isSource, true),
                $field === 'target'
            ));
            $resultList->setSource($this->highlight(
                $searchString,
                $this->tagHandler->restoreInResult($result->source, $isSource, true),
                $isSource)
            );
        }
        error_log(print_r($resultList->getResult(), true));
        return $resultList;
    }

    /***
     * Search the resource for available translation. Where the source text is in
     * resource source language and the received results are in the resource target language
     *
     * {@inheritDoc}
     */
    public function translate(string $searchString)
    {
        //create dummy segment so we can use the lookup
        $dummySegment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $dummySegment editor_Models_Segment */
        $dummySegment->init();

        return $this->queryTm($searchString, $dummySegment, 'source');
    }

    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::delete()
     */
    public function delete(): void
    {
        $successfullyDeleted = true;

        foreach ($this->languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
            $successfullyDeleted = $successfullyDeleted && $this->deleteMemory($memory['filename']);
        }

        if (!$successfullyDeleted) {
            $this->throwBadGateway();
        }
    }

    public function deleteMemory(string $filename): bool
    {
        $deleted = $this->api->delete($filename);

        if ($deleted) {
            return true;
        }

        $resp = $this->api->getResponse();

        if ($resp->getStatus() == 404) {
            // if the result was a 404, then there is nothing to delete,
            // so throw no error then and delete just locally
            return true;
        }

        return false;
    }

    /**
     * Throws a service connector exception
     * @throws editor_Services_Connector_Exception
     */
    private function throwBadGateway(): void
    {
        throw $this->getBadGatewayException();
    }

    private function getBadGatewayException(string $tmName = ''): editor_Services_Connector_Exception
    {
        $ecode = 'E1313';
        $error = $this->api->getError();
        $data = [
            'service' => $this->getResource()->getName(),
            'languageResource' => $this->languageResource ?? '',
            'tmName' => $tmName,
            'error' => $error,
        ];
        if (strpos($error->error ?? '', 'needs to be organized') !== false) {
            $ecode = 'E1314';
            $data['tm'] = $this->languageResource->getName();
        }

        if (strpos($error->error ?? '', 'too many open translation memory databases') !== false) {
            $ecode = 'E1333';
        }

        return new editor_Services_Connector_Exception($ecode, $data);
    }

    /**
     * Replaces not allowed characters with "_" in memory names
     * @param string $name
     * @return string
     */
    private function filterName($name)
    {
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
        editor_Models_LanguageResources_LanguageResource $languageResource = null,
        ?string $tmName = null
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

            return $this->api->status(null) ? LanguageResourceStatus::AVAILABLE : LanguageResourceStatus::ERROR;
        }

        $name = $tmName ?: $this->getWritableMemory();

        if (empty($name)) {
            $this->lastStatusInfo = 'The internal stored filename is invalid';

            return LanguageResourceStatus::NOCONNECTION;
        }

        // let's check the internal state before calling API for status as import worker may be running
        $status = $this->languageResource->getStatus();

        // TODO remove after reorganize status is implemented in status query on t5memory side
        if ($this->isReorganizingAtTheMoment()) {
            // Status import to prevent any other queries to TM to be performed
            return LanguageResourceStatus::IMPORT;
        }

        if ($this->api->status($name)) {
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

    /**
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
    private function calculateMatchRate($matchRate, $metaData, $segment, $filename)
    {

        if ($matchRate < 100) {
            return $matchRate;
        }

        $isExacExac = false;
        $isContext = false;
        foreach ($metaData as $data) {

            //exact-exact match
            if ($data->name == "documentName" && $data->value == $filename) {
                $isExacExac = true;
            }

            //context metch
            if ($data->name == "context" && $data->value == $segment->getMid()) {
                $isContext = true;
            }
        }

        if ($isExacExac && $isContext) {
            return self::CONTEXT_MATCH_VALUE;
        }

        if ($isExacExac) {
            return self::EXACT_EXACT_MATCH_VALUE;
        }

        return $matchRate;
    }

    /***
     * Download and save the existing tm with "fuzzy" name. The new fuzzy connector will be returned.
     * @param int $analysisId
     * @return editor_Services_Connector_Abstract
     * @throws ZfExtended_NotFoundException
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

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);
        $fuzzyMemories = [];

        foreach ($memories as ['filename' => $memory, 'readonly' => $readonly]) {
            $fuzzyFileName = $this->renderFuzzyLanguageResourceName($memory, $analysisId);
            $this->api->setResource($this->languageResource->getResource());

            // TODO T5MEMORY: remove when OpenTM2 is out of production
            if ($this->api->isOpenTM2()) {
                $data = $this->getTm($validExportTypes[$mime], $memory);
                $this->api->createMemory($fuzzyFileName, $this->languageResource->getSourceLangCode(), $data);
            } else {
                // HOTFIX for t5memory BUG:
                // After a clone call the clone might is corrupt, if the cloned TM has (recent) updates
                // an export of the cloned memory before seems to heal that (either as TM or TMX)
                $this->getTm($validExportTypes[$mime], $memory);
                sleep(1);
                $this->api->cloneMemory($fuzzyFileName, $memory);
                sleep(1);
            }

            $fuzzyMemories[] = ['filename' => $fuzzyFileName, 'readonly' => $readonly];
        }

        $fuzzyLanguageResource = clone $this->languageResource;

        //visualized name:
        $fuzzyLanguageResourceName = $this->renderFuzzyLanguageResourceName(
            $this->languageResource->getName(),
            $analysisId
        );
        $fuzzyLanguageResource->setName($fuzzyLanguageResourceName);
        $fuzzyLanguageResource->addSpecificData('memories', $fuzzyMemories);
        //INFO: The resources logging requires resource with valid id.
        //$fuzzyLanguageResource->setId(null);

        $connector = ZfExtended_Factory::get(self::class);
        $connector->connectTo(
            $fuzzyLanguageResource,
            $this->languageResource->getSourceLang(),
            $this->languageResource->getTargetLang()
        );
        // copy the current config (for task specific config)
        $connector->setConfig($this->getConfig());
        // copy the worker user guid
        $connector->setWorkerUserGuid($this->getWorkerUserGuid());
        $connector->isInternalFuzzy = true;
        // needed by the fuzzy connector to reorganize the parent TM if neccessary
        $connector->parentApi = $this->api;

        return $connector;
    }

    /***
     * Get the result list where the >=100 matches with the same target are grouped as 1 match.
     * @return editor_Services_ServiceResult|number
     */
    private function getResultListGrouped(editor_Services_ServiceResult $resultList)
    {
        $allResults = $resultList->getResult();
        if (empty($allResults)) {
            return $resultList;
        }

        $showMultiple100PercentMatches = $this->config
            ->runtimeOptions
            ->LanguageResources
            ->opentm2
            ->showMultiple100PercentMatches;

        $other = [];
        $differentTargetResult = [];
        $document = [];
        $target = null;
        //filter and collect the results
        //all 100>= matches with same target will be collected
        //all <100 mathes will be collected
        //all documentName and documentShortName will be collected from matches >=100
        $filterArray = array_filter(
            $allResults,
            function ($result) use (
                &$other,
                &$document,
                &$target,
                &$differentTargetResult,
                $resultList,
                $showMultiple100PercentMatches
            ) {
                //collect lower than 100 matches to separate array
                if ($result->matchrate < 100) {
                    $other[] = $result;

                    return false;
                }
                //set the compare target
                if (!isset($target)) {
                    $target = $result->target;
                }

                //is with same target or show multiple id disabled collect >=100 match for later sorting
                if ($result->target == $target || !$showMultiple100PercentMatches) {
                    $document[] = array(
                        'documentName' => $resultList->getMetaValue($result->metaData, 'documentName'),
                        'documentShortName' => $resultList->getMetaValue($result->metaData, 'documentShortName'),
                    );
                    return true;
                }
                //collect different target result
                $differentTargetResult[] = $result;

                return false;
            }
        );

        //sort by highest match-rate from the >=100 match results, when same match-rate sort by timestamp
        usort($filterArray, function ($item1, $item2) use ($resultList) {
            //FIXME UGLY UGLY
            // the whole existing code of reducing double 100% matches (getResultListGrouped) must be moved to the processing of the search results for the UI usage of matches
            // this is nothing which should be handled so deep inside of the connector
            // the connector should not make any decision about sorting or so, this is business logic on a higher level, a connector should be only about connecting...
            // if this is moved, there is no need to contain the isFuzzy check anymore since there is then no fuzzy usage anymore.
            $item1IsFuzzy = preg_match('#^translate5-unique-id\[[^\]]+\]$#', $item1->target);
            $item2IsFuzzy = preg_match('#^translate5-unique-id\[[^\]]+\]$#', $item2->target);

            if ($item1IsFuzzy && !$item2IsFuzzy) {
                return 1;
            }

            if (!$item1IsFuzzy && $item2IsFuzzy) {
                return -1;
            }

            if ($item1->matchrate == $item2->matchrate) {
                $date1 = date($resultList->getMetaValue($item1->metaData, 'timestamp'));
                $date2 = date($resultList->getMetaValue($item2->metaData, 'timestamp'));

                return  $date1 < $date2 ? 1 : -1;
            }

            return ($item1->matchrate < $item2->matchrate) ? 1 : -1;
        });

        if (!empty($filterArray)) {
            //get the highest >=100 match, and apply the documentName and documentShrotName from all >=100 matches
            $filterArray = $filterArray[0];
            foreach ($filterArray->metaData as $md) {
                if ($md->name == 'documentName') {
                    $md->value = implode(';', array_column($document, 'documentName'));
                }
                if ($md->name == 'documentShortName') {
                    $md->value = implode(';', array_column($document, 'documentShortName'));
                }
            }
        }

        //if it is single result, init it as array
        if (!is_array($filterArray)) {
            $filterArray = [$filterArray];
        }

        //merge all available results
        $result = array_merge($filterArray, $differentTargetResult);
        $result = array_merge($result, $other);

        $resultList->resetResult();
        $resultList->setResults($result);

        return $resultList;
    }

    /***
     * Reduce the given matchrate to given percent.
     * It is used when unsupported tags are found in the response result, and those tags are removed.
     * @param integer $matchrate
     * @param integer $reducePercent
     * @return number
     */
    protected function reduceMatchrate($matchrate, $reducePercent)
    {
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

    public function reorganizeTm(?string $tmName = null): bool
    {
        if (null === $tmName) {
            $tmName = $this->getWritableMemory();
        }

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
        $this->getTm($validExportTypes['TM'], $tmName);
        sleep(1);
        $reorganized = $this->api->reorganizeTm($tmName);

        if ($this->isInternalFuzzy()) {
            $this->fuzzyReorganize = time();
            $this->waitForReorganization($tmName);

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
        if ($this->fuzzyReorganize > 0) {
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

        if ((new DateTimeImmutable($reorganizeStartedAt))->modify(
                sprintf('+%d minutes', self::MAX_REORGANIZE_TIME_MINUTES)
            ) < new DateTimeImmutable()
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

    /**
     * Helper to wait for a internal reorganization
     *
     * @param string $tmName
     * @throws editor_Services_Connector_Exception
     * @throws editor_Services_Exceptions_InvalidResponse
     */
    private function waitForReorganization(string $tmName): void
    {
        while ($this->fuzzyReorganize > 0) {
            // if reorganize takes too long we end with exception
            if ((time() - $this->fuzzyReorganize) > T5Memory::REQUEST_TIMEOUT) {
                throw new editor_Services_Connector_Exception('E1512');
            }

            // wait 10 sec
            sleep(10);

            // if TM is answering, we assume reorganize succeeded
            if ($this->api->isRequestable($tmName)) {
                $this->fuzzyReorganize = -1;
                return;
            }
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

    private function queryTm(
        string $queryString,
        editor_Models_Segment $segment,
        string $fileName
    ): editor_Services_ServiceResult {
        $resultList = new editor_Services_ServiceResult();
        $resultList->setLanguageResource($this->languageResource);

        //if source is empty, OpenTM2 will return an error, therefore we just return an empty list
        if (empty($queryString) && $queryString !== '0') {
            return $resultList;
        }

        //Although we take the source fields from the OpenTM2 answer below
        // we have to set the default source here to fill the be added internal tags
        $resultList->setDefaultSource($queryString);
        $query = $this->tagHandler->prepareQuery($queryString);
        $results = [];

        foreach ($this->languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
            $successful = $this->api->lookup($segment, $query, $fileName, $memory['filename']);

            if (!$successful && $this->needsReorganizing($this->api->getError())) {
                $this->addReorganizeWarning($segment->getTask());
                $this->reorganizeTm($memory['filename']);
                $successful = $this->api->lookup($segment, $query, $fileName, $memory['filename']);
            }

            if (!$successful) {
                $this->logger->exception($this->getBadGatewayException($memory['filename']));

                continue;
            }

            $result = $this->api->getResult();

            if ((int)$result->NumOfFoundProposals === 0) {
                continue;
            }

            $results[] = $result->results;
        }

        if (empty($results)) {
            return $resultList;
        }

        $results = array_merge(...$results);

        foreach ($results as $found) {
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
            $resultList->addResult($target, $matchrate, $this->getMetaData($found), $found->target);
            $resultList->setSource($source);
        }

        return $resultList;
    }

    private function getWritableMemory(): string
    {
        foreach ($this->languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
            if (!$memory['readonly']) {
                return $memory['filename'];
            }
        }

        throw new editor_Services_Connector_Exception('E1564', [
            'name' => $this->languageResource->getName(),
        ]);
    }

    private function isMemoryOverflown(object $error): bool
    {
        $errorCodes = explode(
            ',',
            $this->config->runtimeOptions->LanguageResources->t5memory->memoryOverflowErrorCodes
        );
        $errorCodes = array_map(fn($code) => 'rc = ' . $code, $errorCodes);

        return isset($error->error)
            && str_replace($errorCodes, '', $error->error) !== $error->error;
    }

    private function importTmxIntoMemory(string $fileContent, string $tmName): bool
    {
        $successful = false;

        try {
            $successful = $this->api->importMemory($fileContent, $tmName);

            if (!$successful) {
                $this->logger->error('E1303', 'OpenTM2: could not add TMX data to TM', [
                    'languageResource' => $this->languageResource,
                    'apiError' => $this->api->getError(),
                ]);
            }

            if (!$successful && $this->needsReorganizing($this->api->getError())) {
                $this->addReorganizeWarning();
                $this->reorganizeTm($tmName);
            }
        } catch (editor_Models_Import_FileParser_InvalidXMLException $e) {
            $e->addExtraData([
                'languageResource' => $this->languageResource,
            ]);
            $this->logger->exception($e);
        }

        $this->waitForImportFinish($tmName);
        $status = $this->getStatus($this->languageResource->getResource(), $this->languageResource, $tmName);

        $error = $this->api->getError();
        // In case we've got memory overflow error we need to create another memory and import further
        if ($status === LanguageResourceStatus::ERROR && $this->isMemoryOverflown($error)) {
            $newName = $this->generateNextMemoryName($this->languageResource);
            $newName = $this->api->createEmptyMemory($newName, $this->languageResource->getSourceLangCode());
            $this->addMemoryToLanguageResource($newName);

            // Filter TMX data from already imported segments
            $fileContent = $this->cutOffTmx(
                $fileContent,
                $this->getOverflowSegmentNumber($error->error)
            );

            // Import further
            return $this->importTmxIntoMemory($fileContent, $newName);
        }

        return $successful;
    }

    private function cutOffTmx(string $tmxData, int $segmentToStartFrom): string
    {
        $doc = new DOMDocument();
        $doc->loadXML($tmxData);

        if (!$doc->hasChildNodes()) {
            $error = libxml_get_last_error();

            // TODO vice-versa?
            if (str_contains($error->message, 'labelled UTF-16 but has UTF-8') !== false) {
                $tmxData = preg_replace('/encoding="UTF-16"/', 'encoding="UTF-8"', $tmxData, 1);
                $doc->loadXML($tmxData);
            }
        }

        unset($tmxData);

        // Create an XPath to query the document
        $xpath = new DOMXPath($doc);

        // Find all 'tu' elements
        $tuNodes = $xpath->query('/tmx/body/tu');

        // Remove 'tu' elements before the segment index
        for ($i = 0; $i < $segmentToStartFrom; $i++) {
            $tuNodes->item($i)->parentNode->removeChild($tuNodes->item($i));
        }
        unset($tuNodes);

        // Save the modified TMX data to a new variable
        return $doc->saveXML();
    }

    private function getOverflowSegmentNumber(string $error): int
    {
        preg_match('/rc = \d+; segment #(\d+) wasn\'t imported/', $error, $matches);

        if (!isset($matches[1])) {
            $this->logger->error(
                'E1313',
                't5memory responded with memory overflow error, ' .
                'but we were unable to distinguish the segment number for reimport', [
                    'languageResource' => $this->languageResource,
                    'apiError' => $error,
                ]
            );
            throw new editor_Services_Connector_Exception('E1313', ['error' => $error]);
        }

        return (int)($matches[1]);
    }

    private function waitForImportFinish(string $tmName): void
    {
        while (true) {
            $status = $this->getStatus($this->languageResource->getResource(), $this->languageResource, $tmName);

            if ($status !== LanguageResourceStatus::IMPORT) {
                break;
            }

            sleep(2);
        }
    }

    private function generateNextMemoryName(editor_Models_LanguageResources_LanguageResource $languageResource): string
    {
        $memories = $languageResource->getSpecificData('memories', parseAsArray: true);

        return $memories[0]['filename'] . '_' . count($memories);
    }

    // region export TM
    // TODO change to interface method
    public function exportsFile(): bool
    {
        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        return count($memories) >= 1;
    }

    // TODO Move to a separate class(es) during refactoring
    public function export(string $mime): string
    {
        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        if ($mime === $this->getValidExportTypes()['TMX']) {
            return $this->exportAllAsOneTmx($memories, $mime);
        }

        return $this->exportAllAsArchive($memories, $mime);
    }

    private function exportAllAsOneTmx(array $memories, string $mime): string
    {
        $exportDir = APPLICATION_PATH . '/../data/TMExport/';
        @mkdir($exportDir, recursive: true);
        $resultFilename = $exportDir . $this->languageResource->getId() . '_' . uniqid() . '.tmx';
        $writer = new XMLWriter();
        $writer->openURI($resultFilename);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        stream_filter_register('fix-t5n-tag', T5NTagSchemaFixFilter::class);

        foreach ($memories as $memoryNumber => $memory) {
            $filename = $exportDir . $memory['filename'] . '_' . uniqid() . '.tmx';
            file_put_contents(
                $filename,
                $this->getTm($mime, $memory['filename'])
            );

            $stream = "php://filter/read=fix-t5n-tag/resource=$filename";
            $reader = new XMLReader();
            $reader->open($stream);

            while ($reader->read()) {
                if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'tu') {
                    $writer->writeRaw($this->conversionService->convertT5MemoryTagToNumber($reader->readOuterXML()));
                }

                // Further code is only applicable for the first file
                if ($memoryNumber > 0) {
                    continue;
                }

                if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'header') {
                    $writer->writeRaw($reader->readOuterXML());
                }

                if (!in_array($reader->name, ['tmx', 'body'])) {
                    continue;
                }

                if ($reader->nodeType == XMLReader::ELEMENT) {
                    $writer->startElement($reader->name);

                    if ($reader->hasAttributes) {
                        while ($reader->moveToNextAttribute()) {
                            $writer->writeAttribute($reader->name, $reader->value);
                        }
                    }

                    if ($reader->isEmptyElement) {
                        $writer->endElement();
                    }
                }
            }

            $reader->close();

            unlink($filename);

            if ($memoryNumber < count($memories) - 1) {
                $writer->writeComment('Next file');
            }
        }

        $writer->flush();

        // Finalizing document with $writer->endDocument() adds closing tags for all bpt-ept tags
        // so add body and tmx closing tags manually
        file_put_contents($resultFilename, PHP_EOL . '</body>' . PHP_EOL . '</tmx>', FILE_APPEND);

        return $resultFilename;
    }

    private function exportAllAsArchive(array $memories, string $mime): string
    {
        $exportDir = APPLICATION_PATH . '/../data/TMExport/';
        $tmpDir = $exportDir . $this->languageResource->getId() . '_' . uniqid() . '/';
        @mkdir($tmpDir, recursive: true);

        foreach ($memories as $index => $memory) {
            file_put_contents($tmpDir . ($index + 1) . '.tm', $this->getTm($mime, $memory['filename']));
        }

        $zipFileName = $exportDir . $this->languageResource->getId() . '_' . uniqid() . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = basename($filePath);

                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        foreach ($files as $file) {
            if (!$file->isDir() && is_file($file->getRealPath())) {
                unlink($file->getRealPath());
            }
        }
        rmdir($tmpDir);

        return $zipFileName;
    }
    // endregion export TM

    /**
     * Check if segment was updated properly
     * and if not - add a log record for that for debug purposes
     *
     * @param editor_Models_Segment $segment
     * @param bool $recheckOnUpdate
     *
     * @return void
     */
    private function checkUpdatedSegment(editor_Models_Segment $segment, bool $recheckOnUpdate): void
    {
        if (!$this->config->runtimeOptions->LanguageResources->checkSegmentsAfterUpdate
            || !$recheckOnUpdate
        ) {
            // Checking segment after update is disabled in config or in parameter, nothing to do
            return;
        }

        $result = $this->query($segment);

        $logError = fn(string $reason) => $this->logger->error(
            'E1586',
            $reason,
            [
                'languageResource' => $this->languageResource,
                'segment' => $segment,
                'response' => json_encode($result->getResult(), JSON_PRETTY_PRINT)
            ]
        );

        $maxMatchRateResult = $result->getMaxMatchRateResult();

        // If there is no result at all, it means that segment was not saved to TM
        if (!$maxMatchRateResult) {
            $logError('Segment was not saved to TM');

            return;
        }

        // Just saved segment should have matchrate 103
        $matchRateFits = $maxMatchRateResult->matchrate === 103;

        // Target should be the same as in the segment
        $target = $this->tagHandler->prepareQuery($segment->getTargetEdit());
        // Replacing \r\n to \n back because t5memory replaces \n to \r\n
        $targetIsTheSame = str_replace("\r\n", "\n", $maxMatchRateResult->rawTarget) === $target;

        $resultTimestamp = $result->getMetaValue($maxMatchRateResult->metaData, 'timestamp');
        $resultDate = DatetimeImmutable::createFromFormat('Y-m-d H:i:s T', $resultTimestamp);
        // Timestamp should be not older than 1 minute otherwise it is an old segment which wasn't updated
        $isResultFresh = $resultDate >= new DateTimeImmutable('-1 minute');

        if (!$matchRateFits || !$targetIsTheSame || !$isResultFresh) {
            $logError(match (false) {
                $matchRateFits => 'Match rate is not 103',
                $targetIsTheSame => 'Saved segment target differs with provided',
                $isResultFresh => 'Got old result',
            });
        }
    }
}
