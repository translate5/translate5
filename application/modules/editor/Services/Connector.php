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

use MittagQI\Translate5\Segment\TagRepair\HtmlProcessor;

/**
 * Language Resource Connector class
 * - provides a connection to a concrete language resource, via the internal adapter (which contains the concrete connector instance)
 * - intercepts some calls to the adapter to provide unified logging etc per each call
 * - all non intercepted methods are passed directly to the underlying adapter
 * @method editor_Services_ServiceResult query() query(editor_Models_Segment $segment)
 * @method editor_Services_ServiceResult search() search(string $searchString, $field = 'source', $offset = null)
 * @method editor_Services_ServiceResult translate() translate(string $searchString)
 * @method void update(editor_Models_Segment $segment, $recheckOnUpdate = false) editor_Services_Connector_Abstract::update()
 * @method string getStatus() getStatus(editor_Models_LanguageResources_Resource $resource, editor_Models_LanguageResources_LanguageResource $languageResource = null) returns the LanguageResource status
 * @method string getLastStatusInfo() getLastStatusInfo() returns the last store status info from the last getStatus call
 * @method string getTm($mime, string $tmName = '') editor_Services_Connector_FilebasedAbstract::getTm()
 * @method boolean addTm(array $fileInfo = null,array $params=null) editor_Services_Connector_Abstract::addTm()
 * @method boolean addAdditionalTm(array $fileinfo = null, array $params = null) editor_Services_Connector_Abstract::addAdditionalTm()
 */
class editor_Services_Connector
{
    /***
     * The request source when language resources is used is InstantTranslate
     * @var string
     */
    const REQUEST_SOURCE_INSTANT_TRANSLATE = 'instanttranslate';
    
    /***
     * The request source when language resource is used is the editor
     * @var string
     */
    const REQUEST_SOURCE_EDITOR = 'editor';

    /***
     * An error with markup tags when parsing it for request
     * @var string
     */
    const TAG_ERROR_PREPARE = 'tagprepare';

    /***
     * An error with markup tags when re-applying the request
     * @var string
     */
    const TAG_ERROR_RECREATE = 'tagrecreate';

    /***
     * The real service connector
     * @var editor_Services_Connector_Abstract
     */
    protected $adapter;

    /***
     *
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $languageResource;
    
    /***
     * if set to true, it will get the results from the batch cache table when using the query action
     * @var boolean
     */
    protected $batchEnabled = false;

