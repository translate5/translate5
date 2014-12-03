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
 * DB Access for Segment Meta Data
 */
class editor_Models_Db_SegmentMeta extends Zend_Db_Table_Abstract {
    protected $_name = 'LEK_segments_meta';
    public $_primary = 'id';

    /**
     * Adds a columns to the meta table
     * type is one of editor_Models_Segment_Meta::META_TYPE_* constants
     * 
     * @param string $name
     * @param string $type
     * @param mixed $default
     * @param string $comment
     * @param integer $length
     */
    public function addColumn($columnname, $type, $default, $comment, $length = 0) {
        switch($type) {
            case editor_Models_Segment_Meta::META_TYPE_BOOLEAN:
                $type = 'TINYINT';
                $default = (int)(boolean)$default;
                break;
            case editor_Models_Segment_Meta::META_TYPE_INTEGER:
                $type = 'INT(11)';
                $default = (int)$default;
                break;
            case editor_Models_Segment_Meta::META_TYPE_FLOAT:
                $type = 'FLOAT(5,2)';
                $default = (float)$default;
                break;
            case editor_Models_Segment_Meta::META_TYPE_STRING:
                $type = 'VARCHAR('.(empty($length) ? '255' : $length).')';
                $default = "'".(string)addslashes($default)."'";
                break;
            default:
                break;
        }
        if(is_null($default)) {
            $default = 'NULL';
        }
        $alter = 'ALTER TABLE `%s` ADD COLUMN `%s` %s DEFAULT %s COMMENT "%s";';
        $this->_db->query(sprintf($alter, $this->_name, $columnname, $type, $default, addslashes($comment)));
        $this->_metadata = array();
        $this->_metadataCache = null;
        $this->_setupMetadata();
    }
}