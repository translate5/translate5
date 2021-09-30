<?php
/**
 * Class to access the table with the name of the class name (in lower case)
 */
class editor_Models_Db_Terminology_TermEntry extends Zend_Db_Table_Abstract {
    protected $_name    = 'terms_term_entry';
    public $_primary = 'id';
}