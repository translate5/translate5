<?php 

/***
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getOrderId() getOrderId()
 * @method void setOrderId() setOrderId(string $orderId)
 * @method string getEndCustomer() getEndCustomer()
 * @method void setEndCustomer() setEndCustomer(string $endCustomer)
 * @method string getProjectNameEndCustomer() getProjectNameEndCustomer()
 * @method void setProjectNameEndCustomer() setProjectNameEndCustomer(string $projectNameEndCustomer)
 * @method string getType() getType()
 * @method void setType() setType(string $type)
 * @method string getSubmissionDate() getSubmissionDate()
 * @method void setSubmissionDate() setSubmissionDate(string $submissionDate)
 * @method string getPmCustomer() getPmCustomer()
 * @method void setPmCustomer() setPmCustomer(string $pmCustomer)
 * @method float getPreliminaryWeightedWords() getPreliminaryWeightedWords()
 * @method void setPreliminaryWeightedWords() setPreliminaryWeightedWords(float $preliminaryWeightedWords)
 * @method float getWeightedWords() getWeightedWords()
 * @method void setWeightedWords() setWeightedWords(float $weightedWords)
 * @method float getHours() getHours()
 * @method void setHours() setHours(float $hours)
 * @method float getHandoffValue() getHandoffValue()
 * @method void setHandoffValue() setHandoffValue(float $handoffValue)
 * @method string getPrNumber() getPrNumber()
 * @method void setPrNumber() setPrNumber(string $prNumber)
 * @method integer getBalanceValueCheck() getBalanceValueCheck()
 * @method void setBalanceValueCheck() setBalanceValueCheck(integer $balanceValueCheck)
 * @method integer getHandoffNumber() getHandoffNumber()
 * @method void setHandoffNumber() setHandoffNumber(integer $handoffNumber)
 */
class erp_Plugins_Moravia_Models_ProductionData extends  ZfExtended_Models_Entity_Abstract{
    protected $dbInstanceClass = 'erp_Plugins_Moravia_Models_Db_ProductionData';
    protected $validatorInstanceClass = 'erp_Plugins_Moravia_Models_Validator_ProductionData';
    
    
    /***
     * Production table fields to request fields map
     * @var array
     */
    protected $fieldMap=[
        'productionType'=>'type'
    ];
    
    /***
     * List of all handoff production specific fields. When one of those fields is changed for line item, its value should be updated of each line items of the handoff.
     * @var array
     */
    protected $handoffSpecificProductionFields=[
        'type',
        'handoffValue',
        'endCustomer',
        'projectNameEndCustomer',
        'pmCustomer'
    ];
    
    
    /***
     * Data container for each handoff production specific field
     * @var array
     */
    protected $handoffSpecificProductionFieldsData=[];
    
    
    /***
     * List of all handoff order specific fields. When one of those fields is changed for line item, its value should be updated of each line items of the handoff.
     * @var array
     */
    protected $handoffSpecificOrderFields=[
        'releaseDate',
        'pmId',
    ];
    
    
    /***
     * Data container for each handoff specific order field
     * @var array
     */
    protected $handoffSpecificOrderFieldsData=[];
    
    /***
     * 
     * @param array $data
     */
    public function setData(array $data) {
        //reset the handoff specific production field data
        $this->handoffSpecificProductionFieldsData=[];
        //reset the handoff specific order field adta
        $this->handoffSpecificOrderFieldsData=[];
        
        //init the entity 
        foreach ($data as $field=>$value){
            if($this->hasField($field) || $this->hasProductionField($field)){
                $this->__call('set'.$field, array($value));
                
                //collect production specific line items data
                if(in_array($field, $this->handoffSpecificProductionFields)){
                    $this->handoffSpecificProductionFieldsData[$field]=$value;
                }
            }
            
            //collect order specific line items data
            if(in_array($field, $this->handoffSpecificOrderFields)){
                $this->handoffSpecificOrderFieldsData[$field]=$value;
            }
        }
    }
    
    public function save() {
        parent::save();
        $this->updateHandoffSpecificLineitemsData();
    }
    
    /***
     * Update the handoff specific data when line items is saved.
     * 
     * ex: the order/production name is handoff specific (it is the same for all line items of one handoff), 
     * so when the name is changed in one of the lineitems of the handoff, update this name for all lineitems of that handoff
     */
    public function updateHandoffSpecificLineitemsData() {
        if(!empty($this->handoffSpecificProductionFieldsData)){
            $this->db->update($this->handoffSpecificProductionFieldsData, [
                'handoffNumber=?'=>$this->getHandoffNumber()
            ]);
        }
        
        if(!empty($this->handoffSpecificOrderFieldsData)){
            $model=ZfExtended_Factory::get('erp_Models_Order');
            /* @var $model erp_Models_Order  */
            
            //find all line items of a handoff
            $lineItems=$this->findAllByHandoff($this->getHandoffNumber());
            if(empty($lineItems)){
                return;
            }
            //get all line items orderId and update the values
            $lineItems=array_column($lineItems,'orderId');
            $model->db->update($this->handoffSpecificOrderFieldsData, [
                'id IN (?)'=>$lineItems
            ]);
        }
    }
    
