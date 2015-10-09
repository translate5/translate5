<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Foldertree Object Instanz wie in der Applikation benötigt
 *
 * @method void setId() setId(integer $id)
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method void setFileName() setFileName(string $name)
 * @method void setSourceLang() setSourceLang(integer $source)
 * @method void setTargetLang() setTargetLang(integer $target)
 * @method void setRelaisLang() setRelaisLang(integer $target)
 * @method void setFileOrder() setFileOrder(integer $order)
 * @method integer getId() getId()
 * @method string getTaskGuid() getTaskGuid()
 * @method string getFileName() getFileName()
 * @method integer getSourceLang() getSourceLang()
 * @method integer getTargetLang() getTargetLang()
 * @method integer getRelaisLang() getRelaisLang()
 * @method integer getFileOrder() getFileOrder()
 */
class editor_Models_File extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Files';
    
    /**
     * remove dummy directory entries
     * @param array $idList
     */
    public function cleanupDirectoryIncrements(array $idList) {
        $this->db->delete('`id` in ('.join(',', $idList).')');
    }
    
    /**
     * @param array $taskGuids
     * @return array taskGuid => cnt
     */
    public function getFileCountPerTasks(array $taskGuids) {
        if(empty($taskGuids)) {
            return array();
        }
        
        $s = $this->db->select()
        ->from($this->db, array('cnt' => 'count(id)', 'taskGuid'))
        ->where('taskGuid in (?)', $taskGuids)
        ->group('taskGuid');
        
        $keys = array();
        $values = array_map(function($item) use (&$keys){
            $keys[] = $item['taskGuid'];
            return $item['cnt'];
        }, $this->db->fetchAll($s)->toArray());
        return array_combine($keys, $values);
    }
}