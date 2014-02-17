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
 * Foldertree Object Instanz wie in der Applikation benötigt
 *
 */
class editor_Models_SegmentHistory extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_SegmentsHistory';

    protected $_segmentHistoryData = array();

    protected $_lengthToTruncateSegmentsToSort = null;

    public function __construct()
    {
        $session = new Zend_Session_Namespace();
        $this->lengthToTruncateSegmentsToSort = $session->runtimeOptions->lengthToTruncateSegmentsToSort;
    }
    /**
     * @param $segment
     * @return string
     */
    protected function _truncateSegmentsToSort($segment)
    {
        if(!is_string($segment)){
            return $segment;
        }
        return mb_substr($segment,0,$this->lengthToTruncateSegmentsToSort,'utf-8');
    }
    /**
     * @param $name
     * @param $value
     */
    public function setField($name, $value)
    {
        $this->_segmentHistoryData[$name]['original'] = $value;
        $this->_segmentHistoryData[$name]['originalMd5'] = md5($value);
        $this->_segmentHistoryData[$name]['originalToSort'] = $this->_truncateSegmentsToSort($value);
    }
    /**
     * @param $name
     * @param $value
     */
    public function setFieldEdited($name, $value)
    {
        $this->_segmentHistoryData[$name]['edited'] = $value;
        $this->_segmentHistoryData[$name]['editedMd5'] = md5($value);
        $this->_segmentHistoryData[$name]['editedToSort'] = $this->_truncateSegmentsToSort($value);
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getField($name)
    {
        return $this->_segmentHistoryData[$name]['original'];
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getFieldMd5($name)
    {
        return $this->_segmentHistoryData[$name]['originalMd5'];
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getFieldToSort($name)
    {
        return $this->_segmentHistoryData[$name]['originalToSort'];
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getFieldEdited($name)
    {
        return $this->_segmentHistoryData[$name]['edited'];
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getFieldEditedMd5($name)
    {
        return $this->_segmentHistoryData[$name]['editedMd5'];
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getFieldEditedToSort($name)
    {
        return $this->_segmentHistoryData[$name]['editedToSort'];
    }

    /**
     * @param $userGuid
     * @return array
     */
    public function loadByUserGuid($userGuid)
    {
        $s = $this->db->select()
            ->where('userGuid = ?', $userGuid)
            ->order('id ASC');
        return $this->db->getAdapter()->fetchAll($s);
    }

    /**
     * @param $UserGuid
     */
    public function initHistoryData($UserGuid)
    {
        $segmentHistorySata = new editor_Models_SegmentHistoryData();
        $this->_segmentHistoryData = $segmentHistorySata->loadByUserGuid($UserGuid);
    }
}
