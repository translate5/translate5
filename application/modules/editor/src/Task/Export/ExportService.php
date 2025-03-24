<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
             https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\Task\Export;

use editor_Models_Task;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Task\Overview\SegmentDataProviderFactory;
use MittagQI\Translate5\Task\Overview\SegmentDataTable;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\MqmTagFormatter;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\ReplaceInternalTagWithSpanFormatter;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\TermTagFormatter;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\TrackChangesTagFormatter;
use Zend_Config;
use Zend_Exception;
use Zend_Registry;
use Zend_View;
use Zend_View_Exception;
use ZfExtended_Zendoverwrites_Translate;

class ExportService
{
    private readonly string $assetsDirectory;

    private readonly string $viewsDirectory;

    public function __construct(
        private readonly SegmentDataProviderFactory $segmentDataProviderFactory,
        private readonly TaskRepository $taskRepository,
        private readonly Zend_Config $config,
        private readonly ReplaceInternalTagWithSpanFormatter $replaceInternalTagWithSpanFormatter,
        private readonly TermTagFormatter $termTagFormatter,
        private readonly MqmTagFormatter $mqmTagFormatter,
        private readonly TrackChangesTagFormatter $trackChangesTagFormatter,
        private readonly Zend_View $view,
        private readonly ZfExtended_Zendoverwrites_Translate $translate,
    ) {
        $this->assetsDirectory = APPLICATION_ROOT . '/public/modules/editor';
        $this->viewsDirectory = APPLICATION_PATH . '/modules/' . Zend_Registry::get('module') . '/views';
    }

    /**
     * @codeCoverageIgnore
     * @throws Zend_Exception
     * @throws \Zend_Log_Exception
     * @throws \Zend_Translate_Exception
     */
    public static function create(string $locale): self
    {
        $translate = new ZfExtended_Zendoverwrites_Translate($locale);

        return new self(
            SegmentDataProviderFactory::create($translate),
            TaskRepository::create(),
            Zend_Registry::get('config'),
            new ReplaceInternalTagWithSpanFormatter(),
            new TermTagFormatter(),
            MqmTagFormatter::create(),
            TrackChangesTagFormatter::create(),
            new Zend_View(),
            $translate,
        );
    }

    public function asHtml(int $taskId): string
    {
        $task = $this->taskRepository->get($taskId);
        $segmentDataTable = $this->getSegmentedData($task);

        $this->viewConfigure($task, $segmentDataTable);

        return $this->view->render('overview.phtml');
    }

    private function getAdditionalCss(): array
    {
        $css = $this->config->runtimeOptions->publicAdditions?->css;

        if (empty($css)) {
            return [];
        }

        return is_string($css) ? [$css] : $css->toArray();
    }

    /**
     * @throws Zend_View_Exception
     */
    private function addAssets($protocol): void
    {
        $stylesheets = [];
        foreach ($this->getAdditionalCss() as $css) {
            $stylesheets[] = $protocol . $this->config->runtimeOptions->server->name . "/" . $css;
        }
        $this->view->assign('stylesheets', $stylesheets);

        $this->view->headScript()->appendScript(file_get_contents($this->assetsDirectory . '/js/task/export/html.js'));
        $this->view->headStyle()->appendStyle(file_get_contents($this->assetsDirectory . '/css/task/export/html.css'));
    }

    private function getSegmentedData(editor_Models_Task $task): SegmentDataTable
    {
        return $this->segmentDataProviderFactory
            ->getProvider([
                $this->replaceInternalTagWithSpanFormatter,
                $this->termTagFormatter,
                $this->mqmTagFormatter,
                $this->trackChangesTagFormatter,
            ])
            ->getSegmentDataTable($task);
    }

    /**
     * @throws Zend_View_Exception
     * @throws Zend_Exception
     */
    private function viewConfigure(editor_Models_Task $task, SegmentDataTable $segmentDataTable): void
    {
        $this->view->getHelper('translate')->setTranslator($this->translate);

        $this->view->addScriptPath($this->viewsDirectory . '/scripts/task/');

        $protocol = $this->config->runtimeOptions->server->protocol;
        $this->addAssets($protocol);
        $this->view->assign(
            'taskUrl',
            $protocol . $this->config->runtimeOptions->server->name . '/editor/taskid/' . $task->getId()
        );
        $this->view->assign('taskName', $task->getTaskName());
        $this->view->assign('segmentDataTable', $segmentDataTable);
        $this->view->assign('defaultFilters', $this->config->runtimeOptions->exportService->defaultFilters);
        $this->view->addHelperPath($this->viewsDirectory . '/helpers/Task', 'View_Helper_');
    }
}
