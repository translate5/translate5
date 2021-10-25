<?php

class erp_CustomView_Offer extends erp_CustomView_Abstract{
    public function __construct(){
        $this->name="offer";
        $this->label='Angebote';
        
        $this->tableName="ERP_order";
        
        $stateFilter=new stdClass();
        $stateFilter->type='list';
        $stateFilter->field='state';
        $stateFilter->table=$this->tableName;
        $stateFilter->comparison='in';
        $stateFilter->value=erp_Models_Order::$statesOffer;
        
        $this->filters=array(
            'state'=>$stateFilter
        );
    }
}