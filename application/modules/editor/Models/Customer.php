<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**
 * Customer Entity Objekt
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * 
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * 
 * @method string getNumber() getNumber()
 * @method void setNumber() setNumber(string $number)
 * 
 * @method integer getSearchCharacterLimit() getSearchCharacterLimit()
 * @method void setSearchCharacterLimit() setSearchCharacterLimit(integer $searchCharacterLimit)
 * 
*/
class editor_Models_Customer extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Customer';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Customer';
    
    
    /***
     * Find customer by number
     * 
     * @param mixed $number
     */
    public function findCustomerByNumber($number){
        $s = $this->db->select()
        ->where('number = ?', $number);
        $res=$this->db->fetchRow($s);
        if(empty($res)) {
            return $res;
        }
        return $res->toArray();
    }
    
    /***
     * Search customers by given search string.
     * The search will provide any match on name field.
     *
     * @param string $searchString
     * @return array|array
     */
    public function search($searchString,$fields=array()) {
        $s = $this->db->select();
        if(!empty($fields)){
            $s->from($this->tableName,$fields);
        }
        $s->where('lower(name) LIKE lower(?)','%'.$searchString.'%');
        return $this->db->fetchAll($s)->toArray();
    }
}
