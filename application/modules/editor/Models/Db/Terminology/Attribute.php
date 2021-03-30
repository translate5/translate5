<?php
/**
 * Class to access the table with the name of the class name (in lower case)
 */
class editor_Models_Db_Terminology_Attribute extends Zend_Db_Table_Abstract {
    protected $_name    = 'terms_attributes';
    public $_primary = 'id';
}