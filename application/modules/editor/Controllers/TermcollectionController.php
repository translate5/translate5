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
 * 
 */
class editor_TermcollectionController extends ZfExtended_RestController  {
    
    const FILE_UPLOAD_NAME = 'tbxUpload';
    
    protected $entityClass = 'editor_Models_TermCollection_TermCollection';
    
    /**
     * @var editor_Models_TermCollection_TermCollection
     */
    protected $entity;
    
    /**
     * @var array
     */
    protected $uploadErrors = array();

    /***
     * Info: function incomplete! 
     * Only used in a test at the moment! 
     */
    public function exportAction() {
        $this->decodePutData();
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        
        $export = ZfExtended_Factory::get('editor_Models_Export_Terminology_Tbx');
        /* @var $export editor_Models_Export_Terminology_Tbx */
        
        $data=$term->loadSortedByCollectionAndLanguages([$this->data->collectionId]);
        $export->setData($data);
        $exportData=$export->export();
        
        $this->view->filedata=$exportData;
    }
    
    /***
     * Import the terms into the term collection
     * 
     * @throws ZfExtended_Exception
     */
    public function importAction(){
        $params=$this->getRequest()->getParams();
        
        if(!isset($params['collectionId'])){
            throw new ZfExtended_ValidateException("The import term collection is not defined.");
        }
        
        $filePath=$this->getUploadedTbxFilePaths();
        if(!$this->validateUpload()){
            $this->view->success=false;
        }else{
            //the return is needed for the tests
            $this->view->success=$this->entity->importTbx($filePath,$params);
        }
    }
    
    /***
     * Search terms 
     */
    public function searchAction(){
        $params=$this->getRequest()->getParams();
        $responseArray=array();
        
        $collectionIds=$this->getCollectionForLogedUser();
        
        if(empty($collectionIds)){
            $this->view->rows=$responseArray;
            return;
        }
        
        $model=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $model editor_Models_Term */
        
        $config = Zend_Registry::get('config');
        $termCount=$config->runtimeOptions->termportal->searchTermsCount;
        
        if(isset($params['term'])){
            $languages = $params['language'] ?? null;
            
            //if the limit is disabled, do not use it
            if(isset($params['disableLimit']) && $params['disableLimit']=="true"){
                $termCount=null;
            }
            
            $responseArray['term'] = $this->mergeProposalData($model->searchTermByLanguage($params['term'],$languages,$collectionIds,$termCount));
            
        }
        
        $this->view->rows=$responseArray;
    }
    
    /**
     * Merge / convert the proposal information in the result data 
     * @param array $rows
     * @return array|null
     */
    protected function mergeProposalData($rows) {
        $mergeProposal = function($item) {
            
            //FIXME: compute proposable also via ACLs here!
            $item['proposable'] = true;
            
            if(empty($item['id'])){
                $item['proposal'] = null;
            }
            else {
                $item['proposal'] = [
                    'id' => $item['id'],
                    'term' => $item['term'],
                    'created' => $item['created'],
                ];
            }
            unset($item['id']);
            unset($item['term']);
            unset($item['created']);
            return $item;
        };
        if(empty($rows)){
            return null;
        }
        return array_map($mergeProposal, $rows);
    }
    
    /***
     * Search term entry and term attributes in group
     */
    public function searchattributeAction(){
        $params=$this->getRequest()->getParams();
        $responseArray=array();
        
        $collectionIds=$this->getCollectionForLogedUser();
        
        if(empty($collectionIds)){
            $this->view->rows=$responseArray;
            return;
        }
        
        $model=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $model editor_Models_Term */
        
        if(isset($params['groupId'])){
            $responseArray['termAttributes'] = $this->groupTermsAndAttributes($model->searchTermAttributesInTermentry($params['groupId'],$collectionIds));
            
            $entryAttr=ZfExtended_Factory::get('editor_Models_Term_Attribute');
            /* @var $entryAttr editor_Models_Term_Attribute */
            $responseArray['termEntryAttributes']=$entryAttr->getAttributesForTermEntry($params['groupId'],$collectionIds);
            
        }
        $this->view->rows=$responseArray;
    }
    