    /**
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     * @param $sourceLang
     * @param $targetLang
     * @return void
     * @throws editor_Services_Exceptions_NoService
     */
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource, $sourceLang = null, $targetLang = null): void
    {
        $this->connectToResourceOnly($languageResource->getResource());
        $this->adapter->connectTo($languageResource, $sourceLang, $targetLang);
    }
    
    /**
     * Connects to a given resource only, for requests not using a concrete language resource (ping calls for example)
     *
     * @param editor_Models_LanguageResources_Resource $resource
     */
    protected function connectToResourceOnly(editor_Models_LanguageResources_Resource $resource): void
    {
        if (method_exists($resource, 'getConnector')) {
            $connector = $resource->getConnector();
        } else {
            $connector = ZfExtended_Factory::get($resource->getServiceType() . editor_Services_Manager::CLS_CONNECTOR);
        }

        $connector->setResource($resource);
        $this->adapter = $connector;
    }
    
    /**
     * Init connector for fuzzy usage
     * TODO FIXME: The method is improperly named as it actually clones or creates a fuzzy connector
     * @param integer  $analysisId
     * @return editor_Services_Connector
     */
    public function initForFuzzyAnalysis(int $analysisId): editor_Services_Connector {
        $fuzzyConnector = clone $this;
        /* @var $fuzzyConnector editor_Services_Connector */
        $fuzzyConnector->adapter = $fuzzyConnector->adapter->initForFuzzyAnalysis($analysisId);
        return $fuzzyConnector;
    }
    
    /***
     * Invoke the query resource action so the MT logger can be used
     * @param editor_Models_Segment $segment
     * @return editor_Services_ServiceResult
     */
    protected function _query(editor_Models_Segment $segment) {
        $serviceResult = null;
        $isBatchRequest = $this->batchEnabled && $this->adapter->isBatchQuery();
        //if the batch query is enabled, get the results from the cache
        if($isBatchRequest){
            $serviceResult = $this->getCachedResult($segment);
        }else{
            $serviceResult = $this->adapter->query($segment);
        }
        //log the MT ussage when there are mt results
        //Info: for loggin TM results, the result is logged only when the result is used (segment save/update , matchrate >=100)
        //for batch query, the segments will be loged in the batch proccess
        if(!$isBatchRequest && !empty($serviceResult) && $this->isMtAdapter()){
            $this->logAdapterUsage($segment);
        }
        
        $this->adapter->logForSegment($segment);
        return $serviceResult;
    }
    
    /***
     * Invoke search resource action so the MT logger can be used
     * This is the main entry point for the concordance search
     * @param string $searchString
     * @param string $field
     * @param integer $offset
     * @return editor_Services_ServiceResult
     */
    protected function _search(string $searchString, $field = 'source', $offset = null) {
        //searches are always without tags
        return $this->adapter->search(strip_tags($searchString), $field, $offset);
    }
    
    /***
     * Invoke translate resource action so the MT logger can be used
     * This is the main entry point for InstantTranslate
     * @param string $searchString
     * @return editor_Services_ServiceResult
     */
    protected function _translate(string $searchString){

        //instant translate calls are by default always without tags ... only if the adapter supports tags we leave them
        if($this->adapter->canTranslateHtmlTags()){

            // when the service is capable of processing raw markup directly
            // we use the TagRepair's processor to automatically repair lost or "defect" tags when requesting the translation
            // InstantTranslate will preserve HTML comments which otherwise have no meaning in T5
            $processor = new HtmlProcessor(true);
            $serviceResult = $this->adapter->translate($processor->prepareRequest(trim($searchString)));
            // UGLY: The service result holds a list of results (representing the translated texts + metadata) which unfortunately have no defined format
            $results = $serviceResult->getResult();
            if(count($results) > 0){
                $results[0]->target = $processor->restoreResult($results[0]->target);
                if($processor->hasPreparationError()){
                    $results[0]->tagError = self::TAG_ERROR_PREPARE;
                } else if($processor->hasRecreationError()){
                    $results[0]->tagError = self::TAG_ERROR_RECREATE;
                }
                $serviceResult->setResults($results);
            }
        } else if($this->adapter->canTranslateInternalTags()) {

            // when the connector is able to process the internal T5 format for segment text we convert the raw markup and reconvert it after translation
            // we use the utilities broker that is already instantiated in the concrete connector
            $utilities = $this->adapter->getTagHandler()->getUtilities();
            // protect tags to t5 internal tags & convert whitespace to t5 whitespace tags, which then can be processed by the resource
            $searchString = $this->convertMarkupToInternalTags($searchString, $utilities);
            // translate it (if possible)
            $serviceResult = $this->adapter->translate($searchString);
            // UGLY: The service result holds a list of results (representing the translated texts + metadata) which unfortunately have no defined format
            $results = $serviceResult->getResult();
            if(count($results) > 0){
                // revert the internal and whitespace tags to the input format
                $results[0]->target = $this->convertInternalTagsToMarkup($results[0]->target, $utilities);
                $serviceResult->setResults($results);
            }
        } else {
            $searchString = trim(strip_tags($searchString));
            $serviceResult = $this->adapter->translate($searchString);
        }
        //log the instant translate results, when the adapter is of mt type or when the result set
        //contains result with matchrate >=100
        if(!empty($serviceResult) && ($this->isMtAdapter() || $serviceResult->has100PercentMatch())){
            $this->logAdapterUsage($searchString);
        }
        return $serviceResult;
    }
    /**
     * Protect markup with whitespace & tags to internal tags
     * this simplifies but still copies the logic of editor_Models_Import_FileParser_Csv::parseSegment
     * @param string $markup
     * @param editor_Models_Segment_UtilityBroker $utilities
     * @return string
     */
    private function convertMarkupToInternalTags(string $markup, editor_Models_Segment_UtilityBroker $utilities) : string {
        $shortTagIdent = 1;
        $markup = $utilities->tagProtection->protectTags($markup, false);
        $markup = $utilities->whitespace->convertToInternalTags($markup, $shortTagIdent);
        $markup = $utilities->whitespace->protectWhitespace($markup, $utilities->whitespace::ENTITY_MODE_OFF);
        $markup = $utilities->whitespace->convertToInternalTags($markup, $shortTagIdent);
        return $markup;
    }
    /**
     * Revert markup with whitespace encoded to internal tags to it's original format
     * this simplifies but still copies the logic of editor_Models_Export_FileParser::exportSingleSegmentContent
     * @param string $textWithTags
     * @param editor_Models_Segment_UtilityBroker $utilities
     * @return string
     */
    private function convertInternalTagsToMarkup(string $textWithTags, editor_Models_Segment_UtilityBroker $utilities) : string {
        $textWithTags = $utilities->internalTag->restore($textWithTags);
        $textWithTags =  $utilities->whitespace->unprotectWhitespace($textWithTags);
        return $textWithTags;
    }
    /***
     * This magic method is invoked each time a nonexistent method is called on the object.
     * If the function exist in the adapter it will be called there.
     * @param string $method
     * @param mixed $arguments
     * @return mixed
     * @throws ZfExtended_BadGateway
     * @throws editor_Services_Connector_Exception
     */
    public function __call(string $method, array $arguments): mixed {
        $toThrow = null;
        // if is called getStatus, the determined status is calculated there.
        // If there is an error in getStatus, this is either handled there, or in doubt we set NO_CONNECTION
        $status = editor_Services_Connector_Abstract::STATUS_NOCONNECTION;
        try {
            $internalMethod = '_'.$method;
            //check if method is wrapped here, then call it
            if(method_exists($this, $internalMethod)) {
                return call_user_func_array([$this, $internalMethod], $arguments);
            }
            //if not call it directly in the adapter
            if(method_exists($this->adapter, $method)) {
                return call_user_func_array([$this->adapter, $method], $arguments);
            }
        } catch (ZfExtended_BadGateway $toThrow) {
            //handle legacy BadGateway messages, see below

            //FIXME a connector should not throw Http Exceptions (since we can not know here on handling if the connector uses HTTP or some other raw connector
            // conclusion: the connectors themself have to convert the Http exceptions to editor_Services_Connector_Exception exceptions
            // with the below error codes (best directly in the abstract HttpApi)
        } catch (ZfExtended_Zendoverwrites_Http_Exception_Down $e) {
                //'E1311' => 'Could not connect to {service}: server not reachable',
                $ecode = 'E1311';
        } catch (ZfExtended_Zendoverwrites_Http_Exception_TimeOut $e) {
                //'E1312' => 'Could not connect to {service}: timeout on connection to server',
                $ecode = 'E1312';
        } catch (ZfExtended_Zendoverwrites_Http_Exception_NoResponse $e) {
                //'E1370' => 'Empty response from {service}',
                $ecode = 'E1370';
        }
        if(isset($ecode) && isset($e)) {
            $toThrow = new editor_Services_Connector_Exception($ecode, [
                'service' => $this->adapter->getResource()->getName(),
                'languageResource' => $this->languageResource,
            ], $e);
        }
        
        //IMPORTANT: getStatus must not throw an exception! Instead return the status in case of a here handled exception
        if($method == 'getStatus' || $method == 'batchQuery') {
            $this->adapter->setLastStatusInfo($this->adapter->logger->formatMessage($toThrow->getMessage(), $toThrow->getErrors()));
            $this->adapter->logger->exception($toThrow);
            return $status;
        }
        
        if(!empty($toThrow)) {
            throw $toThrow;
        }
        //do nothing if the method does not exist in the underyling adapter.
        return null;
    }
    
    /**
     * Returns the languages for the given resource
     * @param editor_Models_LanguageResources_Resource $resource
     * @return array
     */
    public function languages(editor_Models_LanguageResources_Resource $resource): array{
        $this->connectToResourceOnly($resource);
        try {
            return $this->__call('languages', []);
        }
        catch(editor_Services_Connector_Exception $e) {
            //since is called without a connected resource, we can not use the adapter logger here
            Zend_Registry::get('logger')->exception($e);
            return [];
        }
    }
    
    /**
     * Check the resource connection. Returns true, if a connection with the resource can be established
     * @param editor_Models_LanguageResources_Resource $resource
     * @return boolean
     */
    public function ping(editor_Models_LanguageResources_Resource $resource){
        $this->connectToResourceOnly($resource);
        //a ping is successfull if the status of the resource is available or not loaded
        $isValidFor = [editor_Services_Connector_Abstract::STATUS_AVAILABLE, editor_Services_Connector_Abstract::STATUS_NOT_LOADED];
        return in_array($this->getStatus($resource), $isValidFor);
    }
    
    /***
     * Load the lates service result cache for the given segment in the current language resource
     * @param editor_Models_Segment $segment
     * @return editor_Services_ServiceResult
     */
    protected function getCachedResult(editor_Models_Segment $segment) {
        $model = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\Pretranslation\BatchResult');
        /* @var $model MittagQI\Translate5\LanguageResource\Pretranslation\BatchResult */
        return $model->getResults($segment->getId(), $this->adapter->getLanguageResource()->getId());
    }
    
    /***
     * Load the query results from batch query table
     * @return void
     */
    public function enableBatch(): void
    {
        $this->batchEnabled = true;
    }
    
    /***
     * Load the results with calling the adapters query action
     * @return void
     */
    public function disableBatch(): void
    {
        $this->batchEnabled = false;
    }

    /***
     * Is the current adapter of mt type
     * @return bool
     */
    protected function isMtAdapter(): bool
    {
        if(!isset($this->adapter)){
            return false;
        }
        return $this->adapter->getLanguageResource()->isMt();
    }

    /***
     * Set the adapters batch content field with the given value. This is required so the batch supported connectors can
     * make difference if segment should be queried based on if there is segment value for the contentField.
     * For segments where the contentField has value no resource is queried
     * @param string $contentField
     * @return void
     */
    public function setAdapterBatchContentField(string $contentField = editor_Models_SegmentField::TYPE_SOURCE): void
    {
        if(!isset($this->adapter) || $this->adapter->isBatchQuery() === false){
            return;
        }
        // for batch query supported resources, set the content field to relais. For pre-translation based on the content field,
        // we check if the field is empty. Pretranslation is posible only for empty content fields
        $this->adapter->setContentField($contentField);
    }

    /**
     * Shows if connector can export tm as a file, not a string
     *
     * @return bool
     */
    public function exportsFile(): bool
    {
        if (method_exists($this->adapter, 'exportsFile')) {
            return $this->adapter->exportsFile();
        }

        return false;
    }
}
