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
 * 
 * FIXME add __called function names from underlying adapter (ABstractConnector)
 */
class editor_Services_Connector {
    
    /***
     * The request source when language resources is used is instant translate
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
    
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource,$sourceLang=null,$targetLang=null){
        $serviceType = $languageResource->getServiceType();
        $connector = ZfExtended_Factory::get($serviceType.editor_Services_Manager::CLS_CONNECTOR);
        /* @var $connector editor_Services_Connector_Abstract */
        $connector->connectTo($languageResource,$sourceLang,$targetLang);
        $this->adapter=$connector;
        $this->languageResource=$languageResource;
        $this->sourceLang=$sourceLang;
        $this->targetLang=$targetLang;
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
    public function query(editor_Models_Segment $segment) {
        try {
            $serviceResult=$this->adapter->query($segment);
        } catch (Exception $e) {
            $this->logException($e,$segment->getTaskGuid());
        }
        return $serviceResult;
    }
    
    /***
     * Invoke search resource action so the MT logger can be used
     * @param string $searchString
     * @param string $field
     * @param integer $offset
     * @return editor_Services_ServiceResult
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        try {
            $serviceResult=$this->adapter->search($searchString,$field,$offset);
        } catch (Exception $e) {
            $this->logException($e);
        }
        return $serviceResult;
    }

    /***
     * Invoke the translate resource action so the MT logger can be used
     * @param string $searchString
     * @return editor_Services_ServiceResult
     */
    public function translate(string $searchString){
        try {
            $serviceResult=$this->adapter->translate($searchString);
        } catch (Exception $e) {
            $this->logException($e);
        }
        return $serviceResult;
    }
    
    /***
     * Invoke the getStatus function since it is used by reference
     * @param string $moreInfo
     */
    public function getStatus(&$moreInfo){
        try {
            return $this->adapter->getStatus($moreInfo);
        } catch (Exception $e) {
            $this->logException($e);
        }
    }
    
    /***
     * This magic method is invoked each time a nonexistent method is called on the object.
     * If the function exist in the adapter it will be called there.
     * @param string $method
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($method,$arguments){
        if(method_exists($this->adapter, $method)) {
            return call_user_func_array([$this->adapter, $method], $arguments);
        }
    }
    
    /***
     * Logs the given exception (writes log entry in language resources log table and if 
     * the task is available, writes log entry in the task log table to)
     * 
     * @param Exception $e
     * @param string $taskGuid
     */
    protected function logException(Exception $e,string $taskGuid=''){
        $session = new Zend_Session_Namespace();
        $taskGuid=$taskGuid ?? $session->taskGuid;
        $extra=[];
        $extra['languageResource']=$this->languageResource;
        if(!empty($taskGuid)){
            $task=ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($taskGuid);
            $extra['task']=$task;
        }
        $extra['message']=$e->getMessage();
        throw new editor_Services_Connector_Exception('E1282',$extra);
    }
    
//     /***
//      * Log MT language resources ussage
//      * @param mixed $queryString
//      * @param string $requestSource
//      */
//     protected function logMtUsage($querySource,$requestSource){
//         //use the logger only for MT resoruces
//         if($this->languageResource->getResourceType()!=editor_Models_Segment_MatchRateType::TYPE_MT){
//             return;
//         }
//         $mtlogger=ZfExtended_Factory::get('editor_Models_LanguageResources_MtUsageLogger');
//         /* @var $mtlogger editor_Models_LanguageResources_MtUsageLogger */
//         $mtlogger->setLanguageResourceId($this->languageResource->getId());
//         $mtlogger->setSourceLang($this->sourceLang);
//         $mtlogger->setTargetLang($this->targetLang);
//         $mtlogger->setQueryString($this->getQueryString($querySource));
//         $mtlogger->setRequestSource($requestSource);
//         $mtlogger->setTranslatedCharacterCount($this->getCharacterCount($querySource));
        
//         //the request is triggered via editor, save the task customers as customers
//         if($requestSource==self::REQUEST_SOURCE_EDITOR){
//             $task=ZfExtended_Factory::get('editor_Models_Task');
//             /* @var $task editor_Models_Task */
//             $task->loadByTaskGuid($querySource->getTaskGuid());
//             $mtlogger->setCustomers($task->getCustomerId());
            
//         }
        
//         //the request is triggered via instanttranslate, save the languageresource customers of user customers
//         if($requestSource==self::REQUEST_SOURCE_INSTANT_TRANSLATE){
//             $mtlogger->setCustomers($this->getInstantTranslateRequestSourceCustomers());
//         }
        
//         $mtlogger->save();
//     }
    
//     /***
//      * Count characters in the requested language resources query string/segment
//      * @param mixed $query
//      * @return integer
//      */
//     protected function getCharacterCount($query){
//         return mb_strlen($this->getQueryString($query));
//     }
    
//     /***
//      * Get the query string and removes the tags from it
//      */
//     protected function getQueryString($query){
//         if($query instanceof editor_Models_Segment){
//             $queryString=$this->adapter->getQueryString($query);
//             //remove all tags, since the mt engines are ignoring the tags
//             $queryString=$query->stripTags($queryString);
//             $query=$queryString;
//         }
//         return $query;
//     }
    
//     /***
//      * Get customers when instant translate is used as request source.
//      * The return value will be the intersection of the customers of the language resource and the customers of the current user
//      * @return NULL|array
//      */
//     protected function getInstantTranslateRequestSourceCustomers(){
//         $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
//         /* @var $userModel ZfExtended_Models_User */
//         $userCustomers=$userModel->getUserCustomersFromSession();
        
//         if(empty($userCustomers)){
//             return null;
//         }
        
//         $la=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
//         /* @var $la editor_Models_LanguageResources_CustomerAssoc */
//         $resourceCustomers=$la->loadByLanguageResourceId($this->languageResource->getId());
//         $resourceCustomers=array_column($resourceCustomers,'customerId');
//         $return=array_intersect($userCustomers,$resourceCustomers);
//         if(empty($return)){
//             return null;
//         }
//         //return with leading and trailing comma so the customers are searchable
//         return ','.implode(',', $return).',';
//     }
}