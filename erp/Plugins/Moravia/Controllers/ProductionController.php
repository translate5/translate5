<?php

class erp_Plugins_Moravia_ProductionController extends ZfExtended_RestController {
    protected $entityClass = 'erp_Plugins_Moravia_Models_ProductionData';
    /**
     * @var erp_Plugins_Moravia_Models_ProductionData
     */
    protected $entity;
    
    
    public function billcollectionAction(){
        $rowsUpdated=$this->updateBillCollection();
        $this->view->rows =[];
        $this->view->total =$rowsUpdated;
    }
    
    public function balancevaluecheckAction(){
        if($this->getParam('handoffNumber')==null){
            $this->view->rows =[];
            $this->view->total =0;
            return;
        }
        
        //if the balance value check field is set, update the balance value check for the handoff
        if(array_key_exists('balanceValueCheck',$this->getRequest()->getParams())){
            $balanceValueCheck=$this->getParam('balanceValueCheck');
            if(!empty($balanceValueCheck)){
                $balanceValueCheck=filter_var($balanceValueCheck, FILTER_VALIDATE_BOOLEAN);
            }else{
                $balanceValueCheck=null;
            }
            $this->entity->updateBalanceValueCheck($this->getParam('handoffNumber'),$balanceValueCheck);
            return;
        }
        //the request form record
        $record=json_decode($this->getParam('record'),true);
        $return=$this->entity->balanceValueCheck((int)$this->getParam('handoffNumber'),$record);
        //if it is string, set the fail message
        if(is_string($return)){
            $this->view->message=$return;
            $return=[];
        }
        $this->view->rows=$return;
        $this->view->total=count($return);
    }
    
    /***
     * Update the collected orders from bill collection.
     * The data which will be updated, is loaded based on the filter set on the frontend.
     * @return number
     */
    protected function updateBillCollection(){
        if($this->getParam('dateField')==null || $this->getParam('dateValue')==null || $this->getParam('state')==null){
            return 0;
        }
        
        $model=ZfExtended_Factory::get('erp_Models_Order');
        /* @var $model erp_Models_Order */
        $tmpFilter=ZfExtended_Factory::get('ZfExtended_Models_Filter_ExtJs',[
            $model,
            $this->getParam('filter')
        ]);
        
        //when the current filter status is prapproved set the status to billed
        //when the current filter status is billed set the status to paid
        $filterMap=[
            'prapproved'=>'billed',
            'billed'=>'paid'
        ];
        //set the model filter from the frontend filter
        $model->filterAndSort($tmpFilter);
        
        //load the filtered rows
        $rows=$model->loadAll(false);
        
        $ids=array_column($rows,'id');
        
        $order=ZfExtended_Factory::get('erp_Models_Order');
        /* @var $order erp_Models_Order */
        //check if for the curent select there is debitNumber defined, if yes use it
        $debitNumber=array_column($rows,'debitNumber');
        $debitNumber=array_unique($debitNumber);
        $debitNumber = array_filter($debitNumber);

        if(empty($debitNumber)){
            $debitNumber=$order->generateDebitNumber($this->getParam('dateValue'));
        }else{
            $debitNumber=$debitNumber[0];
        }
        
        return $model->db->update([
            $this->getParam('dateField')=>$this->getParam('dateValue'),
            'state'=>$filterMap[$this->getParam('state')],
            'debitNumber'=>$debitNumber
        ], ['id IN(?)'=>$ids]);
    }
}