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

declare(strict_types=1);

namespace Translate5\MaintenanceCli\Command\T5Memory;

use MittagQI\Translate5\ContentProtection\ConversionState;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;

class ScheduleConversionForAllCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 't5memory:conversion:scheduleForAll';

    /**
     * @throws \Zend_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $languageResourceRepository = LanguageResourceRepository::create();
        $tmConversionService = TmConversionService::create();

        $lrs = $languageResourceRepository->getAllByServiceName(\editor_Services_T5Memory_Service::NAME);

        $counter = 0;
        foreach ($lrs as $lr) {
            if (ConversionState::NotConverted !== $tmConversionService->getConversionState((int) $lr->getId())) {
                continue;
            }

            $counter++;
            $tmConversionService->scheduleConversion((int) $lr->getId());
        }

        if ($counter === 0) {
            $this->io->warning('No conversions scheduled.');
        } else {
            $this->io->success('Conversion scheduled for ' . $counter . ' language resources.');
        }

        return Command::SUCCESS;
    }
}
