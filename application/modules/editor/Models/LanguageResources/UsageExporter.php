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
        "langageResourceType"=>"Resource type",
        "langageResourceName"=>"Resource name",
        "customerId"=>"Customer",
        "langageResourceType"=>"Resource type",
        "sourceLang"=>"Source Language",
        "targetLang"=>"Target Language",
        "yearAndMonth"=>"Month and Year",
        "totalCharacters"=>"Characters",
        "timestamp"=>"Timestamp",
        "charactersPerCustomer"=>"Characters per customer",
        "taskCount"=>"Number of documents"
    ];
    
    /***
     * Spredsheet name
     * @var string
     */
    protected $name;
    
    /***
     * 
     * @var integer
     */
    protected $worksheetIndex = 0;
    
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
        
        $this->excel = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        
        $this->name = $this->translate->_("Resource usage for all customers");
        
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
     */
    public function excel(int $customerId=null) {
        
        if(!empty($customerId)) {
            $this->name = $this->translate->_("Resource usage for customer ").$this->customers[$customerId]['name'];
        }
        // set property for export-filename
        //add the timestump to the export file
        $this->excel->setProperty('filename', $this->name.'_'.NOW_ISO);
        
        $this->usageByMonth($customerId);
        $this->usageLog($customerId);
        $this->ussageInDocuments($customerId);

        $sp = $this->excel->getSpreadsheet();
        
        //this will adjust the cell size to fit the text
        foreach ($sp->getWorksheetIterator() as $worksheet) {
            
            $sp->setActiveSheetIndex($sp->getIndex($worksheet));

            $sheet = $sp->getActiveSheet();
            $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);
            
            /** @var PHPExcel_Cell $cell */
            foreach ($cellIterator as $cell) {
                $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
            }
        }
        $sp->setActiveSheetIndex(0);
        $this->excel->sendDownload();
    }
    
    /***
     * Add worksheet to the current spreedsheet
     * @param array $data
     * @param string $name
     */
    protected function addWorkSheet(array $data,string $name) {
        //validate the data and empty row if required
        $this->checkIfEmpty($data);
        
        if($this->worksheetIndex == 0){
            //Get the first autocreated worksheet and rename it.
            //The sheet contains the total sums per customer and month
            $this->excel->getWorksheetByName('Worksheet')->setTitle('Resource usage by month');
        }else{
            $this->excel->addWorksheet($name, $this->worksheetIndex);
        }
        
        $this->excel->loadArrayData($data,$this->worksheetIndex);
        $this->worksheetIndex++;
    }
    
    /***
     * Generate worksheet usage by month
     * @param int $customerId
     */
    protected function usageByMonth(int $customerId=null) {
        $model = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageSumLogger');
        /* @var $model editor_Models_LanguageResources_UsageSumLogger */
        $data = $model->loadMonthlySummaryByResource($customerId);
        
        $this->addWorkSheet($data, 'Resource usage by month');
    }
    
    /***
     * Generate worksheet character usage per customer
     * @param int $customerId
     */
    protected function usageLog(int $customerId=null) {
        $model = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageLogger');
        /* @var $model editor_Models_LanguageResources_UsageLogger */
        $data = $model->loadByCustomer($customerId);
        
        $this->addWorkSheet($data, 'Resource usage log');
    }
    
    /***
     * Generate worksheet task usage by document type
     * @param int $customerId
     */
    protected function ussageInDocuments(int $customerId=null) {
        $model = ZfExtended_Factory::get('editor_Models_TaskUsageLog');
        /* @var $model editor_Models_TaskUsageLog */
        $data = $model->loadByTypeAndCustomer($customerId,editor_Plugins_InstantTranslate_Filetranslationhelper::INITIAL_TASKTYPE_PRETRANSLATE);
        
        $this->addWorkSheet($data, 'Documents by month');
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

