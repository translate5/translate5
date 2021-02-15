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
 * Language Resource Connector class
 * - provides a connection to a concrete language resource, via the internal adapter (which contains the concrete connector instance)
 * - intercepts some calls to the adapter to provide unified logging etc per each call
 * - all non intercepted methods are passed directly to the underlying adapter
 * @method editor_Services_ServiceResult query() query(editor_Models_Segment $segment)
 * @method editor_Services_ServiceResult search() search(string $searchString, $field = 'source', $offset = null)
 * @method editor_Services_ServiceResult translate() translate(string $searchString)
 * @method string getStatus() getStatus(editor_Models_LanguageResources_Resource $resource) returns the LanguageResource status
 * @method string getLastStatusInfo() getLastStatusInfo() returns the last store status info from the last getStatus call
 */
class editor_Services_Connector {
    
    /***
     * The request source when language resources is used is InstantTranslate
     * @var string
     */
    const REQUEST_SOURCE_INSTANT_TRANSLATE='instanttranslate';
    
    /***
     * The request source when language resource is used is the editor
     * @var string
     */
    const REQUEST_SOURCE_EDITOR='editor';
    
    
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
     * Requested source language id
     * @var integer
     */
    protected $sourceLang;
    
    
    /***
     * Requested target language id
     * @var integer
     */
    protected $targetLang;
    
