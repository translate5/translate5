<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * ERP-Vendor Entity Objekt.
 * Does not work like other Entity-Objects because this one is only a read-connector to an external database.
 * 
 * @method integer getVendorId() getVendorId()
 * @method void setVendorId() setVendorId(integer $id)
 */
class erp_Models_Vendor{
    
    /**
     * @var array
     */
    public $dbConfig = false;
    
    /**
     * @var Zend_Db_Table_Abstract
     */
    public $db = false;
    
    /**
     * Holds the count of vendors loaded by loadAll()
     * @var integer
     */
    protected $totalCount = 0;
    
    
    /**
     * Init the the DB object
     */
    public function __construct() {
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        
        try {
            $config = Zend_Registry::get('config');
            $this->dbConfig = $config->runtimeOptions->erp->db->erpVendor->toArray();
            
            $this->db = Zend_Db::factory($this->dbConfig['adapter'], $this->dbConfig);
            $this->db->getConnection();
        }
        catch (Zend_Db_Adapter_Exception $e) {
            $log->logError('Could not connect to vendor-database (connection-error).', __CLASS__.' -> '.__FUNCTION__.'; '.$e->getMessage());
        }
        catch (Exception $e) {
            $log->logError('Could not connect to vendor-database (config-error).', __CLASS__.' -> '.__FUNCTION__.'; '.$e->getMessage());
        }
    }
    
    /*
     * load all vendors from (tvin) 
     * based on the language combination, tvin server will respond with corresponding vendors data
     * customerId is used for sortinh the vendors
     */
    public function loadAllWithTerms($sourcelang,$targetlang,$customerId) {

        $data = $this->getVendors($sourcelang,$targetlang);
        $this->totalCount = count($data);
        if ($this->totalCount < 1) {
            return array();
        }
        
        $mappedData = array();
        $vendorIds="";
        foreach ($data as $row) {
            $mappedData[] = $this->mapFields($row,$sourcelang);
            $vendorIds.=",".$row->Vid;
        }
        $vendorIds = substr($vendorIds,1);
        $mappedData = $this->reorderVendors($mappedData,$sourcelang,$targetlang, $customerId,$vendorIds);
        
        //this query will return maximum 5 invoices numbers.ordered by latest modified on the top
        $sql = "SELECT x.billNumber, x.vendorId, COUNT(*) AS `rank`,x.modifiedDate
                FROM ERP_purchaseOrder x
                JOIN ERP_purchaseOrder y
                ON y.vendorId = x.vendorId 
                AND (y.modifiedDate >= x.modifiedDate) 
                WHERE x.billNumber <> ''
                GROUP BY x.vendorId, x.billNumber 
                HAVING `rank` <= 5
                ORDER BY vendorId,modifiedDate DESC;";

        $db = Zend_Db_Table::getDefaultAdapter();
        $invoicesArray = $db->query($sql)->fetchAll();
        //merge the received result into the vendor store
        foreach($invoicesArray as $row){
            $index = array_search($row['vendorId'], array_column($mappedData, 'id'));
            if($index===false){
                continue;
            }
            array_push($mappedData[$index]['invoicesList'], $row['billNumber']);
        }
        return $mappedData;
    }
    
    
    public function getTotalCount() {
        return $this->totalCount;
    }
    
    
    public function load($id) {
        $s = $this->db->select()->from($this->dbConfig['dbtable']);
        $s->where('Id = ?', $id);
        
        $data = $this->db->fetchRow($s);
        if (empty($data)) {
            throw new ZfExtended_Models_Entity_NotFoundException('Vendor Not Found: Id: '.$id);
        }
        
        $data = (array) $data;
        
        return $this->mapFields($data);
    }
    
    
    /**
     * Map fields from external Vendor database to internal structur
     * 
     * @param array $data
     * @return array $vendor
     */
    private function mapFields($data,$sourceLang='') {
        $vendor = array();
        $company = '';
        
        $vendor['id'] = $data->Vid;
        $vendor['number'] = $data->VendorNumber;
        
        if($data->IsCompany) {
            $vendor['IsCompany'] = true;
            $vendor['Company'] = $data->Company;
            
            //if is company and first name and last name are available, show - lastname, firstname (company name) (vendorNumber)
            if($data->LastName!="" && $data->FirstName!=""){
                $vendor['text'] = $data->LastName.', '.$data->FirstName.' ('.$data->Company.') ('.$company.$data->VendorNumber.')';
            }else{
                $vendor['text'] = $data->Company.' ('.$company.$data->VendorNumber.')';
            }
        }else{
            $vendor['IsCompany'] = false;
            $vendor['Company'] = '';
            $vendor['text'] = $data->LastName.', '.$data->FirstName.' ('.$data->VendorNumber.')';
        }
        
        $taxRate = $data->VAT;
        if (empty($taxRate)) {
            $taxRate = 0;
        }
        $vendor['taxRate'] = (float) $taxRate;
        
        $vendor['currency'] = $data->Currency;
        
        $vendor['paymentTerms']=[];
        
        $vendor['paymentTerms']['data']=$data->PaymentDetails;
        
        $vendor['invoicesList']=[];
        
        $vendor['targetLang']=$data->targetLang;
        
        //this data is needed for pdf export
        $vendor['FirstName']=$data->FirstName;
        $vendor['LastName']=$data->LastName;
        $vendor['Address1']=$data->Address1;
        $vendor['Address2']=$data->Address2;
        $vendor['PostCode']=$data->PostCode;
        $vendor['City']=$data->City;
        $vendor['Country']=$data->Country;
        $vendor['SourceLang']=$sourceLang;
        $vendor['Prices']=$data->Prices;
        $vendor['Email']=$data->Email;
        $vendor['Salutation']=$data->Salutation;
        //error_log(print_r($data,1));
        return $vendor;
    }
    /***
     * Order vendors according to the turnover they made with TransMission in the last 1 year(highest turnover will be on the top).
     * The number of documents from type 'billed', 'paid', 'ordered' will rate the vendor to the highest rank of this array.
     */
    private function reorderVendors(array $targetArray,$sourceLang,$targetLang,$customerId,$vendorIds){
        $db = Zend_Db_Table::getDefaultAdapter();

        $sql=' SELECT sum(po.netValue) as netValue,po.orderId,po.number,po.sourceLang,po.targetLang,po.creationDate,po.customerName,po.vendorId,po.vendorName '. 
             ' FROM ERP_purchaseOrder po '.
             ' INNER JOIN ERP_order o ON o.id= po.orderId '.
             ' WHERE (o.state in ("billed", "paid","ordered")) '.
             ' AND po.creationDate >= DATE_SUB(NOW(),INTERVAL 1 YEAR) '.
             ' AND o.customerId = '.$db->quote($customerId,'INTEGER').' '.
             ' AND po.sourceLang = '.$db->quote($sourceLang).' '.
             ' AND po.targetLang IN('.$db->quote($targetLang).') '.
             ' AND po.vendorId IN ('.$vendorIds.') '.
             ' GROUP BY po.vendorId '.
             ' ORDER BY netValue DESC,po.vendorName ASC';
        
        $templateArray = $db->query($sql)->fetchAll();

        //reorder the input array based on the order of the 'vendor rank array'
        $retval = $this->customSort($templateArray, $targetArray);
        return $retval; 
    }
    