    /***
     * Check if the given field name exist in the field mapping array.
     * If the field value exist, the parametar value will be replaced with the mapped one.
     * @param string $field
     * @return boolean
     */
    protected function hasProductionField(&$field) {
        if(!isset($this->fieldMap[$field])){
            return false;
        }
        $field=$this->fieldMap[$field];
        return true;
    }
    
    /***
     * Load production data by given order id
     * @param integer $orderId
     */
    public function loadByOrderId($orderId){
        try {
            $s = $this->db->select()->where('orderId = ?', $orderId);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (!$row) {
            $this->notFound(__CLASS__ . '#taskGuid', $orderId);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
    }
    
    /***
     * Find all lineitems of a handoffNumber
     * 
     * @param int $handoffNumber
     * @return array
     */
    public function findAllByHandoff(int $handoffNumber) {
        $s=$this->db->select()
        ->where('handoffNumber=?',$handoffNumber);
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Find the next handof number from the production data table.
     * The next number will be the highest handoff nuber in the table + 1
     * @return number
     */
    public function findNextHandoffNumber(){
        $s = $this->db->select()->from($this->db->info(Zend_Db_Table_Abstract::NAME),array('MAX(handoffNumber) as handoffNumber'));
        $ret=$this->db->fetchRow($s)->toArray();
        if(!empty($ret)){
            $ret=$ret['handoffNumber'];
        }
        return $ret<1 ? 1 : $ret+1;
    }
    
    /***
     * Update the balance value check for the line items of the handoff.
     * The function will return empty array when the balance value check was not valid/successful for the given handoffNumber
     * @param int $handoffNumber
     * @param array $record
     * @return array|string|array
     */
    public function balanceValueCheck(int $handoffNumber,array $record){
        
        //valid states for the balance value check line items
        $validStates=[
            'prcreated',
            'prapproved',
            'billed',
            'paid'
        ];
        
        $result=[];
        //merge the form record into the check result list
        array_push($result,$record);
        
        $s = $this->db->select()
        ->from(array("pd" => $this->db->info(Zend_Db_Table_Abstract::NAME)))
        ->setIntegrityCheck(false)
        ->join(array("o" => "ERP_order"),"pd.orderId = o.id")
        ->where('pd.handoffNumber=?',$handoffNumber);

        if(!empty($record['id'])){
            $s->where('o.id NOT IN (?)',$record['id']);//ignore the form record since it is merged before
        }
        $result=array_merge($result,$this->db->fetchAll($s)->toArray());
        if(empty($result)){
            return [];
        }
        
        $config = Zend_Registry::get('config');
        
        $balanceConfig=$config->runtimeOptions->plugins->Moravia->balanceValueCheckValues->toArray();
        
        $handoffValue=0;
        $handoffValueTotal=0;
        $endCustomerName='';
        foreach ($result as $single){
            //check if the current production row has state different that the valid states for balancevaluecheck
            if(!in_array($single['state'],$validStates)){
                //one of the line item has invalid status -> reset the balance value check to null
                $this->updateBalanceValueCheck($handoffNumber,null);
                //return empty message, so the frontend knows there is no message
                return '';
            }
            
            if(empty($endCustomerName)){
                $endCustomerName=$single['endCustomer'];
            }
            
            //all results must be from the same endcustomer
            if($endCustomerName!=$single['endCustomer']){
                return 'Der Endkunde ist nicht für alle "Line-items" gleich';
            }
            
            if($handoffValue<1){
                $handoffValue=$single['handoffValue'];
            }
            
            $handoffValueTotal+=$single['billNetValue'];
        }
        
        //default border
        $minRange=$handoffValue-0.25;
        $maxRange=$handoffValue+0.25;
        
        if(isset($balanceConfig[$endCustomerName])){
            //calculate the lower range border out of the endcustomer config
            $minRange=$handoffValue-($handoffValue*$balanceConfig[$endCustomerName]/100);
        }
        
        //is the handoff total within the allowed range
        $returnValue=($handoffValueTotal>=$minRange) && ($handoffValueTotal <=$maxRange);
        
        //the balance value check failed
        if(!$returnValue){
            return [];
        }
        
        //the balance value check was successful, set for each line item the balancevaluecheck to true
        $this->updateBalanceValueCheck($handoffNumber,true);
        
        return $result;
    }
    
    /***
     * Update the balance value check for all line items of the handoff
     * @param int $handoffNumber
     * @param mixed $balanceValueCheck: the value can be true, false or null
     * @return number
     */
    public function updateBalanceValueCheck(int $handoffNumber,$balanceValueCheck=null) {
        return $this->db->update([
            'balanceValueCheck'=>$balanceValueCheck
        ], ['handoffNumber=?'=>$handoffNumber]);
    }
    
}