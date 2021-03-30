<?php
/**
 * Class to access the table with the name of the class name (in lower case)
 */
class editor_Models_Db_Terminology_Langset extends Zend_Db_Table_Abstract {
    protected $_name    = 'terms_langset';
    public $_primary = 'id';
}