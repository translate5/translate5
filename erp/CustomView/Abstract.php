<?php

abstract class erp_CustomView_Abstract{
    protected $filters=array();
    protected $tableName='ERP_order';
    protected $name='offer';
    protected $label='Angebote';
    
    abstract public function __construct();
    
    public function getName() {
        return $this->name;
    }
    
    public function getTablename() {
        return $this->tableName;
    }
    
    public function getFilters(){
        return $this->filters;
    }
    
    public function getLabel(){
        return $this->label;
    }
}