    /***
     * if set to true, it will get the results from the batch cache table when using the query action
     * @var boolean
     */
    protected $batchQuery = false;
    
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource, $sourceLang = null, $targetLang = null){
        $this->connectToResourceOnly($languageResource->getResource());
        $this->adapter->connectTo($languageResource, $sourceLang, $targetLang);
        $this->sourceLang = $sourceLang;
        $this->targetLang = $targetLang;
    }
    
    /**
     * Connects to a given resource only, for requests not using a concrete language resource (ping calls for example)
     * @param editor_Models_LanguageResources_Resource $resource
     */
    protected function connectToResourceOnly(editor_Models_LanguageResources_Resource $resource){
        $connector = ZfExtended_Factory::get($resource->getServiceType().editor_Services_Manager::CLS_CONNECTOR);
        /* @var $connector editor_Services_Connector_Abstract */
        $connector->setResource($resource);
        $this->adapter = $connector;
    }
    
    /**
     * Init connector for fuzzy usage
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
        //if the batch query is enabled, get the results from the cache
        if($this->batchQuery && $this->adapter->isBatchQuery()){
            $serviceResult = $this->getCachedResult($segment);
        }else{
            $serviceResult = $this->adapter->query($segment);
        }
        //log the MT ussage when there are mt results
        //Info: for loggin TM results, the result is logged only when the result is used (segment save/update , matchrate >=100)
        if(!empty($serviceResult) && $this->isMtAdapter()){
            $this->logAdapterUsage($segment, self::REQUEST_SOURCE_EDITOR);
        }
        $this->adapter->logForSegment($segment);
        return $serviceResult;
    }
    
    /***
     * Invoke search resource action so the MT logger can be used
     * @param string $searchString
     * @param string $field
     * @param integer $offset
     * @return editor_Services_ServiceResult
     */
    protected function _search(string $searchString, $field = 'source', $offset = null) {
        //searches are always with out tags
        return $this->adapter->search(strip_tags($searchString), $field, $offset);
    }
    
    /***
     * Invoke the translate resource action so the MT logger can be used
     * @param string $searchString
     * @return editor_Services_ServiceResult
     */
    protected function _translate(string $searchString){
        //instant translate calls are always with out tags
        $searchString = trim(strip_tags($searchString));
        $serviceResult = $this->adapter->translate($searchString);
        //log the instant translate results, when the adapter is of mt type or when the result set
        //contains result with matchrate >=100
        if(!empty($serviceResult) && ($this->isMtAdapter() || $serviceResult->has100PercentMatch())){
            $this->logAdapterUsage($searchString, self::REQUEST_SOURCE_INSTANT_TRANSLATE);
        }
        return $serviceResult;
    }
    
    /***
     * This magic method is invoked each time a nonexistent method is called on the object.
     * If the function exist in the adapter it will be called there.
     * @param string $method
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($method, $arguments){
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
        } catch (ZfExtended_Zendoverwrites_Http_Exception_TimeOut | ZfExtended_Zendoverwrites_Http_Exception_Down $e) {
            if($e instanceof  ZfExtended_Zendoverwrites_Http_Exception_Down) {
                //'E1311' => 'Could not connect to language resource {service}: server not reachable',
                $ecode = 'E1311';
            }
            else {
                //'E1312' => 'Could not connect to language resource {service}: timeout on connection to server',
                $ecode = 'E1312';
            }
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
        $model = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_BatchResult');
        /* @var $model editor_Plugins_MatchAnalysis_Models_BatchResult */
        return $model->getResults($segment->getId(), $this->adapter->getLanguageResource()->getId());
    }
    
    /***
     * Load the query results from batch query table
     */
    public function enableBatch() {
        $this->batchQuery = true;
    }
    
    /***
     * Load the results with calling the adapters query action
     */
    public function disableBatch() {
        $this->batchQuery = false;
    }
    
    
    /***
     * Log how many characters are used/translated from the current adapter request
     * 
     * @param mixed $queryString
     * @param string $requestSource
     */
    public function logAdapterUsage($querySource,$requestSource){
        $mtlogger=ZfExtended_Factory::get('editor_Models_LanguageResources_UsageLogger');
        /* @var $mtlogger editor_Models_LanguageResources_UsageLogger */
        $mtlogger->setLanguageResourceId($this->adapter->getLanguageResource()->getId());
        $mtlogger->setSourceLang($this->sourceLang);
        $mtlogger->setTargetLang($this->targetLang);
        
        $logQueryString =$this->toLogQueryString($querySource);

        $mtlogger->setQueryString($logQueryString);
        $mtlogger->setRequestSource($requestSource);
        $mtlogger->setTranslatedCharacterCount($this->getCharacterCount($logQueryString));
        
        //the request is triggered via editor, save the task customers as customers
        if($requestSource==self::REQUEST_SOURCE_EDITOR){
            $task=ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($querySource->getTaskGuid());
            $mtlogger->setCustomers($task->getCustomerId());
            
        }
        //the request is triggered via instanttranslate, save the languageresource customers of user customers
        if($requestSource==self::REQUEST_SOURCE_INSTANT_TRANSLATE){
            $mtlogger->setCustomers($this->getInstantTranslateRequestSourceCustomers());
        }
        
        $mtlogger->save();
    }
    
    /***
     * Count characters in the requested language resources query string. The input string should not contains any tags
     * @param string $query
     * @return integer
     */
    protected function getCharacterCount(string $query){
        return mb_strlen($query);
    }
    
    /***
     * Prepare the query string for saveing in the log table
     * @param mixed $query
     * @return string
     */
    protected function toLogQueryString($query){
        //if the query is segment, get the query string fron the segment
        if($query instanceof editor_Models_Segment){
            $queryString=$this->adapter->getQueryString($query);
            //remove all tags, since the mt engines are ignoring the tags
            return $query->stripTags($queryString);
        }
        //INFO: remove the tags when the string is saved to the log table
        return strip_tags($query);
    }
    
    /***
     * Get customers when InstantTranslate is used as request source.
     * The return value will be the intersection of the customers of the language resource and the customers of the current user
     * @return NULL|array
     */
    protected function getInstantTranslateRequestSourceCustomers(){
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $userCustomers=$userModel->getUserCustomersFromSession();
        
        if(empty($userCustomers)){
            return null;
        }
        
        $la=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $la editor_Models_LanguageResources_CustomerAssoc */
        $resourceCustomers=$la->loadByLanguageResourceId($this->adapter->getLanguageResource()->getId());
        $resourceCustomers=array_column($resourceCustomers,'customerId');
        $return=array_intersect($userCustomers,$resourceCustomers);
        if(empty($return)){
            return null;
        }
        //return with leading and trailing comma so the customers are searchable
        return ','.implode(',', $return).',';
    }

    /***
     * Is the current adapter of mt type
     * @return boolean
     */
    protected function isMtAdapter(){
        if(!isset($this->adapter)){
            return false;
        }
        return $this->adapter->getLanguageResource()->isMt();
    }
}
