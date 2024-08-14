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

use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Task\Overview\SegmentDataProviderFactory;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\MqmTagFormatter;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\ReplaceInternalTagWithSpanFormatter;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\TermTagFormatter;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\TrackChangesTagFormatter;
use Zend_Config;
use Zend_Registry;
use Zend_View;

class ExportService
{
    public function __construct(
        private readonly SegmentDataProviderFactory $segmentDataProviderFactory,
        private readonly TaskRepository $taskRepository,
        private readonly Zend_Config $config,
        private readonly ReplaceInternalTagWithSpanFormatter $replaceInternalTagWithSpanFormatter,
        private readonly TermTagFormatter $termTagFormatter,
        private readonly MqmTagFormatter $mqmTagFormatter,
        private readonly TrackChangesTagFormatter $trackChangesTagFormatter,
    ) {
    }

    public static function create(): self
    {
        return new self(
            SegmentDataProviderFactory::create(),
            new TaskRepository(),
            Zend_Registry::get('config'),
            new ReplaceInternalTagWithSpanFormatter(),
            new TermTagFormatter(),
            MqmTagFormatter::create(),
            TrackChangesTagFormatter::create()
        );
    }

    public function asHtml(int $taskId): string
    {
        $task = $this->taskRepository->get($taskId);
        $segmentDataTable = $this->segmentDataProviderFactory
            ->getProvider([
                $this->replaceInternalTagWithSpanFormatter,
                $this->termTagFormatter,
                $this->mqmTagFormatter,
                $this->trackChangesTagFormatter,
            ])
            ->getSegmentDataTable($task);

        $view = new Zend_View();

        $view->addScriptPath(APPLICATION_PATH . '/modules/' . Zend_Registry::get('module') . '/views/scripts/task/');

        $view->assign('taskUrl', 'https://' . $this->config->runtimeOptions->server->name . '/editor/taskid/' . $task->getId());
        $view->assign('taskName', $task->getTaskName());
        $view->assign('segmentDataTable', $segmentDataTable);

        return $view->render('overview.phtml');
    }
}
