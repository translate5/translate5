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

namespace MittagQI\Translate5\Plugins\TMMaintenance\Service;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager;
use MittagQI\Translate5\LanguageResource\Status;
use MittagQI\Translate5\Plugins\TMMaintenance\Exception\BatchDeleteException;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Worker_Abstract;

class TuBatchDeleteWorker extends ZfExtended_Worker_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.languageResource.tu-batch-delete');
    }

    public static function queueWorker(LanguageResource $languageResource, SearchDTO $searchDto): int
    {
        $worker = ZfExtended_Factory::get(self::class);

        if ($worker->init(parameters: [
            'languageResourceId' => $languageResource->getId(),
            'searchDto' => $searchDto,
        ])) {
            return $worker->queue();
        }

        throw new BatchDeleteException('E1688');
    }

    protected function validateParameters(array $parameters): bool
    {
        if (! array_key_exists('languageResourceId', $parameters)) {
            return false;
        }

        if (! array_key_exists('searchDto', $parameters)) {
            return false;
        }

        if (! $parameters['searchDto'] instanceof SearchDTO) {
            return false;
        }

        return true;
    }

    protected function work(): bool
    {
        $params = $this->workerModel->getParameters();
        $languageResource = LanguageResourceRepository::create()->get((int) $params['languageResourceId']);

        if (editor_Services_Manager::SERVICE_OPENTM2 !== $languageResource->getServiceType()) {
            $languageResource->setStatus(Status::AVAILABLE);
            $languageResource->save();

            return false;
        }

        TuBatchDeleteService::create()->deleteBatch($languageResource, $params['searchDto']);

        return true;
    }
}
