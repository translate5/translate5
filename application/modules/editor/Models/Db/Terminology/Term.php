<?php
/**
 * Class to access the table with the name of the class name (in lower case)
 */
class editor_Models_Db_Terminology_Term extends Zend_Db_Table_Abstract {
    protected $_name    = 'terms_term';
    public $_primary = 'id';
}