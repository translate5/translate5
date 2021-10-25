<?php

class erp_Plugins_Moravia_Models_Validator_Pmcustomers extends ZfExtended_Models_Validator_Abstract {
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Models_Validator_Abstract::defineValidators()
    */
    protected function defineValidators() {
        $this->addValidator('id', 'int',[],true);
        $this->addValidator('name', 'stringLength', array('min' => 0, 'max' => 255));
    }
}
