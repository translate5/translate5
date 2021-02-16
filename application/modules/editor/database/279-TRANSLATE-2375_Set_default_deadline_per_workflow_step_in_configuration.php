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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
  README: 279-TRANSLATE-2375_Set_default_deadline_per_workflow_step_in_configuration
  Generates default deadline date config for each workflo and workflow step
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '279-TRANSLATE-2375_Set_default_deadline_per_workflow_step_in_configuration.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}


$wm = ZfExtended_Factory::get('editor_Workflow_Manager');
/* @var $wm editor_Workflow_Manager */

$stepToIgnode=['no workflow','workflowEnded'];
$configs = [];
foreach ($wm->getWorkflowData() as $workflow){
    foreach ($workflow->stepChain as $step) {
        if(!in_array($step, $stepToIgnode)){
            $configs[] = '.'.$workflow->id.'.'.$step.'.';
        }
    }
}
if(empty($configs)){
    return;
}
$configs=array_map(function($item){
    return 'runtimeOptions.workflow'.$item.'defaultDeadlineDate';
}, $configs);


foreach ($configs as $config){
    $model = ZfExtended_Factory::get('editor_Models_Config');
    /* @var $model editor_Models_Config */
    $model->setConfirmed(1);
    $model->setModule('editor');
    $model->setCategory('workflow');
    $model->setType('integer');
    $model->setDescription('');
    $model->setLevel(editor_Models_Config::CONFIG_LEVEL_TASKIMPORT);
    $model->setDescription('The config defines, how many days the deadline should be in the future based on the order date');
    $model->setGuiGroup('Workflow');
    $model->setValue("");
    $model->setDefault("");
    $model->setDefaults("");
    
    $tmp = explode('.', str_replace(['runtimeOptions.workflow.','.defaultDeadlineDate'], '',$config ));
    
    $model->setGuiName('Default deadline date: workflow:'.$tmp[0].',step:'.$tmp[1]);
    $model->setName($config);
    $model->save();
}