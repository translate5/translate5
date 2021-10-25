<?php

class erp_Plugins_Moravia_Models_Validator_ProductionData extends ZfExtended_Models_Validator_Abstract {
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Models_Validator_Abstract::defineValidators()
    */
    protected function defineValidators() {
        $this->addValidator('id', 'int');
        $this->addValidator('orderId', 'int');
        $this->addValidator('endCustomer', 'stringLength', array('min' => 0, 'max' => 45));
        $this->addValidator('projectNameEndCustomer', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('type', 'stringLength', array('min' => 0, 'max' => 45));
        $this->addValidator('submissionDate', 'date', array('Y-m-d'));
        $this->addValidator('pmCustomer', 'stringLength', array('min' => 0, 'max' => 45));
        $this->addValidator('preliminaryWeightedWords', 'float');
        $this->addValidator('weightedWords', 'float');
        $this->addValidator('hours', 'float');
        $this->addValidator('handoffValue', 'float');
        $this->addValidator('prNumber', 'stringLength', array('min' => 0, 'max' => 45));
        $this->addValidator('balanceValueCheck', 'int',[],true);
        $this->addValidator('handoffNumber', 'int');
    }
}
