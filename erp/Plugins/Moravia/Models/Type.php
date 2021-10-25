<?php 

/***
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 */
class erp_Plugins_Moravia_Models_Type extends  ZfExtended_Models_Entity_Abstract{
    protected $dbInstanceClass = 'erp_Plugins_Moravia_Models_Db_Type';
    protected $validatorInstanceClass = 'erp_Plugins_Moravia_Models_Validator_Type';
}