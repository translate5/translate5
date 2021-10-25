<?php

class erp_CustomView_Bill extends erp_CustomView_Abstract{
    public function __construct(){
        $this->name="bill";
        $this->label='Rechnungen';
        
        $this->tableName="ERP_order";
        
        $stateFilter=new stdClass();
        $stateFilter->type='list';
        $stateFilter->field='state';
        $stateFilter->table=$this->tableName;
        $stateFilter->comparison='in';
        $stateFilter->value=[erp_Models_Order::STATE_ORDERED];
        
        $this->filters=array(
            'state'=>$stateFilter
        );
    }
}