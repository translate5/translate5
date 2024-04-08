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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\ReimportSegments;

/**
 * Re-imports the segments of a task back into the chosen TM
 */
class editor_Models_LanguageResources_Worker extends editor_Models_Task_AbstractWorker
{
    private ReimportSegments $reimport;
    private LanguageResource $languageResource;

    protected function validateParameters($parameters = array()): bool
    {
        if (empty($parameters['languageResourceId'])) {
            return false;
        }

        return true;
    }

    public function work(): bool
    {
        $params = $this->workerModel->getParameters();

        $this->languageResource = \ZfExtended_Factory::get(LanguageResource::class);
        $this->languageResource->load($params['languageResourceId']);

        $this->reimport = new ReimportSegments($this->languageResource, $this->task);

        return $this->reimport->reimport($params);
    }

    /**
     * @param Throwable $workException
     *
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    protected function handleWorkerException(Throwable $workException): void
    {
        $this->reimport->reopenTask();
        $this->reimport->getLogger()->error(
            'E0000',
            'Task reimport in TM failed - please check log for reason and restart!'
        );

        if ($workException instanceof ZfExtended_ErrorCodeException) {
            $workException->addExtraData(['languageResource' => $this->languageResource]);
        }

        parent::handleWorkerException($workException);
    }
}
