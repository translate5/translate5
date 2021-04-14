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
class editor_Models_LanguageResources_UsageExporter{
    
    const MONTHLY_SUMMARY_BY_RESOURCE = 'MonthlySummaryByResource';
    const USAGE_LOG_BY_CUSTOMER = 'UsageLogByCustomer';
    const DOCUMENT_USAGE = 'DocumentUsage';
    CONST CHUNKS_BUFFER =1000000;//number of log entries per excel file
    
    /***
     * 
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /***
     * 
     * @var array
     */
    protected $languages;

    /***
     * 
     * @var array
     */
    protected $customers;
    
    /***
     * @var ZfExtended_Models_Entity_ExcelExport
     */
    protected $excel;
    
    protected $labels = [
        "langageResourceType"=>"Typ der Ressource",
        "langageResourceName"=>"Name der Ressource",
        "langageResourceServiceName"=> "Ressource",
        "customerId"=>"Kunde",
        "sourceLang"=>"Quellsprache",
        "targetLang"=>"Zielsprache",
        "yearAndMonth"=>"Jahr/Monat",
        "totalCharacters"=>"Übersetzte Zeichen",
        "timestamp"=>"Zeitstempel",
        "charactersPerCustomer"=>"Übersetzte Zeichen",
        "taskCount"=>"Anzahl der mit InstantTranslate übersetzten Dokumente",
        "customers" =>"Kunden",
        "repetition" => "Wiederholung"
    ];
    
    /***
     * Spredsheet name
     * @var string
     */
    protected $name;
    
    /***
     * Zip folder location
     * @var string
     */
    protected $zipFolder;
    
    /***
     * Zip file name
     */
    protected $zipName;
    /***
     * 
     * @var integer
     */
    protected $worksheetIndex = 0;
    
    /***
     * Document usage task types
     * @var array
     */
    protected $documentTaskType = [editor_Models_Task::INITIAL_TASKTYPE_DEFAULT,editor_Models_Task::INITIAL_TASKTYPE_PROJECT_TASK];
    
    public function __construct() {
        $this->init();
    }
    
    protected function init(){
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        $languages = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languages editor_Models_Languages */
        $this->languages = $languages->loadAllKeyCustom('id');
        
        $customers = ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customers editor_Models_Customer */
        $this->customers = $customers->loadAllKeyCustom('id');
        
        $this->initExcelExport();
    }
    
    /***
     * Setup the excel export object, cell value callbacks and lables
     */
    protected function initExcelExport(){
        $this->excel = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        
        //set the spredsheet labels and translate the cell headers
        foreach ($this->labels as $label=>$text){
            $this->excel->setLabel($label, $this->translate->_($text));
        }
        
        $langs = $this->languages;
        $langCallback = function($id) use ($langs){
            return $langs[$id]['langName'];
        };
        
        $this->excel->setCallback('sourceLang',$langCallback);
        $this->excel->setCallback('targetLang',$langCallback);
        
        $this->excel->setCallback('repetition',function($repetition){
            return $repetition == 1 ? '✓' : '';
        });
        
        
        $customersArray = $this->customers;
        $this->excel->setCallback('customerId',function($id) use ($customersArray){
            return $customersArray[$id]['name'];
        });
            
        //set callback for comma separated customers
        $this->excel->setCallback('customers',function($customerIds) use ($customersArray){
            $customerIds = explode(',', trim($customerIds,','));
            $names = [];
            foreach ($customerIds as $id) {
                $names[] = $customersArray[$id]['name'];
            }
            return implode(',', $names);
        });
    }
    
