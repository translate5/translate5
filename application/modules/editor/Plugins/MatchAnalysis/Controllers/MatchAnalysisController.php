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

/**
 */
class editor_Plugins_MatchAnalysis_MatchAnalysisController extends ZfExtended_RestController
{
    /**
     * @var editor_Plugins_MatchAnalysis_Models_MatchAnalysis
     */
    protected $entityClass = 'editor_Plugins_MatchAnalysis_Models_MatchAnalysis';

    /**
     * @var editor_Plugins_MatchAnalysis_Export_ExportExcel
     */
    protected $helperExcelClass = 'editor_Plugins_MatchAnalysis_Export_ExportExcel';

    /**
     * @var editor_Plugins_MatchAnalysis_Export_ExportXml
     */
    protected $helperXMLClass = 'editor_Plugins_MatchAnalysis_Export_ExportXml';


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
        $this->view->rows = $this->entity->loadByBestMatchRate($taskGuid);
    }


    public function exportAction()
    {
        $params = $this->getAllParams();

        $rows = $this->entity->loadByBestMatchRate($params['taskGuid'], true);


        switch ($params["type"]) {
            case "exportExcel":
                ZfExtended_Factory::get($this->helperExcelClass)->generateExcel($rows);
                break;
            case "exportXml":
                $x = ZfExtended_Factory::get($this->helperXMLClass)->generateXML($rows, $params['taskGuid']);
                echo $x->asXML();

                break;
        }
    }
}