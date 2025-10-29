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

namespace MittagQI\Translate5\LanguageResource;

use editor_Models_ConfigException;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Task as Task;
use editor_Services_Connector;
use editor_Services_Exceptions_NoService;
use editor_Services_Manager;
use MittagQI\Translate5\Integration\FileBasedInterface;
use MittagQI\Translate5\LanguageResource\Adapter\LanguagePairDTO;
use MittagQI\Translate5\LanguageResource\Adapter\LanguageResolutionType;
use ReflectionException;
use Zend_Exception;
use ZfExtended_Exception;
use ZfExtended_Models_Entity_NotFoundException;

class ConnectorForTaskProvider
{
    public function __construct(
        private readonly editor_Services_Manager $serviceManager
    ) {
    }

    public static function create(): self
    {
        return new self(new editor_Services_Manager());
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Services_Exceptions_NoService
     * @throws Zend_Exception
     * @throws editor_Models_ConfigException
     * @throws ZfExtended_Exception
     * @throws ReflectionException
     */
    public function provideForPivotPretrans(
        LanguageResource $languageResource,
        Task $task
    ): editor_Services_Connector|FileBasedInterface {
        $languagePair = new LanguagePairDTO(
            $task->getSourceLang(),
            $task->getRelaisLang(),
            //in pivot context in current code we use only given sublanguage + major → no other sub languages
            // This is state of the art at the moment, but might also be a bug so that all could be used
            LanguageResolutionType::IncludeMajor
        );

        return $this->serviceManager->getConnector(
            $languageResource,
            $languagePair,
            $task->getConfig(),
            (int) $task->getCustomerId()
        );
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     * @throws editor_Services_Exceptions_NoService
     * @throws ReflectionException
     * @throws editor_Models_ConfigException
     */
    public function provideForTargetPretrans(
        LanguageResource $languageResource,
        Task $task
    ): editor_Services_Connector|FileBasedInterface {
        $languagePair = new LanguagePairDTO(
            $task->getSourceLang(),
            $task->getTargetLang(),
            //in pre-translation context we have to use all sibling sublanguages to a given one + major language
            LanguageResolutionType::IncludeMajorAndSubLanguages,
        );

        return $this->serviceManager->getConnector(
            $languageResource,
            $languagePair,
            $task->getConfig(),
            (int) $task->getCustomerId()
        );
    }
}
