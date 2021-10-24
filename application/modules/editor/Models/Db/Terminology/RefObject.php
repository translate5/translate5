<?php
/**
 * Class to access the table with the name of the class name (in lower case)
 */
class editor_Models_Db_Terminology_RefObject extends Zend_Db_Table_Abstract {
    protected $_name = 'terms_ref_object';
    public $_primary = 'id';
}