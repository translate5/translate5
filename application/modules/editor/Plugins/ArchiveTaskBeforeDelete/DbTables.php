<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 */
class editor_Plugins_ArchiveTaskBeforeDelete_DbTables {
    
    //TODO
    //'Sonderbehandlung' => 'LEK_segment_views
    
    //  → mysqldump mit --where zur Einschränkung, im Where Statement kann ein subselect verwendet werden, allerdings nur mit --single-transaction, z.B.:
    //mysqldump -h mittagqi -u root -p icorrectT5 LEK_segments2terms --single-transaction --where="segmentId in (select id from LEK_segments where taskGuid = '{35f7268b-6cc1-4dd6-9a76-46e1b81dbd40}')"

    /**
     * Here are defined all Editor tables, and how to deal with them on using the archiver:
     * false → means do not archive the table at all (since it has no correlation with the task at all)
     * true → backup the whole table (currently only useful for Zf_dbversion)
     * 'taskGuid' → use the default taskGuid where clause
     * any other string → is used as parameter for mysqldump, {TASKGUID} is later replaced with the taskGuid to be archived
     * @var array
     */
    protected $tables = array(
        'LEK_browser_log' => false,
        'LEK_customer' => false,
        'LEK_categories' => false,
        'LEK_languageresources_category_assoc' => false,
        'LEK_change_log' => false,
        'LEK_comments' => 'taskGuid',
        'LEK_comment_meta' => 'taskGuid',
        'LEK_customer_config' => false,
        'LEK_files' => 'taskGuid',
        'LEK_file_filter' => 'taskGuid',
        'LEK_foldertree' => 'taskGuid',
        'LEK_languages' => false,
        // languageresources tables disabled for archiving, since task data is not depending from them, and existence of languageresources does not depend on the existence of the task
        'LEK_languageresources_customerassoc' => false,
        'LEK_languageresources_internal_tm' => false,
        'LEK_languageresources_languages' => false,
        'LEK_languageresources_log' => false,
        'LEK_languageresources_usage_log' => false,
        'LEK_languageresources_usage_log_sum' => false,
        'LEK_languageresources_taskassoc' => false,
        'LEK_languageresources' => false,
        'LEK_match_analysis' => 'taskGuid',
        'LEK_match_analysis_batchresults' => false,
        'LEK_match_analysis_taskassoc' => 'taskGuid',
        'LEK_pixel_mapping' => false,
        'LEK_plugin_segmentstatistic_terms' => 'taskGuid',
        'LEK_plugin_segmentstatistics' => 'taskGuid',
        'LEK_workflow_log' => false, //its just logging, we dont archive that
        'LEK_segment_data' => 'taskGuid',
        'LEK_segment_field' => 'taskGuid',
        'LEK_segment_history' => 'taskGuid',
        'LEK_segment_history_data' => 'taskGuid',
        'LEK_segment_user_assoc' => 'taskGuid',
        'LEK_segments' => 'taskGuid',
        'LEK_segments_meta' => 'taskGuid',
        'LEK_segment_quality' => 'taskGuid',
        // LEK_segment_tags is just a temorary datamodel during import
        'LEK_segment_tags' => false,
        //not needed anymore, but keeping as reference how to to implement filters
        //'LEK_skeletonfiles' => array('--single-transaction', "--where=fileId in (select id from LEK_files where taskGuid = '{TASKGUID}')"),
        'LEK_task' => 'taskGuid',
        'LEK_task_config' => 'taskGuid',
        'LEK_task_excelexport' => 'taskGuid',
        'LEK_taskUserAssoc' => 'taskGuid',
        'LEK_taskUserTracking' => 'taskGuid',
        'LEK_task_log' => 'taskGuid',
        'LEK_task_meta' => 'taskGuid',
        'LEK_task_migration' => 'taskGuid',
        'LEK_task_usage_log' => false,
        'LEK_terms' => false,
        'LEK_term_attributes'=>false,
        'LEK_term_attributes_label'=>false,
        'LEK_term_attribute_history'=>false,
        'LEK_term_attribute_proposal'=>false,
        'LEK_term_entry'=>false,
        'LEK_term_history'=>false,
        'LEK_term_proposal'=>false,
        'LEK_user_assoc_default' => false,
        'LEK_user_changelog_info' => false,
        'LEK_user_config' => false,
        'LEK_user_meta' => false,
        'LEK_visualreview_files' => 'taskGuid',
        'LEK_visualreview_segmentmapping' => 'taskGuid',
        // visualreview font tables disabled for archiving, since task data is not depending from them and fonts can be reapplied
        'LEK_visualreview_font' => false,
        'LEK_visualreview_font_taskassoc' => false,
        'LEK_workflow' => false,
        'LEK_workflow_action' => false,
        'LEK_workflow_step' => false,
        'LEK_workflow_userpref' => 'taskGuid',
        'Zf_dbversion' => true
    );

