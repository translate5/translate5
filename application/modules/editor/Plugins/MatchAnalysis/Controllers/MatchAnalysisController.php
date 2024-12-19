<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\ZfExtended\Controller\Response\Header;

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

    /**
     * The download-actions need to be csrf unprotected!
     */
    protected array $_unprotectedActions = ['export'];

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
        $rows = $this->entity->loadByBestMatchRate($taskGuid, unitType: $this->getParam('unitType'));

        $fieldConfig = [[
            'name' => 'id',
            'type' => 'int',
        ], [
            'name' => 'created',
        ], [
            'name' => 'unitCountTotal',
            'type' => 'number',
        ], [
            'name' => 'penalty',
            'type' => 'number',
        ]];

        foreach ($this->entity->getFuzzyRanges() as $begin => $end) {
            $fieldConfig[] = [
                'name' => (string) $begin,
                'begin' => (string) $begin, //we just deliver begin and end in the field config to be used by the grid in the GUI then
                'end' => (string) $end,
                'type' => 'number',
            ];
        }

        // Get pricingPresetId
        $meta = ZfExtended_Factory::get(editor_Models_Task_Meta::class);
        $meta->loadByTaskGuid($taskGuid);

        $currency = $this->entity->getPricing()['currency'];
        $noPricing = $this->entity->getPricing()['noPricing'];
        $pricingPresetId = $meta->getPricingPresetId();

        $lspUserRepository = LspUserRepository::create();
        $authLspUser = $lspUserRepository->findByUserGuid(ZfExtended_Authentication::getInstance()->getUserGuid());

        if ($authLspUser && (!$authLspUser->isCoordinator() || !$authLspUser->lsp->isDirectLsp())) {
            $pricingPresetId = null;
            $currency = null;
            $noPricing = true;

            foreach ($rows as $key => $row) {
                if ('amount' === $row['resourceName']) {
                    unset($rows[$key]);
                }
            }
        }

        $this->view->rows = $rows;

        //the columns information is calculated from the field data in the GUI
        $this->view->metaData = [
            'fields' => $fieldConfig,
            'pricingPresetId' => $pricingPresetId,
            'currency' => $currency,
            'noPricing' => $noPricing,
        ];
    }

    public function exportAction()
    {
        $params = $this->getAllParams();

        /* @var $task editor_Models_Task */
        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($params['taskGuid']);

        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $fileName = $translate->_('Trefferanalyse') . ' - ' . $task->getTaskName();
        $taskNr = $task->getTaskNr();
        if (! empty($taskNr)) {
            $fileName = $fileName . ' - (' . $taskNr . ')';
        }

        switch ($params["type"]) {
            case "exportDebug":
                $this->setParam('notGrouped', true);
                $this->indexAction();

                return;

            case "exportExcel":

                // Get rows
                $rows = $this->entity->loadByBestMatchRate(
                    $params['taskGuid'],
                    unitType: $this->getParam('unitType')
                );

                // Do export and download result file
                ZfExtended_Factory
                    ::get(editor_Plugins_MatchAnalysis_Export_ExportExcel::class)
                        ->generateExcelAndProvideDownload($task, $rows, $fileName);

                break;
            case "exportXml":

                // Get rows
                $rows = $this->entity->loadByBestMatchRate(
                    $params['taskGuid'],
                    false,
                    $this->getParam('unitType')
                );

                // Get xml exported instance
                /* @var $exporter editor_Plugins_MatchAnalysis_Export_Xml */
                $exporter = ZfExtended_Factory::get(
                    editor_Plugins_MatchAnalysis_Export_Xml::class,
                    [$task, $this->entity->getFuzzyRanges()]
                );
                $exporter->setIsCharacterBased($this->getParam('unitType') === 'character');

                // Do export
                $x = $exporter->generateXML($rows, $params['taskGuid']);

                // Download result
                Header::sendDownload(
                    rawurlencode($fileName . ' ' . date('- Y-m-d') . '.xml'),
                    'text/xml',
                    'no-cache',
                    -1,
                    [
                        'Expires' => '0',
                    ]
                );
                echo $x->asXML();

                //with XML formatting:
                //                 $dom = dom_import_simplexml($x)->ownerDocument;
                //                 $dom->formatOutput = true;
                //                 echo $dom->saveXML();
                //                 break;

                break;
            default:
                throw new ZfExtended_NotFoundException('No analysis for given type found');
        }
    }
}