    /***
     * Generate mt ussage log excel export. When no customer is provider, it will export the data for all customers.
     * 
     * @param int $customerId
     * @return boolean
     */
    public function excel(int $customerId=null) :bool{
        
        //load the export data
        $data = $this->getExportRawData($customerId);
        
        if(empty($data)){
            return false;
        }
        
        //split the usage log by chunks. Each chunk is separate excel in the zip download
        $chunks =array_chunk($data[self::USAGE_LOG_BY_CUSTOMER], self::CHUNKS_BUFFER);
        $chunkIndex = 0;
        
        if(!empty($customerId)) {
            $this->name =$this->translate->_("Ressourcen-Nutzung fuer Kunde").' '.$this->customers[$customerId]['name'];
        }else{
            $this->name =$this->translate->_("Ressourcen-Nutzung fuer alle Kunden");
        }
        
        //if the export is splited in multiple excels, setup the export zip directory
        if($hasChunks = count($chunks)>1){
            $this->setupZip();
        }
        
        foreach ($chunks as $chunk){
            
            $chunkIndex++;

            //if we should generate more then 1 excel, add the part number to it
            if($hasChunks){
                if(!empty($customerId)) {
                    $this->name =$this->translate->_("Ressourcen-Nutzung fuer Kunde").' '.$this->customers[$customerId]['name'];
                }else{
                    $this->name =$this->translate->_("Ressourcen-Nutzung fuer alle Kunden");
                }
                $this->name = $chunkIndex.'-'.$this->name;
            }
            
            // set property for export-filename
            //add the timestump to the export file
            $this->excel->setProperty('filename', $this->name);
            
            $this->usageByMonth($data[self::MONTHLY_SUMMARY_BY_RESOURCE],$customerId);
            $this->usageLog($chunk,$customerId);
            $this->usageInDocuments($data[self::DOCUMENT_USAGE],$customerId);
    
            $sp = $this->excel->getSpreadsheet();
            
            //this will adjust the cell size to fit the text
            $this->excel->autosizeColumns($sp);
            
            //set the first sheet as active on file open
            $sp->setActiveSheetIndex(0);
            //
            if($hasChunks){
                $this->excel->saveToDisc($this->zipFolder.$this->name.'.xlsx');
            }else{
                $this->excel->sendDownload();
            }
            $this->worksheetIndex = 0;
            
            //initialize the excel export data for another excel file
            $this->initExcelExport();
        }
        //if it is chunk export, generate the zip and send it to download
        if($hasChunks){
            $this->downloadZip($this->zipName,$this->zipFolder);
        }
        
        return true;
    }
    
    /***
     * Load all required export data
     * 
     * @param int $customerId
     * @return array
     */
    public function getExportRawData(int $customerId = null) : array{
        $rawData = [];
        
        $model = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageSumLogger');
        /* @var $model editor_Models_LanguageResources_UsageSumLogger */
        
        $rawData[self::MONTHLY_SUMMARY_BY_RESOURCE] = $model->loadMonthlySummaryByResource($customerId);
        
        $model = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageLogger');
        /* @var $model editor_Models_LanguageResources_UsageLogger */
        
        $rawData[self::USAGE_LOG_BY_CUSTOMER] = $model->loadByCustomer($customerId);
        
        $model = ZfExtended_Factory::get('editor_Models_TaskUsageLog');
        /* @var $model editor_Models_TaskUsageLog */
        
        $rawData[self::DOCUMENT_USAGE] = $model->loadByTypeAndCustomer($customerId,$this->documentTaskType);
        
        return $rawData;
    }
    
    /***
     * Get the data in format required for the tests. In the returned result, unneeded fields will be filtered.
     * 
     * @param int $customerId
     * @return array
     */
    public function getExportRawDataTests(int $customerId = null) : array{
        
        $result = $this->getExportRawData($customerId);
        
        $unset = ["customerId","yearAndMonth","timestamp","customers"];
        $languages = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languages editor_Models_Languages */
        $languages = $languages->loadAllKeyValueCustom('id','rfc5646');
        
        //filter out and convert fields
        $filterRows = function($needle,&$haystack) use($languages){
            foreach ($haystack as &$single){
                foreach ($single as $key=>&$value){
                    if(in_array($key, $needle)){
                        unset($single[$key]);
                    }
                    //convert the languages to rfc values
                    if(in_array($key,['sourceLang','targetLang'])){
                        $value = $languages[$value];
                    }
                }
            }
        };
        
        $filterRows($unset,$result[self::MONTHLY_SUMMARY_BY_RESOURCE]);
        $filterRows($unset,$result[self::USAGE_LOG_BY_CUSTOMER]);
        $filterRows($unset,$result[self::DOCUMENT_USAGE]);
        
        return $result;
    }
    
    /***
     * 
     * @param array $newTaskType
     */
    public function setDocumentTaskType(array $newTaskType) {
        $this->documentTaskType = $newTaskType;
    }
    