    /**
     * This method is intended to be called directly from CLI, in the build scripts of translate5.
     * So it is ensured, that no new LEK table (relating to tasks) is forgotten in the archive plugin
     */
    public static function runFromCLI($projectRoot, $zendLib) {
        self::initCliRuntime($projectRoot, $zendLib);
        $instance = ZfExtended_Factory::get(__CLASS__);
        $missing = $instance->checkMissingInList();
        if(empty($missing)) {
            //since other checks will follow we can not exit(0) here
            return;
        }
        if(!empty($missing['addedToSystem'])) {
            echo 'The following DB tables are not listed in '.__CLASS__.PHP_EOL;
            print_r($missing['addedToSystem']);
        }
        if(!empty($missing['missingInSystem'])) {
            echo 'The following DB tables are not in the DB but listed in '.__CLASS__.PHP_EOL;
            echo 'Did you forgot to apply the tables to your local DB?'.PHP_EOL;
            print_r($missing['missingInSystem']);
        }
        exit(1); //since used as CLI use CLI exit codes here, 0 is true, other than 0 is error
    }

    /**
     * This method is intended to be called directly from CLI, in the build scripts of translate5.
     * So it is ensured, that no new LEK table (relating to tasks) is forgotten in the archive plugin
     */
    public static function run($projectRoot, $zendLib): array {
        self::initCliRuntime($projectRoot, $zendLib);
        $instance = ZfExtended_Factory::get(__CLASS__);
        return $instance->checkMissingInList();
    }
    /**
     * Runs our check for unit-test
     * @return array
     */
    public static function runTest(): array {
        $instance = ZfExtended_Factory::get(__CLASS__);
        return $instance->checkMissingInList();
    }
    
    protected function checkMissingInList(): array {
        $config = Zend_Registry::get('config');
        $db = Zend_Db::factory($config->resources->db);
        
        $filtered = array_filter($db->listTables(), function($table){
            if(preg_match('/LEK_segment_view_[a-z0-9]{32}/', $table)) {
                return false;
            }
            return strpos($table, 'LEK_') === 0;
        });
        
        $filtered[] = 'Zf_dbversion';
        
        $configuredTables = array_keys($this->tables);
        $addedToSystem = array_diff($filtered, $configuredTables);  //gib die vom ersten die nicht im zweiten
        $missingInSystem = array_diff($configuredTables, $filtered);  //gib die vom ersten die nicht im zweiten
        
        if(empty($addedToSystem) && empty($missingInSystem)){
            return [];
        }
        
        return [
            'addedToSystem' => $addedToSystem,
            'missingInSystem' => $missingInSystem,
        ];
    }
    
    /**
     * Used to init the translate5 eco system
     *
     * @TODO for futural tests integrated in build this method should be placed more reusable
     *
     * @param string $projectRoot path to the project installation directory
     * @param string $zendLib path to the zend library
     */
    protected static function initCliRuntime($projectRoot, $zendLib) {
        //presetting Zend include path, get from outside!
        $path = get_include_path();
        set_include_path($projectRoot.PATH_SEPARATOR.$path.PATH_SEPARATOR.$zendLib);
        
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['HTTP_HOST'] = 'localhost';
        define('APPLICATION_PATH', $projectRoot.DIRECTORY_SEPARATOR.'application');
        define('APPLICATION_ENV', 'application');

        require_once 'Zend/Session.php';
        Zend_Session::$_unitTestEnabled = true;
        require_once 'library/ZfExtended/BaseIndex.php';
        $index = ZfExtended_BaseIndex::getInstance();
        $index->initApplication()->bootstrap();
        $index->addModuleOptions('default');
    }
    
    /**
     * returns an array with table names and
     * @param string $taskGuid
     * @return multitype:string mixed
     */
    public function getArchiveListFor($taskGuid) {
        $result = array();
        $replaceTaskGuid = function($whatToDo) use ($taskGuid) {
            return str_replace('{TASKGUID}', $taskGuid, $whatToDo);
        };
        foreach($this->tables as $table => $whatToDo) {
            if($whatToDo === false) {
                continue;
            }
            if($whatToDo === true) {
                $result[$table] = '';
                continue;
            }
            if($whatToDo === 'taskGuid') {
                $result[$table] = '--where=taskGuid = \''.$taskGuid.'\'';
                continue;
            }
            if(is_array($whatToDo)) {
                $result[$table] = array_map($replaceTaskGuid, $whatToDo);
                continue;
            }
            $result[$table] = $replaceTaskGuid($whatToDo);
        }
        return $result;
    }
}
