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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Default Model for Plugin SegmentStatistics
 * 
 * @method void setId() setId(integer $id)
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method void setSegmentId() setSegmentId(integer $segmentid)
 * @method void setFileId() setFileId(integer $fileid)
 * @method void setFieldName() setFieldName(string $name)
 * @method void setTermNotFound() setTermNotFound(integer $count)
 * @method void setCharCount() setCharCount(integer $count)
 * 
 * @method integer getId() getId()
 * @method string getTaskGuid() getTaskGuid()
 * @method integer getSegmentId() getSegmentId()
 * @method integer getFileId() getFileId()
 * @method string getFieldName() getFieldName()
 * @method integer getTermNotFound() getTermNotFound()
 * @method integer getCharCount() getCharCount()
 */
class editor_Plugins_SegmentStatistics_Models_Statistics extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Plugins_SegmentStatistics_Models_Db_Statistics';
    
    /**
     * returns the statistics summary for the given taskGuid
     * @param string $taskGuid
     * @return array
     */
    public function getSummary($taskGuid) {
        $files = $this->getFiles($taskGuid);
        $cols = array('fileId', 'fieldName', 'charCount' => 'SUM(charCount)', 
                    'termNotFoundCount' => 'SUM(termNotFound)', 'segmentsPerFile' => 'COUNT(id)');
        $s = $this->db->select()
            ->from($this->db, $cols)
            ->where('taskGuid = ?', $taskGuid)
            ->group('fileId')
            ->group('fieldName');
        $rows = $this->db->fetchAll($s);
        
        $result = array();
        foreach($rows as $row) {
            $stat = $row->toArray();
            $stat['fileName'] = $files[$stat['fileId']];
            $result[] = $stat;
        }
        return $result;
    }
    
    /**
     * returns a map between fileIds and filepaths for the desired task
     * @param string $taskGuid
     * @return [string]
     */
    protected function getFiles($taskGuid) {
        $config = Zend_Registry::get('config');
        $filetree = ZfExtended_Factory::get ( 'editor_Models_Foldertree' );
        /* @var $filetree editor_Models_Foldertree */
        
        $files = $filetree->getPaths($taskGuid, $filetree::TYPE_FILE );
        $proofRead = $config->runtimeOptions->import->proofReadDirectory;
        foreach ( $files as $fileid => $file ) {
            $files [$fileid] = trim ( str_replace ( '#!#' . $proofRead, '', '#!#' . $file ), '/\\' );
        }
        return $files;
    }
}