    /***
     * Group term and term attributes data by term. Each row will represent one term and its attributes in attributes array.
     * The term attributes itself will be grouped in parent-child structure
     * @param array $data
     * @return array
     */
    protected function groupTermsAndAttributes(array $data){
        $map=[];
        $termColumns=[
            'definition',
            'groupId',
            'label',
            'value',
            'desc',
            'termStatus',
            'termId',
            'collectionId',
            'languageId',
            'term'
        ];
        //available proposal columns
        $termProposalColumns=[
            'proposalTerm',
            'proposalId',
            'proposalCreated',
            'proposalUserGuid',
            'proposalUserName'
        ];
        //maping between database name and proposal table real name (on the frontend we have under the proposal array the real names)
        $termProposalColumnsNameMap=[
            'proposalTerm'=>'term',
            'proposalId'=>'id',
            'proposalCreated'=>'created',
            'proposalUserGuid'=>'userGuid',
            'proposalUserName'=>'userName'
        ];
        
        $attribute=ZfExtended_Factory::get('editor_Models_Term_Attribute');
        /* @var $attribute editor_Models_Term_Attribute */
        
        //Group term-termattribute data by term. For each grouped attributes field will be created
        $oldKey='';
        $groupOldKey=false;
        $termProposalData=[];
        
        foreach ($data as $tmp){
            $termKey=$tmp['termId'];
            
            
            if(!isset($map[$termKey])){
                $termKey=$tmp['termId'];
                $map[$termKey]=[];
                $map[$termKey]['attributes']=[];
                if(!empty($oldKey) && !empty($map[$oldKey])){
                    $map[$oldKey]['attributes']=$attribute->createChildTree($map[$oldKey]['attributes']);
                    $groupOldKey=true;
                    $map[$oldKey]['proposal']=!empty($termProposalData['term']) ? $termProposalData : null;
                    $termProposalData=[];
                }
            }
            
            //split the term fields and term attributes
            $atr=[];
            foreach ($tmp as $key=>$value){
                if(!in_array($key,$termColumns)){
                    $atr[$key]=$value;
                    if(in_array($key,$termProposalColumns)){
                        $termProposalData[$termProposalColumnsNameMap[$key]]=$value;
                    }
                }else{
                    $map[$termKey][$key]=$value;
                }
            }
            
//FIXME add ACL checking into the proposable calculation here 
            $atr['proposable'] = $attribute->isProposable($atr['name'], $atr['attrType']);
            
            array_push($map[$termKey]['attributes'],$atr);
            $oldKey = $tmp['termId'];
            $groupOldKey=false;
        }
        //if not grouped after foreach, group the last result
        if(!$groupOldKey){
            $map[$oldKey]['attributes']=$attribute->createChildTree($map[$oldKey]['attributes']);
            $map[$oldKey]['proposal']=!empty($termProposalData['term']) ? $termProposalData : null;
        }
        
        if(empty($map)){
            return null;
        }
        return $map;
    }
    
    /***
     * This action is only used in a test at the moment! 
     */
    public function testgetattributesAction(){
        $this->view->rows=$this->entity->getAttributesCountForCollection($this->getParam('collectionId'));
    }
    
    /***
     * Return the uploaded tbx files paths
     * 
     * @throws ZfExtended_FileUploadException
     * @return array
     */
    private function getUploadedTbxFilePaths(){
        $upload = new Zend_File_Transfer();
        $upload->addValidator('Extension', false, 'tbx');
        // Returns all known internal file information
        $files = $upload->getFileInfo();
        $filePath=[];
        foreach ($files as $file => $info) {
            // file uploaded ?
            if (!$upload->isUploaded($file)) {
                $this->uploadErrors[]="The file is not uploaded";
                continue;
            }
            
            // validators are ok ?
            if (!$upload->isValid($file)) {
                $this->uploadErrors[]="The file:".$file." is with invalid file extension";
                continue;
            }
            $filePath[]=$info['tmp_name'];
        }
        return $filePath;
    }
    
    /***
     * Get the available collections for the currently logged user
     * 
     * @return array
     */
    private function getCollectionForLogedUser(){
        
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $customers=$userModel->getUserCustomersFromSession();

        if(empty($customers)){
            return array();
        }
        
        $collection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $collection editor_Models_TermCollection_TermCollection */
        $collectionIds=$collection->getCollectionsIdsForCustomer($customers);
        
        return $collectionIds;
    }
    
    /**
     * translates and transport upload errors to the frontend
     * @return boolean if there are upload errors false, true otherwise
     */
    protected function validateUpload() {
        if(empty($this->uploadErrors)){
            return true;
        }
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        $errors = array(self::FILE_UPLOAD_NAME => array());
        
        foreach($this->uploadErrors as $error) {
            $errors[self::FILE_UPLOAD_NAME][] = $translate->_($error);
        }
        
        $e = new ZfExtended_ValidateException(print_r($errors, 1));
        $e->setErrors($errors);
        $this->handleValidateException($e);
        return false;
    }
}

