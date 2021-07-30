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

/***
 * Provides functionality for logging the langauge resources usage
 *
 */
trait editor_Services_UsageLogerTrait {
    /***
     * The user which is used for file pre-translation. This is needed so we do not load the user each time when the
     * segment usage is loged
     * @var ZfExtended_Models_User
     */
    protected $userFilePretranslate;
    
    /***
     * The current task which is being pretranslated. This is needed, so we do not load the task each time when the segment
     * usage is loged
     * @var editor_Models_Task
     */
    protected $task;

    /***
     * The user which did trigger the worker
     * @var string
     */
    protected $workerUserGuid = null;
    
    
    /***
     * Log how many characters are used/translated from the current adapter request
     *
     * @param mixed $queryString
     * @param boolean $isSegmentRepetition : is the queryString segment repetition. If yes, the repetition flag will be set to 1.
     */
    public function logAdapterUsage($querySource, bool $isSegmentRepetition = false){

        $logger=ZfExtended_Factory::get('editor_Models_LanguageResources_UsageLogger');
        /* @var $logger editor_Models_LanguageResources_UsageLogger */

        $logger->setLanguageResourceId($this->getLanguageResource()->getId());
        $logger->setSourceLang($this->sourceLang);
        $logger->setTargetLang($this->targetLang);

        $logQueryString =$this->toLogQueryString($querySource);
        
        $logger->setQueryString($logQueryString);
        
        $logger->setTranslatedCharacterCount($this->getCharacterCount($logQueryString));

        if($isSegmentRepetition){
            $logger->setRepetition(1);
        }

        //if the query source is segment, the context is for task
        if($querySource instanceof editor_Models_Segment){
            
            // init the default request source
            $logger->setRequestSource(editor_Services_Connector::REQUEST_SOURCE_EDITOR);
            
            // load the task for the current request
            $this->loadTask($querySource->getTaskGuid());

            //by default, set the customers to the task customer
            $logger->setCustomers($this->task->getCustomerId());

            //if the task type is hidden task( file-pretranslation), we need to load the pre-translation task user, and calculate the customers
            if($this->task->isHiddenTask()){

                // load the user which pre-translates the task and store the user in class variable
                $this->loadUserFilePretranslate();

                //for instant translate, calculate the customers
                $logger->setCustomers($this->getInstantTranslateRequestSourceCustomers());
                
                // set the request source to instant-translate
                $logger->setRequestSource(editor_Services_Connector::REQUEST_SOURCE_INSTANT_TRANSLATE);

                // for file pre-translation, the logger user is the task pm user -> userFilePretranslate
                $logger->setUserGuid($this->userFilePretranslate->getUserGuid());
            }

        }elseif(is_string($querySource)){//if the the querySource is string, the context is instant-translate (translate request)
            // calculate the customers for the instant-translate request
            $logger->setCustomers($this->getInstantTranslateRequestSourceCustomers());
            //set the request source to instant-translate
            $logger->setRequestSource(editor_Services_Connector::REQUEST_SOURCE_INSTANT_TRANSLATE);
            // for instant translate search use the session user
            $logger->setUserGuid(editor_User::instance()->getGuid());
        }

        // if no user is set from above, try to set one
        if($logger->getUserGuid() === null){

            $session = new Zend_Session_Namespace('user');

            if(!is_null($this->workerUserGuid)){
                // it is worker context, use the user from there (this call is from analysis)
                $logger->setUserGuid($this->workerUserGuid);
            }elseif(isset($session->data->id)){
                // the session user exist, use the session user (this call is from regular query action)
                $logger->setUserGuid($session->data->userGuid);
            }else{
                // no user was found, set the system user (this call is from the tests)
                $logger->setUserGuid(ZfExtended_Models_User::SYSTEM_GUID);
            }
        }

        $logger->save();
    }

    /***
     * Load the task from the current request and store it in local variable task
     * @param string $taskGuid
     */
    protected function loadTask(string $taskGuid){
        // load the task for the current request
        if(!isset($this->task) || $this->task->getTaskGuid()!=$taskGuid){
            $this->task = ZfExtended_Factory::get('editor_Models_Task');
            $this->task->loadByTaskGuid($taskGuid);
        }
    }

    /***
     * @param string $userGuid
     */
    protected function loadUserFilePretranslate(){
        if(!isset($this->userFilePretranslate) || $this->userFilePretranslate->getUserGuid() != $this->task->getPmGuid()){
            //when file is being pretranslated in instant translate, the task pm is always the user who runs the pretranslation
            $this->userFilePretranslate = ZfExtended_Factory::get('ZfExtended_Models_User');
            $this->userFilePretranslate->loadByGuid($this->task->getPmGuid());
        }
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
            $queryString=$this->getQueryString($query);
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
        if(!isset($this->userFilePretranslate)){
            $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
            /* @var $userModel ZfExtended_Models_User */
            $userCustomers=$userModel->getUserCustomersFromSession();
        }else{
            $userCustomers = $this->userFilePretranslate->getCustomersArray();
        }
        
        if(empty($userCustomers)){
            return null;
        }
        
        $la=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $la editor_Models_LanguageResources_CustomerAssoc */
        $resourceCustomers=$la->loadByLanguageResourceId($this->getLanguageResource()->getId());
        $resourceCustomers=array_column($resourceCustomers,'customerId');
        $return=array_intersect($userCustomers,$resourceCustomers);
        if(empty($return)){
            return null;
        }
        //return with leading and trailing comma so the customers are searchable
        return ','.implode(',', $return).',';
    }

    /***
     * Setter for the internal worker user guid property
     * @param string|null $workerUserGuid
     */
    public function setWorkerUserGuid(string $workerUserGuid = null){
        $this->workerUserGuid = $workerUserGuid;
    }

    /***
     * Return the current worker user guid
     * @return string|null
     */
    public function getWorkerUserGuid(){
        return $this->workerUserGuid;
    }
}
