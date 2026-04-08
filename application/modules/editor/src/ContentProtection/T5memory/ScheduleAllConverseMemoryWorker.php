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

declare(strict_types=1);

namespace MittagQI\Translate5\ContentProtection\T5memory;

use MittagQI\Translate5\ContentProtection\ConversionState;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use ZfExtended_Worker_Abstract;

class ScheduleAllConverseMemoryWorker extends ZfExtended_Worker_Abstract
{
    private readonly TmConversionService $tmConversionService;

    private readonly LanguageResourceRepository $languageResourceRepository;

    private readonly TaskTmRepository $taskTmRepository;

    public function __construct()
    {
        parent::__construct();
        $this->log = \Zend_Registry::get('logger')->cloneMe('editor.content-protection.t5memory.conversion');
        $this->tmConversionService = TmConversionService::create();
        $this->languageResourceRepository = LanguageResourceRepository::create();
        $this->taskTmRepository = TaskTmRepository::create();
    }

    protected function validateParameters(array $parameters): bool
    {
        return true;
    }

    protected function work(): bool
    {
        $lrs = $this->languageResourceRepository->getAllByServiceName(\editor_Services_T5Memory_Service::NAME);

        $taskTmIds = $this->taskTmRepository->getLanguageResourceIdsList();
        $taskTmIds = array_combine($taskTmIds, array_fill(0, count($taskTmIds), true));

        foreach ($lrs as $lr) {
            if (isset($taskTmIds[(int) $lr->getId()])) {
                continue;
            }

            if (ConversionState::NotConverted !== $this->tmConversionService->getConversionState((int) $lr->getId())) {
                continue;
            }

            $this->tmConversionService->scheduleConversion((int) $lr->getId());
        }

        return true;
    }
}
