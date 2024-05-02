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

namespace Translate5\MaintenanceCli\Command\T5Memory\Traits;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

trait FilteringByNameTrait
{
    abstract protected function getInput(): InputInterface;

    private function isFilteringByName(): bool
    {
        // TODO move OPTION_TM_NAME constant here after migrating to PHP 8.2
        return ! empty($this->getInput()->getOption(self::OPTION_TM_NAME));
    }

    protected function getTmUuid(InputInterface $input): ?string
    {
        $uuid = $input->getArgument(self::ARGUMENT_UUID);

        if (! empty($uuid)) {
            return $uuid;
        }

        $nameFilter = null;

        if ($this->isFilteringByName()) {
            $nameFilter = $this->input->getOption(self::OPTION_TM_NAME);
            $this->io->note('NAME FILTER: ' . $nameFilter);
        }

        $tmsList = $this->getLocalTmsList($nameFilter);

        if (empty($tmsList)) {
            if ($this->input->hasOption(self::OPTION_TM_NAME)) {
                $this->io->warning(
                    'There are no translation memories that match "'
                    . $this->input->getOption(self::OPTION_TM_NAME)
                    . '"'
                );
            } else {
                $this->io->warning('There are no translation memories in t5memory');
            }

            return null;
        }

        $askMemories = new ChoiceQuestion('Please choose a Memory:', array_values($tmsList), null);
        $tmName = $this->io->askQuestion($askMemories);

        return array_search($tmName, $tmsList);
    }
}
