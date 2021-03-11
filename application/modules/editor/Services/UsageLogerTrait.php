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
     * The current task which is being pretranslated. This is needed so we do not load the task each time when the segment
     * usage is loged
     * @var editor_Models_Task
     */
    protected $taskFilePretranslate;
    
    
    /***
     * Log how many characters are used/translated from the current adapter request
     *
     * @param mixed $queryString
     */
    public function logAdapterUsage($querySource){
        $mtlogger=ZfExtended_Factory::get('editor_Models_LanguageResources_UsageLogger');
        /* @var $mtlogger editor_Models_LanguageResources_UsageLogger */
        $mtlogger->setLanguageResourceId($this->getLanguageResource()->getId());
        $mtlogger->setSourceLang($this->sourceLang);
        $mtlogger->setTargetLang($this->targetLang);
        
        $logQueryString =$this->toLogQueryString($querySource);
        
        $mtlogger->setQueryString($logQueryString);
        
        $mtlogger->setTranslatedCharacterCount($this->getCharacterCount($logQueryString));
        
        //if the query source is segment, the context is for task
        if($querySource instanceof editor_Models_Segment){
            
            //set the request source to editor
            $mtlogger->setRequestSource(editor_Services_Connector::REQUEST_SOURCE_EDITOR);
            
            if($querySource->getMatchRate()==editor_Services_Connector_FilebasedAbstract::REPETITION_MATCH_VALUE){
                $mtlogger->setRepetition(1);
            }
            
            //load the task for the current request
            if(!isset($this->taskFilePretranslate) || $this->taskFilePretranslate->getTaskGuid()!=$querySource->getTaskGuid()){
                $this->taskFilePretranslate = ZfExtended_Factory::get('editor_Models_Task');
                $this->taskFilePretranslate->loadByTaskGuid($querySource->getTaskGuid());
            }
            
            //by default, set the customers to the task customer
            $mtlogger->setCustomers($this->taskFilePretranslate->getCustomerId());
            
            //if the task type is file-pretranslation, we need to load the pre-translation task user, and calculate the customers
            if($this->taskFilePretranslate->getTaskType() == editor_Plugins_InstantTranslate_Filetranslationhelper::INITIAL_TASKTYPE_PRETRANSLATE){
                
                if(!isset($this->userFilePretranslate) || $this->userFilePretranslate->getUserGuid() != $this->taskFilePretranslate->getPmGuid()){
                    //when file is being pretranslated in instant translate, the task pm is always the user who runs the pretranslation
                    $this->userFilePretranslate = ZfExtended_Factory::get('ZfExtended_Models_User');
                    $this->userFilePretranslate->loadByGuid($this->taskFilePretranslate->getPmGuid());
                }
                //for instant translate, calculate the customers
                $mtlogger->setCustomers($this->getInstantTranslateRequestSourceCustomers());
                //set the request source to instant-translate
                $mtlogger->setRequestSource(editor_Services_Connector::REQUEST_SOURCE_INSTANT_TRANSLATE);
            }
        }elseif(is_string($querySource)){//if the the querySource is string, the context is instant-translate (translate request)
            //calculate the customers for the instant-translate request
            $mtlogger->setCustomers($this->getInstantTranslateRequestSourceCustomers());
            //set the request source to instant-translate
            $mtlogger->setRequestSource(editor_Services_Connector::REQUEST_SOURCE_INSTANT_TRANSLATE);
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
}
