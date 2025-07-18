<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @method string getId()
 * @method void setId(int $id)
 * @method string getFileId()
 * @method void setFileId(int $fileId)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getFilter()
 * @method void setFilter(string $filterClass)
 * @method string|null getParameters()
 * @method void setParameters(string|null $parameters)
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $taskGuid)
 * @method int getWeight()
 * @method void setWeight(int $weight)
 */
class editor_Models_File_Filter extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_File_Filter';

    /**
     * Loads all file filters for a specific file and type
     */
    public function loadForFile(int $fileId, string $type): Zend_Db_Table_Rowset_Abstract
    {
        $s = $this->db->select()
            ->where('fileId = ?', $fileId)
            ->where('type = ?', $type)
            ->order('weight ASC')
            ->order('id ASC');

        return $this->db->fetchAll($s);
    }

    /**
     * Loads all file filters for a specific task and type
     */
    public function loadForTask(string $taskGuid, string $type): Zend_Db_Table_Rowset_Abstract
    {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('type = ?', $type)
            ->order('weight ASC')
            ->order('id ASC');

        return $this->db->fetchAll($s);
    }
}
