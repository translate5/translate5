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
 * Helper Class to join a select statement to the segment meta table and filter elements by meta attributes out of config
 */
class editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin {
    const META_ALIAS = 'meta';
    /**
     * @var string
     */
    protected $tableAlias = 'target';
    protected $segIdCol = 'segmentId';

    /**
     * @var array
     */
    protected $metaToIgnore = array();
    
    /**
     * @var boolean
     */
    protected static $enabled = true;
    
    public function __construct() {
        $this->initFilterConditions();
    }
    
    /**
     * Since this class is used at multiple places it can be completly disabled in a static manner
     * @param bool $enabled
     */
    public static function setEnabled($enabled = true) {
        self::$enabled = $enabled;
    }
    
    protected function initFilterConditions() {
        $config = Zend_Registry::get('config');
        $metaToIgnore = $config->runtimeOptions->plugins->SegmentStatistics->metaToIgnore;
        foreach($metaToIgnore as $metadata => $val){
            if($val){
                $this->metaToIgnore[]= $metadata;
            }
        }
    }
    
    /**
     * @return boolean
     */
    protected function hasFilterConditions() {
        return !empty($this->metaToIgnore);
    }
    
    /**
     * @return array
     */
    public function getFilterConditions() {
        return $this->metaToIgnore;
    }
    
    /**
     * sets the target table alias for the table to be joined
     * @param string $targetTableAlias
     */
    public function setTarget($targetTableAlias) {
        $this->tableAlias = $targetTableAlias;
    }
    
    /**
     * sets the segment id column name of the table to be joined
     * @param string $colName
     */
    public function setSegmentIdColumn($colName) {
        $this->segIdCol = $colName;
    }
    
    /**
     * joins the given statement to the segmentsMeta table to filter several segments for statistics
     * @param Zend_Db_Table_Select $s
     * @param string $taskGuid
     * @return Zend_Db_Table_Select
     */
    public function segmentsMetaJoin(Zend_Db_Table_Select $s, $taskGuid) {
        if(!self::$enabled || !$this->hasFilterConditions()) {
            return $s;
        }
        $segMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $segMeta editor_Models_Db_SegmentMeta */
        
        $meta = array(self::META_ALIAS => $segMeta->info($segMeta::NAME));
        $s->join($meta, self::META_ALIAS.'.segmentId = '.$this->tableAlias.'.'.$this->segIdCol, array())
        ->where(self::META_ALIAS.'.taskGuid = ?', $taskGuid)
        ->setIntegrityCheck(false);
        return $this->addFilterCondition($s);
    }

    /**
     * Adds the segmentsMeta table filter condition from config to the statement
     * @param Zend_Db_Table_Select $s
     * @return Zend_Db_Table_Select
     */
    protected function addFilterCondition(Zend_Db_Table_Select $s) {
        foreach($this->metaToIgnore as $metadata){
            //Since the filter includes all segments not having the meta value, 
            //we have to select all 0 values
            $s->where(self::META_ALIAS.'.'.$metadata.' = ?', 0);
        }
        return $s;
    }
}