    /***
     * Add worksheet to the current spreedsheet
     * @param array $data
     * @param string $name
     */
    protected function addWorkSheet(array $data,string $name,string $comment = '') {
        //validate the data and empty row if required
        $this->checkIfEmpty($data);
        
        //add comment as separate column at the end of the first row
        if(!empty($comment)){
            $data[0]['Info'] = $comment;
        }
        
        if($this->worksheetIndex == 0){
            //Get the first autocreated worksheet and rename it.
            //The sheet contains the total sums per customer and month
            $this->excel->getWorksheetByName('Worksheet')->setTitle($name);
        }else{
            $this->excel->addWorksheet($name, $this->worksheetIndex);
        }
        $this->excel->loadArrayData($data,$this->worksheetIndex);
        $this->worksheetIndex++;
    }
    
    /***
     * Setup zip folder for export.
     * This will also set the zipName and zipFolder properties
     */
    protected function setupZip(){
        //generate unique zip name
        $this->zipName = $this->name.'_'.NOW_ISO.'.zip';
        //create the directory where the excel chunks will be saved
        $this->zipFolder=APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$this->zipName.DIRECTORY_SEPARATOR;
        is_dir($this->zipFolder) || mkdir($this->zipFolder);
    }
    
    /***
     * Generate the zip from chunk-export files and send the zip for download
     * @param string $name
     * @param string $path
     * @throws ZfExtended_ErrorCodeException
     */
    protected function downloadZip(string $name,string $path){
        $zip = new ZipArchive();
        $finalZip = $path.$name;
        $zipStatus = $zip->open($finalZip, ZIPARCHIVE::CREATE);
        if (!$zipStatus) {
            throw new editor_Models_LanguageResources_UsageExporterException('E1335',[
                'path'=>$finalZip,
                'errorCode' => $zipStatus
            ]);
        }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            //ignore dots
            if( in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR)+1), array('.', '..'))) {
                continue;
            }
            $zip->addFromString($file->getFileName(), file_get_contents(realpath($file)));
        }
        if(!$zip->close()) {
            throw new editor_Models_LanguageResources_UsageExporterException('E1336',[
                'path'=>$finalZip
            ]);
        }
        header('Content-Type: application/zip', TRUE);
        header('Content-Disposition: attachment; filename="'.$name.'"');
        readfile($finalZip);
        
        //clean files
        $this->cleanExportZip($path);
    }
    
    /***
     * Remove the all produced files from the disk
     * @param string $path
     */
    protected function cleanExportZip(string $path){
        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            }
            if ($fileinfo->isFile()) {
                unlink($path . $fileinfo->getFilename());
            }
        }
        rmdir($path);
    }
    
    /***
     * Generate worksheet usage by month
     * @param array $data
     * @param int $customerId
     */
    protected function usageByMonth(array $data,int $customerId=null) {
        //add row wich explains the current worksheet
        $comment = $this->translate->_('Diese Daten enthalten alle Anfragen an Sprachressourcen, egal ob durch Aufgaben oder via InstantTranslate.'); 
        $this->addWorkSheet($data, $this->translate->_('Ressourcen-Nutzung pro Monat'),$comment);
    }
    
    /***
     * Generate worksheet character usage per customer
     * 
     * @param array $data
     * @param int $customerId
     */
    protected function usageLog(array $data,int $customerId=null) {
        //add row wich explains the current worksheet
        $comment = $this->translate->_('Diese Daten enthalten alle Anfragen an Sprachressourcen, egal ob durch Aufgaben oder via InstantTranslate.');
        $comment.=$this->translate->_('Jede Zeile korrespondiert mit einer Anfrage an eine Sprachressource.');
        $this->addWorkSheet($data, $this->translate->_('Log der Ressouren-Nutzung'),$comment);
    }
    
    /***
     * Generate worksheet task usage by document type
     * 
     * @param array $data
     * @param int $customerId
     */
    protected function usageInDocuments(array $data,int $customerId=null) {
        //add row wich explains the current worksheet
        $comment = $this->translate->_('Anzahl der mit InstantTranslate übersetzten Dokumente');
        $this->addWorkSheet($data, $this->translate->_('Dokumente pro Monat'),$comment);
    }
    
    /***
     * Check if the current result array is empty, if yes, this will add an empty result text message
     * @param array $data
     * @return array
     */
    protected function checkIfEmpty(array &$data = []) {
        if(!empty($data)){
            return $data;
        }
        array_push($data,['No results where found for the current worksheet'=>'']);
    }
}

