<?php

class erp_Plugins_Moravia_ProductionView extends erp_CustomView_Abstract{
    
    public function __construct(){
        $this->name="production";
        $this->label='Produktion';
        $this->tableName="ERP_order_production";
        $this->filters=array();
    }
}