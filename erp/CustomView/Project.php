<?php

class erp_CustomView_Project extends erp_CustomView_Abstract{
    public function __construct(){
        $this->name="project";
        $this->label='Aufträge';
        
        $this->tableName="ERP_order";
        
        $stateFilter=new stdClass();
        $stateFilter->type='list';
        $stateFilter->field='state';
        $stateFilter->table=$this->tableName;
        $stateFilter->comparison='in';
        $stateFilter->value=erp_Models_Order::$statesBill;
        
        $this->filters=array(
            'state'=>$stateFilter
        );
    }
}