    /***
     * Based on the source array the target array will be reordered ,so the vendors with highest rank will be on the top.
     * @param array $templateArray
     * @param array $targetArray
     * @return array
     */
    private function customSort($templateArray,$targetArray){
        $order =array_column($templateArray, 'netValue', 'vendorId');
        usort($targetArray, function ($a, $b) use ($order) {
            //$a < $b => -1
            //$a > $b => 1
            //$a == $b => 0
            $aCnt = empty($order[$a['id']]) ? false : $order[$a['id']];            
            $bCnt = empty($order[$b['id']]) ? false : $order[$b['id']];
            if($aCnt === $bCnt) {
                return strcmp($a['text'], $b['text']);
            }
            if ($aCnt === false) {
                return 1;
            }
            if ($bCnt === false) {
                return -1;
            }
            return $bCnt - $aCnt;
        });
            
        return $targetArray;
    }
    /***
     * The function will merge the data from PaymentDetails table into the vendors store
     * @param array $vendorsArray
     */
    public function getPaymentTerms($vendorsArray){
        if(empty($vendorsArray)){
            return;
        }
        $buildIn="";
        foreach($vendorsArray as $row){
            $buildIn.=",".$row['id'];
        }
        $s = $this->db->select()->from("PaymentDetails")->where("Vid IN(".substr($buildIn,1).")");
        $data = $this->db->fetchAll($s);
        foreach($data as $row){
            $index = array_search($row['Vid'], array_column($vendorsArray, 'id'));
            
            if(empty($vendorsArray[$index]['paymentTerms'])){
                $vendorsArray[$index]['paymentTerms']=array();
            }
            array_push($vendorsArray[$index]['paymentTerms'], $row);
        }
        return $vendorsArray;
    }

    public function mapTvinServerFields($fieldname){
        return $this->mapFields[$fieldname];
    }
    
    public function getVendors($sourcelang,$targetlang){
        $vendorService = ZfExtended_Factory::get('erp_Models_Tvin_VendorService');
        /* @var $vendorService erp_Models_Tvin_VendorService */
        
        $vendorService->setSourceLang($sourcelang);
        $vendorService->setTargetlang($targetlang);
        //send the request
        $responseData = $vendorService->open();
        if(empty($responseData)){
            return [];
        }  
        return $this->decodeAndMergeVendorData($responseData);
    }
    
    /***
     * Decode the response data from the server and put the data into array
     * @param json $responseData
     * @return array
     */
    private function decodeAndMergeVendorData($responseData){
        $responseDataDecoded = $responseData;
        if(!is_array($responseData)){
            //decode the response data
            $responseDataDecoded = json_decode($responseData);
        }
        
        //provide the data in the forma that we need
        $data=[];
        foreach(get_object_vars($responseDataDecoded) as $property => $value) {
            foreach ($responseDataDecoded->$property as $vendor){
                $vendor->targetLang =$property;
                array_push($data, $vendor);
            }
        }
        
        return $data;
    }
}
