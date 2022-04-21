<?php
/*
 * START LICENSE AND COPYRIGHT
 *
 *  This file is part of translate5
 *
 *  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 *
 *  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 *
 *  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 *  as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 *  included in the packaging of this file.  Please review the following information
 *  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 *  http://www.gnu.org/licenses/agpl.html
 *
 *  There is a plugin exception available for use with this release of translate5 for
 *  translate5: Please see http://www.translate5.net/plugin-exception.txt or
 *  plugin-exception.txt in the root folder of translate5.
 *
 *  @copyright  Marc Mittag, MittagQI - Quality Informatics
 *  @author     MittagQI - Quality Informatics
 *  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 * 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
 *
 * END LICENSE AND COPYRIGHT
 */

/**
 */
class editor_Plugins_MatchAnalysis_MatchAnalysisController extends ZfExtended_RestController
{
    /**
     * @var string
     */
    protected $entityClass = 'editor_Plugins_MatchAnalysis_Models_MatchAnalysis';

    /**
     * @var editor_Plugins_MatchAnalysis_Models_MatchAnalysis
     */
    protected $entity;

    public function indexAction()
    {
        $taskGuid = $this->getParam('taskGuid', false);
        if (empty($taskGuid)) {
            //check if the taskGuid is provided via filter
            $this->entity->getFilter()->hasFilter('taskGuid', $taskGuid);
            $taskGuid = $taskGuid->value ?? null;
        }
        if (empty($taskGuid)) {
            // MatchAnalysis Plug-In: tried to load analysis data without providing a valid taskGuid
            // Reason is unfixed: TRANSLATE-1637: MatchAnalysis: Errors in Frontend when analysing multiple tasks
            throw new editor_Plugins_MatchAnalysis_Exception("E1103");
        }

        //INFO: this is a non api property. It is used only for the tests
        //if not grouped is set, load all analysis records (only the last analysis) for the task guid
        $notGrouped = $this->getParam('notGrouped', false);
        if ($notGrouped) {
            $this->view->rows = $this->entity->loadLastByTaskGuid($taskGuid);
            return;
        }

        // based on a request parameter, set the analysis calculation unit
        $this->view->rows = $this->entity->loadByBestMatchRate($taskGuid,unitType: $this->getParam('unitType'));

        $fieldConfig = [[
            'name' => 'id',
            'type' => 'int'
        ],[
            'name' => 'created',
        ],[
            'name' => 'unitCountTotal',
            'type' => 'int'
        ]];
        foreach($this->entity->getFuzzyRanges() as $begin => $end) {
            $fieldConfig[] = [
                'name' => (string) $begin,
                'begin' => (string) $begin, //we just deliver begin and end in the field config to be used by the grid in the GUI then
                'end' => (string) $end,
                'type' => 'int',
            ];
        }

        //the columns information is calculated from the field data in the GUI
        $this->view->metaData = [
            "fields" => $fieldConfig
        ];
    }


    public function exportAction()
    {
        $params = $this->getAllParams();

        /* @var $task editor_Models_Task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($params['taskGuid']);

        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $fileName = $translate->_('Trefferanalyse').' - '.$task->getTaskName();
        $taskNr = $task->getTaskNr();
        if(!empty($taskNr)) {
            $fileName = $fileName . ' - ('.$taskNr.')';
        }

        switch ($params["type"]) {
            case "exportExcel":
                $rows = $this->entity->loadByBestMatchRate($params['taskGuid'],unitType: $this->getParam('unitType'));
                $exporter = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Export_ExportExcel');
                /* @var $exporter editor_Plugins_MatchAnalysis_Export_ExportExcel */
                $exporter->generateExcelAndProvideDownload($task, $rows, $fileName);
                break;
            case "exportXml":
                $rows = $this->entity->loadByBestMatchRate($params['taskGuid'], false, $this->getParam('unitType'));
                $exporter = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Export_Xml', [$task]);
                /* @var $exporter editor_Plugins_MatchAnalysis_Export_Xml */

                $exporter->setIsCharacterBased($this->getParam('unitType') === 'character');

                $x = $exporter->generateXML($rows, $params['taskGuid']);

                $fileName = $fileName.' '.date('- Y-m-d').'.xml';
                
                header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($fileName));
                header('Content-Type:text/xml');
                // if you want to directly download then set expires time
                header('Expires: 0');
                
                //with XML formatting:
//                 $dom = dom_import_simplexml($x)->ownerDocument;
//                 $dom->formatOutput = true;
//                 echo $dom->saveXML();
//                 break;
                
                echo $x->asXML();

                break;
            default :
                throw new ZfExtended_NotFoundException('No analysis for given type found');
        }
    }
}