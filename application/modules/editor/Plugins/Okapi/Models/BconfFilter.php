<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 		     http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

/**
 * Okapi Bconf Filter Entity Object
 *
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 */
class editor_Plugins_Okapi_Models_BconfFilter extends ZfExtended_Models_Entity_Abstract {
    
    const DATA_DIR = 'editorOkapiBconf';
    
    protected $dbInstanceClass = 'editor_Plugins_Okapi_Models_Db_BconfFilter';
    protected $validatorInstanceClass = 'editor_Plugins_Okapi_Models_Validator_BconfFilter';

    /** get all the row by $bconfId
     * @param $bconfId
     * @return array|null
     */
     public function getByBconfId($bconfId): ?array {
          // find all fonts for a task
          $select = $this->db->select()
               ->where('bconfId = ?', $bconfId);
          $rows = $this->loadFilterdCustom($select);
          if($rows != null){
              return $rows;
          }
          return null;
     }

    /**
     * Override that supports composite key
     * @param int $bconfId
     * @param string $okapiId  Not in signature to match ZfExtended_Models_Entity_Abstract::load
     * @return Zend_Db_Table_Row_Abstract|null
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function load($bconfId) {
        $args = func_get_args();
        $okapiId = $args[1];
        try {
            $rowset = $this->db->find($bconfId, $okapiId);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (!$rowset || $rowset->count() == 0) {
            $this->notFound('#PK', [$bconfId, $okapiId]);
        }
        $rowset->rewind(); // triggers loading
        //load implies loading one Row, so use only the first row
        return $this->row = $rowset->current();
    }


}
