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

namespace Translate5\MaintenanceCli\Command;

use MittagQI\Translate5\ContentProtection\T5memory\RecalculateRulesHashWorker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Factory;
use ZfExtended_Models_Worker;

class ContentProtectionLanguageRulesHashesRefreshCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'content-protection:language-rules-hashes:refresh';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('Content protection: Refresh language rules hashes');

        $worker = ZfExtended_Factory::get(RecalculateRulesHashWorker::class);

        if (! $worker->init()) {
            return 1;
        }

        $worker->queue();
        $model = $worker->getModel();
        $model->setState(ZfExtended_Models_Worker::STATE_WAITING);
        $model->save();

        $workerInstance = \ZfExtended_Worker_Abstract::instanceByModel($model);
        if (! $workerInstance || ! $workerInstance->runQueued()) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
